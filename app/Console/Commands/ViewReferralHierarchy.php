<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class ViewReferralHierarchy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referrals:view-hierarchy 
                            {user-id : The ID of the user to view referrals for}
                            {--depth=5 : Maximum depth to display (default: 5)}
                            {--show-inactive : Include users with less than $50 invested}
                            {--compact : Show compact view without details}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display complete referral hierarchy for a user in tree format (Active = $50+ invested)';

    private $totalReferrals = 0;
    private $levelCounts = [];
    private $activeCount = 0;
    private $inactiveCount = 0;
    private $totalInvested = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('user-id');
        $maxDepth = $this->option('depth');
        $showInactive = $this->option('show-inactive');
        $compact = $this->option('compact');

        $user = User::with(['profile'])->find($userId);

        if (!$user) {
            $this->error("❌ User not found: {$userId}");
            return 1;
        }

        // Reset counters
        $this->totalReferrals = 0;
        $this->levelCounts = [];
        $this->activeCount = 0;
        $this->inactiveCount = 0;
        $this->totalInvested = 0;

        // Display user info
        $this->info("👤 Referral Hierarchy for: {$user->name} ({$user->email})");
        $this->line("📊 User ID: {$user->id} | Status: {$user->status} | Tier Level: " . ($user->profile->level ?? 0));
        $this->line("💰 Total Invested: $" . number_format($user->total_invested ?? 0, 2));
        $this->line(str_repeat('─', 80));
        $this->newLine();

        // Build and display the tree
        $this->displayTree($user, 0, '', $maxDepth, $showInactive, $compact);

        // Display summary
        $this->newLine();
        $this->line(str_repeat('─', 80));
        $this->info("📈 Summary Statistics:");
        $this->line("  • Total Referrals: {$this->totalReferrals}");
        $this->line("  • Active: {$this->activeCount} | Inactive: {$this->inactiveCount}");
        $this->line("  • Total Network Investment: $" . number_format($this->totalInvested, 2));
        
        $this->newLine();
        $this->info("📊 Referrals by Level:");
        foreach ($this->levelCounts as $level => $count) {
            $this->line("  • Level {$level}: {$count} users");
        }

        return 0;
    }

    /**
     * Display referral tree recursively
     */
    private function displayTree(User $user, int $currentLevel, string $prefix, int $maxDepth, bool $showInactive, bool $compact): void
    {
        // Stop if max depth reached
        if ($currentLevel >= $maxDepth) {
            return;
        }

        // Get direct referrals
        $referrals = $user->directReferrals()->with(['profile'])->get();

        // Filter based on investment if not showing inactive
        if (!$showInactive) {
            $referrals = $referrals->filter(function ($referral) {
                return ($referral->total_invested ?? 0) >= 50;
            });
        }

        if ($referrals->isEmpty()) {
            return;
        }

        foreach ($referrals as $index => $referral) {
            $isLast = ($index === $referrals->count() - 1);
            
            // Update statistics
            $this->totalReferrals++;
            $level = $currentLevel + 1;
            $this->levelCounts[$level] = ($this->levelCounts[$level] ?? 0) + 1;
            
            $invested = $referral->total_invested ?? 0;
            
            // Check if user is truly active (has invested $50+)
            $isActive = $invested >= 50;
            
            if ($isActive) {
                $this->activeCount++;
            } else {
                $this->inactiveCount++;
            }
            
            $this->totalInvested += $invested;

            // Build tree characters
            $connector = $isLast ? '└─' : '├─';
            $extender = $isLast ? '  ' : '│ ';

            // Build the line
            $line = $prefix . $connector . ' ';
            
            // Status indicator based on investment
            $statusIcon = $isActive ? '✅' : '❌';
            
            // Tier level
            $tierLevel = $referral->profile->level ?? 0;
            $tierBadge = "L{$tierLevel}";
            
            if ($compact) {
                // Compact view
                $line .= "{$statusIcon} [{$tierBadge}] {$referral->name} (#{$referral->id})";
            } else {
                // Detailed view
                $line .= "{$statusIcon} [{$tierBadge}] {$referral->name} (#{$referral->id})";
                $line .= " | {$referral->email}";
                $line .= " | $" . number_format($invested, 2);
                $line .= " | " . ($isActive ? 'Active ($50+)' : 'Inactive (< $50)');
            }

            $this->line($line);

            // Recursively display children
            $this->displayTree($referral, $currentLevel + 1, $prefix . $extender, $maxDepth, $showInactive, $compact);
        }
    }
}