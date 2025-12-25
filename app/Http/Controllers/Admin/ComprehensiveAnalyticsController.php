<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\Lead;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ComprehensiveAnalyticsController extends Controller
{
    /**
     * Display the comprehensive analytics dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $summaryData = $this->getComprehensiveSummaryData();
        $recentData = $this->getRecentActivityData();
        
        return view('admin.reports.index', compact('user', 'summaryData', 'recentData'));
    }

    /**
     * Get comprehensive summary data for all metrics
     */
    private function getComprehensiveSummaryData(string $period = '7d'): array
    {
        $startDate = $this->getStartDateFromPeriod($period);
        
        return [
            'transactions' => $this->getTransactionSummary($startDate),
            'users' => $this->getUserSummary($startDate),
            'leads' => $this->getLeadSummary($startDate),
        ];
    }

    /**
     * Get transaction summary data
     */
    private function getTransactionSummary(Carbon $startDate): array
    {
        return [
            'deposits' => (float) Transaction::deposits()
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'deposits_count' => Transaction::deposits()
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'withdrawals' => (float) Transaction::withdrawals()
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'withdrawals_count' => Transaction::withdrawals()
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'commissions' => (float) Transaction::where('type', Transaction::TYPE_COMMISSION)
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'roi' => (float) Transaction::where('type', Transaction::TYPE_ROI)
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'bonus' => (float) Transaction::where('type', Transaction::TYPE_BONUS)
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
            'investments' => (float) Transaction::where('type', Transaction::TYPE_INVESTMENT)
                ->completed()
                ->where('created_at', '>=', $startDate)
                ->sum('amount'),
        ];
    }

    /**
     * Get user summary data
     */
    private function getUserSummary(Carbon $startDate): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $newUsers = User::where('created_at', '>=', $startDate)->count();
        $kycVerified = User::kycVerified()->count();
        $onlineNow = User::where('last_login_at', '>=', Carbon::now()->subMinutes(5))->count();
        
        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'inactive' => User::where('status', 'inactive')->count(),
            'blocked' => User::where('status', 'blocked')->count(),
            'new_registrations' => $newUsers,
            'kyc_verified' => $kycVerified,
            'online_now' => $onlineNow,
            'activation_rate' => $totalUsers > 0 ? ($activeUsers / $totalUsers) * 100 : 0,
        ];
    }

    /**
     * Get lead summary data
     */
    private function getLeadSummary(Carbon $startDate): array
    {
        $totalLeads = Lead::count();
        $hotLeads = Lead::where('status', 'hot')->count();
        $warmLeads = Lead::where('status', 'warm')->count();
        $coldLeads = Lead::where('status', 'cold')->count();
        $convertedLeads = Lead::where('status', 'converted')->count();
        
        return [
            'total' => $totalLeads,
            'hot' => $hotLeads,
            'warm' => $warmLeads,
            'cold' => $coldLeads,
            'converted' => $convertedLeads,
            'new_leads' => Lead::where('created_at', '>=', $startDate)->count(),
            'conversion_rate' => $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0,
        ];
    }

    /**
     * Get recent activity data
     */
    private function getRecentActivityData(): array
    {
        return [
            'transactions' => Transaction::with(['user' => function($query) {
                $query->select('id', 'first_name', 'last_name', 'email');
            }])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'users' => User::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'leads' => Lead::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * API endpoint for chart data based on type
     */
    public function getChartData(Request $request)
    {
        $period = $request->get('period', '7d');
        $type = $request->get('type', 'transactions');
        $days = $this->getDaysFromPeriod($period);
        
        switch ($type) {
            case 'transactions':
                return $this->getTransactionChartData($days);
            case 'users':
                return $this->getUserChartData($days);
            case 'leads':
                return $this->getLeadChartData($days);
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
    }

    /**
     * Get transaction chart data
     */
    private function getTransactionChartData(int $days): \Illuminate\Http\JsonResponse
    {
        $chartData = collect();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartData->push([
                'date' => $date->format('Y-m-d'),
                'deposits' => (float) Transaction::deposits()
                    ->completed()
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
                'withdrawals' => (float) Transaction::withdrawals()
                    ->completed()
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
                'commissions' => (float) Transaction::where('type', Transaction::TYPE_COMMISSION)
                    ->completed()
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
            ]);
        }

        return response()->json([
            'series' => [
                [
                    'name' => 'Deposits',
                    'data' => $chartData->pluck('deposits')->toArray()
                ],
                [
                    'name' => 'Withdrawals',
                    'data' => $chartData->pluck('withdrawals')->toArray()
                ],
                [
                    'name' => 'Commissions',
                    'data' => $chartData->pluck('commissions')->toArray()
                ]
            ],
            'categories' => $chartData->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray()
        ]);
    }

    /**
     * Get user chart data
     */
    private function getUserChartData(int $days): \Illuminate\Http\JsonResponse
    {
        $chartData = collect();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartData->push([
                'date' => $date->format('Y-m-d'),
                'new_users' => User::whereDate('created_at', $date)->count(),
            ]);
        }

        // Get user status breakdown
        $statusBreakdown = [
            User::where('status', 'active')->count(),
            User::where('status', 'inactive')->count(),
            User::where('status', 'blocked')->count(),
            User::where('status', 'pending_verification')->count(),
        ];

        return response()->json([
            'new_users' => $chartData->pluck('new_users')->toArray(),
            'categories' => $chartData->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'status_breakdown' => $statusBreakdown,
        ]);
    }

    /**
     * Get lead chart data
     */
    private function getLeadChartData(int $days): \Illuminate\Http\JsonResponse
    {
        $chartData = collect();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartData->push([
                'date' => $date->format('Y-m-d'),
                'new_leads' => Lead::whereDate('created_at', $date)->count(),
            ]);
        }

        // Get lead status breakdown
        $statusBreakdown = [
            Lead::where('status', 'hot')->count(),
            Lead::where('status', 'warm')->count(),
            Lead::where('status', 'cold')->count(),
            Lead::where('status', 'converted')->count(),
            Lead::whereIn('status', ['lost', 'unqualified'])->count(),
        ];

        return response()->json([
            'new_leads' => $chartData->pluck('new_leads')->toArray(),
            'categories' => $chartData->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
            'status_breakdown' => $statusBreakdown,
        ]);
    }

    /**
     * Get comprehensive summary data via API
     */
    public function getSummaryData(Request $request)
    {
        $period = $request->get('period', '7d');
        $summaryData = $this->getComprehensiveSummaryData($period);
        
        // Add monthly trends for comparison chart
        $monthlyData = $this->getMonthlyTrendsData();
        
        return response()->json([
            'summary' => $summaryData,
            'monthly_deposits' => $monthlyData['deposits'],
            'monthly_users' => $monthlyData['users'],
            'monthly_leads' => $monthlyData['leads'],
            'months' => $monthlyData['months'],
        ]);
    }

    /**
     * Get monthly trends data for comparison chart
     */
    private function getMonthlyTrendsData(): array
    {
        $months = collect();
        $deposits = collect();
        $users = collect();
        $leads = collect();
        
        // Get last 6 months data
        for ($i = 5; $i >= 0; $i--) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = Carbon::now()->subMonths($i)->endOfMonth();
            
            $months->push($startOfMonth->format('M Y'));
            
            $deposits->push(
                (float) Transaction::deposits()
                    ->completed()
                    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                    ->sum('amount')
            );
            
            $users->push(
                User::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count()
            );
            
            $leads->push(
                Lead::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count()
            );
        }
        
        return [
            'months' => $months->toArray(),
            'deposits' => $deposits->toArray(),
            'users' => $users->toArray(),
            'leads' => $leads->toArray(),
        ];
    }

    /**
     * Get detailed analytics report
     */
    public function getDetailedReport(Request $request)
    {
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateFromPeriod($period);
        
        $report = [
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'transactions' => $this->getDetailedTransactionAnalytics($startDate),
            'users' => $this->getDetailedUserAnalytics($startDate),
            'leads' => $this->getDetailedLeadAnalytics($startDate),
            'performance_metrics' => $this->getPerformanceMetrics($startDate),
        ];
        
        return response()->json($report);
    }

    /**
     * Get detailed transaction analytics
     */
    private function getDetailedTransactionAnalytics(Carbon $startDate): array
    {
        $transactions = Transaction::where('created_at', '>=', $startDate);
        
        return [
            'total_volume' => (float) $transactions->sum('amount'),
            'transaction_count' => $transactions->count(),
            'avg_transaction_value' => (float) $transactions->avg('amount'),
            'by_type' => [
                'deposits' => [
                    'amount' => (float) $transactions->clone()->deposits()->sum('amount'),
                    'count' => $transactions->clone()->deposits()->count(),
                    'avg' => (float) $transactions->clone()->deposits()->avg('amount'),
                ],
                'withdrawals' => [
                    'amount' => (float) $transactions->clone()->withdrawals()->sum('amount'),
                    'count' => $transactions->clone()->withdrawals()->count(),
                    'avg' => (float) $transactions->clone()->withdrawals()->avg('amount'),
                ],
            ],
            'by_status' => [
                'completed' => $transactions->clone()->completed()->count(),
                'pending' => $transactions->clone()->pending()->count(),
                'failed' => $transactions->clone()->where('status', 'failed')->count(),
            ],
            'top_users' => $this->getTopTransactionUsers($startDate),
        ];
    }

    /**
     * Get detailed user analytics
     */
    private function getDetailedUserAnalytics(Carbon $startDate): array
    {
        $users = User::where('created_at', '>=', $startDate);
        
        return [
            'new_registrations' => $users->count(),
            'verified_users' => $users->clone()->verified()->count(),
            'kyc_completed' => $users->clone()->kycVerified()->count(),
            'active_users' => $users->clone()->active()->count(),
            'by_status' => [
                'active' => User::active()->count(),
                'inactive' => User::where('status', 'inactive')->count(),
                'blocked' => User::where('status', 'blocked')->count(),
            ],
            'geographical_distribution' => $this->getUserGeographicalDistribution(),
            'referral_stats' => $this->getUserReferralStats($startDate),
        ];
    }

    /**
     * Get detailed lead analytics
     */
    private function getDetailedLeadAnalytics(Carbon $startDate): array
    {
        $leads = Lead::where('created_at', '>=', $startDate);
        
        return [
            'new_leads' => $leads->count(),
            'converted_leads' => $leads->clone()->converted()->count(),
            'conversion_rate' => $this->getLeadConversionRate($startDate),
            'by_status' => [
                'hot' => Lead::hot()->count(),
                'warm' => Lead::where('status', 'warm')->count(),
                'cold' => Lead::where('status', 'cold')->count(),
                'converted' => Lead::converted()->count(),
            ],
            'by_source' => $this->getLeadsBySource($startDate),
            'avg_conversion_time' => $this->getAverageConversionTime(),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics(Carbon $startDate): array
    {
        return [
            'user_acquisition_cost' => $this->calculateUserAcquisitionCost($startDate),
            'customer_lifetime_value' => $this->calculateCustomerLifetimeValue(),
            'retention_rate' => $this->calculateRetentionRate($startDate),
            'churn_rate' => $this->calculateChurnRate($startDate),
            'revenue_per_user' => $this->calculateRevenuePerUser($startDate),
        ];
    }

    /**
     * Helper method to get top transaction users
     */
    private function getTopTransactionUsers(Carbon $startDate): array
    {
        return User::select('users.*')
            ->selectRaw('SUM(transactions.amount) as total_transaction_amount')
            ->selectRaw('COUNT(transactions.id) as transaction_count')
            ->join('transactions', 'users.id', '=', 'transactions.user_id')
            ->where('transactions.created_at', '>=', $startDate)
            ->where('transactions.status', 'completed')
            ->groupBy('users.id')
            ->orderByDesc('total_transaction_amount')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'total_amount' => (float) $user->total_transaction_amount,
                    'transaction_count' => (int) $user->transaction_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get user geographical distribution
     */
    private function getUserGeographicalDistribution(): array
    {
        return UserProfile::select('country')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'country' => $item->country,
                    'country_name' => $item->country_name,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get user referral statistics
     */
    private function getUserReferralStats(Carbon $startDate): array
    {
        $totalReferrals = User::whereNotNull('sponsor_id')
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $topReferrers = User::select('users.*')
            ->selectRaw('COUNT(referrals.id) as referral_count')
            ->leftJoin('users as referrals', 'users.id', '=', 'referrals.sponsor_id')
            ->where('referrals.created_at', '>=', $startDate)
            ->groupBy('users.id')
            ->having('referral_count', '>', 0)
            ->orderByDesc('referral_count')
            ->limit(10)
            ->get();

        return [
            'total_referrals' => $totalReferrals,
            'top_referrers' => $topReferrers->map(function ($user) {
                return [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'referral_count' => (int) $user->referral_count,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get leads by source
     */
    private function getLeadsBySource(Carbon $startDate): array
    {
        return Lead::select('source')
            ->selectRaw('COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Calculate lead conversion rate
     */
    private function getLeadConversionRate(Carbon $startDate): float
    {
        $totalLeads = Lead::where('created_at', '>=', $startDate)->count();
        $convertedLeads = Lead::where('created_at', '>=', $startDate)
            ->where('status', 'converted')
            ->count();
        
        return $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;
    }

    /**
     * Calculate average conversion time
     */
    private function getAverageConversionTime(): float
    {
        return Lead::where('status', 'converted')
            ->whereNotNull('updated_at')
            ->get()
            ->avg(function ($lead) {
                return $lead->created_at->diffInDays($lead->updated_at);
            }) ?? 0;
    }

    /**
     * Business metrics calculations
     */
    private function calculateUserAcquisitionCost(Carbon $startDate): float
    {
        // Placeholder - implement based on your marketing spend data
        return 0.0;
    }

    private function calculateCustomerLifetimeValue(): float
    {
        return (float) User::whereHas('transactions', function ($q) {
            $q->completed();
        })
        ->withSum(['transactions' => function ($q) {
            $q->completed();
        }], 'amount')
        ->get()
        ->avg('transactions_sum_amount') ?? 0;
    }

    private function calculateRetentionRate(Carbon $startDate): float
    {
        $usersFromPeriod = User::where('created_at', '<=', $startDate->copy()->subMonth())->count();
        $activeFromPeriod = User::where('created_at', '<=', $startDate->copy()->subMonth())
            ->where('last_login_at', '>=', $startDate)
            ->count();
        
        return $usersFromPeriod > 0 ? ($activeFromPeriod / $usersFromPeriod) * 100 : 0;
    }

    private function calculateChurnRate(Carbon $startDate): float
    {
        return 100 - $this->calculateRetentionRate($startDate);
    }

    private function calculateRevenuePerUser(Carbon $startDate): float
    {
        $totalRevenue = Transaction::deposits()
            ->completed()
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
        
        $activeUsers = User::where('last_login_at', '>=', $startDate)->count();
        
        return $activeUsers > 0 ? $totalRevenue / $activeUsers : 0;
    }

    /**
     * Export comprehensive report
     */
    public function exportReport(Request $request)
    {
        $period = $request->get('period', '30d');
        $format = $request->get('format', 'json'); // json, csv, pdf
        
        $report = $this->getDetailedReport($request)->getData();
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($report);
            case 'pdf':
                return $this->exportToPdf($report);
            default:
                return response()->json($report)
                    ->header('Content-Disposition', 'attachment; filename="comprehensive-report-' . now()->format('Y-m-d-H-i-s') . '.json"');
        }
    }

    /**
     * Helper methods for period calculations
     */
    private function getDaysFromPeriod(string $period): int
    {
        return match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };
    }

    private function getStartDateFromPeriod(string $period): Carbon
    {
        return match($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30),
        };
    }

    /**
     * Export methods (placeholder implementations)
     */
    private function exportToCsv($data)
    {
        // Implement CSV export logic
        return response()->json(['message' => 'CSV export not yet implemented']);
    }

    private function exportToPdf($data)
    {
        // Implement PDF export logic  
        return response()->json(['message' => 'PDF export not yet implemented']);
    }
}