<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MassEmailCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'content',
        'recipient_groups',
        'specific_users',
        'total_recipients',
        'emails_sent',
        'emails_failed',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'created_by',
        'cancelled_by',
        'error_message',
        'metadata'
    ];

    protected function casts(): array
    {
        return [
            'recipient_groups' => 'array',
            'specific_users' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'emails_sent' => 'integer',
            'emails_failed' => 'integer',
            'total_recipients' => 'integer',
            'metadata' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user who created this campaign.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created this campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who cancelled this campaign.
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_recipients <= 0) {
            return 0;
        }

        return round(($this->emails_sent / $this->total_recipients) * 100, 2);
    }

    /**
     * Get success rate percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        $totalProcessed = $this->emails_sent + $this->emails_failed;
        
        if ($totalProcessed <= 0) {
            return 0;
        }

        return round(($this->emails_sent / $totalProcessed) * 100, 2);
    }

    /**
     * Get remaining emails count.
     */
    public function getRemainingEmailsAttribute(): int
    {
        return max(0, $this->total_recipients - $this->emails_sent - $this->emails_failed);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-secondary',
            'scheduled' => 'bg-info',
            'sending' => 'bg-warning',
            'completed' => 'bg-success',
            'cancelled' => 'bg-danger',
            'failed' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'scheduled' => 'Scheduled',
            'sending' => 'Sending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'failed' => 'Failed',
            default => 'Unknown'
        };
    }

    /**
     * Get recipient groups display.
     */
    public function getRecipientGroupsDisplayAttribute(): string
    {
        if (empty($this->recipient_groups)) {
            return 'None';
        }

        $groups = [];
        foreach ($this->recipient_groups as $group) {
            $groups[] = match($group) {
                'all' => 'All Users',
                'active' => 'Active Users',
                'inactive' => 'Inactive Users',
                'blocked' => 'Blocked Users',
                'kyc_verified' => 'KYC Verified',
                'email_verified' => 'Email Verified',
                'specific_users' => 'Specific Users',
                default => ucfirst($group)
            };
        }

        return implode(', ', $groups);
    }

    /**
     * Get formatted scheduled time.
     */
    public function getFormattedScheduledAtAttribute(): ?string
    {
        return $this->scheduled_at?->format('M d, Y \a\t g:i A');
    }

    /**
     * Get formatted duration.
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? $this->cancelled_at ?? now();
        return $this->started_at->diffForHumans($endTime, true);
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if campaign is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if campaign is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if campaign is currently sending.
     */
    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    /**
     * Check if campaign is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if campaign is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if campaign failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if campaign can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'scheduled', 'sending']);
    }

    /**
     * Check if campaign can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['pending', 'scheduled']);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mark campaign as started.
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => 'sending',
            'started_at' => now()
        ]);
    }

    /**
     * Mark campaign as completed.
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    /**
     * Mark campaign as failed.
     */
    public function markAsFailed(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now()
        ]);
    }

    /**
     * Increment sent emails count.
     */
    public function incrementSent(): bool
    {
        return $this->increment('emails_sent');
    }

    /**
     * Increment failed emails count.
     */
    public function incrementFailed(): bool
    {
        return $this->increment('emails_failed');
    }

    /**
     * Check if campaign is finished sending.
     */
    public function isFinished(): bool
    {
        return ($this->emails_sent + $this->emails_failed) >= $this->total_recipients;
    }

    /**
     * Complete campaign if all emails are processed.
     */
    public function checkAndComplete(): void
    {
        if ($this->isFinished() && $this->isSending()) {
            $this->markAsCompleted();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for pending campaigns.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for scheduled campaigns.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for sending campaigns.
     */
    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }

    /**
     * Scope for completed campaigns.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled campaigns.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for failed campaigns.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for active campaigns (sending or scheduled).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['sending', 'scheduled']);
    }

    /**
     * Scope for campaigns due to send.
     */
    public function scopeDueToSend($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for campaigns by creator.
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get campaign statistics.
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'pending' => self::pending()->count(),
            'scheduled' => self::scheduled()->count(),
            'sending' => self::sending()->count(),
            'completed' => self::completed()->count(),
            'cancelled' => self::cancelled()->count(),
            'failed' => self::failed()->count(),
            'total_emails_sent' => self::sum('emails_sent'),
            'total_emails_failed' => self::sum('emails_failed'),
        ];
    }

    /**
     * Get campaigns created today.
     */
    public static function todaysCampaigns()
    {
        return self::whereDate('created_at', today());
    }

    /**
     * Get campaigns created this week.
     */
    public static function thisWeeksCampaigns()
    {
        return self::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Get campaigns created this month.
     */
    public static function thisMonthsCampaigns()
    {
        return self::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}