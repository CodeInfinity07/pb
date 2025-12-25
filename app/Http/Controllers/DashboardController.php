<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use App\Models\UserInvestment;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Models\InvestmentPlan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FA\Google2FA;


class DashboardController extends Controller
{
    /**
     * Check if user is admin and redirect to admin route if needed
     */
    private function checkAdminRedirect(string $routeName, array $parameters = []): ?RedirectResponse
    {
        $user = Auth::user();
        if ($user && $user->hasRole('admin')) {
            return redirect()->route('admin.' . $routeName, $parameters);
        }
        return null;
    }

    /**
     * Display the enhanced user dashboard.
     */
    public function index(Request $request): View|RedirectResponse
    {
        // Check for admin redirect
        if ($redirect = $this->checkAdminRedirect('dashboard')) {
            return $redirect;
        }

        $user = Auth::user();

        // Load all necessary relationships (without crypto wallets - we'll query those directly)
        $user->load([
            'profile',
            'transactions' => function ($query) {
                $query->latest()->limit(50); // Limit to recent transactions for performance
            },
            'directReferrals.profile'
        ]);

        // Get comprehensive dashboard data
        $dashboardData = $this->getComprehensiveDashboardData($user);

        return view('dashboards.index', compact('user', 'dashboardData'));
    }

    /**
     * Support section methods - with admin redirects
     */
    public function contact(Request $request): View|RedirectResponse
    {
        // Check for admin redirect
        if ($redirect = $this->checkAdminRedirect('contact')) {
            return $redirect;
        }

        $user = Auth::user();
        $user->load(['profile']);
        return view('support.contact-us', compact('user'));
    }

    public function faqs(Request $request): View|RedirectResponse
    {
        // Check for admin redirect
        if ($redirect = $this->checkAdminRedirect('faqs')) {
            return $redirect;
        }

        $user = Auth::user();
        $user->load(['profile']);
        return view('support.faqs', compact('user'));
    }

    public function news(Request $request): View|RedirectResponse
    {
        // Check for admin redirect
        if ($redirect = $this->checkAdminRedirect('news')) {
            return $redirect;
        }

        $user = Auth::user();
        $user->load(['profile']);
        return view('support.timeline', compact('user'));
    }

    public function about(Request $request): View|RedirectResponse
    {
        // Check for admin redirect
        if ($redirect = $this->checkAdminRedirect('about')) {
            return $redirect;
        }

        $user = Auth::user();
        $user->load(['profile']);
        return view('support.about-us', compact('user'));
    }

    /**
     * Profile management methods - with admin redirects
     */
    public function profile(): View|RedirectResponse
    {
        $user = Auth::user();
        $user->load('profile');

        // If user is admin, return admin profile view directly
        if ($user->hasRole('admin')) {
            return view('admin.profile.edit', compact('user'));
        }

        // Otherwise return regular user profile view
        return view('profile.edit', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        // Check for admin redirect

        $user = Auth::user();

        // Capture tab_source early for session flashing
        $tabSource = $request->input('tab_source', 'general');

        $validated = $request->validate([
            // User table fields
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],

            // UserProfile table fields
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'country' => 'required|string|max:2',
            'city' => 'required|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',

            // Business information
            'business_name' => 'nullable|string|max:255',
            'business_address' => 'nullable|string|max:1000',

            // Social media links
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'telegram_username' => 'nullable|string|max:255|regex:/^@?[a-zA-Z0-9_]+$/',
            'whatsapp_number' => 'nullable|string|max:255',

            // Notification preferences
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',

            // Localization
            'preferred_language' => 'required|string|max:5|in:en,es,fr,de,ja',
            'timezone' => 'required|string|max:255',

            // Tab source tracking
            'tab_source' => 'nullable|string|in:general,preferences',
        ]);

        try {
            DB::beginTransaction();

            // Handle avatar upload
            $avatarPath = null;
            if ($request->hasFile('avatar')) {
                // Delete old avatar if exists
                if ($user->profile && $user->profile->avatar) {
                    Storage::disk('public')->delete($user->profile->avatar);
                }

                $avatarPath = $request->file('avatar')->store('avatars', 'public');
            }

            // Update user basic information
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'phone' => $validated['phone'],
            ]);

            // Prepare profile data
            $profileData = [
                'country' => $validated['country'],
                'city' => $validated['city'],
                'state_province' => $validated['state_province'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'address' => $validated['address'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'business_name' => $validated['business_name'] ?? null,
                'business_address' => $validated['business_address'] ?? null,
                'facebook_url' => $validated['facebook_url'] ?? null,
                'twitter_url' => $validated['twitter_url'] ?? null,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'telegram_username' => $validated['telegram_username'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'email_notifications' => $request->has('email_notifications') ? 1 : 0,
                'sms_notifications' => $request->has('sms_notifications') ? 1 : 0,
                'preferred_language' => $validated['preferred_language'],
                'timezone' => $validated['timezone'],
            ];

            // Add avatar if uploaded
            if ($avatarPath) {
                $profileData['avatar'] = $avatarPath;
            }

            // Update or create profile
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            DB::commit();

            Log::info('User profile updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'updated_fields' => array_keys($validated),
                'avatar_updated' => $avatarPath ? true : false,
                'tab_source' => $tabSource,
            ]);

            // Determine success message based on tab source
            $successMessage = $tabSource === 'preferences'
                ? 'Preferences updated successfully!'
                : 'Profile updated successfully!';

            // Redirect based on user role with tab source in session
            $routeName = $user->hasRole('admin') ? 'admin.profile' : 'user.profile';

            return redirect()->route($routeName)
                ->with('success', $successMessage)
                ->with('tab_source', $tabSource); // Flash tab source to session

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tab_source' => $tabSource,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update profile. Please try again.')
                ->with('tab_source', $tabSource); // Preserve tab source on error
        }
    }

    // Replace your existing updatePassword method in DashboardController
    public function updatePassword(Request $request): RedirectResponse
    {

        $user = Auth::user();

        // Rate limiting for password changes (max 5 attempts per hour per user)
        $key = 'password-change-' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            return redirect()->back()
                ->with('error', "Too many password change attempts. Please try again in {$minutes} minutes.");
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'current_password.required' => 'Current password is required.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            DB::beginTransaction();

            // Check if the new password is the same as the current password
            if (Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'The new password must be different from your current password.',
                ]);
            }

            $user->update([
                'password' => Hash::make($validated['password'])
            ]);

            // Clear the rate limiter on successful password change
            RateLimiter::clear($key);

            DB::commit();

            Log::info('User password updated successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
            ]);

            // Redirect based on user role
            $routeName = $user->hasRole('admin') ? 'admin.profile' : 'user.profile';
            return redirect()->route($routeName)
                ->with('success', 'Password updated successfully!');

        } catch (ValidationException $e) {
            DB::rollBack();
            RateLimiter::hit($key, 3600); // 1 hour

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\Exception $e) {
            DB::rollBack();
            RateLimiter::hit($key, 3600); // 1 hour

            Log::error('Password update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update password. Please try again.')
                ->withInput();
        }
    }

    /**
     * Two-Factor Authentication Methods
     */

    /**
     * Show 2FA setup page.
     */
    public function twoFactorSetup(): View|RedirectResponse
    {
        $user = Auth::user();
        $user->load('profile');
    
        $google2fa = new Google2FA();
    
        // Always generate a fresh secret for new setup attempts
        $secret = $google2fa->generateSecretKey(32);
        $user->setGoogle2FASecret($secret);
    
        // Generate QR Code URL with role-specific app name
        $appName = $user->hasRole('admin') 
            ? config('app.name') . ' - Admin' 
            : config('app.name');
            
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret
        );
    
        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();
    
        // Return appropriate view based on user role
        $viewName = $user->hasRole('admin') 
            ? 'admin.profile.two-factor.setup' 
            : 'profile.two-factor.setup';
    
        return view($viewName, compact('user', 'qrCodeUrl', 'secret', 'backupCodes'));
    }

    /**
     * Enable 2FA.
     */
    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user->google2fa_secret) {
            return redirect()->back()->with('error', '2FA secret not found. Please try setup again.');
        }

        // Verify code during setup (before 2FA is enabled)
        if ($user->verifyTwoFactorCode($request->code, true)) {
            $user->enableTwoFactor();

            $routeName = $user->hasRole('admin') ? 'admin.profile' : 'user.profile';
            return redirect()->route($routeName)
                ->with('success', '2FA has been successfully enabled for your account!');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Invalid verification code. Please try again.');
    }

    /**
     * Disable 2FA.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->back()->with('error', '2FA is not enabled on your account.');
        }

        // Verify code for already enabled 2FA
        if ($user->verifyTwoFactorCode($request->code, false)) {
            $user->disableTwoFactor();

            $routeName = $user->hasRole('admin') ? 'admin.profile' : 'user.profile';
            return redirect()->route($routeName)
                ->with('success', '2FA has been disabled for your account.');
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Invalid verification code. Please try again.');
    }

    /**
     * Show 2FA verification page (for login).
     */
    public function twoFactorChallenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    /**
     * Verify 2FA code during login.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login')->with('error', 'Invalid session.');
        }

        if ($user->verifyTwoFactorCode($request->code, false)) {
            session(['2fa_verified' => true]);
            return redirect()->intended(route('user.dashboard'));
        }

        return redirect()->back()
            ->withInput()
            ->withErrors(['code' => 'The provided two-factor authentication code is invalid.']);
    }

    /**
 * Show recovery codes.
 */
public function showRecoveryCodes(): View|RedirectResponse
{
    $user = Auth::user();

    if (!$user->hasTwoFactorEnabled()) {
        return redirect()->route('user.profile')->with('error', '2FA is not enabled on your account.');
    }

    // Generate recovery codes for display
    $recoveryCodes = $this->generateBackupCodes();

    // Return appropriate view based on user role
    $viewName = $user->hasRole('admin') 
        ? 'admin.profile.two-factor.recovery-codes' 
        : 'profile.two-factor.recovery-codes';

    return view($viewName, compact('user', 'recoveryCodes'));
}

    /**
     * Regenerate recovery codes.
     */
    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return redirect()->back()->with('error', '2FA is not enabled on your account.');
        }

        if ($user->verifyTwoFactorCode($request->code, false)) {
            // In a production app, you would store the new codes in database
            $newRecoveryCodes = $this->generateBackupCodes();

            return redirect()->route('user.two-factor.recovery')
                ->with('success', 'New recovery codes have been generated!')
                ->with('new_codes', $newRecoveryCodes);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', 'Invalid verification code. Please try again.');
    }

    /**
     * Handle recovery code verification during login.
     */
    public function verifyRecoveryCode(Request $request): RedirectResponse
    {
        $request->validate([
            'recovery_code' => 'required|string',
        ]);

        $user = Auth::user();

        if (!$user || !$user->hasTwoFactorEnabled()) {
            return redirect()->route('login')->with('error', 'Invalid session.');
        }

        // Format recovery code
        $recoveryCode = strtoupper(str_replace('-', '', $request->recovery_code));

        // Basic validation - in production, verify against stored codes
        if (strlen($recoveryCode) === 8 && ctype_alnum($recoveryCode)) {
            session(['2fa_verified' => true]);

            return redirect()->intended(route('user.dashboard'))
                ->with('warning', 'You used a recovery code to login. Consider regenerating your recovery codes.');
        }

        return redirect()->back()
            ->withInput()
            ->withErrors(['recovery_code' => 'The provided recovery code is invalid.']);
    }

    /**
     * Generate backup codes.
     */
    private function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid()), 0, 4) . '-' . substr(md5(uniqid()), 0, 4));
        }
        return $codes;
    }

    /**
     * AJAX endpoints for real-time updates - with admin checks
     */
    public function getBalanceUpdate(Request $request): JsonResponse
    {
        $user = Auth::user();

        // For AJAX requests, we might want to return an error instead of redirecting
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Admin users should use admin endpoints',
                'redirect_url' => route('admin.dashboard')
            ], 403);
        }

        return response()->json([
            'total_balance' => $this->getTotalBalance($user),
            'available_balance' => $this->getAvailableBalance($user),
            'locked_balance' => $this->getLockedBalance($user),
        ]);
    }

    public function getEarningsUpdate(Request $request): JsonResponse
    {
        $user = Auth::user();

        // For AJAX requests, we might want to return an error instead of redirecting
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Admin users should use admin endpoints',
                'redirect_url' => route('admin.dashboard')
            ], 403);
        }

        return response()->json([
            'total_earnings' => $this->getTotalEarnings($user),
            'today_earnings' => $this->getTodayEarnings($user),
            'this_month_earnings' => $this->getThisMonthEarnings($user),
        ]);
    }

    public function getRecentActivity(Request $request): JsonResponse
    {
        $user = Auth::user();

        // For AJAX requests, we might want to return an error instead of redirecting
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Admin users should use admin endpoints',
                'redirect_url' => route('admin.dashboard')
            ], 403);
        }

        $limit = $request->get('limit', 10);

        return response()->json([
            'transactions' => $this->getRecentTransactions($user, $limit),
        ]);
    }

    public function getReferralStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        // For AJAX requests, we might want to return an error instead of redirecting
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Admin users should use admin endpoints',
                'redirect_url' => route('admin.dashboard')
            ], 403);
        }

        return response()->json([
            'total_referrals' => $this->getTotalReferrals($user),
            'active_referrals' => $this->getActiveReferrals($user),
            'total_referral_earnings' => $this->getTotalReferralEarnings($user),
            'pending_commissions' => $this->getPendingCommissions($user),
        ]);
    }

    public function getPendingCommissionsDetail(Request $request): JsonResponse
    {
        $user = Auth::user();

        // For AJAX requests, we might want to return an error instead of redirecting
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Admin users should use admin endpoints',
                'redirect_url' => route('admin.dashboard')
            ], 403);
        }

        $pendingCommissions = $user->transactions()
            ->where('type', Transaction::TYPE_COMMISSION)
            ->where('status', Transaction::STATUS_PENDING)
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email');
                }
            ])
            ->latest()
            ->get();

        $summary = [
            'total_pending_amount' => $pendingCommissions->sum('amount'),
            'total_commissions' => $pendingCommissions->count(),
            'earliest_date' => $pendingCommissions->min('created_at'),
            'farthest_date' => $pendingCommissions->max('created_at'),
            'ready_commissions' => $pendingCommissions->where('created_at', '<=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'commissions' => $pendingCommissions,
                'summary' => $summary
            ]
        ]);
    }

    public function getStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        // For AJAX requests, we might want to return an error instead of redirecting
        if ($user->hasRole('admin')) {
            return response()->json([
                'error' => 'Admin users should use admin endpoints',
                'redirect_url' => route('admin.dashboard')
            ], 403);
        }

        $stats = $this->getDashboardStats($user);
        return response()->json($stats);
    }

    // ... Rest of the private methods remain unchanged ...

    /**
     * Get comprehensive dashboard data
     */
    private function getComprehensiveDashboardData(User $user): array
    {
        return [
            // Balance Information
            'total_balance' => $this->getTotalBalance($user),
            'available_balance' => $this->getAvailableBalance($user),
            'locked_balance' => $this->getLockedBalance($user),

            // Earnings Information
            'total_earnings' => $this->getTotalEarnings($user),
            'today_earnings' => $this->getTodayEarnings($user),
            'this_month_earnings' => $this->getThisMonthEarnings($user),

            // Deposit Information
            'total_deposits' => $this->getTotalDeposits($user),
            'last_deposit' => $this->getLastDeposit($user),
            'recent_deposits' => $this->getRecentDeposits($user, 3),
            'pending_deposits' => $this->getPendingDeposits($user),

            // Withdrawal Information
            'total_withdrawals' => $this->getTotalWithdrawals($user),
            'last_withdrawal' => $this->getLastWithdrawal($user),
            'recent_withdrawals' => $this->getRecentWithdrawals($user, 3),
            'pending_withdrawals' => $this->getPendingWithdrawals($user),

            // Investment Information
            'total_investments' => $this->getTotalInvestments($user),
            'active_investments' => $this->getActiveInvestments($user),
            'investment_returns' => $this->getInvestmentReturns($user),
            'recent_investments' => $this->getRecentInvestments($user, 3),

            // Referral Information
            'total_referrals' => $this->getTotalReferrals($user),
            'active_referrals' => $this->getActiveReferrals($user),
            'total_referral_earnings' => $this->getTotalReferralEarnings($user),
            'pending_commissions' => $this->getPendingCommissions($user),
            'referral_link' => $this->getReferralLink($user),

            // Recent Activity
            'recent_transactions' => $this->getRecentTransactions($user, 10),

            // Account Stats
            'account_age_days' => $this->getAccountAgeDays($user),
            'last_login' => $user->last_login_at,
        ];
    }

    // Balance Methods - Updated to match WalletController pattern
    private function getTotalBalance(User $user): float
    {
        // Get total USD value from all crypto wallets - same pattern as WalletController
        $wallets = \App\Models\CryptoWallet::where('user_id', $user->id)
            ->with('cryptocurrency')
            ->active()
            ->get();

        return $wallets->sum('usd_value');
    }

    private function getAvailableBalance(User $user): float
    {
        // For crypto wallets, available balance is typically the same as total balance
        // unless you have pending withdrawals or locked amounts
        $totalBalance = $this->getTotalBalance($user);
        $lockedBalance = $this->getLockedBalance($user);
        return max(0, $totalBalance - $lockedBalance);
    }

    private function getLockedBalance(User $user): float
    {
        // Calculate locked balance from pending withdrawals
        return $user->transactions()
            ->where('type', \App\Models\Transaction::TYPE_WITHDRAWAL)
            ->whereIn('status', [\App\Models\Transaction::STATUS_PENDING, \App\Models\Transaction::STATUS_PROCESSING])
            ->sum('amount');
    }

    // Earnings Methods
    private function getTotalEarnings(User $user): float
    {
        return $user->transactions()
            ->whereIn('type', [Transaction::TYPE_COMMISSION, Transaction::TYPE_ROI, Transaction::TYPE_BONUS])
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    private function getTodayEarnings(User $user): float
    {
        return $user->transactions()
            ->whereIn('type', [Transaction::TYPE_COMMISSION, Transaction::TYPE_ROI, Transaction::TYPE_BONUS])
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereDate('created_at', today())
            ->sum('amount');
    }

    private function getThisMonthEarnings(User $user): float
    {
        return $user->transactions()
            ->whereIn('type', [Transaction::TYPE_COMMISSION, Transaction::TYPE_ROI, Transaction::TYPE_BONUS])
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    // Deposit Methods - Updated to work with Transaction system
    private function getTotalDeposits(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    private function getLastDeposit(User $user): float
    {
        $lastDeposit = $user->transactions()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->latest()
            ->first();

        return $lastDeposit ? $lastDeposit->amount : 0.00;
    }

    private function getRecentDeposits(User $user, int $limit = 5)
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function getPendingDeposits(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_DEPOSIT)
            ->where('status', Transaction::STATUS_PENDING)
            ->sum('amount');
    }

    // Withdrawal Methods - Updated to work with Transaction system
    private function getTotalWithdrawals(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    private function getLastWithdrawal(User $user): float
    {
        $lastWithdrawal = $user->transactions()
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->latest()
            ->first();

        return $lastWithdrawal ? $lastWithdrawal->amount : 0.00;
    }

    private function getRecentWithdrawals(User $user, int $limit = 5)
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function getPendingWithdrawals(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->whereIn('status', [Transaction::STATUS_PENDING, Transaction::STATUS_PROCESSING])
            ->sum('amount');
    }

    // Investment Methods
    private function getTotalInvestments(User $user): float
    {
        // If you have a user_investments table, use that
        // Otherwise, sum investment transactions
        return $user->transactions()
            ->where('type', Transaction::TYPE_INVESTMENT)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    private function getActiveInvestments(User $user): int
    {
        // Count active investments - adjust based on your investment tracking method
        return $user->transactions()
            ->where('type', Transaction::TYPE_INVESTMENT)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', now()->subDays(365)) // Active within last year
            ->count();
    }

    private function getInvestmentReturns(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_ROI)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    private function getRecentInvestments(User $user, int $limit = 5)
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_INVESTMENT)
            ->latest()
            ->limit($limit)
            ->get();
    }

    // Referral Methods
    private function getTotalReferrals(User $user): int
    {
        return $user->directReferrals()->count();
    }

    private function getActiveReferrals(User $user): int
    {
        return $user->directReferrals()
            ->where('status', 'active')
            ->count();
    }

    private function getTotalReferralEarnings(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_COMMISSION)
            ->where('status', Transaction::STATUS_COMPLETED)
            ->sum('amount');
    }

    private function getPendingCommissions(User $user): float
    {
        return $user->transactions()
            ->where('type', Transaction::TYPE_COMMISSION)
            ->where('status', Transaction::STATUS_PENDING)
            ->sum('amount');
    }

    private function getReferralLink(User $user): string
    {
        return $user->profile?->referrallink;
    }

    // Activity Methods
    private function getRecentTransactions(User $user, int $limit = 10)
    {
        return $user->transactions()
            ->latest()
            ->limit($limit)
            ->get();
    }

    // Account Methods
    private function getAccountAgeDays(User $user): int
    {
        return $user->created_at->diffInDays(now());
    }

    /**
     * Existing methods maintained for backward compatibility
     */

    /**
     * Get dashboard statistics.
     */
    private function getDashboardStats(User $user): array
    {
        // Referral statistics
        $totalReferrals = $user->referralTree()->count();
        $activeReferrals = $user->directReferrals()->where('status', 'active')->count();
        $referralEarnings = $user->referrals()->sum('commission_earned');
        $maxLevel = $user->referralTree()->max('level') ?? 0;

        // Financial statistics
        $pendingCommissions = $user->transactions()
            ->where('type', 'commission')
            ->where('status', 'pending')
            ->sum('amount');

        $pendingWithdrawals = $user->transactions()
            ->where('type', 'withdrawal')
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        // Investment statistics
        $totalInvestments = $user->investments()
            ->where('status', 'active')
            ->sum('amount');

        $activeInvestmentsCount = $user->investments()
            ->where('status', 'active')
            ->count();

        return [
            'totalReferrals' => $totalReferrals,
            'activeReferrals' => $activeReferrals,
            'referralEarnings' => $referralEarnings,
            'maxLevel' => $maxLevel,
            'pendingCommissions' => $pendingCommissions,
            'pendingWithdrawals' => $pendingWithdrawals,
            'totalInvestments' => $totalInvestments,
            'activeInvestmentsCount' => $activeInvestmentsCount,
        ];
    }

    /**
     * Get recent referrals.
     */
    private function getRecentReferrals(User $user)
    {
        return $user->directReferrals()
            ->with('profile')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($referral) {
                return (object) [
                    'id' => $referral->id,
                    'fullname' => $referral->full_name,
                    'email' => $referral->email,
                    'avatar' => $referral->profile->avatar ?? '/images/users/avatar-1.jpg',
                    'level' => 1, // Direct referral
                    'created_at' => $referral->created_at,
                    'status' => $referral->status,
                    'commission' => $this->calculateReferralCommission($referral),
                ];
            });
    }

    /**
     * Get user wallets (dummy data for now).
     */
    private function getUserWallets(User $user)
    {
        // Return dummy wallet data until crypto wallet system is implemented
        return [
            (object) [
                'id' => 1,
                'name' => 'Bitcoin Wallet',
                'currency' => 'BTC',
                'balance' => 0.00000000,
                'usd_rate' => 45250.00
            ],
            (object) [
                'id' => 2,
                'name' => 'Ethereum Wallet',
                'currency' => 'ETH',
                'balance' => 0.00000000,
                'usd_rate' => 2850.00
            ],
            (object) [
                'id' => 3,
                'name' => 'USDT Wallet',
                'currency' => 'USDT',
                'balance' => 0.00000000,
                'usd_rate' => 1.00
            ],
        ];
    }

    /**
     * Calculate referral commission (placeholder).
     */
    private function calculateReferralCommission(User $referral): float
    {
        // Get commission from user_referrals table
        $referralRecord = DB::table('user_referrals')
            ->where('user_id', $referral->id)
            ->first();

        return $referralRecord ? $referralRecord->commission_earned : 0.00;
    }
}