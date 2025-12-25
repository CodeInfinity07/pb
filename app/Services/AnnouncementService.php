<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Models\UserAnnouncementView;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnnouncementService
{
    /**
     * Get pending announcements for a specific user.
     */
    public function getPendingAnnouncementsForUser(User $user): Collection
    {
        $cacheKey = "user_announcements_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return Announcement::query()->shouldShowNow()
                ->where(function ($query) use ($user) {
                    // Target audience filtering
                    $query->where('target_audience', 'all')
                          ->orWhere(function ($q) use ($user) {
                              $q->where('target_audience', 'active')
                                ->where(function () use ($user) {
                                    return $user->isActive();
                                });
                          })
                          ->orWhere(function ($q) use ($user) {
                              $q->where('target_audience', 'verified')
                                ->where(function () use ($user) {
                                    return $user->isVerified();
                                });
                          })
                          ->orWhere(function ($q) use ($user) {
                              $q->where('target_audience', 'kyc_verified')
                                ->where(function () use ($user) {
                                    return $user->isKycVerified();
                                });
                          })
                          ->orWhere(function ($q) use ($user) {
                              $q->where('target_audience', 'specific')
                                ->whereJsonContains('target_user_ids', $user->id);
                          });
                })
                ->whereDoesntHave('userViews', function ($viewQuery) use ($user) {
                    $viewQuery->where('user_id', $user->id);
                })
                ->byPriority()
                ->limit(3) // Limit to prevent overwhelming users
                ->get();
        });
    }

    /**
     * Mark announcement as viewed by user.
     */
    public function markAsViewed(Announcement $announcement, User $user): bool
    {
        try {
            // Check if already viewed
            if ($announcement->hasUserViewed($user)) {
                return true;
            }

            // Create view record
            UserAnnouncementView::create([
                'user_id' => $user->id,
                'announcement_id' => $announcement->id,
                'viewed_at' => now(),
            ]);

            // Clear user's announcement cache
            Cache::forget("user_announcements_{$user->id}");

            Log::info('Announcement marked as viewed', [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
                'announcement_title' => $announcement->title
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to mark announcement as viewed', [
                'announcement_id' => $announcement->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get announcement statistics for admin.
     */
    public function getStatistics(): array
    {
        return Cache::remember('announcement_statistics', 600, function () {
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
        });
    }

    /**
     * Get announcement view analytics.
     */
    public function getViewAnalytics(Announcement $announcement): array
    {
        return [
            'total_views' => $announcement->userViews()->count(),
            'unique_viewers' => $announcement->userViews()->distinct('user_id')->count(),
            'views_today' => $announcement->userViews()->today()->count(),
            'views_this_week' => $announcement->userViews()->thisWeek()->count(),
            'views_this_month' => $announcement->userViews()->thisMonth()->count(),
            'view_rate' => $this->calculateViewRate($announcement),
            'engagement_score' => $this->calculateEngagementScore($announcement),
        ];
    }

    /**
     * Calculate view rate percentage for announcement.
     */
    private function calculateViewRate(Announcement $announcement): float
    {
        $targetCount = $this->getTargetAudienceCount($announcement);
        
        if ($targetCount === 0) {
            return 0;
        }

        $viewCount = $announcement->userViews()->distinct('user_id')->count();
        return round(($viewCount / $targetCount) * 100, 2);
    }

    /**
     * Calculate engagement score for announcement.
     */
    private function calculateEngagementScore(Announcement $announcement): float
    {
        $views = $announcement->userViews()->count();
        $uniqueViews = $announcement->userViews()->distinct('user_id')->count();
        $daysActive = max(1, $announcement->created_at->diffInDays(now()));
        
        // Basic engagement formula
        $score = ($uniqueViews * 10) + ($views * 2);
        $dailyAverage = $score / $daysActive;
        
        return round($dailyAverage, 2);
    }

    /**
     * Get target audience count for announcement.
     */
    public function getTargetAudienceCount(Announcement $announcement): int
    {
        return match($announcement->target_audience) {
            'all' => User::count(),
            'active' => User::active()->count(),
            'verified' => User::verified()->count(),
            'kyc_verified' => User::kycVerified()->count(),
            'specific' => count($announcement->target_user_ids ?? []),
            default => 0
        };
    }

    /**
     * Clean up expired announcements.
     */
    public function cleanupExpired(): int
    {
        $expiredCount = Announcement::expired()
            ->where('status', '!=', 'inactive')
            ->update(['status' => 'inactive']);

        if ($expiredCount > 0) {
            Log::info("Cleaned up {$expiredCount} expired announcements");
            
            // Clear caches
            Cache::forget('announcement_statistics');
            $this->clearAllUserCaches();
        }

        return $expiredCount;
    }

    /**
     * Activate scheduled announcements.
     */
    public function activateScheduled(): int
    {
        $activatedCount = Announcement::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->update(['status' => 'active']);

        if ($activatedCount > 0) {
            Log::info("Activated {$activatedCount} scheduled announcements");
            
            // Clear caches
            Cache::forget('announcement_statistics');
            $this->clearAllUserCaches();
        }

        return $activatedCount;
    }

    /**
     * Clear all user announcement caches.
     */
    public function clearAllUserCaches(): void
    {
        // This is a simplified approach - in production you might want to use cache tags
        Cache::flush();
    }

    /**
     * Get announcements for specific user with pagination.
     */
    public function getAnnouncementsForUser(User $user, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Announcement::where(function ($query) use ($user) {
            $query->where('target_audience', 'all')
                  ->orWhere(function ($q) use ($user) {
                      $q->where('target_audience', 'active')
                        ->where(function () use ($user) {
                            return $user->isActive();
                        });
                  })
                  ->orWhere(function ($q) use ($user) {
                      $q->where('target_audience', 'verified')
                        ->where(function () use ($user) {
                            return $user->isVerified();
                        });
                  })
                  ->orWhere(function ($q) use ($user) {
                      $q->where('target_audience', 'kyc_verified')
                        ->where(function () use ($user) {
                            return $user->isKycVerified();
                        });
                  })
                  ->orWhere(function ($q) use ($user) {
                      $q->where('target_audience', 'specific')
                        ->whereJsonContains('target_user_ids', $user->id);
                  });
        })
        ->with(['userViews' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }])
        ->byPriority()
        ->latest()
        ->paginate($perPage);
    }

    /**
     * Check if user can view announcement.
     */
    public function canUserView(Announcement $announcement, User $user): bool
    {
        return $announcement->shouldShowToUser($user);
    }

    /**
     * Get announcement modal HTML for user.
     */
    public function getAnnouncementModalHtml(Announcement $announcement): string
    {
        $typeColors = [
            'info' => 'primary',
            'success' => 'success',
            'warning' => 'warning',
            'danger' => 'danger'
        ];

        $color = $typeColors[$announcement->type] ?? 'primary';
        $dismissible = $announcement->is_dismissible;
        $buttonLink = $announcement->button_link;
        $buttonText = $announcement->button_text;

        return "
        <div class='modal fade announcement-modal' id='announcementModal{$announcement->id}' tabindex='-1' data-bs-backdrop='" . ($dismissible ? 'true' : 'static') . "' data-bs-keyboard='" . ($dismissible ? 'true' : 'false') . "'>
            <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content border-0 shadow-lg'>
                    <div class='modal-header bg-{$color} text-white border-0'>
                        <h5 class='modal-title d-flex align-items-center'>
                            <iconify-icon icon='{$announcement->type_icon}' class='me-2 fs-4'></iconify-icon>
                            {$announcement->title}
                        </h5>
                        " . ($dismissible ? "<button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal' aria-label='Close'></button>" : "") . "
                    </div>
                    <div class='modal-body'>
                        <div class='announcement-content'>
                            " . nl2br(e($announcement->content)) . "
                        </div>
                    </div>
                    <div class='modal-footer border-0'>
                        " . ($buttonLink 
                            ? "<a href='{$buttonLink}' class='btn btn-{$color}' onclick='markAnnouncementViewed({$announcement->id})'>{$buttonText}</a>"
                            : "<button type='button' class='btn btn-{$color}' onclick='markAnnouncementViewed({$announcement->id})' data-bs-dismiss='modal'>{$buttonText}</button>"
                        ) . "
                    </div>
                </div>
            </div>
        </div>";
    }
}