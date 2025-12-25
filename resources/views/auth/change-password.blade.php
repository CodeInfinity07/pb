@extends('layouts.auth', ['title' => 'Change Password'])

@section('content')
    <div class="col-xl-12">
        <div class="card auth-card">
            <div class="card-body p-0">
                <div class="row align-items-center g-0">
                    <div class="col-lg-6 d-none d-lg-inline-block border-end">
                        <div class="auth-page-sidebar">
                            <img src="/images/sign-in.svg" alt="auth" class="img-fluid" />
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="p-4">
                            <div class="mx-auto mb-4 text-center auth-logo">
                                <a href="{{ route('any', 'home') }}" class="logo-dark">
                                    <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                                </a>

                                <a href="{{ route('any', 'home') }}" class="logo-light">
                                    <img src="/images/logo-light.png" height="60" alt="logo light" />
                                </a>
                            </div>
                            
                            <div class="text-center mb-4">
                                <div class="avatar-md mx-auto">
                                    <div class="avatar-title rounded-circle bg-light">
                                        <i class="bx bx-lock-alt h2 mb-0 text-warning"></i>
                                    </div>
                                </div>
                                <div class="p-2 mt-4">
                                    <h4>Change Your Password</h4>
                                    <p class="text-muted">You're using a temporary password. Please set a new secure password to continue.</p>
                                </div>
                            </div>
                            
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-10">
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <strong>Please fix the following errors:</strong>
                                            <ul class="mb-0 mt-2 ps-3">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    @if (session('warning'))
                                        <div class="alert alert-warning">
                                            <i class="bx bx-info-circle me-1"></i>
                                            {{ session('warning') }}
                                        </div>
                                    @endif

                                    @if (session('error'))
                                        <div class="alert alert-danger">
                                            <i class="bx bx-error me-1"></i>
                                            {{ session('error') }}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('password.change.update') }}" id="changePasswordForm" class="authentication-form">
                                        @csrf

                                        <!-- Current Password -->
                                        <div class="mb-3">
                                            <label class="form-label" for="current_password">
                                                Current Password (Temporary)
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       id="current_password" 
                                                       name="current_password" 
                                                       class="form-control @error('current_password') is-invalid @enderror" 
                                                       required 
                                                       autofocus>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleCurrent" tabindex="-1">
                                                    <i class="bx bx-hide"></i>
                                                </button>
                                            </div>
                                            @error('current_password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <!-- New Password -->
                                        <div class="mb-3">
                                            <label class="form-label" for="password">
                                                New Password
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       id="password" 
                                                       name="password" 
                                                       class="form-control @error('password') is-invalid @enderror" 
                                                       minlength="8" 
                                                       required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleNew" tabindex="-1">
                                                    <i class="bx bx-hide"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Minimum 8 characters</div>
                                            @error('password')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                            
                                            <!-- Password Strength Indicator -->
                                            <div class="mt-2">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <small class="text-muted">Password Strength:</small>
                                                    <small id="strengthText" class="text-muted">Enter password</small>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div id="strengthBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Confirm Password -->
                                        <div class="mb-3">
                                            <label class="form-label" for="password_confirmation">
                                                Confirm New Password
                                            </label>
                                            <div class="input-group">
                                                <input type="password" 
                                                       id="password_confirmation" 
                                                       name="password_confirmation" 
                                                       class="form-control" 
                                                       minlength="8" 
                                                       required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggleConfirm" tabindex="-1">
                                                    <i class="bx bx-hide"></i>
                                                </button>
                                            </div>
                                            <div id="passwordMatchIndicator" class="form-text mt-1"></div>
                                        </div>

                                        <!-- Password Requirements -->
                                        <div class="alert alert-info mb-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bx bx-info-circle me-2 mt-1"></i>
                                                <div>
                                                    <strong>Password Requirements:</strong>
                                                    <ul class="mb-0 mt-2 ps-3" style="font-size: 13px;">
                                                        <li>At least 8 characters long</li>
                                                        <li>Mix of uppercase and lowercase letters</li>
                                                        <li>Include numbers and special characters</li>
                                                        <li>Must be different from current password</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="mb-1 text-center d-grid">
                                            <button class="btn btn-primary btn-lg" type="submit" id="submitBtn">
                                                <i class="bx bx-lock-alt me-1"></i>
                                                <span id="submitBtnText">Change Password</span>
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Logout Option -->
                                    <div class="text-center mt-3">
                                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-link text-decoration-none p-0">
                                                <i class="bx bx-log-out me-1"></i>Logout
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-white mb-0 text-center">
            Need help? 
            <a href="mailto:support@yoursite.com" class="text-white fw-bold ms-1">Contact Support</a>
        </p>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const elements = {
        currentPassword: document.getElementById('current_password'),
        password: document.getElementById('password'),
        passwordConfirmation: document.getElementById('password_confirmation'),
        toggleCurrent: document.getElementById('toggleCurrent'),
        toggleNew: document.getElementById('toggleNew'),
        toggleConfirm: document.getElementById('toggleConfirm'),
        strengthBar: document.getElementById('strengthBar'),
        strengthText: document.getElementById('strengthText'),
        matchIndicator: document.getElementById('passwordMatchIndicator'),
        form: document.getElementById('changePasswordForm'),
        submitBtn: document.getElementById('submitBtn'),
        submitBtnText: document.getElementById('submitBtnText')
    };

    // Password toggle functionality
    function setupPasswordToggle(button, input) {
        if (!button || !input) return;
        
        button.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            } else {
                input.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            }
        });
    }

    setupPasswordToggle(elements.toggleCurrent, elements.currentPassword);
    setupPasswordToggle(elements.toggleNew, elements.password);
    setupPasswordToggle(elements.toggleConfirm, elements.passwordConfirmation);

    // Password strength checker
    elements.password.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrength(strength);
        checkPasswordMatch();
    });

    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length === 0) {
            return { score: 0, text: 'Enter password', class: '', width: 0 };
        }
        
        // Length score
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;
        if (password.length >= 16) score += 10;
        
        // Character variety score
        if (/[a-z]/.test(password)) score += 15;
        if (/[A-Z]/.test(password)) score += 15;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^A-Za-z0-9]/.test(password)) score += 15;
        
        // Determine strength level
        if (score >= 80) {
            return { score: 100, text: 'Very Strong', class: 'bg-success', width: 100 };
        } else if (score >= 60) {
            return { score: 75, text: 'Strong', class: 'bg-success', width: 75 };
        } else if (score >= 40) {
            return { score: 50, text: 'Medium', class: 'bg-warning', width: 50 };
        } else if (score >= 20) {
            return { score: 25, text: 'Weak', class: 'bg-danger', width: 25 };
        } else {
            return { score: 10, text: 'Very Weak', class: 'bg-danger', width: 10 };
        }
    }

    function updatePasswordStrength(strength) {
        elements.strengthBar.style.width = strength.width + '%';
        elements.strengthBar.className = 'progress-bar ' + strength.class;
        elements.strengthText.textContent = strength.text;
        elements.strengthText.className = 'text-' + (strength.class.replace('bg-', ''));
    }

    // Password confirmation matching
    function checkPasswordMatch() {
        const password = elements.password.value;
        const confirm = elements.passwordConfirmation.value;
        
        if (confirm.length === 0) {
            elements.matchIndicator.textContent = '';
            elements.matchIndicator.className = 'form-text mt-1';
            return;
        }
        
        if (password === confirm) {
            elements.matchIndicator.textContent = '✓ Passwords match';
            elements.matchIndicator.className = 'form-text mt-1 text-success';
            elements.passwordConfirmation.classList.add('is-valid');
            elements.passwordConfirmation.classList.remove('is-invalid');
        } else {
            elements.matchIndicator.textContent = '✗ Passwords do not match';
            elements.matchIndicator.className = 'form-text mt-1 text-danger';
            elements.passwordConfirmation.classList.add('is-invalid');
            elements.passwordConfirmation.classList.remove('is-valid');
        }
    }
    
    elements.password.addEventListener('input', checkPasswordMatch);
    elements.passwordConfirmation.addEventListener('input', checkPasswordMatch);

    // Form submission
    elements.form.addEventListener('submit', function(e) {
        const password = elements.password.value;
        const confirm = elements.passwordConfirmation.value;
        
        // Validate passwords match
        if (password !== confirm) {
            e.preventDefault();
            elements.matchIndicator.textContent = '✗ Passwords do not match';
            elements.matchIndicator.className = 'form-text mt-1 text-danger';
            elements.passwordConfirmation.focus();
            return false;
        }
        
        // Validate password strength
        const strength = calculatePasswordStrength(password);
        if (strength.score < 25) {
            e.preventDefault();
            alert('Please choose a stronger password for your security.');
            elements.password.focus();
            return false;
        }
        
        // Disable submit button
        elements.submitBtn.disabled = true;
        elements.submitBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Changing Password...';
    });

    // Focus on first input
    elements.currentPassword.focus();
});
</script>

<style>
.progress {
    border-radius: 2px;
    background-color: #e9ecef;
}

.progress-bar {
    transition: width 0.3s ease, background-color 0.3s ease;
}

.input-group .btn {
    border-left: 0;
}

.input-group .form-control:focus + .btn {
    border-color: #86b7fe;
}

.form-control.is-valid {
    border-color: #198754;
    background-image: none;
    padding-right: 0.75rem;
}

.form-control.is-invalid {
    background-image: none;
    padding-right: 0.75rem;
}

.alert-info {
    background-color: #cff4fc;
    border-color: #9eeaf9;
    color: #055160;
}

.btn:disabled {
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .auth-page-sidebar {
        padding: 2rem;
    }
}
</style>
@endsection