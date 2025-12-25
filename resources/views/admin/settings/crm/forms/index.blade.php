@extends('admin.layouts.vertical', ['title' => 'Forms Management', 'subTitle' => 'CRM'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Forms Management</h4>
                            <p class="text-muted mb-0">Create and manage lead capture forms</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addFormModal">
                                Create Form
                            </button>
                            <select class="form-select form-select-sm" onchange="filterForms(this.value)" style="width: auto;">
                                <option value="" {{ !request('status') ? 'selected' : '' }}>All Forms</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="input-group" style="width: 250px;">
                                <input type="text" class="form-control form-control-sm" id="formSearch" placeholder="Search forms..." value="{{ request('search') }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="searchForms()">
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
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:forms-add-on" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Forms</h6>
                    <h5 class="mb-0 fw-bold">{{ $formStats['total'] }}</h5>
                    <small class="text-muted">All forms</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols-light:interactive-space-outline-rounded" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active Forms</h6>
                    <h5 class="mb-0 fw-bold">{{ $formStats['active'] }}</h5>
                    <small class="text-muted">Published</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:send-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Submissions</h6>
                    <h5 class="mb-0 fw-bold">{{ $formStats['total_submissions'] }}</h5>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:perm-contact-calendar-outline-sharp" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Today's Submissions</h6>
                    <h5 class="mb-0 fw-bold">{{ $formStats['submissions_today'] }}</h5>
                    <small class="text-muted">New entries</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Forms Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="card-title mb-0">Forms ({{ $forms->total() }})</h5>
                    @if(request('status') || request('search'))
                        <a href="{{ route('admin.crm.forms.index') }}" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon> Clear Filters
                        </a>
                    @endif
                </div>

                @if($forms->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Form</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Submissions</th>
                                            <th scope="col" class="border-0">Public URL</th>
                                            <th scope="col" class="border-0">Created By</th>
                                            <th scope="col" class="border-0">Created</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($forms as $form)
                                            <tr class="form-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $form->is_active ? 'success' : 'secondary' }} me-3">
                                                            <iconify-icon icon="material-symbols:forms-add-on" class="text-white"></iconify-icon>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $form->title }}</h6>
                                                            <small class="text-muted">{{ Str::limit($form->description, 50) }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-{{ $form->is_active ? 'success' : 'secondary' }}">
                                                        <iconify-icon icon="iconamoon:{{ $form->is_active ? 'check-circle' : 'pause-circle' }}-duotone" class="me-1"></iconify-icon>
                                                        {{ $form->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-semibold">{{ $form->submissions_count }}</div>
                                                    @if($form->submissions->count() > 0)
                                                        <small class="text-muted">Latest: {{ $form->submissions->first()->created_at->diffForHumans() }}</small>
                                                    @else
                                                        <small class="text-muted">No submissions</small>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <code class="small">{{ Str::limit($form->public_url, 30) }}</code>
                                                        <button class="btn btn-sm btn-outline-info" onclick="copyToClipboard('{{ $form->public_url }}')" title="Copy URL">
                                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-semibold">{{ $form->createdBy->full_name ?? 'System' }}</div>
                                                    <small class="text-muted">{{ $form->createdBy->email ?? '' }}</small>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="fw-semibold">{{ $form->created_at->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ $form->created_at->diffForHumans() }}</small>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ $form->public_url }}" target="_blank">
                                                                    <iconify-icon icon="iconamoon:link-external-duotone" class="me-2"></iconify-icon>Preview Form
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewFormSubmissions('{{ $form->id }}', '{{ $form->title }}')">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Submissions
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="editForm('{{ $form->id }}')">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Form
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="toggleFormStatus('{{ $form->id }}', {{ $form->is_active ? 'false' : 'true' }})">
                                                                    <iconify-icon icon="iconamoon:{{ $form->is_active ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>{{ $form->is_active ? 'Deactivate' : 'Activate' }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="duplicateForm('{{ $form->id }}', '{{ $form->title }}')">
                                                                    <iconify-icon icon="iconamoon:copy-duotone" class="me-2"></iconify-icon>Duplicate
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteForm('{{ $form->id }}', '{{ $form->title }}')">
                                                                    <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete Form
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
                                @foreach($forms as $form)
                                    <div class="col-12">
                                        <div class="card form-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="avatar avatar-sm rounded-circle bg-{{ $form->is_active ? 'success' : 'secondary' }}">
                                                            <iconify-icon icon="material-symbols:forms-add-on" class="text-white"></iconify-icon>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $form->title }}</h6>
                                                            <small class="text-muted">{{ $form->submissions_count }} submissions</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ $form->public_url }}" target="_blank">
                                                                    <iconify-icon icon="iconamoon:link-external-duotone" class="me-2"></iconify-icon>Preview Form
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewFormSubmissions('{{ $form->id }}', '{{ $form->title }}')">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Submissions
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="editForm('{{ $form->id }}')">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Form
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Status and Description Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge bg-{{ $form->is_active ? 'success' : 'secondary' }}">
                                                            <iconify-icon icon="iconamoon:{{ $form->is_active ? 'check-circle' : 'pause-circle' }}-duotone" class="me-1"></iconify-icon>
                                                            {{ $form->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleFormDetails('{{ $form->id }}')">
                                                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $form->id }}"></iconify-icon>
                                                    </button>
                                                </div>

                                                {{-- Description and Creation Info --}}
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <small class="text-muted d-block">{{ Str::limit($form->description, 50) }}</small>
                                                        <small class="text-muted">Created: {{ $form->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <button class="btn btn-sm btn-outline-info" onclick="copyToClipboard('{{ $form->public_url }}')">
                                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                                        </button>
                                                    </div>
                                                </div>

                                                {{-- Expandable Details --}}
                                                <div class="collapse mt-3" id="details-{{ $form->id }}">
                                                    <div class="border-top pt-3">
                                                        <div class="row g-2 small">
                                                            <div class="col-12">
                                                                <div class="text-muted">Public URL</div>
                                                                <div class="small">
                                                                    <code>{{ $form->public_url }}</code>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Created By</div>
                                                                <div class="fw-semibold">{{ $form->createdBy->full_name ?? 'System' }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Button Text</div>
                                                                <div class="fw-semibold">{{ $form->submit_button_text }}</div>
                                                            </div>
                                                            @if($form->submissions->count() > 0)
                                                                <div class="col-6">
                                                                    <div class="text-muted">Latest Submission</div>
                                                                    <div class="fw-semibold">{{ $form->submissions->first()->created_at->diffForHumans() }}</div>
                                                                </div>
                                                            @endif
                                                            <div class="col-12">
                                                                <div class="text-muted">Standard Fields</div>
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    @foreach($form->standard_fields as $field)
                                                                        <span class="badge bg-light text-dark">{{ ucfirst($field) }}</span>
                                                                    @endforeach
                                                                </div>
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
                    @if($forms->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $forms->firstItem() }}</span> to <span class="fw-semibold">{{ $forms->lastItem() }}</span> of <span class="fw-semibold">{{ $forms->total() }}</span> forms
                                </div>
                                <div>
                                    {{ $forms->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="material-symbols:forms-add-on" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Forms Found</h6>
                            <p class="text-muted">No forms match your current filter criteria.</p>
                            @if(request('status') || request('search'))
                                <a href="{{ route('admin.crm.forms.index') }}" class="btn btn-primary">Clear Filters</a>
                            @else
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFormModal">
                                    Create Your First Form
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Add Form Modal --}}
<div class="modal fade" id="addFormModal" tabindex="-1" aria-labelledby="addFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addFormModalLabel">Create New Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addFormForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Form Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required placeholder="e.g., Contact Us Form">
                            <div class="invalid-feedback">Please provide a form title.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Brief description of the form purpose"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Submit Button Text <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="submit_button_text" value="Submit" required>
                            <div class="invalid-feedback">Please provide button text.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Success Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="success_message" rows="2" required placeholder="Thank you! We'll get back to you soon."></textarea>
                            <div class="invalid-feedback">Please provide a success message.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Standard Fields <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="first_name" id="field_first_name" checked>
                                        <label class="form-check-label" for="field_first_name">First Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="last_name" id="field_last_name" checked>
                                        <label class="form-check-label" for="field_last_name">Last Name</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="email" id="field_email" checked>
                                        <label class="form-check-label" for="field_email">Email</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="mobile" id="field_mobile" checked>
                                        <label class="form-check-label" for="field_mobile">Mobile</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="whatsapp" id="field_whatsapp">
                                        <label class="form-check-label" for="field_whatsapp">WhatsApp</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="country" id="field_country">
                                        <label class="form-check-label" for="field_country">Country</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="interest" id="field_interest">
                                        <label class="form-check-label" for="field_interest">Interest Level</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="standard_fields[]" value="notes" id="field_notes">
                                        <label class="form-check-label" for="field_notes">Notes/Message</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" value="1" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Publish form immediately
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-2 d-none" role="status"></span>
                        Create Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Form Submissions Modal --}}
<div class="modal fade" id="submissionsModal" tabindex="-1" aria-labelledby="submissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="submissionsModalLabel">Form Submissions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="submissionsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
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
function filterForms(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function searchForms() {
    const searchTerm = document.getElementById('formSearch').value.trim();
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
function toggleFormDetails(formId) {
    const detailsElement = document.getElementById(`details-${formId}`);
    const chevronElement = document.getElementById(`chevron-${formId}`);
    
    // Close all other open details
    document.querySelectorAll('.collapse.show').forEach(element => {
        if (element.id !== `details-${formId}`) {
            element.classList.remove('show');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        if (chevron.id !== `chevron-${formId}`) {
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

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('URL copied to clipboard!', 'success');
    }, function() {
        showAlert('Failed to copy URL', 'danger');
    });
}

// Toggle form status
function toggleFormStatus(formId, newStatus) {
    const action = newStatus === 'true' ? 'activate' : 'deactivate';
    if (confirm(`Are you sure you want to ${action} this form?`)) {
        fetch(`{{ url('admin/crm/forms') }}/${formId}/toggle-status`, {
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
            showAlert('Failed to update form status', 'danger');
        });
    }
}

// View form submissions
function viewFormSubmissions(formId, formTitle) {
    const modal = new bootstrap.Modal(document.getElementById('submissionsModal'));
    const content = document.getElementById('submissionsContent');
    
    // Update modal title
    document.querySelector('#submissionsModal .modal-title').textContent = `${formTitle} - Submissions`;
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch submissions
    fetch(`{{ url('admin/crm/forms') }}/${formId}/submissions`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load submissions</div>';
        });
}

// AJAX Form Handlers
document.getElementById('addFormForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const formData = new FormData(this);
    
    fetch('{{ route("admin.crm.forms.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('addFormModal')).hide();
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to create form', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while creating the form', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Event Listeners
document.getElementById('formSearch').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchForms();
    }
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.form-mobile-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});

// Reset modal when closed
document.getElementById('addFormModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('addFormForm');
    form.reset();
    form.classList.remove('was-validated');
    
    // Reset checkboxes to default state
    document.getElementById('field_first_name').checked = true;
    document.getElementById('field_last_name').checked = true;
    document.getElementById('field_email').checked = true;
    document.getElementById('field_mobile').checked = true;
    document.getElementById('is_active').checked = true;
});

// Placeholder Functions (to be implemented)
function editForm(id) {
    showAlert('Edit form functionality coming soon', 'info');
}

function duplicateForm(id, title) {
    if (confirm(`Duplicate form "${title}"?`)) {
        showAlert('Duplicate form functionality coming soon', 'info');
    }
}

function deleteForm(id, title) {
    if (confirm(`Are you sure you want to delete form "${title}"? This action cannot be undone.`)) {
        showAlert('Delete form functionality coming soon', 'info');
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

.form-row {
    transition: background-color 0.15s ease-in-out;
}

.form-row:hover {
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
.form-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.form-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.collapse {
    transition: height 0.35s ease;
}

/* Code Styles */
code {
    background-color: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    color: #6f42c1;
    border: 1px solid #e9ecef;
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
    .form-mobile-card .card-body {
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