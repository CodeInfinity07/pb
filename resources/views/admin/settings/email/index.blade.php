@extends('admin.layouts.vertical', ['title' => 'Email Settings', 'subTitle' => 'System Management'])

@section('content')

{{-- Header Section --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Email Settings</h4>
                        <p class="text-muted mb-0">Configure SMTP settings, email templates, and notification preferences</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="testConnection()">
                            <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                            Test Connection
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showTestEmailModal()">
                            <iconify-icon icon="iconamoon:email-duotone" class="me-1"></iconify-icon>
                            Send Test Email
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="showQueueModal()">
                            <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                            Email Queue
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Current Configuration Alert --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:information-circle-duotone" class="fs-5 me-2"></iconify-icon>
            <div>
                <strong>Current SMTP Configuration:</strong> 
                {{ config('mail.mailers.smtp.host') }}:{{ config('mail.mailers.smtp.port') }} ({{ config('mail.mailers.smtp.encryption') ?: 'No encryption' }}) | 
                From: {{ config('mail.from.address') }} ({{ config('mail.from.name') }})
            </div>
        </div>
    </div>
</div>

{{-- Main Content --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-pills nav-justified" id="emailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="smtp-tab" data-bs-toggle="pill" data-bs-target="#smtp-settings" type="button" role="tab">
                            <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                            SMTP Configuration
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="templates-tab" data-bs-toggle="pill" data-bs-target="#email-templates" type="button" role="tab">
                            <iconify-icon icon="iconamoon:file-document-duotone" class="me-2"></iconify-icon>
                            Email Templates
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#notification-settings" type="button" role="tab">
                            <iconify-icon icon="iconamoon:notification-duotone" class="me-2"></iconify-icon>
                            Notifications
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="emailTabContent">
                    
                    {{-- SMTP Configuration Tab --}}
                    <div class="tab-pane fade show active" id="smtp-settings" role="tabpanel">
                        <form id="smtpForm">
                            @csrf
                            
                            {{-- Server Configuration --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:server-duotone" class="me-2"></iconify-icon>
                                    Server Configuration
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="mail_host" class="form-label fw-semibold">SMTP Host <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="mail_host" name="mail_host" 
                                               value="{{ getSetting('mail_host', config('mail.mailers.smtp.host')) }}" required>
                                        <div class="form-text">Current: {{ config('mail.mailers.smtp.host') }}</div>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="mail_port" class="form-label fw-semibold">Port <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="mail_port" name="mail_port" 
                                               value="{{ getSetting('mail_port', config('mail.mailers.smtp.port')) }}" required>
                                        <div class="form-text">Current: {{ config('mail.mailers.smtp.port') }}</div>
                                    </div>

                                    <div class="col-md-3">
                                        <label for="mail_encryption" class="form-label fw-semibold">Encryption</label>
                                        <select class="form-select" id="mail_encryption" name="mail_encryption">
                                            <option value="tls" {{ config('mail.mailers.smtp.encryption') === 'tls' ? 'selected' : '' }}>TLS</option>
                                            <option value="ssl" {{ config('mail.mailers.smtp.encryption') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                            <option value="" {{ config('mail.mailers.smtp.encryption') === '' ? 'selected' : '' }}>None</option>
                                        </select>
                                        <div class="form-text">Current: {{ config('mail.mailers.smtp.encryption') ?: 'None' }}</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="mail_username" class="form-label fw-semibold">Username</label>
                                        <input type="text" class="form-control" id="mail_username" name="mail_username" 
                                               value="{{ getSetting('mail_username', config('mail.mailers.smtp.username')) }}">
                                        <div class="form-text">SMTP authentication username</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="mail_password" class="form-label fw-semibold">Password</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="mail_password" name="mail_password" 
                                                   value="{{ str_repeat('*', 12) }}">
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('mail_password')">
                                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                        <div class="form-text">Status: {{ config('mail.mailers.smtp.password') ? 'Configured' : 'Not Set' }}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Sender Information --}}
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                                    Sender Information
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="mail_from_address" class="form-label fw-semibold">From Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" 
                                               value="{{ getSetting('mail_from_address', config('mail.from.address')) }}" required>
                                        <div class="form-text">Current: {{ config('mail.from.address') }}</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="mail_from_name" class="form-label fw-semibold">From Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" 
                                               value="{{ getSetting('mail_from_name', config('mail.from.name')) }}" required>
                                        <div class="form-text">Current: {{ config('mail.from.name') }}</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="mail_reply_to" class="form-label fw-semibold">Reply-To Email</label>
                                        <input type="email" class="form-control" id="mail_reply_to" name="mail_reply_to" 
                                               value="{{ getSetting('mail_reply_to', config('mail.from.address')) }}">
                                        <div class="form-text">Where replies should be sent</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="mail_timeout" class="form-label fw-semibold">Timeout (seconds)</label>
                                        <input type="number" class="form-control" id="mail_timeout" name="mail_timeout" 
                                               value="{{ getSetting('mail_timeout', 30) }}" min="10" max="120">
                                        <div class="form-text">Connection timeout duration</div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                                    Save SMTP Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Email Templates Tab --}}
                    <div class="tab-pane fade" id="email-templates" role="tabpanel">
                        <form id="templatesForm">
                            @csrf
                            
                            {{-- Welcome Email --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:user-add-duotone" class="me-2"></iconify-icon>
                                    Welcome Email
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card bg-light border-0 mb-3">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_welcome_email" name="enable_welcome_email" 
                                                           {{ getSetting('enable_welcome_email', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_welcome_email">
                                                        Send Welcome Emails to New Users
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="welcome_email_subject" class="form-label fw-semibold">Subject Line</label>
                                        <input type="text" class="form-control" id="welcome_email_subject" name="welcome_email_subject" 
                                               value="{{ getSetting('welcome_email_subject', 'Welcome to ' . config('app.name')) }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="welcome_delay" class="form-label fw-semibold">Send Delay (minutes)</label>
                                        <input type="number" class="form-control" id="welcome_delay" name="welcome_delay" 
                                               value="{{ getSetting('welcome_delay', 0) }}" min="0" max="1440">
                                        <div class="form-text">Delay before sending welcome email</div>
                                    </div>

                                    <div class="col-12">
                                        <label for="welcome_email_content" class="form-label fw-semibold">Email Content</label>
                                        <textarea class="form-control" id="welcome_email_content" name="welcome_email_content" rows="4">{{ getSetting('welcome_email_content', 'Welcome to our platform! We\'re excited to have you join our community.') }}</textarea>
                                        <div class="form-text">Available variables: {user_name}, {platform_name}, {login_url}</div>
                                    </div>
                                </div>
                            </div>

                            {{-- Transaction Notifications --}}
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:invoice-duotone" class="me-2"></iconify-icon>
                                    Transaction Notifications
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_deposit_notifications" name="enable_deposit_notifications" 
                                                           {{ getSetting('enable_deposit_notifications', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_deposit_notifications">
                                                        Deposit Confirmations
                                                    </label>
                                                    <div class="form-text">Email users when deposits are confirmed</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="enable_withdrawal_notifications" name="enable_withdrawal_notifications" 
                                                           {{ getSetting('enable_withdrawal_notifications', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="enable_withdrawal_notifications">
                                                        Withdrawal Updates
                                                    </label>
                                                    <div class="form-text">Email users about withdrawal status changes</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:file-document-duotone" class="me-1"></iconify-icon>
                                    Save Email Templates
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Notifications Tab --}}
                    <div class="tab-pane fade" id="notification-settings" role="tabpanel">
                        <form id="notificationsForm">
                            @csrf
                            
                            {{-- Admin Notifications --}}
                            <div class="mb-5">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:shield-duotone" class="me-2"></iconify-icon>
                                    Admin Notifications
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="admin_notification_email" class="form-label fw-semibold">Admin Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="admin_notification_email" name="admin_notification_email" 
                                               value="{{ getSetting('admin_notification_email', 'admin@mlmtrial.live') }}" required>
                                        <div class="form-text">Where admin notifications are sent</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="large_deposit_threshold" class="form-label fw-semibold">Large Deposit Threshold</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="large_deposit_threshold" name="large_deposit_threshold" 
                                                   value="{{ getSetting('large_deposit_threshold', 1000) }}" min="100" step="50">
                                        </div>
                                        <div class="form-text">Notify for deposits above this amount</div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="notify_new_registrations" name="notify_new_registrations" 
                                                           {{ getSetting('notify_new_registrations', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="notify_new_registrations">
                                                        New User Registrations
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="notify_large_deposits" name="notify_large_deposits" 
                                                           {{ getSetting('notify_large_deposits', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="notify_large_deposits">
                                                        Large Deposits
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="notify_withdrawal_requests" name="notify_withdrawal_requests" 
                                                           {{ getSetting('notify_withdrawal_requests', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="notify_withdrawal_requests">
                                                        Withdrawal Requests
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="notify_kyc_submissions" name="notify_kyc_submissions" 
                                                           {{ getSetting('notify_kyc_submissions', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="notify_kyc_submissions">
                                                        KYC Submissions
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Email Queue Settings --}}
                            <div class="mb-4">
                                <h5 class="text-primary border-bottom pb-2 mb-4">
                                    <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                                    Email Queue & Rate Limiting
                                </h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="max_emails_per_minute" class="form-label fw-semibold">Max Emails/Minute</label>
                                        <input type="number" class="form-control" id="max_emails_per_minute" name="max_emails_per_minute" 
                                               value="{{ getSetting('max_emails_per_minute', 60) }}" min="1" max="300">
                                        <div class="form-text">Rate limit for outgoing emails</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="retry_failed_emails" class="form-label fw-semibold">Retry Failed Emails</label>
                                        <input type="number" class="form-control" id="retry_failed_emails" name="retry_failed_emails" 
                                               value="{{ getSetting('retry_failed_emails', 3) }}" min="0" max="10">
                                        <div class="form-text">Number of retry attempts</div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="notification_delay" class="form-label fw-semibold">Notification Delay (minutes)</label>
                                        <input type="number" class="form-control" id="notification_delay" name="notification_delay" 
                                               value="{{ getSetting('notification_delay', 0) }}" min="0" max="60">
                                        <div class="form-text">Delay before sending notifications</div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="allow_user_unsubscribe" name="allow_user_unsubscribe" 
                                                           {{ getSetting('allow_user_unsubscribe', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="allow_user_unsubscribe">
                                                        Allow User Unsubscribe
                                                    </label>
                                                    <div class="form-text">Users can opt out of non-essential emails</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="track_email_opens" name="track_email_opens" 
                                                           {{ getSetting('track_email_opens', false) ? 'checked' : '' }}>
                                                    <label class="form-check-label fw-semibold" for="track_email_opens">
                                                        Track Email Opens
                                                    </label>
                                                    <div class="form-text">Track when users open emails</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:notification-duotone" class="me-1"></iconify-icon>
                                    Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Test Email Modal --}}
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testEmailForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="test_recipient" class="form-label fw-semibold">Recipient Email</label>
                        <input type="email" class="form-control" id="test_recipient" name="test_email_recipient" 
                               value="{{ auth()->user()->email }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="test_subject" class="form-label fw-semibold">Subject</label>
                        <input type="text" class="form-control" id="test_subject" name="test_email_subject" 
                               value="Test Email from {{ config('app.name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="test_content" class="form-label fw-semibold">Message</label>
                        <textarea class="form-control" id="test_content" name="test_email_content" rows="4" required>This is a test email to verify your SMTP configuration is working correctly.

Sent at: {{ now()->format('Y-m-d H:i:s') }}
From: {{ config('app.name') }} Admin Panel</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Email Queue Modal --}}
<div class="modal fade" id="queueModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Email Queue Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="queueContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning" onclick="clearQueue()">Clear Queue</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Form submission handlers
document.getElementById('smtpForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveEmailSettings('smtp', this);
});

document.getElementById('templatesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveEmailSettings('templates', this);
});

document.getElementById('notificationsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveEmailSettings('notifications', this);
});

document.getElementById('testEmailForm').addEventListener('submit', function(e) {
    e.preventDefault();
    sendTestEmailNow();
});

// Save email settings
function saveEmailSettings(category, form) {
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Saving...';
    submitBtn.disabled = true;
    
    fetch('/admin/email-settings/update', {
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
            showAlert(data.message || 'Settings saved successfully!', 'success');
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

// Test SMTP connection
function testConnection() {
    showAlert('Testing SMTP connection...', 'info');
    
    fetch('/admin/email-settings/test-connection', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('SMTP connection test successful!', 'success');
        } else {
            showAlert(data.message || 'Connection test failed', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Connection test failed', 'danger');
    });
}

// Show test email modal
function showTestEmailModal() {
    new bootstrap.Modal(document.getElementById('testEmailModal')).show();
}

// Send test email
function sendTestEmailNow() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('testEmailModal'));
    const formData = new FormData(document.getElementById('testEmailForm'));
    
    const submitBtn = document.querySelector('#testEmailForm button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Sending...';
    submitBtn.disabled = true;
    
    fetch('/admin/email-settings/send-test', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        modal.hide();
        if (data.success) {
            showAlert('Test email sent successfully!', 'success');
        } else {
            showAlert(data.message || 'Failed to send test email', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to send test email', 'danger');
        modal.hide();
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Show queue modal
function showQueueModal() {
    const modal = new bootstrap.Modal(document.getElementById('queueModal'));
    const content = document.getElementById('queueContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch('/admin/email-settings/queue-status')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load queue status</div>';
        });
}

// Clear email queue
function clearQueue() {
    if (confirm('Are you sure you want to clear the email queue?')) {
        fetch('/admin/email-settings/clear-queue', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                showQueueModal(); // Refresh queue display
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to clear queue', 'danger');
        });
    }
}

// Toggle password visibility
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = '<iconify-icon icon="iconamoon:eye-slash-duotone"></iconify-icon>';
    } else {
        input.type = 'password';
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
const tabs = document.querySelectorAll('#emailTabs button[data-bs-toggle="pill"]');
tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', function(e) {
        localStorage.setItem('activeEmailTab', e.target.id);
    });
});

// Restore active tab
document.addEventListener('DOMContentLoaded', function() {
    const activeTab = localStorage.getItem('activeEmailTab');
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
/* Email settings styling */
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

.border-bottom {
    border-bottom: 2px solid #e9ecef !important;
}

.input-group .btn {
    border-color: #dee2e6;
}

.input-group .btn:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.card.bg-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
}

.alert {
    border-radius: 8px;
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
</style>
@endsection