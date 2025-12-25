<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\AccountBalance;
use App\Models\UserEarning;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $sponsorId = $request->get('ref');
        $sponsor = null;

        // If sponsor ID is provided, find the sponsor by referral_code
        if ($sponsorId) {
            $sponsor = User::where('referral_code', $sponsorId)->first();
        }

        return view('auth.register', compact('sponsorId', 'sponsor'));
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'max:50', 'unique:users', 'alpha_dash', 'min:3'],
            'phone' => ['required', 'string', 'max:20', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'country' => ['required', 'string', 'max:2'],
            'city' => ['required', 'string', 'max:255'],
            'sponsor_id' => ['required', 'string', 'max:50'],
        ], [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes and underscores.',
            'username.min' => 'Username must be at least 3 characters.',
            'phone.unique' => 'This phone number is already registered.',
            'country.max' => 'Please select a valid country.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        try {
            DB::beginTransaction();

            // Log registration attempt
            \Log::info('Registration attempt started', [
                'email' => $validated['email'],
                'username' => $validated['username'],
                'sponsor_id' => $validated['sponsor_id']
            ]);

            // Validate sponsor - required field (must be a referral_code)
            $sponsor = null;
            if (empty($validated['sponsor_id'])) {
                throw ValidationException::withMessages([
                    'sponsor_id' => ['Sponsor ID is required to register.'],
                ]);
            }

            // Find sponsor by referral_code only
            \Log::info('Looking for sponsor with referral code: ' . $validated['sponsor_id']);
            $sponsor = User::where('referral_code', $validated['sponsor_id'])->first();

            if (!$sponsor) {
                \Log::error('Sponsor not found for referral code: ' . $validated['sponsor_id']);
                throw ValidationException::withMessages([
                    'sponsor_id' => ['Invalid sponsor ID. Please enter a valid referral code.'],
                ]);
            }

            \Log::info('Sponsor found', ['sponsor_id' => $sponsor->id, 'sponsor_name' => $sponsor->first_name . ' ' . $sponsor->last_name]);

            // Check if sponsor is active
            if ($sponsor->status !== 'active') {
                \Log::error('Sponsor is not active', ['sponsor_status' => $sponsor->status]);
                throw ValidationException::withMessages([
                    'sponsor_id' => ['This sponsor account is not active.'],
                ]);
            }

            // Generate unique referral code for the new user
            $userReferralCode = $this->generateUniqueReferralCode();
            \Log::info('Generated referral code for new user: ' . $userReferralCode);

            // Create the user
            \Log::info('Creating user...');
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'referral_code' => $userReferralCode,
                'sponsor_id' => $sponsor->id,
                'email_verified_at' => null,
                'status' => 'pending_verification',
            ]);
            \Log::info('User created successfully', ['user_id' => $user->id]);

            // Create user profile
            \Log::info('Creating user profile...');
            UserProfile::create([
                'user_id' => $user->id,
                'country' => $validated['country'],
                'city' => $validated['city'],
                'referrallink' => url('/register?ref=' . $userReferralCode),
                'level' => '0',
                'total_investments' => 0,
                'total_deposit' => 0,
                'total_withdraw' => 0,
                'last_deposit' => 0,
                'last_withdraw' => 0,
                'kyc_status' => 'pending',
                'email_notifications' => true,
                'sms_notifications' => false,
                'preferred_language' => 'en',
                'timezone' => 'UTC',
                'phone_verified' => true,
                'two_factor_enabled' => false,
            ]);
            \Log::info('User profile created successfully');

            // Create initial account balance
            \Log::info('Creating account balance...');
            AccountBalance::create([
                'user_id' => $user->id,
                'balance' => 0.00,
                'locked_balance' => 0.00,
            ]);
            \Log::info('Account balance created successfully');

            // Create initial earnings record
            \Log::info('Creating user earnings...');
            UserEarning::create([
                'user_id' => $user->id,
                'total' => '0.00',
                'today' => '0.00',
                'last_earning_date' => null,
            ]);
            \Log::info('User earnings created successfully');

            // Update sponsor's referral relationships - always exists now
            \Log::info('Updating sponsor stats...');
            $this->updateSponsorStats($sponsor, $user);
            \Log::info('Sponsor stats updated successfully');

            DB::commit();

            // 🔔 SEND NEW REFERRAL NOTIFICATION TO SPONSOR
            try {
                $totalReferrals = DB::table('user_referrals')
                    ->where('sponsor_id', $sponsor->id)
                    ->where('level', 1)
                    ->count();

                $sponsor->notify(
                    \App\Notifications\UnifiedNotification::newReferral(
                        $user->first_name . ' ' . $user->last_name,
                        $totalReferrals,
                        $sponsor->profile->referrallink ?? url('/register?ref=' . $sponsor->referral_code)
                    )
                );

                \Log::info('New referral notification sent to sponsor', [
                    'sponsor_id' => $sponsor->id,
                    'new_user_id' => $user->id,
                    'total_referrals' => $totalReferrals
                ]);
            } catch (\Exception $notificationError) {
                // Don't fail registration if notification fails
                \Log::error('New referral notification failed', [
                    'sponsor_id' => $sponsor->id,
                    'new_user_id' => $user->id,
                    'error' => $notificationError->getMessage()
                ]);
            }
            
            \Log::info('Registration completed successfully', ['user_id' => $user->id]);

            // Fire the registered event (this will send email verification)
            event(new Registered($user));

            // Log the user in automatically
            Auth::login($user);
            \Log::info('User logged in automatically after registration', ['user_id' => $user->id]);

            // Redirect to email verification page
            return redirect()->route('verification.notice')
                ->with('success', 'Registration successful! Please check your email to verify your account.');

        } catch (ValidationException $e) {
            DB::rollBack();
            \Log::error('Registration validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            // Log the complete error details
            \Log::error('Registration failed with exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'validated_data' => $validated
            ]);

            // Show detailed error in development, generic in production
            $errorMessage = config('app.debug')
                ? 'Registration failed: ' . $e->getMessage() . ' (File: ' . $e->getFile() . ', Line: ' . $e->getLine() . ')'
                : 'Registration failed. Please try again.';

            return back()
                ->withErrors(['error' => $errorMessage])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Generate a unique referral code
     */
    private function generateUniqueReferralCode(): string
    {
        do {
            // Generate 3 random uppercase letters
            $letters = strtoupper(Str::random(3));

            // Generate 4 random digits, zero-padded
            $numbers = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

            $code = $letters . $numbers;
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }


    /**
     * Update sponsor statistics and build referral tree
     */
    private function updateSponsorStats(User $sponsor, User $newUser): void
    {
        // Create direct referral relationship
        DB::table('user_referrals')->insert([
            'sponsor_id' => $sponsor->id,
            'user_id' => $newUser->id,
            'level' => 1,
            'status' => 'active',
            'commission_earned' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Build referral tree for MLM structure
        $this->buildReferralTree($sponsor->id, $newUser->id, 1);

        // Update sponsor's profile referral count if profile exists
        if ($sponsor->profile) {
            $sponsor->profile()->increment('referral_count');
        }
    }

    /**
     * Build referral tree for MLM structure
     */
    private function buildReferralTree(int $sponsorId, int $newUserId, int $level): void
    {
        // Insert the direct relationship
        DB::table('referral_tree')->insert([
            'sponsor_id' => $sponsorId,
            'user_id' => $newUserId,
            'level' => $level,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Find the sponsor's sponsors (for multi-level commission structure)
        $upperSponsors = DB::table('referral_tree')
            ->where('user_id', $sponsorId)
            ->where('level', '<', 10)
            ->orderBy('level')
            ->get();

        foreach ($upperSponsors as $upperSponsor) {
            $newLevel = $upperSponsor->level + 1;
            if ($newLevel <= 10) {
                DB::table('referral_tree')->insert([
                    'sponsor_id' => $upperSponsor->sponsor_id,
                    'user_id' => $newUserId,
                    'level' => $newLevel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Check if username is available (AJAX endpoint)
     */
    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:50'
        ]);

        $username = $request->get('username');
        $exists = User::where('username', $username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Username is already taken' : 'Username is available'
        ]);
    }

    /**
     * Check if sponsor ID is valid (AJAX endpoint)
     */
    public function checkSponsorId(Request $request)
    {
        $request->validate([
            'sponsor_id' => 'required|string|max:50'
        ]);

        $sponsorId = $request->get('sponsor_id');

        if (empty($sponsorId)) {
            return response()->json([
                'valid' => true,
                'sponsor_name' => null,
                'message' => 'No sponsor ID provided'
            ]);
        }

        $sponsor = User::where('referral_code', $sponsorId)->first();

        if ($sponsor) {
            // Check if sponsor is active
            if ($sponsor->status !== 'active') {
                return response()->json([
                    'valid' => false,
                    'sponsor_name' => null,
                    'message' => 'This sponsor account is not active'
                ]);
            }

            return response()->json([
                'valid' => true,
                'sponsor_name' => $sponsor->first_name . ' ' . $sponsor->last_name,
                'message' => "Sponsor Name: {$sponsor->first_name} {$sponsor->last_name}"
            ]);
        }

        return response()->json([
            'valid' => false,
            'sponsor_name' => null,
            'message' => 'Invalid sponsor ID'
        ]);
    }
}