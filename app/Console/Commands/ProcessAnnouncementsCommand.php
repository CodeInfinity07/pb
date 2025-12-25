<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnnouncementService;
use Illuminate\Support\Facades\Log;

class ProcessAnnouncementsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'announcements:process 
                            {--cleanup : Clean up expired announcements}
                            {--activate : Activate scheduled announcements}
                            {--stats : Show announcement statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Process announcements: activate scheduled ones and clean up expired ones';

    protected $announcementService;

    /**
     * Create a new command instance.
     */
    public function __construct(AnnouncementService $announcementService)
    {
        parent::__construct();
        $this->announcementService = $announcementService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing announcements...');

        $activatedCount = 0;
        $cleanedUpCount = 0;

        try {
            // Activate scheduled announcements if --activate flag is used or no specific flag
            if ($this->option('activate') || (!$this->option('cleanup') && !$this->option('stats'))) {
                $this->info('Activating scheduled announcements...');
                $activatedCount = $this->announcementService->activateScheduled();
                
                if ($activatedCount > 0) {
                    $this->info("✓ Activated {$activatedCount} scheduled announcements");
                } else {
                    $this->line('• No scheduled announcements to activate');
                }
            }

            // Clean up expired announcements if --cleanup flag is used or no specific flag
            if ($this->option('cleanup') || (!$this->option('activate') && !$this->option('stats'))) {
                $this->info('Cleaning up expired announcements...');
                $cleanedUpCount = $this->announcementService->cleanupExpired();
                
                if ($cleanedUpCount > 0) {
                    $this->info("✓ Cleaned up {$cleanedUpCount} expired announcements");
                } else {
                    $this->line('• No expired announcements to clean up');
                }
            }

            // Show statistics if --stats flag is used
            if ($this->option('stats')) {
                $this->showStatistics();
            }

            // Summary
            if ($activatedCount > 0 || $cleanedUpCount > 0) {
                $this->info("\nSummary:");
                if ($activatedCount > 0) {
                    $this->line("• {$activatedCount} announcements activated");
                }
                if ($cleanedUpCount > 0) {
                    $this->line("• {$cleanedUpCount} announcements cleaned up");
                }
                
                Log::info('Announcements processed successfully', [
                    'activated' => $activatedCount,
                    'cleaned_up' => $cleanedUpCount
                ]);
            } else {
                $this->line('• No announcements needed processing');
            }

            $this->info('✓ Announcement processing completed successfully');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Failed to process announcements: ' . $e->getMessage());
            
            Log::error('Announcement processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }

    /**
     * Show announcement statistics.
     */
    private function showStatistics()
    {
        $this->info('Loading announcement statistics...');
        
        try {
            $stats = $this->announcementService->getStatistics();
            
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Announcements', number_format($stats['total_announcements'])],
                    ['Active Announcements', number_format($stats['active_announcements'])],
                    ['Scheduled Announcements', number_format($stats['scheduled_announcements'])],
                    ['Expired Announcements', number_format($stats['expired_announcements'])],
                    ['Total Views', number_format($stats['total_views'])],
                    ['Unique Viewers', number_format($stats['unique_viewers'])],
                    ['Views Today', number_format($stats['views_today'])],
                    ['Views This Week', number_format($stats['views_this_week'])],
                    ['Views This Month', number_format($stats['views_this_month'])],
                ]
            );

            // Additional metrics
            if ($stats['total_announcements'] > 0) {
                $viewRate = $stats['total_views'] > 0 ? round(($stats['unique_viewers'] / $stats['total_views']) * 100, 2) : 0;
                $activePercentage = round(($stats['active_announcements'] / $stats['total_announcements']) * 100, 2);
                
                $this->info("\nAdditional Insights:");
                $this->line("• View Rate: {$viewRate}% (unique viewers vs total views)");
                $this->line("• Active Rate: {$activePercentage}% (active vs total announcements)");
                
                if ($stats['expired_announcements'] > 0) {
                    $this->warn("• {$stats['expired_announcements']} announcements have expired and may need cleanup");
                }
            }

        } catch (\Exception $e) {
            $this->error('Failed to load statistics: ' . $e->getMessage());
        }
    }
}

/*
|--------------------------------------------------------------------------
| Register Command in Kernel.php
|--------------------------------------------------------------------------
| Add this line to your app/Console/Kernel.php in the commands() method:
|
| $this->command('announcements:process', App\Console\Commands\ProcessAnnouncementsCommand::class);
|
| Or add to $commands array:
| protected $commands = [
|     Commands\ProcessAnnouncementsCommand::class,
| ];
|
| Add to schedule() method in Kernel.php for automatic processing:
| $schedule->command('announcements:process')->everyFiveMinutes();
|
| Usage Examples:
| php artisan announcements:process                    # Process all (activate & cleanup)
| php artisan announcements:process --activate         # Only activate scheduled
| php artisan announcements:process --cleanup          # Only cleanup expired
| php artisan announcements:process --stats            # Show statistics only
| php artisan announcements:process --activate --stats # Activate and show stats
|--------------------------------------------------------------------------
*/