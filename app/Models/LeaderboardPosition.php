<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderboardPosition extends Model
{
    use HasFactory;

    protected $fillable = [
        'leaderboard_id',
        'user_id',
        'position',
        'referral_count',
        'prize_amount',
        'prize_awarded',
        'prize_awarded_at',
    ];

    protected $casts = [
        'prize_amount' => 'decimal:2',
        'prize_awarded' => 'boolean',
        'prize_awarded_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function leaderboard(): BelongsTo
    {
        return $this->belongsTo(Leaderboard::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeWinners($query)
    {
        return $query->where('prize_amount', '>', 0);
    }

    public function scopePrizeAwarded($query)
    {
        return $query->where('prize_awarded', true);
    }

    public function scopePrizePending($query)
    {
        return $query->where('prize_amount', '>', 0)
                    ->where('prize_awarded', false);
    }

    public function scopeTopPositions($query, $limit = 10)
    {
        return $query->orderBy('position')->limit($limit);
    }

    /**
     * Accessors
     */
    public function getPositionDisplayAttribute(): string
    {
        return match($this->position) {
            1 => '🥇 1st',
            2 => '🥈 2nd', 
            3 => '🥉 3rd',
            default => "#{$this->position}"
        };
    }

    public function getPositionBadgeClassAttribute(): string
    {
        return match($this->position) {
            1 => 'bg-warning text-dark', // Gold
            2 => 'bg-secondary text-white', // Silver
            3 => 'bg-warning text-dark', // Bronze (using warning for bronze-ish color)
            default => 'bg-primary text-white'
        };
    }

    public function getPrizeStatusBadgeClassAttribute(): string
    {
        if (!$this->prize_amount || $this->prize_amount <= 0) {
            return 'bg-light text-dark';
        }

        return $this->prize_awarded ? 'bg-success' : 'bg-warning text-dark';
    }

    public function getPrizeStatusTextAttribute(): string
    {
        if (!$this->prize_amount || $this->prize_amount <= 0) {
            return 'No Prize';
        }

        return $this->prize_awarded ? 'Awarded' : 'Pending';
    }

    /**
     * Helper Methods
     */
    public function isWinner(): bool
    {
        return $this->prize_amount > 0;
    }

    public function isPrizeAwarded(): bool
    {
        return $this->prize_awarded;
    }

    public function isPrizePending(): bool
    {
        return $this->isWinner() && !$this->isPrizeAwarded();
    }

    public function isTopThree(): bool
    {
        return $this->position <= 3;
    }

    public function getPositionIcon(): string
    {
        return match($this->position) {
            1 => 'iconamoon:trophy-duotone',
            2 => 'iconamoon:medal-duotone',
            3 => 'iconamoon:medal-duotone',
            default => 'iconamoon:hashtag-duotone'
        };
    }

    public function markPrizeAsAwarded(): bool
    {
        return $this->update([
            'prize_awarded' => true,
            'prize_awarded_at' => now(),
        ]);
    }

    public function getFormattedPrizeAmount(): string
    {
        if (!$this->prize_amount || $this->prize_amount <= 0) {
            return 'No Prize';
        }

        return '$' . number_format($this->prize_amount, 2);
    }
}