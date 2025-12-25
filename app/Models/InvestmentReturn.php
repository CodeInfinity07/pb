<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class InvestmentReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_investment_id',
        'user_id',
        'amount',
        'type',
        'status',
        'due_date',
        'paid_at',
        'transaction_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user investment this return belongs to.
     */
    public function userInvestment(): BelongsTo
    {
        return $this->belongsTo(UserInvestment::class);
    }

    /**
     * Get the user who will receive this return.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related transaction (if applicable).
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'transaction_id');
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
     * Get formatted due date.
     */
    public function getFormattedDueDateAttribute(): string
    {
        return $this->due_date->format('M d, Y');
    }

    /**
     * Get formatted paid date.
     */
    public function getFormattedPaidDateAttribute(): ?string
    {
        return $this->paid_at ? $this->paid_at->format('M d, Y H:i') : null;
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning',
            'paid' => 'bg-success',
            'failed' => 'bg-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get status icon.
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'iconamoon:clock-duotone',
            'paid' => 'iconamoon:check-circle-duotone',
            'failed' => 'iconamoon:close-circle-duotone',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    /**
     * Get type badge class.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'interest' => 'bg-primary',
            'capital' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    /**
     * Get type icon.
     */
    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            'interest' => 'iconamoon:star-duotone',
            'capital' => 'material-symbols:account-balance-wallet',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    /**
     * Check if return is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    /**
     * Get days overdue (negative if not due yet).
     */
    public function getDaysOverdueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    /**
     * Get time until due or overdue text.
     */
    public function getTimeStatusAttribute(): string
    {
        if ($this->status === 'paid') {
            return 'Paid ' . $this->paid_at->diffForHumans();
        }

        if ($this->status === 'failed') {
            return 'Failed';
        }

        if ($this->is_overdue) {
            return 'Overdue by ' . abs($this->days_overdue) . ' day' . (abs($this->days_overdue) > 1 ? 's' : '');
        }

        return 'Due ' . $this->due_date->diffForHumans();
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if return is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if return is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if return has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if return is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->getIsOverdueAttribute();
    }

    /**
     * Check if return is interest type.
     */
    public function isInterest(): bool
    {
        return $this->type === 'interest';
    }

    /**
     * Check if return is capital type.
     */
    public function isCapital(): bool
    {
        return $this->type === 'capital';
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mark return as paid.
     */
    public function markAsPaid(?string $transactionId = null): bool
    {
        $updateData = [
            'status' => 'paid',
            'paid_at' => now(),
        ];

        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        $result = $this->update($updateData);

        if ($result) {
            // Update user investment paid return amount
            $this->userInvestment->addReturnPayment($this->amount, $this->type);

            // Check if investment should be completed
            if ($this->isCapital() && $this->userInvestment->hasMatured()) {
                $this->userInvestment->complete();
            }
        }

        return $result;
    }

    /**
     * Mark return as failed.
     */
    public function markAsFailed(string $reason = null): bool
    {
        $updateData = [
            'status' => 'failed',
        ];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        return $this->update($updateData);
    }

    /**
     * Reset return to pending status.
     */
    public function resetToPending(): bool
    {
        return $this->update([
            'status' => 'pending',
            'paid_at' => null,
            'transaction_id' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for pending returns.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for paid returns.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for failed returns.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for overdue returns.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('due_date', '<', now());
    }

    /**
     * Scope for due today.
     */
    public function scopeDueToday($query)
    {
        return $query->where('status', 'pending')
            ->whereDate('due_date', today());
    }

    /**
     * Scope for due this week.
     */
    public function scopeDueThisWeek($query)
    {
        return $query->where('status', 'pending')
            ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope by return type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for interest returns.
     */
    public function scopeInterest($query)
    {
        return $query->where('type', 'interest');
    }

    /**
     * Scope for capital returns.
     */
    public function scopeCapital($query)
    {
        return $query->where('type', 'capital');
    }

    /**
     * Scope by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by user investment.
     */
    public function scopeByInvestment($query, $investmentId)
    {
        return $query->where('user_investment_id', $investmentId);
    }

    /**
     * Scope with related data.
     */
    public function scopeWithDetails($query)
    {
        return $query->with(['user', 'userInvestment.investmentPlan']);
    }

    /**
     * Scope ordered by due date.
     */
    public function scopeOrderedByDueDate($query, string $direction = 'asc')
    {
        return $query->orderBy('due_date', $direction);
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
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
        ];
    }

    /**
     * Get available types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            'interest' => 'Interest',
            'capital' => 'Capital',
        ];
    }

    /**
     * Get statistics for dashboard.
     */
    public static function getStatistics(): array
    {
        return [
            'total_returns' => self::count(),
            'pending_returns' => self::pending()->count(),
            'paid_returns' => self::paid()->count(),
            'failed_returns' => self::failed()->count(),
            'overdue_returns' => self::overdue()->count(),
            'due_today' => self::dueToday()->count(),
            'due_this_week' => self::dueThisWeek()->count(),
            'total_amount_pending' => self::pending()->sum('amount'),
            'total_amount_paid' => self::paid()->sum('amount'),
            'total_amount_overdue' => self::overdue()->sum('amount'),
        ];
    }

    /**
     * Process pending returns (for scheduled task).
     */
    public static function processPendingReturns(): array
    {
        $dueReturns = self::dueToday()->with(['user', 'userInvestment'])->get();
        $processed = 0;
        $failed = 0;

        foreach ($dueReturns as $return) {
            try {
                // Here you would integrate with your payment system
                // For now, we'll mark as paid if user has sufficient balance
                $user = $return->user;
                
                // This is a placeholder - implement your actual payment logic
                if ($user->canReceivePayment($return->amount)) {
                    $return->markAsPaid();
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $return->markAsFailed('Processing error: ' . $e->getMessage());
                $failed++;
            }
        }

        return [
            'total_due' => $dueReturns->count(),
            'processed' => $processed,
            'failed' => $failed,
        ];
    }

    /**
     * Get overdue summary for admin.
     */
    public static function getOverdueSummary(): array
    {
        $overdueReturns = self::overdue()->with(['user', 'userInvestment.investmentPlan'])->get();

        return [
            'count' => $overdueReturns->count(),
            'total_amount' => $overdueReturns->sum('amount'),
            'by_plan' => $overdueReturns->groupBy('userInvestment.investmentPlan.name')
                ->map(function ($returns) {
                    return [
                        'count' => $returns->count(),
                        'amount' => $returns->sum('amount'),
                    ];
                }),
            'by_user' => $overdueReturns->groupBy('user.full_name')
                ->map(function ($returns) {
                    return [
                        'count' => $returns->count(),
                        'amount' => $returns->sum('amount'),
                    ];
                }),
        ];
    }
}