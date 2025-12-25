<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Str;
use Exception;

class ReferralController extends Controller
{
    /**
     * Display the referrals page
     */
    public function index()
    {
        $user = Auth::user();

        // Load relationships that exist
        $user->load(['profile', 'earnings']);

        // Get referral statistics
        $stats = $this->getReferralStats($user);

        // Get referral levels with statistics
        $levels = $this->getReferralLevels($user);

        // Get paginated referred users (first page)
        $paginatedUsers = $this->getPaginatedReferrals($user, 1);

        // Get site settings
        $siteData = $this->getSiteData();

        return view('referrals.index', compact(
            'user',
            'stats',
            'levels',
            'paginatedUsers',
            'siteData'
        ));
    }

    /**
     * Get referral tree data for the tree component
     */
    public function getTreeData()
    {
        $user = Auth::user();

        try {
            $treeData = $this->buildReferralTree($user);

            return response()->json([
                'success' => true,
                'data' => $treeData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load referral tree: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build the referral tree structure
     */
    private function buildReferralTree($rootUser)
    {
        $treeData = [];

        // Load profile for root user
        $rootUser->load('profile');

        // Add root user
        $rootData = $this->formatUserForTree($rootUser, true);
        $rootData['children'] = $this->getReferralChildren($rootUser->id);
        $treeData["user_{$rootUser->id}"] = $rootData;

        // Recursively add all descendants
        $this->addDescendantsToTree($treeData, $rootUser, 1);

        return $treeData;
    }


    /**
     * Recursively add descendants to the tree
     */
    private function addDescendantsToTree(&$treeData, $parentUser, $currentTier)
    {
        if ($currentTier > 3) {
            return; // Limit to 3 tiers
        }

        // Load referrals with their profiles
        $directReferrals = User::where('sponsor_id', $parentUser->id)
            ->with('profile') // Load profile relationship
            ->get();

        foreach ($directReferrals as $referral) {
            $userData = $this->formatUserForTree($referral, false, $currentTier);
            $userData['children'] = $this->getReferralChildren($referral->id);
            $userData['sponsorName'] = $parentUser->full_name ?? $parentUser->username;

            $treeData["user_{$referral->id}"] = $userData;

            // Recursively add this user's referrals
            $this->addDescendantsToTree($treeData, $referral, $currentTier + 1);
        }
    }

    /**
     * Format user data for tree structure
     */
    private function formatUserForTree($user, $isRoot = false, $tier = 0)
    {
        // Get raw numeric level only - no formatting here
        if ($user->profile && $user->profile->level !== null) {
            $level = (int) $user->profile->level; // Fallback to profile level
        }

        // Get active investments count first
        $activeInvestments = $this->getUserActiveInvestments($user);

        // Set status to 'active' if user has active investments
        $status = $activeInvestments > 0 ? 'active' : 'inactive';

        return [
            'id' => $user->id,
            'name' => $user->full_name ?? $user->username,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'level' => $level, // Raw integer only - no "TL -" formatting
            'status' => $status,
            'tier' => $isRoot ? 0 : $tier,
            'isRoot' => $isRoot,
            'children' => [], // Will be populated separately
            'sponsorName' => null, // Will be set for non-root users
            'created_at' => $user->created_at,
            'deposits' => $this->getUserDeposits($user),
            'active_investments' => $activeInvestments,
            'commissions' => $this->getUserCommissions($user)
        ];
    }


    /**
     * Get referral children IDs
     */
    private function getReferralChildren($userId)
    {
        return User::where('sponsor_id', $userId)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get user's total deposits
     */
    private function getUserDeposits($user)
    {
        return Transaction::where('user_id', $user->id)
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get user's total commissions
     */
    private function getUserCommissions($user)
    {
        return Transaction::where('user_id', $user->id)
            ->where('type', 'commission')
            ->where('status', 'completed')
            ->sum('amount');
    }


    /**
     * Get user's active investments count
     */
    private function getUserActiveInvestments($user)
    {
        return \App\Models\UserInvestment::where('user_id', $user->id)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Get pending commissions via AJAX
     */
    public function getPendingCommissions()
    {
        $user = Auth::user();

        try {
            // Get pending commission transactions
            $pendingCommissions = Transaction::where('user_id', $user->id)
                ->where('type', 'commission')
                ->where('status', 'pending')
                ->get()
                ->map(function ($commission, $index) {
                    $createdAt = Carbon::parse($commission->created_at);
                    $waitPeriodDays = 7; // Default wait period
                    $eligibleDate = $createdAt->copy()->addDays($waitPeriodDays);
                    $pendingDays = $createdAt->diffInDays(now());
                    $remainingDays = max(0, $waitPeriodDays - $pendingDays);

                    return [
                        'id' => $commission->id,
                        'commission_amount' => $commission->amount,
                        'referral_name' => 'Referral User #' . ($index + 1),
                        'referral_email' => 'user' . ($index + 1) . '@example.com',
                        'pending_days' => $pendingDays,
                        'total_wait_days' => $waitPeriodDays,
                        'remaining_days' => $remainingDays,
                        'date_eligible' => $eligibleDate->toDateString(),
                        'created_at' => $commission->created_at
                    ];
                });

            $summary = [
                'total_pending_amount' => $pendingCommissions->sum('commission_amount'),
                'total_commissions' => $pendingCommissions->count(),
                'ready_commissions' => $pendingCommissions->where('remaining_days', 0)->count(),
                'wait_period_days' => 7
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'commissions' => $pendingCommissions,
                    'summary' => $summary
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending commissions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get users for specific page via AJAX
     */
    public function getUsersForPage(Request $request)
    {
        $user = Auth::user();
        $page = $request->get('page', 1);

        try {
            $paginatedUsers = $this->getPaginatedReferrals($user, $page);

            return response()->json([
                'success' => true,
                'data' => $paginatedUsers
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users for this page: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get referral statistics
     */
    private function getReferralStats($user)
    {
        // Get direct referrals count
        $totalReferrals = User::where('sponsor_id', $user->id)->count();

        // Get today's commission earnings
        $todayEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'commission')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        // Get total commission earnings
        $totalEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'commission')
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'total_referrals' => $totalReferrals,
            'today_earnings' => number_format($todayEarnings, 2),
            'total_earnings' => number_format($totalEarnings, 2)
        ];
    }

    /**
     * Get referral levels with statistics
     */
    private function getReferralLevels($user)
    {
        $levels = [];

        // Level 1 (Direct referrals)
        $level1Users = User::where('sponsor_id', $user->id)->get();
        $level1Deposits = $level1Users->sum(function ($user) {
            return Transaction::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount');
        });
        $level1Earnings = $this->calculateLevelEarnings($user, 1);

        $levels[] = [
            'level' => 1,
            'users' => $level1Users->count(),
            'active_users' => $level1Users->where('status', 'active')->count(),
            'deposit' => '$' . number_format($level1Deposits, 2),
            'earning' => '$' . number_format($level1Earnings, 2)
        ];

        // Level 2 (Referrals of referrals)
        $level2Users = collect();
        foreach ($level1Users as $level1User) {
            $level2Referrals = User::where('sponsor_id', $level1User->id)->get();
            $level2Users = $level2Users->merge($level2Referrals);
        }

        $level2Deposits = $level2Users->sum(function ($user) {
            return Transaction::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount');
        });
        $level2Earnings = $this->calculateLevelEarnings($user, 2);

        $levels[] = [
            'level' => 2,
            'users' => $level2Users->count(),
            'active_users' => $level2Users->where('status', 'active')->count(),
            'deposit' => '$' . number_format($level2Deposits, 2),
            'earning' => '$' . number_format($level2Earnings, 2)
        ];

        // Level 3
        $level3Users = collect();
        foreach ($level2Users as $level2User) {
            $level3Referrals = User::where('sponsor_id', $level2User->id)->get();
            $level3Users = $level3Users->merge($level3Referrals);
        }

        $level3Deposits = $level3Users->sum(function ($user) {
            return Transaction::where('user_id', $user->id)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount');
        });
        $level3Earnings = $this->calculateLevelEarnings($user, 3);

        $levels[] = [
            'level' => 3,
            'users' => $level3Users->count(),
            'active_users' => $level3Users->where('status', 'active')->count(),
            'deposit' => '$' . number_format($level3Deposits, 2),
            'earning' => '$' . number_format($level3Earnings, 2)
        ];

        return $levels;
    }

    /**
     * Calculate earnings for a specific level (simplified)
     */
    private function calculateLevelEarnings($user, $level)
    {
        // Get total commission earnings and distribute based on level
        $totalCommissions = Transaction::where('user_id', $user->id)
            ->where('type', 'commission')
            ->where('status', 'completed')
            ->sum('amount');

        // Simple distribution: Level 1 gets 60%, Level 2 gets 30%, Level 3 gets 10%
        $levelPercentages = [1 => 0.6, 2 => 0.3, 3 => 0.1];

        return $totalCommissions * ($levelPercentages[$level] ?? 0);
    }

    /**
     * Get paginated referrals
     */
    private function getPaginatedReferrals($user, $page = 1, $perPage = 10)
    {
        $offset = ($page - 1) * $perPage;

        $query = User::where('sponsor_id', $user->id)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $users = $query->skip($offset)->take($perPage)->get();

        $formattedUsers = $users->map(function ($referralUser) {
            $totalDeposits = Transaction::where('user_id', $referralUser->id)
                ->where('type', 'deposit')
                ->where('status', 'completed')
                ->sum('amount');

            return [
                'id' => $referralUser->id,
                'fullname' => $referralUser->full_name,
                'username' => $referralUser->username,
                'email' => $referralUser->email,
                'phone' => $referralUser->phone,
                'email_verified' => $referralUser->hasVerifiedEmail(),
                'phone_verified' => false, // Default to false if no profile relation
                'status' => $referralUser->status === 'active' ? '1' : '0',
                'deposit' => '$' . number_format($totalDeposits, 2),
                'currency' => 'USD',
                'icon' => null,
                'created_at' => $referralUser->created_at
            ];
        });

        return [
            'users' => $formattedUsers,
            'details' => [
                'currentPage' => $page,
                'totalItems' => $total,
                'itemsPerPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ]
        ];
    }

    /**
     * Get site data (simplified)
     */
    private function getSiteData()
    {
        return [
            'content' => [
                'affiliates' => [
                    'total_referrals' => 'Total Referrals',
                    'today_commission' => "Today's Commission",
                    'total_commission' => 'Total Commission',
                    'referral_link' => 'Referral Link',
                    'level_statistics' => 'Level Statistics',
                    'referred_users' => 'Referred Users',
                    'active_users' => 'Active',
                    'total_deposits' => 'Deposits',
                    'earnings' => 'Earnings',
                    'no_referrals' => 'No referrals found',
                    'copied_text' => 'Copied to clipboard!'
                ]
            ],
            'infobox_settings' => [
                'affiliates_show_levels' => true,
                'affiliates_show_stats' => true,
                'affiliates_show_refid' => true,
                'affiliates_show_reflink' => true,
                'affiliates_show_affiliates_users_email' => true,
                'affiliates_show_levels_stats' => true,
                'affiliates_show_affiliates_pms' => true,
                'affiliates_show_banners' => false, // Disabled for now
                'affiliates_show_affiliates_users' => true,
                'affiliates_show_affiliates_users_username' => true
            ],
            'referral_settings' => [
                'banners' => []
            ]
        ];
    }

    /**
     * Show form to create direct referral
     */
    public function createDirectReferral()
    {
        $user = Auth::user();

        // Check if user can create referrals
        if (!$user->isActive() || !$user->hasVerifiedEmail()) {
            return redirect()->route('referrals.index')
                ->with('error', 'You must verify your account before adding referrals.');
        }

        return view('referrals.create', compact('user'));
    }

    /**
     * Store direct referral
     */

    public function storeDirectReferral(Request $request)
    {
        $sponsor = Auth::user();

        // Validate input
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
            'phone' => ['required', 'string', 'max:20'],
            'game_username' => ['required', 'string', 'min:10'],
            'game_password' => ['required', 'string', 'min:6'],
        ], [
            'game_username.min' => 'Game username must be at least 10 characters',
            'game_password.min' => 'Game password must be at least 6 characters',
        ]);

        DB::beginTransaction();

        try {
            // Step 1: Validate game credentials
            Log::info('Validating game credentials', [
                'sponsor_id' => $sponsor->id,
                'game_username' => $validated['game_username']
            ]);

            $gameValidation = $this->validateGameCredentials(
                $validated['game_username'],
                $validated['game_password']
            );

            Log::info('Game validation result', [
                'success' => $gameValidation['success'],
                'message' => $gameValidation['message'] ?? 'N/A'
            ]);

            if (!$gameValidation['success']) {
                DB::rollback();

                return redirect()->back()
                    ->withInput($request->except(['password', 'game_password']))
                    ->with('error', $gameValidation['message'])
                    ->withErrors([
                        'game_username' => $gameValidation['message'],
                        'game_password' => 'Please verify your game credentials.'
                    ]);
            }

            // Step 2: Generate unique referral code
            $referralCode = $this->generateReferralCode();
            $temporaryPassword = $this->generateTemporaryPassword();

            Log::info('Generated credentials for new referral', [
                'sponsor_id' => $sponsor->id,
                'referral_code' => $referralCode,
                'has_temp_password' => true
            ]);

            // Step 3: Create user account
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'phone' => $validated['phone'],
                'password' => Hash::make($temporaryPassword),
                'sponsor_id' => $sponsor->id,
                'referral_code' => $referralCode,
                'status' => 'pending_verification',
                'must_change_password' => true,
                'role' => User::ROLE_USER,
            ]);

            // Step 4: Create user profile with game credentials AND referrallink
            $user->profile()->create([
                'uname' => $gameValidation['data']['uname'],
                'upwd' => encrypt($validated['game_password']),
                'umoney' => 0.00,
                'level' => 0,
                'city' => null,
                'referrallink' => $referralCode,
                'kyc_status' => 'pending',
                'game_linked_at' => now(),
                'game_settings' => json_encode([
                    'linked_by' => $sponsor->id,
                    'linked_by_name' => $sponsor->full_name,
                    'linked_at' => now()->toDateTimeString(),
                    'linked_from' => request()->ip(),
                    'api_response' => $gameValidation['data']
                ])
            ]);

            // Step 5: Create referral record
            $user->referralRecords()->create([
                'sponsor_id' => $sponsor->id,
                'level' => 1,
                'status' => 'active',
                'referred_at' => now(),
            ]);

            // Step 6: Create default crypto wallets
            $this->createDefaultWallets($user);

            DB::commit();

            // Step 7: Send verification email
            try {
                $user->sendEmailVerificationNotification();
            } catch (Exception $e) {
                Log::error('Failed to send verification email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Direct referral created successfully', [
                'sponsor_id' => $sponsor->id,
                'new_user_id' => $user->id,
                'email' => $user->email,
                'referral_code' => $referralCode,
                'game_username' => $gameValidation['data']['uname']
            ]);

            // Encrypt and pass data via URL parameter
            $encryptedData = encrypt([
                'temp_password' => $temporaryPassword,
                'user_email' => $user->email,
                'user_name' => $user->full_name,
                'username' => $user->username,
                'phone' => $user->phone,
                'referral_code' => $referralCode,
                'game_username' => $gameValidation['data']['uname']
            ]);

            return redirect()->route('referrals.success', ['data' => $encryptedData]);

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Failed to create direct referral', [
                'sponsor_id' => $sponsor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput($request->except(['password', 'game_password']))
                ->with('error', 'Failed to create referral account: ' . $e->getMessage());
        }
    }

    /**
     * Validate game credentials with external API
     * (Similar to BotController's validateGameCredentials)
     */
    /**
     * Validate game credentials with external API
     */
    private function validateGameCredentials(string $username, string $password): array
    {
        $apiUrl = config('services.game_api.url');

        // Use mock validation if API not configured
        if (!$apiUrl || $apiUrl === 'mock' || str_contains($apiUrl, 'your-game-api.com')) {
            return $this->mockValidateCredentials($username, $password);
        }

        try {
            // Make request without retry to avoid exception issues
            $response = Http::timeout(15)
                ->post($apiUrl, [
                    'username' => $username,
                    'pwd' => $password
                ]);

            // Parse response data
            $data = $response->json() ?? [];
            $statusCode = $response->status();

            // Handle successful authentication (200-299 status codes)
            if ($response->successful()) {
                $message = strtolower($data['message'] ?? '');
                $isSuccessfulLogin = str_contains($message, 'login successful') ||
                    str_contains($message, 'success');
                $hasValidData = isset($data['uname']) && !empty($data['uname']);
                $successFlag = ($data['success'] ?? false) === true;

                if ($isSuccessfulLogin || $hasValidData || $successFlag) {
                    Log::info('Game credentials validated successfully', [
                        'username' => $username
                    ]);

                    return [
                        'success' => true,
                        'data' => [
                            'uname' => $data['uname'] ?? $username,
                            'umoney' => floatval($data['umoney'] ?? 0)
                        ]
                    ];
                }
            }

            // If we get here, authentication failed - extract error message from response
            $errorMessage = $data['message'] ?? 'Invalid game credentials. Please check your username and password.';

            Log::warning('Game credentials validation failed', [
                'username' => $username,
                'status_code' => $statusCode,
                'error_message' => $errorMessage,
                'response_body' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Illuminate\Http\Client\RequestException $e) {
            // This exception contains the response, extract error from it
            $response = $e->response;

            if ($response) {
                try {
                    $data = $response->json() ?? [];
                    $errorMessage = $data['message'] ?? 'Invalid game credentials. Please verify and try again.';

                    Log::warning('Game API returned error response', [
                        'username' => $username,
                        'status_code' => $response->status(),
                        'error_message' => $errorMessage,
                        'exception_message' => $e->getMessage()
                    ]);

                    return [
                        'success' => false,
                        'message' => $errorMessage
                    ];
                } catch (\Exception $parseError) {
                    Log::error('Failed to parse error response', [
                        'username' => $username,
                        'response_body' => $response->body(),
                        'parse_error' => $parseError->getMessage()
                    ]);
                }
            }

            // If we couldn't parse the error, return generic message
            return [
                'success' => false,
                'message' => 'Unable to validate credentials. Please try again.'
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Game API connection failed', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Unable to connect to game server. Please check your internet connection and try again.'
            ];

        } catch (Exception $e) {
            Log::error('Game credentials validation unexpected error', [
                'username' => $username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.'
            ];
        }
    }

    /**
     * Mock validation for testing
     */
    private function mockValidateCredentials(string $username, string $password): array
    {
        if (strlen($username) < 10) {
            return [
                'success' => false,
                'message' => 'Game username must be at least 10 characters'
            ];
        }

        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'Game password must be at least 6 characters'
            ];
        }

        // Mock successful validation
        return [
            'success' => true,
            'data' => [
                'uname' => $username,
                'umoney' => 0.00
            ]
        ];
    }

    /**
     * Create default crypto wallets for new user
     */
    private function createDefaultWallets(User $user): void
    {
        $defaultCurrencies = ['USDT_TRC20', 'BTC', 'ETH'];

        foreach ($defaultCurrencies as $currency) {
            $crypto = \App\Models\Cryptocurrency::where('symbol', $currency)->first();

            if ($crypto) {
                $user->wallets()->create([
                    'currency' => $currency,
                    'name' => $crypto->name,
                    'balance' => 0,
                    'usd_rate' => $crypto->rate ?? 1,
                    'is_active' => true
                ]);
            }
        }
    }

    /**
     * AJAX: Check if game username is available
     */
    public function checkGameUsername(Request $request)
    {
        $username = $request->input('username');

        if (strlen($username) < 10) {
            return response()->json([
                'available' => false,
                'message' => 'Username must be at least 10 characters'
            ]);
        }

        // Check if already used in system
        $exists = \App\Models\UserProfile::where('uname', $username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'This game username is already linked to another account' : 'Username available'
        ]);
    }

    /**
     * AJAX: Validate game credentials
     */
    public function validateGameCredentialsAjax(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:10',
            'password' => 'required|string|min:6'
        ]);

        $result = $this->validateGameCredentials(
            $request->username,
            $request->password
        );

        return response()->json($result);
    }

    /**
     * Generate a unique referral code
     */
    private function generateReferralCode(): string
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
     * Generate a secure temporary password
     */
    private function generateTemporaryPassword(): string
    {
        // Generate a random password: 3 uppercase + 4 digits + 3 lowercase + 2 special
        $uppercase = strtoupper(\Str::random(3));
        $digits = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $lowercase = strtolower(\Str::random(3));
        $special = ['@', '#', '$', '%'][random_int(0, 3)] . ['!', '*', '&', '+'][random_int(0, 3)];

        // Combine and shuffle
        $password = $uppercase . $digits . $lowercase . $special;

        return $password;
    }

    /**
     * Show referral creation success page
     */
    public function referralSuccess($data)
    {
        try {
            $user = \Auth::user();

            // Decrypt the data from URL parameter
            $successData = decrypt($data);

            Log::info('Success page accessed with decrypted data');

            return view('referrals.success', compact('successData', 'user'));

        } catch (\Exception $e) {
            Log::error('Failed to decrypt success data', [
                'error' => $e->getMessage()
            ]);

            return redirect()->route('referrals.index')
                ->with('error', 'Invalid or expired referral data.');
        }
    }
}