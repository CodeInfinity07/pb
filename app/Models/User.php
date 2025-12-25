<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Laravel\Sanctum\HasApiTokens;  // Import this
use Carbon\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasPushSubscriptions;

    // Define role constants
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPPORT = 'support';
    public const ROLE_MODERATOR = 'moderator';
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'username',
        'phone',
        'password',
        'referral_code',
        'sponsor_id',
        'status',
        'role',
        'last_login_at',
        'last_login_ip',
        'google2fa_secret',
        'google2fa_enabled',
        'google2fa_enabled_at',
        'user_level',
        'total_invested',
        'total_earned',
        'level_updated_at',
        'email_verified_at',
        'must_change_password',
        'password_changed_at',
        'push_notifications_enabled',
        'last_push_subscription_at',
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google2fa_secret', // Hide the 2FA secret
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'google2fa_enabled_at' => 'datetime',
            'google2fa_enabled' => 'boolean',
            'password' => 'hashed',
            'total_invested' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'level_updated_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get user's total balance.
     */
    public function getTotalBalanceAttribute(): float
    {
        return $this->accountBalance ? $this->accountBalance->balance : 0.00;
    }


    /**
     * Get user's total earnings.
     */
    public function getTotalEarningsAttribute(): string
    {
        return $this->earnings ? $this->earnings->total : '0.00';
    }

    /**
     * Get user's today earnings.
     */
    public function getTodayEarningsAttribute(): string
    {
        return $this->earnings ? $this->earnings->today : '0.00';
    }

    /**
     * Get count of active direct referrals.
     */
    public function getActiveReferralsCountAttribute(): int
    {
        return $this->directReferrals()->where('status', 'active')->count();
    }

    /**
     * Get referral earnings.
     */
    public function getReferralEarningsAttribute(): float
    {
        return $this->referrals()->sum('commission_earned');
    }

    /**
     * Get pending commissions.
     */
    public function getPendingCommissionsAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'commission')
            ->where('status', 'pending')
            ->sum('amount');
    }

    /**
     * Get active investments.
     */
    public function getActiveInvestmentsAttribute()
    {
        return $this->investments()->where('status', 'active')->get();
    }

    /**
     * Get total investment amount.
     */
    public function getTotalInvestmentAmountAttribute(): float
    {
        return $this->investments()->where('status', 'active')->sum('amount');
    }

    /**
     * Get last deposit amount.
     */
    public function getLastDepositAttribute(): float
    {
        $lastDeposit = $this->transactions()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->latest()
            ->first();

        return $lastDeposit ? $lastDeposit->amount : 0.00;
    }

    /**
     * Get last withdrawal amount.
     */
    public function getLastWithdrawalAttribute(): float
    {
        $lastWithdrawal = $this->transactions()
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->latest()
            ->first();

        return $lastWithdrawal ? $lastWithdrawal->amount : 0.00;
    }

    /**
     * Get total deposits.
     */
    public function getTotalDepositsAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'deposit')
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get total withdrawals.
     */
    public function getTotalWithdrawalsAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'withdrawal')
            ->where('status', 'completed')
            ->sum('amount');
    }

    /**
     * Get pending withdrawals.
     */
    public function getPendingWithdrawalsAttribute(): float
    {
        return $this->transactions()
            ->where('type', 'withdrawal')
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');
    }

    /**
     * Get user's referral link.
     */
    public function getReferralLinkAttribute(): string
    {
        return url('/register?ref=' . $this->referral_code);
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is support.
     */
    public function isSupport(): bool
    {
        return $this->hasRole(self::ROLE_SUPPORT);
    }

    /**
     * Check if user is moderator.
     */
    public function isModerator(): bool
    {
        return $this->hasRole(self::ROLE_MODERATOR);
    }

    /**
     * Check if user is regular user.
     */
    public function isUser(): bool
    {
        return $this->hasRole(self::ROLE_USER);
    }

    /**
     * Check if user has admin privileges.
     */
    public function hasAdminPrivileges(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN]);
    }

    /**
     * Check if user has staff privileges.
     */
    public function hasStaffPrivileges(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_SUPPORT, self::ROLE_MODERATOR]);
    }

    /**
     * Check if user can manage other users.
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_MODERATOR]);
    }

    /**
     * Check if user can access admin panel.
     */
    public function canAccessAdmin(): bool
    {
        return $this->hasStaffPrivileges();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $role): bool
    {
        if (in_array($role, [self::ROLE_ADMIN, self::ROLE_SUPPORT, self::ROLE_MODERATOR, self::ROLE_USER])) {
            return $this->update(['role' => $role]);
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the user's profile.
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the user's account balance.
     */
    public function accountBalance(): HasOne
    {
        return $this->hasOne(AccountBalance::class);
    }

    /**
     * Get the user's earnings.
     */
    public function earnings(): HasOne
    {
        return $this->hasOne(UserEarning::class);
    }

    /**
     * Get the user's crypto wallets.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(CryptoWallet::class);
    }

    /**
     * Get the user's transactions.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the user's investments.
     */
    public function investments(): HasMany
    {
        return $this->hasMany(UserInvestment::class);
    }

    /**
     * Get the user's sponsor (upline).
     */
    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sponsor_id');
    }

    /**
     * Get the user's direct referrals.
     */
    public function directReferrals(): HasMany
    {
        return $this->hasMany(User::class, 'sponsor_id');
    }

    /**
     * Get all users this user has referred (direct).
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'sponsor_id');
    }

    /**
     * Get all KYC verifications for this user.
     */
    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    /**
     * Get the latest KYC verification.
     */
    public function latestKycVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class)->latest();
    }

    /**
     * Get the approved KYC verification.
     */
    public function approvedKycVerification(): HasOne
    {
        return $this->hasOne(KycVerification::class)->approved();
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is KYC verified (updated to check new KYC table).
     */
    public function isKycVerified(): bool
    {
        // Check new verification table first
        if ($this->approvedKycVerification()->exists()) {
            return true;
        }

        // Fallback to profile table for backward compatibility
        return $this->profile && $this->profile->kyc_status === 'verified';
    }

    /**
     * Get KYC verification status.
     */
    public function getKycStatusAttribute(): string
    {
        $latestVerification = $this->latestKycVerification;

        if ($latestVerification) {
            if ($latestVerification->isApproved()) {
                return 'verified';
            } elseif ($latestVerification->isDeclined()) {
                return 'rejected';
            } elseif ($latestVerification->isPending()) {
                return 'pending';
            }
        }

        // Fallback to profile KYC status
        return $this->profile->kyc_status ?? 'not_submitted';
    }

    /**
     * Check if user can access dashboard (verified and active)
     */
    public function canAccessDashboard(): bool
    {
        return $this->hasVerifiedEmail() && $this->isActive();
    }

    /**
     * Check if user can withdraw.
     */
    public function canWithdraw(float $amount): bool
    {
        return $this->isActive()
            && $this->isVerified()
            && $this->isKycVerified()
            && $this->getAvailableBalanceAttribute() >= $amount;
    }

    /**
     * Check if user can invest.
     */
    public function canInvest(float $amount): bool
    {
        return $this->isActive()
            && $this->isVerified()
            && $this->getAvailableBalanceAttribute() >= $amount;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Update last login information.
     */
    public function updateLastLogin(?string $ip = null): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip ?: request()->ip(),
        ]);
    }

    /**
     * Activate the user account.
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate the user account.
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Block the user account.
     */
    public function block(): bool
    {
        return $this->update(['status' => 'blocked']);
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL VERIFICATION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Send email verification notification (override to add logging)
     */
    public function sendEmailVerificationNotification()
    {
        \Log::info('Sending email verification', [
            'user_id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name
        ]);

        $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail);
    }

    /**
     * Mark email as verified (override to add logging and status update)
     */
    public function markEmailAsVerified()
    {
        $result = $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();

        if ($result) {
            // Update status to active when email is verified
            $this->update(['status' => 'active']);

            // Send welcome email NOW that email is verified
            $this->notify(
                \App\Notifications\UnifiedNotification::welcome($this)
            );

            \Log::info('Email marked as verified and user activated', [
                'user_id' => $this->id,
                'email' => $this->email,
                'verified_at' => $this->email_verified_at,
                'status' => $this->status
            ]);
        }

        return $result;
    }

    /**
     * Determine if the user has verified their email address.
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get the email address that should be used for verification.
     */
    public function getEmailForVerification()
    {
        return $this->email;
    }

    /*
    |--------------------------------------------------------------------------
    | QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope for KYC verified users (updated to check new table).
     */
    public function scopeKycVerified($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('approvedKycVerification')
                ->orWhereHas('profile', function ($profileQuery) {
                    $profileQuery->where('kyc_status', 'verified');
                });
        });
    }

    /**
     * Scope for users with KYC pending.
     */
    public function scopeKycPending($query)
    {
        return $query->whereHas('kycVerifications', function ($q) {
            $q->pending();
        });
    }

    /**
     * Scope for users with KYC declined.
     */
    public function scopeKycDeclined($query)
    {
        return $query->whereHas('kycVerifications', function ($q) {
            $q->declined();
        });
    }

    /**
     * Scope for users with pending email verification.
     */
    public function scopePendingVerification($query)
    {
        return $query->where('status', 'pending_verification')
            ->whereNull('email_verified_at');
    }

    /**
     * Scope for users by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for users by role.
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope for admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }

    /**
     * Scope for support users.
     */
    public function scopeSupport($query)
    {
        return $query->where('role', self::ROLE_SUPPORT);
    }

    /**
     * Scope for moderator users.
     */
    public function scopeModerators($query)
    {
        return $query->where('role', self::ROLE_MODERATOR);
    }

    /**
     * Scope for regular users.
     */
    public function scopeRegularUsers($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope for staff users (admin, support, moderator).
     */
    public function scopeStaff($query)
    {
        return $query->whereIn('role', [self::ROLE_ADMIN, self::ROLE_SUPPORT, self::ROLE_MODERATOR]);
    }

    /**
     * Scope for users with sponsor.
     */
    public function scopeWithSponsor($query)
    {
        return $query->whereNotNull('sponsor_id');
    }

    /**
     * Scope for users without sponsor.
     */
    public function scopeWithoutSponsor($query)
    {
        return $query->whereNull('sponsor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | UTILITY METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get user's initials for avatar fallback.
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    /**
     * Get user's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name ?: $this->username;
    }

    /**
     * Get user's role display name.
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return ucfirst($this->role);
    }

    /**
     * Get all available roles.
     */
    public static function getAvailableRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_SUPPORT => 'Support',
            self::ROLE_MODERATOR => 'Moderator',
            self::ROLE_USER => 'User',
        ];
    }

    /**
     * Check if user was referred by another user.
     */
    public function hasSpons(): bool
    {
        return !is_null($this->sponsor_id);
    }

    /**
     * Get user's level in referral hierarchy.
     */
    public function getReferralLevelAttribute(): int
    {
        if (!$this->hasSpons()) {
            return 0; // Root level
        }

        $level = 1;
        $currentUser = $this;

        while ($currentUser->sponsor) {
            $level++;
            $currentUser = $currentUser->sponsor;

            // Prevent infinite loop
            if ($level > 20)
                break;
        }

        return $level;
    }

    /**
     * Get formatted registration date.
     */
    public function getFormattedRegistrationDateAttribute(): string
    {
        return $this->created_at->format('M d, Y');
    }

    /**
     * Check if user registered today.
     */
    public function isNewUser(): bool
    {
        return $this->created_at->isToday();
    }

    /**
     * Get user's age in days since registration.
     */
    public function getAccountAgeInDaysAttribute(): int
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate referral code if not provided
        static::creating(function ($user) {
            if (empty($user->referral_code)) {
                $user->referral_code = 'MLM' . strtoupper(\Str::random(6));
            }

            // Set default role if not provided
            if (empty($user->role)) {
                $user->role = self::ROLE_USER;
            }
        });

        // Log user creation
        static::created(function ($user) {
            \Log::info('New user created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'referral_code' => $user->referral_code,
                'sponsor_id' => $user->sponsor_id,
                'role' => $user->role
            ]);
        });
    }

    public function canManageStaff(): bool
    {
        return $this->isAdmin();
    }

    public function canPromoteUsers(): bool
    {
        return $this->hasAnyRole(['admin', 'support', 'moderator']);
    }

    public function getStaffPrivileges(): array
    {
        $privileges = [
            'admin' => [
                'manage_users' => true,
                'manage_staff' => true,
                'financial_controls' => true,
                'system_settings' => true,
                'view_all_reports' => true,
            ],
            'support' => [
                'manage_users' => true,
                'manage_staff' => false,
                'financial_controls' => true,
                'system_settings' => false,
                'view_all_reports' => true,
            ],
            'moderator' => [
                'manage_users' => true,
                'manage_staff' => false,
                'financial_controls' => false,
                'system_settings' => false,
                'view_all_reports' => false,
            ]
        ];

        return $privileges[$this->role] ?? [];
    }

    /**
     * Get all referrals where this user is the sponsor.
     */
    public function sponsoredReferrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'sponsor_id');
    }

    /**
     * Get all referrals where this user is the referred user.
     */
    public function referralRecords(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'user_id');
    }

    /**
     * Get active sponsored referrals.
     */
    public function activeSponsoredReferrals(): HasMany
    {
        return $this->hasMany(UserReferral::class, 'sponsor_id')->where('status', 'active');
    }

    /**
     * Get total commission earned from referrals.
     */
    public function getTotalReferralCommissionAttribute(): float
    {
        return $this->sponsoredReferrals()->sum('commission_earned');
    }

    /**
     * Get active referrals count (from UserReferral table).
     */
    public function getActiveReferralsFromTableAttribute(): int
    {
        return $this->activeSponsoredReferrals()->count();
    }

    /**
     * Check if user has any active referrals.
     */
    public function hasActiveReferrals(): bool
    {
        return $this->activeSponsoredReferrals()->exists();
    }

    public function commissionTier()
    {
        return $this->hasOneThrough(
            CommissionSetting::class,
            UserProfile::class,
            'user_id', // Foreign key on UserProfile
            'level',   // Foreign key on CommissionSetting
            'id',      // Local key on User
            'level'    // Local key on UserProfile
        );
    }

    /*
|--------------------------------------------------------------------------
| INVESTMENT RELATIONSHIPS
|--------------------------------------------------------------------------
*/

    /**
     * Get the user's active investments.
     */
    public function activeInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class)->where('status', 'active');
    }

    /**
     * Get the user's completed investments.
     */
    public function completedInvestments(): HasMany
    {
        return $this->hasMany(UserInvestment::class)->where('status', 'completed');
    }

    /**
     * Get the user's investment returns.
     */
    public function investmentReturns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class);
    }

    /**
     * Get the user's pending investment returns.
     */
    public function pendingInvestmentReturns(): HasMany
    {
        return $this->hasMany(InvestmentReturn::class)->where('status', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | INVESTMENT ACCESSORS & ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Get user's active investment amount.
     */
    public function getActiveInvestmentAmountAttribute(): float
    {
        return $this->activeInvestments()->sum('amount');
    }

    /**
     * Get user's total investment returns.
     */
    public function getTotalInvestmentReturnsAttribute(): float
    {
        return $this->investments()->sum('paid_return');
    }

    /**
     * Get user's pending investment returns amount.
     */
    public function getPendingInvestmentReturnsAttribute(): float
    {
        return $this->pendingInvestmentReturns()->sum('amount');
    }

    /**
     * Get user's investment ROI.
     */
    public function getInvestmentROIAttribute(): float
    {
        $totalInvested = $this->getTotalInvestmentAmountAttribute();
        if ($totalInvested <= 0) {
            return 0;
        }

        $totalReturns = $this->getTotalInvestmentReturnsAttribute();
        return ($totalReturns / $totalInvested) * 100;
    }

    /**
     * Get count of active investments.
     */
    public function getActiveInvestmentsCountAttribute(): int
    {
        return $this->activeInvestments()->count();
    }

    /**
     * Get count of completed investments.
     */
    public function getCompletedInvestmentsCountAttribute(): int
    {
        return $this->completedInvestments()->count();
    }

    /*
    |--------------------------------------------------------------------------
    | INVESTMENT STATUS CHECK METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user has any investments.
     */
    public function hasInvestments(): bool
    {
        return $this->investments()->exists();
    }

    /**
     * Check if user has active investments.
     */
    public function hasActiveInvestments(): bool
    {
        return $this->activeInvestments()->exists();
    }


    /**
     * Check if user can invest in specific plan.
     */
    public function canInvestInPlan(InvestmentPlan $plan, float $amount): bool
    {
        return $this->canInvest($amount)
            && $plan->canInvest($amount);
    }

    /**
     * Check if user can receive investment payments.
     */
    public function canReceivePayment(float $amount): bool
    {
        // This is a placeholder - implement your actual payment logic
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | INVESTMENT ACTION METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new investment for the user.
     */
    public function createInvestment(InvestmentPlan $plan, float $amount): ?UserInvestment
    {
        if (!$this->canInvestInPlan($plan, $amount)) {
            return null;
        }

        // Deduct amount from user balance
        if (!$this->deductBalance($amount, 'investment', "Investment in {$plan->name}")) {
            return null;
        }

        // Create the investment
        return $this->investments()->create([
            'investment_plan_id' => $plan->id,
            'amount' => $amount,
            'status' => 'active',
            'started_at' => now(),
        ]);
    }

    /**
     * Deduct balance for investment.
     */
    private function deductBalance(float $amount, string $type, string $description): bool
    {
        if ($this->getAvailableBalanceAttribute() < $amount) {
            return false;
        }

        // Update account balance
        if ($this->accountBalance) {
            $this->accountBalance->decrement('balance', $amount);
        }

        // Create transaction record
        $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
            'status' => 'completed',
            'description' => $description,
            'balance_after' => $this->getAvailableBalanceAttribute(),
        ]);

        return true;
    }

    /**
     * Add investment return to user balance.
     */
    public function addInvestmentReturn(
        float $amount,
        string $description = null,
        string $transactionId = null,
        string $type = 'roi'
    ): bool {
        // Generate transaction ID if not provided
        if (!$transactionId) {
            $transactionId = 'RET_' . time() . '_' . $this->id . '_' . uniqid();
        }

        try {
            // Find user's primary active crypto wallet (like in deposit system)
            $wallet = \App\Models\CryptoWallet::where('user_id', $this->id)
                ->where('is_active', 1)
                ->first();

            if (!$wallet) {
                // Fallback: get any active wallet
                $wallet = \App\Models\CryptoWallet::where('user_id', $this->id)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$wallet) {
                \Log::error('No active crypto wallet found for investment return', [
                    'user_id' => $this->id,
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'type' => $type
                ]);
                return false;
            }

            // Capture balance BEFORE increment (like deposit system)
            $oldBalance = $wallet->balance;

            // Credit the wallet balance (like deposit system)
            $wallet->increment('balance', $amount);
            $newBalance = $wallet->fresh()->balance;

            // Create transaction record with custom type
            $this->transactions()->create([
                'transaction_id' => $transactionId,
                'type' => $type, // Use the passed transaction type
                'amount' => $amount,
                'currency' => $wallet->currency, // Add currency if your transactions table has it
                'status' => 'completed',
                'description' => $description ?? $this->getDefaultDescription($type),
                'balance_after' => $newBalance,
                'metadata' => [
                    'wallet_id' => $wallet->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'currency' => $wallet->currency,
                    'source' => 'investment_return',
                    'transaction_type' => $type
                ]
            ]);

            \Log::info('Investment return processed successfully', [
                'user_id' => $this->id,
                'wallet_id' => $wallet->id,
                'currency' => $wallet->currency,
                'amount' => $amount,
                'type' => $type,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'transaction_id' => $transactionId
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Investment return processing failed', [
                'user_id' => $this->id,
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get default description based on transaction type
     */
    private function getDefaultDescription(string $type): string
    {
        return match ($type) {
            'roi' => 'Investment return payment',
            'commission' => 'Commission payment',
            'bonus' => 'Bonus payment',
            'profit' => 'Profit sharing distribution',
            default => 'Investment return payment',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | INVESTMENT QUERY SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Scope for users with investments.
     */
    public function scopeWithInvestments($query)
    {
        return $query->whereHas('investments');
    }

    /**
     * Scope for users with active investments.
     */
    public function scopeWithActiveInvestments($query)
    {
        return $query->whereHas('activeInvestments');
    }

    /**
     * Scope for users by investment amount range.
     */
    public function scopeByInvestmentRange($query, float $min, float $max)
    {
        return $query->whereHas('investments', function ($investmentQuery) use ($min, $max) {
            $investmentQuery->whereBetween('amount', [$min, $max]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | INVESTMENT STATISTICS METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Get user's investment statistics.
     */
    public function getInvestmentStatistics(): array
    {
        return [
            'total_investments' => $this->investments()->count(),
            'active_investments' => $this->activeInvestments()->count(),
            'completed_investments' => $this->completedInvestments()->count(),
            'total_invested' => $this->getTotalInvestmentAmountAttribute(),
            'active_invested' => $this->getActiveInvestmentAmountAttribute(),
            'total_returns' => $this->getTotalInvestmentReturnsAttribute(),
            'pending_returns' => $this->getPendingInvestmentReturnsAttribute(),
            'roi_percentage' => $this->getInvestmentROIAttribute(),
            'pending_returns_count' => $this->pendingInvestmentReturns()->count(),
        ];
    }

    /**
     * Get user's investment performance by plan.
     */
    public function getInvestmentPerformanceByPlan(): array
    {
        return $this->investments()
            ->with('investmentPlan')
            ->get()
            ->groupBy('investmentPlan.name')
            ->map(function ($investments) {
                return [
                    'count' => $investments->count(),
                    'total_invested' => $investments->sum('amount'),
                    'total_returns' => $investments->sum('paid_return'),
                    'roi' => $investments->sum('amount') > 0
                        ? ($investments->sum('paid_return') / $investments->sum('amount')) * 100
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get user level name.
     */
    public function getLevelNameAttribute(): string
    {
        return self::getLevelNames()[$this->user_level] ?? 'Unknown';
    }

    /**
     * Get user level badge class.
     */
    public function getLevelBadgeClassAttribute(): string
    {
        return match ($this->user_level) {
            0 => 'bg-secondary',
            1 => 'bg-primary',
            2 => 'bg-success',
            3 => 'bg-warning',
            4 => 'bg-danger',
            default => 'bg-dark'
        };
    }

    /**
     * Get user level icon.
     */
    public function getLevelIconAttribute(): string
    {
        return match ($this->user_level) {
            0 => 'iconamoon:star-duotone',
            1 => 'iconamoon:medal-duotone',
            2 => 'iconamoon:trophy-duotone',
            3 => 'iconamoon:crown-duotone',
            4 => 'iconamoon:diamond-duotone',
            default => 'iconamoon:lightning-duotone'
        };
    }

    /**
     * Get progress to next level (for display purposes only).
     */
    public function getLevelProgressAttribute(): array
    {
        $requirements = self::getLevelRequirements();
        $currentLevel = $this->user_level;
        $nextLevel = $currentLevel + 1;

        if (!isset($requirements[$nextLevel])) {
            return [
                'is_max_level' => true,
                'current_level' => $currentLevel,
                'progress_percentage' => 100,
            ];
        }

        $nextRequirement = $requirements[$nextLevel];
        $currentInvested = $this->total_invested;
        $progressPercentage = min(100, ($currentInvested / $nextRequirement['min_invested']) * 100);

        return [
            'is_max_level' => false,
            'current_level' => $currentLevel,
            'next_level' => $nextLevel,
            'current_invested' => $currentInvested,
            'required_invested' => $nextRequirement['min_invested'],
            'remaining_needed' => max(0, $nextRequirement['min_invested'] - $currentInvested),
            'progress_percentage' => $progressPercentage,
            'next_level_name' => self::getLevelNames()[$nextLevel] ?? 'Max Level',
        ];
    }

    /**
     * Check if user can invest in specific tier.
     */
    public function canInvestInTier(InvestmentPlanTier $tier, float $amount): bool
    {
        return $tier->canUserInvest($this, $amount);
    }

    /**
     * Get available investment tiers across all plans.
     */
    public function getAvailableInvestmentTiers(): \Illuminate\Database\Eloquent\Collection
    {
        return InvestmentPlanTier::whereHas('investmentPlan', function ($query) {
            $query->active();
        })
            ->where('is_active', true)
            ->where('min_user_level', '<=', $this->user_level)
            ->with('investmentPlan')
            ->orderBy('tier_level')
            ->get();
    }

    /**
     * Add investment amount (without auto level up).
     */
    public function addInvestment(float $amount): bool
    {
        return $this->increment('total_invested', $amount);
    }

    /**
     * Add earnings (without auto level up).
     */
    public function addEarnings(float $amount): bool
    {
        return $this->increment('total_earned', $amount);
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS FOR USER LEVELS
    |--------------------------------------------------------------------------
    */

    /**
     * Get level names mapping.
     */
    public static function getLevelNames(): array
    {
        return [
            0 => 'Starter',
            1 => 'Bronze',
            2 => 'Silver',
            3 => 'Gold',
            4 => 'Platinum',
            5 => 'Diamond',
            6 => 'Elite',
            7 => 'Master',
            8 => 'Legendary',
            9 => 'Ultimate',
        ];
    }

    /**
     * Get level requirements for reference (display purposes only).
     */
    public static function getLevelRequirements(): array
    {
        return [
            1 => [
                'min_invested' => 500.00,
                'min_earned' => 25.00,
                'description' => 'Invest $500 and earn $25 in returns',
            ],
            2 => [
                'min_invested' => 2000.00,
                'min_earned' => 150.00,
                'description' => 'Invest $2,000 and earn $150 in returns',
            ],
            3 => [
                'min_invested' => 10000.00,
                'min_earned' => 750.00,
                'description' => 'Invest $10,000 and earn $750 in returns',
            ],
            4 => [
                'min_invested' => 50000.00,
                'min_earned' => 5000.00,
                'description' => 'Invest $50,000 and earn $5,000 in returns',
            ],
            5 => [
                'min_invested' => 200000.00,
                'min_earned' => 25000.00,
                'description' => 'Invest $200,000 and earn $25,000 in returns',
            ],
            6 => [
                'min_invested' => 500000.00,
                'min_earned' => 75000.00,
                'description' => 'Invest $500,000 and earn $75,000 in returns',
            ],
            7 => [
                'min_invested' => 1000000.00,
                'min_earned' => 200000.00,
                'description' => 'Invest $1,000,000 and earn $200,000 in returns',
            ],
        ];
    }

    /**
     * Get level statistics for admin dashboard.
     */
    public static function getLevelStatistics(): array
    {
        $stats = self::selectRaw('user_level, COUNT(*) as count')
            ->groupBy('user_level')
            ->orderBy('user_level')
            ->get()
            ->keyBy('user_level');

        $levelNames = self::getLevelNames();
        $result = [];

        foreach ($levelNames as $level => $name) {
            $result[] = [
                'level' => $level,
                'name' => $name,
                'count' => $stats->get($level)->count ?? 0,
            ];
        }

        return $result;
    }

    public function userAnnouncementViews(): HasMany
    {
        return $this->hasMany(UserAnnouncementView::class);
    }

    // Add to your User model
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function supportTicketReplies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class);
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_to');
    }

    /**
     * Get user's crypto wallets
     */
    public function cryptoWallets()
    {
        return $this->hasMany(CryptoWallet::class);
    }

    /**
     * Get total available balance from all active crypto wallets
     */
    public function getAvailableBalanceAttribute()
    {
        return $this->cryptoWallets()
            ->active()
            ->sum('balance');
    }

    /**
     * Get available balance in USD
     */
    public function getAvailableBalanceUsdAttribute()
    {
        return $this->cryptoWallets()
            ->active()
            ->get()
            ->sum('usd_value');
    }

    /**
     * Check if user has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    /**
     * Deduct amount from user's crypto wallets (prioritize USDT)
     */
    public function deductFromWallets(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        $remaining = $amount;

        // Priority order: USDT variants first, then others
        $priority = ['USDT_TRC20', 'USDT_BEP20', 'USDT_ERC20', 'BTC', 'ETH', 'BNB'];

        // First, try priority currencies
        foreach ($priority as $currency) {
            if ($remaining <= 0)
                break;

            $wallet = $this->cryptoWallets()
                ->where('currency', $currency)
                ->where('balance', '>', 0)
                ->first();

            if ($wallet) {
                $deductAmount = min($remaining, $wallet->balance);
                $wallet->decrement('balance', $deductAmount);
                $remaining -= $deductAmount;

                \Log::info('Deducted from wallet', [
                    'user_id' => $this->id,
                    'wallet_id' => $wallet->id,
                    'currency' => $currency,
                    'amount' => $deductAmount,
                    'remaining' => $remaining
                ]);
            }
        }

        // If still remaining, deduct from any available wallet
        if ($remaining > 0) {
            $availableWallets = $this->cryptoWallets()
                ->where('balance', '>', 0)
                ->orderBy('balance', 'desc')
                ->get();

            foreach ($availableWallets as $wallet) {
                if ($remaining <= 0)
                    break;

                $deductAmount = min($remaining, $wallet->balance);
                $wallet->decrement('balance', $deductAmount);
                $remaining -= $deductAmount;

                \Log::info('Deducted from additional wallet', [
                    'user_id' => $this->id,
                    'wallet_id' => $wallet->id,
                    'currency' => $wallet->currency,
                    'amount' => $deductAmount,
                    'remaining' => $remaining
                ]);
            }
        }

        return $remaining <= 0;
    }

    /**
     * Get wallet breakdown for display
     */
    public function getWalletBreakdown(): array
    {
        return $this->cryptoWallets()
            ->active()
            ->with('cryptocurrency')
            ->where('balance', '>', 0)
            ->get()
            ->map(function ($wallet) {
                return [
                    'currency' => $wallet->currency,
                    'balance' => $wallet->balance,
                    'formatted_balance' => $wallet->formatted_balance,
                    'usd_value' => $wallet->usd_value,
                    'formatted_usd_value' => $wallet->formatted_usd_value
                ];
            })
            ->toArray();
    }

    /**
     * Get primary wallet for a currency (or create if doesn't exist)
     */
    public function getOrCreateWallet(string $currency): CryptoWallet
    {
        $wallet = $this->cryptoWallets()->where('currency', $currency)->first();

        if (!$wallet) {
            $crypto = \App\Models\Cryptocurrency::where('symbol', $currency)->first();

            $wallet = $this->cryptoWallets()->create([
                'currency' => $currency,
                'name' => $crypto ? $crypto->name : $currency,
                'balance' => 0,
                'usd_rate' => 1,
                'is_active' => true
            ]);
        }

        return $wallet;
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    /**
     * Get WebPush subscriptions (required by package)
     */
    public function routeNotificationForWebPush()
    {

        return $this->pushSubscriptions()->valid()->get();
    }

    /**
     * Check if user has 2FA enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->google2fa_enabled && !empty($this->google2fa_secret);
    }

    /**
     * Get the user's 2FA secret (decrypted).
     */
    public function getGoogle2FASecret(): ?string
    {
        if (empty($this->google2fa_secret)) {
            \Log::warning('Empty 2FA secret field', ['user_id' => $this->id]);
            return null;
        }

        try {
            $decrypted = decrypt($this->google2fa_secret);
            \Log::info('2FA Secret Retrieved', [
                'user_id' => $this->id,
                'encrypted_length' => strlen($this->google2fa_secret),
                'decrypted_length' => strlen($decrypted),
                'secret_preview' => substr($decrypted, 0, 4) . '...' // Only show first 4 chars for security
            ]);
            return $decrypted;
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt 2FA secret', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set the user's 2FA secret (encrypted).
     */
    public function setGoogle2FASecret(string $secret): void
    {
        $this->google2fa_secret = encrypt($secret);
        $this->save();
    }

    /**
     * Enable 2FA for the user.
     */
    public function enableTwoFactor(): void
    {
        $this->google2fa_enabled = true;
        $this->google2fa_enabled_at = now();
        $this->save();
    }

    /**
     * Disable 2FA for the user.
     */
    public function disableTwoFactor(): void
    {
        $this->google2fa_enabled = false;
        $this->google2fa_secret = null;
        $this->google2fa_enabled_at = null;
        $this->save();
    }

    /**
     * Verify a 2FA code.
     */
    public function verifyTwoFactorCode(string $code, bool $isSetup = false): bool
    {
        // During setup, we don't check if 2FA is enabled yet
        // During login, we do check if 2FA is enabled
        if (!$isSetup && !$this->hasTwoFactorEnabled()) {
            \Log::error('2FA not enabled for login verification attempt', ['user_id' => $this->id]);
            return false;
        }

        $secret = $this->getGoogle2FASecret();
        if (!$secret) {
            \Log::error('No 2FA secret found during verification', ['user_id' => $this->id]);
            return false;
        }

        \Log::info('2FA verification attempt', [
            'user_id' => $this->id,
            'is_setup' => $isSetup,
            'is_enabled' => $this->hasTwoFactorEnabled()
        ]);

        $google2fa = app('pragmarx.google2fa');

        // Debug information
        $currentExpectedCode = $google2fa->getCurrentOtp($secret);
        $window = 2; // Allow 2 windows (4 minutes total)

        \Log::info('2FA Verification Debug', [
            'user_id' => $this->id,
            'provided_code' => $code,
            'expected_code' => $currentExpectedCode,
            'secret_length' => strlen($secret),
            'server_time' => now()->toDateTimeString(),
            'window' => $window,
            'is_setup' => $isSetup
        ]);

        // Try verification
        $result = $google2fa->verifyKey($secret, $code, $window);

        \Log::info('2FA Verification Result', [
            'user_id' => $this->id,
            'result' => $result,
            'code_provided' => $code,
            'expected_code' => $currentExpectedCode,
            'is_setup' => $isSetup
        ]);

        return $result;
    }
    /**
     * Check if user qualifies for level upgrade to level 1
     */
    public function qualifiesForLevelOne(): bool
    {
        return $this->user_level === 0 && $this->total_invested > 0;
    }

    /**
     * Upgrade user to level 1 (from level 0 after first investment)
     */
    public function upgradeToLevelOne(): array
    {
        if (!$this->qualifiesForLevelOne()) {
            return [
                'success' => false,
                'message' => 'User does not qualify for level 1 upgrade',
                'current_level' => $this->user_level,
                'total_invested' => $this->total_invested
            ];
        }

        try {
            DB::beginTransaction();

            // Update user level
            $this->update([
                'user_level' => 1,
                'level_updated_at' => now()
            ]);

            // Update profile level if exists
            if ($this->profile && $this->profile->level === 0) {
                $this->profile->update(['level' => 1]);
            }

            // Create transaction record
            $this->transactions()->create([
                'transaction_id' => 'LEVEL_UP_' . time() . '_' . $this->id,
                'type' => 'level_upgrade',
                'amount' => 0,
                'status' => 'completed',
                'description' => 'Automatic upgrade to Level 1 after first investment',
                'metadata' => [
                    'old_level' => 0,
                    'new_level' => 1,
                    'upgrade_reason' => 'first_investment_qualification',
                    'total_invested' => $this->total_invested,
                    'upgraded_at' => now()->toDateTimeString()
                ]
            ]);

            DB::commit();

            Log::info('User automatically upgraded to level 1', [
                'user_id' => $this->id,
                'email' => $this->email,
                'total_invested' => $this->total_invested,
                'upgraded_at' => now()->toDateTimeString()
            ]);

            return [
                'success' => true,
                'message' => 'Successfully upgraded to Level 1!',
                'old_level' => 0,
                'new_level' => 1,
                'total_invested' => $this->total_invested
            ];

        } catch (Exception $e) {
            DB::rollback();

            Log::error('Failed to upgrade user to level 1', [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to upgrade level: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if user is at starter level (level 0)
     */
    public function isStarterLevel(): bool
    {
        return $this->user_level === 0;
    }

    /**
     * Check if user has made their first investment
     */
    public function hasFirstInvestment(): bool
    {
        return $this->total_invested > 0;
    }

    /**
     * Get level upgrade eligibility status
     */
    public function getLevelUpgradeEligibility(): array
    {
        return [
            'current_level' => $this->user_level,
            'is_starter_level' => $this->isStarterLevel(),
            'has_first_investment' => $this->hasFirstInvestment(),
            'qualifies_for_level_one' => $this->qualifiesForLevelOne(),
            'total_invested' => $this->total_invested,
            'profile_level' => $this->profile ? $this->profile->level : null,
            'level_updated_at' => $this->level_updated_at
        ];
    }

    /**
     * Get level upgrade history from transactions
     */
    public function getLevelUpgradeHistory()
    {
        return $this->transactions()
            ->where('type', 'level_upgrade')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                $metadata = $transaction->metadata ?? [];
                return [
                    'id' => $transaction->id,
                    'old_level' => $metadata['old_level'] ?? null,
                    'new_level' => $metadata['new_level'] ?? null,
                    'reason' => $metadata['upgrade_reason'] ?? 'unknown',
                    'upgraded_at' => $transaction->created_at,
                    'formatted_date' => $transaction->created_at->format('M d, Y g:i A'),
                    'description' => $transaction->description
                ];
            });
    }

    /**
     * Scope for users at starter level (level 0)
     */
    public function scopeStarterLevel($query)
    {
        return $query->where('user_level', 0);
    }

    /**
     * Scope for users who have invested but are still at level 0 (need upgrade)
     */
    public function scopeNeedingLevelUpgrade($query)
    {
        return $query->where('user_level', 0)
            ->where('total_invested', '>', 0);
    }

    /**
     * Check if user is truly active (has status active + investments).
     */
    public function isTrulyActive(): bool
    {
        return $this->status === 'active' && $this->investments()->exists();
    }

    /**
     * Get total invested amount.
     */
    public function getTotalInvestedAttribute(): float
    {
        return $this->investments()->sum('amount');
    }

    /**
     * Get total returns earned.
     */
    public function getTotalReturnsEarnedAttribute(): float
    {
        return $this->investments()->sum('paid_return');
    }

    /**
     * Get the user's login logs
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }
}