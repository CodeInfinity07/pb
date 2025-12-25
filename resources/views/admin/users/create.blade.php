@extends('admin.layouts.vertical', ['title' => 'Create User', 'subTitle' => 'User Management'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Create New User</h4>
                        <p class="text-muted mb-0">Add a new user to the platform</p>
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

<form action="{{ route('admin.users.store') }}" method="POST" id="createUserForm">
    @csrf
    
    <div class="row g-4">
        {{-- Main Form --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Name Fields --}}
                        <div class="col-12 col-md-6">
                            <label for="first_name" class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                   id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                   id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Contact Fields --}}
                        <div class="col-12 col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required>
                            <div class="form-text">User will receive a welcome email with login details</div>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="phone" class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div class="col-12 col-md-6">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                       id="password" name="password" minlength="8" required>
                                <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                    <iconify-icon icon="iconamoon:eye-duotone" id="passwordIcon"></iconify-icon>
                                </button>
                            </div>
                            <div class="form-text">Minimum 8 characters required</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation" 
                                   name="password_confirmation" required>
                            <div class="form-text">Re-enter the password</div>
                        </div>

                        {{-- City Field --}}
                        <div class="col-12 col-md-6">
                            <label for="city" class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                   id="city" name="city" value="{{ old('city') }}" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Username (Auto-generated) --}}
                        <div class="col-12 col-md-6">
                            <label for="username" class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username') }}" required readonly>
                                <button type="button" class="btn btn-outline-secondary" id="generateUsername">
                                    <iconify-icon icon="material-symbols:refresh-rounded"></iconify-icon>
                                </button>
                            </div>
                            <div class="form-text">Auto-generated, click refresh to regenerate</div>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Sponsor Selection --}}
            @if(auth()->user()->isAdmin())
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sponsor Selection</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="sponsor_id" class="form-label fw-semibold">Select Sponsor</label>
                        <select class="form-select @error('sponsor_id') is-invalid @enderror" id="sponsor_id" name="sponsor_id">
                            <option value="">No Sponsor (Direct Registration)</option>
                            @foreach($availableSponsors as $sponsor)
                                <option value="{{ $sponsor->id }}" {{ old('sponsor_id') == $sponsor->id ? 'selected' : '' }}>
                                    {{ $sponsor->full_name }} ({{ $sponsor->email }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Select a user to be the sponsor of this new user</div>
                        @error('sponsor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="d-flex align-items-center p-2 bg-info bg-opacity-10 rounded">
                        <iconify-icon icon="iconamoon:information-duotone" class="text-info me-2"></iconify-icon>
                        <small class="text-info">As admin, you can assign any user as sponsor</small>
                    </div>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sponsor Information</h5>
                </div>
                <div class="card-body">
                    <input type="hidden" name="sponsor_id" value="{{ auth()->user()->id }}">
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                            <span class="avatar-title text-white">{{ auth()->user()->initials }}</span>
                        </div>
                        <div>
                            <div class="fw-semibold">{{ auth()->user()->full_name }}</div>
                            <small class="text-muted">{{ auth()->user()->email }}</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center p-2 bg-success bg-opacity-10 rounded">
                        <iconify-icon icon="iconamoon:check-duotone" class="text-success me-2"></iconify-icon>
                        <small class="text-success">You will be automatically set as the sponsor for this user</small>
                    </div>
                </div>
            </div>
            @endif

            {{-- Account Settings --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="role" class="form-label fw-semibold">User Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="user" {{ old('role', 'user') === 'user' ? 'selected' : '' }}>Regular User</option>
                                @if(auth()->user()->isAdmin())
                                    <option value="moderator" {{ old('role') === 'moderator' ? 'selected' : '' }}>Moderator</option>
                                    <option value="support" {{ old('role') === 'support' ? 'selected' : '' }}>Support</option>
                                    <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                                @endif
                            </select>
                            @error('role')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label for="status" class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="email_verified" name="email_verified" value="1" {{ old('email_verified') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="email_verified">
                                    Mark email as verified
                                </label>
                                <div class="form-text">User won't need to verify their email if checked</div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="send_welcome_email" name="send_welcome_email" value="1" {{ old('send_welcome_email', '1') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="send_welcome_email">
                                    Send welcome email
                                </label>
                                <div class="form-text">Send login credentials and welcome message</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Initial Balance --}}
            @if(auth()->user()->isAdmin())
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Initial Balance</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="initial_balance" class="form-label fw-semibold">Starting Balance</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control @error('initial_balance') is-invalid @enderror" 
                                   id="initial_balance" name="initial_balance" value="{{ old('initial_balance', '0.00') }}">
                        </div>
                        <div class="form-text">Optional starting balance for the new user</div>
                        @error('initial_balance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            @endif
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
                                <iconify-icon icon="iconamoon:user-plus-duotone" class="me-2"></iconify-icon>
                                Create User
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-info" onclick="previewUser()">
                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                                Preview
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('script')
<script>
// Auto-generate username from first name and last name
function generateUsername() {
    const firstName = document.getElementById('first_name').value;
    const lastName = document.getElementById('last_name').value;
    
    if (firstName || lastName) {
        let username = (firstName + lastName).toLowerCase().replace(/[^a-z0-9]/g, '');
        if (username.length < 3) {
            username = 'user' + Math.floor(Math.random() * 10000);
        } else if (username.length > 20) {
            username = username.substring(0, 20);
        }
        // Add random numbers to make it more unique
        username += Math.floor(Math.random() * 1000);
        document.getElementById('username').value = username;
    }
}

// Generate username when name fields change
document.getElementById('first_name').addEventListener('input', generateUsername);
document.getElementById('last_name').addEventListener('input', generateUsername);

// Manual username generation
document.getElementById('generateUsername').addEventListener('click', function() {
    generateUsername();
});

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('password');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.setAttribute('icon', 'iconamoon:eye-off-duotone');
    } else {
        passwordInput.type = 'password';
        passwordIcon.setAttribute('icon', 'iconamoon:eye-duotone');
    }
});

// Form validation
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirmation').value;
    const email = document.getElementById('email').value;
    
    // Password validation
    if (password !== passwordConfirm) {
        e.preventDefault();
        showAlert('Passwords do not match', 'danger');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        showAlert('Password must be at least 8 characters long', 'danger');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        showAlert('Please enter a valid email address', 'danger');
        return false;
    }
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
    submitBtn.disabled = true;
    
    // Re-enable if form submission fails
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});

// Preview user function
function previewUser() {
    const formData = new FormData(document.getElementById('createUserForm'));
    const userData = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        city: formData.get('city'),
        username: formData.get('username'),
        role: formData.get('role'),
        status: formData.get('status')
    };
    
    if (!userData.first_name || !userData.last_name || !userData.email) {
        showAlert('Please fill in at least the name and email fields to preview', 'warning');
        return;
    }
    
    const previewHtml = `
        <div class="d-flex align-items-center mb-3">
            <div class="avatar avatar-lg rounded-circle bg-primary me-3">
                <span class="avatar-title text-white fs-4">${userData.first_name.charAt(0)}${userData.last_name.charAt(0)}</span>
            </div>
            <div>
                <h5 class="mb-0">${userData.first_name} ${userData.last_name}</h5>
                <p class="text-muted mb-0">${userData.email}</p>
                <div class="d-flex gap-2 mt-1">
                    <span class="badge bg-primary-subtle text-primary">${userData.role}</span>
                    <span class="badge bg-success-subtle text-success">${userData.status}</span>
                </div>
            </div>
        </div>
        <div class="row g-2 small">
            <div class="col-6"><strong>Username:</strong> ${userData.username || 'Not set'}</div>
            <div class="col-6"><strong>Phone:</strong> ${userData.phone || 'Not provided'}</div>
            <div class="col-6"><strong>City:</strong> ${userData.city || 'Not provided'}</div>
            <div class="col-6"><strong>Role:</strong> ${userData.role}</div>
        </div>
    `;
    
    showPreviewModal('User Preview', previewHtml);
}

function showPreviewModal(title, content) {
    const modalHtml = `
        <div class="modal fade" id="previewModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">${content}</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal
    const existingModal = document.getElementById('previewModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    new bootstrap.Modal(document.getElementById('previewModal')).show();
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

// Initial username generation on page load if names are present
document.addEventListener('DOMContentLoaded', function() {
    generateUsername();
});
</script>

<style>
.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

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

.input-group .btn {
    border-radius: 0 6px 6px 0;
}

@media (max-width: 768px) {
    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }
    
    .card-body {
        padding: 1rem;
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