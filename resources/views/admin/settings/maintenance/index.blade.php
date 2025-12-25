@extends('admin.layouts.vertical', ['title' => 'Maintenance Mode', 'subTitle' => 'System Management'])

@section('content')

{{-- Header Section --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Maintenance Mode Management</h4>
                        <p class="text-muted mb-0">Control site accessibility and manage maintenance settings</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="checkStatus()">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                            Check Status
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="previewPage()">
                            <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                            Preview Page
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Current Status Alert --}}
<div class="row mb-4">
    <div class="col-12">
        @if($maintenance_status['enabled'])
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:warning-duotone" class="fs-5 me-2"></iconify-icon>
            <div>
                <strong>Maintenance Mode is Currently ACTIVE</strong>
                <br>Since: {{ date('M j, Y g:i A', $maintenance_status['time']) }}
                @if(!empty($maintenance_status['message']))
                    <br>Message: {{ $maintenance_status['message'] }}
                @endif
            </div>
        </div>
        @else
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:check-circle-duotone" class="fs-5 me-2"></iconify-icon>
            <div>
                <strong>Site is Currently LIVE</strong>
                <br>Maintenance mode is disabled - all users can access the site
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Main Content --}}
<div class="row">
    {{-- Maintenance Controls --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                    Maintenance Mode Controls
                </h5>
            </div>
            <div class="card-body">
                <form id="maintenanceForm">
                    @csrf
                    
                    {{-- Quick Actions --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">Quick Actions</h6>
                            <div class="d-flex gap-3">
                                @if($maintenance_status['enabled'])
                                <button type="button" class="btn btn-success" onclick="disableMaintenance()">
                                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                                    Bring Site Online
                                </button>
                                @else
                                <button type="button" class="btn btn-warning" onclick="enableMaintenance()">
                                    <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                    Enable Maintenance Mode
                                </button>
                                @endif
                                
                                <button type="button" class="btn btn-outline-primary" onclick="enableWithCustom()">
                                    <iconify-icon icon="iconamoon:settings-duotone" class="me-1"></iconify-icon>
                                    Custom Settings
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Maintenance Message --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">Maintenance Message</h6>
                        </div>
                        <div class="col-12">
                            <label for="message" class="form-label fw-semibold">Custom Message</label>
                            <textarea class="form-control" id="message" name="message" rows="3" placeholder="We are currently performing scheduled maintenance. Please check back soon.">{{ $maintenance_settings['message'] }}</textarea>
                            <div class="form-text">This message will be displayed to visitors during maintenance</div>
                        </div>
                    </div>

                    {{-- Duration and Contact --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">Additional Information</h6>
                        </div>
                        <div class="col-md-6">
                            <label for="duration" class="form-label fw-semibold">Estimated Duration</label>
                            <input type="text" class="form-control" id="duration" name="duration" value="{{ $maintenance_settings['duration'] }}" placeholder="30 minutes">
                            <div class="form-text">How long maintenance is expected to last</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="contact_email" class="form-label fw-semibold">Contact Email</label>
                            <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ $maintenance_settings['contact_email'] }}" placeholder="admin@example.com">
                            <div class="form-text">Support contact for users during maintenance</div>
                        </div>
                    </div>

                    {{-- Advanced Settings --}}
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2 mb-3">Advanced Settings</h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="retry_after" class="form-label fw-semibold">Retry After (seconds)</label>
                            <input type="number" class="form-control" id="retry_after" name="retry_after" value="3600" min="60" max="86400">
                            <div class="form-text">How often browsers should retry (60-86400 seconds)</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="allowed_ips" class="form-label fw-semibold">Allowed IP Addresses</label>
                            <input type="text" class="form-control" id="allowed_ips" name="allowed_ips" placeholder="192.168.1.1, 10.0.0.1">
                            <div class="form-text">Comma-separated IPs that can bypass maintenance</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="redirect_url" class="form-label fw-semibold">Redirect URL (Optional)</label>
                            <input type="url" class="form-control" id="redirect_url" name="redirect_url" value="{{ $maintenance_settings['redirect_url'] }}" placeholder="https://status.example.com">
                            <div class="form-text">Redirect users to external status page</div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="show_progress" name="show_progress" {{ $maintenance_settings['show_progress'] ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="show_progress">
                                    Show Progress Animation
                                </label>
                                <div class="form-text">Display animated progress bar on maintenance page</div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-primary" onclick="saveSettings()">
                                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                                    Save Settings
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="previewPage()">
                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                    Preview Page
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Sidebar Info --}}
    <div class="col-lg-4">
        {{-- Current Status Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                    Current Status
                </h5>
            </div>
            <div class="card-body">
                <div id="status-info">
                    @if($maintenance_status['enabled'])
                    <div class="text-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-3 d-inline-block">
                            <iconify-icon icon="iconamoon:warning-duotone" class="fs-2 text-warning"></iconify-icon>
                        </div>
                        <h6 class="text-warning">MAINTENANCE MODE</h6>
                        <p class="small text-muted">Site is currently in maintenance mode</p>
                        
                        @if(!empty($maintenance_status['allowed']))
                        <div class="mt-3">
                            <small class="text-muted">Allowed IPs:</small>
                            @foreach($maintenance_status['allowed'] as $ip)
                            <br><code class="small">{{ $ip }}</code>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @else
                    <div class="text-center">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-3 d-inline-block">
                            <iconify-icon icon="iconamoon:check-circle-duotone" class="fs-2 text-success"></iconify-icon>
                        </div>
                        <h6 class="text-success">SITE ONLINE</h6>
                        <p class="small text-muted">All users can access the site</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Info --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:lightning-duotone" class="me-2"></iconify-icon>
                    Quick Info
                </h5>
            </div>
            <div class="card-body">
                <div class="small">
                    <div class="mb-2">
                        <strong>Your IP:</strong> {{ request()->ip() }}
                    </div>
                    <div class="mb-2">
                        <strong>Server Time:</strong> {{ now()->format('M j, Y g:i A') }}
                    </div>
                    <div class="mb-2">
                        <strong>App Environment:</strong> 
                        <span class="badge bg-{{ app()->environment('production') ? 'danger' : 'warning' }}">
                            {{ strtoupper(app()->environment()) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Activity --}}
        @if(count($maintenance_logs) > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:file-document-duotone" class="me-2"></iconify-icon>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                @foreach(array_slice($maintenance_logs, 0, 5) as $log)
                <div class="small text-muted mb-1">
                    {{ Str::limit($log, 80) }}
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Confirmation Modal --}}
<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmButton">Confirm</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let currentAction = null;

function enableMaintenance() {
    showConfirmation(
        'Enable Maintenance Mode',
        'This will put the site in maintenance mode with default settings. Are you sure?',
        () => executeMaintenanceAction('enable', {})
    );
}

function disableMaintenance() {
    showConfirmation(
        'Disable Maintenance Mode',
        'This will bring the site back online and allow all users to access it. Are you sure?',
        () => executeMaintenanceAction('disable', {})
    );
}

function enableWithCustom() {
    const formData = new FormData(document.getElementById('maintenanceForm'));
    const data = Object.fromEntries(formData.entries());
    
    showConfirmation(
        'Enable Maintenance Mode',
        'This will put the site in maintenance mode with your custom settings. Are you sure?',
        () => executeMaintenanceAction('enable', data)
    );
}

function executeMaintenanceAction(action, data) {
    const url = action === 'enable' ? '/admin/maintenance/enable' : '/admin/maintenance/disable';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Operation failed. Please try again.', 'danger');
    });
}

function saveSettings() {
    const formData = new FormData(document.getElementById('maintenanceForm'));
    
    fetch('/admin/maintenance/settings', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Settings saved successfully!', 'success');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to save settings', 'danger');
    });
}

function checkStatus() {
    fetch('/admin/maintenance/status', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Status refreshed', 'info');
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        showAlert('Failed to check status', 'danger');
    });
}

function previewPage() {
    const formData = new FormData(document.getElementById('maintenanceForm'));
    const params = new URLSearchParams(formData);
    
    window.open('/admin/maintenance/preview?' + params.toString(), '_blank');
}

function showConfirmation(title, message, callback) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    
    const confirmButton = document.getElementById('confirmButton');
    confirmButton.onclick = () => {
        callback();
        bootstrap.Modal.getInstance(document.getElementById('confirmationModal')).hide();
    };
    
    new bootstrap.Modal(document.getElementById('confirmationModal')).show();
}

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
    }, 5000);
}

// Auto-refresh status every 30 seconds
setInterval(() => {
    fetch('/admin/maintenance/status')
        .then(response => response.json())
        .then(data => {
            // Update status indicator if needed
            console.log('Status check:', data.status);
        })
        .catch(error => console.log('Status check failed'));
}, 30000);
</script>
@endsection