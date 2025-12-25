<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\BotController;
use App\Services\CommissionDistributionService;
use Illuminate\Support\Facades\Log;

class ProcessInvestmentReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:process-returns 
                            {--dry-run : Show what would be processed without actually processing}
                            {--limit=100 : Limit the number of investments to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due investment returns for all active investments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting investment returns processing...');
        
        $startTime = now();
        
        try {
            // Get the BotController instance with dependency injection
            $commissionService = app(CommissionDistributionService::class);
            $botController = new BotController($commissionService);
            
            // Check for dry run
            if ($this->option('dry-run')) {
                $this->dryRunProcess();
                return 0;
            }
            
            // Process all due returns
            $result = $botController->processAllDueReturns();
            
            // Log the result
            Log::info('Scheduled investment returns processing completed', $result);
            
            // Output results to console
            if ($result['success']) {
                $this->info("✅ Processing completed successfully!");
                $this->line("📊 Processed: {$result['processed']} returns");
                $this->line("💰 Total amount: $" . number_format($result['total_amount'], 2));
                
                if ($result['failed'] > 0) {
                    $this->warn("⚠️  Failed: {$result['failed']} returns");
                    
                    if (!empty($result['errors'])) {
                        $this->line("\n❌ Errors:");
                        foreach ($result['errors'] as $error) {
                            $this->error("  - Investment {$error['investment_id']} (User {$error['user_id']}): {$error['error']}");
                        }
                    }
                }
            } else {
                $this->error("❌ Processing failed: {$result['message']}");
                return 1;
            }
            
            $duration = now()->diffInSeconds($startTime);
            $this->line("\n⏱️  Completed in {$duration} seconds");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            
            Log::error('Investment returns command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Show what would be processed without actually processing
     */
    private function dryRunProcess()
    {
        $this->warn('🔍 DRY RUN MODE - No actual processing will occur');
        
        try {
            $dueInvestments = \App\Models\UserInvestment::where('status', 'active')
                ->with(['user', 'investmentPlan'])
                ->get()
                ->filter(function ($investment) {
                    return $investment->isDueForReturn();
                });
            
            $this->info("📋 Found {$dueInvestments->count()} investments due for returns:");
            
            $totalAmount = 0;
            
            foreach ($dueInvestments as $investment) {
                $returnAmount = $investment->calculateSingleReturn();
                $totalAmount += $returnAmount;
                
                $this->line("  • Investment #{$investment->id} (User: {$investment->user->email})");
                $this->line("    Plan: {$investment->investmentPlan->name}");
                $this->line("    Return Amount: $" . number_format($returnAmount, 2));
                $this->line("    Due Date: " . $investment->getNextReturnDueDate());
                $this->line("");
            }
            
            $this->info("💰 Total amount to be distributed: $" . number_format($totalAmount, 2));
            
        } catch (\Exception $e) {
            $this->error("Dry run failed: " . $e->getMessage());
        }
    }
}