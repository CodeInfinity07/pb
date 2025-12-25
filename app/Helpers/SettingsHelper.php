<?php

// app/Helpers/SettingsHelper.php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('getSetting')) {
    /**
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getSetting(string $key, $default = null)
    {
        return Setting::getValue($key, $default);
    }
}

if (!function_exists('setSetting')) {
    /**
     * Set a setting value.
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $category
     * @param string|null $description
     * @param bool $isPublic
     * @param bool $isEncrypted
     * @return \App\Models\Setting
     */
    function setSetting(
        string $key, 
        $value, 
        string $type = 'string', 
        string $category = 'general', 
        string $description = null, 
        bool $isPublic = false, 
        bool $isEncrypted = false
    ) {
        return Setting::setValue($key, $value, $type, $category, $description, $isPublic, $isEncrypted);
    }
}

if (!function_exists('getSettingsByCategory')) {
    /**
     * Get all settings for a category.
     *
     * @param string $category
     * @return \Illuminate\Support\Collection
     */
    function getSettingsByCategory(string $category)
    {
        return Setting::getByCategory($category);
    }
}

if (!function_exists('getAllSettings')) {
    /**
     * Get all settings as key-value pairs.
     *
     * @return \Illuminate\Support\Collection
     */
    function getAllSettings()
    {
        return Setting::getAllSettings();
    }
}

if (!function_exists('isMaintenanceMode')) {
    /**
     * Check if platform is in maintenance mode.
     *
     * @return bool
     */
    function isMaintenanceMode(): bool
    {
        return getSetting('maintenance_mode', false);
    }
}

if (!function_exists('isTradingEnabled')) {
    /**
     * Check if trading is currently enabled.
     *
     * @return bool
     */
    function isTradingEnabled(): bool
    {
        if (isMaintenanceMode()) {
            return false;
        }

        if (!getSetting('enable_prediction_bot', true)) {
            return false;
        }

        // Check trading hours if 24/7 trading is disabled
        if (!getSetting('enable_24_7_trading', true)) {
            $now = now();
            $startTime = getSetting('trading_start_time', '00:00');
            $endTime = getSetting('trading_end_time', '23:59');
            
            $start = \Carbon\Carbon::createFromFormat('H:i', $startTime);
            $end = \Carbon\Carbon::createFromFormat('H:i', $endTime);
            
            if ($now->lt($start) || $now->gt($end)) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('canRegister')) {
    /**
     * Check if new user registration is allowed.
     *
     * @return bool
     */
    function canRegister(): bool
    {
        return getSetting('allow_registration', true) && !isMaintenanceMode();
    }
}

if (!function_exists('getMinDepositAmount')) {
    /**
     * Get minimum deposit amount.
     *
     * @return float
     */
    function getMinDepositAmount(): float
    {
        return (float) getSetting('min_deposit_amount', 10);
    }
}

if (!function_exists('getMaxDepositAmount')) {
    /**
     * Get maximum deposit amount.
     *
     * @return float
     */
    function getMaxDepositAmount(): float
    {
        return (float) getSetting('max_deposit_amount', 10000);
    }
}

if (!function_exists('getMinWithdrawalAmount')) {
    /**
     * Get minimum withdrawal amount.
     *
     * @return float
     */
    function getMinWithdrawalAmount(): float
    {
        return (float) getSetting('min_withdrawal_amount', 20);
    }
}

if (!function_exists('getMaxWithdrawalAmount')) {
    /**
     * Get maximum withdrawal amount.
     *
     * @return float
     */
    function getMaxWithdrawalAmount(): float
    {
        return (float) getSetting('max_withdrawal_amount', 5000);
    }
}

if (!function_exists('getWithdrawalFee')) {
    /**
     * Calculate withdrawal fee for given amount.
     *
     * @param float $amount
     * @return float
     */
    function getWithdrawalFee(float $amount): float
    {
        $feePercentage = (float) getSetting('withdrawal_fee_percentage', 2);
        return ($amount * $feePercentage) / 100;
    }
}

if (!function_exists('getDepositFee')) {
    /**
     * Calculate deposit fee for given amount.
     *
     * @param float $amount
     * @return float
     */
    function getDepositFee(float $amount): float
    {
        $feePercentage = (float) getSetting('deposit_fee_percentage', 0);
        return ($amount * $feePercentage) / 100;
    }
}

if (!function_exists('getReferralCommission')) {
    /**
     * Get referral commission percentage for specific level.
     *
     * @param int $level
     * @return float
     */
    function getReferralCommission(int $level): float
    {
        $maxLevels = (int) getSetting('max_mlm_levels', 3);
        
        if ($level > $maxLevels) {
            return 0;
        }

        return (float) getSetting("level_{$level}_commission", 0);
    }
}

if (!function_exists('calculateReferralCommission')) {
    /**
     * Calculate referral commission amount.
     *
     * @param float $amount
     * @param int $level
     * @return float
     */
    function calculateReferralCommission(float $amount, int $level): float
    {
        $commissionRate = getReferralCommission($level);
        return ($amount * $commissionRate) / 100;
    }
}

if (!function_exists('isKycRequired')) {
    /**
     * Check if KYC is required for withdrawals.
     *
     * @return bool
     */
    function isKycRequired(): bool
    {
        return getSetting('require_kyc_for_withdrawal', true);
    }
}

if (!function_exists('is2faRequired')) {
    /**
     * Check if 2FA is required for specific action.
     *
     * @param string $action (withdrawals, staff)
     * @return bool
     */
    function is2faRequired(string $action = 'withdrawals'): bool
    {
        switch ($action) {
            case 'withdrawals':
                return getSetting('force_2fa_for_withdrawals', false);
            case 'staff':
                return getSetting('force_2fa_for_staff', true);
            default:
                return false;
        }
    }
}

if (!function_exists('getDefaultCurrency')) {
    /**
     * Get the default platform currency.
     *
     * @return string
     */
    function getDefaultCurrency(): string
    {
        return getSetting('default_currency', 'USD');
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * Format amount with default currency.
     *
     * @param float $amount
     * @param string|null $currency
     * @return string
     */
    function formatCurrency(float $amount, string $currency = null): string
    {
        $currency = $currency ?: getDefaultCurrency();
        
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'PKR' => 'Rs.',
            'BTC' => '₿',
            'ETH' => 'Ξ',
            'USDT' => 'USDT',
        ];

        $symbol = $symbols[$currency] ?? $currency;
        
        if (in_array($currency, ['BTC', 'ETH'])) {
            return $symbol . number_format($amount, 8);
        }
        
        return $symbol . number_format($amount, 2);
    }
}

if (!function_exists('getTradingPairs')) {
    /**
     * Get enabled trading pairs.
     *
     * @return array
     */
    function getTradingPairs(): array
    {
        $pairs = [];
        $availablePairs = [
            'BTCUSD' => 'Bitcoin/USD',
            'ETHUSD' => 'Ethereum/USD', 
            'EURUSD' => 'Euro/USD',
            'GBPUSD' => 'British Pound/USD'
        ];

        foreach ($availablePairs as $pair => $name) {
            $settingKey = 'enable_' . strtolower($pair);
            if (getSetting($settingKey, true)) {
                $pairs[$pair] = $name;
            }
        }

        return $pairs;
    }
}

if (!function_exists('getApiRateLimit')) {
    /**
     * Get API rate limit settings.
     *
     * @param string $period (minute, hour)
     * @return int
     */
    function getApiRateLimit(string $period = 'minute'): int
    {
        switch ($period) {
            case 'minute':
                return (int) getSetting('api_rate_limit_per_minute', 60);
            case 'hour':
                return (int) getSetting('api_rate_limit_per_hour', 1000);
            default:
                return 60;
        }
    }
}

if (!function_exists('isCountryBlocked')) {
    /**
     * Check if a country is blocked.
     *
     * @param string $countryCode
     * @return bool
     */
    function isCountryBlocked(string $countryCode): bool
    {
        $blockedCountries = getSetting('blocked_countries', '');
        
        if (empty($blockedCountries)) {
            return false;
        }

        $blockedList = array_map('trim', explode(',', $blockedCountries));
        return in_array(strtoupper($countryCode), array_map('strtoupper', $blockedList));
    }
}

if (!function_exists('getPlatformInfo')) {
    /**
     * Get basic platform information.
     *
     * @return array
     */
    function getPlatformInfo(): array
    {
        return [
            'name' => getSetting('app_name', config('app.name')),
            'tagline' => getSetting('site_tagline', 'Advanced Prediction Trading Platform'),
            'description' => getSetting('site_description', ''),
            'support_email' => getSetting('support_email', 'support@mlmtrial.live'),
            'currency' => getDefaultCurrency(),
            'maintenance_mode' => isMaintenanceMode(),
            'registration_enabled' => canRegister(),
            'trading_enabled' => isTradingEnabled(),
        ];
    }
}