@extends('admin.layouts.vertical', ['title' => 'Leaderboard Details', 'subTitle' => $leaderboard->title])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="rounded-circle {{ $leaderboard->type === 'target' ? 'bg-info' : 'bg-primary' }} d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="text-white fs-4"></iconify-icon>
                                </div>
                            </div>
                            <div>
                                <h4 class="mb-1 text-dark">{{ $leaderboard->title }}</h4>
                                <p class="text-muted mb-0">
                                    <span class="badge {{ $leaderboard->type_badge_class }} me-2">
                                        <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="me-1"></iconify-icon>
                                        {{ $leaderboard->type_display }}
                                    </span>
                                    <span class="badge {{ $leaderboard->status_badge_class }} me-2">{{ ucfirst($leaderboard->status) }}</span>
                                    {{ $leaderboard->duration_display }}
                                </p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            @if($leaderboard->status !== 'completed')
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="calculatePositions({{ $leaderboard->id }})">
                                <iconify-icon icon="iconamoon:calculator-duotone" class="me-1"></iconify-icon>
                                Calculate Positions
                            </button>
                            @endif
                            
                            @if($leaderboard->canDistributePrizes())
                            <button type="button" class="btn btn-success btn-sm" onclick="distributePrizes({{ $leaderboard->id }})">
                                <iconify-icon icon="iconamoon:dollar-duotone" class="me-1"></iconify-icon>
                                Distribute Prizes
                            </button>
                            @endif
                            
                            <a href="{{ route('admin.leaderboards.edit', $leaderboard) }}" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                                Edit
                            </a>
                            
                            <a href="{{ route('admin.leaderboards.index') }}" class="btn btn-outline-secondary btn-sm">
                                <iconify-icon icon="iconamoon:arrow-left-duotone" class="me-1"></iconify-icon>
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Enhanced Statistics Row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:people-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Participants</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($leaderboardStats['total_participants']) }}</h5>
                    <small class="text-muted">competing</small>
                </div>
            </div>
        </div>
        
        @if($leaderboard->type === 'target')
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:check-circle-duotone" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Qualified</h6>
                    <h5 class="mb-0 fw-bold">{{ $leaderboard->getQualifiedCount() }}</h5>
                    <small class="text-muted">reached target</small>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:target-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Target</h6>
                    <h5 class="mb-0 fw-bold">{{ $leaderboard->target_referrals }}</h5>
                    <small class="text-muted">referrals needed</small>
                </div>
            </div>
        </div>
        @else
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:trophy-duotone" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Winners</h6>
                    <h5 class="mb-0 fw-bold">{{ $leaderboardStats['total_winners'] }}</h5>
                    <small class="text-muted">with prizes</small>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:medal-duotone" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Prize Positions</h6>
                    <h5 class="mb-0 fw-bold">{{ count($leaderboard->prize_structure ?? []) }}</h5>
                    <small class="text-muted">configured</small>
                </div>
            </div>
        </div>
        @endif
        
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:dollar-duotone" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Prizes</h6>
                    <h5 class="mb-0 fw-bold">${{ number_format($leaderboardStats['total_prize_amount'], 2) }}</h5>
                    <small class="text-muted">available</small>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    @if($leaderboard->isActive())
                        <iconify-icon icon="iconamoon:clock-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                        <h6 class="text-muted mb-1">Days Left</h6>
                        <h5 class="mb-0 fw-bold">{{ $leaderboardStats['days_remaining'] }}</h5>
                        <small class="text-muted">remaining</small>
                    @else
                        <iconify-icon icon="iconamoon:calendar-duotone" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                        <h6 class="text-muted mb-1">Duration</h6>
                        <h5 class="mb-0 fw-bold">{{ $leaderboardStats['duration_days'] }}</h5>
                        <small class="text-muted">days</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Content --}}
        <div class="col-lg-8">
            {{-- Leaderboard Positions --}}
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0 fw-bold">
                            <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->type === 'target' ? 'Participant Progress' : 'Leaderboard Rankings' }}
                        </h5>
                        <div class="d-flex gap-2 align-items-center">
                            @if($leaderboard->isActive())
                            <div class="progress" style="width: 100px; height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $leaderboardStats['progress'] }}%"></div>
                            </div>
                            <small class="text-muted">{{ $leaderboardStats['progress'] }}%</small>
                            @endif
                            @if($leaderboard->type === 'target')
                            <small class="text-muted">
                                {{ $leaderboard->getQualifiedCount() }}/{{ $leaderboardStats['total_participants'] }} qualified
                            </small>
                            @endif
                        </div>
                    </div>
                </div>

                @if($topPositions->count() > 0)
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        @if($leaderboard->type === 'competitive')
                                        <th scope="col" class="border-0">Position</th>
                                        @endif
                                        <th scope="col" class="border-0">User</th>
                                        <th scope="col" class="border-0">Referrals</th>
                                        @if($leaderboard->type === 'target')
                                        <th scope="col" class="border-0">Progress</th>
                                        @endif
                                        <th scope="col" class="border-0">Prize</th>
                                        <th scope="col" class="border-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topPositions as $position)
                                        <tr class="{{ $leaderboard->type === 'target' && $position->referral_count >= $leaderboard->target_referrals ? 'table-success' : '' }}">
                                            @if($leaderboard->type === 'competitive')
                                            <td class="py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-2">
                                                        <iconify-icon icon="{{ $position->getPositionIcon() }}" 
                                                                     class="fs-4 {{ $position->isTopThree() ? 'text-warning' : 'text-muted' }}"></iconify-icon>
                                                    </div>
                                                    <span class="badge {{ $position->position_badge_class }} fs-6">
                                                        {{ $position->position_display }}
                                                    </span>
                                                </div>
                                            </td>
                                            @endif
                                            <td class="py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                                            <iconify-icon icon="iconamoon:profile-duotone" class="text-white"></iconify-icon>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">{{ $position->user->full_name }}</h6>
                                                        <small class="text-muted">{{ $position->user->email }}</small>
                                                        @if($leaderboard->type === 'competitive')
                                                        <small class="d-block text-muted">Rank #{{ $position->position }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <div class="fw-bold fs-5">{{ number_format($position->referral_count) }}</div>
                                                <small class="text-muted">referrals</small>
                                            </td>
                                            @if($leaderboard->type === 'target')
                                            <td class="py-3">
                                                @php
                                                    $progress = min(100, ($position->referral_count / $leaderboard->target_referrals) * 100);
                                                    $qualified = $position->referral_count >= $leaderboard->target_referrals;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 80px; height: 6px;">
                                                        <div class="progress-bar {{ $qualified ? 'bg-success' : 'bg-primary' }}" style="width: {{ $progress }}%"></div>
                                                    </div>
                                                    <small class="text-muted">{{ round($progress) }}%</small>
                                                </div>
                                                @if($qualified)
                                                    <small class="text-success fw-semibold">✓ Target Reached</small>
                                                @else
                                                    <small class="text-muted">{{ $leaderboard->target_referrals - $position->referral_count }} more needed</small>
                                                @endif
                                            </td>
                                            @endif
                                            <td class="py-3">
                                                @if($position->prize_amount > 0)
                                                    <div class="fw-bold text-success">${{ number_format($position->prize_amount, 2) }}</div>
                                                    @if($leaderboard->type === 'target')
                                                        <small class="text-muted">Target achievement</small>
                                                    @endif
                                                @else
                                                    @if($leaderboard->type === 'target' && $position->referral_count >= $leaderboard->target_referrals)
                                                        <span class="text-warning">
                                                            <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                                            Max winners reached
                                                        </span>
                                                    @else
                                                        <span class="text-muted">No prize</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="py-3">
                                                <span class="badge {{ $position->prize_status_badge_class }}">
                                                    {{ $position->prize_status_text }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-4">
                            <iconify-icon icon="iconamoon:people-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Participants Yet</h6>
                            <p class="text-muted">No one has joined this {{ $leaderboard->type === 'target' ? 'target challenge' : 'leaderboard competition' }} yet.</p>
                            @if($leaderboard->status !== 'completed')
                            <button type="button" class="btn btn-primary btn-sm" onclick="calculatePositions({{ $leaderboard->id }})">
                                <iconify-icon icon="iconamoon:calculator-duotone" class="me-1"></iconify-icon>
                                Calculate Positions
                            </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Leaderboard Details --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                        Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">TYPE</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:{{ $leaderboard->type === 'target' ? 'target' : 'trophy' }}-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->type_display }}
                        </div>
                    </div>

                    @if($leaderboard->description)
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">DESCRIPTION</label>
                        <p class="mb-0">{{ $leaderboard->description }}</p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">CREATED BY</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->creator->full_name ?? 'System' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">REFERRAL TYPE</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:people-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->referral_type_display }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">MAX POSITIONS</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:hashtag-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->max_positions }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">VISIBILITY</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:{{ $leaderboard->show_to_users ? 'eye' : 'eye-off' }}-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->show_to_users ? 'Visible to users' : 'Hidden from users' }}
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold small text-muted">CREATED</label>
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:calendar-duotone" class="me-2"></iconify-icon>
                            {{ $leaderboard->created_at->format('M d, Y \a\t g:i A') }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Type-Specific Configuration --}}
            @if($leaderboard->type === 'target')
            {{-- Target Configuration --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:target-duotone" class="me-2"></iconify-icon>
                        Target Configuration
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="fw-bold fs-4 text-info">{{ $leaderboard->target_referrals }}</div>
                            <small class="text-muted">Target Referrals</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold fs-4 text-success">${{ number_format($leaderboard->target_prize_amount, 2) }}</div>
                            <small class="text-muted">Prize Amount</small>
                        </div>
                    </div>
                    
                    @if($leaderboard->max_winners)
                    <div class="alert alert-info d-flex align-items-center">
                        <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                        <div>
                            <strong>Winner Limit:</strong> Maximum of {{ $leaderboard->max_winners }} winners can receive the prize.
                        </div>
                    </div>
                    @else
                    <div class="alert alert-success d-flex align-items-center">
                        <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>
                        <div>
                            <strong>Unlimited Winners:</strong> All users who reach the target will receive the prize.
                        </div>
                    </div>
                    @endif
                    
                    @if($leaderboard->getParticipantsCount() > 0)
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="fw-bold text-success">{{ $leaderboard->getQualifiedCount() }}</div>
                            <small class="text-muted">Qualified</small>
                        </div>
                        <div class="col-6">
                            <div class="fw-bold">{{ round(($leaderboard->getQualifiedCount() / $leaderboard->getParticipantsCount()) * 100) }}%</div>
                            <small class="text-muted">Success Rate</small>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @else
            {{-- Competitive Prize Structure --}}
            @if($leaderboard->prize_structure && count($leaderboard->prize_structure) > 0)
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:dollar-duotone" class="me-2"></iconify-icon>
                        Prize Structure
                    </h6>
                </div>
                <div class="card-body">
                    @foreach($leaderboard->prize_structure as $index => $prize)
                        @if(isset($prize['amount']) && $prize['amount'] > 0)
                        <div class="d-flex align-items-center justify-content-between mb-2 {{ !$loop->last ? 'border-bottom pb-2' : '' }}">
                            <div class="d-flex align-items-center">
                                <iconify-icon icon="iconamoon:{{ $index === 0 ? 'trophy' : 'medal' }}-duotone" 
                                             class="me-2 {{ $index < 3 ? 'text-warning' : 'text-muted' }}"></iconify-icon>
                                <span class="fw-semibold">
                                    @if(isset($prize['position']))
                                        Position {{ $prize['position'] }}
                                    @elseif(isset($prize['from_position']) && isset($prize['to_position']))
                                        Positions {{ $prize['from_position'] }}-{{ $prize['to_position'] }}
                                    @endif
                                </span>
                            </div>
                            <span class="fw-bold text-success">${{ number_format($prize['amount'], 2) }}</span>
                        </div>
                        @endif
                    @endforeach
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            {{ count($leaderboard->prize_structure) }} prize positions configured
                        </small>
                    </div>
                </div>
            </div>
            @endif
            @endif

            {{-- Prize Distribution Status --}}
            @if($leaderboard->status === 'completed')
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>
                        Prize Distribution
                    </h6>
                </div>
                <div class="card-body">
                    @if($leaderboard->prizes_distributed)
                        <div class="alert alert-success d-flex align-items-center mb-3">
                            <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>
                            <div>
                                <strong>Prizes Distributed!</strong><br>
                                <small class="text-muted">
                                    Distributed on {{ $leaderboard->prizes_distributed_at->format('M d, Y \a\t g:i A') }}
                                    @if($leaderboard->prizeDistributor)
                                        by {{ $leaderboard->prizeDistributor->full_name }}
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div class="row text-center">
                            <div class="col-6">
                                <div class="fw-bold text-success">${{ number_format($leaderboardStats['awarded_prize_amount'], 2) }}</div>
                                <small class="text-muted">Awarded</small>
                            </div>
                            <div class="col-6">
                                <div class="fw-bold">{{ $leaderboardStats['total_winners'] }}</div>
                                <small class="text-muted">Winners</small>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-center mb-3">
                            <iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>
                            <div>
                                <strong>Prizes Not Distributed</strong><br>
                                <small class="text-muted">
                                    @if($leaderboard->type === 'target')
                                        {{ $leaderboard->getQualifiedCount() }} qualified winners are waiting for their prizes
                                    @else
                                        {{ $leaderboardStats['total_winners'] }} winners are waiting for their prizes
                                    @endif
                                </small>
                            </div>
                        </div>

                        @if($leaderboard->canDistributePrizes())
                        <div class="d-grid">
                            <button type="button" class="btn btn-success" onclick="distributePrizes({{ $leaderboard->id }})">
                                <iconify-icon icon="iconamoon:dollar-duotone" class="me-1"></iconify-icon>
                                Distribute Prizes (${{ number_format($leaderboardStats['pending_prize_amount'], 2) }})
                            </button>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
            @endif
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
    const leaderboardType = '{{ $leaderboard->type }}';
    let confirmMessage = 'This will distribute prizes to all winners and add amounts to their account balances. This action cannot be undone. Continue?';
    
    if (leaderboardType === 'target') {
        const qualifiedCount = {{ $leaderboard->getQualifiedCount() }};
        confirmMessage = `This will distribute prizes to ${qualifiedCount} qualified winners (users who reached the target). This action cannot be undone. Continue?`;
    }
    
    if (!confirm(confirmMessage)) return;
    
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

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh positions if leaderboard is active
    @if($leaderboard->isActive())
    setInterval(() => {
        // Could add auto-refresh logic here if needed
        // For now, just visual feedback that the leaderboard is live
    }, 30000); // 30 seconds
    @endif

    // Add visual indicators for target-based qualified users
    @if($leaderboard->type === 'target')
    const qualifiedRows = document.querySelectorAll('.table-success');
    qualifiedRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(25, 135, 84, 0.2)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = 'rgba(25, 135, 84, 0.1)';
        });
    });
    @endif
});
</script>

<style>
/* Custom styles for leaderboard show page */
.card {
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
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

.table tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Target-specific styling */
.table-success {
    background-color: rgba(25, 135, 84, 0.1) !important;
}

.table-success td {
    border-color: rgba(25, 135, 84, 0.2);
}

/* Badge styles */
.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
    display: inline-flex;
    align-items: center;
}

/* Progress bar */
.progress {
    border-radius: 4px;
}

.progress-bar {
    border-radius: 4px;
    transition: width 0.6s ease;
}

/* Alert styles */
.alert {
    border-radius: 0.5rem;
    border: none;
}

/* Statistics cards hover effect */
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Target achievement styling */
.target-achieved {
    background: linear-gradient(45deg, #d4edda, #c3e6cb);
}

.target-progress {
    background: linear-gradient(45deg, #d1ecf1, #bee5eb);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    /* Stack statistics cards on mobile */
    .col-6.col-lg-2 {
        margin-bottom: 0.75rem;
    }
}

/* Animation for prize distribution */
@keyframes prizeDistributed {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.prize-distributed {
    animation: prizeDistributed 0.6s ease-in-out;
}

/* Target vs Competitive differentiation */
.target-leaderboard .card-header {
    background: linear-gradient(45deg, #0dcaf0, #31d2f2);
}

.competitive-leaderboard .card-header {
    background: linear-gradient(45deg, #0d6efd, #6610f2);
}

/* Enhanced progress indicators */
.progress-bar.bg-success {
    background: linear-gradient(45deg, #198754, #20c997) !important;
}

.progress-bar.bg-primary {
    background: linear-gradient(45deg, #0d6efd, #6f42c1) !important;
}

/* Special styling for qualified users in target leaderboards */
@if($leaderboard->type === 'target')
.qualified-indicator {
    position: relative;
}

.qualified-indicator::before {
    content: '✓';
    position: absolute;
    top: -5px;
    right: -5px;
    background: #198754;
    color: white;
    border-radius: 50%;
    width: 16px;
    height: 16px;
    font-size: 10px;
    text-align: center;
    line-height: 16px;
}
@endif
</style>
@endsection