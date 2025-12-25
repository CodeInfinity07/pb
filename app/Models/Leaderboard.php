<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'type', // New field
        'start_date',
        'end_date',
        'show_to_users',
        'max_positions',
        'referral_type',
        'prize_structure',
        'target_referrals', // New field
        'target_prize_amount', // New field
        'max_winners', // New field
        'prizes_distributed',
        'created_by',
        'prizes_distributed_at',
        'prizes_distributed_by',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'show_to_users' => 'boolean',
        'prizes_distributed' => 'boolean',
        'prize_structure' => 'array',
        'target_prize_amount' => 'decimal:2',
        'prizes_distributed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prizeDistributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prizes_distributed_by');
    }

    public function positions(): HasMany
    {
        return $this->hasMany(LeaderboardPosition::class);
    }

    public function topPositions(): HasMany
    {
        return $this->hasMany(LeaderboardPosition::class)->orderBy('position');
    }

    public function qualifiedPositions(): HasMany
    {
        return $this->hasMany(LeaderboardPosition::class)
            ->where('referral_count', '>=', $this->target_referrals ?? 0);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeVisible($query)
    {
        return $query->where('show_to_users', true);
    }

    public function scopeCurrent($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeCompetitive($query)
    {
        return $query->where('type', 'competitive');
    }

    public function scopeTarget($query)
    {
        return $query->where('type', 'target');
    }

    /**
     * Accessors & Mutators
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-success',
            'completed' => 'bg-primary',
            'inactive' => 'bg-secondary',
            default => 'bg-secondary'
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            'competitive' => 'bg-primary',
            'target' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    public function getTypeDisplayAttribute(): string
    {
        return match($this->type) {
            'competitive' => 'Competitive Ranking',
            'target' => 'Target Achievement',
            default => 'Competitive Ranking'
        };
    }

    public function getReferralTypeDisplayAttribute(): string
    {
        return match($this->referral_type) {
            'all' => 'All Referrals',
            'first_level' => 'First Level Only',
            'verified_only' => 'Verified Users Only',
            default => 'All Referrals'
        };
    }

    public function getDurationDisplayAttribute(): string
    {
        $start = $this->start_date->format('M d, Y');
        $end = $this->end_date->format('M d, Y');
        
        if ($start === $end) {
            return $start;
        }
        
        return "{$start} - {$end}";
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->status !== 'active') {
            return 0;
        }

        return max(0, now()->diffInDays($this->end_date, false));
    }

    public function getTotalPrizeAmountAttribute(): float
    {
        if ($this->type === 'target') {
            // For target-based, calculate potential total based on qualified users
            $qualifiedCount = $this->getQualifiedCount();
            return $this->target_prize_amount * $qualifiedCount;
        }

        if (!$this->prize_structure) {
            return 0.0;
        }

        return collect($this->prize_structure)->sum('amount');
    }

    public function getMaxPossiblePrizeAmountAttribute(): float
    {
        if ($this->type === 'target') {
            // Calculate maximum possible if all users reach target
            $maxWinners = $this->max_winners ?: 1000; // Default reasonable limit
            return $this->target_prize_amount * $maxWinners;
        }

        return $this->getTotalPrizeAmountAttribute();
    }

    /**
     * Helper Methods
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               now()->between($this->start_date, $this->end_date);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->end_date < now();
    }

    public function isUpcoming(): bool
    {
        return $this->start_date > now();
    }

    public function isCompetitive(): bool
    {
        return $this->type === 'competitive';
    }

    public function isTarget(): bool
    {
        return $this->type === 'target';
    }

    public function canActivate(): bool
    {
        return $this->status === 'inactive' && $this->start_date <= now();
    }

    public function canComplete(): bool
    {
        return $this->status === 'active' && $this->end_date < now();
    }

    public function canDistributePrizes(): bool
    {
        return $this->status === 'completed' && 
               !$this->prizes_distributed && 
               $this->positions()->exists();
    }

    public function hasEnded(): bool
    {
        return $this->end_date < now();
    }

    public function getProgress(): int
    {
        if ($this->isUpcoming()) {
            return 0;
        }

        if ($this->hasEnded()) {
            return 100;
        }

        $total = $this->start_date->diffInSeconds($this->end_date);
        $elapsed = $this->start_date->diffInSeconds(now());

        return round(($elapsed / $total) * 100);
    }

    public function getUserPosition(User $user): ?LeaderboardPosition
    {
        return $this->positions()->where('user_id', $user->id)->first();
    }

    public function getUserRank(User $user): ?int
    {
        $position = $this->getUserPosition($user);
        return $position ? $position->position : null;
    }

    public function getParticipantsCount(): int
    {
        return $this->positions()->count();
    }

    public function getWinnersCount(): int
    {
        if ($this->type === 'target') {
            return $this->getQualifiedCount();
        }

        return $this->positions()->where('prize_amount', '>', 0)->count();
    }

    public function getQualifiedCount(): int
    {
        if ($this->type !== 'target') {
            return 0;
        }

        return $this->positions()
            ->where('referral_count', '>=', $this->target_referrals)
            ->count();
    }

    public function userQualifies(User $user): bool
    {
        if ($this->type !== 'target') {
            return false;
        }

        $position = $this->getUserPosition($user);
        return $position && $position->referral_count >= $this->target_referrals;
    }

    public function getTargetProgress(User $user): float
    {
        if ($this->type !== 'target') {
            return 0;
        }

        $position = $this->getUserPosition($user);
        if (!$position) {
            return 0;
        }

        return min(100, ($position->referral_count / $this->target_referrals) * 100);
    }

    /**
     * Calculate and update leaderboard positions
     */
    public function calculatePositions(): void
    {
        app('App\Services\LeaderboardService')->calculatePositions($this);
    }

    /**
     * Distribute prizes to winners
     */
    public function distributePrizes(): bool
    {
        return app('App\Services\LeaderboardService')->distributePrizes($this);
    }

    /**
     * Get formatted prize information for display
     */
    public function getPrizeInfoAttribute(): string
    {
        if ($this->type === 'target') {
            $info = "Target: {$this->target_referrals} referrals = $" . number_format($this->target_prize_amount, 2);
            if ($this->max_winners) {
                $info .= " (Max {$this->max_winners} winners)";
            }
            return $info;
        }

        if (!$this->prize_structure) {
            return 'No prizes configured';
        }

        $prizeCount = count($this->prize_structure);
        $totalAmount = $this->getTotalPrizeAmountAttribute();
        
        return "{$prizeCount} prizes totaling $" . number_format($totalAmount, 2);
    }
}