<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProfitSharingTransaction extends Model
{
    use HasFactory;

    protected $table = 'profit_sharing_txns'; // 👈 specify custom table name

    protected $fillable = [
        'user_investment_id',
        'beneficiary_user_id',
        'source_user_id',
        'commission_level',
        'commission_amount',
        'source_investment_amount',
        'commission_rate',
        'status',
        'paid_at',
        'transaction_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
            'source_investment_amount' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function userInvestment(): BelongsTo
    {
        return $this->belongsTo(UserInvestment::class);
    }

    public function beneficiaryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    public function getFormattedCommissionAmountAttribute(): string
    {
        return '$' . number_format($this->commission_amount, 2);
    }

    public function getFormattedSourceAmountAttribute(): string
    {
        return '$' . number_format($this->source_investment_amount, 2);
    }

    public function getFormattedCommissionRateAttribute(): string
    {
        return $this->commission_rate . '%';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-warning',
            'paid' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'iconamoon:clock-duotone',
            'paid' => 'iconamoon:check-circle-duotone',
            'failed' => 'iconamoon:close-circle-duotone',
            'cancelled' => 'iconamoon:ban-duotone',
            default => 'iconamoon:question-circle-duotone'
        };
    }

    public function getCommissionLevelNameAttribute(): string
    {
        return match ($this->commission_level) {
            1 => 'Direct Referral (Level 1)',
            2 => 'Indirect Referral (Level 2)',
            3 => 'Third Level Referral (Level 3)',
            default => "Level {$this->commission_level}"
        };
    }

    public function getFormattedPaidDateAttribute(): ?string
    {
        return $this->paid_at ? $this->paid_at->format('M d, Y H:i') : null;
    }

    public function getCreatedAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(?string $transactionReference = null): bool
    {
        $updateData = [
            'status' => 'paid',
            'paid_at' => now(),
        ];

        if ($transactionReference) {
            $updateData['transaction_reference'] = $transactionReference;
        }

        return $this->update($updateData);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason = null): bool
    {
        $updateData = ['status' => 'failed'];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        return $this->update($updateData);
    }

    /**
     * Cancel the transaction
     */
    public function cancel(string $reason = null): bool
    {
        $updateData = ['status' => 'cancelled'];

        if ($reason) {
            $updateData['notes'] = $reason;
        }

        return $this->update($updateData);
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('commission_level', $level);
    }

    public function scopeByBeneficiary($query, $userId)
    {
        return $query->where('beneficiary_user_id', $userId);
    }

    public function scopeBySource($query, $userId)
    {
        return $query->where('source_user_id', $userId);
    }

    public function scopeWithDetails($query)
    {
        return $query->with(['beneficiaryUser', 'sourceUser', 'userInvestment.investmentPlan']);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create profit sharing transactions for an investment
     */
    public static function createForInvestment(UserInvestment $investment): array
    {
        $plan = $investment->investmentPlan;
        $user = $investment->user;

        if (!$plan->profit_sharing_enabled || !$plan->is_tiered) {
            return [];
        }

        // Get tier configuration
        $tier = $plan->tiers()->where('tier_level', $investment->tier_level)->first();
        if (!$tier) {
            return [];
        }

        $profitSharing = $tier->profitSharing()->active()->first();
        if (!$profitSharing) {
            return [];
        }

        $transactions = [];
        $currentUser = $user;

        // Create transactions for each level
        for ($level = 1; $level <= 3; $level++) {
            $sponsor = $currentUser->sponsor; // Assumes User model has sponsor relationship

            if (!$sponsor) {
                break; // No more sponsors up the chain
            }

            $commissionAmount = $profitSharing->calculateCommission($level, $investment->amount);

            if ($commissionAmount > 0) {
                $transaction = self::create([
                    'user_investment_id' => $investment->id,
                    'beneficiary_user_id' => $sponsor->id,
                    'source_user_id' => $user->id,
                    'commission_level' => $level,
                    'commission_amount' => $commissionAmount,
                    'source_investment_amount' => $investment->amount,
                    'commission_rate' => $level == 1 ? $profitSharing->level_1_commission :
                        ($level == 2 ? $profitSharing->level_2_commission : $profitSharing->level_3_commission),
                    'status' => 'pending',
                ]);

                $transactions[] = $transaction;
            }

            $currentUser = $sponsor; // Move up the chain
        }

        return $transactions;
    }

    /**
     * Get available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get statistics for dashboard
     */
    public static function getStatistics(): array
    {
        return [
            'total_transactions' => self::count(),
            'pending_transactions' => self::pending()->count(),
            'paid_transactions' => self::paid()->count(),
            'failed_transactions' => self::failed()->count(),
            'total_commissions_pending' => self::pending()->sum('commission_amount'),
            'total_commissions_paid' => self::paid()->sum('commission_amount'),
            'average_commission' => self::avg('commission_amount'),
            'top_earners' => self::paid()
                ->selectRaw('beneficiary_user_id, SUM(commission_amount) as total_earned')
                ->groupBy('beneficiary_user_id')
                ->orderByDesc('total_earned')
                ->limit(10)
                ->with('beneficiaryUser')
                ->get(),
        ];
    }

    /**
     * Process pending transactions (for scheduled tasks)
     */
    public static function processPendingTransactions(): array
    {
        $pendingTransactions = self::pending()->with(['beneficiaryUser'])->get();
        $processed = 0;
        $failed = 0;

        foreach ($pendingTransactions as $transaction) {
            try {
                // Here you would integrate with your payment/wallet system
                // For now, we'll mark as paid if beneficiary exists
                if ($transaction->beneficiaryUser) {
                    // Add commission to user's wallet/balance
                    // $transaction->beneficiaryUser->addCommission($transaction->commission_amount);

                    $transaction->markAsPaid();
                    $processed++;
                } else {
                    $transaction->markAsFailed('Beneficiary user not found');
                    $failed++;
                }
            } catch (\Exception $e) {
                $transaction->markAsFailed('Processing error: ' . $e->getMessage());
                $failed++;
            }
        }

        return [
            'total_processed' => $pendingTransactions->count(),
            'successful' => $processed,
            'failed' => $failed,
        ];
    }
}