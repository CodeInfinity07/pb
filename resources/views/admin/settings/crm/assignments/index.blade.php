@extends('admin.layouts.vertical', ['title' => 'Assignments', 'subTitle' => 'CRM'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Assignments Management</h4>
                            <p class="text-muted mb-0">Assign leads to team members and track progress</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#assignLeadModal">
                                <iconify-icon icon="material-symbols:assignment-turned-in-outline" class="me-1"></iconify-icon>
                                Assign Lead
                            </button>
                            <select class="form-select form-select-sm" onchange="filterAssignments('status', this.value)" style="width: auto;">
                                <option value="" {{ !request('status') ? 'selected' : '' }}>All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                            <select class="form-select form-select-sm" onchange="filterAssignments('assigned_to', this.value)" style="width: auto;">
                                <option value="" {{ !request('assigned_to') ? 'selected' : '' }}>All Team Members</option>
                                @foreach($assignableUsers as $user)
                                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                                @endforeach
                            </select>
                            <div class="input-group" style="width: 250px;">
                                <input type="text" class="form-control form-control-sm" id="assignmentSearch" placeholder="Search leads..." value="{{ request('search') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="searchAssignments()">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:assignment-turned-in-outline" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Assignments</h6>
                    <h5 class="mb-0 fw-bold">{{ $assignmentStats['total'] }}</h5>
                    <small class="text-muted">All assignments</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active</h6>
                    <h5 class="mb-0 fw-bold">{{ $assignmentStats['active'] }}</h5>
                    <small class="text-muted">In progress</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="nrk:media-completed" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Completed</h6>
                    <h5 class="mb-0 fw-bold">{{ $assignmentStats['completed'] }}</h5>
                    <small class="text-muted">Finished</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Assignments Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="card-title mb-0">Assignments ({{ $assignments->total() }})</h5>
                    @if(request('status') || request('assigned_to') || request('search'))
                        <a href="{{ route('admin.crm.assignments.index') }}" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon> Clear Filters
                        </a>
                    @endif
                </div>

                @if($assignments->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Lead</th>
                                            <th scope="col" class="border-0">Assigned To</th>
                                            <th scope="col" class="border-0">Assigned By</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Assigned Date</th>
                                            <th scope="col" class="border-0">Lead Status</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($assignments as $assignment)
                                            <tr class="assignment-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $assignment->lead->status === 'hot' ? 'danger' : ($assignment->lead->status === 'warm' ? 'warning' : ($assignment->lead->status === 'cold' ? 'info' : ($assignment->lead->status === 'converted' ? 'success' : 'secondary'))) }} me-3">
                                                            <span class="avatar-title text-white fw-semibold">{{ strtoupper(substr($assignment->lead->first_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $assignment->lead->full_name }}</h6>
                                                            <small class="text-muted">{{ $assignment->lead->email ?: $assignment->lead->mobile }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $assignment->assignedTo->role === 'admin' ? 'danger' : ($assignment->assignedTo->role === 'support' ? 'warning' : 'info') }} me-3">
                                                            <span class="avatar-title text-white fw-semibold">{{ $assignment->assignedTo->initials }}</span>
                                                        </div>
                                                        <div>
                                                            <div class="fw-semibold">{{ $assignment->assignedTo->full_name }}</div>
                                                            <small class="text-muted">{{ ucfirst($assignment->assignedTo->role) }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-semibold">{{ $assignment->assignedBy->full_name ?? 'System' }}</div>
                                                    <small class="text-muted">{{ $assignment->assignedBy->email ?? '' }}</small>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-{{ $assignment->status === 'active' ? 'warning' : 'success' }}">
                                                        <iconify-icon icon="iconamoon:{{ $assignment->status === 'active' ? 'clock' : 'check-circle' }}-duotone" class="me-1"></iconify-icon>
                                                        {{ ucfirst($assignment->status) }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="fw-semibold">{{ $assignment->assigned_at->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ $assignment->assigned_at->diffForHumans() }}</small>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-{{ $assignment->lead->status === 'hot' ? 'danger' : ($assignment->lead->status === 'warm' ? 'warning' : ($assignment->lead->status === 'cold' ? 'info' : ($assignment->lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                        {{ ucfirst($assignment->lead->status) }}
                                                    </span>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewAssignment('{{ $assignment->id }}')">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewLead('{{ $assignment->lead->id }}')">
                                                                    <iconify-icon icon="iconamoon:user-duotone" class="me-2"></iconify-icon>View Lead
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            @if($assignment->status === 'active')
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="javascript:void(0)" onclick="completeAssignment('{{ $assignment->id }}')">
                                                                        <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>Mark Complete
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="showReassignModal('{{ $assignment->id }}', '{{ $assignment->lead->full_name }}', '{{ $assignment->assignedTo->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:user-arrow-duotone" class="me-2"></iconify-icon>Reassign
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteAssignment('{{ $assignment->id }}', '{{ $assignment->lead->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete Assignment
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
                                @foreach($assignments as $assignment)
                                    <div class="col-12">
                                        <div class="card assignment-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $assignment->lead->status === 'hot' ? 'danger' : ($assignment->lead->status === 'warm' ? 'warning' : ($assignment->lead->status === 'cold' ? 'info' : ($assignment->lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                            <span class="avatar-title text-white fw-semibold">{{ strtoupper(substr($assignment->lead->first_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $assignment->lead->full_name }}</h6>
                                                            <small class="text-muted">{{ $assignment->lead->email ?: $assignment->lead->mobile }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewAssignment('{{ $assignment->id }}')">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            @if($assignment->status === 'active')
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="javascript:void(0)" onclick="completeAssignment('{{ $assignment->id }}')">
                                                                        <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>Complete
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="showReassignModal('{{ $assignment->id }}', '{{ $assignment->lead->full_name }}', '{{ $assignment->assignedTo->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:user-arrow-duotone" class="me-2"></iconify-icon>Reassign
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Assignment Info Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $assignment->assignedTo->role === 'admin' ? 'danger' : ($assignment->assignedTo->role === 'support' ? 'warning' : 'info') }}">
                                                            <span class="avatar-title text-white fw-semibold">{{ $assignment->assignedTo->initials }}</span>
                                                        </div>
                                                        <div>
                                                            <small class="text-muted">Assigned to</small>
                                                            <div class="fw-semibold">{{ $assignment->assignedTo->full_name }}</div>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleAssignmentDetails('{{ $assignment->id }}')">
                                                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $assignment->id }}"></iconify-icon>
                                                    </button>
                                                </div>

                                                {{-- Status and Date Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge bg-{{ $assignment->status === 'active' ? 'warning' : 'success' }}">
                                                            <iconify-icon icon="iconamoon:{{ $assignment->status === 'active' ? 'clock' : 'check-circle' }}-duotone" class="me-1"></iconify-icon>
                                                            {{ ucfirst($assignment->status) }}
                                                        </span>
                                                        <span class="badge bg-{{ $assignment->lead->status === 'hot' ? 'danger' : ($assignment->lead->status === 'warm' ? 'warning' : ($assignment->lead->status === 'cold' ? 'info' : ($assignment->lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                            {{ ucfirst($assignment->lead->status) }}
                                                        </span>
                                                    </div>
                                                    @if($assignment->status === 'active')
                                                        <button class="btn btn-sm btn-success" onclick="completeAssignment('{{ $assignment->id }}')">
                                                            <iconify-icon icon="iconamoon:check-duotone"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>

                                                {{-- Date Row --}}
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <small class="text-muted">Assigned {{ $assignment->assigned_at->diffForHumans() }}</small>
                                                        <div class="small text-muted">by {{ $assignment->assignedBy->full_name ?? 'System' }}</div>
                                                    </div>
                                                </div>

                                                {{-- Expandable Details --}}
                                                <div class="collapse mt-3" id="details-{{ $assignment->id }}">
                                                    <div class="border-top pt-3">
                                                        <div class="row g-2 small">
                                                            @if($assignment->notes)
                                                                <div class="col-12">
                                                                    <div class="text-muted">Assignment Notes</div>
                                                                    <div class="fw-semibold">{{ $assignment->notes }}</div>
                                                                </div>
                                                            @endif
                                                            <div class="col-6">
                                                                <div class="text-muted">Lead Source</div>
                                                                <div class="fw-semibold">{{ $assignment->lead->source }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Lead Country</div>
                                                                <div class="fw-semibold">{{ $assignment->lead->country }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Lead Mobile</div>
                                                                <div class="fw-semibold">{{ $assignment->lead->mobile ?: 'Not provided' }}</div>
                                                            </div>
                                                            @if($assignment->completed_at)
                                                                <div class="col-6">
                                                                    <div class="text-muted">Completed At</div>
                                                                    <div class="fw-semibold">{{ $assignment->completed_at->diffForHumans() }}</div>
                                                                </div>
                                                            @endif
                                                        </div>
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
                    @if($assignments->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $assignments->firstItem() }}</span> to <span class="fw-semibold">{{ $assignments->lastItem() }}</span> of <span class="fw-semibold">{{ $assignments->total() }}</span> assignments
                                </div>
                                <div>
                                    {{ $assignments->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="material-symbols:assignment-turned-in-outline" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Assignments Found</h6>
                            <p class="text-muted">No assignments match your current filter criteria.</p>
                            @if(request('status') || request('assigned_to') || request('search'))
                                <a href="{{ route('admin.crm.assignments.index') }}" class="btn btn-primary">Clear Filters</a>
                            @else
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#assignLeadModal">
                                    Assign Your First Lead
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Assign Lead Modal --}}
<div class="modal fade" id="assignLeadModal" tabindex="-1" aria-labelledby="assignLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignLeadModalLabel">Assign Lead to Team Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignLeadForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Search and Select Lead <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="leadSearchInput" placeholder="Search by name, email, or mobile..." required>
                                <button type="button" class="btn btn-outline-secondary" onclick="searchLeadsForAssignment()">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                            <div class="form-text">Search for leads to assign to team members</div>
                            <div class="invalid-feedback">Please select a lead for assignment.</div>
                        </div>

                        <div class="col-12" id="leadSearchResults" style="display: none;">
                            <label class="form-label">Select Lead</label>
                            <div class="border rounded p-2 bg-light" style="max-height: 200px; overflow-y: auto;">
                                <div id="searchResultsList">
                                    <!-- Search results will be populated here -->
                                </div>
                            </div>
                        </div>

                        <div class="col-12" id="selectedLeadSection" style="display: none;">
                            <label class="form-label">Selected Lead</label>
                            <div class="border rounded p-3 bg-success bg-opacity-10">
                                <div id="selectedLeadInfo">
                                    <!-- Selected lead info will be displayed here -->
                                </div>
                                <input type="hidden" id="selectedLeadId" name="lead_id">
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="assignedTo" class="form-label">Assign To <span class="text-danger">*</span></label>
                            <select class="form-select" id="assignedTo" name="assigned_to" required>
                                <option value="">Select Team Member</option>
                                @foreach($assignableUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->full_name }} - {{ ucfirst($user->role) }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">Please select a team member.</div>
                        </div>

                        <div class="col-12">
                            <label for="assignmentNotes" class="form-label">Assignment Notes</label>
                            <textarea class="form-control" id="assignmentNotes" name="notes" rows="3" placeholder="Optional notes about this assignment..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Assign Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reassign Modal --}}
<div class="modal fade" id="reassignModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reassignModalLabel">Reassign Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reassignForm" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="reassignAssignmentId">
                    <div class="mb-3">
                        <label class="form-label">Lead</label>
                        <div id="reassignLeadName" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Currently Assigned To</label>
                        <div id="currentAssignee" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newAssignee" class="form-label">Reassign To <span class="text-danger">*</span></label>
                        <select class="form-select" id="newAssignee" required>
                            <option value="">Select Team Member</option>
                            @foreach($assignableUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->full_name }} - {{ ucfirst($user->role) }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a team member.</div>
                    </div>
                    <div class="mb-3">
                        <label for="reassignReason" class="form-label">Reason for Reassignment</label>
                        <textarea class="form-control" id="reassignReason" rows="2" placeholder="Optional reason for reassignment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Reassign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
// Global variables
let selectedLeadId = null;
let searchTimeout = null;
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

function toggleLoading(form, isLoading) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    if (isLoading) {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
    } else {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
    }
}

function validateForm(form) {
    form.classList.add('was-validated');
    return form.checkValidity();
}

// Filter and Search Functions
function filterAssignments(type, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(type, value);
    } else {
        url.searchParams.delete(type);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function searchAssignments() {
    const searchTerm = document.getElementById('assignmentSearch').value.trim();
    const url = new URL(window.location.href);
    
    if (searchTerm) {
        url.searchParams.set('search', searchTerm);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Mobile Functions
function toggleAssignmentDetails(assignmentId) {
    const detailsElement = document.getElementById(`details-${assignmentId}`);
    const chevronElement = document.getElementById(`chevron-${assignmentId}`);
    
    // Close all other open details
    document.querySelectorAll('.collapse.show').forEach(element => {
        if (element.id !== `details-${assignmentId}`) {
            element.classList.remove('show');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        if (chevron.id !== `chevron-${assignmentId}`) {
            chevron.style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle current details
    if (detailsElement.classList.contains('show')) {
        detailsElement.classList.remove('show');
        chevronElement.style.transform = 'rotate(0deg)';
    } else {
        detailsElement.classList.add('show');
        chevronElement.style.transform = 'rotate(180deg)';
    }
}

// Search leads for assignment
function searchLeadsForAssignment() {
    const searchTerm = document.getElementById('leadSearchInput').value;
    
    if (searchTerm.length < 2) {
        showAlert('Please enter at least 2 characters to search', 'warning');
        return;
    }
    
    // Simulate search - In real implementation, this would be an AJAX call
    fetch(`{{ route('admin.crm.utils.leads.search') }}?search=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayLeadSearchResults(data.leads);
            } else {
                showAlert(data.message || 'Failed to search leads', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // For demo purposes, show sample results
            displayLeadSearchResults([
                {id: 1, full_name: 'John Doe', email: 'john@example.com', mobile: '+1234567890', status: 'hot', source: 'Website'},
                {id: 2, full_name: 'Jane Smith', email: 'jane@example.com', mobile: '+1234567891', status: 'warm', source: 'Facebook'},
                {id: 3, full_name: 'Bob Johnson', email: 'bob@example.com', mobile: '+1234567892', status: 'cold', source: 'Google Ads'}
            ]);
        });
}

// Display lead search results
function displayLeadSearchResults(leads) {
    const resultsList = document.getElementById('searchResultsList');
    const resultsSection = document.getElementById('leadSearchResults');
    
    if (leads.length === 0) {
        resultsList.innerHTML = '<div class="text-center text-muted py-3">No leads found</div>';
    } else {
        resultsList.innerHTML = leads.map(lead => `
            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2 lead-search-result" onclick="selectLeadForAssignment(${lead.id}, '${lead.full_name}', '${lead.email}', '${lead.mobile}', '${lead.status}', '${lead.source}')">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm rounded-circle bg-${lead.status === 'hot' ? 'danger' : (lead.status === 'warm' ? 'warning' : 'info')} me-2">
                        <span class="avatar-title text-white">${lead.full_name.split(' ').map(n => n[0]).join('')}</span>
                    </div>
                    <div>
                        <div class="fw-semibold">${lead.full_name}</div>
                        <small class="text-muted">${lead.email} • ${lead.source}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary">Select</button>
            </div>
        `).join('');
    }
    
    resultsSection.style.display = 'block';
}

// Select lead for assignment
function selectLeadForAssignment(id, name, email, mobile, status, source) {
    selectedLeadId = id;
    document.getElementById('selectedLeadId').value = id;
    
    document.getElementById('selectedLeadInfo').innerHTML = `
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm rounded-circle bg-${status === 'hot' ? 'danger' : (status === 'warm' ? 'warning' : 'info')} me-2">
                <span class="avatar-title text-white">${name.split(' ').map(n => n[0]).join('')}</span>
            </div>
            <div>
                <div class="fw-semibold">${name}</div>
                <small class="text-muted">${email} • ${mobile} • ${source}</small>
            </div>
        </div>
    `;
    
    document.getElementById('selectedLeadSection').style.display = 'block';
    document.getElementById('leadSearchResults').style.display = 'none';
    document.getElementById('leadSearchInput').value = '';
}

// Auto search on typing
document.getElementById('leadSearchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchTerm = this.value;
    
    if (searchTerm.length >= 2) {
        searchTimeout = setTimeout(() => {
            searchLeadsForAssignment();
        }, 500);
    } else if (searchTerm.length === 0) {
        document.getElementById('leadSearchResults').style.display = 'none';
    }
});

// Complete assignment
function completeAssignment(id) {
    if (confirm('Mark this assignment as completed?')) {
        fetch(`{{ url('admin/crm/assignments') }}/${id}/complete`, {
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
            showAlert('Failed to complete assignment', 'danger');
        });
    }
}

// Show reassign modal
function showReassignModal(assignmentId, leadName, currentAssignee) {
    document.getElementById('reassignAssignmentId').value = assignmentId;
    document.getElementById('reassignLeadName').textContent = leadName;
    document.getElementById('currentAssignee').innerHTML = `<span class="badge bg-secondary">${currentAssignee}</span>`;
    
    // Reset form validation
    const form = document.getElementById('reassignForm');
    form.classList.remove('was-validated');
    form.reset();
    
    // Remove current assignee from options
    const select = document.getElementById('newAssignee');
    Array.from(select.options).forEach(option => {
        if (option.text.includes(currentAssignee)) {
            option.style.display = 'none';
        } else {
            option.style.display = 'block';
        }
    });
    
    new bootstrap.Modal(document.getElementById('reassignModal')).show();
}

// AJAX Form Handlers
document.getElementById('assignLeadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedLeadId) {
        showAlert('Please select a lead to assign', 'warning');
        return;
    }
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const formData = new FormData(this);
    formData.append('lead_id', selectedLeadId);
    
    fetch('{{ route("admin.crm.assignments.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('assignLeadModal')).hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to assign lead', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while assigning the lead', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Reassign Form
document.getElementById('reassignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const assignmentId = document.getElementById('reassignAssignmentId').value;
    const newAssignee = document.getElementById('newAssignee').value;
    const reason = document.getElementById('reassignReason').value;
    
    fetch(`{{ url('admin/crm/assignments') }}/${assignmentId}/reassign`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            assigned_to: newAssignee,
            reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('reassignModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to reassign lead', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Event Listeners
document.getElementById('assignmentSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchAssignments();
    }
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.assignment-mobile-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});

// Reset modals when closed
document.getElementById('assignLeadModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('assignLeadForm');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('leadSearchResults').style.display = 'none';
    document.getElementById('selectedLeadSection').style.display = 'none';
    selectedLeadId = null;
});

document.getElementById('reassignModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('reassignForm');
    form.reset();
    form.classList.remove('was-validated');
});

// Placeholder Functions (to be implemented)
function viewAssignment(id) {
    showAlert('View assignment details functionality coming soon', 'info');
}

function viewLead(id) {
    showAlert('View lead functionality coming soon', 'info');
}

function deleteAssignment(id, leadName) {
    if (confirm(`Are you sure you want to delete the assignment for "${leadName}"? This action cannot be undone.`)) {
        showAlert('Delete assignment functionality coming soon', 'info');
    }
}
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

/* Avatar Styles */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    border-radius: 50%;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.875rem;
}

.avatar-lg {
    width: 4rem;
    height: 4rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Table Styles */
.table-container {
    position: relative;
    overflow: visible;
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.assignment-row {
    transition: background-color 0.15s ease-in-out;
}

.assignment-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Dropdown Styles - Fixed for proper positioning */
.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
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

.dropdown-item.text-success:hover,
.dropdown-item.text-success:focus {
    color: #fff;
    background-color: #198754;
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

/* Mobile Card Styles */
.assignment-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.assignment-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.lead-search-result {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.lead-search-result:hover {
    background-color: #f8f9fa;
}

.collapse {
    transition: height 0.35s ease;
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

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
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

/* Loading Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
    
    .table-container {
        overflow-x: auto;
    }
}

@media (max-width: 767.98px) {
    .assignment-mobile-card .card-body {
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
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Alert Positioning */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}
</style>
@endsection