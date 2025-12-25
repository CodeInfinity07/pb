<?php

namespace App\Services;

use App\Models\Leaderboard;
use App\Models\LeaderboardPosition;
use App\Models\User;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeaderboardService
{
    /**
     * Calculate and update leaderboard positions for both competitive and target types
     * Only counts referrals where the referred user has made an investment
     */
    public function calculatePositions(Leaderboard $leaderboard): int
    {
        Log::info('Calculating leaderboard positions', [
            'leaderboard_id' => $leaderboard->id,
            'type' => $leaderboard->type,
            'title' => $leaderboard->title
        ]);

        DB::beginTransaction();
        
        try {
            // Clear existing positions
            $leaderboard->positions()->delete();

            // Get referral counts for the leaderboard period (only those with investments)
            $referralData = $this->getReferralCounts($leaderboard);

            if (empty($referralData)) {
                DB::commit();
                Log::info('No referral data found for leaderboard', [
                    'leaderboard_id' => $leaderboard->id
                ]);
                return 0;
            }

            // Create positions based on leaderboard type
            $participantCount = match($leaderboard->type) {
                'competitive' => $this->createCompetitivePositions($leaderboard, $referralData),
                'target' => $this->createTargetPositions($leaderboard, $referralData),
                default => $this->createCompetitivePositions($leaderboard, $referralData)
            };

            DB::commit();

            Log::info('Leaderboard positions calculated successfully', [
                'leaderboard_id' => $leaderboard->id,
                'type' => $leaderboard->type,
                'total_positions' => $participantCount
            ]);

            return $participantCount;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to calculate leaderboard positions', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Create positions for competitive leaderboards (traditional ranking)
     */
    private function createCompetitivePositions(Leaderboard $leaderboard, array $referralData): int
    {
        $position = 1;
        $currentRank = 1;
        $lastCount = null;
        $positionsCreated = 0;

        foreach ($referralData as $data) {
            // Handle ties - users with same count get same position
            if ($lastCount !== null && $data['referral_count'] !== $lastCount) {
                $currentRank = $position;
            }

            // Only create positions up to max_positions limit for display
            if ($position <= $leaderboard->max_positions) {
                $prizeAmount = $this->calculateCompetitivePrizeAmount($leaderboard, $currentRank);

                LeaderboardPosition::create([
                    'leaderboard_id' => $leaderboard->id,
                    'user_id' => $data['sponsor_id'],
                    'position' => $currentRank,
                    'referral_count' => $data['referral_count'],
                    'prize_amount' => $prizeAmount,
                    'prize_awarded' => false,
                ]);

                $positionsCreated++;
            }

            $lastCount = $data['referral_count'];
            $position++;
        }

        return $positionsCreated;
    }

    /**
     * Create positions for target-based leaderboards (goal achievement)
     */
    private function createTargetPositions(Leaderboard $leaderboard, array $referralData): int
    {
        $position = 1;
        $positionsCreated = 0;
        $qualifiedWinners = 0;

        foreach ($referralData as $data) {
            // Check if user qualifies for the prize
            $qualifies = $data['referral_count'] >= $leaderboard->target_referrals;
            $prizeAmount = 0;

            // Award prize if user qualifies and we haven't exceeded max winners limit
            if ($qualifies) {
                $hasWinnersLimit = $leaderboard->max_winners !== null;
                
                if (!$hasWinnersLimit || $qualifiedWinners < $leaderboard->max_winners) {
                    $prizeAmount = $leaderboard->target_prize_amount;
                    $qualifiedWinners++;
                }
            }

            // Create position for display (up to max_positions limit)
            if ($position <= $leaderboard->max_positions) {
                LeaderboardPosition::create([
                    'leaderboard_id' => $leaderboard->id,
                    'user_id' => $data['sponsor_id'],
                    'position' => $position,
                    'referral_count' => $data['referral_count'],
                    'prize_amount' => $prizeAmount,
                    'prize_awarded' => false,
                ]);

                $positionsCreated++;
            }

            $position++;
        }

        Log::info('Target leaderboard positions created', [
            'leaderboard_id' => $leaderboard->id,
            'total_participants' => count($referralData),
            'qualified_winners' => $qualifiedWinners,
            'target_referrals' => $leaderboard->target_referrals,
            'max_winners' => $leaderboard->max_winners,
            'positions_displayed' => $positionsCreated
        ]);

        return $positionsCreated;
    }

    /**
     * Get referral counts for users during leaderboard period
     * Only count referrals where the referred user has made an investment
     */
    private function getReferralCounts(Leaderboard $leaderboard): array
    {
        $query = User::select([
                'sponsor_id', 
                DB::raw('COUNT(*) as referral_count'),
                DB::raw('MIN(users.created_at) as earliest_referral')
            ])
            ->whereNotNull('sponsor_id')
            ->whereBetween('users.created_at', [$leaderboard->start_date, $leaderboard->end_date])
            // CRITICAL: Only count referrals who have made investments
            ->whereHas('investments', function($investmentQuery) use ($leaderboard) {
                // Only count investments made during leaderboard period
                $investmentQuery->where('user_investments.created_at', '>=', $leaderboard->start_date)
                               ->where('user_investments.created_at', '<=', $leaderboard->end_date)
                               ->whereIn('status', ['active', 'completed']);
                               
                // Optional: Add minimum investment amount filter
                // $investmentQuery->where('amount', '>=', 100); // minimum $100 investment
            })
            ->groupBy('sponsor_id');

        // Apply referral type filters
        switch ($leaderboard->referral_type) {
            case 'first_level':
                // Only direct referrals - already filtered by sponsor_id
                break;
                
            case 'verified_only':
                $query->whereNotNull('email_verified_at');
                break;
                
            case 'active_only':
                $query->where('status', 'active');
                break;
                
            case 'all':
            default:
                // No additional filters
                break;
        }

        $results = $query->orderByDesc('referral_count')
                        ->orderBy('earliest_referral') // Tie breaker: earlier registration wins
                        ->get();

        // Convert to array format
        return $results->map(function ($item) {
            return [
                'sponsor_id' => $item->sponsor_id,
                'referral_count' => (int) $item->referral_count,
                'earliest_referral' => $item->earliest_referral
            ];
        })->toArray();
    }

    /**
     * Calculate prize amount for competitive leaderboard position
     */
    private function calculateCompetitivePrizeAmount(Leaderboard $leaderboard, int $position): float
    {
        if (!$leaderboard->prize_structure) {
            return 0.0;
        }

        foreach ($leaderboard->prize_structure as $prize) {
            // Check exact position match
            if (isset($prize['position']) && $prize['position'] == $position) {
                return (float) ($prize['amount'] ?? 0);
            }
            
            // Check range prizes (e.g., positions 4-10)
            if (isset($prize['from_position']) && isset($prize['to_position'])) {
                if ($position >= $prize['from_position'] && $position <= $prize['to_position']) {
                    return (float) ($prize['amount'] ?? 0);
                }
            }
        }

        return 0.0;
    }

    /**
     * Distribute prizes to winners
     */
    public function distributePrizes(Leaderboard $leaderboard): bool
    {
        if (!$leaderboard->canDistributePrizes()) {
            Log::warning('Cannot distribute prizes for leaderboard', [
                'leaderboard_id' => $leaderboard->id,
                'status' => $leaderboard->status,
                'prizes_distributed' => $leaderboard->prizes_distributed
            ]);
            return false;
        }

        Log::info('Starting prize distribution', [
            'leaderboard_id' => $leaderboard->id,
            'type' => $leaderboard->type
        ]);

        DB::beginTransaction();

        try {
            $winners = $leaderboard->positions()
                ->where('prize_amount', '>', 0)
                ->where('prize_awarded', false)
                ->with('user')
                ->get();

            if ($winners->isEmpty()) {
                Log::info('No winners found for prize distribution', [
                    'leaderboard_id' => $leaderboard->id
                ]);
                
                // Still mark as distributed to prevent future attempts
                $leaderboard->update([
                    'prizes_distributed' => true,
                    'prizes_distributed_at' => now(),
                    'prizes_distributed_by' => auth()->id(),
                ]);

                DB::commit();
                return true;
            }

            $totalDistributed = 0;
            $successfulDistributions = 0;

            foreach ($winners as $position) {
                $user = $position->user;
                
                if (!$user) {
                    Log::error('User not found for position', [
                        'position_id' => $position->id,
                        'user_id' => $position->user_id
                    ]);
                    continue;
                }

                // Add prize to user's crypto wallet
                if ($this->addPrizeToUserWallet($user, $position->prize_amount, $leaderboard)) {
                    // Mark prize as awarded
                    $position->update([
                        'prize_awarded' => true,
                        'prize_awarded_at' => now(),
                    ]);

                    // Create transaction record
                    $this->createPrizeTransaction($user, $position, $leaderboard);

                    $totalDistributed += $position->prize_amount;
                    $successfulDistributions++;

                    Log::info('Prize awarded to user', [
                        'leaderboard_id' => $leaderboard->id,
                        'user_id' => $user->id,
                        'position' => $position->position,
                        'amount' => $position->prize_amount
                    ]);
                } else {
                    Log::error('Failed to distribute prize to user', [
                        'leaderboard_id' => $leaderboard->id,
                        'user_id' => $user->id,
                        'position' => $position->position,
                        'amount' => $position->prize_amount
                    ]);
                }
            }

            // Mark leaderboard as prizes distributed
            $leaderboard->update([
                'prizes_distributed' => true,
                'prizes_distributed_at' => now(),
                'prizes_distributed_by' => auth()->id(),
            ]);

            DB::commit();

            Log::info('Prize distribution completed', [
                'leaderboard_id' => $leaderboard->id,
                'total_winners' => $winners->count(),
                'successful_distributions' => $successfulDistributions,
                'total_amount_distributed' => $totalDistributed
            ]);

            return $successfulDistributions > 0;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Prize distribution failed', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Add prize amount to user's crypto wallet
     */
    private function addPrizeToUserWallet(User $user, float $amount, Leaderboard $leaderboard): bool
    {
        try {
            // Get user's primary USDT wallet or create one
            $wallet = $user->getOrCreateWallet('USDT_TRC20');
            
            // Add prize amount to wallet
            $wallet->increment('balance', $amount);
            
            Log::info('Prize added to user wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'leaderboard_id' => $leaderboard->id,
                'new_balance' => $wallet->fresh()->balance
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add prize to user wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create transaction record for prize distribution
     */
    private function createPrizeTransaction(User $user, LeaderboardPosition $position, Leaderboard $leaderboard): void
    {
        try {
            $transactionId = 'LEADERBOARD_' . $leaderboard->id . '_POS_' . $position->position . '_' . time();
            
            $description = $leaderboard->type === 'target' 
                ? "Target Achievement Prize - {$leaderboard->title} (Reached {$position->referral_count} qualified referrals)"
                : "Leaderboard Prize - Position #{$position->position} in {$leaderboard->title}";

            $user->transactions()->create([
                'transaction_id' => $transactionId,
                'type' => 'leaderboard_prize',
                'amount' => $position->prize_amount,
                'currency' => 'USDT_TRC20',
                'status' => 'completed',
                'description' => $description,
                'balance_after' => $user->available_balance,
                'metadata' => [
                    'leaderboard_id' => $leaderboard->id,
                    'leaderboard_title' => $leaderboard->title,
                    'leaderboard_type' => $leaderboard->type,
                    'position' => $position->position,
                    'qualified_referral_count' => $position->referral_count,
                    'target_referrals' => $leaderboard->target_referrals,
                    'prize_type' => 'leaderboard_prize',
                    'awarded_at' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create prize transaction', [
                'user_id' => $user->id,
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get active leaderboards for users
     */
    public function getActiveLeaderboards()
    {
        return Leaderboard::active()
            ->visible()
            ->current()
            ->with(['positions.user'])
            ->orderBy('end_date')
            ->get();
    }

    /**
     * Get completed leaderboards for users
     */
    public function getCompletedLeaderboards($limit = 5)
    {
        return Leaderboard::completed()
            ->visible()
            ->with(['positions.user'])
            ->orderByDesc('end_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's leaderboard history
     */
    public function getUserLeaderboardHistory(User $user, $limit = 10)
    {
        return LeaderboardPosition::whereHas('leaderboard', function($q) {
                $q->where('show_to_users', true);
            })
            ->where('user_id', $user->id)
            ->with(['leaderboard'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get leaderboard statistics for admin dashboard
     */
    public function getStatistics(): array
    {
        return [
            // Basic counts
            'total_leaderboards' => Leaderboard::count(),
            'active_leaderboards' => Leaderboard::active()->count(),
            'completed_leaderboards' => Leaderboard::completed()->count(),
            'upcoming_leaderboards' => Leaderboard::upcoming()->count(),
            
            // Type breakdown
            'competitive_leaderboards' => Leaderboard::competitive()->count(),
            'target_leaderboards' => Leaderboard::target()->count(),
            
            // Participation stats (only qualified participants)
            'total_participants' => LeaderboardPosition::distinct('user_id')->count(),
            'total_winners' => LeaderboardPosition::winners()->count(),
            
            // Prize statistics
            'total_prizes_distributed' => Leaderboard::where('prizes_distributed', true)->count(),
            'total_prize_amount_awarded' => LeaderboardPosition::prizeAwarded()->sum('prize_amount'),
            'pending_prize_amount' => LeaderboardPosition::prizePending()->sum('prize_amount'),
            
            // Investment-based referral tracking
            'total_qualified_referrals' => $this->getTotalQualifiedReferrals(),
            
            // Recent activity
            'leaderboards_this_month' => Leaderboard::whereMonth('created_at', now()->month)
                                                  ->whereYear('created_at', now()->year)
                                                  ->count(),
            'prizes_distributed_this_month' => LeaderboardPosition::prizeAwarded()
                                                                 ->whereMonth('prize_awarded_at', now()->month)
                                                                 ->whereYear('prize_awarded_at', now()->year)
                                                                 ->sum('prize_amount'),
        ];
    }

    /**
     * Get total count of qualified referrals (those with investments)
     */
    private function getTotalQualifiedReferrals(): int
    {
        return User::whereNotNull('sponsor_id')
            ->whereHas('investments', function($query) {
                $query->whereIn('status', ['active', 'completed']);
            })
            ->count();
    }

    /**
     * Auto-complete expired leaderboards
     */
    public function autoCompleteExpiredLeaderboards(): int
    {
        $expiredLeaderboards = Leaderboard::active()
            ->where('end_date', '<', now())
            ->get();

        $completed = 0;

        foreach ($expiredLeaderboards as $leaderboard) {
            try {
                // Calculate final positions
                $this->calculatePositions($leaderboard);

                // Mark as completed
                $leaderboard->update(['status' => 'completed']);

                $completed++;

                Log::info('Auto-completed expired leaderboard', [
                    'leaderboard_id' => $leaderboard->id,
                    'title' => $leaderboard->title,
                    'type' => $leaderboard->type
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to auto-complete leaderboard', [
                    'leaderboard_id' => $leaderboard->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $completed;
    }

    /**
     * Get current user rankings in active leaderboards
     * Only count qualified referrals (those with investments)
     */
    public function getCurrentUserRankings(User $user): array
    {
        $activeLeaderboards = $this->getActiveLeaderboards();
        $rankings = [];

        foreach ($activeLeaderboards as $leaderboard) {
            $position = $leaderboard->getUserPosition($user);
            
            if ($position) {
                $rankings[] = [
                    'leaderboard' => $leaderboard,
                    'position' => $position,
                    'rank' => $position->position,
                    'qualified_referral_count' => $position->referral_count,
                    'qualified' => $leaderboard->type === 'target' 
                        ? $position->referral_count >= $leaderboard->target_referrals 
                        : true,
                    'progress' => $leaderboard->type === 'target'
                        ? min(100, ($position->referral_count / $leaderboard->target_referrals) * 100)
                        : null
                ];
            } else {
                // Calculate current qualified referral count for this period
                $currentCount = $this->getUserReferralCount($user, $leaderboard);
                
                if ($currentCount > 0) {
                    $rankings[] = [
                        'leaderboard' => $leaderboard,
                        'position' => null,
                        'rank' => null,
                        'qualified_referral_count' => $currentCount,
                        'qualified' => $leaderboard->type === 'target' 
                            ? $currentCount >= $leaderboard->target_referrals 
                            : false,
                        'progress' => $leaderboard->type === 'target'
                            ? min(100, ($currentCount / $leaderboard->target_referrals) * 100)
                            : null
                    ];
                }
            }
        }

        return $rankings;
    }

    /**
     * Get user's qualified referral count for a specific leaderboard period
     * Only count referrals where the referred user has made an investment
     */
    private function getUserReferralCount(User $user, Leaderboard $leaderboard): int
    {
        $query = User::where('sponsor_id', $user->id)
            ->whereBetween('created_at', [$leaderboard->start_date, $leaderboard->end_date])
            // CRITICAL: Only count referrals who have made investments
            ->whereHas('investments', function($investmentQuery) use ($leaderboard) {
                // Only count investments made during leaderboard period
                $investmentQuery->where('user_investments.created_at', '>=', $leaderboard->start_date)
                               ->where('user_investments.created_at', '<=', $leaderboard->end_date)
                               ->whereIn('status', ['active', 'completed']);
                               
                // Optional: Add minimum investment amount filter
                // $investmentQuery->where('amount', '>=', 100); // minimum $100 investment
            });

        // Apply referral type filters
        switch ($leaderboard->referral_type) {
            case 'first_level':
                // Only direct referrals - no additional filtering needed
                break;
                
            case 'verified_only':
                $query->whereNotNull('email_verified_at');
                break;
                
            case 'active_only':
                $query->where('status', 'active');
                break;
                
            case 'all':
            default:
                // No additional filters
                break;
        }

        return $query->count();
    }

    /**
     * Get top performing sponsors for a leaderboard period
     * Only count qualified referrals (those with investments)
     */
    public function getTopSponsors(Leaderboard $leaderboard, int $limit = 10): array
    {
        $referralData = $this->getReferralCounts($leaderboard);
        
        return collect($referralData)->take($limit)->map(function($data) use ($leaderboard) {
            $sponsor = User::find($data['sponsor_id']);
            
            $potentialPrize = $leaderboard->type === 'competitive'
                ? $this->calculatePotentialCompetitivePrize($leaderboard, $data['referral_count'])
                : ($data['referral_count'] >= $leaderboard->target_referrals ? $leaderboard->target_prize_amount : 0);
            
            return [
                'user' => $sponsor,
                'qualified_referral_count' => $data['referral_count'],
                'prize_potential' => $potentialPrize,
                'qualified' => $leaderboard->type === 'target' 
                    ? $data['referral_count'] >= $leaderboard->target_referrals
                    : true
            ];
        })->toArray();
    }

    /**
     * Calculate potential prize for competitive leaderboards based on current standings
     */
    private function calculatePotentialCompetitivePrize(Leaderboard $leaderboard, int $referralCount): float
    {
        $referralData = $this->getReferralCounts($leaderboard);
        
        // Find where this count would rank
        $position = 1;
        foreach ($referralData as $data) {
            if ($data['referral_count'] > $referralCount) {
                $position++;
            } else {
                break;
            }
        }

        return $this->calculateCompetitivePrizeAmount($leaderboard, $position);
    }

    /**
     * Get leaderboard performance analytics
     */
    public function getLeaderboardAnalytics(Leaderboard $leaderboard): array
    {
        $positions = $leaderboard->positions()->with('user')->get();
        
        $analytics = [
            'total_participants' => $positions->count(),
            'total_qualified_referrals' => $positions->sum('referral_count'),
            'average_qualified_referrals' => $positions->count() > 0 ? round($positions->avg('referral_count'), 2) : 0,
            'top_performer' => $positions->sortBy('position')->first(),
            'total_prize_pool' => $positions->sum('prize_amount'),
            'prizes_awarded' => $positions->where('prize_awarded', true)->sum('prize_amount'),
            'prizes_pending' => $positions->where('prize_awarded', false)->sum('prize_amount'),
            'participation_by_day' => $this->getQualifiedParticipationByDay($leaderboard),
        ];

        // Add type-specific analytics
        if ($leaderboard->type === 'target') {
            $qualified = $positions->where('referral_count', '>=', $leaderboard->target_referrals);
            $analytics['qualified_participants'] = $qualified->count();
            $analytics['qualification_rate'] = $positions->count() > 0 
                ? round(($qualified->count() / $positions->count()) * 100, 2) 
                : 0;
            $analytics['target_referrals'] = $leaderboard->target_referrals;
            $analytics['max_winners'] = $leaderboard->max_winners;
        } else {
            $analytics['winner_positions'] = $positions->where('prize_amount', '>', 0)->count();
        }

        return $analytics;
    }

    /**
     * Get daily qualified participation data for leaderboard period
     * Only count referrals with investments
     */
    private function getQualifiedParticipationByDay(Leaderboard $leaderboard): array
    {
        $dailyData = User::whereNotNull('sponsor_id')
            ->whereBetween('created_at', [$leaderboard->start_date, $leaderboard->end_date])
            ->whereHas('investments', function($investmentQuery) use ($leaderboard) {
                $investmentQuery->where('user_investments.created_at', '>=', $leaderboard->start_date)
                               ->where('user_investments.created_at', '<=', $leaderboard->end_date)
                               ->whereIn('status', ['active', 'completed']);
            })
            ->selectRaw('DATE(users.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $dailyData->pluck('count', 'date')->toArray();
    }

    /**
     * Update user's real-time leaderboard position
     * Only count qualified referrals (those with investments)
     */
    public function updateUserPosition(User $user): void
    {
        $activeLeaderboards = $this->getActiveLeaderboards();
        
        foreach ($activeLeaderboards as $leaderboard) {
            $currentCount = $this->getUserReferralCount($user, $leaderboard);
            
            // Update or create position
            $position = $leaderboard->positions()
                ->where('user_id', $user->id)
                ->first();

            if ($position) {
                $position->update(['referral_count' => $currentCount]);
            } else {
                // Create new position if user has qualified referrals
                if ($currentCount > 0) {
                    LeaderboardPosition::create([
                        'leaderboard_id' => $leaderboard->id,
                        'user_id' => $user->id,
                        'position' => 999, // Temporary position, will be recalculated
                        'referral_count' => $currentCount,
                        'prize_amount' => 0, // Will be calculated when positions are finalized
                    ]);
                }
            }
        }
    }

    /**
     * Recalculate all positions for active leaderboards (for real-time updates)
     */
    public function recalculateActivePositions(): void
    {
        $activeLeaderboards = $this->getActiveLeaderboards();
        
        foreach ($activeLeaderboards as $leaderboard) {
            try {
                $this->calculatePositions($leaderboard);
                
                Log::info('Recalculated positions for active leaderboard', [
                    'leaderboard_id' => $leaderboard->id,
                    'type' => $leaderboard->type
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to recalculate positions for leaderboard', [
                    'leaderboard_id' => $leaderboard->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Get leaderboard type statistics
     */
    public function getTypeStatistics(): array
    {
        return [
            'competitive' => [
                'total' => Leaderboard::competitive()->count(),
                'active' => Leaderboard::competitive()->active()->count(),
                'completed' => Leaderboard::competitive()->completed()->count(),
                'total_prizes' => LeaderboardPosition::whereHas('leaderboard', function($q) {
                    $q->where('type', 'competitive');
                })->sum('prize_amount'),
            ],
            'target' => [
                'total' => Leaderboard::target()->count(),
                'active' => Leaderboard::target()->active()->count(),
                'completed' => Leaderboard::target()->completed()->count(),
                'total_prizes' => LeaderboardPosition::whereHas('leaderboard', function($q) {
                    $q->where('type', 'target');
                })->sum('prize_amount'),
            ]
        ];
    }

    /**
     * Check if user qualifies for any target-based leaderboard prizes
     * Only count qualified referrals (those with investments)
     */
    public function checkUserTargetQualifications(User $user): array
    {
        $activeTargetLeaderboards = Leaderboard::target()
            ->active()
            ->visible()
            ->current()
            ->get();

        $qualifications = [];

        foreach ($activeTargetLeaderboards as $leaderboard) {
            $currentCount = $this->getUserReferralCount($user, $leaderboard);
            $qualifies = $currentCount >= $leaderboard->target_referrals;

            $qualifications[] = [
                'leaderboard' => $leaderboard,
                'current_qualified_referrals' => $currentCount,
                'target_referrals' => $leaderboard->target_referrals,
                'qualifies' => $qualifies,
                'progress_percentage' => min(100, ($currentCount / $leaderboard->target_referrals) * 100),
                'remaining_qualified_referrals' => max(0, $leaderboard->target_referrals - $currentCount),
                'prize_amount' => $leaderboard->target_prize_amount,
            ];
        }

        return $qualifications;
    }

    /**
     * Get investment statistics for leaderboard context
     */
    public function getInvestmentStatistics(): array
    {
        return [
            'total_users_with_investments' => User::whereHas('investments', function($query) {
                $query->whereIn('status', ['active', 'completed']);
            })->count(),
            'total_qualified_referrals' => $this->getTotalQualifiedReferrals(),
            'average_investment_per_referral' => UserInvestment::whereIn('status', ['active', 'completed'])
                ->whereHas('user', function($query) {
                    $query->whereNotNull('sponsor_id');
                })
                ->avg('amount'),
            'total_investment_from_referrals' => UserInvestment::whereIn('status', ['active', 'completed'])
                ->whereHas('user', function($query) {
                    $query->whereNotNull('sponsor_id');
                })
                ->sum('amount'),
        ];
    }

    /**
     * Get user's investment-based referral statistics
     */
    public function getUserReferralStatistics(User $user): array
    {
        $totalReferrals = User::where('sponsor_id', $user->id)->count();
        $qualifiedReferrals = User::where('sponsor_id', $user->id)
            ->whereHas('investments', function($query) {
                $query->whereIn('status', ['active', 'completed']);
            })
            ->count();

        $totalInvestmentFromReferrals = UserInvestment::whereIn('status', ['active', 'completed'])
            ->whereHas('user', function($query) use ($user) {
                $query->where('sponsor_id', $user->id);
            })
            ->sum('amount');

        return [
            'total_referrals' => $totalReferrals,
            'qualified_referrals' => $qualifiedReferrals,
            'qualification_rate' => $totalReferrals > 0 ? round(($qualifiedReferrals / $totalReferrals) * 100, 2) : 0,
            'total_investment_from_referrals' => $totalInvestmentFromReferrals,
            'average_investment_per_qualified_referral' => $qualifiedReferrals > 0 
                ? round($totalInvestmentFromReferrals / $qualifiedReferrals, 2) 
                : 0,
        ];
    }
}