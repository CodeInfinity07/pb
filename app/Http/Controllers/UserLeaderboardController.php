<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Leaderboard;
use App\Models\LeaderboardPosition;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class UserLeaderboardController extends Controller
{
    protected $leaderboardService;
    
    public function __construct(LeaderboardService $leaderboardService)
    {
        $this->leaderboardService = $leaderboardService;
    }

    /**
     * Display the main leaderboards page for users
     */
    public function index(): View
    {
        $user = Auth::user();

        try {
            // Get active leaderboards with basic info
            $activeLeaderboards = $this->getActiveLeaderboards();

            // Get recent completed leaderboards
            $completedLeaderboards = $this->getCompletedLeaderboards(5);

            // Get user's current rankings in active leaderboards
            $userRankings = $this->getUserCurrentRankings($user);

            // Get user's recent leaderboard history
            $userHistory = $this->getUserRecentHistory($user, 5);

            return view('leaderboards.index', compact(
                'activeLeaderboards',
                'completedLeaderboards', 
                'userRankings',
                'userHistory',
                'user'
            ));

        } catch (Exception $e) {
            Log::error('Failed to load leaderboards index', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return view('leaderboards.index')->with([
                'activeLeaderboards' => collect(),
                'completedLeaderboards' => collect(),
                'userRankings' => [],
                'userHistory' => collect(),
                'user' => $user,
                'error' => 'Failed to load leaderboard data. Please try again later.'
            ]);
        }
    }

    /**
     * Show specific leaderboard details
     */
    public function show(Leaderboard $leaderboard): View
    {
        // Check if user can view this leaderboard
        if (!$leaderboard->show_to_users) {
            abort(404, 'Leaderboard not found.');
        }

        $user = Auth::user();

        try {
            // Load leaderboard with necessary relationships
            $leaderboard->load(['creator', 'prizeDistributor']);

            // Get top positions with user data
            $topPositions = $this->getTopPositions($leaderboard);

            // Get user's position in this leaderboard
            $userPosition = $leaderboard->getUserPosition($user);

            // Get user's current referral count if leaderboard is active
            $userCurrentReferrals = $leaderboard->isActive() 
                ? $this->getCurrentUserReferralCount($user, $leaderboard)
                : 0;

            // Calculate leaderboard statistics
            $stats = $this->calculateLeaderboardStats($leaderboard);

            return view('leaderboards.show', compact(
                'leaderboard',
                'topPositions',
                'userPosition',
                'userCurrentReferrals',
                'stats',
                'user'
            ));

        } catch (Exception $e) {
            Log::error('Failed to load leaderboard details', [
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('user.leaderboards.index')
                ->with('error', 'Failed to load leaderboard details. Please try again.');
        }
    }

    /**
     * Get leaderboard data for AJAX requests with caching
     */
    public function getData(Leaderboard $leaderboard): JsonResponse
    {
        // Security check
        if (!$leaderboard->show_to_users) {
            return $this->errorResponse('Leaderboard not found.', 404);
        }

        $user = Auth::user();

        try {
            // Cache general leaderboard data for 30 seconds
            $cacheKey = "leaderboard_data_{$leaderboard->id}";
            $generalData = Cache::remember($cacheKey, 30, function () use ($leaderboard) {
                return $this->getGeneralLeaderboardData($leaderboard);
            });

            // Get user-specific data (not cached as it's user-specific)
            $userData = $this->getUserSpecificData($user, $leaderboard);

            // Merge data
            $responseData = array_merge($generalData, $userData);

            return $this->successResponse($responseData);

        } catch (Exception $e) {
            Log::error('Failed to get leaderboard data', [
                'leaderboard_id' => $leaderboard->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to load leaderboard data.');
        }
    }

    /**
     * Get user's dashboard data for the main leaderboards page
     */
    public function getDashboardData(): JsonResponse
    {
        $user = Auth::user();

        try {
            // Cache dashboard data for 60 seconds
            $cacheKey = "user_leaderboard_dashboard_{$user->id}";
            $data = Cache::remember($cacheKey, 60, function () use ($user) {
                return $this->buildDashboardData($user);
            });

            return $this->successResponse($data);

        } catch (Exception $e) {
            Log::error('Failed to get dashboard data', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to load dashboard data.');
        }
    }

    /**
     * Get user's leaderboard history with pagination
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:50',
            'status' => 'string|in:active,completed,inactive'
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 10;
        $status = $validated['status'] ?? null;

        try {
            $query = LeaderboardPosition::whereHas('leaderboard', function ($q) use ($status) {
                    $q->where('show_to_users', true);
                    if ($status) {
                        $q->where('status', $status);
                    }
                })
                ->where('user_id', $user->id)
                ->with(['leaderboard:id,title,status,end_date,type,target_referrals,target_prize_amount'])
                ->orderByDesc('created_at');

            $history = $query->paginate($perPage, ['*'], 'page', $page);

            $data = [
                'history' => $history->getCollection()->map(function ($position) {
                    return [
                        'id' => $position->id,
                        'leaderboard_id' => $position->leaderboard->id,
                        'leaderboard_title' => $position->leaderboard->title,
                        'leaderboard_type' => $position->leaderboard->type,
                        'leaderboard_status' => $position->leaderboard->status,
                        'position' => $position->position,
                        'position_display' => $this->getPositionDisplay($position->position),
                        'referral_count' => $position->referral_count,
                        'prize_amount' => $position->prize_amount,
                        'formatted_prize' => $position->getFormattedPrizeAmount(),
                        'prize_awarded' => $position->prize_awarded,
                        'prize_status' => $position->getPrizeStatusTextAttribute(),
                        'is_top_three' => $position->position <= 3,
                        'qualified' => $position->leaderboard->type === 'target' 
                            ? $position->referral_count >= $position->leaderboard->target_referrals
                            : true,
                        'leaderboard_end_date' => $position->leaderboard->end_date->format('M d, Y'),
                        'leaderboard_url' => route('user.leaderboards.show', $position->leaderboard),
                        'participated_at' => $position->created_at->format('M d, Y')
                    ];
                }),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'from' => $history->firstItem(),
                    'to' => $history->lastItem(),
                    'has_more_pages' => $history->hasMorePages(),
                    'prev_page_url' => $history->previousPageUrl(),
                    'next_page_url' => $history->nextPageUrl()
                ]
            ];

            return $this->successResponse($data);

        } catch (Exception $e) {
            Log::error('Failed to get user leaderboard history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to load history.');
        }
    }

    /**
     * Get live leaderboard updates for active competitions
     */
    public function getLiveUpdates(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'leaderboard_ids' => 'array',
            'leaderboard_ids.*' => 'integer|exists:leaderboards,id'
        ]);

        try {
            $leaderboardIds = $validated['leaderboard_ids'] ?? [];
            $updates = [];

            foreach ($leaderboardIds as $leaderboardId) {
                $leaderboard = Leaderboard::find($leaderboardId);
                
                if ($leaderboard && $leaderboard->show_to_users && $leaderboard->isActive()) {
                    $updates[$leaderboardId] = [
                        'participant_count' => $leaderboard->getParticipantsCount(),
                        'days_remaining' => $leaderboard->days_remaining,
                        'progress' => $leaderboard->getProgress(),
                        'user_current_referrals' => $this->getCurrentUserReferralCount($user, $leaderboard),
                        'last_updated' => now()->format('H:i:s')
                    ];
                }
            }

            return $this->successResponse(['updates' => $updates]);

        } catch (Exception $e) {
            Log::error('Failed to get live updates', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to get live updates.');
        }
    }

    /**
     * Get user's target qualifications for target-based leaderboards
     */
    public function getTargetQualifications(): JsonResponse
    {
        $user = Auth::user();

        try {
            $qualifications = $this->leaderboardService->checkUserTargetQualifications($user);

            $data = collect($qualifications)->map(function ($qualification) {
                return [
                    'leaderboard_id' => $qualification['leaderboard']->id,
                    'leaderboard_title' => $qualification['leaderboard']->title,
                    'current_referrals' => $qualification['current_referrals'],
                    'target_referrals' => $qualification['target_referrals'],
                    'qualifies' => $qualification['qualifies'],
                    'progress_percentage' => $qualification['progress_percentage'],
                    'remaining_referrals' => $qualification['remaining_referrals'],
                    'prize_amount' => $qualification['prize_amount'],
                    'days_remaining' => $qualification['leaderboard']->days_remaining,
                    'leaderboard_url' => route('user.leaderboards.show', $qualification['leaderboard'])
                ];
            });

            return $this->successResponse(['qualifications' => $data]);

        } catch (Exception $e) {
            Log::error('Failed to get target qualifications', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to get qualifications.');
        }
    }

    /**
     * Private helper methods
     */

    private function getActiveLeaderboards()
    {
        return Cache::remember('active_leaderboards', 120, function () {
            return Leaderboard::active()
                ->visible()
                ->current()
                ->select(['id', 'title', 'description', 'start_date', 'end_date', 'type', 'max_positions', 'target_referrals', 'target_prize_amount', 'prize_structure'])
                ->orderBy('end_date')
                ->get();
        });
    }

    private function getCompletedLeaderboards($limit = 5)
    {
        return Cache::remember("completed_leaderboards_{$limit}", 300, function () use ($limit) {
            return Leaderboard::completed()
                ->visible()
                ->select(['id', 'title', 'description', 'start_date', 'end_date', 'type', 'prize_structure', 'target_prize_amount'])
                ->orderByDesc('end_date')
                ->limit($limit)
                ->get();
        });
    }

    private function getUserCurrentRankings(User $user)
    {
        try {
            return $this->leaderboardService->getCurrentUserRankings($user);
        } catch (Exception $e) {
            Log::error('Failed to get user current rankings', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    private function getUserRecentHistory(User $user, $limit = 5)
    {
        try {
            return LeaderboardPosition::whereHas('leaderboard', function($q) {
                    $q->where('show_to_users', true);
                })
                ->where('user_id', $user->id)
                ->with(['leaderboard:id,title,status,end_date,type'])
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        } catch (Exception $e) {
            Log::error('Failed to get user recent history', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    private function getTopPositions(Leaderboard $leaderboard)
    {
        return $leaderboard->positions()
            ->with(['user:id,first_name,last_name,email'])
            ->orderBy('position')
            ->limit($leaderboard->max_positions)
            ->get();
    }

    private function getCurrentUserReferralCount(User $user, Leaderboard $leaderboard): int
    {
        try {
            $query = User::where('sponsor_id', $user->id)
                ->whereBetween('created_at', [$leaderboard->start_date, $leaderboard->end_date]);

            // Apply referral type filters
            switch ($leaderboard->referral_type) {
                case 'verified_only':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'active_only':
                    $query->where('status', 'active');
                    break;
                case 'first_level':
                case 'all':
                default:
                    // No additional filters needed
                    break;
            }

            return $query->count();

        } catch (Exception $e) {
            Log::error('Failed to get user referral count', [
                'user_id' => $user->id,
                'leaderboard_id' => $leaderboard->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    private function calculateLeaderboardStats(Leaderboard $leaderboard): array
    {
        return [
            'total_participants' => $leaderboard->getParticipantsCount(),
            'total_winners' => $leaderboard->getWinnersCount(),
            'total_prize_amount' => $leaderboard->total_prize_amount,
            'duration_days' => $leaderboard->start_date->diffInDays($leaderboard->end_date) + 1,
            'days_remaining' => $leaderboard->days_remaining,
            'progress' => $leaderboard->getProgress(),
            'qualified_count' => $leaderboard->type === 'target' ? $leaderboard->getQualifiedCount() : 0
        ];
    }

    private function getGeneralLeaderboardData(Leaderboard $leaderboard): array
    {
        $topPositions = $this->getTopPositions($leaderboard);

        return [
            'id' => $leaderboard->id,
            'title' => $leaderboard->title,
            'description' => $leaderboard->description,
            'status' => $leaderboard->status,
            'type' => $leaderboard->type,
            'type_display' => $leaderboard->type_display,
            'duration_display' => $leaderboard->duration_display,
            'referral_type_display' => $leaderboard->referral_type_display,
            'is_active' => $leaderboard->isActive(),
            'is_completed' => $leaderboard->isCompleted(),
            'days_remaining' => $leaderboard->days_remaining,
            'progress' => $leaderboard->getProgress(),
            'total_participants' => $leaderboard->getParticipantsCount(),
            'total_winners' => $leaderboard->getWinnersCount(),
            'total_prize_amount' => $leaderboard->total_prize_amount,
            'target_referrals' => $leaderboard->target_referrals,
            'target_prize_amount' => $leaderboard->target_prize_amount,
            'qualified_count' => $leaderboard->type === 'target' ? $leaderboard->getQualifiedCount() : null,
            'positions' => $topPositions->map(function ($position) {
                return [
                    'position' => $position->position,
                    'position_display' => $this->getPositionDisplay($position->position),
                    'user_name' => $position->user->first_name . ' ' . $position->user->last_name,
                    'user_email' => $position->user->email,
                    'referral_count' => $position->referral_count,
                    'prize_amount' => $position->prize_amount,
                    'formatted_prize' => $position->getFormattedPrizeAmount(),
                    'is_top_three' => $position->position <= 3,
                    'prize_awarded' => $position->prize_awarded
                ];
            }),
            'last_updated' => now()->format('Y-m-d H:i:s')
        ];
    }

    private function getUserSpecificData(User $user, Leaderboard $leaderboard): array
    {
        $userPosition = $leaderboard->getUserPosition($user);
        $userCurrentReferrals = $leaderboard->isActive() 
            ? $this->getCurrentUserReferralCount($user, $leaderboard)
            : 0;

        $userData = [
            'user_current_referrals' => $userCurrentReferrals,
            'user_position' => null
        ];

        if ($userPosition) {
            $userData['user_position'] = [
                'position' => $userPosition->position,
                'position_display' => $this->getPositionDisplay($userPosition->position),
                'referral_count' => $userPosition->referral_count,
                'prize_amount' => $userPosition->prize_amount,
                'formatted_prize' => $userPosition->getFormattedPrizeAmount(),
                'prize_awarded' => $userPosition->prize_awarded,
                'is_top_three' => $userPosition->position <= 3
            ];

            // Add target-specific data
            if ($leaderboard->type === 'target') {
                $userData['user_position']['qualified'] = $userPosition->referral_count >= $leaderboard->target_referrals;
                $userData['user_position']['progress_percentage'] = min(100, ($userPosition->referral_count / $leaderboard->target_referrals) * 100);
                $userData['user_position']['remaining_referrals'] = max(0, $leaderboard->target_referrals - $userPosition->referral_count);
            }
        }

        return $userData;
    }

    private function buildDashboardData(User $user): array
    {
        // Get active leaderboards
        $activeLeaderboards = $this->getActiveLeaderboards();
        
        // Get user rankings
        $userRankings = $this->getUserCurrentRankings($user);

        // Get user statistics
        $userStats = [
            'total_participations' => LeaderboardPosition::where('user_id', $user->id)->count(),
            'total_prizes_won' => LeaderboardPosition::where('user_id', $user->id)
                ->where('prize_awarded', true)
                ->sum('prize_amount'),
            'best_rank' => LeaderboardPosition::where('user_id', $user->id)
                ->min('position') ?: null,
            'active_competitions' => $activeLeaderboards->count(),
            'pending_prizes' => LeaderboardPosition::where('user_id', $user->id)
                ->where('prize_amount', '>', 0)
                ->where('prize_awarded', false)
                ->sum('prize_amount')
        ];

        return [
            'active_leaderboards' => $activeLeaderboards->map(function ($leaderboard) {
                return [
                    'id' => $leaderboard->id,
                    'title' => $leaderboard->title,
                    'type' => $leaderboard->type,
                    'type_display' => $leaderboard->type_display,
                    'days_remaining' => $leaderboard->days_remaining,
                    'total_participants' => $leaderboard->getParticipantsCount(),
                    'total_prize_amount' => $leaderboard->total_prize_amount,
                    'progress' => $leaderboard->getProgress(),
                    'url' => route('user.leaderboards.show', $leaderboard)
                ];
            }),
            'user_rankings' => collect($userRankings)->map(function ($ranking) {
                return [
                    'leaderboard_id' => $ranking['leaderboard']->id,
                    'leaderboard_title' => $ranking['leaderboard']->title,
                    'leaderboard_type' => $ranking['leaderboard']->type,
                    'position' => $ranking['rank'],
                    'position_display' => $ranking['position'] ? $this->getPositionDisplay($ranking['position']->position) : null,
                    'referral_count' => $ranking['referral_count'],
                    'qualified' => $ranking['qualified'] ?? false,
                    'progress' => $ranking['progress'],
                    'leaderboard_url' => route('user.leaderboards.show', $ranking['leaderboard'])
                ];
            }),
            'stats' => $userStats
        ];
    }

    private function getPositionDisplay(int $position): string
    {
        return match($position) {
            1 => '🥇 1st',
            2 => '🥈 2nd',
            3 => '🥉 3rd',
            default => "#{$position}"
        };
    }

    private function successResponse(array $data, string $message = null): JsonResponse
    {
        $response = ['success' => true, 'data' => $data];
        
        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }
}