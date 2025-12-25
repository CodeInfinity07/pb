@extends('admin.layouts.vertical', ['title' => 'Blocked Users', 'subTitle' => 'Admin'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Blocked Users Management</h4>
                        <p class="text-muted mb-0">Manage blocked and suspended user accounts</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#blockUserModal">
                            Block User
                        </button>
                        <div class="d-flex gap-2">
                            <form method="GET" class="d-flex" id="searchForm">
                                <input type="hidden" name="reason" value="{{ request('reason') }}">
                                <div class="input-group input-group-sm" style="width: 200px;">
                                    <input type="text" name="search" class="form-control" placeholder="Search users..." value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                    </button>
                                </div>
                            </form>
                            <select class="form-select form-select-sm" onchange="filterByReason(this.value)" style="width: auto;">
                                <option value="" {{ !request('reason') ? 'selected' : '' }}>All Reasons</option>
                                <option value="spam" {{ request('reason') === 'spam' ? 'selected' : '' }}>Spam</option>
                                <option value="fraud" {{ request('reason') === 'fraud' ? 'selected' : '' }}>Fraud</option>
                                <option value="violation" {{ request('reason') === 'violation' ? 'selected' : '' }}>Policy Violation</option>
                                <option value="abuse" {{ request('reason') === 'abuse' ? 'selected' : '' }}>Abuse</option>
                                <option value="security" {{ request('reason') === 'security' ? 'selected' : '' }}>Security</option>
                                <option value="other" {{ request('reason') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:close-circle-1-duotone" class="text-danger" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Total Blocked</h6>
                <h5 class="mb-0">{{ $blockedStats['total'] }}</h5>
                <small class="text-muted">Users blocked</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:calendar-today-sharp" class="text-warning" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">This Month</h6>
                <h5 class="mb-0">{{ $blockedStats['this_month'] }}</h5>
                <small class="text-muted">Users blocked</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="gg:unblock" class="text-success" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Unblocked</h6>
                <h5 class="mb-0">{{ $blockedStats['unblocked_today'] }}</h5>
                <small class="text-muted">Today</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="ic:baseline-preview" class="text-info" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Under Review</h6>
                <h5 class="mb-0">{{ $blockedStats['under_review'] }}</h5>
                <small class="text-muted">Pending review</small>
            </div>
        </div>
    </div>
</div>

{{-- Blocked Users Table/Cards --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="d-flex card-header justify-content-between align-items-center">
                <h5 class="card-title mb-0">Blocked Users ({{ $blockedUsers->total() }})</h5>
                @if(request()->hasAny(['search', 'reason']))
                <div class="flex-shrink-0">
                    <a href="{{ route('admin.blocked-users.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="material-symbols:refresh-rounded"></iconify-icon> Clear Filters
                    </a>
                </div>
                @endif
            </div>

            @if($blockedUsers->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">Block Reason</th>
                                    <th scope="col">Blocked Date</th>
                                    <th scope="col">Blocked By</th>
                                    <th scope="col">Balance</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($blockedUsers as $blockedUser)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-danger me-2">
                                                <span class="avatar-title text-white">{{ $blockedUser->initials }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $blockedUser->full_name }}</h6>
                                                <small class="text-muted">{{ $blockedUser->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($blockedUser->block_reason)
                                            <span class="badge bg-danger-subtle text-danger p-1">
                                                {{ ucfirst($blockedUser->block_reason) }}
                                            </span>
                                            @if($blockedUser->block_notes)
                                            <br><small class="text-muted">{{ Str::limit($blockedUser->block_notes, 50) }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">No reason specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($blockedUser->blocked_at)
                                            {{ $blockedUser->blocked_at->format('M d, Y') }}
                                            <small class="text-muted d-block">{{ $blockedUser->blocked_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($blockedUser->blocked_by_user)
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs rounded-circle bg-secondary me-1">
                                                    <span class="avatar-title text-white">{{ $blockedUser->blocked_by_user->initials }}</span>
                                                </div>
                                                <small>{{ $blockedUser->blocked_by_user->full_name }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-warning">${{ number_format($blockedUser->accountBalance->balance ?? 0, 2) }}</strong>
                                        @if($blockedUser->accountBalance && $blockedUser->accountBalance->locked_balance > 0)
                                            <small class="text-muted d-block">${{ number_format($blockedUser->accountBalance->locked_balance, 2) }} locked</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showUserDetails('{{ $blockedUser->id }}')">
                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                </a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.users.edit', $blockedUser->id) }}">
                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit User
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="showUnblockModal('{{ $blockedUser->id }}', '{{ $blockedUser->full_name }}')">
                                                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-2"></iconify-icon>Unblock User
                                                </a></li>
                                                <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="showBlockDetailsModal('{{ $blockedUser->id }}')">
                                                    <iconify-icon icon="iconamoon:information-duotone" class="me-2"></iconify-icon>Block Details
                                                </a></li>
                                                @if($blockedUser->accountBalance && $blockedUser->accountBalance->balance > 0)
                                                <li><a class="dropdown-item text-info" href="javascript:void(0)" onclick="showBalanceModal('{{ $blockedUser->id }}', '{{ $blockedUser->full_name }}', '{{ $blockedUser->accountBalance->balance }}')">
                                                    <iconify-icon icon="material-symbols:account-balance-wallet" class="me-2"></iconify-icon>Manage Balance
                                                </a></li>
                                                @endif
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
                        @foreach($blockedUsers as $blockedUser)
                        <div class="col-12">
                            <div class="card blocked-user-card border border-danger border-opacity-25">
                                <div class="card-body p-3">
                                    {{-- Header Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm rounded-circle bg-danger">
                                                <span class="avatar-title text-white">{{ $blockedUser->initials }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $blockedUser->full_name }}</h6>
                                                <small class="text-muted">{{ $blockedUser->username }}</small>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showUserDetails('{{ $blockedUser->id }}')">
                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                </a></li>
                                                <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="showUnblockModal('{{ $blockedUser->id }}', '{{ $blockedUser->full_name }}')">
                                                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-2"></iconify-icon>Unblock
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Status and Reason Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-danger-subtle text-danger">Blocked</span>
                                            @if($blockedUser->block_reason)
                                                <span class="badge bg-warning-subtle text-warning">{{ ucfirst($blockedUser->block_reason) }}</span>
                                            @endif
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails('{{ $blockedUser->id }}')">
                                            <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $blockedUser->id }}"></iconify-icon>
                                        </button>
                                    </div>

                                    {{-- Email and Balance Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <small class="text-muted">{{ $blockedUser->email }}</small>
                                            <div class="fw-semibold text-warning">${{ number_format($blockedUser->accountBalance->balance ?? 0, 2) }}</div>
                                        </div>
                                        <iconify-icon icon="iconamoon:close-circle-1-duotone" class="text-danger fs-20"></iconify-icon>
                                    </div>

                                    {{-- Expandable Details --}}
                                    <div class="collapse mt-3" id="details-{{ $blockedUser->id }}">
                                        <div class="border-top pt-3">
                                            <div class="row g-2 small">
                                                <div class="col-6">
                                                    <div class="text-muted">Blocked Date</div>
                                                    <div>{{ $blockedUser->blocked_at ? $blockedUser->blocked_at->format('M d, Y') : 'Unknown' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Blocked By</div>
                                                    <div>{{ $blockedUser->blocked_by_user ? $blockedUser->blocked_by_user->full_name : 'System' }}</div>
                                                </div>
                                                @if($blockedUser->block_notes)
                                                <div class="col-12">
                                                    <div class="text-muted">Notes</div>
                                                    <div>{{ $blockedUser->block_notes }}</div>
                                                </div>
                                                @endif
                                                <div class="col-6">
                                                    <div class="text-muted">Total Transactions</div>
                                                    <div>{{ $blockedUser->transactions->count() }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Referrals</div>
                                                    <div>{{ $blockedUser->directReferrals->count() }}</div>
                                                </div>
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

            {{-- Pagination --}}
            @if($blockedUsers->hasPages())
            <div class="card-footer border-top border-light">
                <div class="align-items-center justify-content-between row text-center text-sm-start">
                    <div class="col-sm">
                        <div class="text-muted">
                            Showing
                            <span class="fw-semibold text-body">{{ $blockedUsers->firstItem() }}</span>
                            to
                            <span class="fw-semibold text-body">{{ $blockedUsers->lastItem() }}</span>
                            of
                            <span class="fw-semibold">{{ $blockedUsers->total() }}</span>
                            Blocked Users
                        </div>
                    </div>
                    <div class="col-sm-auto mt-3 mt-sm-0">
                        {{ $blockedUsers->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="fs-1 text-success mb-3"></iconify-icon>
                    <h6 class="text-muted">No Blocked Users Found</h6>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'reason']))
                            No blocked users match your current filters.
                        @else
                            No users are currently blocked.
                        @endif
                    </p>
                    @if(request()->hasAny(['search', 'reason']))
                    <a href="{{ route('admin.blocked-users.index') }}" class="btn btn-primary">Clear Filters</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Block User Modal --}}
<div class="modal fade" id="blockUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Block User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="blockUserForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Search and Select User <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="userSearch" placeholder="Search by name, email, or username...">
                                <button type="button" class="btn btn-outline-secondary" onclick="searchActiveUsers()">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                            <div class="form-text">Search for active users to block them</div>
                        </div>

                        <div class="col-12" id="userSearchResults" style="display: none;">
                            <label class="form-label fw-semibold">Select User</label>
                            <div class="border rounded p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                                <div id="searchResultsList">
                                    <!-- Search results will be populated here -->
                                </div>
                            </div>
                        </div>

                        <div class="col-12" id="selectedUserSection" style="display: none;">
                            <label class="form-label fw-semibold">Selected User</label>
                            <div class="border rounded p-3 bg-danger bg-opacity-10">
                                <div id="selectedUserInfo">
                                    <!-- Selected user info will be displayed here -->
                                </div>
                                <input type="hidden" id="selectedUserId" name="user_id">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="blockReason" class="form-label fw-semibold">Block Reason <span class="text-danger">*</span></label>
                            <select class="form-select" id="blockReason" name="reason" required>
                                <option value="">Select Reason</option>
                                <option value="spam">Spam Activity</option>
                                <option value="fraud">Fraudulent Behavior</option>
                                <option value="violation">Policy Violation</option>
                                <option value="abuse">Harassment/Abuse</option>
                                <option value="security">Security Concerns</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="blockDuration" class="form-label fw-semibold">Block Duration</label>
                            <select class="form-select" id="blockDuration" name="duration">
                                <option value="permanent">Permanent</option>
                                <option value="30">30 Days</option>
                                <option value="7">7 Days</option>
                                <option value="3">3 Days</option>
                                <option value="1">1 Day</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="blockNotes" class="form-label fw-semibold">Additional Notes</label>
                            <textarea class="form-control" id="blockNotes" name="notes" rows="3" placeholder="Detailed reason for blocking this user..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="notifyUserBlock" name="notify_user" value="1" checked>
                                <label class="form-check-label" for="notifyUserBlock">
                                    Send notification email to user about account suspension
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <iconify-icon icon="iconamoon:user-minus-duotone" class="me-2"></iconify-icon>
                        Block User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Unblock User Modal --}}
<div class="modal fade" id="unblockUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Unblock User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="unblockUserForm">
                <div class="modal-body">
                    <input type="hidden" id="unblockUserId">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <div id="unblockUserName" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label for="unblockReason" class="form-label">Reason for Unblocking <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="unblockReason" rows="3" placeholder="Why are you unblocking this user?" required></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="notifyUserUnblock" value="1" checked>
                            <label class="form-check-label" for="notifyUserUnblock">
                                Send welcome back notification to user
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-2"></iconify-icon>
                        Unblock User
                    </button>
                </div>
            </form>
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
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

{{-- Balance Management Modal --}}
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="balanceForm">
                <div class="modal-body">
                    <input type="hidden" id="balance_user_id">
                    <div class="alert alert-warning">
                        <iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>
                        This user is blocked. Balance changes will be logged for audit purposes.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <div id="balance_user_name" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Balance</label>
                        <div id="current_balance" class="form-control-plaintext fw-bold text-success"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" id="balance_amount" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select class="form-select" id="balance_type" required>
                                    <option value="add">Add (+)</option>
                                    <option value="subtract">Subtract (-)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" id="balance_reason" placeholder="Reason for adjustment" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Adjust Balance</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let selectedUserId = null;
let searchTimeout = null;

// Filter by block reason
function filterByReason(reason) {
    const url = new URL(window.location.href);
    if (reason) {
        url.searchParams.set('reason', reason);
    } else {
        url.searchParams.delete('reason');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Toggle mobile details
function toggleDetails(userId) {
    const detailsElement = document.getElementById(`details-${userId}`);
    const chevronElement = document.getElementById(`chevron-${userId}`);
    
    if (detailsElement.classList.contains('show')) {
        detailsElement.classList.remove('show');
        chevronElement.style.transform = 'rotate(0deg)';
    } else {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
        
        detailsElement.classList.add('show');
        chevronElement.style.transform = 'rotate(180deg)';
    }
}

// Search active users for blocking
function searchActiveUsers() {
    const searchTerm = document.getElementById('userSearch').value;
    
    if (searchTerm.length < 2) {
        showAlert('Please enter at least 2 characters to search', 'warning');
        return;
    }
    
    fetch(`{{ route('admin.blocked-users.search-active-users') }}?search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySearchResults(data.users);
            } else {
                showAlert(data.message || 'Failed to search users', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to search users', 'danger');
        });
}

// Display search results
function displaySearchResults(users) {
    const resultsList = document.getElementById('searchResultsList');
    const resultsSection = document.getElementById('userSearchResults');
    
    if (users.length === 0) {
        resultsList.innerHTML = '<div class="text-center text-muted py-3">No active users found</div>';
    } else {
        resultsList.innerHTML = users.map(user => `
            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2 user-search-result" onclick="selectUserForBlocking(${user.id}, '${user.full_name}', '${user.email}', '${user.username}')">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                        <span class="avatar-title text-white">${user.initials}</span>
                    </div>
                    <div>
                        <div class="fw-semibold">${user.full_name}</div>
                        <small class="text-muted">${user.email}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger">Select</button>
            </div>
        `).join('');
    }
    
    resultsSection.style.display = 'block';
}

// Select user for blocking
function selectUserForBlocking(id, name, email, username) {
    selectedUserId = id;
    document.getElementById('selectedUserId').value = id;
    
    document.getElementById('selectedUserInfo').innerHTML = `
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm rounded-circle bg-danger me-2">
                <span class="avatar-title text-white">${name.split(' ').map(n => n[0]).join('')}</span>
            </div>
            <div>
                <div class="fw-semibold">${name}</div>
                <small class="text-muted">${email} • @${username}</small>
            </div>
        </div>
    `;
    
    document.getElementById('selectedUserSection').style.display = 'block';
    document.getElementById('userSearchResults').style.display = 'none';
    document.getElementById('userSearch').value = '';
}

// Auto search on typing
document.getElementById('userSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchTerm = this.value;
    
    if (searchTerm.length >= 2) {
        searchTimeout = setTimeout(() => {
            searchActiveUsers();
        }, 500);
    } else if (searchTerm.length === 0) {
        document.getElementById('userSearchResults').style.display = 'none';
    }
});

// Block user form submission
document.getElementById('blockUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedUserId) {
        showAlert('Please select a user to block', 'warning');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('user_id', selectedUserId);
    
    fetch('{{ route("admin.blocked-users.block-user") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('blockUserModal')).hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to block user', 'danger');
    });
});

// Show unblock modal
function showUnblockModal(userId, userName) {
    document.getElementById('unblockUserId').value = userId;
    document.getElementById('unblockUserName').textContent = userName;
    document.getElementById('unblockReason').value = '';
    new bootstrap.Modal(document.getElementById('unblockUserModal')).show();
}

// Unblock user form submission
document.getElementById('unblockUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('unblockUserId').value;
    const reason = document.getElementById('unblockReason').value;
    const notify = document.getElementById('notifyUserUnblock').checked;
    
    fetch(`{{ url('admin/blocked-users') }}/${userId}/unblock`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            reason: reason,
            notify_user: notify
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('unblockUserModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to unblock user', 'danger');
    });
});

// Show user details
function showUserDetails(userId) {
    fetch(`{{ url('admin/users') }}/${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('userModalContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
            } else {
                showAlert('Failed to load user details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load user details', 'danger');
        });
}

// Show balance modal
function showBalanceModal(userId, userName, currentBalance) {
    document.getElementById('balance_user_id').value = userId;
    document.getElementById('balance_user_name').textContent = userName;
    document.getElementById('current_balance').textContent = '$' + parseFloat(currentBalance).toFixed(2);
    document.getElementById('balance_amount').value = '';
    document.getElementById('balance_reason').value = '';
    new bootstrap.Modal(document.getElementById('balanceModal')).show();
}

// Balance form submission
document.getElementById('balanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('balance_user_id').value;
    const formData = {
        amount: document.getElementById('balance_amount').value,
        type: document.getElementById('balance_type').value,
        reason: document.getElementById('balance_reason').value
    };
    
    fetch(`{{ url('admin/users') }}/${userId}/adjust-balance`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('balanceModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to adjust balance', 'danger');
    });
});

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 4000);
}

// Reset block modal when closed
document.getElementById('blockUserModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('blockUserForm').reset();
    document.getElementById('userSearchResults').style.display = 'none';
    document.getElementById('selectedUserSection').style.display = 'none';
    selectedUserId = null;
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.blocked-user-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});
</script>

<style>
.blocked-user-card {
    transition: all 0.2s ease;
    border-radius: 8px;
}

.blocked-user-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.user-search-result {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.user-search-result:hover {
    background-color: #f8f9fa;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.avatar-xs {
    width: 1.5rem;
    height: 1.5rem;
    font-size: 0.65rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.fs-20 {
    font-size: 1.25rem;
}

.collapse {
    transition: height 0.3s ease;
}

.table-card .table thead th {
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 0.75rem;
}

.table-card .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
}

.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Mobile responsive fixes */
@media (max-width: 768px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem;
    }
}

@media (max-width: 576px) {
    .blocked-user-card .card-body {
        padding: 0.75rem;
    }
    
    .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .input-group {
        width: 100% !important;
    }
}

.btn, .badge, .card {
    transition: all 0.2s ease;
}
</style>
@endsection