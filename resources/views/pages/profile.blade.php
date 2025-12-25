@extends('layouts.vertical', ['title' => 'Edit Profile', 'subTitle' => 'Pages'])

@section('content')

<form action="" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <div class="row">
        <div class="col-xxl-4">
            <div class="row">
                <div class="col-12">
                    <!-- Profile Picture Section -->
                    <div class="card">
                        <div class="position-relative">
                            <img src="/images/small/img-6.jpg" alt="" class="card-img rounded-bottom-0" height="200" />
                            <div class="position-absolute top-100 start-0 translate-middle-y ms-3">
                                <div class="avatar-lg rounded-circle position-relative border border-light border-3 overflow-hidden">
                                    <img src="{{ $user->profile->avatar ? asset('storage/' . $user->profile->avatar) : '/images/users/avatar-1.jpg' }}" 
                                         alt="" class="w-100 h-100 object-cover" id="avatarPreview" />
                                </div>
                                <label for="avatar" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle">
                                    <i class="bx bx-camera"></i>
                                    <input type="file" id="avatar" name="avatar" class="d-none" accept="image/*">
                                </label>
                            </div>
                        </div>
                        <div class="card-body mt-4">
                            <div class="text-center">
                                <h4 class="mb-1">{{ $user->first_name }} {{ $user->last_name }}</h4>
                                <p class="fs-14 mb-0 text-muted">{{ $user->profile->level ?? 'TL - 0' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Preferences -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Notification Preferences</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" 
                                           name="email_notifications" value="1" 
                                           {{ old('email_notifications', $user->profile->email_notifications) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emailNotifications">
                                        Email Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="smsNotifications" 
                                           name="sms_notifications" value="1"
                                           {{ old('sms_notifications', $user->profile->sms_notifications) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="smsNotifications">
                                        SMS Notifications
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Language & Timezone -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Language & Region</h5>
                        </div>
                        <div class="card-body">
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
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-8">
            <div class="row">
                <!-- Basic Information -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="firstName" class="form-label">First Name *</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror" 
                                       id="firstName" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="lastName" class="form-label">Last Name *</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                       id="lastName" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                       id="username" name="username" value="{{ old('username', $user->username) }}" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                       id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(!$user->email_verified_at)
                                    <div class="form-text text-warning">
                                        <i class="bx bx-info-circle"></i> Email not verified
                                    </div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if(!$user->profile->phone_verified)
                                    <div class="form-text text-warning">
                                        <i class="bx bx-info-circle"></i> Phone not verified
                                    </div>
                                @endif
                            </div>
                            <div class="mb-3">
                                <label for="dateOfBirth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                       id="dateOfBirth" name="date_of_birth" 
                                       value="{{ old('date_of_birth', $user->profile->date_of_birth) }}">
                                @error('date_of_birth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
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

                <!-- Address Information -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Address Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="country" class="form-label">Country *</label>
                                <select class="form-select @error('country') is-invalid @enderror" id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <option value="US" {{ old('country', $user->profile->country) == 'US' ? 'selected' : '' }}>United States</option>
                                    <option value="CA" {{ old('country', $user->profile->country) == 'CA' ? 'selected' : '' }}>Canada</option>
                                    <option value="GB" {{ old('country', $user->profile->country) == 'GB' ? 'selected' : '' }}>United Kingdom</option>
                                    <option value="AU" {{ old('country', $user->profile->country) == 'AU' ? 'selected' : '' }}>Australia</option>
                                    <option value="DE" {{ old('country', $user->profile->country) == 'DE' ? 'selected' : '' }}>Germany</option>
                                    <option value="FR" {{ old('country', $user->profile->country) == 'FR' ? 'selected' : '' }}>France</option>
                                    <option value="JP" {{ old('country', $user->profile->country) == 'JP' ? 'selected' : '' }}>Japan</option>
                                    <!-- Add more countries as needed -->
                                </select>
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                       id="city" name="city" value="{{ old('city', $user->profile->city) }}" required>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="stateProvince" class="form-label">State/Province</label>
                                <input type="text" class="form-control @error('state_province') is-invalid @enderror" 
                                       id="stateProvince" name="state_province" 
                                       value="{{ old('state_province', $user->profile->state_province) }}">
                                @error('state_province')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="postalCode" class="form-label">Postal Code</label>
                                <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                       id="postalCode" name="postal_code" 
                                       value="{{ old('postal_code', $user->profile->postal_code) }}">
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" 
                                          id="address" name="address" rows="3">{{ old('address', $user->profile->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Business Information -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Business Information <span class="text-muted fs-14">(Optional)</span></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="businessName" class="form-label">Business Name</label>
                                <input type="text" class="form-control @error('business_name') is-invalid @enderror" 
                                       id="businessName" name="business_name" 
                                       value="{{ old('business_name', $user->profile->business_name) }}">
                                @error('business_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="businessAddress" class="form-label">Business Address</label>
                                <textarea class="form-control @error('business_address') is-invalid @enderror" 
                                          id="businessAddress" name="business_address" rows="3">{{ old('business_address', $user->profile->business_address) }}</textarea>
                                @error('business_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media Links -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Social Media Links <span class="text-muted fs-14">(Optional)</span></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="facebookUrl" class="form-label">
                                    <i class="bx bxl-facebook text-primary me-1"></i>Facebook
                                </label>
                                <input type="url" class="form-control @error('facebook_url') is-invalid @enderror" 
                                       id="facebookUrl" name="facebook_url" 
                                       value="{{ old('facebook_url', $user->profile->facebook_url) }}"
                                       placeholder="https://facebook.com/username">
                                @error('facebook_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="twitterUrl" class="form-label">
                                    <i class="bx bxl-twitter text-info me-1"></i>Twitter
                                </label>
                                <input type="url" class="form-control @error('twitter_url') is-invalid @enderror" 
                                       id="twitterUrl" name="twitter_url" 
                                       value="{{ old('twitter_url', $user->profile->twitter_url) }}"
                                       placeholder="https://twitter.com/username">
                                @error('twitter_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="linkedinUrl" class="form-label">
                                    <i class="bx bxl-linkedin text-primary me-1"></i>LinkedIn
                                </label>
                                <input type="url" class="form-control @error('linkedin_url') is-invalid @enderror" 
                                       id="linkedinUrl" name="linkedin_url" 
                                       value="{{ old('linkedin_url', $user->profile->linkedin_url) }}"
                                       placeholder="https://linkedin.com/in/username">
                                @error('linkedin_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="telegramUsername" class="form-label">
                                    <i class="bx bxl-telegram text-info me-1"></i>Telegram
                                </label>
                                <input type="text" class="form-control @error('telegram_username') is-invalid @enderror" 
                                       id="telegramUsername" name="telegram_username" 
                                       value="{{ old('telegram_username', $user->profile->telegram_username) }}"
                                       placeholder="@username">
                                @error('telegram_username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="whatsappNumber" class="form-label">
                                    <i class="bx bxl-whatsapp text-success me-1"></i>WhatsApp
                                </label>
                                <input type="tel" class="form-control @error('whatsapp_number') is-invalid @enderror" 
                                       id="whatsappNumber" name="whatsapp_number" 
                                       value="{{ old('whatsapp_number', $user->profile->whatsapp_number) }}"
                                       placeholder="+1234567890">
                                @error('whatsapp_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="{{ route('profile.show') }}" class="btn btn-light">
                                    <i class="bx bx-x me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save me-1"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Avatar preview
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
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
    
    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first invalid field
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
    });
});
</script>
@endpush

@endsection