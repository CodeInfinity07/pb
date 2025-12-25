<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\DynamicMailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Exception;

class EmailSettingsController extends Controller
{
    /**
     * Display the email settings page.
     */
    public function index()
    {
        $this->checkAdminAccess();
        $user = Auth::user();
        return view('admin.settings.email.index', compact('user'));
    }

    /**
     * Update email settings with proper password handling.
     */
    public function update(Request $request)
    {
        $this->checkAdminAccess();

        try {
            $category = $request->header('X-Settings-Category', 'smtp');
            $settings = $request->except(['_token', '_method']);
            
            // Validate settings based on category
            $this->validateSettingsByCategory($settings, $category);
            
            // Process and save settings
            $processedSettings = $this->processSettingsByCategory($settings, $category);
            $this->saveSettingsToDatabase($processedSettings);

            // Apply database settings to Laravel mail configuration
            $this->applyDatabaseMailConfig();

            $this->logSettingsUpdate($category, count($processedSettings));

            return response()->json([
                'success' => true,
                'message' => ucfirst($category) . ' settings saved and applied successfully!',
                'updated_count' => count($processedSettings),
                'config_source' => $this->getConfigSource(),
                'current_config' => $this->getCurrentMailConfig()
            ]);

        } catch (Exception $e) {
            $this->logError('Settings update failed', $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test SMTP connection with current configuration.
     */
    public function testConnection(Request $request)
    {
        $this->checkAdminAccess();

        try {
            // Apply database settings first
            $this->applyDatabaseMailConfig();

            // Get current configuration
            $config = $this->getCurrentSmtpConfig();
            $this->validateSmtpConfig($config);

            // Perform connection tests
            $this->performConnectivityTest($config);
            $this->performSmtpHandshakeTest($config);

            $configSource = $this->getConfigSource();
            $this->logConnectionTest(true, $config, $configSource);

            return response()->json([
                'success' => true,
                'message' => "SMTP connection successful! Using {$configSource} settings.",
                'details' => [
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'] ?: 'None',
                    'source' => $configSource
                ]
            ]);

        } catch (Exception $e) {
            $this->logConnectionTest(false, $config ?? [], null, $e);
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'troubleshooting' => $this->getConnectionTroubleshooting()
            ], 500);
        }
    }

    /**
     * Send test email using current configuration.
     */
    public function sendTestEmail(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'test_email_recipient' => 'required|email',
            'test_email_subject' => 'required|string|max:255',
            'test_email_content' => 'required|string'
        ]);

        try {
            // Apply database settings
            $this->applyDatabaseMailConfig();

            // Send test email
            $this->sendEmailMessage(
                $validated['test_email_recipient'],
                $validated['test_email_subject'],
                $validated['test_email_content']
            );

            $configSource = $this->getConfigSource();
            $this->logTestEmail(true, $validated['test_email_recipient'], $configSource);

            return response()->json([
                'success' => true,
                'message' => "Test email sent successfully to {$validated['test_email_recipient']}! (Using {$configSource} settings)"
            ]);

        } catch (Exception $e) {
            $this->logTestEmail(false, $validated['test_email_recipient'], null, $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
                'troubleshooting' => $this->getEmailTroubleshooting()
            ], 500);
        }
    }

    /**
     * Get email queue status.
     */
    public function queueStatus(Request $request)
    {
        $this->checkAdminAccess();

        try {
            $queueData = $this->getQueueStatistics();
            $html = $this->generateQueueStatusHtml($queueData);

            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $queueData
            ]);

        } catch (Exception $e) {
            $this->logError('Queue status check failed', $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to get queue status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear email queue.
     */
    public function clearQueue(Request $request)
    {
        $this->checkAdminAccess();

        try {
            $clearedCount = $this->clearQueueJobs();
            
            Log::info('Email queue cleared', [
                'user_id' => auth()->id(),
                'cleared_count' => $clearedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Email queue cleared successfully! ({$clearedCount} jobs removed)"
            ]);

        } catch (Exception $e) {
            $this->logError('Queue clear failed', $e);
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear queue: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug current mail configuration.
     */
    public function debugConfiguration(Request $request)
    {
        $this->checkAdminAccess();

        try {
            // Apply database settings
            $this->applyDatabaseMailConfig();

            $debug = $this->buildDebugData();

            return response()->json([
                'success' => true,
                'debug' => $debug,
                'summary' => $this->buildDebugSummary()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check admin access.
     */
    private function checkAdminAccess()
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Validate settings based on category.
     */
    private function validateSettingsByCategory(array $settings, string $category)
    {
        if ($category === 'smtp') {
            $this->validateSmtpSettings($settings);
        } elseif ($category === 'templates') {
            $this->validateTemplateSettings($settings);
        } elseif ($category === 'notifications') {
            $this->validateNotificationSettings($settings);
        }
    }

    /**
     * Validate SMTP settings.
     */
    private function validateSmtpSettings(array $settings)
    {
        $required = ['mail_host', 'mail_port', 'mail_from_address', 'mail_from_name'];
        
        foreach ($required as $field) {
            if (empty($settings[$field])) {
                throw new Exception("Required field '{$field}' is missing or empty");
            }
        }

        if (!filter_var($settings['mail_from_address'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid from email address format');
        }

        $port = (int) $settings['mail_port'];
        if ($port < 1 || $port > 65535) {
            throw new Exception('Port must be between 1 and 65535');
        }

        if (!empty($settings['mail_encryption']) && !in_array($settings['mail_encryption'], ['tls', 'ssl'])) {
            throw new Exception('Encryption must be either "tls" or "ssl"');
        }
    }

    /**
     * Validate template settings.
     */
    private function validateTemplateSettings(array $settings)
    {
        if (!empty($settings['welcome_delay'])) {
            $delay = (int) $settings['welcome_delay'];
            if ($delay < 0 || $delay > 1440) {
                throw new Exception('Welcome delay must be between 0 and 1440 minutes');
            }
        }
    }

    /**
     * Validate notification settings.
     */
    private function validateNotificationSettings(array $settings)
    {
        if (!empty($settings['admin_notification_email'])) {
            if (!filter_var($settings['admin_notification_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid admin notification email address');
            }
        }

        if (!empty($settings['large_deposit_threshold'])) {
            $threshold = (float) $settings['large_deposit_threshold'];
            if ($threshold < 0) {
                throw new Exception('Large deposit threshold cannot be negative');
            }
        }
    }

    /**
     * Process settings by category with proper type conversion.
     */
    private function processSettingsByCategory(array $settings, string $category): array
    {
        $processed = [];

        foreach ($settings as $key => $value) {
            $processed[$key] = $this->processIndividualSetting($key, $value);
        }

        return $processed;
    }

    /**
     * Process individual setting with proper type conversion and password handling.
     */
    private function processIndividualSetting(string $key, $value): array
    {
        // Handle password fields specially
        if ($key === 'mail_password') {
            $value = $this->handlePasswordField($value);
        }

        $definition = $this->getSettingDefinition($key);
        $processedValue = $this->convertValueByType($value, $definition['type']);

        return [
            'value' => $processedValue,
            'type' => $definition['type'],
            'description' => $definition['description'],
            'is_public' => $definition['is_public'] ?? false,
            'is_encrypted' => $definition['is_encrypted'] ?? false,
        ];
    }

    /**
     * Handle password field to avoid saving asterisks.
     */
    private function handlePasswordField($value)
    {
        // If password is empty, asterisks, or just whitespace, keep existing
        if (empty(trim($value)) || preg_match('/^\*+$/', $value)) {
            $existingPassword = getSetting('mail_password');
            if ($existingPassword) {
                Log::info('Preserving existing password (asterisks or empty input detected)');
                return $existingPassword;
            }
        }

        return $value;
    }

    /**
     * Get setting definition with metadata.
     */
    private function getSettingDefinition(string $key): array
    {
        $definitions = [
            // SMTP Settings
            'mail_mailer' => ['type' => 'string', 'description' => 'Email driver for sending emails'],
            'mail_host' => ['type' => 'string', 'description' => 'SMTP server hostname'],
            'mail_port' => ['type' => 'integer', 'description' => 'SMTP server port'],
            'mail_encryption' => ['type' => 'string', 'description' => 'Email encryption method (tls/ssl)'],
            'mail_username' => ['type' => 'string', 'description' => 'SMTP authentication username', 'is_encrypted' => true],
            'mail_password' => ['type' => 'string', 'description' => 'SMTP authentication password', 'is_encrypted' => true],
            'mail_timeout' => ['type' => 'integer', 'description' => 'SMTP connection timeout in seconds'],
            'mail_from_address' => ['type' => 'string', 'description' => 'Default sender email address'],
            'mail_from_name' => ['type' => 'string', 'description' => 'Default sender name'],
            'mail_reply_to' => ['type' => 'string', 'description' => 'Reply-to email address'],

            // Template Settings
            'enable_welcome_email' => ['type' => 'boolean', 'description' => 'Send welcome emails to new users'],
            'welcome_email_subject' => ['type' => 'string', 'description' => 'Subject line for welcome emails'],
            'welcome_email_content' => ['type' => 'string', 'description' => 'Content template for welcome emails'],
            'welcome_delay' => ['type' => 'integer', 'description' => 'Delay before sending welcome email in minutes'],
            'enable_deposit_notifications' => ['type' => 'boolean', 'description' => 'Send email notifications for deposits'],
            'enable_withdrawal_notifications' => ['type' => 'boolean', 'description' => 'Send email notifications for withdrawals'],

            // Notification Settings
            'admin_notification_email' => ['type' => 'string', 'description' => 'Email address for admin notifications'],
            'large_deposit_threshold' => ['type' => 'float', 'description' => 'Deposit amount threshold for notifications'],
            'notify_new_registrations' => ['type' => 'boolean', 'description' => 'Notify admins of new user registrations'],
            'notify_large_deposits' => ['type' => 'boolean', 'description' => 'Notify admins of large deposits'],
            'notify_withdrawal_requests' => ['type' => 'boolean', 'description' => 'Notify admins of withdrawal requests'],
            'notify_kyc_submissions' => ['type' => 'boolean', 'description' => 'Notify admins of KYC submissions'],
            'notification_delay' => ['type' => 'integer', 'description' => 'Delay before sending notifications in minutes'],
            'max_emails_per_minute' => ['type' => 'integer', 'description' => 'Maximum emails to send per minute'],
            'retry_failed_emails' => ['type' => 'integer', 'description' => 'Number of retry attempts for failed emails'],
            'allow_user_unsubscribe' => ['type' => 'boolean', 'description' => 'Allow users to unsubscribe from emails'],
            'track_email_opens' => ['type' => 'boolean', 'description' => 'Track when users open emails'],
        ];

        return $definitions[$key] ?? ['type' => 'string', 'description' => "Email setting for {$key}"];
    }

    /**
     * Convert value based on type.
     */
    private function convertValueByType($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return $value === 'on' || $value === '1' || $value === true || $value === 1;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Save processed settings to database.
     */
    private function saveSettingsToDatabase(array $processedSettings)
    {
        foreach ($processedSettings as $key => $data) {
            Setting::setValue(
                $key,
                $data['value'],
                $data['type'],
                'email',
                $data['description'],
                $data['is_public'],
                $data['is_encrypted']
            );
        }
    }

    /**
     * Apply database mail configuration to Laravel.
     */
    private function applyDatabaseMailConfig()
    {
        DynamicMailConfigService::configure();
    }

    /**
     * Get current SMTP configuration.
     */
    private function getCurrentSmtpConfig(): array
    {
        return [
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'password' => config('mail.mailers.smtp.password'),
            'timeout' => config('mail.mailers.smtp.timeout', 30),
        ];
    }

    /**
     * Validate SMTP configuration.
     */
    private function validateSmtpConfig(array $config)
    {
        if (empty($config['host'])) {
            throw new Exception('SMTP host is not configured');
        }

        if (empty($config['port']) || !is_numeric($config['port'])) {
            throw new Exception('SMTP port is not properly configured');
        }
    }

    /**
     * Perform basic connectivity test.
     */
    private function performConnectivityTest(array $config)
    {
        $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
        
        if (!$socket) {
            throw new Exception("Cannot connect to {$config['host']}:{$config['port']} - {$errstr} (Error: {$errno})");
        }
        
        fclose($socket);
    }

    /**
     * Perform SMTP handshake test.
     */
    private function performSmtpHandshakeTest(array $config)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = stream_socket_client(
            "tcp://{$config['host']}:{$config['port']}",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new Exception("SMTP handshake failed: {$errstr} (Error: {$errno})");
        }

        // Test server greeting
        $response = fgets($socket);
        if (strpos($response, '220') !== 0) {
            fclose($socket);
            throw new Exception("Invalid SMTP greeting: " . trim($response));
        }

        // Test EHLO command
        fwrite($socket, "EHLO localhost\r\n");
        $response = fgets($socket);
        if (strpos($response, '250') !== 0) {
            fclose($socket);
            throw new Exception("EHLO command failed: " . trim($response));
        }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    /**
     * Send email message.
     */
    private function sendEmailMessage(string $recipient, string $subject, string $content)
    {
        Mail::raw($content, function ($message) use ($recipient, $subject) {
            $message->to($recipient)
                   ->subject($subject)
                   ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    /**
     * Get configuration source.
     */
    private function getConfigSource(): string
    {
        return DynamicMailConfigService::hasDatabaseOverrides() ? 'database' : '.env';
    }

    /**
     * Get current mail configuration.
     */
    private function getCurrentMailConfig(): array
    {
        return DynamicMailConfigService::getCurrentConfig();
    }

    /**
     * Get queue statistics.
     */
    private function getQueueStatistics(): array
    {
        $driver = config('queue.default');
        
        try {
            if ($driver === 'database') {
                $pending = DB::table('jobs')->where('queue', 'default')->count();
                $failed = DB::table('failed_jobs')->count();
                
                return [
                    'driver' => 'database',
                    'pending' => $pending,
                    'processing' => 0,
                    'failed' => $failed,
                    'total_jobs' => $pending + $failed
                ];
            }
            
            return [
                'driver' => $driver,
                'pending' => 0,
                'processing' => 0,
                'failed' => 0,
                'message' => "Queue statistics for {$driver} driver not available"
            ];
            
        } catch (Exception $e) {
            return [
                'driver' => $driver,
                'pending' => 0,
                'processing' => 0,
                'failed' => 0,
                'error' => 'Could not access queue data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clear queue jobs.
     */
    private function clearQueueJobs(): int
    {
        $driver = config('queue.default');
        
        if ($driver === 'database') {
            return DB::table('jobs')->where('queue', 'default')->delete();
        } elseif ($driver === 'redis') {
            Artisan::call('queue:clear', ['--queue' => 'default']);
            return 'Redis queue cleared';
        }
        
        return 0;
    }

    /**
     * Generate HTML for queue status display.
     */
    private function generateQueueStatusHtml(array $queueData): string
    {
        $driver = $queueData['driver'] ?? 'unknown';
        $pending = $queueData['pending'] ?? 0;
        $processing = $queueData['processing'] ?? 0;
        $failed = $queueData['failed'] ?? 0;

        return "
        <div class='row g-3'>
            <div class='col-md-3'>
                <div class='card text-center'>
                    <div class='card-body'>
                        <h5 class='text-primary'>{$pending}</h5>
                        <small class='text-muted'>Pending</small>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card text-center'>
                    <div class='card-body'>
                        <h5 class='text-warning'>{$processing}</h5>
                        <small class='text-muted'>Processing</small>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card text-center'>
                    <div class='card-body'>
                        <h5 class='text-danger'>{$failed}</h5>
                        <small class='text-muted'>Failed</small>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card text-center'>
                    <div class='card-body'>
                        <h5 class='text-info'>{$driver}</h5>
                        <small class='text-muted'>Driver</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class='mt-3'>
            <div class='alert alert-info'>
                <strong>Queue Driver:</strong> {$driver}<br>
                <strong>Total Jobs:</strong> " . ($pending + $processing + $failed) . "<br>
                <strong>Last Updated:</strong> " . now()->format('Y-m-d H:i:s') . "
            </div>
        </div>";
    }

    /**
     * Build debug data.
     */
    private function buildDebugData(): array
    {
        return [
            'database_settings' => [
                'mail_host' => getSetting('mail_host'),
                'mail_port' => getSetting('mail_port'),
                'mail_encryption' => getSetting('mail_encryption'),
                'mail_username' => getSetting('mail_username'),
                'mail_from_address' => getSetting('mail_from_address'),
                'mail_from_name' => getSetting('mail_from_name'),
                'password_configured' => !empty(getSetting('mail_password')),
            ],
            'current_laravel_config' => [
                'default_mailer' => config('mail.default'),
                'smtp_host' => config('mail.mailers.smtp.host'),
                'smtp_port' => config('mail.mailers.smtp.port'),
                'smtp_encryption' => config('mail.mailers.smtp.encryption'),
                'smtp_username' => config('mail.mailers.smtp.username'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'ssl_verification_disabled' => config('mail.mailers.smtp.verify_peer') === false,
            ],
            'env_settings' => [
                'MAIL_MAILER' => env('MAIL_MAILER'),
                'MAIL_HOST' => env('MAIL_HOST'),
                'MAIL_PORT' => env('MAIL_PORT'),
                'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
                'MAIL_USERNAME' => env('MAIL_USERNAME'),
                'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
            ],
            'status' => [
                'using_database_settings' => DynamicMailConfigService::hasDatabaseOverrides(),
                'database_host_configured' => !empty(getSetting('mail_host')),
                'config_matches_database' => config('mail.mailers.smtp.host') === getSetting('mail_host'),
            ]
        ];
    }

    /**
     * Build debug summary.
     */
    private function buildDebugSummary(): array
    {
        return [
            'configuration_source' => $this->getConfigSource(),
            'current_host' => config('mail.mailers.smtp.host'),
            'current_port' => config('mail.mailers.smtp.port'),
            'ssl_status' => 'Verification disabled (avoids certificate issues)',
            'password_status' => !empty(getSetting('mail_password')) ? 'Configured' : 'Not set'
        ];
    }

    /**
     * Get connection troubleshooting tips.
     */
    private function getConnectionTroubleshooting(): array
    {
        return [
            'Verify SMTP host and port settings',
            'Check username and password credentials',
            'Try different ports: 587 (TLS), 465 (SSL), 25 (plain)',
            'Ensure firewall allows SMTP traffic',
            'Verify email provider supports SMTP access',
            'Check for network connectivity issues',
            'SSL verification is disabled to avoid certificate problems'
        ];
    }

    /**
     * Get email troubleshooting tips.
     */
    private function getEmailTroubleshooting(): array
    {
        return [
            'Test SMTP connection first before sending emails',
            'Verify recipient email address is valid',
            'Check that from address is properly configured',
            'Review email provider rate limits and restrictions',
            'Check Laravel logs for detailed error information',
            'Ensure password is correctly saved (not asterisks)',
            'Verify mail queue is running if using queued emails'
        ];
    }

    /**
     * Log settings update.
     */
    private function logSettingsUpdate(string $category, int $count)
    {
        Log::info('Email settings updated and applied', [
            'category' => $category,
            'user_id' => auth()->id(),
            'settings_count' => $count,
            'config_source' => $this->getConfigSource()
        ]);
    }

    /**
     * Log connection test.
     */
    private function logConnectionTest(bool $success, array $config, ?string $source, ?Exception $exception = null)
    {
        if ($success) {
            Log::info('Email connection test successful', [
                'user_id' => auth()->id(),
                'config_source' => $source,
                'host' => $config['host'] ?? 'unknown',
                'port' => $config['port'] ?? 'unknown'
            ]);
        } else {
            Log::error('Email connection test failed', [
                'user_id' => auth()->id(),
                'error' => $exception ? $exception->getMessage() : 'Unknown error',
                'host' => $config['host'] ?? 'unknown',
                'port' => $config['port'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Log test email.
     */
    private function logTestEmail(bool $success, string $recipient, ?string $source, ?Exception $exception = null)
    {
        if ($success) {
            Log::info('Test email sent successfully', [
                'user_id' => auth()->id(),
                'recipient' => $recipient,
                'config_source' => $source
            ]);
        } else {
            Log::error('Test email failed', [
                'user_id' => auth()->id(),
                'recipient' => $recipient,
                'error' => $exception ? $exception->getMessage() : 'Unknown error'
            ]);
        }
    }

    /**
     * Log general errors.
     */
    private function logError(string $message, Exception $exception)
    {
        Log::error($message, [
            'user_id' => auth()->id(),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
}