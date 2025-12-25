@extends('layouts.vertical', ['title' => 'KYC Verification Complete', 'subTitle' => 'Documents Submitted'])

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-5">
                        <!-- Status Badge -->
                        <div class="mb-4">
                            <span class="badge bg-info-subtle text-info fs-14 px-4 py-2 rounded-pill">
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                                Under Review
                            </span>
                        </div>

                        <!-- Processing Time Info -->
                        <div class="row justify-content-center mb-4">
                            <div class="col-md-8">
                                <div class="card bg-info-subtle border-0">
                                    <div class="card-body text-center p-4">
                                        <iconify-icon icon="iconamoon:clock-duotone" class="fs-48 text-info mb-3"></iconify-icon>
                                        <h6 class="mb-2 text-info">Estimated Processing Time</h6>
                                        <h5 class="mb-2 text-info">5 minutes - 24 hours</h5>
                                        <small class="text-muted">Most verifications are completed within minutes</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Session Reference -->
                        @if(isset($sessionId) && $sessionId)
                            <div class="alert alert-info border-0 d-inline-block mb-4" role="alert">
                                <iconify-icon icon="iconamoon:document-duotone" class="me-2"></iconify-icon>
                                <strong>Reference ID:</strong> {{ $sessionId }}
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button type="button" id="close-window-btn" class="btn btn-primary btn-lg px-5 py-3 rounded-pill">
                                Close Window
                            </button>
                        </div>

                        <!-- Additional Info -->
                        <div class="mt-4">
                            <small class="text-muted d-block mb-2">
                                <iconify-icon icon="iconamoon:shield-check-duotone" class="me-1"></iconify-icon>
                                Your documents are processed securely using bank-level encryption
                            </small>
                            <small class="text-muted">
                                <iconify-icon icon="iconamoon:mail-duotone" class="me-1"></iconify-icon>
                                Check your email for verification updates
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Container -->
        <div id="alert-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;"></div>
    </div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeCompletePage();
});

function initializeCompletePage() {
    // Setup event listeners
    setupEventListeners();
    
    // Notify parent window about completion
    notifyParentWindow();
    
    // Auto-close after delay (optional)
    // Uncomment the following lines if you want auto-close after 10 seconds
    // setTimeout(() => {
    //     closeWindow();
    // }, 10000);
}

function setupEventListeners() {
    // Close window button
    const closeBtn = document.getElementById('close-window-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeWindow();
        });
    }
    
    // View status button - opens main KYC page in parent window
    const statusBtn = document.getElementById('view-status-btn');
    if (statusBtn) {
        statusBtn.addEventListener('click', function() {
            // If this is a popup, try to redirect parent window
            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.location.href = '{{ route('kyc.index') }}';
                    closeWindow();
                } catch (e) {
                    // If cross-origin, just show alert
                    showAlert('info', 'Please check the main window for your verification status.');
                }
            } else {
                // If not a popup, redirect current window
                window.location.href = '{{ route('kyc.index') }}';
            }
        });
    }
    
    // Listen for escape key to close window
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeWindow();
        }
    });
    
    // Handle browser back/forward
    window.addEventListener('beforeunload', function() {
        notifyParentWindow();
    });
}

function closeWindow() {
    // Notify parent window before closing
    notifyParentWindow();
    
    // Show closing message
    showAlert('info', 'Closing verification window...');
    
    // Small delay for visual feedback
    setTimeout(() => {
        // Try different methods to close the window
        if (window.opener && !window.opener.closed) {
            // This is a popup window
            window.close();
        } else if (window.parent !== window) {
            // This is in a frame/iframe
            try {
                window.parent.postMessage({
                    type: 'VERIFICATION_COMPLETE',
                    action: 'CLOSE_FRAME'
                }, '*');
            } catch (e) {
                console.log('Could not send message to parent frame');
            }
        } else {
            // Standalone window - redirect to main KYC page
            window.location.href = '{{ route('kyc.index') }}';
        }
    }, 500);
}

function notifyParentWindow() {
    try {
        // Method 1: PostMessage to parent window (for popups)
        if (window.opener && !window.opener.closed) {
            window.opener.postMessage({
                type: 'VERIFICATION_COMPLETE',
                status: 'submitted',
                message: 'Documents submitted successfully',
                timestamp: new Date().toISOString()
            }, '*');
        }
        
        // Method 2: PostMessage to parent frame (for iframes)
        if (window.parent !== window) {
            window.parent.postMessage({
                type: 'VERIFICATION_COMPLETE',
                status: 'submitted',
                message: 'Documents submitted successfully',
                timestamp: new Date().toISOString()
            }, '*');
        }
        
        // Method 3: Custom event (fallback)
        if (window.parent && window.parent.document) {
            const event = new CustomEvent('verificationComplete', {
                detail: {
                    status: 'submitted',
                    message: 'Documents submitted successfully'
                }
            });
            window.parent.document.dispatchEvent(event);
        }
    } catch (error) {
        console.log('Could not notify parent window:', error);
    }
}

function showAlert(type, message) {
    const container = document.getElementById('alert-container');
    if (!container) return;
    
    // Remove existing alerts
    container.innerHTML = '';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow-sm border-0`;
    
    const iconMap = {
        'success': 'iconamoon:check-circle-duotone',
        'danger': 'iconamoon:warning-duotone',
        'info': 'iconamoon:info-circle-duotone',
        'warning': 'iconamoon:alert-duotone'
    };
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-start">
            <iconify-icon icon="${iconMap[type] || iconMap.info}" class="me-2 mt-1 flex-shrink-0"></iconify-icon>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    container.appendChild(alertDiv);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            try {
                const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                alert.close();
            } catch (e) {
                alertDiv.remove();
            }
        }
    }, 4000);
}

// Add some visual feedback on load
window.addEventListener('load', function() {
    // Add entrance animation
    document.querySelector('.card').style.opacity = '0';
    document.querySelector('.card').style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        document.querySelector('.card').style.transition = 'all 0.5s ease';
        document.querySelector('.card').style.opacity = '1';
        document.querySelector('.card').style.transform = 'translateY(0)';
    }, 100);
    
    // Show success message
    setTimeout(() => {
        showAlert('success', 'Your documents have been submitted successfully!');
    }, 800);
});

// Handle focus for better UX
window.addEventListener('focus', function() {
    // Refresh close button focus
    const closeBtn = document.getElementById('close-window-btn');
    if (closeBtn) {
        closeBtn.focus();
    }
});
</script>

<style>
/* Custom animations */
@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.3;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.1;
    }
}

.animate-pulse {
    animation: pulse 2s infinite;
}

/* Avatar size for extra large */
.avatar-xxl {
    width: 6rem;
    height: 6rem;
}

/* Smooth transitions */
.card {
    transition: all 0.3s ease;
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Focus styles */
.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    outline: none;
}

/* Custom scrollbar for better look */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
@endsection