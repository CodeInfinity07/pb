@extends('admin.layouts.vertical', ['title' => 'Notification Logs', 'subTitle' => 'Notification Management'])

@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Notification Logs</h4>
                        <p class="text-muted mb-0">View notification history and delivery status</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">
                            Back to Notifications
                        </a>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="showClearModal()">
                            Clear Old Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="notification_type" class="form-label">Notification Type</label>
                        <select class="form-select" id="notification_type" name="notification_type">
                            <option value="">All Types</option>
                            <option value="WelcomeUser" {{ request('notification_type') == 'WelcomeUser' ? 'selected' : '' }}>Welcome User</option>
                            <option value="OrderConfirmation" {{ request('notification_type') == 'OrderConfirmation' ? 'selected' : '' }}>Order Confirmation</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                    </div>
                    
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Notification History</h5>
                    <span class="badge bg-primary">{{ $notifications->total() }} total records</span>
                </div>
            </div>
            <div class="card-body p-0">
                @if($notifications->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Recipient</th>
                                <th>Content</th>
                                <th>Status</th>
                                <th>Sent</th>
                                <th>Read</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($notifications as $notification)
                            <tr>
                                <td>
                                    <strong>{{ class_basename($notification->type) }}</strong>
                                    <br><small class="text-muted">{{ $notification->type }}</small>
                                </td>
                                <td>
                                    @if($notification->notifiable_type === 'App\Models\User')
                                        <strong>User ID: {{ $notification->notifiable_id }}</strong>
                                        <br><small class="text-muted">Registered User</small>
                                    @else
                                        <strong>{{ $notification->notifiable_type }}</strong>
                                        <br><small class="text-muted">ID: {{ $notification->notifiable_id }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $data = is_string($notification->data) ? json_decode($notification->data, true) : (array) $notification->data;
                                    @endphp
                                    @if($data && is_array($data))
                                        <div>
                                            @if(isset($data['title']))
                                                <strong>{{ $data['title'] }}</strong><br>
                                            @endif
                                            @if(isset($data['message']))
                                                <span class="text-muted">{{ Str::limit($data['message'], 50) }}</span>
                                            @elseif(isset($data['subject']))
                                                <span class="text-muted">{{ Str::limit($data['subject'], 50) }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No preview available</span>
                                    @endif
                                </td>
                                <td>
                                    @if($notification->read_at)
                                        <span class="badge bg-success">Read</span>
                                    @else
                                        <span class="badge bg-warning">Unread</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $createdAt = \Carbon\Carbon::parse($notification->created_at);
                                    @endphp
                                    <span title="{{ $createdAt->format('Y-m-d H:i:s') }}">
                                        {{ $createdAt->format('M j, Y') }}
                                        <br><small class="text-muted">{{ $createdAt->format('g:i A') }}</small>
                                    </span>
                                </td>
                                <td>
                                    @if($notification->read_at)
                                        @php
                                            $readAt = \Carbon\Carbon::parse($notification->read_at);
                                        @endphp
                                        <span title="{{ $readAt->format('Y-m-d H:i:s') }}">
                                            {{ $readAt->format('M j, Y') }}
                                            <br><small class="text-muted">{{ $readAt->format('g:i A') }}</small>
                                        </span>
                                    @else
                                        <span class="text-muted">Not read</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" onclick="showNotificationData('{{ $notification->id }}')" title="View Details">
                                            View
                                        </button>
                                        @if(!$notification->read_at)
                                        <button type="button" class="btn btn-outline-success" onclick="markAsRead('{{ $notification->id }}')" title="Mark as Read">
                                            Mark Read
                                        </button>
                                        @endif
                                        <button type="button" class="btn btn-outline-danger" onclick="deleteNotification('{{ $notification->id }}')" title="Delete">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center p-3">
                    <div>
                        <span class="text-muted">
                            Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} 
                            of {{ $notifications->total() }} results
                        </span>
                    </div>
                    <div>
                        {{ $notifications->appends(request()->query())->links() }}
                    </div>
                </div>
                @else
                <div class="text-center py-5">
                    <h5 class="text-muted">No Notification Logs Found</h5>
                    <p class="text-muted">No notifications match your current filter criteria</p>
                    <a href="{{ route('admin.notifications.logs') }}" class="btn btn-outline-primary">
                        Reset Filters
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="notificationDataModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Notification Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="notification-details">
                    <div class="text-center py-3">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Old Notification Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="clearLogsForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="clear_days" class="form-label">Delete notifications older than</label>
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
                    <button type="submit" class="btn btn-warning">Clear Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
function showNotificationData(notificationId) {
    const modal = new bootstrap.Modal(document.getElementById('notificationDataModal'));
    const detailsDiv = document.getElementById('notification-details');
    
    detailsDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary"></div></div>';
    modal.show();
    
    setTimeout(() => {
        detailsDiv.innerHTML = '<div class="row g-3"><div class="col-12"><h6>Notification ID: ' + notificationId + '</h6><p class="text-muted">Detailed notification data would be loaded here.</p></div></div>';
    }, 1000);
}

function markAsRead(notificationId) {
    if (confirm('Mark this notification as read?')) {
        fetch('/admin/notifications/' + notificationId + '/mark-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Notification marked as read');
                location.reload();
            } else {
                alert('Failed to mark as read');
            }
        })
        .catch(error => {
            alert('Failed to mark as read');
        });
    }
}

function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        fetch('/admin/notifications/log/' + notificationId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Notification deleted successfully');
                location.reload();
            } else {
                alert('Failed to delete notification');
            }
        })
        .catch(error => {
            alert('Failed to delete notification');
        });
    }
}

function showClearModal() {
    new bootstrap.Modal(document.getElementById('clearLogsModal')).show();
}

document.getElementById('clearLogsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
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
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('clearLogsModal')).hide();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Failed to clear logs');
    });
});
</script>
@endsection