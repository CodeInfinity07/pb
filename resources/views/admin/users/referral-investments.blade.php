@extends('admin.layouts.vertical', ['title' => 'Referral Investments', 'subTitle' => 'Admin'])

@section('content')

{{-- Back Button & User Info Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-md rounded-circle bg-primary">
                                <span class="avatar-title text-white fw-semibold fs-5">
                                    {{ $targetUser->initials }}
                                </span>
                            </div>
                            <div>
                                <h5 class="mb-0">{{ $targetUser->full_name }}</h5>
                                <p class="text-muted mb-0 small">{{ $targetUser->email }} • Joined {{ $targetUser->created_at->format('M d, Y') }}</p>
                            </div>
                        </div>
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
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:profile-circle-duotone" class="text-primary" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">{{ number_format($summary->total_referrals ?? 0) }}</h5>
                <h6 class="text-muted mb-0">Total Referrals</h6>
                <small class="text-muted">{{ $summary->max_depth ?? 0 }} level{{ ($summary->max_depth ?? 0) > 1 ? 's' : '' }} deep</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="mdi:bitcoin" class="text-success" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">${{ number_format($summary->total_invested_by_all_referrals ?? 0, 2) }}</h5>
                <h6 class="text-muted mb-0">Total Invested</h6>
                <small class="text-muted">{{ number_format($summary->total_investments ?? 0) }} investment{{ ($summary->total_investments ?? 0) != 1 ? 's' : '' }}</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="material-symbols:bar-chart-4-bars" class="text-info" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">${{ number_format($summary->total_roi_paid_to_all_referrals ?? 0, 2) }}</h5>
                <h6 class="text-muted mb-0">Total ROI Paid</h6>
                <small class="text-muted">{{ number_format($summary->total_roi_payments ?? 0) }} payment{{ ($summary->total_roi_payments ?? 0) != 1 ? 's' : '' }}</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-warning" style="font-size: 2.5rem;"></iconify-icon>
                </div>
                <h5 class="mb-1">{{ number_format($summary->users_with_active_investments ?? 0) }}</h5>
                <h6 class="text-muted mb-0">Active Investors</h6>
                <small class="text-muted">Currently investing</small>
            </div>
        </div>
    </div>
</div>

{{-- Filters & Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
                    <div>
                        <h5 class="card-title mb-1">Referral Investments ({{ $referralData->total() }})</h5>
                        <p class="text-muted mb-0 small">Track investment performance across your referral network</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        {{-- Search --}}
                        <form method="GET" class="d-flex" id="searchForm">
                            <input type="hidden" name="level" value="{{ request('level') }}">
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <input type="text" name="search" class="form-control" placeholder="Search referrals..." value="{{ request('search') }}">
                                <button type="submit" class="btn btn-outline-secondary">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                        </form>
                        
                        {{-- Level Filter --}}
                        <select class="form-select form-select-sm" onchange="filterByLevel(this.value)" style="width: auto;">
                            <option value="" {{ !request('level') ? 'selected' : '' }}>All Levels</option>
                            @for($i = 1; $i <= ($summary->max_depth ?? 5); $i++)
                                <option value="{{ $i }}" {{ request('level') == $i ? 'selected' : '' }}>Level {{ $i }}</option>
                            @endfor
                        </select>
                        
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="showLevelSummary()">
                            <iconify-icon icon="iconamoon:graph-bar-duotone"></iconify-icon> Summary
                        </button>
                        
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="exportData()">
                            <iconify-icon icon="iconamoon:file-export-duotone"></iconify-icon> Export
                        </button>
                        
                        @if(request()->hasAny(['level', 'search']))
                        <a href="{{ route('admin.users.referral-investments', $targetUser->id) }}" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="material-symbols:refresh-rounded"></iconify-icon> Clear
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            @if($referralData->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Level</th>
                                    <th>User</th>
                                    <th class="text-end">Total Invested</th>
                                    <th class="text-end">Total ROI</th>
                                    <th class="text-center">Investments</th>
                                    <th class="text-center pe-4">Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($referralData as $referral)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-primary">L{{ $referral->referral_level }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-circle bg-secondary me-2">
                                                    <span class="avatar-title text-white fw-semibold">
                                                        {{ strtoupper(substr($referral->full_name, 0, 2)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold">{{ $referral->full_name }}</div>
                                                    <small class="text-muted">{{ $referral->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-success">${{ number_format($referral->total_invested, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <strong class="text-info">${{ number_format($referral->total_roi_received, 2) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            <div>
                                                @if($referral->active_investments > 0)
                                                    <span class="badge bg-primary">{{ $referral->active_investments }} Active</span>
                                                @endif
                                                @if($referral->completed_investments > 0)
                                                    <span class="badge bg-success ms-1">{{ $referral->completed_investments }} Done</span>
                                                @endif
                                            </div>
                                            <small class="text-muted d-block mt-1">{{ $referral->investment_count }} total</small>
                                        </td>
                                        <td class="text-center pe-4">
                                            <small>{{ \Carbon\Carbon::parse($referral->created_at)->format('M d, Y') }}</small>
                                            <small class="text-muted d-block">{{ \Carbon\Carbon::parse($referral->created_at)->diffForHumans() }}</small>
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
                        @foreach($referralData as $referral)
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-primary me-2">L{{ $referral->referral_level }}</span>
                                                <div>
                                                    <h6 class="mb-0">{{ $referral->full_name }}</h6>
                                                    <small class="text-muted">{{ $referral->username }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-2 text-center mb-2">
                                            <div class="col-4">
                                                <div class="small">
                                                    <div class="text-muted">Invested</div>
                                                    <div class="fw-bold text-success">${{ number_format($referral->total_invested, 2) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="small">
                                                    <div class="text-muted">ROI Paid</div>
                                                    <div class="fw-bold text-info">${{ number_format($referral->total_roi_received, 2) }}</div>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="small">
                                                    <div class="text-muted">Investments</div>
                                                    <div>
                                                        @if($referral->active_investments > 0)
                                                            <span class="badge bg-primary">{{ $referral->active_investments }}</span>
                                                        @endif
                                                        @if($referral->completed_investments > 0)
                                                            <span class="badge bg-success">{{ $referral->completed_investments }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center text-muted small border-top pt-2">
                                            Joined {{ \Carbon\Carbon::parse($referral->created_at)->format('M d, Y') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Pagination --}}
            @if($referralData->hasPages())
            <div class="card-footer border-top">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="text-muted small">
                        Showing {{ $referralData->firstItem() }} to {{ $referralData->lastItem() }} of {{ $referralData->total() }} referrals
                    </div>
                    <div>
                        {{ $referralData->appends(request()->query())->links('pagination::bootstrap-4') }}
                    </div>
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:search-duotone" class="text-muted" style="font-size: 4rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-2">No Referral Investments Found</h6>
                <p class="text-muted mb-3">
                    @if(request()->hasAny(['level', 'search']))
                        No referrals match your current filters.
                    @else
                        This user's referrals haven't made any investments yet.
                    @endif
                </p>
                @if(request()->hasAny(['level', 'search']))
                <a href="{{ route('admin.users.referral-investments', $targetUser->id) }}" class="btn btn-primary">
                    <iconify-icon icon="iconamoon:refresh-duotone" class="me-1"></iconify-icon>Clear Filters
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Level Summary Modal --}}
<div class="modal fade" id="levelSummaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:graph-bar-duotone" class="me-2"></iconify-icon>
                    Summary by Level
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="levelSummaryContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Filter by level
function filterByLevel(level) {
    const url = new URL(window.location.href);
    if (level) {
        url.searchParams.set('level', level);
    } else {
        url.searchParams.delete('level');
    }
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}

// Export data
function exportData() {
    const url = '{{ route("admin.users.export-referral-investments", $targetUser->id) }}';
    const params = new URLSearchParams(window.location.search);
    window.open(url + '?' + params.toString(), '_blank');
}

// Show level summary
function showLevelSummary() {
    const modal = new bootstrap.Modal(document.getElementById('levelSummaryModal'));
    modal.show();
    
    fetch('{{ route("admin.users.referral-summary-by-level", $targetUser->id) }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="table-responsive">';
                html += '<table class="table table-hover align-middle mb-0">';
                html += '<thead class="bg-light">';
                html += '<tr>';
                html += '<th>Level</th>';
                html += '<th class="text-end">Users</th>';
                html += '<th class="text-end">Total Invested</th>';
                html += '<th class="text-end">Total ROI</th>';
                html += '<th class="text-end">Investments</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';
                
                if (data.data.length === 0) {
                    html += '<tr><td colspan="5" class="text-center py-4 text-muted">No data available</td></tr>';
                } else {
                    data.data.forEach(level => {
                        html += '<tr>';
                        html += `<td><span class="badge bg-primary">Level ${level.referral_level}</span></td>`;
                        html += `<td class="text-end"><strong>${level.users_count}</strong></td>`;
                        html += `<td class="text-end text-success"><strong>$${parseFloat(level.total_invested).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>`;
                        html += `<td class="text-end text-info"><strong>$${parseFloat(level.total_roi_paid).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>`;
                        html += `<td class="text-end">${level.investment_count}</td>`;
                        html += '</tr>';
                    });
                }
                
                html += '</tbody>';
                html += '</table>';
                html += '</div>';
                
                document.getElementById('levelSummaryContent').innerHTML = html;
            } else {
                document.getElementById('levelSummaryContent').innerHTML = 
                    '<div class="alert alert-danger mb-0">Failed to load summary</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('levelSummaryContent').innerHTML = 
                '<div class="alert alert-danger mb-0">Failed to load summary</div>';
        });
}

// Alert helper
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 400px;';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <iconify-icon icon="iconamoon:${type === 'success' ? 'check-circle-1' : 'information-circle'}-duotone" class="me-2 fs-5"></iconify-icon>
            <div>${message}</div>
        </div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<style>
.avatar-sm {
    width: 2.25rem;
    height: 2.25rem;
    font-size: 0.875rem;
}

.avatar-md {
    width: 3rem;
    height: 3rem;
    font-size: 1.125rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: inherit;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    white-space: nowrap;
    padding: 0.875rem 0.75rem;
}

.table td {
    vertical-align: middle;
    padding: 0.875rem 0.75rem;
}

.card {
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: none;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.badge {
    font-weight: 500;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
}

.btn-sm {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
}

/* Summary cards hover effect */
.col-6.col-lg-3 .card {
    transition: transform 0.2s;
}

.col-6.col-lg-3 .card:hover {
    transform: translateY(-2px);
}

/* Mobile responsive */
@media (max-width: 767.98px) {
    .avatar-sm {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Pagination styling */
.pagination {
    margin-bottom: 0;
}

.page-link {
    border-radius: 0.375rem;
    margin: 0 0.125rem;
    border: none;
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
@endsection