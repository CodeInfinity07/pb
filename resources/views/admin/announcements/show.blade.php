@extends('admin.layouts.vertical', ['title' => 'Announcement Details', 'subTitle' => 'View Announcement Information'])

@section('content')

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle bg-{{ $announcement->type }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <iconify-icon icon="{{ $announcement->type_icon }}" class="text-white fs-5"></iconify-icon>
                        </div>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">{{ $announcement->title }}</h5>
                        <small class="text-muted">Created {{ $announcement->created_at->format('M d, Y \a\t g:i A') }} by {{ $announcement->creator->full_name }}</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $announcement->status_badge_class }} px-3 py-2">
                        {{ ucfirst($announcement->status) }}
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <iconify-icon icon="iconamoon:menu-dots-duotone" class="me-1"></iconify-icon>
                            Actions
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('admin.announcements.edit', $announcement) }}">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="toggleAnnouncementStatus({{ $announcement->id }})">
                                <iconify-icon icon="iconamoon:{{ $announcement->isActive() ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                                {{ $announcement->isActive() ? 'Deactivate' : 'Activate' }}
                            </a></li>
                            @if($announcement->total_views > 0)
                            <li><a class="dropdown-item" href="#" onclick="resetViews({{ $announcement->id }})">
                                <iconify-icon icon="material-symbols:refresh-rounded" class="me-2"></iconify-icon>Reset Views
                            </a></li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteAnnouncement({{ $announcement->id }})">
                                <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
                            </a></li>
                        </ul>
                    </div>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-sm btn-outline-secondary">
                        <span class="d-none d-sm-inline ms-1">Back to List</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status Alerts --}}
@if($announcement->hasExpired())
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:clock-duotone" class="fs-5 me-2"></iconify-icon>
            <div class="flex-grow-1">
                <strong>Expired Announcement!</strong> 
                This announcement expired on {{ $announcement->expires_at->format('M d, Y \a\t g:i A') }}.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@elseif($announcement->isScheduled())
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:clock-duotone" class="fs-5 me-2"></iconify-icon>
            <div class="flex-grow-1">
                <strong>Scheduled Announcement!</strong> 
                This announcement is scheduled for {{ $announcement->scheduled_at->format('M d, Y \a\t g:i A') }}.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@elseif($announcement->isActive())
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:check-circle-duotone" class="fs-5 me-2"></iconify-icon>
            <div class="flex-grow-1">
                <strong>Active Announcement!</strong> 
                This announcement is currently being shown to users.
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

{{-- Main Content --}}
<div class="row">
    {{-- Announcement Details --}}
    <div class="col-lg-8">
        {{-- Content Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:document-duotone" class="me-2"></iconify-icon>
                    Announcement Content
                </h5>
            </div>
            <div class="card-body">
                {{-- Preview --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold">Live Preview</h6>
                        <button class="btn btn-sm btn-outline-primary" onclick="showFullPreview()">
                            <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                            Full Preview
                        </button>
                    </div>
                    
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <iconify-icon icon="{{ $announcement->type_icon }}" class="fs-4 text-{{ $announcement->type }} me-2"></iconify-icon>
                            <h6 class="mb-0">{{ $announcement->title }}</h6>
                        </div>
                        <p class="mb-2">{{ $announcement->content }}</p>
                        <div class="text-end">
                            @if($announcement->button_link)
                            <a href="{{ $announcement->button_link }}" class="btn btn-sm btn-{{ $announcement->type }}" target="_blank">
                                {{ $announcement->button_text }}
                            </a>
                            @else
                            <button type="button" class="btn btn-sm btn-{{ $announcement->type }}">
                                {{ $announcement->button_text }}
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Content Details --}}
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Content Length</label>
                        <div class="form-control-plaintext">{{ strlen($announcement->content) }} characters</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Word Count</label>
                        <div class="form-control-plaintext">{{ str_word_count($announcement->content) }} words</div>
                    </div>
                    @if($announcement->button_link)
                    <div class="col-12">
                        <label class="form-label fw-bold">Button Link</label>
                        <div class="form-control-plaintext">
                            <a href="{{ $announcement->button_link }}" target="_blank" class="text-decoration-none">
                                {{ $announcement->button_link }}
                                <iconify-icon icon="iconamoon:external-link-duotone" class="ms-1"></iconify-icon>
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- View Statistics --}}
        @if($announcement->total_views > 0)
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:chart-duotone" class="me-2"></iconify-icon>
                    View Analytics
                </h5>
                <button class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                    <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                    Refresh
                </button>
            </div>
            <div class="card-body">
                {{-- Statistics Cards --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded bg-primary-subtle">
                            <h4 class="text-primary mb-1">{{ number_format($viewStats['total_views']) }}</h4>
                            <small class="text-muted">Total Views</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded bg-success-subtle">
                            <h4 class="text-success mb-1">{{ number_format($viewStats['unique_viewers']) }}</h4>
                            <small class="text-muted">Unique Viewers</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded bg-info-subtle">
                            <h4 class="text-info mb-1">{{ number_format($viewStats['views_today']) }}</h4>
                            <small class="text-muted">Views Today</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded bg-warning-subtle">
                            <h4 class="text-warning mb-1">{{ number_format($viewStats['views_this_week']) }}</h4>
                            <small class="text-muted">This Week</small>
                        </div>
                    </div>
                </div>

                {{-- View Rate Progress --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold">View Rate</span>
                        <span class="text-muted">{{ $viewStats['unique_viewers'] }} / {{ $announcement->target_audience === 'all' ? 'All Users' : 'Target Audience' }}</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ min(100, ($viewStats['unique_viewers'] / max(1, $viewStats['total_views'])) * 100) }}%"></div>
                    </div>
                </div>

                {{-- Recent Activity --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0">Recent Activity</h6>
                    <small class="text-muted">Last 30 days</small>
                </div>
                <div class="row g-2 small">
                    <div class="col-4 text-center">
                        <div class="fw-semibold text-success">{{ number_format($viewStats['views_this_month']) }}</div>
                        <div class="text-muted">This Month</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-semibold text-primary">{{ number_format($viewStats['views_this_week']) }}</div>
                        <div class="text-muted">This Week</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="fw-semibold text-info">{{ number_format($viewStats['views_today']) }}</div>
                        <div class="text-muted">Today</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Recent Viewers --}}
        @if($recentViewers->count() > 0)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:users-duotone" class="me-2"></iconify-icon>
                    Recent Viewers ({{ $recentViewers->count() }})
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Viewed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentViewers as $view)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <span class="text-white fw-bold small">{{ $view->user->initials }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $view->user->full_name }}</div>
                                            <small class="text-muted">{{ $view->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $view->user->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($view->user->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $view->viewed_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $view->viewed_at->format('g:i A') }}</small>
                                    <div class="small text-muted">{{ $view->viewed_at->diffForHumans() }}</div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
        @else
        {{-- No Views Yet --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <iconify-icon icon="iconamoon:eye-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                <h5 class="text-muted">No views yet</h5>
                <p class="text-muted">This announcement hasn't been viewed by any users yet.</p>
                @if(!$announcement->isActive())
                <button class="btn btn-primary" onclick="toggleAnnouncementStatus({{ $announcement->id }})">
                    <iconify-icon icon="iconamoon:play-duotone" class="me-1"></iconify-icon>
                    Activate Announcement
                </button>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Settings Overview --}}
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                    Settings Overview
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Type</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="{{ $announcement->type_icon }}" class="text-{{ $announcement->type }} me-2"></iconify-icon>
                            <span class="badge {{ $announcement->type_badge_class }}">{{ ucfirst($announcement->type) }}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Priority</label>
                        <div class="form-control-plaintext">{{ $announcement->priority }}</div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Show Once</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-{{ $announcement->show_once ? 'success' : 'secondary' }}">
                                {{ $announcement->show_once ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Dismissible</label>
                        <div class="form-control-plaintext">
                            <span class="badge bg-{{ $announcement->is_dismissible ? 'success' : 'danger' }}">
                                {{ $announcement->is_dismissible ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Target Audience --}}
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                    Target Audience
                </h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Audience Type</label>
                    <div class="form-control-plaintext">{{ $announcement->target_audience_display }}</div>
                </div>
                
                @if($announcement->target_audience === 'specific' && $announcement->target_user_ids)
                <div class="mb-3">
                    <label class="form-label fw-semibold">Specific Users</label>
                    <div class="form-control-plaintext">{{ count($announcement->target_user_ids) }} users selected</div>
                </div>
                @endif

                <div class="alert alert-info d-flex align-items-center">
                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                    <div>
                        <strong>Estimated Reach:</strong> 
                        <span id="estimatedReach">Calculating...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scheduling Information --}}
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                    Scheduling
                </h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Created</label>
                        <div class="form-control-plaintext">
                            {{ $announcement->created_at->format('M d, Y \a\t g:i A') }}
                            <div class="small text-muted">{{ $announcement->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    
                    @if($announcement->scheduled_at)
                    <div class="col-12">
                        <label class="form-label fw-semibold">Scheduled For</label>
                        <div class="form-control-plaintext">
                            {{ $announcement->scheduled_at->format('M d, Y \a\t g:i A') }}
                            <div class="small text-muted">{{ $announcement->scheduled_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif

                    @if($announcement->expires_at)
                    <div class="col-12">
                        <label class="form-label fw-semibold">Expires</label>
                        <div class="form-control-plaintext">
                            {{ $announcement->expires_at->format('M d, Y \a\t g:i A') }}
                            <div class="small text-muted">{{ $announcement->expires_at->diffForHumans() }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:lightning-duotone" class="me-2"></iconify-icon>
                    Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-primary">
                        <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                        Edit Announcement
                    </a>
                    <button class="btn btn-{{ $announcement->isActive() ? 'warning' : 'success' }}" onclick="toggleAnnouncementStatus({{ $announcement->id }})">
                        <iconify-icon icon="iconamoon:{{ $announcement->isActive() ? 'pause' : 'play' }}-duotone" class="me-1"></iconify-icon>
                        {{ $announcement->isActive() ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button class="btn btn-info" onclick="showFullPreview()">
                        <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                        Full Preview
                    </button>
                    @if($announcement->total_views > 0)
                    <button class="btn btn-outline-warning" onclick="resetViews({{ $announcement->id }})">
                        <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                        Reset Views
                    </button>
                    @endif
                    <button class="btn btn-outline-danger" onclick="deleteAnnouncement({{ $announcement->id }})">
                        <iconify-icon icon="iconamoon:trash-duotone" class="me-1"></iconify-icon>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Full Preview Modal -->
<div class="modal fade" id="fullPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-{{ $announcement->type }} text-white border-0">
                <h5 class="modal-title d-flex align-items-center">
                    <iconify-icon icon="{{ $announcement->type_icon }}" class="me-2 fs-4"></iconify-icon>
                    {{ $announcement->title }}
                </h5>
                @if($announcement->is_dismissible)
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                @endif
            </div>
            <div class="modal-body">
                <div class="announcement-content">
                    {{ $announcement->content }}
                </div>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                @if($announcement->button_link)
                <a href="{{ $announcement->button_link }}" class="btn btn-{{ $announcement->type }} btn-lg px-4" target="_blank">
                    {{ $announcement->button_text }}
                </a>
                @else
                <button type="button" class="btn btn-{{ $announcement->type }} btn-lg px-4" data-bs-dismiss="modal">
                    {{ $announcement->button_text }}
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Load estimated reach on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateEstimatedReach();
});

function calculateEstimatedReach() {
    fetch('{{ route("admin.announcements.target-count") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            target_audience: '{{ $announcement->target_audience }}',
            target_user_ids: @json($announcement->target_user_ids ?? [])
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('estimatedReach').textContent = `${data.count} users`;
        } else {
            document.getElementById('estimatedReach').textContent = 'Unable to calculate';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('estimatedReach').textContent = 'Error calculating';
    });
}

function toggleAnnouncementStatus(announcementId) {
    if (!confirm('Are you sure you want to change this announcement\'s status?')) return;
    
    fetch(`/admin/announcements/${announcementId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
        showAlert('Error updating announcement status.', 'danger');
    });
}

function deleteAnnouncement(announcementId) {
    if (!confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) return;
    
    fetch(`/admin/announcements/${announcementId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.href = '{{ route("admin.announcements.index") }}';
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting announcement.', 'danger');
    });
}

function resetViews(announcementId) {
    if (!confirm('Are you sure you want to reset all views for this announcement? Users will see it again.')) return;
    
    fetch(`/admin/announcements/${announcementId}/reset-views`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
        showAlert('Error resetting views.', 'danger');
    });
}

function showFullPreview() {
    new bootstrap.Modal(document.getElementById('fullPreviewModal')).show();
}

function refreshStats() {
    showAlert('Refreshing statistics...', 'info');
    location.reload();
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 4000);
}
</script>

<style>
/* Simple, clean styling matching the dashboard pattern */
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-danger-subtle { background-color: rgba(220, 53, 69, 0.1) !important; }
.bg-warning-subtle { background-color: rgba(255, 193, 7, 0.1) !important; }
.bg-info-subtle { background-color: rgba(23, 162, 184, 0.1) !important; }
.bg-primary-subtle { background-color: rgba(0, 123, 255, 0.1) !important; }

.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress {
    border-radius: 4px;
}

.badge {
    font-weight: 500;
}

.announcement-content {
    white-space: pre-wrap;
    line-height: 1.6;
}

/* Mobile improvements */
@media (max-width: 768px) {
    .card-title {
        font-size: 1rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .d-grid .btn {
        padding: 0.5rem;
        font-size: 0.875rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    h4 {
        font-size: 1.25rem;
    }
    
    h5 {
        font-size: 1.125rem;
    }
}
</style>
@endsection