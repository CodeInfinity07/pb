<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'crypto_address',
        'crypto_txid',
        'description',
        'metadata',
        'processed_at',
        'processed_by'
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'amount' => 'decimal:8'
    ];

    /**
     * Transaction types
     */
    const TYPE_DEPOSIT = 'deposit';
    const TYPE_FEE = 'fee';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_COMMISSION = 'commission';
    const TYPE_ROI = 'roi';
    const TYPE_INVESTMENT = 'investment';
    const TYPE_BONUS = 'bonus';
    const TYPE_PROFIT = 'profit';
    const TYPE_CREDIT_ADJUSTMENT = 'credit_adjustment';
    const TYPE_DEBIT_ADJUSTMENT = 'debit_adjustment';

    /**
     * Transaction statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the user that owns the transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed the transaction
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // TRANSACTION TYPE SCOPES

    /**
     * Scope for deposit transactions
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', self::TYPE_DEPOSIT);
    }

    /**
     * Scope for withdrawal transactions
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', self::TYPE_WITHDRAWAL);
    }

    /**
     * Scope for commission transactions
     */
    public function scopeCommissions($query)
    {
        return $query->where('type', self::TYPE_COMMISSION);
    }

    /**
     * Scope for ROI transactions
     */
    public function scopeRoi($query)
    {
        return $query->where('type', self::TYPE_ROI);
    }

    /**
     * Scope for investment transactions
     */
    public function scopeInvestments($query)
    {
        return $query->where('type', self::TYPE_INVESTMENT);
    }

    /**
     * Scope for bonus transactions
     */
    public function scopeBonus($query)
    {
        return $query->where('type', self::TYPE_BONUS);
    }

    /**
     * Scope for profit sharing transactions
     */
    public function scopeProfits($query)
    {
        return $query->where('type', self::TYPE_PROFIT);
    }

    /**
     * Scope for credit adjustment transactions
     */
    public function scopeCreditAdjustments($query)
    {
        return $query->where('type', self::TYPE_CREDIT_ADJUSTMENT);
    }

    /**
     * Scope for debit adjustment transactions
     */
    public function scopeDebitAdjustments($query)
    {
        return $query->where('type', self::TYPE_DEBIT_ADJUSTMENT);
    }

    // TRANSACTION STATUS SCOPES

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for processing transactions
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for cancelled transactions
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // OTHER SCOPES

    /**
     * Scope for crypto transactions
     */
    public function scopeCrypto($query)
    {
        return $query->whereNotNull('crypto_address');
    }

    // HELPER METHODS

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transaction is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if transaction is a deposit
     */
    public function isDeposit(): bool
    {
        return $this->type === self::TYPE_DEPOSIT;
    }

    /**
     * Check if transaction is a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return $this->type === self::TYPE_WITHDRAWAL;
    }

    /**
     * Check if transaction is a commission
     */
    public function isCommission(): bool
    {
        return $this->type === self::TYPE_COMMISSION;
    }

    /**
     * Check if transaction is ROI
     */
    public function isRoi(): bool
    {
        return $this->type === self::TYPE_ROI;
    }

    /**
     * Check if transaction is an investment
     */
    public function isInvestment(): bool
    {
        return $this->type === self::TYPE_INVESTMENT;
    }

    /**
     * Check if transaction is a bonus
     */
    public function isBonus(): bool
    {
        return $this->type === self::TYPE_BONUS;
    }

    /**
     * Check if transaction is profit sharing
     */
    public function isProfit(): bool
    {
        return $this->type === self::TYPE_PROFIT;
    }

    /**
     * Check if transaction is a credit adjustment
     */
    public function isCreditAdjustment(): bool
    {
        return $this->type === self::TYPE_CREDIT_ADJUSTMENT;
    }

    /**
     * Check if transaction is a debit adjustment
     */
    public function isDebitAdjustment(): bool
    {
        return $this->type === self::TYPE_DEBIT_ADJUSTMENT;
    }

    // ACCESSORS

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        if (in_array($this->currency, ['BTC', 'ETH', 'LTC'])) {
            return number_format($this->amount, 8) . ' ' . $this->currency;
        }
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get display description based on transaction type
     */
    public function getDisplayDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        switch ($this->type) {
            case self::TYPE_DEPOSIT:
                return "Crypto deposit ({$this->currency})";
            case self::TYPE_WITHDRAWAL:
                return "Crypto withdrawal ({$this->currency})";
            case self::TYPE_COMMISSION:
                return "Commission earned";
            case self::TYPE_ROI:
                return "Return on investment";
            case self::TYPE_INVESTMENT:
                return "Investment made";
            case self::TYPE_BONUS:
                return "Bonus received";
            case self::TYPE_PROFIT:
                return "Profit sharing distribution";
            case self::TYPE_CREDIT_ADJUSTMENT:
                return "Credit adjustment";
            case self::TYPE_DEBIT_ADJUSTMENT:
                return "Debit adjustment";
            default:
                return ucfirst($this->type) . " transaction";
        }
    }

    /**
     * Get type color for UI display
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_DEPOSIT => 'success',
            self::TYPE_WITHDRAWAL => 'warning',
            self::TYPE_COMMISSION => 'primary',
            self::TYPE_ROI => 'info',
            self::TYPE_INVESTMENT => 'dark',
            self::TYPE_BONUS => 'secondary',
            self::TYPE_PROFIT => 'success',
            self::TYPE_CREDIT_ADJUSTMENT => 'success',
            self::TYPE_DEBIT_ADJUSTMENT => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get status color for UI display
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    // STATIC METHODS

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(string $type, string $currency, int $userId): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        return $prefix . '_' . strtoupper($currency) . '_' . $userId . '_' . time();
    }

    /**
     * Get all available transaction types
     */
    public static function getTransactionTypes(): array
    {
        return [
            self::TYPE_DEPOSIT => 'Deposit',
            self::TYPE_WITHDRAWAL => 'Withdrawal',
            self::TYPE_COMMISSION => 'Commission',
            self::TYPE_ROI => 'ROI',
            self::TYPE_INVESTMENT => 'Investment',
            self::TYPE_BONUS => 'Bonus',
            self::TYPE_PROFIT => 'Profit Sharing',
            self::TYPE_CREDIT_ADJUSTMENT => 'Credit Adjustment',
            self::TYPE_DEBIT_ADJUSTMENT => 'Debit Adjustment',
        ];
    }

    /**
     * Get all available transaction statuses
     */
    public static function getTransactionStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get transaction statistics for dashboard
     */
    public static function getStatistics(string $period = '30d'): array
    {
        $startDate = match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };

        return [
            'total_transactions' => self::where('created_at', '>=', $startDate)->count(),
            'total_amount' => self::where('created_at', '>=', $startDate)->sum('amount'),
            'completed_transactions' => self::completed()->where('created_at', '>=', $startDate)->count(),
            'pending_transactions' => self::pending()->where('created_at', '>=', $startDate)->count(),
            'processing_transactions' => self::processing()->where('created_at', '>=', $startDate)->count(),
            'failed_transactions' => self::failed()->where('created_at', '>=', $startDate)->count(),
            'by_type' => [
                'deposits' => self::deposits()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'withdrawals' => self::withdrawals()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'commissions' => self::commissions()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'roi' => self::roi()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'investments' => self::investments()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'bonus' => self::bonus()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'profits' => self::profits()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'credit_adjustments' => self::creditAdjustments()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'debit_adjustments' => self::debitAdjustments()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
            ]
        ];
    }

    /**
     * Get transaction counts by status
     */
    public static function getStatusCounts(string $period = '30d'): array
    {
        $startDate = match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };

        return [
            'pending' => self::pending()->where('created_at', '>=', $startDate)->count(),
            'processing' => self::processing()->where('created_at', '>=', $startDate)->count(),
            'completed' => self::completed()->where('created_at', '>=', $startDate)->count(),
            'failed' => self::failed()->where('created_at', '>=', $startDate)->count(),
            'cancelled' => self::cancelled()->where('created_at', '>=', $startDate)->count(),
        ];
    }

    /**
     * Get transaction counts by type
     */
    public static function getTypeCounts(string $period = '30d'): array
    {
        $startDate = match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '1y' => now()->subYear(),
            default => now()->subDays(30),
        };

        return [
            'deposits' => self::deposits()->where('created_at', '>=', $startDate)->count(),
            'withdrawals' => self::withdrawals()->where('created_at', '>=', $startDate)->count(),
            'commissions' => self::commissions()->where('created_at', '>=', $startDate)->count(),
            'roi' => self::roi()->where('created_at', '>=', $startDate)->count(),
            'investments' => self::investments()->where('created_at', '>=', $startDate)->count(),
            'bonus' => self::bonus()->where('created_at', '>=', $startDate)->count(),
            'profits' => self::profits()->where('created_at', '>=', $startDate)->count(),
            'credit_adjustments' => self::creditAdjustments()->where('created_at', '>=', $startDate)->count(),
            'debit_adjustments' => self::debitAdjustments()->where('created_at', '>=', $startDate)->count(),
        ];
    }
}