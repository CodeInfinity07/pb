@extends('layouts.vertical', ['title' => 'Edit', 'subTitle' => 'Profile'])

@section('content')
<div class="container-fluid">
    
    <!-- Mobile-First Header -->
    <div class="row mb-3 mb-md-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <h4 class="mb-1">Edit Profile</h4>
                    <p class="text-muted mb-0 small">Manage your account settings and preferences</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bx bx-x-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bx bx-error me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Profile Header Card -->
    <div class="card mb-3 mb-md-4">
        <div class="card-body p-3 p-md-4">
            <div class="row align-items-center">
                <div class="col-auto">
                    <div class="position-relative">
                        <div class="avatar-lg rounded-circle overflow-hidden border border-light border-3 shadow-sm">
                            <img src="{{ $user->profile->avatar ? asset('storage/' . $user->profile->avatar) : '/images/users/avatar-1.jpg' }}" 
                                 alt="{{ $user->first_name }}" class="w-100 h-100 object-cover" id="avatarPreview" />
                        </div>
                        <label for="avatar" class="btn btn-primary btn-sm position-absolute bottom-0 end-0 rounded-circle shadow-sm" 
                               style="transform: translate(25%, 25%);" data-bs-toggle="tooltip" data-bs-title="Change Avatar">
                            <i class="bx bx-camera"></i>
                            <input type="file" id="avatar" name="avatar" class="d-none" accept="image/*" form="profileForm">
                        </label>
                    </div>
                </div>
                <div class="col ps-3">
                    <h5 class="mb-1">{{ $user->first_name }} {{ $user->last_name }}</h5>
                    <p class="text-muted mb-2 small">TL - {{ $user->profile->level ?? '0' }}</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-light text-dark border small">
                            <i class="bx bx-user me-1"></i>{{ ucfirst(str_replace('_', ' ', $user->status)) }}
                        </span>
                        @if($user->email_verified_at)
                            <span class="badge bg-success-subtle text-success small">
                                <i class="bx bx-check-circle me-1"></i>Verified
                            </span>
                        @endif
                        @if($user->hasTwoFactorEnabled())
                            <span class="badge bg-success-subtle text-success small">
                                <i class="bx bx-shield-check me-1"></i>2FA
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Form -->
    <form action="{{ route('user.profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab_source" value="general">
        <input type="hidden" name="preferred_language" value="{{ old('preferred_language', $user->profile->preferred_language ?? 'en') }}">
        <input type="hidden" name="timezone" value="{{ old('timezone', $user->profile->timezone ?? 'UTC') }}">
        <input type="hidden" name="email_notifications" value="{{ old('email_notifications', $user->profile->email_notifications) ? '1' : '0' }}">
        <input type="hidden" name="sms_notifications" value="{{ old('sms_notifications', $user->profile->sms_notifications) ? '1' : '0' }}">
        
        <!-- Personal Information Section -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-user me-2 text-primary"></i>Personal Information
                        </h5>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="firstName" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="lastName" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username', $user->username) }}" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       id="dateOfBirth" name="date_of_birth" 
                                       value="{{ old('date_of_birth', $user->profile->date_of_birth) }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $user->profile->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ old('gender', $user->profile->gender) == 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="other" {{ old('gender', $user->profile->gender) == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('gender')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-phone me-2 text-primary"></i>Contact Information
                        </h5>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(!$user->email_verified_at)
                                    <div class="form-text text-warning small">
                                        <i class="bx bx-info-circle"></i> Email verification pending
                                    </div>
                                @endif
                            </div>
                            
                            <div class="col-12">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(!$user->profile->phone_verified)
                                    <div class="form-text text-warning small">
                                        <i class="bx bx-info-circle"></i> Phone verification pending
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information Section -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bx bx-map me-2 text-primary"></i>Address Information
                        </h5>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="country" class="form-label">Country *</label>
                                <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="AF" {{ old('country', $user->profile->country) == 'AF' ? 'selected' : '' }}>Afghanistan</option>
                                    <option value="AL" {{ old('country', $user->profile->country) == 'AL' ? 'selected' : '' }}>Albania</option>
                                    <option value="DZ" {{ old('country', $user->profile->country) == 'DZ' ? 'selected' : '' }}>Algeria</option>
                                    <option value="AD" {{ old('country', $user->profile->country) == 'AD' ? 'selected' : '' }}>Andorra</option>
                                    <option value="AO" {{ old('country', $user->profile->country) == 'AO' ? 'selected' : '' }}>Angola</option>
                                    <option value="AR" {{ old('country', $user->profile->country) == 'AR' ? 'selected' : '' }}>Argentina</option>
                                    <option value="AM" {{ old('country', $user->profile->country) == 'AM' ? 'selected' : '' }}>Armenia</option>
                                    <option value="AU" {{ old('country', $user->profile->country) == 'AU' ? 'selected' : '' }}>Australia</option>
                                    <option value="AT" {{ old('country', $user->profile->country) == 'AT' ? 'selected' : '' }}>Austria</option>
                                    <option value="AZ" {{ old('country', $user->profile->country) == 'AZ' ? 'selected' : '' }}>Azerbaijan</option>
                                    <option value="BD" {{ old('country', $user->profile->country) == 'BD' ? 'selected' : '' }}>Bangladesh</option>
                                    <option value="BE" {{ old('country', $user->profile->country) == 'BE' ? 'selected' : '' }}>Belgium</option>
                                    <option value="BR" {{ old('country', $user->profile->country) == 'BR' ? 'selected' : '' }}>Brazil</option>
                                    <option value="CA" {{ old('country', $user->profile->country) == 'CA' ? 'selected' : '' }}>Canada</option>
                                    <option value="CN" {{ old('country', $user->profile->country) == 'CN' ? 'selected' : '' }}>China</option>
                                    <option value="FR" {{ old('country', $user->profile->country) == 'FR' ? 'selected' : '' }}>France</option>
                                    <option value="DE" {{ old('country', $user->profile->country) == 'DE' ? 'selected' : '' }}>Germany</option>
                                    <option value="IN" {{ old('country', $user->profile->country) == 'IN' ? 'selected' : '' }}>India</option>
                                    <option value="ID" {{ old('country', $user->profile->country) == 'ID' ? 'selected' : '' }}>Indonesia</option>
                                    <option value="IT" {{ old('country', $user->profile->country) == 'IT' ? 'selected' : '' }}>Italy</option>
                                    <option value="JP" {{ old('country', $user->profile->country) == 'JP' ? 'selected' : '' }}>Japan</option>
                                    <option value="MY" {{ old('country', $user->profile->country) == 'MY' ? 'selected' : '' }}>Malaysia</option>
                                    <option value="NL" {{ old('country', $user->profile->country) == 'NL' ? 'selected' : '' }}>Netherlands</option>
                                    <option value="NG" {{ old('country', $user->profile->country) == 'NG' ? 'selected' : '' }}>Nigeria</option>
                                    <option value="PK" {{ old('country', $user->profile->country) == 'PK' ? 'selected' : '' }}>Pakistan</option>
                                    <option value="PH" {{ old('country', $user->profile->country) == 'PH' ? 'selected' : '' }}>Philippines</option>
                                    <option value="RU" {{ old('country', $user->profile->country) == 'RU' ? 'selected' : '' }}>Russia</option>
                                    <option value="SA" {{ old('country', $user->profile->country) == 'SA' ? 'selected' : '' }}>Saudi Arabia</option>
                                    <option value="SG" {{ old('country', $user->profile->country) == 'SG' ? 'selected' : '' }}>Singapore</option>
                                    <option value="ZA" {{ old('country', $user->profile->country) == 'ZA' ? 'selected' : '' }}>South Africa</option>
                                    <option value="KR" {{ old('country', $user->profile->country) == 'KR' ? 'selected' : '' }}>South Korea</option>
                                    <option value="ES" {{ old('country', $user->profile->country) == 'ES' ? 'selected' : '' }}>Spain</option>
                                    <option value="LK" {{ old('country', $user->profile->country) == 'LK' ? 'selected' : '' }}>Sri Lanka</option>
                                    <option value="TH" {{ old('country', $user->profile->country) == 'TH' ? 'selected' : '' }}>Thailand</option>
                                    <option value="TR" {{ old('country', $user->profile->country) == 'TR' ? 'selected' : '' }}>Turkey</option>
                                    <option value="UA" {{ old('country', $user->profile->country) == 'UA' ? 'selected' : '' }}>Ukraine</option>
                                    <option value="AE" {{ old('country', $user->profile->country) == 'AE' ? 'selected' : '' }}>United Arab Emirates</option>
                                    <option value="GB" {{ old('country', $user->profile->country) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="US" {{ old('country', $user->profile->country) == 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="VN" {{ old('country', $user->profile->country) == 'VN' ? 'selected' : '' }}>Vietnam</option>
                                </select>
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="city" name="city" value="{{ old('city', $user->profile->city) }}" required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="stateProvince" class="form-label">State/Province</label>
                                <input type="text" class="form-control @error('state_province') is-invalid @enderror" 
                                       id="stateProvince" name="state_province" 
                                       value="{{ old('state_province', $user->profile->state_province) }}">
                                @error('state_province')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="postalCode" class="form-label">Postal Code</label>
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                       id="postalCode" name="postal_code" 
                                       value="{{ old('postal_code', $user->profile->postal_code) }}">
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" name="address" rows="2">{{ old('address', $user->profile->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-grid d-md-flex gap-2 justify-content-md-end">
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="bx bx-refresh me-1"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Security & Preferences - Mobile Responsive Cards -->
    <div class="row g-3 g-md-4">
        
        <!-- Security Settings -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-shield me-2 text-primary"></i>Security Settings
                    </h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    
                    <!-- Change Password Form -->
                    <div class="mb-4">
                        <h6 class="mb-3">
                            <i class="bx bx-lock-alt me-2 text-primary"></i>Change Password
                        </h6>
                        
                        <form id="passwordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="current_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                        <i class="bx bx-hide" id="toggleCurrentIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="new_password" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                        <i class="bx bx-hide" id="toggleNewIcon"></i>
                                    </button>
                                </div>
                                <div class="form-text small">
                                    Password must be at least 8 characters long
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm New Password *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirmation" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bx bx-hide" id="toggleConfirmIcon"></i>
                                    </button>
                                </div>
                                <div id="passwordMatchIndicator" class="form-text mt-1"></div>
                            </div>

                            <!-- Password Strength -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">Password Strength:</small>
                                    <small id="strengthText" class="text-muted">Enter password</small>
                                </div>
                                <div class="progress" style="height: 4px;">
                                    <div id="strengthBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div class="d-grid d-md-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Update Password
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetPasswordForm()">
                                    <i class="bx bx-refresh me-1"></i>Reset
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Two-Factor Authentication -->
                    <div class="border-top pt-4">
                        <h6 class="mb-3">
                            <i class="bx bx-shield-check me-2 text-primary"></i>Two-Factor Authentication
                        </h6>
                        
                        @if($user->hasTwoFactorEnabled())
                            <div class="alert alert-success d-flex align-items-start mb-3" role="alert">
                                <i class="bx bx-check-circle fs-18 me-2 mt-1"></i>
                                <div>
                                    <strong>2FA is enabled</strong><br>
                                    <small class="text-muted">Your account is secured with two-factor authentication.</small>
                                </div>
                            </div>
                            
                            <div class="d-grid d-md-flex gap-2">
                                <a href="{{ route('user.two-factor.recovery') }}" class="btn btn-outline-primary">
                                    <i class="bx bx-key me-1"></i>View Recovery Codes
                                </a>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disable2FAModal">
                                    <i class="bx bx-shield-x me-1"></i>Disable 2FA
                                </button>
                            </div>
                        @else
                            <div class="alert alert-warning d-flex align-items-start mb-3" role="alert">
                                <i class="bx bx-shield-x fs-18 me-2 mt-1"></i>
                                <div>
                                    <strong>2FA is disabled</strong><br>
                                    <small class="text-muted">Enhance your account security by enabling two-factor authentication.</small>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <a href="{{ route('user.two-factor.setup') }}" class="btn btn-primary">
                                    <i class="bx bx-shield-plus me-1"></i>Enable 2FA
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Preferences -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-cog me-2 text-primary"></i>Preferences
                    </h5>
                </div>
                <div class="card-body p-3 p-md-4">
                    <form action="{{ route('user.profile.update') }}" method="POST" id="preferencesForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="first_name" value="{{ $user->first_name }}">
                        <input type="hidden" name="last_name" value="{{ $user->last_name }}">
                        <input type="hidden" name="username" value="{{ $user->username }}">
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <input type="hidden" name="phone" value="{{ $user->phone }}">
                        <input type="hidden" name="country" value="{{ $user->profile->country }}">
                        <input type="hidden" name="city" value="{{ $user->profile->city }}">
                        <input type="hidden" name="tab_source" value="preferences">
                        
                        <!-- Notifications -->
                        <div class="mb-4">
                            <h6 class="mb-3">
                                <i class="bx bx-bell me-2 text-primary"></i>Notifications
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded mb-2">
                                <div>
                                    <div class="fw-semibold">Email Notifications</div>
                                    <small class="text-muted">Receive notifications via email</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" 
                                           name="email_notifications" value="1" 
                                           {{ old('email_notifications', $user->profile->email_notifications) ? 'checked' : '' }}>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div>
                                    <div class="fw-semibold">SMS Notifications</div>
                                    <small class="text-muted">Receive notifications via SMS</small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="smsNotifications" 
                                           name="sms_notifications" value="1"
                                           {{ old('sms_notifications', $user->profile->sms_notifications) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>

                        <!-- Language & Region -->
                        <div class="border-top pt-4">
                            <h6 class="mb-3">
                                <i class="bx bx-globe me-2 text-primary"></i>Language & Region
                            </h6>
                            
                            <div class="mb-3">
                                <label for="preferredLanguage" class="form-label">Preferred Language</label>
                                <select class="form-select" id="preferredLanguage" name="preferred_language">
                                    <option value="en" {{ old('preferred_language', $user->profile->preferred_language) == 'en' ? 'selected' : '' }}>English</option>
                                    <option value="es" {{ old('preferred_language', $user->profile->preferred_language) == 'es' ? 'selected' : '' }}>Spanish</option>
                                    <option value="fr" {{ old('preferred_language', $user->profile->preferred_language) == 'fr' ? 'selected' : '' }}>French</option>
                                    <option value="de" {{ old('preferred_language', $user->profile->preferred_language) == 'de' ? 'selected' : '' }}>German</option>
                                    <option value="ja" {{ old('preferred_language', $user->profile->preferred_language) == 'ja' ? 'selected' : '' }}>Japanese</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select" id="timezone" name="timezone">
                                    <option value="UTC" {{ old('timezone', $user->profile->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    <option value="America/New_York" {{ old('timezone', $user->profile->timezone) == 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                    <option value="America/Chicago" {{ old('timezone', $user->profile->timezone) == 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                    <option value="America/Denver" {{ old('timezone', $user->profile->timezone) == 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                    <option value="America/Los_Angeles" {{ old('timezone', $user->profile->timezone) == 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                    <option value="Europe/London" {{ old('timezone', $user->profile->timezone) == 'Europe/London' ? 'selected' : '' }}>London</option>
                                    <option value="Europe/Paris" {{ old('timezone', $user->profile->timezone) == 'Europe/Paris' ? 'selected' : '' }}>Paris</option>
                                    <option value="Asia/Tokyo" {{ old('timezone', $user->profile->timezone) == 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Submit Preferences -->
                        <div class="border-top pt-3">
                            <div class="d-grid d-md-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Save Preferences
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetPreferencesForm()">
                                    <i class="bx bx-refresh me-1"></i>Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Disable 2FA Modal -->
@if($user->hasTwoFactorEnabled())
<div class="modal fade" id="disable2FAModal" tabindex="-1" aria-labelledby="disable2FAModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('user.two-factor.disable') }}" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="modal-header">
                    <h5 class="modal-title" id="disable2FAModalLabel">
                        <i class="bx bx-shield-x me-2"></i>Disable Two-Factor Authentication
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Warning!</strong> Disabling 2FA will make your account less secure.
                    </div>
                    
                    <div class="mb-3">
                        <label for="disablePassword" class="form-label">Current Password *</label>
                        <input type="password" class="form-control" id="disablePassword" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="disableCode" class="form-label">2FA Verification Code *</label>
                        <input type="text" class="form-control" id="disableCode" name="code" 
                               placeholder="Enter 6-digit code" maxlength="6" required>
                        <div class="form-text">Enter the code from your authenticator app.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bx bx-shield-x me-1"></i>Disable 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Avatar preview
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Password form handling
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all password fields');
                return;
            }
            
            if (newPassword.length < 8) {
                alert('New password must be at least 8 characters long');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New password and confirmation do not match');
                return;
            }
            
            // Create and submit form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("user.profile.password") }}';
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            const methodOverride = document.createElement('input');
            methodOverride.type = 'hidden';
            methodOverride.name = '_method';
            methodOverride.value = 'PUT';
            form.appendChild(methodOverride);
            
            const fields = [
                { name: 'current_password', value: currentPassword },
                { name: 'password', value: newPassword },
                { name: 'password_confirmation', value: confirmPassword }
            ];
            
            fields.forEach(field => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = field.name;
                input.value = field.value;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        });
    }

    // 2FA code input formatting
    const codeInputs = document.querySelectorAll('input[name="code"]');
    codeInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 6) {
                this.value = this.value.slice(0, 6);
            }
        });
    });

    // Password toggle functionality
    function setupPasswordToggle(toggleId, inputId, iconId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        
        if (toggle && input && icon) {
            toggle.addEventListener('click', function() {
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                icon.className = type === 'password' ? 'bx bx-hide' : 'bx bx-show';
            });
        }
    }

    setupPasswordToggle('toggleCurrentPassword', 'current_password', 'toggleCurrentIcon');
    setupPasswordToggle('toggleNewPassword', 'new_password', 'toggleNewIcon');
    setupPasswordToggle('toggleConfirmPassword', 'password_confirmation', 'toggleConfirmIcon');

    // Password strength checker
    const passwordInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrength(strength);
        });
    }

    // Password confirmation matching
    const confirmInput = document.getElementById('password_confirmation');
    const matchIndicator = document.getElementById('passwordMatchIndicator');
    
    if (passwordInput && confirmInput && matchIndicator) {
        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length === 0) {
                matchIndicator.textContent = '';
                matchIndicator.className = 'form-text mt-1';
                return;
            }
            
            if (password === confirm) {
                matchIndicator.textContent = '✓ Passwords match';
                matchIndicator.className = 'form-text mt-1 text-success';
            } else {
                matchIndicator.textContent = '✗ Passwords do not match';
                matchIndicator.className = 'form-text mt-1 text-danger';
            }
        }
        
        passwordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);
    }

    function calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length === 0) {
            return { score: 0, text: 'Enter password', class: '' };
        }
        
        if (password.length >= 8) score += 20;
        if (password.length >= 12) score += 10;
        if (/[a-z]/.test(password)) score += 15;
        if (/[A-Z]/.test(password)) score += 15;
        if (/[0-9]/.test(password)) score += 15;
        if (/[^A-Za-z0-9]/.test(password)) score += 25;
        
        if (score >= 80) {
            return { score: 100, text: 'Very Strong', class: 'bg-success' };
        } else if (score >= 60) {
            return { score: 75, text: 'Strong', class: 'bg-success' };
        } else if (score >= 40) {
            return { score: 50, text: 'Medium', class: 'bg-warning' };
        } else if (score >= 20) {
            return { score: 25, text: 'Weak', class: 'bg-danger' };
        } else {
            return { score: 10, text: 'Very Weak', class: 'bg-danger' };
        }
    }

    function updatePasswordStrength(strength) {
        strengthBar.style.width = strength.score + '%';
        strengthBar.className = 'progress-bar ' + strength.class;
        strengthText.textContent = strength.text;
    }
});

// Form reset functions
function resetForm() {
    const form = document.getElementById('profileForm');
    if (form) {
        form.reset();
        const avatarPreview = document.getElementById('avatarPreview');
        const originalSrc = "{{ $user->profile->avatar ? asset('storage/' . $user->profile->avatar) : '/images/users/avatar-1.jpg' }}";
        if (avatarPreview) {
            avatarPreview.src = originalSrc;
        }
    }
}

function resetPasswordForm() {
    const passwordFields = ['current_password', 'new_password', 'password_confirmation'];
    passwordFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
            field.type = 'password';
            field.classList.remove('is-valid', 'is-invalid');
        }
    });
    
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const matchIndicator = document.getElementById('passwordMatchIndicator');
    
    if (strengthBar) {
        strengthBar.style.width = '0%';
        strengthBar.className = 'progress-bar';
    }
    
    if (strengthText) {
        strengthText.textContent = 'Enter password';
    }
    
    if (matchIndicator) {
        matchIndicator.textContent = '';
        matchIndicator.className = 'form-text mt-1';
    }
    
    const eyeIcons = [
        { iconId: 'toggleCurrentIcon', inputId: 'current_password' },
        { iconId: 'toggleNewIcon', inputId: 'new_password' },
        { iconId: 'toggleConfirmIcon', inputId: 'password_confirmation' }
    ];
    
    eyeIcons.forEach(({ iconId, inputId }) => {
        const icon = document.getElementById(iconId);
        const input = document.getElementById(inputId);
        if (icon && input) {
            icon.className = 'bx bx-hide';
            input.type = 'password';
        }
    });
}

function resetPreferencesForm() {
    const form = document.getElementById('preferencesForm');
    if (form) {
        const selectElements = form.querySelectorAll('select:not([type="hidden"])');
        const checkboxElements = form.querySelectorAll('input[type="checkbox"]');
        
        selectElements.forEach(select => {
            select.selectedIndex = 0;
        });
        
        checkboxElements.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
}
</script>

<style>
/* Mobile-first responsive improvements */
@media (max-width: 768px) {
    
    .card-body {
        padding: 1rem !important;
    }
    
    .card-header {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
    
    .form-control, .form-select {
        font-size: 1rem;
    }
    
    .modal-dialog {
        margin: 1rem;
    }
    
    .avatar-lg {
        width: 3rem;
        height: 3rem;
    }
    
    h5.card-title {
        font-size: 1rem;
    }
    
    .badge {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .d-grid .btn {
        margin-bottom: 0.5rem;
    }
    
    .d-grid .btn:last-child {
        margin-bottom: 0;
    }
    
    .form-check-input {
        width: 1.5em;
        height: 1.5em;
    }
    
    .modal-body, .modal-header, .modal-footer {
        padding: 1rem;
    }
}

/* Form improvements */
.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    border-color: #86b7fe;
}

/* Card improvements */
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

/* Progress bar styling */
.progress {
    height: 4px;
    background-color: #e9ecef;
    border-radius: 2px;
}

.progress-bar {
    transition: width 0.3s ease;
    border-radius: 2px;
}

/* Button improvements */
.btn {
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

/* Alert improvements */
.alert {
    border-radius: 8px;
    border: none;
}

/* Badge improvements */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    border-radius: 4px;
}

/* Form switch improvements */
.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.form-check-input {
    border-radius: 50rem;
}

/* Border improvements */
.border {
    border-radius: 6px !important;
}

/* Responsive spacing */
.g-3 {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.g-md-4 {
    --bs-gutter-x: 1.5rem;
    --bs-gutter-y: 1.5rem;
}

@media (min-width: 768px) {
    .g-md-4 {
        --bs-gutter-x: 1.5rem;
        --bs-gutter-y: 1.5rem;
    }
}
</style>
@endsection