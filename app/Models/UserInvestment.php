<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class UserInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'amount',
        'total_return',
        'tier_level',
        'paid_return',
        'status',
        'started_at',
        'ends_at',
        'last_return_at',
        'completed_at',
        'return_history',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_return' => 'decimal:2',
            'paid_return' => 'decimal:2',
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'last_return_at' => 'datetime',
            'completed_at' => 'datetime',
            'return_history' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user who owns this investment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the investment plan.
     */
    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    /**
     * Get all return payments for this investment.
     */
    public function returns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class);
    }

    /**
     * Get pending return payments.
     */
    public function pendingReturns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class)->where('status', 'pending');
    }

    /**
     * Get paid return payments.
     */
    public function paidReturns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class)->where('status', 'paid');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get formatted total return.
     */
    public function getFormattedTotalReturnAttribute(): string
    {
        return '$' . number_format($this->total_return, 2);
    }

    /**
     * Get formatted paid return.
     */
    public function getFormattedPaidReturnAttribute(): string
    {
        return '$' . number_format($this->paid_return, 2);
    }

    /**
     * Get remaining return amount.
     */
    public function getRemainingReturnAttribute(): float
    {
        return $this->total_return - $this->paid_return;
    }

    /**
     * Get formatted remaining return.
     */
    public function getFormattedRemainingReturnAttribute(): string
    {
        return '$' . number_format($this->getRemainingReturnAttribute(), 2);
    }

    /**
     * Get investment progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->status === 'completed') {
            return 100;
        }

        $totalDays = $this->started_at->diffInDays($this->ends_at);
        $passedDays = $this->started_at->diffInDays(now());

        return min(100, max(0, ($passedDays / $totalDays) * 100));
    }

    /**
     * Get days remaining.
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->status === 'completed' || now()->isAfter($this->ends_at)) {
            return 0;
        }

        return now()->diffInDays($this->ends_at);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'completed' => 'bg-primary',
            'cancelled' => 'bg-danger',
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
            'completed' => 'iconamoon:star-duotone',
            'cancelled' => 'iconamoon:close-circle-duotone',
            'paused' => 'iconamoon:clock-duotone',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    /**
     * Get formatted start date.
     */
    public function getFormattedStartDateAttribute(): string
    {
        return $this->started_at->format('M d, Y');
    }

    /**
     * Get formatted end date.
     */
    public function getFormattedEndDateAttribute(): string
    {
        return $this->ends_at->format('M d, Y');
    }

    /**
     * Get how long ago investment was created.
     */
    public function getCreatedAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get maturity status text.
     */
    public function getMaturityStatusAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'Completed';
        }

        if (now()->isAfter($this->ends_at)) {
            return 'Matured';
        }

        return 'Active';
    }

    /**
     * Get expected maturity amount.
     */
    public function getExpectedMaturityAmountAttribute(): float
    {
        return $this->investmentPlan->capital_return
            ? $this->amount + $this->total_return
            : $this->total_return;
    }

    /**
     * Get formatted expected maturity amount.
     */
    public function getFormattedExpectedMaturityAmountAttribute(): string
    {
        return '$' . number_format($this->getExpectedMaturityAmountAttribute(), 2);
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if investment is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if investment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if investment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if investment is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Check if investment has matured.
     */
    public function hasMatured(): bool
    {
        return now()->isAfter($this->ends_at);
    }

    /**
     * Check if investment is due for return payment.
     */
    public function isDueForReturn(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $plan = $this->investmentPlan;
        $nextDueDate = $this->getNextReturnDueDate();

        return $nextDueDate && now()->isAfter($nextDueDate);
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get next return due date.
     */
    public function getNextReturnDueDate(): ?Carbon
    {
        if (!$this->isActive()) {
            return null;
        }

        $plan = $this->investmentPlan;
        $lastReturn = $this->last_return_at ?? $this->started_at;

        // Use copy() to avoid modifying the original Carbon instance
        return match ($plan->interest_type) {
            'daily' => $lastReturn->copy()->addDay(),
            'weekly' => $lastReturn->copy()->addWeek(),
            'monthly' => $lastReturn->copy()->addMonth(),
            'yearly' => $lastReturn->copy()->addYear(),
            default => null
        };
    }

    /**
     * Calculate single return amount.
     */
    public function calculateSingleReturn(): float
    {
        // Ensure investment plan is loaded
        if (!$this->relationLoaded('investmentPlan')) {
            $this->load('investmentPlan');
        }

        // Check if investment plan exists
        if (!$this->investmentPlan) {
            return 0.00;
        }

        $plan = $this->investmentPlan;
        $investmentAmount = floatval($this->amount ?? 0);

        // Check if this is a tiered plan and investment has a tier level
        if ($plan->is_tiered && !empty($this->tier_level)) {
            // Find the specific tier for this investment
            $tier = InvestmentPlanTier::where('investment_plan_id', $plan->id)
                ->where('tier_level', $this->tier_level)
                ->where('is_active', true)
                ->first();

            if ($tier) {
                // Use tier's interest rate
                $interestRate = floatval($tier->interest_rate ?? 0);
            } else {
                // Fallback to plan's base interest rate if tier not found
                $interestRate = floatval($plan->interest_rate ?? 0);
            }
        } else {
            // Use plan's base interest rate for non-tiered plans
            $interestRate = floatval($plan->interest_rate ?? 0);
        }

        // Calculate return: amount × (rate / 100)
        $rate = $interestRate / 100;
        $returnAmount = $investmentAmount * $rate;

        return round($returnAmount, 2);
    }

    /**
     * Calculate total expected return.
     */
    public function calculateExpectedReturn(): float
    {
        return $this->investmentPlan->calculateTotalReturn($this->amount);
    }

    /**
     * Calculate ROI percentage.
     */
    public function getROIPercentage(): float
    {
        return ($this->total_return / $this->amount) * 100;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Complete the investment.
     */
    public function complete(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel the investment.
     */
    public function cancel(): bool
    {
        // Mark all pending returns as failed
        $this->pendingReturns()->update(['status' => 'failed']);

        return $this->update([
            'status' => 'cancelled',
        ]);
    }

    /**
     * Pause the investment.
     */
    public function pause(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    /**
     * Resume the investment.
     */
    public function resume(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Add a return payment.
     */
    public function addReturnPayment(float $amount, string $type = 'interest'): bool
    {
        $this->increment('paid_return', $amount);
        $this->update(['last_return_at' => now()]);

        // Update return history
        $history = $this->return_history ?? [];
        $history[] = [
            'amount' => $amount,
            'type' => $type,
            'date' => now()->toISOString(),
        ];

        $this->update(['return_history' => $history]);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active investments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed investments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled investments.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for paused investments.
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope for matured investments.
     */
    public function scopeMatured($query)
    {
        return $query->where('ends_at', '<=', now());
    }

    /**
     * Scope for investments due for return.
     */
    public function scopeDueForReturn($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('last_return_at')
                    ->orWhere('last_return_at', '<=', now()->subDay());
            });
    }

    /**
     * Scope by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by investment plan.
     */
    public function scopeByPlan($query, $planId)
    {
        return $query->where('investment_plan_id', $planId);
    }

    /**
     * Scope with related data.
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['user', 'investmentPlan', 'returns']);
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
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'paused' => 'Paused',
        ];
    }

    /**
     * Get statistics for dashboard.
     */
    public static function getStatistics(): array
    {
        return [
            'total_investments' => self::count(),
            'active_investments' => self::active()->count(),
            'completed_investments' => self::completed()->count(),
            'total_invested_amount' => self::sum('amount'),
            'total_returns_paid' => self::sum('paid_return'),
            'matured_investments' => self::matured()->count(),
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($investment) {
            $plan = $investment->investmentPlan;

            // Calculate total return
            $investment->total_return = $plan->calculateTotalReturn($investment->amount);

            // Set end date
            $investment->ends_at = $investment->started_at->addDays($plan->duration_days);
        });

        static::created(function ($investment) {
            // Update plan statistics
            $investment->investmentPlan->addInvestment($investment->amount);

            // Create return schedule
            $investment->createReturnSchedule();
        });
    }

    /**
     * Create return schedule for this investment.
     */
    public function createReturnSchedule(): void
    {
        $plan = $this->investmentPlan;
        $periods = $plan->getReturnPeriods();
        $currentDate = $this->started_at;

        for ($i = 0; $i < $periods; $i++) {
            $dueDate = match ($plan->interest_type) {
                'daily' => $currentDate->copy()->addDays($i + 1),
                'weekly' => $currentDate->copy()->addWeeks($i + 1),
                'monthly' => $currentDate->copy()->addMonths($i + 1),
                'yearly' => $currentDate->copy()->addYears($i + 1),
                default => $currentDate->copy()->addDays($i + 1)
            };

            // Don't create returns beyond the investment end date
            if ($dueDate->isAfter($this->ends_at)) {
                break;
            }

            InvestmentReturn::create([
                'user_investment_id' => $this->id,
                'user_id' => $this->user_id,
                'amount' => $this->calculateSingleReturn(),
                'type' => 'interest',
                'due_date' => $dueDate,
                'status' => 'pending',
            ]);
        }

        // Create capital return if applicable
        if ($plan->capital_return) {
            InvestmentReturn::create([
                'user_investment_id' => $this->id,
                'user_id' => $this->user_id,
                'amount' => $this->amount,
                'type' => 'capital',
                'due_date' => $this->ends_at,
                'status' => 'pending',
            ]);
        }
    }
}