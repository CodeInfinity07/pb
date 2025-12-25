@extends('admin.layouts.vertical', ['title' => 'Edit Announcement', 'subTitle' => 'Update System Announcement'])

@section('content')

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <iconify-icon icon="iconamoon:edit-duotone" class="text-white fs-5"></iconify-icon>
                        </div>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Edit Announcement</h5>
                        <small class="text-muted">Update system announcement: {{ $announcement->title }}</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.announcements.show', $announcement) }}" class="btn btn-sm btn-outline-info">
                        <iconify-icon icon="iconamoon:eye-duotone" class="align-text-bottom"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">View Details</span>
                    </a>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="iconamoon:arrow-left-duotone" class="align-text-bottom"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Back to List</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Current Status Alert --}}
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
@endif

{{-- Edit Form --}}
<div class="row">
    <div class="col-12">
        <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST" id="announcementForm">
            @csrf
            @method('PUT')
            
            <div class="row">
                {{-- Main Form --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>
                                Announcement Details
                            </h5>
                        </div>
                        <div class="card-body">
                            {{-- Title --}}
                            <div class="mb-4">
                                <label for="title" class="form-label fw-bold">
                                    <iconify-icon icon="iconamoon:text-duotone" class="me-1"></iconify-icon>
                                    Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                       id="title" name="title" value="{{ old('title', $announcement->title) }}" 
                                       maxlength="255" placeholder="Enter announcement title..." required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Keep it clear and concise (max 255 characters)</div>
                            </div>

                            {{-- Content --}}
                            <div class="mb-4">
                                <label for="content" class="form-label fw-bold">
                                    <iconify-icon icon="iconamoon:document-duotone" class="me-1"></iconify-icon>
                                    Message <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control @error('content') is-invalid @enderror" 
                                          id="content" name="content" rows="6" required 
                                          placeholder="Enter your announcement message...">{{ old('content', $announcement->content) }}</textarea>
                                @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Detailed message that users will see in the modal</div>
                            </div>

                            {{-- Type --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <iconify-icon icon="iconamoon:tag-duotone" class="me-1"></iconify-icon>
                                    Announcement Type <span class="text-danger">*</span>
                                </label>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" 
                                                   id="type_info" value="info" {{ old('type', $announcement->type) === 'info' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_info">
                                                <iconify-icon icon="iconamoon:information-circle-duotone" class="text-info me-2"></iconify-icon>
                                                Info
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" 
                                                   id="type_success" value="success" {{ old('type', $announcement->type) === 'success' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_success">
                                                <iconify-icon icon="iconamoon:check-circle-duotone" class="text-success me-2"></iconify-icon>
                                                Success
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" 
                                                   id="type_warning" value="warning" {{ old('type', $announcement->type) === 'warning' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_warning">
                                                <iconify-icon icon="iconamoon:warning-duotone" class="text-warning me-2"></iconify-icon>
                                                Warning
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type" 
                                                   id="type_danger" value="danger" {{ old('type', $announcement->type) === 'danger' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_danger">
                                                <iconify-icon icon="iconamoon:close-circle-duotone" class="text-danger me-2"></iconify-icon>
                                                Danger
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Button Configuration --}}
                            <div class="mb-4">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <iconify-icon icon="iconamoon:click-duotone" class="me-2"></iconify-icon>
                                    Button Configuration
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="button_text" class="form-label fw-semibold">Button Text <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('button_text') is-invalid @enderror" 
                                               id="button_text" name="button_text" value="{{ old('button_text', $announcement->button_text) }}" 
                                               maxlength="50" required>
                                        @error('button_text')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="button_link" class="form-label fw-semibold">Button Link (Optional)</label>
                                        <input type="url" class="form-control @error('button_link') is-invalid @enderror" 
                                               id="button_link" name="button_link" value="{{ old('button_link', $announcement->button_link) }}" 
                                               placeholder="https://example.com">
                                        @error('button_link')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Leave empty to just dismiss the modal</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sidebar Settings --}}
                <div class="col-lg-4">
                    {{-- View Statistics --}}
                    @if($announcement->total_views > 0)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:chart-duotone" class="me-2"></iconify-icon>
                                View Statistics
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 text-center">
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <h5 class="text-primary mb-0">{{ number_format($announcement->total_views) }}</h5>
                                        <small class="text-muted">Total Views</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-2">
                                        <h5 class="text-success mb-0">{{ number_format($announcement->unique_viewers) }}</h5>
                                        <small class="text-muted">Unique Viewers</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="alert alert-warning small">
                                    <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                    Changes may affect who can view this announcement
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

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
                                <label class="form-label fw-semibold">Who should see this? <span class="text-danger">*</span></label>
                                <select class="form-select @error('target_audience') is-invalid @enderror" 
                                        name="target_audience" id="target_audience" onchange="toggleSpecificUsers()" required>
                                    <option value="all" {{ old('target_audience', $announcement->target_audience) === 'all' ? 'selected' : '' }}>All Users</option>
                                    <option value="active" {{ old('target_audience', $announcement->target_audience) === 'active' ? 'selected' : '' }}>Active Users</option>
                                    <option value="verified" {{ old('target_audience', $announcement->target_audience) === 'verified' ? 'selected' : '' }}>Verified Users</option>
                                    <option value="kyc_verified" {{ old('target_audience', $announcement->target_audience) === 'kyc_verified' ? 'selected' : '' }}>KYC Verified Users</option>
                                    <option value="specific" {{ old('target_audience', $announcement->target_audience) === 'specific' ? 'selected' : '' }}>Specific Users</option>
                                </select>
                                @error('target_audience')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="specificUsersSection" class="mt-3" style="display: none;">
                                <label class="form-label fw-semibold">Search Users</label>
                                <input type="text" class="form-control" id="userSearch" placeholder="Search users by name or email..." onkeyup="searchUsers(this.value)">
                                <div id="userSearchResults" class="mt-2"></div>
                                <div id="selectedUsers" class="mt-2"></div>
                                @error('target_user_ids')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <div class="alert alert-info d-flex align-items-center">
                                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                                    <div>
                                        <strong>Target Count: </strong>
                                        <span id="targetCount">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Display Settings --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                                Display Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="priority" class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                                <select class="form-select @error('priority') is-invalid @enderror" name="priority" required>
                                    <option value="1" {{ old('priority', $announcement->priority) == '1' ? 'selected' : '' }}>High Priority (1)</option>
                                    <option value="2" {{ old('priority', $announcement->priority) == '2' ? 'selected' : '' }}>Medium Priority (2)</option>
                                    <option value="3" {{ old('priority', $announcement->priority) == '3' ? 'selected' : '' }}>Normal Priority (3)</option>
                                    <option value="4" {{ old('priority', $announcement->priority) == '4' ? 'selected' : '' }}>Low Priority (4)</option>
                                    <option value="5" {{ old('priority', $announcement->priority) == '5' ? 'selected' : '' }}>Lowest Priority (5)</option>
                                </select>
                                @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Lower numbers show first</div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_once" id="show_once" value="1" {{ old('show_once', $announcement->show_once) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_once">
                                        Show only once per user
                                    </label>
                                </div>
                                <div class="form-text">Recommended for most announcements</div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_dismissible" id="is_dismissible" value="1" {{ old('is_dismissible', $announcement->is_dismissible) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_dismissible">
                                        Allow users to dismiss
                                    </label>
                                </div>
                                <div class="form-text">Users can close without clicking button</div>
                            </div>
                        </div>
                    </div>

                    {{-- Scheduling --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                                Scheduling
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="status_active" value="active" {{ old('status', $announcement->status) === 'active' ? 'checked' : '' }} onchange="toggleScheduling()">
                                            <label class="form-check-label" for="status_active">Active Now</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="status_scheduled" value="scheduled" {{ old('status', $announcement->status) === 'scheduled' ? 'checked' : '' }} onchange="toggleScheduling()">
                                            <label class="form-check-label" for="status_scheduled">Schedule</label>
                                        </div>
                                    </div>
                                </div>
                                @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div id="schedulingSection" class="mt-3" style="display: none;">
                                <label for="scheduled_at" class="form-label fw-semibold">Schedule For</label>
                                <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                       id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at', $announcement->scheduled_at ? $announcement->scheduled_at->format('Y-m-d\TH:i') : '') }}" 
                                       min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                                @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mt-3">
                                <label for="expires_at" class="form-label fw-semibold">Expires At (Optional)</label>
                                <input type="datetime-local" class="form-control @error('expires_at') is-invalid @enderror" 
                                       id="expires_at" name="expires_at" value="{{ old('expires_at', $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : '') }}">
                                @error('expires_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Announcement will hide after this date</div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-info" onclick="previewAnnouncement()">
                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                    Preview Changes
                                </button>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                                    Update Announcement
                                </button>
                                <a href="{{ route('admin.announcements.show', $announcement) }}" class="btn btn-outline-secondary">
                                    <iconify-icon icon="iconamoon:close-duotone" class="me-1"></iconify-icon>
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                    Announcement Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Preview</button>
                <button type="button" class="btn btn-primary" onclick="submitFromPreview()">
                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                    Update Announcement
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let selectedUserIds = @json(old('target_user_ids', $announcement->target_user_ids ?? []));

// Handle form submission
document.getElementById('announcementForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    submitBtn.disabled = true;
    spinner.style.display = 'inline-block';
    
    // Add selected user IDs as hidden inputs
    if (selectedUserIds.length > 0) {
        selectedUserIds.forEach(userId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'target_user_ids[]';
            input.value = userId;
            this.appendChild(input);
        });
    }
});

function toggleSpecificUsers() {
    const targetAudience = document.getElementById('target_audience').value;
    const section = document.getElementById('specificUsersSection');
    
    if (targetAudience === 'specific') {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
        selectedUserIds = [];
        updateSelectedUsers();
    }
    
    updateTargetCount();
}

function toggleScheduling() {
    const scheduleRadio = document.getElementById('status_scheduled');
    const section = document.getElementById('schedulingSection');
    
    if (scheduleRadio.checked) {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

function updateTargetCount() {
    const targetAudience = document.getElementById('target_audience').value;
    
    if (targetAudience === 'specific') {
        document.getElementById('targetCount').textContent = `${selectedUserIds.length} specific users`;
        return;
    }

    fetch('{{ route("admin.announcements.target-count") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            target_audience: targetAudience,
            target_user_ids: selectedUserIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('targetCount').textContent = `${data.count} users`;
        } else {
            document.getElementById('targetCount').textContent = 'Error counting users';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('targetCount').textContent = 'Error counting users';
    });
}

function searchUsers(query) {
    if (query.length < 2) {
        document.getElementById('userSearchResults').innerHTML = '';
        return;
    }

    fetch('{{ route("admin.announcements.search-users") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ search: query })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySearchResults(data.users);
        }
    })
    .catch(error => console.error('Error:', error));
}

function displaySearchResults(users) {
    const container = document.getElementById('userSearchResults');
    container.innerHTML = '';
    
    users.forEach(user => {
        if (!selectedUserIds.includes(user.id)) {
            const div = document.createElement('div');
            div.className = 'user-search-item';
            div.innerHTML = `
                <strong>${user.name}</strong> (${user.email})
                <span class="badge bg-${user.status === 'active' ? 'success' : 'secondary'} ms-2">${user.status}</span>
            `;
            div.onclick = () => selectUser(user);
            container.appendChild(div);
        }
    });
}

function selectUser(user) {
    selectedUserIds.push(user.id);
    updateSelectedUsers();
    updateTargetCount();
    document.getElementById('userSearch').value = '';
    document.getElementById('userSearchResults').innerHTML = '';
}

function updateSelectedUsers() {
    const container = document.getElementById('selectedUsers');
    container.innerHTML = '';
    
    selectedUserIds.forEach(userId => {
        const span = document.createElement('span');
        span.className = 'selected-user-tag';
        span.innerHTML = `User ${userId} <span class="remove-user" onclick="removeUser(${userId})">&times;</span>`;
        container.appendChild(span);
    });
}

function removeUser(userId) {
    selectedUserIds = selectedUserIds.filter(id => id !== userId);
    updateSelectedUsers();
    updateTargetCount();
}

function previewAnnouncement() {
    const formData = new FormData(document.getElementById('announcementForm'));
    const data = {
        title: formData.get('title'),
        content: formData.get('content'),
        type: formData.get('type'),
        button_text: formData.get('button_text'),
        button_link: formData.get('button_link')
    };

    if (!data.title || !data.content || !data.type) {
        showAlert('Please fill in the required fields before previewing.', 'warning');
        return;
    }

    fetch('{{ route("admin.announcements.preview") }}', {
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
            displayPreview(data.preview);
        } else {
            showAlert('Failed to generate preview.', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to generate preview.', 'error');
    });
}

function displayPreview(preview) {
    const typeColors = {
        'info': 'primary',
        'success': 'success',
        'warning': 'warning',
        'danger': 'danger'
    };
    
    const content = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-${typeColors[preview.type]} text-white">
                    <h5 class="modal-title d-flex align-items-center">
                        <iconify-icon icon="${preview.type_icon}" class="me-2 fs-4"></iconify-icon>
                        ${preview.title}
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3">${preview.content}</p>
                </div>
                <div class="modal-footer">
                    ${preview.button_link 
                        ? `<a href="${preview.button_link}" class="btn btn-${typeColors[preview.type]}">${preview.button_text}</a>`
                        : `<button type="button" class="btn btn-${typeColors[preview.type]}">${preview.button_text}</button>`
                    }
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('previewContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function submitFromPreview() {
    bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
    document.getElementById('announcementForm').submit();
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedUsers();
    toggleSpecificUsers();
    toggleScheduling();
    updateTargetCount();
});
</script>

<style>
.user-search-item {
    padding: 8px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 4px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-search-item:hover {
    background-color: #f8f9fa;
}

.selected-user-tag {
    display: inline-block;
    background-color: #0d6efd;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    margin: 2px;
    font-size: 0.875rem;
}

.selected-user-tag .remove-user {
    margin-left: 6px;
    cursor: pointer;
    font-weight: bold;
}

.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
}
</style>
@endsection