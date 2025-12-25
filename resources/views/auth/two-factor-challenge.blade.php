@extends('layouts.auth', ['title' => 'Two-Factor Authentication'])

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
                                <a href="{{ route('home')}}" class="logo-dark">
                                <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                            </a>

                            <a href="{{ route('home')}}" class="logo-light">
                                <img src="/images/logo-light.png" height="60" alt="logo light" />
                            </a>
                            </div>
                            
                            <div class="text-center mb-4">
                                <div class="avatar-md mx-auto">
                                    <div class="avatar-title rounded-circle bg-light">
                                        <i class="bx bx-shield-check h2 mb-0 text-primary"></i>
                                    </div>
                                </div>
                                <div class="p-2 mt-4">
                                    <h4>Two-Factor Authentication</h4>
                                    <p class="text-muted">Enter the 6-digit code from your authenticator app to continue.</p>
                                </div>
                            </div>
                            
                            <div class="row justify-content-center">
                                <div class="col-12 col-md-8">
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            @foreach ($errors->all() as $error)
                                                <p class="mb-0">{{ $error }}</p>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (session('error'))
                                        <div class="alert alert-danger">
                                            {{ session('error') }}
                                        </div>
                                    @endif

                                    @if (session('info'))
                                        <div class="alert alert-info">
                                            {{ session('info') }}
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('two-factor.verify') }}" class="authentication-form">
                                        @csrf

                                        <div class="mb-4">
                                            <label class="form-label" for="code">Authentication Code</label>
                                            <input type="text" 
                                                   id="code" 
                                                   name="code" 
                                                   class="form-control form-control-lg text-center @error('code') is-invalid @enderror" 
                                                   placeholder="000000" 
                                                   maxlength="6" 
                                                   required 
                                                   autocomplete="off"
                                                   autofocus
                                                   style="letter-spacing: 0.5em; font-size: 1.5rem;">
                                            @error('code')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                            <div class="form-text text-center mt-2">
                                                <i class="bx bx-info-circle me-1"></i>
                                                Open your authenticator app and enter the current 6-digit code
                                            </div>
                                        </div>

                                        <div class="mb-1 text-center d-grid">
                                            <button class="btn btn-success btn-lg" type="submit" id="verifyBtn">
                                                <i class="bx bx-shield-check me-1"></i>Verify Code
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Recovery Option -->
                                    <div class="text-center mt-4">
                                        <p class="text-muted mb-2">Lost access to your authenticator app?</p>
                                        <button type="button" class="btn btn-link text-decoration-none p-0" onclick="showRecoveryModal()">
                                            <i class="bx bx-key me-1"></i>Use Recovery Code
                                        </button>
                                    </div>

                                    <!-- Logout Option -->
                                    <div class="text-center mt-3">
                                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                <i class="bx bx-log-out me-1"></i>Logout & Try Again
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
            Having trouble? 
            <a href="mailto:support@yoursite.com" class="text-white fw-bold ms-1">Contact Support</a>
        </p>
    </div>

    <!-- Recovery Modal with Fixed Position -->
    <div id="recoveryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99999;">
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); width: 90%; max-width: 500px; z-index: 100000;">
            
            <!-- Modal Header -->
            <div style="padding: 20px 24px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0; font-size: 18px; font-weight: 600;">
                    <i class="bx bx-key me-2"></i>Use Recovery Code
                </h5>
                <button type="button" onclick="hideRecoveryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6c757d; padding: 0; width: 32px; height: 32px;">
                    &times;
                </button>
            </div>

            <!-- Modal Body -->
            <form action="{{ route('two-factor.recovery') }}" method="POST">
                @csrf
                <div style="padding: 24px;">
                    <p style="color: #6c757d; margin-bottom: 20px;">
                        Enter one of your 8-digit recovery codes to access your account.
                    </p>
                    
                    <div style="margin-bottom: 20px;">
                        <label for="recovery_code" class="form-label">Recovery Code</label>
                        <input type="text" 
                               class="form-control text-center" 
                               id="recovery_code" 
                               name="recovery_code" 
                               placeholder="ABCD-EFGH" 
                               maxlength="9"
                               style="letter-spacing: 0.2em; font-size: 16px; padding: 12px;"
                               required>
                        <div class="form-text" style="text-align: center; margin-top: 8px; color: #6c757d;">Format: XXXX-XXXX</div>
                    </div>

                    <div class="alert alert-warning" style="background-color: #fff3cd; border: 1px solid #ffecb5; color: #856404; padding: 12px; border-radius: 4px;">
                        <i class="bx bx-info-circle me-1"></i>
                        <strong>Note:</strong> Each recovery code can only be used once.
                    </div>
                </div>

                <!-- Modal Footer -->
                <div style="padding: 16px 24px; border-top: 1px solid #e9ecef; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn btn-light" onclick="hideRecoveryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check me-1"></i>Verify Recovery Code
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('script')
<script>
// Simple modal functions
function showRecoveryModal() {
    document.getElementById('recoveryModal').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent scrolling
    
    // Focus on recovery code input after a short delay
    setTimeout(function() {
        const input = document.getElementById('recovery_code');
        if (input) {
            input.focus();
        }
    }, 100);
}

function hideRecoveryModal() {
    document.getElementById('recoveryModal').style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scrolling
    
    // Clear the input
    const input = document.getElementById('recovery_code');
    if (input) {
        input.value = '';
    }
}

// Close modal when clicking outside
document.getElementById('recoveryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideRecoveryModal();
    }
});

// Close modal with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideRecoveryModal();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code');
    const verifyBtn = document.getElementById('verifyBtn');
    const recoveryInput = document.getElementById('recovery_code');
    
    // Format main code input
    codeInput.addEventListener('input', function(e) {
        // Remove non-numeric characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 6 digits
        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }
        
        // Enable/disable verify button
        verifyBtn.disabled = this.value.length !== 6;
    });

    // Auto-submit when 6 digits are entered
    codeInput.addEventListener('input', function(e) {
        if (this.value.length === 6) {
            setTimeout(() => {
                if (this.value.length === 6) {
                    this.form.submit();
                }
            }, 500);
        }
    });

    // Format recovery code input
    if (recoveryInput) {
        recoveryInput.addEventListener('input', function(e) {
            // Remove non-alphanumeric characters and convert to uppercase
            let value = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            
            // Add dash after 4 characters
            if (value.length > 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            
            this.value = value;
        });
    }

    // Initial state
    verifyBtn.disabled = codeInput.value.length !== 6;

    // Focus the main code input
    codeInput.focus();
});
</script>
@endsection