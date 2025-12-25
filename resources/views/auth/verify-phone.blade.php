@extends('layouts.auth', ['title' => 'Verify Your Phone'])

@push('head')
    <!-- Ensure CSRF token is available -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

<div class="col-xl-5">
    <div class="card auth-card">
        <div class="card-body px-3 py-5 text-center">
            @if(!$phone_verified)
                <!-- Hidden CSRF token for JavaScript access -->
                <input type="hidden" name="_token" value="{{ csrf_token() }}" id="csrf-token">
                
                <div class="mb-4">
                    <i class="bx bxl-whatsapp text-success" style="font-size: 4rem;"></i>
                </div>

                <h2 class="fw-bold fs-18 mb-3">Verify Your Phone</h2>
                <p class="text-muted mb-4">
                    Send a WhatsApp message with the verification code to complete your phone verification.
                </p>

                <!-- Phone Number Display -->
                <div class="mb-4 p-3 bg-light rounded border">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Your Phone Number</small>
                            <span class="fw-bold fs-16" id="current-phone">{{ $phone }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePhoneModal">
                            <i class="bx bx-edit me-1"></i> Change
                        </button>
                    </div>
                </div>

                <!-- WhatsApp Number Display -->
                <div class="mb-4 p-3 bg-success bg-opacity-10 rounded border border-success border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-success d-block">Send Message To</small>
                            <span class="fw-bold fs-16 text-success">+{{ $whatsapp_number }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="copyToClipboard('+{{ $whatsapp_number }}', 'WhatsApp number')">
                            <i class="bx bx-copy me-1"></i> Copy
                        </button>
                    </div>
                </div>

                <!-- Timer -->
                <div class="mb-4 p-3 rounded border-2" id="timer-container">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bx bx-time me-2 fs-5" id="timer-icon"></i>
                        <span class="fw-bold fs-4" id="timer-display">10:00</span>
                        <button type="button" class="btn btn-sm btn-outline-warning ms-3 d-none" id="refresh-btn" onclick="refreshCode()">
                            <i class="bx bx-refresh me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Verification Code -->
                <div class="mb-4">
                    <label class="form-label fw-semibold text-start w-100">Verification Code:</label>
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-fill p-3 bg-primary bg-opacity-10 rounded border border-primary border-opacity-25 text-center">
                            <span class="fw-bold fs-3 text-primary" id="verification-code">{{ $verification_code }}</span>
                        </div>
                        <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard(document.getElementById('verification-code').textContent, 'Verification code')" title="Copy code">
                            <i class="bx bx-copy"></i>
                        </button>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="bx bx-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        <i class="bx bx-error-circle me-2"></i>
                        {{ session('error') }}
                    </div>
                @endif

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success w-100" id="whatsapp-btn" onclick="openWhatsApp()">
                        <i class="bx bxl-whatsapp me-1"></i> Open WhatsApp
                    </button>

                    <button type="button" class="btn btn-primary w-100" id="verify-btn" onclick="verifyCode()">
                        <i class="bx bx-check-circle me-1"></i> 
                        <span id="verify-btn-text">I have sent the message</span>
                    </button>

                    <button type="button" class="btn btn-outline-secondary w-100" id="refresh-code-btn" onclick="refreshCode()">
                        <i class="bx bx-refresh me-1"></i> Generate New Code
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bx bx-log-out me-1"></i> Use Different Account
                        </button>
                    </form>
                </div>

            @else
                <!-- Already Verified -->
                <div class="mb-4">
                    <i class="bx bx-check-circle text-success" style="font-size: 4rem;"></i>
                </div>

                <h2 class="fw-bold fs-18 mb-3 text-success">Phone Verified!</h2>
                <p class="text-muted mb-4">
                    Your phone number <strong>{{ $phone }}</strong> has been successfully verified.
                </p>

                <div class="d-grid">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary w-100">
                        <i class="bx bx-home me-1"></i> Go to Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>

    @if(!$phone_verified)
        <p class="text-white mb-0 text-center">
            Need to update your phone number?
            <a href="#" class="text-white fw-bold ms-1" data-bs-toggle="modal" data-bs-target="#changePhoneModal">Change Phone</a>
        </p>
    @endif
</div>

<!-- Change Phone Modal -->
<div class="modal fade" id="changePhoneModal" tabindex="-1" aria-labelledby="changePhoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="change-phone-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="changePhoneModalLabel">Change Phone Number</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new-phone" class="form-label">New Phone Number</label>
                        <input type="tel" class="form-control" id="new-phone" name="phone" value="{{ $phone }}" placeholder="Enter phone number (e.g., +923001234567)">
                        <div class="invalid-feedback" id="phone-error"></div>
                    </div>
                    <div class="text-muted">
                        <small>Please enter your phone number with country code (e.g., +923001234567 or 03001234567)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="save-phone-btn">
                        <span id="save-phone-text">Save Phone Number</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Helper function to get CSRF token
function getCSRFToken() {
    // Try to get from meta tag first
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        return metaTag.getAttribute('content');
    }
    
    // Try to get from Laravel's global variable
    if (typeof window.Laravel !== 'undefined' && window.Laravel.csrfToken) {
        return window.Laravel.csrfToken;
    }
    
    // Try to get from a hidden input field
    const hiddenInput = document.querySelector('input[name="_token"]');
    if (hiddenInput) {
        return hiddenInput.value;
    }
    
    // Last resort - try to get from session
    return '{{ csrf_token() }}';
}
const WHATSAPP_NUMBER = '{{ $whatsapp_number }}';
let timeLeft = 600; // 10 minutes in seconds
let timerInterval;
let hasExpired = false;
let refreshCooldown = false;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if code expires_at exists and calculate remaining time
    @if($code_expires_at)
        try {
            const expiresAt = new Date('{{ $code_expires_at }}');
            const now = new Date();
            timeLeft = Math.max(0, Math.floor((expiresAt - now) / 1000));
            
            console.log('Expires at:', expiresAt);
            console.log('Current time:', now);
            console.log('Time left:', timeLeft, 'seconds');
        } catch (error) {
            console.log('Error parsing expiration time:', error);
            timeLeft = 600; // Default to 10 minutes
        }
    @else
        console.log('No expiration time provided, using default 10 minutes');
        timeLeft = 600; // Default to 10 minutes
    @endif
    
    // If time has already expired, mark it as expired
    if (timeLeft <= 0) {
        hasExpired = true;
        timeLeft = 0;
    }
    
    startTimer();
    
    // Auto-check verification status every 10 seconds
    let checkInterval = setInterval(() => {
        checkVerificationStatus();
    }, 10000);

    // Stop checking if user leaves page
    window.addEventListener('beforeunload', () => {
        clearInterval(checkInterval);
    });
});

// Timer functions
function startTimer() {
    updateTimerDisplay();
    
    if (timerInterval) {
        clearInterval(timerInterval);
    }
    
    timerInterval = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();
        
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            hasExpired = true;
            updateTimerDisplay();
        }
    }, 1000);
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    const timeString = hasExpired ? 'EXPIRED' : `${minutes}:${seconds.toString().padStart(2, '0')}`;
    
    const timerDisplay = document.getElementById('timer-display');
    const timerContainer = document.getElementById('timer-container');
    const timerIcon = document.getElementById('timer-icon');
    const refreshBtn = document.getElementById('refresh-btn');
    const whatsappBtn = document.getElementById('whatsapp-btn');
    
    timerDisplay.textContent = timeString;
    
    if (hasExpired) {
        timerContainer.className = 'mb-4 p-3 rounded border-2 border-danger bg-danger bg-opacity-10';
        timerIcon.className = 'bx bx-time me-2 fs-5 text-danger';
        timerDisplay.className = 'fw-bold fs-4 text-danger';
        refreshBtn.classList.remove('d-none');
        whatsappBtn.disabled = true;
        whatsappBtn.classList.add('disabled');
    } else if (timeLeft < 60) {
        timerContainer.className = 'mb-4 p-3 rounded border-2 border-warning bg-warning bg-opacity-10';
        timerIcon.className = 'bx bx-time me-2 fs-5 text-warning';
        timerDisplay.className = 'fw-bold fs-4 text-warning';
    } else {
        timerContainer.className = 'mb-4 p-3 rounded border-2 border-success bg-success bg-opacity-10';
        timerIcon.className = 'bx bx-time me-2 fs-5 text-success';
        timerDisplay.className = 'fw-bold fs-4 text-success';
    }
}

// Copy to clipboard function
async function copyToClipboard(text, type) {
    try {
        await navigator.clipboard.writeText(text);
        showToast(`${type} copied!`, 'success');
    } catch (err) {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showToast(`${type} copied!`, 'success');
    }
}

// Open WhatsApp
function openWhatsApp() {
    if (hasExpired) {
        showToast('Verification code has expired. Please refresh and try again.', 'danger');
        return;
    }
    
    const code = document.getElementById('verification-code').textContent;
    const message = `Verification code: ${code}`;
    const whatsappUrl = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
    
    window.open(whatsappUrl, '_blank');
    showToast('WhatsApp opened. Send the message and return here to confirm.', 'primary');
}

// Verify code
async function verifyCode() {
    if (hasExpired) {
        showToast('Verification code has expired. Please refresh and try again.', 'danger');
        return;
    }
    
    const verifyBtn = document.getElementById('verify-btn');
    const verifyBtnText = document.getElementById('verify-btn-text');
    
    verifyBtn.disabled = true;
    verifyBtnText.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Verifying...';
    
    try {
        const response = await fetch('{{ route("phone.verify.submit") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.href = data.redirect || '{{ route("dashboard") }}';
            }, 1500);
        } else {
            showToast(data.message, 'danger');
        }
    } catch (error) {
        showToast('Verification failed. Please try again.', 'danger');
    } finally {
        verifyBtn.disabled = false;
        verifyBtnText.innerHTML = 'I have sent the message';
    }
}

// Refresh code
async function refreshCode() {
    if (refreshCooldown) {
        showToast('Please wait before generating a new code.', 'warning');
        return;
    }

    // Check if CSRF token is available
    const csrfToken = getCSRFToken();
    if (!csrfToken) {
        console.error('CSRF token not found');
        showToast('Security token not found. Please refresh the page.', 'danger');
        return;
    }

    refreshCooldown = true;
    const refreshCodeBtn = document.getElementById('refresh-code-btn');
    const refreshBtn = document.getElementById('refresh-btn');
    
    // Disable both refresh buttons
    if (refreshCodeBtn) {
        refreshCodeBtn.disabled = true;
        refreshCodeBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Generating...';
    }
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Generating...';
    }

    try {
        console.log('Sending request to generate new code...');
        console.log('CSRF Token:', csrfToken.substring(0, 10) + '...');
        
        const response = await fetch('{{ route("phone.generate-code") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP Error Response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}, body: ${errorText}`);
        }
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            document.getElementById('verification-code').textContent = data.code;
            timeLeft = 600; // Reset to 10 minutes
            hasExpired = false;
            
            // Hide the timer refresh button
            if (refreshBtn) {
                refreshBtn.classList.add('d-none');
            }
            
            // Re-enable WhatsApp button
            const whatsappBtn = document.getElementById('whatsapp-btn');
            if (whatsappBtn) {
                whatsappBtn.disabled = false;
                whatsappBtn.classList.remove('disabled');
            }
            
            clearInterval(timerInterval);
            startTimer();
            
            showToast('New verification code generated successfully!', 'success');
        } else {
            console.error('Server returned error:', data);
            showToast(data.message || 'Failed to generate new code', 'danger');
        }
    } catch (error) {
        console.error('Error generating new code:', error);
        if (error.message.includes('CSRF')) {
            showToast('Security token expired. Please refresh the page.', 'danger');
        } else if (error.message.includes('Network')) {
            showToast('Network error. Please check your connection and try again.', 'danger');
        } else {
            showToast('Error: ' + error.message, 'danger');
        }
    } finally {
        // Re-enable buttons
        if (refreshCodeBtn) {
            refreshCodeBtn.disabled = false;
            refreshCodeBtn.innerHTML = '<i class="bx bx-refresh me-1"></i> Generate New Code';
        }
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="bx bx-refresh me-1"></i> Refresh';
        }
        
        // Re-enable refresh after 30 seconds
        setTimeout(() => {
            refreshCooldown = false;
            console.log('Refresh cooldown lifted');
        }, 30000);
    }
}

// Check verification status
async function checkVerificationStatus() {
    try {
        const response = await fetch('{{ route("phone.check-status") }}', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.message_received) {
            showToast('Message received! Verification completed.', 'success');
            setTimeout(() => {
                window.location.href = '{{ route("dashboard") }}';
            }, 1500);
        }
    } catch (error) {
        // Silent fail - user can manually refresh
    }
}

// Change phone form
document.getElementById('change-phone-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const saveBtn = document.getElementById('save-phone-btn');
    const saveBtnText = document.getElementById('save-phone-text');
    const phoneInput = document.getElementById('new-phone');
    const phoneError = document.getElementById('phone-error');
    
    saveBtn.disabled = true;
    saveBtnText.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...';
    phoneError.textContent = '';
    phoneInput.classList.remove('is-invalid');
    
    try {
        const response = await fetch('{{ route("phone.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify({
                phone: phoneInput.value
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('current-phone').textContent = data.phone;
            // Close modal using Bootstrap 5 method
            const modal = document.getElementById('changePhoneModal');
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
            showToast(data.message, 'success');
            
            // Refresh the page to get new verification code
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            phoneError.textContent = data.message;
            phoneInput.classList.add('is-invalid');
        }
    } catch (error) {
        phoneError.textContent = 'An error occurred. Please try again.';
        phoneInput.classList.add('is-invalid');
    } finally {
        saveBtn.disabled = false;
        saveBtnText.textContent = 'Save Phone Number';
    }
});

// Toastify notification function
function showToast(message, type) {
    // Map types to Toastify classes
    const typeMap = {
        'success': 'success',
        'danger': 'danger',
        'error': 'danger',
        'warning': 'warning',
        'info': 'primary',
        'primary': 'primary'
    };
    
    const className = typeMap[type] || 'primary';
    
    // Use Toastify directly
    if (typeof Toastify !== 'undefined') {
        Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: "right",
            className: className,
            close: true,
            style: {
                background: className === 'success' ? '#198754' : 
                          className === 'danger' ? '#dc3545' : 
                          className === 'warning' ? '#ffc107' : 
                          '#0d6efd'
            }
        }).showToast();
    } else {
        // Fallback to alert if Toastify is not available
        alert(message);
    }
}
</script>
@endsection