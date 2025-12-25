<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'minimum_amount',      // For non-tiered plans
        'maximum_amount',      // For non-tiered plans
        'interest_rate',       // For non-tiered plans
        'interest_type',
        'duration_days',
        'return_type',
        'capital_return',
        'status',
        'total_investors',
        'total_invested',
        'features',
        'badge',
        'color_scheme',
        'sort_order',
        'is_tiered',          // Whether this plan uses tiers
        'max_tier_level',     // Maximum tier level available
        'base_interest_rate', // Base interest rate for calculations
        'tier_settings',      // Additional tier configuration
        'profit_sharing_enabled', // Enable profit sharing
    ];

    protected function casts(): array
    {
        return [
            'minimum_amount' => 'decimal:2',
            'maximum_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'base_interest_rate' => 'decimal:2',
            'total_invested' => 'decimal:2',
            'capital_return' => 'boolean',
            'is_tiered' => 'boolean',
            'profit_sharing_enabled' => 'boolean',
            'features' => 'array',
            'tier_settings' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get all user investments for this plan.
     */
    public function userInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * Get active user investments for this plan.
     */
    public function activeInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class)->where('status', 'active');
    }

    /**
     * Get completed user investments for this plan.
     */
    public function completedInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class)->where('status', 'completed');
    }

    /**
     * Get investment plan tiers.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(InvestmentPlanTier::class)->orderBy('tier_level');
    }

    /**
     * Get active tiers only.
     */
    public function activeTiers(): HasMany
    {
        return $this->hasMany(InvestmentPlanTier::class)
            ->where('is_active', true)
            ->orderBy('tier_level');
    }

    /**
     * Get profit sharing configurations for this plan
     */
    public function profitSharingConfigs(): HasMany
    {
        return $this->hasMany(InvestmentPlanProfitSharing::class);
    }

    /**
     * Get active profit sharing configurations
     */
    public function activeProfitSharingConfigs(): HasMany
    {
        return $this->hasMany(InvestmentPlanProfitSharing::class)->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted minimum amount (for non-tiered plans).
     */
    public function getFormattedMinimumAttribute(): string
    {
        if ($this->is_tiered) {
            $lowestTier = $this->activeTiers->first();
            return $lowestTier ? $lowestTier->formatted_minimum : '$0.00';
        }
        return '$' . number_format($this->minimum_amount ?? 0, 2);
    }

    /**
     * Get formatted maximum amount (for non-tiered plans).
     */
    public function getFormattedMaximumAttribute(): string
    {
        if ($this->is_tiered) {
            $highestTier = $this->activeTiers->last();
            return $highestTier ? $highestTier->formatted_maximum : '$0.00';
        }
        return '$' . number_format($this->maximum_amount ?? 0, 2);
    }

    /**
     * Get formatted total invested.
     */
    public function getFormattedTotalInvestedAttribute(): string
    {
        return '$' . number_format($this->total_invested, 2);
    }

    /**
     * Get interest rate display (handles both tiered and non-tiered).
     */
    public function getFormattedInterestRateAttribute(): string
    {
        if ($this->is_tiered) {
            $tiers = $this->activeTiers;
            if ($tiers->count() > 1) {
                $lowest = $tiers->first();
                $highest = $tiers->last();
                return $lowest->interest_rate . '% - ' . $highest->interest_rate . '% ' . ucfirst($this->interest_type);
            } elseif ($tiers->count() === 1) {
                return $tiers->first()->interest_rate . '% ' . ucfirst($this->interest_type);
            }
            return 'Varies by tier';
        }
        return ($this->interest_rate ?? 0) . '% ' . ucfirst($this->interest_type);
    }

    /**
     * Get plan duration in human readable format.
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration_days < 7) {
            return $this->duration_days . ' day' . ($this->duration_days > 1 ? 's' : '');
        } elseif ($this->duration_days < 30) {
            $weeks = round($this->duration_days / 7);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '');
        } elseif ($this->duration_days < 365) {
            $months = round($this->duration_days / 30);
            return $months . ' month' . ($months > 1 ? 's' : '');
        } else {
            $years = round($this->duration_days / 365);
            return $years . ' year' . ($years > 1 ? 's' : '');
        }
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'inactive' => 'bg-secondary',
            'paused' => 'bg-warning',
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
            'inactive' => 'iconamoon:close-circle-duotone',
            'paused' => 'iconamoon:clock-duotone',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    /**
     * Get card color scheme.
     */
    public function getCardColorSchemeAttribute(): string
    {
        return match ($this->color_scheme) {
            'primary' => 'border-primary',
            'success' => 'border-success',
            'warning' => 'border-warning',
            'danger' => 'border-danger',
            'info' => 'border-info',
            default => 'border-primary'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | TIER-RELATED METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if plan uses tiers.
     */
    public function isTiered(): bool
    {
        return $this->is_tiered;
    }

    /**
     * Get available tiers for a user.
     */
    public function getAvailableTiersForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $this->activeTiers()
            ->where('min_user_level', '<=', $user->user_level)
            ->get();
    }

    /**
     * Get tier for specific amount and user.
     */
    public function getTierForInvestment(User $user, float $amount): ?InvestmentPlanTier
    {
        return InvestmentPlanTier::findTierForInvestment($this, $user, $amount);
    }

    /**
     * Check if user can invest specific amount.
     */
    public function canUserInvest(User $user, float $amount): array
    {
        if (!$this->isActive()) {
            return ['can_invest' => false, 'reason' => 'Plan is not active'];
        }

        if ($this->is_tiered) {
            $tier = $this->getTierForInvestment($user, $amount);
            if (!$tier) {
                return [
                    'can_invest' => false, 
                    'reason' => 'No eligible tier found for this amount and user level',
                    'user_level' => $user->user_level,
                    'amount' => $amount
                ];
            }
            return [
                'can_invest' => true, 
                'tier' => $tier,
                'interest_rate' => $tier->interest_rate
            ];
        } else {
            // Non-tiered plan logic
            if ($amount < $this->minimum_amount || $amount > $this->maximum_amount) {
                return [
                    'can_invest' => false,
                    'reason' => "Amount must be between {$this->formatted_minimum} and {$this->formatted_maximum}"
                ];
            }
            return [
                'can_invest' => true,
                'interest_rate' => $this->interest_rate
            ];
        }
    }

    /**
     * Get investment summary for user.
     */
    public function getInvestmentSummaryForUser(User $user): array
    {
        $availableTiers = $this->getAvailableTiersForUser($user);
        
        return [
            'plan_name' => $this->name,
            'is_tiered' => $this->is_tiered,
            'user_level' => $user->user_level,
            'available_tiers' => $availableTiers->map(function ($tier) {
                return [
                    'tier_level' => $tier->tier_level,
                    'tier_name' => $tier->tier_name,
                    'investment_range' => $tier->investment_range,
                    'interest_rate' => $tier->formatted_interest_rate,
                    'features' => $tier->tier_features ?? [],
                ];
            }),
            'locked_tiers' => $this->activeTiers()
                ->where('min_user_level', '>', $user->user_level)
                ->get()
                ->map(function ($tier) use ($user) {
                    return [
                        'tier_level' => $tier->tier_level,
                        'tier_name' => $tier->tier_name,
                        'required_level' => $tier->min_user_level,
                        'levels_needed' => $tier->min_user_level - $user->user_level,
                        'interest_rate' => $tier->formatted_interest_rate,
                    ];
                }),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PROFIT SHARING METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if profit sharing is enabled and configured
     */
    public function hasProfitSharing(): bool
    {
        return $this->profit_sharing_enabled && 
               $this->is_tiered && 
               $this->activeProfitSharingConfigs()->exists();
    }

    /**
     * Get profit sharing configuration for a specific tier
     */
    public function getProfitSharingForTier(int $tierLevel): ?InvestmentPlanProfitSharing
    {
        if (!$this->hasProfitSharing()) {
            return null;
        }

        $tier = $this->tiers()->where('tier_level', $tierLevel)->first();
        if (!$tier) {
            return null;
        }

        return $this->activeProfitSharingConfigs()
            ->where('investment_plan_tier_id', $tier->id)
            ->first();
    }

    /**
     * Enable profit sharing for this plan
     */
    public function enableProfitSharing(): bool
    {
        if (!$this->is_tiered) {
            return false; // Can only enable on tiered plans
        }

        $result = $this->update(['profit_sharing_enabled' => true]);

        if ($result) {
            // Create default profit sharing configurations if none exist
            if ($this->profitSharingConfigs()->count() === 0) {
                InvestmentPlanProfitSharing::createDefaultForPlan($this);
            }
        }

        return $result;
    }

    /**
     * Disable profit sharing for this plan
     */
    public function disableProfitSharing(): bool
    {
        return $this->update(['profit_sharing_enabled' => false]);
    }

    /**
     * Get profit sharing statistics for this plan
     */
    public function getProfitSharingStats(): array
    {
        if (!$this->hasProfitSharing()) {
            return [
                'enabled' => false,
                'total_commissions_generated' => 0,
                'total_commissions_paid' => 0,
                'active_configurations' => 0,
            ];
        }

        $stats = ProfitSharingTransaction::whereHas('userInvestment', function($query) {
                $query->where('investment_plan_id', $this->id);
            })
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status = "paid" THEN commission_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = "pending" THEN commission_amount ELSE 0 END) as total_pending,
                SUM(commission_amount) as total_generated
            ')
            ->first();

        return [
            'enabled' => true,
            'total_transactions' => $stats->total_transactions ?? 0,
            'total_commissions_generated' => $stats->total_generated ?? 0,
            'total_commissions_paid' => $stats->total_paid ?? 0,
            'total_commissions_pending' => $stats->total_pending ?? 0,
            'active_configurations' => $this->activeProfitSharingConfigs()->count(),
            'tiers_with_profit_sharing' => $this->activeProfitSharingConfigs()->distinct('investment_plan_tier_id')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if plan is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if plan is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if plan is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate return periods based on interest type and duration.
     */
    public function getReturnPeriods(): int
    {
        return match ($this->interest_type) {
            'daily' => $this->duration_days,
            'weekly' => ceil($this->duration_days / 7),
            'monthly' => ceil($this->duration_days / 30),
            'yearly' => ceil($this->duration_days / 365),
            default => $this->duration_days
        };
    }

    /**
     * Calculate total return for given investment amount and tier.
     */
    public function calculateTotalReturn(float $amount, ?InvestmentPlanTier $tier = null): float
    {
        $periods = $this->getReturnPeriods();
        $rate = $tier ? $tier->interest_rate / 100 : $this->interest_rate / 100;

        if ($this->return_type === 'compound') {
            return $amount * (pow(1 + $rate, $periods) - 1);
        } else {
            return $amount * $rate * $periods;
        }
    }

    /**
     * Calculate single return payment.
     */
    public function calculateSingleReturn(float $amount, ?InvestmentPlanTier $tier = null): float
    {
        $rate = $tier ? $tier->interest_rate / 100 : $this->interest_rate / 100;
        return $amount * $rate;
    }

    /**
     * Calculate maturity amount (principal + returns).
     */
    public function calculateMaturityAmount(float $amount, ?InvestmentPlanTier $tier = null): float
    {
        $totalReturn = $this->calculateTotalReturn($amount, $tier);
        return $this->capital_return ? $amount + $totalReturn : $totalReturn;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Activate the plan.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the plan.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Pause the plan.
     */
    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    /**
     * Increment total investors and invested amount.
     */
    public function addInvestment(float $amount): bool
    {
        return $this->increment('total_investors') && 
               $this->increment('total_invested', $amount);
    }

    /**
     * Decrement total investors and invested amount.
     */
    public function removeInvestment(float $amount): bool
    {
        return $this->decrement('total_investors') && 
               $this->decrement('total_invested', $amount);
    }

    /**
     * Setup default tiers for the plan.
     */
    public function setupDefaultTiers(): array
    {
        if (!$this->is_tiered) {
            return [];
        }

        return InvestmentPlanTier::createDefaultTiers($this);
    }

    /**
     * Convert to tiered plan.
     */
    public function convertToTiered(): bool
    {
        if ($this->is_tiered) {
            return false; // Already tiered
        }

        $result = $this->update([
            'is_tiered' => true,
            'max_tier_level' => 5,
            'base_interest_rate' => $this->interest_rate,
        ]);

        if ($result) {
            $this->setupDefaultTiers();
        }

        return $result;
    }

    /**
     * Convert to non-tiered plan.
     */
    public function convertToNonTiered(): bool
    {
        if (!$this->is_tiered) {
            return false; // Already non-tiered
        }

        // Delete all tiers and profit sharing configs
        $this->tiers()->delete();
        $this->profitSharingConfigs()->delete();

        return $this->update([
            'is_tiered' => false,
            'max_tier_level' => 0,
            'base_interest_rate' => null,
            'tier_settings' => null,
            'profit_sharing_enabled' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive plans.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for paused plans.
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tiered plans.
     */
    public function scopeTiered($query)
    {
        return $query->where('is_tiered', true);
    }

    /**
     * Scope for non-tiered plans.
     */
    public function scopeNonTiered($query)
    {
        return $query->where('is_tiered', false);
    }

    /**
     * Scope for plans with profit sharing enabled
     */
    public function scopeWithProfitSharing($query)
    {
        return $query->where('profit_sharing_enabled', true);
    }

    /**
     * Scope for plans with complete profit sharing setup
     */
    public function scopeWithActiveProfitSharing($query)
    {
        return $query->where('profit_sharing_enabled', true)
            ->where('is_tiered', true)
            ->whereHas('activeProfitSharingConfigs');
    }

    /**
     * Scope for popular plans (with badge).
     */
    public function scopePopular($query)
    {
        return $query->whereNotNull('badge');
    }

    /**
     * Scope ordered by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Scope with investment stats.
     */
    public function scopeWithStats($query)
    {
        return $query->withCount(['userInvestments', 'activeInvestments'])
            ->withSum('userInvestments', 'amount');
    }

    /**
     * Scope with tiers.
     */
    public function scopeWithTiers($query)
    {
        return $query->with(['tiers' => function ($query) {
            $query->orderBy('tier_level');
        }]);
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
            'paused' => 'Paused',
        ];
    }

    /**
     * Get available interest types.
     */
    public static function getInterestTypes(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'yearly' => 'Yearly',
        ];
    }

    /**
     * Get available return types.
     */
    public static function getReturnTypes(): array
    {
        return [
            'fixed' => 'Fixed Interest',
            'compound' => 'Compound Interest',
        ];
    }

    /**
     * Get color schemes.
     */
    public static function getColorSchemes(): array
    {
        return [
            'primary' => 'Primary (Blue)',
            'success' => 'Success (Green)',
            'warning' => 'Warning (Yellow)',
            'danger' => 'Danger (Red)',
            'info' => 'Info (Cyan)',
        ];
    }

    /**
     * Get statistics for dashboard including profit sharing.
     */
    public static function getStatistics(): array
    {
        $baseStats = [
            'total_plans' => self::count(),
            'active_plans' => self::active()->count(),
            'inactive_plans' => self::inactive()->count(),
            'paused_plans' => self::paused()->count(),
            'tiered_plans' => self::tiered()->count(),
            'non_tiered_plans' => self::nonTiered()->count(),
            'total_invested' => self::sum('total_invested'),
            'total_investors' => self::sum('total_investors'),
            'avg_investment' => self::where('total_investors', '>', 0)->avg('total_invested'),
        ];

        // Add profit sharing statistics
        $profitSharingStats = [
            'profit_sharing_enabled_plans' => self::withProfitSharing()->count(),
            'total_profit_sharing_transactions' => ProfitSharingTransaction::count(),
            'total_commissions_paid' => ProfitSharingTransaction::paid()->sum('commission_amount'),
            'total_commissions_pending' => ProfitSharingTransaction::pending()->sum('commission_amount'),
        ];

        return array_merge($baseStats, $profitSharingStats);
    }
}