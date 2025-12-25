<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for admin routes and API routes
        if ($this->shouldSkipMaintenanceCheck($request)) {
            return $next($request);
        }

        // Check if maintenance mode is enabled
        if (isMaintenanceMode()) {
            // Allow admins to bypass maintenance mode
            if (auth()->check() && auth()->user()->isAdmin()) {
                return $next($request);
            }

            // Return maintenance page for regular users
            return $this->maintenanceResponse($request);
        }

        return $next($request);
    }

    /**
     * Determine if maintenance check should be skipped.
     */
    private function shouldSkipMaintenanceCheck(Request $request): bool
    {
        $skipPatterns = [
            'admin/*',
            'api/*',
            'login',
            'logout',
            'maintenance',
            '_debugbar/*',
        ];

        foreach ($skipPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return maintenance mode response.
     */
    private function maintenanceResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Platform is currently under maintenance. Please try again later.',
                'maintenance_mode' => true,
                'retry_after' => 3600 // Suggest retry after 1 hour
            ], 503);
        }

        // Return maintenance view
        return response()->view('maintenance', [
            'platform_name' => getSetting('app_name', config('app.name')),
            'support_email' => getSetting('support_email', 'support@mlmtrial.live'),
            'message' => 'We are currently performing scheduled maintenance to improve your experience. Please check back soon.',
        ], 503);
    }
}