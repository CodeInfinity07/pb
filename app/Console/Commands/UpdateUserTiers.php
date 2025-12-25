<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CommissionSetting;
use App\Models\User;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateUserTiers extends Command
{
    protected $signature = 'users:update-tiers 
                            {--dry-run : Show what would be updated without actually updating}
                            {--user-id= : Update specific user by ID}';

    protected $description = 'Update user tiers based on investment and qualified referral hierarchy';

    private $stats = [
        'processed' => 0,
        'updated' => 0,
        'upgrades' => 0,
        'downgrades' => 0,
        'errors' => 0,
    ];

    private $tierStats = [];
    private $tiers;

    public function handle(): int
    {
        $startTime = now();
        
        $this->info('Starting User Tier Update Process');
        $this->newLine();

        try {
            // Load active tiers
            $this->tiers = CommissionSetting::where('is_active', true)
                ->orderBy('level', 'asc')
                ->get();

            if ($this->tiers->isEmpty()) {
                $this->error('No active commission tiers found!');
                return 1;
            }

            $this->initializeTierStats();
            $this->displayTierRequirements();

            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No changes will be saved');
                $this->newLine();
            }

            // Execute
            if ($userId = $this->option('user-id')) {
                $this->processSpecificUser($userId);
            } else {
                $this->processAllUsers();
            }

            // Display results
            $this->displayResults($startTime);

            return 0;

        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::error('User tier update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function initializeTierStats(): void
    {
        $this->tierStats[0] = ['current' => 0, 'proposed' => 0];
        
        foreach ($this->tiers as $tier) {
            $this->tierStats[$tier->level] = ['current' => 0, 'proposed' => 0];
        }
    }

    private function displayTierRequirements(): void
    {
        $this->info('Commission Tier Requirements:');
        
        $requirements = [['0', 'No Tier', '< $50', '-', '-', 'Any']];

        foreach ($this->tiers as $tier) {
            $requirements[] = [
                $tier->level,
                $tier->name,
                '$50+',
                $tier->min_direct_referrals,
                $tier->min_indirect_referrals,
                'Active'
            ];
        }

        $this->table(
            ['Level', 'Tier', 'Min Investment', 'Min Direct', 'Min Indirect', 'Status'],
            $requirements
        );
        
        $this->line('Note: Direct = Level 1, Indirect = Level 2 + Level 3 (All must have invested $50+)');
        $this->newLine();
    }

    private function processAllUsers(): void
    {
        $totalUsers = User::count();
        $this->info("Processing {$totalUsers} users...");
        
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        $changes = [];

        User::with(['profile'])->chunk(100, function ($users) use (&$progressBar, &$changes) {
            foreach ($users as $user) {
                $this->stats['processed']++;
                $progressBar->advance();

                if (!$user->profile) {
                    continue;
                }

                try {
                    $result = $this->evaluateUser($user);
                    
                    if ($result['changed']) {
                        $changes[] = $result;
                    }

                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::error('Failed to process user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        if (!empty($changes)) {
            $this->displayChanges($changes);
        }
    }

    private function processSpecificUser(int $userId): void
    {
        $user = User::with(['profile'])->find($userId);

        if (!$user) {
            $this->error("User #{$userId} not found");
            return;
        }

        if (!$user->profile) {
            $this->error("User profile not found for user #{$userId}");
            return;
        }

        $this->info("Processing User #{$userId} ({$user->email})");
        $this->newLine();

        $result = $this->evaluateUser($user, true);
        
        if (!$result['changed']) {
            $tierName = $this->getTierName($result['current_level']);
            $this->info("User is already at correct tier: Level {$result['current_level']} ({$tierName})");
        }
    }

    private function evaluateUser(User $user, bool $verbose = false): array
    {
        $currentLevel = $user->profile->level ?? 0;
        $this->tierStats[$currentLevel]['current']++;

        // Get user qualification data
        $qualifications = $this->getUserQualifications($user);
        
        // Determine qualifying tier
        $newLevel = $this->determineQualifyingTier($qualifications);
        $this->tierStats[$newLevel]['proposed']++;

        $changed = $currentLevel !== $newLevel;
        
        if ($changed) {
            if ($newLevel > $currentLevel) {
                $this->stats['upgrades']++;
            } else {
                $this->stats['downgrades']++;
            }
        }

        if ($verbose) {
            $this->displayUserDetails($user, $qualifications, $currentLevel, $newLevel);
        }

        // Apply changes if not dry run
        if ($changed && !$this->option('dry-run')) {
            $this->applyTierChange($user, $newLevel);
            $this->stats['updated']++;
        }

        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'current_level' => $currentLevel,
            'new_level' => $newLevel,
            'changed' => $changed,
            'qualifications' => $qualifications,
        ];
    }

    private function getUserQualifications(User $user): array
    {
        // Calculate user's total invested from user_investments table
        $totalInvested = UserInvestment::where('user_id', $user->id)->sum('amount') ?? 0;
        
        $isActive = $user->status === 'active';
        $meetsInvestment = $totalInvested >= 50;

        // Get all user IDs who have invested $50+
        $qualifiedUserIds = UserInvestment::select('user_id')
            ->groupBy('user_id')
            ->havingRaw('SUM(amount) >= 50')
            ->pluck('user_id')
            ->toArray();

        // Level 1 (Direct) - users where I am the sponsor AND they have invested $50+
        $level1Ids = User::where('sponsor_id', $user->id)
            ->where('status', 'active')
            ->whereIn('id', $qualifiedUserIds)
            ->pluck('id')
            ->toArray();

        $directCount = count($level1Ids);

        // Level 2 - users where my Level 1 referrals are the sponsor AND they have invested $50+
        $level2Count = 0;
        $level2Ids = [];
        
        if (!empty($level1Ids)) {
            $level2Ids = User::whereIn('sponsor_id', $level1Ids)
                ->where('status', 'active')
                ->whereIn('id', $qualifiedUserIds)
                ->pluck('id')
                ->toArray();
            
            $level2Count = count($level2Ids);
        }

        // Level 3 - users where my Level 2 referrals are the sponsor AND they have invested $50+
        $level3Count = 0;
        
        if (!empty($level2Ids)) {
            $level3Count = User::whereIn('sponsor_id', $level2Ids)
                ->where('status', 'active')
                ->whereIn('id', $qualifiedUserIds)
                ->count();
        }

        $indirectCount = $level2Count + $level3Count;

        return [
            'total_invested' => $totalInvested,
            'is_active' => $isActive,
            'meets_investment' => $meetsInvestment,
            'direct_count' => $directCount,
            'level_2_count' => $level2Count,
            'level_3_count' => $level3Count,
            'indirect_count' => $indirectCount,
        ];
    }

    private function determineQualifyingTier(array $qual): int
    {
        // Must be active and have $50+ invested to qualify for any tier
        if (!$qual['is_active'] || !$qual['meets_investment']) {
            return 0;
        }

        // Check tiers from highest to lowest
        foreach ($this->tiers->reverse() as $tier) {
            if ($qual['direct_count'] >= $tier->min_direct_referrals 
                && $qual['indirect_count'] >= $tier->min_indirect_referrals) {
                return $tier->level;
            }
        }

        return 0;
    }

    private function displayUserDetails(User $user, array $qual, int $currentLevel, int $newLevel): void
    {
        $currentTier = $this->getTierName($currentLevel);
        $newTier = $this->getTierName($newLevel);

        $this->table(
            ['Metric', 'Value'],
            [
                ['User ID', $user->id],
                ['Email', $user->email],
                ['Status', $user->status],
                ['Total Invested', '$' . number_format($qual['total_invested'], 2)],
                ['', ''],
                ['Direct Referrals (Level 1)', $qual['direct_count']],
                ['Level 2 Referrals', $qual['level_2_count']],
                ['Level 3 Referrals', $qual['level_3_count']],
                ['Indirect Total (L2+L3)', $qual['indirect_count']],
                ['', ''],
                ['Current Tier', "Level {$currentLevel} ({$currentTier})"],
                ['New Tier', "Level {$newLevel} ({$newTier})"],
                ['Change', $currentLevel === $newLevel ? 'No Change' : ($newLevel > $currentLevel ? 'UPGRADE' : 'DOWNGRADE')],
            ]
        );

        if ($newLevel === 0) {
            $this->newLine();
            $this->warn('Reason for Level 0:');
            if (!$qual['is_active']) {
                $this->line('  - User is not active');
            }
            if (!$qual['meets_investment']) {
                $this->line('  - Has not invested $50 or more');
            }
            if ($qual['is_active'] && $qual['meets_investment']) {
                $this->line('  - Insufficient qualified referrals');
            }
        }

        $this->newLine();
    }

    private function displayChanges(array $changes): void
    {
        $this->info('Users with Tier Changes:');
        $this->newLine();

        foreach ($changes as $change) {
            $symbol = $change['new_level'] > $change['current_level'] ? '^' : 'v';
            $currentTier = $this->getTierName($change['current_level']);
            $newTier = $this->getTierName($change['new_level']);
            
            $this->line(sprintf(
                '  %s User #%d (%s): Level %d (%s) -> Level %d (%s)',
                $symbol,
                $change['user_id'],
                $change['email'],
                $change['current_level'],
                $currentTier,
                $change['new_level'],
                $newTier
            ));

            $qual = $change['qualifications'];
            $this->line(sprintf(
                '     Invested: $%s | Direct: %d | Indirect: %d (L2:%d + L3:%d)',
                number_format($qual['total_invested'], 2),
                $qual['direct_count'],
                $qual['indirect_count'],
                $qual['level_2_count'],
                $qual['level_3_count']
            ));
        }

        $this->newLine();
    }

    private function applyTierChange(User $user, int $newLevel): void
    {
        DB::transaction(function () use ($user, $newLevel) {
            // Update profile
            $user->profile->update(['level' => $newLevel]);

            // Update active investments
            $user->activeInvestments()->update(['tier_level' => $newLevel]);

            Log::info('User tier updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'new_level' => $newLevel,
            ]);
        });
    }

    private function displayResults($startTime): void
    {
        $duration = now()->diffInSeconds($startTime);

        $this->newLine();
        $this->info('Execution Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $this->stats['processed']],
                ['Updated', $this->stats['updated']],
                ['Upgrades', $this->stats['upgrades']],
                ['Downgrades', $this->stats['downgrades']],
                ['Errors', $this->stats['errors']],
                ['Duration', $duration . ' seconds'],
            ]
        );

        $this->newLine();
        $this->displayTierDistribution();

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('This was a DRY RUN - no changes were saved');
        }
    }

    private function displayTierDistribution(): void
    {
        $this->info('Tier Distribution:');

        $distribution = [];
        
        foreach ($this->tierStats as $level => $stats) {
            $tierName = $this->getTierName($level);
            $change = $stats['proposed'] - $stats['current'];
            $changeStr = $change > 0 ? "+{$change}" : ($change < 0 ? (string)$change : '0');

            $distribution[] = [
                $level,
                $tierName,
                $stats['current'],
                $stats['proposed'],
                $changeStr,
            ];
        }

        $this->table(
            ['Level', 'Tier Name', 'Current', 'Proposed', 'Change'],
            $distribution
        );
    }

    private function getTierName(int $level): string
    {
        if ($level === 0) {
            return 'No Tier';
        }

        $tier = $this->tiers->firstWhere('level', $level);
        return $tier ? $tier->name : "Level {$level}";
    }
}