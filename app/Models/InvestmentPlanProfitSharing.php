<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentPlanProfitSharing extends Model
{
    use HasFactory;

    protected $table = 'investment_plan_profit_sharing';

    protected $fillable = [
        'investment_plan_id',
        'investment_plan_tier_id',
        'level_1_commission',
        'level_2_commission',
        'level_3_commission',
        'max_commission_cap',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level_1_commission' => 'decimal:2',
            'level_2_commission' => 'decimal:2',
            'level_3_commission' => 'decimal:2',
            'max_commission_cap' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlanTier::class, 'investment_plan_tier_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    public function getFormattedLevel1CommissionAttribute(): string
    {
        return $this->level_1_commission . '%';
    }

    public function getFormattedLevel2CommissionAttribute(): string
    {
        return $this->level_2_commission . '%';
    }

    public function getFormattedLevel3CommissionAttribute(): string
    {
        return $this->level_3_commission . '%';
    }

    public function getFormattedMaxCapAttribute(): string
    {
        return $this->max_commission_cap ? '$' . number_format($this->max_commission_cap, 2) : 'No Cap';
    }

    public function getTotalCommissionRateAttribute(): float
    {
        return $this->level_1_commission + $this->level_2_commission + $this->level_3_commission;
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate commission for a specific level and amount
     */
    public function calculateCommission(int $level, float $amount): float
    {
        $rate = match($level) {
            1 => $this->level_1_commission,
            2 => $this->level_2_commission,
            3 => $this->level_3_commission,
            default => 0
        };

        $commission = ($amount * $rate) / 100;

        // Apply cap if set
        if ($this->max_commission_cap) {
            $commission = min($commission, $this->max_commission_cap);
        }

        return round($commission, 2);
    }

    /**
     * Get all commission levels data
     */
    public function getCommissionBreakdown(float $amount): array
    {
        return [
            'level_1' => [
                'rate' => $this->level_1_commission,
                'amount' => $this->calculateCommission(1, $amount),
                'formatted_rate' => $this->formatted_level_1_commission,
                'formatted_amount' => '$' . number_format($this->calculateCommission(1, $amount), 2),
            ],
            'level_2' => [
                'rate' => $this->level_2_commission,
                'amount' => $this->calculateCommission(2, $amount),
                'formatted_rate' => $this->formatted_level_2_commission,
                'formatted_amount' => '$' . number_format($this->calculateCommission(2, $amount), 2),
            ],
            'level_3' => [
                'rate' => $this->level_3_commission,
                'amount' => $this->calculateCommission(3, $amount),
                'formatted_rate' => $this->formatted_level_3_commission,
                'formatted_amount' => '$' . number_format($this->calculateCommission(3, $amount), 2),
            ],
            'total' => [
                'rate' => $this->total_commission_rate,
                'amount' => $this->calculateCommission(1, $amount) + 
                           $this->calculateCommission(2, $amount) + 
                           $this->calculateCommission(3, $amount),
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION METHODS
    |--------------------------------------------------------------------------
    */

    public function isValidCommissionStructure(): array
    {
        $errors = [];

        if ($this->total_commission_rate > 50) {
            $errors[] = 'Total commission rate cannot exceed 50%';
        }

        if ($this->level_1_commission < 0 || $this->level_2_commission < 0 || $this->level_3_commission < 0) {
            $errors[] = 'Commission rates cannot be negative';
        }

        if ($this->level_1_commission > 25 || $this->level_2_commission > 25 || $this->level_3_commission > 25) {
            $errors[] = 'Individual level commission cannot exceed 25%';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlan($query, $planId)
    {
        return $query->where('investment_plan_id', $planId);
    }

    public function scopeForTier($query, $tierId)
    {
        return $query->where('investment_plan_tier_id', $tierId);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create default profit sharing for all tiers of a plan
     */
    public static function createDefaultForPlan(InvestmentPlan $plan): array
    {
        if (!$plan->is_tiered) {
            return [];
        }

        $created = [];
        $defaultRates = [
            1 => ['level_1' => 5.0, 'level_2' => 3.0, 'level_3' => 2.0], // Bronze
            2 => ['level_1' => 6.0, 'level_2' => 4.0, 'level_3' => 2.5], // Silver
            3 => ['level_1' => 7.0, 'level_2' => 5.0, 'level_3' => 3.0], // Gold
            4 => ['level_1' => 8.0, 'level_2' => 6.0, 'level_3' => 4.0], // Platinum
            5 => ['level_1' => 10.0, 'level_2' => 7.0, 'level_3' => 5.0], // Diamond
        ];

        foreach ($plan->tiers as $tier) {
            $rates = $defaultRates[$tier->tier_level] ?? $defaultRates[1];

            $profitSharing = self::create([
                'investment_plan_id' => $plan->id,
                'investment_plan_tier_id' => $tier->id,
                'level_1_commission' => $rates['level_1'],
                'level_2_commission' => $rates['level_2'],
                'level_3_commission' => $rates['level_3'],
                'is_active' => true,
            ]);

            $created[] = $profitSharing;
        }

        return $created;
    }

    /**
     * Get profit sharing statistics
     */
    public static function getStatistics(): array
    {
        $totalConfigs = self::count();
        $activeConfigs = self::active()->count();
        
        $avgCommissions = self::selectRaw('
            AVG(level_1_commission) as avg_level_1,
            AVG(level_2_commission) as avg_level_2,
            AVG(level_3_commission) as avg_level_3
        ')->first();

        return [
            'total_configurations' => $totalConfigs,
            'active_configurations' => $activeConfigs,
            'average_commissions' => [
                'level_1' => round($avgCommissions->avg_level_1 ?? 0, 2),
                'level_2' => round($avgCommissions->avg_level_2 ?? 0, 2),
                'level_3' => round($avgCommissions->avg_level_3 ?? 0, 2),
            ],
            'plans_with_profit_sharing' => InvestmentPlan::where('profit_sharing_enabled', true)->count(),
        ];
    }
}