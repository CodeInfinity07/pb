<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordChange
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->must_change_password) {
            // Allow access to password change routes
            if (!$request->routeIs('password.change.*') && !$request->routeIs('logout')) {
                return redirect()->route('password.change.form')
                    ->with('warning', 'You must change your temporary password before continuing.');
            }
        }

        return $next($request);
    }
}