@extends('layouts.auth', ['title' => 'Verify Your Email'])

@section('content')

    <div class="col-xl-5">
        <div class="card auth-card">
            <div class="card-body px-3 py-5 text-center">
                <div class="mx-auto mb-4 auth-logo">
                    <a href="{{ route('home')}}" class="logo-dark">
                        <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                    </a>

                    <a href="{{ route('home')}}" class="logo-light">
                        <img src="/images/logo-light.png" height="60" alt="logo light" />
                    </a>
                </div>

                <div class="mb-4">
                    <i class="bx bx-envelope-open text-primary" style="font-size: 4rem;"></i>
                </div>

                <h2 class="fw-bold fs-18 mb-3">Verify Your Email</h2>
                <p class="text-muted mb-4">
                    We've sent a verification link to <strong>{{ auth()->user()->email }}</strong>.
                    Please check your inbox and click the link to activate your account.
                </p>

                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success">
                        <i class="bx bx-check-circle me-2"></i>
                        A new verification link has been sent to your email address.
                    </div>
                @endif

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
                    <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100" id="resendBtn">
                            <i class="bx bx-refresh me-1"></i> Resend Verification Email
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="bx bx-log-out me-1"></i> Use Different Account
                        </button>
                    </form>
                </div>

                <div class="mt-4">
                    <p class="text-muted small mb-2">
                        Didn't receive the email? Check your spam folder.
                    </p>
                    <p class="text-muted small">
                        Having trouble? <a href="mailto:support@yoursite.com" class="text-primary">Contact Support</a>
                    </p>
                </div>
            </div>
        </div>

        <p class="text-white mb-0 text-center">
            Need to update your email?
            <a href="{{ route('user.profile') }}" class="text-white fw-bold ms-1">Change Email</a>
        </p>
    </div>

@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const resendForm = document.getElementById('resendForm');
            const resendBtn = document.getElementById('resendBtn');
            let resendCooldown = false;

            resendForm.addEventListener('submit', function (e) {
                if (resendCooldown) {
                    e.preventDefault();
                    return;
                }

                resendCooldown = true;
                resendBtn.disabled = true;
                resendBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Sending...';

                // Re-enable after 30 seconds
                setTimeout(() => {
                    resendCooldown = false;
                    resendBtn.disabled = false;
                    resendBtn.innerHTML = '<i class="bx bx-refresh me-1"></i> Resend Verification Email';
                }, 30000);
            });

            // Auto-check for verification every 10 seconds
            let checkInterval = setInterval(() => {
                fetch('{{ route("verification.check") }}', {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.verified) {
                            clearInterval(checkInterval);
                            window.location.href = '{{ route("dashboard") }}';
                        }
                    })
                    .catch(error => {
                        // Silent fail - user can manually refresh
                    });
            }, 10000);
        });
    </script>
@endsection