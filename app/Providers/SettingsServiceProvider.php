<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register settings helper functions
        require_once app_path('Helpers/SettingsHelper.php');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load settings into config at runtime for critical ones
        $this->loadCriticalSettings();
    }

    /**
     * Load critical settings into Laravel config.
     */
    private function loadCriticalSettings(): void
    {
        try {
            // Only load if database is available and settings table exists
            if ($this->databaseReady()) {
                // Set maintenance mode
                $maintenanceMode = \App\Models\Setting::getValue('maintenance_mode', false);
                config(['app.maintenance_mode' => $maintenanceMode]);

                // Set default currency
                $defaultCurrency = \App\Models\Setting::getValue('default_currency', 'USD');
                config(['app.default_currency' => $defaultCurrency]);

                // Set app name if different from config
                $appName = \App\Models\Setting::getValue('app_name');
                if ($appName && $appName !== config('app.name')) {
                    config(['app.name' => $appName]);
                }

                // Set security settings
                $passwordMinLength = \App\Models\Setting::getValue('password_min_length', 8);
                config(['auth.password_min_length' => $passwordMinLength]);

                $maxLoginAttempts = \App\Models\Setting::getValue('max_login_attempts', 5);
                config(['auth.max_login_attempts' => $maxLoginAttempts]);

                // Set session timeout
                $sessionTimeout = \App\Models\Setting::getValue('session_timeout');
                if ($sessionTimeout) {
                    config(['session.lifetime' => $sessionTimeout]);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if database/table doesn't exist yet
            // This prevents errors during migration or fresh installation
        }
    }

    /**
     * Check if database and settings table are ready.
     */
    private function databaseReady(): bool
    {
        try {
            \Illuminate\Support\Facades\Schema::hasTable('settings');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}