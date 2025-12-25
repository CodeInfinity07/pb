@extends('admin.layouts.vertical', ['title' => 'Leaderboards', 'subTitle' => 'Manage Referral Leaderboards'])

@section('content')
<div class="container-fluid">
    {{-- Active Leaderboards Alert --}}
    @if(isset($stats['active_leaderboards']) && $stats['active_leaderboards'] > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center">
                    <iconify-icon icon="iconamoon:trophy-duotone" class="fs-5 me-3"></iconify-icon>
                    <div class="flex-grow-1">
                        <strong>Active Leaderboards!</strong> 
                        You have {{ $stats['active_leaderboards'] }} leaderboards currently running.
                        <a href="#" onclick="showActiveLeaderboards()" class="alert-link ms-2">View Details</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Referral Leaderboards</h4>
                            <p class="text-muted mb-0">Create and manage both competitive rankings and target-based achievement leaderboards</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="autoCompleteExpired()">
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                Auto-Complete Expired
                            </button>
                            <a href="{{ route('admin.leaderboards.create') }}" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                New Leaderboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:trophy-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_leaderboards']) }}</h5>
                    <small class="text-muted">All leaderboards</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:play-duotone" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active</h6>
                    <h5 class="mb-0 fw-bold">{{ $stats['active_leaderboards'] }}</h5>
                    <small class="text-muted">Currently running</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:check-circle-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Completed</h6>
                    <h5 class="mb-0 fw-bold">{{ $stats['completed_leaderboards'] }}</h5>
                    <small class="text-muted">Finished</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:trophy-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Competitive</h6>
                    <h5 class="mb-0 fw-bold">{{ $stats['competitive_leaderboards'] ?? 0 }}</h5>
                    <small class="text-muted">Ranking based</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:target-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Target</h6>
                    <h5 class="mb-0 fw-bold">{{ $stats['target_leaderboards'] ?? 0 }}</h5>
                    <small class="text-muted">Goal based</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:dollar-duotone" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Prizes</h6>
                    <h5 class="mb-0 fw-bold">${{ number_format($stats['total_prize_amount'] ?? 0, 2) }}</h5>
                    <small class="text-muted">Distributed</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Filters --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Search Leaderboards</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </span>
                                <input type="text" class="form-control" name="search" value="{{ $search ?? '' }}" 
                                       placeholder="Search by title or description...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="completed" {{ ($status ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">Type</label>
                            <select class="form-select" name="type">
                                <option value="">All Types</option>
                                <option value="competitive" {{ (request('type') ?? '') === 'competitive' ? 'selected' : '' }}>Competitive</option>
                                <option value="target" {{ (request('type') ?? '') === 'target' ? 'selected' : '' }}>Target</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid gap-2 d-sm-flex">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <iconify-icon icon="iconamoon:search-duotone" class="me-1"></iconify-icon>
                                    Filter
                                </button>
                                <a href="{{ route('admin.leaderboards.index') }}" class="btn btn-outline-secondary">
                                    <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Leaderboards Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">Leaderboards ({{ $leaderboards->total() ?? 0 }})</h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshStats()">
                            <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                            Refresh
                        </button>
                    </div>
                </div>

                @if(($leaderboards->count() ?? 0) > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="leaderboards-table-container">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="border-0">Leaderboard</th>
                                            <th scope="col" class="border-0">Type</th>
                                            <th scope="col" class="border-0">Duration</th>
                                            <th scope="col" class="border-0">Status</th>
                                            <th scope="col" class="border-0">Participants</th>
                                            <th scope="col" class="border-0">Prizes</th>
                                            <th scope="col" class="border-0 text-center" style="width: 120px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($leaderboards as $leaderboard)
                                            <tr class="leaderboard-row">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="rounded-circle {{ $leaderboard->type === 'target' ? 'bg-info' : 'bg-primary' }} d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="text-white fs-5"></iconify-icon>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $leaderboard->title }}</h6>
                                                            @if($leaderboard->description)
                                                            <small class="text-muted">{{ Str::limit($leaderboard->description, 60) }}</small>
                                                            @endif
                                                            <div class="small text-muted mt-1">
                                                                <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                                                {{ $leaderboard->creator->full_name ?? 'System' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $leaderboard->type_badge_class }}">
                                                        <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="me-1"></iconify-icon>
                                                        {{ $leaderboard->type_display }}
                                                    </span>
                                                    @if($leaderboard->type === 'target')
                                                        <div class="small text-muted mt-1">
                                                            Target: {{ $leaderboard->target_referrals }} referrals
                                                        </div>
                                                        <div class="small text-info">
                                                            Prize: ${{ number_format($leaderboard->target_prize_amount, 2) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div class="fw-semibold">{{ $leaderboard->duration_display }}</div>
                                                        <div class="text-muted">{{ $leaderboard->referral_type_display }}</div>
                                                        @if($leaderboard->isActive())
                                                        <div class="text-primary">{{ $leaderboard->days_remaining }} days left</div>
                                                        @endif
                                                        @if($leaderboard->isUpcoming())
                                                        <div class="text-info">Starts {{ $leaderboard->start_date->diffForHumans() }}</div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $leaderboard->status_badge_class }} leaderboard-status-badge" data-leaderboard-id="{{ $leaderboard->id }}">
                                                        <iconify-icon icon="iconamoon:{{ $leaderboard->status === 'active' ? 'play' : ($leaderboard->status === 'completed' ? 'check-circle' : 'pause') }}-duotone" class="me-1"></iconify-icon>
                                                        {{ ucfirst($leaderboard->status) }}
                                                    </span>
                                                    @if($leaderboard->hasEnded() && $leaderboard->status !== 'completed')
                                                    <div class="small text-warning mt-1">
                                                        <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                                        Expired
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-bold">{{ number_format($leaderboard->getParticipantsCount()) }}</div>
                                                    <div class="text-muted small">participants</div>
                                                    @if($leaderboard->type === 'target' && $leaderboard->getParticipantsCount() > 0)
                                                        <div class="small text-success">
                                                            {{ $leaderboard->getQualifiedCount() }} qualified
                                                        </div>
                                                    @endif
                                                    @if($leaderboard->getParticipantsCount() > 0)
                                                    <div class="progress mt-1" style="height: 3px;">
                                                        <div class="progress-bar bg-primary" style="width: {{ min(100, ($leaderboard->getParticipantsCount() / $leaderboard->max_positions) * 100) }}%"></div>
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="py-3">
                                                    <div class="fw-bold">${{ number_format($leaderboard->total_prize_amount, 2) }}</div>
                                                    @if($leaderboard->type === 'competitive')
                                                        <div class="text-muted small">{{ $leaderboard->getWinnersCount() }} winners</div>
                                                    @else
                                                        <div class="text-muted small">
                                                            @if($leaderboard->max_winners)
                                                                max {{ $leaderboard->max_winners }} winners
                                                            @else
                                                                unlimited winners
                                                            @endif
                                                        </div>
                                                    @endif
                                                    @if($leaderboard->prizes_distributed)
                                                    <div class="small text-success mt-1">
                                                        <iconify-icon icon="iconamoon:check-circle-duotone" class="me-1"></iconify-icon>
                                                        Distributed
                                                    </div>
                                                    @elseif($leaderboard->canDistributePrizes())
                                                    <div class="small text-warning mt-1">
                                                        <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                                        Ready to distribute
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.leaderboards.show', $leaderboard) }}">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.leaderboards.edit', $leaderboard) }}">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Leaderboard
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            @if($leaderboard->status !== 'completed')
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="toggleLeaderboardStatus({{ $leaderboard->id }})">
                                                                    <iconify-icon icon="iconamoon:{{ $leaderboard->status === 'active' ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                                                                    {{ $leaderboard->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                                </a>
                                                            </li>
                                                            @endif
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="calculatePositions({{ $leaderboard->id }})">
                                                                    <iconify-icon icon="iconamoon:calculator-duotone" class="me-2"></iconify-icon>Calculate Positions
                                                                </a>
                                                            </li>
                                                            @if($leaderboard->hasEnded() && $leaderboard->status !== 'completed')
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="completeLeaderboard({{ $leaderboard->id }})">
                                                                    <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>Mark Complete
                                                                </a>
                                                            </li>
                                                            @endif
                                                            @if($leaderboard->canDistributePrizes())
                                                            <li>
                                                                <a class="dropdown-item text-success" href="javascript:void(0)" onclick="distributePrizes({{ $leaderboard->id }})">
                                                                    <iconify-icon icon="iconamoon:dollar-duotone" class="me-2"></iconify-icon>Distribute Prizes
                                                                </a>
                                                            </li>
                                                            @endif
                                                            <li><hr class="dropdown-divider"></li>
                                                            @if(!$leaderboard->prizes_distributed)
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteLeaderboard({{ $leaderboard->id }})">
                                                                    <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
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
                                @foreach($leaderboards as $leaderboard)
                                    <div class="col-12">
                                        <div class="card leaderboard-mobile-card border">
                                            <div class="card-body p-3">
                                                {{-- Header Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="rounded-circle {{ $leaderboard->type === 'target' ? 'bg-info' : 'bg-primary' }} d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                            <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="text-white"></iconify-icon>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $leaderboard->title }}</h6>
                                                            <small class="text-muted">{{ $leaderboard->creator->full_name ?? 'System' }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="{{ route('admin.leaderboards.show', $leaderboard) }}">
                                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="{{ route('admin.leaderboards.edit', $leaderboard) }}">
                                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit
                                                            </a></li>
                                                            @if($leaderboard->canDistributePrizes())
                                                            <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="distributePrizes({{ $leaderboard->id }})">
                                                                <iconify-icon icon="iconamoon:dollar-duotone" class="me-2"></iconify-icon>Distribute Prizes
                                                            </a></li>
                                                            @endif
                                                            @if(!$leaderboard->prizes_distributed)
                                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteLeaderboard({{ $leaderboard->id }})">
                                                                <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
                                                            </a></li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </div>

                                                {{-- Type and Status Row --}}
                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge {{ $leaderboard->type_badge_class }}">
                                                            <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="me-1"></iconify-icon>
                                                            {{ $leaderboard->type_display }}
                                                        </span>
                                                        <span class="badge {{ $leaderboard->status_badge_class }}">
                                                            {{ ucfirst($leaderboard->status) }}
                                                        </span>
                                                        <span class="badge bg-light text-dark">
                                                            {{ $leaderboard->getParticipantsCount() }} participants
                                                        </span>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-semibold">${{ number_format($leaderboard->total_prize_amount, 2) }}</div>
                                                        <small class="text-muted">total prizes</small>
                                                    </div>
                                                </div>

                                                {{-- Details Row --}}
                                                <div class="mb-2">
                                                    @if($leaderboard->description)
                                                    <p class="text-muted mb-2 small">{{ Str::limit($leaderboard->description, 100) }}</p>
                                                    @endif
                                                    <div class="small text-muted mb-2">
                                                        <strong>Duration:</strong> {{ $leaderboard->duration_display }}
                                                    </div>
                                                    <div class="small mb-2">
                                                        <strong>Type:</strong> {{ $leaderboard->referral_type_display }}
                                                    </div>
                                                    @if($leaderboard->type === 'target')
                                                    <div class="small text-info">
                                                        <strong>Target:</strong> {{ $leaderboard->target_referrals }} referrals = ${{ number_format($leaderboard->target_prize_amount, 2) }}
                                                        @if($leaderboard->getParticipantsCount() > 0)
                                                        <span class="ms-2 text-success">({{ $leaderboard->getQualifiedCount() }} qualified)</span>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    @if($leaderboards->hasPages())
                        <div class="card-footer border-top bg-light">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="text-muted small">
                                    Showing <span class="fw-semibold">{{ $leaderboards->firstItem() }}</span> to <span class="fw-semibold">{{ $leaderboards->lastItem() }}</span> of <span class="fw-semibold">{{ $leaderboards->total() }}</span> leaderboards
                                </div>
                                <div>
                                    {{ $leaderboards->appends(request()->query())->links() }}
                                </div>
                            </div>
                        </div>
                    @endif

                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:trophy-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Leaderboards Found</h6>
                            <p class="text-muted">No leaderboards match your current filter criteria.</p>
                            @if(request('status') || request('search') || request('type'))
                                <a href="{{ route('admin.leaderboards.index') }}" class="btn btn-primary">Clear Filters</a>
                            @else
                                <a href="{{ route('admin.leaderboards.create') }}" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                    Create Leaderboard
                                </a>
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

function toggleLeaderboardStatus(leaderboardId) {
    if (!confirm('Are you sure you want to change this leaderboard\'s status?')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    fetch(`/admin/leaderboards/${leaderboardId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector(`.leaderboard-status-badge[data-leaderboard-id="${leaderboardId}"]`);
            if (badge) {
                badge.className = `badge ${data.badge_class || 'bg-secondary'} leaderboard-status-badge`;
                badge.innerHTML = `<iconify-icon icon="iconamoon:${getStatusIcon(data.status)}" class="me-1"></iconify-icon>${capitalizeFirst(data.status)}`;
            }
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating leaderboard status.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function calculatePositions(leaderboardId) {
    if (!confirm('This will recalculate all positions based on current referral data. Continue?')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    showAlert('Calculating positions...', 'info');
    
    fetch(`/admin/leaderboards/${leaderboardId}/calculate-positions`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`${data.message} (${data.participants} participants)`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Failed to calculate positions', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error calculating positions.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function distributePrizes(leaderboardId) {
    if (!confirm('This will distribute prizes to all winners and add amounts to their account balances. This action cannot be undone. Continue?')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    showAlert('Distributing prizes...', 'info');
    
    fetch(`/admin/leaderboards/${leaderboardId}/distribute-prizes`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`${data.message} Total: $${data.total_amount}`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Failed to distribute prizes', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error distributing prizes.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function completeLeaderboard(leaderboardId) {
    if (!confirm('This will mark the leaderboard as completed and calculate final positions. Continue?')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    fetch(`/admin/leaderboards/${leaderboardId}/complete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to complete leaderboard', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error completing leaderboard.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function deleteLeaderboard(leaderboardId) {
    if (!confirm('Are you sure you want to delete this leaderboard? This action cannot be undone.')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    fetch(`/admin/leaderboards/${leaderboardId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to delete leaderboard', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting leaderboard.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function autoCompleteExpired() {
    if (!confirm('This will automatically complete all expired leaderboards. Continue?')) return;
    
    if (isSubmitting) return;
    isSubmitting = true;
    
    showAlert('Processing expired leaderboards...', 'info');
    
    fetch('/admin/leaderboards/auto-complete-expired', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(data.message || 'Failed to auto-complete expired leaderboards', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error processing expired leaderboards.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
    });
}

function refreshStats() {
    showAlert('Refreshing statistics...', 'info');
    setTimeout(() => location.reload(), 500);
}

function showActiveLeaderboards() {
    window.location.href = '{{ route("admin.leaderboards.index") }}?status=active';
}

function getStatusIcon(status) {
    const icons = {
        'active': 'play-duotone',
        'inactive': 'pause-duotone',
        'completed': 'check-circle-duotone'
    };
    return icons[status] || 'question-circle-duotone';
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
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

/* Table Container */
.leaderboards-table-container {
    position: relative;
    overflow: visible;
}

/* Table Styles */
.table {
    margin-bottom: 0;
    position: relative;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 1rem 0.75rem;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.leaderboard-row {
    transition: background-color 0.15s ease-in-out;
}

.leaderboard-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Mobile Card Styles */
.leaderboard-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.leaderboard-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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

/* Progress Bar */
.progress {
    border-radius: 4px;
    height: 3px;
}

.progress-bar {
    border-radius: 4px;
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

/* Dropdown Styles */
.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-item {
    display: flex;
    align-items: center;
    transition: all 0.15s ease-in-out;
}

.dropdown-item:hover {
    transform: translateX(2px);
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

/* Alert Container */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

/* Type-specific styling */
.bg-info-light {
    background-color: rgba(13, 202, 240, 0.1);
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
}

@media (max-width: 767.98px) {
    .leaderboard-mobile-card .card-body {
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
    
    .dropdown-menu {
        min-width: 8rem;
        font-size: 0.8rem;
    }
    
    .dropdown-item {
        padding: 0.4rem 0.8rem;
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
</style>
@endsection