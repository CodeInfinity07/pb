{{-- resources/views/admin/support/index.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'Support Dashboard', 'subTitle' => 'Monitor and manage customer support tickets'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">
                                <iconify-icon icon="iconamoon:ticket-duotone" class="me-2"></iconify-icon>
                                Support Dashboard
                            </h4>
                            <p class="text-muted mb-0">Monitor and manage customer support tickets efficiently</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-info btn-sm d-flex align-items-center" onclick="refreshStats()" id="refreshBtn">
                                <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                            <a href="{{ route('admin.support.tickets') }}" class="btn btn-primary btn-sm d-flex align-items-center">
                                <iconify-icon icon="iconamoon:menu-burger-horizontal-duotone" class="me-1"></iconify-icon>
                                All Tickets
                            </a>
                            <a href="{{ route('admin.support.tickets', ['status' => 'open']) }}" class="btn btn-warning btn-sm d-flex align-items-center">
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                Open Tickets
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:ticket-duotone" class="text-primary mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Tickets</h6>
                    <h4 class="mb-0 fw-bold text-primary">{{ number_format($stats['total_tickets']) }}</h4>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Open Tickets</h6>
                    <h4 class="mb-0 fw-bold text-warning">{{ number_format($stats['open_tickets']) }}</h4>
                    <small class="text-muted">Need attention</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:profile-circle-duotone" class="text-info mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Pending Response</h6>
                    <h4 class="mb-0 fw-bold text-info">{{ number_format($stats['pending_tickets']) }}</h4>
                    <small class="text-muted">Awaiting user</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-success mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Resolved Today</h6>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['resolved_today']) }}</h4>
                    <small class="text-muted">{{ now()->format('M j') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Unassigned</h6>
                    <h5 class="mb-0 fw-bold text-secondary">{{ number_format($stats['unassigned_tickets']) }}</h5>
                    <small class="text-muted">Need assignment</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:attention-circle-duotone" class="text-danger mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Urgent</h6>
                    <h5 class="mb-0 fw-bold text-danger">{{ number_format($stats['urgent_tickets']) }}</h5>
                    <small class="text-muted">High priority</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:clock-duotone" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Overdue</h6>
                    <h5 class="mb-0 fw-bold text-warning">{{ number_format($stats['overdue_tickets']) }}</h5>
                    <small class="text-muted">Past SLA</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:profile-circle-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">My Assigned</h6>
                    <h5 class="mb-0 fw-bold text-primary">{{ number_format($stats['my_assigned_tickets']) }}</h5>
                    <small class="text-muted">Assigned to me</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent Tickets --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                        Recent Tickets
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.support.tickets') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>

                @if($recentTickets->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">Ticket</th>
                                            <th class="border-0">Customer</th>
                                            <th class="border-0">Subject</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Priority</th>
                                            <th class="border-0">Updated</th>
                                            <th class="border-0 text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentTickets->take(8) as $ticket)
                                            <tr class="ticket-row" style="cursor: pointer;" 
                                                onclick="window.location='{{ route('admin.support.show', $ticket) }}'">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        @if($ticket->is_overdue)
                                                            <iconify-icon icon="iconamoon:clock-duotone" class="text-danger me-2" title="Overdue"></iconify-icon>
                                                        @endif
                                                        <code class="text-primary">{{ $ticket->ticket_number }}</code>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <span class="avatar-title rounded-circle bg-primary text-white">
                                                                {{ substr($ticket->user->name, 0, 1) }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $ticket->user->name }}</h6>
                                                            <small class="text-muted">{{ Str::limit($ticket->user->email, 20) }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div>
                                                        <span class="fw-semibold">{{ Str::limit($ticket->subject, 35) }}</span>
                                                        @if($ticket->category)
                                                            <br><small class="text-muted">{{ Str::title($ticket->category) }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $ticket->status_badge }}">
                                                        {{ $ticket->status_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $ticket->priority_badge }}">
                                                        {{ $ticket->priority_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <div>
                                                        <span class="text-muted small">{{ $ticket->updated_at->diffForHumans() }}</span>
                                                        @if($ticket->lastReplyBy)
                                                            <br><small class="text-muted">by {{ Str::limit($ticket->lastReplyBy->name, 15) }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <a href="{{ route('admin.support.show', $ticket) }}" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation()">
                                                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile View --}}
                        <div class="d-lg-none p-3">
                            <div class="row g-3">
                                @foreach($recentTickets->take(6) as $ticket)
                                    <div class="col-12">
                                        <div class="card border ticket-mobile-card" onclick="window.location='{{ route('admin.support.show', $ticket) }}'">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <code class="text-primary">{{ $ticket->ticket_number }}</code>
                                                        @if($ticket->is_overdue)
                                                            <span class="badge bg-danger ms-1">Overdue</span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">{{ $ticket->updated_at->diffForHumans() }}</small>
                                                </div>
                                                
                                                <h6 class="mb-1">{{ Str::limit($ticket->subject, 40) }}</h6>
                                                <p class="text-muted small mb-2">{{ $ticket->user->name }}</p>
                                                
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span class="badge {{ $ticket->status_badge }}">{{ $ticket->status_text }}</span>
                                                    <span class="badge {{ $ticket->priority_badge }}">{{ $ticket->priority_text }}</span>
                                                    @if($ticket->category)
                                                        <span class="badge bg-light text-dark">{{ Str::title($ticket->category) }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-4">
                            <iconify-icon icon="iconamoon:ticket-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h5 class="text-muted">No Recent Tickets</h5>
                            <p class="text-muted">No support tickets have been created recently.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions & Stats --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:lightning-1-duotone" class="me-1"></iconify-icon>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.support.tickets', ['status' => 'open']) }}" class="btn btn-outline-warning btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                                Open Tickets
                            </span>
                            <span class="badge bg-warning">{{ $stats['open_tickets'] }}</span>
                        </a>
                        
                        <a href="{{ route('admin.support.tickets', ['assigned_to' => 'unassigned']) }}" class="btn btn-outline-secondary btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="me-2"></iconify-icon>
                                Unassigned
                            </span>
                            <span class="badge bg-secondary">{{ $stats['unassigned_tickets'] }}</span>
                        </a>
                        
                        <a href="{{ route('admin.support.tickets', ['priority' => 'urgent']) }}" class="btn btn-outline-danger btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:attention-circle-duotone" class="me-2"></iconify-icon>
                                Urgent Tickets
                            </span>
                            <span class="badge bg-danger">{{ $stats['urgent_tickets'] }}</span>
                        </a>
                        
                        <a href="{{ route('admin.support.tickets', ['assigned_to' => auth()->id()]) }}" class="btn btn-outline-primary btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:profile-circle-duotone" class="me-2"></iconify-icon>
                                My Tickets
                            </span>
                            <span class="badge bg-primary">{{ $stats['my_assigned_tickets'] }}</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Performance Overview --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:trend-up-duotone" class="me-1"></iconify-icon>
                        Performance Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <h6 class="mb-0 text-primary">{{ $stats['total_tickets'] }}</h6>
                                <small class="text-muted">Total Tickets</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <h6 class="mb-0 text-success">{{ $stats['resolved_today'] }}</h6>
                                <small class="text-muted">Resolved Today</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <h6 class="mb-0 text-warning">{{ $stats['open_tickets'] }}</h6>
                                <small class="text-muted">Still Open</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded">
                                <h6 class="mb-0 text-info">{{ $stats['pending_tickets'] }}</h6>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>

                    {{-- Resolution Rate --}}
                    <div class="mt-3">
                        @php
                            $totalActive = $stats['open_tickets'] + $stats['resolved_today'] + $stats['pending_tickets'];
                            $resolutionRate = $totalActive > 0 ? round(($stats['resolved_today'] / $totalActive) * 100, 1) : 0;
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Resolution Rate Today</small>
                            <small class="fw-semibold">{{ $resolutionRate }}%</small>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $resolutionRate }}%"></div>
                        </div>
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
// Auto-refresh functionality
let autoRefreshInterval;
const REFRESH_INTERVAL = 60000; // 1 minute

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    setupEventListeners();
});

function setupEventListeners() {
    // Add hover effects to ticket rows
    document.querySelectorAll('.ticket-row').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0, 123, 255, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Add click handler for mobile ticket cards
    document.querySelectorAll('.ticket-mobile-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        refreshStats(true); // Silent refresh
    }, REFRESH_INTERVAL);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

function refreshStats(silent = false) {
    const refreshBtn = document.getElementById('refreshBtn');
    
    if (!silent) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<iconify-icon icon="iconamoon:restart-duotone" class="me-1 spinning"></iconify-icon>Refreshing...';
        showAlert('Refreshing dashboard statistics...', 'info', 2000);
    }
    
    // You can make an AJAX call here to get updated stats
    // For now, we'll just reload the page
    setTimeout(() => {
        location.reload();
    }, silent ? 100 : 1000);
}

// Utility functions
function showAlert(message, type = 'info', duration = 4000) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const iconMap = {
        success: 'check-circle-1',
        danger: 'close-circle-1',
        warning: 'attention-circle',
        info: 'information-circle'
    };
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm" id="${alertId}" role="alert">
            <iconify-icon icon="iconamoon:${iconMap[type]}-duotone" class="me-2"></iconify-icon>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

// Handle page visibility change to pause/resume auto-refresh
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>

<style>
/* Base card styles */
.card {
    border-radius: 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
}

/* Stats card hover effect */
.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Avatar styles */
.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-weight: 600;
    font-size: 0.875rem;
}

/* Table hover effects */
.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.ticket-row {
    transition: all 0.2s ease;
}

.ticket-mobile-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

/* Code styling for ticket numbers */
code {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    background-color: rgba(0, 123, 255, 0.1);
    border-radius: 0.25rem;
    color: var(--bs-primary);
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Monaco, 'Courier New', monospace;
}

/* Badge improvements */
.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
}

/* Button improvements */
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

/* Progress bar styling */
.progress {
    border-radius: 0.375rem;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.6s ease;
}

/* Loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinning {
    animation: spin 1s linear infinite;
}

/* Alert container */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .stats-card .card-body {
        padding: 1rem 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 575.98px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
    
    .stats-card h4 {
        font-size: 1.5rem;
    }
}

/* Dark mode support (if your admin template supports it) */
@media (prefers-color-scheme: dark) {
    .bg-light {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }
    
    code {
        background-color: rgba(0, 123, 255, 0.2);
    }
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Focus states for accessibility */
.btn:focus,
.card:focus-within {
    outline: 2px solid rgba(0, 123, 255, 0.25);
    outline-offset: 2px;
}

/* Print styles */
@media print {
    .btn,
    .dropdown,
    #alertContainer {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
}
</style>
@endsection