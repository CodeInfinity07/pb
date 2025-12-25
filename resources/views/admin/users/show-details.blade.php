{{-- User Details Modal Content --}}
<div class="row g-4">
    {{-- User Profile Section --}}
    <div class="col-12 col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Profile Information</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg rounded-circle bg-primary me-3">
                        <span class="avatar-title text-white fs-5">{{ $user->initials }}</span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $user->full_name }}</h5>
                        <p class="text-muted mb-0">{{ $user->email }}</p>
                        <div class="d-flex gap-2 mt-2">
                            <span
                                class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'support' ? 'warning' : ($user->role === 'moderator' ? 'info' : 'secondary')) }}-subtle text-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'support' ? 'warning' : ($user->role === 'moderator' ? 'info' : 'secondary')) }}">
                                {{ ucfirst($user->role) }}
                            </span>
                            <span
                                class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}-subtle text-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row g-2 small">
                    <div class="col-6">
                        <div class="text-muted">Username</div>
                        <div class="fw-semibold">{{ $user->username }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Phone</div>
                        <div class="fw-semibold">{{ $user->phone ?: 'Not provided' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Email Status</div>
                        <div>
                            @if($user->hasVerifiedEmail())
                                <span class="badge bg-success-subtle text-success">Verified ✓</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Unverified</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">KYC Status</div>
                        <div>
                            @if($user->profile)
                                <span
                                    class="badge bg-{{ $user->profile->kyc_status === 'verified' ? 'success' : ($user->profile->kyc_status === 'rejected' ? 'danger' : 'warning') }}-subtle text-{{ $user->profile->kyc_status === 'verified' ? 'success' : ($user->profile->kyc_status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $user->profile->kyc_status)) }}
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Not Submitted</span>
                            @endif
                        </div>
                    </div>
                    @if($user->profile)
                        <div class="col-6">
                            <div class="text-muted">Country</div>
                            <div class="fw-semibold">{{ $user->profile->country_name ?? 'Not set' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">City</div>
                            <div class="fw-semibold">{{ $user->profile->city ?: 'Not set' }}</div>
                        </div>
                    @endif
                    <div class="col-6">
                        <div class="text-muted">Registered</div>
                        <div class="fw-semibold">{{ $user->created_at->format('M d, Y') }}</div>
                        <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Last Login</div>
                        <div class="fw-semibold">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('M d, Y') }}
                                <small class="text-muted d-block">{{ $user->last_login_at->diffForHumans() }}</small>
                            @else
                                Never
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

   {{-- Financial Information --}}
<div class="col-12 col-md-6">
    <div class="card h-100">
        <div class="card-header">
            <h6 class="mb-0">Financial Information</h6>
        </div>
        <div class="card-body">
            {{-- Balance Info --}}
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                        <iconify-icon icon="material-symbols:account-balance-wallet" class="text-success fs-4 mb-2"></iconify-icon>
                        <div class="text-muted small">Total Wallet Balance</div>
                        @if($user->cryptoWallets && $user->cryptoWallets->isNotEmpty())
                            <small class="text-muted">{{ $user->cryptoWallets->where('is_active', true)->count() }} active wallet(s)</small>
                        @else
                            <div class="d-flex align-items-center justify-content-center py-2">
                                <iconify-icon icon="material-symbols:account-balance-wallet" class="text-muted fs-4 me-2"></iconify-icon>
                                <span class="text-muted">No wallets</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Crypto Wallets Breakdown --}}
            @if($user->cryptoWallets && $user->cryptoWallets->isNotEmpty())
            <div class="mb-4">
                <div class="text-muted small mb-2">Active Wallets</div>
                <div class="row g-2">
                    @foreach($user->cryptoWallets->where('is_active', true)->take(3) as $wallet)
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                            <div>
                                <small class="fw-semibold">{{ $wallet->currency }}</small>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ number_format($wallet->balance, 8) }} {{ $wallet->currency }}</div>
                            </div>
                            <div class="text-end">
                                <small class="fw-semibold text-success">${{ number_format($wallet->balance * ($wallet->usd_rate ?? 0), 2) }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @if($user->cryptoWallets->where('is_active', true)->count() > 3)
                    <div class="col-12">
                        <small class="text-muted">+ {{ $user->cryptoWallets->where('is_active', true)->count() - 3 }} more wallet(s)</small>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Stats --}}
            <div class="row g-2 small">
                <div class="col-6">
                    <div class="text-muted">Total Deposits</div>
                    <div class="fw-semibold text-success">${{ number_format($userStats['total_deposits'], 2) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">Total Withdrawals</div>
                    <div class="fw-semibold text-danger">${{ number_format($userStats['total_withdrawals'], 2) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">Pending Withdrawals</div>
                    <div class="fw-semibold text-warning">${{ number_format($userStats['pending_withdrawals'], 2) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">Total Commissions</div>
                    <div class="fw-semibold text-info">${{ number_format($userStats['total_commissions'], 2) }}</div>
                </div>
                @if($user->earnings)
                <div class="col-6">
                    <div class="text-muted">Total Earnings</div>
                    <div class="fw-semibold">${{ $user->earnings->formatted_total }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">Today Earnings</div>
                    <div class="fw-semibold">${{ $user->earnings->formatted_today }}</div>
                </div>
                @endif
                <div class="col-6">
                    <div class="text-muted">Total Transactions</div>
                    <div class="fw-semibold">{{ number_format($userStats['transaction_count']) }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">Account Age</div>
                    <div class="fw-semibold">{{ $userStats['account_age_days'] }} days</div>
                </div>
            </div>
        </div>
    </div>
</div>

    {{-- Referral Information --}}
    @if($user->sponsor || $userStats['referral_count'] > 0)
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Referral Information</h6>
                </div>
                <div class="card-body">
                    @if($user->sponsor)
                        <div class="mb-3">
                            <div class="text-muted small">Sponsored by</div>
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                    <span class="avatar-title text-white">{{ $user->sponsor->initials }}</span>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->sponsor->full_name }}</div>
                                    <small class="text-muted">{{ $user->sponsor->email }}</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row g-2 small">
                        <div class="col-6">
                            <div class="text-muted">Direct Referrals</div>
                            <div class="fw-semibold">{{ number_format($userStats['referral_count']) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Active Referrals</div>
                            <div class="fw-semibold">{{ number_format($userStats['active_referrals']) }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted">Referral Code</div>
                            <div class="d-flex align-items-center">
                                <code class="flex-grow-1">{{ $user->referral_code }}</code>
                                <button class="btn btn-sm btn-outline-secondary ms-2"
                                    onclick="copyText('{{ $user->referral_code }}')">
                                    <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Recent Activity --}}
    <div class="col-12 {{ $user->sponsor || $userStats['referral_count'] > 0 ? 'col-md-6' : '' }}">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Recent Transactions</h6>
            </div>
            <div class="card-body">
                @if($user->transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr class="text-muted small">
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($user->transactions->take(5) as $transaction)
                                    <tr>
                                        <td>
                                            <span
                                                class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }} p-1">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td
                                            class="fw-semibold {{ $transaction->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                            {{ $transaction->type === 'withdrawal' ? '-' : '+' }}${{ number_format($transaction->amount, 2) }}
                                        </td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }} p-1">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td class="text-muted">{{ $transaction->created_at->format('M d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <iconify-icon icon="iconamoon:history-duotone" class="fs-4 mb-2"></iconify-icon>
                        <p class="mb-0">No transactions yet</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Direct Referrals List --}}
    @if($user->directReferrals->count() > 0)
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Direct Referrals ({{ $user->directReferrals->count() }})</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <thead>
                                <tr class="text-muted small">
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Balance</th>
                                    <th>Investments</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @foreach($user->directReferrals->take(10) as $referral)
                                    @php
                                        $hasInvestments = $referral->investments()->exists();
                                        $isTrulyActive = $referral->status === 'active' && $hasInvestments;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs rounded-circle bg-light me-2">
                                                    <span class="avatar-title">{{ $referral->initials }}</span>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $referral->full_name }}</div>
                                                    <div class="text-muted">{{ $referral->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($referral->status === 'blocked')
                                                <span class="badge bg-danger-subtle text-danger p-1">
                                                    Blocked
                                                </span>
                                            @elseif($isTrulyActive)
                                                <span class="badge bg-success-subtle text-success p-1">
                                                    Active
                                                </span>
                                            @elseif($referral->status === 'active' && !$hasInvestments)
                                                <span class="badge bg-warning-subtle text-warning p-1">
                                                    Registered
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary p-1">
                                                    Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($referral->cryptoWallets && $referral->cryptoWallets->isNotEmpty())
                                                <strong
                                                    class="text-success">${{ number_format($referral->total_wallet_balance_usd ?? 0, 2) }}</strong>
                                            @else
                                                <div class="d-flex align-items-center">
                                                    <iconify-icon icon="material-symbols:account-balance-wallet"
                                                        class="text-muted"></iconify-icon>
                                                    <small class="text-muted ms-1">No wallets</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($hasInvestments)
                                                <span class="badge bg-info-subtle text-info p-1">
                                                    {{ $referral->investments()->count() }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-muted">{{ $referral->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($user->directReferrals->count() > 10)
                        <div class="text-center mt-2">
                            <small class="text-muted">Showing 10 of {{ $user->directReferrals->count() }} referrals</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    .avatar-xs {
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.65rem;
    }

    .avatar-lg {
        width: 3rem;
        height: 3rem;
        font-size: 1rem;
    }

    code {
        background-color: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
        word-break: break-all;
    }
</style>

<script>
    function copyText(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                // You can add a toast notification here
                console.log('Copied to clipboard');
            });
        } else {
            // Fallback
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
    }
</script>