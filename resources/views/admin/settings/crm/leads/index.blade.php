@extends('admin.layouts.vertical', ['title' => 'All Leads', 'subTitle' => 'CRM'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">All Leads</h4>
                            <p class="text-muted mb-0">Manage and track your leads efficiently</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                                Add Lead
                            </button>
                            <select class="form-select form-select-sm" onchange="filterLeads('status', this.value)" style="width: auto;">
                                <option value="" {{ !request('status') ? 'selected' : '' }}>All Status</option>
                                <option value="hot" {{ request('status') === 'hot' ? 'selected' : '' }}>Hot</option>
                                <option value="warm" {{ request('status') === 'warm' ? 'selected' : '' }}>Warm</option>
                                <option value="cold" {{ request('status') === 'cold' ? 'selected' : '' }}>Cold</option>
                                <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                                <option value="lost" {{ request('status') === 'lost' ? 'selected' : '' }}>Lost</option>
                            </select>
                            <select class="form-select form-select-sm" onchange="filterLeads('source', this.value)" style="width: auto;">
                                <option value="" {{ !request('source') ? 'selected' : '' }}>All Sources</option>
                                @foreach($sources as $source)
                                    <option value="{{ $source }}" {{ request('source') === $source ? 'selected' : '' }}>{{ $source }}</option>
                                @endforeach
                            </select>
                            <div class="input-group" style="width: 250px;">
                                <input type="text" class="form-control form-control-sm" id="leadSearch" placeholder="Search leads..." value="{{ request('search') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="searchLeads()">
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
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="simple-icons:googleads" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total</h6>
                    <h5 class="mb-0 fw-bold">{{ $leadStats['total'] }}</h5>
                    <small class="text-muted">All leads</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="mdi:firebase" class="text-danger mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Hot</h6>
                    <h5 class="mb-0 fw-bold">{{ $leadStats['hot'] }}</h5>
                    <small class="text-muted">High priority</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:device-thermostat" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Warm</h6>
                    <h5 class="mb-0 fw-bold">{{ $leadStats['warm'] }}</h5>
                    <small class="text-muted">Medium priority</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="mdi:snowflake-variant" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Cold</h6>
                    <h5 class="mb-0 fw-bold">{{ $leadStats['cold'] }}</h5>
                    <small class="text-muted">Low priority</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:check-circle" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Converted</h6>
                    <h5 class="mb-0 fw-bold">{{ $leadStats['converted'] }}</h5>
                    <small class="text-muted">Success</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:calendar-apps-script-outline-sharp" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Today</h6>
                    <h5 class="mb-0 fw-bold">{{ $leadStats['today'] }}</h5>
                    <small class="text-muted">New leads</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Leads Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">


                @if($leads->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Lead</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Source</th>
                                            <th scope="col" class="border-0">Interest</th>
                                            <th scope="col" class="border-0">Country</th>
                                            <th scope="col" class="border-0">Next Followup</th>
                                            <th scope="col" class="border-0">Created</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($leads as $lead)
                                            <tr class="lead-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }} me-3">
                                                            <span class="avatar-title text-white fw-semibold">{{ strtoupper(substr($lead->first_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $lead->full_name }}</h6>
                                                            <small class="text-muted">{{ $lead->email ?: $lead->mobile }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                        {{ ucfirst($lead->status) }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-secondary">{{ $lead->source }}</span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-{{ $lead->interest === 'High' ? 'danger' : ($lead->interest === 'Medium' ? 'warning' : 'info') }}">
                                                        {{ $lead->interest }}
                                                    </span>
                                                </td>
                                                <td class="py-3">{{ $lead->country }}</td>
                                                <td class="py-3">
                                                    @if($lead->followups->count() > 0)
                                                        @php $nextFollowup = $lead->followups->first(); @endphp
                                                        <div class="small">
                                                            <div class="fw-semibold">{{ $nextFollowup->followup_date->format('M d, Y') }}</div>
                                                            <small class="text-muted">{{ ucfirst($nextFollowup->type) }}</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No followup</span>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="fw-semibold">{{ $lead->created_at->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ $lead->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewLead('{{ $lead->id }}')">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="editLead('{{ $lead->id }}')">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Lead
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="showStatusModal('{{ $lead->id }}', '{{ $lead->full_name }}', '{{ $lead->status }}')">
                                                                    <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-2"></iconify-icon>Change Status
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="showFollowupModal('{{ $lead->id }}', '{{ $lead->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:calendar-add-duotone" class="me-2"></iconify-icon>Add Followup
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="showAssignModal('{{ $lead->id }}', '{{ $lead->full_name }}')">
                                                                    <iconify-icon icon="material-symbols:frame-person" class="me-2"></iconify-icon>Assign Lead
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteLead('{{ $lead->id }}', '{{ $lead->full_name }}')">
                                                                    <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete Lead
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
                                @foreach($leads as $lead)
                                    <div class="col-12">
                                        <div class="card lead-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                            <span class="avatar-title text-white fw-semibold">{{ strtoupper(substr($lead->first_name, 0, 1)) }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $lead->full_name }}</h6>
                                                            <small class="text-muted">{{ $lead->email ?: $lead->mobile }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="viewLead('{{ $lead->id }}')">
                                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="editLead('{{ $lead->id }}')">
                                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Lead
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="showFollowupModal('{{ $lead->id }}', '{{ $lead->full_name }}')">
                                                                <iconify-icon icon="iconamoon:calendar-add-duotone" class="me-2"></iconify-icon>Add Followup
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Status and Badges Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                                                            {{ ucfirst($lead->status) }}
                                                        </span>
                                                        <span class="badge bg-secondary">{{ $lead->source }}</span>
                                                        <span class="badge bg-{{ $lead->interest === 'High' ? 'danger' : ($lead->interest === 'Medium' ? 'warning' : 'info') }}">
                                                            {{ $lead->interest }}
                                                        </span>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleLeadDetails('{{ $lead->id }}')">
                                                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $lead->id }}"></iconify-icon>
                                                    </button>
                                                </div>

                                                {{-- Basic Info Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <small class="text-muted d-block">{{ $lead->country }}</small>
                                                        <small class="text-muted">Created: {{ $lead->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    @if($lead->followups->count() > 0)
                                                        @php $nextFollowup = $lead->followups->first(); @endphp
                                                        <div class="text-end">
                                                            <small class="text-muted d-block">Next: {{ $nextFollowup->followup_date->format('M d') }}</small>
                                                            <small class="text-muted">{{ ucfirst($nextFollowup->type) }}</small>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Expandable Details --}}
                                                <div class="collapse mt-3" id="details-{{ $lead->id }}">
                                                    <div class="border-top pt-3">
                                                        <div class="row g-2 small">
                                                            <div class="col-6">
                                                                <div class="text-muted">Mobile</div>
                                                                <div class="fw-semibold">{{ $lead->mobile ?: 'Not provided' }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">WhatsApp</div>
                                                                <div class="fw-semibold">{{ $lead->whatsapp ?: 'Not provided' }}</div>
                                                            </div>
                                                            @if($lead->notes)
                                                                <div class="col-12">
                                                                    <div class="text-muted">Notes</div>
                                                                    <div class="small">{{ Str::limit($lead->notes, 100) }}</div>
                                                                </div>
                                                            @endif
                                                            <div class="col-6">
                                                                <div class="text-muted">Created By</div>
                                                                <div class="fw-semibold">{{ $lead->createdBy->full_name ?? 'System' }}</div>
                                                            </div>
                                                            @if($lead->assignments->count() > 0)
                                                                <div class="col-6">
                                                                    <div class="text-muted">Assigned To</div>
                                                                    <div class="fw-semibold">{{ $lead->assignments->first()->assignedTo->full_name }}</div>
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
                    @if($leads->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $leads->firstItem() }}</span> to <span class="fw-semibold">{{ $leads->lastItem() }}</span> of <span class="fw-semibold">{{ $leads->total() }}</span> leads
                                </div>
                                <div>
                                    {{ $leads->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="simple-icons:googleads" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Leads Found</h6>
                            <p class="text-muted">No leads match your current filter criteria.</p>
                            @if(request('status') || request('source') || request('search'))
                                <a href="{{ route('admin.crm.leads.index') }}" class="btn btn-primary">Clear Filters</a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add Lead Modal --}}
<div class="modal fade" id="addLeadModal" tabindex="-1" aria-labelledby="addLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLeadModalLabel">Add New Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addLeadForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" required>
                            <div class="invalid-feedback">Please provide a first name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" required>
                            <div class="invalid-feedback">Please provide a last name.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="mobile" required>
                            <div class="invalid-feedback">Please provide a mobile number.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input type="tel" class="form-control" name="whatsapp">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="country" required>
                            <div class="invalid-feedback">Please provide a country.</div>
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
                            <div class="invalid-feedback">Please select a source.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="">Select Status</option>
                                <option value="cold">Cold</option>
                                <option value="warm">Warm</option>
                                <option value="hot">Hot</option>
                            </select>
                            <div class="invalid-feedback">Please select a status.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Interest Level <span class="text-danger">*</span></label>
                            <select class="form-select" name="interest" required>
                                <option value="">Select Interest Level</option>
                                <option value="Low">Low</option>
                                <option value="Medium">Medium</option>
                                <option value="High">High</option>
                            </select>
                            <div class="invalid-feedback">Please select an interest level.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Additional information about the lead..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Add Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Change Status Modal --}}
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Change Lead Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changeStatusForm" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="statusLeadId">
                    <div class="mb-3">
                        <label class="form-label">Lead</label>
                        <div id="statusLeadName" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div id="currentStatus" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="newStatus" required>
                            <option value="">Select Status</option>
                            <option value="hot">Hot</option>
                            <option value="warm">Warm</option>
                            <option value="cold">Cold</option>
                            <option value="converted">Converted</option>
                            <option value="lost">Lost</option>
                        </select>
                        <div class="invalid-feedback">Please select a new status.</div>
                    </div>
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="statusNotes" rows="3" placeholder="Reason for status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Update Status
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
function filterLeads(type, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(type, value);
    } else {
        url.searchParams.delete(type);
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function searchLeads() {
    const searchTerm = document.getElementById('leadSearch').value.trim();
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
function toggleLeadDetails(leadId) {
    const detailsElement = document.getElementById(`details-${leadId}`);
    const chevronElement = document.getElementById(`chevron-${leadId}`);
    
    // Close all other open details
    document.querySelectorAll('.collapse.show').forEach(element => {
        if (element.id !== `details-${leadId}`) {
            element.classList.remove('show');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        if (chevron.id !== `chevron-${leadId}`) {
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

// Modal Functions
function showStatusModal(leadId, leadName, currentStatus) {
    document.getElementById('statusLeadId').value = leadId;
    document.getElementById('statusLeadName').textContent = leadName;
    document.getElementById('currentStatus').innerHTML = `<span class="badge bg-secondary">${currentStatus.toUpperCase()}</span>`;
    
    // Reset form
    const form = document.getElementById('changeStatusForm');
    form.classList.remove('was-validated');
    form.reset();
    
    // Hide current status from options
    const select = document.getElementById('newStatus');
    Array.from(select.options).forEach(option => {
        option.style.display = option.value === currentStatus ? 'none' : 'block';
    });
    
    new bootstrap.Modal(document.getElementById('changeStatusModal')).show();
}

// AJAX Form Handlers
document.getElementById('addLeadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
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
            showAlert(data.message || 'Failed to add lead', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while adding the lead', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

document.getElementById('changeStatusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const leadId = document.getElementById('statusLeadId').value;
    const newStatus = document.getElementById('newStatus').value;
    const notes = document.getElementById('statusNotes').value;
    
    fetch(`{{ url('admin/crm/leads') }}/${leadId}/status`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            status: newStatus,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('changeStatusModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating the status', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Event Listeners
document.getElementById('leadSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchLeads();
    }
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.lead-mobile-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});

// Reset modals when closed
document.getElementById('addLeadModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('addLeadForm');
    form.reset();
    form.classList.remove('was-validated');
});

document.getElementById('changeStatusModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('changeStatusForm');
    form.reset();
    form.classList.remove('was-validated');
});

// Placeholder Functions (to be implemented)
function viewLead(id) {
    showAlert('View lead functionality coming soon', 'info');
}

function editLead(id) {
    showAlert('Edit lead functionality coming soon', 'info');
}

function showFollowupModal(leadId, leadName) {
    showAlert('Add followup functionality coming soon', 'info');
}

function showAssignModal(leadId, leadName) {
    showAlert('Assign lead functionality coming soon', 'info');
}

function deleteLead(id, name) {
    if (confirm(`Are you sure you want to delete lead "${name}"? This action cannot be undone.`)) {
        showAlert('Delete lead functionality coming soon', 'info');
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

.lead-row {
    transition: background-color 0.15s ease-in-out;
}

.lead-row:hover {
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
.lead-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.lead-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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
    .lead-mobile-card .card-body {
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