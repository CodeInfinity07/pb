@extends('admin.layouts.vertical', ['title' => 'Login Reports', 'subTitle' => 'Reports'])

@section('content')

<div class="row">
    <div class="col-12">
        {{-- Summary Cards --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                                    <iconify-icon icon="iconamoon:enter-duotone"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Total Logins</p>
                            <h4 class="mb-0" id="totalLogins">{{ number_format($summaryData['total_logins']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-success-subtle text-success rounded-circle fs-20">
                                    <iconify-icon icon="iconamoon:check-circle-duotone"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Success Rate</p>
                            <h4 class="mb-0" id="successRate">{{ $summaryData['success_rate'] }}%</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-info-subtle text-info rounded-circle fs-20">
                                    <iconify-icon icon="iconamoon:profile-duotone"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Unique Users</p>
                            <h4 class="mb-0" id="uniqueUsers">{{ number_format($summaryData['unique_users']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar-sm">
                                <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-20">
                                    <iconify-icon icon="iconamoon:calendar-duotone"></iconify-icon>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <p class="text-muted mb-1">Today's Logins</p>
                            <h4 class="mb-0" id="todayLogins">{{ number_format($summaryData['today_logins']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            {{-- Header with Filters --}}
            <div class="d-flex card-header justify-content-between align-items-center">
                <h4 class="card-title mb-0">Login Activity Logs</h4>
                <div class="flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-success" onclick="exportLogs()">
                        Export
                    </button>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card-body border-bottom py-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control form-control-sm" placeholder="Search..." id="searchInput">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" id="userFilter">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" onchange="filterLogs('status', this.value)" id="statusFilter">
                            <option value="">All Status</option>
                            @foreach($filterOptions['statuses'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" onchange="handleDateRangeChange(this.value)" id="predefinedDateRange">
                            @foreach($filterOptions['date_ranges'] as $key => $label)
                            <option value="{{ $key }}" {{ $key == '30' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="customDateRange" class="form-control form-control-sm" placeholder="Select custom dates" style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-danger w-100" id="clearCustomDateFilter" style="display: none;">
                            <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            {{-- Login Logs Table --}}
            <div class="card-body p-0">
                <div class="table-responsive table-card">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <thead class="bg-light bg-opacity-50 thead-sm">
                            <tr>
                                <th scope="col">User</th>
                                <th scope="col">IP Address</th>
                                <th scope="col">Device/Browser</th>
                                <th scope="col">Location</th>
                                <th scope="col">Login Time</th>
                                <th scope="col">Status</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="loginLogsTableBody">
                            @if($loginLogs->count() > 0)
                                @foreach($loginLogs as $log)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                                <span class="avatar-title text-white">{{ $log->user ? $log->user->initials : 'U' }}</span>
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $log->user ? $log->user->full_name : 'Unknown User' }}</h6>
                                                <small class="text-muted">{{ $log->user ? $log->user->email : 'Unknown' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code class="small">{{ $log->ip_address }}</code>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <iconify-icon icon="{{ $log->device_icon }}" class="me-2 fs-18"></iconify-icon>
                                            <div>
                                                <div class="fw-medium">{{ $log->browser ?? 'Unknown' }}</div>
                                                <small class="text-muted">{{ $log->platform ?? 'Unknown' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ trim(($log->city ? $log->city . ', ' : '') . ($log->country ?? '')) ?: 'Unknown' }}</td>
                                    <td>
                                        {{ $log->login_at->format('d M, y') }}
                                        <small class="text-muted d-block">{{ $log->login_at->format('h:i:s A') }}</small>
                                    </td>
                                    <td>
                                        @if($log->is_successful)
                                            <span class="badge bg-success-subtle text-success">Success</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Failed</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="showLoginDetails({{ $log->id }})" title="View Details">
                                            <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                                        <h6 class="text-muted">No Login Logs Found</h6>
                                        <p class="text-muted mb-0">No login activity to display.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Container --}}
            <div id="loginLogsPaginationContainer">
                @if($loginLogs->hasPages())
                {{-- Pagination HTML (same pattern as before) --}}
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Login Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
let currentFilters = {
    user_id: '',
    status: '',
    search: '',
    date_range: '30',
    start_date: '',
    end_date: '',
    per_page: 25,
    page: 1
};

let customDatePicker;
let searchTimeout;

document.addEventListener('DOMContentLoaded', function() {
    initializeDatePicker();
    initializeSearch();
    initializeUserSelect();
});

function initializeDatePicker() {
    const predefinedSelect = document.getElementById('predefinedDateRange');
    const customInput = document.getElementById('customDateRange');
    const clearBtn = document.getElementById('clearCustomDateFilter');
    
    customDatePicker = flatpickr("#customDateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        maxDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                currentFilters.start_date = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                currentFilters.end_date = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                currentFilters.date_range = 'custom';
                currentFilters.page = 1;
                clearBtn.style.display = 'inline-block';
                loadLoginLogs();
            }
        }
    });
    
    clearBtn.addEventListener('click', function() {
        customDatePicker.clear();
        currentFilters.start_date = '';
        currentFilters.end_date = '';
        currentFilters.date_range = '30';
        currentFilters.page = 1;
        this.style.display = 'none';
        customInput.style.display = 'none';
        predefinedSelect.value = '30';
        loadLoginLogs();
    });
}

function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentFilters.search = e.target.value;
            currentFilters.page = 1;
            loadLoginLogs();
        }, 500);
    });
}

function initializeUserSelect() {
    $('#userFilter').select2({
        placeholder: 'Search users...',
        allowClear: true,
        ajax: {
            url: '{{ route("admin.reports.login.search-users") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return { results: data.users };
            }
        }
    }).on('change', function() {
        currentFilters.user_id = $(this).val();
        currentFilters.page = 1;
        loadLoginLogs();
    });
}

function handleDateRangeChange(value) {
    const customInput = document.getElementById('customDateRange');
    const clearBtn = document.getElementById('clearCustomDateFilter');
    
    if (value === 'custom') {
        customInput.style.display = 'block';
        customDatePicker.open();
    } else {
        customInput.style.display = 'none';
        clearBtn.style.display = 'none';
        currentFilters.start_date = '';
        currentFilters.end_date = '';
        currentFilters.date_range = value;
        currentFilters.page = 1;
        loadLoginLogs();
    }
}

function filterLogs(filterType, value) {
    currentFilters[filterType] = value;
    currentFilters.page = 1;
    loadLoginLogs();
}

function loadLoginLogsPage(page) {
    currentFilters.page = page;
    loadLoginLogs();
}

function loadLoginLogs() {
    const tableBody = document.getElementById('loginLogsTableBody');
    const paginationContainer = document.getElementById('loginLogsPaginationContainer');
    
    // Build query params
    const params = new URLSearchParams();
    if (currentFilters.user_id) params.append('user_id', currentFilters.user_id);
    if (currentFilters.status) params.append('status', currentFilters.status);
    if (currentFilters.search) params.append('search', currentFilters.search);
    if (currentFilters.date_range && currentFilters.date_range !== 'custom') params.append('date_range', currentFilters.date_range);
    if (currentFilters.start_date) params.append('start_date', currentFilters.start_date);
    if (currentFilters.end_date) params.append('end_date', currentFilters.end_date);
    if (currentFilters.per_page) params.append('per_page', currentFilters.per_page);
    if (currentFilters.page) params.append('page', currentFilters.page);
    
    fetch(`{{ route('admin.reports.login.filter-ajax') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tableBody.innerHTML = data.html;
                paginationContainer.innerHTML = data.pagination;
                
                // Update summary cards
                if (data.summary) {
                    document.getElementById('totalLogins').textContent = data.summary.total_logins.toLocaleString();
                    document.getElementById('successRate').textContent = data.summary.success_rate + '%';
                    document.getElementById('uniqueUsers').textContent = data.summary.unique_users.toLocaleString();
                    document.getElementById('todayLogins').textContent = data.summary.today_logins.toLocaleString();
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load login logs', 'danger');
        });
}

function showLoginDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const content = document.getElementById('modalContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`{{ url('admin/reports/login') }}/${logId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `<div class="alert alert-danger">Failed to load details</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load login details</div>';
        });
}

function exportLogs() {
    const params = new URLSearchParams();
    if (currentFilters.user_id) params.append('user_id', currentFilters.user_id);
    if (currentFilters.status) params.append('status', currentFilters.status);
    if (currentFilters.date_range) params.append('date_range', currentFilters.date_range);
    
    window.location.href = `{{ route('admin.reports.login.export') }}?${params.toString()}`;
    showAlert('Export started! File will download shortly.', 'success');
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
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
.table-responsive {
    min-height: 400px;
    position: relative;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

code {
    background-color: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
}

.select2-container {
    width: 100% !important;
}
</style>
@endsection