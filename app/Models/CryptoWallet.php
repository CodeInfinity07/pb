<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'currency',
        'name',
        'address',
        'balance',
        'usd_rate',
        'is_active'
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'usd_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the crypto wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cryptocurrency details
     */
    public function cryptocurrency(): BelongsTo
    {
        return $this->belongsTo(Cryptocurrency::class, 'currency', 'symbol');
    }

    /**
     * Get the USD value of the balance
     */
    public function getUsdValueAttribute(): float
    {
        return (float) ($this->balance * $this->usd_rate);
    }

    /**
     * Check if wallet has a withdrawal address set
     */
    public function hasAddress(): bool
    {
        return !empty($this->address);
    }

    /**
     * Get the appropriate address for withdrawals
     */
    public function getWithdrawalAddressAttribute(): ?string
    {
        return $this->address;
    }

    /**
     * Check if wallet can accept deposits
     */
    public function canReceiveDeposits(): bool
    {
        return $this->is_active && $this->cryptocurrency->is_active;
    }

    /**
     * Check if wallet can make withdrawals
     */
    public function canMakeWithdrawals(): bool
    {
        return $this->is_active && 
               $this->cryptocurrency->is_active && 
               $this->hasAddress() && 
               $this->balance > 0;
    }

    /**
     * Get available balance for withdrawal (minus fees)
     */
    public function getAvailableWithdrawalAmountAttribute(): float
    {
        $fee = $this->cryptocurrency->withdrawal_fee ?? 0;
        return max(0, $this->balance - $fee);
    }

    /**
     * Get formatted balance with currency symbol
     */
    public function getFormattedBalanceAttribute(): string
    {
        $decimals = $this->cryptocurrency->decimal_places ?? 8;
        return number_format($this->balance, $decimals) . ' ' . $this->currency;
    }

    /**
     * Get formatted USD value
     */
    public function getFormattedUsdValueAttribute(): string
    {
        return '$' . number_format($this->getUsdValueAttribute(), 2);
    }

    /**
     * Scope for active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for wallets with balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('balance', '>', 0);
    }

    /**
     * Scope for wallets that can make withdrawals
     */
    public function scopeCanWithdraw($query)
    {
        return $query->where('is_active', true)
                    ->where('balance', '>', 0)
                    ->whereNotNull('address')
                    ->whereHas('cryptocurrency', function($q) {
                        $q->where('is_active', true);
                    });
    }

    /**
     * Scope for wallets with specific currency
     */
    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Add balance to wallet
     */
    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Subtract balance from wallet
     */
    public function subtractBalance(float $amount): bool
    {
        if ($this->balance >= $amount) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Process a withdrawal (subtract amount + fee)
     */
    public function processWithdrawal(float $amount): bool
    {
        $fee = $this->cryptocurrency->withdrawal_fee ?? 0;
        $totalDeduction = $amount + $fee;
        
        if ($this->balance >= $totalDeduction) {
            $this->decrement('balance', $totalDeduction);
            return true;
        }
        return false;
    }

    /**
     * Get withdrawal limits
     */
    public function getWithdrawalLimitsAttribute(): array
    {
        $crypto = $this->cryptocurrency;
        $fee = $crypto->withdrawal_fee ?? 0;
        
        return [
            'min' => $crypto->min_withdrawal ?? 0,
            'max' => min(
                $this->balance - $fee,
                $crypto->max_withdrawal ?? PHP_FLOAT_MAX
            ),
            'fee' => $fee
        ];
    }
}