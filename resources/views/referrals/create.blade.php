@extends('layouts.vertical', ['title' => 'Add Direct Referral', 'subTitle' => 'Referrals'])

@section('content')
<div class="container-fluid">

    <!-- Success Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="bx bx-check-circle fs-20 me-2"></i>
                <div class="flex-grow-1">{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Error Messages -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="bx bx-error-circle fs-20 me-2"></i>
                <div class="flex-grow-1">
                    <strong>Error:</strong> {{ session('error') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-start">
                <i class="bx bx-error fs-20 me-2"></i>
                <div class="flex-grow-1">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Info Card -->
    <div class="card mb-4 border-info">
        <div class="card-body">
            <div class="d-flex align-items-start">
                <div class="flex-shrink-0">
                    <i class="bx bx-info-circle text-info fs-24"></i>
                </div>
                <div class="flex-grow-1 ms-3">
                    <h6 class="mb-2">Important Information</h6>
                    <ul class="mb-0 small text-muted ps-3">
                        <li>You are creating an account on behalf of your referral</li>
                        <li>A temporary password will be generated (you must save it and share with the user)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <form action="{{ route('referrals.store-direct') }}" method="POST" id="createReferralForm" novalidate>
        @csrf
        
        <!-- Personal Information -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="bx bx-user me-2 text-primary"></i>Personal Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('first_name') is-invalid @enderror" 
                               id="firstName" 
                               name="first_name" 
                               value="{{ old('first_name') }}" 
                               required>
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('last_name') is-invalid @enderror" 
                               id="lastName" 
                               name="last_name" 
                               value="{{ old('last_name') }}" 
                               required>
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('username') is-invalid @enderror" 
                               id="username" 
                               name="username" 
                               value="{{ old('username') }}" 
                               pattern="[a-zA-Z0-9_]+" 
                               minlength="3"
                               required>
                        <div class="form-text small">Only letters, numbers, and underscores. Minimum 3 characters.</div>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div id="usernameAvailability"></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" 
                               class="form-control @error('phone') is-invalid @enderror" 
                               id="phone" 
                               name="phone" 
                               value="{{ old('phone') }}" 
                               required>
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            <div class="d-flex align-items-start">
                                <i class="bx bx-key fs-20 me-2"></i>
                                <div class="flex-grow-1">
                                    <strong>Temporary Password:</strong>
                                    <div class="small mt-1">
                                        A secure temporary password will be automatically generated. 
                                        You must save it and share it with the user. 
                                        The user will be required to change it on first login.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Account Information -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="bx bx-joystick me-2 text-primary"></i>Game Account Information
                </h5>
            </div>
            <div class="card-body">
                
                <!-- Game Credential Errors (if any) -->
                @if($errors->has('game_username') || $errors->has('game_password'))
                    <div class="alert alert-danger mb-3" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-error-circle fs-20 me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Game Credential Validation Failed</strong>
                                @if($errors->has('game_username'))
                                    <div class="mt-1">{{ $errors->first('game_username') }}</div>
                                @endif
                                @if($errors->has('game_password'))
                                    <div class="mt-1">{{ $errors->first('game_password') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <div class="alert alert-info mb-3" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bx bx-info-circle fs-20 me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Game Account Required</strong>
                            <div class="small mt-1">The game account credentials will be validated before creating the referral account. Make sure the credentials are correct.</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="gameUsername" class="form-label">Game Username <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('game_username') is-invalid @enderror" 
                               id="gameUsername" 
                               name="game_username" 
                               value="{{ old('game_username') }}" 
                               minlength="10" 
                               required
                               autocomplete="off">
                        <div class="form-text small">Minimum 10 characters</div>
                        @error('game_username')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div id="gameUsernameAvailability"></div>
                    </div>
                    
                    <div class="col-12">
                        <label for="gamePassword" class="form-label">Game Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control @error('game_password') is-invalid @enderror" 
                                   id="gamePassword" 
                                   name="game_password" 
                                   minlength="6" 
                                   required
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" id="toggleGamePassword">
                                <i class="bx bx-hide"></i>
                            </button>
                        </div>
                        <div class="form-text small">Minimum 6 characters</div>
                        @error('game_password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <button type="button" class="btn btn-primary" id="validateCredentialsBtn">
                            <i class="bx bx-shield-check me-1"></i>
                            <span id="validateBtnText">Validate Game Credentials</span>
                        </button>
                        <div id="validationResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sponsor Information -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">
                    <i class="bx bx-user-check me-2 text-primary"></i>Sponsor Information
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success mb-0" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bx bx-user-check fs-20 me-2"></i>
                        <div class="flex-grow-1">
                            <strong>You are the sponsor</strong>
                            <div class="small mt-1 text-muted">
                                Sponsor: {{ $user->full_name }} ({{ $user->email }})<br>
                                Referral Code: <strong class="text-dark">{{ $user->referral_code }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                    <a href="{{ route('referrals.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-x me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bx bx-user-plus me-1"></i>
                        <span id="submitBtnText">Create Referral Account</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('script')
<script>
(function() {
    'use strict';

    // State management
    let credentialsValidated = false;
    let validationInProgress = false;

    // DOM elements
    const elements = {
        form: document.getElementById('createReferralForm'),
        username: document.getElementById('username'),
        usernameAvailability: document.getElementById('usernameAvailability'),
        gameUsername: document.getElementById('gameUsername'),
        gamePassword: document.getElementById('gamePassword'),
        gameUsernameAvailability: document.getElementById('gameUsernameAvailability'),
        toggleGamePassword: document.getElementById('toggleGamePassword'),
        validateBtn: document.getElementById('validateCredentialsBtn'),
        validateBtnText: document.getElementById('validateBtnText'),
        validationResult: document.getElementById('validationResult'),
        submitBtn: document.getElementById('submitBtn'),
        submitBtnText: document.getElementById('submitBtnText')
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initPasswordToggle();
        initUsernameCheck();
        initGameUsernameCheck();
        initCredentialValidation();
        initFormSubmission();
        
        // Reset validation state on page load (important for back button/errors)
        resetValidationState();
    });

    /**
     * Reset validation state
     */
    function resetValidationState() {
        credentialsValidated = false;
        validationInProgress = false;
        
        // Clear validation result
        if (elements.validationResult) {
            elements.validationResult.innerHTML = '';
        }

        // Remove validation classes from game fields
        if (elements.gameUsername) {
            elements.gameUsername.classList.remove('is-valid', 'is-invalid');
        }
        if (elements.gamePassword) {
            elements.gamePassword.classList.remove('is-valid', 'is-invalid');
        }
    }

    /**
     * Password toggle functionality
     */
    function initPasswordToggle() {
        if (elements.toggleGamePassword) {
            elements.toggleGamePassword.addEventListener('click', function() {
                const icon = this.querySelector('i');
                
                if (elements.gamePassword.type === 'password') {
                    elements.gamePassword.type = 'text';
                    icon.classList.remove('bx-hide');
                    icon.classList.add('bx-show');
                } else {
                    elements.gamePassword.type = 'password';
                    icon.classList.remove('bx-show');
                    icon.classList.add('bx-hide');
                }
            });
        }
    }

    /**
     * Username availability check
     */
    function initUsernameCheck() {
        if (!elements.username || !elements.usernameAvailability) return;

        let usernameTimeout;

        elements.username.addEventListener('input', function() {
            clearTimeout(usernameTimeout);
            const username = this.value.trim();

            // Clear previous messages
            elements.usernameAvailability.innerHTML = '';
            this.classList.remove('is-valid', 'is-invalid');

            if (username.length < 3) return;

            // Validate pattern
            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                elements.usernameAvailability.innerHTML = '<small class="text-danger"><i class="bx bx-x-circle"></i> Only letters, numbers, and underscores allowed</small>';
                this.classList.add('is-invalid');
                return;
            }

            usernameTimeout = setTimeout(() => {
                fetch(`{{ route('register.check-username') }}?username=${encodeURIComponent(username)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            elements.usernameAvailability.innerHTML = '<small class="text-success"><i class="bx bx-check-circle"></i> Username available</small>';
                            elements.username.classList.add('is-valid');
                        } else {
                            elements.usernameAvailability.innerHTML = `<small class="text-danger"><i class="bx bx-x-circle"></i> ${data.message}</small>`;
                            elements.username.classList.add('is-invalid');
                        }
                    })
                    .catch(error => {
                        console.error('Username check error:', error);
                    });
            }, 500);
        });
    }

    /**
     * Game username availability check
     */
    function initGameUsernameCheck() {
        if (!elements.gameUsername || !elements.gameUsernameAvailability) return;

        let gameUsernameTimeout;

        elements.gameUsername.addEventListener('input', function() {
            clearTimeout(gameUsernameTimeout);
            const username = this.value.trim();

            // Clear previous messages
            elements.gameUsernameAvailability.innerHTML = '';
            
            // Reset validation state when username changes
            resetValidationState();

            if (username.length < 10) return;

            gameUsernameTimeout = setTimeout(() => {
                fetch(`{{ route('referrals.check-game-username') }}?username=${encodeURIComponent(username)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            elements.gameUsernameAvailability.innerHTML = '<small class="text-success"><i class="bx bx-check-circle"></i> ' + data.message + '</small>';
                        } else {
                            elements.gameUsernameAvailability.innerHTML = '<small class="text-danger"><i class="bx bx-x-circle"></i> ' + data.message + '</small>';
                        }
                    })
                    .catch(error => {
                        console.error('Game username check error:', error);
                    });
            }, 500);
        });

        // Reset validation when game password changes
        if (elements.gamePassword) {
            elements.gamePassword.addEventListener('input', function() {
                resetValidationState();
            });
        }
    }

    /**
     * Game credential validation
     */
    function initCredentialValidation() {
        if (!elements.validateBtn) return;

        elements.validateBtn.addEventListener('click', function() {
            const gameUsername = elements.gameUsername.value.trim();
            const gamePassword = elements.gamePassword.value.trim();

            // Clear previous validation
            elements.validationResult.innerHTML = '';
            elements.gameUsername.classList.remove('is-valid', 'is-invalid');
            elements.gamePassword.classList.remove('is-valid', 'is-invalid');

            // Validate inputs
            if (!gameUsername || !gamePassword) {
                showValidationError('Please enter both game username and password');
                return;
            }

            if (gameUsername.length < 10) {
                showValidationError('Game username must be at least 10 characters');
                elements.gameUsername.classList.add('is-invalid');
                return;
            }

            if (gamePassword.length < 6) {
                showValidationError('Game password must be at least 6 characters');
                elements.gamePassword.classList.add('is-invalid');
                return;
            }

            // Prevent multiple simultaneous validations
            if (validationInProgress) {
                return;
            }

            validateCredentials(gameUsername, gamePassword);
        });
    }

    /**
     * Perform credential validation via AJAX
     */
    function validateCredentials(username, password) {
        validationInProgress = true;
        credentialsValidated = false;

        // Show loading state
        elements.validateBtn.disabled = true;
        elements.validateBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validating...';
        
        elements.validationResult.innerHTML = `
            <div class="alert alert-info">
                <i class="bx bx-loader-circle bx-spin me-2"></i>Validating game credentials, please wait...
            </div>
        `;

        // Make AJAX request
        fetch('{{ route('referrals.validate-game-credentials') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: username,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {
            validationInProgress = false;

            if (data.success) {
                credentialsValidated = true;
                elements.gameUsername.classList.remove('is-invalid');
                elements.gameUsername.classList.add('is-valid');
                elements.gamePassword.classList.remove('is-invalid');
                elements.gamePassword.classList.add('is-valid');
                
                elements.validationResult.innerHTML = `
                    <div class="alert alert-success">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-check-circle fs-20 me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Validation Successful!</strong>
                                <div class="small mt-1">Game username: ${data.data.uname}</div>
                                <div class="small">You can now create the referral account.</div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                credentialsValidated = false;
                elements.gameUsername.classList.add('is-invalid');
                elements.gamePassword.classList.add('is-invalid');
                
                elements.validationResult.innerHTML = `
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-error-circle fs-20 me-2"></i>
                            <div class="flex-grow-1">
                                <strong>Validation Failed</strong>
                                <div class="mt-1">${data.message}</div>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            validationInProgress = false;
            credentialsValidated = false;
            
            console.error('Validation error:', error);
            
            elements.validationResult.innerHTML = `
                <div class="alert alert-danger">
                    <div class="d-flex align-items-start">
                        <i class="bx bx-error-circle fs-20 me-2"></i>
                        <div class="flex-grow-1">
                            <strong>Validation Error</strong>
                            <div class="mt-1">An error occurred during validation. Please try again.</div>
                        </div>
                    </div>
                </div>
            `;
        })
        .finally(() => {
            // Reset button state
            elements.validateBtn.disabled = false;
            elements.validateBtnText.textContent = 'Validate Game Credentials';
        });
    }

    /**
     * Show validation error
     */
    function showValidationError(message) {
        elements.validationResult.innerHTML = `
            <div class="alert alert-warning">
                <div class="d-flex align-items-start">
                    <i class="bx bx-info-circle fs-20 me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                </div>
            </div>
        `;
    }

    /**
     * Form submission handling
     */
    function initFormSubmission() {
        if (!elements.form) return;

        elements.form.addEventListener('submit', function(e) {
            const gameUsername = elements.gameUsername.value.trim();
            const gamePassword = elements.gamePassword.value.trim();

            // Basic validation
            if (!gameUsername || !gamePassword) {
                e.preventDefault();
                alert('Please fill in all game account fields');
                return false;
            }

            if (gameUsername.length < 10) {
                e.preventDefault();
                alert('Game username must be at least 10 characters');
                return false;
            }

            if (gamePassword.length < 6) {
                e.preventDefault();
                alert('Game password must be at least 6 characters');
                return false;
            }

            // Warning if not validated
            if (!credentialsValidated) {
                e.preventDefault();
                
                if (confirm('Warning: You haven\'t validated the game credentials yet.\n\nThe account creation will fail if the credentials are incorrect.\n\nDo you want to continue anyway?')) {
                    // User confirmed, submit the form
                    submitForm();
                }
                return false;
            }

            // Disable submit button to prevent double submission
            disableSubmitButton();
        });
    }

    /**
     * Disable submit button
     */
    function disableSubmitButton() {
        if (elements.submitBtn) {
            elements.submitBtn.disabled = true;
            elements.submitBtnText.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating Account...';
        }
    }

    /**
     * Submit form programmatically
     */
    function submitForm() {
        disableSubmitButton();
        elements.form.submit();
    }

})();
</script>

<style>
/* Card styling */
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    border: 1px solid #e9ecef;
}

.card-header {
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.25rem;
}

.card-body {
    padding: 1.25rem;
}

/* Form styling */
.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: #495057;
}

.form-control:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-control.is-valid {
    border-color: #198754;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
}

/* Alert styling */
.alert {
    border-radius: 8px;
    border: none;
}

.alert-info {
    background-color: #cff4fc;
    color: #055160;
}

.alert-success {
    background-color: #d1e7dd;
    color: #0a3622;
}

.alert-danger {
    background-color: #f8d7da;
    color: #58151c;
}

.alert-warning {
    background-color: #fff3cd;
    color: #664d03;
}

/* Button styling */
.btn {
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

/* Responsive */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
    }
    
    .btn {
        width: 100%;
    }
}

/* Icon sizes */
.fs-20 {
    font-size: 1.25rem;
}

.fs-24 {
    font-size: 1.5rem;
}

/* Spinner animation */
@keyframes bx-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.bx-spin {
    animation: bx-spin 1s linear infinite;
}
</style>
@endsection