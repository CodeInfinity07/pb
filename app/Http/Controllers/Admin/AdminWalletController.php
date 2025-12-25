<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CryptoWallet;
use App\Models\Cryptocurrency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Exception;

class AdminWalletController extends Controller
{
    /**
     * Display wallet management dashboard
     */
    public function index(): View
    {
        $this->checkAccess();
        $user = \Auth::user();
        
        $stats = [
            'total_wallets' => CryptoWallet::count(),
            'active_wallets' => CryptoWallet::where('is_active', true)->count(),
            'wallets_with_balance' => CryptoWallet::where('balance', '>', 0)->count(),
            'total_usd_value' => CryptoWallet::selectRaw('SUM(balance * usd_rate) as total')->value('total') ?? 0,
            'currencies_count' => Cryptocurrency::active()->count(),
            'users_with_wallets' => CryptoWallet::distinct('user_id')->count(),
            'zero_balance_wallets' => CryptoWallet::where('balance', 0)->count(),
            'high_balance_wallets' => CryptoWallet::whereRaw('balance * usd_rate > 1000')->count(),
        ];
        
        $recentWallets = CryptoWallet::with(['user', 'cryptocurrency'])
            ->latest()
            ->limit(5)
            ->get();

        $cryptocurrencies = Cryptocurrency::active()->ordered()->get();

        return view('admin.finance.wallets.index', compact('stats', 'recentWallets', 'cryptocurrencies', 'user'));
    }

    /**
     * Get wallets with filters
     */
    public function getWallets(Request $request): JsonResponse
    {
        try {
            $query = CryptoWallet::with(['user', 'cryptocurrency']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                })->orWhere('address', 'LIKE', "%{$search}%");
            }

            // Currency filter
            if ($request->filled('currency')) {
                $query->where('currency', $request->currency);
            }

            // Status filter
            if ($request->filled('status')) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            // Balance filter
            if ($request->filled('balance_filter')) {
                switch ($request->balance_filter) {
                    case 'with_balance':
                        $query->where('balance', '>', 0);
                        break;
                    case 'zero_balance':
                        $query->where('balance', '=', 0);
                        break;
                    case 'high_balance':
                        $query->where('balance', '>', 1000);
                        break;
                }
            }

            $wallets = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'wallets' => $wallets
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load wallets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show wallet details
     */
    public function show(CryptoWallet $wallet): JsonResponse
    {
        try {
            $wallet->load(['user', 'cryptocurrency']);
            
            $recentTransactions = Transaction::where('user_id', $wallet->user_id)
                ->where(function($q) use ($wallet) {
                    $q->where('description', 'LIKE', '%' . $wallet->currency . '%')
                      ->orWhere('type', 'adj')
                      ->orWhere('type', 'dep')
                      ->orWhere('type', 'with');
                })
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'wallet' => $wallet,
                'recent_transactions' => $recentTransactions
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load wallet details'
            ], 500);
        }
    }

    /**
     * Adjust wallet balance
     */
    public function adjustBalance(Request $request, CryptoWallet $wallet): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'type' => 'required|in:add,subtract,set',
            'description' => 'nullable|string|max:200'
        ]);

        try {
            DB::beginTransaction();

            $oldBalance = $wallet->balance;
            $amount = abs($request->amount);
            
            switch ($request->type) {
                case 'add':
                    $newBalance = $oldBalance + $amount;
                    $changeAmount = $amount;
                    break;
                case 'subtract':
                    $newBalance = max(0, $oldBalance - $amount);
                    $changeAmount = -$amount;
                    break;
                case 'set':
                    $newBalance = $amount;
                    $changeAmount = $amount - $oldBalance;
                    break;
            }

            $wallet->update(['balance' => $newBalance]);

            // Create transaction with minimal required fields
            $this->createTransaction([
                'user_id' => $wallet->user_id,
                'amount' => $changeAmount,
                'description' => $request->description ?: "Balance {$request->type}: {$amount} {$wallet->currency}"
            ]);

            DB::commit();

            Log::info('Admin adjusted wallet balance', [
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'currency' => $wallet->currency,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Balance adjusted successfully',
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Balance adjustment failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust balance'
            ], 500);
        }
    }

    /**
     * Toggle wallet status
     */
    public function toggleStatus(CryptoWallet $wallet): JsonResponse
    {
        try {
            $newStatus = !$wallet->is_active;
            $wallet->update(['is_active' => $newStatus]);

            Log::info('Admin toggled wallet status', [
                'wallet_id' => $wallet->id,
                'new_status' => $newStatus ? 'active' : 'inactive',
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wallet status updated',
                'is_active' => $newStatus
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Update wallet address
     */
    public function updateAddress(Request $request, CryptoWallet $wallet): JsonResponse
    {
        $request->validate([
            'address' => 'nullable|string|max:255'
        ]);

        try {
            $wallet->update(['address' => $request->address]);

            Log::info('Admin updated wallet address', [
                'wallet_id' => $wallet->id,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update address'
            ], 500);
        }
    }

    /**
     * Create new wallet
     */
    public function createWallet(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'currency' => 'required|exists:cryptocurrencies,symbol',
            'balance' => 'nullable|numeric|min:0',
            'address' => 'nullable|string|max:255'
        ]);

        try {
            // Check if wallet exists
            $exists = CryptoWallet::where('user_id', $request->user_id)
                ->where('currency', $request->currency)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet already exists for this user and currency'
                ], 400);
            }

            $crypto = Cryptocurrency::where('symbol', $request->currency)->first();
            $balance = $request->balance ?? 0;
            
            $wallet = CryptoWallet::create([
                'user_id' => $request->user_id,
                'currency' => $request->currency,
                'name' => $crypto->name,
                'balance' => $balance,
                'address' => $request->address,
                'usd_rate' => 1,
                'is_active' => true
            ]);

            // Create initial transaction if balance > 0
            if ($balance > 0) {
                $this->createTransaction([
                    'user_id' => $request->user_id,
                    'amount' => $balance,
                    'description' => "Initial balance: {$balance} {$request->currency}"
                ]);
            }

            Log::info('Admin created wallet', [
                'wallet_id' => $wallet->id,
                'user_id' => $request->user_id,
                'currency' => $request->currency,
                'initial_balance' => $balance,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Wallet created successfully',
                'wallet' => $wallet->load(['user', 'cryptocurrency'])
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create wallet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search users for wallet creation
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2|max:50'
        ]);

        try {
            $search = $request->search;
            $users = User::where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('last_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('username', 'LIKE', "%{$search}%");
            })
            ->where('status', 'active')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'status']);

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed'
            ], 500);
        }
    }

    /**
     * Bulk actions on wallets
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'wallet_ids' => 'required|array',
            'wallet_ids.*' => 'exists:crypto_wallets,id'
        ]);

        try {
            DB::beginTransaction();

            $wallets = CryptoWallet::whereIn('id', $request->wallet_ids)->get();
            $count = 0;

            foreach ($wallets as $wallet) {
                switch ($request->action) {
                    case 'activate':
                        $wallet->update(['is_active' => true]);
                        $count++;
                        break;
                    case 'deactivate':
                        $wallet->update(['is_active' => false]);
                        $count++;
                        break;
                    case 'delete':
                        if ($wallet->balance == 0) {
                            $wallet->delete();
                            $count++;
                        }
                        break;
                }
            }

            DB::commit();

            Log::info('Admin bulk action on wallets', [
                'action' => $request->action,
                'affected_count' => $count,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bulk action completed. {$count} wallets affected."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed'
            ], 500);
        }
    }

    /**
     * Export wallets to CSV
     */
    public function export(Request $request)
    {
        try {
            $query = CryptoWallet::with(['user', 'cryptocurrency']);

            // Apply filters
            if ($request->filled('currency')) {
                $query->where('currency', $request->currency);
            }
            if ($request->filled('status') && $request->status !== '') {
                $query->where('is_active', $request->status === 'active');
            }

            $wallets = $query->get();
            $filename = 'wallets_' . now()->format('Y_m_d_H_i_s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            $callback = function() use ($wallets) {
                $file = fopen('php://output', 'w');
                
                fputcsv($file, [
                    'User Name', 'Email', 'Currency', 'Balance', 
                    'USD Value', 'Address', 'Status', 'Created'
                ]);

                foreach ($wallets as $wallet) {
                    fputcsv($file, [
                        $wallet->user->full_name ?? 'N/A',
                        $wallet->user->email ?? 'N/A',
                        $wallet->currency,
                        number_format($wallet->balance, 8),
                        '$' . number_format($wallet->balance * $wallet->usd_rate, 2),
                        $wallet->address ?: 'Not Set',
                        $wallet->is_active ? 'Active' : 'Inactive',
                        $wallet->created_at->format('Y-m-d H:i:s')
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed'
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $stats = [
                'total_wallets' => CryptoWallet::count(),
                'active_wallets' => CryptoWallet::where('is_active', true)->count(),
                'wallets_with_balance' => CryptoWallet::where('balance', '>', 0)->count(),
                'total_usd_value' => CryptoWallet::selectRaw('SUM(balance * usd_rate) as total')->value('total') ?? 0,
                'currencies_count' => Cryptocurrency::active()->count(),
                'users_with_wallets' => CryptoWallet::distinct('user_id')->count(),
                'zero_balance_wallets' => CryptoWallet::where('balance', 0)->count(),
                'high_balance_wallets' => CryptoWallet::whereRaw('balance * usd_rate > 1000')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Create transaction with error handling
     */
    private function createTransaction(array $data): void
    {
        try {
            // Use very short type to avoid column length issues
            $transaction = [
                'user_id' => $data['user_id'],
                'type' => 'adj', // Very short - should fit any column
                'amount' => $data['amount'],
                'status' => 'completed',
                'description' => $data['description'],
            ];

            // Add transaction_id if required
            if (Schema::hasColumn('transactions', 'transaction_id')) {
                $transaction['transaction_id'] = 'TXN_' . time() . '_' . rand(100, 999);
            }

            // Add balance_after if column exists
            if (Schema::hasColumn('transactions', 'balance_after') && isset($data['balance_after'])) {
                $transaction['balance_after'] = $data['balance_after'];
            }

            Transaction::create($transaction);
            
        } catch (Exception $e) {
            // Log the error but don't fail the main operation
            Log::warning('Transaction creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Check admin access
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasStaffPrivileges()) {
            abort(403, 'Access denied');
        }
    }
}