@extends('layouts.vertical', ['title' => 'Referral Created Successfully', 'subTitle' => 'Referrals'])

@section('content')
<div class="container-fluid">
    
    <!-- Success Header -->
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            
            <!-- Success Icon -->
            <div class="text-center mb-4">
                <div class="avatar-xl mx-auto mb-3">
                    <div class="avatar-title bg-success-subtle text-success rounded-circle">
                        <i class="bx bx-check-circle display-4"></i>
                    </div>
                </div>
                <h3 class="text-success mb-2">Referral Account Created Successfully!</h3>
                <p class="text-muted">Save these credentials and share them securely with your referral.</p>
            </div>

            <!-- Credentials Card -->
            <div class="card border-success mb-4">
                <div class="card-header bg-success-subtle border-success">
                    <h5 class="card-title text-success mb-0">
                        <i class="bx bx-lock-open me-2"></i>Account Credentials
                    </h5>
                </div>
                <div class="card-body">
                    
                    <!-- Important Notice -->
                    <div class="alert alert-warning mb-4" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bx bx-error-circle fs-20 me-2"></i>
                            <div class="flex-grow-1">
                                <strong>IMPORTANT:</strong> These credentials will only be shown once. 
                                Make sure to save them before leaving this page!
                            </div>
                        </div>
                    </div>

                    <!-- Credentials Grid -->
                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bx bx-user me-1"></i>Full Name
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-semibold" 
                                       value="{{ $successData['user_name'] }}" 
                                       readonly 
                                       id="userName">
                                <button class="btn btn-outline-secondary copy-btn" 
                                        type="button" 
                                        data-target="userName"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bx bx-at me-1"></i>Username
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-semibold" 
                                       value="{{ $successData['username'] }}" 
                                       readonly 
                                       id="username">
                                <button class="btn btn-outline-secondary copy-btn" 
                                        type="button" 
                                        data-target="username"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bx bx-envelope me-1"></i>Email Address
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-semibold" 
                                       value="{{ $successData['user_email'] }}" 
                                       readonly 
                                       id="userEmail">
                                <button class="btn btn-outline-secondary copy-btn" 
                                        type="button" 
                                        data-target="userEmail"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bx bx-phone me-1"></i>Phone Number
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-semibold" 
                                       value="{{ $successData['phone'] }}" 
                                       readonly 
                                       id="userPhone">
                                <button class="btn btn-outline-secondary copy-btn" 
                                        type="button" 
                                        data-target="userPhone"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Referral Code -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bx bx-link me-1"></i>Referral Code
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-semibold text-primary" 
                                       value="{{ $successData['referral_code'] }}" 
                                       readonly 
                                       id="referralCode">
                                <button class="btn btn-outline-secondary copy-btn" 
                                        type="button" 
                                        data-target="referralCode"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Game Username -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bx bx-joystick me-1"></i>Game Username
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-semibold text-info" 
                                       value="{{ $successData['game_username'] }}" 
                                       readonly 
                                       id="gameUsername">
                                <button class="btn btn-outline-secondary copy-btn" 
                                        type="button" 
                                        data-target="gameUsername"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Temporary Password - Full Width & Highlighted -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-danger small mb-2">
                                <i class="bx bx-key me-1"></i>Temporary Password
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control fw-bold fs-5 text-danger bg-danger-subtle border-danger" 
                                       value="{{ $successData['temp_password'] }}" 
                                       readonly 
                                       id="tempPassword">
                                <button class="btn btn-danger copy-btn" 
                                        type="button" 
                                        data-target="tempPassword"
                                        title="Copy to clipboard">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                            <div class="form-text text-danger mt-2">
                                <i class="bx bx-info-circle me-1"></i>
                                User must change this password on first login.
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                        <button class="btn btn-primary" id="copyAllBtn">
                            <i class="bx bx-copy-alt me-1"></i>Copy All Credentials
                        </button>
                        <button class="btn btn-success" id="printBtn">
                            <i class="bx bx-printer me-1"></i>Print Credentials
                        </button>
                        <button class="btn btn-info" id="downloadBtn">
                            <i class="bx bx-download me-1"></i>Download as Text
                        </button>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="card border-info mb-4">
                <div class="card-body">
                    <h6 class="card-title text-info mb-3">
                        <i class="bx bx-info-circle me-2"></i>Next Steps
                    </h6>
                    <ul class="mb-0">
                        <li class="mb-2">A verification email has been sent to <strong>{{ $successData['user_email'] }}</strong></li>
                        <li class="mb-2">Share the credentials securely with your referral</li>
                        <li class="mb-2">The user must verify their email before accessing their account</li>
                        <li class="mb-2">They will be required to change the temporary password on first login</li>
                        <li>The game account has been successfully linked</li>
                    </ul>
                </div>
            </div>

            <!-- Navigation Actions -->
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">What would you like to do next?</h6>
                    <div class="d-flex flex-column flex-md-row gap-3">
                        <a href="{{ route('referrals.index') }}" class="btn btn-lg btn-outline-primary flex-fill">
                            <i class="bx bx-list-ul me-2"></i>View All Referrals
                        </a>
                        <a href="{{ route('referrals.create-direct') }}" class="btn btn-lg btn-primary flex-fill">
                            <i class="bx bx-user-plus me-2"></i>Create Another Referral
                        </a>
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
    'use strict';

    /**
     * Copy text to clipboard
     */
    function copyToClipboard(elementId, buttonEl) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.error('Element not found:', elementId);
            return;
        }
        
        const text = element.value;
        
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = buttonEl.innerHTML;
            const originalClass = buttonEl.className;
            
            buttonEl.innerHTML = '<i class="bx bx-check"></i> Copied!';
            buttonEl.className = buttonEl.className.replace('btn-outline-secondary', 'btn-success').replace('btn-danger', 'btn-success');
            
            setTimeout(() => {
                buttonEl.innerHTML = originalHTML;
                buttonEl.className = originalClass;
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy to clipboard');
        });
    }

    /**
     * Copy all credentials to clipboard
     */
    function copyAllCredentials(buttonEl) {
        const credentials = {
            name: document.getElementById('userName')?.value || '',
            username: document.getElementById('username')?.value || '',
            email: document.getElementById('userEmail')?.value || '',
            phone: document.getElementById('userPhone')?.value || '',
            referralCode: document.getElementById('referralCode')?.value || '',
            gameUsername: document.getElementById('gameUsername')?.value || '',
            tempPassword: document.getElementById('tempPassword')?.value || ''
        };
        
        const text = `NEW REFERRAL ACCOUNT CREDENTIALS
========================================

Full Name: ${credentials.name}
Username: ${credentials.username}
Email: ${credentials.email}
Phone: ${credentials.phone}
Referral Code: ${credentials.referralCode}
Game Username: ${credentials.gameUsername}

TEMPORARY PASSWORD: ${credentials.tempPassword}

========================================
IMPORTANT NOTES:
- User must verify their email address
- User must change password on first login
- Share these credentials securely
========================================`;
        
        navigator.clipboard.writeText(text).then(() => {
            const originalHTML = buttonEl.innerHTML;
            buttonEl.innerHTML = '<i class="bx bx-check"></i> All Copied!';
            buttonEl.classList.remove('btn-primary');
            buttonEl.classList.add('btn-success');
            
            setTimeout(() => {
                buttonEl.innerHTML = originalHTML;
                buttonEl.classList.remove('btn-success');
                buttonEl.classList.add('btn-primary');
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy credentials to clipboard');
        });
    }

    /**
     * Download credentials as text file
     */
    function downloadCredentials(buttonEl) {
        const credentials = {
            name: document.getElementById('userName')?.value || '',
            username: document.getElementById('username')?.value || '',
            email: document.getElementById('userEmail')?.value || '',
            phone: document.getElementById('userPhone')?.value || '',
            referralCode: document.getElementById('referralCode')?.value || '',
            gameUsername: document.getElementById('gameUsername')?.value || '',
            tempPassword: document.getElementById('tempPassword')?.value || ''
        };
        
        const text = `NEW REFERRAL ACCOUNT CREDENTIALS
========================================

Full Name: ${credentials.name}
Username: ${credentials.username}
Email: ${credentials.email}
Phone: ${credentials.phone}
Referral Code: ${credentials.referralCode}
Game Username: ${credentials.gameUsername}

TEMPORARY PASSWORD: ${credentials.tempPassword}

========================================
IMPORTANT NOTES:
- User must verify their email address
- User must change password on first login
- Share these credentials securely
========================================

Generated on: ${new Date().toLocaleString()}`;
        
        const blob = new Blob([text], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `referral-credentials-${credentials.username}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        const originalHTML = buttonEl.innerHTML;
        buttonEl.innerHTML = '<i class="bx bx-check"></i> Downloaded!';
        buttonEl.classList.remove('btn-info');
        buttonEl.classList.add('btn-success');
        
        setTimeout(() => {
            buttonEl.innerHTML = originalHTML;
            buttonEl.classList.remove('btn-success');
            buttonEl.classList.add('btn-info');
        }, 3000);
    }

    // Attach event listeners to all copy buttons
    document.querySelectorAll('.copy-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const targetId = this.getAttribute('data-target');
            if (targetId) {
                copyToClipboard(targetId, this);
            }
        });
    });

    // Attach event listener to copy all button
    const copyAllBtn = document.getElementById('copyAllBtn');
    if (copyAllBtn) {
        copyAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            copyAllCredentials(this);
        });
    }

    // Attach event listener to print button
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.print();
        });
    }

    // Attach event listener to download button
    const downloadBtn = document.getElementById('downloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            downloadCredentials(this);
        });
    }
});
</script>

<style>
/* Print styles */
@media print {
    .btn, .card-header, .alert-warning {
        display: none !important;
    }
    
    .card {
        border: 2px solid #000 !important;
        page-break-inside: avoid;
    }
    
    .input-group input {
        border: none !important;
        font-size: 14pt !important;
    }
}

/* Enhanced credential display */
.bg-danger-subtle {
    background-color: #f8d7da !important;
}

.border-danger {
    border-color: #dc3545 !important;
}

/* Copy button animation */
.btn i.bx-check {
    animation: checkmark 0.3s ease-in-out;
}

@keyframes checkmark {
    0% { transform: scale(0); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Responsive improvements */
@media (max-width: 768px) {
    .btn-lg {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
}
</style>
@endsection