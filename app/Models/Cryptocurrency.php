<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cryptocurrency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'symbol',
        'icon',
        'network',
        'contract_address',
        'decimal_places',
        'min_withdrawal',
        'max_withdrawal',
        'withdrawal_fee',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'min_withdrawal' => 'decimal:8',
        'max_withdrawal' => 'decimal:8',
        'withdrawal_fee' => 'decimal:8',
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Get all crypto wallets for this cryptocurrency
     */
    public function cryptoWallets(): HasMany
    {
        return $this->hasMany(CryptoWallet::class, 'currency', 'symbol');
    }

    /**
     * Scope for active cryptocurrencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordered cryptocurrencies
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get formatted withdrawal fee with symbol
     */
    public function getFormattedWithdrawalFeeAttribute(): string
    {
        return number_format($this->withdrawal_fee, $this->decimal_places) . ' ' . $this->symbol;
    }

    /**
     * Get the icon URL
     */
    public function getIconUrlAttribute(): string
    {
        if ($this->icon && file_exists(public_path('images/crypto/' . $this->icon))) {
            return asset('images/crypto/' . $this->icon);
        }
        
        // Fallback to a default crypto icon or external service
        return "https://predictor.guru/images/icons/19.svg";
    }
}