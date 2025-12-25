<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ImpersonationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $impersonationData = $this->getImpersonationData();
            
            // Share impersonation data with all views
            View::share('impersonationStatus', $impersonationData);
            
            // Add impersonation info to request for easy access in controllers
            $request->attributes->set('impersonation', $impersonationData);
        }

        return $next($request);
    }

    /**
     * Get current impersonation data.
     *
     * @return array|null
     */
    private function getImpersonationData(): ?array
    {
        $originalAdminId = Session::get('impersonation.original_admin_id');
        $targetUserId = Session::get('impersonation.target_user_id');
        $startedAt = Session::get('impersonation.started_at');

        // If no impersonation session, return null
        if (!$originalAdminId || !$targetUserId) {
            return null;
        }

        try {
            $originalAdmin = User::find($originalAdminId);
            $currentUser = Auth::user();

            // Validate that the session is still valid
            if (!$originalAdmin || !$currentUser || $currentUser->id != $targetUserId) {
                // Clear invalid session data
                Session::forget('impersonation.original_admin_id');
                Session::forget('impersonation.target_user_id');
                Session::forget('impersonation.started_at');
                return null;
            }

            return [
                'is_impersonating' => true,
                'original_admin' => [
                    'id' => $originalAdmin->id,
                    'name' => $originalAdmin->full_name,
                    'email' => $originalAdmin->email,
                ],
                'current_user' => [
                    'id' => $currentUser->id,
                    'name' => $currentUser->full_name,
                    'email' => $currentUser->email,
                ],
                'started_at' => $startedAt,
                'duration' => $startedAt ? now()->diffForHumans($startedAt, true) : 'Unknown',
                'can_stop' => true,
            ];

        } catch (\Exception $e) {
            // Clear session on any error
            Session::forget('impersonation.original_admin_id');
            Session::forget('impersonation.target_user_id');
            Session::forget('impersonation.started_at');
            
            \Log::warning('Impersonation session error: ' . $e->getMessage());
            return null;
        }
    }
}