<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        // Check if user has admin privileges
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        $user = Auth::user();

        return view('admin.settings.system.index', compact('user'));
    }

    /**
     * Update settings based on the category.
     */
    public function update(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $category = $request->header('X-Settings-Category', 'general');
            $settings = $request->except(['_token', '_method']);
            
            // Process settings based on category
            $processedSettings = $this->processSettingsByCategory($settings, $category);
            
            // Update settings in database
            foreach ($processedSettings as $key => $data) {
                Setting::setValue(
                    $key,
                    $data['value'],
                    $data['type'],
                    $category,
                    $data['description'] ?? null,
                    $data['is_public'] ?? false,
                    $data['is_encrypted'] ?? false
                );
            }

            // Log the action
            Log::info('Settings updated', [
                'category' => $category,
                'user_id' => auth()->id(),
                'settings_count' => count($processedSettings)
            ]);

            // Clear relevant caches
            $this->clearSettingsCache($category);

            return response()->json([
                'success' => true,
                'message' => ucfirst($category) . ' settings updated successfully!',
                'updated_count' => count($processedSettings)
            ]);

        } catch (\Exception $e) {
            Log::error('Settings update failed', [
                'error' => $e->getMessage(),
                'category' => $category ?? 'unknown',
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process settings based on their category.
     */
    private function processSettingsByCategory(array $settings, string $category): array
    {
        $processed = [];

        foreach ($settings as $key => $value) {
            $processed[$key] = $this->getSettingDefinition($key, $value, $category);
        }

        return $processed;
    }

    /**
     * Get setting definition with proper type and metadata.
     */
    private function getSettingDefinition(string $key, $value, string $category): array
    {
        // Define setting types and metadata
        $definitions = [
            // General settings
            'app_name' => ['type' => 'string', 'is_public' => true],
            'site_tagline' => ['type' => 'string', 'is_public' => true],
            'site_description' => ['type' => 'string', 'is_public' => true],
            'support_email' => ['type' => 'string', 'is_public' => true],
            'default_currency' => ['type' => 'string', 'is_public' => true],
            'date_format' => ['type' => 'string', 'is_public' => true],
            'allow_registration' => ['type' => 'boolean', 'is_public' => true],
            'require_email_verification' => ['type' => 'boolean'],
            'enable_referral_system' => ['type' => 'boolean', 'is_public' => true],
            'enable_prediction_bot' => ['type' => 'boolean', 'is_public' => true],
            'maintenance_mode' => ['type' => 'boolean'],
            'enable_demo_mode' => ['type' => 'boolean', 'is_public' => true],

            // Trading settings
            'bot_prediction_accuracy' => ['type' => 'integer'],
            'min_prediction_amount' => ['type' => 'float', 'is_public' => true],
            'max_prediction_amount' => ['type' => 'float', 'is_public' => true],
            'prediction_duration_min' => ['type' => 'integer', 'is_public' => true],
            'prediction_duration_max' => ['type' => 'integer', 'is_public' => true],
            'daily_prediction_limit' => ['type' => 'integer', 'is_public' => true],
            'enable_btcusd' => ['type' => 'boolean', 'is_public' => true],
            'enable_ethusd' => ['type' => 'boolean', 'is_public' => true],
            'enable_eurusd' => ['type' => 'boolean', 'is_public' => true],
            'enable_gbpusd' => ['type' => 'boolean', 'is_public' => true],
            'enable_24_7_trading' => ['type' => 'boolean', 'is_public' => true],
            'trading_start_time' => ['type' => 'string', 'is_public' => true],
            'trading_end_time' => ['type' => 'string', 'is_public' => true],

            // Financial settings
            'min_deposit_amount' => ['type' => 'float', 'is_public' => true],
            'max_deposit_amount' => ['type' => 'float', 'is_public' => true],
            'deposit_fee_percentage' => ['type' => 'float'],
            'min_withdrawal_amount' => ['type' => 'float', 'is_public' => true],
            'max_withdrawal_amount' => ['type' => 'float', 'is_public' => true],
            'withdrawal_fee_percentage' => ['type' => 'float'],
            'level_1_commission' => ['type' => 'float'],
            'level_2_commission' => ['type' => 'float'],
            'level_3_commission' => ['type' => 'float'],
            'max_mlm_levels' => ['type' => 'integer'],
            'min_referral_deposit' => ['type' => 'float'],

            // Security settings
            'password_min_length' => ['type' => 'integer'],
            'max_login_attempts' => ['type' => 'integer'],
            'session_timeout' => ['type' => 'integer'],
            'enable_2fa' => ['type' => 'boolean'],
            'force_2fa_for_staff' => ['type' => 'boolean'],
            'force_2fa_for_withdrawals' => ['type' => 'boolean'],
            'require_kyc_for_withdrawal' => ['type' => 'boolean'],
            'log_user_activities' => ['type' => 'boolean'],
            'blocked_countries' => ['type' => 'string'],

            // API settings
            'enable_auto_kyc_approval' => ['type' => 'boolean'],
            'api_rate_limit_per_minute' => ['type' => 'integer'],
            'api_rate_limit_per_hour' => ['type' => 'integer'],
            'enable_api_throttling' => ['type' => 'boolean'],
        ];

        $definition = $definitions[$key] ?? ['type' => 'string'];

        // Convert checkbox values to proper boolean
        if ($definition['type'] === 'boolean') {
            $value = $value === 'on' || $value === '1' || $value === true;
        }

        // Convert numeric values
        if ($definition['type'] === 'integer') {
            $value = (int) $value;
        } elseif ($definition['type'] === 'float') {
            $value = (float) $value;
        }

        return [
            'value' => $value,
            'type' => $definition['type'],
            'is_public' => $definition['is_public'] ?? false,
            'is_encrypted' => $definition['is_encrypted'] ?? false,
            'description' => $this->getSettingDescription($key)
        ];
    }

    /**
     * Get description for a setting key.
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            'app_name' => 'Application name displayed throughout the platform',
            'site_tagline' => 'Brief tagline/slogan for the platform',
            'site_description' => 'Platform description used for SEO and metadata',
            'support_email' => 'Primary support email address',
            'default_currency' => 'Default currency for the platform',
            'date_format' => 'Default date format for displaying dates',
            'allow_registration' => 'Allow new users to register on the platform',
            'require_email_verification' => 'Require email verification for new users',
            'enable_referral_system' => 'Enable MLM/referral system functionality',
            'enable_prediction_bot' => 'Enable prediction bot trading features',
            'maintenance_mode' => 'Put platform in maintenance mode',
            'enable_demo_mode' => 'Allow users to use demo trading accounts',
            'bot_prediction_accuracy' => 'Target accuracy percentage for bot predictions',
            'min_prediction_amount' => 'Minimum amount for predictions',
            'max_prediction_amount' => 'Maximum amount for predictions',
            'prediction_duration_min' => 'Minimum prediction duration in seconds',
            'prediction_duration_max' => 'Maximum prediction duration in seconds',
            'daily_prediction_limit' => 'Maximum predictions per user per day',
            'min_deposit_amount' => 'Minimum deposit amount',
            'max_deposit_amount' => 'Maximum deposit amount',
            'deposit_fee_percentage' => 'Fee percentage for deposits',
            'min_withdrawal_amount' => 'Minimum withdrawal amount',
            'max_withdrawal_amount' => 'Maximum withdrawal amount',
            'withdrawal_fee_percentage' => 'Fee percentage for withdrawals',
            'level_1_commission' => 'Commission percentage for level 1 referrals',
            'level_2_commission' => 'Commission percentage for level 2 referrals',
            'level_3_commission' => 'Commission percentage for level 3 referrals',
            'max_mlm_levels' => 'Maximum MLM levels for commission calculation',
            'min_referral_deposit' => 'Minimum deposit required to earn referral commissions',
            'password_min_length' => 'Minimum required password length',
            'max_login_attempts' => 'Maximum login attempts before account lockout',
            'session_timeout' => 'Session timeout duration in minutes',
            'enable_2fa' => 'Enable two-factor authentication support',
            'force_2fa_for_staff' => 'Require 2FA for all staff accounts',
            'force_2fa_for_withdrawals' => 'Require 2FA verification for withdrawals',
            'require_kyc_for_withdrawal' => 'Require KYC verification before withdrawals',
            'log_user_activities' => 'Log user activities for security monitoring',
            'blocked_countries' => 'Countries blocked from platform access',
            'enable_auto_kyc_approval' => 'Automatically approve KYC verifications',
            'api_rate_limit_per_minute' => 'Maximum API requests per minute',
            'api_rate_limit_per_hour' => 'Maximum API requests per hour',
            'enable_api_throttling' => 'Enable API rate limiting',
        ];

        return $descriptions[$key] ?? "Setting for {$key}";
    }

    /**
     * Clear settings cache.
     */
    private function clearSettingsCache(string $category = null): void
    {
        if ($category) {
            Cache::forget("settings_{$category}");
        }
        
        Cache::forget('all_settings');
        
        // Clear individual setting caches (this is less efficient but more thorough)
        Cache::flush();
    }

    /**
     * Clear application cache.
     */
    public function clearCache(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            // Clear various caches
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            Log::info('Application cache cleared', ['user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Application cache cleared successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Cache clear failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export settings configuration.
     */
    public function exportSettings(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Access denied');
        }

        try {
            $settings = Setting::all()->map(function ($setting) {
                return [
                    'key' => $setting->key,
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'category' => $setting->category,
                    'description' => $setting->description,
                    'is_public' => $setting->is_public,
                    'is_encrypted' => $setting->is_encrypted ? true : false, // Don't export actual encrypted values
                ];
            });

            $exportData = [
                'platform_info' => [
                    'name' => config('app.name'),
                    'url' => config('app.url'),
                    'exported_at' => now()->toISOString(),
                    'exported_by' => auth()->user()->email,
                ],
                'settings' => $settings,
                'environment' => [
                    'app_env' => config('app.env'),
                    'app_debug' => config('app.debug'),
                    'database_connection' => config('database.default'),
                    'cache_driver' => config('cache.default'),
                    'session_driver' => config('session.driver'),
                ]
            ];

            $filename = 'settings_export_' . now()->format('Y_m_d_H_i_s') . '.json';

            return Response::json($exportData, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Settings export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get settings for a specific category.
     */
    public function getSettings(Request $request, string $category = null)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            if ($category) {
                $settings = Setting::where('category', $category)->get();
            } else {
                $settings = Setting::all();
            }

            return response()->json([
                'success' => true,
                'settings' => $settings,
                'category' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset settings to default values.
     */
    public function resetToDefaults(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        try {
            $category = $request->input('category');
            
            if ($category) {
                // Reset specific category
                Setting::where('category', $category)->delete();
            } else {
                // Reset all settings
                Setting::truncate();
            }

            // Populate with defaults
            $defaults = Setting::getDefaultSettings();
            
            if ($category) {
                $defaults = array_filter($defaults, function($setting) use ($category) {
                    return $setting['category'] === $category;
                });
            }

            Setting::bulkUpdate($defaults);

            $this->clearSettingsCache($category);

            Log::info('Settings reset to defaults', [
                'category' => $category ?: 'all',
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => ($category ? ucfirst($category) . ' settings' : 'All settings') . ' have been reset to default values!'
            ]);

        } catch (\Exception $e) {
            Log::error('Settings reset failed', [
                'error' => $e->getMessage(),
                'category' => $category ?? 'all',
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset settings: ' . $e->getMessage()
            ], 500);
        }
    }
}