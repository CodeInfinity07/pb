<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use App\Models\UserProfile;
use App\Models\KycVerification;
use App\Notifications\KycStatusUpdated;
use App\Notifications\StaffPromotion;
use App\Notifications\UserBlocked;
use App\Notifications\UserUnblocked;
use App\Notifications\WelcomeNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display users listing with filters and statistics
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $verification = $request->get('verification');
        $search = $request->get('search');
        $investment_status = $request->get('investment_status');

        // Build query with crypto wallet relationship - FIXED ambiguous column
        $query = User::with([
            'profile',
            'cryptoWallets' => function ($q) {
                $q->where('crypto_wallets.is_active', true)->with([
                    'cryptocurrency' => function ($subq) {
                        $subq->where('cryptocurrencies.is_active', true);
                    }
                ]);
            },
            'earnings',
            'investments' => function ($q) {
                $q->select('user_id', 'status', 'amount', 'paid_return', 'created_at')
                    ->latest()
                    ->limit(3);
            }
        ])
            ->select('id', 'first_name', 'last_name', 'email', 'username', 'status', 'created_at', 'last_login_at', 'sponsor_id');

        // Investment status filter
        if ($investment_status) {
            switch ($investment_status) {
                case 'has_investments':
                    $query->whereHas('investments');
                    break;
                case 'no_investments':
                    $query->whereDoesntHave('investments');
                    break;
                case 'active_investments':
                    $query->whereHas('investments', function ($q) {
                        $q->where('status', 'active');
                    });
                    break;
                case 'completed_investments':
                    $query->whereHas('investments', function ($q) {
                        $q->where('status', 'completed');
                    });
                    break;
            }
        }

        // Verification filter
        if ($verification) {
            switch ($verification) {
                case 'email_verified':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'email_unverified':
                    $query->whereNull('email_verified_at');
                    break;
                case 'kyc_verified':
                    $query->whereHas('profile', function ($q) {
                        $q->where('kyc_status', 'verified');
                    });
                    break;
                case 'kyc_pending':
                    $query->whereHas('profile', function ($q) {
                        $q->whereIn('kyc_status', ['pending', 'submitted', 'under_review']);
                    });
                    break;
            }
        }

        // Search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Get users with pagination
        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        // Add sponsor chain and calculate total wallet balance for each user
        $users->getCollection()->transform(function ($user) {
            $user->sponsor_chain = $this->getSponsorChain($user);

            // Calculate total balance across all active wallets in USD
            $user->total_wallet_balance_usd = $user->cryptoWallets
                ->where('is_active', true)
                ->sum(function ($wallet) {
                    return $wallet->balance * ($wallet->usd_rate ?? 0);
                });

            // Get primary wallet (USDT or highest balance)
            $user->primary_wallet = $user->cryptoWallets
                ->where('is_active', true)
                ->sortByDesc(function ($wallet) {
                    // Prioritize USDT wallets, then by balance
                    $priority = str_contains($wallet->currency, 'USDT') ? 1000000 : 0;
                    return $priority + ($wallet->balance * ($wallet->usd_rate ?? 1));
                })
                ->first();

            return $user;
        });

        // Get summary statistics
        $summaryData = $this->getUserSummaryData($request);

        return view('admin.users.index', compact('user', 'users', 'summaryData'));
    }

    /**
     * Show user details
     */
    public function show($id)
    {
        $user = User::with([
            'profile',
            'cryptoWallets' => function ($q) {
                $q->where('crypto_wallets.is_active', true)->with([
                    'cryptocurrency' => function ($subq) {
                        $subq->where('cryptocurrencies.is_active', true);
                    }
                ]);
            },
            'earnings',
            'transactions' => function ($q) {
                $q->latest()->limit(10);
            },
            'directReferrals' => function ($q) {
                $q->limit(50);
            },
            'sponsor',
            'investments'
        ])->findOrFail($id);

        $userStats = $this->getUserStats($user);

        return response()->json([
            'success' => true,
            'html' => view('admin.users.show-details', compact('user', 'userStats'))->render()
        ]);
    }


    /**
     * Show edit form
     */
    public function edit($id)
    {
        $user = User::with('profile')->findOrFail($id);
        $roles = User::getAvailableRoles();
        $countries = $this->getCountries();

        return view('admin.users.edit', compact('user', 'roles', 'countries'));
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:' . implode(',', array_keys(User::getAvailableRoles())),
            'status' => 'required|in:active,inactive,blocked',
            'password' => 'nullable|min:8|confirmed',
            // Profile fields
            'country' => 'nullable|string|max:3',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'date_of_birth' => 'nullable|date|before:today',
            'kyc_status' => 'nullable|in:not_submitted,pending,submitted,under_review,verified,rejected',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Update user data
            $userData = $request->only([
                'first_name',
                'last_name',
                'email',
                'username',
                'phone',
                'role',
                'status'
            ]);

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Update profile data
            if ($user->profile) {
                $profileData = $request->only([
                    'country',
                    'city',
                    'address',
                    'date_of_birth',
                    'kyc_status'
                ]);
                $user->profile->update($profileData);
            } else {
                // Create profile if doesn't exist
                UserProfile::create(array_merge([
                    'user_id' => $user->id
                ], $request->only(['country', 'city', 'address', 'date_of_birth', 'kyc_status'])));
            }

            DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $user = Auth::user();
        $roles = User::getAvailableRoles();
        $countries = $this->getCountries();

        // Get available sponsors based on current user's role
        $availableSponsors = collect();

        if ($user->isAdmin()) {
            // Admins can select any active user as sponsor
            $availableSponsors = User::where('status', 'active')
                ->where('id', '!=', $user->id) // Exclude current admin
                ->select('id', 'first_name', 'last_name', 'email', 'username')
                ->orderBy('first_name')
                ->get();
        }

        return view('admin.users.create', compact(
            'user',
            'roles',
            'countries',
            'availableSponsors'
        ));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:8|confirmed',
            'city' => 'required|string|max:255',
            'role' => 'required|in:' . implode(',', array_keys(User::getAvailableRoles())),
            'status' => 'required|in:active,inactive',
            'sponsor_id' => 'nullable|exists:users,id',
            'initial_balance' => 'nullable|numeric|min:0',
            'email_verified' => 'nullable|boolean',
            'send_welcome_email' => 'nullable|boolean',
        ]);

        // Additional validation based on user role
        if (!$currentUser->isAdmin()) {
            // Non-admin users can only create regular users
            $validator->after(function ($validator) use ($request) {
                if ($request->role !== 'user') {
                    $validator->errors()->add('role', 'You can only create regular users.');
                }
            });

            // Non-admin users cannot set initial balance
            if ($request->filled('initial_balance') && $request->initial_balance > 0) {
                $validator->errors()->add('initial_balance', 'You cannot set initial balance.');
            }
        }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Determine sponsor based on role logic
            $sponsorId = null;
            $user_referral_code = $this->generateReferralCode();
            if ($currentUser->isAdmin()) {
                // Admin can choose any sponsor or leave empty
                $sponsorId = $request->sponsor_id;
            } else {
                // Non-admin users automatically become sponsors
                $sponsorId = $currentUser->id;
            }

            // Create the user
            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'username' => $request->username,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => $request->status,
                'sponsor_id' => $sponsorId,
                'referral_code' => $user_referral_code,
            ];

            // Set email verification status
            if ($request->email_verified) {
                $userData['email_verified_at'] = now();
            }

            $newUser = User::create($userData);

            // Create user profile
            $newUser->profile()->create([
                'city' => $request->city,
                'kyc_status' => 'pending',
                'referrallink' => $user_referral_code
            ]);

            // Create account balance
            $initialBalance = $currentUser->isAdmin() && $request->filled('initial_balance')
                ? $request->initial_balance
                : 0;

            $newUser->accountBalance()->create([
                'balance' => $initialBalance,
                'locked_balance' => 0,
            ]);

            // Create earnings record
            $newUser->earnings()->create([
                'total' => '0.00',
                'today' => '0.00',
            ]);

            // Create initial balance transaction if amount > 0
            if ($initialBalance > 0) {
                Transaction::create([
                    'user_id' => $newUser->id,
                    'transaction_id' => Transaction::generateTransactionId('credit_adjustment', 'USD', $newUser->id),
                    'type' => Transaction::TYPE_CREDIT_ADJUSTMENT,
                    'amount' => $initialBalance,
                    'currency' => 'USD',
                    'status' => Transaction::STATUS_COMPLETED,
                    'description' => 'Initial balance credit by admin',
                    'processed_at' => now(),
                    'processed_by' => $currentUser->id,
                ]);
            }

            DB::commit();

            // Send welcome email if requested
            // if ($request->send_welcome_email) {
            //     try {
            //         $newUser->notify(new WelcomeNewUser([
            //             'password' => $request->password, // Only for welcome email
            //             'created_by' => $currentUser->full_name,
            //             'sponsor' => $sponsorId ? User::find($sponsorId)->full_name : null,
            //         ]));
            //     } catch (\Exception $e) {
            //         // Log email error but don't fail the user creation
            //         \Log::error('Failed to send welcome email', [
            //             'user_id' => $newUser->id,
            //             'error' => $e->getMessage()
            //         ]);
            //     }
            // }

            // Log the user creation
            \Log::info('New user created by admin', [
                'created_user_id' => $newUser->id,
                'created_by' => $currentUser->id,
                'sponsor_id' => $sponsorId,
                'initial_balance' => $initialBalance,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', "User {$newUser->full_name} created successfully!" .
                    ($request->send_welcome_email ? " Welcome email has been sent." : ""));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'data' => $request->except(['password', 'password_confirmation'])
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Toggle user status
     */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "User {$newStatus} successfully",
            'status' => $newStatus
        ]);
    }

    /**
     * Verify user email
     */
    public function verifyEmail($id)
    {
        $user = User::findOrFail($id);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            return response()->json([
                'success' => true,
                'message' => 'Email verified successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Email is already verified'
        ]);
    }

    /**
     * Adjust user balance
     */
    /**
     * Adjust user balance (updated for CryptoWallet)
     */
    public function adjustBalance(Request $request, $id)
    {
        $user = User::with('cryptoWallets')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:add,subtract',
            'reason' => 'required|string|max:255',
            'currency' => 'nullable|string|max:20' // Optional: let admin specify currency
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            DB::beginTransaction();

            $amount = abs($request->amount);

            // Find user's active crypto wallet
            $wallet = null;

            if ($request->filled('currency')) {
                // Admin specified a currency
                $wallet = $user->cryptoWallets()
                    ->where('currency', $request->currency)
                    ->where('is_active', true)
                    ->first();
            } else {
                // Auto-select wallet (prioritize USDT like in commission system)
                $priority = ['USDT_TRC20', 'USDT_BEP20', 'USDT_ERC20', 'BTC', 'ETH', 'BNB'];

                foreach ($priority as $currency) {
                    $wallet = $user->cryptoWallets()
                        ->where('currency', $currency)
                        ->where('is_active', true)
                        ->first();

                    if ($wallet) {
                        break;
                    }
                }

                // If no priority wallet found, get any active wallet
                if (!$wallet) {
                    $wallet = $user->cryptoWallets()
                        ->where('is_active', true)
                        ->first();
                }
            }

            // Check if wallet exists
            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => $request->filled('currency')
                        ? "User does not have an active {$request->currency} wallet"
                        : 'User does not have any active crypto wallets'
                ]);
            }

            // Capture old balance for logging
            $oldBalance = $wallet->balance;

            // Apply balance adjustment
            if ($request->type === 'add') {
                $wallet->increment('balance', $amount);
                $transactionAmount = $amount;
            } else {
                // Subtract - check sufficient balance
                if ($wallet->balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient balance in {$wallet->currency} wallet. Available: " . number_format($wallet->balance, 8) . " {$wallet->currency}"
                    ]);
                }
                $wallet->decrement('balance', $amount);
                $transactionAmount = $amount; // Store as positive amount, type indicates operation
            }

            $newBalance = $wallet->fresh()->balance;

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'transaction_id' => 'ADJ_' . strtoupper($request->type) . '_' . time() . '_' . $user->id,
                'type' => 'adjust',
                'amount' => $transactionAmount,
                'currency' => $wallet->currency,
                'status' => Transaction::STATUS_COMPLETED,
                'description' => "Admin balance adjustment: {$request->reason}",
                'processed_at' => now(),
                'processed_by' => Auth::id(),
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'adjustment_type' => $request->type,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'admin_reason' => $request->reason,
                    'currency' => $wallet->currency,
                    'adjusted_by' => Auth::user()->full_name,
                    'adjusted_at' => now()->toISOString()
                ]
            ]);

            // Log the adjustment
            \Log::info('Admin balance adjustment completed', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'adjustment_type' => $request->type,
                'amount' => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'admin_id' => Auth::id(),
                'reason' => $request->reason
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Balance adjusted successfully",
                'data' => [
                    'currency' => $wallet->currency,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'adjustment_amount' => $amount,
                    'adjustment_type' => $request->type,
                    'formatted_new_balance' => number_format($newBalance, 8) . ' ' . $wallet->currency
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Admin balance adjustment failed', [
                'user_id' => $id,
                'admin_id' => Auth::id(),
                'amount' => $request->amount ?? 0,
                'type' => $request->type ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust balance: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export users data
     */
    public function export(Request $request)
    {
        // Apply same filters as index
        $query = User::with(['profile', 'cryptoWallets', 'earnings', 'investments']);

        if ($request->get('investment_status')) {
            $investment_status = $request->get('investment_status');
            switch ($investment_status) {
                case 'has_investments':
                    $query->whereHas('investments');
                    break;
                case 'no_investments':
                    $query->whereDoesntHave('investments');
                    break;
                case 'active_investments':
                    $query->whereHas('investments', function ($q) {
                        $q->where('status', 'active');
                    });
                    break;
                case 'completed_investments':
                    $query->whereHas('investments', function ($q) {
                        $q->where('status', 'completed');
                    });
                    break;
            }
        }

        if ($request->get('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        // Add sponsor chain and wallet data to each user for export
        $users->transform(function ($user) {
            $user->sponsor_chain = $this->getSponsorChain($user);
            $user->total_wallet_balance_usd = $user->cryptoWallets->sum(function ($wallet) {
                return $wallet->balance * ($wallet->usd_rate ?? 0);
            });
            return $user;
        });

        $exportData = [
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()->full_name ?? auth()->user()->email,
            'total_users' => $users->count(),
            'filters' => $request->only(['investment_status', 'verification', 'search']),
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                    'email' => $user->email,
                    'username' => $user->username,
                    'status' => $user->status,
                    'email_verified' => $user->hasVerifiedEmail(),
                    'kyc_status' => $user->profile->kyc_status ?? 'not_submitted',
                    'total_wallet_balance_usd' => $user->total_wallet_balance_usd,
                    'active_wallets' => $user->cryptoWallets->where('is_active', true)->count(),
                    'wallet_details' => $user->cryptoWallets->map(function ($wallet) {
                        return [
                            'currency' => $wallet->currency,
                            'balance' => $wallet->balance,
                            'usd_value' => $wallet->balance * ($wallet->usd_rate ?? 0),
                            'is_active' => $wallet->is_active
                        ];
                    })->toArray(),
                    'total_earnings' => $user->earnings->total ?? 0,
                    'total_invested' => $user->investments->sum('amount') ?? 0,
                    'investment_returns' => $user->investments->sum('paid_return') ?? 0,
                    'active_investments' => $user->investments->where('status', 'active')->count(),
                    'completed_investments' => $user->investments->where('status', 'completed')->count(),
                    'sponsor_chain' => collect($user->sponsor_chain)->map(function ($sponsor) {
                        return [
                            'level' => $sponsor['level'],
                            'name' => ($sponsor['user']->first_name ?? '') . ' ' . ($sponsor['user']->last_name ?? ''),
                            'email' => $sponsor['user']->email ?? ''
                        ];
                    })->toArray(),
                    'country' => $user->profile->country ?? null,
                    'registered_at' => $user->created_at->toISOString(),
                    'last_login_at' => $user->last_login_at?->toISOString(),
                ];
            })
        ];

        $filename = 'users-export-' . now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    /*
    |--------------------------------------------------------------------------
    | STAFF MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display staff management page
     */
    public function staffIndex(Request $request)
    {
        $user = Auth::user();

        // Get filter parameter
        $roleFilter = $request->get('role');

        // Build query for staff members (exclude 'user' role)
        $query = User::with(['profile', 'accountBalance', 'directReferrals'])
            ->whereIn('role', ['admin', 'support', 'moderator'])
            ->select('id', 'first_name', 'last_name', 'email', 'username', 'phone', 'role', 'status', 'created_at', 'last_login_at');

        // Apply role filter
        if ($roleFilter && in_array($roleFilter, ['admin', 'support', 'moderator'])) {
            $query->where('role', $roleFilter);
        }

        // Get paginated staff members
        $staffMembers = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get staff statistics
        $staffStats = [
            'admins' => User::where('role', 'admin')->count(),
            'support' => User::where('role', 'support')->count(),
            'moderators' => User::where('role', 'moderator')->count(),
            'total' => User::whereIn('role', ['admin', 'support', 'moderator'])->count(),
        ];

        return view('admin.staff.index', compact(
            'user',
            'staffMembers',
            'staffStats'
        ));
    }

    /**
     * Search users for promotion (AJAX)
     */
    public function searchUsers(Request $request)
    {
        $search = $request->get('search');

        if (strlen($search) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 2 characters'
            ]);
        }

        try {
            // Search only users with 'user' role (not staff)
            $users = User::where('role', 'user')
                ->where('status', 'active')
                ->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                })
                ->select('id', 'first_name', 'last_name', 'email', 'username')
                ->limit(10)
                ->get();

            // Add initials for display
            $users->each(function ($user) {
                $user->initials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                $user->full_name = $user->first_name . ' ' . $user->last_name;
            });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Promote user to staff role
     */
    public function promoteUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:moderator,support,admin',
            'reason' => 'nullable|string|max:255',
            'notify_user' => 'nullable|boolean',
        ]);

        // Additional validation for role permissions
        $currentUser = Auth::user();
        if (!$currentUser->isAdmin() && $request->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot promote users to admin role'
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        try {
            DB::beginTransaction();

            $userToPromote = User::findOrFail($request->user_id);

            // Verify user has 'user' role
            if ($userToPromote->role !== 'user') {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected user is already a staff member'
                ]);
            }

            $oldRole = $userToPromote->role;

            // Update user role
            $userToPromote->update([
                'role' => $request->role
            ]);

            // Log the promotion
            \Log::info('User promoted to staff', [
                'user_id' => $userToPromote->id,
                'promoted_by' => $currentUser->id,
                'old_role' => $oldRole,
                'new_role' => $request->role,
                'reason' => $request->reason
            ]);

            DB::commit();

            // Send notification email if requested
            if ($request->notify_user) {
                try {
                    $userToPromote->notify(new StaffPromotion([
                        'promoted_by' => $currentUser->full_name,
                        'new_role' => $request->role,
                        'reason' => $request->reason
                    ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to send promotion notification', [
                        'user_id' => $userToPromote->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$userToPromote->full_name} has been promoted to " . ucfirst($request->role)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to promote user', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to promote user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Change staff member's role
     */
    public function changeRole(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:moderator,support,admin',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $currentUser = Auth::user();

        // Only admins can change roles
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can change staff roles'
            ]);
        }

        try {
            $staffMember = User::findOrFail($id);

            // Prevent changing own role
            if ($staffMember->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot change your own role'
                ]);
            }

            // Verify user is staff
            if (!in_array($staffMember->role, ['admin', 'support', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a staff member'
                ]);
            }

            $oldRole = $staffMember->role;

            // Update role
            $staffMember->update([
                'role' => $request->role
            ]);

            // Log the change
            \Log::info('Staff role changed', [
                'user_id' => $staffMember->id,
                'changed_by' => $currentUser->id,
                'old_role' => $oldRole,
                'new_role' => $request->role,
                'reason' => $request->reason
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$staffMember->full_name}'s role changed from " . ucfirst($oldRole) . " to " . ucfirst($request->role)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to change staff role', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to change role: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Demote staff member to regular user
     */
    public function demoteStaff($id)
    {
        $currentUser = Auth::user();

        // Only admins can demote staff
        if (!$currentUser->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can demote staff members'
            ]);
        }

        try {
            $staffMember = User::findOrFail($id);

            // Prevent demoting self
            if ($staffMember->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot demote yourself'
                ]);
            }

            // Verify user is staff
            if (!in_array($staffMember->role, ['admin', 'support', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a staff member'
                ]);
            }

            $oldRole = $staffMember->role;

            // Demote to user role
            $staffMember->update([
                'role' => 'user'
            ]);

            // Log the demotion
            \Log::info('Staff member demoted', [
                'user_id' => $staffMember->id,
                'demoted_by' => $currentUser->id,
                'old_role' => $oldRole,
                'new_role' => 'user'
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$staffMember->full_name} has been demoted to regular user"
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to demote staff member', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to demote staff member: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BLOCKED USERS MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display blocked users management page
     */
    public function blockedUsersIndex(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $reasonFilter = $request->get('reason');
        $search = $request->get('search');

        // Build query for blocked users
        $query = User::with(['profile', 'accountBalance', 'transactions', 'directReferrals'])
            ->where('status', 'blocked')
            ->select(
                'id',
                'first_name',
                'last_name',
                'email',
                'username',
                'phone',
                'role',
                'status',
                'created_at',
                'last_login_at',
                'blocked_at',
                'blocked_by',
                'block_reason',
                'block_notes',
                'block_expires_at'
            );

        // Apply reason filter
        if ($reasonFilter && in_array($reasonFilter, ['spam', 'fraud', 'violation', 'abuse', 'security', 'other'])) {
            $query->where('block_reason', $reasonFilter);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Get paginated blocked users with blocked_by relationship
        $blockedUsers = $query->with([
            'blockedByUser' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'email');
            }
        ])->orderBy('blocked_at', 'desc')->paginate(20);

        // Get blocked user statistics
        $blockedStats = $this->getBlockedUserStats();

        return view('admin.blocked-users.index', compact(
            'user',
            'blockedUsers',
            'blockedStats'
        ));
    }

    /**
     * Search active users for blocking (AJAX)
     */
    public function searchActiveUsers(Request $request)
    {
        $search = $request->get('search');

        if (strlen($search) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 2 characters'
            ]);
        }

        try {
            // Search only active users (not blocked, not staff unless admin)
            $query = User::where('status', 'active');

            // Non-admin users cannot block staff
            if (!Auth::user()->isAdmin()) {
                $query->where('role', 'user');
            }

            $users = $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            })
                ->select('id', 'first_name', 'last_name', 'email', 'username')
                ->limit(10)
                ->get();

            // Add initials for display
            $users->each(function ($user) {
                $user->initials = strtoupper(substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1));
                $user->full_name = $user->first_name . ' ' . $user->last_name;
            });

            return response()->json([
                'success' => true,
                'users' => $users
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search users: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Block a user
     */
    public function blockUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|in:spam,fraud,violation,abuse,security,other',
            'duration' => 'nullable|in:1,3,7,30,permanent',
            'notes' => 'nullable|string|max:500',
            'notify_user' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $currentUser = Auth::user();

        try {
            DB::beginTransaction();

            $userToBlock = User::findOrFail($request->user_id);

            // Prevent blocking self
            if ($userToBlock->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot block yourself'
                ]);
            }

            // Verify user is not already blocked
            if ($userToBlock->status === 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is already blocked'
                ]);
            }

            // Only admins can block staff members
            if (!$currentUser->isAdmin() && in_array($userToBlock->role, ['admin', 'support', 'moderator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot block staff members'
                ]);
            }

            // Calculate expiration date
            $expiresAt = null;
            if ($request->duration && $request->duration !== 'permanent') {
                $expiresAt = now()->addDays((int) $request->duration);
            }

            // Update user status and block information
            $userToBlock->update([
                'status' => 'blocked',
                'blocked_at' => now(),
                'blocked_by' => $currentUser->id,
                'block_reason' => $request->reason,
                'block_notes' => $request->notes,
                'block_expires_at' => $expiresAt,
            ]);

            // Log the block action
            \Log::info('User blocked', [
                'user_id' => $userToBlock->id,
                'blocked_by' => $currentUser->id,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'duration' => $request->duration,
                'expires_at' => $expiresAt
            ]);

            DB::commit();

            // Send notification email if requested
            if ($request->notify_user) {
                try {
                    $userToBlock->notify(new UserBlocked([
                        'blocked_by' => $currentUser->full_name,
                        'reason' => $request->reason,
                        'notes' => $request->notes,
                        'duration' => $request->duration,
                        'expires_at' => $expiresAt
                    ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to send block notification', [
                        'user_id' => $userToBlock->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$userToBlock->full_name} has been blocked successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to block user', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to block user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Unblock a user
     */
    public function unblockUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
            'notify_user' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $currentUser = Auth::user();

        try {
            DB::beginTransaction();

            $blockedUser = User::findOrFail($id);

            // Verify user is blocked
            if ($blockedUser->status !== 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not currently blocked'
                ]);
            }

            // Store old block info for logging
            $oldBlockInfo = [
                'blocked_at' => $blockedUser->blocked_at,
                'blocked_by' => $blockedUser->blocked_by,
                'block_reason' => $blockedUser->block_reason,
                'block_notes' => $blockedUser->block_notes
            ];

            // Update user status and clear block information
            $blockedUser->update([
                'status' => 'active',
                'unblocked_at' => now(),
                'unblocked_by' => $currentUser->id,
                'unblock_reason' => $request->reason,
                // Keep block history for audit purposes, don't clear it
            ]);

            // Log the unblock action
            \Log::info('User unblocked', [
                'user_id' => $blockedUser->id,
                'unblocked_by' => $currentUser->id,
                'unblock_reason' => $request->reason,
                'was_blocked_for' => $oldBlockInfo['block_reason'],
                'blocked_duration' => $blockedUser->blocked_at ? $blockedUser->blocked_at->diffInDays(now()) : null
            ]);

            DB::commit();

            // Send welcome back notification if requested
            if ($request->notify_user) {
                try {
                    $blockedUser->notify(new UserUnblocked([
                        'unblocked_by' => $currentUser->full_name,
                        'reason' => $request->reason
                    ]));
                } catch (\Exception $e) {
                    \Log::error('Failed to send unblock notification', [
                        'user_id' => $blockedUser->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$blockedUser->full_name} has been unblocked successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to unblock user', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to unblock user: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get detailed block information for a user
     */
    public function getBlockDetails($id)
    {
        try {
            $user = User::with(['blockedByUser', 'unblockedByUser'])->findOrFail($id);

            if ($user->status !== 'blocked') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not currently blocked'
                ]);
            }

            $blockDetails = [
                'blocked_at' => $user->blocked_at ? $user->blocked_at->format('M d, Y \a\t g:i A') : 'Unknown',
                'blocked_by' => $user->blockedByUser ? $user->blockedByUser->full_name : 'System',
                'block_reason' => $user->block_reason ? ucfirst($user->block_reason) : 'No reason specified',
                'block_notes' => $user->block_notes ?: 'No additional notes',
                'expires_at' => $user->block_expires_at ? $user->block_expires_at->format('M d, Y \a\t g:i A') : 'Permanent',
                'duration_so_far' => $user->blocked_at ? $user->blocked_at->diffForHumans() : 'Unknown',
                'is_expired' => $user->block_expires_at ? $user->block_expires_at->isPast() : false
            ];

            return response()->json([
                'success' => true,
                'details' => $blockDetails
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load block details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Process expired blocks (can be called via cron job)
     */
    public function processExpiredBlocks()
    {
        try {
            $expiredUsers = User::where('status', 'blocked')
                ->whereNotNull('block_expires_at')
                ->where('block_expires_at', '<=', now())
                ->get();

            $processedCount = 0;

            foreach ($expiredUsers as $user) {
                $user->update([
                    'status' => 'active',
                    'unblocked_at' => now(),
                    'unblock_reason' => 'Block period expired automatically'
                ]);

                \Log::info('User automatically unblocked due to expired block period', [
                    'user_id' => $user->id,
                    'was_blocked_for' => $user->block_reason,
                    'block_duration' => $user->blocked_at->diffInDays($user->block_expires_at)
                ]);

                $processedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Processed {$processedCount} expired blocks",
                'processed_count' => $processedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to process expired blocks', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process expired blocks: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | KYC MANAGEMENT METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Display KYC management page
     */
    public function kycIndex(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $statusFilter = $request->get('status');
        $search = $request->get('search');

        // Build query for users with their profiles
        $query = User::with([
            'profile',
            'accountBalance',
            'kycVerifications' => function ($q) {
                $q->latest()->first();
            }
        ])
            ->select('users.id', 'first_name', 'last_name', 'email', 'username', 'phone', 'role', 'status', 'users.created_at', 'users.updated_at');

        // Apply status filter
        if ($statusFilter && in_array($statusFilter, ['not_submitted', 'pending', 'submitted', 'under_review', 'verified', 'rejected'])) {
            $query->whereHas('profile', function ($q) use ($statusFilter) {
                $q->where('kyc_status', $statusFilter);
            });
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Get paginated users with proper table prefixes
        $kycUsers = $query->leftJoin('user_profiles as profiles', 'users.id', '=', 'profiles.user_id')
            ->orderByRaw("
            CASE 
                WHEN profiles.kyc_status = 'submitted' THEN 1
                WHEN profiles.kyc_status = 'under_review' THEN 2
                WHEN profiles.kyc_status = 'pending' THEN 3
                WHEN profiles.kyc_status = 'rejected' THEN 4
                WHEN profiles.kyc_status = 'verified' THEN 5
                ELSE 6
            END
        ")
            ->orderBy('profiles.kyc_submitted_at', 'desc')
            ->paginate(20);

        // Get KYC statistics
        $kycStats = $this->getKycStats();

        return view('admin.kyc.index', compact(
            'user',
            'kycUsers',
            'kycStats'
        ));
    }

    /**
     * Update KYC status for a user
     */
    public function updateKycStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:not_submitted,pending,submitted,under_review,verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'notify_user' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $currentUser = Auth::user();

        try {
            DB::beginTransaction();

            $user = User::with('profile')->findOrFail($id);

            // Create profile if doesn't exist
            if (!$user->profile) {
                $user->profile()->create([
                    'kyc_status' => $request->status
                ]);
            } else {
                $profileData = [
                    'kyc_status' => $request->status,
                ];

                // Handle specific status updates
                switch ($request->status) {
                    case 'verified':
                        $profileData['kyc_verified_at'] = now();
                        $profileData['kyc_rejection_reason'] = null;
                        break;
                    case 'rejected':
                        $profileData['kyc_rejection_reason'] = $request->rejection_reason;
                        $profileData['kyc_verified_at'] = null;
                        break;
                    case 'submitted':
                        if (!$user->profile->kyc_submitted_at) {
                            $profileData['kyc_submitted_at'] = now();
                        }
                        break;
                    case 'under_review':
                        if (!$user->profile->kyc_submitted_at) {
                            $profileData['kyc_submitted_at'] = now();
                        }
                        break;
                    default:
                        if (in_array($request->status, ['not_submitted', 'pending'])) {
                            $profileData['kyc_verified_at'] = null;
                            $profileData['kyc_rejection_reason'] = null;
                        }
                        break;
                }

                $user->profile->update($profileData);
            }

            // Log the status change
            \Log::info('KYC status updated', [
                'user_id' => $user->id,
                'updated_by' => $currentUser->id,
                'old_status' => $user->profile->kyc_status,
                'new_status' => $request->status,
                'rejection_reason' => $request->rejection_reason,
                'notes' => $request->notes
            ]);

            DB::commit();

            // Send notification email if requested
            // if ($request->notify_user) {
            //     try {
            //         $user->notify(new KycStatusUpdated([
            //             'status' => $request->status,
            //             'rejection_reason' => $request->rejection_reason,
            //             'notes' => $request->notes,
            //             'updated_by' => $currentUser->full_name
            //         ]));
            //     } catch (\Exception $e) {
            //         \Log::error('Failed to send KYC status notification', [
            //             'user_id' => $user->id,
            //             'error' => $e->getMessage()
            //         ]);
            //     }
            // }

            return response()->json([
                'success' => true,
                'message' => "KYC status updated to " . ucwords(str_replace('_', ' ', $request->status)) . " successfully"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update KYC status', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update KYC status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get detailed KYC information for a user
     */
    public function getKycDetails($id)
    {
        try {
            $user = User::with([
                'profile',
                'kycVerifications' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])->findOrFail($id);

            $kycDetails = [
                'user' => [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'joined' => $user->created_at->format('M d, Y'),
                ],
                'profile' => [
                    'kyc_status' => $user->profile->kyc_status ?? 'not_submitted',
                    'country' => $user->profile->country_name ?? 'Not specified',
                    'city' => $user->profile->city ?? 'Not specified',
                    'address' => $user->profile->address ?? 'Not provided',
                    'date_of_birth' => $user->profile->date_of_birth ? $user->profile->date_of_birth->format('M d, Y') : 'Not provided',
                    'kyc_submitted_at' => $user->profile->kyc_submitted_at ? $user->profile->kyc_submitted_at->format('M d, Y \a\t g:i A') : null,
                    'kyc_verified_at' => $user->profile->kyc_verified_at ? $user->profile->kyc_verified_at->format('M d, Y \a\t g:i A') : null,
                    'kyc_rejection_reason' => $user->profile->kyc_rejection_reason,
                ],
                'verifications' => $user->kycVerifications->map(function ($verification) {
                    return [
                        'id' => $verification->id,
                        'status' => $verification->status,
                        'decision' => $verification->decision,
                        'verified_name' => $verification->verified_full_name,
                        'document_type' => $verification->document_type_display,
                        'document_country' => $verification->document_country,
                        'document_number' => $verification->document_number,
                        'document_verified' => $verification->document_verified,
                        'face_verified' => $verification->face_verified,
                        'liveness_check' => $verification->liveness_check,
                        'created_at' => $verification->created_at->format('M d, Y \a\t g:i A'),
                        'verified_at' => $verification->verified_at ? $verification->verified_at->format('M d, Y \a\t g:i A') : null,
                    ];
                })->toArray()
            ];

            $html = view('admin.kyc.details', compact('user', 'kycDetails'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load KYC details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk update KYC statuses
     */
    public function bulkUpdateKycStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'status' => 'required|in:not_submitted,pending,submitted,under_review,verified,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:500',
            'notify_users' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $currentUser = Auth::user();

        try {
            DB::beginTransaction();

            $userIds = $request->user_ids;
            $processedCount = 0;

            foreach ($userIds as $userId) {
                $user = User::with('profile')->find($userId);
                if (!$user)
                    continue;

                // Create profile if doesn't exist
                if (!$user->profile) {
                    $user->profile()->create(['kyc_status' => $request->status]);
                } else {
                    $profileData = ['kyc_status' => $request->status];

                    // Handle specific status updates
                    switch ($request->status) {
                        case 'verified':
                            $profileData['kyc_verified_at'] = now();
                            $profileData['kyc_rejection_reason'] = null;
                            break;
                        case 'rejected':
                            $profileData['kyc_rejection_reason'] = $request->rejection_reason;
                            $profileData['kyc_verified_at'] = null;
                            break;
                        case 'submitted':
                            if (!$user->profile->kyc_submitted_at) {
                                $profileData['kyc_submitted_at'] = now();
                            }
                            break;
                    }

                    $user->profile->update($profileData);
                }

                // Send notification if requested
                // if ($request->notify_users) {
                //     try {
                //         $user->notify(new KycStatusUpdated([
                //             'status' => $request->status,
                //             'rejection_reason' => $request->rejection_reason,
                //             'updated_by' => $currentUser->full_name,
                //             'bulk_update' => true
                //         ]));
                //     } catch (\Exception $e) {
                //         \Log::error('Failed to send bulk KYC notification', [
                //             'user_id' => $user->id,
                //             'error' => $e->getMessage()
                //         ]);
                //     }
                // }

                $processedCount++;
            }

            // Log the bulk update
            \Log::info('Bulk KYC status update', [
                'updated_by' => $currentUser->id,
                'user_count' => $processedCount,
                'new_status' => $request->status,
                'rejection_reason' => $request->rejection_reason
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated KYC status for {$processedCount} users"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to bulk update KYC status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update KYC statuses: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Export KYC data
     */
    public function exportKycData(Request $request)
    {
        try {
            // Apply same filters as index
            $query = User::with(['profile', 'kycVerifications']);

            if ($request->get('status')) {
                $query->whereHas('profile', function ($q) use ($request) {
                    $q->where('kyc_status', $request->get('status'));
                });
            }

            if ($request->get('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            }

            $users = $query->get();

            $exportData = [
                'exported_at' => now()->toISOString(),
                'exported_by' => auth()->user()->full_name,
                'total_users' => $users->count(),
                'filters' => $request->only(['status', 'search']),
                'users' => $users->map(function ($user) {
                    $latestVerification = $user->kycVerifications->first();

                    return [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'kyc_status' => $user->profile->kyc_status ?? 'not_submitted',
                        'kyc_submitted_at' => $user->profile->kyc_submitted_at?->toISOString(),
                        'kyc_verified_at' => $user->profile->kyc_verified_at?->toISOString(),
                        'kyc_rejection_reason' => $user->profile->kyc_rejection_reason,
                        'country' => $user->profile->country,
                        'city' => $user->profile->city,
                        'date_of_birth' => $user->profile->date_of_birth?->toDateString(),
                        'latest_verification_status' => $latestVerification->status ?? null,
                        'latest_verification_decision' => $latestVerification->decision ?? null,
                        'registered_at' => $user->created_at->toISOString(),
                    ];
                })
            ];

            $filename = 'kyc-export-' . now()->format('Y-m-d-H-i-s') . '.json';

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export KYC data: ' . $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get user summary statistics
     */
    private function getUserSummaryData($request): array
    {
        $baseQuery = User::query();

        // Apply same filters as main query for consistent stats
        if ($request->get('search')) {
            $search = $request->get('search');
            $baseQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Calculate total wallet balance across all users - FIXED with table aliases
        $totalWalletBalance = \App\Models\CryptoWallet::where('crypto_wallets.is_active', true)
            ->join('cryptocurrencies', 'crypto_wallets.currency', '=', 'cryptocurrencies.symbol')
            ->where('cryptocurrencies.is_active', true)
            ->selectRaw('SUM(crypto_wallets.balance * crypto_wallets.usd_rate) as total_usd')
            ->value('total_usd') ?? 0;

        $avgWalletBalance = $totalWalletBalance / max(1, (clone $baseQuery)->count());

        return [
            'total_users' => (clone $baseQuery)->count(),
            // Updated: Active users must have status='active' AND have investments
            'active_users' => (clone $baseQuery)
                ->where('status', 'active')
                ->whereHas('investments', function ($q) {
                    $q->whereIn('status', ['active', 'completed']);
                })
                ->count(),
            'inactive_users' => (clone $baseQuery)->where('status', 'inactive')->count(),
            'blocked_users' => (clone $baseQuery)->where('status', 'blocked')->count(),
            'verified_users' => (clone $baseQuery)->whereNotNull('email_verified_at')->count(),
            'kyc_verified' => (clone $baseQuery)->whereHas('profile', function ($q) {
                $q->where('kyc_status', 'verified');
            })->count(),
            'new_today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'new_this_week' => (clone $baseQuery)->where('created_at', '>=', now()->startOfWeek())->count(),
            'online_now' => (clone $baseQuery)->where('last_login_at', '>=', now()->subMinutes(5))->count(),
            // Investment-related stats
            'users_with_investments' => (clone $baseQuery)->whereHas('investments')->count(),
            'users_with_active_investments' => (clone $baseQuery)
                ->whereHas('investments', function ($q) {
                    $q->where('status', 'active');
                })
                ->count(),
            'total_balance' => $totalWalletBalance,
            'avg_balance' => $avgWalletBalance,
        ];
    }


    /**
     * Get individual user statistics
     */
    private function getUserStats($user): array
    {
        // Get investment statistics
        $totalInvested = $user->investments()->sum('amount');
        $activeInvestments = $user->investments()->where('status', 'active')->count();
        $completedInvestments = $user->investments()->where('status', 'completed')->count();
        $totalReturnsEarned = $user->investments()->sum('paid_return');
        $expectedReturns = $user->investments()->where('status', 'active')->sum('total_return');

        // Calculate total wallet balance in USD - filter active wallets
        $totalWalletBalance = $user->cryptoWallets
            ->where('is_active', true)
            ->sum(function ($wallet) {
                return $wallet->balance * ($wallet->usd_rate ?? 0);
            });

        return [
            // Existing transaction stats
            'total_deposits' => $user->transactions()->where('type', 'deposit')->where('status', 'completed')->sum('amount'),
            'total_withdrawals' => $user->transactions()->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'),
            'pending_withdrawals' => $user->transactions()->where('type', 'withdrawal')->whereIn('status', ['pending', 'processing'])->sum('amount'),
            'total_commissions' => $user->transactions()->where('type', 'commission')->where('status', 'completed')->sum('amount'),
            'transaction_count' => $user->transactions()->count(),

            // Wallet stats
            'total_wallet_balance' => $totalWalletBalance,
            'active_wallets' => $user->cryptoWallets->where('is_active', true)->count(),
            'wallets_with_balance' => $user->cryptoWallets->where('is_active', true)->where('balance', '>', 0)->count(),

            // Investment stats
            'total_invested' => $totalInvested,
            'active_investments' => $activeInvestments,
            'completed_investments' => $completedInvestments,
            'total_returns_earned' => $totalReturnsEarned,
            'expected_returns' => $expectedReturns,
            'investment_roi' => $totalInvested > 0 ? ($totalReturnsEarned / $totalInvested) * 100 : 0,

            // Existing stats
            'referral_count' => $user->directReferrals()->count(),
            'active_referrals' => $user->directReferrals()->whereHas('investments')->count(),
            'account_age_days' => $user->created_at->diffInDays(now()),
            'last_login_ago' => $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never',
        ];
    }


    /**
     * Get blocked user statistics
     */
    private function getBlockedUserStats(): array
    {
        return [
            'total' => User::where('status', 'blocked')->count(),
            'this_month' => User::where('status', 'blocked')
                ->where('blocked_at', '>=', now()->startOfMonth())
                ->count(),
            'unblocked_today' => User::where('status', 'active')
                ->whereNotNull('blocked_at')
                ->whereDate('updated_at', today())
                ->count(),
            'under_review' => User::where('status', 'blocked')
                ->where('block_reason', 'security')
                ->orWhere('block_reason', 'fraud')
                ->count(),
        ];
    }

    /**
     * Get KYC statistics
     */
    private function getKycStats(): array
    {
        $stats = UserProfile::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN kyc_status = "verified" THEN 1 ELSE 0 END) as verified,
            SUM(CASE WHEN kyc_status = "rejected" THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN kyc_status = "under_review" THEN 1 ELSE 0 END) as under_review,
            SUM(CASE WHEN kyc_status = "submitted" THEN 1 ELSE 0 END) as submitted,
            SUM(CASE WHEN kyc_status = "pending" THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN kyc_status IS NULL OR kyc_status = "not_submitted" THEN 1 ELSE 0 END) as not_submitted
        ')->first();

        return [
            'total' => User::count(),
            'verified' => $stats->verified ?? 0,
            'rejected' => $stats->rejected ?? 0,
            'under_review' => $stats->under_review ?? 0,
            'submitted' => $stats->submitted ?? 0,
            'pending' => $stats->pending ?? 0,
            'not_submitted' => $stats->not_submitted ?? 0,
        ];
    }

    /**
     * Get countries list
     */
    private function getCountries(): array
    {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BD' => 'Bangladesh',
            'BE' => 'Belgium',
            'BR' => 'Brazil',
            'CA' => 'Canada',
            'CN' => 'China',
            'FR' => 'France',
            'DE' => 'Germany',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'MY' => 'Malaysia',
            'NL' => 'Netherlands',
            'NG' => 'Nigeria',
            'PK' => 'Pakistan',
            'PH' => 'Philippines',
            'RU' => 'Russia',
            'SA' => 'Saudi Arabia',
            'SG' => 'Singapore',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'TH' => 'Thailand',
            'TR' => 'Turkey',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'VN' => 'Vietnam',
        ];
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

    public function getInactiveActiveUsers(Request $request)
    {
        $users = User::where('status', 'active')
            ->whereDoesntHave('investments')
            ->with(['profile', 'accountBalance'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Users with active status but no investments',
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'created_at' => $user->created_at->diffForHumans(),
                    'balance' => $user->accountBalance ? $user->accountBalance->balance : 0,
                ];
            }),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    // 5. Add method to check user's investment eligibility
    public function checkInvestmentEligibility($userId)
    {
        $user = User::with(['investments', 'profile', 'accountBalance'])->findOrFail($userId);

        $hasActiveInvestment = $user->investments()->where('status', 'active')->exists();
        $hasCompletedInvestment = $user->investments()->where('status', 'completed')->exists();
        $hasAnyInvestment = $user->investments()->exists();
        $isKycVerified = $user->profile && $user->profile->kyc_status === 'verified';
        $hasBalance = $user->accountBalance && $user->accountBalance->balance > 0;

        return response()->json([
            'success' => true,
            'user_id' => $userId,
            'eligibility' => [
                'is_truly_active' => $user->status === 'active' && $hasAnyInvestment,
                'can_invest' => $user->status === 'active' && $isKycVerified && $hasBalance,
                'has_active_investment' => $hasActiveInvestment,
                'has_completed_investment' => $hasCompletedInvestment,
                'has_any_investment' => $hasAnyInvestment,
                'kyc_verified' => $isKycVerified,
                'has_balance' => $hasBalance,
                'balance_amount' => $user->accountBalance ? $user->accountBalance->balance : 0,
            ]
        ]);
    }

    /**
     * Get sponsor chain for a user
     */
    private function getSponsorChain(User $user, int $levels = 3): array
    {
        $chain = [];
        $currentUser = $user;
        $level = 1;

        while ($level <= $levels && $currentUser->sponsor_id) {
            $sponsor = User::with('profile')->find($currentUser->sponsor_id);

            if (!$sponsor) {
                break;
            }

            $chain[] = [
                'user' => $sponsor,
                'level' => $level
            ];

            $currentUser = $sponsor;
            $level++;
        }

        return $chain;
    }

    /*
|--------------------------------------------------------------------------
| REFERRAL INVESTMENTS METHODS
|--------------------------------------------------------------------------
*/

    /**
     * Display referral investments page for a specific user
     */
    public function referralInvestments($id, Request $request)
    {
        $user = Auth::user();
        $targetUser = User::with(['profile', 'directReferrals'])->findOrFail($id);

        // Get filter parameters
        $levelFilter = $request->get('level');
        $search = $request->get('search');
        $statusFilter = $request->get('status');

        // Get summary statistics
        $summary = $this->getReferralInvestmentSummary($id);

        // Get all referral data
        $allReferralData = $this->getReferralInvestmentData($id, $levelFilter);

        // Apply search filter
        if ($search) {
            $allReferralData = array_filter($allReferralData, function ($referral) use ($search) {
                return stripos($referral->full_name, $search) !== false ||
                    stripos($referral->email, $search) !== false ||
                    stripos($referral->username, $search) !== false;
            });
        }

        // Apply status filter
        if ($statusFilter) {
            $allReferralData = array_filter($allReferralData, function ($referral) use ($statusFilter) {
                return $referral->status === $statusFilter;
            });
        }

        // Convert to collection for pagination
        $collection = collect($allReferralData);

        // Paginate the results
        $perPage = 20;
        $currentPage = $request->get('page', 1);
        $currentPageData = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $referralData = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageData,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.users.referral-investments', compact(
            'user',
            'targetUser',
            'referralData',
            'summary'
        ));
    }


    /**
     * Get referral investment data using recursive CTE
     */
    private function getReferralInvestmentData($userId, $levelFilter = null)
    {
        $query = "
        WITH RECURSIVE referral_tree AS (
            -- Base case: direct referrals (level 1)
            SELECT 
                id, 
                sponsor_id, 
                email,
                CONCAT(first_name, ' ', last_name) as full_name,
                username,
                status,
                created_at,
                1 as referral_level
            FROM users
            WHERE sponsor_id = ?
            
            UNION ALL
            
            -- Recursive case: indirect referrals (level 2, 3, 4...)
            SELECT 
                u.id, 
                u.sponsor_id,
                u.email,
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.username,
                u.status,
                u.created_at,
                rt.referral_level + 1
            FROM users u
            INNER JOIN referral_tree rt ON u.sponsor_id = rt.id
            WHERE rt.referral_level < 10
        ),
        investment_summary AS (
            SELECT 
                user_id,
                SUM(amount) as total_invested,
                COUNT(id) as investment_count,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_investments,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_investments
            FROM user_investments
            GROUP BY user_id
        ),
        roi_summary AS (
            SELECT 
                user_id,
                SUM(CASE WHEN type = 'roi' AND status = 'completed' THEN amount ELSE 0 END) as total_roi_received,
                COUNT(CASE WHEN type = 'roi' AND status = 'completed' THEN 1 END) as roi_payment_count
            FROM transactions
            GROUP BY user_id
        )
        SELECT 
            rt.id as user_id,
            rt.full_name,
            rt.email,
            rt.username,
            rt.status,
            rt.created_at,
            rt.referral_level,
            COALESCE(inv.total_invested, 0) as total_invested,
            COALESCE(roi.total_roi_received, 0) as total_roi_received,
            COALESCE(inv.investment_count, 0) as investment_count,
            COALESCE(roi.roi_payment_count, 0) as roi_payment_count,
            COALESCE(inv.active_investments, 0) as active_investments,
            COALESCE(inv.completed_investments, 0) as completed_investments
        FROM referral_tree rt
        LEFT JOIN investment_summary inv ON rt.id = inv.user_id
        LEFT JOIN roi_summary roi ON rt.id = roi.user_id
        WHERE COALESCE(inv.total_invested, 0) > 0
    ";

        // Add level filter if specified
        if ($levelFilter) {
            $query .= " AND rt.referral_level = ?";
            $params = [$userId, (int) $levelFilter];
        } else {
            $params = [$userId];
        }

        $query .= " ORDER BY rt.referral_level, rt.id";

        return DB::select($query, $params);
    }

    /**
     * Get referral investment summary
     */
    private function getReferralInvestmentSummary($userId)
    {
        $query = "
        WITH RECURSIVE referral_tree AS (
            SELECT id, sponsor_id, 1 as referral_level
            FROM users
            WHERE sponsor_id = ?
            
            UNION ALL
            
            SELECT u.id, u.sponsor_id, rt.referral_level + 1
            FROM users u
            INNER JOIN referral_tree rt ON u.sponsor_id = rt.id
            WHERE rt.referral_level < 10
        ),
        investment_summary AS (
            SELECT 
                user_id,
                SUM(amount) as total_invested,
                COUNT(id) as total_investments,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count
            FROM user_investments
            WHERE user_id IN (SELECT id FROM referral_tree)
            GROUP BY user_id
        ),
        roi_summary AS (
            SELECT 
                user_id,
                SUM(CASE WHEN type = 'roi' AND status = 'completed' THEN amount ELSE 0 END) as total_roi_paid,
                COUNT(CASE WHEN type = 'roi' AND status = 'completed' THEN 1 END) as roi_payment_count
            FROM transactions
            WHERE user_id IN (SELECT id FROM referral_tree)
            GROUP BY user_id
        )
        SELECT 
            COUNT(DISTINCT rt.id) as total_referrals,
            MAX(rt.referral_level) as max_depth,
            COALESCE(SUM(inv.total_invested), 0) as total_invested_by_all_referrals,
            COALESCE(SUM(roi.total_roi_paid), 0) as total_roi_paid_to_all_referrals,
            COALESCE(SUM(inv.total_investments), 0) as total_investments,
            COALESCE(SUM(roi.roi_payment_count), 0) as total_roi_payments,
            COUNT(DISTINCT CASE WHEN inv.active_count > 0 THEN rt.id END) as users_with_active_investments
        FROM referral_tree rt
        LEFT JOIN investment_summary inv ON rt.id = inv.user_id
        LEFT JOIN roi_summary roi ON rt.id = roi.user_id
    ";

        $result = DB::select($query, [$userId]);
        return $result[0] ?? null;
    }

    /**
     * Get referral summary by level
     */
    public function getReferralSummaryByLevel($id)
    {
        $query = "
        WITH RECURSIVE referral_tree AS (
            SELECT id, sponsor_id, 1 as referral_level
            FROM users
            WHERE sponsor_id = ?
            
            UNION ALL
            
            SELECT u.id, u.sponsor_id, rt.referral_level + 1
            FROM users u
            INNER JOIN referral_tree rt ON u.sponsor_id = rt.id
            WHERE rt.referral_level < 10
        ),
        investment_summary AS (
            SELECT 
                user_id,
                SUM(amount) as total_invested,
                COUNT(id) as investment_count
            FROM user_investments
            WHERE user_id IN (SELECT id FROM referral_tree)
            GROUP BY user_id
        ),
        roi_summary AS (
            SELECT 
                user_id,
                SUM(CASE WHEN type = 'roi' AND status = 'completed' THEN amount ELSE 0 END) as total_roi_paid
            FROM transactions
            WHERE user_id IN (SELECT id FROM referral_tree)
            GROUP BY user_id
        )
        SELECT 
            rt.referral_level,
            COUNT(DISTINCT rt.id) as users_count,
            COALESCE(SUM(inv.total_invested), 0) as total_invested,
            COALESCE(SUM(roi.total_roi_paid), 0) as total_roi_paid,
            COALESCE(SUM(inv.investment_count), 0) as investment_count
        FROM referral_tree rt
        LEFT JOIN investment_summary inv ON rt.id = inv.user_id
        LEFT JOIN roi_summary roi ON rt.id = roi.user_id
        GROUP BY rt.referral_level
        ORDER BY rt.referral_level
    ";

        $data = DB::select($query, [$id]);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Export referral investment data
     */
    public function exportReferralInvestments($id)
    {
        $targetUser = User::findOrFail($id);
        $referralData = $this->getReferralInvestmentData($id);
        $summary = $this->getReferralInvestmentSummary($id);

        $exportData = [
            'exported_at' => now()->toISOString(),
            'exported_by' => auth()->user()->full_name,
            'target_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->full_name,
                'email' => $targetUser->email,
                'username' => $targetUser->username,
            ],
            'summary' => [
                'total_referrals' => $summary->total_referrals ?? 0,
                'max_depth' => $summary->max_depth ?? 0,
                'total_invested' => $summary->total_invested_by_all_referrals ?? 0,
                'total_roi_paid' => $summary->total_roi_paid_to_all_referrals ?? 0,
                'total_investments' => $summary->total_investments ?? 0,
                'total_roi_payments' => $summary->total_roi_payments ?? 0,
            ],
            'referrals' => collect($referralData)->map(function ($referral) {
                return [
                    'user_id' => $referral->user_id,
                    'name' => $referral->full_name,
                    'email' => $referral->email,
                    'username' => $referral->username,
                    'status' => $referral->status,
                    'referral_level' => $referral->referral_level,
                    'total_invested' => $referral->total_invested,
                    'total_roi_received' => $referral->total_roi_received,
                    'investment_count' => $referral->investment_count,
                    'roi_payment_count' => $referral->roi_payment_count,
                    'active_investments' => $referral->active_investments,
                    'completed_investments' => $referral->completed_investments,
                    'joined_at' => $referral->created_at,
                ];
            })
        ];

        $filename = 'referral-investments-' . $targetUser->id . '-' . now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }
}