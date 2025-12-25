@extends('admin.layouts.vertical', ['title' => 'CRM Dashboard', 'subTitle' => 'Admin'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">CRM Dashboard</h4>
                        <p class="text-muted mb-0">Customer Relationship Management Overview</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                            Add Lead
                        </button>
                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                            Schedule Followup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="simple-icons:googleads" class="text-primary" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Total Leads</h6>
                <h5 class="mb-0">{{ $crmStats['total_leads'] }}</h5>
                <small class="text-muted">All leads</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="mdi:firebase" class="text-danger" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Hot Leads</h6>
                <h5 class="mb-0">{{ $crmStats['hot_leads'] }}</h5>
                <small class="text-muted">High priority</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:bookmark-check" class="text-success" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Converted</h6>
                <h5 class="mb-0">{{ $crmStats['converted_leads'] }}</h5>
                <small class="text-muted">{{ $crmStats['conversion_rate'] }}% rate</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Pending Followups</h6>
                <h5 class="mb-0">{{ $crmStats['pending_followups'] }}</h5>
                <small class="text-muted">{{ $crmStats['overdue_followups'] }} overdue</small>
            </div>
        </div>
    </div>
</div>

{{-- Secondary Stats Row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:forms-add-on" class="text-info" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Active Forms</h6>
                <h5 class="mb-0">{{ $crmStats['active_forms'] }}</h5>
                <small class="text-muted">Lead capture</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:send-duotone" class="text-secondary" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Form Submissions</h6>
                <h5 class="mb-0">{{ $crmStats['form_submissions_today'] }}</h5>
                <small class="text-muted">Today</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:assignment-turned-in-outline" class="text-dark" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Active Assignments</h6>
                <h5 class="mb-0">{{ $crmStats['active_assignments'] }}</h5>
                <small class="text-muted">In progress</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:account-child-invert-rounded" class="text-primary" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">New Leads</h6>
                <h5 class="mb-0">{{ $crmStats['new_leads_today'] }}</h5>
                <small class="text-muted">Today</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Recent Leads --}}
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="simple-icons:googleads" class="me-2"></iconify-icon>
                    Recent Leads
                </h6>
                <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                @if($recentLeads->count() > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <tbody>
                            @foreach($recentLeads as $lead)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm rounded-circle bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : 'secondary') }} me-2">
                                            <span class="avatar-title text-white">{{ strtoupper(substr($lead->first_name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $lead->full_name }}</h6>
                                            <small class="text-muted">{{ $lead->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}-subtle text-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                        {{ ucfirst($lead->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <small class="text-muted">{{ $lead->created_at->diffForHumans() }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <iconify-icon icon="simple-icons:googleads" class="fs-1 text-muted mb-2"></iconify-icon>
                    <p class="text-muted mb-0">No recent leads found</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Upcoming Followups --}}
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="arcticons:chieffollow" class="me-2"></iconify-icon>
                    Upcoming Followups
                </h6>
                <a href="{{ route('admin.crm.followups.index') }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0">
                @if($upcomingFollowups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <tbody>
                            @foreach($upcomingFollowups as $followup)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fs-4">{{ $followup->type_icon }}</span>
                                        <div>
                                            <h6 class="mb-0">{{ $followup->lead->full_name }}</h6>
                                            <small class="text-muted">{{ ucfirst($followup->type) }} followup</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="small">{{ $followup->followup_date->format('M d') }}</div>
                                    <small class="text-muted">{{ $followup->followup_date->diffForHumans() }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <iconify-icon icon="arcticons:chieffollow" class="fs-1 text-muted mb-2"></iconify-icon>
                    <p class="text-muted mb-0">No upcoming followups</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Overdue Followups & Active Forms Row --}}
<div class="row">
    {{-- Overdue Followups --}}
    @if($overdueFollowups->count() > 0)
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0 text-danger">
                    <iconify-icon icon="material-symbols:warning" class="me-2"></iconify-icon>
                    Overdue Followups
                </h6>
                <span class="badge bg-danger">{{ $overdueFollowups->count() }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <tbody>
                            @foreach($overdueFollowups as $followup)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fs-4">{{ $followup->type_icon }}</span>
                                        <div>
                                            <h6 class="mb-0">{{ $followup->lead->full_name }}</h6>
                                            <small class="text-danger">Due {{ $followup->followup_date->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-success" onclick="completeFollowup({{ $followup->id }})">
                                        <iconify-icon icon="iconamoon:check-duotone"></iconify-icon>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Active Forms --}}
    <div class="col-lg-{{ $overdueFollowups->count() > 0 ? '6' : '12' }} mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="material-symbols:forms-add-on" class="me-2"></iconify-icon>
                    Active Forms
                </h6>
                <a href="{{ route('admin.crm.forms.index') }}" class="btn btn-sm btn-outline-primary">
                    Manage Forms
                </a>
            </div>
            <div class="card-body p-0">
                @if($activeForms->count() > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <tbody>
                            @foreach($activeForms as $form)
                            <tr>
                                <td>
                                    <div>
                                        <h6 class="mb-0">{{ $form->title }}</h6>
                                        <small class="text-muted">{{ $form->submissions_count }} submissions</small>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ $form->public_url }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        <iconify-icon icon="iconamoon:link-external-duotone"></iconify-icon>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <iconify-icon icon="material-symbols:forms-add-on" class="fs-1 text-muted mb-2"></iconify-icon>
                    <p class="text-muted mb-0">No active forms</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add Lead Modal --}}
<div class="modal fade" id="addLeadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addLeadForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="mobile" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" class="form-control" name="whatsapp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="country" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Source <span class="text-danger">*</span></label>
                            <select class="form-select" name="source" required>
                                <option value="">Select Source</option>
                                <option value="Website">Website</option>
                                <option value="Facebook">Facebook</option>
                                <option value="Google Ads">Google Ads</option>
                                <option value="Referral">Referral</option>
                                <option value="Cold Call">Cold Call</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="cold">Cold</option>
                                <option value="warm">Warm</option>
                                <option value="hot">Hot</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Interest Level <span class="text-danger">*</span></label>
                            <select class="form-select" name="interest" required>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Lead</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add Followup Modal --}}
<div class="modal fade" id="addFollowupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Followup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addFollowupForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Lead <span class="text-danger">*</span></label>
                        <select class="form-select" name="lead_id" required>
                            <option value="">Choose a lead...</option>
                            @foreach($recentLeads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->full_name }} - {{ $lead->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Followup Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="followup_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="">Select Type</option>
                            <option value="call">Phone Call</option>
                            <option value="email">Email</option>
                            <option value="meeting">Meeting</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="notes" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Add Lead Form
document.getElementById('addLeadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.crm.leads.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addLeadModal')).hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to add lead', 'danger');
    });
});

// Add Followup Form
document.getElementById('addFollowupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.crm.followups.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addFollowupModal')).hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to schedule followup', 'danger');
    });
});

// Complete Followup
function completeFollowup(id) {
    if (confirm('Mark this followup as completed?')) {
        fetch(`{{ url('admin/crm/followups') }}/${id}/complete`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to complete followup', 'danger');
        });
    }
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 4000);
}

// Reset forms when modals close
document.getElementById('addLeadModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('addLeadForm').reset();
});

document.getElementById('addFollowupModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('addFollowupForm').reset();
});
</script>
@endsection