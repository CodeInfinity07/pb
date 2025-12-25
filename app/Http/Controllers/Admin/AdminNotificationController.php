<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Exception;

class AdminNotificationController extends Controller
{
    /**
     * Display notification management dashboard.
     */
    public function index()
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        $data = [
            'existing_notifications' => $this->getExistingNotifications(),
            'notification_channels' => $this->getAvailableChannels(),
            'recent_notifications' => $this->getRecentNotifications(),
            'notification_stats' => $this->getNotificationStats(),
            'user' => $user,
        ];


        return view('admin.settings.notifications.index', $data);
    }

    /**
     * Show form to create a new notification.
     */
    public function create()
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        $data = [
            'notification_types' => $this->getNotificationTypes(),
            'available_channels' => $this->getAvailableChannels(),
            'user_models' => $this->getUserModels(),
            'user' => $user,
        ];

        return view('admin.settings.notifications.create', $data);
    }

    /**
     * Store a new notification class.
     */
    public function store(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'name' => 'required|string|max:255|regex:/^[A-Za-z][A-Za-z0-9]*$/',
            'description' => 'required|string|max:500',
            'channels' => 'required|array|min:1',
            'channels.*' => 'string|in:mail,database,broadcast,nexmo,slack',
            'mail_subject' => 'required_if:channels.*,mail|string|max:255',
            'mail_greeting' => 'nullable|string|max:255',
            'mail_content' => 'required_if:channels.*,mail|string',
            'mail_action_text' => 'nullable|string|max:100',
            'mail_action_url' => 'nullable|url',
            'database_title' => 'required_if:channels.*,database|string|max:255',
            'database_message' => 'required_if:channels.*,database|string',
            'variables' => 'nullable|string',
        ]);

        try {
            $className = Str::studly($validated['name']);
            $this->createNotificationClass($className, $validated);
            $this->storeNotificationSettings($className, $validated);

            return response()->json([
                'success' => true,
                'message' => "Notification '{$className}' created successfully!",
                'class_name' => $className,
                'file_path' => "app/Notifications/{$className}.php"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show notification details and allow editing.
     */
    public function show($notification)
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        $notificationPath = app_path("Notifications/{$notification}.php");

        if (!File::exists($notificationPath)) {
            abort(404, 'Notification not found');
        }

        $data = [
            'notification' => $notification,
            'notification_content' => File::get($notificationPath),
            'settings' => $this->getNotificationSettings($notification),
            'usage_stats' => $this->getNotificationUsageStats($notification),
            'user' => $user,
        ];

        return view('admin.settings.notifications.show', $data);
    }

    /**
     * Send test notification.
     */
    public function sendTest(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'notification_class' => 'required|string',
            'recipient_type' => 'required|string|in:user,email',
            'recipient_id' => 'required_if:recipient_type,user|integer|exists:users,id',
            'recipient_email' => 'required_if:recipient_type,email|email',
            'test_data' => 'nullable|json',
        ]);

        try {
            $notificationClass = "App\\Notifications\\{$validated['notification_class']}";

            if (!class_exists($notificationClass)) {
                throw new Exception('Notification class not found');
            }

            $testData = $validated['test_data'] ? json_decode($validated['test_data'], true) : [];
            $notification = new $notificationClass($testData);

            if ($validated['recipient_type'] === 'user') {
                $user = \App\Models\User::findOrFail($validated['recipient_id']);
                $user->notify($notification);
                $recipient = $user->email;
            } else {
                Notification::route('mail', $validated['recipient_email'])
                    ->notify($notification);
                $recipient = $validated['recipient_email'];
            }

            return response()->json([
                'success' => true,
                'message' => "Test notification sent successfully to {$recipient}!"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a notification class.
     */
    public function destroy($notification)
    {
        $this->checkAdminAccess();

        try {
            $notificationPath = app_path("Notifications/{$notification}.php");

            if (File::exists($notificationPath)) {
                File::delete($notificationPath);
            }

            // Remove from database settings
            DB::table('notification_settings')->where('class_name', $notification)->delete();

            return response()->json([
                'success' => true,
                'message' => "Notification '{$notification}' deleted successfully!"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification logs/history.
     */
    public function logs(Request $request)
    {
        $this->checkAdminAccess();

        $user = Auth::user();

        $query = DB::table('notifications')
            ->select('*')
            ->orderBy('created_at', 'desc');

        if ($request->notification_type) {
            $query->where('type', 'like', '%' . $request->notification_type . '%');
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $notifications = $query->paginate(50);

        return view('admin.settings.notifications.logs', compact('notifications', 'user'));
    }

    /**
     * Clear old notifications.
     */
    public function clearOld(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'days' => 'required|integer|min:1|max:365',
            'read_only' => 'boolean',
        ]);

        try {
            $query = DB::table('notifications')
                ->where('created_at', '<', now()->subDays($validated['days']));

            if ($validated['read_only']) {
                $query->whereNotNull('read_at');
            }

            $deletedCount = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} old notifications."
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check admin access.
     */
    private function checkAdminAccess()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Get existing notification classes.
     */
    private function getExistingNotifications(): array
    {
        $notificationPath = app_path('Notifications');
        $notifications = [];

        if (File::isDirectory($notificationPath)) {
            $files = File::files($notificationPath);

            foreach ($files as $file) {
                $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $notifications[] = [
                    'name' => $className,
                    'file' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'modified' => $file->getMTime(),
                    'settings' => $this->getNotificationSettings($className),
                ];
            }
        }

        return $notifications;
    }

    /**
     * Get available notification channels.
     */
    private function getAvailableChannels(): array
    {
        return [
            'mail' => [
                'name' => 'Email',
                'description' => 'Send notifications via email',
                'icon' => 'iconamoon:email-duotone'
            ],
            'database' => [
                'name' => 'Database',
                'description' => 'Store notifications in database',
                'icon' => 'material-symbols:database-sharp'
            ],
        ];
    }

    /**
     * Get recent notifications from database.
     */
    private function getRecentNotifications(): array
    {
        try {
            return DB::table('notifications')
                ->select('type', 'created_at', 'read_at', 'data')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get notification statistics.
     */
    private function getNotificationStats(): array
    {
        try {
            $total = DB::table('notifications')->count();
            $unread = DB::table('notifications')->whereNull('read_at')->count();
            $today = DB::table('notifications')->whereDate('created_at', today())->count();
            $thisWeek = DB::table('notifications')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

            return compact('total', 'unread', 'today', 'thisWeek');
        } catch (Exception $e) {
            return ['total' => 0, 'unread' => 0, 'today' => 0, 'thisWeek' => 0];
        }
    }

    /**
     * Get notification types for creation.
     */
    private function getNotificationTypes(): array
    {
        return [
            'user_welcome' => 'Welcome Message',
            'order_confirmation' => 'Order Confirmation',
            'payment_received' => 'Payment Received',
            'account_verified' => 'Account Verified',
            'password_reset' => 'Password Reset',
            'system_maintenance' => 'System Maintenance',
            'security_alert' => 'Security Alert',
            'subscription_reminder' => 'Subscription Reminder',
            'custom' => 'Custom Notification',
        ];
    }

    /**
     * Get user models for testing.
     */
    private function getUserModels(): array
    {
        try {
            return \App\Models\User::select('id', 'name', 'email')
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Create notification class file.
     */
    private function createNotificationClass(string $className, array $data)
    {
        $stub = $this->getNotificationStub();
        $content = $this->replaceStubPlaceholders($stub, $className, $data);

        $filePath = app_path("Notifications/{$className}.php");

        if (File::exists($filePath)) {
            throw new Exception("Notification class '{$className}' already exists");
        }

        File::put($filePath, $content);
    }

    /**
     * Get notification class stub.
     */
    private function getNotificationStub(): string
    {
        return '<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class {{CLASS_NAME}} extends Notification
{
    use Queueable;

    public $data;

    /**
     * Create a new notification instance.
     */
    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /**
     * Get the notification\'s delivery channels.
     */
    public function via($notifiable): array
    {
        return {{CHANNELS}};
    }

    {{MAIL_METHOD}}

    {{DATABASE_METHOD}}

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            //
        ];
    }
}';
    }

    /**
     * Replace stub placeholders.
     */
    private function replaceStubPlaceholders(string $stub, string $className, array $data): string
    {
        $channels = "['" . implode("', '", $data['channels']) . "']";

        $mailMethod = in_array('mail', $data['channels']) ? $this->generateMailMethod($data) : '';
        $databaseMethod = in_array('database', $data['channels']) ? $this->generateDatabaseMethod($data) : '';

        return str_replace([
            '{{CLASS_NAME}}',
            '{{CHANNELS}}',
            '{{MAIL_METHOD}}',
            '{{DATABASE_METHOD}}',
        ], [
            $className,
            $channels,
            $mailMethod,
            $databaseMethod,
        ], $stub);
    }

    /**
     * Generate mail method.
     */
    private function generateMailMethod(array $data): string
    {
        $actionLine = '';
        if (!empty($data['mail_action_text']) && !empty($data['mail_action_url'])) {
            $actionLine = "->action('{$data['mail_action_text']}', '{$data['mail_action_url']}')";
        }

        return "
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(\$notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('{$data['mail_subject']}')
                    ->greeting('{$data['mail_greeting']}')
                    ->line('{$data['mail_content']}')
                    {$actionLine}
                    ->line('Thank you for using our application!');
    }";
    }

    /**
     * Generate database method.
     */
    private function generateDatabaseMethod(array $data): string
    {
        return "
    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(\$notifiable): array
    {
        return [
            'title' => '{$data['database_title']}',
            'message' => '{$data['database_message']}',
            'data' => \$this->data,
        ];
    }";
    }

    /**
     * Store notification settings in database.
     */
    private function storeNotificationSettings(string $className, array $data)
    {
        DB::table('notification_settings')->updateOrInsert(
            ['class_name' => $className],
            [
                'description' => $data['description'],
                'channels' => json_encode($data['channels']),
                'settings' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get notification settings from database.
     */
    private function getNotificationSettings(string $className): ?array
    {
        $settings = DB::table('notification_settings')
            ->where('class_name', $className)
            ->first();

        return $settings ? [
            'description' => $settings->description,
            'channels' => json_decode($settings->channels, true),
            'settings' => json_decode($settings->settings, true),
        ] : null;
    }

    /**
     * Get notification usage statistics.
     */
    private function getNotificationUsageStats(string $notification): array
    {
        try {
            $type = "App\\Notifications\\{$notification}";

            $total = DB::table('notifications')->where('type', $type)->count();
            $thisMonth = DB::table('notifications')
                ->where('type', $type)
                ->whereMonth('created_at', now()->month)
                ->count();
            $unread = DB::table('notifications')
                ->where('type', $type)
                ->whereNull('read_at')
                ->count();

            return compact('total', 'thisMonth', 'unread');
        } catch (Exception $e) {
            return ['total' => 0, 'thisMonth' => 0, 'unread' => 0];
        }
    }

    public function markAsRead(Request $request, $id)
    {
        $this->checkAdminAccess();

        try {
            $notification = DB::table('notifications')->where('id', $id)->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            DB::table('notifications')
                ->where('id', $id)
                ->update(['read_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a specific notification log.
     */
    public function deleteLog(Request $request, $id)
    {
        $this->checkAdminAccess();

        try {
            $deleted = DB::table('notifications')->where('id', $id)->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export notification logs to CSV.
     */
    public function exportCsv(Request $request)
    {
        $this->checkAdminAccess();

        try {
            $query = DB::table('notifications')
                ->select('*')
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->notification_type) {
                $query->where('type', 'like', '%' . $request->notification_type . '%');
            }

            if ($request->date_from) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            if ($request->read_status === 'read') {
                $query->whereNotNull('read_at');
            } elseif ($request->read_status === 'unread') {
                $query->whereNull('read_at');
            }

            $notifications = $query->get();

            // Generate CSV content
            $csvContent = $this->generateCsvContent($notifications);

            $filename = 'notification_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle bulk actions on notifications.
     */
    public function bulkActions(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'action' => 'required|string|in:mark_read,delete,export',
            'notification_ids' => 'required|array|min:1',
            'notification_ids.*' => 'string',
        ]);

        try {
            $count = 0;

            switch ($validated['action']) {
                case 'mark_read':
                    $count = DB::table('notifications')
                        ->whereIn('id', $validated['notification_ids'])
                        ->whereNull('read_at')
                        ->update(['read_at' => now()]);
                    $message = "Marked {$count} notifications as read";
                    break;

                case 'delete':
                    $count = DB::table('notifications')
                        ->whereIn('id', $validated['notification_ids'])
                        ->delete();
                    $message = "Deleted {$count} notifications";
                    break;

                case 'export':
                    $notifications = DB::table('notifications')
                        ->whereIn('id', $validated['notification_ids'])
                        ->get();

                    $csvContent = $this->generateCsvContent($notifications);
                    $filename = 'selected_notifications_' . now()->format('Y-m-d_H-i-s') . '.csv';

                    return response($csvContent)
                        ->header('Content-Type', 'text/csv')
                        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification statistics for dashboard.
     */
    public function getStatistics(Request $request)
    {
        $this->checkAdminAccess();

        try {
            $stats = [
                'total_notifications' => DB::table('notifications')->count(),
                'unread_notifications' => DB::table('notifications')->whereNull('read_at')->count(),
                'notifications_today' => DB::table('notifications')->whereDate('created_at', today())->count(),
                'notifications_this_week' => DB::table('notifications')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'notifications_this_month' => DB::table('notifications')->whereMonth('created_at', now()->month)->count(),
                'most_used_types' => $this->getMostUsedNotificationTypes(),
                'recent_activity' => $this->getRecentNotificationActivity(),
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update notification settings.
     */
    public function updateSettings(Request $request, $notification)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'is_active' => 'boolean',
            'description' => 'string|max:500',
            'channels' => 'array',
            'settings' => 'array',
        ]);

        try {
            DB::table('notification_settings')
                ->where('class_name', $notification)
                ->update([
                    'is_active' => $validated['is_active'] ?? true,
                    'description' => $validated['description'] ?? null,
                    'channels' => json_encode($validated['channels'] ?? []),
                    'settings' => json_encode($validated['settings'] ?? []),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate CSV content from notifications.
     */
    private function generateCsvContent($notifications): string
    {
        $csvContent = "ID,Type,Notifiable Type,Notifiable ID,Data,Read At,Created At\n";

        foreach ($notifications as $notification) {
            $data = is_string($notification->data) ? $notification->data : json_encode($notification->data);
            $data = str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $data); // Escape CSV

            $csvContent .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $notification->id,
                class_basename($notification->type),
                $notification->notifiable_type,
                $notification->notifiable_id,
                $data,
                $notification->read_at ?? 'Not read',
                $notification->created_at
            );
        }

        return $csvContent;
    }

    /**
     * Get most used notification types.
     */
    private function getMostUsedNotificationTypes(): array
    {
        try {
            return DB::table('notifications')
                ->select('type', DB::raw('count(*) as count'))
                ->groupBy('type')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => class_basename($item->type),
                        'count' => $item->count
                    ];
                })
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get recent notification activity.
     */
    private function getRecentNotificationActivity(): array
    {
        try {
            return DB::table('notifications')
                ->select('type', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => class_basename($item->type),
                        'created_at' => $item->created_at,
                        'time_ago' => \Carbon\Carbon::parse($item->created_at)->diffForHumans()
                    ];
                })
                ->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Increment usage count for a notification type.
     */
    public static function incrementUsageCount(string $notificationClass): void
    {
        try {
            DB::table('notification_settings')
                ->where('class_name', $notificationClass)
                ->increment('usage_count');

            DB::table('notification_settings')
                ->where('class_name', $notificationClass)
                ->update(['last_used_at' => now()]);
        } catch (Exception $e) {
            // Silently fail to avoid breaking notification sending
            \Log::warning('Failed to increment notification usage count', [
                'class' => $notificationClass,
                'error' => $e->getMessage()
            ]);
        }
    }
}