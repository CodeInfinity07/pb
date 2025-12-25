@extends('admin.layouts.vertical', ['title' => 'System Settings', 'subTitle' => 'System Management'])

@section('content')

{{-- Header Section --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">System Settings</h4>
                        <p class="text-muted mb-0">Configure core platform settings and system preferences</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearCache()">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                            Clear Cache
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="exportSettings()">
                            <iconify-icon icon="iconamoon:download-duotone" class="me-1"></iconify-icon>
                            Export
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Settings Content --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-pills nav-justified" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                            <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                            General Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                            <iconify-icon icon="iconamoon:shield-duotone" class="me-2"></iconify-icon>
                            Security Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="api-tab" data-bs-toggle="pill" data-bs-target="#api" type="button" role="tab">
                            <iconify-icon icon="iconamoon:code-duotone" class="me-2"></iconify-icon>
                            API & Integrations
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="settingsTabContent">
                    
                    {{-- General Settings Tab --}}
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <form id="generalSettingsForm">
                            @csrf
                            
                            {{-- Platform Information --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:globe-duotone" class="me-2"></iconify-icon>
                                    Platform Information
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="app_name" class="form-label fw-semibold">Platform Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" 
                                               value="{{ getSetting('app_name', config('app.name', 'MLM Platform')) }}" required>
                                        <div class="form-text">The name displayed throughout your platform</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="site_tagline" class="form-label fw-semibold">Platform Tagline</label>
                                        <input type="text" class="form-control" id="site_tagline" name="site_tagline" 
                                               value="{{ getSetting('site_tagline', 'Advanced Prediction Trading Platform') }}">
                                        <div class="form-text">Brief tagline shown in headers</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="site_description" class="form-label fw-semibold">Platform Description</label>
                                        <textarea class="form-control" id="site_description" name="site_description" rows="3" 
                                                  placeholder="Describe your platform...">{{ getSetting('site_description', 'Advanced MLM prediction bot platform with real-time trading capabilities') }}</textarea>
                                        <div class="form-text">Used for SEO and platform descriptions</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="support_email" class="form-label fw-semibold">Support Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="support_email" name="support_email" 
                                               value="{{ getSetting('support_email', 'support@mlmtrial.live') }}" required>
                                        <div class="form-text">Primary contact email for user support</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="default_currency" class="form-label fw-semibold">Default Currency</label>
                                        <select class="form-select" id="default_currency" name="default_currency">
                                            <option value="USD" {{ getSetting('default_currency', 'USD') === 'USD' ? 'selected' : '' }}>USD - US Dollar</option>
                                            <option value="EUR" {{ getSetting('default_currency', 'USD') === 'EUR' ? 'selected' : '' }}>EUR - Euro</option>
                                            <option value="GBP" {{ getSetting('default_currency', 'USD') === 'GBP' ? 'selected' : '' }}>GBP - British Pound</option>
                                            <option value="PKR" {{ getSetting('default_currency', 'USD') === 'PKR' ? 'selected' : '' }}>PKR - Pakistani Rupee</option>
                                            <option value="BTC" {{ getSetting('default_currency', 'USD') === 'BTC' ? 'selected' : '' }}>BTC - Bitcoin</option>
                                            <option value="ETH" {{ getSetting('default_currency', 'USD') === 'ETH' ? 'selected' : '' }}>ETH - Ethereum</option>
                                            <option value="USDT" {{ getSetting('default_currency', 'USD') === 'USDT' ? 'selected' : '' }}>USDT - Tether</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- System Preferences --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:settings-gear-duotone" class="me-2"></iconify-icon>
                                    System Preferences
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="app_timezone" class="form-label fw-semibold">Platform Timezone</label>
                                        <select class="form-select" id="app_timezone" name="app_timezone">
                                            <option value="UTC" {{ config('app.timezone') === 'UTC' ? 'selected' : '' }}>UTC (Current)</option>
                                            <option value="America/New_York" {{ config('app.timezone') === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                            <option value="America/Chicago" {{ config('app.timezone') === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                            <option value="Europe/London" {{ config('app.timezone') === 'Europe/London' ? 'selected' : '' }}>London Time</option>
                                            <option value="Asia/Karachi" {{ config('app.timezone') === 'Asia/Karachi' ? 'selected' : '' }}>Pakistan Time</option>
                                        </select>
                                        <div class="form-text">Current: {{ config('app.timezone') }}</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="date_format" class="form-label fw-semibold">Date Format</label>
                                        <select class="form-select" id="date_format" name="date_format">
                                            <option value="Y-m-d" {{ getSetting('date_format', 'Y-m-d') === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                            <option value="m/d/Y" {{ getSetting('date_format', 'Y-m-d') === 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                            <option value="d/m/Y" {{ getSetting('date_format', 'Y-m-d') === 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                            <option value="M d, Y" {{ getSetting('date_format', 'Y-m-d') === 'M d, Y' ? 'selected' : '' }}>Month DD, YYYY</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="session_timeout" class="form-label fw-semibold">Session Timeout (minutes)</label>
                                        <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                               value="{{ getSetting('session_timeout', config('session.lifetime', 120)) }}" min="30" max="1440">
                                        <div class="form-text">Auto-logout after inactivity</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Feature Controls --}}
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>
                                    Feature Controls
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                                           {{ getSetting('maintenance_mode', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="maintenance_mode">
                                                        Maintenance Mode
                                                    </label>
                                                    <div class="form-text">Put platform in maintenance mode</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="allow_registration" name="allow_registration" 
                                                           {{ getSetting('allow_registration', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="allow_registration">
                                                        Allow New Registration
                                                    </label>
                                                    <div class="form-text">Enable/disable new user signups</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="require_email_verification" name="require_email_verification" 
                                                           {{ getSetting('require_email_verification', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="require_email_verification">
                                                        Require Email Verification
                                                    </label>
                                                    <div class="form-text">Users must verify email before access</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_referral_system" name="enable_referral_system" 
                                                           {{ getSetting('enable_referral_system', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_referral_system">
                                                        Enable MLM/Referral System
                                                    </label>
                                                    <div class="form-text">Allow users to build referral networks</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                                    Save General Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Security Settings Tab --}}
                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <form id="securitySettingsForm">
                            @csrf
                            
                            {{-- Authentication Security --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:lock-duotone" class="me-2"></iconify-icon>
                                    Authentication Security
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="password_min_length" class="form-label fw-semibold">Minimum Password Length</label>
                                        <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                               value="{{ getSetting('password_min_length', 8) }}" min="6" max="32">
                                        <div class="form-text">Minimum characters required</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="max_login_attempts" class="form-label fw-semibold">Max Login Attempts</label>
                                        <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                               value="{{ getSetting('max_login_attempts', 5) }}" min="3" max="10">
                                        <div class="form-text">Failed attempts before lockout</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="lockout_duration" class="form-label fw-semibold">Lockout Duration (minutes)</label>
                                        <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" 
                                               value="{{ getSetting('lockout_duration', 15) }}" min="5" max="1440">
                                        <div class="form-text">Account lockout duration</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Two-Factor Authentication --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:mobile-duotone" class="me-2"></iconify-icon>
                                    Two-Factor Authentication
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa" 
                                                           {{ getSetting('enable_2fa', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_2fa">
                                                        Enable 2FA Support
                                                    </label>
                                                    <div class="form-text">Allow users to enable two-factor authentication</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="force_2fa_for_staff" name="force_2fa_for_staff" 
                                                           {{ getSetting('force_2fa_for_staff', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="force_2fa_for_staff">
                                                        Force 2FA for Staff
                                                    </label>
                                                    <div class="form-text">Require 2FA for all admin/staff accounts</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="force_2fa_for_withdrawals" name="force_2fa_for_withdrawals" 
                                                           {{ getSetting('force_2fa_for_withdrawals', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="force_2fa_for_withdrawals">
                                                        Require 2FA for Withdrawals
                                                    </label>
                                                    <div class="form-text">Force 2FA verification for all withdrawals</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="require_kyc_for_withdrawal" name="require_kyc_for_withdrawal" 
                                                           {{ getSetting('require_kyc_for_withdrawal', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="require_kyc_for_withdrawal">
                                                        Require KYC for Withdrawals
                                                    </label>
                                                    <div class="form-text">Users must complete KYC before withdrawing</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Access Control --}}
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:globe-duotone" class="me-2"></iconify-icon>
                                    Access Control
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="log_user_activities" name="log_user_activities" 
                                                           {{ getSetting('log_user_activities', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="log_user_activities">
                                                        Log User Activities
                                                    </label>
                                                    <div class="form-text">Keep detailed activity logs for security</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="blocked_countries" class="form-label fw-semibold">Blocked Countries</label>
                                        <textarea class="form-control" id="blocked_countries" name="blocked_countries" rows="3" 
                                                  placeholder="Enter country codes separated by commas (e.g., CN,RU,IR)">{{ getSetting('blocked_countries', '') }}</textarea>
                                        <div class="form-text">ISO country codes to block platform access</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:shield-check-duotone" class="me-1"></iconify-icon>
                                    Save Security Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- API & Integrations Tab --}}
                    <div class="tab-pane fade" id="api" role="tabpanel">
                        <form id="apiSettingsForm">
                            @csrf
                            
                            {{-- Payment Gateway Integration --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="material-symbols:account-balance-wallet" class="me-2"></iconify-icon>
                                    Plisio Payment Gateway
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="plisio_secret_key" class="form-label fw-semibold">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="plisio_secret_key" name="plisio_secret_key" 
                                                   value="{{ str_repeat('*', 40) }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="showSecretKey('plisio_secret_key', '{{ config('payment.plisio.secret_key') }}')">
                                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                        <div class="form-text">Status: {{ config('payment.plisio.secret_key') ? 'Configured' : 'Not Set' }}</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="plisio_api_url" class="form-label fw-semibold">API URL</label>
                                        <input type="url" class="form-control" id="plisio_api_url" name="plisio_api_url" 
                                               value="{{ config('payment.plisio.api_url', 'https://api.plisio.net/api/v1') }}" readonly>
                                        <div class="form-text">Payment gateway endpoint</div>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="plisio_callback_url" class="form-label fw-semibold">Callback URL</label>
                                        <input type="url" class="form-control" id="plisio_callback_url" name="plisio_callback_url" 
                                               value="{{ config('payment.plisio.callback_url', url('/api/webhooks/plisio')) }}" readonly>
                                        <div class="form-text">Set this URL in your Plisio dashboard for payment notifications</div>
                                    </div>
                                </div>
                            </div>

                            {{-- KYC Integration --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="arcticons:laokyc" class="me-2"></iconify-icon>
                                    Veriff KYC Integration
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="veriff_api_key" class="form-label fw-semibold">API Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="veriff_api_key" name="veriff_api_key" 
                                                   value="{{ str_repeat('*', 40) }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="showSecretKey('veriff_api_key', '{{ config('services.veriff.api_key') }}')">
                                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                        <div class="form-text">Status: {{ config('services.veriff.api_key') ? 'Configured' : 'Not Set' }}</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="veriff_secret_key" class="form-label fw-semibold">Secret Key</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="veriff_secret_key" name="veriff_secret_key" 
                                                   value="{{ str_repeat('*', 40) }}" readonly>
                                            <button class="btn btn-outline-secondary" type="button" onclick="showSecretKey('veriff_secret_key', '{{ config('services.veriff.secret_key') }}')">
                                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                        <div class="form-text">Status: {{ config('services.veriff.secret_key') ? 'Configured' : 'Not Set' }}</div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_auto_kyc_approval" name="enable_auto_kyc_approval" 
                                                           {{ getSetting('enable_auto_kyc_approval', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_auto_kyc_approval">
                                                        Enable Automatic KYC Approval
                                                    </label>
                                                    <div class="form-text">Automatically approve KYC based on Veriff response</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- API Rate Limiting --}}
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:code-duotone" class="me-2"></iconify-icon>
                                    API Rate Limiting
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="api_rate_limit_per_minute" class="form-label fw-semibold">Per Minute Limit</label>
                                        <input type="number" class="form-control" id="api_rate_limit_per_minute" name="api_rate_limit_per_minute" 
                                               value="{{ getSetting('api_rate_limit_per_minute', 60) }}" min="1" max="1000">
                                        <div class="form-text">Maximum requests per minute</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="api_rate_limit_per_hour" class="form-label fw-semibold">Per Hour Limit</label>
                                        <input type="number" class="form-control" id="api_rate_limit_per_hour" name="api_rate_limit_per_hour" 
                                               value="{{ getSetting('api_rate_limit_per_hour', 1000) }}" min="1" max="10000">
                                        <div class="form-text">Maximum requests per hour</div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card bg-light border-0 h-100">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_api_throttling" name="enable_api_throttling" 
                                                           {{ getSetting('enable_api_throttling', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_api_throttling">
                                                        Enable API Throttling
                                                    </label>
                                                    <div class="form-text">Enforce API rate limits</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:code-duotone" class="me-1"></iconify-icon>
                                    Save API Settings
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Form submission handlers
document.getElementById('generalSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('general', this);
});

document.getElementById('securitySettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('security', this);
});

document.getElementById('apiSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('api', this);
});

// Save settings function
function saveSettings(category, form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Saving...';
    submitBtn.disabled = true;
    
    fetch('/admin/settings/update', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Settings-Category': category
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message || `${category.charAt(0).toUpperCase() + category.slice(1)} settings saved successfully!`, 'success');
        } else {
            showAlert(data.message || 'Failed to save settings', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to save settings', 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Clear cache
function clearCache() {
    if(confirm('Are you sure you want to clear the application cache?')) {
        fetch('/admin/settings/cache/clear', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message || 'Cache cleared successfully!', data.success ? 'success' : 'danger');
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to clear cache', 'danger');
        });
    }
}

// Export settings
function exportSettings() {
    window.open('/admin/settings/export', '_blank');
    showAlert('Settings export started...', 'info');
}

// Show/hide secret keys
function showSecretKey(inputId, actualValue) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        input.value = actualValue;
        button.innerHTML = '<iconify-icon icon="iconamoon:eye-slash-duotone"></iconify-icon>';
    } else {
        input.type = 'password';
        input.value = '{{ str_repeat("*", 40) }}';
        button.innerHTML = '<iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>';
    }
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 4000);
}

// Tab persistence
const tabs = document.querySelectorAll('#settingsTabs button[data-bs-toggle="pill"]');
tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function(e) {
        localStorage.setItem('activeSettingsTab', e.target.id);
    });
});

// Restore active tab
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        const tab = document.getElementById(activeTab);
        if (tab) {
            const bootstrap_tab = new bootstrap.Tab(tab);
            bootstrap_tab.show();
        }
    }
});
</script>

<style>
/* Clean, modern styling */
.nav-pills .nav-link {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    margin-right: 0.5rem;
    color: #6c757d;
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-color: #007bff;
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

.nav-pills .nav-link:hover:not(.active) {
    background: #e9ecef;
    color: #007bff;
    border-color: #007bff;
}

.card {
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    border: 1px solid #e9ecef;
}

.card-body {
    padding: 2rem;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.btn {
    border-radius: 8px;
    padding: 0.5rem 1.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.4);
}

h5.text-primary {
    color: #007bff !important;
    font-weight: 600;
}

.bg-light {
    background-color: #f8f9fa !important;
    transition: all 0.3s ease;
}

.bg-light:hover {
    background-color: #e9ecef !important;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .nav-pills .nav-link {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        margin-right: 0.25rem;
        margin-bottom: 0.5rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.5rem 1rem;
    }
}

@media (max-width: 576px) {
    .nav-pills .nav-link {
        padding: 0.5rem;
        flex: 1;
        text-align: center;
    }
    
    .nav-pills .nav-link iconify-icon {
        display: block;
        margin: 0 auto 0.25rem;
    }
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Input group improvements */
.input-group .btn {
    border-color: #dee2e6;
}

.input-group .btn:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

/* Form sections */
.border-bottom {
    border-bottom: 2px solid #e9ecef !important;
}

/* Switch styling improvements */
.form-check-input:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

/* Card hover effects */
.card.bg-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}
</style>
@endsection