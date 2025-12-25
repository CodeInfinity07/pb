<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cryptocurrency;
use App\Models\CryptoWallet;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class AdminCryptocurrencyController extends Controller
{
    /**
     * Display cryptocurrency management dashboard
     */
    public function index(): View
    {
        $this->checkAccess();
        $user = \Auth::user();
        
        $stats = [
            'total_cryptocurrencies' => Cryptocurrency::count(),
            'active_cryptocurrencies' => Cryptocurrency::where('is_active', true)->count(),
            'total_wallets' => CryptoWallet::count(),
            'currencies_with_wallets' => CryptoWallet::distinct('currency')->count(),
        ];
        
        $cryptocurrencies = Cryptocurrency::withCount('cryptoWallets')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.finance.cryptocurrencies.index', compact('stats', 'cryptocurrencies', 'user'));
    }

    /**
     * Show create cryptocurrency form
     */
    public function create(): View
    {
        $this->checkAccess();
        
        $maxSortOrder = Cryptocurrency::max('sort_order') ?? 0;
        
        return view('admin.finance.cryptocurrencies.create', compact('maxSortOrder'));
    }

    /**
     * Store new cryptocurrency
     */
    public function store(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10|unique:cryptocurrencies,symbol',
            'network' => 'required|string|max:255',
            'contract_address' => 'nullable|string|max:255',
            'decimal_places' => 'required|integer|min:0|max:18',
            'min_withdrawal' => 'required|numeric|min:0',
            'max_withdrawal' => 'nullable|numeric|min:0',
            'withdrawal_fee' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048'
        ]);

        try {
            DB::beginTransaction();

            // Handle icon upload
            $iconPath = null;
            if ($request->hasFile('icon')) {
                $iconPath = $this->uploadIcon($request->file('icon'), $validated['symbol']);
            }

            $cryptocurrency = Cryptocurrency::create([
                'name' => $validated['name'],
                'symbol' => strtoupper($validated['symbol']),
                'network' => $validated['network'],
                'contract_address' => $validated['contract_address'],
                'decimal_places' => $validated['decimal_places'],
                'min_withdrawal' => $validated['min_withdrawal'],
                'max_withdrawal' => $validated['max_withdrawal'],
                'withdrawal_fee' => $validated['withdrawal_fee'],
                'sort_order' => $validated['sort_order'],
                'is_active' => $validated['is_active'] ?? true,
                'icon' => $iconPath,
            ]);

            DB::commit();

            Log::info('Cryptocurrency created by admin', [
                'cryptocurrency_id' => $cryptocurrency->id,
                'symbol' => $cryptocurrency->symbol,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cryptocurrency created successfully',
                'cryptocurrency' => $cryptocurrency
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Cryptocurrency creation failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create cryptocurrency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show specific cryptocurrency
     */
    public function show(Cryptocurrency $cryptocurrency): JsonResponse
    {
        $this->checkAccess();

        try {
            $cryptocurrency->load('cryptoWallets.user');
            
            return response()->json([
                'success' => true,
                'cryptocurrency' => $cryptocurrency
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load cryptocurrency details'
            ], 500);
        }
    }

    /**
     * Show edit cryptocurrency form
     */
    public function edit(Cryptocurrency $cryptocurrency): View
    {
        $this->checkAccess();
        
        return view('admin.finance.cryptocurrencies.edit', compact('cryptocurrency'));
    }

    /**
     * Update cryptocurrency
     */
    public function update(Request $request, Cryptocurrency $cryptocurrency): JsonResponse
{
    // Ensure we always return JSON, even for exceptions
    try {
        $this->checkAccess();

        // Custom validation with JSON error responses
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10|unique:cryptocurrencies,symbol,' . $cryptocurrency->id,
            'network' => 'required|string|max:255',
            'contract_address' => 'nullable|string|max:255',
            'decimal_places' => 'required|integer|min:0|max:18',
            'min_withdrawal' => 'required|numeric|min:0',
            'max_withdrawal' => 'nullable|numeric|min:0',
            'withdrawal_fee' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
            'icon' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'remove_icon' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        // Handle icon operations with better error handling
        $iconPath = $cryptocurrency->icon;
        
        try {
            if ($request->boolean('remove_icon')) {
                $this->deleteIcon($cryptocurrency->icon);
                $iconPath = null;
            } elseif ($request->hasFile('icon')) {
                // Validate file upload
                $file = $request->file('icon');
                if (!$file->isValid()) {
                    throw new Exception('Invalid file upload');
                }
                
                $this->deleteIcon($cryptocurrency->icon);
                $iconPath = $this->uploadIcon($file, $validated['symbol']);
            }
        } catch (Exception $fileError) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'File upload error: ' . $fileError->getMessage()
            ], 500);
        }

        // Update cryptocurrency
        $cryptocurrency->update([
            'name' => $validated['name'],
            'symbol' => strtoupper($validated['symbol']),
            'network' => $validated['network'],
            'contract_address' => $validated['contract_address'],
            'decimal_places' => $validated['decimal_places'],
            'min_withdrawal' => $validated['min_withdrawal'],
            'max_withdrawal' => $validated['max_withdrawal'],
            'withdrawal_fee' => $validated['withdrawal_fee'],
            'sort_order' => $validated['sort_order'],
            'is_active' => $validated['is_active'] ?? $cryptocurrency->is_active,
            'icon' => $iconPath,
        ]);

        DB::commit();

        Log::info('Cryptocurrency updated by admin', [
            'cryptocurrency_id' => $cryptocurrency->id,
            'symbol' => $cryptocurrency->symbol,
            'admin_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cryptocurrency updated successfully',
            'cryptocurrency' => $cryptocurrency->fresh()
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        
        Log::error('Cryptocurrency update failed', [
            'cryptocurrency_id' => $cryptocurrency->id ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'admin_id' => auth()->id()
        ]);

        // Always return JSON, never let it fall through to HTML error pages
        return response()->json([
            'success' => false,
            'message' => 'Failed to update cryptocurrency: ' . $e->getMessage(),
            'debug' => config('app.debug') ? $e->getTraceAsString() : null
        ], 500);
    }
}

    /**
     * Delete cryptocurrency
     */
    public function destroy(Cryptocurrency $cryptocurrency): JsonResponse
    {
        $this->checkAccess();

        try {
            // Check if cryptocurrency has associated wallets
            if ($cryptocurrency->cryptoWallets()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete cryptocurrency. It has associated wallets.'
                ], 400);
            }

            DB::beginTransaction();

            // Delete icon file
            $this->deleteIcon($cryptocurrency->icon);

            $symbol = $cryptocurrency->symbol;
            $cryptocurrency->delete();

            DB::commit();

            Log::info('Cryptocurrency deleted by admin', [
                'symbol' => $symbol,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cryptocurrency deleted successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Cryptocurrency deletion failed', [
                'cryptocurrency_id' => $cryptocurrency->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cryptocurrency: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle cryptocurrency status
     */
    public function toggleStatus(Cryptocurrency $cryptocurrency): JsonResponse
    {
        $this->checkAccess();

        try {
            $newStatus = !$cryptocurrency->is_active;
            $cryptocurrency->update(['is_active' => $newStatus]);

            Log::info('Cryptocurrency status toggled by admin', [
                'cryptocurrency_id' => $cryptocurrency->id,
                'symbol' => $cryptocurrency->symbol,
                'new_status' => $newStatus ? 'active' : 'inactive',
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cryptocurrency status updated successfully',
                'is_active' => $newStatus
            ]);

        } catch (Exception $e) {
            Log::error('Cryptocurrency status toggle failed', [
                'cryptocurrency_id' => $cryptocurrency->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update cryptocurrency status'
            ], 500);
        }
    }

    /**
     * Update sort order
     */
    public function updateOrder(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'cryptocurrencies' => 'required|array',
            'cryptocurrencies.*' => 'exists:cryptocurrencies,id'
        ]);

        try {
            DB::beginTransaction();

            foreach ($validated['cryptocurrencies'] as $index => $id) {
                Cryptocurrency::where('id', $id)->update(['sort_order' => $index + 1]);
            }

            DB::commit();

            Log::info('Cryptocurrency order updated by admin', [
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order'
            ], 500);
        }
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->checkAccess();

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'cryptocurrency_ids' => 'required|array',
            'cryptocurrency_ids.*' => 'exists:cryptocurrencies,id'
        ]);

        try {
            DB::beginTransaction();

            $cryptocurrencies = Cryptocurrency::whereIn('id', $validated['cryptocurrency_ids'])->get();
            $count = 0;

            foreach ($cryptocurrencies as $cryptocurrency) {
                switch ($validated['action']) {
                    case 'activate':
                        $cryptocurrency->update(['is_active' => true]);
                        $count++;
                        break;
                    case 'deactivate':
                        $cryptocurrency->update(['is_active' => false]);
                        $count++;
                        break;
                    case 'delete':
                        if (!$cryptocurrency->cryptoWallets()->exists()) {
                            $this->deleteIcon($cryptocurrency->icon);
                            $cryptocurrency->delete();
                            $count++;
                        }
                        break;
                }
            }

            DB::commit();

            Log::info('Bulk cryptocurrency action performed by admin', [
                'action' => $validated['action'],
                'affected_count' => $count,
                'admin_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bulk action completed. {$count} cryptocurrencies affected."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get statistics
     */
    public function getStatistics(): JsonResponse
    {
        $this->checkAccess();

        try {
            $stats = [
                'total_cryptocurrencies' => Cryptocurrency::count(),
                'active_cryptocurrencies' => Cryptocurrency::where('is_active', true)->count(),
                'inactive_cryptocurrencies' => Cryptocurrency::where('is_active', false)->count(),
                'total_wallets' => CryptoWallet::count(),
                'currencies_with_wallets' => CryptoWallet::distinct('currency')->count(),
                'currencies_without_wallets' => Cryptocurrency::whereDoesntHave('cryptoWallets')->count(),
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
     * Upload cryptocurrency icon
     */
    private function uploadIcon($file, string $symbol): string
    {
        $fileName = strtolower($symbol) . '.' . $file->getClientOriginalExtension();
        
        // Store in public/images/crypto/ directory
        $path = $file->move(public_path('images/crypto'), $fileName);
        
        return $fileName;
    }

    /**
     * Delete cryptocurrency icon
     */
    private function deleteIcon(?string $icon): void
    {
        if ($icon && file_exists(public_path('images/crypto/' . $icon))) {
            unlink(public_path('images/crypto/' . $icon));
        }
    }

    /**
     * Check admin access
     */
    private function checkAccess(): void
    {
        if (!auth()->user()->hasStaffPrivileges()) {
            abort(403, 'Access denied. Staff privileges required.');
        }
    }
}