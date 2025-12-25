<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InvestmentPlanTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_plan_id',
        'tier_level',
        'tier_name',
        'minimum_amount',
        'maximum_amount',
        'interest_rate',
        'min_user_level',
        'tier_description',
        'tier_features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'tier_features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the investment plan this tier belongs to.
     */
    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    /**
     * Get investments made in this tier.
     */
    public function userInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class, 'tier_level', 'tier_level')
            ->where('investment_plan_id', $this->investment_plan_id);
    }

    /**
     * Get the profit sharing configuration for this tier
     */
    public function profitSharing(): HasOne
    {
        return $this->hasOne(InvestmentPlanProfitSharing::class, 'investment_plan_tier_id');
    }

    /**
     * Get active profit sharing configuration
     */
    public function activeProfitSharing(): HasOne
    {
        return $this->hasOne(InvestmentPlanProfitSharing::class, 'investment_plan_tier_id')
            ->where('is_active', true);
    }

    /**
     * Get all profit sharing transactions for investments in this tier
     */
    public function profitSharingTransactions(): HasMany
    {
        return $this->hasMany(ProfitSharingTransaction::class, 'investment_plan_tier_id')
            ->through('userInvestments');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted minimum amount.
     */
    public function getFormattedMinimumAttribute(): string
    {
        return '$' . number_format($this->minimum_amount, 2);
    }

    /**
     * Get formatted maximum amount.
     */
    public function getFormattedMaximumAttribute(): string
    {
        return '$' . number_format($this->maximum_amount, 2);
    }

    /**
     * Get formatted interest rate.
     */
    public function getFormattedInterestRateAttribute(): string
    {
        return $this->interest_rate . '%';
    }

    /**
     * Get investment range as string.
     */
    public function getInvestmentRangeAttribute(): string
    {
        return $this->formatted_minimum . ' - ' . $this->formatted_maximum;
    }

    /**
     * Get tier display name with level.
     */
    public function getDisplayNameAttribute(): string
    {
        return "Tier {$this->tier_level}: {$this->tier_name}";
    }

    /**
     * Get tier badge class based on level.
     */
    public function getTierBadgeClassAttribute(): string
    {
        return match (true) {
            $this->tier_level === 0 => 'bg-light text-dark',
            $this->tier_level === 1 => 'bg-secondary',
            $this->tier_level === 2 => 'bg-primary',
            $this->tier_level === 3 => 'bg-success',
            $this->tier_level === 4 => 'bg-warning',
            $this->tier_level === 5 => 'bg-danger',
            $this->tier_level >= 6 => 'bg-dark',
            default => 'bg-secondary'
        };
    }

    /**
     * Get tier icon based on level.
     */
    public function getTierIconAttribute(): string
    {
        return match (true) {
            $this->tier_level === 0 => 'iconamoon:user-duotone',
            $this->tier_level === 1 => 'iconamoon:star-duotone',
            $this->tier_level === 2 => 'iconamoon:medal-duotone',
            $this->tier_level === 3 => 'iconamoon:trophy-duotone',
            $this->tier_level === 4 => 'iconamoon:crown-duotone',
            $this->tier_level === 5 => 'iconamoon:diamond-duotone',
            $this->tier_level >= 6 => 'iconamoon:lightning-duotone',
            default => 'iconamoon:star-duotone'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | PROFIT SHARING METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if this tier has profit sharing configured
     */
    public function hasProfitSharing(): bool
    {
        return $this->investmentPlan->profit_sharing_enabled && 
               $this->activeProfitSharing()->exists();
    }

    /**
     * Get profit sharing configuration
     */
    public function getProfitSharingConfig(): ?InvestmentPlanProfitSharing
    {
        return $this->activeProfitSharing;
    }

    /**
     * Create or update profit sharing configuration for this tier
     */
    public function setProfitSharing(array $commissionRates): InvestmentPlanProfitSharing
    {
        return InvestmentPlanProfitSharing::updateOrCreate(
            [
                'investment_plan_id' => $this->investment_plan_id,
                'investment_plan_tier_id' => $this->id,
            ],
            array_merge($commissionRates, ['is_active' => true])
        );
    }

    /**
     * Remove profit sharing configuration for this tier
     */
    public function removeProfitSharing(): bool
    {
        return $this->profitSharing()->delete();
    }

    /**
     * Get profit sharing preview for a given investment amount
     */
    public function getProfitSharingPreview(float $amount): array
    {
        $profitSharing = $this->activeProfitSharing;
        
        if (!$profitSharing) {
            return [
                'has_profit_sharing' => false,
                'commissions' => [],
                'total_commission' => 0,
            ];
        }

        $breakdown = $profitSharing->getCommissionBreakdown($amount);

        return [
            'has_profit_sharing' => true,
            'commissions' => $breakdown,
            'total_commission' => $breakdown['total']['amount'],
            'formatted_total' => '$' . number_format($breakdown['total']['amount'], 2),
            'commission_structure' => [
                'level_1' => [
                    'rate' => $profitSharing->level_1_commission,
                    'amount' => $breakdown['level_1']['amount'],
                    'description' => 'Direct referral commission'
                ],
                'level_2' => [
                    'rate' => $profitSharing->level_2_commission,
                    'amount' => $breakdown['level_2']['amount'],
                    'description' => 'Indirect referral commission'
                ],
                'level_3' => [
                    'rate' => $profitSharing->level_3_commission,
                    'amount' => $breakdown['level_3']['amount'],
                    'description' => 'Third level referral commission'
                ]
            ]
        ];
    }

    /**
     * Get profit sharing statistics for this tier
     */
    public function getProfitSharingStats(): array
    {
        if (!$this->hasProfitSharing()) {
            return [
                'enabled' => false,
                'total_commissions' => 0,
                'commissions_paid' => 0,
                'commissions_pending' => 0,
            ];
        }

        $stats = ProfitSharingTransaction::whereHas('userInvestment', function($query) {
                $query->where('investment_plan_id', $this->investment_plan_id)
                      ->where('tier_level', $this->tier_level);
            })
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "paid" THEN commission_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = "pending" THEN commission_amount ELSE 0 END) as total_pending,
                SUM(commission_amount) as total_generated,
                AVG(commission_amount) as avg_commission
            ')
            ->first();

        return [
            'enabled' => true,
            'total_transactions' => $stats->total_transactions ?? 0,
            'total_commissions' => $stats->total_generated ?? 0,
            'commissions_paid' => $stats->total_paid ?? 0,
            'commissions_pending' => $stats->total_pending ?? 0,
            'average_commission' => $stats->avg_commission ?? 0,
            'formatted_total' => '$' . number_format($stats->total_generated ?? 0, 2),
            'formatted_paid' => '$' . number_format($stats->total_paid ?? 0, 2),
            'formatted_pending' => '$' . number_format($stats->total_pending ?? 0, 2),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user can invest in this tier.
     */
    public function canUserInvest(User $user, float $amount): bool
    {
        // Check if tier is active
        if (!$this->is_active) {
            return false;
        }

        // Check user level requirement
        if ($user->user_level < $this->min_user_level) {
            return false;
        }

        // Check investment amount range
        if ($amount < $this->minimum_amount || $amount > $this->maximum_amount) {
            return false;
        }

        return true;
    }

    /**
     * Get detailed eligibility check for user
     */
    public function getEligibilityCheck(User $user, float $amount): array
    {
        $checks = [
            'tier_active' => [
                'passed' => $this->is_active,
                'message' => $this->is_active ? 'Tier is active' : 'Tier is currently inactive'
            ],
            'user_level' => [
                'passed' => $user->user_level >= $this->min_user_level,
                'message' => $user->user_level >= $this->min_user_level 
                    ? "User level {$user->user_level} meets requirement" 
                    : "Requires user level {$this->min_user_level}, current: {$user->user_level}"
            ],
            'amount_range' => [
                'passed' => $amount >= $this->minimum_amount && $amount <= $this->maximum_amount,
                'message' => ($amount >= $this->minimum_amount && $amount <= $this->maximum_amount)
                    ? 'Investment amount within tier range'
                    : "Amount must be between {$this->investment_range}"
            ]
        ];

        $allPassed = collect($checks)->pluck('passed')->every(fn($passed) => $passed);

        return [
            'eligible' => $allPassed,
            'checks' => $checks,
            'summary' => $allPassed 
                ? 'User is eligible for this tier' 
                : 'User does not meet tier requirements'
        ];
    }

    /**
     * Check if user meets the level requirement.
     */
    public function userMeetsLevelRequirement(User $user): bool
    {
        return $user->user_level >= $this->min_user_level;
    }

    /**
     * Check if amount is within tier range.
     */
    public function isAmountInRange(float $amount): bool
    {
        return $amount >= $this->minimum_amount && $amount <= $this->maximum_amount;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate return for this tier based on plan settings.
     */
    public function calculateReturn(float $amount): float
    {
        $plan = $this->investmentPlan;
        $periods = $plan->getReturnPeriods();
        $rate = $this->interest_rate / 100;

        if ($plan->return_type === 'compound') {
            return $amount * (pow(1 + $rate, $periods) - 1);
        } else {
            return $amount * $rate * $periods;
        }
    }

    /**
     * Calculate maturity amount for this tier.
     */
    public function calculateMaturityAmount(float $amount): float
    {
        $totalReturn = $this->calculateReturn($amount);
        return $this->investmentPlan->capital_return ? $amount + $totalReturn : $totalReturn;
    }

    /**
     * Calculate single period return.
     */
    public function calculateSingleReturn(float $amount): float
    {
        return $amount * ($this->interest_rate / 100);
    }

    /**
     * Get investment projection breakdown
     */
    public function getInvestmentProjection(float $amount): array
    {
        $plan = $this->investmentPlan;
        $singleReturn = $this->calculateSingleReturn($amount);
        $totalReturn = $this->calculateReturn($amount);
        $periods = $plan->getReturnPeriods();
        $maturityAmount = $this->calculateMaturityAmount($amount);

        $projection = [
            'investment_amount' => $amount,
            'formatted_investment' => '$' . number_format($amount, 2),
            'tier_info' => [
                'name' => $this->tier_name,
                'level' => $this->tier_level,
                'interest_rate' => $this->interest_rate,
                'formatted_rate' => $this->formatted_interest_rate
            ],
            'returns' => [
                'single_return' => $singleReturn,
                'formatted_single' => '$' . number_format($singleReturn, 2),
                'total_return' => $totalReturn,
                'formatted_total' => '$' . number_format($totalReturn, 2),
                'periods' => $periods,
                'frequency' => $plan->interest_type
            ],
            'maturity' => [
                'amount' => $maturityAmount,
                'formatted_amount' => '$' . number_format($maturityAmount, 2),
                'capital_returned' => $plan->capital_return,
                'duration_days' => $plan->duration_days,
                'formatted_duration' => $plan->formatted_duration
            ],
            'roi' => [
                'percentage' => ($totalReturn / $amount) * 100,
                'formatted_percentage' => number_format(($totalReturn / $amount) * 100, 2) . '%'
            ]
        ];

        // Add profit sharing projection if enabled
        if ($this->hasProfitSharing()) {
            $projection['profit_sharing'] = $this->getProfitSharingPreview($amount);
        }

        return $projection;
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
     * Scope for tiers accessible by user level.
     */
    public function scopeAccessibleByLevel($query, int $userLevel)
    {
        return $query->where('min_user_level', '<=', $userLevel);
    }

    /**
     * Scope for tiers that contain the given amount.
     */
    public function scopeForAmount($query, float $amount)
    {
        return $query->where('minimum_amount', '<=', $amount)
            ->where('maximum_amount', '>=', $amount);
    }

    /**
     * Scope ordered by tier level.
     */
    public function scopeOrderedByLevel($query)
    {
        return $query->orderBy('tier_level', 'asc');
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('tier_level', 'asc');
    }

    /**
     * Scope by investment plan.
     */
    public function scopeByPlan($query, $planId)
    {
        return $query->where('investment_plan_id', $planId);
    }

    /**
     * Scope for tiers with profit sharing enabled
     */
    public function scopeWithProfitSharing($query)
    {
        return $query->whereHas('investmentPlan', function($q) {
            $q->where('profit_sharing_enabled', true);
        })->whereHas('activeProfitSharing');
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get default tier names by level.
     */
    public static function getDefaultTierNames(): array
    {
        return [
            0 => 'Starter',
            1 => 'Bronze',
            2 => 'Silver',
            3 => 'Gold',
            4 => 'Platinum',
            5 => 'Diamond',
            6 => 'Elite',
            7 => 'Master',
            8 => 'Legendary',
            9 => 'Ultimate',
        ];
    }

    /**
     * Create default tiers for a plan.
     */
    public static function createDefaultTiers(InvestmentPlan $plan): array
    {
        $defaultTiers = [
            [
                'tier_level' => 0,
                'tier_name' => 'Starter',
                'minimum_amount' => 1.00,
                'maximum_amount' => 99.99,
                'interest_rate' => 3.00,
                'min_user_level' => 0,
                'tier_description' => 'Entry level for new investors to get started',
                'tier_features' => ['Basic support', 'Daily returns'],
            ],
            [
                'tier_level' => 1,
                'tier_name' => 'Bronze',
                'minimum_amount' => 100.00,
                'maximum_amount' => 499.99,
                'interest_rate' => 5.00,
                'min_user_level' => 1,
                'tier_description' => 'Perfect for beginners to start their investment journey',
                'tier_features' => ['Email support', 'Investment tracking'],
            ],
            [
                'tier_level' => 2,
                'tier_name' => 'Silver',
                'minimum_amount' => 500.00,
                'maximum_amount' => 999.99,
                'interest_rate' => 7.50,
                'min_user_level' => 2,
                'tier_description' => 'Enhanced returns for growing investors',
                'tier_features' => ['Priority support', 'Advanced analytics'],
            ],
            [
                'tier_level' => 3,
                'tier_name' => 'Gold',
                'minimum_amount' => 1000.00,
                'maximum_amount' => 4999.99,
                'interest_rate' => 10.00,
                'min_user_level' => 3,
                'tier_description' => 'Substantial investments with premium benefits',
                'tier_features' => ['Dedicated support', 'Custom reports'],
            ],
            [
                'tier_level' => 4,
                'tier_name' => 'Platinum',
                'minimum_amount' => 5000.00,
                'maximum_amount' => 19999.99,
                'interest_rate' => 12.50,
                'min_user_level' => 4,
                'tier_description' => 'High-value investments with exclusive perks',
                'tier_features' => ['VIP support', 'Investment advisor'],
            ],
            [
                'tier_level' => 5,
                'tier_name' => 'Diamond',
                'minimum_amount' => 20000.00,
                'maximum_amount' => 99999.99,
                'interest_rate' => 15.00,
                'min_user_level' => 5,
                'tier_description' => 'Elite investment tier for serious investors',
                'tier_features' => ['Personal manager', 'Exclusive events'],
            ],
        ];

        $createdTiers = [];
        foreach ($defaultTiers as $tierData) {
            $tierData['investment_plan_id'] = $plan->id;
            $tierData['is_active'] = true;
            $tierData['sort_order'] = $tierData['tier_level'];
            $createdTiers[] = self::create($tierData);
        }

        return $createdTiers;
    }

    /**
     * Find appropriate tier for user and amount.
     */
    public static function findTierForInvestment(InvestmentPlan $plan, User $user, float $amount): ?self
    {
        return self::where('investment_plan_id', $plan->id)
            ->active()
            ->accessibleByLevel($user->user_level)
            ->forAmount($amount)
            ->orderBy('tier_level', 'desc') // Get highest eligible tier
            ->first();
    }

    /**
     * Get tier statistics for a plan including profit sharing data.
     */
    public static function getPlanTierStats(InvestmentPlan $plan): array
    {
        $tiers = self::where('investment_plan_id', $plan->id)
            ->withCount('userInvestments')
            ->withSum('userInvestments', 'amount')
            ->with('activeProfitSharing')
            ->get();

        return $tiers->map(function ($tier) {
            $baseStats = [
                'id' => $tier->id,
                'tier_level' => $tier->tier_level,
                'tier_name' => $tier->tier_name,
                'investment_range' => $tier->investment_range,
                'interest_rate' => $tier->formatted_interest_rate,
                'min_user_level' => $tier->min_user_level,
                'total_investments' => $tier->user_investments_count ?? 0,
                'total_amount' => $tier->user_investments_sum_amount ?? 0,
                'formatted_amount' => '$' . number_format($tier->user_investments_sum_amount ?? 0, 2),
                'is_active' => $tier->is_active,
                'tier_features' => $tier->tier_features ?? [],
            ];

            // Add profit sharing stats if enabled
            if ($tier->hasProfitSharing()) {
                $profitSharing = $tier->activeProfitSharing;
                $baseStats['profit_sharing'] = [
                    'enabled' => true,
                    'level_1_commission' => $profitSharing->formatted_level_1_commission,
                    'level_2_commission' => $profitSharing->formatted_level_2_commission,
                    'level_3_commission' => $profitSharing->formatted_level_3_commission,
                    'total_rate' => $profitSharing->total_commission_rate . '%',
                    'max_cap' => $profitSharing->formatted_max_cap,
                ];

                // Get commission statistics for this tier
                $commissionStats = $tier->getProfitSharingStats();
                $baseStats['commission_stats'] = $commissionStats;
            } else {
                $baseStats['profit_sharing'] = ['enabled' => false];
                $baseStats['commission_stats'] = null;
            }

            return $baseStats;
        })->toArray();
    }

    /**
     * Get available user level options
     */
    public static function getUserLevelOptions(): array
    {
        return [
            0 => 'TL-0',
            1 => 'TL-1',
            2 => 'TL-2',
            3 => 'TL-3',
            4 => 'TL-4',
            5 => 'TL-5',
            6 => 'TL-6',
        ];
    }

    /**
     * Get tier statistics across all plans
     */
    public static function getGlobalTierStats(): array
    {
        return [
            'total_tiers' => self::count(),
            'active_tiers' => self::active()->count(),
            'tiers_with_profit_sharing' => self::withProfitSharing()->count(),
            'avg_interest_rate' => self::avg('interest_rate'),
            'tier_distribution' => self::selectRaw('tier_level, COUNT(*) as count')
                ->groupBy('tier_level')
                ->orderBy('tier_level')
                ->get()
                ->pluck('count', 'tier_level')
                ->toArray(),
            'investment_volume_by_tier' => self::withSum('userInvestments', 'amount')
                ->selectRaw('tier_level, SUM(user_investments_sum_amount) as total_volume')
                ->groupBy('tier_level')
                ->orderBy('tier_level')
                ->get()
                ->pluck('total_volume', 'tier_level')
                ->toArray(),
        ];
    }
}