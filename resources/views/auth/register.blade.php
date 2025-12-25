@extends('layouts.auth', ['title' => 'Registration'])

@section('content')

    <div class="col-xl-5">
        <div class="card auth-card">
            <div class="card-body px-3 py-5">
                <div class="mx-auto mb-4 text-center auth-logo">
                    <a href="{{ route('home')}}" class="logo-dark">
                        <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                    </a>

                    <a href="{{ route('home')}}" class="logo-light">
                        <img src="/images/logo-light.png" height="60" alt="logo light" />
                    </a>
                </div>

                <h2 class="fw-bold text-center fs-18">
                    Sign Up
                </h2>
                <p class="text-muted text-center mt-1 mb-4">
                    New to our platform? Sign up now! It only
                    takes a minute.
                </p>

                @if(isset($sponsorId) && isset($sponsor) && $sponsorId && $sponsor)
                    <div class="alert alert-success mb-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-user-check fs-5 me-2"></i>
                            <div>
                                <strong>Sponsored by:</strong> {{ $sponsor->first_name }} {{ $sponsor->last_name }}
                                <br><small class="text-muted">You'll join {{ $sponsor->first_name }} {{ $sponsor->last_name }}'s
                                    network</small>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="px-4">
                    <form method="POST" action="{{ route('register') }}" class="authentication-form" id="registrationForm">
                        @csrf

                        <!-- Name Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="first_name">First Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="first_name" name="first_name"
                                        class="form-control @error('first_name') is-invalid @enderror"
                                        placeholder="Enter first name" value="{{ old('first_name') }}" required />
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="last_name">Last Name <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="last_name" name="last_name"
                                        class="form-control @error('last_name') is-invalid @enderror"
                                        placeholder="Enter last name" value="{{ old('last_name') }}" required />
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="username" name="username"
                                    class="form-control @error('username') is-invalid @enderror"
                                    placeholder="Choose unique username" value="{{ old('username') }}" required />
                                <span class="input-group-text" id="usernameStatus">
                                    <i class="bx bx-check text-success d-none" id="usernameAvailable"></i>
                                    <i class="bx bx-x text-danger d-none" id="usernameTaken"></i>
                                </span>
                            </div>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Only letters, numbers, dashes and underscores</small>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label class="form-label" for="email">Email <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror" placeholder="Enter your email"
                                value="{{ old('email') }}" required />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone Number -->
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" id="phone" name="phone"
                                class="form-control @error('phone') is-invalid @enderror" placeholder="+1234567890"
                                value="{{ old('phone') }}" required />
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="country">Country <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select @error('country') is-invalid @enderror" id="country"
                                        name="country" required>
                                        <option value="">Select Country</option>
                                        <option value="AF" {{ old('country') == 'AF' ? 'selected' : '' }}>Afghanistan</option>
                                        <option value="AL" {{ old('country') == 'AL' ? 'selected' : '' }}>Albania</option>
                                        <option value="DZ" {{ old('country') == 'DZ' ? 'selected' : '' }}>Algeria</option>
                                        <option value="AR" {{ old('country') == 'AR' ? 'selected' : '' }}>Argentina</option>
                                        <option value="AU" {{ old('country') == 'AU' ? 'selected' : '' }}>Australia</option>
                                        <option value="AT" {{ old('country') == 'AT' ? 'selected' : '' }}>Austria</option>
                                        <option value="BD" {{ old('country') == 'BD' ? 'selected' : '' }}>Bangladesh</option>
                                        <option value="BE" {{ old('country') == 'BE' ? 'selected' : '' }}>Belgium</option>
                                        <option value="BR" {{ old('country') == 'BR' ? 'selected' : '' }}>Brazil</option>
                                        <option value="CA" {{ old('country') == 'CA' ? 'selected' : '' }}>Canada</option>
                                        <option value="CN" {{ old('country') == 'CN' ? 'selected' : '' }}>China</option>
                                        <option value="FR" {{ old('country') == 'FR' ? 'selected' : '' }}>France</option>
                                        <option value="DE" {{ old('country') == 'DE' ? 'selected' : '' }}>Germany</option>
                                        <option value="IN" {{ old('country') == 'IN' ? 'selected' : '' }}>India</option>
                                        <option value="ID" {{ old('country') == 'ID' ? 'selected' : '' }}>Indonesia</option>
                                        <option value="IT" {{ old('country') == 'IT' ? 'selected' : '' }}>Italy</option>
                                        <option value="JP" {{ old('country') == 'JP' ? 'selected' : '' }}>Japan</option>
                                        <option value="MY" {{ old('country') == 'MY' ? 'selected' : '' }}>Malaysia</option>
                                        <option value="NL" {{ old('country') == 'NL' ? 'selected' : '' }}>Netherlands</option>
                                        <option value="NG" {{ old('country') == 'NG' ? 'selected' : '' }}>Nigeria</option>
                                        <option value="PK" {{ old('country') == 'PK' ? 'selected' : '' }}>Pakistan</option>
                                        <option value="PH" {{ old('country') == 'PH' ? 'selected' : '' }}>Philippines</option>
                                        <option value="RU" {{ old('country') == 'RU' ? 'selected' : '' }}>Russia</option>
                                        <option value="SA" {{ old('country') == 'SA' ? 'selected' : '' }}>Saudi Arabia
                                        </option>
                                        <option value="SG" {{ old('country') == 'SG' ? 'selected' : '' }}>Singapore</option>
                                        <option value="ZA" {{ old('country') == 'ZA' ? 'selected' : '' }}>South Africa
                                        </option>
                                        <option value="KR" {{ old('country') == 'KR' ? 'selected' : '' }}>South Korea</option>
                                        <option value="ES" {{ old('country') == 'ES' ? 'selected' : '' }}>Spain</option>
                                        <option value="LK" {{ old('country') == 'LK' ? 'selected' : '' }}>Sri Lanka</option>
                                        <option value="TH" {{ old('country') == 'TH' ? 'selected' : '' }}>Thailand</option>
                                        <option value="TR" {{ old('country') == 'TR' ? 'selected' : '' }}>Turkey</option>
                                        <option value="UA" {{ old('country') == 'UA' ? 'selected' : '' }}>Ukraine</option>
                                        <option value="AE" {{ old('country') == 'AE' ? 'selected' : '' }}>United Arab Emirates
                                        </option>
                                        <option value="GB" {{ old('country') == 'GB' ? 'selected' : '' }}>United Kingdom
                                        </option>
                                        <option value="US" {{ old('country') == 'US' ? 'selected' : '' }}>United States
                                        </option>
                                        <option value="VN" {{ old('country') == 'VN' ? 'selected' : '' }}>Vietnam</option>
                                    </select>
                                    @error('country')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label" for="city">City <span class="text-danger">*</span></label>
                                    <input type="text" id="city" name="city"
                                        class="form-control @error('city') is-invalid @enderror"
                                        placeholder="Enter your city" value="{{ old('city') }}" required />
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sponsor ID -->
                        <div class="mb-3">
                            <label class="form-label" for="sponsor_id">Sponsor Referral Code <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" id="sponsor_id" name="sponsor_id"
                                    class="form-control @error('sponsor_id') is-invalid @enderror"
                                    placeholder="Enter sponsor's referral code (required)"
                                    value="{{ old('sponsor_id', $sponsorId ?? '') }}" required />
                                <span class="input-group-text" id="sponsorStatus">
                                    <i class="bx bx-check text-success d-none" id="sponsorValid"></i>
                                    <i class="bx bx-x text-danger d-none" id="sponsorInvalid"></i>
                                </span>
                            </div>
                            @error('sponsor_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="sponsorMessage" class="form-text"></div>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" id="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Enter your password" required />
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bx bx-show"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label class="form-label" for="password_confirmation">Confirm Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-control" placeholder="Confirm your password" required />
                        </div>

                        <div class="mb-1 text-center d-grid">
                            <button class="btn btn-primary" type="submit" id="registerBtn">
                                <i class="bx bx-user-plus me-1"></i>Sign Up
                            </button>
                        </div>
                    </form>
                </div>
                <!-- end col -->
            </div>
            <!-- end card-body -->
        </div>
        <!-- end card -->

        <p class="text-white mb-0 text-center">
            I already have an account
<a href="{{ route('login')}}" class="text-white fw-bold ms-1">Sign In</a>
        </p>
    </div>

@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Username availability check
            const usernameInput = document.getElementById('username');
            const usernameAvailable = document.getElementById('usernameAvailable');
            const usernameTaken = document.getElementById('usernameTaken');
            let usernameTimeout;

            usernameInput.addEventListener('input', function () {
                clearTimeout(usernameTimeout);
                const username = this.value.trim();

                // Hide all status icons
                usernameAvailable.classList.add('d-none');
                usernameTaken.classList.add('d-none');

                if (username.length > 0) {
                    usernameTimeout = setTimeout(() => {
                        fetch(`{{ route('register.check-username') }}?username=${encodeURIComponent(username)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.available) {
                                    usernameAvailable.classList.remove('d-none');
                                    usernameTaken.classList.add('d-none');
                                } else {
                                    usernameAvailable.classList.add('d-none');
                                    usernameTaken.classList.remove('d-none');
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }, 500);
                }
            });

            // Sponsor ID validation
            const sponsorInput = document.getElementById('sponsor_id');
            const sponsorValid = document.getElementById('sponsorValid');
            const sponsorInvalid = document.getElementById('sponsorInvalid');
            const sponsorMessage = document.getElementById('sponsorMessage');
            let sponsorTimeout;

            sponsorInput.addEventListener('input', function () {
                clearTimeout(sponsorTimeout);
                const sponsorId = this.value.trim();

                // Hide all status icons
                sponsorValid.classList.add('d-none');
                sponsorInvalid.classList.add('d-none');
                sponsorMessage.textContent = '';

                if (sponsorId.length > 0) {
                    sponsorTimeout = setTimeout(() => {
                        fetch(`{{ route('register.check-sponsor') }}?sponsor_id=${encodeURIComponent(sponsorId)}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.valid && data.sponsor_name) {
                                    sponsorValid.classList.remove('d-none');
                                    sponsorInvalid.classList.add('d-none');
                                    sponsorMessage.textContent = data.message;
                                    sponsorMessage.className = 'form-text text-success';
                                } else {
                                    sponsorValid.classList.add('d-none');
                                    sponsorInvalid.classList.remove('d-none');
                                    sponsorMessage.textContent = data.message;
                                    sponsorMessage.className = 'form-text text-danger';
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }, 500);
                } else {
                    // Show error if sponsor referral code is empty (required field)
                    sponsorInvalid.classList.remove('d-none');
                    sponsorMessage.textContent = 'Sponsor referral code is required';
                    sponsorMessage.className = 'form-text text-danger';
                }
            });

            // Password toggle
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                const icon = this.querySelector('i');
                icon.classList.toggle('bx-show');
                icon.classList.toggle('bx-hide');
            });

            // Form submission
            const form = document.getElementById('registrationForm');
            const submitBtn = document.getElementById('registerBtn');

            form.addEventListener('submit', function () {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Creating Account...';
            });
        });
    </script>
@endsection