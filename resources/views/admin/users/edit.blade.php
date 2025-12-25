@extends('admin.layouts.vertical', ['title' => 'Edit User', 'subTitle' => 'User Management'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Edit User</h4>
                        <p class="text-muted mb-0">Update {{ $user->full_name }}'s information</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <iconify-icon icon="iconamoon:arrow-left-2-duotone" class="me-2"></iconify-icon>
                            Back to Users
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('admin.users.update', $user->id) }}" method="POST" id="userForm">
    @csrf
    @method('PUT')
    
    <div class="row g-4">
        {{-- Main Form --}}
        <div class="col-12 col-lg-8">
            {{-- User Info Card --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">User Information</h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'user' ? 'primary' : 'warning') }}-subtle text-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'user' ? 'primary' : 'warning') }}">
                                {{ ucfirst($user->role) }}
                            </span>
                            <span class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}-subtle text-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    {{-- User Avatar Section --}}
                    <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
                        <div class="avatar avatar-lg rounded-circle bg-primary me-3">
                            <span class="avatar-title text-white fs-4">{{ $user->initials }}</span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">{{ $user->full_name }}</h5>
                            <p class="text-muted mb-1">{{ $user->email }}</p>
                            <div class="d-flex flex-wrap gap-1">
                                @if($user->hasVerifiedEmail())
                                    <span class="badge bg-success-subtle text-success">Email Verified</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Email Unverified</span>
                                @endif
                                @if($user->profile && $user->profile->kyc_status === 'verified')
                                    <span class="badge bg-info-subtle text-info">KYC Verified</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-success fw-bold">${{ number_format($user->accountBalance->balance ?? 0, 2) }}</div>
                            <small class="text-muted">Account Balance</small>
                        </div>
                    </div>

                    {{-- Form Fields --}}
                    <div class="row g-3">
                        {{-- Name Fields --}}
                        <div class="col-12 col-md-6">
                            <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                   id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                   id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Contact Fields --}}
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   id="username" name="username" value="{{ old('username', $user->username) }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Role & Status --}}
                        <div class="col-12 col-md-6">
                            <label for="role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                @foreach($roles as $key => $value)
                                    <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="status" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="blocked" {{ old('status', $user->status) === 'blocked' ? 'selected' : '' }}>Blocked</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password Section --}}
                        <div class="col-12">
                            <hr class="my-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="change_password">
                                <label class="form-check-label fw-semibold" for="change_password">
                                    Change Password
                                </label>
                            </div>
                        </div>
                        
                        <div id="password_fields" class="col-12" style="display: none;">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="password" class="form-label fw-semibold">New Password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" minlength="8">
                                    <div class="form-text">Minimum 8 characters</div>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Profile Information --}}
            @if($user->profile)
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="country" class="form-label fw-semibold">Country</label>
                            <select class="form-select @error('country') is-invalid @enderror" id="country" name="country">
                                <option value="">Select Country</option>
                                @foreach($countries as $code => $name)
                                    <option value="{{ $code }}" 
                                        {{ old('country', $user->profile->country ?? '') === $code ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="city" class="form-label fw-semibold">City</label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                   id="city" name="city" value="{{ old('city', $user->profile->city ?? '') }}">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="address" class="form-label fw-semibold">Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" name="address" rows="2">{{ old('address', $user->profile->address ?? '') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="date_of_birth" class="form-label fw-semibold">Date of Birth</label>
                            <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                   id="date_of_birth" name="date_of_birth" 
                                   value="{{ old('date_of_birth', $user->profile->date_of_birth ? $user->profile->date_of_birth->format('Y-m-d') : '') }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="kyc_status" class="form-label fw-semibold">KYC Status</label>
                            <select class="form-select @error('kyc_status') is-invalid @enderror" id="kyc_status" name="kyc_status">
                                <option value="not_submitted" {{ old('kyc_status', $user->profile->kyc_status ?? 'not_submitted') === 'not_submitted' ? 'selected' : '' }}>Not Submitted</option>
                                <option value="pending" {{ old('kyc_status', $user->profile->kyc_status ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="under_review" {{ old('kyc_status', $user->profile->kyc_status ?? '') === 'under_review' ? 'selected' : '' }}>Under Review</option>
                                <option value="verified" {{ old('kyc_status', $user->profile->kyc_status ?? '') === 'verified' ? 'selected' : '' }}>Verified</option>
                                <option value="rejected" {{ old('kyc_status', $user->profile->kyc_status ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                            @error('kyc_status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
        
        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Quick Stats --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-3 bg-success bg-opacity-10 rounded">
                                <iconify-icon icon="material-symbols:account-balance-wallet" class="text-success fs-4"></iconify-icon>
                                <div class="mt-2">
                                    <div class="fw-bold text-success">${{ number_format($user->accountBalance->balance ?? 0, 2) }}</div>
                                    <small class="text-muted">Balance</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-info bg-opacity-10 rounded">
                                <iconify-icon icon="iconamoon:chart-duotone" class="text-info fs-4"></iconify-icon>
                                <div class="mt-2">
                                    <div class="fw-bold text-info">${{ number_format($user->earnings->total ?? 0, 2) }}</div>
                                    <small class="text-muted">Total Earnings</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-primary bg-opacity-10 rounded">
                                <iconify-icon icon="iconamoon:profile-duotone" class="text-primary fs-4"></iconify-icon>
                                <div class="mt-2">
                                    <div class="fw-bold text-primary">{{ $user->directReferrals->count() }}</div>
                                    <small class="text-muted">Referrals</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-warning bg-opacity-10 rounded">
                                <iconify-icon icon="iconamoon:history-duotone" class="text-warning fs-4"></iconify-icon>
                                <div class="mt-2">
                                    <div class="fw-bold text-warning">{{ $user->transactions->count() }}</div>
                                    <small class="text-muted">Transactions</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Account Details --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Details</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">User ID</span>
                            <span class="fw-semibold">{{ $user->id }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Joined</span>
                            <span class="fw-semibold">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Last Login</span>
                            <span class="fw-semibold">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}</span>
                        </div>
                        @if($user->sponsor)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Sponsor</span>
                            <span class="fw-semibold">{{ $user->sponsor->full_name }}</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Referral Code</span>
                            <span class="fw-semibold">{{ $user->referral_code }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if(!$user->hasVerifiedEmail())
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="verifyEmail()">
                            <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>
                            Verify Email
                        </button>
                        @endif
                        
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="showBalanceModal()">
                            <iconify-icon icon="material-symbols:account-balance-wallet" class="me-2"></iconify-icon>
                            Adjust Balance
                        </button>
                        
                        <button type="button" class="btn btn-outline-{{ $user->status === 'active' ? 'warning' : 'success' }} btn-sm" onclick="toggleStatus()">
                            <iconify-icon icon="iconamoon:{{ $user->status === 'active' ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                            {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Action Buttons --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row gap-2 justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>
                                Update User
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-info" onclick="showUserDetails()">
                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Balance Modal --}}
<div class="modal fade" id="balanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="balanceForm">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <iconify-icon icon="iconamoon:information-duotone" class="me-2"></iconify-icon>
                        Current balance: <strong>${{ number_format($user->accountBalance->balance ?? 0, 2) }}</strong>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="balance_amount" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="balance_type" required>
                                <option value="add">Add (+)</option>
                                <option value="subtract">Subtract (-)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Reason</label>
                            <input type="text" class="form-control" id="balance_reason" placeholder="Reason for adjustment" required>
                        </div>
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
// Password toggle
document.getElementById('change_password').addEventListener('change', function() {
    const passwordFields = document.getElementById('password_fields');
    const passwordInput = document.getElementById('password');
    
    if (this.checked) {
        passwordFields.style.display = 'block';
        passwordInput.required = true;
    } else {
        passwordFields.style.display = 'none';
        passwordInput.required = false;
        passwordInput.value = '';
        document.getElementById('password_confirmation').value = '';
    }
});

// Quick Actions
function verifyEmail() {
    if (confirm('Manually verify this user\'s email address?')) {
        fetch(`{{ route('admin.users.verify-email', $user->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'warning');
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(() => showAlert('Failed to verify email', 'danger'));
    }
}

function toggleStatus() {
    const currentStatus = '{{ $user->status }}';
    const newStatus = currentStatus === 'active' ? 'deactivate' : 'activate';
    
    if (confirm(`Are you sure you want to ${newStatus} this user?`)) {
        fetch(`{{ route('admin.users.toggle-status', $user->id) }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(() => showAlert('Failed to update status', 'danger'));
    }
}

function showBalanceModal() {
    new bootstrap.Modal(document.getElementById('balanceModal')).show();
}

function showUserDetails() {
    fetch(`{{ route('admin.users.show', $user->id) }}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Create and show details modal
                const modalHtml = `
                    <div class="modal fade" id="userDetailsModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">User Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">${data.html}</div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
            }
        })
        .catch(() => showAlert('Failed to load user details', 'danger'));
}

// Balance form submission
document.getElementById('balanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        amount: document.getElementById('balance_amount').value,
        type: document.getElementById('balance_type').value,
        reason: document.getElementById('balance_reason').value
    };
    
    fetch(`{{ route('admin.users.adjust-balance', $user->id) }}`, {
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
        if (data.success) setTimeout(() => location.reload(), 1000);
    })
    .catch(() => showAlert('Failed to adjust balance', 'danger'));
});

// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const changePassword = document.getElementById('change_password').checked;
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirmation').value;
    
    if (changePassword && password !== passwordConfirm) {
        e.preventDefault();
        showAlert('Passwords do not match', 'danger');
        return false;
    }
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
</script>

<style>
.avatar-lg {
    width: 3rem;
    height: 3rem;
    font-size: 1.1rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 1rem;
}

.form-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.form-control, .form-select {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus, .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
}

.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.4em 0.7em;
}

.btn {
    border-radius: 6px;
    font-weight: 500;
}

@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .avatar-lg {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 1rem;
    }
    
    .row.g-3 {
        --bs-gutter-x: 0.75rem;
        --bs-gutter-y: 0.75rem;
    }
}

@media (max-width: 576px) {
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 0.75rem;
    }
}
</style>
@endsection