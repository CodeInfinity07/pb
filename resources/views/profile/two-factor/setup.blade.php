@extends('layouts.vertical', ['title' => 'Two-Factor Authentication', 'subTitle' => 'Setup'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="bx bx-shield-plus me-2"></i>Enable Two-Factor Authentication
                </h4>
                <p class="card-title-desc mb-0">
                    Secure your account with two-factor authentication using Google Authenticator or similar apps.
                </p>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- QR Code Section -->
                    <div class="col-lg-6">
                        <div class="text-center mb-4">
                            <h5 class="mb-3">1. Scan QR Code</h5>
                            <div class="qr-code-container p-3 bg-light rounded">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" 
                                     alt="2FA QR Code" class="img-fluid">
                            </div>
                            <p class="mt-3 text-muted small">
                                Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)
                            </p>
                        </div>

                        <!-- Manual Entry Section -->
                        <div class="text-center">
                            <h6 class="mb-2">Manual Entry</h6>
                            <p class="text-muted small mb-2">If you can't scan the QR code, enter this secret key manually:</p>
                            <div class="alert alert-info">
                                <code class="fs-12">{{ $secret }}</code>
                                <button type="button" class="btn btn-sm btn-outline-primary ms-2" onclick="copyToClipboard('{{ $secret }}')">
                                    <i class="bx bx-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Section -->
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <h5 class="mb-3">2. Verify Setup</h5>
                            <form action="{{ route('user.two-factor.enable') }}" method="POST">
                                @csrf
                                
                                <div class="mb-3">
                                    <label for="code" class="form-label">Verification Code *</label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" 
                                           id="code" name="code" placeholder="Enter 6-digit code" 
                                           maxlength="6" required autocomplete="off">
                                    @error('code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Enter the 6-digit code from your authenticator app</div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bx bx-check me-1"></i>Enable 2FA
                                    </button>
                                    <a href="{{ route('user.profile') }}" class="btn btn-light">
                                        <i class="bx bx-arrow-back me-1"></i>Cancel
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Backup Codes Section -->
                        <div class="alert alert-warning">
                            <h6><i class="bx bx-info-circle me-1"></i>Important</h6>
                            <p class="mb-2 small">After enabling 2FA, you'll receive backup recovery codes. Store them safely!</p>
                            <ul class="mb-0 small">
                                <li>Use recovery codes if you lose access to your authenticator app</li>
                                <li>Each code can only be used once</li>
                                <li>Keep them in a secure location</li>
                            </ul>
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
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'toast show position-fixed top-0 end-0 m-3';
        toast.innerHTML = `
            <div class="toast-header bg-success text-white">
                <i class="bx bx-check me-2"></i>
                <strong class="me-auto">Copied!</strong>
            </div>
            <div class="toast-body">
                Secret key copied to clipboard
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Format 2FA code input
    const codeInput = document.getElementById('code');
    
    codeInput.addEventListener('input', function(e) {
        // Remove non-numeric characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 6 digits
        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }
    });
});
</script>
@endsection