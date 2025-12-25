<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use NotificationChannels\WebPush\PushSubscription as WebPushSubscription;

/**
 * App\Models\PushSubscription
 *
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string|null $public_key
 * @property string|null $auth_token
 * @property string $content_encoding
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read string $endpoint_domain
 * @property-read string $browser
 * @property-read string $browser_icon
 * @property-read bool $is_valid
 * @property-read bool $is_expired
 */
class PushSubscription extends WebPushSubscription
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'public_key',
        'auth_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user that owns the push subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the formatted endpoint domain.
     */
    public function getEndpointDomainAttribute(): string
    {
        if (empty($this->endpoint)) {
            return 'unknown';
        }

        return parse_url($this->endpoint, PHP_URL_HOST) ?? 'unknown';
    }

    /**
     * Get the browser type based on endpoint.
     */
    public function getBrowserAttribute(): string
    {
        if (empty($this->endpoint)) {
            return 'Unknown';
        }

        $endpoint = $this->endpoint;
        
        if (str_contains($endpoint, 'fcm.googleapis.com')) {
            return 'Chrome/Edge';
        }
        
        if (str_contains($endpoint, 'mozilla.com') || str_contains($endpoint, 'mozaws.net')) {
            return 'Firefox';
        }
        
        if (str_contains($endpoint, 'apple.com') || str_contains($endpoint, 'push.apple.com')) {
            return 'Safari';
        }
        
        if (str_contains($endpoint, 'microsoft.com') || str_contains($endpoint, 'notify.windows.com')) {
            return 'Edge';
        }
        
        return 'Unknown';
    }

    /**
     * Get the browser icon class (Font Awesome).
     */
    public function getBrowserIconAttribute(): string
    {
        return match ($this->browser) {
            'Chrome/Edge' => 'fab fa-chrome',
            'Firefox' => 'fab fa-firefox-browser',
            'Safari' => 'fab fa-safari',
            'Edge' => 'fab fa-edge',
            default => 'fas fa-globe',
        };
    }

    /**
     * Check if subscription is valid.
     */
    public function getIsValidAttribute(): bool
    {
        return !empty($this->endpoint) && 
               !empty($this->public_key) && 
               !empty($this->auth_token);
    }

    /**
     * Check if subscription is expired (older than 6 months).
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->updated_at && $this->updated_at->lt(now()->subMonths(6));
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to get only valid subscriptions.
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNotNull('endpoint')
                    ->whereNotNull('public_key')
                    ->whereNotNull('auth_token');
    }

    /**
     * Scope to get expired subscriptions.
     */
    public function scopeExpired(Builder $query, int $months = 6): Builder
    {
        return $query->where('updated_at', '<', now()->subMonths($months));
    }

    /**
     * Scope to get active subscriptions (updated recently).
     */
    public function scopeActive(Builder $query, int $days = 30): Builder
    {
        return $query->where('updated_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get subscriptions by browser type.
     */
    public function scopeByBrowser(Builder $query, string $browser): Builder
    {
        return match (strtolower($browser)) {
            'chrome', 'edge' => $query->where('endpoint', 'like', '%fcm.googleapis.com%'),
            'firefox' => $query->where(function ($q) {
                $q->where('endpoint', 'like', '%mozilla.com%')
                  ->orWhere('endpoint', 'like', '%mozaws.net%');
            }),
            'safari' => $query->where(function ($q) {
                $q->where('endpoint', 'like', '%apple.com%')
                  ->orWhere('endpoint', 'like', '%push.apple.com%');
            }),
            default => $query,
        };
    }

    /**
     * Scope to get recent subscriptions.
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get subscriptions for a specific user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create or update subscription from request data.
     */
    public static function createFromSubscription(array $subscription, int $userId): self
    {
        // Validate required fields
        if (empty($subscription['endpoint']) || empty($subscription['keys'])) {
            throw new \InvalidArgumentException('Invalid subscription data: endpoint and keys are required');
        }

        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'endpoint' => $subscription['endpoint']
            ],
            [
                'public_key' => $subscription['keys']['p256dh'] ?? null,
                'auth_token' => $subscription['keys']['auth'] ?? null,
                'content_encoding' => $subscription['contentEncoding'] ?? 'aes128gcm',
            ]
        );
    }

    /**
     * Check if subscription is still valid.
     */
    public function isValid(): bool
    {
        return $this->is_valid;
    }

    /**
     * Check if subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->is_expired;
    }

    /**
     * Touch the updated_at timestamp to mark as active.
     */
    public function markAsActive(): bool
    {
        return $this->touch();
    }

    /**
     * Safely delete this subscription.
     */
    public function deleteSafely(): bool
    {
        try {
            return $this->delete();
        } catch (\Exception $e) {
            \Log::error('Failed to delete push subscription', [
                'subscription_id' => $this->id,
                'user_id' => $this->user_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get comprehensive subscription statistics.
     */
    public static function getStatistics(): array
    {
        $total = self::count();
        $valid = self::valid()->count();
        $active = self::active(30)->count();
        $expired = self::expired()->count();
        
        $browserStats = self::selectRaw('
            CASE 
                WHEN endpoint LIKE "%fcm.googleapis.com%" THEN "Chrome/Edge"
                WHEN endpoint LIKE "%mozilla.com%" OR endpoint LIKE "%mozaws.net%" THEN "Firefox" 
                WHEN endpoint LIKE "%apple.com%" OR endpoint LIKE "%push.apple.com%" THEN "Safari"
                WHEN endpoint LIKE "%microsoft.com%" OR endpoint LIKE "%notify.windows.com%" THEN "Edge"
                ELSE "Other"
            END as browser,
            COUNT(*) as count
        ')
        ->groupBy('browser')
        ->pluck('count', 'browser')
        ->toArray();

        // Get user engagement stats
        $usersWithSubscriptions = self::distinct('user_id')->count('user_id');
        $avgSubscriptionsPerUser = $usersWithSubscriptions > 0 
            ? round($total / $usersWithSubscriptions, 2) 
            : 0;

        return [
            'total' => $total,
            'valid' => $valid,
            'active' => $active,
            'expired' => $expired,
            'browsers' => $browserStats,
            'users_with_subscriptions' => $usersWithSubscriptions,
            'avg_subscriptions_per_user' => $avgSubscriptionsPerUser,
            'activity_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
            'validity_rate' => $total > 0 ? round(($valid / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Clean up expired subscriptions.
     */
    public static function cleanupExpired(int $months = 6): int
    {
        try {
            $deleted = self::expired($months)->delete();
            
            \Log::info('Cleaned up expired push subscriptions', [
                'deleted_count' => $deleted,
                'older_than_months' => $months
            ]);
            
            return $deleted;
        } catch (\Exception $e) {
            \Log::error('Failed to cleanup expired subscriptions', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Remove duplicate subscriptions for same user/endpoint.
     */
    public static function removeDuplicates(): int
    {
        $duplicates = self::selectRaw('user_id, endpoint, COUNT(*) as count')
            ->groupBy('user_id', 'endpoint')
            ->having('count', '>', 1)
            ->get();

        $removed = 0;
        
        foreach ($duplicates as $duplicate) {
            $subscriptions = self::where('user_id', $duplicate->user_id)
                ->where('endpoint', $duplicate->endpoint)
                ->orderBy('updated_at', 'desc')
                ->get();

            // Keep the most recent one, delete the rest
            $subscriptions->skip(1)->each(function ($subscription) use (&$removed) {
                if ($subscription->delete()) {
                    $removed++;
                }
            });
        }

        if ($removed > 0) {
            \Log::info('Removed duplicate push subscriptions', [
                'removed_count' => $removed
            ]);
        }

        return $removed;
    }

    /**
     * Clean up invalid subscriptions (missing required fields).
     */
    public static function cleanupInvalid(): int
    {
        try {
            $deleted = self::where(function ($query) {
                $query->whereNull('endpoint')
                      ->orWhereNull('public_key')
                      ->orWhereNull('auth_token');
            })->delete();
            
            if ($deleted > 0) {
                \Log::info('Cleaned up invalid push subscriptions', [
                    'deleted_count' => $deleted
                ]);
            }
            
            return $deleted;
        } catch (\Exception $e) {
            \Log::error('Failed to cleanup invalid subscriptions', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Perform full maintenance (cleanup expired, invalid, and duplicates).
     */
    public static function performMaintenance(): array
    {
        $results = [
            'expired_removed' => self::cleanupExpired(),
            'invalid_removed' => self::cleanupInvalid(),
            'duplicates_removed' => self::removeDuplicates(),
        ];

        $results['total_removed'] = array_sum($results);

        \Log::info('Push subscription maintenance completed', $results);

        return $results;
    }

    /**
     * Get subscriptions that need verification (not updated in 30+ days).
     */
    public static function needsVerification(): Builder
    {
        return self::valid()
            ->where('updated_at', '<', now()->subDays(30))
            ->where('updated_at', '>', now()->subMonths(6));
    }

    /**
     * Get browser distribution as percentage.
     */
    public static function getBrowserDistribution(): array
    {
        $total = self::count();
        
        if ($total === 0) {
            return [];
        }

        $stats = self::selectRaw('
            CASE 
                WHEN endpoint LIKE "%fcm.googleapis.com%" THEN "Chrome/Edge"
                WHEN endpoint LIKE "%mozilla.com%" OR endpoint LIKE "%mozaws.net%" THEN "Firefox" 
                WHEN endpoint LIKE "%apple.com%" OR endpoint LIKE "%push.apple.com%" THEN "Safari"
                WHEN endpoint LIKE "%microsoft.com%" OR endpoint LIKE "%notify.windows.com%" THEN "Edge"
                ELSE "Other"
            END as browser,
            COUNT(*) as count
        ')
        ->groupBy('browser')
        ->get()
        ->mapWithKeys(function ($item) use ($total) {
            return [
                $item->browser => [
                    'count' => $item->count,
                    'percentage' => round(($item->count / $total) * 100, 2)
                ]
            ];
        })
        ->toArray();

        return $stats;
    }
}