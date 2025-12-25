@extends('layouts.vertical', ['title' => 'CRM', 'subTitle' => 'Features'])

@section('content')
<div class="row">
    <div class="col-12">
        
        <!-- Navigation -->
        <div class="card mb-3">
            <div class="card-body">
                <!-- Mobile Navigation -->
                <div class="d-md-none">
                    <select class="form-select" id="mobileNav">
                        <option value="dashboard">Dashboard</option>
                        <option value="leads">All Leads</option>
                        <option value="add-lead">Add Lead</option>
                        <option value="assignments">Assignments</option>
                        <option value="forms">Forms</option>
                    </select>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="d-none d-md-block">
                    <ul class="nav nav-pills nav-justified">
                        <li class="nav-item">
                            <button class="nav-link active" data-tab="dashboard">
                                <iconify-icon icon="material-symbols:dashboard" class="me-1"></iconify-icon>
                                Dashboard
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-tab="leads">
                                <iconify-icon icon="material-symbols:group" class="me-1"></iconify-icon>
                                All Leads
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-tab="add-lead">
                                <iconify-icon icon="material-symbols:person-add" class="me-1"></iconify-icon>
                                Add Lead
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-tab="assignments">
                                <iconify-icon icon="material-symbols:assignment" class="me-1"></iconify-icon>
                                Assignments
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" data-tab="forms">
                                <iconify-icon icon="material-symbols:description" class="me-1"></iconify-icon>
                                Forms
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Dashboard Tab -->
        <div class="tab-content" id="dashboard-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Dashboard Overview</h5>
                <button class="btn btn-primary" onclick="switchTab('add-lead')">
                    <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                    Add Lead
                </button>
            </div>
            <!-- Stats Cards -->
            <div class="row mb-4" id="statsCards">
                <div class="col-6 col-lg-3">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <iconify-icon icon="material-symbols:trending-up" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                            <h4 class="text-primary mb-1" id="activeLeadsCount">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </h4>
                            <small class="text-muted">Active Leads</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <iconify-icon icon="material-symbols:check-circle" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                            <h4 class="text-success mb-1" id="convertedLeadsCount">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </h4>
                            <small class="text-muted">Converted</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-warning">
                        <div class="card-body text-center">
                            <iconify-icon icon="material-symbols:schedule" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                            <h4 class="text-warning mb-1" id="todayFollowupsCount">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </h4>
                            <small class="text-muted">Follow-ups Due</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-danger">
                        <div class="card-body text-center">
                            <iconify-icon icon="material-symbols:error" class="text-danger mb-2" style="font-size: 2rem;"></iconify-icon>
                            <h4 class="text-danger mb-1" id="overdueFollowupsCount">
                                <div class="spinner-border spinner-border-sm" role="status"></div>
                            </h4>
                            <small class="text-muted">Overdue</small>
                        </div>
                    </div>
                </div>
            </div>

            

            <!-- Today's Follow-ups -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <iconify-icon icon="material-symbols:today" class="me-2"></iconify-icon>
                        Today's Follow-ups
                    </h6>
                    <span class="badge bg-primary" id="todayFollowupsBadge">0</span>
                </div>
                <div class="card-body p-0">
                    <div id="todayFollowups">
                        <div class="p-4 text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Leads -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <iconify-icon icon="material-symbols:person-add" class="me-2"></iconify-icon>
                        Recent Leads
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div id="recentLeads">
                        <div class="p-4 text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Leads Tab -->
        <div class="tab-content" id="leads-content" style="display: none;">
            <!-- Search and Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <iconify-icon icon="material-symbols:search"></iconify-icon>
                                </span>
                                <input type="text" class="form-control" id="searchLeads" placeholder="Search leads by name, email or phone...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterStatus">
                                <option value="">All Status</option>
                                <option value="hot">Hot</option>
                                <option value="warm">Warm</option>
                                <option value="cold">Cold</option>
                                <option value="converted">Converted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterSource">
                                <option value="">All Sources</option>
                                <option value="Facebook">Facebook</option>
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Website">Website</option>
                                <option value="YouTube">YouTube</option>
                                <option value="Referral">Referral</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" onclick="loadLeads()" id="refreshLeadsBtn">
                                <iconify-icon icon="material-symbols:refresh" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leads List -->
            <div id="leadsContainer">
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <!-- Leads Pagination -->
            <div id="leadsPagination" style="display: none;">
                <!-- Pagination will be loaded here -->
            </div>
        </div>

        <!-- Add Lead Tab -->
        <div class="tab-content" id="add-lead-content" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <iconify-icon icon="material-symbols:person-add" class="me-2"></iconify-icon>
                        Add New Lead
                    </h6>
                </div>
                <div class="card-body">
                    <form id="addLeadForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="firstName" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="lastName">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="mobile" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">WhatsApp</label>
                                <input type="text" class="form-control" name="whatsapp">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country</label>
                                <select class="form-select" name="country" id="countrySelect">
                                    <option value="">Select Country</option>
                                    <option value="Pakistan">Pakistan</option>
                                    <option value="India">India</option>
                                    <option value="Bangladesh">Bangladesh</option>
                                    <option value="UAE">UAE</option>
                                    <option value="Saudi Arabia">Saudi Arabia</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Source</label>
                                <select class="form-select" name="source" id="sourceSelect">
                                    <option value="">Select Source</option>
                                    <option value="Facebook">Facebook</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="Website">Website</option>
                                    <option value="YouTube">YouTube</option>
                                    <option value="Referral">Referral</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Interest Level</label>
                                <select class="form-select" name="interest">
                                    <option value="">Select Level</option>
                                    <option value="Low">Low</option>
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="notes" rows="4" placeholder="Enter lead details, conversation notes, and interests..." required></textarea>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-100" id="addLeadBtn">
                                <iconify-icon icon="material-symbols:add" class="me-2"></iconify-icon>
                                Add Lead
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div class="tab-content" id="assignments-content" style="display: none;">
            <!-- Assignment Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <!-- Desktop Filters -->
                    <div class="d-none d-md-block">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="assignmentFilter" id="allAssignments" value="all" checked>
                            <label class="btn btn-outline-primary" for="allAssignments">All Assignments</label>
                            
                            <input type="radio" class="btn-check" name="assignmentFilter" id="assignedByMe" value="by-me">
                            <label class="btn btn-outline-primary" for="assignedByMe">Assigned by Me</label>
                            
                            <input type="radio" class="btn-check" name="assignmentFilter" id="assignedToMe" value="to-me">
                            <label class="btn btn-outline-primary" for="assignedToMe">Assigned to Me</label>
                        </div>
                    </div>

                    <!-- Mobile Filters -->
                    <div class="d-md-none">
                        <select class="form-select" id="mobileAssignmentFilter">
                            <option value="all">All Assignments</option>
                            <option value="by-me">Assigned by Me</option>
                            <option value="to-me">Assigned to Me</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Assignments List -->
            <div id="assignmentsContainer">
                <div class="text-center p-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms Tab -->
        <div class="tab-content" id="forms-content" style="display: none;">
            <!-- Form Builder Sub-navigation -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="formSubTab" id="createFormTab" value="create" checked>
                        <label class="btn btn-outline-primary" for="createFormTab">
                            <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                            Create Form
                        </label>
                        
                        <input type="radio" class="btn-check" name="formSubTab" id="manageFormsTab" value="manage">
                        <label class="btn btn-outline-primary" for="manageFormsTab">
                            <iconify-icon icon="material-symbols:list" class="me-1"></iconify-icon>
                            Manage Forms
                        </label>
                    </div>
                </div>
            </div>

            <!-- Create Form Content -->
            <div id="createFormContent">
                <!-- Form Settings -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Form Settings</h6>
                    </div>
                    <div class="card-body">
                        <form id="createFormForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Form Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" id="formTitle" placeholder="e.g., Business Opportunity Interest Form" required>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="formDescription" rows="2" placeholder="Brief description of what this form is for"></textarea>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Submit Button Text</label>
                                    <input type="text" class="form-control" name="submit_button_text" id="submitText" value="Submit Application">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Success Message</label>
                                    <input type="text" class="form-control" name="success_message" id="successMessage" value="Thank you! We'll contact you soon.">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Standard Fields -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0">Standard Fields</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeFirstName" name="standard_fields[first_name]" value="1" checked>
                                    <label class="form-check-label" for="includeFirstName">First Name</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeLastName" name="standard_fields[last_name]" value="1">
                                    <label class="form-check-label" for="includeLastName">Last Name</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeEmail" name="standard_fields[email]" value="1" checked>
                                    <label class="form-check-label" for="includeEmail">Email</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeMobile" name="standard_fields[mobile]" value="1" checked>
                                    <label class="form-check-label" for="includeMobile">Mobile</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeCountry" name="standard_fields[country]" value="1">
                                    <label class="form-check-label" for="includeCountry">Country</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="includeWhatsapp" name="standard_fields[whatsapp]" value="1">
                                    <label class="form-check-label" for="includeWhatsapp">WhatsApp</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Fields -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Custom Fields</h6>
                        <button type="button" class="btn btn-sm btn-primary" onclick="addCustomField()">
                            <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                            Add Field
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="customFieldsContainer">
                            <p class="text-muted text-center mb-0">No custom fields added yet</p>
                        </div>
                    </div>
                </div>

                <!-- Save Form -->
                <button type="button" class="btn btn-success w-100" onclick="saveForm()" id="saveFormBtn">
                    <iconify-icon icon="material-symbols:save" class="me-2"></iconify-icon>
                    Save Form
                </button>
            </div>

            <!-- Manage Forms Content -->
            <div id="manageFormsContent" style="display: none;">
                <div id="formsListContainer">
                    <div class="text-center p-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lead Details Modal -->
<div class="modal fade" id="leadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="leadModalTitle">Lead Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="leadModalContent">
                    <div class="text-center p-4">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="openFollowupModal()">
                    <iconify-icon icon="material-symbols:schedule" class="me-1"></iconify-icon>
                    Add Follow-up
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Follow-up Modal -->
<div class="modal fade" id="followupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <iconify-icon icon="material-symbols:schedule" class="me-2"></iconify-icon>
                    Add Follow-up
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="followupForm">
                    @csrf
                    <input type="hidden" id="followupLeadId" name="lead_id">
                    <div class="mb-3">
                        <label class="form-label">Follow-up Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="followupDate" name="followup_date" required>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Follow-up Type</label>
                        <select class="form-select" id="followupType" name="type">
                            <option value="call">Phone Call</option>
                            <option value="email">Email</option>
                            <option value="meeting">Meeting</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="followupNotes" name="notes" rows="3" placeholder="Enter follow-up details and action items..." required></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveFollowup()" id="saveFollowupBtn">
                    <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                    Add Follow-up
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <iconify-icon icon="material-symbols:assignment" class="me-2"></iconify-icon>
                    Assign Lead
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignmentForm">
                    @csrf
                    <input type="hidden" id="assignmentLeadId" name="lead_id">
                    <div class="mb-3">
                        <label class="form-label">Assign to User <span class="text-danger">*</span></label>
                        <select class="form-select" id="assignedTo" name="assigned_to" required>
                            <option value="">Select User</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="assignmentNotes" name="notes" rows="3" placeholder="Assignment notes..."></textarea>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAssignment()" id="saveAssignmentBtn">
                    <iconify-icon icon="material-symbols:assignment" class="me-1"></iconify-icon>
                    Assign Lead
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="toastContainer"></div>
</div>

<!-- CSRF Token for AJAX -->
<meta name="csrf-token" content="{{ csrf_token() }}">

@endsection

@section('script')
<script>

    console.log("loaded")
// Global Variables
let currentTab = 'dashboard';
let currentLeadId = null;
let customFieldCounter = 0;
let currentPage = 1;
let leadsPerPage = 10;
let isLoading = false;

// API URLs
const API_URLS = {
    dashboard: '{{ route("crm.dashboard.data") }}',
    leads: '{{ route("crm.leads.index") }}',
    storeLead: '{{ route("crm.leads.store") }}',
    showLead: '{{ url("crm/leads") }}',
    storeFollowup: '{{ route("crm.followups.store") }}',
    assignments: '{{ route("crm.assignments.index") }}',
    storeAssignment: '{{ route("crm.assignments.store") }}',
    forms: '{{ route("crm.forms.index") }}',
    storeForm: '{{ route("crm.forms.store") }}',
    toggleFormStatus: '{{ url("crm/forms") }}',
    deleteForm: '{{ url("crm/forms") }}',
    assignableUsers: '{{ route("crm.utils.users.assignable") }}',
};

// CSRF token setup for AJAX
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Initialize App
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadDashboard();
    loadAssignableUsers();
});

function setupEventListeners() {
    // Navigation
    document.getElementById('mobileNav').addEventListener('change', function() {
        switchTab(this.value);
    });

    document.querySelectorAll('[data-tab]').forEach(button => {
        button.addEventListener('click', function() {
            switchTab(this.dataset.tab);
        });
    });

    // Forms
    document.getElementById('addLeadForm').addEventListener('submit', handleAddLead);
    document.getElementById('followupForm').addEventListener('submit', function(e) { e.preventDefault(); });
    document.getElementById('assignmentForm').addEventListener('submit', function(e) { e.preventDefault(); });

    // Search and Filter
    document.getElementById('searchLeads').addEventListener('input', debounce(loadLeads, 300));
    document.getElementById('filterStatus').addEventListener('change', loadLeads);
    document.getElementById('filterSource').addEventListener('change', loadLeads);

    // Assignment Filters
    document.querySelectorAll('input[name="assignmentFilter"]').forEach(radio => {
        radio.addEventListener('change', loadAssignments);
    });

    // Mobile Assignment Filter
    document.getElementById('mobileAssignmentFilter').addEventListener('change', function() {
        document.querySelector(`input[name="assignmentFilter"][value="${this.value}"]`).checked = true;
        loadAssignments();
    });

    // Form Sub-tabs
    document.querySelectorAll('input[name="formSubTab"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleFormSubTabs(this.value);
        });
    });
}

// Navigation Functions
function switchTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });

    // Remove active class from all nav buttons
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-content').style.display = 'block';

    // Add active class to selected nav button
    const navButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (navButton) {
        navButton.classList.add('active');
    }

    // Update mobile select
    document.getElementById('mobileNav').value = tabName;

    currentTab = tabName;

    // Load content based on tab
    switch(tabName) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'leads':
            currentPage = 1;
            loadLeads();
            break;
        case 'assignments':
            loadAssignments();
            break;
        case 'forms':
            if (document.getElementById('manageFormsTab').checked) {
                loadForms();
            }
            break;
    }
}

// Dashboard Functions
function loadDashboard() {
    fetch(API_URLS.dashboard)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateDashboardStats(data.stats);
                loadTodayFollowups(data.todayFollowups);
                loadRecentLeads(data.recentLeads);
            }
        })
        .catch(error => {
            console.error('Error loading dashboard:', error);
            showToast('Error loading dashboard data', 'error');
        });
}

function updateDashboardStats(stats) {
    document.getElementById('activeLeadsCount').textContent = stats.activeLeads || 0;
    document.getElementById('convertedLeadsCount').textContent = stats.convertedLeads || 0;
    document.getElementById('todayFollowupsCount').textContent = stats.todayFollowups || 0;
    document.getElementById('overdueFollowupsCount').textContent = stats.overdueFollowups || 0;
    document.getElementById('todayFollowupsBadge').textContent = stats.todayFollowups || 0;
}

function loadTodayFollowups(followups = null) {
    const container = document.getElementById('todayFollowups');
    
    if (followups === null) {
        // Show loading
        container.innerHTML = `
            <div class="p-4 text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        return;
    }

    if (followups.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-muted">
                <iconify-icon icon="material-symbols:check-circle" style="font-size: 2rem;" class="mb-2"></iconify-icon>
                <p class="mb-0">No follow-ups due today</p>
            </div>
        `;
        return;
    }

    let html = '';
    followups.forEach(followup => {
        html += `
            <div class="border-bottom p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1" onclick="viewLead(${followup.lead.id})" style="cursor: pointer;">
                        <h6 class="mb-1">${followup.lead.first_name} ${followup.lead.last_name}</h6>
                        <div class="small text-muted mb-1">
                            <iconify-icon icon="material-symbols:call" class="me-1"></iconify-icon>
                            ${followup.lead.mobile}
                        </div>
                        <div class="mb-2">
                            <span class="badge bg-${getStatusColor(followup.lead.status)}">${followup.lead.status.toUpperCase()}</span>
                            <span class="small text-muted ms-2">${followup.typeIcon} ${followup.type}</span>
                        </div>
                        <div class="small text-muted">${followup.notes}</div>
                    </div>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-primary" onclick="makeCall(${followup.lead.id})" title="Call">
                            <iconify-icon icon="material-symbols:call"></iconify-icon>
                        </button>
                        <button class="btn btn-sm btn-outline-success" onclick="openFollowupModalForLead(${followup.lead.id})" title="Add Follow-up">
                            <iconify-icon icon="material-symbols:add"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function loadRecentLeads(leads = null) {
    const container = document.getElementById('recentLeads');
    
    if (leads === null) {
        container.innerHTML = `
            <div class="p-4 text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        return;
    }

    if (leads.length === 0) {
        container.innerHTML = `
            <div class="p-4 text-center text-muted">
                <iconify-icon icon="material-symbols:group" style="font-size: 2rem;" class="mb-2"></iconify-icon>
                <p class="mb-0">No recent leads</p>
            </div>
        `;
        return;
    }

    let html = '';
    leads.forEach(lead => {
        html += `
            <div class="border-bottom p-3" onclick="viewLead(${lead.id})" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${lead.first_name} ${lead.last_name}</h6>
                        <div class="small text-muted mb-1">${lead.email || 'No email'}</div>
                        <div class="small text-muted mb-2">${lead.mobile}</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-${getStatusColor(lead.status)}">${lead.status.toUpperCase()}</span>
                            <small class="text-muted">${lead.source || 'Unknown'}</small>
                        </div>
                    </div>
                    <small class="text-muted">${lead.created_at}</small>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Leads Functions
function loadLeads() {
    if (isLoading) return;
    isLoading = true;

    const searchTerm = document.getElementById('searchLeads').value;
    const statusFilter = document.getElementById('filterStatus').value;
    const sourceFilter = document.getElementById('filterSource').value;
    
    const params = new URLSearchParams({
        page: currentPage,
        search: searchTerm,
        status: statusFilter,
        source: sourceFilter,
    });

    const container = document.getElementById('leadsContainer');
    const refreshBtn = document.getElementById('refreshLeadsBtn');
    
    // Show loading state
    if (currentPage === 1) {
        container.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    }
    
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div>Loading...';

    fetch(`${API_URLS.leads}?${params}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayLeads(data.leads);
            displayPagination(data.pagination);
        } else {
            showToast(data.message || 'Error loading leads', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading leads:', error);
        showToast('Error loading leads', 'error');
        container.innerHTML = `
            <div class="alert alert-danger text-center">
                <iconify-icon icon="material-symbols:error" class="me-2"></iconify-icon>
                Error loading leads. Please try again.
            </div>
        `;
    })
    .finally(() => {
        isLoading = false;
        refreshBtn.disabled = false;
        refreshBtn.innerHTML = '<iconify-icon icon="material-symbols:refresh" class="me-1"></iconify-icon>Refresh';
    });
}

function displayLeads(leads) {
    const container = document.getElementById('leadsContainer');

    if (!leads || leads.length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-5">
                    <iconify-icon icon="material-symbols:group" style="font-size: 3rem;" class="text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No leads found</h6>
                    <p class="text-muted">Try adjusting your search filters or add a new lead.</p>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    leads.forEach(lead => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1" onclick="viewLead(${lead.id})" style="cursor: pointer;">
                            <h6 class="mb-1">${lead.first_name} ${lead.last_name || ''}</h6>
                            <div class="small text-muted mb-1">${lead.email || 'No email'}</div>
                            <div class="small text-muted mb-2">${lead.mobile}</div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-${getStatusColor(lead.status)}">${lead.status.toUpperCase()}</span>
                                <small class="text-muted">${lead.source || 'Unknown'} • ${lead.interest || 'Not set'}</small>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                <iconify-icon icon="material-symbols:more-vert"></iconify-icon>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="viewLead(${lead.id})">
                                        <iconify-icon icon="material-symbols:visibility" class="me-2"></iconify-icon>
                                        View Details
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="openFollowupModalForLead(${lead.id})">
                                        <iconify-icon icon="material-symbols:schedule" class="me-2"></iconify-icon>
                                        Add Follow-up
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="openAssignmentModalForLead(${lead.id})">
                                        <iconify-icon icon="material-symbols:share" class="me-2"></iconify-icon>
                                        Assign
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteLead(${lead.id})">
                                        <iconify-icon icon="material-symbols:delete" class="me-2"></iconify-icon>
                                        Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="small text-muted">
                        Added on ${lead.formatted_created_at || lead.created_at}
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function displayPagination(pagination) {
    const paginationContainer = document.getElementById('leadsPagination');
    
    if (!pagination || pagination.last_page <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    const startItem = pagination.from || 1;
    const endItem = pagination.to || pagination.total;
    const totalItems = pagination.total || 0;

    let paginationHtml = `
        <div class="card">
            <div class="card-footer border-top border-light">
                <div class="row align-items-center justify-content-between text-center text-sm-start">
                    <div class="col-sm">
                        <div class="text-muted">
                            Showing
                            <span class="fw-semibold text-body">${startItem}</span>
                            to
                            <span class="fw-semibold text-body">${endItem}</span>
                            of
                            <span class="fw-semibold">${totalItems}</span>
                            Leads
                        </div>
                    </div>
                    <div class="col-sm-auto mt-3 mt-sm-0">
                        <ul class="pagination pagination-boxed pagination-sm mb-0 justify-content-center">
    `;

    // Previous button
    if (pagination.current_page === 1) {
        paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
            </li>
        `;
    } else {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})"><i class="bx bxs-chevron-left"></i></a>
            </li>
        `;
    }

    // Page numbers
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            paginationHtml += `
                <li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>
            `;
        } else {
            paginationHtml += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                </li>
            `;
        }
    }

    // Next button
    if (!pagination.has_more_pages) {
        paginationHtml += `
            <li class="page-item disabled">
                <span class="page-link"><i class="bx bxs-chevron-right"></i></span>
            </li>
        `;
    } else {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})"><i class="bx bxs-chevron-right"></i></a>
            </li>
        `;
    }

    paginationHtml += `
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    `;

    paginationContainer.innerHTML = paginationHtml;
    paginationContainer.style.display = 'block';
}

function changePage(page) {
    currentPage = page;
    loadLeads();
    document.getElementById('leadsContainer').scrollIntoView({ behavior: 'smooth' });
}

// Add Lead Function
function handleAddLead(e) {
    e.preventDefault();
    
    const btn = document.getElementById('addLeadBtn');
    const form = e.target;
    const formData = new FormData(form);

    // Clear previous errors
    clearFormErrors(form);
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Adding...';

    fetch(API_URLS.storeLead, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            form.reset();
            switchTab('leads'); // Switch to leads tab to see the new lead
        } else {
            if (data.errors) {
                displayFormErrors(form, data.errors);
            }
            showToast(data.message || 'Error adding lead', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding lead:', error);
        showToast('Error adding lead', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="material-symbols:add" class="me-2"></iconify-icon>Add Lead';
    });
}

// Lead Details and Modal Functions
function viewLead(leadId) {
    const modal = new bootstrap.Modal(document.getElementById('leadModal'));
    const content = document.getElementById('leadModalContent');
    const title = document.getElementById('leadModalTitle');
    
    currentLeadId = leadId;
    
    // Show loading state
    content.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();

    fetch(`${API_URLS.showLead}/${leadId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayLeadDetails(data.lead);
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <iconify-icon icon="material-symbols:error" class="me-2"></iconify-icon>
                        ${data.message || 'Error loading lead details'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading lead details:', error);
            content.innerHTML = `
                <div class="alert alert-danger">
                    <iconify-icon icon="material-symbols:error" class="me-2"></iconify-icon>
                    Error loading lead details
                </div>
            `;
        });
}

function displayLeadDetails(lead) {
    const content = document.getElementById('leadModalContent');
    const title = document.getElementById('leadModalTitle');
    
    title.textContent = `${lead.first_name} ${lead.last_name || ''}`;

    let followupsHtml = '';
    if (lead.followups && lead.followups.length > 0) {
        followupsHtml = `
            <div class="mt-4">
                <h6 class="mb-3">Follow-up History</h6>
                <div class="follow-ups-timeline">
        `;

        lead.followups.forEach(followup => {
            followupsHtml += `
                <div class="card mb-2 ${followup.completed ? 'border-success' : 'border-warning'}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span>${getFollowupTypeIcon(followup.type)}</span>
                                <span class="fw-medium">${followup.type.charAt(0).toUpperCase() + followup.type.slice(1)}</span>
                                <span class="small text-muted">${followup.formatted_date || followup.followup_date}</span>
                            </div>
                            <span class="badge ${followup.completed ? 'bg-success' : 'bg-warning'}">
                                ${followup.completed ? 'Completed' : 'Pending'}
                            </span>
                        </div>
                        <p class="small text-muted mb-0">${followup.notes}</p>
                    </div>
                </div>
            `;
        });

        followupsHtml += `
                </div>
            </div>
        `;
    } else {
        followupsHtml = `
            <div class="mt-4">
                <h6 class="mb-3">Follow-up History</h6>
                <p class="text-muted text-center py-3">No follow-ups yet</p>
            </div>
        `;
    }

    const htmlContent = `
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="small text-muted">Email</label>
                <div class="fw-medium">${lead.email || 'Not provided'}</div>
            </div>
            <div class="col-md-6">
                <label class="small text-muted">Mobile</label>
                <div class="fw-medium">${lead.mobile}</div>
            </div>
            <div class="col-md-6">
                <label class="small text-muted">WhatsApp</label>
                <div class="fw-medium">${lead.whatsapp || 'Not provided'}</div>
            </div>
            <div class="col-md-6">
                <label class="small text-muted">Country</label>
                <div class="fw-medium">${lead.country || 'Not provided'}</div>
            </div>
            <div class="col-md-6">
                <label class="small text-muted">Source</label>
                <div class="fw-medium">${lead.source || 'Not provided'}</div>
            </div>
            <div class="col-md-6">
                <label class="small text-muted">Status</label>
                <div><span class="badge bg-${getStatusColor(lead.status)}">${lead.status.toUpperCase()}</span></div>
            </div>
        </div>

        <div class="mb-4">
            <label class="small text-muted">Notes</label>
            <div class="bg-light p-3 rounded mt-1">${lead.notes}</div>
        </div>

        <div class="row g-3 text-center mb-4">
            <div class="col-4">
                <div class="small text-muted">Created</div>
                <div class="fw-medium small">${lead.created_at}</div>
            </div>
            <div class="col-4">
                <div class="small text-muted">Interest Level</div>
                <div class="fw-medium small">${lead.interest || 'Not set'}</div>
            </div>
            <div class="col-4">
                <div class="small text-muted">Follow-ups</div>
                <div class="fw-medium small">${lead.followups ? lead.followups.length : 0}</div>
            </div>
        </div>

        ${followupsHtml}
    `;

    content.innerHTML = htmlContent;
}

// Follow-up Functions
function openFollowupModalForLead(leadId) {
    currentLeadId = leadId;
    openFollowupModal();
}

function openFollowupModal() {
    if (!currentLeadId) {
        showToast('Please select a lead first', 'error');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('followupModal'));
    const form = document.getElementById('followupForm');
    
    // Reset form and set values
    form.reset();
    clearFormErrors(form);
    
    document.getElementById('followupLeadId').value = currentLeadId;
    
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('followupDate').value = today;
    document.getElementById('followupDate').min = today;

    modal.show();
}

function saveFollowup() {
    const form = document.getElementById('followupForm');
    const btn = document.getElementById('saveFollowupBtn');
    const formData = new FormData(form);

    // Clear previous errors
    clearFormErrors(form);
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div>Adding...';

    fetch(API_URLS.storeFollowup, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('followupModal')).hide();
            
            // Refresh dashboard if we're on it
            if (currentTab === 'dashboard') {
                loadDashboard();
            }
            
            // Refresh lead modal if open
            if (currentLeadId && bootstrap.Modal.getInstance(document.getElementById('leadModal'))) {
                viewLead(currentLeadId);
            }
        } else {
            if (data.errors) {
                displayFormErrors(form, data.errors);
            }
            showToast(data.message || 'Error adding follow-up', 'error');
        }
    })
    .catch(error => {
        console.error('Error adding follow-up:', error);
        showToast('Error adding follow-up', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>Add Follow-up';
    });
}

// Assignment Functions
function loadAssignments() {
    const container = document.getElementById('assignmentsContainer');
    let filter = document.querySelector('input[name="assignmentFilter"]:checked').value;
    
    // For mobile, get from select
    if (window.innerWidth < 768) {
        filter = document.getElementById('mobileAssignmentFilter').value;
    }

    // Show loading state
    container.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    const params = new URLSearchParams({ filter: filter });

    fetch(`${API_URLS.assignments}?${params}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAssignments(data.assignments);
        } else {
            showToast(data.message || 'Error loading assignments', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading assignments:', error);
        container.innerHTML = `
            <div class="alert alert-danger text-center">
                <iconify-icon icon="material-symbols:error" class="me-2"></iconify-icon>
                Error loading assignments
            </div>
        `;
    });
}

function displayAssignments(assignments) {
    const container = document.getElementById('assignmentsContainer');

    if (!assignments || assignments.length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-5">
                    <iconify-icon icon="material-symbols:assignment" style="font-size: 3rem;" class="text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No assignments found</h6>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    assignments.forEach(assignment => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${assignment.lead_name}</h6>
                            <div class="small text-muted mb-1">
                                <iconify-icon icon="material-symbols:person" class="me-1"></iconify-icon>
                                ${assignment.assigned_by} → ${assignment.assigned_to}
                            </div>
                            <div class="small text-muted">
                                <iconify-icon icon="material-symbols:calendar-today" class="me-1"></iconify-icon>
                                Assigned on ${assignment.assigned_date}
                            </div>
                        </div>
                        <span class="badge bg-success">ACTIVE</span>
                    </div>
                    ${assignment.notes ? `<div class="small bg-light p-2 rounded">${assignment.notes}</div>` : ''}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function openAssignmentModalForLead(leadId) {
    currentLeadId = leadId;
    
    const modal = new bootstrap.Modal(document.getElementById('assignmentModal'));
    const form = document.getElementById('assignmentForm');
    
    // Reset form
    form.reset();
    clearFormErrors(form);
    
    document.getElementById('assignmentLeadId').value = currentLeadId;
    
    modal.show();
}

function saveAssignment() {
    const form = document.getElementById('assignmentForm');
    const btn = document.getElementById('saveAssignmentBtn');
    const formData = new FormData(form);

    // Clear previous errors
    clearFormErrors(form);
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div>Assigning...';

    fetch(API_URLS.storeAssignment, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
            
            // Refresh assignments if we're on that tab
            if (currentTab === 'assignments') {
                loadAssignments();
            }
        } else {
            if (data.errors) {
                displayFormErrors(form, data.errors);
            }
            showToast(data.message || 'Error assigning lead', 'error');
        }
    })
    .catch(error => {
        console.error('Error assigning lead:', error);
        showToast('Error assigning lead', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="material-symbols:assignment" class="me-1"></iconify-icon>Assign Lead';
    });
}

function loadAssignableUsers() {
    fetch(API_URLS.assignableUsers)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('assignedTo');
                select.innerHTML = '<option value="">Select User</option>';
                
                data.users.forEach(user => {
                    select.innerHTML += `<option value="${user.id}">${user.name} (${user.email})</option>`;
                });
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
        });
}

// Forms Functions
function toggleFormSubTabs(activeTab) {
    if (activeTab === 'create') {
        document.getElementById('createFormContent').style.display = 'block';
        document.getElementById('manageFormsContent').style.display = 'none';
    } else {
        document.getElementById('createFormContent').style.display = 'none';
        document.getElementById('manageFormsContent').style.display = 'block';
        loadForms();
    }
}

function saveForm() {
    const form = document.getElementById('createFormForm');
    const btn = document.getElementById('saveFormBtn');
    const formData = new FormData(form);

    // Add standard fields data
    const standardFields = {};
    document.querySelectorAll('input[name^="standard_fields"]').forEach(input => {
        if (input.checked) {
            const fieldName = input.name.match(/\[([^\]]+)\]/)[1];
            standardFields[fieldName] = true;
        }
    });
    formData.append('standard_fields', JSON.stringify(standardFields));

    // Clear previous errors
    clearFormErrors(form);
    
    // Show loading state
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div>Saving...';

    fetch(API_URLS.storeForm, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            
            // Reset form
            form.reset();
            document.getElementById('customFieldsContainer').innerHTML = '<p class="text-muted text-center mb-0">No custom fields added yet</p>';
            
            // Switch to manage tab
            document.getElementById('manageFormsTab').click();
        } else {
            if (data.errors) {
                displayFormErrors(form, data.errors);
            }
            showToast(data.message || 'Error creating form', 'error');
        }
    })
    .catch(error => {
        console.error('Error creating form:', error);
        showToast('Error creating form', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="material-symbols:save" class="me-2"></iconify-icon>Save Form';
    });
}

function loadForms() {
    const container = document.getElementById('formsListContainer');
    
    // Show loading state
    container.innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    fetch(API_URLS.forms, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayForms(data.forms);
        } else {
            showToast(data.message || 'Error loading forms', 'error');
        }
    })
    .catch(error => {
        console.error('Error loading forms:', error);
        container.innerHTML = `
            <div class="alert alert-danger text-center">
                <iconify-icon icon="material-symbols:error" class="me-2"></iconify-icon>
                Error loading forms
            </div>
        `;
    });
}

function displayForms(forms) {
    const container = document.getElementById('formsListContainer');

    if (!forms || forms.length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-5">
                    <iconify-icon icon="material-symbols:description" style="font-size: 3rem;" class="text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No forms created yet</h6>
                    <button class="btn btn-primary mt-2" onclick="document.getElementById('createFormTab').click()">
                        <iconify-icon icon="material-symbols:add" class="me-1"></iconify-icon>
                        Create Your First Form
                    </button>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    forms.forEach(form => {
        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${form.title}</h6>
                            <div class="small text-muted mb-2">${form.description || ''}</div>
                            <div class="d-flex gap-3 small text-muted">
                                <span>
                                    <iconify-icon icon="material-symbols:bar-chart" class="me-1"></iconify-icon>
                                    ${form.submissions} submissions
                                </span>
                                <span>
                                    <iconify-icon icon="material-symbols:calendar-today" class="me-1"></iconify-icon>
                                    Created ${form.created_at}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge ${form.is_active ? 'bg-success' : 'bg-secondary'} mb-2">
                                ${form.is_active ? 'ACTIVE' : 'INACTIVE'}
                            </span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <iconify-icon icon="material-symbols:more-vert"></iconify-icon>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="toggleFormStatus(${form.id})">
                                            <iconify-icon icon="material-symbols:power-settings-new" class="me-2"></iconify-icon>
                                            ${form.is_active ? 'Deactivate' : 'Activate'}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="copyFormLink('${form.slug}')">
                                            <iconify-icon icon="material-symbols:link" class="me-2"></iconify-icon>
                                            Copy Link
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="deleteForm(${form.id})">
                                            <iconify-icon icon="material-symbols:delete" class="me-2"></iconify-icon>
                                            Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" 
                               value="${form.public_url}" 
                               readonly onclick="this.select()">
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyFormLink('${form.slug}')">
                            <iconify-icon icon="material-symbols:content-copy"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function toggleFormStatus(formId) {
    fetch(`${API_URLS.toggleFormStatus}/${formId}/toggle-status`, {
        method: 'PUT',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadForms(); // Refresh the forms list
        } else {
            showToast(data.message || 'Error updating form status', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating form status:', error);
        showToast('Error updating form status', 'error');
    });
}

function deleteForm(formId) {
    if (!confirm('Are you sure you want to delete this form? This action cannot be undone.')) {
        return;
    }

    fetch(`${API_URLS.deleteForm}/${formId}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadForms(); // Refresh the forms list
        } else {
            showToast(data.message || 'Error deleting form', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting form:', error);
        showToast('Error deleting form', 'error');
    });
}

function copyFormLink(slug) {
    const link = `{{ url('/forms') }}/${slug}`;
    navigator.clipboard.writeText(link).then(() => {
        showToast('Form link copied to clipboard!', 'success');
    }).catch(error => {
        console.error('Error copying to clipboard:', error);
        showToast('Error copying link', 'error');
    });
}

// Custom Fields Functions
function addCustomField() {
    customFieldCounter++;
    const container = document.getElementById('customFieldsContainer');

    // Remove "no fields" message
    if (container.querySelector('.text-muted')) {
        container.innerHTML = '';
    }

    const fieldHtml = `
        <div class="card mb-3" id="customField_${customFieldCounter}">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small">Field Label</label>
                        <input type="text" class="form-control" name="custom_fields[${customFieldCounter}][label]" placeholder="e.g., Current Profession">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Field Type</label>
                        <select class="form-select" name="custom_fields[${customFieldCounter}][type]" onchange="toggleFieldOptions(this)">
                            <option value="text">Text Input</option>
                            <option value="email">Email</option>
                            <option value="tel">Phone</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Dropdown</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">&nbsp;</label>
                        <button type="button" class="btn btn-outline-danger w-100" onclick="removeCustomField(${customFieldCounter})">
                            <iconify-icon icon="material-symbols:delete"></iconify-icon>
                        </button>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="custom_fields[${customFieldCounter}][required]" value="1">
                            <label class="form-check-label small">Required field</label>
                        </div>
                    </div>
                    <div class="col-12 field-options" style="display: none;">
                        <label class="form-label small">Options (one per line)</label>
                        <textarea class="form-control" name="custom_fields[${customFieldCounter}][options]" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', fieldHtml);
}

function removeCustomField(fieldId) {
    document.getElementById(`customField_${fieldId}`).remove();

    const container = document.getElementById('customFieldsContainer');
    if (container.children.length === 0) {
        container.innerHTML = '<p class="text-muted text-center mb-0">No custom fields added yet</p>';
    }
}

function toggleFieldOptions(selectElement) {
    const fieldContainer = selectElement.closest('.card');
    const optionsContainer = fieldContainer.querySelector('.field-options');

    if (selectElement.value === 'select') {
        optionsContainer.style.display = 'block';
    } else {
        optionsContainer.style.display = 'none';
    }
}

// Utility Functions
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toastId = 'toast_' + Date.now();

    const toastHtml = `
        <div class="toast show align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} border-0 mb-2" id="${toastId}">
            <div class="d-flex">
                <div class="toast-body">
                    <iconify-icon icon="material-symbols:${type === 'success' ? 'check-circle' : type === 'error' ? 'error' : 'info'}" class="me-2"></iconify-icon>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest('.toast').remove()"></button>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHtml);

    setTimeout(() => {
        const toast = document.getElementById(toastId);
        if (toast) toast.remove();
    }, 4000);
}

function getStatusColor(status) {
    const colors = {
        hot: 'danger',
        warm: 'warning',
        cold: 'info',
        converted: 'success'
    };
    return colors[status] || 'secondary';
}

function getFollowupTypeIcon(type) {
    const icons = {
        call: '📞',
        email: '📧',
        meeting: '🤝',
        whatsapp: '💬',
        other: '📝'
    };
    return icons[type] || '📝';
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function clearFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(element => {
        element.classList.remove('is-invalid');
    });
    form.querySelectorAll('.invalid-feedback').forEach(element => {
        element.textContent = '';
    });
}

function displayFormErrors(form, errors) {
    for (const [field, messages] of Object.entries(errors)) {
        const input = form.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = messages[0];
            }
        }
    }
}

// Placeholder functions for future implementation
function makeCall(leadId) {
    showToast('Call functionality coming soon', 'info');
}

function deleteLead(leadId) {
    if (!confirm('Are you sure you want to delete this lead?')) {
        return;
    }
    
    fetch(`${API_URLS.showLead}/${leadId}`, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            loadLeads(); // Refresh the leads list
        } else {
            showToast(data.message || 'Error deleting lead', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting lead:', error);
        showToast('Error deleting lead', 'error');
    });
}
</script>

<style>
/* Clean, simple CRM styles */
.card {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.2s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.nav-pills .nav-link {
    border-radius: 0.375rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: white;
}

.nav-pills .nav-link:not(.active):hover {
    background-color: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}

.btn {
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.form-control,
.form-select {
    border-radius: 0.375rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.badge {
    font-size: 0.75em;
    font-weight: 500;
    padding: 0.4em 0.8em;
}

.toast {
    min-width: 300px;
}

.modal-content {
    border-radius: 0.75rem;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.dropdown-item {
    padding: 0.5rem 1rem;
    transition: background-color 0.2s ease;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Follow-up timeline */
.follow-ups-timeline .card {
    border-left: 3px solid #dee2e6;
}

.follow-ups-timeline .card.border-success {
    border-left-color: #198754;
}

.follow-ups-timeline .card.border-warning {
    border-left-color: #fd7e14;
}

/* Pagination styling */
.pagination-boxed .page-link {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    margin: 0 2px;
    padding: 0.375rem 0.75rem;
}

.pagination-boxed .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Form validation */
.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .nav-pills .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 0.75rem;
    }
    
    h6 {
        font-size: 0.95rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }

    /* Hide some pagination numbers on very small screens */
    .pagination .page-item:not(.active):not(.disabled):nth-child(n+5):nth-last-child(n+5) {
        display: none;
    }
}

/* Animation for tab switching */
.tab-content {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
@endsection