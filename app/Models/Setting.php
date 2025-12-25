<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'category',
        'description',
        'is_public',
        'is_encrypted'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        // Clear cache when settings are modified
        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_{$setting->category}");
            Cache::forget('all_settings');
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            Cache::forget("settings_{$setting->category}");
            Cache::forget('all_settings');
        });
    }

    /**
     * Get setting value with proper type casting and decryption
     */
    public function getValueAttribute($value)
    {
        // Decrypt if encrypted
        if ($this->is_encrypted && $value) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return original value
                return $value;
            }
        }

        // Type casting
        return $this->castValue($value, $this->type);
    }

    /**
     * Set setting value with proper encryption
     */
    public function setValueAttribute($value)
    {
        // Convert to string for storage
        $stringValue = $this->convertToString($value, $this->type);
        
        // Encrypt if needed
        if ($this->is_encrypted && $stringValue) {
            $stringValue = Crypt::encryptString($stringValue);
        }

        $this->attributes['value'] = $stringValue;
    }

    /**
     * Cast value to proper type
     */
    private function castValue($value, $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'float' => (float) $value,
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Convert value to string for storage
     */
    private function convertToString($value, $type)
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'array', 'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Get setting by key with caching
     */
    public static function getValue($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set setting value
     */
    public static function setValue($key, $value, $type = 'string', $category = 'general', $description = null, $isPublic = false, $isEncrypted = false)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'category' => $category,
                'description' => $description,
                'is_public' => $isPublic,
                'is_encrypted' => $isEncrypted,
            ]
        );
    }

    /**
     * Get all settings by category
     */
    public static function getByCategory($category)
    {
        return Cache::remember("settings_{$category}", 3600, function () use ($category) {
            return static::where('category', $category)->get()->pluck('value', 'key');
        });
    }

    /**
     * Get all settings as key-value pairs
     */
    public static function getAllSettings()
    {
        return Cache::remember('all_settings', 3600, function () {
            return static::all()->pluck('value', 'key');
        });
    }

    /**
     * Bulk update settings
     */
    public static function bulkUpdate(array $settings, $category = null)
    {
        foreach ($settings as $key => $data) {
            if (is_array($data)) {
                static::setValue(
                    $key,
                    $data['value'],
                    $data['type'] ?? 'string',
                    $data['category'] ?? $category ?? 'general',
                    $data['description'] ?? null,
                    $data['is_public'] ?? false,
                    $data['is_encrypted'] ?? false
                );
            } else {
                static::setValue($key, $data, 'string', $category ?? 'general');
            }
        }
    }

    /**
     * Scope for public settings
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get default settings structure
     */
    public static function getDefaultSettings()
    {
        return [
            // General Settings
            'app_name' => [
                'value' => config('app.name', 'MLM Platform'),
                'type' => 'string',
                'category' => 'general',
                'description' => 'Application name',
                'is_public' => true
            ],
            'site_tagline' => [
                'value' => 'Advanced Prediction Trading Platform',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Site tagline/slogan',
                'is_public' => true
            ],
            'site_description' => [
                'value' => 'Advanced MLM prediction bot platform with real-time trading capabilities',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Site description for SEO',
                'is_public' => true
            ],
            'support_email' => [
                'value' => 'support@mlmtrial.live',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Support email address',
                'is_public' => true
            ],
            'default_currency' => [
                'value' => 'USD',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Default platform currency',
                'is_public' => true
            ],
            'date_format' => [
                'value' => 'Y-m-d',
                'type' => 'string',
                'category' => 'general',
                'description' => 'Default date format',
                'is_public' => true
            ],
            'allow_registration' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Allow new user registration',
                'is_public' => true
            ],
            'require_email_verification' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Require email verification for new users'
            ],
            'enable_referral_system' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Enable MLM/referral system',
                'is_public' => true
            ],
            'enable_prediction_bot' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Enable prediction bot trading',
                'is_public' => true
            ],
            'maintenance_mode' => [
                'value' => false,
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Platform maintenance mode'
            ],
            'enable_demo_mode' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'general',
                'description' => 'Enable demo trading accounts',
                'is_public' => true
            ],

            // Trading Settings
            'bot_prediction_accuracy' => [
                'value' => 75,
                'type' => 'integer',
                'category' => 'trading',
                'description' => 'Bot prediction accuracy percentage'
            ],
            'min_prediction_amount' => [
                'value' => 1,
                'type' => 'float',
                'category' => 'trading',
                'description' => 'Minimum prediction amount',
                'is_public' => true
            ],
            'max_prediction_amount' => [
                'value' => 1000,
                'type' => 'float',
                'category' => 'trading',
                'description' => 'Maximum prediction amount',
                'is_public' => true
            ],
            'prediction_duration_min' => [
                'value' => 30,
                'type' => 'integer',
                'category' => 'trading',
                'description' => 'Minimum prediction duration in seconds',
                'is_public' => true
            ],
            'prediction_duration_max' => [
                'value' => 300,
                'type' => 'integer',
                'category' => 'trading',
                'description' => 'Maximum prediction duration in seconds',
                'is_public' => true
            ],
            'daily_prediction_limit' => [
                'value' => 50,
                'type' => 'integer',
                'category' => 'trading',
                'description' => 'Daily prediction limit per user',
                'is_public' => true
            ],
            'enable_24_7_trading' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'trading',
                'description' => 'Enable 24/7 trading',
                'is_public' => true
            ],

            // Financial Settings
            'min_deposit_amount' => [
                'value' => 10,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Minimum deposit amount',
                'is_public' => true
            ],
            'max_deposit_amount' => [
                'value' => 10000,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Maximum deposit amount',
                'is_public' => true
            ],
            'deposit_fee_percentage' => [
                'value' => 0,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Deposit fee percentage'
            ],
            'min_withdrawal_amount' => [
                'value' => 20,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Minimum withdrawal amount',
                'is_public' => true
            ],
            'max_withdrawal_amount' => [
                'value' => 5000,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Maximum withdrawal amount',
                'is_public' => true
            ],
            'withdrawal_fee_percentage' => [
                'value' => 2,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Withdrawal fee percentage'
            ],

            // MLM Commission Settings
            'level_1_commission' => [
                'value' => 10,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Level 1 referral commission percentage'
            ],
            'level_2_commission' => [
                'value' => 5,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Level 2 referral commission percentage'
            ],
            'level_3_commission' => [
                'value' => 2,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Level 3 referral commission percentage'
            ],
            'max_mlm_levels' => [
                'value' => 3,
                'type' => 'integer',
                'category' => 'financial',
                'description' => 'Maximum MLM levels for commissions'
            ],
            'min_referral_deposit' => [
                'value' => 50,
                'type' => 'float',
                'category' => 'financial',
                'description' => 'Minimum referral deposit to earn commissions'
            ],

            // Security Settings
            'password_min_length' => [
                'value' => 8,
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Minimum password length'
            ],
            'max_login_attempts' => [
                'value' => 5,
                'type' => 'integer',
                'category' => 'security',
                'description' => 'Maximum login attempts before lockout'
            ],
            'enable_2fa' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Enable two-factor authentication support'
            ],
            'force_2fa_for_staff' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Force 2FA for staff accounts'
            ],
            'force_2fa_for_withdrawals' => [
                'value' => false,
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Require 2FA for withdrawals'
            ],
            'require_kyc_for_withdrawal' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Require KYC verification for withdrawals'
            ],
            'log_user_activities' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'security',
                'description' => 'Log user activities for security'
            ],
            'blocked_countries' => [
                'value' => '',
                'type' => 'string',
                'category' => 'security',
                'description' => 'Comma-separated list of blocked country codes'
            ],

            // API Settings
            'enable_auto_kyc_approval' => [
                'value' => false,
                'type' => 'boolean',
                'category' => 'api',
                'description' => 'Enable automatic KYC approval'
            ],
            'api_rate_limit_per_minute' => [
                'value' => 60,
                'type' => 'integer',
                'category' => 'api',
                'description' => 'API rate limit per minute'
            ],
            'api_rate_limit_per_hour' => [
                'value' => 1000,
                'type' => 'integer',
                'category' => 'api',
                'description' => 'API rate limit per hour'
            ],
            'enable_api_throttling' => [
                'value' => true,
                'type' => 'boolean',
                'category' => 'api',
                'description' => 'Enable API rate limiting'
            ],
        ];
    }
}