<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserReferral extends Model
{
    use HasFactory;

    protected $fillable = [
        'sponsor_id',
        'user_id',
        'level',
        'status',
        'commission_earned',
    ];

    protected function casts(): array
    {
        return [
            'commission_earned' => 'decimal:2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the sponsor (upline) user.
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    /**
     * Get the referred user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted commission earned.
     */
    public function getFormattedCommissionAttribute(): string
    {
        return '$' . number_format($this->commission_earned, 2);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-warning',
            'blocked' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'active' => 'iconamoon:check-circle-duotone',
            'inactive' => 'iconamoon:clock-duotone',
            'blocked' => 'iconamoon:close-circle-duotone',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    /**
     * Get how long ago the referral was created.
     */
    public function getCreatedAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get formatted creation date.
     */
    public function getFormattedCreatedDateAttribute(): string
    {
        return $this->created_at->format('M d, Y H:i');
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if referral is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if referral is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if referral is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if referral has earned commission.
     */
    public function hasEarnedCommission(): bool
    {
        return $this->commission_earned > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Activate the referral.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the referral.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Block the referral.
     */
    public function block(): bool
    {
        return $this->update(['status' => 'blocked']);
    }

    /**
     * Add commission to the referral.
     */
    public function addCommission(float $amount): bool
    {
        return $this->increment('commission_earned', $amount);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active referrals.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive referrals.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for blocked referrals.
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope by sponsor.
     */
    public function scopeBySponsor($query, int $sponsorId)
    {
        return $query->where('sponsor_id', $sponsorId);
    }

    /**
     * Scope by level.
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope for referrals with commission.
     */
    public function scopeWithCommission($query)
    {
        return $query->where('commission_earned', '>', 0);
    }

    /**
     * Scope for recent referrals.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for today's referrals.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope for this week's referrals.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope for this month's referrals.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope with user and sponsor details.
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['user', 'sponsor']);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'blocked' => 'Blocked',
        ];
    }

    /**
     * Get statistics for dashboard.
     */
    public static function getStatistics(): array
    {
        return [
            'total_referrals' => self::count(),
            'active_referrals' => self::active()->count(),
            'inactive_referrals' => self::inactive()->count(),
            'blocked_referrals' => self::blocked()->count(),
            'today_referrals' => self::today()->count(),
            'this_week_referrals' => self::thisWeek()->count(),
            'this_month_referrals' => self::thisMonth()->count(),
            'total_commission' => self::sum('commission_earned'),
            'active_commission' => self::active()->sum('commission_earned'),
        ];
    }

    /**
     * Get top sponsors by referral count.
     */
    public static function getTopSponsors(int $limit = 10): \Illuminate\Support\Collection
    {
        return self::select('sponsor_id')
            ->selectRaw('COUNT(*) as referral_count')
            ->selectRaw('SUM(commission_earned) as total_commission')
            ->with('sponsor:id,first_name,last_name,email')
            ->groupBy('sponsor_id')
            ->orderByDesc('referral_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get referral performance by date range.
     */
    public static function getPerformanceData(Carbon $startDate, Carbon $endDate): array
    {
        $data = self::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(commission_earned) as commission')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($date) => Carbon::parse($date)->format('M d'))->toArray(),
            'referrals' => $data->pluck('count')->toArray(),
            'commissions' => $data->pluck('commission')->map(fn($amount) => (float) $amount)->toArray(),
        ];
    }

    /**
     * Calculate commission based on user's tier.
     */
    public function calculateTierCommission(float $amount): float
    {
        $sponsor = $this->sponsor;
        if (!$sponsor || !$sponsor->profile)
            return 0;

        $tier = CommissionSetting::getForUser($sponsor);
        if (!$tier)
            return 0;

        return $tier->calculateCommission($amount, $this->level);
    }
}