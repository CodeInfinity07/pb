<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\UserEarning;
use App\Models\CryptoWallet;
use App\Models\Lead;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $dashboardData = $this->getDashboardData();

        return view('admin.dashboard.index', compact('user', 'dashboardData'));
    }

    /**
     * Show transaction analytics page
     */
    public function transactionAnalytics()
    {
        $user = Auth::user();
        $dashboardData = $this->getDashboardData();

        // Get transaction type totals for summary cards
        $transactionTotals = [
            'deposits' => (float) Transaction::deposits()->completed()->sum('amount'),
            'withdrawals' => (float) Transaction::withdrawals()->completed()->sum('amount'),
            'commissions' => (float) Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()->sum('amount'),
            'roi' => (float) Transaction::where('type', Transaction::TYPE_ROI)->completed()->sum('amount'),
            'bonus' => (float) Transaction::where('type', Transaction::TYPE_BONUS)->completed()->sum('amount'),
            'investments' => (float) Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()->sum('amount'),
        ];

        return view('admin.reports.index', compact('user', 'dashboardData', 'transactionTotals'));
    }

    /**
     * Get comprehensive dashboard data
     */
    private function getDashboardData(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            // User Statistics
            'user_stats' => $this->getUserStats($today, $thisWeek, $thisMonth),

            // Financial Summary
            'financial_summary' => $this->getFinancialSummary($today, $thisMonth, $lastMonth),

            // System Health
            'system_health' => $this->getSystemHealth(),

            // Recent Activity
            'recent_activity' => $this->getRecentActivity(),

            // Performance Metrics
            'performance_metrics' => $this->getPerformanceMetrics($thisMonth, $lastMonth),

            // Key Alerts
            'alerts' => $this->getKeyAlerts(),

            // Charts Data - ENHANCED WITH ALL TRANSACTION TYPES
            'charts_data' => $this->getChartsData(),

            // Quick Stats
            'quick_stats' => $this->getQuickStats($today),
        ];
    }

    /**
     * Get user statistics
     */
    private function getUserStats($today, $thisWeek, $thisMonth): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 'active')->count();
        $inactiveUsers = User::where('status', 'inactive')->count();
        $blockedUsers = User::where('status', 'blocked')->count();

        return [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'inactive' => $inactiveUsers,
            'blocked' => $blockedUsers,
            'verified' => User::verified()->count(),
            'kyc_verified' => User::kycVerified()->count(),
            'registrations' => [
                'today' => User::whereDate('created_at', $today)->count(),
                'this_week' => User::where('created_at', '>=', $thisWeek)->count(),
                'this_month' => User::where('created_at', '>=', $thisMonth)->count(),
            ],
            'activity' => [
                'online_now' => User::where('last_login_at', '>=', Carbon::now()->subMinutes(5))->count(),
                'today_logins' => User::whereDate('last_login_at', $today)->count(),
                'active_this_week' => User::where('last_login_at', '>=', $thisWeek)->count(),
            ],
        ];
    }

    /**
     * Get financial summary
     */
    private function getFinancialSummary($today, $thisMonth, $lastMonth): array
    {
        // Total Deposits
        $totalDeposits = Transaction::deposits()->completed()->sum('amount');
        $monthlyDeposits = Transaction::deposits()->completed()
            ->where('created_at', '>=', $thisMonth)->sum('amount');
        $todayDeposits = Transaction::deposits()->completed()
            ->whereDate('created_at', $today)->sum('amount');

        // Total Withdrawals
        $totalWithdrawals = Transaction::withdrawals()->completed()->sum('amount');
        $monthlyWithdrawals = Transaction::withdrawals()->completed()
            ->where('created_at', '>=', $thisMonth)->sum('amount');
        $pendingWithdrawals = Transaction::withdrawals()->pending()->sum('amount');

        // Commission Stats
        $totalCommissions = Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()->sum('amount');
        $monthlyCommissions = Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()
            ->where('created_at', '>=', $thisMonth)->sum('amount');

        // ROI Stats
        $totalROI = Transaction::where('type', Transaction::TYPE_ROI)->completed()->sum('amount');

        // Bonus Stats
        $totalBonus = Transaction::where('type', Transaction::TYPE_BONUS)->completed()->sum('amount');

        // Investment Stats
        $totalInvestments = Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()->sum('amount');

        // Platform Balance
        $totalBalance = CryptoWallet::sum('balance');
        $totalLockedBalance = $totalDeposits - $totalWithdrawals;
        $netProfit = $totalLockedBalance - $totalBalance;

        return [
            'deposits' => [
                'total' => $totalDeposits,
                'monthly' => $monthlyDeposits,
                'today' => $todayDeposits,
                'pending' => Transaction::deposits()->pending()->sum('amount'),
                'count' => Transaction::deposits()->completed()->count(),
            ],
            'withdrawals' => [
                'total' => $totalWithdrawals,
                'monthly' => $monthlyWithdrawals,
                'pending' => $pendingWithdrawals,
                'pending_count' => Transaction::withdrawals()->pending()->count(),
                'count' => Transaction::withdrawals()->completed()->count(),
            ],
            'commissions' => [
                'total' => $totalCommissions,
                'monthly' => $monthlyCommissions,
                'pending' => Transaction::where('type', Transaction::TYPE_COMMISSION)->pending()->sum('amount'),
            ],
            'roi' => [
                'total' => $totalROI,
                'monthly' => Transaction::where('type', Transaction::TYPE_ROI)->completed()
                    ->where('created_at', '>=', $thisMonth)->sum('amount'),
            ],
            'bonus' => [
                'total' => $totalBonus,
                'monthly' => Transaction::where('type', Transaction::TYPE_BONUS)->completed()
                    ->where('created_at', '>=', $thisMonth)->sum('amount'),
            ],
            'investments' => [
                'total' => $totalInvestments,
                'monthly' => Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()
                    ->where('created_at', '>=', $thisMonth)->sum('amount'),
            ],
            'platform' => [
                'total_balance' => $totalBalance,
                'available_balance' => $totalBalance,
                'locked_balance' => $totalLockedBalance,
                'profit' => $totalDeposits - $totalBalance - $totalWithdrawals,
            ],
        ];
    }

    /**
     * Get system health metrics
     */
    private function getSystemHealth(): array
    {
        $diskUsage = $this->getDiskUsage();
        $memoryUsage = $this->getMemoryUsage();

        return [
            'status' => 'healthy',
            'uptime' => $this->getSystemUptime(),
            'disk_usage' => $diskUsage,
            'memory_usage' => $memoryUsage,
            'database' => [
                'status' => $this->checkDatabaseConnection(),
                'queries_today' => 0,
                'connection_pool' => 'active',
            ],
            'queue' => [
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'processed_today' => 0,
            ],
            'cache' => [
                'status' => 'active',
                'hit_rate' => 95.5,
                'size' => '24.5 MB',
            ],
            'storage' => [
                'disk_free' => $this->formatBytes(disk_free_space('/')),
                'disk_total' => $this->formatBytes(disk_total_space('/')),
                'uploads_size' => '1.2 GB',
            ],
        ];
    }

    private function getRecentActivity(): array
    {
        // Fixed: Properly load user relationships with sponsor_id for sponsor chain
        $recentTransactions = Transaction::with([
            'user' => function ($query) {
                // ADD 'sponsor_id' to the select
                $query->select('id', 'first_name', 'last_name', 'email', 'username', 'sponsor_id');
            }
        ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Add sponsor chain to transactions
        $recentTransactions->transform(function ($transaction) {
            if ($transaction->user) {
                $transaction->user->sponsor_chain = $this->getSponsorChain($transaction->user);
            }
            return $transaction;
        });

        $recentUsers = User::with([
            'profile' => function ($query) {
                $query->select('id', 'user_id', 'kyc_status', 'country');
            }
        ])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentLogins = User::whereNotNull('last_login_at')
            ->select('id', 'first_name', 'last_name', 'email', 'last_login_at', 'last_login_ip')
            ->orderBy('last_login_at', 'desc')
            ->limit(5)
            ->get();

        return [
            'transactions' => $recentTransactions,
            'users' => $recentUsers,
            'logins' => $recentLogins,
            'leads' => Lead::with('createdBy')->orderBy('created_at', 'desc')->limit(5)->get(),
            'form_submissions' => FormSubmission::with('form')->orderBy('created_at', 'desc')->limit(5)->get(),
        ];
    }

    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($thisMonth, $lastMonth): array
    {
        $thisMonthUsers = User::where('created_at', '>=', $thisMonth)->count();
        $lastMonthUsers = User::where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $thisMonth)->count();

        $thisMonthDeposits = Transaction::deposits()->completed()
            ->where('created_at', '>=', $thisMonth)->sum('amount');
        $lastMonthDeposits = Transaction::deposits()->completed()
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $thisMonth)->sum('amount');

        return [
            'user_growth' => [
                'current' => $thisMonthUsers,
                'previous' => $lastMonthUsers,
                'percentage' => $lastMonthUsers > 0 ? (($thisMonthUsers - $lastMonthUsers) / $lastMonthUsers) * 100 : 0,
            ],
            'deposit_growth' => [
                'current' => $thisMonthDeposits,
                'previous' => $lastMonthDeposits,
                'percentage' => $lastMonthDeposits > 0 ? (($thisMonthDeposits - $lastMonthDeposits) / $lastMonthDeposits) * 100 : 0,
            ],
            'conversion_rate' => $this->getConversionRate(),
            'retention_rate' => $this->getRetentionRate(),
            'avg_transaction_value' => $this->getAvgTransactionValue(),
            'platform_growth' => $this->getPlatformGrowthRate($thisMonth, $lastMonth),
        ];
    }

    /**
     * Get key alerts
     */
    private function getKeyAlerts(): array
    {
        $alerts = [];

        // Pending withdrawals alert
        $pendingWithdrawals = Transaction::withdrawals()->pending()->count();
        if ($pendingWithdrawals > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Pending Withdrawals',
                'message' => "{$pendingWithdrawals} withdrawal(s) require attention",
                'icon' => 'iconamoon:warning-duotone',
                'action' => '#', // You can add proper routes later
                'priority' => 'high',
            ];
        }

        // New users alert
        $todayRegistrations = User::whereDate('created_at', Carbon::today())->count();
        if ($todayRegistrations > 10) {
            $alerts[] = [
                'type' => 'success',
                'title' => 'High Registration Rate',
                'message' => "{$todayRegistrations} new users registered today",
                'icon' => 'iconamoon:user-plus-duotone',
                'action' => '#',
                'priority' => 'medium',
            ];
        }

        // System health alerts
        $diskUsage = $this->getDiskUsage();
        if ($diskUsage > 80) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'High Disk Usage',
                'message' => "Disk usage is at {$diskUsage}%",
                'icon' => 'iconamoon:hard-drive-duotone',
                'action' => '#',
                'priority' => 'critical',
            ];
        }

        // Large pending transactions
        $largePendingAmount = Transaction::pending()->where('amount', '>', 1000)->sum('amount');
        if ($largePendingAmount > 10000) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Large Pending Transactions',
                'message' => '$' . number_format($largePendingAmount, 2) . ' in large pending transactions',
                'icon' => 'iconamoon:dollar-circle-duotone',
                'action' => '#',
                'priority' => 'high',
            ];
        }

        // KYC verifications needed
        $pendingKyc = User::whereHas('profile', function ($q) {
            $q->whereIn('kyc_status', ['pending', 'submitted', 'under_review']);
        })->count();

        if ($pendingKyc > 5) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'KYC Verifications Pending',
                'message' => "{$pendingKyc} KYC verifications need review",
                'icon' => 'iconamoon:profile-duotone',
                'action' => '#',
                'priority' => 'medium',
            ];
        }

        return $alerts;
    }

    /**
     * Get enhanced charts data for transactions - UPDATED TO INCLUDE ALL TRANSACTION TYPES
     */
    private function getChartsData(): array
    {
        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $last30Days->push([
                'date' => $date->format('Y-m-d'),
                'users' => User::whereDate('created_at', $date)->count(),
                'deposits' => (float) Transaction::deposits()->completed()->whereDate('created_at', $date)->sum('amount'),
                'withdrawals' => (float) Transaction::withdrawals()->completed()->whereDate('created_at', $date)->sum('amount'),
                'commissions' => (float) Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()->whereDate('created_at', $date)->sum('amount'),
                'roi' => (float) Transaction::where('type', Transaction::TYPE_ROI)->completed()->whereDate('created_at', $date)->sum('amount'),
                'bonus' => (float) Transaction::where('type', Transaction::TYPE_BONUS)->completed()->whereDate('created_at', $date)->sum('amount'),
                'investments' => (float) Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()->whereDate('created_at', $date)->sum('amount'),
            ]);
        }

        return [
            'user_registrations' => $last30Days->pluck('users')->toArray(),
            'daily_deposits' => $last30Days->pluck('deposits')->toArray(),
            'daily_withdrawals' => $last30Days->pluck('withdrawals')->toArray(),
            'daily_commissions' => $last30Days->pluck('commissions')->toArray(),
            'daily_roi' => $last30Days->pluck('roi')->toArray(),
            'daily_bonus' => $last30Days->pluck('bonus')->toArray(),
            'daily_investments' => $last30Days->pluck('investments')->toArray(),
            'labels' => $last30Days->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray(),
        ];
    }

    /**
     * Get quick stats
     */
    private function getQuickStats($today): array
    {
        return [
            'online_users' => User::where('last_login_at', '>=', Carbon::now()->subMinutes(5))->count(),
            'pending_kyc' => User::whereHas('profile', function ($q) {
                $q->whereIn('kyc_status', ['pending', 'submitted', 'under_review']);
            })->count(),
            'active_investments' => Transaction::where('type', Transaction::TYPE_INVESTMENT)
                ->completed()->whereDate('created_at', $today)->count(),
            'support_tickets' => 0, // Implement if you have a support system
            'total_transactions_today' => Transaction::whereDate('created_at', $today)->count(),
            'revenue_today' => Transaction::whereDate('created_at', $today)
                ->whereIn('type', [Transaction::TYPE_DEPOSIT, Transaction::TYPE_INVESTMENT])
                ->completed()->sum('amount'),
        ];
    }

    /**
     * ==========================================
     * TRANSACTION ANALYTICS API ENDPOINTS
     * ==========================================
     */

    /**
     * Get transaction chart data for specific period - UPDATED WITH ALL TRANSACTION TYPES
     */
    public function getTransactionChartData(Request $request)
    {
        $period = $request->get('period', '30d'); // 7d, 30d, 90d, 1y
        $days = $this->getDaysFromPeriod($period);

        $chartData = collect();
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $chartData->push([
                'date' => $date->format('Y-m-d'),
                'deposits' => (float) Transaction::deposits()->completed()->whereDate('created_at', $date)->sum('amount'),
                'withdrawals' => (float) Transaction::withdrawals()->completed()->whereDate('created_at', $date)->sum('amount'),
                'commissions' => (float) Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()->whereDate('created_at', $date)->sum('amount'),
                'roi' => (float) Transaction::where('type', Transaction::TYPE_ROI)->completed()->whereDate('created_at', $date)->sum('amount'),
                'bonus' => (float) Transaction::where('type', Transaction::TYPE_BONUS)->completed()->whereDate('created_at', $date)->sum('amount'),
                'investments' => (float) Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()->whereDate('created_at', $date)->sum('amount'),
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
                ],
                [
                    'name' => 'ROI',
                    'data' => $chartData->pluck('roi')->toArray()
                ],
                [
                    'name' => 'Bonus',
                    'data' => $chartData->pluck('bonus')->toArray()
                ],
                [
                    'name' => 'Investments',
                    'data' => $chartData->pluck('investments')->toArray()
                ]
            ],
            'categories' => $chartData->pluck('date')->map(function ($date) {
                return Carbon::parse($date)->format('M d');
            })->toArray()
        ]);
    }

    /**
     * Get transaction summary by type - UPDATED WITH ALL TRANSACTION TYPES
     */
    public function getTransactionSummary(Request $request)
    {
        $period = $request->get('period', '30d');
        $startDate = $this->getStartDateFromPeriod($period);

        $summary = [
            'deposits' => [
                'total' => (float) Transaction::deposits()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'count' => Transaction::deposits()->completed()->where('created_at', '>=', $startDate)->count(),
                'avg' => 0
            ],
            'withdrawals' => [
                'total' => (float) Transaction::withdrawals()->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'count' => Transaction::withdrawals()->completed()->where('created_at', '>=', $startDate)->count(),
                'avg' => 0
            ],
            'commissions' => [
                'total' => (float) Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'count' => Transaction::where('type', Transaction::TYPE_COMMISSION)->completed()->where('created_at', '>=', $startDate)->count(),
                'avg' => 0
            ],
            'roi' => [
                'total' => (float) Transaction::where('type', Transaction::TYPE_ROI)->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'count' => Transaction::where('type', Transaction::TYPE_ROI)->completed()->where('created_at', '>=', $startDate)->count(),
                'avg' => 0
            ],
            'bonus' => [
                'total' => (float) Transaction::where('type', Transaction::TYPE_BONUS)->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'count' => Transaction::where('type', Transaction::TYPE_BONUS)->completed()->where('created_at', '>=', $startDate)->count(),
                'avg' => 0
            ],
            'investments' => [
                'total' => (float) Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()->where('created_at', '>=', $startDate)->sum('amount'),
                'count' => Transaction::where('type', Transaction::TYPE_INVESTMENT)->completed()->where('created_at', '>=', $startDate)->count(),
                'avg' => 0
            ]
        ];

        // Calculate averages
        foreach ($summary as $type => &$data) {
            $data['avg'] = $data['count'] > 0 ? $data['total'] / $data['count'] : 0;
        }

        return response()->json($summary);
    }

    /**
     * API endpoint for live stats updates
     */
    public function getStats(Request $request)
    {
        $stats = [
            'online_users' => User::where('last_login_at', '>=', Carbon::now()->subMinutes(5))->count(),
            'pending_withdrawals' => Transaction::withdrawals()->pending()->count(),
            'today_registrations' => User::whereDate('created_at', Carbon::today())->count(),
            'today_deposits' => (float) Transaction::deposits()->completed()->whereDate('created_at', Carbon::today())->sum('amount'),
            'today_withdrawals' => (float) Transaction::withdrawals()->completed()->whereDate('created_at', Carbon::today())->sum('amount'),
            'pending_kyc' => User::whereHas('profile', function ($q) {
                $q->whereIn('kyc_status', ['pending', 'submitted', 'under_review']);
            })->count(),
            'active_sessions' => User::where('last_login_at', '>=', Carbon::now()->subHour())->count(),
        ];

        return response()->json($stats);
    }

    /**
     * System health check
     */
    public function systemHealth()
    {
        $health = $this->getSystemHealth();

        // Add real-time checks
        $health['timestamp'] = Carbon::now()->toISOString();
        $health['response_time'] = round((microtime(true) - LARAVEL_START) * 1000, 2) . 'ms';

        return response()->json($health);
    }

    /**
     * Export dashboard data
     */
    public function exportData(Request $request)
    {
        $data = $this->getDashboardData();

        // Add export metadata
        $export = [
            'exported_at' => Carbon::now()->toISOString(),
            'exported_by' => auth()->user()->full_name,
            'period' => $request->get('period', '30d'),
            'data' => $data
        ];

        $filename = 'admin-dashboard-' . Carbon::now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($export)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * ==========================================
     * HELPER METHODS
     * ==========================================
     */

    /**
     * Helper methods for period calculations
     */
    private function getDaysFromPeriod(string $period): int
    {
        switch ($period) {
            case '7d':
                return 7;
            case '30d':
                return 30;
            case '90d':
                return 90;
            case '1y':
                return 365;
            default:
                return 30;
        }
    }

    private function getStartDateFromPeriod(string $period): Carbon
    {
        switch ($period) {
            case '7d':
                return Carbon::now()->subDays(7);
            case '30d':
                return Carbon::now()->subDays(30);
            case '90d':
                return Carbon::now()->subDays(90);
            case '1y':
                return Carbon::now()->subYear();
            default:
                return Carbon::now()->subDays(30);
        }
    }

    /**
     * Helper methods for system metrics
     */
    private function getDiskUsage(): int
    {
        $bytes = disk_total_space('/') - disk_free_space('/');
        $total = disk_total_space('/');
        return $total > 0 ? round(($bytes / $total) * 100) : 0;
    }

    private function getMemoryUsage(): array
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);

        return [
            'current' => $this->formatBytes($memory),
            'peak' => $this->formatBytes($peak),
            'limit' => ini_get('memory_limit'),
            'usage_percentage' => $this->getMemoryUsagePercentage(),
        ];
    }

    private function getMemoryUsagePercentage(): float
    {
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $current = memory_get_usage(true);

        return $limit > 0 ? round(($current / $limit) * 100, 2) : 0;
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1')
            return PHP_INT_MAX;

        $value = (int) $limit;
        $unit = strtolower(substr($limit, -1));

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    private function getSystemUptime(): string
    {
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime)[0];
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            $minutes = floor(($uptime % 3600) / 60);
            return "{$days}d {$hours}h {$minutes}m";
        }
        return 'Unknown';
    }

    private function checkDatabaseConnection(): string
    {
        try {
            DB::connection()->getPdo();
            $startTime = microtime(true);
            DB::select('SELECT 1');
            $queryTime = round((microtime(true) - $startTime) * 1000, 2);
            return $queryTime < 100 ? 'excellent' : ($queryTime < 500 ? 'good' : 'slow');
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function getConversionRate(): float
    {
        $totalUsers = User::count();
        $usersWithDeposits = User::whereHas('transactions', function ($q) {
            $q->where('type', Transaction::TYPE_DEPOSIT)->where('status', 'completed');
        })->count();

        return $totalUsers > 0 ? round(($usersWithDeposits / $totalUsers) * 100, 2) : 0;
    }

    private function getRetentionRate(): float
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $usersFromMonth = User::where('created_at', '<=', $thirtyDaysAgo)->count();
        $activeFromMonth = User::where('created_at', '<=', $thirtyDaysAgo)
            ->where('last_login_at', '>=', $thirtyDaysAgo)->count();

        return $usersFromMonth > 0 ? round(($activeFromMonth / $usersFromMonth) * 100, 2) : 0;
    }

    private function getAvgTransactionValue(): float
    {
        return (float) Transaction::completed()->avg('amount') ?? 0;
    }

    private function getPlatformGrowthRate($thisMonth, $lastMonth): array
    {
        $thisMonthRevenue = Transaction::whereIn('type', [
            Transaction::TYPE_DEPOSIT,
            Transaction::TYPE_INVESTMENT
        ])->completed()->where('created_at', '>=', $thisMonth)->sum('amount');

        $lastMonthRevenue = Transaction::whereIn('type', [
            Transaction::TYPE_DEPOSIT,
            Transaction::TYPE_INVESTMENT
        ])->completed()
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $thisMonth)->sum('amount');

        return [
            'revenue_growth' => $lastMonthRevenue > 0 ?
                round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 2) : 0,
            'transaction_growth' => $this->getTransactionGrowthRate($thisMonth, $lastMonth),
        ];
    }

    private function getTransactionGrowthRate($thisMonth, $lastMonth): float
    {
        $thisMonthTx = Transaction::completed()->where('created_at', '>=', $thisMonth)->count();
        $lastMonthTx = Transaction::completed()
            ->where('created_at', '>=', $lastMonth)
            ->where('created_at', '<', $thisMonth)->count();

        return $lastMonthTx > 0 ? round((($thisMonthTx - $lastMonthTx) / $lastMonthTx) * 100, 2) : 0;
    }

    private function formatBytes($size, $precision = 2): string
    {
        if ($size == 0)
            return '0 B';

        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    /**
     * Get filtered transactions for dashboard (AJAX)
     */
    public function getFilteredTransactions(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        $query = Transaction::with([
            'user' => function ($query) {
                // ADD 'sponsor_id' to the select
                $query->select('id', 'first_name', 'last_name', 'email', 'username', 'sponsor_id');
            }
        ])->select('*');

        // Apply filters
        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Apply custom date range filter
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform transactions to include sponsor chain
        $transactions->getCollection()->transform(function ($transaction) {
            if ($transaction->user) {
                $transaction->user->sponsor_chain = $this->getSponsorChain($transaction->user);
            }
            return $transaction;
        });

        // Build transactions HTML
        $html = $this->buildTransactionsHTML($transactions);

        // Build pagination HTML
        $paginationHtml = $this->buildPaginationHTML($transactions);

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => $paginationHtml,
            'count' => $transactions->count(),
            'total' => $transactions->total(),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage()
        ]);
    }

    /**
     * Build transactions table HTML
     */
    private function buildTransactionsHTML($transactions)
    {
        if ($transactions->count() === 0) {
            return '<tr>
            <td colspan="7" class="text-center py-4">
                <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                <h6 class="text-muted">No Transactions Found</h6>
                <p class="text-muted mb-0">No transactions match the selected filters.</p>
            </td>
        </tr>';
        }

        $html = '';

        foreach ($transactions as $transaction) {
            // Determine badge colors based on type
            $typeColors = [
                'deposit' => 'success',
                'withdrawal' => 'warning',
                'commission' => 'primary',
                'roi' => 'info',
                'bonus' => 'secondary'
            ];
            $typeColor = $typeColors[$transaction->type] ?? 'dark';

            // Determine badge colors based on status
            $statusColors = [
                'completed' => 'success',
                'pending' => 'warning',
                'processing' => 'info',
                'failed' => 'danger'
            ];
            $statusColor = $statusColors[$transaction->status] ?? 'secondary';

            // Amount styling
            $amountClass = in_array($transaction->type, ['withdrawal']) ? 'text-danger' : 'text-success';
            $amountSign = in_array($transaction->type, ['withdrawal']) ? '-' : '+';

            // User info
            $userInitials = $transaction->user ? $transaction->user->initials : 'U';
            $userFullName = $transaction->user ? $transaction->user->full_name : 'Unknown User';
            $transactionIdShort = \Str::limit($transaction->transaction_id, 15);

            $html .= '<tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                        <span class="avatar-title text-white">' . e($userInitials) . '</span>
                    </div>
                    <div>
                        <h6 class="mb-0">' . e($userFullName) . '</h6>
                        <code class="small">' . e($transactionIdShort) . '...</code>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-' . $typeColor . '-subtle text-' . $typeColor . ' p-1">
                    ' . ucfirst($transaction->type) . '
                </span>
            </td>
            <td>
                <strong class="' . $amountClass . '">
                    ' . $amountSign . $transaction->formatted_amount . '
                </strong>
            </td>';

            // ADD THIS: Sponsor Chain Column
            $html .= '<td>';
            if ($transaction->user && !empty($transaction->user->sponsor_chain)) {
                $chain = collect($transaction->user->sponsor_chain);
                $firstLevel = $chain->first();
                $lastLevel = $chain->last();
                $hasMultipleLevels = $chain->count() > 1;

                $sponsorNames = $chain->map(function ($sponsor) {
                    return 'L' . $sponsor['level'] . ': ' . $sponsor['user']->first_name . ' ' . $sponsor['user']->last_name;
                })->implode(' → ');

                $html .= '<div class="small" title="' . e($sponsorNames) . '" style="cursor: help;">
                ' . \Str::limit($firstLevel['user']->first_name . ' ' . $firstLevel['user']->last_name, 12);

                if ($hasMultipleLevels) {
                    $html .= ' <span class="text-primary">...</span> ' .
                        \Str::limit($lastLevel['user']->first_name . ' ' . $lastLevel['user']->last_name, 12);
                }

                $html .= '<div class="mt-1">
                <span class="badge bg-info">' . $chain->count() . ' level' . ($chain->count() > 1 ? 's' : '') . '</span>
            </div></div>';
            } else {
                $html .= '<span class="text-muted small">Direct signup</span>';
            }
            $html .= '</td>';

            $html .= '<td>
                ' . $transaction->created_at->format('d M, y') . '
                <small class="text-muted d-block">' . $transaction->created_at->format('h:i:s A') . '</small>
            </td>
            <td>
                <span class="badge bg-' . $statusColor . '-subtle text-' . $statusColor . ' p-1">
                    ' . ucfirst($transaction->status) . '
                </span>
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        Actions
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="showTransactionDetails(\'' . $transaction->id . '\')">
                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                            </a>
                        </li>';

            if ($transaction->status !== 'completed') {
                $html .= '<li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateTransactionStatusDashboard(\'' . $transaction->id . '\', \'completed\')">
                                <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>Mark Completed
                            </a>
                        </li>';
            }

            $html .= '</ul>
                </div>
            </td>
        </tr>';
        }

        return $html;
    }

    /**
     * Build smart pagination HTML with ellipsis to prevent overflow
     */
    private function buildPaginationHTML($transactions)
    {
        if (!$transactions->hasPages()) {
            return '';
        }

        $currentPage = $transactions->currentPage();
        $lastPage = $transactions->lastPage();

        $html = '<div class="card-footer border-top border-light">
        <div class="align-items-center justify-content-between row text-center text-sm-start">
            <div class="col-sm">
                <div class="text-muted">
                    Showing
                    <span class="fw-semibold text-body">' . $transactions->firstItem() . '</span>
                    to
                    <span class="fw-semibold text-body">' . $transactions->lastItem() . '</span>
                    of
                    <span class="fw-semibold">' . $transactions->total() . '</span>
                    Transactions
                </div>
            </div>
            <div class="col-sm-auto mt-3 mt-sm-0">
                <ul class="pagination pagination-boxed pagination-sm mb-0 justify-content-center">';

        // Previous button
        if ($transactions->onFirstPage()) {
            $html .= '<li class="page-item disabled">
            <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
        </li>';
        } else {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadDashboardTransactionsPage(' . ($currentPage - 1) . ')">
                <i class="bx bxs-chevron-left"></i>
            </a>
        </li>';
        }

        // Smart pagination - calculate pages to show
        $pagesToShow = $this->calculatePagesToShow($currentPage, $lastPage);

        // Render page numbers
        foreach ($pagesToShow as $page) {
            if ($page === '...') {
                $html .= '<li class="page-item disabled">
                <span class="page-link">...</span>
            </li>';
            } elseif ($page == $currentPage) {
                $html .= '<li class="page-item active">
                <span class="page-link">' . $page . '</span>
            </li>';
            } else {
                $html .= '<li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="loadDashboardTransactionsPage(' . $page . ')">' . $page . '</a>
            </li>';
            }
        }

        // Next button
        if ($transactions->hasMorePages()) {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadDashboardTransactionsPage(' . ($currentPage + 1) . ')">
                <i class="bx bxs-chevron-right"></i>
            </a>
        </li>';
        } else {
            $html .= '<li class="page-item disabled">
            <span class="page-link"><i class="bx bxs-chevron-right"></i></span>
        </li>';
        }

        $html .= '</ul>
            </div>
        </div>
    </div>';

        return $html;
    }

    /**
     * Calculate which page numbers to show with ellipsis
     * Shows max 7 page buttons to prevent overflow
     */
    private function calculatePagesToShow($currentPage, $lastPage)
    {
        $pagesToShow = [];

        if ($lastPage <= 7) {
            // Show all pages if 7 or less
            return range(1, $lastPage);
        }

        // Always show first page
        $pagesToShow[] = 1;

        if ($currentPage > 4) {
            // Add ellipsis after first page
            $pagesToShow[] = '...';
        }

        // Calculate range around current page
        $start = max(2, $currentPage - 1);
        $end = min($lastPage - 1, $currentPage + 1);

        // Adjust if we're near the beginning
        if ($currentPage <= 4) {
            $start = 2;
            $end = min(6, $lastPage - 1);
        }

        // Adjust if we're near the end
        if ($currentPage >= $lastPage - 3) {
            $start = max(2, $lastPage - 5);
            $end = $lastPage - 1;
        }

        // Add middle pages
        for ($i = $start; $i <= $end; $i++) {
            $pagesToShow[] = $i;
        }

        if ($currentPage < $lastPage - 3) {
            // Add ellipsis before last page
            $pagesToShow[] = '...';
        }

        // Always show last page
        $pagesToShow[] = $lastPage;

        return $pagesToShow;
    }

    /**
     * Get filtered users for dashboard (AJAX)
     */
    public function getFilteredUsers(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        $query = User::with([
            'profile',
            'cryptoWallets' => function ($q) {
                $q->where('crypto_wallets.is_active', true)->with([
                    'cryptocurrency' => function ($subq) {
                        $subq->where('cryptocurrencies.is_active', true);
                    }
                ]);
            },
            'investments' => function ($q) {
                $q->select('user_id', 'status', 'amount', 'paid_return', 'created_at')
                    ->latest()
                    ->limit(3);
            }
        ])->select('id', 'first_name', 'last_name', 'email', 'username', 'status', 'created_at', 'last_login_at', 'sponsor_id', 'email_verified_at');

        // Apply filters
        if ($request->investment_status) {
            switch ($request->investment_status) {
                case 'has_investments':
                    $query->whereHas('investments');
                    break;
                case 'no_investments':
                    $query->whereDoesntHave('investments');
                    break;
                case 'active_investments':
                    $query->whereHas('investments', function ($q) {
                        $q->where('status', 'active');
                    });
                    break;
            }
        }

        if ($request->verification) {
            switch ($request->verification) {
                case 'email_verified':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'email_unverified':
                    $query->whereNull('email_verified_at');
                    break;
                case 'kyc_verified':
                    $query->whereHas('profile', function ($q) {
                        $q->where('kyc_status', 'verified');
                    });
                    break;
            }
        }

        // Apply status filter - NEW LOGIC: Active = has investments, Inactive = no investments
        if ($request->status) {
            switch ($request->status) {
                case 'active':
                    // Active = has investments
                    $query->whereHas('investments');
                    break;
                case 'inactive':
                    // Inactive = no investments
                    $query->whereDoesntHave('investments');
                    break;
                case 'blocked':
                    // Blocked = blocked status in database
                    $query->where('status', 'blocked');
                    break;
            }
        }

        // Apply custom date range filter
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // Search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Add sponsor chain and wallet data
        $users->getCollection()->transform(function ($user) {
            $user->sponsor_chain = $this->getSponsorChain($user);
            $user->total_wallet_balance_usd = $user->cryptoWallets
                ->where('is_active', true)
                ->sum(function ($wallet) {
                    return $wallet->balance * ($wallet->usd_rate ?? 0);
                });
            $user->primary_wallet = $user->cryptoWallets
                ->where('is_active', true)
                ->sortByDesc(function ($wallet) {
                    $priority = str_contains($wallet->currency, 'USDT') ? 1000000 : 0;
                    return $priority + ($wallet->balance * ($wallet->usd_rate ?? 1));
                })
                ->first();
            return $user;
        });

        // Build users HTML
        $html = $this->buildUsersHTML($users);

        // Build pagination HTML
        $paginationHtml = $this->buildUsersPaginationHTML($users);

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => $paginationHtml,
            'count' => $users->count(),
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage()
        ]);
    }

    /**
     * Build users table HTML
     */
    private function buildUsersHTML($users)
    {
        if ($users->count() === 0) {
            return '<tr>
            <td colspan="7" class="text-center py-4">
                <iconify-icon icon="iconamoon:profile-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                <h6 class="text-muted">No Users Found</h6>
                <p class="text-muted mb-0">No users match the selected filters.</p>
            </td>
        </tr>';
        }

        $html = '';

        foreach ($users as $user) {
            $userInitials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
            $statusColor = $user->status === 'active' ? 'success' : ($user->status === 'blocked' ? 'danger' : 'warning');

            // Investment stats
            $activeInvestments = $user->investments->where('status', 'active')->count();
            $totalInvested = $user->investments->sum('amount');
            $totalReturns = $user->investments->sum('paid_return');

            $html .= '<tr>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                        <span class="avatar-title text-white">' . e($userInitials) . '</span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="mb-0">' . e($user->full_name) . '</h6>';

            // Status icons
            if ($user->profile && $user->profile->kyc_status === 'verified') {
                $html .= '<iconify-icon icon="material-symbols:verified" class="text-success" style="font-size: 1rem;" title="KYC Verified"></iconify-icon>';
            }
            if ($user->investments && $user->investments->isNotEmpty()) {
                $html .= '<iconify-icon icon="iconamoon:coin-duotone" class="text-success" style="font-size: 1rem;" title="Has Investments"></iconify-icon>';
            }
            if ($user->hasVerifiedEmail()) {
                $html .= '<iconify-icon icon="material-symbols:mark-email-read-rounded" class="text-info" style="font-size: 0.9rem;" title="Email Verified"></iconify-icon>';
            }

            $html .= '</div>
                        <small class="text-muted">' . e($user->email) . '</small>
                    </div>
                </div>
            </td>
            <td>';

            // Investments column
            if ($user->investments && $user->investments->isNotEmpty()) {
                $html .= '<div class="small text-center">';
                if ($activeInvestments > 0) {
                    $html .= '<span class="badge bg-primary">' . $activeInvestments . ' Active</span> ';
                }
                $html .= '<div class="text-muted mt-1">
                <strong>$' . number_format($totalInvested, 2) . '</strong> invested';
                if ($totalReturns > 0) {
                    $html .= '<br><small class="text-success">$' . number_format($totalReturns, 2) . ' earned</small>';
                }
                $html .= '</div></div>';
            } else {
                $html .= '<div class="text-center">
                <iconify-icon icon="iconamoon:sign-minus-duotone" class="text-muted fs-5"></iconify-icon>
                <div class="small text-muted">No investments</div>
            </div>';
            }

            $html .= '</td>
            <td>';

            // Wallet balance
            if ($user->total_wallet_balance_usd > 0) {
                $html .= '<div class="text-center">
                <strong class="text-success">$' . number_format($user->total_wallet_balance_usd, 2) . '</strong>
            </div>';
            } else {
                $html .= '<div class="text-center text-muted">$0.00</div>';
            }

            $html .= '</td>
            <td>';

            // Sponsor chain
            if (!empty($user->sponsor_chain)) {
                $chain = collect($user->sponsor_chain);
                $firstLevel = $chain->first();
                $lastLevel = $chain->last();
                $hasMultipleLevels = $chain->count() > 1;

                $sponsorNames = $chain->map(function ($sponsor) {
                    return 'L' . $sponsor['level'] . ': ' . $sponsor['user']->first_name . ' ' . $sponsor['user']->last_name;
                })->implode(' → ');

                $html .= '<div class="small" title="' . e($sponsorNames) . '" style="cursor: help;">
                ' . \Str::limit($firstLevel['user']->first_name . ' ' . $firstLevel['user']->last_name, 12);

                if ($hasMultipleLevels) {
                    $html .= ' <span class="text-primary">...</span> ' .
                        \Str::limit($lastLevel['user']->first_name . ' ' . $lastLevel['user']->last_name, 12);
                }

                $html .= '<div class="mt-1">
                <span class="badge bg-info">' . $chain->count() . ' level' . ($chain->count() > 1 ? 's' : '') . '</span>
            </div></div>';
            } else {
                $html .= '<span class="text-muted small">Direct signup</span>';
            }

            $html .= '</td>
            <td>
                <div class="small">
                    <div>' . $user->created_at->format('M d, Y') . '</div>
                    <div class="text-muted">' . $user->created_at->diffForHumans() . '</div>
                </div>
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                        <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="' . route('admin.users.edit', $user->id) . '">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>View/Edit
                            </a>
                        </li>
                    </ul>
                </div>
            </td>
        </tr>';
        }

        return $html;
    }

    /**
     * Build users pagination HTML
     */
    private function buildUsersPaginationHTML($users)
    {
        if (!$users->hasPages()) {
            return '';
        }

        $currentPage = $users->currentPage();
        $lastPage = $users->lastPage();

        $html = '<div class="card-footer border-top">
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-muted small">
                Showing ' . $users->firstItem() . ' to ' . $users->lastItem() . ' of ' . $users->total() . ' users
            </div>
            <div>
                <ul class="pagination pagination-sm mb-0">';

        // Previous button
        if ($users->onFirstPage()) {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bx bxs-chevron-left"></i></span></li>';
        } else {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadDashboardUsersPage(' . ($currentPage - 1) . ')">
                <i class="bx bxs-chevron-left"></i>
            </a>
        </li>';
        }

        // Page numbers with ellipsis
        $pagesToShow = $this->calculatePagesToShow($currentPage, $lastPage);

        foreach ($pagesToShow as $page) {
            if ($page === '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            } elseif ($page == $currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
            } else {
                $html .= '<li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="loadDashboardUsersPage(' . $page . ')">' . $page . '</a>
            </li>';
            }
        }

        // Next button
        if ($users->hasMorePages()) {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadDashboardUsersPage(' . ($currentPage + 1) . ')">
                <i class="bx bxs-chevron-right"></i>
            </a>
        </li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link"><i class="bx bxs-chevron-right"></i></span></li>';
        }

        $html .= '</ul>
            </div>
        </div>
    </div>';

        return $html;
    }

    /**
     * Get sponsor chain for a user
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
}