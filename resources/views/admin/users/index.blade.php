@extends('admin.layouts.vertical', ['title' => 'User Management', 'subTitle' => 'Admin'])

@section('content')

{{-- Header with Filters --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">User Management</h4>
                        <p class="text-muted mb-0">Manage and monitor all platform users</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        {{-- Search --}}
                        <form method="GET" class="d-flex" id="searchForm">
                            <input type="hidden" name="investment_status" value="{{ request('investment_status') }}">
                            <input type="hidden" name="verification" value="{{ request('verification') }}">
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ request('search') }}">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                        </form>
                        
                        {{-- Investment Status Filter --}}
                        <select class="form-select form-select-sm" onchange="filterUsers('investment_status', this.value)" style="width: auto;">
                            <option value="" {{ !request('investment_status') ? 'selected' : '' }}>All Users</option>
                            <option value="has_investments" {{ request('investment_status') === 'has_investments' ? 'selected' : '' }}>Has Investments</option>
                            <option value="no_investments" {{ request('investment_status') === 'no_investments' ? 'selected' : '' }}>No Investments</option>
                            <option value="active_investments" {{ request('investment_status') === 'active_investments' ? 'selected' : '' }}>Active Investments</option>
                            <option value="completed_investments" {{ request('investment_status') === 'completed_investments' ? 'selected' : '' }}>Completed Investments</option>
                        </select>
                        
                        {{-- Verification Filter --}}
                        <select class="form-select form-select-sm" onchange="filterUsers('verification', this.value)" style="width: auto;">
                            <option value="" {{ !request('verification') ? 'selected' : '' }}>All Verification</option>
                            <option value="email_verified" {{ request('verification') === 'email_verified' ? 'selected' : '' }}>Email Verified</option>
                            <option value="email_unverified" {{ request('verification') === 'email_unverified' ? 'selected' : '' }}>Email Unverified</option>
                            <option value="kyc_verified" {{ request('verification') === 'kyc_verified' ? 'selected' : '' }}>KYC Verified</option>
                            <option value="kyc_pending" {{ request('verification') === 'kyc_pending' ? 'selected' : '' }}>KYC Pending</option>
                        </select>
                        
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportUsers()">
                            <iconify-icon icon="iconamoon:file-export-duotone"></iconify-icon> Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards - 4 Cards Grid --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:profile-duotone" class="text-primary" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">{{ number_format($summaryData['total_users']) }}</h5>
                <h6 class="text-muted mb-0">Total Users</h6>
                <small class="text-muted">{{ number_format($summaryData['new_today']) }} joined today</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-success" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">{{ number_format($summaryData['active_users']) }}</h5>
                <h6 class="text-muted mb-0">Active Users</h6>
                <small class="text-muted">Status active + investments</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="material-symbols:verified" class="text-info" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">{{ number_format($summaryData['kyc_verified']) }}</h5>
                <h6 class="text-muted mb-0">KYC Verified</h6>
                <small class="text-muted">Documents verified</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="material-symbols:mark-email-read-rounded" class="text-warning" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">{{ number_format($summaryData['verified_users']) }}</h5>
                <h6 class="text-muted mb-0">Email Verified</h6>
                <small class="text-muted">Confirmed emails</small>
            </div>
        </div>
    </div>
</div>

{{-- Users Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Users ({{ $users->total() }})</h5>
                @if(request()->hasAny(['investment_status', 'verification', 'search']))
                <div class="flex-shrink-0">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="material-symbols:refresh-rounded"></iconify-icon> Clear Filters
                    </a>
                </div>
                @endif
            </div>

            @if($users->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive-wrapper">
                        <table class="table table-borderless table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">User</th>
                                    <th>Investments</th>
                                    <th>Wallet Balance</th>
                                    <th>Sponsor Chain</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th class="text-center pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user_item)
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                                                <span class="avatar-title text-white fw-semibold">
                                                    {{ strtoupper(substr($user_item->first_name, 0, 1) . substr($user_item->last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <h6 class="mb-0">{{ $user_item->first_name }} {{ $user_item->last_name }}</h6>
                                                    
                                                    {{-- Status Icons --}}
                                                    @if($user_item->profile && $user_item->profile->kyc_status === 'verified')
                                                        <iconify-icon icon="material-symbols:verified" 
                                                                    class="text-success" 
                                                                    style="font-size: 1.1rem;"
                                                                    data-bs-toggle="tooltip" 
                                                                    title="KYC Verified"></iconify-icon>
                                                    @endif
                                                    
                                                    @if($user_item->investments && $user_item->investments->isNotEmpty())
                                                        <iconify-icon icon="iconamoon:coin-duotone" 
                                                                    class="text-success" 
                                                                    style="font-size: 1.1rem;"
                                                                    data-bs-toggle="tooltip" 
                                                                    title="Has Investments"></iconify-icon>
                                                    @endif
                                                    
                                                    @if($user_item->hasVerifiedEmail())
                                                        <iconify-icon icon="material-symbols:mark-email-read-rounded" 
                                                                    class="text-info" 
                                                                    style="font-size: 1rem;"
                                                                    data-bs-toggle="tooltip" 
                                                                    title="Email Verified"></iconify-icon>
                                                    @endif
                                                </div>
                                                <small class="text-muted">{{ $user_item->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($user_item->investments && $user_item->investments->isNotEmpty())
                                            @php
                                                $activeInvestments = $user_item->investments->where('status', 'active')->count();
                                                $completedInvestments = $user_item->investments->where('status', 'completed')->count();
                                                $totalInvested = $user_item->investments->sum('amount');
                                                $totalReturns = $user_item->investments->sum('paid_return');
                                            @endphp
                                            <div class="small text-center py-1">
                                                <div class="mb-1">
                                                    @if($activeInvestments > 0)
                                                        <span class="badge bg-primary">{{ $activeInvestments }} Active</span>
                                                    @endif
                                                    @if($completedInvestments > 0)
                                                        <span class="badge bg-success ms-1">{{ $completedInvestments }} Done</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted">
                                                    <strong>${{ number_format($totalInvested, 2) }}</strong> invested
                                                    @if($totalReturns > 0)
                                                        <br><small class="text-success">${{ number_format($totalReturns, 2) }} earned</small>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-center py-1">
                                                <iconify-icon icon="iconamoon:sign-minus-duotone" class="text-muted fs-5"></iconify-icon>
                                                <div class="small text-muted mt-1">No investments</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user_item->cryptoWallets && $user_item->cryptoWallets->isNotEmpty())
                                            <div class="text-center py-1">
                                                <strong class="text-success">${{ number_format($user_item->total_wallet_balance_usd ?? 0, 2) }}</strong>
                                            </div>
                                        @else
                                            <div class="text-center py-1">
                                                <iconify-icon icon="material-symbols:account-balance-wallet" class="text-muted fs-5"></iconify-icon>
                                                <div class="small text-muted mt-1">No wallets</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!empty($user_item->sponsor_chain))
                                            @php
                                                $chain = collect($user_item->sponsor_chain);
                                                $firstLevel = $chain->first();
                                                $lastLevel = $chain->last();
                                                $hasMultipleLevels = $chain->count() > 1;
                                            @endphp
                                            
                                            <div class="sponsor-chain-container" 
                                                 data-bs-toggle="tooltip" 
                                                 data-bs-placement="top" 
                                                 data-bs-custom-class="sponsor-tooltip"
                                                 title="@foreach($chain as $sponsor)L{{ $sponsor['level'] }}: {{ $sponsor['user']->first_name }} {{ $sponsor['user']->last_name }}{{ !$loop->last ? ' → ' : '' }}@endforeach">
                                                <div class="small text-muted">
                                                    {{ Str::limit($firstLevel['user']->first_name . ' ' . $firstLevel['user']->last_name, 15) }}
                                                    @if($hasMultipleLevels)
                                                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="mx-1"></iconify-icon>
                                                        <span class="text-primary">...</span>
                                                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="mx-1"></iconify-icon>
                                                        {{ Str::limit($lastLevel['user']->first_name . ' ' . $lastLevel['user']->last_name, 15) }}
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    <small class="badge bg-info">{{ $chain->count() }} level{{ $chain->count() > 1 ? 's' : '' }}</small>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">Direct signup</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div>{{ $user_item->created_at->format('M d, Y') }}</div>
                                            <div class="text-muted">{{ $user_item->created_at->diffForHumans() }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @if($user_item->last_login_at)
                                                <div>{{ $user_item->last_login_at->format('M d, Y') }}</div>
                                                <div class="text-muted">{{ $user_item->last_login_at->diffForHumans() }}</div>
                                            @else
                                                <span class="text-muted">Never logged in</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center pe-4">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                    data-bs-toggle="dropdown" 
                                                    data-bs-auto-close="true"
                                                    data-bs-boundary="viewport">
                                                <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item text-warning" href="javascript:void(0)" onclick="impersonateUser('{{ $user_item->id }}', '{{ $user_item->first_name }} {{ $user_item->last_name }}', '{{ $user_item->email }}')">
                                                        <iconify-icon icon="material-symbols-light:login" class="me-2"></iconify-icon>Impersonate User
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="showUserDetails('{{ $user_item->id }}')">
                                                        <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                    </a>
                                                </li>
                                                 <li>
                                                    <a class="dropdown-item" href="{{ route('admin.users.referral-investments', $user_item->id) }}" >
                                                        <iconify-icon icon="material-symbols:graph-6" class="me-2"></iconify-icon>Referral Analysis
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('admin.users.edit', $user_item->id) }}">
                                                        <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit User
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="toggleUserStatus('{{ $user_item->id }}')">
                                                        <iconify-icon icon="fluent:status-32-regular" class="me-2"></iconify-icon>
                                                        {{ $user_item->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                    </a>
                                                </li>
                                                @if(!$user_item->hasVerifiedEmail())
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="verifyUserEmail('{{ $user_item->id }}')">
                                                        <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>Verify Email
                                                    </a>
                                                </li>
                                                @endif
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="showBalanceModal('{{ $user_item->id }}', '{{ $user_item->first_name }} {{ $user_item->last_name }}', '{{ $user_item->total_wallet_balance_usd ?? 0 }}', '{{ $user_item->primary_wallet ? $user_item->primary_wallet->currency : '' }}', '{{ $user_item->primary_wallet ? $user_item->primary_wallet->balance : 0 }}')">
                                                        <iconify-icon icon="material-symbols:account-balance-wallet" class="me-2"></iconify-icon>Adjust Balance
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="showKycModal('{{ $user_item->id }}', '{{ $user_item->profile->kyc_status ?? 'not_submitted' }}')">
                                                        <iconify-icon icon="arcticons:laokyc" class="me-2"></iconify-icon>Update KYC
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none p-3">
                    <div class="row g-3">
                        @foreach($users as $user_item)
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body p-3">
                                    {{-- User Header --}}
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                                <span class="avatar-title text-white fw-semibold">
                                                    {{ strtoupper(substr($user_item->first_name, 0, 1) . substr($user_item->last_name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <h6 class="mb-0">{{ $user_item->first_name }} {{ $user_item->last_name }}</h6>
                                                    
                                                    {{-- Status Icons --}}
                                                    @if($user_item->profile && $user_item->profile->kyc_status === 'verified')
                                                        <iconify-icon icon="material-symbols:verified" class="text-success" style="font-size: 0.9rem;"></iconify-icon>
                                                    @endif
                                                    @if($user_item->investments && $user_item->investments->isNotEmpty())
                                                        <iconify-icon icon="iconamoon:coin-duotone" class="text-success" style="font-size: 0.9rem;"></iconify-icon>
                                                    @endif
                                                    @if($user_item->hasVerifiedEmail())
                                                        <iconify-icon icon="material-symbols:mark-email-read-rounded" class="text-info" style="font-size: 0.8rem;"></iconify-icon>
                                                    @endif
                                                </div>
                                                <small class="text-muted">{{ $user_item->username }}</small>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-light" onclick="toggleMobileDetails('{{ $user_item->id }}')">
                                            <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="mobile-chevron-{{ $user_item->id }}"></iconify-icon>
                                        </button>
                                    </div>

                                    {{-- Quick Info Row --}}
                                    <div class="row g-2 text-center">
                                        <div class="col-4">
                                            <div class="small">
                                                <div class="text-muted">Wallet</div>
                                                @if($user_item->cryptoWallets && $user_item->cryptoWallets->isNotEmpty())
                                                    <div class="fw-semibold text-success">${{ number_format($user_item->total_wallet_balance_usd ?? 0, 2) }}</div>
                                                @else
                                                    <div class="text-muted">No wallets</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="small">
                                                <div class="text-muted">Investments</div>
                                                @if($user_item->investments && $user_item->investments->isNotEmpty())
                                                    <div class="fw-semibold">${{ number_format($user_item->investments->sum('amount'), 2) }}</div>
                                                @else
                                                    <div class="text-muted">None</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="small">
                                                <div class="text-muted">Sponsor</div>
                                                @if(!empty($user_item->sponsor_chain))
                                                    <div class="fw-semibold">{{ count($user_item->sponsor_chain) }} levels</div>
                                                @else
                                                    <div class="text-muted">Direct</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Expandable Details --}}
                                    <div class="collapse mt-3" id="mobile-details-{{ $user_item->id }}">
                                        <div class="border-top pt-3">
                                            <div class="row g-2 small">
                                                <div class="col-6">
                                                    <div class="text-muted">Email</div>
                                                    <div>{{ $user_item->email }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Joined</div>
                                                    <div>{{ $user_item->created_at->format('M d, Y') }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Last Login</div>
                                                    <div>{{ $user_item->last_login_at ? $user_item->last_login_at->diffForHumans() : 'Never' }}</div>
                                                </div>
                                                @if(!empty($user_item->sponsor_chain))
                                                <div class="col-12">
                                                    <div class="text-muted">Sponsor Chain</div>
                                                    <div class="small">
                                                        @foreach($user_item->sponsor_chain as $sponsor)
                                                            L{{ $sponsor['level'] }}: {{ $sponsor['user']->first_name }} {{ $sponsor['user']->last_name }}{{ !$loop->last ? ' → ' : '' }}
                                                        @endforeach
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                            
                                            {{-- Mobile Actions --}}
                                            <div class="d-flex gap-2 mt-3 pt-2 border-top">
                                                <button class="btn btn-sm btn-outline-primary flex-fill" onclick="showUserDetails('{{ $user_item->id }}')">
                                                    <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon> View
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="showBalanceModal('{{ $user_item->id }}', '{{ $user_item->first_name }} {{ $user_item->last_name }}', '{{ $user_item->total_wallet_balance_usd ?? 0 }}', '{{ $user_item->primary_wallet ? $user_item->primary_wallet->currency : '' }}', '{{ $user_item->primary_wallet ? $user_item->primary_wallet->balance : 0 }}')">
                                                    <iconify-icon icon="material-symbols:account-balance-wallet"></iconify-icon> Balance
                                                </button>
                                                <a href="{{ route('admin.users.edit', $user_item->id) }}" class="btn btn-sm btn-outline-info flex-fill">
                                                    <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon> Edit
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Pagination Footer --}}
            @if($users->hasPages())
            <div class="card-footer border-top">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                    </div>
                    <div>
                        {{ $users->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:profile-duotone" class="text-muted" style="font-size: 4rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-2">No Users Found</h6>
                <p class="text-muted mb-3">No users match your current search criteria.</p>
                @if(request()->hasAny(['investment_status', 'verification', 'search']))
                <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                    <iconify-icon icon="iconamoon:refresh-duotone" class="me-1"></iconify-icon>Clear Filters
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- User Details Modal --}}
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userModalContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Balance Adjustment Modal --}}
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Crypto Wallet Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="balanceForm">
                <div class="modal-body">
                    <input type="hidden" id="balance_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <div id="balance_user_name" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Total Portfolio Value</label>
                        <div id="current_total_balance" class="form-control-plaintext fw-bold text-success fs-5"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Primary Wallet</label>
                        <div id="primary_wallet_info" class="form-control-plaintext text-info"></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <select class="form-select" id="balance_currency">
                                    <option value="">Auto-select wallet</option>
                                    <option value="USDT_TRC20">USDT (TRC20)</option>
                                    <option value="USDT_BEP20">USDT (BEP20)</option>
                                    <option value="USDT_ERC20">USDT (ERC20)</option>
                                    <option value="BTC">Bitcoin (BTC)</option>
                                    <option value="ETH">Ethereum (ETH)</option>
                                    <option value="BNB">Binance Coin (BNB)</option>
                                </select>
                                <small class="text-muted">Leave empty to auto-select best wallet</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.00000001" class="form-control" id="balance_amount" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="balance_type" required>
                            <option value="add">Add (+)</option>
                            <option value="subtract">Subtract (-)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" id="balance_reason" placeholder="Reason for adjustment" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <iconify-icon icon="material-symbols:account-balance-wallet" class="me-1"></iconify-icon>Adjust Balance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- KYC Status Modal --}}
<div class="modal fade" id="kycModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update KYC Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="kycForm">
                <div class="modal-body">
                    <input type="hidden" id="kyc_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">KYC Status</label>
                        <select class="form-select" id="kyc_status" required>
                            <option value="not_submitted">Not Submitted</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="rejection_reason_group" style="display: none;">
                        <label class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="kyc_rejection_reason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <iconify-icon icon="iconamoon:certificate-duotone" class="me-1"></iconify-icon>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Impersonation Confirmation Modal --}}
<div class="modal fade" id="impersonationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm User Impersonation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <iconify-icon icon="iconamoon:shield-warning-duotone" class="me-2"></iconify-icon>
                    <strong>Security Warning:</strong> You are about to login as another user. This action will be logged for security purposes.
                </div>
                <div class="mb-3">
                    <h6>User Details:</h6>
                    <div id="impersonationUserDetails" class="border rounded p-3 bg-light">
                        <!-- User details will be populated here -->
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="impersonationConfirm">
                    <label class="form-check-label" for="impersonationConfirm">
                        I understand this action will be logged and I have authorization to access this user's account.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmImpersonationBtn" disabled onclick="confirmImpersonation()">
                    <iconify-icon icon="material-symbols-light:login" class="me-1"></iconify-icon>
                    Start Impersonation
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Global variables for impersonation
let currentImpersonationUserId = null;
let isSubmittingImpersonation = false;

// Impersonate user function
function impersonateUser(userId, userName, userEmail) {
    currentImpersonationUserId = userId;
    
    // Populate user details in modal
    const userDetailsDiv = document.getElementById('impersonationUserDetails');
    userDetailsDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                <span class="avatar-title text-white fw-semibold">
                    ${userName.split(' ').map(n => n[0]).join('')}
                </span>
            </div>
            <div>
                <div class="fw-semibold">${userName}</div>
                <div class="text-muted small">${userEmail}</div>
                <div class="text-muted small">User ID: #${userId}</div>
            </div>
        </div>
    `;
    
    // Reset confirmation checkbox
    document.getElementById('impersonationConfirm').checked = false;
    document.getElementById('confirmImpersonationBtn').disabled = true;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('impersonationModal'));
    modal.show();
}

// Confirm impersonation
function confirmImpersonation() {
    if (!currentImpersonationUserId) {
        showAlert('Invalid user selected.', 'danger');
        return;
    }
    
    if (isSubmittingImpersonation) return;
    isSubmittingImpersonation = true;
    
    // Disable button and show loading
    const btn = document.getElementById('confirmImpersonationBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Starting...';
    
    fetch('{{ route("admin.impersonation.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            user_id: currentImpersonationUserId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Redirect to user dashboard
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 1500);
        } else {
            showAlert(data.message || 'Failed to start impersonation', 'danger');
            // Reset button
            btn.disabled = false;
            btn.innerHTML = '<iconify-icon icon="material-symbols-light:login" class="me-1"></iconify-icon>Start Impersonation';
            isSubmittingImpersonation = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error starting impersonation.', 'danger');
        // Reset button
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="material-symbols-light:login" class="me-1"></iconify-icon>Start Impersonation';
        isSubmittingImpersonation = false;
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Impersonation confirmation checkbox handler
    const confirmCheckbox = document.getElementById('impersonationConfirm');
    if (confirmCheckbox) {
        confirmCheckbox.addEventListener('change', function() {
            document.getElementById('confirmImpersonationBtn').disabled = !this.checked;
        });
    }
    
    // Fix dropdown overflow issue
    const dropdowns = document.querySelectorAll('.table .dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('show.bs.dropdown', function() {
            // Remove table overflow when dropdown opens
            const wrapper = this.closest('.table-responsive-wrapper');
            if (wrapper) {
                wrapper.style.overflow = 'visible';
            }
        });
        
        dropdown.addEventListener('hide.bs.dropdown', function() {
            // Restore table overflow when dropdown closes
            const wrapper = this.closest('.table-responsive-wrapper');
            if (wrapper) {
                wrapper.style.overflow = 'auto';
            }
        });
    });
});

// Filter users
function filterUsers(type, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(type, value);
    } else {
        url.searchParams.delete(type);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Toggle mobile details
function toggleMobileDetails(userId) {
    const detailsElement = document.getElementById(`mobile-details-${userId}`);
    const chevronElement = document.getElementById(`mobile-chevron-${userId}`);
    
    if (detailsElement.classList.contains('show')) {
        detailsElement.classList.remove('show');
        chevronElement.style.transform = 'rotate(0deg)';
    } else {
        // Close other open details
        document.querySelectorAll('.collapse.show').forEach(element => {
            if (element.id.startsWith('mobile-details-')) {
                element.classList.remove('show');
            }
        });
        document.querySelectorAll('[id^="mobile-chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
        
        detailsElement.classList.add('show');
        chevronElement.style.transform = 'rotate(180deg)';
    }
}

// Show user details modal
function showUserDetails(userId) {
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
    
    fetch(`{{ url('admin/users') }}/${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userModalContent').innerHTML = data.html;
            } else {
                document.getElementById('userModalContent').innerHTML = '<div class="alert alert-danger">Failed to load user details</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('userModalContent').innerHTML = '<div class="alert alert-danger">Failed to load user details</div>';
        });
}

// Toggle user status
function toggleUserStatus(userId) {
    if (confirm('Are you sure you want to change this user\'s status?')) {
        fetch(`{{ url('admin/users') }}/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to update user status', 'danger');
        });
    }
}

// Verify user email
function verifyUserEmail(userId) {
    if (confirm('Manually verify this user\'s email address?')) {
        fetch(`{{ url('admin/users') }}/${userId}/verify-email`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'warning');
            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to verify email', 'danger');
        });
    }
}

// Show balance adjustment modal
function showBalanceModal(userId, userName, totalBalanceUsd, primaryCurrency, primaryBalance) {
    document.getElementById('balance_user_id').value = userId;
    document.getElementById('balance_user_name').textContent = userName;
    document.getElementById('current_total_balance').textContent = '$' + parseFloat(totalBalanceUsd || 0).toFixed(2);
    
    if (primaryCurrency && primaryBalance) {
        document.getElementById('primary_wallet_info').textContent = 
            parseFloat(primaryBalance).toFixed(8) + ' ' + primaryCurrency;
    } else {
        document.getElementById('primary_wallet_info').textContent = 'No active wallets';
    }
    
    // Reset form
    document.getElementById('balance_amount').value = '';
    document.getElementById('balance_reason').value = '';
    document.getElementById('balance_currency').value = '';
    document.getElementById('balance_type').value = 'add';
    
    new bootstrap.Modal(document.getElementById('balanceModal')).show();
}

// Show KYC modal
function showKycModal(userId, currentStatus) {
    document.getElementById('kyc_user_id').value = userId;
    document.getElementById('kyc_status').value = currentStatus;
    toggleRejectionReason(currentStatus);
    new bootstrap.Modal(document.getElementById('kycModal')).show();
}

// Toggle rejection reason field
function toggleRejectionReason(status) {
    const rejectionGroup = document.getElementById('rejection_reason_group');
    if (status === 'rejected') {
        rejectionGroup.style.display = 'block';
        document.getElementById('kyc_rejection_reason').required = true;
    } else {
        rejectionGroup.style.display = 'none';
        document.getElementById('kyc_rejection_reason').required = false;
    }
}

// Handle balance form submission
document.getElementById('balanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('balance_user_id').value;
    const formData = {
        amount: document.getElementById('balance_amount').value,
        type: document.getElementById('balance_type').value,
        reason: document.getElementById('balance_reason').value,
        currency: document.getElementById('balance_currency').value || null
    };
    
    fetch(`{{ url('admin/users') }}/${userId}/adjust-balance`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('balanceModal')).hide();
        if (data.success) {
            const message = data.data 
                ? `Balance adjusted successfully! ${data.data.formatted_new_balance || ''}`
                : data.message;
            showAlert(message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to adjust balance', 'danger');
    });
});

// Handle KYC form submission
document.getElementById('kycForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('kyc_user_id').value;
    const formData = {
        status: document.getElementById('kyc_status').value,
        rejection_reason: document.getElementById('kyc_rejection_reason').value
    };
    
    fetch(`{{ url('admin/users') }}/${userId}/update-kyc-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('kycModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update KYC status', 'danger');
    });
});

// Export users
function exportUsers() {
    const url = new URL('{{ route("admin.users.export") }}');
    const searchParams = new URLSearchParams(window.location.search);
    searchParams.forEach((value, key) => {
        if (key !== 'page') {
            url.searchParams.set(key, value);
        }
    });
    window.open(url.toString(), '_blank');
}

// KYC status change handler
document.getElementById('kyc_status').addEventListener('change', function() {
    toggleRejectionReason(this.value);
});

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <iconify-icon icon="iconamoon:${type === 'success' ? 'check-circle-1' : type === 'danger' ? 'close-circle-1' : 'information-circle'}-duotone" class="me-2 fs-5"></iconify-icon>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 5000);
}
</script>

<style>
/* Custom avatar styling */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    vertical-align: top;
}

.avatar-sm {
    width: 2.25rem;
    height: 2.25rem;
    font-size: 0.875rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: inherit;
}

/* Sponsor chain tooltip styling */
.sponsor-chain-container {
    cursor: help;
    padding: 0.5rem;
    border-radius: 6px;
    transition: background-color 0.2s;
}

.sponsor-chain-container:hover {
    background-color: rgba(13, 110, 253, 0.1);
}

.sponsor-tooltip .tooltip-inner {
    max-width: 320px;
    text-align: left;
    padding: 8px 12px;
    background-color: #212529;
    border-radius: 6px;
    font-size: 0.875rem;
}

/* Table styling */
.table th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 1rem 0.75rem;
    white-space: nowrap;
}

.table td {
    padding: 0.875rem 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.04);
}

/* FIX: Table responsive wrapper for dropdown overflow */
.table-responsive-wrapper {
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
}

/* Ensure dropdown menu stays within viewport */
.dropdown-menu {
    position: absolute;
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border-radius: 0.75rem;
    padding: 0.5rem;
    max-height: 400px;
    overflow-y: auto;
    will-change: transform;
    z-index: 1050;
}

/* Prevent dropdown from causing horizontal scroll */
.table td:last-child {
    position: relative;
}

.table td:last-child .dropdown {
    position: static;
}

.table td:last-child .dropdown-menu {
    right: 0;
    left: auto;
    margin-top: 0.25rem;
}

/* Card styling */
.card {
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Badge styling */
.badge {
    font-weight: 500;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
}

/* Button styling */
.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
}

/* Dropdown styling */
.dropdown-item {
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

/* Mobile responsive adjustments */
@media (max-width: 991.98px) {
    .table-responsive-wrapper {
        border-radius: 0;
    }
    
    .card-body {
        padding: 1rem;
    }
}

@media (max-width: 767.98px) {
    .avatar-sm {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Animation for expand/collapse */
.collapse {
    transition: height 0.3s ease;
}

/* Icon alignment */
iconify-icon {
    vertical-align: middle;
}

/* Summary cards hover effect */
.col-6.col-lg-3 .card:hover {
    transform: translateY(-2px);
}

/* Loading spinner in modal */
.spinner-border {
    width: 2rem;
    height: 2rem;
}

/* Impersonation Modal Styling */
#impersonationModal .modal-content {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}

#impersonationModal .alert-warning {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
    border-radius: 0.5rem;
}

#impersonationModal .form-check-input:checked {
    background-color: #ffc107;
    border-color: #ffc107;
}
</style>
@endsection