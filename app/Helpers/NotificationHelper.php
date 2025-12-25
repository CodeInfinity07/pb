<?php

// Create: app/Helpers/NotificationHelper.php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class NotificationHelper
{
    /**
     * Get all available notification classes.
     */
    public static function getAvailableNotifications(): array
    {
        $notifications = [];
        $notificationPath = app_path('Notifications');
        
        if (is_dir($notificationPath)) {
            $files = scandir($notificationPath);
            
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $className = pathinfo($file, PATHINFO_FILENAME);
                    $notifications[$className] = [
                        'class' => $className,
                        'file' => $file,
                        'namespace' => "App\\Notifications\\{$className}",
                    ];
                }
            }
        }
        
        return $notifications;
    }

    /**
     * Check if a notification class exists.
     */
    public static function notificationExists(string $className): bool
    {
        $filePath = app_path("Notifications/{$className}.php");
        return file_exists($filePath) && class_exists("App\\Notifications\\{$className}");
    }

    /**
     * Get notification statistics.
     */
    public static function getNotificationStats(): array
    {
        try {
            return [
                'total' => DB::table('notifications')->count(),
                'unread' => DB::table('notifications')->whereNull('read_at')->count(),
                'today' => DB::table('notifications')->whereDate('created_at', today())->count(),
                'this_week' => DB::table('notifications')->whereBetween('created_at', [
                    now()->startOfWeek(), 
                    now()->endOfWeek()
                ])->count(),
                'this_month' => DB::table('notifications')->whereMonth('created_at', now()->month)->count(),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get notification statistics', ['error' => $e->getMessage()]);
            return ['total' => 0, 'unread' => 0, 'today' => 0, 'this_week' => 0, 'this_month' => 0];
        }
    }

    /**
     * Clean up old notifications.
     */
    public static function cleanupOldNotifications(int $days = 30, bool $readOnly = true): int
    {
        try {
            $query = DB::table('notifications')
                       ->where('created_at', '<', now()->subDays($days));

            if ($readOnly) {
                $query->whereNotNull('read_at');
            }

            return $query->delete();
        } catch (Exception $e) {
            Log::error('Failed to cleanup old notifications', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Send notification safely with error handling.
     */
    public static function sendNotificationSafely($notifiable, $notification): bool
    {
        try {
            $notifiable->notify($notification);
            
            // Track usage if notification settings exist
            if (method_exists($notification, 'getNotificationClassName')) {
                static::trackNotificationUsage($notification->getNotificationClassName());
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to send notification', [
                'notification' => get_class($notification),
                'notifiable' => get_class($notifiable),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Track notification usage.
     */
    public static function trackNotificationUsage(string $className): void
    {
        try {
            DB::table('notification_settings')
              ->where('class_name', $className)
              ->increment('usage_count');
              
            DB::table('notification_settings')
              ->where('class_name', $className)
              ->update(['last_used_at' => now()]);
        } catch (Exception $e) {
            // Silent fail to avoid breaking notification flow
            Log::debug('Failed to track notification usage', [
                'class' => $className,
                'error' => $e->getMessage()
            ]);
        }
    }
}