<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class DynamicMailConfigService
{
    /**
     * Apply database email settings to Laravel's mail configuration
     */
    public static function configure()
    {
        try {
            // Get settings from database
            $dbSettings = [
                'mailer' => getSetting('mail_mailer'),
                'host' => getSetting('mail_host'),
                'port' => getSetting('mail_port'),
                'encryption' => getSetting('mail_encryption'),
                'username' => self::getDecryptedSetting('mail_username'),  // DECRYPT HERE
                'password' => self::getDecryptedSetting('mail_password'),  // DECRYPT HERE
                'timeout' => getSetting('mail_timeout'),
                'from_address' => getSetting('mail_from_address'),
                'from_name' => getSetting('mail_from_name'),
            ];

            // Only apply database settings if host is configured in database
            if (!empty($dbSettings['host'])) {
                
                // Override Laravel's mail configuration with database values
                Config::set([
                    'mail.default' => $dbSettings['mailer'] ?: 'smtp',
                    'mail.mailers.smtp.host' => $dbSettings['host'],
                    'mail.mailers.smtp.port' => (int) ($dbSettings['port'] ?: 587),
                    'mail.mailers.smtp.encryption' => $dbSettings['encryption'] ?: 'tls',
                    'mail.mailers.smtp.username' => $dbSettings['username'],
                    'mail.mailers.smtp.password' => $dbSettings['password'],
                    'mail.mailers.smtp.timeout' => (int) ($dbSettings['timeout'] ?: 30),
                    'mail.mailers.smtp.verify_peer' => false,       // ADD THIS
                    'mail.mailers.smtp.verify_peer_name' => false,  // ADD THIS
                    'mail.from.address' => $dbSettings['from_address'],
                    'mail.from.name' => $dbSettings['from_name'],
                ]);

                // Force Laravel to recreate mail manager to pick up new config
                app()->forgetInstance('mail.manager');
                app()->forgetInstance('mailer');
                
                Log::info('Database mail configuration applied', [
                    'host' => $dbSettings['host'],
                    'port' => $dbSettings['port'],
                    'from' => $dbSettings['from_address'],
                    'username' => $dbSettings['username'],
                    'password_set' => !empty($dbSettings['password'])
                ]);
                
                return true;
            }
            
            Log::info('No database mail settings found, using .env defaults');
            return false;

        } catch (\Exception $e) {
            Log::error('Failed to apply database mail settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get and decrypt a setting value if it's encrypted
     */
    private static function getDecryptedSetting(string $key): ?string
    {
        $setting = Setting::where('key', $key)->first();
        
        if (!$setting || empty($setting->value)) {
            return null;
        }

        // If the setting is marked as encrypted, decrypt it
        if ($setting->is_encrypted) {
            try {
                $decrypted = decrypt($setting->value);
                Log::debug("Decrypted setting: {$key}");
                return $decrypted;
            } catch (\Exception $e) {
                Log::error("Failed to decrypt setting: {$key}", [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        }

        // Return plain value if not encrypted
        return $setting->value;
    }

    /**
     * Check if database has mail settings that differ from config
     */
    public static function hasDatabaseOverrides()
    {
        $dbHost = getSetting('mail_host');
        $configHost = config('mail.mailers.smtp.host');
        
        return !empty($dbHost) && $dbHost !== $configHost;
    }

    /**
     * Get current mail configuration being used
     */
    public static function getCurrentConfig()
    {
        return [
            'source' => self::hasDatabaseOverrides() ? 'database' : 'env',
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'password_set' => !empty(config('mail.mailers.smtp.password'))
        ];
    }
}