<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnnouncementView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'announcement_id',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user who viewed the announcement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the announcement that was viewed.
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get formatted viewed date.
     */
    public function getFormattedViewedAtAttribute(): string
    {
        return $this->viewed_at->format('M d, Y \a\t g:i A');
    }

    /**
     * Get how long ago the announcement was viewed.
     */
    public function getViewedAgoAttribute(): string
    {
        return $this->viewed_at->diffForHumans();
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for views by user.
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope for views by announcement.
     */
    public function scopeByAnnouncement($query, Announcement $announcement)
    {
        return $query->where('announcement_id', $announcement->id);
    }

    /**
     * Scope for recent views.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('viewed_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for views today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    /**
     * Scope for views this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope for views this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('viewed_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}