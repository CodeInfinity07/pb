<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;  // Add this
use App\Models\CryptoWallet;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'locked_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'locked_balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

     // Relationship to crypto wallet
    public function cryptoWallet(): HasOne
    {
        return $this->hasOne(CryptoWallet::class, 'user_id', 'user_id');
    }

    public function getBalanceAttribute(): float
    {
        return $this->cryptoWallet->balance ?? 0;
    }

    /**
     * Get available balance (total - locked)
     */
    public function getAvailableBalanceAttribute(): float
    {
        return $this->balance - $this->locked_balance;
    }

    /**
     * Add to balance
     */
    public function addBalance(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    /**
     * Subtract from balance
     */
    public function subtractBalance(float $amount): bool
    {
        if ($this->getAvailableBalanceAttribute() >= $amount) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Lock balance (for pending withdrawals)
     */
    public function lockBalance(float $amount): bool
    {
        if ($this->getAvailableBalanceAttribute() >= $amount) {
            $this->increment('locked_balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Unlock balance
     */
    public function unlockBalance(float $amount): void
    {
        $unlockAmount = min($amount, $this->locked_balance);
        $this->decrement('locked_balance', $unlockAmount);
    }

    /**
     * Transfer locked balance to main balance (on withdrawal completion)
     */
    public function processWithdrawal(float $amount): bool
    {
        if ($this->locked_balance >= $amount) {
            $this->decrement('locked_balance', $amount);
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2);
    }

    /**
     * Get formatted available balance
     */
    public function getFormattedAvailableBalanceAttribute(): string
    {
        return number_format($this->getAvailableBalanceAttribute(), 2);
    }

    /**
     * Get formatted locked balance
     */
    public function getFormattedLockedBalanceAttribute(): string
    {
        return number_format($this->locked_balance, 2);
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->getAvailableBalanceAttribute() >= $amount;
    }

    /**
     * Scope for users with balance above amount
     */
    public function scopeWithBalanceAbove($query, float $amount)
    {
        return $query->where('balance', '>', $amount);
    }

    /**
     * Scope for users with available balance above amount
     */
    public function scopeWithAvailableBalanceAbove($query, float $amount)
    {
        return $query->whereRaw('(balance - locked_balance) > ?', [$amount]);
    }
}