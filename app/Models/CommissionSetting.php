<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'level',
        'name',
        'min_investment',
        'min_direct_referrals',
        'min_indirect_referrals',
        'commission_level_1',
        'commission_level_2',
        'commission_level_3',
        'is_active',
        'color',
        'description',
        'sort_order'
    ];

    protected function casts(): array
    {
        return [
            'min_investment' => 'decimal:2',
            'commission_level_1' => 'decimal:2',
            'commission_level_2' => 'decimal:2',
            'commission_level_3' => 'decimal:2',
            'is_active' => 'boolean',
            'min_direct_referrals' => 'integer',
            'min_indirect_referrals' => 'integer',
            'level' => 'integer',
            'sort_order' => 'integer'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get users who qualify for this tier.
     */
    public function qualifiedUsers(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'level', 'level');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted minimum investment.
     */
    public function getFormattedMinInvestmentAttribute(): string
    {
        return '$' . number_format($this->min_investment, 2);
    }

    /**
     * Get formatted commission percentages.
     */
    public function getFormattedCommissionsAttribute(): array
    {
        return [
            'level_1' => $this->commission_level_1 . '%',
            'level_2' => $this->commission_level_2 . '%',
            'level_3' => $this->commission_level_3 . '%'
        ];
    }

    /**
     * Get tier color with fallback.
     */
    public function getTierColorAttribute(): string
    {
        return $this->color ?: $this->getDefaultColor();
    }

    /**
     * Get default color based on level.
     */
    private function getDefaultColor(): string
    {
        $colors = [
            1 => '#6c757d', // Gray
            2 => '#17a2b8', // Info
            3 => '#28a745', // Success
            4 => '#ffc107', // Warning
            5 => '#fd7e14', // Orange
            6 => '#dc3545', // Danger
            7 => '#6f42c1', // Purple
        ];

        return $colors[$this->level] ?? '#6c757d';
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active ? 'bg-success' : 'bg-secondary';
    }

    /**
     * Get total commission percentage.
     */
    public function getTotalCommissionAttribute(): float
    {
        return $this->commission_level_1 + $this->commission_level_2 + $this->commission_level_3;
    }

    /*
    |--------------------------------------------------------------------------
    | BUSINESS LOGIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user qualifies for this tier.
     */
    public function userQualifies(User $user): bool
    {
        $profile = $user->profile;
        if (!$profile) return false;

        // Check investment requirement
        if ($profile->total_investments < $this->min_investment) {
            return false;
        }

        // Check direct referrals requirement
        $directReferrals = UserReferral::where('sponsor_id', $user->id)
            ->where('level', 1)
            ->where('status', 'active')
            ->count();

        if ($directReferrals < $this->min_direct_referrals) {
            return false;
        }

        // Check indirect referrals requirement
        $indirectReferrals = UserReferral::where('sponsor_id', $user->id)
            ->whereIn('level', [2, 3])
            ->where('status', 'active')
            ->count();

        if ($indirectReferrals < $this->min_indirect_referrals) {
            return false;
        }

        return true;
    }

    /**
     * Calculate commission for a referral transaction.
     */
    public function calculateCommission(float $amount, int $referralLevel): float
    {
        if (!$this->is_active) return 0;

        $percentage = match($referralLevel) {
            1 => $this->commission_level_1,
            2 => $this->commission_level_2,
            3 => $this->commission_level_3,
            default => 0
        };

        return ($amount * $percentage) / 100;
    }

    /**
     * Get qualified users count.
     */
    public function getQualifiedUsersCountAttribute(): int
    {
        return User::whereHas('profile', function($query) {
            $query->where('level', $this->level);
        })->count();
    }

    /**
     * Get requirements summary.
     */
    public function getRequirementsSummaryAttribute(): array
    {
        return [
            'investment' => $this->formatted_min_investment,
            'direct_referrals' => $this->min_direct_referrals . ' direct referrals',
            'indirect_referrals' => $this->min_indirect_referrals . ' indirect referrals'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active tiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered tiers.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('level');
    }

    /**
     * Scope by level.
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get commission setting for user's current tier.
     */
    public static function getForUser(User $user): ?self
    {
        $profile = $user->profile;
        if (!$profile || !$profile->level) return null;

        return self::active()
            ->where('level', $profile->level)
            ->first();
    }

    /**
     * Get all active tiers with statistics.
     */
    public static function getWithStats(): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->ordered()
            ->get()
            ->map(function ($tier) {
                $tier->users_count = $tier->qualified_users_count;
                return $tier;
            });
    }

    /**
     * Calculate total platform commissions.
     */
    public static function calculatePlatformCommissions(float $amount): array
    {
        $tiers = self::active()->get();
        $totalCommissions = [];

        foreach ($tiers as $tier) {
            $totalCommissions[$tier->level] = [
                'level_1' => $tier->calculateCommission($amount, 1),
                'level_2' => $tier->calculateCommission($amount, 2),
                'level_3' => $tier->calculateCommission($amount, 3),
                'total' => $tier->calculateCommission($amount, 1) + 
                          $tier->calculateCommission($amount, 2) + 
                          $tier->calculateCommission($amount, 3)
            ];
        }

        return $totalCommissions;
    }

    /**
     * Get next tier for user upgrade path.
     */
    public static function getNextTier(User $user): ?self
    {
        $currentLevel = $user->profile->level ?? 0;
        
        return self::active()
            ->where('level', '>', $currentLevel)
            ->orderBy('level')
            ->first();
    }

    /**
     * Get default tier settings.
     */
    public static function getDefaultTiers(): array
    {
        return [
            [
                'level' => 1,
                'name' => 'Bronze',
                'min_investment' => 100,
                'min_direct_referrals' => 0,
                'min_indirect_referrals' => 0,
                'commission_level_1' => 5.0,
                'commission_level_2' => 2.0,
                'commission_level_3' => 1.0,
                'color' => '#6c757d',
                'description' => 'Entry level tier',
                'sort_order' => 1
            ],
            [
                'level' => 2,
                'name' => 'Silver',
                'min_investment' => 500,
                'min_direct_referrals' => 3,
                'min_indirect_referrals' => 5,
                'commission_level_1' => 7.0,
                'commission_level_2' => 3.0,
                'commission_level_3' => 1.5,
                'color' => '#17a2b8',
                'description' => 'Intermediate tier with better commissions',
                'sort_order' => 2
            ],
            [
                'level' => 3,
                'name' => 'Gold',
                'min_investment' => 1000,
                'min_direct_referrals' => 5,
                'min_indirect_referrals' => 10,
                'commission_level_1' => 10.0,
                'commission_level_2' => 5.0,
                'commission_level_3' => 2.5,
                'color' => '#ffc107',
                'description' => 'Premium tier with highest commissions',
                'sort_order' => 3
            ]
        ];
    }

    /**
     * Create default tiers.
     */
    public static function seedDefaultTiers(): void
    {
        foreach (self::getDefaultTiers() as $tierData) {
            self::updateOrCreate(
                ['level' => $tierData['level']],
                array_merge($tierData, ['is_active' => true])
            );
        }
    }
}