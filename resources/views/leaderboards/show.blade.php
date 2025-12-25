@extends('layouts.vertical', ['title' => $leaderboard->title, 'subTitle' => 'Competition Details'])

@section('content')
<div class="container-fluid">

    <!-- Back Navigation -->
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('user.leaderboards.index') }}" class="text-decoration-none">
                            <iconify-icon icon="iconamoon:arrow-left-duotone" class="me-1"></iconify-icon>
                            Leaderboards
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $leaderboard->title }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Competition Status Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-{{ $leaderboard->isActive() ? 'success' : ($leaderboard->isCompleted() ? 'info' : 'secondary') }} shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="avatar-lg bg-{{ $leaderboard->type === 'target' ? 'info' : 'primary' }}-subtle rounded-circle d-flex align-items-center justify-content-center">
                                        <iconify-icon icon="{{ $leaderboard->type === 'target' ? 'iconamoon:target-duotone' : 'iconamoon:trophy-duotone' }}" class="fs-32 text-{{ $leaderboard->type === 'target' ? 'info' : 'primary' }}"></iconify-icon>
                                    </div>
                                </div>
                                <div>
                                    <h2 class="mb-2">{{ $leaderboard->title }}</h2>
                                    @if($leaderboard->description)
                                        <p class="text-muted mb-2">{{ $leaderboard->description }}</p>
                                    @endif
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <span class="badge bg-{{ $leaderboard->isActive() ? 'success' : ($leaderboard->isCompleted() ? 'info' : 'secondary') }} fs-6">
                                            {{ ucfirst($leaderboard->status) }}
                                        </span>
                                        <span class="badge bg-{{ $leaderboard->type === 'target' ? 'info' : 'primary' }}-subtle text-{{ $leaderboard->type === 'target' ? 'info' : 'primary' }}">
                                            {{ $leaderboard->type_display }}
                                        </span>
                                        <span class="text-muted">{{ $leaderboard->referral_type_display }}</span>
                                        @if($leaderboard->isActive())
                                            <span class="badge bg-success-subtle text-success">
                                                <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                                Live Competition
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Stats Row -->
                            <div class="row g-4">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-center p-3 bg-success-subtle rounded-3">
                                        <div class="fw-semibold fs-4 text-success" id="total-prize-display">
                                            ${{ number_format($stats['total_prize_amount']) }}
                                        </div>
                                        <small class="text-muted fw-medium">Total Prize Pool</small>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-center p-3 bg-info-subtle rounded-3">
                                        <div class="fw-semibold fs-4 text-info" id="participant-count">
                                            {{ $stats['total_participants'] }}
                                        </div>
                                        <small class="text-muted fw-medium">Participants</small>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-center p-3 bg-warning-subtle rounded-3">
                                        <div class="fw-semibold fs-4 text-warning" id="winners-count">
                                            {{ $stats['total_winners'] }}
                                        </div>
                                        <small class="text-muted fw-medium">{{ $leaderboard->type === 'target' ? 'Qualified' : 'Winners' }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="text-center p-3 bg-primary-subtle rounded-3">
                                        <div class="fw-semibold fs-4 text-primary">
                                            {{ $stats['duration_days'] }}
                                        </div>
                                        <small class="text-muted fw-medium">Duration (Days)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                            @if($leaderboard->isActive())
                                <div class="mb-3">
                                    <h6 class="text-muted mb-1">Time Remaining</h6>
                                    <h2 class="mb-0 fw-bold text-warning" id="days-remaining">{{ $stats['days_remaining'] }} days</h2>
                                    <small class="text-muted">Ends {{ $leaderboard->end_date->format('M d, Y H:i') }}</small>
                                </div>
                                <div class="progress mb-2" style="height: 12px;">
                                    <div class="progress-bar bg-gradient-success" 
                                         role="progressbar" 
                                         style="width: {{ $stats['progress'] }}%" 
                                         id="progress-bar"></div>
                                </div>
                                <small class="text-muted fw-medium">
                                    <span id="progress-text">{{ $stats['progress'] }}%</span> complete
                                </small>
                            @else
                                <div class="text-center">
                                    <h6 class="text-muted mb-1">Competition</h6>
                                    <h5 class="mb-0">{{ $leaderboard->isCompleted() ? 'Completed' : 'Inactive' }}</h5>
                                    <small class="text-muted">{{ $leaderboard->end_date->format('M d, Y') }}</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Leaderboard Content -->
        <div class="col-lg-8">
            
            @if($leaderboard->type === 'target')
            <!-- Target-Based Competition Info -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-info-subtle border-0">
                    <h5 class="card-title mb-0 text-info">
                        <iconify-icon icon="iconamoon:target-duotone" class="me-2"></iconify-icon>
                        Target Achievement Challenge
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="avatar-md bg-info rounded-circle d-flex align-items-center justify-content-center">
                                        <iconify-icon icon="iconamoon:profile-duotone" class="fs-24 text-white"></iconify-icon>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-info">{{ $leaderboard->target_referrals }}</h4>
                                    <p class="mb-0 text-muted">Referrals Required</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="d-flex align-items-center justify-content-md-end">
                                <div class="me-3">
                                    <h4 class="mb-1 text-success">${{ number_format($leaderboard->target_prize_amount) }}</h4>
                                    <p class="mb-0 text-muted">Prize per Qualifier</p>
                                </div>
                                <div class="avatar-md bg-success rounded-circle d-flex align-items-center justify-content-center">
                                    <iconify-icon icon="iconamoon:dollar-duotone" class="fs-24 text-white"></iconify-icon>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($leaderboard->max_winners)
                    <div class="alert alert-warning border-0 mt-3 mb-0">
                        <iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>
                        <strong>Limited Winners:</strong> Maximum {{ number_format($leaderboard->max_winners) }} winners will receive prizes.
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Top Rankings Display -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:trophy-duotone" class="me-2 text-warning"></iconify-icon>
                        {{ $leaderboard->type === 'competitive' ? 'Top Rankings' : 'Participant Rankings' }}
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        @if($leaderboard->isActive())
                            <small class="text-success">
                                <iconify-icon icon="iconamoon:refresh" class="pulse"></iconify-icon>
                                Live Rankings
                            </small>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshLeaderboard()">
                                <iconify-icon icon="iconamoon:refresh" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                        @endif
                        <small class="text-muted">
                            Updated: <span id="last-updated">{{ now()->format('H:i') }}</span>
                        </small>
                    </div>
                </div>
                <div class="card-body">
                    @if($topPositions->count() > 0)
                        
                        <!-- Top 3 Podium (for competitive leaderboards) -->
                        @if($leaderboard->type === 'competitive' && $topPositions->count() >= 3)
                        <div class="podium-section mb-4 p-4 bg-gradient-light rounded-3">
                            <h6 class="text-center mb-4 text-muted">🏆 Top 3 Champions 🏆</h6>
                            <div class="podium-container">
                                <!-- 2nd Place -->
                                <div class="podium-position podium-second">
                                    <div class="podium-content">
                                        <div class="podium-avatar bg-secondary">
                                            <iconify-icon icon="iconamoon:medal-duotone" class="text-white"></iconify-icon>
                                            <span class="podium-rank">2</span>
                                        </div>
                                        <div class="podium-info">
                                            <h6 class="podium-name">{{ $topPositions[1]->user->first_name }} {{ Str::limit($topPositions[1]->user->last_name, 8) }}</h6>
                                            <div class="podium-score text-secondary fw-bold">{{ $topPositions[1]->referral_count }}</div>
                                            <small class="podium-prize text-success fw-bold">${{ number_format($topPositions[1]->prize_amount) }}</small>
                                        </div>
                                    </div>
                                    <div class="podium-base podium-silver"></div>
                                </div>
                                
                                <!-- 1st Place -->
                                <div class="podium-position podium-first">
                                    <div class="podium-content winner-glow">
                                        <div class="podium-avatar bg-warning winner">
                                            <iconify-icon icon="iconamoon:crown-duotone" class="text-white"></iconify-icon>
                                            <span class="podium-rank">1</span>
                                        </div>
                                        <div class="podium-info">
                                            <h6 class="podium-name fw-bold">{{ $topPositions[0]->user->first_name }} {{ Str::limit($topPositions[0]->user->last_name, 8) }}</h6>
                                            <div class="podium-score text-warning fw-bold fs-5">{{ $topPositions[0]->referral_count }}</div>
                                            <small class="podium-prize text-success fw-bold fs-6">${{ number_format($topPositions[0]->prize_amount) }}</small>
                                        </div>
                                    </div>
                                    <div class="podium-base podium-gold"></div>
                                </div>
                                
                                <!-- 3rd Place -->
                                <div class="podium-position podium-third">
                                    <div class="podium-content">
                                        <div class="podium-avatar bg-info">
                                            <iconify-icon icon="iconamoon:medal-duotone" class="text-white"></iconify-icon>
                                            <span class="podium-rank">3</span>
                                        </div>
                                        <div class="podium-info">
                                            <h6 class="podium-name">{{ $topPositions[2]->user->first_name }} {{ Str::limit($topPositions[2]->user->last_name, 8) }}</h6>
                                            <div class="podium-score text-info fw-bold">{{ $topPositions[2]->referral_count }}</div>
                                            <small class="podium-prize text-success fw-bold">${{ number_format($topPositions[2]->prize_amount) }}</small>
                                        </div>
                                    </div>
                                    <div class="podium-base podium-bronze"></div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Full Rankings Table -->
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 leaderboard-table">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="fw-semibold">Rank</th>
                                        <th class="fw-semibold">Participant</th>
                                        <th class="fw-semibold">Referrals</th>
                                        @if($leaderboard->type === 'target')
                                            <th class="fw-semibold">Progress</th>
                                            <th class="fw-semibold">Status</th>
                                        @else
                                            <th class="fw-semibold">Prize</th>
                                            <th class="fw-semibold">Status</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody id="rankings-tbody">
                                    @foreach($topPositions as $position)
                                    <tr class="{{ $position->user_id == $user->id ? 'table-warning user-row' : '' }} ranking-row" 
                                        data-position="{{ $position->position }}">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($position->position <= 3 && $leaderboard->type === 'competitive')
                                                    <iconify-icon icon="iconamoon:crown-duotone" class="me-2 text-{{ $position->position == 1 ? 'warning' : ($position->position == 2 ? 'secondary' : 'info') }}"></iconify-icon>
                                                @endif
                                                <span class="fw-bold fs-5 rank-number text-{{ $position->position <= 3 ? 'primary' : 'muted' }}">
                                                    #{{ $position->position }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-{{ $position->user_id == $user->id ? 'warning' : 'primary' }}-subtle rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                    <iconify-icon icon="iconamoon:profile-duotone" class="text-{{ $position->user_id == $user->id ? 'warning' : 'primary' }}"></iconify-icon>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 participant-name">
                                                        {{ $position->user->first_name }} {{ $position->user->last_name }}
                                                    </h6>
                                                    @if($position->user_id == $user->id)
                                                        <small class="text-warning fw-bold">
                                                            <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>You
                                                        </small>
                                                    @elseif($position->position <= 3 && $leaderboard->type === 'competitive')
                                                        <small class="text-muted">
                                                            {{ $position->position == 1 ? '👑 Champion' : ($position->position == 2 ? '🥈 Runner-up' : '🥉 Third Place') }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-info referral-count fs-5">{{ $position->referral_count }}</span>
                                            @if($leaderboard->type === 'competitive' && $position->position <= 3)
                                                <small class="text-muted d-block">Leading</small>
                                            @endif
                                        </td>
                                        @if($leaderboard->type === 'target')
                                            <td>
                                                @php
                                                    $progress = min(100, ($position->referral_count / $leaderboard->target_referrals) * 100);
                                                    $qualified = $position->referral_count >= $leaderboard->target_referrals;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <div class="progress me-2" style="width: 100px; height: 8px;">
                                                        <div class="progress-bar bg-{{ $qualified ? 'success' : 'primary' }}" 
                                                             style="width: {{ $progress }}%"></div>
                                                    </div>
                                                    <small class="text-{{ $qualified ? 'success' : 'muted' }} fw-medium">
                                                        {{ number_format($progress, 1) }}%
                                                    </small>
                                                </div>
                                                @if(!$qualified)
                                                    <small class="text-muted">
                                                        {{ $leaderboard->target_referrals - $position->referral_count }} more needed
                                                    </small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($qualified)
                                                    <span class="badge bg-success">
                                                        <iconify-icon icon="iconamoon:check-circle-duotone" class="me-1"></iconify-icon>
                                                        Qualified (${{ number_format($leaderboard->target_prize_amount) }})
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                                        In Progress
                                                    </span>
                                                @endif
                                            </td>
                                        @else
                                            <td>
                                                <span class="fw-bold text-success prize-amount fs-6">
                                                    ${{ number_format($position->prize_amount) }}
                                                </span>
                                                @if($leaderboard->isActive() && !$position->prize_awarded)
                                                    <small class="text-muted d-block">Pending</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($position->position <= 3)
                                                    <span class="badge bg-success">
                                                        <iconify-icon icon="iconamoon:trophy-duotone" class="me-1"></iconify-icon>
                                                        Winner
                                                    </span>
                                                @elseif($position->position <= 5)
                                                    <span class="badge bg-info">
                                                        <iconify-icon icon="iconamoon:medal-duotone" class="me-1"></iconify-icon>
                                                        Top 5
                                                    </span>
                                                @elseif($position->prize_amount > 0)
                                                    <span class="badge bg-primary">
                                                        <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>
                                                        Prize Winner
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                                        Participant
                                                    </span>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:users-duotone" class="fs-48 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Participants Yet</h6>
                            <p class="text-muted">Be the first to participate in this competition</p>
                            <a href="{{ route('user.referrals') }}" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                Start Referring Now
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            
            <!-- Your Performance Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-{{ $userPosition && $userPosition->position <= 3 ? 'warning' : 'primary' }} text-white">
                    <h5 class="card-title mb-0 text-white">
                        <iconify-icon icon="iconamoon:star-duotone" class="me-2"></iconify-icon>
                        Your Performance
                    </h5>
                </div>
                <div class="card-body" id="user-performance-section">
                    @if($userPosition)
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div class="avatar-xl bg-{{ $userPosition->position <= 3 ? 'warning' : 'primary' }}-subtle rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center">
                                    @if($userPosition->position <= 3)
                                        <iconify-icon icon="iconamoon:crown-duotone" class="fs-32 text-warning"></iconify-icon>
                                    @else
                                        <iconify-icon icon="iconamoon:trophy-duotone" class="fs-32 text-primary"></iconify-icon>
                                    @endif
                                </div>
                                @if($userPosition->position <= 3)
                                    <span class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-warning text-dark">
                                        Top {{ $userPosition->position }}!
                                    </span>
                                @endif
                            </div>
                            <h2 class="mb-1 text-{{ $userPosition->position <= 3 ? 'warning' : 'primary' }}" id="user-rank">
                                #{{ $userPosition->position }}
                            </h2>
                            <p class="text-muted mb-0">Your Current Rank</p>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="text-center p-3 bg-info-subtle rounded-3">
                                    <div class="fw-bold fs-4 text-info" id="user-referrals">{{ $userPosition->referral_count }}</div>
                                    <small class="text-muted fw-medium">Referrals</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-success-subtle rounded-3">
                                    <div class="fw-bold fs-4 text-success" id="user-prize">
                                        ${{ number_format($userPosition->prize_amount) }}
                                    </div>
                                    <small class="text-muted fw-medium">{{ $leaderboard->type === 'target' ? 'Potential' : 'Current' }} Prize</small>
                                </div>
                            </div>
                        </div>

                        @if($leaderboard->type === 'target')
                            <!-- Target Progress -->
                            @php
                                $progress = min(100, ($userPosition->referral_count / $leaderboard->target_referrals) * 100);
                                $qualified = $userPosition->referral_count >= $leaderboard->target_referrals;
                                $remaining = max(0, $leaderboard->target_referrals - $userPosition->referral_count);
                            @endphp
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted fw-medium">Target Progress</small>
                                    <small class="text-{{ $qualified ? 'success' : 'primary' }} fw-bold">{{ number_format($progress, 1) }}%</small>
                                </div>
                                <div class="progress mb-3" style="height: 12px;">
                                    <div class="progress-bar bg-{{ $qualified ? 'success' : 'primary' }} progress-bar-striped {{ $qualified ? '' : 'progress-bar-animated' }}" 
                                         role="progressbar" 
                                         style="width: {{ $progress }}%"></div>
                                </div>
                                @if($qualified)
                                    <div class="alert alert-success border-0">
                                        <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>
                                        <strong>Congratulations!</strong> You've qualified for the ${{ number_format($leaderboard->target_prize_amount) }} prize!
                                    </div>
                                @else
                                    <div class="alert alert-info border-0">
                                        <iconify-icon icon="iconamoon:target-duotone" class="me-2"></iconify-icon>
                                        <strong>Keep going!</strong> You need {{ $remaining }} more referrals to qualify.
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- Competitive Progress to next level -->
                            @if($userPosition->position > 1)
                                @php
                                    $nextPosition = $topPositions->where('position', $userPosition->position - 1)->first();
                                @endphp
                                @if($nextPosition)
                                <div class="alert alert-primary border-0 mb-3">
                                    <div class="d-flex align-items-center">
                                        <iconify-icon icon="iconamoon:target-duotone" class="text-primary me-2"></iconify-icon>
                                        <div>
                                            <small class="fw-semibold">Next Goal:</small>
                                            <div class="small">Need {{ $nextPosition->referral_count - $userPosition->referral_count }} more referrals to reach #{{ $nextPosition->position }}</div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endif
                        @endif

                        @if($leaderboard->isActive() && $userCurrentReferrals != $userPosition->referral_count)
                            <div class="alert alert-warning border-0">
                                <small>
                                    <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                    You have <strong>{{ $userCurrentReferrals }}</strong> referrals this period. 
                                    Rankings update hourly.
                                </small>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-4">
                            <iconify-icon icon="iconamoon:trophy-duotone" class="fs-48 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">Not Ranked Yet</h6>
                            @if($leaderboard->isActive())
                                <p class="text-muted mb-3">Start referring users to join the leaderboard</p>
                                <a href="{{ route('user.referrals') }}" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                    Get Started
                                </a>
                                @if($userCurrentReferrals > 0)
                                    <div class="alert alert-info border-0 mt-3">
                                        <small>
                                            <iconify-icon icon="iconamoon:info-circle-duotone" class="me-1"></iconify-icon>
                                            You have <strong>{{ $userCurrentReferrals }}</strong> referrals this period. Keep going!
                                        </small>
                                    </div>
                                @endif
                            @else
                                <p class="text-muted">Competition has ended</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Referral Tools Card -->
            @if($leaderboard->isActive())
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:share-duotone" class="me-2"></iconify-icon>
                        Referral Tools
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Your Referral Link</label>
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   id="referralLink" 
                                   value="{{ route('register') }}?ref={{ $user->referral_code }}" 
                                   readonly>
                            <button class="btn btn-outline-primary btn-sm" 
                                    type="button" 
                                    onclick="copyReferralLink()">
                                <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-sm" onclick="shareToSocial('whatsapp')">
                            <iconify-icon icon="iconamoon:share-duotone" class="me-1"></iconify-icon>
                            Share on WhatsApp
                        </button>
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-info btn-sm w-100" onclick="shareToSocial('twitter')">
                                    <iconify-icon icon="iconamoon:share-duotone" class="me-1"></iconify-icon>
                                    Twitter
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-primary btn-sm w-100" onclick="shareToSocial('linkedin')">
                                    <iconify-icon icon="iconamoon:share-duotone" class="me-1"></iconify-icon>
                                    LinkedIn
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Competition Details -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:info-circle-duotone" class="me-2"></iconify-icon>
                        Competition Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="detail-row">
                        <span class="text-muted fw-medium">Competition Type</span>
                        <span class="fw-semibold">{{ $leaderboard->type_display }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="text-muted fw-medium">Start Date</span>
                        <span class="fw-semibold">{{ $leaderboard->start_date->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="text-muted fw-medium">End Date</span>
                        <span class="fw-semibold">{{ $leaderboard->end_date->format('M d, Y H:i') }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="text-muted fw-medium">Duration</span>
                        <span class="fw-semibold">{{ $stats['duration_days'] }} days</span>
                    </div>
                    <div class="detail-row">
                        <span class="text-muted fw-medium">Referral Type</span>
                        <span class="fw-semibold">{{ $leaderboard->referral_type_display }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="text-muted fw-medium">Rankings Shown</span>
                        <span class="fw-semibold">Top {{ $leaderboard->max_positions }}</span>
                    </div>
                    @if($leaderboard->type === 'target')
                        <div class="detail-row">
                            <span class="text-muted fw-medium">Target Referrals</span>
                            <span class="fw-semibold">{{ $leaderboard->target_referrals }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-muted fw-medium">Prize per Winner</span>
                            <span class="fw-semibold text-success">${{ number_format($leaderboard->target_prize_amount) }}</span>
                        </div>
                        @if($leaderboard->max_winners)
                            <div class="detail-row">
                                <span class="text-muted fw-medium">Max Winners</span>
                                <span class="fw-semibold">{{ number_format($leaderboard->max_winners) }}</span>
                            </div>
                        @endif
                    @endif
                    <div class="detail-row border-0">
                        <span class="text-muted fw-medium">Created By</span>
                        <span class="fw-semibold">{{ $leaderboard->creator->first_name ?? 'System' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alert-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;"></div>
@endsection

@section('script')
<script>
let refreshInterval;
let leaderboardData = {
    id: {{ $leaderboard->id }},
    isActive: {{ $leaderboard->isActive() ? 'true' : 'false' }},
    type: '{{ $leaderboard->type }}'
};

document.addEventListener('DOMContentLoaded', function() {
    // Auto-refresh if competition is active
    if (leaderboardData.isActive) {
        startAutoRefresh();
    }
    
    // Add animation to numbers
    animateCounters();
});

function startAutoRefresh() {
    // Refresh every 60 seconds for active competitions
    refreshInterval = setInterval(() => {
        if (document.visibilityState === 'visible') {
            refreshLeaderboardData();
        }
    }, 60000);
}

function animateCounters() {
    // Animate the numbers for better UX
    const counters = document.querySelectorAll('.referral-count, .prize-amount, .participant-count');
    counters.forEach(counter => {
        counter.style.opacity = '0';
        setTimeout(() => {
            counter.style.transition = 'opacity 0.5s ease-in';
            counter.style.opacity = '1';
        }, Math.random() * 500);
    });
}

function refreshLeaderboard() {
    const refreshBtn = event.target.closest('button');
    const originalText = refreshBtn.innerHTML;
    
    refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Refreshing...';
    refreshBtn.disabled = true;
    
    refreshLeaderboardData().finally(() => {
        setTimeout(() => {
            refreshBtn.innerHTML = originalText;
            refreshBtn.disabled = false;
        }, 1000);
    });
}

function refreshLeaderboardData() {
    return fetch(`{{ route("user.leaderboards.api.data", $leaderboard) }}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateLeaderboardDisplay(data.data);
                updateLastUpdated();
                showAlert('success', 'Leaderboard refreshed successfully!');
                animateCounters();
            } else {
                showAlert('danger', 'Failed to refresh leaderboard: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Refresh failed:', error);
            showAlert('danger', 'Network error occurred while refreshing');
        });
}

function updateLeaderboardDisplay(data) {
    // Update general stats
    document.getElementById('participant-count').textContent = data.total_participants;
    document.getElementById('winners-count').textContent = data.total_winners || data.qualified_count || 0;
    
    if (data.days_remaining !== undefined) {
        const daysElement = document.getElementById('days-remaining');
        if (daysElement) {
            daysElement.textContent = data.days_remaining + ' days';
        }
    }
    
    if (data.progress !== undefined) {
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        if (progressBar && progressText) {
            progressBar.style.width = data.progress + '%';
            progressText.textContent = data.progress + '%';
        }
    }

    // Update user-specific data
    if (data.user_position) {
        updateUserPerformance(data.user_position);
    } else if (data.user_current_referrals !== undefined) {
        // Update current referrals for non-ranked users
        const alerts = document.querySelectorAll('#user-performance-section .alert-info');
        alerts.forEach(alert => {
            if (alert.textContent.includes('referrals this period')) {
                alert.innerHTML = `
                    <small>
                        <iconify-icon icon="iconamoon:info-circle-duotone" class="me-1"></iconify-icon>
                        You have <strong>${data.user_current_referrals}</strong> referrals this period. Keep going!
                    </small>
                `;
            }
        });
    }

    // Update positions in table
    updatePositionsTable(data.positions);
}

function updateUserPerformance(userPosition) {
    // Update rank
    const rankElement = document.getElementById('user-rank');
    if (rankElement) {
        rankElement.textContent = '#' + userPosition.position;
    }
    
    // Update referrals
    const referralsElement = document.getElementById('user-referrals');
    if (referralsElement) {
        referralsElement.textContent = userPosition.referral_count;
    }
    
    // Update prize
    const prizeElement = document.getElementById('user-prize');
    if (prizeElement) {
        prizeElement.textContent = userPosition.formatted_prize;
    }
    
    // Update progress for target-based competitions
    if (leaderboardData.type === 'target' && userPosition.progress_percentage !== undefined) {
        const progressBars = document.querySelectorAll('#user-performance-section .progress-bar');
        const progressTexts = document.querySelectorAll('#user-performance-section small[class*="text-"]');
        
        progressBars.forEach(bar => {
            bar.style.width = userPosition.progress_percentage + '%';
            bar.className = userPosition.qualified ? 
                'progress-bar bg-success' : 
                'progress-bar bg-primary progress-bar-striped progress-bar-animated';
        });
    }
}

function updatePositionsTable(positions) {
    const tbody = document.getElementById('rankings-tbody');
    if (!tbody || !positions) return;
    
    positions.forEach(position => {
        const row = document.querySelector(`tr[data-position="${position.position}"]`);
        if (row) {
            // Update referral count
            const referralElement = row.querySelector('.referral-count');
            if (referralElement) {
                referralElement.textContent = position.referral_count;
            }
            
            // Update prize amount (for competitive)
            const prizeElement = row.querySelector('.prize-amount');
            if (prizeElement) {
                prizeElement.textContent = position.formatted_prize;
            }
            
            // Update progress bar (for target-based)
            const progressBars = row.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                if (position.progress_percentage !== undefined) {
                    bar.style.width = position.progress_percentage + '%';
                    bar.className = position.qualified ? 
                        'progress-bar bg-success' : 
                        'progress-bar bg-primary';
                }
            });
        }
    });
}

function copyReferralLink() {
    const referralLink = document.getElementById('referralLink');
    if (!referralLink) return;
    
    referralLink.select();
    referralLink.setSelectionRange(0, 99999);
    
    if (navigator.clipboard) {
        navigator.clipboard.writeText(referralLink.value).then(() => {
            showAlert('success', 'Referral link copied to clipboard!');
        }).catch(() => {
            document.execCommand('copy');
            showAlert('success', 'Referral link copied to clipboard!');
        });
    } else {
        document.execCommand('copy');
        showAlert('success', 'Referral link copied to clipboard!');
    }
}

function shareToSocial(platform) {
    const referralLink = document.getElementById('referralLink').value;
    const competitionName = '{{ $leaderboard->title }}';
    const message = `🏆 Join me in the "${competitionName}" competition! 💰 Win amazing prizes by referring new users. Sign up using my referral link:`;
    
    let shareUrl = '';
    
    switch(platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}&url=${encodeURIComponent(referralLink)}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(referralLink)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(message + ' ' + referralLink)}`;
            break;
        case 'telegram':
            shareUrl = `https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent(message)}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        showAlert('info', `Shared to ${platform.charAt(0).toUpperCase() + platform.slice(1)}!`);
    }
}

function updateLastUpdated() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    const lastUpdatedEl = document.getElementById('last-updated');
    if (lastUpdatedEl) {
        lastUpdatedEl.textContent = timeString;
    }
}

function showAlert(type, message) {
    const container = document.getElementById('alert-container');
    if (!container) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show shadow-lg border-0`;
    alertDiv.innerHTML = `
        <div class="d-flex align-items-start">
            <iconify-icon icon="iconamoon:${type === 'success' ? 'check-circle-duotone' : type === 'danger' ? 'warning-duotone' : 'info-circle-duotone'}" class="fs-5 me-2 mt-1"></iconify-icon>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    container.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            try {
                const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                alert.close();
            } catch (e) {
                alertDiv.remove();
            }
        }
    }, 5000);
}

// Cleanup interval on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style>
/* Podium Styles */
.podium-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.podium-container {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 1rem;
    min-height: 160px;
    position: relative;
}

.podium-position {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    max-width: 140px;
}

.podium-content {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    text-align: center;
    margin-bottom: 0.5rem;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    position: relative;
}

.podium-content:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.winner-glow {
    animation: winnerGlow 2s infinite ease-in-out;
}

@keyframes winnerGlow {
    0%, 100% { box-shadow: 0 4px 16px rgba(255, 193, 7, 0.3); }
    50% { box-shadow: 0 8px 32px rgba(255, 193, 7, 0.5); }
}

.podium-avatar {
    width: 3.5rem;
    height: 3.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    position: relative;
    font-size: 1.2rem;
}

.podium-avatar.winner {
    width: 4rem;
    height: 4rem;
    font-size: 1.4rem;
}

.podium-rank {
    position: absolute;
    top: -5px;
    right: -5px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
}

.podium-name {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 110px;
}

.podium-score {
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.podium-prize {
    font-size: 0.8rem;
    font-weight: 600;
}

.podium-base {
    width: 100%;
    border-radius: 8px 8px 0 0;
    border: 3px solid;
}

/* Podium Heights and Colors */
.podium-first {
    order: 2;
}

.podium-first .podium-content {
    border-color: #ffc107;
}

.podium-first .podium-base.podium-gold {
    height: 60px;
    background: linear-gradient(135deg, #ffc107 0%, #ffb300 100%);
    border-color: #ffb300;
}

.podium-second {
    order: 1;
    margin-top: 25px;
}

.podium-second .podium-content {
    border-color: #6c757d;
}

.podium-second .podium-base.podium-silver {
    height: 45px;
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border-color: #5a6268;
}

.podium-third {
    order: 3;
    margin-top: 35px;
}

.podium-third .podium-content {
    border-color: #17a2b8;
}

.podium-third .podium-base.podium-bronze {
    height: 35px;
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    border-color: #138496;
}

/* General Styles */
.bg-gradient-light {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.bg-gradient-success {
    background: linear-gradient(45deg, #28a745, #20c997);
}

.card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.avatar-sm { width: 2.5rem; height: 2.5rem; }
.avatar-md { width: 3rem; height: 3rem; }
.avatar-lg { width: 4rem; height: 4rem; }
.avatar-xl { width: 5rem; height: 5rem; }

.table-warning.user-row {
    background-color: rgba(255, 193, 7, 0.15);
    border-left: 4px solid #ffc107;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.03);
}

.ranking-row {
    transition: all 0.2s ease;
}

.ranking-row:hover {
    transform: translateX(3px);
}

.detail-row {
    display: flex;
    justify-content: between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
    border-bottom: none;
}

.pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Utility Classes */
.bg-info-subtle { background-color: rgba(13, 202, 240, 0.1) !important; }
.bg-primary-subtle { background-color: rgba(13, 110, 253, 0.1) !important; }
.bg-success-subtle { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-warning-subtle { background-color: rgba(255, 193, 7, 0.1) !important; }
.bg-secondary-subtle { background-color: rgba(108, 117, 125, 0.1) !important; }

.text-info { color: #0dcaf0 !important; }
.text-primary { color: #0d6efd !important; }
.text-warning { color: #ffc107 !important; }

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .podium-container {
        gap: 0.5rem;
        min-height: 140px;
    }
    
    .podium-position {
        max-width: 110px;
    }
    
    .podium-content {
        padding: 0.75rem;
    }
    
    .podium-avatar {
        width: 3rem;
        height: 3rem;
        font-size: 1rem;
    }
    
    .podium-avatar.winner {
        width: 3.5rem;
        height: 3.5rem;
        font-size: 1.2rem;
    }
    
    .podium-name {
        font-size: 0.8rem;
        max-width: 90px;
    }
    
    .podium-score {
        font-size: 1rem;
    }
    
    .podium-prize {
        font-size: 0.7rem;
    }
    
    .podium-first .podium-base.podium-gold { height: 50px; }
    .podium-second { margin-top: 20px; }
    .podium-second .podium-base.podium-silver { height: 40px; }
    .podium-third { margin-top: 30px; }
    .podium-third .podium-base.podium-bronze { height: 30px; }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .avatar-xl {
        width: 4rem;
        height: 4rem;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}

@media (max-width: 576px) {
    .podium-container {
        gap: 0.25rem;
        min-height: 120px;
    }
    
    .podium-position {
        max-width: 90px;
    }
    
    .podium-content {
        padding: 0.5rem;
        border-radius: 8px;
    }
    
    .podium-avatar {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }
    
    .podium-avatar.winner {
        width: 3rem;
        height: 3rem;
        font-size: 1rem;
    }
    
    .podium-name {
        font-size: 0.75rem;
        max-width: 80px;
        margin-bottom: 0.2rem;
    }
    
    .podium-score {
        font-size: 0.9rem;
        margin-bottom: 0.2rem;
    }
    
    .podium-prize {
        font-size: 0.65rem;
    }
    
    .podium-rank {
        width: 16px;
        height: 16px;
        font-size: 0.6rem;
        top: -3px;
        right: -3px;
    }
}
</style>
@endsection