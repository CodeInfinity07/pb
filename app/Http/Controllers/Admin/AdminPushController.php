<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PushSubscription;
use App\Notifications\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use Exception;

class AdminPushController extends Controller
{
    /**
     * Display push notification management dashboard
     */
    public function index()
    {
        $user = \Auth::user();
        $stats = $this->getStatistics();
        
        return view('admin.push-notifications.index', [
            'stats' => $stats,
            'recentNotifications' => $this->getRecentNotifications(),
            'user' => $user
        ]);
    }

    /**
     * Get push notification statistics
     */
    public function getStatistics(): array
    {
        return PushSubscription::getStatistics();
    }

    /**
     * Get statistics API endpoint
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->getStatistics();
            
            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get push statistics', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Send notification to specific user
     */
    public function sendToUser(Request $request): JsonResponse
    {
        try {
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
                    'message' => 'User has no active push subscriptions'
                ], 400);
            }

            $targetUser->notify(new PushNotification(
                $request->title,
                $request->body,
                $request->input('url', '/dashboard'),
                $request->input('icon', '/images/icons/192.png'),
                $request->input('badge', '/images/icons/72.png'),
                array_merge($request->input('data', []), [
                    'sent_by_admin' => true,
                    'admin_id' => auth()->id(),
                    'sent_at' => now()->toISOString()
                ])
            ));

            // Log the notification
            $this->logNotification('single_user', $request->all(), $targetUser->id);

            Log::info('Admin notification sent to user', [
                'admin_id' => auth()->id(),
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
                'admin_id' => auth()->id(),
                'target_user_id' => $request->input('user_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Broadcast notification to multiple users
     */
    public function broadcast(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:100',
                'body' => 'required|string|max:300',
                'url' => 'sometimes|string|max:255',
                'icon' => 'sometimes|string|max:255',
                'badge' => 'sometimes|string|max:255',
                'data' => 'sometimes|array',
                'target_type' => 'required|string|in:all,role,status,kyc,specific,active,recent',
                'target_role' => 'sometimes|string|in:all,user,admin,support,moderator',  // FIXED
                'target_status' => 'sometimes|string|in:active,inactive,blocked',
                'user_ids' => 'sometimes|array',
                'user_ids.*' => 'exists:users,id',
                'days_active' => 'sometimes|integer|min:1|max:365'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid broadcast data',
                    'errors' => $validator->errors()
                ], 422);
            }

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
                    'sent_by_admin' => true,
                    'admin_id' => auth()->id(),
                    'sent_at' => now()->toISOString(),
                    'target_type' => $request->target_type
                ])
            ));

            // Log the notification
            $this->logNotification('broadcast', $request->all(), $users->pluck('id')->toArray());

            Log::info('Broadcast notification sent', [
                'admin_id' => auth()->id(),
                'title' => $request->title,
                'target_users' => $users->count(),
                'target_type' => $request->target_type,
                'total_devices' => $users->sum(fn($user) => $user->pushSubscriptions()->count())
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
                'admin_id' => auth()->id(),
                'title' => $request->input('title')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send broadcast notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recipient count for preview
     */
    public function getRecipientCount(Request $request): JsonResponse
    {
        try {
            $users = $this->getTargetedUsers($request);
            
            $subscriptionsCount = DB::table('push_subscriptions')
                ->whereIn('user_id', $users->pluck('id'))
                ->where(function($query) {
                    $query->whereNotNull('endpoint')
                          ->whereNotNull('public_key')
                          ->whereNotNull('auth_token');
                })
                ->count();

            return response()->json([
                'success' => true,
                'users_count' => $users->count(),
                'devices_count' => $subscriptionsCount,
                'preview' => $users->take(5)->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'devices' => $user->pushSubscriptions()->count()
                    ];
                })
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recipient count'
            ], 500);
        }
    }

    /**
     * Search users for targeting
     */
    public function searchUsers(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search', '');
            
            $users = User::where(function($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('username', 'like', "%{$search}%");
                })
                ->whereHas('pushSubscriptions')
                ->select('id', 'first_name', 'last_name', 'email', 'username')
                ->limit(20)
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'username' => $user->username,
                        'devices' => $user->pushSubscriptions()->count()
                    ];
                });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed'
            ], 500);
        }
    }

    /**
     * Cleanup expired or invalid subscriptions
     */
    public function cleanup(): JsonResponse
    {
        try {
            $results = PushSubscription::performMaintenance();

            Log::info('Push subscriptions cleanup completed by admin', [
                'admin_id' => auth()->id(),
                'results' => $results
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cleanup completed successfully',
                'results' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to cleanup push subscriptions', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup subscriptions'
            ], 500);
        }
    }

    /**
     * Get notification history
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        
        $history = DB::table('push_notification_logs')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        }

        return view('admin.push-notifications.history', compact('history'));
    }

    /**
     * Get browser distribution
     */
    public function browserDistribution(): JsonResponse
    {
        try {
            $distribution = PushSubscription::getBrowserDistribution();
            
            return response()->json([
                'success' => true,
                'distribution' => $distribution
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get browser distribution'
            ], 500);
        }
    }

    /**
     * Send test notification to admin
     */
    public function sendTest(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:100',
                'body' => 'sometimes|string|max:300'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = auth()->user();
            
            if ($admin->pushSubscriptions()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have no active push subscriptions. Please enable notifications first.'
                ], 400);
            }

            $admin->notify(new PushNotification(
                $request->input('title', 'Test Notification from Admin Panel'),
                $request->input('body', 'This is a test push notification. Everything is working correctly!'),
                '/admin/push',
                '/images/icons/192.png',
                '/images/icons/72.png',
                [
                    'test' => true,
                    'sent_at' => now()->toISOString()
                ]
            ));

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent to your devices',
                'devices_count' => $admin->pushSubscriptions()->count()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification'
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

        switch ($request->target_type) {
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
     * Helper: Log notification for audit trail
     */
    private function logNotification(string $type, array $data, $recipients)
    {
        try {
            DB::table('push_notification_logs')->insert([
                'admin_id' => auth()->id(),
                'type' => $type,
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'recipients' => is_array($recipients) ? json_encode($recipients) : json_encode([$recipients]),
                'recipients_count' => is_array($recipients) ? count($recipients) : 1,
                'target_type' => $data['target_type'] ?? null,
                'metadata' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            Log::warning('Failed to log push notification', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper: Get recent notifications
     */
    private function getRecentNotifications()
    {
        try {
            return DB::table('push_notification_logs')
                ->join('users', 'push_notification_logs.admin_id', '=', 'users.id')
                ->select(
                    'push_notification_logs.*',
                    'users.first_name',
                    'users.last_name'
                )
                ->orderBy('push_notification_logs.created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (Exception $e) {
            return collect();
        }
    }
}