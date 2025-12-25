<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class PushNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $url;
    protected string $icon;
    protected string $badge;
    protected array $data;
    protected array $options;
    protected array $actions;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title, 
        string $body, 
        string $url = '/', 
        string $icon = null,
        string $badge = null,
        array $data = [],
        array $options = [],
        array $actions = []
    ) {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
        $this->icon = $icon ?? config('webpush.defaults.icon', '/images/icons/192.png');
        $this->badge = $badge ?? config('webpush.defaults.badge', '/images/icons/72.png');
        $this->data = $data;
        $this->options = array_merge([
            'TTL' => config('webpush.defaults.ttl', 3600),
            'urgency' => config('webpush.defaults.urgency', 'normal'),
            'topic' => config('webpush.defaults.topic', 'general'),
        ], $options);
        $this->actions = $actions;

        // Set queue delay if specified
        if (isset($options['delay'])) {
            $this->delay($options['delay']);
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        // Only send if user has push subscriptions and notifications enabled
        if (!$notifiable->pushSubscriptions()->exists() || 
            !($notifiable->push_notifications_enabled ?? true)) {
            Log::info('Skipping push notification - no subscriptions or disabled', [
                'user_id' => $notifiable->id,
                'has_subscriptions' => $notifiable->pushSubscriptions()->exists(),
                'notifications_enabled' => $notifiable->push_notifications_enabled ?? false
            ]);
            return [];
        }

        return [WebPushChannel::class];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $message = (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->badge($this->badge)
            ->data([
                'url' => $this->url,
                'notification_id' => $notification->id ?? null,
                'user_id' => $notifiable->id,
                'timestamp' => now()->toISOString(),
                ...$this->data
            ])
            ->options($this->options);

        // Add custom actions if provided
        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                $message->action($action['action'], $action['title'], $action['icon'] ?? null);
            }
        } else {
            // Add default actions
            $message->action('View', 'view')
                   ->action('Dismiss', 'dismiss');
        }

        // Add image if provided
        if (isset($this->data['image'])) {
            $message->image($this->data['image']);
        }

        Log::info('Push notification prepared', [
            'user_id' => $notifiable->id,
            'title' => $this->title,
            'subscriptions_count' => $notifiable->pushSubscriptions()->count(),
            'options' => $this->options
        ]);

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'data' => $this->data,
            'options' => $this->options,
            'user_id' => $notifiable->id,
            'sent_at' => now()->toISOString(),
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Push notification failed', [
            'title' => $this->title,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC FACTORY METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create a welcome notification.
     */
    public static function welcome(string $appName = null): self
    {
        $appName = $appName ?? config('app.name', 'App');
        
        return new self(
            title: "Welcome to {$appName}! 🎉",
            body: "You'll now receive important updates and notifications.",
            url: '/dashboard',
            data: [
                'type' => 'welcome',
                'priority' => 'high'
            ],
            actions: [
                ['action' => 'view', 'title' => 'Go to Dashboard'],
                ['action' => 'settings', 'title' => 'Notification Settings']
            ]
        );
    }

    /**
     * Create a transaction notification.
     */
    public static function transaction(
        string $type, 
        float $amount, 
        string $currency = 'USD',
        string $status = 'completed'
    ): self {
        $emoji = match ($type) {
            'deposit' => '💰',
            'withdrawal' => '💸',
            'investment' => '📈',
            'commission' => '🎁',
            'bonus' => '🎉',
            default => '💳'
        };

        $title = match ($type) {
            'deposit' => "Deposit {$emoji}",
            'withdrawal' => "Withdrawal {$emoji}",
            'investment' => "Investment {$emoji}",
            'commission' => "Commission Earned {$emoji}",
            'bonus' => "Bonus Received {$emoji}",
            default => "Transaction {$emoji}"
        };

        $statusText = match ($status) {
            'completed' => 'completed successfully',
            'pending' => 'is being processed',
            'failed' => 'has failed',
            default => "status: {$status}"
        };

        return new self(
            title: $title,
            body: "Your {$type} of {$currency} {$amount} {$statusText}.",
            url: '/transactions',
            data: [
                'type' => 'transaction',
                'transaction_type' => $type,
                'amount' => $amount,
                'currency' => $currency,
                'status' => $status,
                'priority' => $status === 'failed' ? 'high' : 'normal'
            ]
        );
    }

    /**
     * Create an investment return notification.
     */
    public static function investmentReturn(float $amount, string $planName): self
    {
        return new self(
            title: "Investment Return 📈",
            body: "You've received ${$amount} return from your {$planName} investment!",
            url: '/bot',
            data: [
                'type' => 'investment_return',
                'amount' => $amount,
                'plan' => $planName,
                'priority' => 'normal'
            ]
        );
    }

    /**
     * Create a referral notification.
     */
    public static function referral(string $referralName, float $commission = null): self
    {
        $body = $commission 
            ? "New referral {$referralName} joined! You earned \${$commission} commission."
            : "New referral {$referralName} has joined your team!";

        return new self(
            title: "New Referral 🤝",
            body: $body,
            url: '/referrals',
            data: [
                'type' => 'referral',
                'referral_name' => $referralName,
                'commission' => $commission,
                'priority' => 'normal'
            ]
        );
    }

    /**
     * Create a level upgrade notification.
     */
    public static function levelUpgrade(int $oldLevel, int $newLevel, string $levelName): self
    {
        return new self(
            title: "Level Up! 🚀",
            body: "Congratulations! You've been upgraded from Level {$oldLevel} to Level {$newLevel} ({$levelName})!",
            url: '/dashboard',
            data: [
                'type' => 'level_upgrade',
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'level_name' => $levelName,
                'priority' => 'high'
            ],
            options: [
                'requireInteraction' => true
            ]
        );
    }

    /**
     * Create a KYC status notification.
     */
    public static function kycStatus(string $status): self
    {
        $emoji = match ($status) {
            'approved', 'verified' => '✅',
            'rejected', 'declined' => '❌',
            'pending' => '⏳',
            default => 'ℹ️'
        };

        $title = "KYC Update {$emoji}";
        $body = match ($status) {
            'approved', 'verified' => 'Your identity verification has been approved! You can now access all features.',
            'rejected', 'declined' => 'Your identity verification was declined. Please resubmit your documents.',
            'pending' => 'Your identity verification is being reviewed. We\'ll notify you once complete.',
            default => "Your KYC status has been updated to: {$status}"
        };

        return new self(
            title: $title,
            body: $body,
            url: '/kyc',
            data: [
                'type' => 'kyc_status',
                'status' => $status,
                'priority' => 'high'
            ]
        );
    }

    /**
     * Create a support ticket notification.
     */
    public static function supportTicket(string $action, string $ticketId, string $subject = null): self
    {
        $title = match ($action) {
            'created' => 'Support Ticket Created 🎫',
            'replied' => 'New Reply on Ticket 💬',
            'resolved' => 'Ticket Resolved ✅',
            'closed' => 'Ticket Closed 🔒',
            default => 'Support Ticket Update 🎫'
        };

        $body = match ($action) {
            'created' => "Your support ticket #{$ticketId} has been created.",
            'replied' => "You have a new reply on ticket #{$ticketId}.",
            'resolved' => "Your support ticket #{$ticketId} has been resolved.",
            'closed' => "Your support ticket #{$ticketId} has been closed.",
            default => "Your support ticket #{$ticketId} has been updated."
        };

        if ($subject) {
            $body .= " Subject: {$subject}";
        }

        return new self(
            title: $title,
            body: $body,
            url: "/support/{$ticketId}",
            data: [
                'type' => 'support_ticket',
                'action' => $action,
                'ticket_id' => $ticketId,
                'subject' => $subject,
                'priority' => 'normal'
            ]
        );
    }

    /**
     * Create a maintenance notification.
     */
    public static function maintenance(string $action, \DateTime $scheduledTime = null): self
    {
        $title = match ($action) {
            'scheduled' => 'Maintenance Scheduled 🔧',
            'starting' => 'Maintenance Starting 🚧',
            'completed' => 'Maintenance Complete ✅',
            default => 'System Maintenance 🔧'
        };

        $body = match ($action) {
            'scheduled' => $scheduledTime 
                ? "System maintenance is scheduled for " . $scheduledTime->format('M j, Y g:i A')
                : "System maintenance has been scheduled.",
            'starting' => "System maintenance is starting now. Some features may be temporarily unavailable.",
            'completed' => "System maintenance has been completed. All features are now available.",
            default => "System maintenance notification."
        };

        return new self(
            title: $title,
            body: $body,
            url: '/dashboard',
            data: [
                'type' => 'maintenance',
                'action' => $action,
                'scheduled_time' => $scheduledTime?->toISOString(),
                'priority' => 'high'
            ]
        );
    }

    /**
     * Create a custom announcement notification.
     */
    public static function announcement(string $title, string $message, string $url = '/dashboard'): self
    {
        return new self(
            title: "📢 " . $title,
            body: $message,
            url: $url,
            data: [
                'type' => 'announcement',
                'priority' => 'normal'
            ],
            options: [
                'requireInteraction' => false
            ]
        );
    }
}