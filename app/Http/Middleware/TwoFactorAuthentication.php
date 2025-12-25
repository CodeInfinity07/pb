<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if (session('2fa_verified')) {
            return $next($request);
        }

        // Allow 2FA-related routes
        $allowedRoutes = [
            'two-factor.challenge',
            'two-factor.verify', 
            'two-factor.recovery',
            'logout',
        ];

        if (in_array($request->route()->getName(), $allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('two-factor.challenge');
    }
}