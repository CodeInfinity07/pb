<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Display a listing of the user's transactions
     */
    public function index(Request $request): View
    {
        $query = Transaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        $user = Auth::user();

        // Apply filters
        if ($request->filled('type')) {
            $validTypes = ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus'];
            if (in_array($request->type, $validTypes)) {
                $query->where('type', $request->type);
            }
        }

        if ($request->filled('status')) {
            $validStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];
            if (in_array($request->status, $validStatuses)) {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->currency));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->paginate(20)->withQueryString();

        return view('finance.transactions', compact('transactions', 'user'));
    }

    /**
     * Get transaction details for modal/AJAX
     */
    public function details(Request $request, Transaction $transaction): JsonResponse
    {
        // Ensure user can only view their own transactions
        if ($transaction->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $html = view('finance.partials.details', compact('transaction'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Transaction details error: ' . $e->getMessage(), [
                'transaction_id' => $transaction->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load transaction details',
                'error' => config('app.debug') ? $e->getMessage() : null // Show error in debug mode
            ], 500);
        }
    }

    /**
     * Show a specific transaction
     */
    public function show(Transaction $transaction): View
    {
        // Ensure user can only view their own transactions
        if ($transaction->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to this transaction');
        }

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Get transactions summary/statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $summary = [
            'total_deposits' => Transaction::where('user_id', $userId)
                ->deposits()
                ->completed()
                ->sum('amount'),
            'total_withdrawals' => Transaction::where('user_id', $userId)
                ->withdrawals()
                ->completed()
                ->sum('amount'),
            'pending_count' => Transaction::where('user_id', $userId)
                ->pending()
                ->count(),
            'completed_count' => Transaction::where('user_id', $userId)
                ->completed()
                ->count(),
            'this_month_deposits' => Transaction::where('user_id', $userId)
                ->deposits()
                ->completed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'this_month_withdrawals' => Transaction::where('user_id', $userId)
                ->withdrawals()
                ->completed()
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
        ];

        return response()->json($summary);
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $query = Transaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('type')) {
            $validTypes = ['deposit', 'withdrawal', 'commission', 'roi', 'investment', 'bonus'];
            if (in_array($request->type, $validTypes)) {
                $query->where('type', $request->type);
            }
        }

        if ($request->filled('status')) {
            $validStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];
            if (in_array($request->status, $validStatuses)) {
                $query->where('status', $request->status);
            }
        }

        $transactions = $query->get();

        $filename = 'transactions_' . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Transaction ID',
                'Type',
                'Amount',
                'Currency',
                'Status',
                'Payment Method',
                'Crypto Address',
                'Crypto TxID',
                'Description',
                'Created At',
                'Processed At'
            ]);

            // Add transaction data
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->transaction_id,
                    $transaction->type,
                    $transaction->amount,
                    $transaction->currency,
                    $transaction->status,
                    $transaction->payment_method ?? '',
                    $transaction->crypto_address ?? '',
                    $transaction->crypto_txid ?? '',
                    $transaction->display_description,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->processed_at ? $transaction->processed_at->format('Y-m-d H:i:s') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get recent transactions for dashboard
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);

        $transactions = Transaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                    'amount' => $transaction->formatted_amount,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->diffForHumans(),
                    'description' => $transaction->display_description
                ];
            });

        return response()->json($transactions);
    }

    /**
     * Search transactions
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:3|max:100'
        ]);

        $query = $request->get('query');

        $transactions = Transaction::where('user_id', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('transaction_id', 'like', "%{$query}%")
                    ->orWhere('crypto_address', 'like', "%{$query}%")
                    ->orWhere('crypto_txid', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                    'amount' => $transaction->formatted_amount,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('M d, Y H:i'),
                    'description' => $transaction->display_description
                ];
            });

        return response()->json([
            'success' => true,
            'transactions' => $transactions,
            'count' => $transactions->count()
        ]);
    }

    /**
     * Get transactions by currency
     */
    public function byCurrency(Request $request, string $currency): JsonResponse
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->where('currency', strtoupper($currency))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->type,
                    'amount' => $transaction->formatted_amount,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('M d, Y H:i'),
                    'crypto_address' => $transaction->crypto_address,
                    'crypto_txid' => $transaction->crypto_txid
                ];
            });

        return response()->json($transactions);
    }
}