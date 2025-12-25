<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminReferralController extends Controller
{
    /**
     * Display the referrals dashboard.
     */
    public function index(Request $request): View
    {
        $user = \Auth::user();

        // Get filter parameters
        $status = $request->get('status');
        $dateRange = $request->get('date_range', '30');
        $sponsorId = $request->get('sponsor_id');
        $search = $request->get('search');

        // Build query for users who have sponsors (are referrals)
        $query = User::with(['profile', 'sponsor.profile'])
            ->whereNotNull('sponsor_id');

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($sponsorId) {
            $query->where('sponsor_id', $sponsorId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%")
                  ->orWhereHas('sponsor', function ($sponsorQuery) use ($search) {
                      $sponsorQuery->where('first_name', 'LIKE', "%{$search}%")
                          ->orWhere('last_name', 'LIKE', "%{$search}%")
                          ->orWhere('email', 'LIKE', "%{$search}%")
                          ->orWhere('username', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Apply date range filter
        if ($dateRange && $dateRange !== 'all') {
            $days = intval($dateRange);
            $query->where('created_at', '>=', now()->subDays($days));
        }

        // Get paginated results
        $referrals = $query->latest()->paginate(20)->appends($request->query());

        // Add computed fields for each referral
        $referrals->getCollection()->transform(function ($referral) {
            $referral->commission_earned = $this->calculateCommissionForUser($referral);
            $referral->referral_level = $this->calculateReferralLevel($referral);
            return $referral;
        });

        // Get statistics
        $stats = $this->getReferralStatistics($dateRange);

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        return view('admin.referrals.index', compact(
            'referrals',
            'stats',
            'filterOptions',
            'status',
            'user',
            'dateRange',
            'sponsorId',
            'search'
        ));
    }

    /**
     * Display referral details.
     */
    public function show(User $referral): View
    {
        $user = \Auth::user();

        $referral->load(['profile', 'sponsor.profile']);
        
        // Calculate additional data
        $referral->commission_earned = $this->calculateCommissionForUser($referral);
        $referral->referral_level = $this->calculateReferralLevel($referral);

        // Get referral history for this user (users they referred)
        $userReferrals = User::where('sponsor_id', $referral->id)
            ->with(['profile'])
            ->latest()
            ->get();

        // Get their own referral chain (who referred them up the chain)
        $referralChain = $this->getReferralChain($referral);

        // Get referral statistics for this user
        $referralStats = $this->getUserReferralStatistics($referral);

        return view('admin.referrals.show', compact(
            'referral',
            'userReferrals',
            'referralChain',
            'referralStats',
            'user'
        ));
    }

    /**
     * Update user status (replaces referral status update).
     */
    public function updateStatus(Request $request, User $referral): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:active,inactive,blocked,pending_verification',
            'notes' => 'nullable|string|max:500'
        ]);

        $referral->update([
            'status' => $request->status
        ]);

        // Update profile notes if provided
        if ($request->notes && $referral->profile) {
            $currentNotes = $referral->profile->notes ?? '';
            $newNote = "[" . now()->format('Y-m-d H:i:s') . "] Status changed to {$request->status}: {$request->notes}";
            $referral->profile->update([
                'notes' => $currentNotes . "\n" . $newNote
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully',
            'data' => [
                'id' => $referral->id,
                'status' => $referral->status,
                'status_badge_class' => $this->getStatusBadgeClass($referral->status)
            ]
        ]);
    }

    /**
     * Bulk update user statuses.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'status' => 'required|in:active,inactive,blocked,pending_verification'
        ]);

        $updated = User::whereIn('id', $request->user_ids)
            ->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updated} user(s)",
            'data' => [
                'updated_count' => $updated,
                'status' => $request->status
            ]
        ]);
    }

    /**
     * Get referral analytics data.
     */
    public function analytics(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30');
        $startDate = now()->subDays($dateRange);
        $endDate = now();

        $analytics = $this->getPerformanceData($startDate, $endDate);
        
        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Export referrals data.
     */
    public function export(Request $request)
    {
        $status = $request->get('status');
        $dateRange = $request->get('date_range');
        
        $query = User::with(['profile', 'sponsor'])
            ->whereNotNull('sponsor_id');

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateRange && $dateRange !== 'all') {
            $days = intval($dateRange);
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $referrals = $query->get();

        $filename = 'referrals_' . now()->format('Y_m_d_H_i_s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($referrals) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Sponsor Name',
                'Sponsor Email',
                'Referred User Name',
                'Referred User Email',
                'Referral Level',
                'Status',
                'Total Invested',
                'Commission Earned',
                'Registration Date',
                'Last Login'
            ]);

            // Add data rows
            foreach ($referrals as $referral) {
                $commissionEarned = $this->calculateCommissionForUser($referral);
                $referralLevel = $this->calculateReferralLevel($referral);
                
                fputcsv($file, [
                    $referral->id,
                    $referral->sponsor ? $referral->sponsor->full_name : 'N/A',
                    $referral->sponsor ? $referral->sponsor->email : 'N/A',
                    $referral->full_name,
                    $referral->email,
                    $referralLevel,
                    ucfirst($referral->status),
                    '$' . number_format($referral->total_invested ?? 0, 2),
                    '$' . number_format($commissionEarned, 2),
                    $referral->created_at->format('Y-m-d H:i:s'),
                    $referral->last_login_at ? $referral->last_login_at->format('Y-m-d H:i:s') : 'Never',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get top sponsors data.
     */
    public function topSponsors(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        
        $topSponsors = User::select('users.*')
            ->selectRaw('COUNT(referrals.id) as referral_count')
            ->selectRaw('SUM(referrals.total_invested) as total_referral_investments')
            ->leftJoin('users as referrals', 'users.id', '=', 'referrals.sponsor_id')
            ->with(['profile'])
            ->groupBy('users.id')
            ->having('referral_count', '>', 0)
            ->orderByDesc('referral_count')
            ->limit($limit)
            ->get();

        // Calculate commission for each sponsor
        $topSponsors->transform(function ($sponsor) {
            $sponsor->total_commission = $this->calculateTotalCommissionForSponsor($sponsor);
            return $sponsor;
        });

        return response()->json([
            'success' => true,
            'data' => $topSponsors
        ]);
    }

    /**
     * Search sponsors for assignment.
     */
    public function searchSponsors(Request $request): JsonResponse
    {
        $search = $request->get('search');
        
        $sponsors = User::where(function ($query) use ($search) {
            $query->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%");
        })
        ->where('status', 'active')
        ->limit(20)
        ->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json([
            'success' => true,
            'data' => $sponsors->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'display' => $user->full_name . ' (' . $user->email . ')'
                ];
            })
        ]);
    }

    /**
     * Get referral commission history.
     */
    public function commissionHistory(Request $request, User $referral): JsonResponse
    {
        // Get commission transactions for this user's sponsor
        $commissionTransactions = [];
        
        if ($referral->sponsor) {
            $commissionTransactions = $referral->sponsor->transactions()
                ->where('type', 'commission')
                ->where('description', 'LIKE', "%referral%")
                ->latest()
                ->limit(20)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_commission' => $this->calculateCommissionForUser($referral),
                'commission_transactions' => $commissionTransactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'amount' => $transaction->amount,
                        'description' => $transaction->description,
                        'status' => $transaction->status,
                        'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                        'formatted_amount' => '$' . number_format($transaction->amount, 2)
                    ];
                })
            ]
        ]);
    }

    /**
     * Get dashboard stats for AJAX updates.
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $dateRange = $request->get('date_range', '30');
        $stats = $this->getReferralStatistics($dateRange);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Display referral tree visualization.
     */
    public function tree(Request $request): View
    {
        $user = \Auth::user();

        $userId = $request->get('user_id');
        $maxDepth = $request->get('max_depth', 3);
        $showInactive = $request->get('show_inactive', false);
        
        // Get all users for the dropdown
        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();
        
        $treeData = null;
        $selectedUser = null;
        
        if ($userId) {
            $selectedUser = User::find($userId);
            if ($selectedUser) {
                $treeData = $this->buildReferralTree($selectedUser, $maxDepth, $showInactive);
            }
        }
        
        return view('admin.referrals.tree', compact(
            'users',
            'selectedUser', 
            'treeData',
            'userId',
            'user',
            'maxDepth',
            'showInactive'
        ));
    }

    /**
     * Get tree data via AJAX.
     */
    public function getTreeData(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        $maxDepth = $request->get('max_depth', 3);
        $showInactive = $request->get('show_inactive', false);
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }
        
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
        
        $treeData = $this->buildReferralTree($user, $maxDepth, $showInactive);
        
        return response()->json([
            'success' => true,
            'data' => $treeData
        ]);
    }

    /**
     * Search users for tree selection.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $search = $request->get('search');
        
        $users = User::where(function ($query) use ($search) {
            $query->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('username', 'LIKE', "%{$search}%");
        })
        ->where('role', 'user')
        ->limit(20)
        ->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'display' => $user->full_name . ' (' . $user->email . ')'
                ];
            })
        ]);
    }

    /**
     * Get tree statistics.
     */
    public function getTreeStats(Request $request): JsonResponse
    {
        $userId = $request->get('user_id');
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User ID is required'
            ]);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        // Calculate tree statistics
        $stats = $this->calculateTreeStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Private method to get referral statistics.
     */
    private function getReferralStatistics(string $dateRange = '30'): array
    {
        // Base statistics
        $baseQuery = User::whereNotNull('sponsor_id');
        $totalReferrals = $baseQuery->count();
        $activeReferrals = $baseQuery->where('status', 'active')->count();
        $inactiveReferrals = $baseQuery->where('status', 'inactive')->count();
        $blockedReferrals = $baseQuery->where('status', 'blocked')->count();
        
        // Time-based statistics
        $todayReferrals = User::whereNotNull('sponsor_id')->whereDate('created_at', today())->count();
        $thisWeekReferrals = User::whereNotNull('sponsor_id')->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        $thisMonthReferrals = User::whereNotNull('sponsor_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        // Date-specific stats for the selected period
        $periodQuery = User::whereNotNull('sponsor_id');
        if ($dateRange !== 'all') {
            $days = intval($dateRange);
            $periodQuery->where('created_at', '>=', now()->subDays($days));
        }

        $periodReferrals = $periodQuery->count();
        $periodActive = (clone $periodQuery)->where('status', 'active')->count();

        // Calculate total commission from profiles
        $totalCommission = UserProfile::whereHas('user', function ($q) {
            $q->whereNotNull('sponsor_id');
        })->sum('total_commission_earned');

        // Active commission (commission from active referrals only)
        $activeCommission = UserProfile::whereHas('user', function ($q) {
            $q->whereNotNull('sponsor_id')->where('status', 'active');
        })->sum('total_commission_earned');

        // Calculate growth rate
        $previousPeriodQuery = User::whereNotNull('sponsor_id');
        if ($dateRange !== 'all') {
            $days = intval($dateRange);
            $previousPeriodQuery->whereBetween('created_at', [
                now()->subDays($days * 2),
                now()->subDays($days)
            ]);
        }

        $previousPeriodCount = $previousPeriodQuery->count();
        $growthRate = $previousPeriodCount > 0 
            ? (($periodReferrals - $previousPeriodCount) / $previousPeriodCount) * 100 
            : 0;

        return [
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'inactive_referrals' => $inactiveReferrals,
            'blocked_referrals' => $blockedReferrals,
            'today_referrals' => $todayReferrals,
            'this_week_referrals' => $thisWeekReferrals,
            'this_month_referrals' => $thisMonthReferrals,
            'period_referrals' => $periodReferrals,
            'period_active' => $periodActive,
            'total_commission' => $totalCommission,
            'active_commission' => $activeCommission,
            'growth_rate' => round($growthRate, 1),
            'average_commission' => $totalReferrals > 0 
                ? round($totalCommission / $totalReferrals, 2) 
                : 0,
            'conversion_rate' => $totalReferrals > 0 
                ? round(($activeReferrals / $totalReferrals) * 100, 1) 
                : 0
        ];
    }

    /**
     * Build referral tree data structure.
     */
    private function buildReferralTree(User $user, int $maxDepth = 3, bool $showInactive = false, int $currentDepth = 0): array
    {
        // Base user data
        $userData = [
            'id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'status' => $user->status,
            'is_active' => $user->isActive(),
            'total_referrals' => 0,
            'active_referrals' => 0,
            'total_commission' => 0,
            'level' => $currentDepth,
            'children' => [],
            'user_data' => [
                'total_invested' => $user->total_invested ?? 0,
                'created_at' => $user->created_at->format('M d, Y'),
                'last_login' => $user->last_login_at ? $user->last_login_at->format('M d, Y') : 'Never'
            ]
        ];

        // Get referral statistics
        $directReferrals = User::where('sponsor_id', $user->id);
        $activeDirectReferrals = User::where('sponsor_id', $user->id)->where('status', 'active');
        
        $userData['total_referrals'] = $directReferrals->count();
        $userData['active_referrals'] = $activeDirectReferrals->count();
        $userData['total_commission'] = $this->calculateTotalCommissionForSponsor($user);

        // Stop recursion if we've reached max depth
        if ($currentDepth >= $maxDepth) {
            return $userData;
        }

        // Get direct referrals
        $referralsQuery = User::where('sponsor_id', $user->id);

        if (!$showInactive) {
            $referralsQuery->where('status', 'active');
        }

        $referrals = $referralsQuery->get();

        // Build children recursively
        foreach ($referrals as $referral) {
            $childData = $this->buildReferralTree(
                $referral, 
                $maxDepth, 
                $showInactive, 
                $currentDepth + 1
            );
            
            $userData['children'][] = $childData;
        }

        return $userData;
    }

    /**
     * Calculate comprehensive tree statistics.
     */
    private function calculateTreeStatistics(User $user): array
    {
        // Direct referrals
        $directReferrals = User::where('sponsor_id', $user->id)->get();
        $activeDirectReferrals = $directReferrals->where('status', 'active');
        $inactiveDirectReferrals = $directReferrals->where('status', 'inactive');
        $blockedDirectReferrals = $directReferrals->where('status', 'blocked');

        // Get all downline
        $allDownlineIds = $this->getAllDownlineIds($user->id);
        $totalDownline = count($allDownlineIds);

        // Calculate levels
        $levelStats = [];
        if ($totalDownline > 0) {
            for ($i = 1; $i <= 5; $i++) {
                $levelStats["level_{$i}"] = $this->countUsersAtLevel($user->id, $i);
            }
        }

        return [
            'direct_referrals' => [
                'total' => $directReferrals->count(),
                'active' => $activeDirectReferrals->count(),
                'inactive' => $inactiveDirectReferrals->count(),
                'blocked' => $blockedDirectReferrals->count(),
                'commission' => $this->calculateTotalCommissionForSponsor($user)
            ],
            'total_downline' => $totalDownline,
            'levels' => $levelStats,
            'user_info' => [
                'name' => $user->full_name,
                'email' => $user->email,
                'status' => $user->status,
                'joined' => $user->created_at->format('M d, Y'),
                'total_invested' => $user->total_invested ?? 0,
                'total_balance' => $user->available_balance ?? 0
            ]
        ];
    }

    /**
     * Get all downline user IDs recursively.
     */
    private function getAllDownlineIds(int $sponsorId, array &$collected = []): array
    {
        $directReferrals = User::where('sponsor_id', $sponsorId)
            ->pluck('id')
            ->toArray();

        foreach ($directReferrals as $userId) {
            if (!in_array($userId, $collected)) {
                $collected[] = $userId;
                $this->getAllDownlineIds($userId, $collected);
            }
        }

        return $collected;
    }

    /**
     * Count users at specific referral level.
     */
    private function countUsersAtLevel(int $sponsorId, int $targetLevel, int $currentLevel = 1): int
    {
        if ($currentLevel == $targetLevel) {
            return User::where('sponsor_id', $sponsorId)->count();
        }

        if ($currentLevel > $targetLevel) {
            return 0;
        }

        $count = 0;
        $directReferrals = User::where('sponsor_id', $sponsorId)->pluck('id');
        
        foreach ($directReferrals as $userId) {
            $count += $this->countUsersAtLevel($userId, $targetLevel, $currentLevel + 1);
        }

        return $count;
    }

    /**
     * Calculate commission earned from a specific referral user.
     */
    private function calculateCommissionForUser(User $referral): float
    {
        if (!$referral->sponsor) {
            return 0;
        }

        // Get commission from sponsor's profile if it exists
        if ($referral->sponsor->profile && $referral->sponsor->profile->total_commission_earned > 0) {
            // This is a simplified calculation - you might want to implement more complex logic
            $level = $this->calculateReferralLevel($referral);
            $commissionRate = $this->getCommissionRateForLevel($level);
            return ($referral->total_invested ?? 0) * $commissionRate;
        }

        return 0;
    }

    /**
     * Calculate total commission for a sponsor from all their referrals.
     */
    private function calculateTotalCommissionForSponsor(User $sponsor): float
    {
        $totalCommission = 0;
        $referrals = User::where('sponsor_id', $sponsor->id)->get();
        
        foreach ($referrals as $referral) {
            $level = $this->calculateReferralLevel($referral);
            $commissionRate = $this->getCommissionRateForLevel($level);
            $totalCommission += ($referral->total_invested ?? 0) * $commissionRate;
        }

        return $totalCommission;
    }

    /**
     * Calculate referral level (depth from sponsor).
     */
    private function calculateReferralLevel(User $user): int
    {
        $level = 1;
        $currentUser = $user;

        while ($currentUser->sponsor && $level < 10) { // Prevent infinite loops
            $level++;
            $currentUser = $currentUser->sponsor;
        }

        return $level;
    }

    /**
     * Get commission rate based on referral level.
     */
    private function getCommissionRateForLevel(int $level): float
    {
        $rates = [
            1 => 0.10, // 10% for level 1
            2 => 0.05, // 5% for level 2  
            3 => 0.03, // 3% for level 3
            4 => 0.02, // 2% for level 4
            5 => 0.01, // 1% for level 5
        ];

        return $rates[$level] ?? 0;
    }

    /**
     * Get referral chain (upline) for a user.
     */
    private function getReferralChain(User $user): array
    {
        $chain = [];
        $currentUser = $user;
        $level = 0;

        while ($currentUser->sponsor && $level < 10) { // Prevent infinite loops
            $chain[] = [
                'user' => $currentUser->sponsor,
                'level' => $level + 1
            ];
            $currentUser = $currentUser->sponsor;
            $level++;
        }

        return $chain;
    }

    /**
     * Get statistics for a specific user's referral performance.
     */
    private function getUserReferralStatistics(User $user): array
    {
        $directReferrals = User::where('sponsor_id', $user->id)->get();
        $activeDirectReferrals = $directReferrals->where('status', 'active');
        
        return [
            'direct_referrals' => $directReferrals->count(),
            'active_referrals' => $activeDirectReferrals->count(),
            'total_referral_investments' => $directReferrals->sum('total_invested'),
            'total_commission_earned' => $this->calculateTotalCommissionForSponsor($user),
            'average_referral_investment' => $directReferrals->count() > 0 
                ? $directReferrals->avg('total_invested') 
                : 0
        ];
    }

    /**
     * Get performance data for charts.
     */
    private function getPerformanceData(Carbon $startDate, Carbon $endDate): array
    {
        $data = User::whereNotNull('sponsor_id')
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_invested) as investment')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($date) => Carbon::parse($date)->format('M d'))->toArray(),
            'referrals' => $data->pluck('count')->toArray(),
            'investments' => $data->pluck('investment')->map(fn($amount) => (float) $amount)->toArray(),
        ];
    }

    /**
     * Get status badge class.
     */
    private function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            'active' => 'bg-success',
            'inactive' => 'bg-warning', 
            'blocked' => 'bg-danger',
            'pending_verification' => 'bg-info',
            default => 'bg-secondary'
        };
    }

    /**
     * Get filter options.
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'blocked' => 'Blocked',
                'pending_verification' => 'Pending Verification'
            ],
            'date_ranges' => [
                '7' => 'Last 7 days',
                '30' => 'Last 30 days',
                '60' => 'Last 60 days',
                '90' => 'Last 90 days',
                '365' => 'Last year',
                'all' => 'All time'
            ]
        ];
    }
}