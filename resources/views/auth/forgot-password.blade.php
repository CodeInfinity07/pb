@extends('layouts.auth', ['title' => 'Password'])

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
                            Reset Password
                        </h2>
                        <p class="text-muted text-center mt-1 mb-4">
                            Enter your email address and
                            we'll send you an email with
                            instructions to reset your
                            password.
                        </p>

                        {{-- Display success message --}}
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

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
                                <form method="POST" action="{{ route('password.email') }}" class="authentication-form">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               class="form-control @error('email') is-invalid @enderror" 
                                               placeholder="Enter your email" 
                                               value="{{ old('email') }}" 
                                               required autofocus />
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="mb-1 text-center d-grid">
                                        <button class="btn btn-primary" type="submit">
                                            Send Password Reset Link
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