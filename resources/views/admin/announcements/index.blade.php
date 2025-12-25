@extends('admin.layouts.vertical', ['title' => 'Announcements', 'subTitle' => 'Manage System Announcements'])

@section('content')
<div class="container-fluid">
    {{-- Active Announcements Alert --}}
    @if(isset($stats['active_announcements']) && $stats['active_announcements'] > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <iconify-icon icon="iconamoon:notification-duotone" class="fs-5 me-3"></iconify-icon>
                    <div class="flex-grow-1">
                        <strong>Active Announcements!</strong> 
                        You have {{ $stats['active_announcements'] }} announcements currently active.
                        <a href="#" onclick="showActiveAnnouncements()" class="alert-link ms-2">View Details</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">System Announcements</h4>
                            <p class="text-muted mb-0">Create and manage user announcements and notifications</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center" onclick="previewAnnouncement()">
                                <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                Preview
                            </button>
                            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                New Announcement
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:notification-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_announcements']) }}</h5>
                    <small class="text-muted">All announcements</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="ic:sharp-verified" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active</h6>
                    <h5 class="mb-0 fw-bold">{{ $stats['active_announcements'] }}</h5>
                    <small class="text-muted">Currently live</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:eye-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Views</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_views']) }}</h5>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Scheduled</h6>
                    <h5 class="mb-0 fw-bold">{{ $stats['scheduled_announcements'] }}</h5>
                    <small class="text-muted">Future posts</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Search Announcements</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </span>
                                <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}" 
                                       placeholder="Search by title or content...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="scheduled" {{ ($status ?? '') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Type</label>
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="info" {{ ($type ?? '') === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="success" {{ ($type ?? '') === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="warning" {{ ($type ?? '') === 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="danger" {{ ($type ?? '') === 'danger' ? 'selected' : '' }}>Danger</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Audience</label>
                            <select class="form-select" name="target_audience">
                                <option value="">All Audiences</option>
                                <option value="all" {{ ($target_audience ?? '') === 'all' ? 'selected' : '' }}>All Users</option>
                                <option value="active" {{ ($target_audience ?? '') === 'active' ? 'selected' : '' }}>Active Users</option>
                                <option value="verified" {{ ($target_audience ?? '') === 'verified' ? 'selected' : '' }}>Verified Users</option>
                                <option value="kyc_verified" {{ ($target_audience ?? '') === 'kyc_verified' ? 'selected' : '' }}>KYC Verified</option>
                                <option value="specific" {{ ($target_audience ?? '') === 'specific' ? 'selected' : '' }}>Specific Users</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center">
                                    <iconify-icon icon="iconamoon:search-duotone" class="me-1"></iconify-icon>
                                    Filter
                                </button>
                                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
                                    <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Announcements Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Announcements ({{ $announcements->total() ?? 0 }})</h5>
                        <button class="btn btn-sm btn-outline-secondary d-flex align-items-center" onclick="refreshStats()">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                            Refresh
                        </button>
                    </div>
                </div>

                @if(($announcements->count() ?? 0) > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="announcements-table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Announcement</th>
                                            <th scope="col" class="border-0">Type & Audience</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Views</th>
                                            <th scope="col" class="border-0">Schedule</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($announcements as $announcement)
                                            <tr class="announcement-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="rounded-circle bg-{{ $announcement->type === 'info' ? 'primary' : ($announcement->type === 'success' ? 'success' : ($announcement->type === 'warning' ? 'warning' : 'danger')) }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <iconify-icon icon="iconamoon:notification-duotone" class="text-white fs-5"></iconify-icon>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $announcement->title }}</h6>
                                                            <small class="text-muted">{{ Str::limit(strip_tags($announcement->content), 60) }}</small>
                                                            <div class="small text-muted mt-1">
                                                                <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                                                {{ $announcement->creator->full_name ?? 'System' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="mb-2">
                                                        <span class="badge bg-{{ $announcement->type === 'info' ? 'primary' : ($announcement->type === 'success' ? 'success' : ($announcement->type === 'warning' ? 'warning' : 'danger')) }}">
                                                            {{ ucfirst($announcement->type ?? 'info') }}
                                                        </span>
                                                    </div>
                                                    <div class="small text-muted">{{ $announcement->target_audience_display ?? 'All Users' }}</div>
                                                    <div class="small">
                                                        <span class="fw-semibold">Priority:</span> {{ $announcement->priority ?? 'Medium' }}
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-{{ $announcement->status === 'active' ? 'success' : ($announcement->status === 'scheduled' ? 'warning' : 'secondary') }} announcement-status-badge" data-announcement-id="{{ $announcement->id }}">
                                                        <iconify-icon icon="iconamoon:{{ $announcement->status === 'active' ? 'check-circle' : ($announcement->status === 'scheduled' ? 'clock' : 'pause-circle') }}-duotone" class="me-1"></iconify-icon>
                                                        {{ ucfirst($announcement->status ?? 'inactive') }}
                                                    </span>
                                                    @if(method_exists($announcement, 'hasExpired') && $announcement->hasExpired())
                                                    <div class="small text-danger mt-1">
                                                        <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                                        Expired
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-bold">{{ number_format($announcement->total_views ?? 0) }}</div>
                                                    <div class="text-muted small">{{ number_format($announcement->unique_viewers ?? 0) }} unique</div>
                                                    @if(($announcement->total_views ?? 0) > 0)
                                                    <div class="progress mt-1" style="height: 3px;">
                                                        <div class="progress-bar bg-primary" style="width: {{ min(100, (($announcement->unique_viewers ?? 0) / max(1, $announcement->total_views)) * 100) }}%"></div>
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    @if($announcement->scheduled_at ?? false)
                                                    <div class="small">
                                                        <div class="fw-semibold">Scheduled:</div>
                                                        <div class="text-muted">{{ $announcement->scheduled_at->format('M d, Y') }}</div>
                                                        <div class="text-muted">{{ $announcement->scheduled_at->format('g:i A') }}</div>
                                                    </div>
                                                    @endif
                                                    @if($announcement->expires_at ?? false)
                                                    <div class="small mt-1">
                                                        <div class="fw-semibold">Expires:</div>
                                                        <div class="text-muted">{{ $announcement->expires_at->format('M d, Y') }}</div>
                                                    </div>
                                                    @endif
                                                    @if(!($announcement->scheduled_at ?? false) && !($announcement->expires_at ?? false))
                                                    <div class="">
                                                        <span class="badge bg-success">Instant</span>
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.announcements.show', $announcement) }}">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.announcements.edit', $announcement) }}">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Announcement
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="toggleAnnouncementStatus({{ $announcement->id }})">
                                                                    <iconify-icon icon="iconamoon:{{ (method_exists($announcement, 'isActive') && $announcement->isActive()) ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                                                                    {{ (method_exists($announcement, 'isActive') && $announcement->isActive()) ? 'Deactivate' : 'Activate' }}
                                                                </a>
                                                            </li>
                                                            @if(($announcement->total_views ?? 0) > 0)
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="resetViews({{ $announcement->id }})">
                                                                    <iconify-icon icon="material-symbols:refresh-rounded" class="me-2"></iconify-icon>Reset Views
                                                                </a>
                                                            </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="previewAnnouncement({{ $announcement->id }})">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>Preview
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteAnnouncement({{ $announcement->id }})">
                                                                    <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile Card View --}}
                        <div class="d-lg-none p-3">
                            <div class="row g-3">
                                @foreach($announcements as $announcement)
                                    <div class="col-12">
                                        <div class="card announcement-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle bg-{{ $announcement->type === 'info' ? 'primary' : ($announcement->type === 'success' ? 'success' : ($announcement->type === 'warning' ? 'warning' : 'danger')) }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <iconify-icon icon="iconamoon:notification-duotone" class="text-white"></iconify-icon>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $announcement->title }}</h6>
                                                            <small class="text-muted">{{ $announcement->creator->full_name ?? 'System' }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="{{ route('admin.announcements.show', $announcement) }}">
                                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="{{ route('admin.announcements.edit', $announcement) }}">
                                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="toggleAnnouncementStatus({{ $announcement->id }})">
                                                                <iconify-icon icon="iconamoon:{{ (method_exists($announcement, 'isActive') && $announcement->isActive()) ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                                                                {{ (method_exists($announcement, 'isActive') && $announcement->isActive()) ? 'Deactivate' : 'Activate' }}
                                                            </a></li>
                                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteAnnouncement({{ $announcement->id }})">
                                                                <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Status and Badges Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge bg-{{ $announcement->type === 'info' ? 'primary' : ($announcement->type === 'success' ? 'success' : ($announcement->type === 'warning' ? 'warning' : 'danger')) }}">
                                                            {{ ucfirst($announcement->type ?? 'info') }}
                                                        </span>
                                                        <span class="badge bg-{{ $announcement->status === 'active' ? 'success' : ($announcement->status === 'scheduled' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst($announcement->status ?? 'inactive') }}
                                                        </span>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-semibold">{{ number_format($announcement->total_views ?? 0) }}</div>
                                                        <small class="text-muted">views</small>
                                                    </div>
                                                </div>

                                                {{-- Content Row --}}
                                                <div class="mb-2">
                                                    <p class="text-muted mb-2 small">{{ Str::limit(strip_tags($announcement->content), 100) }}</p>
                                                    <div class="small text-muted mb-2">
                                                        <strong>Audience:</strong> {{ $announcement->target_audience_display ?? 'All Users' }}
                                                    </div>
                                                    <div class="small">
                                                        <strong>Schedule:</strong> 
                                                        @if($announcement->scheduled_at ?? false)
                                                            <span class="badge bg-warning">{{ $announcement->scheduled_at->format('M d, Y g:i A') }}</span>
                                                        @elseif($announcement->expires_at ?? false)
                                                            <span class="badge bg-info">Expires {{ $announcement->expires_at->format('M d, Y') }}</span>
                                                        @else
                                                            <span class="badge bg-success">Instant</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    @if($announcements->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $announcements->firstItem() }}</span> to <span class="fw-semibold">{{ $announcements->lastItem() }}</span> of <span class="fw-semibold">{{ $announcements->total() }}</span> announcements
                                </div>
                                <div>
                                    {{ $announcements->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:notification-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Announcements Found</h6>
                            <p class="text-muted">No announcements match your current filter criteria.</p>
                            @if(request('status') || request('type') || request('target_audience') || request('search'))
                                <a href="{{ route('admin.announcements.index') }}" class="btn btn-primary">Clear Filters</a>
                            @else
                                <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                    Create Announcement
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
// Global variables
let isSubmitting = false;

// Utility Functions
function showAlert(message, type = 'info', duration = 4000) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm" id="${alertId}" role="alert">
            <iconify-icon icon="iconamoon:${type === 'success' ? 'check-circle' : type === 'danger' ? 'close-circle' : 'info-circle'}-duotone" class="me-2"></iconify-icon>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
    
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.remove();
        }
    }, duration);
}

function toggleAnnouncementStatus(announcementId) {
    if (!confirm('Are you sure you want to change this announcement\'s status?')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
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
            const badge = document.querySelector(`.announcement-status-badge[data-announcement-id="${announcementId}"]`);
            if (badge) {
                badge.className = `badge ${data.badge_class || 'bg-secondary'} announcement-status-badge`;
                badge.innerHTML = `<iconify-icon icon="${getStatusIcon(data.status)}" class="me-1"></iconify-icon>${capitalizeFirst(data.status)}`;
            }
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating announcement status.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function deleteAnnouncement(announcementId) {
    if (!confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
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
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to delete announcement', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting announcement.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function resetViews(announcementId) {
    if (!confirm('Are you sure you want to reset all views for this announcement? Users will see it again.')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
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
            showAlert(data.message || 'Failed to reset views', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error resetting views.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function refreshStats() {
    showAlert('Refreshing statistics...', 'info');
    setTimeout(() => location.reload(), 500);
}

function previewAnnouncement(announcementId = null) {
    if (announcementId) {
        showAlert('Loading preview...', 'info');
        // Add actual preview functionality here
    } else {
        showAlert('Create an announcement to see the preview feature!', 'info');
    }
}

function showActiveAnnouncements() {
    window.location.href = '{{ route("admin.announcements.index") }}?status=active';
}

function getStatusIcon(status) {
    const icons = {
        'active': 'iconamoon:check-circle-duotone',
        'inactive': 'iconamoon:pause-circle-duotone',
        'scheduled': 'iconamoon:clock-duotone'
    };
    return icons[status] || 'iconamoon:question-circle-duotone';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
});
</script>

<style>
/* Base Styles */
.card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
}

/* Table Container - Fixed for dropdown overflow */
.announcements-table-container {
    position: relative;
    overflow: visible;
}

/* Table Styles */
.table {
    margin-bottom: 0;
    position: relative;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 1rem 0.75rem;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.announcement-row {
    transition: background-color 0.15s ease-in-out;
}

.announcement-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Dropdown Styles - Properly positioned */
.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    left: auto;
    z-index: 1050;
    display: none;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    font-size: 0.875rem;
    color: #212529;
    text-align: left;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-menu.show {
    display: block;
}

.dropdown-menu-end {
    right: 0;
    left: auto;
}

.dropdown-item {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

.dropdown-item:hover,
.dropdown-item:focus {
    color: #1e2125;
    background-color: #e9ecef;
}

.dropdown-item.text-danger:hover,
.dropdown-item.text-danger:focus {
    color: #fff;
    background-color: #dc3545;
}

.dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid rgba(0, 0, 0, 0.15);
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
    display: inline-flex;
    align-items: center;
}

/* Progress Bar */
.progress {
    border-radius: 4px;
    height: 3px;
}

.progress-bar {
    border-radius: 4px;
}

/* Button Styles */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Form Styles */
.form-control,
.form-select {
    border-radius: 0.5rem;
    border: 1px solid #ced4da;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Mobile Card Styles */
.announcement-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.announcement-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Alert Container */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
}

@media (max-width: 767.98px) {
    .announcement-mobile-card .card-body {
        padding: 1rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .dropdown-menu {
        min-width: 8rem;
        font-size: 0.8rem;
    }
    
    .dropdown-item {
        padding: 0.4rem 0.8rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection