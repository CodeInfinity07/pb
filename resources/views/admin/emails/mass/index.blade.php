@extends('admin.layouts.vertical', ['title' => 'Mass Email Campaigns', 'subTitle' => 'Send bulk emails to users'])

@section('content')

{{-- Key Alerts Section --}}
@if(isset($stats['active_campaigns']) && $stats['active_campaigns'] > 0)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center" role="alert">
            <iconify-icon icon="iconamoon:email-duotone" class="fs-5 me-2"></iconify-icon>
            <div class="flex-grow-1">
                <strong>Active Campaigns!</strong> 
                You have {{ $stats['active_campaigns'] }} campaigns currently running.
                <a href="#" onclick="loadAllCampaigns()" class="alert-link ms-2">View Details</a>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <iconify-icon icon="iconamoon:email-duotone" class="text-white fs-5"></iconify-icon>
                        </div>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Mass Email Campaigns</h5>
                        <small class="text-muted">Send bulk emails to users based on various criteria</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                        <span class="d-none d-sm-inline ms-1">New Campaign</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Statistics Cards --}}
<div class="row mb-4">
    {{-- Total Campaigns --}}
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <iconify-icon icon="iconamoon:email-duotone" class="fs-1 text-primary mb-2"></iconify-icon>
                <h4 class="mb-1">{{ number_format($stats['total_campaigns']) }}</h4>
                <h6 class="text-muted mb-3">Total Campaigns</h6>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="small">
                            <div class="fw-semibold text-warning">{{ $stats['active_campaigns'] }}</div>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="small">
                            <div class="fw-semibold text-success">{{ $stats['completed_campaigns'] }}</div>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Emails Sent --}}
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <iconify-icon icon="lsicon:email-send-outline" class="fs-1 text-success mb-2"></iconify-icon>
                <h4 class="mb-1">{{ number_format($stats['total_emails_sent']) }}</h4>
                <h6 class="text-muted mb-3">Emails Sent</h6>
                <div class="text-center">
                    <div class="small">
                        <div class="fw-semibold text-success">{{ number_format($stats['total_emails_sent']) }}</div>
                        <small class="text-muted">Total Delivered</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Total Users --}}
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <iconify-icon icon="iconamoon:profile-duotone" class="fs-1 text-info mb-2"></iconify-icon>
                <h4 class="mb-1">{{ number_format($stats['total_users']) }}</h4>
                <h6 class="text-muted mb-3">Total Users</h6>
                <div class="row g-2">
                    <div class="col-6">
                        <div class="small text-center">
                            <div class="fw-semibold text-success">{{ number_format($stats['active_users']) }}</div>
                            <small class="text-muted">Active</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="small text-center">
                            <div class="fw-semibold text-info">{{ number_format($stats['kyc_verified_users']) }}</div>
                            <small class="text-muted">KYC</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Email Verified --}}
    <div class="col-sm-6 col-lg-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <iconify-icon icon="ic:sharp-verified" class="fs-1 text-warning mb-2"></iconify-icon>
                <h4 class="mb-1">{{ number_format($stats['email_verified_users']) }}</h4>
                <h6 class="text-muted mb-3">Email Verified</h6>
                <div class="text-center">
                    <div class="small">
                        <div class="fw-semibold text-warning">{{ number_format($stats['email_verified_users']) }}</div>
                        <small class="text-muted">Verified</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Campaign Management & Recent Campaigns --}}
<div class="row">
    {{-- Recent Campaigns --}}
    <div class="col-lg-8 mb-3">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Campaigns</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadAllCampaigns()">
                    View All
                </button>
            </div>
            <div class="card-body" id="campaignsContainer">
                @if($recentCampaigns->count() > 0)
                    {{-- Desktop View --}}
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Status</th>
                                        <th>Recipients</th>
                                        <th>Progress</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentCampaigns as $campaign)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $campaign->name }}</div>
                                            <div class="text-muted small">{{ Str::limit($campaign->subject, 50) }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $campaign->status_badge_class }}">
                                                {{ $campaign->status_display }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ number_format($campaign->total_recipients) }}</div>
                                            <div class="text-muted small">{{ $campaign->recipient_groups_display }}</div>
                                        </td>
                                        <td>
                                            <div class="progress mb-1" style="height: 4px;">
                                                <div class="progress-bar" 
                                                     style="width: {{ $campaign->progress_percentage }}%"
                                                     aria-valuenow="{{ $campaign->progress_percentage }}"
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                {{ $campaign->emails_sent }}/{{ $campaign->total_recipients }} 
                                                ({{ $campaign->progress_percentage }}%)
                                            </small>
                                        </td>
                                        <td>
                                            <div>{{ $campaign->created_at->format('M d, Y') }}</div>
                                            <div class="text-muted small">{{ $campaign->created_at->format('g:i A') }}</div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @if($campaign->can_be_cancelled)
                                                <button type="button" class="btn btn-outline-danger" 
                                                        onclick="cancelCampaign({{ $campaign->id }})">
                                                    <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Mobile View --}}
                    <div class="d-md-none">
                        @foreach($recentCampaigns as $campaign)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $campaign->name }}</div>
                                    <small class="text-muted">{{ $campaign->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="text-end">
                                    <span class="badge {{ $campaign->status_badge_class }} mb-1">
                                        {{ $campaign->status_display }}
                                    </span>
                                    <div class="fw-semibold">{{ number_format($campaign->total_recipients) }} recipients</div>
                                </div>
                            </div>
                            <div class="progress mb-2" style="height: 4px;">
                                <div class="progress-bar" style="width: {{ $campaign->progress_percentage }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">{{ $campaign->emails_sent }}/{{ $campaign->total_recipients }} ({{ $campaign->progress_percentage }}%)</small>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="viewCampaign({{ $campaign->id }})">
                                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                    </button>
                                    @if($campaign->can_be_cancelled)
                                    <button type="button" class="btn btn-outline-danger" onclick="cancelCampaign({{ $campaign->id }})">
                                        <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:email-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h5 class="text-muted">No campaigns found</h5>
                    <p class="text-muted">Create your first mass email campaign to get started.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                        <iconify-icon icon="iconamoon:email-plus-duotone" class="me-1"></iconify-icon>
                        Create Campaign
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Quick Actions & Email Info --}}
    <div class="col-lg-4 mb-3">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                        New Campaign
                    </button>
                    <button type="button" class="btn btn-info" onclick="loadAllCampaigns()">
                        View All Campaigns
                    </button>
                    <a href="{{ route('admin.email-settings.index') }}" class="btn btn-outline-primary">
                        Email Settings
                    </a>
                </div>
            </div>
        </div>

        {{-- Email System Information --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Email System</h5>
            </div>
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-12 d-flex justify-content-between">
                        <span class="text-muted">Total Recipients:</span>
                        <span class="fw-semibold">{{ number_format($stats['total_users']) }}</span>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <span class="text-muted">Active Users:</span>
                        <span class="fw-semibold text-success">{{ number_format($stats['active_users']) }}</span>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <span class="text-muted">KYC Verified:</span>
                        <span class="fw-semibold text-info">{{ number_format($stats['kyc_verified_users']) }}</span>
                    </div>
                    <div class="col-12 d-flex justify-content-between">
                        <span class="text-muted">Email Verified:</span>
                        <span class="fw-semibold text-warning">{{ number_format($stats['email_verified_users']) }}</span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top">
                    <div class="row g-2 small">
                        <div class="col-12 d-flex justify-content-between">
                            <span class="text-muted">Active Campaigns:</span>
                            <span class="badge bg-{{ $stats['active_campaigns'] > 0 ? 'warning' : 'success' }}">
                                {{ $stats['active_campaigns'] }}
                            </span>
                        </div>
                        <div class="col-12 d-flex justify-content-between">
                            <span class="text-muted">Emails Sent:</span>
                            <span class="fw-semibold">{{ number_format($stats['total_emails_sent']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-top text-center">
                    <small class="text-muted">Last Updated: <span id="lastUpdate">{{ now()->format('H:i:s') }}</span></small>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshStats()">
                            <iconify-icon icon="material-symbols:refresh" class="align-text-bottom"></iconify-icon>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Campaign Modal -->
<div class="modal fade" id="newCampaignModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:email-plus-duotone" class="me-2"></iconify-icon>
                    Create Mass Email Campaign
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="massEmailForm" onsubmit="return false;">
                <div class="modal-body">
                    <!-- Recipient Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                            Recipients
                        </label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="all" id="recipients_all" name="recipient_groups[]" onchange="updateRecipientCount()">
                                    <label class="form-check-label" for="recipients_all">All Users</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="active" id="recipients_active" name="recipient_groups[]" onchange="updateRecipientCount()">
                                    <label class="form-check-label" for="recipients_active">Active Users</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="inactive" id="recipients_inactive" name="recipient_groups[]" onchange="updateRecipientCount()">
                                    <label class="form-check-label" for="recipients_inactive">Inactive Users</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="blocked" id="recipients_blocked" name="recipient_groups[]" onchange="updateRecipientCount()">
                                    <label class="form-check-label" for="recipients_blocked">Blocked Users</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="kyc_verified" id="recipients_kyc" name="recipient_groups[]" onchange="updateRecipientCount()">
                                    <label class="form-check-label" for="recipients_kyc">KYC Verified</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="email_verified" id="recipients_email" name="recipient_groups[]" onchange="updateRecipientCount()">
                                    <label class="form-check-label" for="recipients_email">Email Verified</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="specific_users" id="recipients_specific" name="recipient_groups[]" onchange="toggleSpecificUsers()">
                                <label class="form-check-label" for="recipients_specific">Specific Users</label>
                            </div>
                            <div id="specificUsersSection" class="mt-2" style="display: none;">
                                <input type="text" class="form-control" id="userSearch" placeholder="Search users by name or email..." onkeyup="searchUsers(this.value)">
                                <div id="userSearchResults" class="mt-2"></div>
                                <div id="selectedUsers" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="alert alert-info d-flex align-items-center">
                                <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                                <div>
                                    <strong>Recipients: </strong>
                                    <span id="recipientCount">Select recipient groups above</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Email Content -->
                    <div class="mb-3">
                        <label for="emailSubject" class="form-label fw-bold">
                            <iconify-icon icon="iconamoon:email-duotone" class="me-1"></iconify-icon>
                            Subject <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="emailSubject" name="subject" required maxlength="255" placeholder="Enter email subject...">
                    </div>

                    <div class="mb-3">
                        <label for="emailContent" class="form-label fw-bold">
                            <iconify-icon icon="iconamoon:document-duotone" class="me-1"></iconify-icon>
                            Message <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="emailContent" name="content" rows="8" required placeholder="Type your email message here..."></textarea>
                        <div class="form-text">
                            <strong>Available placeholders:</strong> 
                            @{{first_name}}, @{{last_name}}, @{{full_name}}, @{{email}}, @{{username}}, @{{user_level}}, 
                            @{{total_invested}}, @{{total_earned}}, @{{site_name}}, @{{current_date}}, etc.
                        </div>
                    </div>

                    <!-- Scheduling Options -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                            Delivery Options
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_option" id="send_now" value="now" checked onchange="toggleScheduling()">
                                    <label class="form-check-label" for="send_now">Send Immediately</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_option" id="schedule_later" value="schedule" onchange="toggleScheduling()">
                                    <label class="form-check-label" for="schedule_later">Schedule for Later</label>
                                </div>
                            </div>
                        </div>
                        <div id="schedulingSection" class="mt-2" style="display: none;">
                            <input type="datetime-local" class="form-control" id="scheduledAt" name="scheduled_at" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-info me-2" onclick="previewEmail()">
                        <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                        Preview
                    </button>
                    <button type="button" class="btn btn-primary" onclick="sendMassEmail()" id="sendEmailBtn">
                        <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                        <iconify-icon icon="iconamoon:email-duotone" class="me-1"></iconify-icon>
                        Send Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                    Email Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="confirmSendFromPreview()">
                    <iconify-icon icon="iconamoon:email-duotone" class="me-1"></iconify-icon>
                    Send Email
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let selectedUserIds = [];

function updateRecipientCount() {
    const checkboxes = document.querySelectorAll('input[name="recipient_groups[]"]:checked');
    const groups = Array.from(checkboxes).map(cb => cb.value);
    
    if (groups.length === 0) {
        document.getElementById('recipientCount').textContent = 'Select recipient groups above';
        return;
    }

    fetch('{{ route("admin.mass-email.recipient-count") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            recipient_groups: groups,
            specific_users: selectedUserIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('recipientCount').textContent = `${data.count} recipients`;
        } else {
            document.getElementById('recipientCount').textContent = 'Error counting recipients';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('recipientCount').textContent = 'Error counting recipients';
    });
}

function toggleSpecificUsers() {
    const checkbox = document.getElementById('recipients_specific');
    const section = document.getElementById('specificUsersSection');
    
    if (checkbox.checked) {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
        selectedUserIds = [];
        updateSelectedUsers();
        updateRecipientCount();
    }
}

function toggleScheduling() {
    const scheduleRadio = document.getElementById('schedule_later');
    const section = document.getElementById('schedulingSection');
    
    if (scheduleRadio.checked) {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
}

function searchUsers(query) {
    if (query.length < 2) {
        document.getElementById('userSearchResults').innerHTML = '';
        return;
    }

    fetch('{{ route("admin.mass-email.search-users") }}', {
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
                <strong>${user.first_name} ${user.last_name}</strong> (${user.email})
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
    updateRecipientCount();
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
    updateRecipientCount();
}

function previewEmail() {
    const formData = new FormData(document.getElementById('massEmailForm'));
    const data = {
        subject: formData.get('subject'),
        content: formData.get('content'),
        recipient_groups: Array.from(document.querySelectorAll('input[name="recipient_groups[]"]:checked')).map(cb => cb.value),
        specific_users: selectedUserIds
    };

    if (!data.subject || !data.content || data.recipient_groups.length === 0) {
        showAlert('Please fill in all required fields and select recipients.', 'error');
        return;
    }

    fetch('{{ route("admin.mass-email.preview") }}', {
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
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to generate preview.', 'error');
    });
}

function displayPreview(preview) {
    const content = `
        <div class="mb-3">
            <strong>Subject:</strong> ${preview.subject}
        </div>
        <div class="mb-3">
            <strong>Recipients:</strong> ${preview.recipient_count} users (${preview.groups.join(', ')})
        </div>
        <div class="mb-3">
            <strong>Sample Recipients:</strong>
            <ul class="list-unstyled ms-3">
                ${preview.sample_recipients.map(user => `<li>${user.first_name} ${user.last_name} (${user.email})</li>`).join('')}
            </ul>
        </div>
        <div class="mb-3">
            <strong>Message Preview:</strong>
            <div class="border p-3 mt-2" style="white-space: pre-wrap;">${preview.content}</div>
        </div>
    `;
    
    document.getElementById('previewContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function sendMassEmail() {
    const button = document.getElementById('sendEmailBtn');
    const spinner = button.querySelector('.spinner-border');
    
    const formData = new FormData(document.getElementById('massEmailForm'));
    const data = {
        subject: formData.get('subject'),
        content: formData.get('content'),
        recipient_groups: Array.from(document.querySelectorAll('input[name="recipient_groups[]"]:checked')).map(cb => cb.value),
        specific_users: selectedUserIds,
        send_immediately: document.getElementById('send_now').checked,
        scheduled_at: document.getElementById('schedule_later').checked ? formData.get('scheduled_at') : null
    };

    if (!data.subject || !data.content || data.recipient_groups.length === 0) {
        showAlert('Please fill in all required fields and select recipients.', 'error');
        return;
    }

    button.disabled = true;
    spinner.style.display = 'inline-block';

    fetch('{{ route("admin.mass-email.send") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        spinner.style.display = 'none';
        
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('newCampaignModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        button.disabled = false;
        spinner.style.display = 'none';
        console.error('Error:', error);
        showAlert('Failed to send email.', 'error');
    });
}

function confirmSendFromPreview() {
    bootstrap.Modal.getInstance(document.getElementById('previewModal')).hide();
    sendMassEmail();
}

function viewCampaign(campaignId) {
    window.location.href = `/admin/mass-email/campaigns/${campaignId}`;
}

function cancelCampaign(campaignId) {
    if (confirm('Are you sure you want to cancel this campaign? This will stop any remaining emails from being sent.')) {
        fetch(`/admin/mass-email/campaigns/${campaignId}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to cancel campaign.', 'error');
        });
    }
}

function loadAllCampaigns() {
    window.location.href = '{{ route("admin.mass-email.campaigns") }}';
}

function refreshStats() {
    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
    showAlert('Statistics refreshed!', 'success');
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