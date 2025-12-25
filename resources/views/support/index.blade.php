@extends('layouts.vertical', ['title' => 'Support Tickets', 'subTitle' => 'Help & Support'])

@section('content')

{{-- Support Statistics Row --}}
<div class="row mb-3 mb-md-4">
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-primary h-100">
            <div class="card-body text-center p-3">
                <iconify-icon icon="iconamoon:ticket-duotone" class="fs-2 fs-md-1 text-primary mb-2"></iconify-icon>
                <h5 class="h6 h-md-4 text-primary mb-1">{{ $stats['total_tickets'] }}</h5>
                <small class="text-muted d-block">Total Tickets</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-warning h-100">
            <div class="card-body text-center p-3">
                <iconify-icon icon="iconamoon:clock-duotone" class="fs-2 fs-md-1 text-warning mb-2"></iconify-icon>
                <h5 class="h6 h-md-4 text-warning mb-1">{{ $stats['open_tickets'] }}</h5>
                <small class="text-muted d-block">Open Tickets</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-success h-100">
            <div class="card-body text-center p-3">
                <iconify-icon icon="material-symbols:check-circle-outline" class="fs-2 fs-md-1 text-success mb-2"></iconify-icon>
                <h5 class="h6 h-md-4 text-success mb-1">{{ $stats['resolved_tickets'] }}</h5>
                <small class="text-muted d-block">Resolved</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3 mb-3">
        <div class="card border-secondary h-100">
            <div class="card-body text-center p-3">
                <iconify-icon icon="iconamoon:folder-duotone" class="fs-2 fs-md-1 text-secondary mb-2"></iconify-icon>
                <h5 class="h6 h-md-4 text-secondary mb-1">{{ $stats['closed_tickets'] }}</h5>
                <small class="text-muted d-block">Closed</small>
            </div>
        </div>
    </div>
</div>

{{-- Main Support Tickets Card --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <h4 class="card-title mb-0">My Support Tickets</h4>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="row g-2">
                            <div class="col-12 col-sm-4 col-md-4">
                                <select class="form-select form-select-sm" onchange="filterTickets('status', this.value)">
                                    <option value="" {{ !request('status') ? 'selected' : '' }}>All Status</option>
                                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-4 col-md-4">
                                <select class="form-select form-select-sm" onchange="filterTickets('priority', this.value)">
                                    <option value="" {{ !request('priority') ? 'selected' : '' }}>All Priorities</option>
                                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-4 col-md-4">
                                <a href="{{ route('support.create') }}" class="btn btn-primary btn-sm w-100">
                                    <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                                    <span class="d-none d-sm-inline">New Ticket</span>
                                    <span class="d-sm-none">New</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($tickets->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50">
                                <tr>
                                    <th scope="col" class="ps-4">Ticket #</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Priority</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Last Updated</th>
                                    <th scope="col" class="pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                <tr>
                                    <td class="ps-4">
                                        <code class="small">#{{ $ticket->ticket_number }}</code>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ Str::limit($ticket->subject, 40) }}</div>
                                        <small class="text-muted">{{ $ticket->created_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info">
                                            {{ ucfirst($ticket->category) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }}-subtle text-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }}">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }}-subtle text-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }}">
                                            {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $ticket->updated_at->format('M d, Y') }}
                                        <small class="text-muted d-block">{{ $ticket->updated_at->format('h:i A') }}</small>
                                    </td>
                                    <td class="pe-4">
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('support.show', $ticket) }}" class="btn btn-sm btn-outline-primary">
                                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                            </a>
                                            @if($ticket->status !== 'closed')
                                                @if($ticket->status === 'resolved')
                                                <button class="btn btn-sm btn-outline-success" onclick="reopenTicket('{{ $ticket->id }}')">
                                                    <iconify-icon icon="iconamoon:restart-duotone"></iconify-icon>
                                                </button>
                                                @endif
                                                <button class="btn btn-sm btn-outline-danger" onclick="closeTicket('{{ $ticket->id }}')">
                                                    <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none">
                    <div class="p-3">
                        @foreach($tickets as $ticket)
                        <div class="card ticket-card border-start border-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }} border-3 mb-3">
                            <div class="card-body p-3">
                                {{-- Header Row --}}
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                            <code class="small text-nowrap">#{{ $ticket->ticket_number }}</code>
                                            <span class="badge bg-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }}-subtle text-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }} text-nowrap">
                                                {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                            </span>
                                        </div>
                                        <h6 class="mb-1 fw-semibold text-truncate">{{ $ticket->subject }}</h6>
                                        <small class="text-muted">{{ $ticket->created_at->format('M d, Y • H:i') }}</small>
                                    </div>
                                    <div class="dropdown ms-2">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('support.show', $ticket) }}">
                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                            </a></li>
                                            @if($ticket->status !== 'closed')
                                                @if($ticket->status === 'resolved')
                                                <li><a class="dropdown-item text-success" href="#" onclick="reopenTicket('{{ $ticket->id }}')">
                                                    <iconify-icon icon="iconamoon:restart-duotone" class="me-2"></iconify-icon>Reopen
                                                </a></li>
                                                @endif
                                                <li><a class="dropdown-item text-danger" href="#" onclick="closeTicket('{{ $ticket->id }}')">
                                                    <iconify-icon icon="iconamoon:close-duotone" class="me-2"></iconify-icon>Close
                                                </a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                                {{-- Content Row --}}
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="badge bg-info-subtle text-info text-nowrap">{{ ucfirst($ticket->category) }}</span>
                                        <span class="badge bg-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }}-subtle text-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }} text-nowrap">
                                            {{ ucfirst($ticket->priority) }}
                                        </span>
                                    </div>
                                    <small class="text-muted text-nowrap">Updated {{ $ticket->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Pagination Footer --}}
            @if($tickets->hasPages())
            <div class="card-footer border-top">
                <div class="row align-items-center text-center text-sm-start">
                    <div class="col-12 col-sm-6 mb-3 mb-sm-0">
                        <div class="text-muted small">
                            Showing <span class="fw-semibold">{{ $tickets->firstItem() }}</span>
                            to <span class="fw-semibold">{{ $tickets->lastItem() }}</span>
                            of <span class="fw-semibold">{{ $tickets->total() }}</span> tickets
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <nav aria-label="Pagination">
                            <ul class="pagination pagination-sm mb-0 justify-content-center justify-content-sm-end">
                                {{-- Previous Page Link --}}
                                @if ($tickets->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $tickets->previousPageUrl() }}"><i class="bx bxs-chevron-left"></i></a>
                                    </li>
                                @endif

                                {{-- Pagination Elements --}}
                                @php
                                    $start = max($tickets->currentPage() - 2, 1);
                                    $end = min($start + 4, $tickets->lastPage());
                                    $start = max($end - 4, 1);
                                @endphp

                                @if($start > 1)
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $tickets->url(1) }}">1</a>
                                    </li>
                                    @if($start > 2)
                                        <li class="page-item disabled d-none d-sm-block">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                @endif

                                @for($i = $start; $i <= $end; $i++)
                                    @if ($i == $tickets->currentPage())
                                        <li class="page-item active">
                                            <span class="page-link">{{ $i }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $tickets->url($i) }}">{{ $i }}</a>
                                        </li>
                                    @endif
                                @endfor

                                @if($end < $tickets->lastPage())
                                    @if($end < $tickets->lastPage() - 1)
                                        <li class="page-item disabled d-none d-sm-block">
                                            <span class="page-link">...</span>
                                        </li>
                                    @endif
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $tickets->url($tickets->lastPage()) }}">{{ $tickets->lastPage() }}</a>
                                    </li>
                                @endif

                                {{-- Next Page Link --}}
                                @if ($tickets->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $tickets->nextPageUrl() }}"><i class="bx bxs-chevron-right"></i></a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="bx bxs-chevron-right"></i></span>
                                    </li>
                                @endif
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            @endif

            @else
            {{-- Empty State --}}
            <div class="card-body">
                <div class="text-center py-4 py-md-5">
                    <iconify-icon icon="iconamoon:ticket-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Support Tickets</h6>
                    <p class="text-muted mb-4">You haven't created any support tickets yet.</p>
                    <a href="{{ route('support.create') }}" class="btn btn-primary">
                        <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                        Create Your First Ticket
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
function filterTickets(type, value) {
    const url = new URL(window.location.href);
    if (value) {
        url.searchParams.set(type, value);
    } else {
        url.searchParams.delete(type);
    }
    window.location.href = url.toString();
}

function closeTicket(ticketId) {
    if (confirm('Are you sure you want to close this ticket?')) {
        fetch(`{{ url('support') }}/${ticketId}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ _method: 'PATCH' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message || 'Failed to close ticket', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to close ticket', 'danger');
        });
    }
}

function reopenTicket(ticketId) {
    fetch(`{{ url('support') }}/${ticketId}/reopen`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to reopen ticket', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to reopen ticket', 'danger');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; left: 20px; z-index: 9999; max-width: calc(100% - 40px);';
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
/* Base card styling */
.card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: none;
    transition: all 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

/* Statistics cards responsive */
.card-body {
    min-height: auto;
}

/* Mobile-first responsive design */
@media (max-width: 575.98px) {
    /* Statistics cards on mobile */
    .card-body.text-center {
        padding: 1rem 0.75rem;
    }
    
    .fs-2 {
        font-size: 1.5rem !important;
    }
    
    .h6 {
        font-size: 1rem;
    }
    
    /* Ticket cards on mobile */
    .ticket-card .card-body {
        padding: 1rem;
    }
    
    .ticket-card h6 {
        font-size: 0.9rem;
        line-height: 1.3;
    }
    
    /* Badge sizing */
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    /* Code elements */
    code {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
    
    /* Dropdown menu positioning */
    .dropdown-menu {
        font-size: 0.85rem;
    }
    
    /* Alert positioning on mobile */
    .position-fixed {
        left: 10px !important;
        right: 10px !important;
        max-width: calc(100% - 20px) !important;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    /* Small tablet adjustments */
    .card-body.text-center {
        padding: 1.25rem;
    }
    
    .fs-2 {
        font-size: 1.75rem !important;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    /* Medium tablet adjustments */
    .card-header .row .col-md-6:last-child .row {
        gap: 0.5rem;
    }
    
    .btn-sm {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
    }
}

/* Table responsive improvements */
.table-responsive {
    border-radius: 0;
    -webkit-overflow-scrolling: touch;
}

.table th,
.table td {
    white-space: nowrap;
    vertical-align: middle;
}

/* Badge styling */
.badge {
    font-weight: 500;
    white-space: nowrap;
}

.badge[class*="-subtle"] {
    padding: 0.35em 0.65em;
}

/* Ticket card improvements */
.ticket-card {
    border-radius: 8px;
    transition: all 0.2s ease;
}

.ticket-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Utility classes for mobile */
.min-w-0 {
    min-width: 0;
}

.text-nowrap {
    white-space: nowrap;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Pagination mobile improvements */
@media (max-width: 575.98px) {
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .page-link {
        padding: 0.375rem 0.5rem;
        font-size: 0.8rem;
    }
    
    .pagination .page-item {
        margin: 0 1px;
    }
}

/* Form select mobile improvements */
.form-select-sm {
    font-size: 0.8rem;
    padding: 0.375rem 1.75rem 0.375rem 0.5rem;
}

@media (max-width: 575.98px) {
    .form-select-sm {
        font-size: 0.75rem;
        padding: 0.25rem 1.5rem 0.25rem 0.375rem;
    }
}

/* Button improvements */
.btn {
    transition: all 0.2s ease;
    white-space: nowrap;
}

.btn:hover:not(.disabled):not(:disabled) {
    transform: translateY(-1px);
}

/* Icon consistency */
iconify-icon {
    vertical-align: middle;
}

/* Loading and interaction states */
.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Smooth scrolling for mobile */
@media (max-width: 991.98px) {
    .table-responsive {
        scrollbar-width: thin;
        scrollbar-color: #dee2e6 transparent;
    }
    
    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #dee2e6;
        border-radius: 3px;
    }
}
</style>
@endsection