@extends('admin.layouts.vertical', ['title' => 'User Impersonation', 'subTitle' => 'Login as Any User'])

@section('content')
<div class="container-fluid">
    {{-- Current Impersonation Alert --}}
    @if($currentImpersonation && $currentImpersonation['is_impersonating'])
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <iconify-icon icon="iconamoon:shield-warning-duotone" class="fs-5 me-3"></iconify-icon>
                    <div class="flex-grow-1">
                        <strong>Currently Impersonating!</strong> 
                        You are logged in as <strong>{{ $currentImpersonation['current_user']['name'] }}</strong>
                        <span class="ms-2 small">({{ $currentImpersonation['duration'] }})</span>
                        <a href="{{ route('admin.impersonation.stop') }}" class="alert-link ms-3">Stop Impersonation</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">User Impersonation</h4>
                            <p class="text-muted mb-0">Login as any user for support and debugging purposes</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-info btn-sm d-flex align-items-center" onclick="quickSearch()">
                                <iconify-icon icon="iconamoon:search-duotone" class="me-1"></iconify-icon>
                                Quick Search
                            </button>
                            @if($currentImpersonation && $currentImpersonation['is_impersonating'])
                            <a href="{{ route('admin.impersonation.stop') }}" class="btn btn-warning btn-sm">
                                <iconify-icon icon="iconamoon:exit-duotone" class="me-1"></iconify-icon>
                                Stop Impersonation
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:profile-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Users</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_users']) }}</h5>
                    <small class="text-muted">Available to impersonate</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="ic:sharp-verified" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active Users</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['active_users']) }}</h5>
                    <small class="text-muted">Status active + investments</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="nrk:media-completed" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Verified</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['verified_users']) }}</h5>
                    <small class="text-muted">Email verified</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="arcticons:laokyc" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">KYC Verified</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['kyc_verified_users']) }}</h5>
                    <small class="text-muted">KYC completed</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Search Users</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </span>
                                <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}" 
                                       placeholder="Name, email, or username...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active (with investments)</option>
                                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="suspended" {{ ($status ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Role</label>
                            <select class="form-select" name="role">
                                <option value="">All Roles</option>
                                <option value="user" {{ ($role ?? '') === 'user' ? 'selected' : '' }}>User</option>
                                <option value="staff" {{ ($role ?? '') === 'staff' ? 'selected' : '' }}>Staff</option>
                                <option value="moderator" {{ ($role ?? '') === 'moderator' ? 'selected' : '' }}>Moderator</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="iconamoon:search-duotone" class="me-1"></iconify-icon>
                                    Filter
                                </button>
                                <a href="{{ route('admin.impersonation.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Users ({{ $users->total() ?? 0 }})</h5>
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center" onclick="refreshPage()">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                            Refresh
                        </button>
                    </div>
                </div>

                @if(($users->count() ?? 0) > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="users-table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">User</th>
                                            <th scope="col" class="border-0">Status & Role</th>
                                            <th scope="col" class="border-0">Investments</th>
                                            <th scope="col" class="border-0">Verification</th>
                                            <th scope="col" class="border-0">Last Activity</th>
                                            <th scope="col" class="border-0 text-center" style="width: 150px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $userItem)
                                            @php
                                                // Determine if user is truly active (has investments)
                                                $isTrulyActive = $userItem->status === 'active' && $userItem->investments()->exists();
                                                $hasInvestments = $userItem->investments()->exists();
                                                $totalInvested = $userItem->investments()->sum('amount');
                                            @endphp
                                            <tr class="user-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <iconify-icon icon="iconamoon:profile-duotone" class="text-white fs-5"></iconify-icon>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $userItem->full_name }}</h6>
                                                            <small class="text-muted">{{ $userItem->email }}</small>
                                                            <div class="small text-muted mt-1">
                                                                <iconify-icon icon="iconamoon:at-duotone" class="me-1"></iconify-icon>
                                                                {{ $userItem->username }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="mb-2">
                                                        @if($isTrulyActive)
                                                            <span class="badge bg-success" data-bs-toggle="tooltip" title="Active with investments">
                                                                Active
                                                            </span>
                                                        @elseif($userItem->status === 'active' && !$hasInvestments)
                                                            <span class="badge bg-warning" data-bs-toggle="tooltip" title="Registered but no investments">
                                                                Registered
                                                            </span>
                                                        @elseif($userItem->status === 'suspended')
                                                            <span class="badge bg-danger">
                                                                Suspended
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">
                                                                {{ ucfirst($userItem->status) }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="small text-muted">{{ ucfirst($userItem->role ?? 'User') }}</div>
                                                    <div class="small">
                                                        <span class="fw-semibold">ID:</span> #{{ $userItem->id }}
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    @if($hasInvestments)
                                                        <div class="text-center">
                                                            <div class="fw-semibold text-success">${{ number_format($totalInvested, 2) }}</div>
                                                            <small class="text-muted">
                                                                {{ $userItem->investments()->count() }} 
                                                                {{ Str::plural('investment', $userItem->investments()->count()) }}
                                                            </small>
                                                        </div>
                                                    @else
                                                        <div class="text-center">
                                                            <iconify-icon icon="iconamoon:sign-minus-duotone" class="text-muted fs-5"></iconify-icon>
                                                            <div class="small text-muted">No investments</div>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex flex-column gap-1">
                                                        <span class="badge bg-{{ $userItem->email_verified_at ? 'success' : 'warning' }} small">
                                                            <iconify-icon icon="iconamoon:email-{{ $userItem->email_verified_at ? 'check' : 'close' }}-duotone" class="me-1"></iconify-icon>
                                                            Email {{ $userItem->email_verified_at ? 'Verified' : 'Pending' }}
                                                        </span>
                                                        @if($userItem->kyc_status)
                                                        <span class="badge bg-{{ $userItem->kyc_status === 'verified' ? 'success' : ($userItem->kyc_status === 'pending' ? 'warning' : 'danger') }} small">
                                                            <iconify-icon icon="iconamoon:shield-{{ $userItem->kyc_status === 'verified' ? 'check' : 'warning' }}-duotone" class="me-1"></iconify-icon>
                                                            KYC {{ ucfirst($userItem->kyc_status) }}
                                                        </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    @if($userItem->last_login_at)
                                                    <div class="small">
                                                        <div class="fw-semibold">{{ $userItem->last_login_at->format('M d, Y') }}</div>
                                                        <div class="text-muted">{{ $userItem->last_login_at->format('g:i A') }}</div>
                                                        <div class="text-muted">{{ $userItem->last_login_at->diffForHumans() }}</div>
                                                    </div>
                                                    @else
                                                    <span class="badge bg-secondary">Never</span>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item text-warning" href="javascript:void(0)" onclick="impersonateUser({{ $userItem->id }}, '{{ $userItem->full_name }}', '{{ $userItem->email }}')">
                                                                    <iconify-icon icon="material-symbols-light:login" class="me-2"></iconify-icon>Impersonate User
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.users.show', $userItem) }}">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.users.edit', $userItem) }}">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit User
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
                                @foreach($users as $userItem)
                                    @php
                                        $isTrulyActive = $userItem->status === 'active' && $userItem->investments()->exists();
                                        $hasInvestments = $userItem->investments()->exists();
                                        $totalInvested = $userItem->investments()->sum('amount');
                                    @endphp
                                    <div class="col-12">
                                        <div class="card user-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <iconify-icon icon="iconamoon:profile-duotone" class="text-white"></iconify-icon>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $userItem->full_name }}</h6>
                                                            <small class="text-muted">{{ $userItem->email }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="impersonateUser({{ $userItem->id }}, '{{ $userItem->full_name }}', '{{ $userItem->email }}')">
                                                                <iconify-icon icon="material-symbols-light:login" class="me-2"></iconify-icon>Impersonate
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="{{ route('admin.users.show', $userItem) }}">
                                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="{{ route('admin.users.edit', $userItem) }}">
                                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Status and Badges Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @if($isTrulyActive)
                                                            <span class="badge bg-success">Active</span>
                                                        @elseif($userItem->status === 'active' && !$hasInvestments)
                                                            <span class="badge bg-warning">Registered</span>
                                                        @elseif($userItem->status === 'suspended')
                                                            <span class="badge bg-danger">Suspended</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ ucfirst($userItem->status) }}</span>
                                                        @endif
                                                        <span class="badge bg-info">
                                                            {{ ucfirst($userItem->role ?? 'User') }}
                                                        </span>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-semibold">ID #{{ $userItem->id }}</div>
                                                        <small class="text-muted">{{ $userItem->username }}</small>
                                                    </div>
                                                </div>

                                                {{-- Investment Info --}}
                                                <div class="mb-2">
                                                    @if($hasInvestments)
                                                        <div class="small">
                                                            <strong>Investments:</strong> 
                                                            <span class="text-success">${{ number_format($totalInvested, 2) }}</span>
                                                            <span class="text-muted">({{ $userItem->investments()->count() }} {{ Str::plural('plan', $userItem->investments()->count()) }})</span>
                                                        </div>
                                                    @else
                                                        <div class="small text-muted">
                                                            <strong>Investments:</strong> None
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Details Row --}}
                                                <div class="mb-2">
                                                    <div class="d-flex flex-wrap gap-1 mb-2">
                                                        <span class="badge bg-{{ $userItem->email_verified_at ? 'success' : 'warning' }} small">
                                                            Email {{ $userItem->email_verified_at ? 'Verified' : 'Pending' }}
                                                        </span>
                                                        @if($userItem->kyc_status)
                                                        <span class="badge bg-{{ $userItem->kyc_status === 'verified' ? 'success' : ($userItem->kyc_status === 'pending' ? 'warning' : 'danger') }} small">
                                                            KYC {{ ucfirst($userItem->kyc_status) }}
                                                        </span>
                                                        @endif
                                                    </div>
                                                    <div class="small">
                                                        <strong>Last Login:</strong> 
                                                        @if($userItem->last_login_at)
                                                            {{ $userItem->last_login_at->diffForHumans() }}
                                                        @else
                                                            <span class="text-muted">Never</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Pagination (Updated to match users blade exactly) --}}
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
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:profile-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Users Found</h6>
                            <p class="text-muted">No users match your current filter criteria.</p>
                            @if(request('status') || request('role') || request('search'))
                                <a href="{{ route('admin.impersonation.index') }}" class="btn btn-primary">Clear Filters</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick Search Modal --}}
<div class="modal fade" id="quickSearchModal" tabindex="-1" aria-labelledby="quickSearchModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickSearchModalLabel">Quick User Search</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="quickSearchInput" class="form-label">Search Users</label>
                    <input type="text" class="form-control" id="quickSearchInput" placeholder="Type to search users..." autocomplete="off">
                </div>
                <div id="quickSearchResults" class="list-group">
                    <div class="text-center text-muted py-3">
                        <iconify-icon icon="iconamoon:search-duotone" class="fs-3 mb-2"></iconify-icon>
                        <div>Start typing to search users...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Impersonation Confirmation Modal --}}
<div class="modal fade" id="impersonationModal" tabindex="-1" aria-labelledby="impersonationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="impersonationModalLabel">Confirm User Impersonation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
// Global variables
let isSubmitting = false;
let currentImpersonationUserId = null;
let quickSearchTimeout = null;

// Utility Functions
function showAlert(message, type = 'info', duration = 4000) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm" id="${alertId}" role="alert">
            <iconify-icon icon="iconamoon:${type === 'success' ? 'check-circle' : type === 'danger' ? 'close-circle' : 'info-circle'}-duotone" class="me-2"></iconify-icon>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.remove();
        }
    }, duration);
}

function quickSearch() {
    const modal = new bootstrap.Modal(document.getElementById('quickSearchModal'));
    modal.show();
    
    // Focus on search input after modal is shown
    document.getElementById('quickSearchModal').addEventListener('shown.bs.modal', function () {
        document.getElementById('quickSearchInput').focus();
    });
}

function impersonateUser(userId, userName, userEmail) {
    currentImpersonationUserId = userId;
    
    // Populate user details in modal
    const userDetailsDiv = document.getElementById('impersonationUserDetails');
    userDetailsDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                <iconify-icon icon="iconamoon:profile-duotone" class="text-white"></iconify-icon>
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

function confirmImpersonation() {
    if (!currentImpersonationUserId) {
        showAlert('Invalid user selected.', 'danger');
        return;
    }
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    // Disable button and show loading
    const btn = document.getElementById('confirmImpersonationBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Starting...';
    
    fetch('{{ route("admin.impersonation.start") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error starting impersonation.', 'danger');
        // Reset button
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="material-symbols-light:login" class="me-1"></iconify-icon>Start Impersonation';
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function refreshPage() {
    showAlert('Refreshing page...', 'info');
    setTimeout(() => location.reload(), 500);
}

function performQuickSearch(query) {
    if (!query || query.length < 2) {
        document.getElementById('quickSearchResults').innerHTML = `
            <div class="text-center text-muted py-3">
                <iconify-icon icon="iconamoon:search-duotone" class="fs-3 mb-2"></iconify-icon>
                <div>Start typing to search users...</div>
            </div>
        `;
        return;
    }
    
    // Show loading
    document.getElementById('quickSearchResults').innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm me-2"></div>
            Searching users...
        </div>
    `;
    
    fetch('{{ route("admin.impersonation.search-users") }}?' + new URLSearchParams({
        search: query
    }))
    .then(response => response.json())
    .then(data => {
        if (data.success && data.users.length > 0) {
            const resultsHtml = data.users.map(user => `
                <div class="list-group-item list-group-item-action" onclick="selectUserFromSearch(${user.id}, '${user.name}', '${user.email}')">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                            <iconify-icon icon="iconamoon:profile-duotone" class="text-white small"></iconify-icon>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${user.name}</div>
                            <div class="text-muted small">${user.email}</div>
                            <div class="text-muted small">@${user.username} • ${user.status} • ${user.role}</div>
                        </div>
                        <span class="badge bg-${user.status === 'active' ? 'success' : 'secondary'}">${user.status}</span>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('quickSearchResults').innerHTML = resultsHtml;
        } else {
            document.getElementById('quickSearchResults').innerHTML = `
                <div class="text-center text-muted py-3">
                    <iconify-icon icon="iconamoon:search-duotone" class="fs-3 mb-2"></iconify-icon>
                    <div>No users found matching "${query}"</div>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        document.getElementById('quickSearchResults').innerHTML = `
            <div class="text-center text-danger py-3">
                <iconify-icon icon="iconamoon:close-circle-duotone" class="fs-3 mb-2"></iconify-icon>
                <div>Search failed. Please try again.</div>
            </div>
        `;
    });
}

function selectUserFromSearch(userId, userName, userEmail) {
    // Close search modal
    bootstrap.Modal.getInstance(document.getElementById('quickSearchModal')).hide();
    
    // Start impersonation process
    setTimeout(() => {
        impersonateUser(userId, userName, userEmail);
    }, 300);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Quick search input handler
    const searchInput = document.getElementById('quickSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(quickSearchTimeout);
            quickSearchTimeout = setTimeout(() => {
                performQuickSearch(e.target.value.trim());
            }, 300);
        });
        
        // Handle enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(quickSearchTimeout);
                performQuickSearch(e.target.value.trim());
            }
        });
    }
    
    // Impersonation confirmation checkbox handler
    const confirmCheckbox = document.getElementById('impersonationConfirm');
    if (confirmCheckbox) {
        confirmCheckbox.addEventListener('change', function() {
            document.getElementById('confirmImpersonationBtn').disabled = !this.checked;
        });
    }
});
</script>

<style>
/* Base Styles */
.card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
}

/* Table Container */
.users-table-container {
    position: relative;
    overflow: visible;
}

/* Table Styles */
.table {
    margin-bottom: 0;
    position: relative;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 1rem 0.75rem;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.user-row {
    transition: background-color 0.15s ease-in-out;
}

.user-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Dropdown Styles */
.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    left: auto;
    z-index: 1050;
    display: none;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    font-size: 0.875rem;
    color: #212529;
    text-align: left;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

.dropdown-item:hover,
.dropdown-item:focus {
    color: #1e2125;
    background-color: #e9ecef;
}

.dropdown-item.text-warning:hover,
.dropdown-item.text-warning:focus {
    color: #fff;
    background-color: #ffc107;
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
    display: inline-flex;
    align-items: center;
}

.badge.small {
    font-size: 0.65em;
    padding: 0.25em 0.5em;
}

/* Button Styles */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Form Styles */
.form-control,
.form-select {
    border-radius: 0.5rem;
    border: 1px solid #ced4da;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Mobile Card Styles */
.user-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.user-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Modal Styles */
.modal-content {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.75rem 0.75rem 0 0;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    border-radius: 0 0 0.75rem 0.75rem;
}

/* Quick Search Results */
.list-group-item {
    border-radius: 0.5rem !important;
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

/* Alert Container */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
}

@media (max-width: 767.98px) {
    .user-mobile-card .card-body {
        padding: 1rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .dropdown-menu {
        min-width: 8rem;
        font-size: 0.8rem;
    }
    
    .dropdown-item {
        padding: 0.4rem 0.8rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Impersonation warning styles */
.alert-warning {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
}
</style>
@endsection