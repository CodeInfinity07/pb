<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserAnnouncementController extends Controller
{
    protected $announcementService;

    public function __construct(AnnouncementService $announcementService)
    {
        $this->announcementService = $announcementService;
    }

    /**
     * Get pending announcements for the authenticated user.
     */
    public function getPending(): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user is authenticated
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $announcements = $this->announcementService->getPendingAnnouncementsForUser($user);

            return response()->json([
                'success' => true,
                'announcements' => $announcements->map(function ($announcement) {
                    return [
                        'id' => $announcement->id,
                        'title' => $announcement->title,
                        'content' => $announcement->content,
                        'type' => $announcement->type,
                        'priority' => $announcement->priority,
                        'is_dismissible' => $announcement->is_dismissible,
                        'button_text' => $announcement->button_text,
                        'button_link' => $announcement->button_link,
                        'type_icon' => $announcement->type_icon,
                        'modal_html' => $this->announcementService->getAnnouncementModalHtml($announcement),
                    ];
                }),
                'count' => $announcements->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get pending announcements', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load announcements'
            ], 500);
        }
    }

    /**
     * Mark announcement as viewed by the authenticated user.
     */
    public function markViewed(Announcement $announcement): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user can view this announcement
            if (!$this->announcementService->canUserView($announcement, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this announcement'
                ], 403);
            }

            // Mark as viewed
            $success = $this->announcementService->markAsViewed($announcement, $user);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Announcement marked as viewed'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark announcement as viewed'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Failed to mark announcement as viewed', [
                'announcement_id' => $announcement->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark announcement as viewed'
            ], 500);
        }
    }

    /**
     * Get announcement history for the authenticated user.
     */
    public function getHistory(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = min($request->get('per_page', 10), 50); // Max 50 per page

            $announcements = $this->announcementService->getAnnouncementsForUser($user, $perPage);

            return response()->json([
                'success' => true,
                'announcements' => $announcements->getCollection()->map(function ($announcement) {
                    $hasViewed = $announcement->userViews->isNotEmpty();
                    $viewedAt = $hasViewed ? $announcement->userViews->first()->viewed_at : null;

                    return [
                        'id' => $announcement->id,
                        'title' => $announcement->title,
                        'content' => $announcement->content,
                        'type' => $announcement->type,
                        'created_at' => $announcement->created_at->format('M d, Y \a\t g:i A'),
                        'has_viewed' => $hasViewed,
                        'viewed_at' => $viewedAt ? $viewedAt->format('M d, Y \a\t g:i A') : null,
                        'viewed_ago' => $viewedAt ? $viewedAt->diffForHumans() : null,
                        'type_icon' => $announcement->type_icon,
                        'type_badge_class' => $announcement->type_badge_class,
                    ];
                }),
                'pagination' => [
                    'current_page' => $announcements->currentPage(),
                    'last_page' => $announcements->lastPage(),
                    'per_page' => $announcements->perPage(),
                    'total' => $announcements->total(),
                    'has_more_pages' => $announcements->hasMorePages(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get announcement history', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load announcement history'
            ], 500);
        }
    }

    /**
     * Get a specific announcement for viewing.
     */
    public function show(Announcement $announcement): JsonResponse
    {
        try {
            $user = Auth::user();

            // Check if user can view this announcement
            if (!$this->announcementService->canUserView($announcement, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to view this announcement'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'announcement' => [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'type' => $announcement->type,
                    'is_dismissible' => $announcement->is_dismissible,
                    'button_text' => $announcement->button_text,
                    'button_link' => $announcement->button_link,
                    'type_icon' => $announcement->type_icon,
                    'created_at' => $announcement->created_at->format('M d, Y \a\t g:i A'),
                    'has_viewed' => $announcement->hasUserViewed($user),
                    'modal_html' => $this->announcementService->getAnnouncementModalHtml($announcement),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get announcement', [
                'announcement_id' => $announcement->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load announcement'
            ], 500);
        }
    }

    /**
     * Get announcement statistics for user dashboard.
     */
    public function getUserStats(): JsonResponse
    {
        try {
            $user = Auth::user();

            $stats = [
                'total_announcements' => $this->announcementService->getAnnouncementsForUser($user, 1000)->total(),
                'viewed_announcements' => $user->userAnnouncementViews()->count(),
                'pending_announcements' => $this->announcementService->getPendingAnnouncementsForUser($user)->count(),
                'latest_announcement' => null,
            ];

            // Get latest announcement
            $latestAnnouncement = $this->announcementService->getAnnouncementsForUser($user, 1)->first();
            if ($latestAnnouncement) {
                $stats['latest_announcement'] = [
                    'id' => $latestAnnouncement->id,
                    'title' => $latestAnnouncement->title,
                    'type' => $latestAnnouncement->type,
                    'created_at' => $latestAnnouncement->created_at->diffForHumans(),
                    'has_viewed' => $latestAnnouncement->userViews->isNotEmpty(),
                ];
            }

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get user announcement stats', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load announcement statistics'
            ], 500);
        }
    }
}