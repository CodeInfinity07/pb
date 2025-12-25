<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentPlan;
use App\Models\InvestmentPlanTier;
use App\Models\InvestmentPlanProfitSharing;
use App\Models\UserInvestment;
use App\Models\InvestmentReturn;
use App\Models\ProfitSharingTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class AdminInvestmentController extends Controller
{
    /**
     * Display investment plans dashboard
     */
    public function index(Request $request)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'plan_type' => $request->get('plan_type'),
                'sort_by' => $request->get('sort_by', 'sort_order'),
                'sort_order' => $request->get('sort_order', 'asc')
            ];

            $investmentPlans = $this->getFilteredPlans($filters);
            $statistics = $this->getDashboardStatistics();

            return view('admin.investment.index', compact(
                'investmentPlans',
                'statistics'
            ) + $filters + ['user' => auth()->user()]);

        } catch (\Exception $e) {
            Log::error('Investment dashboard error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Unable to load investment plans dashboard.');
        }
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.investment.create', [
            'user' => auth()->user()
        ]);
    }

    /**
     * Store new investment plan
     */
    public function store(Request $request): JsonResponse
    {
        $planType = $request->boolean('is_tiered') ? 'tiered' : 'simple';

        $validator = $this->validatePlanData($request, $planType);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $plan = $this->createInvestmentPlan($request, $planType);

            if ($planType === 'tiered') {
                $tiers = $this->createPlanTiers($plan, $request->get('tiers', []));

                // Handle profit sharing if enabled
                if ($request->boolean('profit_sharing_enabled') && $request->has('profit_sharing_configs')) {
                    $this->createProfitSharingConfigs($plan, $tiers, $request->get('profit_sharing_configs', []));
                }
            }

            DB::commit();

            $this->logPlanActivity('created', $plan, [
                'plan_type' => $planType,
                'profit_sharing_enabled' => $request->boolean('profit_sharing_enabled'),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Investment plan created successfully!',
                'plan' => $plan->load(['tiers', 'profitSharingConfigs'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Plan creation failed: ' . $e->getMessage(), [
                'admin_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create investment plan. Please try again.'
            ], 500);
        }
    }

    /**
     * Show specific plan details
     */
    public function show(InvestmentPlan $investmentPlan)
    {
        try {
            $investmentPlan->load([
                'tiers' => fn($q) => $q->orderBy('tier_level'),
                'profitSharingConfigs.tier',
                'userInvestments' => fn($q) => $q->with('user')->latest()->limit(10)
            ])->loadCount([
                        'userInvestments',
                        'activeInvestments',
                        'completedInvestments'
                    ])->loadSum('userInvestments', 'amount');

            $analytics = $this->getPlanAnalytics($investmentPlan);

            return view('admin.investment.show', [
                'investmentPlan' => $investmentPlan,
                'analytics' => $analytics,
                'user' => auth()->user()
            ]);

        } catch (\Exception $e) {
            Log::error('Plan view error: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id()
            ]);

            return redirect()->route('admin.investment.index')
                ->with('error', 'Unable to load plan details.');
        }
    }

    /**
     * Show edit form
     */
    public function edit(InvestmentPlan $investmentPlan)
    {
        $investmentPlan->load([
            'tiers' => fn($q) => $q->orderBy('tier_level'),
            'profitSharingConfigs.tier'
        ]);

        return view('admin.investment.edit', [
            'investmentPlan' => $investmentPlan,
            'user' => auth()->user()
        ]);
    }

    /**
     * Update investment plan
     */
    public function update(Request $request, InvestmentPlan $investmentPlan): JsonResponse
    {
        $planType = $request->boolean('is_tiered') ? 'tiered' : 'simple';

        $validator = $this->validatePlanData($request, $planType, $investmentPlan->id);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldPlanType = $investmentPlan->is_tiered ? 'tiered' : 'simple';
            $oldProfitSharingStatus = $investmentPlan->profit_sharing_enabled;

            $this->updateInvestmentPlan($investmentPlan, $request, $planType);

            // Handle plan type conversion
            if ($oldPlanType !== $planType) {
                $this->handlePlanTypeChange($investmentPlan, $planType, $request);
            } elseif ($planType === 'tiered') {
                $tiers = $this->updatePlanTiers($investmentPlan, $request->get('tiers', []));

                // Handle profit sharing updates
                $this->handleProfitSharingUpdate($investmentPlan, $request, $tiers);
            }

            // If converting to simple plan, disable profit sharing
            if ($planType === 'simple') {
                $investmentPlan->update(['profit_sharing_enabled' => false]);
                $investmentPlan->profitSharingConfigs()->delete();
            }

            DB::commit();

            $this->logPlanActivity('updated', $investmentPlan, [
                'old_type' => $oldPlanType,
                'new_type' => $planType,
                'profit_sharing_changed' => $oldProfitSharingStatus !== $request->boolean('profit_sharing_enabled'),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Investment plan updated successfully!',
                'plan' => $investmentPlan->fresh()->load(['tiers', 'profitSharingConfigs'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Plan update failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update investment plan. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete investment plan
     */
    public function destroy(InvestmentPlan $investmentPlan): JsonResponse
    {
        try {
            if ($investmentPlan->activeInvestments()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete plan with active investments.'
                ], 400);
            }

            DB::beginTransaction();

            $planName = $investmentPlan->name;

            // Delete related data
            $investmentPlan->profitSharingConfigs()->delete();
            $investmentPlan->tiers()->delete();
            $investmentPlan->delete();

            DB::commit();

            $this->logPlanActivity('deleted', null, [
                'plan_name' => $planName,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Investment plan deleted successfully!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Plan deletion failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete investment plan.'
            ], 500);
        }
    }

    /**
     * Toggle plan status
     */
    public function toggleStatus(InvestmentPlan $investmentPlan): JsonResponse
    {
        try {
            $newStatus = match ($investmentPlan->status) {
                'active' => 'inactive',
                'inactive' => 'active',
                'paused' => 'active',
                default => 'inactive'
            };

            $investmentPlan->update(['status' => $newStatus]);

            $this->logPlanActivity('status_changed', $investmentPlan, [
                'old_status' => $investmentPlan->getOriginal('status'),
                'new_status' => $newStatus,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Plan status changed to {$newStatus}",
                'status' => $newStatus,
                'badge_class' => $investmentPlan->fresh()->status_badge_class
            ]);

        } catch (\Exception $e) {
            Log::error('Status toggle failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan status.'
            ], 500);
        }
    }

    /**
     * Toggle profit sharing for a plan
     */
    public function toggleProfitSharing(InvestmentPlan $investmentPlan): JsonResponse
    {
        try {
            if (!$investmentPlan->is_tiered) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profit sharing is only available for tiered plans.'
                ], 400);
            }

            $newStatus = !$investmentPlan->profit_sharing_enabled;

            if ($newStatus) {
                // Enabling profit sharing
                $result = $investmentPlan->enableProfitSharing();
            } else {
                // Disabling profit sharing
                $result = $investmentPlan->disableProfitSharing();
            }

            if ($result) {
                $this->logPlanActivity('profit_sharing_toggled', $investmentPlan, [
                    'enabled' => $newStatus,
                    'admin_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Profit sharing ' . ($newStatus ? 'enabled' : 'disabled') . ' successfully!',
                    'profit_sharing_enabled' => $newStatus,
                    'stats' => $investmentPlan->getProfitSharingStats()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profit sharing status.'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Profit sharing toggle failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profit sharing status.'
            ], 500);
        }
    }

    /**
     * Update profit sharing configuration for a plan
     */
    public function updateProfitSharing(Request $request, InvestmentPlan $investmentPlan): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profit_sharing_configs' => 'required|array',
            'profit_sharing_configs.*.tier_id' => 'required|exists:investment_plan_tiers,id',
            'profit_sharing_configs.*.level_1_commission' => 'required|numeric|min:0|max:25',
            'profit_sharing_configs.*.level_2_commission' => 'required|numeric|min:0|max:25',
            'profit_sharing_configs.*.level_3_commission' => 'required|numeric|min:0|max:25',
            'profit_sharing_configs.*.max_commission_cap' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if (!$investmentPlan->is_tiered || !$investmentPlan->profit_sharing_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan must be tiered and have profit sharing enabled.'
                ], 400);
            }

            DB::beginTransaction();

            $configs = $request->get('profit_sharing_configs');
            $updatedConfigs = [];

            foreach ($configs as $configData) {
                // Validate total commission doesn't exceed 50%
                $totalRate = $configData['level_1_commission'] +
                    $configData['level_2_commission'] +
                    $configData['level_3_commission'];

                if ($totalRate > 50) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Total commission rate cannot exceed 50% for any tier.'
                    ], 400);
                }

                $profitSharing = InvestmentPlanProfitSharing::updateOrCreate(
                    [
                        'investment_plan_id' => $investmentPlan->id,
                        'investment_plan_tier_id' => $configData['tier_id'],
                    ],
                    [
                        'level_1_commission' => $configData['level_1_commission'],
                        'level_2_commission' => $configData['level_2_commission'],
                        'level_3_commission' => $configData['level_3_commission'],
                        'max_commission_cap' => $configData['max_commission_cap'] ?? null,
                        'is_active' => true,
                    ]
                );

                $updatedConfigs[] = $profitSharing;
            }

            DB::commit();

            $this->logPlanActivity('profit_sharing_updated', $investmentPlan, [
                'configurations_updated' => count($updatedConfigs),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profit sharing configuration updated successfully!',
                'configurations' => collect($updatedConfigs)->map(function ($config) {
                    return [
                        'tier_id' => $config->investment_plan_tier_id,
                        'tier_name' => $config->tier->tier_name ?? 'Unknown',
                        'level_1_commission' => $config->formatted_level_1_commission,
                        'level_2_commission' => $config->formatted_level_2_commission,
                        'level_3_commission' => $config->formatted_level_3_commission,
                        'total_rate' => $config->total_commission_rate . '%',
                    ];
                })
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Profit sharing update failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profit sharing configuration.'
            ], 500);
        }
    }

    /**
     * Get profit sharing preview for investment amount
     */
    public function getProfitSharingPreview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:investment_plans,id',
            'tier_id' => 'required|exists:investment_plan_tiers,id',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = InvestmentPlan::findOrFail($request->plan_id);
            $tier = InvestmentPlanTier::findOrFail($request->tier_id);
            $amount = $request->amount;

            if (!$plan->hasProfitSharing()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profit sharing is not enabled for this plan.'
                ], 400);
            }

            $preview = $tier->getProfitSharingPreview($amount);

            return response()->json([
                'success' => true,
                'preview' => $preview,
                'investment_amount' => $amount,
                'formatted_investment' => '$' . number_format($amount, 2),
                'tier_info' => [
                    'name' => $tier->tier_name,
                    'level' => $tier->tier_level,
                    'interest_rate' => $tier->formatted_interest_rate,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Profit sharing preview failed: ' . $e->getMessage(), [
                'plan_id' => $request->plan_id,
                'tier_id' => $request->tier_id,
                'amount' => $request->amount
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate profit sharing preview.'
            ], 500);
        }
    }

    /**
     * Get profit sharing transactions for a plan
     */
    public function getProfitSharingTransactions(Request $request, InvestmentPlan $investmentPlan)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'level' => $request->get('level'),
                'search' => $request->get('search'),
            ];

            $query = ProfitSharingTransaction::whereHas('userInvestment', function ($q) use ($investmentPlan) {
                $q->where('investment_plan_id', $investmentPlan->id);
            })->with(['beneficiaryUser', 'sourceUser', 'userInvestment.investmentPlan']);

            if ($filters['status']) {
                $query->where('status', $filters['status']);
            }

            if ($filters['level']) {
                $query->where('commission_level', $filters['level']);
            }

            if ($filters['search']) {
                $query->where(function ($q) use ($filters) {
                    $q->whereHas('beneficiaryUser', function ($subQ) use ($filters) {
                        $subQ->where('first_name', 'like', "%{$filters['search']}%")
                            ->orWhere('last_name', 'like', "%{$filters['search']}%")
                            ->orWhere('email', 'like', "%{$filters['search']}%");
                    })
                        ->orWhereHas('sourceUser', function ($subQ) use ($filters) {
                            $subQ->where('first_name', 'like', "%{$filters['search']}%")
                                ->orWhere('last_name', 'like', "%{$filters['search']}%")
                                ->orWhere('email', 'like', "%{$filters['search']}%");
                        });
                });
            }

            $transactions = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'transactions' => $transactions
                ]);
            }

            return view('admin.investment.profit-sharing-transactions', compact(
                'investmentPlan',
                'transactions'
            ) + $filters + ['user' => auth()->user()]);

        } catch (\Exception $e) {
            Log::error('Profit sharing transactions fetch failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id,
                'admin_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch profit sharing transactions.'
                ], 500);
            }

            return redirect()->back()->with('error', 'Unable to load profit sharing transactions.');
        }
    }

    /**
     * Process profit sharing transactions manually
     */
    public function processProfitSharingTransactions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:profit_sharing_transactions,id',
            'action' => 'required|in:pay,cancel,retry',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactionIds = $request->get('transaction_ids');
            $action = $request->get('action');
            $processed = 0;
            $failed = 0;

            DB::beginTransaction();

            foreach ($transactionIds as $transactionId) {
                $transaction = ProfitSharingTransaction::find($transactionId);

                if (!$transaction) {
                    continue;
                }

                try {
                    switch ($action) {
                        case 'pay':
                            if ($transaction->isPending()) {
                                $transaction->markAsPaid('Manual processing by admin');
                                $processed++;
                            }
                            break;

                        case 'cancel':
                            if ($transaction->isPending()) {
                                $transaction->cancel('Cancelled by admin');
                                $processed++;
                            }
                            break;

                        case 'retry':
                            if ($transaction->isFailed()) {
                                $transaction->update(['status' => 'pending']);
                                $processed++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error('Individual transaction processing failed: ' . $e->getMessage(), [
                        'transaction_id' => $transactionId,
                        'action' => $action
                    ]);
                }
            }

            DB::commit();

            $this->logPlanActivity('profit_sharing_transactions_processed', null, [
                'action' => $action,
                'total_requested' => count($transactionIds),
                'processed' => $processed,
                'failed' => $failed,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} transactions successfully" . ($failed > 0 ? ", {$failed} failed" : ""),
                'processed' => $processed,
                'failed' => $failed
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Bulk profit sharing transaction processing failed: ' . $e->getMessage(), [
                'transaction_ids' => $request->get('transaction_ids'),
                'action' => $request->get('action'),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process profit sharing transactions.'
            ], 500);
        }
    }

    /**
     * Update plan display order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:investment_plans,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $planIds = $request->get('plan_ids');

            foreach ($planIds as $index => $planId) {
                InvestmentPlan::where('id', $planId)->update(['sort_order' => $index]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Plan order updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Order update failed: ' . $e->getMessage(), [
                'plan_ids' => $request->get('plan_ids'),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update plan order.'
            ], 500);
        }
    }

    /**
     * Get tier details for a plan
     */
    public function getTierDetails(InvestmentPlan $investmentPlan): JsonResponse
    {
        try {
            if (!$investmentPlan->is_tiered) {
                return response()->json([
                    'success' => false,
                    'message' => 'This plan does not use tiers.'
                ], 400);
            }

            $tiers = $investmentPlan->tiers()
                ->withCount('userInvestments')
                ->withSum('userInvestments', 'amount')
                ->orderBy('tier_level')
                ->get();

            return response()->json([
                'success' => true,
                'tiers' => $tiers->map(fn($tier) => $this->formatTierData($tier))
            ]);

        } catch (\Exception $e) {
            Log::error('Tier details fetch failed: ' . $e->getMessage(), [
                'plan_id' => $investmentPlan->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tier details.'
            ], 500);
        }
    }

    /**
     * Get user investment options
     */
    public function getUserInvestmentOptions(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'amount' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::findOrFail($request->user_id);
            $amount = $request->get('amount', 0);

            $options = $this->buildInvestmentOptions($user, $amount);

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'level' => $user->user_level,
                    'level_name' => $user->level_name
                ],
                'investment_options' => $options
            ]);

        } catch (\Exception $e) {
            Log::error('Investment options fetch failed: ' . $e->getMessage(), [
                'user_id' => $request->user_id,
                'amount' => $request->amount
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch investment options.'
            ], 500);
        }
    }

    /**
     * Simulate investment returns
     */
    public function simulateInvestment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:investment_plans,id',
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'tier_id' => 'nullable|exists:investment_plan_tiers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = InvestmentPlan::with('tiers')->findOrFail($request->plan_id);
            $user = User::findOrFail($request->user_id);
            $amount = $request->amount;

            $simulation = $this->calculateInvestmentSimulation($plan, $user, $amount, $request->tier_id);

            if (!$simulation['can_invest']) {
                return response()->json([
                    'success' => false,
                    'message' => $simulation['reason']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'simulation' => $simulation
            ]);

        } catch (\Exception $e) {
            Log::error('Investment simulation failed: ' . $e->getMessage(), [
                'plan_id' => $request->plan_id,
                'user_id' => $request->user_id,
                'amount' => $request->amount
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to simulate investment.'
            ], 500);
        }
    }

    /**
     * Update all user levels based on activity
     */
    public function updateUserLevels(): JsonResponse
    {
        try {
            $result = User::updateAllUserLevels();

            Log::info('User levels bulk update completed', [
                'admin_id' => auth()->id(),
                'updated_count' => $result['users_updated'],
                'total_count' => $result['total_users']
            ]);

            return response()->json([
                'success' => true,
                'message' => "Updated {$result['users_updated']} user levels out of {$result['total_users']} users.",
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('User levels update failed: ' . $e->getMessage(), [
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user levels.'
            ], 500);
        }
    }

    /**
     * Get user level statistics
     */
    public function getUserLevelStats(): JsonResponse
    {
        try {
            $stats = User::getLevelStatistics();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('User level stats fetch failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user level statistics.'
            ], 500);
        }
    }

    /**
     * Export investment plans
     */
    public function export(Request $request)
    {
        try {
            $format = $request->get('format', 'csv');
            $filters = [
                'status' => $request->get('status'),
                'plan_type' => $request->get('plan_type')
            ];

            if ($format !== 'csv') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only CSV export is currently supported.'
                ], 400);
            }

            return $this->exportPlansAsCsv($filters);

        } catch (\Exception $e) {
            Log::error('Plans export failed: ' . $e->getMessage(), [
                'admin_id' => auth()->id(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to export investment plans.'
            ], 500);
        }
    }

    /**
     * User investments management
     */
    public function userInvestments(Request $request)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'plan_id' => $request->get('plan_id'),
                'user_level' => $request->get('user_level'),
                'date_range' => $request->get('date_range')
            ];

            $investments = $this->getFilteredInvestments($filters);
            $investmentPlans = InvestmentPlan::active()->orderBy('name')->get();

            return view('admin.investment.user-investments', compact(
                'investments',
                'investmentPlans'
            ) + $filters + ['user' => auth()->user()]);

        } catch (\Exception $e) {
            Log::error('User investments view error: ' . $e->getMessage(), [
                'admin_id' => auth()->id()
            ]);

            return redirect()->back()->with('error', 'Unable to load user investments.');
        }
    }

    /**
     * Investment returns management
     */
    public function investmentReturns(Request $request)
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'search' => $request->get('search'),
                'type' => $request->get('type'),
                'date_range' => $request->get('date_range')
            ];

            $returns = $this->getFilteredReturns($filters);
            $returnStats = InvestmentReturn::getStatistics();

            return view('admin.investment.returns', compact(
                'returns',
                'returnStats'
            ) + $filters + ['user' => auth()->user()]);

        } catch (\Exception $e) {
            Log::error('Investment returns view error: ' . $e->getMessage(), [
                'admin_id' => auth()->id()
            ]);

            return redirect()->back()->with('error', 'Unable to load investment returns.');
        }
    }

    // =============================================
    // PRIVATE HELPER METHODS
    // =============================================

    /**
     * Validate plan data based on type
     */
    private function validatePlanData(Request $request, string $planType, ?int $planId = null): \Illuminate\Contracts\Validation\Validator
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('investment_plans')->ignore($planId)
            ],
            'description' => 'nullable|string|max:1000',
            'interest_type' => 'required|in:daily,weekly,monthly,yearly',
            'duration_days' => 'required|integer|min:1|max:3650',
            'return_type' => 'required|in:fixed,compound',
            'capital_return' => 'boolean',
            'status' => 'required|in:active,inactive,paused',
            'badge' => 'nullable|string|max:50',
            'color_scheme' => 'required|in:primary,success,warning,danger,info',
            'features' => 'nullable|array|max:10',
            'features.*' => 'string|max:255',
            'sort_order' => 'integer|min:0',
            'is_tiered' => 'boolean',
            'profit_sharing_enabled' => 'boolean'
        ];

        if ($planType === 'tiered') {
            $rules['tiers'] = 'required|array|min:1|max:10';
            $rules['tiers.*.tier_level'] = 'required|integer|min:0|max:9';
            $rules['tiers.*.tier_name'] = 'required|string|max:255';
            $rules['tiers.*.minimum_amount'] = 'required|numeric|min:0';
            $rules['tiers.*.maximum_amount'] = 'required|numeric|gt:tiers.*.minimum_amount';
            $rules['tiers.*.interest_rate'] = 'required|numeric|min:0|max:100';
            $rules['tiers.*.min_user_level'] = 'required|integer|min:0|max:9';
            $rules['tiers.*.tier_description'] = 'nullable|string|max:500';
            $rules['tiers.*.tier_features'] = 'nullable|array|max:5';
            $rules['tiers.*.tier_features.*'] = 'string|max:255';

            // Profit sharing validation if enabled
            if ($request->boolean('profit_sharing_enabled') && $request->has('profit_sharing_configs')) {
                $rules['profit_sharing_configs'] = 'array';
                $rules['profit_sharing_configs.*.level_1_commission'] = 'numeric|min:0|max:25';
                $rules['profit_sharing_configs.*.level_2_commission'] = 'numeric|min:0|max:25';
                $rules['profit_sharing_configs.*.level_3_commission'] = 'numeric|min:0|max:25';
                $rules['profit_sharing_configs.*.max_commission_cap'] = 'nullable|numeric|min:0';
            }
        } else {
            $rules['minimum_amount'] = 'required|numeric|min:1';
            $rules['maximum_amount'] = 'required|numeric|gt:minimum_amount';
            $rules['interest_rate'] = 'required|numeric|min:0|max:100';
            $rules['profit_sharing_enabled'] = 'nullable|boolean'; // Must be false for simple plans
        }

        return Validator::make($request->all(), $rules);
    }

    /**
     * Create investment plan
     */
    private function createInvestmentPlan(Request $request, string $planType): InvestmentPlan
    {
        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'interest_type' => $request->interest_type,
            'duration_days' => $request->duration_days,
            'return_type' => $request->return_type,
            'capital_return' => $request->boolean('capital_return', true),
            'status' => $request->status,
            'badge' => $request->badge,
            'color_scheme' => $request->color_scheme,
            'features' => $request->get('features', []),
            'sort_order' => $request->get('sort_order', 0),
            'is_tiered' => $planType === 'tiered',
            'profit_sharing_enabled' => $planType === 'tiered' ? $request->boolean('profit_sharing_enabled', false) : false
        ];

        if ($planType === 'simple') {
            $data['minimum_amount'] = $request->minimum_amount;
            $data['maximum_amount'] = $request->maximum_amount;
            $data['interest_rate'] = $request->interest_rate;
        } else {
            $tiers = $request->get('tiers', []);
            $data['base_interest_rate'] = collect($tiers)->avg('interest_rate');
            $data['max_tier_level'] = collect($tiers)->max('tier_level');
        }

        return InvestmentPlan::create($data);
    }

    /**
     * Create plan tiers
     */
    private function createPlanTiers(InvestmentPlan $plan, array $tiersData): array
    {
        $createdTiers = [];

        foreach ($tiersData as $tierData) {
            $tier = InvestmentPlanTier::create([
                'investment_plan_id' => $plan->id,
                'tier_level' => $tierData['tier_level'], // This now receives 1-based values
                'tier_name' => $tierData['tier_name'],
                'minimum_amount' => $tierData['minimum_amount'],
                'maximum_amount' => $tierData['maximum_amount'],
                'interest_rate' => $tierData['interest_rate'],
                'min_user_level' => $tierData['min_user_level'],
                'tier_description' => $tierData['tier_description'] ?? null,
                'tier_features' => $tierData['tier_features'] ?? [],
                'is_active' => true,
                'sort_order' => $tierData['sort_order'] ?? 0
            ]);

            $createdTiers[] = $tier;
        }

        return $createdTiers;
    }

    /**
     * Create profit sharing configurations
     */
    private function createProfitSharingConfigs(InvestmentPlan $plan, array $tiers, array $configsData): void
    {
        foreach ($configsData as $index => $configData) {
            if (isset($tiers[$index])) {
                $tier = $tiers[$index];

                // Validate commission totals
                $totalCommission = ($configData['level_1_commission'] ?? 0) +
                    ($configData['level_2_commission'] ?? 0) +
                    ($configData['level_3_commission'] ?? 0);

                if ($totalCommission <= 50) {
                    InvestmentPlanProfitSharing::create([
                        'investment_plan_id' => $plan->id,
                        'investment_plan_tier_id' => $tier->id,
                        'level_1_commission' => $configData['level_1_commission'] ?? 0,
                        'level_2_commission' => $configData['level_2_commission'] ?? 0,
                        'level_3_commission' => $configData['level_3_commission'] ?? 0,
                        'max_commission_cap' => $configData['max_commission_cap'] ?? null,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    /**
     * Update investment plan
     */
    private function updateInvestmentPlan(InvestmentPlan $plan, Request $request, string $planType): void
    {
        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'interest_type' => $request->interest_type,
            'duration_days' => $request->duration_days,
            'return_type' => $request->return_type,
            'capital_return' => $request->boolean('capital_return', true),
            'status' => $request->status,
            'badge' => $request->badge,
            'color_scheme' => $request->color_scheme,
            'features' => $request->get('features', []),
            'sort_order' => $request->get('sort_order', 0),
            'is_tiered' => $planType === 'tiered',
            'profit_sharing_enabled' => $planType === 'tiered' ? $request->boolean('profit_sharing_enabled', false) : false
        ];

        if ($planType === 'simple') {
            $data['minimum_amount'] = $request->minimum_amount;
            $data['maximum_amount'] = $request->maximum_amount;
            $data['interest_rate'] = $request->interest_rate;
            $data['base_interest_rate'] = null;
            $data['max_tier_level'] = 0;
        } else {
            $tiers = $request->get('tiers', []);
            $data['minimum_amount'] = null;
            $data['maximum_amount'] = null;
            $data['interest_rate'] = null;
            $data['base_interest_rate'] = collect($tiers)->avg('interest_rate');
            $data['max_tier_level'] = collect($tiers)->max('tier_level');
        }

        $plan->update($data);
    }

    /**
     * Handle plan type conversion
     */
    private function handlePlanTypeChange(InvestmentPlan $plan, string $newType, Request $request): void
    {
        if ($newType === 'simple') {
            // Converting to simple plan - remove all tiers and profit sharing
            $plan->profitSharingConfigs()->delete();
            $plan->tiers()->delete();
        } else {
            // Converting to tiered plan - create tiers
            $tiers = $this->createPlanTiers($plan, $request->get('tiers', []));

            // Handle profit sharing if enabled
            if ($request->boolean('profit_sharing_enabled') && $request->has('profit_sharing_configs')) {
                $this->createProfitSharingConfigs($plan, $tiers, $request->get('profit_sharing_configs', []));
            }
        }
    }

    /**
     * Update plan tiers
     */
    private function updatePlanTiers(InvestmentPlan $plan, array $tiersData): array
    {
        // Delete existing tiers and their profit sharing configs
        $plan->profitSharingConfigs()->delete();
        $plan->tiers()->delete();

        // Recreate tiers
        return $this->createPlanTiers($plan, $tiersData);
    }

    /**
     * Handle profit sharing update
     */
    private function handleProfitSharingUpdate(InvestmentPlan $plan, Request $request, array $tiers): void
    {
        // Delete existing profit sharing configs
        $plan->profitSharingConfigs()->delete();

        // Create new profit sharing configs if enabled
        if ($request->boolean('profit_sharing_enabled') && $request->has('profit_sharing_configs')) {
            $this->createProfitSharingConfigs($plan, $tiers, $request->get('profit_sharing_configs', []));
        }
    }

    /**
     * Get filtered plans
     */
    private function getFilteredPlans(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = InvestmentPlan::query()
            ->withCount(['userInvestments', 'activeInvestments'])
            ->withSum('userInvestments', 'amount')
            ->with(['tiers' => fn($q) => $q->orderBy('tier_level'), 'profitSharingConfigs']);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['plan_type']) {
            $query->where('is_tiered', $filters['plan_type'] === 'tiered');
        }

        if ($filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy($filters['sort_by'], $filters['sort_order'])
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Get filtered investments
     */
    private function getFilteredInvestments(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = UserInvestment::with(['user', 'investmentPlan']);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['plan_id']) {
            $query->where('investment_plan_id', $filters['plan_id']);
        }

        if ($filters['user_level'] !== null) {
            $query->where('user_level_at_investment', $filters['user_level']);
        }

        if ($filters['search']) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('first_name', 'like', "%{$filters['search']}%")
                    ->orWhere('last_name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if ($filters['date_range']) {
            $dates = explode(' - ', $filters['date_range']);
            if (count($dates) === 2) {
                $query->whereBetween('created_at', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            }
        }

        return $query->latest()->paginate(20)->withQueryString();
    }

    /**
     * Get filtered returns
     */
    private function getFilteredReturns(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = InvestmentReturn::with(['user', 'userInvestment.investmentPlan']);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['type']) {
            $query->where('type', $filters['type']);
        }

        if ($filters['search']) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('first_name', 'like', "%{$filters['search']}%")
                    ->orWhere('last_name', 'like', "%{$filters['search']}%")
                    ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if ($filters['date_range']) {
            $dates = explode(' - ', $filters['date_range']);
            if (count($dates) === 2) {
                $query->whereBetween('due_date', [
                    Carbon::parse($dates[0])->startOfDay(),
                    Carbon::parse($dates[1])->endOfDay()
                ]);
            }
        }

        return $query->orderByRaw("FIELD(status, 'pending', 'failed', 'paid')")
            ->orderBy('due_date', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStatistics(): array
    {
        return [
            'plans' => array_merge(
                InvestmentPlan::getStatistics(),
                [
                    'tiered_plans' => InvestmentPlan::where('is_tiered', true)->count(),
                    'simple_plans' => InvestmentPlan::where('is_tiered', false)->count(),
                    'profit_sharing_enabled' => InvestmentPlan::where('profit_sharing_enabled', true)->count()
                ]
            ),
            'investments' => UserInvestment::getStatistics(),
            'returns' => InvestmentReturn::getStatistics(),
            'user_levels' => User::getLevelStatistics(),
            'profit_sharing' => InvestmentPlanProfitSharing::getStatistics(),
            'top_plans' => InvestmentPlan::withCount('activeInvestments')
                ->withSum('activeInvestments', 'amount')
                ->orderByDesc('active_investments_count')
                ->limit(5)
                ->get()
        ];
    }

    /**
     * Get plan analytics
     */
    private function getPlanAnalytics(InvestmentPlan $plan): array
    {
        $analytics = [
            'investment_stats' => $plan->userInvestments()
                ->selectRaw('status, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('status')
                ->get()
                ->keyBy('status'),

            'monthly_data' => $plan->userInvestments()
                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count, SUM(amount) as amount')
                ->where('created_at', '>=', now()->subYear())
                ->groupBy('year', 'month')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get(),

            'user_level_stats' => $plan->userInvestments()
                ->join('users', 'user_investments.user_id', '=', 'users.id')
                ->selectRaw('users.user_level, COUNT(*) as count, SUM(user_investments.amount) as total_amount')
                ->groupBy('users.user_level')
                ->orderBy('users.user_level')
                ->get()
        ];

        if ($plan->is_tiered) {
            $analytics['tier_stats'] = InvestmentPlanTier::getPlanTierStats($plan);
        }

        if ($plan->hasProfitSharing()) {
            $analytics['profit_sharing_stats'] = $plan->getProfitSharingStats();
        }

        return $analytics;
    }

    /**
     * Format tier data for API response
     */
    private function formatTierData(InvestmentPlanTier $tier): array
    {
        return [
            'id' => $tier->id,
            'tier_level' => $tier->tier_level,
            'tier_name' => $tier->tier_name,
            'investment_range' => $tier->investment_range,
            'interest_rate' => $tier->formatted_interest_rate,
            'min_user_level' => $tier->min_user_level,
            'tier_description' => $tier->tier_description,
            'tier_features' => $tier->tier_features,
            'is_active' => $tier->is_active,
            'total_investments' => $tier->user_investments_count ?? 0,
            'total_amount' => $tier->user_investments_sum_amount ?? 0,
            'formatted_amount' => '$' . number_format($tier->user_investments_sum_amount ?? 0, 2)
        ];
    }

    /**
     * Build investment options for user
     */
    private function buildInvestmentOptions(User $user, float $amount): array
    {
        $activePlans = InvestmentPlan::active()->with('tiers')->get();
        $options = [];

        foreach ($activePlans as $plan) {
            $planOption = [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'is_tiered' => $plan->is_tiered,
                'user_level' => $user->user_level
            ];

            if ($plan->is_tiered) {
                $availableTiers = $plan->getAvailableTiersForUser($user);
                $planOption['available_tiers'] = $availableTiers->map(function ($tier) use ($amount) {
                    return [
                        'tier_id' => $tier->id,
                        'tier_level' => $tier->tier_level,
                        'tier_name' => $tier->tier_name,
                        'investment_range' => $tier->investment_range,
                        'interest_rate' => $tier->formatted_interest_rate,
                        'can_invest' => $amount > 0 ? $tier->isAmountInRange($amount) : true
                    ];
                });

                if ($amount > 0) {
                    $tier = $plan->getTierForInvestment($user, $amount);
                    $planOption['recommended_tier'] = $tier ? [
                        'tier_id' => $tier->id,
                        'tier_name' => $tier->tier_name,
                        'interest_rate' => $tier->formatted_interest_rate
                    ] : null;
                }
            } else {
                $canInvest = $plan->canUserInvest($user, $amount);
                $planOption['can_invest'] = $canInvest['can_invest'];
                $planOption['investment_range'] = $plan->formatted_minimum . ' - ' . $plan->formatted_maximum;
                $planOption['interest_rate'] = $plan->formatted_interest_rate;

                if (!$canInvest['can_invest']) {
                    $planOption['reason'] = $canInvest['reason'];
                }
            }

            $options[] = $planOption;
        }

        return $options;
    }

    /**
     * Calculate investment simulation
     */
    private function calculateInvestmentSimulation(InvestmentPlan $plan, User $user, float $amount, ?int $tierId = null): array
    {
        $canInvest = $plan->canUserInvest($user, $amount);

        if (!$canInvest['can_invest']) {
            return [
                'can_invest' => false,
                'reason' => $canInvest['reason']
            ];
        }

        $tier = null;
        if ($plan->is_tiered) {
            $tier = $tierId ? InvestmentPlanTier::find($tierId) : $plan->getTierForInvestment($user, $amount);

            if (!$tier) {
                return [
                    'can_invest' => false,
                    'reason' => 'No suitable tier found for this investment.'
                ];
            }
        }

        $totalReturn = $plan->calculateTotalReturn($amount, $tier);
        $maturityAmount = $plan->calculateMaturityAmount($amount, $tier);
        $singleReturn = $plan->calculateSingleReturn($amount, $tier);
        $returnPeriods = $plan->getReturnPeriods();

        $simulation = [
            'can_invest' => true,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'is_tiered' => $plan->is_tiered,
                'duration_days' => $plan->duration_days,
                'interest_type' => $plan->interest_type,
                'return_type' => $plan->return_type,
                'capital_return' => $plan->capital_return
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'level' => $user->user_level,
                'level_name' => $user->level_name
            ],
            'tier' => $tier ? [
                'id' => $tier->id,
                'level' => $tier->tier_level,
                'name' => $tier->tier_name,
                'interest_rate' => $tier->interest_rate
            ] : null,
            'investment' => [
                'amount' => $amount,
                'formatted_amount' => '$' . number_format($amount, 2)
            ],
            'returns' => [
                'interest_rate' => $tier ? $tier->interest_rate : $plan->interest_rate,
                'return_periods' => $returnPeriods,
                'single_return' => $singleReturn,
                'formatted_single_return' => '$' . number_format($singleReturn, 2),
                'total_return' => $totalReturn,
                'formatted_total_return' => '$' . number_format($totalReturn, 2),
                'maturity_amount' => $maturityAmount,
                'formatted_maturity_amount' => '$' . number_format($maturityAmount, 2),
                'roi_percentage' => ($totalReturn / $amount * 100)
            ],
            'schedule' => $this->generateReturnSchedule($plan, $amount, $tier)
        ];

        // Add profit sharing simulation if enabled
        if ($plan->hasProfitSharing() && $tier) {
            $simulation['profit_sharing'] = $tier->getProfitSharingPreview($amount);
        }

        return $simulation;
    }

    /**
     * Generate return schedule
     */
    private function generateReturnSchedule(InvestmentPlan $plan, float $amount, ?InvestmentPlanTier $tier = null): array
    {
        $schedule = [];
        $currentDate = now();
        $rate = $tier ? $tier->interest_rate / 100 : $plan->interest_rate / 100;
        $periods = $plan->getReturnPeriods();

        $intervalDays = match ($plan->interest_type) {
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'yearly' => 365,
            default => 1
        };

        for ($i = 1; $i <= $periods; $i++) {
            $dueDate = $currentDate->copy()->addDays($i * $intervalDays);
            $returnAmount = $amount * $rate;

            $schedule[] = [
                'period' => $i,
                'due_date' => $dueDate->format('Y-m-d'),
                'formatted_due_date' => $dueDate->format('M d, Y'),
                'type' => 'interest',
                'amount' => $returnAmount,
                'formatted_amount' => '$' . number_format($returnAmount, 2)
            ];
        }

        if ($plan->capital_return) {
            $maturityDate = $currentDate->copy()->addDays($plan->duration_days);
            $schedule[] = [
                'period' => $periods + 1,
                'due_date' => $maturityDate->format('Y-m-d'),
                'formatted_due_date' => $maturityDate->format('M d, Y'),
                'type' => 'capital',
                'amount' => $amount,
                'formatted_amount' => '$' . number_format($amount, 2)
            ];
        }

        return $schedule;
    }

    /**
     * Export plans as CSV
     */
    private function exportPlansAsCsv(array $filters)
    {
        $query = InvestmentPlan::withCount(['userInvestments', 'activeInvestments'])
            ->withSum('userInvestments', 'amount')
            ->with(['tiers', 'profitSharingConfigs']);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        if ($filters['plan_type'] === 'tiered') {
            $query->where('is_tiered', true);
        } elseif ($filters['plan_type'] === 'simple') {
            $query->where('is_tiered', false);
        }

        $plans = $query->orderBy('sort_order')->get();

        $filename = 'investment_plans_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\""
        ];

        $callback = function () use ($plans) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID',
                'Name',
                'Description',
                'Type',
                'Minimum Amount',
                'Maximum Amount',
                'Interest Rate',
                'Interest Type',
                'Duration (Days)',
                'Return Type',
                'Capital Return',
                'Status',
                'Profit Sharing',
                'Total Investors',
                'Total Invested',
                'Active Investments',
                'Tiers Count',
                'Created At'
            ]);

            foreach ($plans as $plan) {
                fputcsv($file, [
                    $plan->id,
                    $plan->name,
                    $plan->description,
                    $plan->is_tiered ? 'Tiered' : 'Simple',
                    $plan->minimum_amount ?? 'N/A',
                    $plan->maximum_amount ?? 'N/A',
                    ($plan->interest_rate ?? $plan->base_interest_rate) . '%',
                    ucfirst($plan->interest_type),
                    $plan->duration_days,
                    ucfirst($plan->return_type),
                    $plan->capital_return ? 'Yes' : 'No',
                    ucfirst($plan->status),
                    $plan->profit_sharing_enabled ? 'Enabled' : 'Disabled',
                    $plan->user_investments_count,
                    $plan->user_investments_sum_amount ?? 0,
                    $plan->active_investments_count,
                    $plan->is_tiered ? $plan->tiers->count() : 0,
                    $plan->created_at->format('Y-m-d H:i:s')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Log plan activity
     */
    private function logPlanActivity(string $action, ?InvestmentPlan $plan, array $data = []): void
    {
        Log::info("Investment plan {$action}", array_merge([
            'plan_id' => $plan?->id,
            'plan_name' => $plan?->name,
            'timestamp' => now()->toISOString()
        ], $data));
    }
}