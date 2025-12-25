<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;
use Illuminate\Support\Facades\Log;

class UnifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $emailTemplate;
    protected $data;
    protected $pushTitle;
    protected $pushBody;
    protected $pushUrl;
    protected $pushIcon;
    protected $type;
    protected $channels;

    /**
     * Create notification instance
     * 
     * @param string $emailTemplateSlug - Slug of email template to use
     * @param array $data - Data to populate template
     * @param string|null $pushTitle - Override push notification title
     * @param string|null $pushBody - Override push notification body
     * @param string $pushUrl - URL for push notification action
     * @param string $pushIcon - Icon for push notification
     * @param array $channels - Channels to use ['mail', 'push', 'database']
     */
    public function __construct(
        string $emailTemplateSlug,
        array $data = [],
        ?string $pushTitle = null,
        ?string $pushBody = null,
        string $pushUrl = '/dashboard',
        string $pushIcon = '/images/icons/192.png',
        array $channels = ['mail', 'push', 'database']
    ) {
        $this->emailTemplate = EmailTemplate::getBySlug($emailTemplateSlug);
        $this->data = $data;
        $this->pushUrl = $pushUrl;
        $this->pushIcon = $pushIcon;
        $this->channels = $channels;
        $this->type = $this->emailTemplate?->category ?? 'general';

        if ($this->emailTemplate) {
            $rendered = $this->emailTemplate->render($data);
            $this->pushTitle = $pushTitle ?? $rendered['subject'];
            $this->pushBody = $pushBody ?? $this->truncate($rendered['body'], 150);
        } else {
            $this->pushTitle = $pushTitle ?? 'Notification';
            $this->pushBody = $pushBody ?? 'You have a new notification';

            Log::warning('Email template not found', [
                'slug' => $emailTemplateSlug,
                'using_fallback' => true
            ]);
        }
    }

    /**
     * Get notification channels
     */
    public function via($notifiable): array
    {
        $activeChannels = ['database']; // Always save to database

        // Check if user has verified email and email is requested
        if (in_array('mail', $this->channels) && $notifiable->hasVerifiedEmail()) {
            $activeChannels[] = 'mail';
        }

        // Check if user has push enabled and push is requested
        if (
            in_array('push', $this->channels) &&
            $notifiable->push_notifications_enabled &&
            $notifiable->pushSubscriptions()->valid()->exists()
        ) {
            $activeChannels[] = WebPushChannel::class;
        }

        Log::info('Notification channels determined', [
            'user_id' => $notifiable->id,
            'requested_channels' => $this->channels,
            'active_channels' => $activeChannels,
            'template' => $this->emailTemplate?->slug ?? 'none'
        ]);

        return $activeChannels;
    }

    /**
     * Get mail representation
     */
    public function toMail($notifiable): MailMessage
    {
        if (!$this->emailTemplate) {
            return $this->getFallbackMail($notifiable);
        }

        $rendered = $this->emailTemplate->render($this->data);

        $mail = (new MailMessage)
            ->subject($rendered['subject'])
            ->greeting('Hello ' . $notifiable->first_name . '!');

        // Split body into lines
        $lines = explode("\n", $rendered['body']);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $mail->line($line);
            }
        }

        // Add action button if URL is in data
        if (!empty($this->data['action_url']) && !empty($this->data['action_text'])) {
            $mail->action($this->data['action_text'], $this->data['action_url']);
        }

        return $mail->line('Thank you for using ' . config('app.name') . '!');
    }

    /**
     * Get web push representation
     */
    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title($this->pushTitle)
            ->body($this->pushBody)
            ->icon($this->pushIcon)
            ->badge('/images/icons/72.png')
            ->action('View', $this->pushUrl)
            ->data([
                'type' => $this->type,
                'template' => $this->emailTemplate?->slug ?? 'none',
                'url' => $this->pushUrl,
                'notification_id' => $notification->id ?? null,
                'timestamp' => now()->toISOString()
            ]);
    }

    /**
     * Get database/array representation
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => $this->pushTitle,
            'body' => $this->pushBody,
            'type' => $this->type,
            'action_url' => $this->pushUrl,
            'icon' => $this->pushIcon,
            'template' => $this->emailTemplate?->slug ?? null,
            'data' => $this->data,
            'read_at' => null
        ];
    }

    /**
     * Get fallback mail when template doesn't exist
     */
    private function getFallbackMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->pushTitle)
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line($this->pushBody)
            ->line('Please check your dashboard for more details.')
            ->action('Go to Dashboard', url('/dashboard'));
    }

    /**
     * Truncate text for push notification
     */
    private function truncate(string $text, int $length): string
    {
        // Remove newlines and extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }

    /**
     * Handle failed notification
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Unified notification failed', [
            'template' => $this->emailTemplate?->slug ?? 'none',
            'exception' => $exception->getMessage(),
            'channels' => $this->channels
        ]);
    }

    // =============================================================================
    // STATIC FACTORY METHODS - Quick notification creation
    // =============================================================================

    /**
     * Send welcome email notification
     */
    public static function welcome($user): self
    {
        return new self(
            'welcome-email',
            [
                'user_name' => $user->full_name,
                'user_email' => $user->email,
                'user_username' => $user->username
            ],
            'Welcome to ' . config('app.name') . '! 🎉',
            'Welcome! Get started with your account.',
            '/dashboard'
        );
    }

    /**
     * Send deposit confirmation
     */
    public static function depositConfirmed($amount, $currency, $transactionId): self
    {
        return new self(
            'deposit-confirmation',
            [
                'amount' => number_format($amount, 2),
                'currency' => $currency,
                'transaction_id' => $transactionId,
                'action_url' => route('transactions.index'),
                'action_text' => 'View Transaction'
            ],
            'Deposit Confirmed 💰',
            "Your deposit of {$amount} {$currency} has been confirmed.",
            '/transactions'
        );
    }

    /**
     * Send withdrawal approved
     */
    public static function withdrawalApproved($amount, $currency, $transactionId, $walletAddress): self
    {
        return new self(
            'withdrawal-approved',
            [
                'amount' => number_format($amount, 2),
                'currency' => $currency,
                'transaction_id' => $transactionId,
                'wallet_address' => $walletAddress,
                'action_url' => route('transactions.index'),
                'action_text' => 'View Transaction'
            ],
            'Withdrawal Approved 💸',
            "Your withdrawal of {$amount} {$currency} has been processed.",
            '/transactions'
        );
    }

    /**
     * Send investment created notification
     */
    public static function investmentCreated($plan, $amount, $duration, $returnPercentage): self
    {
        return new self(
            'investment-created',
            [
                'plan_name' => $plan,
                'amount' => number_format($amount, 2),
                'duration' => $duration,
                'return_percentage' => $returnPercentage,
                'action_url' => route('bot.index'),
                'action_text' => 'View Investment'
            ],
            'Investment Activated 📈',
            "Your investment in {$plan} has been activated.",
            '/bot'
        );
    }

    /**
     * Send investment return paid
     */
    public static function investmentReturnPaid($plan, $amount, $totalEarned): self
    {
        return new self(
            'investment-return-paid',
            [
                'plan_name' => $plan,
                'amount' => number_format($amount, 2),
                'total_earned' => number_format($totalEarned, 2),
                'action_url' => route('bot.index'),
                'action_text' => 'View Investment'
            ],
            'Investment Return Received 💰',
            "You received \${$amount} from your {$plan} investment.",
            '/bot'
        );
    }

    /**
     * Send KYC approved notification
     */
    public static function kycApproved(): self
    {
        return new self(
            'kyc-approved',
            [
                'action_url' => route('dashboard'),
                'action_text' => 'Go to Dashboard'
            ],
            'KYC Verification Approved ✅',
            'Your identity verification has been approved!',
            '/kyc'
        );
    }

    /**
     * Send KYC rejected notification
     */
    public static function kycRejected($reason): self
    {
        return new self(
            'kyc-rejected',
            [
                'rejection_reason' => $reason,
                'kyc_url' => route('kyc.index'),
                'action_url' => route('kyc.index'),
                'action_text' => 'Resubmit KYC'
            ],
            'KYC Verification Requires Attention ❌',
            'Your KYC verification was declined. Please resubmit.',
            '/kyc'
        );
    }

    /**
     * Send new referral notification
     */
    public static function newReferral($referralName, $totalReferrals, $referralLink): self
    {
        return new self(
            'new-referral',
            [
                'referral_name' => $referralName,
                'total_referrals' => $totalReferrals,
                'referral_link' => $referralLink,
                'action_url' => route('referrals.index'),
                'action_text' => 'View Referrals'
            ],
            'New Referral Joined! 🤝',
            "{$referralName} joined using your referral link.",
            '/referrals'
        );
    }

    /**
     * Send commission earned notification
     */
    public static function commissionEarned($amount, $referralName): self
    {
        return new self(
            'commission-earned',
            [
                'amount' => number_format($amount, 2),
                'referral_name' => $referralName,
                'action_url' => route('referrals.index'),
                'action_text' => 'View Earnings'
            ],
            'Commission Earned 🎁',
            "You earned \${$amount} from {$referralName}.",
            '/referrals'
        );
    }

    /**
     * Send support ticket reply notification
     */
    public static function supportTicketReply($ticketNumber, $ticketSubject, $ticketUrl): self
    {
        return new self(
            'support-ticket-reply',
            [
                'ticket_number' => $ticketNumber,
                'ticket_subject' => $ticketSubject,
                'ticket_url' => $ticketUrl,
                'ticket_status' => 'Open',
                'action_url' => $ticketUrl,
                'action_text' => 'View Ticket'
            ],
            'New Reply on Support Ticket 💬',
            "You have a new reply on ticket #{$ticketNumber}.",
            $ticketUrl
        );
    }

    /**
     * Send password changed notification
     */
    public static function passwordChanged(): self
    {
        return new self(
            'password-changed',
            [
                'action_url' => route('user.profile'),
                'action_text' => 'Review Security'
            ],
            'Password Changed 🔐',
            'Your password was recently changed.',
            '/user/profile',
            '/images/icons/security.png',
            ['mail', 'push', 'database'] // Send via all channels for security
        );
    }
}