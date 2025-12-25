@extends('admin.layouts.vertical', ['title' => 'KYC Management', 'subTitle' => 'Admin'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">KYC Management</h4>
                        <p class="text-muted mb-0">Manage user identity verification requests</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <select class="form-select form-select-sm" onchange="filterKyc(this.value)" style="width: auto;">
                            <option value="" {{ !request('status') ? 'selected' : '' }}>All Statuses</option>
                            <option value="not_submitted" {{ request('status') === 'not_submitted' ? 'selected' : '' }}>Not Submitted</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>Submitted</option>
                            <option value="under_review" {{ request('status') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control form-control-sm" id="userSearch" placeholder="Search users..." value="{{ request('search') }}">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="searchKycUsers()">
                                <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols-light:verified" class="text-success" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Verified</h6>
                <h5 class="mb-0">{{ $kycStats['verified'] }}</h5>
                <small class="text-muted">Approved</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Under Review</h6>
                <h5 class="mb-0">{{ $kycStats['under_review'] }}</h5>
                <small class="text-muted">Processing</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:file-duotone" class="text-info" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Submitted</h6>
                <h5 class="mb-0">{{ $kycStats['submitted'] }}</h5>
                <small class="text-muted">Awaiting</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="mage:file-cross-fill" class="text-danger" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Rejected</h6>
                <h5 class="mb-0">{{ $kycStats['rejected'] }}</h5>
                <small class="text-muted">Declined</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:send-duotone" class="text-secondary" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Pending</h6>
                <h5 class="mb-0">{{ $kycStats['pending'] }}</h5>
                <small class="text-muted">Started</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:profile-duotone" class="text-primary" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Total Users</h6>
                <h5 class="mb-0">{{ $kycStats['total'] }}</h5>
                <small class="text-muted">All users</small>
            </div>
        </div>
    </div>
</div>

{{-- KYC Submissions Table/Cards --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="d-flex card-header justify-content-between align-items-center">
                <h5 class="card-title mb-0">KYC Submissions ({{ $kycUsers->total() }})</h5>
                @if(request('status') || request('search'))
                <div class="flex-shrink-0">
                    <a href="{{ route('admin.kyc.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="material-symbols:refresh-rounded"></iconify-icon> Clear Filters
                    </a>
                </div>
                @endif
            </div>

            @if($kycUsers->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">KYC Status</th>
                                    <th scope="col">Country</th>
                                    <th scope="col">Submitted</th>
                                    <th scope="col">Last Updated</th>
                                    <th scope="col" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kycUsers as $current_user)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                                <span class="avatar-title text-white">{{ $current_user->initials }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $current_user->full_name }}</h6>
                                                <small class="text-muted">{{ $current_user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $current_user->kyc_status === 'verified' ? 'success' : ($current_user->kyc_status === 'rejected' ? 'danger' : ($current_user->kyc_status === 'under_review' ? 'warning' : 'secondary')) }}-subtle text-{{ $current_user->kyc_status === 'verified' ? 'success' : ($current_user->kyc_status === 'rejected' ? 'danger' : ($current_user->kyc_status === 'under_review' ? 'warning' : 'secondary')) }} p-1">
                                            <iconify-icon icon="iconamoon:{{ $current_user->kyc_status === 'verified' ? 'check-circle' : ($current_user->kyc_status === 'rejected' ? 'close-circle' : ($current_user->kyc_status === 'under_review' ? 'clock' : 'file')) }}-duotone" class="me-1"></iconify-icon>
                                            {{ ucwords(str_replace('_', ' ', $current_user->kyc_status ?? 'not_submitted')) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($current_user->country)
                                            <div class="d-flex align-items-center">
                                                <img src="https://flagcdn.com/24x18/{{ strtolower($current_user->country) }}.png" alt="{{ $current_user->country }}" class="me-2" style="width: 20px;">
                                                {{ $current_user->country_name }}
                                            </div>
                                        @else
                                            <span class="text-muted">Not specified</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($current_user->kyc_submitted_at)
                                            {{ $current_user->kyc_submitted_at->format('M d, Y') }}
                                            <small class="text-muted d-block">{{ $current_user->kyc_submitted_at->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">Not submitted</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($current_user->kyc_verified_at)
                                            {{ $current_user->kyc_verified_at->format('M d, Y') }}
                                            <small class="text-muted d-block">{{ $current_user->kyc_verified_at->diffForHumans() }}</small>
                                        @else
                                            {{ $current_user->updated_at->format('M d, Y') }}
                                            <small class="text-muted d-block">{{ $current_user->updated_at->diffForHumans() }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showKycDetails('{{ $current_user->id }}')">
                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>KYC Details
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                @if($current_user->kyc_status !== 'verified')
                                                <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateKycStatus('{{ $current_user->id }}', 'verified')">
                                                    <iconify-icon icon="hugeicons:tick-01" class="me-2"></iconify-icon>Approve KYC
                                                </a></li>
                                                @endif
                                                @if($current_user->kyc_status !== 'under_review')
                                                <li><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="updateKycStatus('{{ $current_user->id }}', 'under_review')">
                                                    <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>Mark Under Review
                                                </a></li>
                                                @endif
                                                @if($current_user->kyc_status !== 'rejected')
                                                <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="showRejectModal('{{ $current_user->id }}', '{{ $current_user->full_name }}')">
                                                    <iconify-icon icon="material-symbols:cancel-outline-rounded" class="me-2"></iconify-icon>Reject KYC
                                                </a></li>
                                                @endif
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showKycStatusModal('{{ $current_user->id }}', '{{ $current_user->full_name }}', '{{ $current_user->kyc_status ?? 'not_submitted' }}')">
                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Change Status
                                                </a></li>
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
                        @foreach($kycUsers as $current_user)
                        <div class="col-12">
                            <div class="card kyc-card border">
                                <div class="card-body p-3">
                                    {{-- Header Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm rounded-circle bg-primary">
                                                <span class="avatar-title text-white">{{ $current_user->initials }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $current_user->full_name }}</h6>
                                                <small class="text-muted">{{ $current_user->username }}</small>
                                            </div>
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                                <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="{{ route('admin.users.show', $current_user->id) }}">
                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showKycDetails('{{ $current_user->id }}')">
                                                    <iconify-icon icon="iconamoon:file-text-duotone" class="me-2"></iconify-icon>KYC Details
                                                </a></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="showKycStatusModal('{{ $current_user->id }}', '{{ $current_user->full_name }}', '{{ $current_user->kyc_status ?? 'not_submitted' }}')">
                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Change Status
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Status and Country Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-{{ $current_user->kyc_status === 'verified' ? 'success' : ($current_user->kyc_status === 'rejected' ? 'danger' : ($current_user->kyc_status === 'under_review' ? 'warning' : 'secondary')) }}-subtle text-{{ $current_user->kyc_status === 'verified' ? 'success' : ($current_user->kyc_status === 'rejected' ? 'danger' : ($current_user->kyc_status === 'under_review' ? 'warning' : 'secondary')) }}">
                                                <iconify-icon icon="iconamoon:{{ $current_user->kyc_status === 'verified' ? 'check-circle' : ($current_user->kyc_status === 'rejected' ? 'close-circle' : ($current_user->kyc_status === 'under_review' ? 'clock' : 'file')) }}-duotone" class="me-1"></iconify-icon>
                                                {{ ucwords(str_replace('_', ' ', $current_user->kyc_status ?? 'not_submitted')) }}
                                            </span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails('{{ $current_user->id }}')">
                                            <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $current_user->id }}"></iconify-icon>
                                        </button>
                                    </div>

                                    {{-- Email and Status Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <small class="text-muted">{{ $current_user->email }}</small>
                                            <div class="small text-muted">
                                                @if($current_user->kyc_submitted_at)
                                                    Submitted: {{ $current_user->kyc_submitted_at->diffForHumans() }}
                                                @else
                                                    Not submitted yet
                                                @endif
                                            </div>
                                        </div>
                                        @if($current_user->country)
                                            <img src="https://flagcdn.com/24x18/{{ strtolower($current_user->country) }}.png" alt="{{ $current_user->country }}" style="width: 24px;">
                                        @endif
                                    </div>

                                    {{-- Expandable Details --}}
                                    <div class="collapse mt-3" id="details-{{ $current_user->id }}">
                                        <div class="border-top pt-3">
                                            <div class="row g-2 small">
                                                <div class="col-6">
                                                    <div class="text-muted">Country</div>
                                                    <div>{{ $current_user->country_name ?? 'Not specified' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">City</div>
                                                    <div>{{ $current_user->city ?? 'Not specified' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Phone</div>
                                                    <div>{{ $current_user->phone ?? 'Not provided' }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-muted">Joined</div>
                                                    <div>{{ $current_user->created_at->format('M d, Y') }}</div>
                                                </div>
                                                @if($current_user->kyc_verified_at)
                                                <div class="col-6">
                                                    <div class="text-muted">Verified Date</div>
                                                    <div>{{ $current_user->kyc_verified_at->format('M d, Y') }}</div>
                                                </div>
                                                @endif
                                                @if($current_user->kyc_rejection_reason)
                                                <div class="col-12">
                                                    <div class="text-muted">Rejection Reason</div>
                                                    <div class="text-danger small">{{ $current_user->kyc_rejection_reason }}</div>
                                                </div>
                                                @endif
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
            @if($kycUsers->hasPages())
            <div class="card-footer border-top border-light">
                <div class="align-items-center justify-content-between row text-center text-sm-start">
                    <div class="col-sm">
                        <div class="text-muted">
                            Showing
                            <span class="fw-semibold text-body">{{ $kycUsers->firstItem() }}</span>
                            to
                            <span class="fw-semibold text-body">{{ $kycUsers->lastItem() }}</span>
                            of
                            <span class="fw-semibold">{{ $kycUsers->total() }}</span>
                            Users
                        </div>
                    </div>
                    <div class="col-sm-auto mt-3 mt-sm-0">
                        {{ $kycUsers->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:file-text-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No KYC Submissions Found</h6>
                    <p class="text-muted">No users match your current filter criteria.</p>
                    @if(request('status') || request('search'))
                    <a href="{{ route('admin.kyc.index') }}" class="btn btn-primary">Clear Filters</a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- KYC Status Change Modal --}}
<div class="modal fade" id="kycStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update KYC Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="kycStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="kycUserId">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <div id="kycUserName" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div id="currentKycStatus" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newKycStatus" class="form-label">New Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="newKycStatus" required>
                            <option value="">Select Status</option>
                            <option value="not_submitted">Not Submitted</option>
                            <option value="pending">Pending</option>
                            <option value="submitted">Submitted</option>
                            <option value="under_review">Under Review</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3" id="rejectionReasonField" style="display: none;">
                        <label for="rejectionReason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="kycNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="kycNotes" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="notifyUser" checked>
                            <label class="form-check-label" for="notifyUser">
                                Send notification email to user about status change
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- KYC Details Modal --}}
<div class="modal fade" id="kycDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KYC Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="kycDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Filter KYC by status
function filterKyc(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Search KYC users
function searchKycUsers() {
    const searchTerm = document.getElementById('userSearch').value;
    const url = new URL(window.location.href);
    
    if (searchTerm.trim()) {
        url.searchParams.set('search', searchTerm.trim());
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Handle search on Enter key
document.getElementById('userSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchKycUsers();
    }
});

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

// Quick update KYC status
function updateKycStatus(userId, status) {
    if (confirm(`Are you sure you want to ${status === 'verified' ? 'approve' : 'update'} this user's KYC status?`)) {
        fetch(`{{ url('admin/kyc') }}/${userId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                status: status,
                notify_user: true
            })
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
            showAlert('Failed to update KYC status', 'danger');
        });
    }
}

// Show KYC status change modal
function showKycStatusModal(userId, userName, currentStatus) {
    document.getElementById('kycUserId').value = userId;
    document.getElementById('kycUserName').textContent = userName;
    document.getElementById('currentKycStatus').innerHTML = `<span class="badge bg-secondary">${currentStatus.replace('_', ' ').toUpperCase()}</span>`;
    
    // Remove current status from options
    const select = document.getElementById('newKycStatus');
    Array.from(select.options).forEach(option => {
        option.style.display = option.value === currentStatus ? 'none' : 'block';
    });
    
    new bootstrap.Modal(document.getElementById('kycStatusModal')).show();
}

// Show rejection modal (shortcut for reject status)
function showRejectModal(userId, userName) {
    showKycStatusModal(userId, userName, 'any');
    document.getElementById('newKycStatus').value = 'rejected';
    document.getElementById('rejectionReasonField').style.display = 'block';
    document.getElementById('rejectionReason').required = true;
}

// Handle status change in modal
document.getElementById('newKycStatus').addEventListener('change', function() {
    const rejectionField = document.getElementById('rejectionReasonField');
    const rejectionReason = document.getElementById('rejectionReason');
    
    if (this.value === 'rejected') {
        rejectionField.style.display = 'block';
        rejectionReason.required = true;
    } else {
        rejectionField.style.display = 'none';
        rejectionReason.required = false;
        rejectionReason.value = '';
    }
});

// Handle KYC status form submission
document.getElementById('kycStatusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('kycUserId').value;
    const newStatus = document.getElementById('newKycStatus').value;
    const rejectionReason = document.getElementById('rejectionReason').value;
    const notes = document.getElementById('kycNotes').value;
    const notifyUser = document.getElementById('notifyUser').checked;
    
    fetch(`{{ url('admin/kyc') }}/${userId}/update-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            status: newStatus,
            rejection_reason: rejectionReason,
            notes: notes,
            notify_user: notifyUser
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('kycStatusModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update KYC status', 'danger');
    });
});

// Show KYC details
function showKycDetails(userId) {
    const modal = new bootstrap.Modal(document.getElementById('kycDetailsModal'));
    const content = document.getElementById('kycDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch details
    fetch(`{{ url('admin/kyc') }}/${userId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load KYC details</div>';
        });
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
document.getElementById('kycStatusModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('kycStatusForm').reset();
    document.getElementById('rejectionReasonField').style.display = 'none';
    document.getElementById('rejectionReason').required = false;
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.kyc-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});
</script>

<script>
    function toggleVerificationDetails(index) {
        const detailsElement = document.getElementById(`verification-details-${index}`);
        const chevronElement = document.getElementById(`chevron-verification-${index}`);
        
        if (detailsElement.classList.contains('show')) {
            detailsElement.classList.remove('show');
            chevronElement.style.transform = 'rotate(0deg)';
        } else {
            // Close other open details
            document.querySelectorAll('[id^="verification-details-"]').forEach(element => {
                element.classList.remove('show');
            });
            document.querySelectorAll('[id^="chevron-verification-"]').forEach(chevron => {
                chevron.style.transform = 'rotate(0deg)';
            });
            
            detailsElement.classList.add('show');
            chevronElement.style.transform = 'rotate(180deg)';
        }
    }
    
    // Close mobile details when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.verification-card')) {
            document.querySelectorAll('[id^="verification-details-"]').forEach(element => {
                element.classList.remove('show');
            });
            document.querySelectorAll('[id^="chevron-verification-"]').forEach(chevron => {
                chevron.style.transform = 'rotate(0deg)';
            });
        }
    });
    </script>

<style>
    /* Avatar styling */
    .avatar-lg {
        width: 4rem;
        height: 4rem;
    }
    
    .avatar-title {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    
    /* Timeline styling */
    .timeline-container {
        position: relative;
        padding-left: 1.5rem;
    }
    
    .timeline-container::before {
        content: '';
        position: absolute;
        left: 0.375rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .timeline-marker {
        position: absolute;
        left: -1.375rem;
        width: 0.75rem;
        height: 0.75rem;
        border-radius: 50%;
        border: 2px solid #fff;
        z-index: 1;
    }
    
    .timeline-content {
        margin-left: 0.5rem;
    }
    
    /* Table styling */
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
    
    /* Verification card styling */
    .verification-card {
        transition: all 0.2s ease;
        border-radius: 8px;
    }
    
    .verification-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* Badge styling */
    .badge[class*="-subtle"] {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    /* Code styling */
    code {
        background-color: #f8f9fa;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.875rem;
    }
    
    /* Mobile responsive styles */
    @media (max-width: 768px) {
        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        .timeline-container {
            padding-left: 1rem;
        }
        
        .timeline-marker {
            left: -0.875rem;
            width: 0.5rem;
            height: 0.5rem;
        }
        
        .timeline-content {
            margin-left: 0.25rem;
        }
    }
    
    @media (max-width: 576px) {
        .verification-card .card-body {
            padding: 0.75rem;
        }
        
        .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .fs-5 {
            font-size: 1rem !important;
        }
    }
    
    /* Smooth transitions */
    .btn, .badge, .card {
        transition: all 0.2s ease;
    }
    
    /* Utility classes */
    .fs-6 {
        font-size: 1rem;
    }
    
.kyc-card {
    transition: all 0.2s ease;
    border-radius: 8px;
}

.kyc-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
    .kyc-card .card-body {
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