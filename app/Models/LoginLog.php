<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country',
        'city',
        'is_successful',
        'failure_reason',
        'login_at',
        'logout_at'
    ];

    protected $casts = [
        'is_successful' => 'boolean',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the user that owns the login log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope for failed logins
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    /**
     * Get formatted duration
     */
    public function getSessionDurationAttribute(): ?string
    {
        if (!$this->logout_at) {
            return null;
        }

        $minutes = $this->login_at->diffInMinutes($this->logout_at);
        
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    /**
     * Get device icon
     */
    public function getDeviceIconAttribute(): string
    {
        return match(strtolower($this->device_type ?? 'desktop')) {
            'mobile' => 'iconamoon:phone-duotone',
            'tablet' => 'hugeicons:tablet-01',
            default => 'tabler:device-desktop',
        };
    }

    /**
     * Log a login attempt
     */
    public static function logLogin(
        int $userId,
        bool $isSuccessful = true,
        ?string $failureReason = null
    ): self {
        $userAgent = request()->userAgent();
        $parser = new \Jenssegers\Agent\Agent();
        $parser->setUserAgent($userAgent);

        return self::create([
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'device_type' => $parser->isPhone() ? 'mobile' : ($parser->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $parser->browser(),
            'platform' => $parser->platform(),
            'country' => self::getCountryFromIp(request()->ip()),
            'city' => self::getCityFromIp(request()->ip()),
            'is_successful' => $isSuccessful,
            'failure_reason' => $failureReason,
            'login_at' => now(),
        ]);
    }

    /**
     * Get country from IP (placeholder - implement with IP geolocation service)
     */
    private static function getCountryFromIp(string $ip): ?string
    {
        // Implement with a service like ipapi.com, ipstack.com, etc.
        // For now, return null or use a basic implementation
        return null;
    }

    /**
     * Get city from IP (placeholder - implement with IP geolocation service)
     */
    private static function getCityFromIp(string $ip): ?string
    {
        // Implement with a service like ipapi.com, ipstack.com, etc.
        return null;
    }
}