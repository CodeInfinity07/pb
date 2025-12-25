@extends('admin.layouts.vertical', ['title' => 'Notification Management', 'subTitle' => 'System Management'])

@section('content')

{{-- Header Section --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Notification Management</h4>
                        <p class="text-muted mb-0">Create, manage, and monitor Laravel notifications</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary btn-sm d-flex align-items-center">
                            <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                            Create Notification
                        </a>
                        <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-info btn-sm d-flex align-items-center">
                            <iconify-icon icon="iconamoon:file-document-duotone" class="me-1"></iconify-icon>
                            View Logs
                        </a>
                        <button type="button" class="btn btn-outline-warning btn-sm d-flex align-items-center" onclick="showClearModal()">
                            <iconify-icon icon="iconamoon:trash-duotone" class="me-1"></iconify-icon>
                            Clear Old
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Statistics Cards --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <iconify-icon icon="iconamoon:notification-duotone" class="fs-4 text-primary"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-0">{{ $notification_stats['total'] }}</h5>
                        <p class="text-muted mb-0">Total Notifications</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <iconify-icon icon="iconamoon:eye-duotone" class="fs-4 text-warning"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-0">{{ $notification_stats['unread'] }}</h5>
                        <p class="text-muted mb-0">Unread</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <iconify-icon icon="material-symbols:calendar-today" class="fs-4 text-success"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-0">{{ $notification_stats['today'] }}</h5>
                        <p class="text-muted mb-0">Today</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <iconify-icon icon="formkit:week" class="fs-4 text-info"></iconify-icon>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="mb-0">{{ $notification_stats['thisWeek'] }}</h5>
                        <p class="text-muted mb-0">This Week</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Content --}}
<div class="row">
    {{-- Existing Notifications --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:file-code-duotone" class="me-2"></iconify-icon>
                    Existing Notification Classes
                </h5>
            </div>
            <div class="card-body">
                @if(count($existing_notifications) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Channels</th>
                                    <th>Modified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($existing_notifications as $notification)
                                <tr>
                                    <td>
                                        <strong>{{ $notification['name'] }}</strong>
                                        <br><small class="text-muted">{{ $notification['file'] }}</small>
                                    </td>
                                    <td>
                                        @if($notification['settings'])
                                            {{ $notification['settings']['description'] }}
                                        @else
                                            <span class="text-muted">No description</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($notification['settings'] && $notification['settings']['channels'])
                                            @foreach($notification['settings']['channels'] as $channel)
                                                <span class="badge bg-primary me-1">{{ $channel }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Unknown</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ date('M j, Y g:i A', $notification['modified']) }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.notifications.show', $notification['name']) }}" class="btn btn-outline-primary" title="View Details">
                                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                            </a>
                                            <button type="button" class="btn btn-outline-success" onclick="showTestModal('{{ $notification['name'] }}')" title="Send Test">
                                                <iconify-icon icon="iconamoon:send-duotone"></iconify-icon>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteNotification('{{ $notification['name'] }}')" title="Delete">
                                                <iconify-icon icon="iconamoon:trash-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <iconify-icon icon="iconamoon:file-x-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                        <h5 class="text-muted">No Notification Classes Found</h5>
                        <p class="text-muted">Create your first notification to get started</p>
                        <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                            <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                            Create Notification
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Activity & Channels --}}
    <div class="col-lg-4">
        {{-- Available Channels --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:wifi-duotone" class="me-2"></iconify-icon>
                    Available Channels
                </h5>
            </div>
            <div class="card-body">
                @foreach($notification_channels as $key => $channel)
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <iconify-icon icon="{{ $channel['icon'] }}" class="fs-5 text-primary"></iconify-icon>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0">{{ $channel['name'] }}</h6>
                        <small class="text-muted">{{ $channel['description'] }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Recent Notifications --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                    Recent Activity
                </h5>
            </div>
            <div class="card-body">
                @if(count($recent_notifications) > 0)
                    @foreach($recent_notifications as $notification)
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-light rounded-circle p-2">
                                <iconify-icon icon="iconamoon:notification-duotone" class="text-primary"></iconify-icon>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">{{ class_basename($notification->type) }}</h6>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</small>
                            @if($notification->read_at)
                                <br><span class="badge bg-success">Read</span>
                            @else
                                <br><span class="badge bg-warning">Unread</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                    <div class="text-center">
                        <a href="{{ route('admin.notifications.logs') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                @else
                    <div class="text-center py-3">
                        <iconify-icon icon="iconamoon:clock-duotone" class="fs-4 text-muted mb-2"></iconify-icon>
                        <p class="text-muted mb-0">No recent activity</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Test Notification Modal --}}
<div class="modal fade" id="testNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testNotificationForm">
                <div class="modal-body">
                    <input type="hidden" id="test_notification_class" name="notification_class">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recipient Type</label>
                        <select class="form-select" id="recipient_type" name="recipient_type" onchange="toggleRecipientFields()">
                            <option value="user">Existing User</option>
                            <option value="email">Email Address</option>
                        </select>
                    </div>

                    <div class="mb-3" id="user_select_field">
                        <label for="recipient_id" class="form-label fw-semibold">Select User</label>
                        <select class="form-select" id="recipient_id" name="recipient_id">
                            <option value="">Choose a user...</option>
                            @if(isset($user_models))
                                @foreach($user_models as $user)
                                <option value="{{ $user['id'] }}">{{ $user['name'] }} ({{ $user['email'] }})</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="mb-3" id="email_input_field" style="display: none;">
                        <label for="recipient_email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="recipient_email" name="recipient_email" placeholder="user@example.com">
                    </div>

                    <div class="mb-3">
                        <label for="test_data" class="form-label fw-semibold">Test Data (JSON)</label>
                        <textarea class="form-control" id="test_data" name="test_data" rows="4" placeholder='{"name": "John Doe", "amount": 100}'></textarea>
                        <div class="form-text">Optional: JSON data to pass to the notification</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Clear Old Notifications Modal --}}
<div class="modal fade" id="clearOldModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Old Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="clearOldForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="clear_days" class="form-label fw-semibold">Delete notifications older than</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="clear_days" name="days" value="30" min="1" max="365">
                            <span class="input-group-text">days</span>
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="read_only" name="read_only" checked>
                        <label class="form-check-label" for="read_only">
                            Only delete read notifications
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Clear Notifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Test notification modal
function showTestModal(notificationClass) {
    document.getElementById('test_notification_class').value = notificationClass;
    new bootstrap.Modal(document.getElementById('testNotificationModal')).show();
}

// Toggle recipient fields
function toggleRecipientFields() {
    const type = document.getElementById('recipient_type').value;
    const userField = document.getElementById('user_select_field');
    const emailField = document.getElementById('email_input_field');
    
    if (type === 'user') {
        userField.style.display = 'block';
        emailField.style.display = 'none';
    } else {
        userField.style.display = 'none';
        emailField.style.display = 'block';
    }
}

// Send test notification
document.getElementById('testNotificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Sending...';
    submitBtn.disabled = true;
    
    fetch('{{ route("admin.notifications.test") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('testNotificationModal')).hide();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to send test notification', 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Show clear modal
function showClearModal() {
    new bootstrap.Modal(document.getElementById('clearOldModal')).show();
}

// Clear old notifications
document.getElementById('clearOldForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Clearing...';
    submitBtn.disabled = true;
    
    fetch('{{ route("admin.notifications.clear") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('clearOldModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to clear notifications', 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Delete notification
function deleteNotification(notificationName) {
    if (confirm(`Are you sure you want to delete the "${notificationName}" notification class? This action cannot be undone.`)) {
        fetch(`/admin/notifications/${notificationName}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to delete notification', 'danger');
        });
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
    }, 5000);
}
</script>
@endsection