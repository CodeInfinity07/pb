@extends('admin.layouts.vertical', ['title' => 'Recovery Codes', 'subTitle' => '2FA'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="bx bx-key me-2"></i>Two-Factor Recovery Codes
                </h4>
            </div>
            <div class="card-body">
                <!-- Important Warning -->
                <div class="alert alert-warning border-0">
                    <div class="d-flex align-items-start">
                        <div>
                            <h6 class="mb-1">Important Security Information</h6>
                            <ul class="mb-0 small">
                                <li>Each recovery code can only be used <strong>once</strong></li>
                                <li>Store these codes in a <strong>secure location</strong> (password manager, safe, etc.)</li>
                                <li>Do not share these codes with anyone</li>
                                <li>Generate new codes if these are compromised</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Recovery Codes -->
                <div class="row">
                    <div class="col-12">
                        <div class="bg-light p-4 rounded">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Your Recovery Codes</h5>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="downloadCodes()">
                                        <i class="bx bx-download me-1"></i>Download
                                    </button>
                                </div>
                            </div>
                            
                            <div id="recovery-codes-container">
                                <div class="row" id="codes-grid">
                                    @foreach($recoveryCodes as $index => $code)
                                        <div class="col-md-6 col-xl-3 mb-2">
                                            <div class="code-item p-2 bg-white border rounded text-center position-relative">
                                                <code class="fs-14 fw-bold">{{ $code }}</code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-0 end-0 m-1" 
                                                        onclick="copyCode('{{ $code }}')" style="--bs-btn-padding-y: .15rem; --bs-btn-padding-x: .3rem;">
                                                    <i class="bx bx-copy fs-12"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-warning me-2" data-bs-toggle="modal" data-bs-target="#regenerateCodesModal">
                                    <i class="bx bx-refresh me-1"></i>Generate New Codes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How to Use -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bx bx-help-circle me-2"></i>How to Use Recovery Codes
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="bx bx-shield-check me-1"></i>When to Use</h6>
                        <ul class="text-muted">
                            <li>Lost your authenticator device</li>
                            <li>Device is broken or not working</li>
                            <li>Can't access your authenticator app</li>
                            <li>Getting a new phone</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="bx bx-list-ol me-1"></i>Steps to Use</h6>
                        <ol class="text-muted">
                            <li>Go to the login page</li>
                            <li>Enter your email and password</li>
                            <li>Click "Use Recovery Code"</li>
                            <li>Enter one of these codes</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Regenerate Codes Modal -->
<div class="modal fade" id="regenerateCodesModal" tabindex="-1" aria-labelledby="regenerateCodesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="regenerateCodesModalLabel">
                    <i class="bx bx-refresh me-2"></i>Generate New Recovery Codes
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="bx bx-error-circle me-2"></i>
                    <strong>Warning!</strong> Generating new codes will invalidate all current recovery codes.
                </div>
                
                <p class="text-muted">
                    Are you sure you want to generate new recovery codes? This action cannot be undone.
                </p>
                
                <form action="{{ route('user.two-factor.recovery.regenerate') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirmPassword" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmCode" class="form-label">2FA Code</label>
                        <input type="text" class="form-control" id="confirmCode" name="code" 
                               placeholder="Enter 6-digit code" maxlength="6" required>
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning">
                    <i class="bx bx-refresh me-1"></i>Generate New Codes
                </button>
            </div>
                </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Recovery codes data
    const recoveryCodes = @json($recoveryCodes);
    
    // Copy individual code
    window.copyCode = function(code) {
        navigator.clipboard.writeText(code).then(function() {
            showToast('Code copied!', 'Code copied to clipboard', 'success');
        }).catch(function() {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = code;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('Code copied!', 'Code copied to clipboard', 'success');
        });
    };
    
    // Copy all codes
    window.copyAllCodes = function() {
        const allCodes = recoveryCodes.join('\n');
        navigator.clipboard.writeText(allCodes).then(function() {
            showToast('All codes copied!', 'All recovery codes copied to clipboard', 'success');
        });
    };
    
    // Download codes as text file
    window.downloadCodes = function() {
        const content = `{{ config('app.name') }} - Recovery Codes\n` +
                       `Generated: ${new Date().toLocaleString()}\n` +
                       `\nKeep these codes safe!\n\n` +
                       recoveryCodes.join('\n') +
                       `\n\nNote: Each code can only be used once.`;
        
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = '{{ config("app.name") }}-recovery-codes-{{ date("Y-m-d") }}.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showToast('Download started!', 'Recovery codes downloaded', 'success');
    };
    
    // Print codes
    window.printCodes = function() {
        const printWindow = window.open('', '_blank');
        const content = `
            <html>
                <head>
                    <title>Recovery Codes - {{ config('app.name') }}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .codes { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 20px 0; }
                        .code { padding: 10px; border: 1px solid #ddd; text-align: center; font-family: monospace; font-size: 16px; }
                        .warning { background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 20px 0; }
                        @media print { .no-print { display: none; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>{{ config('app.name') }}</h2>
                        <h3>Two-Factor Authentication Recovery Codes</h3>
                        <p>Generated: ${new Date().toLocaleString()}</p>
                    </div>
                    
                    <div class="warning">
                        <strong>Important:</strong>
                        <ul>
                            <li>Each recovery code can only be used once</li>
                            <li>Store these codes in a secure location</li>
                            <li>Do not share these codes with anyone</li>
                        </ul>
                    </div>
                    
                    <div class="codes">
                        ${recoveryCodes.map(code => `<div class="code">${code}</div>`).join('')}
                    </div>
                    
                    <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
                        Print this page and store it in a safe place
                    </div>
                </body>
            </html>
        `;
        
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    };
    
    // Toast notification function
    function showToast(title, message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast show border-0`;
        toast.innerHTML = `
            <div class="toast-header bg-${type === 'success' ? 'success' : 'primary'} text-white border-0">
                <i class="bx bx-${type === 'success' ? 'check' : 'info-circle'} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }
    
    // Format 2FA code input in modal
    const codeInput = document.getElementById('confirmCode');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 6);
        });
    }
});
</script>
@endsection