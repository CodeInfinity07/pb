<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Models\LeaderboardPosition;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminLeaderboardController extends Controller
{
    protected $leaderboardService;

    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Display leaderboards dashboard.
     */
    public function index(Request $request): View
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        // Get filter parameters
        $search = $request->get('search');
        $status = $request->get('status');
        $sort_by = $request->get('sort_by', 'created_at');
        $sort_order = $request->get('sort_order', 'desc');

        // Build query
        $query = Leaderboard::with(['creator', 'positions']);

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Apply sorting
        $query->orderBy($sort_by, $sort_order);

        // Get leaderboards with pagination
        $leaderboards = $query->paginate(10)->withQueryString();

        // Get statistics
        $stats = $this->leaderboardService->getStatistics();

        return view('admin.leaderboards.index', compact(
            'leaderboards',
            'stats',
            'search',
            'status',
            'user',
            'sort_by',
            'sort_order'
        ));
    }

    /**
     * Show create leaderboard form.
     */
    public function create(): View
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        return view('admin.leaderboards.create', compact('user'));
    }

    /**
     * Store new leaderboard.
     */

    public function store(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        // Base validation rules
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|in:active,inactive',
            'show_to_users' => 'boolean',
            'max_positions' => 'required|integer|min:1|max:100',
            'referral_type' => 'required|string|in:all,first_level,verified_only',
            'type' => 'required|string|in:competitive,target',
        ];

        // Conditional validation based on leaderboard type
        if ($request->input('type') === 'competitive') {
            $rules['prize_structure'] = 'nullable|array';
            $rules['prize_structure.*.position'] = 'nullable|integer|min:1';
            $rules['prize_structure.*.amount'] = 'nullable|numeric|min:0';
        } else if ($request->input('type') === 'target') {
            $rules['target_referrals'] = 'required|integer|min:1|max:1000';
            $rules['target_prize_amount'] = 'required|numeric|min:0.01|max:10000';
            $rules['max_winners'] = 'nullable|integer|min:1|max:10000';
        }

        $validated = $request->validate($rules);

        try {
            // Process data based on type
            if ($validated['type'] === 'competitive') {
                // Process prize structure for competitive leaderboards
                if (isset($validated['prize_structure'])) {
                    // Filter out empty prizes and reindex array
                    $validated['prize_structure'] = array_values(array_filter($validated['prize_structure'], function ($prize) {
                        return isset($prize['amount']) && $prize['amount'] > 0;
                    }));

                    // If no valid prizes, set to null
                    if (empty($validated['prize_structure'])) {
                        $validated['prize_structure'] = null;
                    }
                }

                // Clear target-based fields
                $validated['target_referrals'] = null;
                $validated['target_prize_amount'] = null;
                $validated['max_winners'] = null;
            } else {
                // For target-based leaderboards, clear prize structure
                $validated['prize_structure'] = null;

                // Set default max_winners if not provided
                if (!isset($validated['max_winners'])) {
                    $validated['max_winners'] = null; // No limit
                }
            }

            // Create leaderboard
            $leaderboard = Leaderboard::create(array_merge($validated, [
                'created_by' => auth()->id(),
            ]));

            Log::info('Leaderboard created', [
                'leaderboard_id' => $leaderboard->id,
                'title' => $leaderboard->title,
                'type' => $leaderboard->type,
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->route('admin.leaderboards.show', $leaderboard)
                ->with('success', 'Leaderboard created successfully!');

        } catch (Exception $e) {
            Log::error('Failed to create leaderboard', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withErrors(['error' => 'Failed to create leaderboard. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show leaderboard details.
     */
    public function show(Leaderboard $leaderboard): View
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        $leaderboard->load(['creator', 'positions.user', 'prizeDistributor']);

        // Get leaderboard statistics
        $leaderboardStats = [
            'total_participants' => $leaderboard->getParticipantsCount(),
            'total_winners' => $leaderboard->getWinnersCount(),
            'total_prize_amount' => $leaderboard->total_prize_amount,
            'awarded_prize_amount' => $leaderboard->positions()->where('prize_awarded', true)->sum('prize_amount'),
            'pending_prize_amount' => $leaderboard->positions()->where('prize_awarded', false)->where('prize_amount', '>', 0)->sum('prize_amount'),
            'duration_days' => $leaderboard->start_date->diffInDays($leaderboard->end_date) + 1,
            'days_remaining' => $leaderboard->days_remaining,
            'progress' => $leaderboard->getProgress(),
        ];

        // Get top positions
        $topPositions = $leaderboard->positions()
            ->with('user')
            ->orderBy('position')
            ->limit(10)
            ->get();

        return view('admin.leaderboards.show', compact(
            'leaderboard',
            'leaderboardStats',
            'topPositions',
            'user'
        ));
    }

    /**
     * Show edit leaderboard form.
     */
    public function edit(Leaderboard $leaderboard): View
    {
        $this->checkAdminAccess();
        $user = Auth::user();

        return view('admin.leaderboards.edit', compact('leaderboard', 'user'));
    }

    /**
     * Update leaderboard.
     */
    public function update(Request $request, Leaderboard $leaderboard): RedirectResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|in:active,inactive,completed',
            'show_to_users' => 'boolean',
            'max_positions' => 'required|integer|min:1|max:100',
            'referral_type' => 'required|string|in:all,first_level,verified_only',
            'prize_structure' => 'nullable|array',
            'prize_structure.*.position' => 'nullable|integer|min:1',
            'prize_structure.*.from_position' => 'nullable|integer|min:1',
            'prize_structure.*.to_position' => 'nullable|integer|min:1',
            'prize_structure.*.amount' => 'required_with:prize_structure|numeric|min:0',
        ]);

        try {
            // Validate prize structure
            if (isset($validated['prize_structure'])) {
                $validated['prize_structure'] = array_filter($validated['prize_structure'], function ($prize) {
                    return isset($prize['amount']) && $prize['amount'] > 0;
                });
            }

            $leaderboard->update($validated);

            Log::info('Leaderboard updated', [
                'leaderboard_id' => $leaderboard->id,
                'title' => $leaderboard->title,
                'updated_by' => auth()->id()
            ]);

            return redirect()
                ->route('admin.leaderboards.show', $leaderboard)
                ->with('success', 'Leaderboard updated successfully!');

        } catch (Exception $e) {
            Log::error('Failed to update leaderboard', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withErrors(['error' => 'Failed to update leaderboard. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Delete leaderboard.
     */
    public function destroy(Leaderboard $leaderboard): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            // Check if prizes have been distributed
            if ($leaderboard->prizes_distributed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete leaderboard with distributed prizes.'
                ], 400);
            }

            $leaderboard->delete();

            Log::info('Leaderboard deleted', [
                'leaderboard_id' => $leaderboard->id,
                'title' => $leaderboard->title,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leaderboard deleted successfully.'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete leaderboard', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete leaderboard.'
            ], 500);
        }
    }

    /**
     * Toggle leaderboard status.
     */
    public function toggleStatus(Leaderboard $leaderboard): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $newStatus = match ($leaderboard->status) {
                'active' => 'inactive',
                'inactive' => 'active',
                'completed' => 'completed', // Cannot change completed status
            };

            if ($newStatus === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change status of completed leaderboard.'
                ], 400);
            }

            $leaderboard->update(['status' => $newStatus]);

            Log::info('Leaderboard status toggled', [
                'leaderboard_id' => $leaderboard->id,
                'old_status' => $leaderboard->status,
                'new_status' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Leaderboard {$newStatus} successfully.",
                'status' => $newStatus,
                'badge_class' => $leaderboard->status_badge_class
            ]);

        } catch (Exception $e) {
            Log::error('Failed to toggle leaderboard status', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update leaderboard status.'
            ], 500);
        }
    }

    /**
     * Calculate leaderboard positions.
     */
    public function calculatePositions(Leaderboard $leaderboard): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $this->leaderboardService->calculatePositions($leaderboard);

            return response()->json([
                'success' => true,
                'message' => 'Leaderboard positions calculated successfully.',
                'participants' => $leaderboard->getParticipantsCount()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to calculate leaderboard positions', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate positions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Distribute prizes to winners.
     */
    public function distributePrizes(Leaderboard $leaderboard): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (!$leaderboard->canDistributePrizes()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prizes cannot be distributed for this leaderboard.'
                ], 400);
            }

            $success = $this->leaderboardService->distributePrizes($leaderboard);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Prizes distributed successfully to all winners.',
                    'total_amount' => $leaderboard->positions()->where('prize_awarded', true)->sum('prize_amount')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to distribute prizes.'
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Failed to distribute leaderboard prizes', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to distribute prizes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete leaderboard.
     */
    public function complete(Leaderboard $leaderboard): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            if (!$leaderboard->canComplete()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leaderboard cannot be completed.'
                ], 400);
            }

            // Calculate final positions
            $this->leaderboardService->calculatePositions($leaderboard);

            // Mark as completed
            $leaderboard->update(['status' => 'completed']);

            return response()->json([
                'success' => true,
                'message' => 'Leaderboard completed successfully.',
                'participants' => $leaderboard->getParticipantsCount()
            ]);

        } catch (Exception $e) {
            Log::error('Failed to complete leaderboard', [
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete leaderboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get leaderboard statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $this->checkAdminAccess();

        $stats = $this->leaderboardService->getStatistics();

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Auto-complete expired leaderboards.
     */
    public function autoCompleteExpired(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $completed = $this->leaderboardService->autoCompleteExpiredLeaderboards();

            return response()->json([
                'success' => true,
                'message' => "Auto-completed {$completed} expired leaderboards.",
                'completed_count' => $completed
            ]);

        } catch (Exception $e) {
            Log::error('Failed to auto-complete expired leaderboards', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to auto-complete expired leaderboards.'
            ], 500);
        }
    }

    /**
     * Check admin access.
     */
    private function checkAdminAccess(): void
    {
        if (!auth()->user()->canAccessAdmin()) {
            abort(403, 'Access denied. Admin privileges required.');
        }
    }

    /**
     * Calculate positions for all active leaderboards.
     */
    public function calculateAllActivePositions(): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $activeLeaderboards = Leaderboard::where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get();

            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($activeLeaderboards as $leaderboard) {
                try {
                    $this->leaderboardService->calculatePositions($leaderboard);
                    $successCount++;

                    Log::info('Positions calculated for leaderboard', [
                        'leaderboard_id' => $leaderboard->id,
                        'title' => $leaderboard->title,
                        'participants' => $leaderboard->getParticipantsCount()
                    ]);
                } catch (Exception $e) {
                    $failureCount++;
                    $errors[] = [
                        'leaderboard_id' => $leaderboard->id,
                        'title' => $leaderboard->title,
                        'error' => $e->getMessage()
                    ];

                    Log::error('Failed to calculate positions for leaderboard', [
                        'leaderboard_id' => $leaderboard->id,
                        'title' => $leaderboard->title,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Bulk position calculation completed', [
                'total_leaderboards' => $activeLeaderboards->count(),
                'successful' => $successCount,
                'failed' => $failureCount,
                'calculated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Position calculation completed. Success: {$successCount}, Failed: {$failureCount}",
                'total_leaderboards' => $activeLeaderboards->count(),
                'successful' => $successCount,
                'failed' => $failureCount,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            Log::error('Failed to calculate positions for active leaderboards', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate positions for active leaderboards: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate positions for all active leaderboards (for console usage).
     * This version doesn't require authentication and returns array instead of JSON.
     */
    public function calculateAllActivePositionsConsole(): array
    {
        try {
            $activeLeaderboards = Leaderboard::where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->get();

            $successCount = 0;
            $failureCount = 0;
            $errors = [];

            foreach ($activeLeaderboards as $leaderboard) {
                try {
                    $this->leaderboardService->calculatePositions($leaderboard);
                    $successCount++;

                    Log::info('Positions calculated for leaderboard', [
                        'leaderboard_id' => $leaderboard->id,
                        'title' => $leaderboard->title,
                        'participants' => $leaderboard->getParticipantsCount(),
                        'source' => 'console'
                    ]);
                } catch (Exception $e) {
                    $failureCount++;
                    $errors[] = [
                        'leaderboard_id' => $leaderboard->id,
                        'title' => $leaderboard->title,
                        'error' => $e->getMessage()
                    ];

                    Log::error('Failed to calculate positions for leaderboard', [
                        'leaderboard_id' => $leaderboard->id,
                        'title' => $leaderboard->title,
                        'error' => $e->getMessage(),
                        'source' => 'console'
                    ]);
                }
            }

            Log::info('Bulk position calculation completed', [
                'total_leaderboards' => $activeLeaderboards->count(),
                'successful' => $successCount,
                'failed' => $failureCount,
                'source' => 'console'
            ]);

            return [
                'success' => $failureCount === 0,
                'message' => "Position calculation completed. Success: {$successCount}, Failed: {$failureCount}",
                'total_leaderboards' => $activeLeaderboards->count(),
                'successful' => $successCount,
                'failed' => $failureCount,
                'errors' => $errors
            ];

        } catch (Exception $e) {
            Log::error('Failed to calculate positions for active leaderboards', [
                'error' => $e->getMessage(),
                'source' => 'console'
            ]);

            return [
                'success' => false,
                'message' => 'Failed to calculate positions for active leaderboards: ' . $e->getMessage(),
                'total_leaderboards' => 0,
                'successful' => 0,
                'failed' => 0,
                'errors' => [['error' => $e->getMessage()]]
            ];
        }
    }
}