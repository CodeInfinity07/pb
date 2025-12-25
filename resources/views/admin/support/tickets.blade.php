{{-- resources/views/admin/support/tickets.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'Support Tickets', 'subTitle' => 'Manage all customer support tickets'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Support Tickets</h4>
                            <p class="text-muted mb-0">View and manage all customer support requests</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleFilters()" id="filterToggleBtn">
                                <iconify-icon icon="iconamoon:funnel-duotone" class="me-1"></iconify-icon>
                                Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshTickets()">
                                <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                            <a href="{{ route('admin.support.index') }}" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:apps-duotone" class="me-1"></iconify-icon>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Panel --}}
    <div id="filtersPanel" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:funnel-duotone" class="me-1"></iconify-icon>
                        Filter Tickets
                    </h6>
                </div>
                <form method="GET" action="{{ route('admin.support.tickets') }}" id="filterForm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="">All Statuses</option>
                                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="pending_user" {{ request('status') === 'pending_user' ? 'selected' : '' }}>Pending User</option>
                                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" name="priority" id="priority">
                                    <option value="">All Priorities</option>
                                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category" id="category">
                                    <option value="">All Categories</option>
                                    <option value="technical" {{ request('category') === 'technical' ? 'selected' : '' }}>Technical</option>
                                    <option value="billing" {{ request('category') === 'billing' ? 'selected' : '' }}>Billing</option>
                                    <option value="account" {{ request('category') === 'account' ? 'selected' : '' }}>Account</option>
                                    <option value="feature" {{ request('category') === 'feature' ? 'selected' : '' }}>Feature</option>
                                    <option value="bug" {{ request('category') === 'bug' ? 'selected' : '' }}>Bug</option>
                                    <option value="general" {{ request('category') === 'general' ? 'selected' : '' }}>General</option>
                                    <option value="other" {{ request('category') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="assigned_to" class="form-label">Assigned To</label>
                                <select class="form-select" name="assigned_to" id="assigned_to">
                                    <option value="">All Assignments</option>
                                    <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                                    @foreach($assignableUsers as $user)
                                        <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="user_search" class="form-label">User Search</label>
                                <input type="text" class="form-control" name="user_search" id="user_search" 
                                       value="{{ request('user_search') }}" placeholder="Search by user name or email">
                            </div>
                            <div class="col-md-6">
                                <label for="ticket_number" class="form-label">Ticket Number</label>
                                <input type="text" class="form-control" name="ticket_number" id="ticket_number" 
                                       value="{{ request('ticket_number') }}" placeholder="Search by ticket number">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between">
                            <div>
                                @if(request()->hasAny(['status', 'priority', 'category', 'assigned_to', 'user_search', 'ticket_number']))
                                    <a href="{{ route('admin.support.tickets') }}" class="btn btn-outline-secondary btn-sm">
                                        <iconify-icon icon="iconamoon:close-circle-1-duotone" class="me-1"></iconify-icon>
                                        Clear Filters
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:discover-duotone" class="me-1"></iconify-icon>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-primary mb-1">{{ number_format($tickets->total()) }}</h6>
                    <small class="text-muted">Total Found</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-warning mb-1">{{ $tickets->where('status', 'open')->count() }}</h6>
                    <small class="text-muted">Open</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-info mb-1">{{ $tickets->where('status', 'in_progress')->count() }}</h6>
                    <small class="text-muted">In Progress</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-secondary mb-1">{{ $tickets->where('status', 'pending_user')->count() }}</h6>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-success mb-1">{{ $tickets->where('status', 'resolved')->count() }}</h6>
                    <small class="text-muted">Resolved</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-danger mb-1">{{ $tickets->filter(fn($t) => $t->is_overdue)->count() }}</h6>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tickets Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        Support Tickets 
                        @if(request()->hasAny(['status', 'priority', 'category', 'assigned_to', 'user_search', 'ticket_number']))
                            <small class="text-muted">(Filtered Results)</small>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">{{ $tickets->total() }} tickets found</small>
                    </div>
                </div>

                @if($tickets->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">Ticket</th>
                                            <th class="border-0">User</th>
                                            <th class="border-0">Subject</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Priority</th>
                                            <th class="border-0">Assigned To</th>
                                            <th class="border-0">Last Update</th>
                                            <th class="border-0 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($tickets as $ticket)
                                            <tr class="ticket-row" data-ticket-id="{{ $ticket->id }}">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        @if($ticket->is_overdue)
                                                            <iconify-icon icon="iconamoon:clock-duotone" class="text-danger me-2" title="Overdue"></iconify-icon>
                                                        @endif
                                                        <div>
                                                            <code class="text-primary">{{ $ticket->ticket_number }}</code>
                                                            @if($ticket->category)
                                                                <br><small class="text-muted">{{ Str::title($ticket->category) }}</small>
                                                            @endif
                                                        </div>
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
                                                            <small class="text-muted">{{ $ticket->user->email }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="fw-semibold">{{ Str::limit($ticket->subject, 50) }}</span>
                                                    <br><small class="text-muted">{{ Str::limit($ticket->description, 80) }}</small>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $ticket->status_badge }} status-badge" 
                                                          data-ticket-id="{{ $ticket->id }}" style="cursor: pointer;" 
                                                          title="Click to change status">
                                                        {{ $ticket->status_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $ticket->priority_badge }} priority-badge" 
                                                          data-ticket-id="{{ $ticket->id }}" style="cursor: pointer;" 
                                                          title="Click to change priority">
                                                        {{ $ticket->priority_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="assignment-container" data-ticket-id="{{ $ticket->id }}">
                                                        @if($ticket->assignedTo)
                                                            <div class="d-flex align-items-center">
                                                                <div class="avatar-xs me-1">
                                                                    <span class="avatar-title rounded-circle bg-success text-white small">
                                                                        {{ substr($ticket->assignedTo->name, 0, 1) }}
                                                                    </span>
                                                                </div>
                                                                <span class="text-success small">{{ $ticket->assignedTo->name }}</span>
                                                            </div>
                                                        @else
                                                            <button class="btn btn-outline-secondary btn-xs assign-btn" 
                                                                    data-ticket-id="{{ $ticket->id }}">
                                                                <iconify-icon icon="iconamoon:sign-plus-duotone"></iconify-icon>
                                                                Assign
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div>
                                                        <span class="text-muted small">{{ $ticket->updated_at->diffForHumans() }}</span>
                                                        @if($ticket->lastReplyBy)
                                                            <br><small class="text-muted">by {{ $ticket->lastReplyBy->name }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.support.show', $ticket) }}">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                                                                    View Details
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            @if($ticket->assigned_to !== auth()->id())
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                                       onclick="assignToMe({{ $ticket->id }})">
                                                                        <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-2"></iconify-icon>
                                                                        Assign to Me
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            @if($ticket->status !== 'resolved')
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                                       onclick="quickStatusUpdate({{ $ticket->id }}, 'resolved')">
                                                                        <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-2"></iconify-icon>
                                                                        Mark Resolved
                                                                    </a>
                                                                </li>
                                                            @endif
                                                            @if($ticket->status !== 'closed')
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:void(0)" 
                                                                       onclick="quickStatusUpdate({{ $ticket->id }}, 'closed')">
                                                                        <iconify-icon icon="iconamoon:close-circle-1-duotone" class="me-2"></iconify-icon>
                                                                        Close Ticket
                                                                    </a>
                                                                </li>
                                                            @endif
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
                                @foreach($tickets as $ticket)
                                    <div class="col-12">
                                        <div class="card border">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <code class="text-primary">{{ $ticket->ticket_number }}</code>
                                                        @if($ticket->is_overdue)
                                                            <span class="badge bg-danger ms-1">Overdue</span>
                                                        @endif
                                                        <h6 class="mb-1 mt-1">{{ Str::limit($ticket->subject, 40) }}</h6>
                                                        <small class="text-muted">{{ $ticket->user->name }}</small>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.support.show', $ticket) }}">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                                                                    View Details
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="d-flex flex-wrap gap-1 mb-3">
                                                    <span class="badge {{ $ticket->status_badge }}">{{ $ticket->status_text }}</span>
                                                    <span class="badge {{ $ticket->priority_badge }}">{{ $ticket->priority_text }}</span>
                                                    @if($ticket->category)
                                                        <span class="badge bg-light text-dark">{{ Str::title($ticket->category) }}</span>
                                                    @endif
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        @if($ticket->assignedTo)
                                                            Assigned to {{ $ticket->assignedTo->name }}
                                                        @else
                                                            Unassigned
                                                        @endif
                                                    </small>
                                                    <small class="text-muted">{{ $ticket->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    @if($tickets->hasPages())
                        <div class="card-footer">
                            {{ $tickets->links() }}
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:discover-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h5 class="text-muted">No Tickets Found</h5>
                            <p class="text-muted">
                                @if(request()->hasAny(['status', 'priority', 'category', 'assigned_to', 'user_search', 'ticket_number']))
                                    Try adjusting your search filters or 
                                    <a href="{{ route('admin.support.tickets') }}" class="text-primary">clear all filters</a>
                                @else
                                    No support tickets have been created yet.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Status Update Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusTicketId">
                <div class="form-group">
                    <label for="newStatus" class="form-label">New Status</label>
                    <select class="form-select" id="newStatus">
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="pending_user">Pending User</option>
                        <option value="resolved">Resolved</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">Update Status</button>
            </div>
        </div>
    </div>
</div>

{{-- Priority Update Modal --}}
<div class="modal fade" id="priorityModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Priority</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="priorityTicketId">
                <div class="form-group">
                    <label for="newPriority" class="form-label">New Priority</label>
                    <select class="form-select" id="newPriority">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updatePriority()">Update Priority</button>
            </div>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
});

// Event listeners
function setupEventListeners() {
    // Status badge clicks
    document.querySelectorAll('.status-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const ticketId = this.dataset.ticketId;
            showStatusModal(ticketId);
        });
    });

    // Priority badge clicks
    document.querySelectorAll('.priority-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const ticketId = this.dataset.ticketId;
            showPriorityModal(ticketId);
        });
    });

    // Assignment buttons
    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const ticketId = this.dataset.ticketId;
            assignToMe(ticketId);
        });
    });
}

// Filter functions
function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    const btn = document.getElementById('filterToggleBtn');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        btn.classList.add('active');
    } else {
        panel.style.display = 'none';
        btn.classList.remove('active');
    }
}

function refreshTickets() {
    showAlert('Refreshing tickets...', 'info');
    setTimeout(() => location.reload(), 500);
}

// Status modal functions
function showStatusModal(ticketId) {
    document.getElementById('statusTicketId').value = ticketId;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function updateStatus() {
    const ticketId = document.getElementById('statusTicketId').value;
    const newStatus = document.getElementById('newStatus').value;
    
    fetch(`{{ url('admin/support/tickets') }}/${ticketId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update status', 'danger');
    });
}

// Priority modal functions
function showPriorityModal(ticketId) {
    document.getElementById('priorityTicketId').value = ticketId;
    new bootstrap.Modal(document.getElementById('priorityModal')).show();
}

function updatePriority() {
    const ticketId = document.getElementById('priorityTicketId').value;
    const newPriority = document.getElementById('newPriority').value;
    
    fetch(`{{ url('admin/support/tickets') }}/${ticketId}/priority`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ priority: newPriority })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('priorityModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update priority', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update priority', 'danger');
    });
}

// Quick actions
function assignToMe(ticketId) {
    fetch(`{{ url('admin/support/tickets') }}/${ticketId}/assign`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ assigned_to: {{ auth()->id() }} })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to assign ticket', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to assign ticket', 'danger');
    });
}

function quickStatusUpdate(ticketId, status) {
    fetch(`{{ url('admin/support/tickets') }}/${ticketId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update status', 'danger');
    });
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

// Show filters if any are applied
@if(request()->hasAny(['status', 'priority', 'category', 'assigned_to', 'user_search', 'ticket_number']))
    document.addEventListener('DOMContentLoaded', function() {
        toggleFilters();
    });
@endif
</script>

<style>
.card {
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-xs {
    width: 1.5rem;
    height: 1.5rem;
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

code {
    font-size: 0.875rem;
    padding: 0.25rem 0.5rem;
    background-color: rgba(0, 123, 255, 0.1);
    border-radius: 0.25rem;
}

.badge {
    transition: all 0.2s ease;
}

.badge:hover {
    transform: scale(1.05);
}

.status-badge,
.priority-badge {
    cursor: pointer;
}

.status-badge:hover,
.priority-badge:hover {
    opacity: 0.8;
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.2;
}

.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-item {
    transition: all 0.15s ease-in-out;
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

.form-control,
.form-select {
    border-radius: 0.5rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.ticket-row {
    transition: all 0.2s ease;
}

.ticket-row:hover {
    background-color: rgba(0, 123, 255, 0.02);
}
</style>
@endsection