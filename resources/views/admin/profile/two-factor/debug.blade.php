@extends('admin.layouts.vertical', ['title' => '2FA Debug', 'subTitle' => 'Debug'])

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">2FA Debug Information</h4>
            </div>
            <div class="card-body">
                @php
                    $user = Auth::user();
                    $google2fa = app('pragmarx.google2fa');
                    $secret = $user->getGoogle2FASecret();
                    $currentTime = now();
                    $timestamp = $currentTime->timestamp;
                @endphp
                
                <div class="row">
                    <div class="col-md-6">
                        <h5>Server Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <td><strong>Server Time:</strong></td>
                                <td>{{ $currentTime->format('Y-m-d H:i:s T') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Timezone:</strong></td>
                                <td>{{ config('app.timezone') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Unix Timestamp:</strong></td>
                                <td>{{ $timestamp }}</td>
                            </tr>
                            <tr>
                                <td><strong>Has 2FA Secret:</strong></td>
                                <td>{{ $secret ? 'Yes' : 'No' }}</td>
                            </tr>
                            @if($secret)
                            <tr>
                                <td><strong>Secret Length:</strong></td>
                                <td>{{ strlen($secret) }} characters</td>
                            </tr>
                            <tr>
                                <td><strong>Current Expected Code:</strong></td>
                                <td><code>{{ $google2fa->getCurrentOtp($secret) }}</code></td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Test Verification</h5>
                        <form method="POST" action="{{ route('debug.2fa.test') }}">
                            @csrf
                            
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if (session('debug_result'))
                                <div class="alert alert-info">
                                    <h6>Debug Result:</h6>
                                    <pre>{{ json_encode(session('debug_result'), JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                            
                            <div class="mb-3">
                                <label for="test_code" class="form-label">Enter Code from App:</label>
                                <input type="text" class="form-control" id="test_code" name="code" maxlength="6" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Test Verification</button>
                        </form>
                        
                        <hr>
                        
                        <h6>Manual Verification</h6>
                        <p class="small text-muted">Compare the codes above with what your authenticator app shows right now.</p>
                        
                        @if($secret)
                        <p><strong>Secret Key (for manual entry):</strong><br>
                        <code>{{ $secret }}</code>
                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $secret }}')">Copy</button>
                        </p>
                        
                        <p><strong>QR Code URL:</strong><br>
                        <small class="text-muted">{{ $google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret) }}</small>
                        </p>
                        @endif
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h5>Troubleshooting Steps</h5>
                        <div class="alert alert-warning">
                            <h6>Common Issues:</h6>
                            <ul class="mb-0">
                                <li><strong>Time Sync:</strong> Make sure your phone's time is automatically synced</li>
                                <li><strong>Code Timing:</strong> TOTP codes change every 30 seconds</li>
                                <li><strong>App Selection:</strong> Use Google Authenticator, Authy, or similar TOTP apps</li>
                                <li><strong>Manual Entry:</strong> If QR scan fails, manually enter the secret key above</li>
                            </ul>
                        </div>
                        
                        <div class="btn-group" role="group">
                            <a href="{{ route('user.two-factor.setup') }}" class="btn btn-secondary">
                                Back to Setup
                            </a>
                            <button type="button" class="btn btn-danger" onclick="resetSecret()">
                                Reset Secret & Start Over
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Secret copied to clipboard!');
    });
}

function resetSecret() {
    if (confirm('This will reset your 2FA secret and you will need to set it up again. Continue?')) {
        fetch('{{ route("debug.2fa.reset") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            }
        }).then(response => {
            if (response.ok) {
                alert('Secret reset! Redirecting to setup...');
                window.location.href = '{{ route("user.two-factor.setup") }}';
            }
        });
    }
}

// Auto-refresh current codes every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endsection