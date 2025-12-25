@extends('admin.layouts.vertical', ['title' => 'Staff Management', 'subTitle' => 'Admin'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Staff Management</h4>
                        <p class="text-muted mb-0">Manage staff roles and permissions</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#promoteUserModal">
                            Promote User
                        </button>
                        <select class="form-select form-select-sm" onchange="filterStaff(this.value)" style="width: auto;">
                            <option value="" {{ !request('role') ? 'selected' : '' }}>All Roles</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admins</option>
                            <option value="support" {{ request('role') === 'support' ? 'selected' : '' }}>Support</option>
                            <option value="moderator" {{ request('role') === 'moderator' ? 'selected' : '' }}>Moderators</option>
                        </select>
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
                    <iconify-icon icon="clarity:administrator-line" class="text-danger" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Admins</h6>
                <h5 class="mb-0">{{ $staffStats['admins'] }}</h5>
                <small class="text-muted">Full access</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="mdi:account-question-outline" class="text-warning" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Support</h6>
                <h5 class="mb-0">{{ $staffStats['support'] }}</h5>
                <small class="text-muted">User support</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:shield-duotone" class="text-info" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Moderators</h6>
                <h5 class="mb-0">{{ $staffStats['moderators'] }}</h5>
                <small class="text-muted">Moderation</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="guidance:care-staff-area" class="text-success" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Total Staff</h6>
                <h5 class="mb-0">{{ $staffStats['total'] }}</h5>
                <small class="text-muted">Active staff</small>
            </div>
        </div>
    </div>
</div>

{{-- Staff Members Table/Cards --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="d-flex card-header justify-content-between align-items-center">
                <h5 class="card-title mb-0">Staff Members ({{ $staffMembers->total() }})</h5>
                @if(request('role'))
                <div class="flex-shrink-0">
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="material-symbols:refresh-rounded"></iconify-icon> Clear Filter
                    </a>
                </div>
                @endif
            </div>

            @if($staffMembers->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">Staff Member</th>
                                    <th scope="col">Role</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Last Login</th>
                                    <th scope="col">Promoted</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffMembers as $staff)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-{{ $staff->role === 'admin' ? 'danger' : ($staff->role === 'support' ? 'warning' : 'info') }} me-2">
                                                <span class="avatar-title text-white">{{ $staff->initials }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $staff->full_name }}</h6>
                                                <small class="text-muted">{{ $staff->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $staff->role === 'admin' ? 'danger' : ($staff->role === 'support' ? 'warning' : 'info') }}-subtle text-{{ $staff->role === 'admin' ? 'danger' : ($staff->role === 'support' ? 'warning' : 'info') }} p-1">
                                            <iconify-icon icon="iconamoon:{{ $staff->role === 'admin' ? 'crown' : ($staff->role === 'support' ? 'headphones' : 'shield') }}-duotone" class="me-1"></iconify-icon>
                                            {{ ucfirst($staff->role) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $staff->status === 'active' ? 'success' : ($staff->status === 'inactive' ? 'warning' : 'danger') }}-subtle text-{{ $staff->status === 'active' ? 'success' : ($staff->status === 'inactive' ? 'warning' : 'danger') }} p-1">
                                            {{ ucfirst($staff->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($staff->last_login_at)
                                            {{ $staff->last_login_at->format('M d, Y') }}
                                            <small class="text-muted d-block">{{ $staff->last_login_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $staff->created_at->format('M d, Y') }}
                                        <small class="text-muted d-block">{{ $staff->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.users.show', $staff->id) }}">
                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                </a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.users.edit', $staff->id) }}">
                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit User
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                @if(auth()->user()->isAdmin() && $staff->role !== 'admin')
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showRoleChangeModal('{{ $staff->id }}', '{{ $staff->full_name }}', '{{ $staff->role }}')">
                                                    <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-2"></iconify-icon>Change Role
                                                </a></li>
                                                @endif
                                                @if(auth()->user()->isAdmin() && $staff->id !== auth()->user()->id)
                                                <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="demoteStaff('{{ $staff->id }}', '{{ $staff->full_name }}')">
                                                    <iconify-icon icon="iconamoon:arrow-down-2-duotone" class="me-2"></iconify-icon>Demote to User
                                                </a></li>
                                                @endif
                                                @if($staff->status === 'active')
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="toggleStaffStatus('{{ $staff->id }}')">
                                                    <iconify-icon icon="iconamoon:pause-duotone" class="me-2"></iconify-icon>Deactivate
                                                </a></li>
                                                @else
                                                <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="toggleStaffStatus('{{ $staff->id }}')">
                                                    <iconify-icon icon="iconamoon:play-duotone" class="me-2"></iconify-icon>Activate
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
                        @foreach($staffMembers as $staff)
                        <div class="col-12">
                            <div class="card staff-card border">
                                <div class="card-body p-3">
                                    {{-- Header Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm rounded-circle bg-{{ $staff->role === 'admin' ? 'danger' : ($staff->role === 'support' ? 'warning' : 'info') }}">
                                                <span class="avatar-title text-white">{{ $staff->initials }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $staff->full_name }}</h6>
                                                <small class="text-muted">{{ $staff->username }}</small>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.users.show', $staff->id) }}">
                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                </a></li>
                                                <li><a class="dropdown-item" href="{{ route('admin.users.edit', $staff->id) }}">
                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit User
                                                </a></li>
                                                @if(auth()->user()->isAdmin() && $staff->role !== 'admin')
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showRoleChangeModal('{{ $staff->id }}', '{{ $staff->full_name }}', '{{ $staff->role }}')">
                                                    <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-2"></iconify-icon>Change Role
                                                </a></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Status and Role Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-{{ $staff->role === 'admin' ? 'danger' : ($staff->role === 'support' ? 'warning' : 'info') }}-subtle text-{{ $staff->role === 'admin' ? 'danger' : ($staff->role === 'support' ? 'warning' : 'info') }}">
                                                <iconify-icon icon="iconamoon:{{ $staff->role === 'admin' ? 'crown' : ($staff->role === 'support' ? 'headphones' : 'shield') }}-duotone" class="me-1"></iconify-icon>
                                                {{ ucfirst($staff->role) }}
                                            </span>
                                            <span class="badge bg-{{ $staff->status === 'active' ? 'success' : ($staff->status === 'inactive' ? 'warning' : 'danger') }}-subtle text-{{ $staff->status === 'active' ? 'success' : ($staff->status === 'inactive' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($staff->status) }}
                                            </span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails('{{ $staff->id }}')">
                                            <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $staff->id }}"></iconify-icon>
                                        </button>
                                    </div>

                                    {{-- Email and Login Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <small class="text-muted">{{ $staff->email }}</small>
                                            <div class="small text-muted">Last login: {{ $staff->last_login_at ? $staff->last_login_at->diffForHumans() : 'Never' }}</div>
                                        </div>
                                        <iconify-icon icon="iconamoon:profile-duotone" class="text-muted fs-20"></iconify-icon>
                                    </div>

                                    {{-- Expandable Details --}}
                                    <div class="collapse mt-3" id="details-{{ $staff->id }}">
                                        <div class="border-top pt-3">
                                            <div class="row g-2 small">
                                                <div class="col-6">
                                                    <div class="text-muted">Phone</div>
                                                    <div>{{ $staff->phone ?: 'Not provided' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Promoted</div>
                                                    <div>{{ $staff->created_at->format('M d, Y') }}</div>
                                                </div>
                                                @if($staff->profile)
                                                <div class="col-6">
                                                    <div class="text-muted">Country</div>
                                                    <div>{{ $staff->profile->country_name ?? 'Not set' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">City</div>
                                                    <div>{{ $staff->profile->city ?: 'Not set' }}</div>
                                                </div>
                                                @endif
                                                <div class="col-6">
                                                    <div class="text-muted">Account Balance</div>
                                                    <div>${{ number_format($staff->accountBalance->balance ?? 0, 2) }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Total Referrals</div>
                                                    <div>{{ $staff->directReferrals->count() }}</div>
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
            @if($staffMembers->hasPages())
            <div class="card-footer border-top border-light">
                <div class="align-items-center justify-content-between row text-center text-sm-start">
                    <div class="col-sm">
                        <div class="text-muted">
                            Showing
                            <span class="fw-semibold text-body">{{ $staffMembers->firstItem() }}</span>
                            to
                            <span class="fw-semibold text-body">{{ $staffMembers->lastItem() }}</span>
                            of
                            <span class="fw-semibold">{{ $staffMembers->total() }}</span>
                            Staff Members
                        </div>
                    </div>
                    <div class="col-sm-auto mt-3 mt-sm-0">
                        {{ $staffMembers->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:users-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Staff Members Found</h6>
                    <p class="text-muted">No staff members match your current filter.</p>
                    @if(request('role'))
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-primary">Clear Filter</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Promote User Modal --}}
<div class="modal fade" id="promoteUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Promote User to Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="promoteUserForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Search and Select User <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="userSearch" placeholder="Search by name, email, or username...">
                                <button type="button" class="btn btn-outline-secondary" onclick="searchUsers()">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                            <div class="form-text">Search for users with "user" role to promote them to staff</div>
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
                            <div class="border rounded p-3 bg-success bg-opacity-10">
                                <div id="selectedUserInfo">
                                    <!-- Selected user info will be displayed here -->
                                </div>
                                <input type="hidden" id="selectedUserId" name="user_id">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="newRole" class="form-label fw-semibold">Assign Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="newRole" name="role" required>
                                <option value="">Select Role</option>
                                <option value="moderator">Moderator</option>
                                <option value="support">Support</option>
                                @if(auth()->user()->isAdmin())
                                <option value="admin">Administrator</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="promoteReason" class="form-label fw-semibold">Reason for Promotion</label>
                            <input type="text" class="form-control" id="promoteReason" name="reason" placeholder="Optional reason...">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="notifyUser" name="notify_user" value="1" checked>
                                <label class="form-check-label" for="notifyUser">
                                    Send notification email to user about their promotion
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-2"></iconify-icon>
                        Promote User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Change Role Modal --}}
<div class="modal fade" id="changeRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Staff Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changeRoleForm">
                <div class="modal-body">
                    <input type="hidden" id="changeRoleUserId">
                    <div class="mb-3">
                        <label class="form-label">Staff Member</label>
                        <div id="changeRoleUserName" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Role</label>
                        <div id="currentRoleDisplay" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label for="changeToRole" class="form-label">Change to Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="changeToRole" required>
                            <option value="">Select New Role</option>
                            <option value="moderator">Moderator</option>
                            <option value="support">Support</option>
                            @if(auth()->user()->isAdmin())
                            <option value="admin">Administrator</option>
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="roleChangeReason" class="form-label">Reason for Change</label>
                        <input type="text" class="form-control" id="roleChangeReason" placeholder="Optional reason...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Role</button>
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

// Filter staff by role
function filterStaff(role) {
    const url = new URL(window.location.href);
    if (role) {
        url.searchParams.set('role', role);
    } else {
        url.searchParams.delete('role');
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

// Search users for promotion
function searchUsers() {
    const searchTerm = document.getElementById('userSearch').value;
    
    if (searchTerm.length < 2) {
        showAlert('Please enter at least 2 characters to search', 'warning');
        return;
    }
    
    fetch(`{{ route('admin.staff.search-users') }}?search=${encodeURIComponent(searchTerm)}`)
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
        resultsList.innerHTML = '<div class="text-center text-muted py-3">No users found</div>';
    } else {
        resultsList.innerHTML = users.map(user => `
            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2 user-search-result" onclick="selectUser(${user.id}, '${user.full_name}', '${user.email}', '${user.username}')">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                        <span class="avatar-title text-white">${user.initials}</span>
                    </div>
                    <div>
                        <div class="fw-semibold">${user.full_name}</div>
                        <small class="text-muted">${user.email}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary">Select</button>
            </div>
        `).join('');
    }
    
    resultsSection.style.display = 'block';
}

// Select user for promotion
function selectUser(id, name, email, username) {
    selectedUserId = id;
    document.getElementById('selectedUserId').value = id;
    
    document.getElementById('selectedUserInfo').innerHTML = `
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm rounded-circle bg-success me-2">
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
            searchUsers();
        }, 500);
    } else if (searchTerm.length === 0) {
        document.getElementById('userSearchResults').style.display = 'none';
    }
});

// Promote user form submission
document.getElementById('promoteUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedUserId) {
        showAlert('Please select a user to promote', 'warning');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('user_id', selectedUserId);
    
    fetch('{{ route("admin.staff.promote-user") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('promoteUserModal')).hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to promote user', 'danger');
    });
});

// Show role change modal
function showRoleChangeModal(userId, userName, currentRole) {
    document.getElementById('changeRoleUserId').value = userId;
    document.getElementById('changeRoleUserName').textContent = userName;
    document.getElementById('currentRoleDisplay').innerHTML = `<span class="badge bg-${currentRole === 'admin' ? 'danger' : (currentRole === 'support' ? 'warning' : 'info')}-subtle text-${currentRole === 'admin' ? 'danger' : (currentRole === 'support' ? 'warning' : 'info')}">${currentRole.charAt(0).toUpperCase() + currentRole.slice(1)}</span>`;
    
    // Remove current role from options
    const select = document.getElementById('changeToRole');
    Array.from(select.options).forEach(option => {
        option.style.display = option.value === currentRole ? 'none' : 'block';
    });
    
    new bootstrap.Modal(document.getElementById('changeRoleModal')).show();
}

// Change role form submission
document.getElementById('changeRoleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('changeRoleUserId').value;
    const newRole = document.getElementById('changeToRole').value;
    const reason = document.getElementById('roleChangeReason').value;
    
    fetch(`{{ url('admin/staff') }}/${userId}/change-role`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            role: newRole,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('changeRoleModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to change role', 'danger');
    });
});

// Demote staff to user
function demoteStaff(userId, userName) {
    if (confirm(`Are you sure you want to demote ${userName} to a regular user? This will remove all staff privileges.`)) {
        fetch(`{{ url('admin/staff') }}/${userId}/demote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
            showAlert('Failed to demote staff member', 'danger');
        });
    }
}

// Toggle staff status
function toggleStaffStatus(userId) {
    if (confirm('Are you sure you want to change this staff member\'s status?')) {
        fetch(`{{ url('admin/users') }}/${userId}/toggle-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
            showAlert('Failed to update status', 'danger');
        });
    }
}

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

// Reset modal when closed
document.getElementById('promoteUserModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('promoteUserForm').reset();
    document.getElementById('userSearchResults').style.display = 'none';
    document.getElementById('selectedUserSection').style.display = 'none';
    selectedUserId = null;
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.staff-card')) {
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
.staff-card {
    transition: all 0.2s ease;
    border-radius: 8px;
}

.staff-card:hover {
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
}

@media (max-width: 576px) {
    .staff-card .card-body {
        padding: 0.75rem;
    }
    
    .btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
}

.btn, .badge, .card {
    transition: all 0.2s ease;
}
</style>
@endsection