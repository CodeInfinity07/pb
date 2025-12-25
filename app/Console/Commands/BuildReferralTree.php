<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuildReferralTree extends Command
{
    protected $signature = 'referrals:build-tree 
                            {--user-id= : Build tree for specific user only}
                            {--max-level=3 : Maximum referral level to track (default: 3)}
                            {--dry-run : Show what would be created without actually creating}
                            {--truncate : Clear all existing referral records before rebuilding}';

    protected $description = 'Build or rebuild the referral tree in user_referrals table';

    private $created = 0;
    private $updated = 0;
    private $skipped = 0;
    private $errors = 0;

    public function handle(): int
    {
        $startTime = now();
        
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║   Building Referral Tree Structure    ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No data will be saved');
            $this->newLine();
        }

        try {
            // Truncate if requested
            if ($this->option('truncate') && !$this->option('dry-run')) {
                if ($this->confirm('⚠️  This will delete ALL existing referral records. Continue?', false)) {
                    $this->info('Truncating user_referrals table...');
                    UserReferral::truncate();
                    $this->info('✓ Table cleared');
                    $this->newLine();
                } else {
                    $this->info('Truncate cancelled. Proceeding with merge...');
                    $this->newLine();
                }
            }

            // Build for specific user or all users
            if ($userId = $this->option('user-id')) {
                $result = $this->buildForUser($userId);
            } else {
                $result = $this->buildForAllUsers();
            }

            $duration = now()->diffInSeconds($startTime);
            
            $this->newLine();
            $this->info('╔════════════════════════════════════════╗');
            $this->info('║           Build Complete!              ║');
            $this->info('╚════════════════════════════════════════╝');
            $this->newLine();

            $this->displaySummary($duration);

            if ($this->option('dry-run')) {
                $this->newLine();
                $this->warn('This was a DRY RUN. No data was saved.');
                $this->info('Run without --dry-run to save changes:');
                $this->line('php artisan referrals:build-tree');
            }

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('╔════════════════════════════════════════╗');
            $this->error('║           Build Failed!                ║');
            $this->error('╚════════════════════════════════════════╝');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            Log::error('Referral tree build failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }

    /**
     * Build referral tree for all users
     */
    private function buildForAllUsers(): bool
    {
        $this->info('Building referral tree for all users...');
        
        // Get all users who have sponsors (not root users)
        $users = User::whereNotNull('sponsor_id')->get();
        
        $this->info("Found {$users->count()} users with sponsors");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            try {
                $this->buildReferralChain($user);
            } catch (\Exception $e) {
                $this->errors++;
                Log::error('Failed to build referral chain', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return true;
    }

    /**
     * Build referral tree for specific user
     */
    private function buildForUser(int $userId): bool
    {
        $user = User::find($userId);

        if (!$user) {
            $this->error("User #{$userId} not found");
            return false;
        }

        $this->info("Building referral tree for User #{$userId} ({$user->email})");
        $this->newLine();

        if (!$user->sponsor_id) {
            $this->warn('This user has no sponsor. Building their downline instead...');
            $this->newLine();
            return $this->buildDownline($user);
        }

        $this->buildReferralChain($user);

        return true;
    }

    /**
     * Build the referral chain for a user
     * This creates records linking the user to all their upline sponsors
     */
    private function buildReferralChain(User $user): void
    {
        $currentUser = $user;
        $level = 1;
        $maxLevel = (int) $this->option('max-level');

        // Traverse up the sponsor chain
        while ($currentUser->sponsor_id && $level <= $maxLevel) {
            $sponsor = User::find($currentUser->sponsor_id);
            
            if (!$sponsor) {
                $this->errors++;
                Log::warning('Sponsor not found', [
                    'user_id' => $user->id,
                    'sponsor_id' => $currentUser->sponsor_id,
                    'level' => $level
                ]);
                break;
            }

            // Create or update the referral record
            $this->createOrUpdateReferral($sponsor->id, $user->id, $level);

            // Move up the chain
            $currentUser = $sponsor;
            $level++;

            // Prevent infinite loops
            if ($level > 20) {
                Log::error('Infinite loop detected in referral chain', [
                    'user_id' => $user->id,
                    'current_sponsor_id' => $currentUser->id
                ]);
                break;
            }
        }
    }

    /**
     * Build downline for a specific user (all their referrals)
     */
    private function buildDownline(User $user): bool
    {
        $this->info("Building downline for {$user->email}...");
        
        $maxLevel = (int) $this->option('max-level');
        
        // Build each level
        for ($level = 1; $level <= $maxLevel; $level++) {
            $count = $this->buildDownlineLevel($user->id, $level);
            $this->line("Level {$level}: {$count} referrals");
        }

        return true;
    }

    /**
     * Build a specific level of downline
     */
    private function buildDownlineLevel(int $sponsorId, int $level): int
    {
        $count = 0;

        if ($level === 1) {
            // Direct referrals
            $referrals = User::where('sponsor_id', $sponsorId)->get();
            
            foreach ($referrals as $referral) {
                $this->createOrUpdateReferral($sponsorId, $referral->id, 1);
                $count++;
            }
        } else {
            // Get users from previous level
            $previousLevelUsers = UserReferral::where('sponsor_id', $sponsorId)
                ->where('level', $level - 1)
                ->pluck('user_id');

            foreach ($previousLevelUsers as $userId) {
                $directReferrals = User::where('sponsor_id', $userId)->get();
                
                foreach ($directReferrals as $referral) {
                    $this->createOrUpdateReferral($sponsorId, $referral->id, $level);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Create or update a referral record
     */
    private function createOrUpdateReferral(int $sponsorId, int $userId, int $level): void
    {
        $referredUser = User::find($userId);
        
        if (!$referredUser) {
            $this->errors++;
            return;
        }

        $data = [
            'sponsor_id' => $sponsorId,
            'user_id' => $userId,
            'level' => $level,
            'status' => $referredUser->status,
            'commission_earned' => 0, // You can calculate this separately
        ];

        if ($this->option('dry-run')) {
            $existing = UserReferral::where('sponsor_id', $sponsorId)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $this->line("[DRY RUN] Would update: Sponsor #{$sponsorId} -> User #{$userId} (Level {$level})");
                $this->updated++;
            } else {
                $this->line("[DRY RUN] Would create: Sponsor #{$sponsorId} -> User #{$userId} (Level {$level})");
                $this->created++;
            }
            return;
        }

        // Try to update existing record
        $existing = UserReferral::where('sponsor_id', $sponsorId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            // Update if level or status changed
            if ($existing->level !== $level || $existing->status !== $data['status']) {
                $existing->update($data);
                $this->updated++;
                
                Log::info('Referral record updated', [
                    'sponsor_id' => $sponsorId,
                    'user_id' => $userId,
                    'level' => $level,
                    'old_level' => $existing->level,
                ]);
            } else {
                $this->skipped++;
            }
        } else {
            // Create new record
            UserReferral::create($data);
            $this->created++;
            
            Log::info('Referral record created', [
                'sponsor_id' => $sponsorId,
                'user_id' => $userId,
                'level' => $level
            ]);
        }
    }

    /**
     * Display summary statistics
     */
    private function displaySummary(int $duration): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Records Created', $this->created],
                ['Records Updated', $this->updated],
                ['Records Skipped', $this->skipped],
                ['Errors', $this->errors],
                ['Duration', $duration . ' seconds'],
            ]
        );

        if (!$this->option('dry-run')) {
            $this->newLine();
            $this->info('Current Referral Statistics:');
            
            $stats = [
                ['Total Referrals', UserReferral::count()],
                ['Active Referrals', UserReferral::where('status', 'active')->count()],
                ['Level 1 (Direct)', UserReferral::where('level', 1)->count()],
                ['Level 2 (Indirect)', UserReferral::where('level', 2)->count()],
                ['Level 3 (Indirect)', UserReferral::where('level', 3)->count()],
            ];

            // Add higher levels if they exist
            $maxLevel = (int) $this->option('max-level');
            for ($level = 4; $level <= $maxLevel; $level++) {
                $count = UserReferral::where('level', $level)->count();
                if ($count > 0) {
                    $stats[] = ["Level {$level}", $count];
                }
            }

            $this->table(['Statistic', 'Value'], $stats);
        }
    }
}