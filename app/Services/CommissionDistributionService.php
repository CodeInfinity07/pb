<?php

namespace App\Services;

use App\Models\User;
use App\Models\CommissionSetting;
use App\Models\CryptoWallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CommissionDistributionService
{
    /**
     * Distribute commission when a user makes an investment
     */
    public function distributeInvestmentCommissions(User $investor, float $investmentAmount, string $investmentDescription = ''): array
    {
        try {
            $distributedCommissions = [];
            
            DB::beginTransaction();

            // Get the sponsor chain (3 levels up)
            $sponsorChain = $this->getSponsorChain($investor, 3);
            
            if (empty($sponsorChain)) {
                Log::info('No sponsors found for commission distribution', [
                    'investor_id' => $investor->id,
                    'investment_amount' => $investmentAmount
                ]);
                DB::commit();
                return $distributedCommissions;
            }

            Log::info('Starting commission distribution', [
                'investor_id' => $investor->id,
                'investor_name' => $investor->full_name,
                'investment_amount' => $investmentAmount,
                'sponsor_chain_count' => count($sponsorChain),
                'sponsor_chain' => array_map(function($sponsor) {
                    return [
                        'id' => $sponsor['user']->id,
                        'name' => $sponsor['user']->full_name,
                        'level' => $sponsor['level']
                    ];
                }, $sponsorChain)
            ]);

            // Distribute to each level
            foreach ($sponsorChain as $levelIndex => $sponsorData) {
                $sponsor = $sponsorData['user'];
                $level = $levelIndex + 1; // Level 1, 2, 3

                // Skip if sponsor is not active
                if ($sponsor->status !== 'active') {
                    Log::info('Skipping inactive sponsor', [
                        'sponsor_id' => $sponsor->id,
                        'sponsor_status' => $sponsor->status,
                        'level' => $level
                    ]);
                    continue;
                }

                // Get sponsor's commission tier
                $commissionTier = $this->getCommissionSettingForUser($sponsor);
                
                if (!$commissionTier) {
                    Log::info('No commission tier found for sponsor', [
                        'sponsor_id' => $sponsor->id,
                        'sponsor_level' => $sponsor->profile->level ?? 0,
                        'level' => $level
                    ]);
                    continue;
                }

                // Calculate commission based on level
                $commissionPercentage = match($level) {
                    1 => $commissionTier->commission_level_1,
                    2 => $commissionTier->commission_level_2,
                    3 => $commissionTier->commission_level_3,
                    default => 0
                };

                if ($commissionPercentage <= 0) {
                    Log::info('Zero commission percentage for level', [
                        'sponsor_id' => $sponsor->id,
                        'level' => $level,
                        'commission_percentage' => $commissionPercentage
                    ]);
                    continue;
                }

                $commissionAmount = ($investmentAmount * $commissionPercentage) / 100;
                
                if ($commissionAmount <= 0) {
                    continue;
                }

                // Distribute commission to sponsor's wallet
                $success = $this->addCommissionToWallet(
                    $sponsor,
                    $commissionAmount,
                    $investor,
                    $level,
                    $investmentAmount,
                    $investmentDescription
                );

                if ($success) {
                    $distributedCommissions[] = [
                        'sponsor_id' => $sponsor->id,
                        'sponsor_name' => $sponsor->full_name,
                        'level' => $level,
                        'percentage' => $commissionPercentage,
                        'amount' => $commissionAmount,
                        'tier_name' => $commissionTier->name
                    ];

                    Log::info('Commission distributed successfully', [
                        'sponsor_id' => $sponsor->id,
                        'sponsor_name' => $sponsor->full_name,
                        'investor_id' => $investor->id,
                        'level' => $level,
                        'percentage' => $commissionPercentage,
                        'commission_amount' => $commissionAmount,
                        'investment_amount' => $investmentAmount
                    ]);
                } else {
                    Log::error('Failed to distribute commission', [
                        'sponsor_id' => $sponsor->id,
                        'level' => $level,
                        'commission_amount' => $commissionAmount
                    ]);
                }
            }

            DB::commit();

            Log::info('Commission distribution completed', [
                'investor_id' => $investor->id,
                'total_distributed' => count($distributedCommissions),
                'total_commission_amount' => array_sum(array_column($distributedCommissions, 'amount'))
            ]);

            return $distributedCommissions;

        } catch (Exception $e) {
            DB::rollback();
            
            Log::error('Commission distribution failed', [
                'investor_id' => $investor->id,
                'investment_amount' => $investmentAmount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
        }
    }

    /**
     * Get sponsor chain up to specified levels
     */
    private function getSponsorChain(User $user, int $levels = 3): array
    {
        $chain = [];
        $currentUser = $user;
        $level = 1;

        while ($level <= $levels && $currentUser->sponsor_id) {
            $sponsor = User::with('profile')->find($currentUser->sponsor_id);
            
            if (!$sponsor) {
                break;
            }

            $chain[] = [
                'user' => $sponsor,
                'level' => $level
            ];
 
            $currentUser = $sponsor;
            $level++;
        }

        return $chain;
    }

    /**
     * Get commission setting for user based on their profile level
     */
    private function getCommissionSettingForUser(User $user): ?CommissionSetting
    {
        $userLevel = $user->profile ? $user->profile->level : 0;

        return CommissionSetting::where('is_active', true)
            ->where('level', $userLevel)
            ->first();
    }

    /**
     * Add commission to user's crypto wallet
     */
    private function addCommissionToWallet(
        User $sponsor, 
        float $commissionAmount, 
        User $investor, 
        int $level, 
        float $investmentAmount,
        string $investmentDescription = ''
    ): bool {
        try {
            // Find sponsor's primary USDT wallet (prioritize TRC20)
            $wallet = CryptoWallet::where('user_id', $sponsor->id)
                ->whereIn('currency', ['USDT_TRC20', 'USDT_BEP20', 'USDT_ERC20'])
                ->where('is_active', true)
                ->orderByRaw("FIELD(currency, 'USDT_TRC20', 'USDT_BEP20', 'USDT_ERC20')")
                ->first();

            // Fallback to any active wallet if no USDT wallet found
            if (!$wallet) {
                $wallet = CryptoWallet::where('user_id', $sponsor->id)
                    ->where('is_active', true)
                    ->first();
            }

            // Create a USDT_TRC20 wallet if none exists
            if (!$wallet) {
                $wallet = CryptoWallet::create([
                    'user_id' => $sponsor->id,
                    'currency' => 'USDT_TRC20',
                    'name' => 'Tether (TRC20)',
                    'balance' => 0,
                    'usd_rate' => 1,
                    'is_active' => true
                ]);
            }

            // Capture balance before increment
            $oldBalance = $wallet->balance;

            // Add commission to wallet
            $wallet->increment('balance', $commissionAmount);
            $newBalance = $wallet->fresh()->balance;

            // Generate unique transaction ID
            $transactionId = 'COMM_L' . $level . '_' . time() . '_' . $sponsor->id . '_' . uniqid();

            // Create transaction record
            $transaction = Transaction::create([
                'user_id' => $sponsor->id,
                'transaction_id' => $transactionId,
                'type' => Transaction::TYPE_COMMISSION,
                'amount' => $commissionAmount,
                'currency' => $wallet->currency,
                'status' => Transaction::STATUS_COMPLETED,
                'payment_method' => 'commission_distribution',
                'description' => $this->generateCommissionDescription($investor, $level, $investmentAmount, $investmentDescription),
                'processed_at' => now(),
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'commission_level' => $level,
                    'investor_id' => $investor->id,
                    'investor_name' => $investor->full_name,
                    'investment_amount' => $investmentAmount,
                    'commission_source' => 'investment',
                    'currency' => $wallet->currency,
                    'distributed_at' => now()->toISOString()
                ]
            ]);

            Log::info('Commission added to wallet successfully', [
                'sponsor_id' => $sponsor->id,
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'commission_amount' => $commissionAmount,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'transaction_id' => $transaction->id,
                'level' => $level
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to add commission to wallet', [
                'sponsor_id' => $sponsor->id,
                'commission_amount' => $commissionAmount,
                'level' => $level,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Generate commission description
     */
    private function generateCommissionDescription(User $investor, int $level, float $investmentAmount, string $investmentDescription = ''): string
    {
        $levelText = match($level) {
            1 => 'Direct Referral',
            2 => '2nd Level Referral',
            3 => '3rd Level Referral',
            default => "Level {$level} Referral"
        };

        $baseDescription = "Commission from {$levelText} - {$investor->full_name} invested $" . number_format($investmentAmount, 2);
        
        if ($investmentDescription) {
            $baseDescription .= " ({$investmentDescription})";
        }

        return $baseDescription;
    }

    /**
     * Get commission statistics for a user
     */
    public function getCommissionStats(User $user): array
    {
        $commissionTransactions = Transaction::where('user_id', $user->id)
            ->where('type', Transaction::TYPE_COMMISSION)
            ->where('status', Transaction::STATUS_COMPLETED);

        return [
            'total_earned' => $commissionTransactions->sum('amount'),
            'total_transactions' => $commissionTransactions->count(),
            'level_1_earnings' => $commissionTransactions->where('metadata->commission_level', 1)->sum('amount'),
            'level_2_earnings' => $commissionTransactions->where('metadata->commission_level', 2)->sum('amount'),
            'level_3_earnings' => $commissionTransactions->where('metadata->commission_level', 3)->sum('amount'),
            'this_month_earnings' => $commissionTransactions->whereMonth('created_at', now()->month)->sum('amount'),
            'today_earnings' => $commissionTransactions->whereDate('created_at', now()->toDateString())->sum('amount'),
        ];
    }

    /**
     * Get recent commission transactions for a user
     */
    public function getRecentCommissions(User $user, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Transaction::where('user_id', $user->id)
            ->where('type', Transaction::TYPE_COMMISSION)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}