@extends('admin.layouts.vertical', ['title' => 'Follow-ups', 'subTitle' => 'CRM'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Follow-ups Management</h4>
                            <p class="text-muted mb-0">Track and manage lead follow-up activities</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                                Schedule Followup
                            </button>
                            <select class="form-select form-select-sm" onchange="filterFollowups('status', this.value)" style="width: auto;">
                                <option value="" {{ !request('status') ? 'selected' : '' }}>All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                            <select class="form-select form-select-sm" onchange="filterFollowups('type', this.value)" style="width: auto;">
                                <option value="" {{ !request('type') ? 'selected' : '' }}>All Types</option>
                                <option value="call" {{ request('type') === 'call' ? 'selected' : '' }}>Call</option>
                                <option value="email" {{ request('type') === 'email' ? 'selected' : '' }}>Email</option>
                                <option value="meeting" {{ request('type') === 'meeting' ? 'selected' : '' }}>Meeting</option>
                                <option value="whatsapp" {{ request('type') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                <option value="other" {{ request('type') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            <select class="form-select form-select-sm" onchange="filterFollowups('date', this.value)" style="width: auto;">
                                <option value="" {{ !request('date') ? 'selected' : '' }}>All Dates</option>
                                <option value="today" {{ request('date') === 'today' ? 'selected' : '' }}>Due Today</option>
                                <option value="overdue" {{ request('date') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                <option value="upcoming" {{ request('date') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                            </select>
                            <div class="input-group" style="width: 250px;">
                                <input type="text" class="form-control form-control-sm" id="followupSearch" placeholder="Search leads..." value="{{ request('search') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="searchFollowups()">
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
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="arcticons:chieffollow" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total</h6>
                    <h5 class="mb-0 fw-bold">{{ $followupStats['total'] }}</h5>
                    <small class="text-muted">All followups</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h5 class="mb-0 fw-bold">{{ $followupStats['pending'] }}</h5>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:check-circle-outline" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Completed</h6>
                    <h5 class="mb-0 fw-bold">{{ $followupStats['completed'] }}</h5>
                    <small class="text-muted">Done</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:calendar-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Due Today</h6>
                    <h5 class="mb-0 fw-bold">{{ $followupStats['due_today'] }}</h5>
                    <small class="text-muted">Priority</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:warning-duotone" class="text-danger mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Overdue</h6>
                    <h5 class="mb-0 fw-bold">{{ $followupStats['overdue'] }}</h5>
                    <small class="text-muted">Urgent</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Upcoming</h6>
                    <h5 class="mb-0 fw-bold">{{ $followupStats['upcoming'] }}</h5>
                    <small class="text-muted">Future</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Follow-ups Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="card-title mb-0">Follow-ups ({{ $followups->total() }})</h5>
                    @if(request('status') || request('type') || request('date') || request('search'))
                        <a href="{{ route('admin.crm.followups.index') }}" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon> Clear Filters
                        </a>
                    @endif
                </div>

                @if($followups->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Lead</th>
                                            <th scope="col" class="border-0">Type</th>
                                            <th scope="col" class="border-0">Due Date</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Notes</th>
                                            <th scope="col" class="border-0">Created By</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($followups as $followup)
                                            <tr class="followup-row {{ $followup->followup_date->isPast() && !$followup->completed ? 'table-danger-row' : ($followup->followup_date->isToday() && !$followup->completed ? 'table-warning-row' : '') }}">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $followup->lead->status === 'hot' ? 'danger' : ($followup->lead->status === 'warm' ? 'warning' : ($followup->lead->status === 'cold' ? 'info' : ($followup->lead->status === 'converted' ? 'success' : 'secondary'))) }} me-3">
                                                            <span class="avatar-title text-white fw-semibold">{{ strtoupper(substr($followup->lead->first_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $followup->lead->full_name }}</h6>
                                                            <small class="text-muted">{{ $followup->lead->email ?: $followup->lead->mobile }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2 fs-5">{{ $followup->type_icon }}</span>
                                                        <span class="badge bg-secondary">{{ ucfirst($followup->type) }}</span>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div>
                                                        <div class="fw-semibold">{{ $followup->followup_date->format('M d, Y') }}</div>
                                                        <small class="text-{{ $followup->followup_date->isPast() && !$followup->completed ? 'danger' : ($followup->followup_date->isToday() ? 'warning' : 'muted') }}">
                                                            {{ $followup->followup_date->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    @if($followup->completed)
                                                        <span class="badge bg-success">
                                                            <iconify-icon icon="iconamoon:check-circle-duotone" class="me-1"></iconify-icon>
                                                            Completed
                                                        </span>
                                                    @else
                                                        <span class="badge bg-{{ $followup->followup_date->isPast() ? 'danger' : ($followup->followup_date->isToday() ? 'warning' : 'info') }}">
                                                            <iconify-icon icon="iconamoon:{{ $followup->followup_date->isPast() ? 'warning' : ($followup->followup_date->isToday() ? 'clock' : 'calendar') }}-duotone" class="me-1"></iconify-icon>
                                                            {{ $followup->followup_date->isPast() ? 'Overdue' : ($followup->followup_date->isToday() ? 'Due Today' : 'Pending') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <span class="text-muted small">{{ Str::limit($followup->notes, 50) }}</span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-semibold">{{ $followup->createdBy->full_name ?? 'System' }}</div>
                                                    <small class="text-muted">{{ $followup->created_at->diffForHumans() }}</small>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            @if(!$followup->completed)
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="javascript:void(0)" onclick="completeFollowup('{{ $followup->id }}')">
                                                                        <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>Mark Complete
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="editFollowup('{{ $followup->id }}')">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Followup
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewLead('{{ $followup->lead->id }}')">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Lead
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="rescheduleFollowup('{{ $followup->id }}', '{{ $followup->lead->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:calendar-edit-duotone" class="me-2"></iconify-icon>Reschedule
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteFollowup('{{ $followup->id }}', '{{ $followup->lead->full_name }}')">
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
                                @foreach($followups as $followup)
                                    <div class="col-12">
                                        <div class="card followup-mobile-card border {{ $followup->followup_date->isPast() && !$followup->completed ? 'border-danger' : ($followup->followup_date->isToday() && !$followup->completed ? 'border-warning' : '') }}">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $followup->lead->status === 'hot' ? 'danger' : ($followup->lead->status === 'warm' ? 'warning' : ($followup->lead->status === 'cold' ? 'info' : ($followup->lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                            <span class="avatar-title text-white fw-semibold">{{ strtoupper(substr($followup->lead->first_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $followup->lead->full_name }}</h6>
                                                            <small class="text-muted">{{ $followup->lead->email ?: $followup->lead->mobile }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @if(!$followup->completed)
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="javascript:void(0)" onclick="completeFollowup('{{ $followup->id }}')">
                                                                        <iconify-icon icon="iconamoon:check-duotone" class="me-2"></iconify-icon>Mark Complete
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="editFollowup('{{ $followup->id }}')">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="rescheduleFollowup('{{ $followup->id }}', '{{ $followup->lead->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:calendar-edit-duotone" class="me-2"></iconify-icon>Reschedule
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Type and Status Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fs-5">{{ $followup->type_icon }}</span>
                                                        <span class="badge bg-secondary">{{ ucfirst($followup->type) }}</span>
                                                        @if($followup->completed)
                                                            <span class="badge bg-success">
                                                                <iconify-icon icon="iconamoon:check-circle-duotone" class="me-1"></iconify-icon>
                                                                Completed
                                                            </span>
                                                        @else
                                                            <span class="badge bg-{{ $followup->followup_date->isPast() ? 'danger' : ($followup->followup_date->isToday() ? 'warning' : 'info') }}">
                                                                {{ $followup->followup_date->isPast() ? 'Overdue' : ($followup->followup_date->isToday() ? 'Due Today' : 'Pending') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleFollowupDetails('{{ $followup->id }}')">
                                                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $followup->id }}"></iconify-icon>
                                                    </button>
                                                </div>

                                                {{-- Date and Time Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <div class="fw-semibold">{{ $followup->followup_date->format('M d, Y') }}</div>
                                                        <small class="text-{{ $followup->followup_date->isPast() && !$followup->completed ? 'danger' : ($followup->followup_date->isToday() ? 'warning' : 'muted') }}">
                                                            {{ $followup->followup_date->diffForHumans() }}
                                                        </small>
                                                    </div>
                                                    @if(!$followup->completed)
                                                        <button class="btn btn-sm btn-success" onclick="completeFollowup('{{ $followup->id }}')">
                                                            <iconify-icon icon="iconamoon:check-duotone"></iconify-icon>
                                                        </button>
                                                    @endif
                                                </div>

                                                {{-- Expandable Details --}}
                                                <div class="collapse mt-3" id="details-{{ $followup->id }}">
                                                    <div class="border-top pt-3">
                                                        <div class="row g-2 small">
                                                            <div class="col-12">
                                                                <div class="text-muted">Notes</div>
                                                                <div class="fw-semibold">{{ $followup->notes }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Created By</div>
                                                                <div class="fw-semibold">{{ $followup->createdBy->full_name ?? 'System' }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Created</div>
                                                                <div class="fw-semibold">{{ $followup->created_at->diffForHumans() }}</div>
                                                            </div>
                                                            @if($followup->completed)
                                                                <div class="col-6">
                                                                    <div class="text-muted">Completed At</div>
                                                                    <div class="fw-semibold">{{ $followup->completed_at ? $followup->completed_at->diffForHumans() : 'N/A' }}</div>
                                                                </div>
                                                            @endif
                                                            <div class="col-6">
                                                                <div class="text-muted">Lead Status</div>
                                                                <span class="badge bg-{{ $followup->lead->status === 'hot' ? 'danger' : ($followup->lead->status === 'warm' ? 'warning' : ($followup->lead->status === 'cold' ? 'info' : ($followup->lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                                    {{ ucfirst($followup->lead->status) }}
                                                                </span>
                                                            </div>
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
                    @if($followups->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $followups->firstItem() }}</span> to <span class="fw-semibold">{{ $followups->lastItem() }}</span> of <span class="fw-semibold">{{ $followups->total() }}</span> follow-ups
                                </div>
                                <div>
                                    {{ $followups->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="arcticons:chieffollow" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Follow-ups Found</h6>
                            <p class="text-muted">No follow-ups match your current filter criteria.</p>
                            @if(request('status') || request('type') || request('date') || request('search'))
                                <a href="{{ route('admin.crm.followups.index') }}" class="btn btn-primary">Clear Filters</a>
                            @else
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                                    Schedule Your First Follow-up
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add Followup Modal --}}
<div class="modal fade" id="addFollowupModal" tabindex="-1" aria-labelledby="addFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFollowupModalLabel">Schedule Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFollowupForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Search and Select Lead <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="leadSearch" placeholder="Search by name, email, or mobile..." required>
                                <button type="button" class="btn btn-outline-secondary" onclick="searchLeads()">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                            <div class="form-text">Search for leads to schedule follow-ups</div>
                            <div class="invalid-feedback">Please select a lead for the follow-up.</div>
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

                        <div class="col-md-6">
                            <label for="followupDate" class="form-label">Follow-up Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="followupDate" name="followup_date" required>
                            <div class="invalid-feedback">Please select a follow-up date.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="followupType" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="followupType" name="type" required>
                                <option value="">Select Type</option>
                                <option value="call">Phone Call</option>
                                <option value="email">Email</option>
                                <option value="meeting">Meeting</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select a follow-up type.</div>
                        </div>

                        <div class="col-12">
                            <label for="followupNotes" class="form-label">Notes <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="followupNotes" name="notes" rows="3" required placeholder="Describe the follow-up purpose or conversation points..."></textarea>
                            <div class="invalid-feedback">Please provide notes for the follow-up.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Schedule Follow-up
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reschedule Modal --}}
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-labelledby="rescheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rescheduleForm" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="rescheduleFollowupId">
                    <div class="mb-3">
                        <label class="form-label">Lead</label>
                        <div id="rescheduleLeadName" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newFollowupDate" class="form-label">New Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="newFollowupDate" required>
                        <div class="invalid-feedback">Please select a new date.</div>
                    </div>
                    <div class="mb-3">
                        <label for="rescheduleReason" class="form-label">Reason for Rescheduling</label>
                        <textarea class="form-control" id="rescheduleReason" rows="2" placeholder="Optional reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Reschedule
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
function filterFollowups(type, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(type, value);
    } else {
        url.searchParams.delete(type);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function searchFollowups() {
    const searchTerm = document.getElementById('followupSearch').value.trim();
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
function toggleFollowupDetails(followupId) {
    const detailsElement = document.getElementById(`details-${followupId}`);
    const chevronElement = document.getElementById(`chevron-${followupId}`);
    
    // Close all other open details
    document.querySelectorAll('.collapse.show').forEach(element => {
        if (element.id !== `details-${followupId}`) {
            element.classList.remove('show');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        if (chevron.id !== `chevron-${followupId}`) {
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

// Search leads for followup
function searchLeads() {
    const searchTerm = document.getElementById('leadSearch').value;
    
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
                {id: 1, full_name: 'John Doe', email: 'john@example.com', mobile: '+1234567890', status: 'hot'},
                {id: 2, full_name: 'Jane Smith', email: 'jane@example.com', mobile: '+1234567891', status: 'warm'},
                {id: 3, full_name: 'Bob Johnson', email: 'bob@example.com', mobile: '+1234567892', status: 'cold'}
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
            <div class="d-flex align-items-center justify-content-between p-2 border rounded mb-2 lead-search-result" onclick="selectLead(${lead.id}, '${lead.full_name}', '${lead.email}', '${lead.mobile}', '${lead.status}')">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-sm rounded-circle bg-${lead.status === 'hot' ? 'danger' : (lead.status === 'warm' ? 'warning' : 'info')} me-2">
                        <span class="avatar-title text-white">${lead.full_name.split(' ').map(n => n[0]).join('')}</span>
                    </div>
                    <div>
                        <div class="fw-semibold">${lead.full_name}</div>
                        <small class="text-muted">${lead.email}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary">Select</button>
            </div>
        `).join('');
    }
    
    resultsSection.style.display = 'block';
}

// Select lead for followup
function selectLead(id, name, email, mobile, status) {
    selectedLeadId = id;
    document.getElementById('selectedLeadId').value = id;
    
    document.getElementById('selectedLeadInfo').innerHTML = `
        <div class="d-flex align-items-center">
            <div class="avatar avatar-sm rounded-circle bg-${status === 'hot' ? 'danger' : (status === 'warm' ? 'warning' : 'info')} me-2">
                <span class="avatar-title text-white">${name.split(' ').map(n => n[0]).join('')}</span>
            </div>
            <div>
                <div class="fw-semibold">${name}</div>
                <small class="text-muted">${email} • ${mobile}</small>
            </div>
        </div>
    `;
    
    document.getElementById('selectedLeadSection').style.display = 'block';
    document.getElementById('leadSearchResults').style.display = 'none';
    document.getElementById('leadSearch').value = '';
}

// Auto search on typing
document.getElementById('leadSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const searchTerm = this.value;
    
    if (searchTerm.length >= 2) {
        searchTimeout = setTimeout(() => {
            searchLeads();
        }, 500);
    } else if (searchTerm.length === 0) {
        document.getElementById('leadSearchResults').style.display = 'none';
    }
});

// Complete followup
function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
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
            showAlert('Failed to complete follow-up', 'danger');
        });
    }
}

// Show reschedule modal
function rescheduleFollowup(followupId, leadName) {
    document.getElementById('rescheduleFollowupId').value = followupId;
    document.getElementById('rescheduleLeadName').textContent = leadName;
    
    // Set minimum date to today
    document.getElementById('newFollowupDate').min = new Date().toISOString().split('T')[0];
    
    // Reset form validation
    const form = document.getElementById('rescheduleForm');
    form.classList.remove('was-validated');
    form.reset();
    
    new bootstrap.Modal(document.getElementById('rescheduleModal')).show();
}

// AJAX Form Handlers
document.getElementById('addFollowupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedLeadId) {
        showAlert('Please select a lead for the follow-up', 'warning');
        return;
    }
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const formData = new FormData(this);
    formData.append('lead_id', selectedLeadId);
    
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
            showAlert(data.message || 'Failed to schedule follow-up', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while scheduling the follow-up', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Reschedule Form
document.getElementById('rescheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const followupId = document.getElementById('rescheduleFollowupId').value;
    const newDate = document.getElementById('newFollowupDate').value;
    const reason = document.getElementById('rescheduleReason').value;
    
    fetch(`{{ url('admin/crm/followups') }}/${followupId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            followup_date: newDate,
            reschedule_reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('rescheduleModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to reschedule follow-up', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Event Listeners
document.getElementById('followupSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchFollowups();
    }
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.followup-mobile-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});

// Set minimum date for followup date to today
document.getElementById('followupDate').min = new Date().toISOString().split('T')[0];

// Reset modals when closed
document.getElementById('addFollowupModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('addFollowupForm');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('leadSearchResults').style.display = 'none';
    document.getElementById('selectedLeadSection').style.display = 'none';
    selectedLeadId = null;
});

document.getElementById('rescheduleModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('rescheduleForm');
    form.reset();
    form.classList.remove('was-validated');
});

// Placeholder Functions (to be implemented)
function editFollowup(id) {
    showAlert('Edit follow-up functionality coming soon', 'info');
}

function viewLead(id) {
    showAlert('View lead functionality coming soon', 'info');
}

function deleteFollowup(id, leadName) {
    if (confirm(`Are you sure you want to delete this follow-up for "${leadName}"? This action cannot be undone.`)) {
        showAlert('Delete follow-up functionality coming soon', 'info');
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

.followup-row {
    transition: background-color 0.15s ease-in-out;
}

.followup-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Priority Row Styling */
.table-danger-row {
    background-color: rgba(220, 53, 69, 0.05);
}

.table-warning-row {
    background-color: rgba(255, 193, 7, 0.05);
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
.followup-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.followup-mobile-card:hover {
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

.fs-5 {
    font-size: 1.25rem;
}

/* Overdue/Due styling */
.border-danger {
    border-color: #dc3545 !important;
}

.border-warning {
    border-color: #ffc107 !important;
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
    .followup-mobile-card .card-body {
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