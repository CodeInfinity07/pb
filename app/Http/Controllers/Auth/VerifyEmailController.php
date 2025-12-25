<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            
            // Update user status to active
            $request->user()->update(['status' => 'active']);
            
            \Log::info('Email verified successfully', [
                'user_id' => $request->user()->id,
                'email' => $request->user()->email
            ]);
        }

        return redirect()->intended(route('dashboard'))
        ->with('success', 'Email verified successfully! Welcome to our platform.');
    }
}
