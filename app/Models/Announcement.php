<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'target_audience',
        'target_user_ids',
        'priority',
        'status',
        'scheduled_at',
        'expires_at',
        'show_once',
        'is_dismissible',
        'button_text',
        'button_link',
        'created_by'
    ];

    protected function casts(): array
    {
        return [
            'target_user_ids' => 'array',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'show_once' => 'boolean',
            'is_dismissible' => 'boolean',
            'priority' => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user who created this announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all user views for this announcement.
     */
    public function userViews(): HasMany
    {
        return $this->hasMany(UserAnnouncementView::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get the type badge class.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            'success' => 'bg-success',
            'warning' => 'bg-warning',
            'danger' => 'bg-danger',
            'info' => 'bg-info',
            default => 'bg-primary'
        };
    }

    /**
     * Get the type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'success' => 'iconamoon:check-circle-duotone',
            'warning' => 'iconamoon:warning-duotone',
            'danger' => 'iconamoon:close-circle-duotone',
            'info' => 'iconamoon:information-circle-duotone',
            default => 'iconamoon:notification-duotone'
        };
    }

    /**
     * Get the status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'scheduled' => 'bg-warning',
            'expired' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get the status icon.
     */
    public function getStatusIconAttribute(): string
    {
        return match($this->status) {
            'active' => 'iconamoon:check-circle-duotone',
            'inactive' => 'iconamoon:pause-circle-duotone',
            'scheduled' => 'iconamoon:clock-duotone',
            'expired' => 'iconamoon:close-circle-duotone',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    /**
     * Get formatted target audience.
     */
    public function getTargetAudienceDisplayAttribute(): string
    {
        return match($this->target_audience) {
            'all' => 'All Users',
            'active' => 'Active Users',
            'verified' => 'Verified Users',
            'kyc_verified' => 'KYC Verified',
            'specific' => count($this->target_user_ids ?? []) . ' Specific Users',
            default => ucfirst($this->target_audience)
        };
    }

    /**
     * Get total views count.
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->userViews()->count();
    }

    /**
     * Get unique users who viewed this announcement.
     */
    public function getUniqueViewersAttribute(): int
    {
        return $this->userViews()->distinct('user_id')->count();
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if announcement is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if announcement is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if announcement should be shown now.
     */
    public function shouldShowNow(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check if it's scheduled for future
        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }

        // Check if it's expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if announcement has expired.
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if user has already viewed this announcement.
     */
    public function hasUserViewed(User $user): bool
    {
        return $this->userViews()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if user should see this announcement.
     */
    public function shouldShowToUser(User $user): bool
    {
        // Don't show if not active or scheduled for future
        if (!$this->shouldShowNow()) {
            return false;
        }

        // Don't show if user has already viewed and it's set to show once
        if ($this->show_once && $this->hasUserViewed($user)) {
            return false;
        }

        // Check target audience
        return $this->isUserInTargetAudience($user);
    }

    /**
     * Check if user is in target audience.
     */
    public function isUserInTargetAudience(User $user): bool
    {
        return match($this->target_audience) {
            'all' => true,
            'active' => $user->isActive(),
            'verified' => $user->isVerified(),
            'kyc_verified' => $user->isKycVerified(),
            'specific' => in_array($user->id, $this->target_user_ids ?? []),
            default => false
        };
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mark as viewed by user.
     */
    public function markAsViewedBy(User $user): void
    {
        $this->userViews()->firstOrCreate([
            'user_id' => $user->id,
            'announcement_id' => $this->id,
        ], [
            'viewed_at' => now(),
        ]);
    }

    /**
     * Activate the announcement.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the announcement.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active announcements.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for scheduled announcements.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope for announcements that should show now.
     */
    public function scopeShouldShowNow($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for announcements by target audience.
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where('target_audience', $audience);
    }

    /**
     * Scope for announcements by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Scope for announcements for specific user.
     */
    public function scopeForUser($query, User $user)
    {
        return $query->shouldShowNow()
                    ->where(function ($q) use ($user) {
                        $q->where('target_audience', 'all')
                          ->orWhere(function ($subQ) use ($user) {
                              $subQ->where('target_audience', 'active')
                                   ->when($user->isActive(), function ($activeQ) {
                                       return $activeQ;
                                   });
                          })
                          ->orWhere(function ($subQ) use ($user) {
                              $subQ->where('target_audience', 'verified')
                                   ->when($user->isVerified(), function ($verifiedQ) {
                                       return $verifiedQ;
                                   });
                          })
                          ->orWhere(function ($subQ) use ($user) {
                              $subQ->where('target_audience', 'kyc_verified')
                                   ->when($user->isKycVerified(), function ($kycQ) {
                                       return $kycQ;
                                   });
                          })
                          ->orWhere(function ($subQ) use ($user) {
                              $subQ->where('target_audience', 'specific')
                                   ->whereJsonContains('target_user_ids', $user->id);
                          });
                    })
                    ->whereDoesntHave('userViews', function ($viewQuery) use ($user) {
                        $viewQuery->where('user_id', $user->id);
                    });
    }

    /**
     * Scope for expired announcements.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Scope for pending announcements.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for cancelled announcements.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for failed announcements.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for announcements due to send.
     */
    public function scopeDueToSend($query)
    {
        return $query->where('status', 'scheduled')
                    ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope for announcements by creator.
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope for recent announcements.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for announcements created today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for announcements created this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope for announcements created this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get announcement statistics.
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'scheduled' => self::scheduled()->count(),
            'expired' => self::expired()->count(),
            'total_views' => UserAnnouncementView::count(),
            'unique_viewers' => UserAnnouncementView::distinct('user_id')->count(),
        ];
    }

    /**
     * Get announcements created today.
     */
    public static function todaysAnnouncements()
    {
        return self::today();
    }

    /**
     * Get announcements created this week.
     */
    public static function thisWeeksAnnouncements()
    {
        return self::thisWeek();
    }

    /**
     * Get announcements created this month.
     */
    public static function thisMonthsAnnouncements()
    {
        return self::thisMonth();
    }

    /**
     * Get priority levels.
     */
    public static function getPriorityLevels(): array
    {
        return [
            1 => 'Highest',
            2 => 'High',
            3 => 'Medium',
            4 => 'Low',
            5 => 'Lowest',
        ];
    }

    /**
     * Get announcement types.
     */
    public static function getTypes(): array
    {
        return [
            'info' => 'Information',
            'success' => 'Success',
            'warning' => 'Warning',
            'danger' => 'Important/Urgent',
        ];
    }

    /**
     * Get target audiences.
     */
    public static function getTargetAudiences(): array
    {
        return [
            'all' => 'All Users',
            'active' => 'Active Users',
            'verified' => 'Email Verified Users',
            'kyc_verified' => 'KYC Verified Users',
            'specific' => 'Specific Users',
        ];
    }

    /**
     * Get status options.
     */
    public static function getStatusOptions(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'scheduled' => 'Scheduled',
        ];
    }
}