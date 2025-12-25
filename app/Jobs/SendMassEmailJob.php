<?php

namespace App\Jobs;

use App\Models\MassEmailCampaign;
use App\Models\User;
use App\Services\DynamicMailConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Exception;

class SendMassEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 1;
    public $timeout = 120;

    protected MassEmailCampaign $campaign;
    protected User $recipient;

    /**
     * Create a new job instance.
     */
    public function __construct(MassEmailCampaign $campaign, User $recipient)
    {
        $this->campaign = $campaign;
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if campaign is still active
            if (!$this->campaign->isSending()) {
                Log::info('Skipping email send - campaign not active', [
                    'campaign_id' => $this->campaign->id,
                    'recipient_id' => $this->recipient->id,
                    'campaign_status' => $this->campaign->status
                ]);
                return;
            }

            // Apply database mail configuration
            DynamicMailConfigService::configure();

            // Process email content with user-specific placeholders
            $processedContent = $this->processEmailContent(
                $this->campaign->content, 
                $this->recipient
            );

            // Send the email
            Mail::raw($processedContent, function ($message) {
                $message->to($this->recipient->email, $this->recipient->full_name)
                       ->subject($this->campaign->subject)
                       ->from(
                           config('mail.from.address'), 
                           config('mail.from.name')
                       );
            });

            // Update campaign statistics
            $this->campaign->incrementSent();

            // Check if campaign is finished
            $this->campaign->checkAndComplete();

            Log::info('Mass email sent successfully', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'recipient_email' => $this->recipient->email
            ]);

        } catch (Exception $e) {
            // Increment failed count
            $this->campaign->incrementFailed();

            Log::error('Mass email send failed', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'recipient_email' => $this->recipient->email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Check if campaign is finished (including failed emails)
            $this->campaign->checkAndComplete();

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('Mass email job failed permanently', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'recipient_email' => $this->recipient->email,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Ensure failed count is updated
        $this->campaign->incrementFailed();
        
        // Check if campaign should be completed
        $this->campaign->checkAndComplete();
    }

    /**
     * Process email content with user-specific placeholders.
     */
    private function processEmailContent(string $content, User $user): string
    {
        $placeholders = [
            '{{first_name}}' => $user->first_name ?? '',
            '{{last_name}}' => $user->last_name ?? '',
            '{{full_name}}' => $user->full_name ?? '',
            '{{email}}' => $user->email ?? '',
            '{{username}}' => $user->username ?? '',
            '{{user_level}}' => $user->user_level ?? 0,
            '{{level_name}}' => $user->level_name ?? 'Starter',
            '{{total_invested}}' => number_format($user->total_invested ?? 0, 2),
            '{{total_earned}}' => number_format($user->total_earned ?? 0, 2),
            '{{referral_code}}' => $user->referral_code ?? '',
            '{{referral_link}}' => $user->referral_link ?? '',
            '{{status}}' => ucfirst($user->status ?? 'unknown'),
            '{{registration_date}}' => $user->created_at?->format('M d, Y') ?? '',
            '{{site_name}}' => config('app.name', 'Your Site'),
            '{{site_url}}' => config('app.url'),
            '{{current_date}}' => now()->format('F d, Y'),
            '{{current_year}}' => now()->year,
            '{{current_month}}' => now()->format('F'),
            '{{current_day}}' => now()->format('d'),
            '{{dashboard_url}}' => route('dashboard') ?? config('app.url'),
            '{{support_email}}' => config('mail.from.address'),
        ];

        // Add KYC status if profile exists
        if ($user->profile) {
            $placeholders['{{kyc_status}}'] = ucfirst($user->profile->kyc_status ?? 'not_submitted');
            $placeholders['{{country}}'] = $user->profile->country_name ?? '';
            $placeholders['{{city}}'] = $user->profile->city ?? '';
        }

        // Add balance information if available
        if ($user->accountBalance) {
            $placeholders['{{balance}}'] = number_format($user->accountBalance->balance ?? 0, 2);
            $placeholders['{{available_balance}}'] = number_format($user->available_balance ?? 0, 2);
        }

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }

    /**
     * Get the unique job identifier.
     */
    public function uniqueId(): string
    {
        return "mass_email_{$this->campaign->id}_{$this->recipient->id}";
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }
}