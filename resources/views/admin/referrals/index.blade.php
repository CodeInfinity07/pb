@extends('admin.layouts.vertical', ['title' => 'Referrals Management', 'subTitle' => 'Admin'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Referrals Management</h4>
                            <p class="text-muted mb-0">View and manage user referral relationships</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportReferrals()">
                                <iconify-icon icon="iconamoon:download-duotone" class="me-1"></iconify-icon>
                                Export Data
                            </button>
                            <button type="button" class="btn btn-info btn-sm" onclick="refreshStats()">
                                <iconify-icon icon="material-symbols:refresh" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                            <a href="{{ route('admin.referrals.tree') }}" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:hierarchy-duotone" class="me-1"></iconify-icon>
                                Tree View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="streamline:interface-security-shield-profileshield-secure-security-profile-person" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Referrals</h6>
                    <h5 class="mb-0 fw-bold" id="total-referrals">{{ number_format($stats['total_referrals']) }}</h5>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:calendar-add-duotone" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Today</h6>
                    <h5 class="mb-0 fw-bold" id="today-referrals">{{ number_format($stats['today_referrals']) }}</h5>
                    <small class="text-muted">New referrals</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="streamline-sharp:decent-work-and-economic-growth-solid" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Growth Rate</h6>
                    <h5 class="mb-0 fw-bold {{ $stats['growth_rate'] >= 0 ? 'text-success' : 'text-danger' }}" id="growth-rate">
                        {{ $stats['growth_rate'] >= 0 ? '+' : '' }}{{ number_format($stats['growth_rate'], 1) }}%
                    </h5>
                    <small class="text-muted">Last {{ $dateRange }} days</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="streamline:interface-calendar-date-month-thirty-thirty-calendar-date-week-day-month" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">This Month</h6>
                    <h5 class="mb-0 fw-bold" id="month-referrals">{{ number_format($stats['this_month_referrals']) }}</h5>
                    <small class="text-muted">Monthly total</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Referrals Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 bg-light">
                    <h5 class="card-title mb-0">All Referrals</h5>
                    
                    {{-- Filters --}}
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <select class="form-select form-select-sm" onchange="filterReferrals('date_range', this.value)" style="width: auto;">
                            @foreach($filterOptions['date_ranges'] as $key => $label)
                                <option value="{{ $key }}" {{ $dateRange === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        
                        <select class="form-select form-select-sm" onchange="filterReferrals('sponsor_id', this.value)" style="width: auto;">
                            <option value="" {{ !$sponsorId ? 'selected' : '' }}>All Sponsors</option>
                            {{-- This would be populated dynamically via AJAX or passed from controller --}}
                        </select>
                        
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control form-control-sm" placeholder="Search users..." value="{{ $search }}" id="searchInput">
                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="searchReferrals()">
                                <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>

                @if($referrals->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Sponsor</th>
                                            <th scope="col" class="border-0">Referred User</th>
                                            <th scope="col" class="border-0">Registration Date</th>
                                            <th scope="col" class="border-0">Last Login</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($referrals as $referral)
                                            <tr class="referral-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                                                            <span class="avatar-title text-white fw-semibold">{{ $referral->sponsor ? $referral->sponsor->initials : 'N' }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $referral->sponsor ? $referral->sponsor->full_name : 'Direct' }}</h6>
                                                            <small class="text-muted">{{ $referral->sponsor ? $referral->sponsor->email : 'No sponsor' }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar avatar-sm rounded-circle bg-info me-3">
                                                            <span class="avatar-title text-white fw-semibold">{{ $referral->initials }}</span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $referral->full_name }}</h6>
                                                            <small class="text-muted">{{ $referral->email }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="fw-semibold">{{ $referral->created_at->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ $referral->created_at->format('H:i A') }}</small>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        @if($referral->last_login_at)
                                                            <div class="fw-semibold">{{ $referral->last_login_at->format('M d, Y') }}</div>
                                                            <small class="text-muted">{{ $referral->last_login_at->diffForHumans() }}</small>
                                                        @else
                                                            <div class="text-muted">Never</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <a href="{{ route('admin.referrals.show', $referral) }}" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
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
                                @foreach($referrals as $referral)
                                    <div class="col-12">
                                        <div class="card referral-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div>
                                                        <h6 class="mb-0">{{ $referral->full_name }}</h6>
                                                        <small class="text-muted">{{ $referral->email }}</small>
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails('{{ $referral->id }}')">
                                                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-{{ $referral->id }}"></iconify-icon>
                                                    </button>
                                                </div>

                                                {{-- Sponsor and Registration Info --}}
                                                <div class="row g-2 mb-3">
                                                    <div class="col-12">
                                                        <div class="small text-muted">Referred by</div>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                                                <span class="avatar-title text-white fw-semibold">{{ $referral->sponsor ? $referral->sponsor->initials : 'D' }}</span>
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="fw-medium text-truncate">{{ $referral->sponsor ? $referral->sponsor->full_name : 'Direct Registration' }}</div>
                                                                <small class="text-muted">{{ $referral->created_at->format('M d, Y') }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Expandable Details --}}
                                                <div class="collapse mt-3" id="details-{{ $referral->id }}">
                                                    <div class="border-top pt-3">
                                                        <div class="row g-2 small">
                                                            @if($referral->sponsor)
                                                                <div class="col-12">
                                                                    <div class="text-muted">Sponsor Email</div>
                                                                    <div class="fw-semibold">{{ $referral->sponsor->email }}</div>
                                                                </div>
                                                            @endif
                                                            <div class="col-6">
                                                                <div class="text-muted">Registration</div>
                                                                <div class="fw-semibold">{{ $referral->created_at->format('M d, Y H:i') }}</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="text-muted">Last Login</div>
                                                                <div class="fw-semibold">
                                                                    {{ $referral->last_login_at ? $referral->last_login_at->format('M d, Y') : 'Never' }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 pt-2 border-top">
                                                            <a href="{{ route('admin.referrals.show', $referral) }}" class="btn btn-sm btn-outline-primary">
                                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                                                View Details
                                                            </a>
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

                    {{-- Pagination Footer --}}
                    @if($referrals->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $referrals->firstItem() }}</span> to <span class="fw-semibold">{{ $referrals->lastItem() }}</span> of <span class="fw-semibold">{{ $referrals->total() }}</span> referrals
                                </div>
                                <div>
                                    {{ $referrals->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:profile-add-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Referrals Found</h6>
                            <p class="text-muted">No referrals match your current filter criteria.</p>
                            @if($search || $dateRange !== '30')
                                <button type="button" class="btn btn-primary" onclick="clearFilters()">Clear Filters</button>
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

// Filter and search functions
function filterReferrals(filterType, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(filterType, value);
    } else {
        url.searchParams.delete(filterType);
    }
    // Keep the page parameter to maintain pagination context
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function searchReferrals() {
    const search = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.origin + window.location.pathname);
    url.searchParams.set('date_range', '30');
    window.location.href = url.toString();
}

// Mobile toggle details
function toggleDetails(referralId) {
    const detailsElement = document.getElementById(`details-${referralId}`);
    const chevronElement = document.getElementById(`chevron-${referralId}`);
    
    // Close all other open details
    document.querySelectorAll('.collapse.show').forEach(element => {
        if (element.id !== `details-${referralId}`) {
            element.classList.remove('show');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        if (chevron.id !== `chevron-${referralId}`) {
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

// Export function
function exportReferrals() {
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '{{ route("admin.referrals.export") }}?' + params.toString();
    window.open(exportUrl, '_blank');
    showAlert('Export started. File will download shortly.', 'info');
}

// Refresh stats
function refreshStats() {
    const params = new URLSearchParams(window.location.search);
    
    fetch('{{ route("admin.referrals.api.dashboard-stats") }}?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('total-referrals').textContent = stats.total_referrals.toLocaleString();
                document.getElementById('today-referrals').textContent = stats.today_referrals.toLocaleString();
                document.getElementById('growth-rate').textContent = (stats.growth_rate >= 0 ? '+' : '') + stats.growth_rate.toFixed(1) + '%';
                document.getElementById('month-referrals').textContent = stats.this_month_referrals.toLocaleString();
                
                showAlert('Statistics refreshed!', 'success');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to refresh statistics', 'danger');
        });
}

// Event Listeners
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchReferrals();
    }
});

// Close all open details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.referral-mobile-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
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

.referral-row {
    transition: background-color 0.15s ease-in-out;
}

.referral-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Mobile Card Styles */
.referral-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.referral-mobile-card:hover {
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

/* Text Utilities */
.min-w-0 {
    min-width: 0;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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
    .referral-mobile-card .card-body {
        padding: 1rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .text-truncate {
        max-width: 120px;
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