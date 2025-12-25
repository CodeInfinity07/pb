<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Models\UserAnnouncementView;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AdminAnnouncementsController extends Controller
{
    /**
     * Display announcements dashboard.
     */
    public function index(Request $request): View
    {
        $this->checkAdminAccess();
        $user = \Auth::user();

        // Get filter parameters
        $search = $request->get('search');
        $status = $request->get('status');
        $type = $request->get('type');
        $target_audience = $request->get('target_audience');
        $sort_by = $request->get('sort_by', 'priority');
        $sort_order = $request->get('sort_order', 'asc');

        // Build query
        $query = Announcement::with(['creator', 'userViews']);

        // Apply filters
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($target_audience) {
            $query->where('target_audience', $target_audience);
        }

        // Apply sorting
        $query->orderBy($sort_by, $sort_order);

        // Get announcements with pagination
        $announcements = $query->paginate(10)->withQueryString();

        // Get statistics
        $stats = $this->getAnnouncementStatistics();

        return view('admin.announcements.index', compact(
            'announcements',
            'stats',
            'search',
            'status',
            'type',
            'target_audience',
            'user',
            'sort_by',
            'sort_order'
        ));
    }

    /**
     * Show create announcement form.
     */
    public function create(): View
    {
        $this->checkAdminAccess();
        $user = \Auth::user();

        return view('admin.announcements.create', compact('user'));
    }

    /**
     * Store new announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:info,success,warning,danger',
            'target_audience' => 'required|string|in:all,active,verified,kyc_verified,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'priority' => 'required|integer|min:1|max:10',
            'status' => 'required|string|in:active,inactive,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'show_once' => 'boolean',
            'is_dismissible' => 'boolean',
            'button_text' => 'required|string|max:50',
            'button_link' => 'nullable|url',
        ]);

        try {
            // Handle specific user IDs
            if ($validated['target_audience'] === 'specific' && empty($validated['target_user_ids'])) {
                return back()->withErrors(['target_user_ids' => 'You must select at least one user for specific targeting.'])->withInput();
            }

            if ($validated['target_audience'] !== 'specific') {
                $validated['target_user_ids'] = null;
            }

            // Create announcement
            $announcement = Announcement::create(array_merge($validated, [
                'created_by' => auth()->id(),
            ]));

            Log::info('Announcement created', [
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->route('admin.announcements.index')
                ->with('success', 'Announcement created successfully!');

        } catch (Exception $e) {
            Log::error('Failed to create announcement', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withErrors(['error' => 'Failed to create announcement. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show announcement details.
     */
    public function show(Announcement $announcement): View
    {
        $this->checkAdminAccess();
        $user = \Auth::user();

        $announcement->load(['creator', 'userViews.user']);

        // Get view statistics
        $viewStats = [
            'total_views' => $announcement->userViews()->count(),
            'unique_viewers' => $announcement->userViews()->distinct('user_id')->count(),
            'views_today' => $announcement->userViews()->today()->count(),
            'views_this_week' => $announcement->userViews()->thisWeek()->count(),
            'views_this_month' => $announcement->userViews()->thisMonth()->count(),
        ];

        // Get recent viewers
        $recentViewers = $announcement->userViews()
            ->with('user')
            ->latest('viewed_at')
            ->limit(10)
            ->get();

        return view('admin.announcements.show', compact(
            'announcement',
            'viewStats',
            'recentViewers',
            'user'
        ));
    }

    /**
     * Show edit announcement form.
     */
    public function edit(Announcement $announcement): View
    {
        $this->checkAdminAccess();
        $user = \Auth::user();
        
        return view('admin.announcements.edit', compact('announcement', 'user'));
    }

    /**
     * Update announcement.
     */
    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:info,success,warning,danger',
            'target_audience' => 'required|string|in:all,active,verified,kyc_verified,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
            'priority' => 'required|integer|min:1|max:10',
            'status' => 'required|string|in:active,inactive,scheduled',
            'scheduled_at' => 'nullable|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'show_once' => 'boolean',
            'is_dismissible' => 'boolean',
            'button_text' => 'required|string|max:50',
            'button_link' => 'nullable|url',
        ]);

        try {
            // Handle specific user IDs
            if ($validated['target_audience'] === 'specific' && empty($validated['target_user_ids'])) {
                return back()->withErrors(['target_user_ids' => 'You must select at least one user for specific targeting.'])->withInput();
            }

            if ($validated['target_audience'] !== 'specific') {
                $validated['target_user_ids'] = null;
            }

            $announcement->update($validated);

            Log::info('Announcement updated', [
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
                'updated_by' => auth()->id()
            ]);

            return redirect()
                ->route('admin.announcements.show', $announcement)
                ->with('success', 'Announcement updated successfully!');

        } catch (Exception $e) {
            Log::error('Failed to update announcement', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return back()
                ->withErrors(['error' => 'Failed to update announcement. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Delete announcement.
     */
    public function destroy(Announcement $announcement): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $announcement->delete();

            Log::info('Announcement deleted', [
                'announcement_id' => $announcement->id,
                'title' => $announcement->title,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Announcement deleted successfully.'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete announcement', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete announcement.'
            ], 500);
        }
    }

    /**
     * Toggle announcement status.
     */
    public function toggleStatus(Announcement $announcement): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $newStatus = $announcement->isActive() ? 'inactive' : 'active';
            $announcement->update(['status' => $newStatus]);

            Log::info('Announcement status toggled', [
                'announcement_id' => $announcement->id,
                'old_status' => $announcement->status,
                'new_status' => $newStatus,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Announcement {$newStatus} successfully.",
                'status' => $newStatus,
                'badge_class' => $announcement->status_badge_class
            ]);

        } catch (Exception $e) {
            Log::error('Failed to toggle announcement status', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update announcement status.'
            ], 500);
        }
    }

    /**
     * Get announcement statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $this->checkAdminAccess();
        
        $stats = $this->getAnnouncementStatistics();
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Search users for specific targeting.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'search' => 'required|string|min:2|max:50'
        ]);

        try {
            $users = User::where(function ($query) use ($validated) {
                $query->where('first_name', 'LIKE', '%' . $validated['search'] . '%')
                      ->orWhere('last_name', 'LIKE', '%' . $validated['search'] . '%')
                      ->orWhere('email', 'LIKE', '%' . $validated['search'] . '%')
                      ->orWhere('username', 'LIKE', '%' . $validated['search'] . '%');
            })
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'email', 'status'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'status' => $user->status,
                ];
            });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview announcement.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|string|in:info,success,warning,danger',
            'button_text' => 'required|string|max:50',
            'button_link' => 'nullable|url',
        ]);

        return response()->json([
            'success' => true,
            'preview' => [
                'title' => $validated['title'],
                'content' => $validated['content'],
                'type' => $validated['type'],
                'button_text' => $validated['button_text'],
                'button_link' => $validated['button_link'],
                'type_icon' => $this->getTypeIcon($validated['type']),
            ]
        ]);
    }

    /**
     * Reset views for announcement.
     */
    public function resetViews(Announcement $announcement): JsonResponse
    {
        $this->checkAdminAccess();

        try {
            $viewsCount = $announcement->userViews()->count();
            $announcement->userViews()->delete();

            Log::info('Announcement views reset', [
                'announcement_id' => $announcement->id,
                'views_deleted' => $viewsCount,
                'reset_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Reset {$viewsCount} views successfully."
            ]);

        } catch (Exception $e) {
            Log::error('Failed to reset announcement views', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset views.'
            ], 500);
        }
    }

    /**
     * Get target audience count.
     */
    public function getTargetCount(Request $request): JsonResponse
    {
        $this->checkAdminAccess();

        $validated = $request->validate([
            'target_audience' => 'required|string|in:all,active,verified,kyc_verified,specific',
            'target_user_ids' => 'nullable|array',
            'target_user_ids.*' => 'exists:users,id',
        ]);

        try {
            $count = $this->calculateTargetAudienceCount(
                $validated['target_audience'],
                $validated['target_user_ids'] ?? []
            );

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Target audience: {$count} users"
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to count target audience: ' . $e->getMessage()
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
     * Get announcement statistics.
     */
    private function getAnnouncementStatistics(): array
    {
        return [
            'total_announcements' => Announcement::count(),
            'active_announcements' => Announcement::active()->count(),
            'scheduled_announcements' => Announcement::scheduled()->count(),
            'expired_announcements' => Announcement::expired()->count(),
            'total_views' => UserAnnouncementView::count(),
            'unique_viewers' => UserAnnouncementView::distinct('user_id')->count(),
            'views_today' => UserAnnouncementView::today()->count(),
            'views_this_week' => UserAnnouncementView::thisWeek()->count(),
            'views_this_month' => UserAnnouncementView::thisMonth()->count(),
        ];
    }

    /**
     * Calculate target audience count.
     */
    private function calculateTargetAudienceCount(string $audience, array $userIds = []): int
    {
        return match($audience) {
            'all' => User::count(),
            'active' => User::active()->count(),
            'verified' => User::verified()->count(),
            'kyc_verified' => User::kycVerified()->count(),
            'specific' => count($userIds),
            default => 0
        };
    }

    /**
     * Get type icon.
     */
    private function getTypeIcon(string $type): string
    {
        return match($type) {
            'success' => 'iconamoon:check-circle-duotone',
            'warning' => 'iconamoon:warning-duotone',
            'danger' => 'iconamoon:close-circle-duotone',
            'info' => 'iconamoon:information-circle-duotone',
            default => 'iconamoon:notification-duotone'
        };
    }
}