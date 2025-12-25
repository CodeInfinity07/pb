<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionSetting;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class AdminCommissionController extends Controller
{
    /**
     * Display commission settings dashboard.
     */
    public function index(): View
    {
        try {
            $user = \Auth::user();

            $commissionTiers = CommissionSetting::orderBy('level')
                ->get()
                ->map(function ($tier) {
                    $tier->users_count = User::whereHas('profile', function ($query) use ($tier) {
                        $query->where('level', $tier->level);
                    })->count();
                    return $tier;
                });

            // Get platform statistics
            $totalUsers = User::count();
            $totalActiveUsers = User::where('status', 'active')->count();
            $totalCommissionsPaid = Transaction::commissions()->completed()->sum('amount') ?? 0;

            // Add Level 0 users count
            $level0Count = User::whereHas('profile', function ($query) {
                $query->where('level', 0);
            })->count();

            // Calculate commission simulation for $1000 transaction
            $simulationAmount = 1000;
            $commissionSimulation = [];

            foreach ($commissionTiers as $tier) {
                $commissionSimulation[$tier->level] = [
                    'level_1' => ($simulationAmount * ($tier->commission_level_1 ?? 0)) / 100,
                    'level_2' => ($simulationAmount * ($tier->commission_level_2 ?? 0)) / 100,
                    'level_3' => ($simulationAmount * ($tier->commission_level_3 ?? 0)) / 100,
                ];
                $commissionSimulation[$tier->level]['total'] =
                    $commissionSimulation[$tier->level]['level_1'] +
                    $commissionSimulation[$tier->level]['level_2'] +
                    $commissionSimulation[$tier->level]['level_3'];
            }

            return view('admin.referrals.commission.index', compact(
                'commissionTiers',
                'totalUsers',
                'totalActiveUsers',
                'totalCommissionsPaid',
                'simulationAmount',
                'commissionSimulation',
                'level0Count',
                'user'
            ));

        } catch (Exception $e) {
            Log::error('Commission settings page load failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('admin.referrals.commission.index', [
                'commissionTiers' => collect([]),
                'totalUsers' => 0,
                'totalActiveUsers' => 0,
                'totalCommissionsPaid' => 0,
                'simulationAmount' => 1000,
                'commissionSimulation' => [],
                'level0Count' => 0
            ])->with('error', 'Failed to load commission settings.');
        }
    }

    /**
     * Store a newly created tier.
     */
    public function store(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'level' => 'required|integer|min:1|unique:commission_settings,level',
            'name' => 'required|string|max:255',
            'min_investment' => 'required|numeric|min:0',
            'min_direct_referrals' => 'required|integer|min:0',
            'min_indirect_referrals' => 'required|integer|min:0',
            'commission_level_1' => 'required|numeric|min:0|max:100',
            'commission_level_2' => 'required|numeric|min:0|max:100',
            'commission_level_3' => 'required|numeric|min:0|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['sort_order'] = CommissionSetting::max('sort_order') + 1;
            $data['is_active'] = $request->boolean('is_active', true);

            $tier = CommissionSetting::create($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Commission tier created successfully.',
                    'data' => $tier
                ]);
            }

            return redirect()->route('admin.commission.index')
                ->with('success', 'Commission tier created successfully.');

        } catch (Exception $e) {
            Log::error('Commission tier creation failed', [
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create commission tier.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to create commission tier.')
                ->withInput();
        }
    }

    /**
     * Update the specified tier.
     */
    public function update(Request $request, CommissionSetting $commissionSetting)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'level' => ['required', 'integer', 'min:1', Rule::unique('commission_settings')->ignore($commissionSetting->id)],
            'name' => 'required|string|max:255',
            'min_investment' => 'required|numeric|min:0',
            'min_direct_referrals' => 'required|integer|min:0',
            'min_indirect_referrals' => 'required|integer|min:0',
            'commission_level_1' => 'required|numeric|min:0|max:100',
            'commission_level_2' => 'required|numeric|min:0|max:100',
            'commission_level_3' => 'required|numeric|min:0|max:100',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active', false);

            $commissionSetting->update($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Commission tier updated successfully.',
                    'data' => $commissionSetting->fresh()
                ]);
            }

            return redirect()->route('admin.commission.index')
                ->with('success', 'Commission tier updated successfully.');

        } catch (Exception $e) {
            Log::error('Commission tier update failed', [
                'tier_id' => $commissionSetting->id,
                'data' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update commission tier.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update commission tier.')
                ->withInput();
        }
    }

    /**
     * Remove the specified tier.
     */
    public function destroy(CommissionSetting $commissionSetting)
    {
        try {
            // Check if any users are currently assigned to this tier
            $usersCount = User::whereHas('profile', function ($query) use ($commissionSetting) {
                $query->where('level', $commissionSetting->level);
            })->count();

            if ($usersCount > 0) {
                $message = "Cannot delete tier. {$usersCount} users are currently assigned to this tier.";

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 400);
                }

                return redirect()->route('admin.commission.index')
                    ->with('error', $message);
            }

            $tierName = $commissionSetting->name;
            $commissionSetting->delete();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Commission tier '{$tierName}' deleted successfully."
                ]);
            }

            return redirect()->route('admin.commission.index')
                ->with('success', "Commission tier '{$tierName}' deleted successfully.");

        } catch (Exception $e) {
            Log::error('Commission tier deletion failed', [
                'tier_id' => $commissionSetting->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete commission tier.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete commission tier.');
        }
    }

    /**
     * Toggle tier status.
     */
    public function toggleStatus(CommissionSetting $commissionSetting): JsonResponse
    {
        try {
            $newStatus = !$commissionSetting->is_active;
            $commissionSetting->update(['is_active' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'Tier activated successfully.' : 'Tier deactivated successfully.',
                'is_active' => $newStatus
            ]);

        } catch (Exception $e) {
            Log::error('Commission tier status toggle failed', [
                'tier_id' => $commissionSetting->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tier status.'
            ], 500);
        }
    }

    /**
     * Calculate commission preview.
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'tier_id' => 'required|exists:commission_settings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $tier = CommissionSetting::findOrFail($request->tier_id);
            $amount = $request->amount;

            $commissions = [
                'level_1' => ($amount * ($tier->commission_level_1 ?? 0)) / 100,
                'level_2' => ($amount * ($tier->commission_level_2 ?? 0)) / 100,
                'level_3' => ($amount * ($tier->commission_level_3 ?? 0)) / 100
            ];

            $commissions['total'] = $commissions['level_1'] + $commissions['level_2'] + $commissions['level_3'];
            $commissions['remaining'] = $amount - $commissions['total'];

            return response()->json([
                'success' => true,
                'data' => [
                    'amount' => $amount,
                    'commissions' => $commissions,
                    'tier' => [
                        'id' => $tier->id,
                        'name' => $tier->name,
                        'level' => $tier->level
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Commission calculation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate commission preview.'
            ], 500);
        }
    }

    /**
     * Bulk update user tiers based on qualifications.
     */
    public function updateUserTiers(): JsonResponse
    {
        try {
            $updated = 0;
            $investmentsUpdated = 0;
            $upgrades = 0;
            $downgrades = 0;

            $tiers = CommissionSetting::where('is_active', true)
                ->orderBy('level')
                ->get();

            if ($tiers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active commission tiers found.'
                ]);
            }

            Log::info('Starting bulk user tier update via admin', [
                'active_tiers_count' => $tiers->count(),
                'tiers' => $tiers->pluck('name', 'level')->toArray()
            ]);

            DB::transaction(function () use ($tiers, &$updated, &$investmentsUpdated, &$upgrades, &$downgrades) {
                User::with(['profile'])->chunk(100, function ($users) use ($tiers, &$updated, &$investmentsUpdated, &$upgrades, &$downgrades) {
                    foreach ($users as $user) {
                        if (!$user->profile) {
                            continue;
                        }

                        $currentLevel = $user->profile->level ?? 0;
                        $newTier = null;
                        $newLevel = 0; // Default to level 0

                        // Find the highest tier the user qualifies for
                        foreach ($tiers->reverse() as $tier) {
                            if ($this->userQualifiesForTier($user, $tier)) {
                                $newTier = $tier;
                                $newLevel = $tier->level;
                                break;
                            }
                        }

                        // Update user if their level should change (including downgrades to level 0)
                        if ($currentLevel !== $newLevel) {
                            // Update user profile tier
                            $user->profile->update(['level' => $newLevel]);
                            $updated++;

                            // Track upgrades vs downgrades
                            if ($newLevel > $currentLevel) {
                                $upgrades++;
                            } elseif ($newLevel < $currentLevel) {
                                $downgrades++;
                            }

                            // Update active investments tier level
                            $activeInvestments = $user->activeInvestments();
                            $investmentUpdateCount = $activeInvestments->update(['tier_level' => $newLevel]);
                            $investmentsUpdated += $investmentUpdateCount;

                            // Get additional info for logging
                            $totalInvestment = $user->total_invested ?? 0;
                            $directReferrals = $user->directReferrals()->where('status', 'active')->count();
                            $indirectReferrals = $user->referrals()
                                ->whereHas('user', function ($query) {
                                    $query->where('status', 'active');
                                })
                                ->whereIn('level', [2, 3])
                                ->count();

                            Log::info('User tier updated via admin', [
                                'user_id' => $user->id,
                                'user_email' => $user->email,
                                'old_level' => $currentLevel,
                                'new_level' => $newLevel,
                                'tier_name' => $newTier ? $newTier->name : 'No Tier (Level 0)',
                                'change_type' => $newLevel > $currentLevel ? 'upgrade' : 'downgrade',
                                'total_invested' => $totalInvestment,
                                'direct_referrals' => $directReferrals,
                                'indirect_referrals' => $indirectReferrals,
                                'user_status' => $user->status,
                                'active_investments_updated' => $investmentUpdateCount
                            ]);

                            // Log individual investment updates if any
                            if ($investmentUpdateCount > 0) {
                                $investmentIds = $user->activeInvestments()->pluck('id')->toArray();
                                Log::info('Active investments tier updated via admin', [
                                    'user_id' => $user->id,
                                    'new_tier_level' => $newLevel,
                                    'investment_ids' => $investmentIds,
                                    'count' => $investmentUpdateCount
                                ]);
                            }
                        }
                    }
                });
            });

            // Get final tier distribution
            $tierDistribution = $this->getTierDistribution();

            Log::info('Bulk user tier update completed via admin', [
                'total_updated' => $updated,
                'upgrades' => $upgrades,
                'downgrades' => $downgrades,
                'investments_updated' => $investmentsUpdated,
                'final_distribution' => $tierDistribution
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updated} user tiers and {$investmentsUpdated} active investments.",
                'updated_count' => $updated,
                'upgrades_count' => $upgrades,
                'downgrades_count' => $downgrades,
                'investments_updated_count' => $investmentsUpdated,
                'tier_distribution' => $tierDistribution
            ]);

        } catch (Exception $e) {
            Log::error('Bulk user tier update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user tiers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export commission settings to CSV.
     */
    public function export()
    {
        try {
            $tiers = CommissionSetting::orderBy('level')->get();

            $filename = 'commission_settings_' . now()->format('Y_m_d_H_i_s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function () use ($tiers) {
                $file = fopen('php://output', 'w');

                // Add CSV headers
                fputcsv($file, [
                    'Level',
                    'Name',
                    'Min Investment',
                    'Min Direct Referrals',
                    'Min Indirect Referrals',
                    'Commission Level 1 (%)',
                    'Commission Level 2 (%)',
                    'Commission Level 3 (%)',
                    'Total Commission (%)',
                    'Status',
                    'Users Count',
                    'Description'
                ]);

                // Add Level 0
                $level0Count = User::whereHas('profile', function ($query) {
                    $query->where('level', 0);
                })->count();

                fputcsv($file, [
                    0,
                    'No Tier',
                    'N/A',
                    'N/A',
                    'N/A',
                    0,
                    0,
                    0,
                    0,
                    'Active',
                    $level0Count,
                    'Users with less than $50 invested or insufficient referrals'
                ]);

                // Add data rows
                foreach ($tiers as $tier) {
                    $usersCount = User::whereHas('profile', function ($query) use ($tier) {
                        $query->where('level', $tier->level);
                    })->count();

                    fputcsv($file, [
                        $tier->level,
                        $tier->name,
                        '$' . number_format($tier->min_investment, 2),
                        $tier->min_direct_referrals,
                        $tier->min_indirect_referrals,
                        $tier->commission_level_1,
                        $tier->commission_level_2,
                        $tier->commission_level_3,
                        ($tier->commission_level_1 + $tier->commission_level_2 + $tier->commission_level_3),
                        $tier->is_active ? 'Active' : 'Inactive',
                        $usersCount,
                        $tier->description
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            Log::error('Commission settings export failed', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to export commission settings.');
        }
    }

    /**
     * Seed default commission tiers.
     */
    public function seedDefault(): JsonResponse
    {
        try {
            $defaultTiers = [
                [
                    'level' => 1,
                    'name' => 'Bronze',
                    'min_investment' => 50,  // Updated to $50 minimum
                    'min_direct_referrals' => 3,
                    'min_indirect_referrals' => 0,
                    'commission_level_1' => 10,
                    'commission_level_2' => 5,
                    'commission_level_3' => 2,
                    'color' => '#CD7F32',
                    'is_active' => true,
                    'description' => 'Entry level tier - requires $50+ investment and 3 direct referrals'
                ],
                [
                    'level' => 2,
                    'name' => 'Silver',
                    'min_investment' => 50,  // Still requires $50 minimum
                    'min_direct_referrals' => 5,
                    'min_indirect_referrals' => 10,
                    'commission_level_1' => 12,
                    'commission_level_2' => 7,
                    'commission_level_3' => 3,
                    'color' => '#C0C0C0',
                    'is_active' => true,
                    'description' => 'Intermediate tier - requires $50+ investment, 5 direct and 10 indirect referrals'
                ],
                [
                    'level' => 3,
                    'name' => 'Gold',
                    'min_investment' => 50,  // Still requires $50 minimum
                    'min_direct_referrals' => 10,
                    'min_indirect_referrals' => 25,
                    'commission_level_1' => 15,
                    'commission_level_2' => 10,
                    'commission_level_3' => 5,
                    'color' => '#FFD700',
                    'is_active' => true,
                    'description' => 'Advanced tier - requires $50+ investment, 10 direct and 25 indirect referrals'
                ],
                [
                    'level' => 4,
                    'name' => 'Platinum',
                    'min_investment' => 50,  // Still requires $50 minimum
                    'min_direct_referrals' => 20,
                    'min_indirect_referrals' => 50,
                    'commission_level_1' => 20,
                    'commission_level_2' => 15,
                    'commission_level_3' => 10,
                    'color' => '#E5E4E2',
                    'is_active' => true,
                    'description' => 'Premium tier - requires $50+ investment, 20 direct and 50 indirect referrals'
                ]
            ];

            $created = 0;
            $updated = 0;

            foreach ($defaultTiers as $index => $tierData) {
                $tierData['sort_order'] = $index + 1;

                $existingTier = CommissionSetting::where('level', $tierData['level'])->first();

                if ($existingTier) {
                    $existingTier->update($tierData);
                    $updated++;
                } else {
                    CommissionSetting::create($tierData);
                    $created++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully seeded default tiers. Created: {$created}, Updated: {$updated}.",
                'created' => $created,
                'updated' => $updated
            ]);

        } catch (Exception $e) {
            Log::error('Seed default tiers failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to seed default tiers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get commission statistics.
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_tiers' => CommissionSetting::count(),
                'active_tiers' => CommissionSetting::where('is_active', true)->count(),
                'total_users' => User::count(),
                'total_commissions_paid' => Transaction::commissions()->completed()->sum('amount') ?? 0,
                'users_by_tier' => []
            ];

            // Add Level 0 to statistics
            $level0Count = User::whereHas('profile', function ($query) {
                $query->where('level', 0);
            })->count();

            $stats['users_by_tier'][] = [
                'tier_id' => 0,
                'level' => 0,
                'name' => 'No Tier',
                'count' => $level0Count,
                'color' => '#6c757d',
                'is_active' => true
            ];

            $tiers = CommissionSetting::orderBy('level')->get();
            foreach ($tiers as $tier) {
                $userCount = User::whereHas('profile', function ($query) use ($tier) {
                    $query->where('level', $tier->level);
                })->count();

                $stats['users_by_tier'][] = [
                    'tier_id' => $tier->id,
                    'level' => $tier->level,
                    'name' => $tier->name,
                    'count' => $userCount,
                    'color' => $tier->color ?? '#6c757d',
                    'is_active' => $tier->is_active
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Commission statistics fetch failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch commission statistics.'
            ], 500);
        }
    }

    /**
     * Get detailed user tier qualification info.
     */
    public function getUserTierInfo(User $user): JsonResponse
    {
        try {
            $profile = $user->profile;
            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'User profile not found.'
                ], 404);
            }

            $currentLevel = $profile->level ?? 0;
            $totalInvestment = $user->total_invested ?? 0;
            $directReferrals = $user->directReferrals()->where('status', 'active')->count();
            $indirectReferrals = $user->referrals()
                ->whereHas('user', function ($query) {
                    $query->where('status', 'active');
                })
                ->whereIn('level', [2, 3])
                ->count();

            // Check what tiers they qualify for
            $tiers = CommissionSetting::where('is_active', true)->orderBy('level')->get();
            $qualifications = [];
            $highestQualifyingTier = null;

            foreach ($tiers as $tier) {
                $qualifies = $this->userQualifiesForTier($user, $tier);

                $reasons = [];
                if ($user->status !== 'active') {
                    $reasons[] = 'User not active';
                }
                if ($totalInvestment < 50) {
                    $reasons[] = 'Investment < $50 (' . number_format($totalInvestment, 2) . ')';
                }
                if ($directReferrals < $tier->min_direct_referrals) {
                    $reasons[] = "Direct referrals < {$tier->min_direct_referrals} ({$directReferrals})";
                }
                if ($indirectReferrals < $tier->min_indirect_referrals) {
                    $reasons[] = "Indirect referrals < {$tier->min_indirect_referrals} ({$indirectReferrals})";
                }

                $qualifications[] = [
                    'tier_id' => $tier->id,
                    'level' => $tier->level,
                    'name' => $tier->name,
                    'qualifies' => $qualifies,
                    'requirements_met' => empty($reasons),
                    'missing_requirements' => $reasons
                ];
            }

            // Find the highest qualifying tier by checking in reverse
            foreach ($tiers->reverse() as $tier) {
                if ($this->userQualifiesForTier($user, $tier)) {
                    $highestQualifyingTier = $tier;
                    break;
                }
            }

            $recommendedLevel = $highestQualifyingTier ? $highestQualifyingTier->level : 0;
            $needsUpdate = $currentLevel !== $recommendedLevel;

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => $user->status,
                    'current_level' => $currentLevel,
                    'recommended_level' => $recommendedLevel,
                    'needs_update' => $needsUpdate,
                    'stats' => [
                        'total_invested' => $totalInvestment,
                        'direct_referrals' => $directReferrals,
                        'indirect_referrals' => $indirectReferrals
                    ],
                    'qualifications' => $qualifications,
                    'highest_qualifying_tier' => $highestQualifyingTier ? [
                        'id' => $highestQualifyingTier->id,
                        'level' => $highestQualifyingTier->level,
                        'name' => $highestQualifyingTier->name
                    ] : null
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get user tier info', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get user tier information.'
            ], 500);
        }
    }

    /**
     * Update a specific user's tier.
     */
    public function updateSpecificUserTier(Request $request, User $user): JsonResponse
    {
        try {
            if (!$user->profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'User profile not found.'
                ], 404);
            }

            $tiers = CommissionSetting::where('is_active', true)->orderBy('level')->get();

            $currentLevel = $user->profile->level ?? 0;
            $newTier = null;
            $newLevel = 0; // Default to level 0

            // Find the highest tier the user qualifies for
            foreach ($tiers->reverse() as $tier) {
                if ($this->userQualifiesForTier($user, $tier)) {
                    $newTier = $tier;
                    $newLevel = $tier->level;
                    break;
                }
            }

            if ($currentLevel !== $newLevel) {
                DB::transaction(function () use ($user, $newLevel) {
                    $user->profile->update(['level' => $newLevel]);
                    $user->activeInvestments()->update(['tier_level' => $newLevel]);
                });

                $tierName = $newTier ? $newTier->name : 'No Tier (Level 0)';
                $changeType = $newLevel > $currentLevel ? 'upgraded' : 'downgraded';

                Log::info('Individual user tier updated via admin', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'old_level' => $currentLevel,
                    'new_level' => $newLevel,
                    'tier_name' => $tierName,
                    'change_type' => $changeType,
                    'admin_triggered' => true
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "User {$changeType} from level {$currentLevel} to level {$newLevel} ({$tierName})",
                    'data' => [
                        'user_id' => $user->id,
                        'old_level' => $currentLevel,
                        'new_level' => $newLevel,
                        'tier_name' => $tierName,
                        'change_type' => $changeType
                    ]
                ]);
            } else {
                $tierName = $newTier ? $newTier->name : 'No Tier (Level 0)';

                return response()->json([
                    'success' => true,
                    'message' => "User is already at the correct tier level: {$newLevel} ({$tierName})",
                    'data' => [
                        'user_id' => $user->id,
                        'current_level' => $currentLevel,
                        'tier_name' => $tierName,
                        'change_needed' => false
                    ]
                ]);
            }

        } catch (Exception $e) {
            Log::error('Failed to update specific user tier', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user tier.'
            ], 500);
        }
    }

    /**
     * Get current tier distribution including level 0.
     */
    private function getTierDistribution(): array
    {
        $distribution = [];

        // Add Level 0 count
        $level0Count = User::whereHas('profile', function ($query) {
            $query->where('level', 0);
        })->count();

        $distribution[] = [
            'level' => 0,
            'name' => 'No Tier',
            'count' => $level0Count,
            'color' => '#6c757d',
            'is_active' => true
        ];

        // Add commission tiers
        $tiers = CommissionSetting::orderBy('level')->get();

        foreach ($tiers as $tier) {
            $count = User::whereHas('profile', function ($query) use ($tier) {
                $query->where('level', $tier->level);
            })->count();

            $distribution[] = [
                'level' => $tier->level,
                'name' => $tier->name,
                'count' => $count,
                'color' => $tier->color ?? '#6c757d',
                'is_active' => $tier->is_active
            ];
        }

        return $distribution;
    }

    /**
     * Check if user qualifies for a specific tier.
     * Requirements: Active status + $50+ invested + referral requirements
     */
    private function userQualifiesForTier(User $user, CommissionSetting $tier): bool
    {
        // First check if the user themselves is active
        if ($user->status !== 'active') {
            return false;
        }

        // Check minimum investment requirement for ANY tier (must have at least $50 invested)
        $totalInvestment = $user->total_invested ?? 0;
        if ($totalInvestment < 50) {
            return false; // User doesn't qualify for ANY tier if less than $50 invested
        }

        // Check direct referrals requirement
        $directReferrals = $user->directReferrals()->where('status', 'active')->count();
        if ($directReferrals < $tier->min_direct_referrals) {
            return false;
        }

        // Check indirect referrals requirement (2nd and 3rd level only)
        $indirectReferrals = $user->referrals()
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->whereIn('level', [2, 3])  // Only 2nd and 3rd level
            ->count();

        if ($indirectReferrals < $tier->min_indirect_referrals) {
            return false;
        }

        return true;
    }
}