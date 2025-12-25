<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserEarning extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'today',
        'last_earning_date',
    ];

    protected function casts(): array
    {
        return [
            'last_earning_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get total earnings as float
     */
    public function getTotalFloatAttribute(): float
    {
        return (float) $this->total;
    }

    /**
     * Get today earnings as float
     */
    public function getTodayFloatAttribute(): float
    {
        return (float) $this->today;
    }

    /**
     * Add to total earnings
     */
    public function addToTotal(float $amount): void
    {
        $this->increment('total', $amount);
    }

    /**
     * Add to today's earnings
     */
    public function addToToday(float $amount): void
    {
        // Reset today's earnings if it's a new day
        if ($this->last_earning_date && !$this->last_earning_date->isToday()) {
            $this->update([
                'today' => $amount,
                'last_earning_date' => now()->toDate(),
            ]);
        } else {
            $this->increment('today', $amount);
            $this->update(['last_earning_date' => now()->toDate()]);
        }
    }

    /**
     * Reset today's earnings (called daily via cron)
     */
    public function resetDailyEarnings(): void
    {
        $this->update([
            'today' => '0.00',
            'last_earning_date' => now()->toDate(),
        ]);
    }

    /**
     * Get formatted total earnings
     */
    public function getFormattedTotalAttribute(): string
    {
        return number_format((float) $this->total, 2);
    }

    /**
     * Get formatted today earnings
     */
    public function getFormattedTodayAttribute(): string
    {
        return number_format((float) $this->today, 2);
    }

    /**
     * Check if user earned today
     */
    public function hasEarnedToday(): bool
    {
        return $this->last_earning_date && $this->last_earning_date->isToday() && $this->today > 0;
    }

    /**
     * Get days since last earning
     */
    public function getDaysSinceLastEarningAttribute(): int
    {
        if (!$this->last_earning_date) {
            return 0;
        }

        return $this->last_earning_date->diffInDays(now());
    }

    /**
     * Scope for users who earned today
     */
    public function scopeEarnedToday($query)
    {
        return $query->whereDate('last_earning_date', today())
                    ->where('today', '>', 0);
    }

    /**
     * Scope for users with total earnings above amount
     */
    public function scopeWithTotalAbove($query, float $amount)
    {
        return $query->where('total', '>', $amount);
    }

    /**
     * Scope for users with today earnings above amount
     */
    public function scopeWithTodayAbove($query, float $amount)
    {
        return $query->where('today', '>', $amount);
    }
}