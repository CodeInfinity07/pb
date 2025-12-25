{{-- resources/views/admin/investment/index.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'Investment Plans', 'subTitle' => 'Manage Investment Plans'])

@section('css')
<style>
    .stats-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    }
    .plan-type-badge {
        font-size: 0.75rem;
        font-weight: 500;
        border-radius: 6px;
    }
    .tier-indicator {
        background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .action-buttons .btn {
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.875rem;
    }
    .drag-handle {
        cursor: grab;
        color: #6c757d;
        font-size: 1.1rem;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    .sortable-ghost {
        opacity: 0.5;
        background: #f8f9fa;
    }
    .filter-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        border: none;
    }
    .quick-action-card {
        border-radius: 12px;
        border: none;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    .quick-action-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .quick-action-card .card-body {
        position: relative;
        z-index: 2;
    }
    .quick-action-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient);
    }
    .bg-primary-gradient::before { --gradient: linear-gradient(90deg, #0d6efd, #0b5ed7); }
    .bg-success-gradient::before { --gradient: linear-gradient(90deg, #198754, #146c43); }
    .bg-warning-gradient::before { --gradient: linear-gradient(90deg, #ffc107, #ffb300); }
    .bg-info-gradient::before { --gradient: linear-gradient(90deg, #0dcaf0, #0aa2c0); }
</style>
@endsection

@section('content')

{{-- Statistics Overview --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-primary-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:document-bold-duotone" class="avatar-title text-primary fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ $statistics['plans']['total_plans'] }}</h4>
                <p class="text-muted mb-3">Total Plans</p>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <small class="d-block">
                            <span class="fw-semibold text-success">{{ $statistics['plans']['active_plans'] }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Active</div>
                        </small>
                    </div>
                    <div class="col-4">
                        <small class="d-block">
                            <span class="fw-semibold text-info">{{ $statistics['plans']['tiered_plans'] ?? 0 }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Tiered</div>
                        </small>
                    </div>
                    <div class="col-4">
                        <small class="d-block">
                            <span class="fw-semibold text-secondary">{{ $statistics['plans']['simple_plans'] ?? 0 }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Simple</div>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-success-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:users-group-two-rounded-bold-duotone" class="avatar-title text-success fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($statistics['plans']['total_investors']) }}</h4>
                <p class="text-muted mb-3">Total Investors</p>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <small class="d-block">
                            <span class="fw-semibold text-success">{{ $statistics['investments']['active_investments'] ?? 0 }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Active</div>
                        </small>
                    </div>
                    <div class="col-6">
                        <small class="d-block">
                            <span class="fw-semibold text-primary">{{ $statistics['investments']['completed_investments'] ?? 0 }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Completed</div>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-warning-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:wallet-money-bold-duotone" class="avatar-title text-warning fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">${{ number_format($statistics['plans']['total_invested'] ?? 0, 2) }}</h4>
                <p class="text-muted mb-3">Total Invested</p>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <small class="d-block">
                            <span class="fw-semibold text-success">${{ number_format($statistics['investments']['total_invested_amount'] ?? 0, 2) }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Active</div>
                        </small>
                    </div>
                    <div class="col-6">
                        <small class="d-block">
                            <span class="fw-semibold text-info">${{ number_format($statistics['investments']['total_returns_paid'] ?? 0, 2) }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Returns</div>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-info-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:graph-up-bold-duotone" class="avatar-title text-info fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">${{ number_format($statistics['returns']['total_amount_pending'] ?? 0, 2) }}</h4>
                <p class="text-muted mb-3">Pending Returns</p>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <small class="d-block">
                            <span class="fw-semibold text-warning">{{ $statistics['returns']['due_today'] ?? 0 }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Due Today</div>
                        </small>
                    </div>
                    <div class="col-6">
                        <small class="d-block">
                            <span class="fw-semibold text-danger">{{ $statistics['returns']['overdue_returns'] ?? 0 }}</span>
                            <div class="text-muted" style="font-size: 0.7rem;">Overdue</div>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Action Bar --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row gap-3 align-items-md-center justify-content-between">
                    <div>
                        <h5 class="mb-1 fw-bold">Investment Plans Management</h5>
                        <p class="text-muted mb-0">Create and manage simple or tiered investment plans for your platform</p>
                    </div>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="{{ route('admin.investment.create') }}" class="btn btn-primary d-flex align-items-center">
                            <iconify-icon icon="solar:add-circle-bold-duotone" class="me-1"></iconify-icon>
                            Create Plan
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                                <iconify-icon icon="solar:download-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                                Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('admin.investment.export', ['format' => 'csv']) }}">
                                    <iconify-icon icon="solar:document-text-bold-duotone" class="me-2"></iconify-icon>All Plans (CSV)
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.investment.export', ['format' => 'csv', 'status' => 'active']) }}">
                                    <iconify-icon icon="solar:check-circle-bold-duotone" class="me-2"></iconify-icon>Active Only (CSV)
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.investment.export', ['format' => 'csv', 'plan_type' => 'tiered']) }}">
                                    <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-2"></iconify-icon>Tiered Plans (CSV)
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card filter-card">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Search Plans</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <iconify-icon icon="solar:magnifer-bold-duotone"></iconify-icon>
                            </span>
                            <input type="text" class="form-control" name="search" value="{{ $search }}" 
                                   placeholder="Search by name or description...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="paused" {{ $status === 'paused' ? 'selected' : '' }}>Paused</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Plan Type</label>
                        <select class="form-select" name="plan_type">
                            <option value="">All Types</option>
                            <option value="simple" {{ $plan_type === 'simple' ? 'selected' : '' }}>Simple</option>
                            <option value="tiered" {{ $plan_type === 'tiered' ? 'selected' : '' }}>Tiered</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Sort By</label>
                        <select class="form-select" name="sort_by">
                            <option value="sort_order" {{ $sort_by === 'sort_order' ? 'selected' : '' }}>Display Order</option>
                            <option value="name" {{ $sort_by === 'name' ? 'selected' : '' }}>Name</option>
                            <option value="created_at" {{ $sort_by === 'created_at' ? 'selected' : '' }}>Created Date</option>
                            <option value="total_invested" {{ $sort_by === 'total_invested' ? 'selected' : '' }}>Investment Amount</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid gap-2 d-sm-flex">
                            <button type="submit" class="btn btn-primary flex-fill d-flex align-items-center">
                                <iconify-icon icon="solar:magnifer-bold-duotone" class="me-1"></iconify-icon>
                                Filter
                            </button>
                            <a href="{{ route('admin.investment.index') }}" class="btn btn-outline-secondary d-flex align-items-center">
                                <iconify-icon icon="solar:refresh-bold-duotone" class="me-1"></iconify-icon>
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Investment Plans Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold">Investment Plans ({{ $investmentPlans->total() }})</h5>
                    @if($investmentPlans->count() > 1)
                    <button class="btn btn-sm btn-outline-secondary" id="toggleSortMode">
                        <iconify-icon icon="solar:sort-vertical-bold-duotone" class="me-1"></iconify-icon>
                        Sort
                    </button>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if($investmentPlans->count() > 0)
                {{-- Desktop View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 fw-semibold">Plan Details</th>
                                    <th class="border-0 fw-semibold">Type & Range</th>
                                    <th class="border-0 fw-semibold">Returns</th>
                                    <th class="border-0 fw-semibold">Performance</th>
                                    <th class="border-0 fw-semibold">Status</th>
                                    <th class="border-0 fw-semibold text-center" width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sortable-plans">
                                @foreach($investmentPlans as $plan)
                                <tr data-plan-id="{{ $plan->id }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="drag-handle me-3" style="display: none;">
                                                <iconify-icon icon="solar:sort-vertical-bold-duotone"></iconify-icon>
                                            </div>
                                            <div class="avatar-lg bg-{{ $plan->color_scheme }}-subtle rounded-3 me-3">
                                                <iconify-icon icon="solar:star-bold-duotone" class="avatar-title text-{{ $plan->color_scheme }} fs-4"></iconify-icon>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-semibold">{{ $plan->name }}</h6>
                                                @if($plan->badge)
                                                <span class="badge bg-{{ $plan->color_scheme }}-subtle text-{{ $plan->color_scheme }} mb-1">{{ $plan->badge }}</span>
                                                @endif
                                                @if($plan->description)
                                                <p class="text-muted mb-0 small">{{ Str::limit($plan->description, 60) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            @if($plan->is_tiered)
                                            <span class="plan-type-badge badge bg-info-subtle text-info">
                                                <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                                                Tiered
                                            </span>
                                            <span class="tier-indicator">{{ $plan->tiers->count() }} levels</span>
                                            @else
                                            <span class="plan-type-badge badge bg-secondary-subtle text-secondary">
                                                <iconify-icon icon="solar:star-bold-duotone" class="me-1"></iconify-icon>
                                                Simple
                                            </span>
                                            @endif
                                        </div>
                                        <div class="small text-muted">
                                            <div><strong>Min:</strong> {{ $plan->formatted_minimum }}</div>
                                            <div><strong>Max:</strong> {{ $plan->formatted_maximum }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="fw-semibold text-success mb-1">{{ $plan->formatted_interest_rate }}</div>
                                            <div class="text-muted">{{ ucfirst($plan->return_type) }} • {{ $plan->formatted_duration }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-primary"></iconify-icon>
                                                <span class="fw-semibold">{{ number_format($plan->user_investments_count) }}</span>
                                                <span class="text-muted">investors</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <iconify-icon icon="solar:wallet-money-bold-duotone" class="text-success"></iconify-icon>
                                                <span class="fw-semibold">{{ $plan->formatted_total_invested }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $plan->status_badge_class }} plan-status-badge" data-plan-id="{{ $plan->id }}">
                                            <iconify-icon icon="{{ $plan->status_icon }}" class="me-1"></iconify-icon>
                                            {{ ucfirst($plan->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons d-flex gap-1 justify-content-center">
                                            <a href="{{ route('admin.investment.show', $plan) }}" class="btn btn-sm btn-light" title="View Details">
                                                <iconify-icon icon="solar:eye-bold-duotone"></iconify-icon>
                                            </a>
                                            <a href="{{ route('admin.investment.edit', $plan) }}" class="btn btn-sm btn-light" title="Edit Plan">
                                                <iconify-icon icon="solar:pen-bold-duotone"></iconify-icon>
                                            </a>
                                            @if($plan->is_tiered)
                                            <button class="btn btn-sm btn-light" onclick="viewTiers({{ $plan->id }})" title="View Tiers">
                                                <iconify-icon icon="solar:layers-minimalistic-bold-duotone"></iconify-icon>
                                            </button>
                                            @endif
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <iconify-icon icon="solar:menu-dots-bold-duotone"></iconify-icon>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="togglePlanStatus({{ $plan->id }})">
                                                        <iconify-icon icon="solar:{{ $plan->isActive() ? 'pause' : 'play' }}-bold-duotone" class="me-2"></iconify-icon>
                                                        {{ $plan->isActive() ? 'Deactivate' : 'Activate' }}
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="#" onclick="deletePlan({{ $plan->id }})">
                                                        <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" class="me-2"></iconify-icon>Delete
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile View --}}
                <div class="d-lg-none p-3">
                    @foreach($investmentPlans as $plan)
                    <div class="card mb-3 border-start border-{{ $plan->color_scheme }} border-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1 fw-semibold">{{ $plan->name }}</h6>
                                    <div class="d-flex gap-1 mb-2">
                                        @if($plan->is_tiered)
                                        <span class="badge bg-info-subtle text-info">
                                            <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                                            Tiered ({{ $plan->tiers->count() }})
                                        </span>
                                        @else
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <iconify-icon icon="solar:star-bold-duotone" class="me-1"></iconify-icon>
                                            Simple
                                        </span>
                                        @endif
                                        @if($plan->badge)
                                        <span class="badge bg-{{ $plan->color_scheme }}-subtle text-{{ $plan->color_scheme }}">{{ $plan->badge }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="badge {{ $plan->status_badge_class }}">
                                    {{ ucfirst($plan->status) }}
                                </span>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="small">
                                        <div class="text-muted">Investment Range</div>
                                        <div class="fw-semibold">{{ $plan->formatted_minimum }} - {{ $plan->formatted_maximum }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="small">
                                        <div class="text-muted">Returns</div>
                                        <div class="fw-semibold text-success">{{ $plan->formatted_interest_rate }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="small">
                                        <div class="text-muted">Duration</div>
                                        <div class="fw-semibold">{{ $plan->formatted_duration }}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="small">
                                        <div class="text-muted">Investors</div>
                                        <div class="fw-semibold">{{ number_format($plan->user_investments_count) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('admin.investment.show', $plan) }}" class="btn btn-sm btn-primary">
                                    <iconify-icon icon="solar:eye-bold-duotone" class="me-1"></iconify-icon>
                                    View
                                </a>
                                <a href="{{ route('admin.investment.edit', $plan) }}" class="btn btn-sm btn-outline-secondary">
                                    <iconify-icon icon="solar:pen-bold-duotone" class="me-1"></iconify-icon>
                                    Edit
                                </a>
                                @if($plan->is_tiered)
                                <button class="btn btn-sm btn-outline-info" onclick="viewTiers({{ $plan->id }})">
                                    <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                                    Tiers
                                </button>
                                @endif
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        More
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" onclick="togglePlanStatus({{ $plan->id }})">
                                            {{ $plan->isActive() ? 'Deactivate' : 'Activate' }}
                                        </a></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="deletePlan({{ $plan->id }})">Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($investmentPlans->hasPages())
                <div class="px-3 pb-3">
                    {{ $investmentPlans->links() }}
                </div>
                @endif

                @else
                {{-- Empty State --}}
                <div class="text-center py-5">
                    <div class="avatar-xl bg-light rounded-circle mx-auto mb-4">
                        <iconify-icon icon="solar:document-add-bold-duotone" class="avatar-title text-muted fs-1"></iconify-icon>
                    </div>
                    <h5 class="text-muted fw-semibold">No Investment Plans Found</h5>
                    <p class="text-muted mb-4">Get started by creating your first investment plan. Choose between simple or tiered plans to suit your needs.</p>
                    <a href="{{ route('admin.investment.create') }}" class="btn btn-primary">
                        <iconify-icon icon="solar:add-circle-bold-duotone" class="me-1"></iconify-icon>
                        Create First Plan
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tiers Modal --}}
<div class="modal fade" id="tiersModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Plan Tiers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="tiersModalBody">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// Variables
let sortMode = false;
let sortable = null;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
    setupEventListeners();
});

// Initialize sortable functionality
function initializeSortable() {
    const tbody = document.getElementById('sortable-plans');
    if (tbody) {
        sortable = new Sortable(tbody, {
            disabled: true,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            animation: 150
        });
    }
}

// Setup event listeners
function setupEventListeners() {
    const toggleBtn = document.getElementById('toggleSortMode');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', toggleSortMode);
    }
}

// Toggle sort mode
function toggleSortMode() {
    sortMode = !sortMode;
    const dragHandles = document.querySelectorAll('.drag-handle');
    const toggleBtn = document.getElementById('toggleSortMode');
    
    if (sortMode) {
        dragHandles.forEach(handle => handle.style.display = 'block');
        toggleBtn.innerHTML = '<iconify-icon icon="solar:check-circle-bold-duotone" class="me-1"></iconify-icon>Done';
        toggleBtn.classList.remove('btn-outline-secondary');
        toggleBtn.classList.add('btn-success');
        if (sortable) sortable.option('disabled', false);
    } else {
        dragHandles.forEach(handle => handle.style.display = 'none');
        toggleBtn.innerHTML = '<iconify-icon icon="solar:sort-vertical-bold-duotone" class="me-1"></iconify-icon>Sort';
        toggleBtn.classList.remove('btn-success');
        toggleBtn.classList.add('btn-outline-secondary');
        if (sortable) sortable.option('disabled', true);
        updatePlanOrder();
    }
}

// Update plan order
function updatePlanOrder() {
    const tbody = document.getElementById('sortable-plans');
    if (!tbody) return;
    
    const planIds = Array.from(tbody.children).map(row => row.dataset.planId);
    
    fetch('{{ route("admin.investment.update-order") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ plan_ids: planIds })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Plan order updated successfully!', 'success');
        } else {
            showAlert('Error updating plan order.', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating plan order.', 'danger');
    });
}

// View tiers
function viewTiers(planId) {
    const modal = new bootstrap.Modal(document.getElementById('tiersModal'));
    const modalBody = document.getElementById('tiersModalBody');
    
    // Show loading
    modalBody.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch tier data
    fetch(`{{ route('admin.investment.index') }}/${planId}/tiers`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = '';
            data.tiers.forEach(tier => {
                html += `
                    <div class="card mb-3 border-start border-primary border-3">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-semibold">
                                    <span class="badge bg-primary me-2">Tier ${tier.tier_level}</span>
                                    ${tier.tier_name}
                                </h6>
                                <span class="badge ${tier.is_active ? 'bg-success' : 'bg-secondary'}">
                                    ${tier.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <strong>Investment Range:</strong><br>
                                    <span class="text-muted">${tier.investment_range}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Interest Rate:</strong><br>
                                    <span class="text-success fw-semibold">${tier.interest_rate}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Required User Level:</strong><br>
                                    <span class="badge bg-info-subtle text-info">Level ${tier.min_user_level}</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Investments:</strong><br>
                                    <span class="fw-semibold">${tier.total_investments} (${tier.formatted_amount})</span>
                                </div>
                                ${tier.tier_description ? `
                                <div class="col-12">
                                    <strong>Description:</strong><br>
                                    <span class="text-muted">${tier.tier_description}</span>
                                </div>
                                ` : ''}
                                ${tier.tier_features && tier.tier_features.length > 0 ? `
                                <div class="col-12">
                                    <strong>Features:</strong><br>
                                    <ul class="mb-0">
                                        ${tier.tier_features.map(feature => `<li>${feature}</li>`).join('')}
                                    </ul>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            modalBody.innerHTML = html;
        } else {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <iconify-icon icon="solar:danger-circle-bold-duotone" class="me-2"></iconify-icon>
                    ${data.message || 'Error loading tier data'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <iconify-icon icon="solar:danger-circle-bold-duotone" class="me-2"></iconify-icon>
                Error loading tier data
            </div>
        `;
    });
}

// Toggle plan status
function togglePlanStatus(planId) {
    if (!confirm('Are you sure you want to change this plan\'s status?')) return;
    
    fetch(`{{ route('admin.investment.index') }}/${planId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector(`.plan-status-badge[data-plan-id="${planId}"]`);
            if (badge) {
                badge.className = `badge ${data.badge_class} plan-status-badge`;
                badge.innerHTML = `<iconify-icon icon="${getStatusIcon(data.status)}" class="me-1"></iconify-icon>${capitalizeFirst(data.status)}`;
            }
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating plan status.', 'danger');
    });
}

// Delete plan
function deletePlan(planId) {
    if (!confirm('Are you sure you want to delete this investment plan? This action cannot be undone.')) return;
    
    fetch(`{{ route('admin.investment.index') }}/${planId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Remove the row from table
            const row = document.querySelector(`tr[data-plan-id="${planId}"]`);
            if (row) {
                row.remove();
            }
            // Reload page after short delay if no plans left
            setTimeout(() => {
                if (document.querySelectorAll('[data-plan-id]').length === 0) {
                    location.reload();
                }
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting plan.', 'danger');
    });
}

// Update user levels
function updateUserLevels() {
    if (!confirm('This will update all user levels based on their investment activity. Continue?')) return;
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating...';
    btn.disabled = true;
    
    fetch('{{ route("admin.investment.update-user-levels") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating user levels.', 'danger');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Utility functions
function getStatusIcon(status) {
    const icons = {
        'active': 'solar:check-circle-bold-duotone',
        'inactive': 'solar:close-circle-bold-duotone',
        'paused': 'solar:pause-circle-bold-duotone'
    };
    return icons[status] || 'solar:question-circle-bold-duotone';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alertDiv.innerHTML = `
        <iconify-icon icon="solar:${type === 'success' ? 'check-circle' : 'danger-circle'}-bold-duotone" class="me-2"></iconify-icon>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
}
</script>
@endsection