<?php

// app/Providers/DynamicMailServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DynamicMailConfigService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DynamicMailServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Apply database mail settings after app has booted
        $this->app->booted(function () {
            try {
                // Only try to load database settings if we can access the database
                if ($this->databaseIsReady()) {
                    DynamicMailConfigService::configure();
                }
            } catch (\Exception $e) {
                // Silently fail during migrations or database setup
                Log::debug('Database mail settings not loaded: ' . $e->getMessage());
            }
        });
    }

    /**
     * Check if database is ready for queries
     */
    private function databaseIsReady(): bool
    {
        try {
            return app()->bound('db') && Schema::hasTable('settings');
        } catch (\Exception $e) {
            return false;
        }
    }
}