<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminTransactionController extends Controller
{
    /**
     * Display transactions listing with filters and statistics
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $type = $request->get('type');
        $status = $request->get('status');
        $currency = $request->get('currency');
        $search = $request->get('search');
        $dateRange = $request->get('date_range', '30');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Build query with relationships
        $query = Transaction::with(['user:id,first_name,last_name,email,username', 'processedBy:id,first_name,last_name'])
            ->select('*');

        // Apply filters
        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($currency) {
            $query->where('currency', $currency);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('crypto_address', 'like', "%{$search}%")
                    ->orWhere('crypto_txid', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
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
            // Custom date range
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($dateRange && $dateRange !== 'all') {
            // Predefined date range
            $days = (int) $dateRange;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        // Get transactions with pagination
        $transactions = $query->orderBy('created_at', 'desc')->paginate(25);

        // Get summary statistics
        $summaryData = $this->getTransactionSummaryData($request);

        // Get filter options
        $filterOptions = $this->getFilterOptions();

        return view('admin.finance.transactions.index', compact(
            'user',
            'transactions',
            'summaryData',
            'filterOptions'
        ));
    }

    /**
     * Show detailed transaction information
     */
    public function show($id)
    {
        $transaction = Transaction::with([
            'user',
            'processedBy'
        ])->findOrFail($id);

        $transactionDetails = $this->getTransactionDetails($transaction);

        return response()->json([
            'success' => true,
            'html' => view('admin.finance.transactions.details', compact('transaction', 'transactionDetails'))->render()
        ]);
    }

    /**
     * Update transaction status
     */
    public function updateStatus(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,processing,completed,failed,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        $oldStatus = $transaction->status;
        $newStatus = $request->status;

        // Update transaction
        $updateData = [
            'status' => $newStatus,
        ];

        // Set processed timestamp and admin for completed/failed/cancelled
        if (in_array($newStatus, ['completed', 'failed', 'cancelled']) && !$transaction->processed_at) {
            $updateData['processed_at'] = now();
            $updateData['processed_by'] = Auth::id();
        }

        $transaction->update($updateData);

        // Handle balance updates for completed transactions
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $this->handleCompletedTransaction($transaction);
        }

        // Log the status change
        \Log::info('Transaction status updated', [
            'transaction_id' => $transaction->id,
            'updated_by' => Auth::id(),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $request->notes
        ]);

        return response()->json([
            'success' => true,
            'message' => "Transaction status updated to " . ucfirst($newStatus)
        ]);
    }

    /**
     * Bulk update transaction statuses
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array|min:1',
            'transaction_ids.*' => 'exists:transactions,id',
            'status' => 'required|in:pending,processing,completed,failed,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        $transactionIds = $request->transaction_ids;
        $newStatus = $request->status;
        $processedCount = 0;

        try {
            DB::beginTransaction();

            foreach ($transactionIds as $transactionId) {
                $transaction = Transaction::find($transactionId);
                if (!$transaction)
                    continue;

                $oldStatus = $transaction->status;

                // Update transaction
                $updateData = ['status' => $newStatus];

                // Set processed info for final statuses
                if (in_array($newStatus, ['completed', 'failed', 'cancelled']) && !$transaction->processed_at) {
                    $updateData['processed_at'] = now();
                    $updateData['processed_by'] = Auth::id();
                }

                $transaction->update($updateData);

                // Handle balance updates for completed transactions
                if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                    $this->handleCompletedTransaction($transaction);
                }

                $processedCount++;
            }

            DB::commit();

            // Log bulk update
            \Log::info('Bulk transaction status update', [
                'updated_by' => Auth::id(),
                'transaction_count' => $processedCount,
                'new_status' => $newStatus,
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$processedCount} transactions to " . ucfirst($newStatus)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Bulk transaction update failed', [
                'error' => $e->getMessage(),
                'transaction_ids' => $transactionIds
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update transactions: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export transactions data
     */
    public function export(Request $request)
    {
        try {
            // Apply same filters as index
            $query = Transaction::with(['user', 'processedBy']);

            if ($request->get('type')) {
                $query->where('type', $request->get('type'));
            }
            if ($request->get('status')) {
                $query->where('status', $request->get('status'));
            }
            if ($request->get('currency')) {
                $query->where('currency', $request->get('currency'));
            }
            if ($request->get('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('crypto_address', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->get('date_range') && $request->get('date_range') !== 'all') {
                $days = (int) $request->get('date_range');
                $query->where('created_at', '>=', now()->subDays($days));
            }

            $transactions = $query->orderBy('created_at', 'desc')->get();

            $exportData = [
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()->full_name,
                'total_transactions' => $transactions->count(),
                'filters' => $request->only(['type', 'status', 'currency', 'search', 'date_range']),
                'summary' => [
                    'total_amount' => $transactions->sum('amount'),
                    'total_deposits' => $transactions->where('type', 'deposit')->sum('amount'),
                    'total_withdrawals' => $transactions->where('type', 'withdrawal')->sum('amount'),
                    'completed_count' => $transactions->where('status', 'completed')->count(),
                    'pending_count' => $transactions->where('status', 'pending')->count(),
                ],
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'transaction_id' => $transaction->transaction_id,
                        'user_name' => $transaction->user ? $transaction->user->full_name : 'Unknown',
                        'user_email' => $transaction->user ? $transaction->user->email : 'Unknown',
                        'type' => $transaction->type,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'status' => $transaction->status,
                        'payment_method' => $transaction->payment_method,
                        'crypto_address' => $transaction->crypto_address,
                        'crypto_txid' => $transaction->crypto_txid,
                        'description' => $transaction->description,
                        'created_at' => $transaction->created_at->toISOString(),
                        'processed_at' => $transaction->processed_at?->toISOString(),
                        'processed_by' => $transaction->processedBy ? $transaction->processedBy->full_name : null,
                    ];
                })
            ];

            $filename = 'transactions-export-' . now()->format('Y-m-d-H-i-s') . '.json';

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export transactions: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get transaction analytics data
     */
    public function analytics(Request $request)
    {
        $days = $request->get('days', 30);

        $analytics = [
            'chart_data' => $this->getChartData($days),
            'summary_stats' => $this->getAnalyticsSummary($days),
            'top_users' => $this->getTopUsers($days),
            'currency_breakdown' => $this->getCurrencyBreakdown($days)
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get transaction summary statistics
     */
    private function getTransactionSummaryData($request): array
    {
        $baseQuery = Transaction::query();

        // Apply same filters as main query for consistent stats
        if ($request->get('type')) {
            $baseQuery->where('type', $request->get('type'));
        }
        if ($request->get('status')) {
            $baseQuery->where('status', $request->get('status'));
        }
        if ($request->get('currency')) {
            $baseQuery->where('currency', $request->get('currency'));
        }
        if ($request->get('search')) {
            $search = $request->get('search');
            $baseQuery->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($request->get('date_range') && $request->get('date_range') !== 'all') {
            $days = (int) $request->get('date_range');
            $baseQuery->where('created_at', '>=', now()->subDays($days));
        }

        return [
            'total_transactions' => (clone $baseQuery)->count(),
            'total_amount' => (clone $baseQuery)->sum('amount'),
            'completed_transactions' => (clone $baseQuery)->where('status', 'completed')->count(),
            'pending_transactions' => (clone $baseQuery)->where('status', 'pending')->count(),
            'failed_transactions' => (clone $baseQuery)->where('status', 'failed')->count(),
            'total_deposits' => (clone $baseQuery)->where('type', 'deposit')->sum('amount'),
            'total_withdrawals' => (clone $baseQuery)->where('type', 'withdrawal')->sum('amount'),
            'today_transactions' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'today_volume' => (clone $baseQuery)->whereDate('created_at', today())->sum('amount'),
        ];
    }

    /**
     * Get filter options for dropdowns
     */
    private function getFilterOptions(): array
    {
        return [
            'types' => [
                'deposit' => 'Deposits',
                'withdrawal' => 'Withdrawals',
                'commission' => 'Commissions',
                'roi' => 'ROI',
                'investment' => 'Investments',
                'bonus' => 'Bonuses',
                'credit_adjustment' => 'Credit Adjustments',
                'debit_adjustment' => 'Debit Adjustments'
            ],
            'statuses' => [
                'pending' => 'Pending',
                'processing' => 'Processing',
                'completed' => 'Completed',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled'
            ],
            'currencies' => Transaction::distinct()->pluck('currency')->filter()->sort()->values()->toArray(),
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
     * Get detailed transaction information
     */
    private function getTransactionDetails($transaction): array
    {
        return [
            'basic_info' => [
                'transaction_id' => $transaction->transaction_id,
                'type' => ucfirst($transaction->type),
                'amount' => $transaction->formatted_amount,
                'status' => ucfirst($transaction->status),
                'currency' => $transaction->currency,
                'created_at' => $transaction->created_at->format('M d, Y \a\t g:i A'),
                'processed_at' => $transaction->processed_at ? $transaction->processed_at->format('M d, Y \a\t g:i A') : 'Not processed',
            ],
            'user_info' => [
                'name' => $transaction->user ? $transaction->user->full_name : 'Unknown User',
                'email' => $transaction->user ? $transaction->user->email : 'Unknown',
                'user_id' => $transaction->user_id,
            ],
            'payment_info' => [
                'payment_method' => $transaction->payment_method ?: 'Not specified',
                'crypto_address' => $transaction->crypto_address ?: 'N/A',
                'crypto_txid' => $transaction->crypto_txid ?: 'N/A',
                'description' => $transaction->description ?: $transaction->display_description,
            ],
            'admin_info' => [
                'processed_by' => $transaction->processedBy ? $transaction->processedBy->full_name : 'System',
                'metadata' => $transaction->metadata,
            ]
        ];
    }

    /**
     * Handle completed transaction balance updates
     */
    private function handleCompletedTransaction($transaction)
    {
        if (!$transaction->user)
            return;

        $accountBalance = $transaction->user->accountBalance;
        if (!$accountBalance) {
            $accountBalance = $transaction->user->accountBalance()->create([
                'balance' => 0,
                'locked_balance' => 0
            ]);
        }

        switch ($transaction->type) {
            case 'deposit':
            case 'commission':
            case 'roi':
            case 'bonus':
            case 'credit_adjustment':
                $accountBalance->increment('balance', $transaction->amount);
                break;

            case 'withdrawal':
            case 'debit_adjustment':
                $accountBalance->decrement('balance', $transaction->amount);
                break;
        }
    }

    /**
     * Get chart data for analytics
     */
    private function getChartData($days): array
    {
        $startDate = now()->subDays($days);

        $chartData = Transaction::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        return $chartData->map(function ($dayData, $date) {
            return [
                'date' => $date,
                'deposits' => $dayData->where('type', 'deposit')->sum('total') ?: 0,
                'withdrawals' => $dayData->where('type', 'withdrawal')->sum('total') ?: 0,
                'count' => $dayData->sum('count'),
            ];
        })->values()->toArray();
    }

    /**
     * Get analytics summary
     */
    private function getAnalyticsSummary($days): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_volume' => Transaction::where('created_at', '>=', $startDate)->sum('amount'),
            'transaction_count' => Transaction::where('created_at', '>=', $startDate)->count(),
            'avg_transaction' => Transaction::where('created_at', '>=', $startDate)->avg('amount') ?: 0,
            'completion_rate' => $this->getCompletionRate($startDate),
        ];
    }

    /**
     * Get top users by transaction volume
     */
    private function getTopUsers($days): array
    {
        $startDate = now()->subDays($days);

        return Transaction::with('user')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('user_id, COUNT(*) as transaction_count, SUM(amount) as total_amount')
            ->groupBy('user_id')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'user_name' => $item->user ? $item->user->full_name : 'Unknown',
                    'transaction_count' => $item->transaction_count,
                    'total_amount' => $item->total_amount,
                ];
            })
            ->toArray();
    }

    /**
     * Get currency breakdown
     */
    private function getCurrencyBreakdown($days): array
    {
        $startDate = now()->subDays($days);

        return Transaction::where('created_at', '>=', $startDate)
            ->selectRaw('currency, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('currency')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get completion rate
     */
    private function getCompletionRate($startDate): float
    {
        $total = Transaction::where('created_at', '>=', $startDate)->count();
        if ($total === 0)
            return 0;

        $completed = Transaction::where('created_at', '>=', $startDate)
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 2);
    }
    /**
     * Get filtered transactions via AJAX
     */
    public function getFilteredTransactionsAjax(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $query = Transaction::with([
            'user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'username');
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
        } elseif ($request->date_range && $request->date_range !== 'all' && $request->date_range !== 'custom') {
            $days = (int) $request->date_range;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Build transactions HTML
        $html = $this->buildTransactionsTableHTML($transactions);

        // Build pagination HTML
        $paginationHtml = $this->buildTransactionsPaginationHTML($transactions);

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
     * Build transactions table HTML for AJAX
     */
    private function buildTransactionsTableHTML($transactions)
    {
        if ($transactions->count() === 0) {
            return '<tr>
            <td colspan="6" class="text-center py-4">
                <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                <h6 class="text-muted">No Transactions Found</h6>
                <p class="text-muted mb-0">No transactions match the selected filters.</p>
            </td>
        </tr>';
        }

        $html = '';

        foreach ($transactions as $transaction) {
            $typeColors = [
                'deposit' => 'success',
                'withdrawal' => 'warning',
                'commission' => 'primary',
                'roi' => 'info',
                'bonus' => 'secondary'
            ];
            $typeColor = $typeColors[$transaction->type] ?? 'dark';

            $statusColors = [
                'completed' => 'success',
                'pending' => 'warning',
                'processing' => 'info',
                'failed' => 'danger'
            ];
            $statusColor = $statusColors[$transaction->status] ?? 'secondary';

            $amountClass = in_array($transaction->type, ['withdrawal']) ? 'text-danger' : 'text-success';
            $amountSign = in_array($transaction->type, ['withdrawal']) ? '-' : '+';

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
            </td>
            <td>
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
                            <a class="dropdown-item" href="javascript:void(0)" onclick="showDetails(\'' . $transaction->id . '\')">
                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                            </a>
                        </li>';

            if ($transaction->status !== 'completed') {
                $html .= '<li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateTransactionStatus(\'' . $transaction->id . '\', \'completed\')">
                                <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>Mark Completed
                            </a>
                        </li>';
            }

            if ($transaction->status === 'pending') {
                $html .= '<li>
                            <a class="dropdown-item text-info" href="javascript:void(0)" onclick="updateTransactionStatus(\'' . $transaction->id . '\', \'processing\')">
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>Mark Processing
                            </a>
                        </li>';
            }

            $html .= '<li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="showStatusModal(\'' . $transaction->id . '\', \'' . $transaction->status . '\')">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Change Status
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
     * Build pagination HTML for transactions AJAX
     */
    private function buildTransactionsPaginationHTML($transactions)
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
            <a class="page-link" href="javascript:void(0)" onclick="loadTransactionsPage(' . ($currentPage - 1) . ')">
                <i class="bx bxs-chevron-left"></i>
            </a>
        </li>';
        }

        // Smart pagination
        $pagesToShow = $this->calculateTransactionsPagesToShow($currentPage, $lastPage);

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
                <a class="page-link" href="javascript:void(0)" onclick="loadTransactionsPage(' . $page . ')">' . $page . '</a>
            </li>';
            }
        }

        // Next button
        if ($transactions->hasMorePages()) {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadTransactionsPage(' . ($currentPage + 1) . ')">
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
     * Calculate pages to show for transactions
     */
    private function calculateTransactionsPagesToShow($currentPage, $lastPage)
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
     * Display pending withdrawals
     */
    public function pendingWithdrawals(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $dateRange = $request->get('date_range', '30');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $search = $request->get('search');

        // Build query for pending withdrawals
        $query = Transaction::with(['user:id,first_name,last_name,email,username'])
            ->where('type', 'withdrawal')
            ->where('status', 'pending')
            ->select('*');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('crypto_address', 'like', "%{$search}%")
                    ->orWhere('crypto_txid', 'like', "%{$search}%")
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
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        } elseif ($dateRange && $dateRange !== 'all') {
            $days = (int) $dateRange;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        // Get pending withdrawals with pagination
        $pendingWithdrawals = $query->orderBy('created_at', 'desc')->paginate(25);

        // Get summary statistics
        $summaryData = [
            'total_pending' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'today_pending' => Transaction::where('type', 'withdrawal')
                ->where('status', 'pending')
                ->whereDate('created_at', today())
                ->count(),
            'today_amount' => Transaction::where('type', 'withdrawal')
                ->where('status', 'pending')
                ->whereDate('created_at', today())
                ->sum('amount'),
        ];

        // Get filter options
        $filterOptions = [
            'date_ranges' => [
                '7' => 'Last 7 days',
                '30' => 'Last 30 days',
                '90' => 'Last 3 months',
                'all' => 'All time'
            ]
        ];

        return view('admin.finance.withdrawals.pending', compact(
            'user',
            'pendingWithdrawals',
            'summaryData',
            'filterOptions'
        ));
    }

    /**
     * Get filtered pending withdrawals via AJAX
     */
    public function getFilteredPendingWithdrawalsAjax(Request $request)
    {
        $perPage = $request->get('per_page', 25);

        $query = Transaction::with([
            'user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'email', 'username');
            }
        ])
            ->where('type', 'withdrawal')
            ->where('status', 'pending')
            ->select('*');

        // Apply search filter
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('crypto_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Apply custom date range filter
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        } elseif ($request->date_range && $request->date_range !== 'all' && $request->date_range !== 'custom') {
            $days = (int) $request->date_range;
            $query->where('created_at', '>=', now()->subDays($days));
        }

        $pendingWithdrawals = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Build HTML
        $html = $this->buildPendingWithdrawalsTableHTML($pendingWithdrawals);

        // Build pagination HTML
        $paginationHtml = $this->buildPendingWithdrawalsPaginationHTML($pendingWithdrawals);

        return response()->json([
            'success' => true,
            'html' => $html,
            'pagination' => $paginationHtml,
            'count' => $pendingWithdrawals->count(),
            'total' => $pendingWithdrawals->total(),
            'current_page' => $pendingWithdrawals->currentPage(),
            'last_page' => $pendingWithdrawals->lastPage()
        ]);
    }

    /**
     * Build pending withdrawals table HTML for AJAX
     */
    private function buildPendingWithdrawalsTableHTML($pendingWithdrawals)
    {
        if ($pendingWithdrawals->count() === 0) {
            return '<tr>
            <td colspan="7" class="text-center py-5">
                <iconify-icon icon="iconamoon:check-circle-duotone" class="fs-1 text-success mb-3"></iconify-icon>
                <h6 class="text-muted">No Pending Withdrawals</h6>
                <p class="text-muted mb-0">All withdrawals have been processed!</p>
            </td>
        </tr>';
        }

        $html = '';

        foreach ($pendingWithdrawals as $withdrawal) {
            $userInitials = $withdrawal->user ? $withdrawal->user->initials : 'U';
            $userFullName = $withdrawal->user ? $withdrawal->user->full_name : 'Unknown User';
            $userEmail = $withdrawal->user ? $withdrawal->user->email : 'Unknown';
            $transactionIdShort = \Str::limit($withdrawal->transaction_id, 15);
            $cryptoAddress = $withdrawal->crypto_address ? \Str::limit($withdrawal->crypto_address, 20) : 'N/A';

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
                <code class="small">' . e($transactionIdShort) . '...</code>
            </td>
            <td>
                <strong class="text-danger">-' . $withdrawal->formatted_amount . '</strong>
            </td>
            <td>
                <code class="small">' . e($cryptoAddress) . '</code>
            </td>
            <td>
                ' . $withdrawal->created_at->format('d M, y') . '
                <small class="text-muted d-block">' . $withdrawal->created_at->format('h:i:s A') . '</small>
            </td>
            <td>
                <span class="badge bg-warning-subtle text-warning">Pending</span>
            </td>
            <td>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-success" onclick="updateTransactionStatus(\'' . $withdrawal->id . '\', \'completed\')" title="Approve">
                        <iconify-icon icon="iconamoon:check-circle-duotone"></iconify-icon>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="updateTransactionStatus(\'' . $withdrawal->id . '\', \'failed\')" title="Reject">
                        <iconify-icon icon="iconamoon:close-circle-duotone"></iconify-icon>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="showDetails(\'' . $withdrawal->id . '\')" title="View Details">
                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                    </button>
                    <button class="btn btn-sm btn-secondary" onclick="showStatusModal(\'' . $withdrawal->id . '\', \'pending\')" title="Change Status">
                        <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon>
                    </button>
                </div>
            </td>
        </tr>';
        }

        return $html;
    }

    /**
     * Build pagination HTML for pending withdrawals AJAX
     */
    private function buildPendingWithdrawalsPaginationHTML($pendingWithdrawals)
    {
        if (!$pendingWithdrawals->hasPages()) {
            return '';
        }

        $currentPage = $pendingWithdrawals->currentPage();
        $lastPage = $pendingWithdrawals->lastPage();

        $html = '<div class="card-footer border-top border-light">
        <div class="align-items-center justify-content-between row text-center text-sm-start">
            <div class="col-sm">
                <div class="text-muted">
                    Showing
                    <span class="fw-semibold text-body">' . $pendingWithdrawals->firstItem() . '</span>
                    to
                    <span class="fw-semibold text-body">' . $pendingWithdrawals->lastItem() . '</span>
                    of
                    <span class="fw-semibold">' . $pendingWithdrawals->total() . '</span>
                    Pending Withdrawals
                </div>
            </div>
            <div class="col-sm-auto mt-3 mt-sm-0">
                <ul class="pagination pagination-boxed pagination-sm mb-0 justify-content-center">';

        // Previous button
        if ($pendingWithdrawals->onFirstPage()) {
            $html .= '<li class="page-item disabled">
            <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
        </li>';
        } else {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadPendingWithdrawalsPage(' . ($currentPage - 1) . ')">
                <i class="bx bxs-chevron-left"></i>
            </a>
        </li>';
        }

        // Smart pagination
        $pagesToShow = $this->calculateTransactionsPagesToShow($currentPage, $lastPage);

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
                <a class="page-link" href="javascript:void(0)" onclick="loadPendingWithdrawalsPage(' . $page . ')">' . $page . '</a>
            </li>';
            }
        }

        // Next button
        if ($pendingWithdrawals->hasMorePages()) {
            $html .= '<li class="page-item">
            <a class="page-link" href="javascript:void(0)" onclick="loadPendingWithdrawalsPage(' . ($currentPage + 1) . ')">
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
}