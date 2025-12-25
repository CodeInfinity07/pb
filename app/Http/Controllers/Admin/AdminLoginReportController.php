<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminLoginReportController extends Controller
{
    /**
     * Display login reports
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get filter parameters
        $userId = $request->get('user_id');
        $status = $request->get('status');
        $dateRange = $request->get('date_range', '30');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');

        // Build query
        $query = LoginLog::with(['user:id,first_name,last_name,email,username'])
            ->select('*');

        // Apply filters
        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($status === 'successful') {
            $query->where('is_successful', true);
        } elseif ($status === 'failed') {
            $query->where('is_successful', false);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
            });
        }

        // Apply date range filter
        if ($startDate && $endDate) {
            $query->whereBetween('login_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($dateRange && $dateRange !== 'all') {
            $days = (int) $dateRange;
            $query->where('login_at', '>=', now()->subDays($days));
        }

        // Get login logs with pagination
        $loginLogs = $query->orderBy('login_at', 'desc')->paginate(25);

        // Get summary statistics
        $summaryData = $this->getSummaryData($request);

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        return view('admin.reports.login', compact(
            'user',
            'loginLogs',
            'summaryData',
            'filterOptions'
        ));
    }

    /**
     * Get filtered login logs via AJAX
     */
    public function getFilteredLoginLogsAjax(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $query = LoginLog::with([
            'user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'username');
            }
        ])->select('*');

        // Apply filters
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->status === 'successful') {
            $query->where('is_successful', true);
        } elseif ($request->status === 'failed') {
            $query->where('is_successful', false);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Apply date range filter
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('login_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->date_range && $request->date_range !== 'all' && $request->date_range !== 'custom') {
            $days = (int) $request->date_range;
            $query->where('login_at', '>=', now()->subDays($days));
        }

        $loginLogs = $query->orderBy('login_at', 'desc')->paginate($perPage);

        // Build HTML
        $html = $this->buildLoginLogsTableHTML($loginLogs);

        // Build pagination HTML
        $paginationHtml = $this->buildLoginLogsPaginationHTML($loginLogs);

        // Get updated summary
        $summaryData = $this->getSummaryData($request);

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => $paginationHtml,
            'summary' => $summaryData,
            'count' => $loginLogs->count(),
            'total' => $loginLogs->total(),
            'current_page' => $loginLogs->currentPage(),
            'last_page' => $loginLogs->lastPage()
        ]);
    }

    /**
     * Get summary statistics
     */
    private function getSummaryData(Request $request): array
    {
        $baseQuery = LoginLog::query();

        // Apply same filters as main query
        if ($request->get('user_id')) {
            $baseQuery->where('user_id', $request->get('user_id'));
        }

        if ($request->get('status') === 'successful') {
            $baseQuery->where('is_successful', true);
        } elseif ($request->get('status') === 'failed') {
            $baseQuery->where('is_successful', false);
        }

        if ($request->get('search')) {
            $search = $request->get('search');
            $baseQuery->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->get('date_range') && $request->get('date_range') !== 'all') {
            $days = (int) $request->get('date_range');
            $baseQuery->where('login_at', '>=', now()->subDays($days));
        }

        $totalLogins = (clone $baseQuery)->count();
        $successfulLogins = (clone $baseQuery)->where('is_successful', true)->count();
        $failedLogins = (clone $baseQuery)->where('is_successful', false)->count();
        $uniqueUsers = (clone $baseQuery)->distinct('user_id')->count('user_id');
        $uniqueIPs = (clone $baseQuery)->distinct('ip_address')->count('ip_address');
        $todayLogins = LoginLog::whereDate('login_at', today())->count();

        return [
            'total_logins' => $totalLogins,
            'successful_logins' => $successfulLogins,
            'failed_logins' => $failedLogins,
            'success_rate' => $totalLogins > 0 ? round(($successfulLogins / $totalLogins) * 100, 2) : 0,
            'unique_users' => $uniqueUsers,
            'unique_ips' => $uniqueIPs,
            'today_logins' => $todayLogins,
            'avg_daily_logins' => $this->getAverageDailyLogins($request)
        ];
    }

    /**
     * Get average daily logins
     */
    private function getAverageDailyLogins(Request $request): float
    {
        $days = (int) ($request->get('date_range', 30));
        $totalLogins = LoginLog::where('login_at', '>=', now()->subDays($days))->count();
        
        return $days > 0 ? round($totalLogins / $days, 1) : 0;
    }

    /**
     * Get filter options
     */
    private function getFilterOptions(): array
    {
        return [
            'statuses' => [
                'successful' => 'Successful',
                'failed' => 'Failed'
            ],
            'date_ranges' => [
                '7' => 'Last 7 days',
                '30' => 'Last 30 days',
                '90' => 'Last 3 months',
                '365' => 'Last year',
                'all' => 'All time'
            ]
        ];
    }

    /**
     * Build login logs table HTML for AJAX
     */
    private function buildLoginLogsTableHTML($loginLogs)
    {
        if ($loginLogs->count() === 0) {
            return '<tr>
                <td colspan="7" class="text-center py-5">
                    <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Login Logs Found</h6>
                    <p class="text-muted mb-0">No login activity matches your filters.</p>
                </td>
            </tr>';
        }

        $html = '';

        foreach ($loginLogs as $log) {
            $statusBadge = $log->is_successful 
                ? '<span class="badge bg-success-subtle text-success">Success</span>'
                : '<span class="badge bg-danger-subtle text-danger">Failed</span>';

            $userInitials = $log->user ? $log->user->initials : 'U';
            $userFullName = $log->user ? $log->user->full_name : 'Unknown User';
            $userEmail = $log->user ? $log->user->email : 'Unknown';

            $location = trim(($log->city ? $log->city . ', ' : '') . ($log->country ?? ''));
            $location = $location ?: 'Unknown';

            $html .= '<tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                            <span class="avatar-title text-white">' . e($userInitials) . '</span>
                        </div>
                        <div>
                            <h6 class="mb-0">' . e($userFullName) . '</h6>
                            <small class="text-muted">' . e($userEmail) . '</small>
                        </div>
                    </div>
                </td>
                <td>
                    <code class="small">' . e($log->ip_address) . '</code>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="' . $log->device_icon . '" class="me-2 fs-18"></iconify-icon>
                        <div>
                            <div class="fw-medium">' . e($log->browser ?? 'Unknown') . '</div>
                            <small class="text-muted">' . e($log->platform ?? 'Unknown') . '</small>
                        </div>
                    </div>
                </td>
                <td>' . e($location) . '</td>
                <td>
                    ' . $log->login_at->format('d M, y') . '
                    <small class="text-muted d-block">' . $log->login_at->format('h:i:s A') . '</small>
                </td>
                <td>' . $statusBadge . '</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="showLoginDetails(' . $log->id . ')" title="View Details">
                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                    </button>
                </td>
            </tr>';
        }

        return $html;
    }

    /**
     * Build pagination HTML
     */
    private function buildLoginLogsPaginationHTML($loginLogs)
    {
        if (!$loginLogs->hasPages()) {
            return '';
        }

        $currentPage = $loginLogs->currentPage();
        $lastPage = $loginLogs->lastPage();

        $html = '<div class="card-footer border-top border-light">
            <div class="align-items-center justify-content-between row text-center text-sm-start">
                <div class="col-sm">
                    <div class="text-muted">
                        Showing
                        <span class="fw-semibold text-body">' . $loginLogs->firstItem() . '</span>
                        to
                        <span class="fw-semibold text-body">' . $loginLogs->lastItem() . '</span>
                        of
                        <span class="fw-semibold">' . $loginLogs->total() . '</span>
                        Login Logs
                    </div>
                </div>
                <div class="col-sm-auto mt-3 mt-sm-0">
                    <ul class="pagination pagination-boxed pagination-sm mb-0 justify-content-center">';

        // Previous button
        if ($loginLogs->onFirstPage()) {
            $html .= '<li class="page-item disabled">
                <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
            </li>';
        } else {
            $html .= '<li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="loadLoginLogsPage(' . ($currentPage - 1) . ')">
                    <i class="bx bxs-chevron-left"></i>
                </a>
            </li>';
        }

        // Page numbers
        $pagesToShow = $this->calculatePagesToShow($currentPage, $lastPage);

        foreach ($pagesToShow as $page) {
            if ($page === '...') {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            } elseif ($page == $currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $page . '</span></li>';
            } else {
                $html .= '<li class="page-item">
                    <a class="page-link" href="javascript:void(0)" onclick="loadLoginLogsPage(' . $page . ')">' . $page . '</a>
                </li>';
            }
        }

        // Next button
        if ($loginLogs->hasMorePages()) {
            $html .= '<li class="page-item">
                <a class="page-link" href="javascript:void(0)" onclick="loadLoginLogsPage(' . ($currentPage + 1) . ')">
                    <i class="bx bxs-chevron-right"></i>
                </a>
            </li>';
        } else {
            $html .= '<li class="page-item disabled">
                <span class="page-link"><i class="bx bxs-chevron-right"></i></span>
            </li>';
        }

        $html .= '</ul></div></div></div>';

        return $html;
    }

    /**
     * Calculate pages to show
     */
    private function calculatePagesToShow($currentPage, $lastPage)
    {
        $pagesToShow = [];

        if ($lastPage <= 7) {
            return range(1, $lastPage);
        }

        $pagesToShow[] = 1;

        if ($currentPage > 4) {
            $pagesToShow[] = '...';
        }

        $start = max(2, $currentPage - 1);
        $end = min($lastPage - 1, $currentPage + 1);

        if ($currentPage <= 4) {
            $start = 2;
            $end = min(6, $lastPage - 1);
        }

        if ($currentPage >= $lastPage - 3) {
            $start = max(2, $lastPage - 5);
            $end = $lastPage - 1;
        }

        for ($i = $start; $i <= $end; $i++) {
            $pagesToShow[] = $i;
        }

        if ($currentPage < $lastPage - 3) {
            $pagesToShow[] = '...';
        }

        $pagesToShow[] = $lastPage;

        return $pagesToShow;
    }

    /**
     * Show login log details
     */
    public function show($id)
    {
        $loginLog = LoginLog::with('user')->findOrFail($id);

        $html = view('admin.reports.login-details', compact('loginLog'))->render();

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }

    /**
     * Search users for filter
     */
    public function searchUsers(Request $request)
    {
        $search = $request->get('q', '');

        $users = User::where(function ($query) use ($search) {
            $query->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        })
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email', 'username']);

        return response()->json([
            'success' => true,
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->full_name . ' (' . $user->email . ')',
                    'email' => $user->email
                ];
            })
        ]);
    }

    /**
     * Export login logs
     */
    public function export(Request $request)
    {
        try {
            $query = LoginLog::with('user');

            // Apply same filters
            if ($request->get('user_id')) {
                $query->where('user_id', $request->get('user_id'));
            }

            if ($request->get('status') === 'successful') {
                $query->where('is_successful', true);
            } elseif ($request->get('status') === 'failed') {
                $query->where('is_successful', false);
            }

            if ($request->get('date_range') && $request->get('date_range') !== 'all') {
                $days = (int) $request->get('date_range');
                $query->where('login_at', '>=', now()->subDays($days));
            }

            $loginLogs = $query->orderBy('login_at', 'desc')->get();

            $exportData = [
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()->full_name,
                'total_records' => $loginLogs->count(),
                'filters' => $request->only(['user_id', 'status', 'date_range']),
                'data' => $loginLogs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'user_name' => $log->user ? $log->user->full_name : 'Unknown',
                        'user_email' => $log->user ? $log->user->email : 'Unknown',
                        'ip_address' => $log->ip_address,
                        'device_type' => $log->device_type,
                        'browser' => $log->browser,
                        'platform' => $log->platform,
                        'location' => trim(($log->city ? $log->city . ', ' : '') . ($log->country ?? '')),
                        'is_successful' => $log->is_successful ? 'Yes' : 'No',
                        'failure_reason' => $log->failure_reason,
                        'login_at' => $log->login_at->toISOString(),
                        'logout_at' => $log->logout_at?->toISOString(),
                        'session_duration' => $log->session_duration,
                    ];
                })
            ];

            $filename = 'login-report-' . now()->format('Y-m-d-H-i-s') . '.json';

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get analytics data
     */
    public function analytics(Request $request)
    {
        $days = $request->get('days', 30);

        $analytics = [
            'login_trends' => $this->getLoginTrends($days),
            'top_devices' => $this->getTopDevices($days),
            'top_browsers' => $this->getTopBrowsers($days),
            'top_locations' => $this->getTopLocations($days),
            'hourly_distribution' => $this->getHourlyDistribution($days),
            'failed_login_trends' => $this->getFailedLoginTrends($days)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get login trends
     */
    private function getLoginTrends($days)
    {
        return LoginLog::where('login_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(login_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get top devices
     */
    private function getTopDevices($days)
    {
        return LoginLog::where('login_at', '>=', now()->subDays($days))
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get top browsers
     */
    private function getTopBrowsers($days)
    {
        return LoginLog::where('login_at', '>=', now()->subDays($days))
            ->selectRaw('browser, COUNT(*) as count')
            ->groupBy('browser')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get top locations
     */
    private function getTopLocations($days)
    {
        return LoginLog::where('login_at', '>=', now()->subDays($days))
            ->whereNotNull('country')
            ->selectRaw('country, COUNT(*) as count')
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get hourly distribution
     */
    private function getHourlyDistribution($days)
    {
        return LoginLog::where('login_at', '>=', now()->subDays($days))
            ->selectRaw('HOUR(login_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    /**
     * Get failed login trends
     */
    private function getFailedLoginTrends($days)
    {
        return LoginLog::where('login_at', '>=', now()->subDays($days))
            ->where('is_successful', false)
            ->selectRaw('DATE(login_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}