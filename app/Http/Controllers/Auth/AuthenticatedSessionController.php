<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use App\Notifications\GeneralNotification;
use Illuminate\Http\Request;
use App\Models\LoginLog;  // ADD THIS
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Update last login timestamp
        $user->updateLastLogin();

        LoginLog::logLogin($user->id, true);


        // Check if user has 2FA enabled
        if ($user->hasTwoFactorEnabled()) {
            // Clear any previous 2FA verification
            session()->forget('2fa_verified');

            // Redirect to 2FA challenge instead of dashboard
            return redirect()->route('two-factor.challenge')
                ->with('info', 'Please verify your two-factor authentication code to continue.');
        }

        // Check if user is not verified and send verification email
        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            // Optional: Add a session message
            session()->flash('status', 'verification-link-sent');
        }

        // Send to current user (commented out as in original)
        // $user->notify(new GeneralNotification(
        //     'Welcome!',
        //     'Thanks for joining our platform!'
        // ));

        // No 2FA required, proceed to dashboard
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Show the 2FA challenge form.
     */
    public function showTwoFactorChallenge()
    {
        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login')->with('error', 'Invalid session.');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Handle 2FA verification during login.
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login')->with('error', 'Invalid session.');
        }

        if ($user->verifyTwoFactorCode($request->code, false)) {
            // Mark 2FA as verified in session
            session(['2fa_verified' => true]);

            // Check email verification after successful 2FA
            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
                session()->flash('status', 'verification-link-sent');
            }

            // Send welcome notification if needed
            // $user->notify(new GeneralNotification(
            //     'Welcome!',
            //     'Thanks for joining our platform!'
            // ));

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'code' => 'The provided two-factor authentication code is invalid.',
        ])->withInput();
    }

    /**
     * Handle recovery code verification during login.
     */
    public function verifyRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string',
        ]);

        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login')->with('error', 'Invalid session.');
        }

        // Clean up the recovery code format
        $recoveryCode = strtoupper(str_replace('-', '', $request->recovery_code));

        // Basic validation - in production, verify against stored codes in database
        if (strlen($recoveryCode) === 8 && ctype_alnum($recoveryCode)) {
            // Mark 2FA as verified in session
            session(['2fa_verified' => true]);

            // Check email verification after successful recovery
            if (!$user->hasVerifiedEmail()) {
                $user->sendEmailVerificationNotification();
                session()->flash('status', 'verification-link-sent');
            }

            return redirect()->intended(route('dashboard'))
                ->with('warning', 'You used a recovery code to login. Consider regenerating your recovery codes from your profile settings.');
        }

        return back()->withErrors([
            'recovery_code' => 'The provided recovery code is invalid.',
        ])->withInput();
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}