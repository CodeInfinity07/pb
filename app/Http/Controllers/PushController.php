<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

class PushController extends Controller
{
    /**
     * Subscribe to push notifications
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string|max:500',
                'keys.p256dh' => 'required|string|max:255',
                'keys.auth' => 'required|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid subscription data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Check rate limiting (max 5 subscriptions per minute per user)
            $key = 'push_subscribe:' . $user->id;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many subscription attempts. Please try again later.'
                ], 429);
            }

            RateLimiter::hit($key, 60); // 60 seconds

            // Check if user already has too many subscriptions (max 10 per user)
            $existingCount = $user->pushSubscriptions()->count();
            if ($existingCount >= 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'Maximum number of push subscriptions reached (10). Please remove old devices first.'
                ], 400);
            }

            DB::beginTransaction();

            try {
                $subscription = PushSubscription::createFromSubscription(
                    $request->all(),
                    $user->id
                );

                // Update user's notification preferences if this is their first subscription
                if ($existingCount === 0) {
                    $user->update([
                        'push_notifications_enabled' => true,
                        'last_push_subscription_at' => now()
                    ]);
                }

                DB::commit();

                Log::info('Push subscription created', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'endpoint_domain' => parse_url($subscription->endpoint, PHP_URL_HOST)
                ]);

                // Send welcome notification for first subscription
                if ($existingCount === 0) {
                    $this->sendWelcomeNotification($user);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully subscribed to push notifications',
                    'subscription_id' => $subscription->id,
                    'total_subscriptions' => $user->pushSubscriptions()->count()
                ]);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Failed to create push subscription', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'endpoint' => $request->input('endpoint')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe to push notifications'
            ], 500);
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'endpoint' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid endpoint',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            $subscription = PushSubscription::where('endpoint', $request->endpoint)
                ->where('user_id', $user->id)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            $subscription->delete();

            // Update user preferences if this was their last subscription
            $remainingCount = $user->pushSubscriptions()->count();
            if ($remainingCount === 0) {
                $user->update(['push_notifications_enabled' => false]);
            }

            Log::info('Push subscription deleted', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'remaining_subscriptions' => $remainingCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from push notifications',
                'remaining_subscriptions' => $remainingCount
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete push subscription', [
                'error' => $e->getMessage(),
                'endpoint' => $request->input('endpoint'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unsubscribe from push notifications'
            ], 500);
        }
    }

    /**
     * Get VAPID public key for frontend
     */
    public function vapidPublicKey(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key');

        if (!$publicKey) {
            Log::error('VAPID public key not configured');
            return response()->json([
                'success' => false,
                'message' => 'Push notifications are not configured'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'public_key' => $publicKey
        ]);
    }

    /**
     * Get user's push subscriptions
     */
    public function subscriptions(): JsonResponse
    {
        try {
            $user = Auth::user();

            $subscriptions = $user->pushSubscriptions()
                ->select('id', 'endpoint', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'endpoint_domain' => parse_url($subscription->endpoint, PHP_URL_HOST),
                        'created_at' => $subscription->created_at->toISOString(),
                        'last_used' => $subscription->updated_at->toISOString(),
                        'browser' => $this->detectBrowser($subscription->endpoint)
                    ];
                });

            return response()->json([
                'success' => true,
                'subscriptions' => $subscriptions,
                'total' => $subscriptions->count(),
                'notifications_enabled' => $user->push_notifications_enabled ?? false
            ]);

        } catch (Exception $e) {
            Log::error('Failed to fetch push subscriptions', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscriptions'
            ], 500);
        }
    }

    /**
     * Send test notification
     */
    public function sendTestNotification(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            // Rate limiting for test notifications (max 3 per minute per user)
            $key = 'test_notification:' . $user->id;
            if (RateLimiter::tooManyAttempts($key, 3)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many test notifications. Please wait before sending another.'
                ], 429);
            }

            RateLimiter::hit($key, 60);

            // Check if user has push subscriptions
            if ($user->pushSubscriptions()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No push subscriptions found. Please enable notifications first.'
                ], 400);
            }

            // Validate optional test data
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:100',
                'body' => 'sometimes|string|max:300',
                'url' => 'sometimes|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid test notification data',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Send test notification
            $title = $request->input('title', 'Test Notification');
            $body = $request->input('body', 'This is a test push notification from your Laravel PWA!');
            $url = $request->input('url', '/dashboard');

            $user->notify(new PushNotification(
                $title,
                $body,
                $url,
                '/images/icons/192.png',
                '/images/icons/72.png',
                [
                    'test' => true,
                    'timestamp' => now()->toISOString(),
                    'user_id' => $user->id
                ]
            ));

            Log::info('Test notification sent', [
                'user_id' => $user->id,
                'title' => $title,
                'subscriptions_count' => $user->pushSubscriptions()->count()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'sent_to_devices' => $user->pushSubscriptions()->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification'
            ], 500);
        }
    }

    /**
     * Send notification to specific user (admin only)
     */
    public function sendToUser(Request $request): JsonResponse
    {
        try {
            // Check if current user has permission to send notifications
            if (!Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'title' => 'required|string|max:100',
                'body' => 'required|string|max:300',
                'url' => 'sometimes|string|max:255',
                'icon' => 'sometimes|string|max:255',
                'badge' => 'sometimes|string|max:255',
                'data' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid notification data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $targetUser = User::findOrFail($request->user_id);

            if ($targetUser->pushSubscriptions()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no push subscriptions'
                ], 400);
            }

            $targetUser->notify(new PushNotification(
                $request->title,
                $request->body,
                $request->input('url', '/'),
                $request->input('icon', '/images/icons/192.png'),
                $request->input('badge', '/images/icons/72.png'),
                $request->input('data', [])
            ));

            Log::info('Admin notification sent to user', [
                'admin_id' => Auth::id(),
                'target_user_id' => $targetUser->id,
                'title' => $request->title
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'sent_to_devices' => $targetUser->pushSubscriptions()->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send admin notification', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'target_user_id' => $request->input('user_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification'
            ], 500);
        }
    }

    /**
     * Send broadcast notification to all users (admin only)
     */
    public function broadcast(Request $request): JsonResponse
    {
        try {
            // Check if current user has permission to send broadcasts
            if (!Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Base validation rules
            $rules = [
                'title' => 'required|string|max:100',
                'body' => 'required|string|max:300',
                'url' => 'nullable|string|max:255',
                'icon' => 'nullable|string|max:255',
                'badge' => 'nullable|string|max:255',
                'data' => 'nullable|array',
                'target_type' => 'nullable|string|in:all,role,status,kyc,specific,active,recent',
            ];

            // Conditional validation based on target_type
            $targetType = $request->input('target_type', 'all');

            if ($targetType === 'role') {
                $rules['target_role'] = 'required|string|in:user,admin,support,moderator';
            }

            if ($targetType === 'status') {
                $rules['target_status'] = 'required|string|in:active,inactive,blocked';
            }

            if ($targetType === 'specific') {
                $rules['user_ids'] = 'required|array|min:1';
                $rules['user_ids.*'] = 'exists:users,id';
            }

            if (in_array($targetType, ['active', 'recent'])) {
                $rules['days_active'] = 'nullable|integer|min:1|max:365';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid broadcast data',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Build user query based on targeting
            $users = $this->getTargetedUsers($request);

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No users found matching the target criteria'
                ], 400);
            }

            // Send notification to all targeted users
            Notification::send($users, new PushNotification(
                $request->title,
                $request->body,
                $request->input('url', '/dashboard'),
                $request->input('icon', '/images/icons/192.png'),
                $request->input('badge', '/images/icons/72.png'),
                array_merge($request->input('data', []), [
                    'broadcast' => true,
                    'sent_at' => now()->toISOString(),
                    'sent_by' => Auth::id(),
                    'target_type' => $targetType
                ])
            ));

            Log::info('Broadcast notification sent', [
                'admin_id' => Auth::id(),
                'title' => $request->title,
                'target_users' => $users->count(),
                'target_type' => $targetType
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Broadcast notification sent successfully',
                'sent_to_users' => $users->count(),
                'total_devices' => $users->sum(fn($user) => $user->pushSubscriptions()->count())
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send broadcast notification', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id(),
                'title' => $request->input('title')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'push_notifications_enabled' => 'required|boolean',
                'notification_types' => 'sometimes|array',
                'notification_types.*' => 'string|in:orders,messages,promotions,news,reminders,security',
                'quiet_hours_enabled' => 'sometimes|boolean',
                'quiet_hours_start' => 'sometimes|date_format:H:i',
                'quiet_hours_end' => 'sometimes|date_format:H:i'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid preferences data',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            $preferences = [
                'push_notifications_enabled' => $request->push_notifications_enabled,
                'notification_types' => $request->input('notification_types', [
                    'orders',
                    'messages',
                    'security'
                ]),
                'quiet_hours_enabled' => $request->input('quiet_hours_enabled', false),
                'quiet_hours_start' => $request->input('quiet_hours_start'),
                'quiet_hours_end' => $request->input('quiet_hours_end'),
                'updated_at' => now()->toISOString()
            ];

            $user->update([
                'push_notifications_enabled' => $request->push_notifications_enabled,
                'notification_preferences' => $preferences
            ]);

            // If user disabled notifications, we should inform them about re-enabling
            if (!$request->push_notifications_enabled) {
                Log::info('User disabled push notifications', [
                    'user_id' => $user->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'preferences' => $preferences
            ]);

        } catch (Exception $e) {
            Log::error('Failed to update notification preferences', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences'
            ], 500);
        }
    }

    /**
     * Get user's notification preferences
     */
    public function getPreferences(): JsonResponse
    {
        try {
            $user = Auth::user();

            $preferences = $user->notification_preferences ?? [
                'push_notifications_enabled' => $user->push_notifications_enabled ?? false,
                'notification_types' => ['orders', 'messages', 'security'],
                'quiet_hours_enabled' => false,
                'quiet_hours_start' => null,
                'quiet_hours_end' => null
            ];

            return response()->json([
                'success' => true,
                'preferences' => $preferences,
                'subscriptions_count' => $user->pushSubscriptions()->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get notification preferences', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get preferences'
            ], 500);
        }
    }

    /**
     * Remove a specific subscription by ID
     */
    public function removeSubscription(Request $request, $subscriptionId): JsonResponse
    {
        try {
            $user = Auth::user();

            $subscription = $user->pushSubscriptions()
                ->where('id', $subscriptionId)
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }

            $subscription->delete();

            // Update user preferences if this was their last subscription
            $remainingCount = $user->pushSubscriptions()->count();
            if ($remainingCount === 0) {
                $user->update(['push_notifications_enabled' => false]);
            }

            Log::info('Push subscription removed by user', [
                'subscription_id' => $subscriptionId,
                'user_id' => $user->id,
                'remaining_subscriptions' => $remainingCount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription removed successfully',
                'remaining_subscriptions' => $remainingCount
            ]);

        } catch (Exception $e) {
            Log::error('Failed to remove push subscription', [
                'error' => $e->getMessage(),
                'subscription_id' => $subscriptionId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove subscription'
            ], 500);
        }
    }

    /**
     * Cleanup expired or invalid subscriptions
     */
    public function cleanup(): JsonResponse
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Remove subscriptions older than 6 months with no recent activity
            $expiredCount = PushSubscription::where('updated_at', '<', now()->subMonths(6))
                ->delete();

            // Remove duplicate subscriptions (same endpoint for same user)
            $duplicates = DB::select("
                SELECT id, user_id, endpoint, COUNT(*) as count
                FROM push_subscriptions 
                GROUP BY user_id, endpoint 
                HAVING COUNT(*) > 1
            ");

            $duplicatesRemoved = 0;
            foreach ($duplicates as $duplicate) {
                // Keep the most recent one, delete the rest
                $subscriptions = PushSubscription::where('user_id', $duplicate->user_id)
                    ->where('endpoint', $duplicate->endpoint)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $subscriptions->skip(1)->each(function ($subscription) use (&$duplicatesRemoved) {
                    $subscription->delete();
                    $duplicatesRemoved++;
                });
            }

            Log::info('Push subscriptions cleanup completed', [
                'expired_removed' => $expiredCount,
                'duplicates_removed' => $duplicatesRemoved,
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed successfully',
                'expired_removed' => $expiredCount,
                'duplicates_removed' => $duplicatesRemoved
            ]);

        } catch (Exception $e) {
            Log::error('Failed to cleanup push subscriptions', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup subscriptions'
            ], 500);
        }
    }

    /**
     * Get push notification statistics (admin only)
     */
    public function statistics(): JsonResponse
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $stats = [
                'total_subscriptions' => PushSubscription::count(),
                'active_users' => User::whereHas('pushSubscriptions')->count(),
                'subscriptions_today' => PushSubscription::whereDate('created_at', today())->count(),
                'subscriptions_this_week' => PushSubscription::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'subscriptions_this_month' => PushSubscription::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'browser_breakdown' => $this->getBrowserBreakdown(),
                'subscription_trends' => $this->getSubscriptionTrends()
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get push notification statistics', [
                'error' => $e->getMessage(),
                'admin_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Helper: Get targeted users based on filters
     */
    private function getTargetedUsers(Request $request)
    {
        $query = User::whereHas('pushSubscriptions', function($q) {
            $q->valid();
        });

        $targetType = $request->input('target_type', 'all');

        switch ($targetType) {
            case 'all':
                // All users with push subscriptions
                break;

            case 'role':
                if ($request->filled('target_role')) {
                    $query->where('role', $request->target_role);
                }
                break;

            case 'status':
                if ($request->filled('target_status')) {
                    $query->where('status', $request->target_status);
                }
                break;

            case 'kyc':
                $query->where(function($q) {
                    $q->whereHas('approvedKycVerification')
                      ->orWhereHas('profile', function($profileQuery) {
                          $profileQuery->where('kyc_status', 'verified');
                      });
                });
                break;

            case 'specific':
                if ($request->filled('user_ids')) {
                    $query->whereIn('id', $request->user_ids);
                }
                break;

            case 'active':
                $days = $request->input('days_active', 30);
                $query->where('last_login_at', '>=', now()->subDays($days));
                break;

            case 'recent':
                $days = $request->input('days_active', 7);
                $query->where('created_at', '>=', now()->subDays($days));
                break;
        }

        return $query->get();
    }

    /**
     * Private helper methods
     */
    private function sendWelcomeNotification(User $user): void
    {
        try {
            $user->notify(new PushNotification(
                'Welcome to ' . config('app.name') . '!',
                'You\'ll now receive important updates and notifications.',
                '/dashboard',
                '/images/icons/welcome.png',
                '/images/icons/72.png',
                ['welcome' => true, 'user_id' => $user->id]
            ));
        } catch (Exception $e) {
            Log::warning('Failed to send welcome notification', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    private function detectBrowser(string $endpoint): string
    {
        if (Str::contains($endpoint, 'fcm.googleapis.com')) {
            return 'Chrome/Edge';
        } elseif (Str::contains($endpoint, 'mozilla.com')) {
            return 'Firefox';
        } elseif (Str::contains($endpoint, 'apple.com')) {
            return 'Safari';
        }

        return 'Unknown';
    }

    private function getBrowserBreakdown(): array
    {
        $subscriptions = PushSubscription::select('endpoint')->get();

        $breakdown = [
            'Chrome/Edge' => 0,
            'Firefox' => 0,
            'Safari' => 0,
            'Unknown' => 0
        ];

        foreach ($subscriptions as $subscription) {
            $browser = $this->detectBrowser($subscription->endpoint);
            $breakdown[$browser]++;
        }

        return $breakdown;
    }

    private function getSubscriptionTrends(): array
    {
        $trends = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = PushSubscription::whereDate('created_at', $date)->count();

            $trends[] = [
                'date' => $date->format('Y-m-d'),
                'subscriptions' => $count
            ];
        }

        return $trends;
    }
}