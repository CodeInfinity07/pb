@extends('admin.layouts.vertical', ['title' => 'Edit Leaderboard', 'subTitle' => 'Edit ' . $leaderboard->title])

@section('content')

{{-- Error and Success Messages --}}
@if ($errors->any())
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6><iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>Please fix the following errors:</h6>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
@endif

@if (session('success'))
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
</div>
@endif

{{-- Page Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <iconify-icon icon="iconamoon:edit-duotone" class="text-white fs-5"></iconify-icon>
                        </div>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Edit Leaderboard</h5>
                        <small class="text-muted">Update leaderboard settings and configuration</small>
                        <div class="mt-1">
                            <span class="badge {{ $leaderboard->type_badge_class }}">{{ $leaderboard->type_display }}</span>
                            <span class="badge {{ $leaderboard->status_badge_class }} ms-1">{{ ucfirst($leaderboard->status) }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.leaderboards.show', $leaderboard) }}" class="btn btn-sm btn-outline-info">
                        <iconify-icon icon="iconamoon:eye-duotone" class="align-text-bottom"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">View Details</span>
                    </a>
                    <a href="{{ route('admin.leaderboards.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="iconamoon:arrow-left-duotone" class="align-text-bottom"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Back to List</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Warning for Active Leaderboard --}}
@if($leaderboard->isActive())
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center">
            <iconify-icon icon="iconamoon:warning-duotone" class="fs-5 me-3"></iconify-icon>
            <div>
                <strong>Active Leaderboard Warning!</strong>
                This leaderboard is currently active. Changes to duration, type, or configuration may affect ongoing competition results.
            </div>
        </div>
    </div>
</div>
@endif

{{-- Type Change Warning --}}
@if($leaderboard->getParticipantsCount() > 0)
<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-info d-flex align-items-center">
            <iconify-icon icon="iconamoon:information-circle-duotone" class="fs-5 me-3"></iconify-icon>
            <div>
                <strong>Participants Found!</strong>
                This leaderboard has {{ $leaderboard->getParticipantsCount() }} participants. Changing the leaderboard type will require recalculation of positions.
            </div>
        </div>
    </div>
</div>
@endif

{{-- Edit Form --}}
<div class="row">
    <div class="col-12">
        <form action="{{ route('admin.leaderboards.update', $leaderboard) }}" method="POST" id="leaderboardForm">
            @csrf
            @method('PUT')
            
            <div class="row">
                {{-- Main Form --}}
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>
                                Leaderboard Details
                            </h5>
                        </div>
                        <div class="card-body">
                            {{-- Title --}}
                            <div class="mb-4">
                                <label for="title" class="form-label fw-bold">
                                    <iconify-icon icon="iconamoon:text-duotone" class="me-1"></iconify-icon>
                                    Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('title') is-invalid @enderror" 
                                       id="title" name="title" value="{{ old('title', $leaderboard->title) }}" 
                                       maxlength="255" placeholder="Enter leaderboard title..." required>
                                @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Make it exciting and clear (max 255 characters)</div>
                            </div>

                            {{-- Description --}}
                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold">
                                    <iconify-icon icon="iconamoon:document-duotone" class="me-1"></iconify-icon>
                                    Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" name="description" rows="4" 
                                          placeholder="Describe the leaderboard competition rules and details...">{{ old('description', $leaderboard->description) }}</textarea>
                                @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Optional description to explain the competition</div>
                            </div>

                            {{-- Leaderboard Type --}}
                            <div class="mb-4">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <iconify-icon icon="iconamoon:trophy-duotone" class="me-2"></iconify-icon>
                                    Leaderboard Type
                                </h6>
                                @if($leaderboard->getParticipantsCount() > 0 || $leaderboard->prizes_distributed)
                                <div class="alert alert-warning mb-3">
                                    <iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>
                                    Leaderboard type cannot be changed because it has participants or prizes have been distributed.
                                </div>
                                <input type="hidden" name="type" value="{{ $leaderboard->type }}">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card border {{ $leaderboard->type === 'competitive' ? 'border-primary bg-primary bg-opacity-10' : '' }} h-100">
                                            <div class="card-body text-center">
                                                <iconify-icon icon="{{ $leaderboard->type === 'competitive' ? 'iconamoon:trophy-duotone' : 'iconamoon:target-duotone' }}" class="{{ $leaderboard->type === 'competitive' ? 'text-primary' : 'text-info' }} fs-1 mb-2 d-block"></iconify-icon>
                                                <h6 class="fw-bold mb-2">{{ $leaderboard->type_display }}</h6>
                                                <p class="small text-muted mb-0">Current leaderboard type (cannot be changed)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card border h-100 leaderboard-type-option" data-type="competitive">
                                            <div class="card-body text-center">
                                                <div class="form-check d-flex align-items-start">
                                                    <input class="form-check-input me-3 mt-1" 
                                                           type="radio" 
                                                           name="type" 
                                                           id="type_competitive" 
                                                           value="competitive" 
                                                           {{ old('type', $leaderboard->type) === 'competitive' ? 'checked' : '' }}>
                                                    <div class="flex-grow-1">
                                                        <iconify-icon icon="iconamoon:trophy-duotone" class="text-primary fs-1 mb-2 d-block"></iconify-icon>
                                                        <h6 class="fw-bold mb-2">Competitive Ranking</h6>
                                                        <p class="small text-muted mb-0">Traditional ranking system where top performers win prizes based on their position</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border h-100 leaderboard-type-option" data-type="target">
                                            <div class="card-body text-center">
                                                <div class="form-check d-flex align-items-start">
                                                    <input class="form-check-input me-3 mt-1" 
                                                           type="radio" 
                                                           name="type" 
                                                           id="type_target" 
                                                           value="target" 
                                                           {{ old('type', $leaderboard->type) === 'target' ? 'checked' : '' }}>
                                                    <div class="flex-grow-1">
                                                        <iconify-icon icon="iconamoon:target-duotone" class="text-info fs-1 mb-2 d-block"></iconify-icon>
                                                        <h6 class="fw-bold mb-2">Target Achievement</h6>
                                                        <p class="small text-muted mb-0">Goal-based system where all users reaching the target get the same prize</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @error('type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Duration --}}
                            <div class="mb-4">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                                    Competition Duration
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label fw-semibold">Start Date & Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" 
                                               id="start_date" name="start_date" 
                                               value="{{ old('start_date', $leaderboard->start_date->format('Y-m-d\TH:i')) }}" 
                                               required {{ $leaderboard->isActive() && $leaderboard->start_date <= now() ? 'readonly' : '' }}>
                                        @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        @if($leaderboard->isActive() && $leaderboard->start_date <= now())
                                        <div class="form-text text-warning">Cannot change start date of active leaderboard that has already started</div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label fw-semibold">End Date & Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" 
                                               id="end_date" name="end_date" 
                                               value="{{ old('end_date', $leaderboard->end_date->format('Y-m-d\TH:i')) }}" 
                                               required>
                                        @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-text">Competition will automatically end at the specified time</div>
                            </div>

                            {{-- Referral Type --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <iconify-icon icon="iconamoon:people-duotone" class="me-1"></iconify-icon>
                                    Referral Type <span class="text-danger">*</span>
                                </label>
                                @if($leaderboard->isActive())
                                <div class="alert alert-info small mb-2">
                                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                    Changing referral type will affect position calculations for active leaderboard
                                </div>
                                @endif
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="referral_type" 
                                                   id="type_all" value="all" {{ old('referral_type', $leaderboard->referral_type) === 'all' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_all">
                                                <iconify-icon icon="iconamoon:people-duotone" class="text-primary me-2"></iconify-icon>
                                                All Referrals
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="referral_type" 
                                                   id="type_first_level" value="first_level" {{ old('referral_type', $leaderboard->referral_type) === 'first_level' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_first_level">
                                                <iconify-icon icon="iconamoon:profile-duotone" class="text-info me-2"></iconify-icon>
                                                First Level Only
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="referral_type" 
                                                   id="type_verified" value="verified_only" {{ old('referral_type', $leaderboard->referral_type) === 'verified_only' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_verified">
                                                <iconify-icon icon="iconamoon:check-circle-duotone" class="text-success me-2"></iconify-icon>
                                                Verified Only
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('referral_type')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Choose which referrals count towards the leaderboard</div>
                            </div>

                            {{-- Competitive Prize Structure --}}
                            <div class="mb-4" id="competitivePrizes" style="display: none;">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <iconify-icon icon="iconamoon:dollar-duotone" class="me-2"></iconify-icon>
                                    Prize Structure (Competitive)
                                    @if($leaderboard->prizes_distributed)
                                    <span class="badge bg-success ms-2">Prizes Distributed</span>
                                    @endif
                                </h6>
                                
                                @if($leaderboard->prizes_distributed)
                                <div class="alert alert-warning mb-3">
                                    <iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>
                                    Prizes have been distributed. Changes to prize structure will not affect already awarded prizes.
                                </div>
                                @endif
                                
                                <div id="prizeStructure">
                                    @if($leaderboard->prize_structure && count($leaderboard->prize_structure) > 0)
                                        @foreach($leaderboard->prize_structure as $index => $prize)
                                        <div class="prize-row mb-3 p-3 border rounded bg-light">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Position</label>
                                                    <input type="number" class="form-control" name="prize_structure[{{ $index }}][position]" 
                                                           value="{{ $prize['position'] ?? ($index + 1) }}" min="1" max="100">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Prize Amount ($)</label>
                                                    <input type="number" step="0.01" class="form-control" name="prize_structure[{{ $index }}][amount]" 
                                                           value="{{ $prize['amount'] ?? '' }}" placeholder="100.00" min="0">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Actions</label>
                                                    <div class="d-flex align-items-center gap-2">
<span class="badge bg-primary">{{ ($index + 1) . (($index + 1) == 1 ? 'st' : (($index + 1) == 2 ? 'nd' : (($index + 1) == 3 ? 'rd' : 'th'))) }} Place</span>                                                        @if($index > 0 || count($leaderboard->prize_structure) > 1)
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-prize-btn">
                                                            <iconify-icon icon="iconamoon:trash-duotone"></iconify-icon>
                                                        </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="prize-row mb-3 p-3 border rounded bg-light">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Position</label>
                                                    <input type="number" class="form-control" name="prize_structure[0][position]" 
                                                           value="1" min="1" max="100">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Prize Amount ($)</label>
                                                    <input type="number" step="0.01" class="form-control" name="prize_structure[0][amount]" 
                                                           placeholder="100.00" min="0">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Actions</label>
                                                    <div>
                                                        <span class="badge bg-primary">1st Place</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm" id="addPrizeBtn">
                                    <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                    Add Prize Position
                                </button>

                                <div class="form-text mt-2">Define prizes for different positions. Leave amount empty for no prize.</div>
                            </div>

                            {{-- Target Prize Configuration --}}
                            <div class="mb-4" id="targetPrizes" style="display: none;">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <iconify-icon icon="iconamoon:target-duotone" class="me-2"></iconify-icon>
                                    Target Configuration
                                    @if($leaderboard->prizes_distributed)
                                    <span class="badge bg-success ms-2">Prizes Distributed</span>
                                    @endif
                                </h6>
                                
                                @if($leaderboard->prizes_distributed)
                                <div class="alert alert-warning mb-3">
                                    <iconify-icon icon="iconamoon:warning-duotone" class="me-2"></iconify-icon>
                                    Prizes have been distributed. Changes to target configuration will not affect already awarded prizes.
                                </div>
                                @endif
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="target_referrals" class="form-label fw-semibold">
                                            Target Referrals <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control @error('target_referrals') is-invalid @enderror" 
                                               id="target_referrals" 
                                               name="target_referrals" 
                                               value="{{ old('target_referrals', $leaderboard->target_referrals) }}" 
                                               min="1" 
                                               max="1000"
                                               placeholder="10">
                                        @error('target_referrals')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Number of referrals needed to qualify for the prize</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="target_prize_amount" class="form-label fw-semibold">
                                            Prize Amount ($) <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               step="0.01" 
                                               class="form-control @error('target_prize_amount') is-invalid @enderror" 
                                               id="target_prize_amount" 
                                               name="target_prize_amount" 
                                               value="{{ old('target_prize_amount', $leaderboard->target_prize_amount) }}" 
                                               min="0.01" 
                                               max="10000"
                                               placeholder="50.00">
                                        @error('target_prize_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Amount each qualifying user receives</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="max_winners" class="form-label fw-semibold">
                                            Maximum Winners (Optional)
                                        </label>
                                        <input type="number" 
                                               class="form-control @error('max_winners') is-invalid @enderror" 
                                               id="max_winners" 
                                               name="max_winners" 
                                               value="{{ old('max_winners', $leaderboard->max_winners) }}" 
                                               min="1" 
                                               max="10000"
                                               placeholder="Leave empty for unlimited">
                                        @error('max_winners')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Limit the total number of winners (leave empty for no limit)</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sidebar Settings --}}
                <div class="col-lg-4">
                    {{-- Current Status --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                                Current Status
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <span class="badge {{ $leaderboard->status_badge_class }} fs-6 px-3 py-2">
                                    <iconify-icon icon="iconamoon:{{ $leaderboard->status === 'active' ? 'play' : ($leaderboard->status === 'completed' ? 'check-circle' : 'pause') }}-duotone" class="me-1"></iconify-icon>
                                    {{ ucfirst($leaderboard->status) }}
                                </span>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="fw-bold">{{ $leaderboard->getParticipantsCount() }}</div>
                                    <small class="text-muted">Participants</small>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold">${{ number_format($leaderboard->total_prize_amount, 2) }}</div>
                                    <small class="text-muted">Total Prizes</small>
                                </div>
                            </div>

                            @if($leaderboard->isActive())
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Progress</span>
                                    <span>{{ $leaderboard->getProgress() }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $leaderboard->getProgress() }}%"></div>
                                </div>
                            </div>
                            @endif

                            @if($leaderboard->type === 'target')
                            <div class="mt-3">
                                <div class="text-center">
                                    <div class="fw-bold">{{ $leaderboard->getQualifiedCount() }}</div>
                                    <small class="text-muted">Qualified Winners</small>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Leaderboard Settings --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                                Settings
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="max_positions" class="form-label fw-semibold">Max Positions <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('max_positions') is-invalid @enderror" 
                                       id="max_positions" name="max_positions" value="{{ old('max_positions', $leaderboard->max_positions) }}" 
                                       min="1" max="100" required>
                                @error('max_positions')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Maximum users to show on leaderboard</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="status_active" value="active" {{ old('status', $leaderboard->status) === 'active' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="status_active">Active</label>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="status_inactive" value="inactive" {{ old('status', $leaderboard->status) === 'inactive' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="status_inactive">Inactive</label>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="status" 
                                                   id="status_completed" value="completed" {{ old('status', $leaderboard->status) === 'completed' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="status_completed">Completed</label>
                                        </div>
                                    </div>
                                </div>
                                @error('status')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Current leaderboard status</div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="show_to_users" id="show_to_users" value="1" {{ old('show_to_users', $leaderboard->show_to_users) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_to_users">
                                        Show to users
                                    </label>
                                </div>
                                <div class="form-text">Make leaderboard visible to users</div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span class="spinner-border spinner-border-sm me-1" style="display: none;"></span>
                                    <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                                    Update Leaderboard
                                </button>
                                <a href="{{ route('admin.leaderboards.show', $leaderboard) }}" class="btn btn-outline-info">
                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                    View Details
                                </a>
                                <a href="{{ route('admin.leaderboards.index') }}" class="btn btn-outline-secondary">
                                    <iconify-icon icon="iconamoon:close-duotone" class="me-1"></iconify-icon>
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script>
let prizeRowCount = {{ $leaderboard->prize_structure ? count($leaderboard->prize_structure) : 1 }};

document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const form = document.getElementById('leaderboardForm');
    const submitBtn = document.getElementById('submitBtn');
    const addPrizeBtn = document.getElementById('addPrizeBtn');
    const prizeContainer = document.getElementById('prizeStructure');
    const competitivePrizes = document.getElementById('competitivePrizes');
    const targetPrizes = document.getElementById('targetPrizes');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const typeOptions = document.querySelectorAll('.leaderboard-type-option');
    
    // Initialize form based on selected type
    function updateFormFields() {
        const selectedType = document.querySelector('input[name="type"]:checked')?.value || '{{ $leaderboard->type }}';
        
        // Update type option cards
        typeOptions.forEach(option => {
            const card = option;
            const isSelected = option.dataset.type === selectedType;
            
            if (isSelected) {
                card.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
                card.classList.remove('border');
            } else {
                card.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                card.classList.add('border');
            }
        });
        
        // Show/hide appropriate prize sections
        if (selectedType === 'competitive') {
            competitivePrizes.style.display = 'block';
            targetPrizes.style.display = 'none';
            
            // Clear target fields
            if (document.getElementById('target_referrals')) {
                document.getElementById('target_referrals').value = '';
            }
            if (document.getElementById('target_prize_amount')) {
                document.getElementById('target_prize_amount').value = '';
            }
            if (document.getElementById('max_winners')) {
                document.getElementById('max_winners').value = '';
            }
        } else if (selectedType === 'target') {
            competitivePrizes.style.display = 'none';
            targetPrizes.style.display = 'block';
            
            // Clear competitive fields
            const prizeInputs = document.querySelectorAll('input[name*="prize_structure"]');
            prizeInputs.forEach(input => input.value = '');
        }
    }
    
    // Event listeners for type selection
    typeRadios.forEach(radio => {
        radio.addEventListener('change', updateFormFields);
    });
    
    // Click handlers for type option cards
    typeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const typeValue = this.dataset.type;
            const radio = document.getElementById(`type_${typeValue}`);
            if (radio && !radio.disabled) {
                radio.checked = true;
                updateFormFields();
            }
        });
    });
    
    // Handle form submission
    form.addEventListener('submit', function(e) {
        const spinner = submitBtn.querySelector('.spinner-border');
        
        submitBtn.disabled = true;
        spinner.style.display = 'inline-block';
        
        // Validation would go here if needed
    });

    // Add prize row function (for competitive type)
    // Replace the addPrizeBtn event listener with this corrected version:

if (addPrizeBtn) {
    addPrizeBtn.addEventListener('click', function() {
        // Calculate the next position based on existing rows
        const existingRows = document.querySelectorAll('.prize-row');
        const nextPosition = existingRows.length + 1;
        
        prizeRowCount++; // This is just for unique naming of form fields
        
        const newRow = document.createElement('div');
        newRow.className = 'prize-row mb-3 p-3 border rounded bg-light';
        newRow.innerHTML = `
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Position</label>
                    <input type="number" class="form-control" name="prize_structure[${prizeRowCount}][position]" 
                           value="${nextPosition}" min="1" max="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Prize Amount ($)</label>
                    <input type="number" step="0.01" class="form-control" name="prize_structure[${prizeRowCount}][amount]" 
                           placeholder="50.00" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Actions</label>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-secondary">${getPositionSuffix(nextPosition)} Place</span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-prize-btn">
                            <iconify-icon icon="iconamoon:trash-duotone"></iconify-icon>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        prizeContainer.appendChild(newRow);
        updateRemoveButtons();
    });
}
    // Remove prize row (event delegation)
    if (prizeContainer) {
        prizeContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-prize-btn')) {
                const row = e.target.closest('.prize-row');
                if (prizeContainer.children.length > 1) {
                    row.remove();
                    updateRemoveButtons();
                }
            }
        });
    }
    
    // Update remove button visibility
    function updateRemoveButtons() {
        const rows = document.querySelectorAll('.prize-row');
        rows.forEach((row, index) => {
            const removeBtn = row.querySelector('.remove-prize-btn');
            if (removeBtn) {
                if (index === 0 && rows.length === 1) {
                    removeBtn.style.display = 'none';
                } else {
                    removeBtn.style.display = 'block';
                }
            }
        });
    }
    
    // Helper function for position suffix
    function getPositionSuffix(position) {
        const lastDigit = position % 10;
        const lastTwoDigits = position % 100;
        
        if (lastTwoDigits >= 11 && lastTwoDigits <= 13) {
            return position + 'th';
        }
        
        switch (lastDigit) {
            case 1: return position + 'st';
            case 2: return position + 'nd';
            case 3: return position + 'rd';
            default: return position + 'th';
        }
    }
    
    // Date validation
    function validateDates() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            
            if (end <= start) {
                endDate.setCustomValidity('End date must be after start date');
            } else {
                endDate.setCustomValidity('');
            }
        }
    }
    
    // Date validation listeners
    document.getElementById('start_date').addEventListener('change', validateDates);
    document.getElementById('end_date').addEventListener('change', validateDates);

    // Update remove buttons on load
    updateRemoveButtons();

    // Status change warnings
    const statusRadios = document.querySelectorAll('input[name="status"]');
    statusRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'completed' && '{{ $leaderboard->status }}' !== 'completed') {
                if (!confirm('Marking as completed will finalize the leaderboard and calculate final positions. Are you sure?')) {
                    document.querySelector('input[name="status"][value="{{ $leaderboard->status }}"]').checked = true;
                }
            }
        });
    });
    
    // Initialize form
    updateFormFields();
});
</script>

<style>
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.15);
}

.prize-row {
    transition: all 0.2s ease;
}

.prize-row:hover {
    background-color: #e9ecef !important;
}

.form-check-input:checked {
    background-color: #007bff;
    border-color: #007bff;
}

.leaderboard-type-option {
    cursor: pointer;
    transition: all 0.3s ease;
}

.leaderboard-type-option:hover {
    border-color: #007bff !important;
}

.leaderboard-type-option.border-primary {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}

.alert {
    border-radius: 6px;
}

.badge {
    font-weight: 500;
    border-radius: 0.375rem;
}

.progress {
    border-radius: 4px;
}

.progress-bar {
    border-radius: 4px;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
    
    .prize-row .col-md-4 {
        margin-bottom: 0.5rem;
    }
}

.form-control[readonly] {
    background-color: #e9ecef;
    opacity: 1;
}
</style>
@endsection