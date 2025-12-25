<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserInvestment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeUserInvestments extends Command
{
    protected $signature = 'investments:merge 
                            {--dry-run : Preview changes without saving}
                            {--user-id= : Process specific user only}';

    protected $description = 'Merge duplicate active investments - keeps oldest, deletes rest';

    private $stats = [
        'users_checked' => 0,
        'investments_deleted' => 0,
        'total_consolidated' => 0,
        'errors' => 0,
    ];

    private $errors = [];
    private $merges = [];

    public function handle(): int
    {
        $start = now();
        
        $this->info('Investment Merge Tool');
        $this->line('Consolidates multiple active investments in same plan');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No actual changes');
            $this->newLine();
        }

        try {
            if ($userId = $this->option('user-id')) {
                $this->processSingleUser($userId);
            } else {
                $this->processAllUsers();
            }

            $this->showResults($start);
            return 0;

        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::error('Merge command error', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    private function processAllUsers(): void
    {
        // Find all users with duplicate investments
        $duplicates = DB::table('user_investments')
            ->select('user_id', 'investment_plan_id', DB::raw('COUNT(*) as count'))
            ->where('status', 'active')
            ->groupBy('user_id', 'investment_plan_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate investments found.');
            return;
        }

        $this->info("Found {$duplicates->count()} users with duplicates");
        $bar = $this->output->createProgressBar($duplicates->count());
        $bar->start();

        foreach ($duplicates as $dup) {
            $this->stats['users_checked']++;
            
            try {
                $this->mergeInvestments($dup->user_id, $dup->investment_plan_id);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->errors[] = [
                    'user_id' => $dup->user_id,
                    'plan_id' => $dup->investment_plan_id,
                    'error' => $e->getMessage()
                ];
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    private function processSingleUser(int $userId): void
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User #{$userId} not found");
            return;
        }

        $this->info("Processing: {$user->email}");
        $this->newLine();

        $duplicates = DB::table('user_investments')
            ->select('investment_plan_id', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->groupBy('investment_plan_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicates found for this user');
            return;
        }

        $this->stats['users_checked']++;

        foreach ($duplicates as $dup) {
            try {
                $this->mergeInvestments($userId, $dup->investment_plan_id, true);
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->error('Merge failed: ' . $e->getMessage());
            }
        }
    }

    private function mergeInvestments(int $userId, int $planId, bool $verbose = false): void
    {
        // Get all investments for this user/plan combo, oldest first
        $investments = UserInvestment::where('user_id', $userId)
            ->where('investment_plan_id', $planId)
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($investments->count() < 2) {
            return;
        }

        $keep = $investments->first();
        $delete = $investments->slice(1);
        
        $originalAmount = $keep->amount;
        $addAmount = $delete->sum('amount');
        $newAmount = $originalAmount + $addAmount;

        if ($verbose) {
            $this->showMergePreview($keep, $delete, $newAmount);
        }

        // Perform the merge
        if (!$this->option('dry-run')) {
            DB::beginTransaction();
            
            try {
                // Update the investment we're keeping
                $keep->update([
                    'amount' => $newAmount,
                    'notes' => ($keep->notes ?? '') . 
                        "\n[MERGE " . now()->format('Y-m-d H:i') . "] " .
                        "Consolidated {$delete->count()} investment(s). " .
                        "Added: $" . number_format($addAmount, 2) . ". " .
                        "Deleted IDs: " . $delete->pluck('id')->implode(', ')
                ]);

                // Delete the others
                UserInvestment::whereIn('id', $delete->pluck('id'))->delete();

                DB::commit();

                $this->stats['investments_deleted'] += $delete->count();
                $this->stats['total_consolidated'] += $addAmount;

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } else {
            // Dry run - just count
            $this->stats['investments_deleted'] += $delete->count();
            $this->stats['total_consolidated'] += $addAmount;
        }

        // Store merge info
        $this->merges[] = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'kept_id' => $keep->id,
            'deleted_count' => $delete->count(),
            'original' => $originalAmount,
            'added' => $addAmount,
            'new_total' => $newAmount
        ];
    }

    private function showMergePreview($keep, $delete, $newAmount): void
    {
        $this->line('┌─────────────────────────────────────────┐');
        $this->line('│ MERGE PREVIEW                           │');
        $this->line('├─────────────────────────────────────────┤');
        $this->line("│ KEEP: Investment #{$keep->id}");
        $this->line("│   Amount: $" . number_format($keep->amount, 2));
        $this->line("│   Created: {$keep->created_at->format('Y-m-d')}");
        $this->line('│');
        $this->line("│ DELETE: {$delete->count()} investment(s)");
        
        foreach ($delete as $inv) {
            $this->line("│   #{$inv->id}: $" . number_format($inv->amount, 2));
        }
        
        $this->line('│');
        $this->line("│ NEW TOTAL: $" . number_format($newAmount, 2));
        $this->line('└─────────────────────────────────────────┘');
        $this->newLine();
    }

    private function showResults($start): void
    {
        $duration = now()->diffInSeconds($start);

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('RESULTS');
        $this->info('═══════════════════════════════════════');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Users Checked', $this->stats['users_checked']],
                ['Investments Deleted', $this->stats['investments_deleted']],
                ['Amount Consolidated', '$' . number_format($this->stats['total_consolidated'], 2)],
                ['Errors', $this->stats['errors']],
                ['Duration', $duration . 's'],
            ]
        );

        if (!empty($this->merges)) {
            $this->newLine();
            $this->info('MERGES PERFORMED:');
            
            foreach ($this->merges as $m) {
                $this->line(sprintf(
                    '  User #%d | Plan #%d | Kept #%d | Deleted %d | $%s → $%s',
                    $m['user_id'],
                    $m['plan_id'],
                    $m['kept_id'],
                    $m['deleted_count'],
                    number_format($m['original'], 2),
                    number_format($m['new_total'], 2)
                ));
            }
        }

        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('ERRORS:');
            
            foreach ($this->errors as $i => $err) {
                $this->line(sprintf(
                    '  %d. User #%d | Plan #%d',
                    $i + 1,
                    $err['user_id'],
                    $err['plan_id']
                ));
                $this->line("     {$err['error']}");
            }
            
            $this->newLine();
            $this->line('Check logs: storage/logs/laravel.log');
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('⚠ DRY RUN - No changes were made');
        }
    }
}