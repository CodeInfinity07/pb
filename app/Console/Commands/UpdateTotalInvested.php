<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateTotalInvested extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:update-total-invested 
                            {--dry-run : Show what would be updated without actually updating}
                            {--limit=100 : Limit the number of users to process per batch}
                            {--user-id= : Update specific user by ID}
                            {--status= : Filter investments by specific status (active, completed, cancelled, paused)}
                            {--active-only : Count only active investments instead of all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate and update users total_invested field. Default: counts all investments (active, completed, paused) excluding cancelled';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting total_invested update process...');
        $this->displayCalculationMode();
        
        $startTime = now();
        
        try {
            // Check for dry run
            if ($this->option('dry-run')) {
                return $this->dryRunProcess();
            }
            
            // Check for specific user update
            if ($userId = $this->option('user-id')) {
                return $this->updateSpecificUser($userId);
            }
            
            // Process all users
            $result = $this->updateAllUsers();
            
            // Log the result
            Log::info('Total invested update completed', $result);
            
            // Output results to console
            if ($result['success']) {
                $this->info("\n✅ Total invested update completed successfully!");
                $this->line("👥 Users updated: {$result['updated_count']}");
                $this->line("💰 Total amount corrected: $" . number_format($result['total_difference'], 2));
                
                if ($result['failed_count'] > 0) {
                    $this->warn("⚠️  Failed: {$result['failed_count']} users");
                }
                
                if ($result['increased_count'] > 0) {
                    $this->line("  • Increased: {$result['increased_count']} users");
                }
                
                if ($result['decreased_count'] > 0) {
                    $this->line("  • Decreased: {$result['decreased_count']} users");
                }
                
            } else {
                $this->error("❌ Update failed: {$result['message']}");
                return 1;
            }
            
            $duration = now()->diffInSeconds($startTime);
            $this->line("\n⏱️  Completed in {$duration} seconds");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Command failed: " . $e->getMessage());
            
            Log::error('Total invested update command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Display what calculation mode is being used.
     */
    private function displayCalculationMode(): void
    {
        $statusFilter = $this->option('status');
        $activeOnly = $this->option('active-only');
        
        if ($statusFilter) {
            $this->line("📊 Mode: Counting only '{$statusFilter}' investments");
        } elseif ($activeOnly) {
            $this->line("📊 Mode: Counting only 'active' investments");
        } else {
            $this->line("📊 Mode: Counting all investments except cancelled (DEFAULT)");
        }
        $this->line("");
    }
    
    /**
     * Build the query for calculating total invested.
     */
    private function buildInvestmentQuery(int $userId)
    {
        $query = UserInvestment::where('user_id', $userId);
        
        $statusFilter = $this->option('status');
        $activeOnly = $this->option('active-only');
        
        if ($statusFilter) {
            // Validate status filter
            if (!in_array($statusFilter, ['active', 'completed', 'cancelled', 'paused'])) {
                throw new \InvalidArgumentException("Invalid status: {$statusFilter}. Use: active, completed, cancelled, or paused");
            }
            $query->where('status', $statusFilter);
        } elseif ($activeOnly) {
            // Only active investments
            $query->where('status', 'active');
        } else {
            // DEFAULT: All statuses except cancelled (active, completed, paused)
            $query->whereIn('status', ['active', 'completed', 'paused']);
        }
        
        return $query;
    }
    
    /**
     * Update all users' total_invested.
     */
    private function updateAllUsers(): array
    {
        $updated = 0;
        $failed = 0;
        $increased = 0;
        $decreased = 0;
        $totalDifference = 0;
        $limit = $this->option('limit');
        
        // Create progress bar
        $totalUsers = User::count();
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        try {
            DB::transaction(function () use (&$updated, &$failed, &$increased, &$decreased, &$totalDifference, $limit, $progressBar) {
                User::chunk($limit, function ($users) use (&$updated, &$failed, &$increased, &$decreased, &$totalDifference, $progressBar) {
                    foreach ($users as $user) {
                        $progressBar->advance();
                        
                        try {
                            $currentTotal = floatval($user->total_invested ?? 0);
                            
                            // Calculate actual total from investments using the query builder
                            $actualTotal = floatval($this->buildInvestmentQuery($user->id)->sum('amount') ?? 0);
                            
                            // Compare with tolerance for floating point precision
                            $difference = $actualTotal - $currentTotal;
                            
                            if (abs($difference) > 0.01) {
                                $totalDifference += abs($difference);
                                
                                $user->update(['total_invested' => $actualTotal]);
                                $updated++;
                                
                                if ($difference > 0) {
                                    $increased++;
                                } else {
                                    $decreased++;
                                }
                                
                                Log::info('User total_invested updated', [
                                    'user_id' => $user->id,
                                    'user_email' => $user->email,
                                    'old_total' => $currentTotal,
                                    'new_total' => $actualTotal,
                                    'difference' => $difference,
                                    'calculation_mode' => $this->getCalculationMode()
                                ]);
                            }
                        } catch (\Exception $e) {
                            $failed++;
                            Log::error('Failed to update user total_invested', [
                                'user_id' => $user->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                });
            });
            
            $progressBar->finish();
            $this->line("\n");
            
            return [
                'success' => true,
                'message' => 'Successfully updated total_invested.',
                'updated_count' => $updated,
                'failed_count' => $failed,
                'increased_count' => $increased,
                'decreased_count' => $decreased,
                'total_difference' => $totalDifference
            ];
            
        } catch (\Exception $e) {
            $progressBar->finish();
            throw $e;
        }
    }
    
    /**
     * Update specific user's total_invested.
     */
    private function updateSpecificUser(int $userId): int
    {
        $this->info("🔍 Analyzing User: {$userId}");
        
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ User not found: {$userId}");
            return 1;
        }
        
        try {
            // Display user information
            $this->line("\n" . str_repeat('=', 80));
            $this->line("👤 USER INFORMATION");
            $this->line(str_repeat('=', 80));
            $this->line("  • ID: {$user->id}");
            $this->line("  • Email: {$user->email}");
            $this->line("  • Name: " . ($user->name ?? 'N/A'));
            $this->line("  • Current total_invested in DB: $" . number_format(floatval($user->total_invested ?? 0), 2));
            
            // Get all investments for this user
            $allInvestments = UserInvestment::where('user_id', $user->id)
                ->with('investmentPlan')
                ->orderBy('created_at', 'desc')
                ->get();
            
            if ($allInvestments->isEmpty()) {
                $this->warn("\n⚠️  No investments found for this user!");
                return 0;
            }
            
            // Display individual investments
            $this->line("\n" . str_repeat('=', 80));
            $this->line("💼 ALL INVESTMENTS ({$allInvestments->count()} total)");
            $this->line(str_repeat('=', 80));
            
            foreach ($allInvestments as $index => $investment) {
                $isIncluded = $this->isStatusIncluded($investment->status);
                $marker = $isIncluded ? '✓' : '✗';
                
                $this->line("\n{$marker} Investment #{$investment->id}:");
                $this->line("  ├─ Plan: " . ($investment->investmentPlan->name ?? 'N/A'));
                $this->line("  ├─ Amount: $" . number_format($investment->amount, 2));
                $this->line("  ├─ Status: {$investment->status}");
                $this->line("  ├─ Started: " . $investment->started_at->format('Y-m-d H:i:s'));
                $this->line("  ├─ Ends: " . $investment->ends_at->format('Y-m-d H:i:s'));
                
                if ($investment->completed_at) {
                    $this->line("  ├─ Completed: " . $investment->completed_at->format('Y-m-d H:i:s'));
                }
                
                $this->line("  └─ Created: " . $investment->created_at->format('Y-m-d H:i:s'));
            }
            
            // Display breakdown by status
            $this->line("\n" . str_repeat('=', 80));
            $this->line("📊 BREAKDOWN BY STATUS");
            $this->line(str_repeat('=', 80));
            
            $statusGroups = $allInvestments->groupBy('status');
            $includedTotal = 0;
            
            foreach ($statusGroups as $status => $group) {
                $count = $group->count();
                $sum = $group->sum('amount');
                
                $isIncluded = $this->isStatusIncluded($status);
                $marker = $isIncluded ? '✓' : '✗';
                
                $this->line("{$marker} {$status}: {$count} investment(s) = $" . number_format($sum, 2));
                
                if ($isIncluded) {
                    $includedTotal += $sum;
                }
            }
            
            $this->line("\n  Legend: ✓ = Included in calculation | ✗ = Excluded from calculation");
            
            // Calculate totals
            $currentTotal = floatval($user->total_invested ?? 0);
            $actualTotal = floatval($this->buildInvestmentQuery($user->id)->sum('amount') ?? 0);
            $difference = $actualTotal - $currentTotal;
            
            // Display calculation results
            $this->line("\n" . str_repeat('=', 80));
            $this->line("💰 CALCULATION RESULTS");
            $this->line(str_repeat('=', 80));
            $this->line("  • Current total_invested (in DB): $" . number_format($currentTotal, 2));
            $this->line("  • Calculated total (from query): $" . number_format($actualTotal, 2));
            
            if (abs($difference) > 0.01) {
                $symbol = $difference > 0 ? '⬆️' : '⬇️';
                $this->line("  {$symbol} Difference: $" . number_format(abs($difference), 2) . ($difference > 0 ? ' (increase needed)' : ' (decrease needed)'));
                
                $this->line("\n" . str_repeat('=', 80));
                
                // Confirm update
                if ($this->confirm("Do you want to update this user's total_invested?", true)) {
                    DB::transaction(function () use ($user, $actualTotal) {
                        $user->update(['total_invested' => $actualTotal]);
                    });
                    
                    $this->info("\n✅ User {$userId} updated from $" . number_format($currentTotal, 2) . " to $" . number_format($actualTotal, 2));
                    
                    Log::info('User total_invested manually updated', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'old_total' => $currentTotal,
                        'new_total' => $actualTotal,
                        'difference' => $difference,
                        'calculation_mode' => $this->getCalculationMode()
                    ]);
                } else {
                    $this->warn("\n⚠️  Update cancelled by user");
                }
            } else {
                $this->info("\n✅ User {$userId} already has correct total_invested: $" . number_format($actualTotal, 2));
            }
            
            $this->line(str_repeat('=', 80) . "\n");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to update user {$userId}: " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Check if a status is included in the current calculation.
     */
    private function isStatusIncluded(string $status): bool
    {
        $statusFilter = $this->option('status');
        $activeOnly = $this->option('active-only');
        
        if ($statusFilter) {
            return $status === $statusFilter;
        } elseif ($activeOnly) {
            return $status === 'active';
        } else {
            // DEFAULT: all except cancelled
            return in_array($status, ['active', 'completed', 'paused']);
        }
    }
    
    /**
     * Get human-readable calculation mode.
     */
    private function getCalculationMode(): string
    {
        $statusFilter = $this->option('status');
        $activeOnly = $this->option('active-only');
        
        if ($statusFilter) {
            return "status: {$statusFilter}";
        } elseif ($activeOnly) {
            return "active only";
        } else {
            return "all except cancelled (default)";
        }
    }
    
    /**
     * Show what would be updated without actually updating.
     */
    private function dryRunProcess(): int
    {
        $this->warn('🔍 DRY RUN MODE - No actual updates will occur');
        
        try {
            $limit = $this->option('limit');
            
            $updates = 0;
            $increases = 0;
            $decreases = 0;
            $totalDifference = 0;
            $largestIncrease = ['user' => null, 'amount' => 0];
            $largestDecrease = ['user' => null, 'amount' => 0];
            
            $this->line("\n🔄 Proposed Changes:");
            
            $changesShown = 0;
            $maxChangesToShow = 50; // Limit output for readability
            
            User::chunk($limit, function ($users) use (&$updates, &$increases, &$decreases, &$totalDifference, &$largestIncrease, &$largestDecrease, &$changesShown, $maxChangesToShow) {
                foreach ($users as $user) {
                    $currentTotal = floatval($user->total_invested ?? 0);
                    
                    // Calculate actual total from investments
                    $actualTotal = floatval($this->buildInvestmentQuery($user->id)->sum('amount') ?? 0);
                    
                    $difference = $actualTotal - $currentTotal;
                    
                    // Show changes (with float precision tolerance)
                    if (abs($difference) > 0.01) {
                        $updates++;
                        $totalDifference += abs($difference);
                        
                        if ($difference > 0) {
                            $increases++;
                            if ($difference > $largestIncrease['amount']) {
                                $largestIncrease = ['user' => $user, 'amount' => $difference];
                            }
                        } else {
                            $decreases++;
                            if (abs($difference) > $largestDecrease['amount']) {
                                $largestDecrease = ['user' => $user, 'amount' => abs($difference)];
                            }
                        }
                        
                        // Only show first 50 changes to avoid flooding console
                        if ($changesShown < $maxChangesToShow) {
                            $changeType = $difference > 0 ? '⬆️' : '⬇️';
                            
                            $this->line("  {$changeType} User #{$user->id} ({$user->email}):");
                            $this->line("    └─ Current: $" . number_format($currentTotal, 2) . " → Calculated: $" . number_format($actualTotal, 2) . " (Diff: $" . number_format(abs($difference), 2) . ")");
                            
                            $changesShown++;
                            
                            if ($changesShown === $maxChangesToShow) {
                                $this->line("\n  ... (showing first {$maxChangesToShow} changes only)");
                            }
                        }
                    }
                }
            });
            
            $this->line("\n📊 Summary:");
            $this->line("  • Total changes needed: {$updates}");
            $this->line("  • Increases: {$increases}");
            $this->line("  • Decreases: {$decreases}");
            $this->line("  • Total difference amount: $" . number_format($totalDifference, 2));
            
            if ($largestIncrease['user']) {
                $this->line("\n⬆️  Largest increase:");
                $this->line("  • User #{$largestIncrease['user']->id} ({$largestIncrease['user']->email}): +$" . number_format($largestIncrease['amount'], 2));
            }
            
            if ($largestDecrease['user']) {
                $this->line("\n⬇️  Largest decrease:");
                $this->line("  • User #{$largestDecrease['user']->id} ({$largestDecrease['user']->email}): -$" . number_format($largestDecrease['amount'], 2));
            }
            
            if ($updates === 0) {
                $this->info("\n✨ All users already have correct total_invested values!");
            } else {
                $this->line("\n💡 Tip: Run without --dry-run to apply these changes");
                $this->line("💡 Tip: Use --user-id=X to see detailed breakdown for a specific user");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Dry run failed: " . $e->getMessage());
            return 1;
        }
    }
}