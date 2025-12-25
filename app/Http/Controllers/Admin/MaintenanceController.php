<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Exception;

class MaintenanceController extends Controller
{
    /**
     * Display maintenance mode settings.
     */
    public function index()
    {
        $this->checkAdminAccess();
        $user = \Auth::user();

        $data = [
            'maintenance_status' => $this->getMaintenanceStatus(),
            'maintenance_settings' => $this->getMaintenanceSettings(),
            'allowed_ips' => $this->getAllowedIPs(),
            'custom_message' => getSetting('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.'),
            'estimated_duration' => getSetting('maintenance_duration', '30 minutes'),
            'contact_email' => getSetting('maintenance_contact', 'admin@example.com'),
            'show_progress' => getSetting('maintenance_show_progress', false),
            'redirect_url' => getSetting('maintenance_redirect_url', ''),
            'maintenance_logs' => $this->getMaintenanceLogs(),
            'user' => $user,
        ];

        return view('admin.settings.maintenance.index', $data);
    }

    /**
     * Enable maintenance mode.
     */
    public function enable(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
            'retry_after' => 'nullable|integer|min:60|max:86400',
            'allowed_ips' => 'nullable|string',
            'duration' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email',
            'redirect_url' => 'nullable|url',
            'show_progress' => 'boolean',
        ]);

        try {
            // Save maintenance settings
            $this->saveMaintenanceSettings($validated);

            // Build artisan command
            $command = 'down';
            $options = [];

            if (!empty($validated['message'])) {
                $options['--message'] = $validated['message'];
            }

            if (!empty($validated['retry_after'])) {
                $options['--retry'] = $validated['retry_after'];
            }

            if (!empty($validated['allowed_ips'])) {
                $ips = array_map('trim', explode(',', $validated['allowed_ips']));
                $options['--allow'] = $ips;
            }

            if (!empty($validated['redirect_url'])) {
                $options['--redirect'] = $validated['redirect_url'];
            }

            // Create custom maintenance view if message is provided
            if (!empty($validated['message'])) {
                $this->createCustomMaintenanceView($validated);
            }

            // Execute maintenance mode
            Artisan::call($command, $options);

            // Log maintenance activation
            $this->logMaintenanceAction('enabled', $validated);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode enabled successfully!',
                'status' => 'enabled'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to enable maintenance mode', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable maintenance mode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disable maintenance mode.
     */
    public function disable(Request $request)
    {
        $this->checkAdminAccess();

        try {
            // Execute artisan command to bring site back up
            Artisan::call('up');

            // Log maintenance deactivation
            $this->logMaintenanceAction('disabled');

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode disabled successfully!',
                'status' => 'disabled'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to disable maintenance mode', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disable maintenance mode: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current maintenance status.
     */
    public function status(Request $request)
    {
        $this->checkAdminAccess();

        $status = $this->getMaintenanceStatus();

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Preview maintenance page.
     */
    public function preview(Request $request)
    {
        $this->checkAdminAccess();
        $user = \Auth::user();

        $settings = [
            'message' => $request->input('message', 'We are currently performing scheduled maintenance.'),
            'duration' => $request->input('duration', '30 minutes'),
            'contact_email' => $request->input('contact_email', 'admin@example.com'),
            'show_progress' => $request->boolean('show_progress'),
            'user' => $user,
        ];

        return view('admin.settings.maintenance.preview', $settings);
    }

    /**
     * Update maintenance settings without changing status.
     */
    public function updateSettings(Request $request)
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'maintenance_message' => 'nullable|string|max:500',
            'maintenance_duration' => 'nullable|string|max:100',
            'maintenance_contact' => 'nullable|email',
            'maintenance_show_progress' => 'boolean',
            'maintenance_redirect_url' => 'nullable|url',
        ]);

        try {
            foreach ($validated as $key => $value) {
                Setting::setValue($key, $value, 'string', 'maintenance');
            }

            return response()->json([
                'success' => true,
                'message' => 'Maintenance settings updated successfully!'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
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
     * Get current maintenance status.
     */
    private function getMaintenanceStatus(): array
    {
        $maintenanceFile = storage_path('framework/maintenance.php');
        
        if (File::exists($maintenanceFile)) {
            $data = include $maintenanceFile;
            return [
                'enabled' => true,
                'time' => $data['time'] ?? time(),
                'message' => $data['message'] ?? 'Application is in maintenance mode.',
                'retry' => $data['retry'] ?? 60,
                'allowed' => $data['allowed'] ?? [],
                'redirect' => $data['redirect'] ?? null,
            ];
        }

        return ['enabled' => false];
    }

    /**
     * Get maintenance settings from database.
     */
    private function getMaintenanceSettings(): array
    {
        return [
            'message' => getSetting('maintenance_message', 'We are currently performing scheduled maintenance. Please check back soon.'),
            'duration' => getSetting('maintenance_duration', '30 minutes'),
            'contact_email' => getSetting('maintenance_contact', 'admin@example.com'),
            'show_progress' => getSetting('maintenance_show_progress', false),
            'redirect_url' => getSetting('maintenance_redirect_url', ''),
        ];
    }

    /**
     * Get allowed IPs for maintenance mode.
     */
    private function getAllowedIPs(): array
    {
        $status = $this->getMaintenanceStatus();
        return $status['allowed'] ?? [];
    }

    /**
     * Save maintenance settings to database.
     */
    private function saveMaintenanceSettings(array $settings)
    {
        $settingsMap = [
            'message' => 'maintenance_message',
            'duration' => 'maintenance_duration',
            'contact_email' => 'maintenance_contact',
            'show_progress' => 'maintenance_show_progress',
            'redirect_url' => 'maintenance_redirect_url',
        ];

        foreach ($settingsMap as $key => $dbKey) {
            if (isset($settings[$key])) {
                Setting::setValue($dbKey, $settings[$key], 'string', 'maintenance');
            }
        }
    }

    /**
     * Create custom maintenance view.
     */
    private function createCustomMaintenanceView(array $settings)
    {
        $viewPath = resource_path('views/errors/503.blade.php');
        $content = $this->generateMaintenanceViewContent($settings);
        
        // Backup existing 503 view if it exists
        if (File::exists($viewPath)) {
            File::copy($viewPath, $viewPath . '.backup');
        }

        // Create directory if it doesn't exist
        File::ensureDirectoryExists(dirname($viewPath));
        
        // Write custom maintenance view
        File::put($viewPath, $content);
    }

    /**
     * Generate maintenance view content.
     */
    private function generateMaintenanceViewContent(array $settings): string
    {
        $message = $settings['message'] ?? 'We are currently performing scheduled maintenance.';
        $duration = $settings['duration'] ?? '';
        $contactEmail = $settings['contact_email'] ?? '';
        $showProgress = $settings['show_progress'] ?? false;

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - ' . config('app.name') . '</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0; 
            padding: 0; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            color: #333;
        }
        .maintenance-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 500px;
            margin: 1rem;
        }
        .maintenance-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        h1 { color: #333; margin-bottom: 1rem; }
        p { color: #666; line-height: 1.6; margin-bottom: 1rem; }
        .progress-bar {
            background: #f0f0f0;
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin: 1rem 0;
        }
        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            width: 0%;
            animation: progress 10s ease-in-out infinite;
        }
        @keyframes progress {
            0%, 100% { width: 0%; }
            50% { width: 70%; }
        }
        .contact-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">🔧</div>
        <h1>Under Maintenance</h1>
        <p>' . htmlspecialchars($message) . '</p>';

        if ($duration) {
            $content .= '<p><strong>Estimated Duration:</strong> ' . htmlspecialchars($duration) . '</p>';
        }

        if ($showProgress) {
            $content .= '<div class="progress-bar"><div class="progress-fill"></div></div>';
        }

        if ($contactEmail) {
            $content .= '<div class="contact-info">
                <p><strong>Need assistance?</strong></p>
                <p>Contact us: <a href="mailto:' . htmlspecialchars($contactEmail) . '">' . htmlspecialchars($contactEmail) . '</a></p>
            </div>';
        }

        $content .= '<p><small>We apologize for any inconvenience.</small></p>
    </div>
</body>
</html>';

        return $content;
    }

    /**
     * Log maintenance actions.
     */
    private function logMaintenanceAction(string $action, array $data = [])
    {
        Log::info("Maintenance mode {$action}", [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
            'action' => $action,
            'settings' => $data,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get maintenance logs.
     */
    private function getMaintenanceLogs(): array
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            if (!File::exists($logFile)) {
                return [];
            }

            $logs = [];
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_reverse($lines); // Get most recent first

            foreach ($lines as $line) {
                if (strpos($line, 'Maintenance mode') !== false) {
                    $logs[] = $line;
                    if (count($logs) >= 10) break; // Limit to last 10 entries
                }
            }

            return $logs;
        } catch (Exception $e) {
            return [];
        }
    }
}