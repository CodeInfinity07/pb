<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'country',
        'city',
        'avatar',
        'date_of_birth',
        'gender',
        'address',
        'postal_code',
        'state_province',
        'referrallink',
        'treferrallink',
        'level',
        'total_investments',
        'total_deposit',
        'total_withdraw',
        'last_deposit',
        'last_withdraw',
        'kyc_status',
        'kyc_submitted_at',
        'kyc_verified_at',
        'kyc_rejection_reason',
        'kyc_session_id',
        'kyc_documents',
        'uname',        // Add this
        'upwd',         // Add this  
        'umoney',       // Add this
        'game_linked_at', // Add this
        'game_settings',
        'referral_count',
        'total_commission_earned',
        'pending_commission',
        'max_referral_level',
        'email_notifications',
        'sms_notifications',
        'preferred_language',
        'timezone',
        'phone_verified',
        'phone_verified_at',
        'two_factor_enabled',
        'two_factor_secret',
        'tax_id',
        'business_name',
        'business_address',
        'facebook_url',
        'twitter_url',
        'linkedin_url',
        'telegram_username',
        'whatsapp_number',
        'metadata',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'kyc_submitted_at' => 'datetime',
            'kyc_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'total_investments' => 'decimal:2',
            'total_deposit' => 'decimal:2',
            'total_withdraw' => 'decimal:2',
            'last_deposit' => 'decimal:2',
            'last_withdraw' => 'decimal:2',
            'total_commission_earned' => 'decimal:2',
            'pending_commission' => 'decimal:2',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'phone_verified' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'kyc_documents' => 'array',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the full address
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state_province,
            $this->postal_code,
            $this->getCountryNameAttribute(),
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get country name from country code
     */
    public function getCountryNameAttribute(): string
    {
        $countries = [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BD' => 'Bangladesh',
            'BE' => 'Belgium',
            'BR' => 'Brazil',
            'CA' => 'Canada',
            'CN' => 'China',
            'FR' => 'France',
            'DE' => 'Germany',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'MY' => 'Malaysia',
            'NL' => 'Netherlands',
            'NG' => 'Nigeria',
            'PK' => 'Pakistan',
            'PH' => 'Philippines',
            'RU' => 'Russia',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'TH' => 'Thailand',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'VN' => 'Vietnam',
        ];

        return $countries[$this->country] ?? $this->country;
    }

    /**
     * Check if user is KYC verified
     */
    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'verified';
    }

    /**
     * Check if KYC is pending
     */
    public function isKycPending(): bool
    {
        return in_array($this->kyc_status, ['pending', 'submitted', 'under_review']);
    }

    /**
     * Check if KYC was rejected
     */
    public function isKycRejected(): bool
    {
        return $this->kyc_status === 'rejected';
    }

    /**
     * Get KYC status badge class
     */
    public function getKycStatusBadgeClassAttribute(): string
    {
        return match($this->kyc_status) {
            'verified' => 'bg-success',
            'rejected' => 'bg-danger',
            'under_review' => 'bg-warning',
            'submitted' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    /**
     * Get avatar URL with fallback
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && file_exists(public_path($this->avatar))) {
            return asset($this->avatar);
        }

        // Default avatar based on gender or initials
        $initials = substr($this->user->first_name, 0, 1) . substr($this->user->last_name, 0, 1);
        return "https://ui-avatars.com/api/?name={$initials}&background=007bff&color=fff&size=150";
    }

    /**
     * Scope for KYC verified users
     */
    public function scopeKycVerified($query)
    {
        return $query->where('kyc_status', 'verified');
    }

    /**
     * Scope for phone verified users
     */
    public function scopePhoneVerified($query)
    {
        return $query->where('phone_verified', true);
    }

    /**
     * Scope by country
     */
    public function scopeByCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Check if user is phone verified (enhanced version)
     */
    public function isPhoneVerified(): bool
    {
        return $this->phone_verified && $this->phone_verified_at !== null;
    }

    /**
     * Get phone verification date in human readable format
     */
    public function getPhoneVerifiedDateAttribute(): ?string
    {
        if (!$this->phone_verified_at) {
            return null;
        }

        return $this->phone_verified_at->format('M d, Y \a\t g:i A');
    }

    /**
     * Get how long ago phone was verified
     */
    public function getPhoneVerifiedAgoAttribute(): ?string
    {
        if (!$this->phone_verified_at) {
            return null;
        }

        return $this->phone_verified_at->diffForHumans();
    }

    /**
     * Check if phone verification is recent (within specified days)
     */
    public function isPhoneVerificationRecent(int $days = 30): bool
    {
        if (!$this->phone_verified_at) {
            return false;
        }

        return $this->phone_verified_at->isAfter(now()->subDays($days));
    }

    /**
     * Get phone verification age in days
     */
    public function getPhoneVerificationAgeInDays(): ?int
    {
        if (!$this->phone_verified_at) {
            return null;
        }

        return $this->phone_verified_at->diffInDays(now());
    }

    /**
     * Scope for recently phone verified users
     */
    public function scopeRecentlyPhoneVerified($query, int $days = 30)
    {
        return $query->where('phone_verified', true)
                    ->where('phone_verified_at', '>=', now()->subDays($days));
    }
}