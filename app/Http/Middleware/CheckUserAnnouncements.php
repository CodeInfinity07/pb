<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\AnnouncementService;

class CheckUserAnnouncements
{
    protected $announcementService;

    public function __construct(AnnouncementService $announcementService)
    {
        $this->announcementService = $announcementService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only check for announcements on regular web requests for authenticated users
        if (!$request->expectsJson() && 
            Auth::check() && 
            $request->route() && 
            !$request->is('admin/*') && 
            !$request->is('api/*')) {
            
            $user = Auth::user();
            
            // Get pending announcements for this user
            $announcements = $this->announcementService->getPendingAnnouncementsForUser($user);
            
            if ($announcements->isNotEmpty()) {
                // Share announcements with all views
                View::share('pendingAnnouncements', $announcements);
                
                // Add script to check for announcements on page load
                $this->injectAnnouncementScript($response, $announcements);
            }
        }

        return $response;
    }

    /**
     * Inject announcement checking script into the response.
     */
    private function injectAnnouncementScript($response, $announcements)
    {
        $content = $response->getContent();
        
        // Only inject if there's a closing body tag
        if (strpos($content, '</body>') !== false) {
            $script = $this->generateAnnouncementScript($announcements);
            $content = str_replace('</body>', $script . '</body>', $content);
            $response->setContent($content);
        }
    }

    /**
     * Generate the announcement checking script.
     */
    private function generateAnnouncementScript($announcements)
    {
        $announcementData = $announcements->map(function ($announcement) {
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
                'type_badge_class' => $announcement->type_badge_class,
            ];
        })->toJson();

        return "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.checkUserAnnouncements === 'function') {
                window.checkUserAnnouncements(" . $announcementData . ");
            }
        });
        </script>";
    }
}