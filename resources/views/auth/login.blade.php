@extends('layouts.auth', ['title' => 'Login'])

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
                                <a href="{{ route( 'home') }}" class="logo-dark">
                                    <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                                </a>
                                <a href="{{ route( 'home') }}" class="logo-light">
                                    <img src="/images/logo-light.png" height="60" alt="logo light" />
                                </a>
                            </div>

                            <h2 class="fw-bold text-center fs-18">Sign In</h2>

                            <div class="row justify-content-center">
                                <div class="col-12 col-md-8">
                                    <form method="POST" action="{{ route('login') }}" class="authentication-form">
                                        @csrf

                                        @if (sizeof($errors) > 0)
                                            @foreach ($errors->all() as $error)
                                                <p class="text-danger mb-3">Incorrect Username or Password</p>
                                            @endforeach
                                        @endif

                                        <div class="mb-3">
                                            <label class="form-label" for="example-email">Email / Username</label>
                                            <input type="text" id="example-email" name="email" class="form-control"
                                                placeholder="Enter your email/username" required />
                                        </div>

                                        <div class="mb-3">
                                            <a href="{{ route('password.request') }}"
                                                class="float-end text-muted text-unline-dashed ms-1">
                                                Reset password
                                            </a>
                                            <label class="form-label" for="example-password">Password</label>
                                            <div class="input-group">
                                                <input type="password" id="example-password" name="password"
                                                    class="form-control" placeholder="Enter your password" required />
                                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                    <i class="bx bx-show" id="toggleIcon"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="checkbox-signin"
                                                    name="remember" />
                                                <label class="form-check-label" for="checkbox-signin">
                                                    Remember me
                                                </label>
                                            </div>
                                        </div>

                                        <div class="mb-1 text-center d-grid">
                                            <button class="btn btn-primary" type="submit">
                                                Sign In
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-white mb-0 text-center">
            Don't have an account?
            <a href="{{ route('register') }}" class="text-white fw-bold ms-1">Sign Up</a>
        </p>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('example-password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (togglePassword && passwordInput && toggleIcon) {
                togglePassword.addEventListener('click', function () {
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleIcon.classList.remove('bx-show');
                        toggleIcon.classList.add('bx-hide');
                    } else {
                        passwordInput.type = 'password';
                        toggleIcon.classList.remove('bx-hide');
                        toggleIcon.classList.add('bx-show');
                    }
                });
            }
        });
    </script>
@endsection