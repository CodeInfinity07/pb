@extends('layouts.auth', ['title' => 'Reset Password'])

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
                        <h2 class="fw-bold text-center fs-18">
                            Reset Your Password
                        </h2>
                        <p class="text-muted text-center mt-1 mb-4">
                            Enter your new password below.
                        </p>

                        {{-- Display validation errors --}}
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="row justify-content-center">
                            <div class="col-12 col-md-8">
                                <form method="POST" action="{{ route('password.store') }}" class="authentication-form">
                                    @csrf
                                    
                                    <!-- Hidden token field -->
                                    <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               value="{{ old('email', $request->email) }}" 
                                               required readonly />
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="password">New Password</label>
                                        <input type="password" 
                                               id="password" 
                                               name="password" 
                                               class="form-control @error('password') is-invalid @enderror" 
                                               placeholder="Enter new password" 
                                               required autofocus />
                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                                        <input type="password" 
                                               id="password_confirmation" 
                                               name="password_confirmation" 
                                               class="form-control" 
                                               placeholder="Confirm new password" 
                                               required />
                                    </div>

                                    <div class="mb-1 text-center d-grid">
                                        <button class="btn btn-primary" type="submit">
                                            Reset Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->
                    </div>
                </div>
                <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end card-body -->
    </div>
    <!-- end card -->

    <p class="text-white mb-0 text-center">
        Back to
        <a href="{{ route('login') }}" class="text-white fw-bold ms-1">Sign In</a>
    </p>
</div>

@endsection