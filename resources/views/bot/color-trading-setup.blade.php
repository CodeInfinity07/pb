@extends('layouts.vertical', ['title' => 'Color Trading Setup', 'subTitle' => 'Bot'])

@section('content')

<div class="row">
    <div class="col-12 col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title d-flex align-items-center mb-0">
                    <iconify-icon icon="material-symbols:shield-with-check" class="me-2"></iconify-icon>
                    Color Trading Account Setup
                </h4>
            </div>
            <div class="card-body">
                <!-- Success Alert (Hidden by default) -->
                <div id="successAlert" class="alert alert-success alert-dismissible fade" role="alert" style="display: none;">
                    <iconify-icon icon="material-symbols:check-circle" class="me-2"></iconify-icon>
                    <span id="successMessage">Game account linked successfully!</span>
                    <button type="button" class="btn-close" onclick="hideAlert('successAlert')"></button>
                </div>

                <!-- Error Alert (Hidden by default) -->
                <div id="errorAlert" class="alert alert-danger alert-dismissible fade" role="alert" style="display: none;">
                    <iconify-icon icon="material-symbols:error" class="me-2"></iconify-icon>
                    <span id="errorMessage">Please check your credentials and try again.</span>
                    <button type="button" class="btn-close" onclick="hideAlert('errorAlert')"></button>
                </div>

                <!-- Account Setup Form -->
                <div id="setupForm">
                    <div class="mb-4">
                        <p class="text-muted">
                            Link your game account to start using the Color Trading Bot. 
                            Enter your game username (phone number) and password below.
                        </p>
                    </div>

                    <form id="linkAccountForm">
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label fw-medium">
                                Game Username <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username" 
                                placeholder="e.g. 3001234567" 
                                required
                            >
                            <div class="form-text">Enter your game account phone number</div>
                        </div>

                        <div class="mb-4">
                            <label for="pwd" class="form-label fw-medium">
                                Game Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="pwd" 
                                    name="pwd" 
                                    placeholder="Enter your game password" 
                                    required
                                >
                                <button 
                                    class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword()"
                                    id="passwordToggle"
                                >
                                    <iconify-icon icon="material-symbols:visibility" id="passwordIcon"></iconify-icon>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <button 
                                type="submit" 
                                class="btn btn-primary" 
                                id="validateBtn"
                            >
                                <iconify-icon icon="material-symbols:link" class="me-2"></iconify-icon>
                                <span id="validateText">Validate & Link Account</span>
                            </button>

                            <a href="{{ route('bot.index') }}" class="btn btn-outline-secondary">
                                <iconify-icon icon="material-symbols:arrow-back" class="me-2"></iconify-icon>
                                Back to Games
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Success State (Hidden by default) -->
                <div id="successState" class="text-center py-4" style="display: none;">
                    <div class="mb-4">
                        <iconify-icon icon="material-symbols:check-circle" class="text-success fs-1"></iconify-icon>
                    </div>
                    <h5 class="fw-medium mb-2">Account Linked Successfully!</h5>
                    <p class="text-muted mb-4">
                        Your game account "<span id="linkedUsername" class="fw-medium"></span>" is now connected.
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('bot.color-trading.game') }}" class="btn btn-success">
                            <iconify-icon icon="material-symbols:play-arrow" class="me-2"></iconify-icon>
                            Start Playing
                        </a>
                        <a href="{{ route('bot.index') }}" class="btn btn-outline-secondary">
                            <iconify-icon icon="material-symbols:home" class="me-2"></iconify-icon>
                            Back to Games
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Info Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="material-symbols:info" class="me-2"></iconify-icon>
                    Important Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <iconify-icon icon="material-symbols:security" class="text-primary mt-1"></iconify-icon>
                            <div>
                                <h6 class="mb-1">Secure Connection</h6>
                                <p class="text-muted small mb-0">Your credentials are encrypted and secure.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <iconify-icon icon="material-symbols:account-circle" class="text-primary mt-1"></iconify-icon>
                            <div>
                                <h6 class="mb-1">Game Account Required</h6>
                                <p class="text-muted small mb-0">You must have an existing game account.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <iconify-icon icon="material-symbols:verified-user" class="text-primary mt-1"></iconify-icon>
                            <div>
                                <h6 class="mb-1">KYC Verified</h6>
                                <p class="text-muted small mb-0">Your account is KYC verified and ready.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <iconify-icon icon="material-symbols:support" class="text-primary mt-1"></iconify-icon>
                            <div>
                                <h6 class="mb-1">24/7 Support</h6>
                                <p class="text-muted small mb-0">Get help anytime if you need assistance.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('linkAccountForm');
    const validateBtn = document.getElementById('validateBtn');
    const validateText = document.getElementById('validateText');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const pwd = document.getElementById('pwd').value.trim();

        if (!username || !pwd) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }

        if (username.length < 10) {
            showAlert('Username must be at least 10 characters long.', 'error');
            return;
        }

        if (pwd.length < 6) {
            showAlert('Password must be at least 6 characters long.', 'error');
            return;
        }

        setLoadingState(true);

        try {
            const response = await fetch('{{ route("bot.color-trading.link") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    pwd: pwd
                })
            });

            const result = await response.json();

            if (result.success) {
                showSuccessState(result.data.uname, result.data.umoney);
                showAlert('Game account linked successfully!', 'success');
            } else {
                if (result.errors) {
                    // Handle validation errors
                    const errorMessages = Object.values(result.errors).flat();
                    showAlert(errorMessages.join(' '), 'error');
                } else {
                    showAlert(result.message || 'Failed to link account. Please try again.', 'error');
                }
            }
        } catch (error) {
            console.error('Network error:', error);
            showAlert('Network error occurred. Please check your connection and try again.', 'error');
        } finally {
            setLoadingState(false);
        }
    });
});

function setLoadingState(loading) {
    const validateBtn = document.getElementById('validateBtn');
    const validateText = document.getElementById('validateText');
    const usernameInput = document.getElementById('username');
    const pwdInput = document.getElementById('pwd');

    if (loading) {
        validateBtn.disabled = true;
        validateText.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Validating...';
        usernameInput.disabled = true;
        pwdInput.disabled = true;
    } else {
        validateBtn.disabled = false;
        validateText.innerHTML = '<iconify-icon icon="material-symbols:link" class="me-2"></iconify-icon>Validate & Link Account';
        usernameInput.disabled = false;
        pwdInput.disabled = false;
    }
}

function showSuccessState(username, balance) {
    document.getElementById('setupForm').style.display = 'none';
    document.getElementById('successState').style.display = 'block';
    document.getElementById('linkedUsername').textContent = username;
    
    // Show balance if available
    if (balance && balance > 0) {
        const balanceSpan = document.createElement('p');
        balanceSpan.className = 'text-muted mb-4';
        balanceSpan.innerHTML = `Starting Balance: <span class="fw-medium text-success">${parseFloat(balance).toFixed(2)}</span>`;
        document.getElementById('linkedUsername').parentNode.appendChild(balanceSpan);
    }
    
    // Auto redirect after 4 seconds
    let countdown = 4;
    const redirectBtn = document.querySelector('a[href="{{ route("bot.color-trading.game") }}"]');
    const originalText = redirectBtn.innerHTML;
    
    const countdownInterval = setInterval(() => {
        redirectBtn.innerHTML = `<iconify-icon icon="material-symbols:play-arrow" class="me-2"></iconify-icon>Start Playing (${countdown}s)`;
        countdown--;
        
        if (countdown < 0) {
            clearInterval(countdownInterval);
            window.location.href = '{{ route("bot.color-trading.game") }}';
        }
    }, 1000);
}

function showAlert(message, type) {
    hideAllAlerts();
    
    const alertId = type === 'error' ? 'errorAlert' : 'successAlert';
    const messageId = type === 'error' ? 'errorMessage' : 'successMessage';
    
    document.getElementById(messageId).textContent = message;
    const alertElement = document.getElementById(alertId);
    alertElement.style.display = 'block';
    alertElement.classList.add('show');
    
    // Auto hide after 6 seconds
    setTimeout(() => {
        hideAlert(alertId);
    }, 6000);
}

function hideAlert(alertId) {
    const alertElement = document.getElementById(alertId);
    if (alertElement) {
        alertElement.classList.remove('show');
        setTimeout(() => {
            alertElement.style.display = 'none';
        }, 150);
    }
}

function hideAllAlerts() {
    hideAlert('errorAlert');
    hideAlert('successAlert');
}

function togglePassword() {
    const passwordInput = document.getElementById('pwd');
    const passwordIcon = document.getElementById('passwordIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordIcon.setAttribute('icon', 'material-symbols:visibility-off');
    } else {
        passwordInput.type = 'password';
        passwordIcon.setAttribute('icon', 'material-symbols:visibility');
    }
}
</script>
@endsection