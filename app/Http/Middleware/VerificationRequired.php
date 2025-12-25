<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificationRequired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$verifications): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $profile = $user->profile;

        // If no specific verifications are specified, check both phone and KYC
        if (empty($verifications)) {
            $verifications = ['phone', 'kyc'];
        }

        foreach ($verifications as $verification) {
            switch ($verification) {
                case 'phone':
                    if (!$profile || !$profile->phone_verified) {
                        return redirect()->route('phone.verify')
                            ->with('error', 'Phone verification is required to access this feature.');
                    }
                    break;

                case 'kyc':
                    if (!$profile || $profile->kyc_status !== 'verified') {
                        $message = $profile && $profile->kyc_status === 'pending' 
                            ? 'Your KYC verification is pending. Please wait for approval.'
                            : 'KYC verification is required to access this feature.';
                        
                        return redirect()->route('kyc.index')
                            ->with('error', $message);
                    }
                    break;

                case 'email':
                    if (!$user->hasVerifiedEmail()) {
                        return redirect()->route('verification.notice')
                            ->with('error', 'Email verification is required to access this feature.');
                    }
                    break;

                case 'active':
                    if ($user->status !== 'active') {
                        return redirect()->route('dashboard')
                            ->with('error', 'Your account is not active. Please contact support.');
                    }
                    break;
            }
        }

        return $next($request);
    }
}