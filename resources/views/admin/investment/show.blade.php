{{-- resources/views/admin/investment/show.blade.php --}}
@extends('admin.layouts.vertical', ['title' => $investmentPlan->name, 'subTitle' => 'Investment Plan Details'])

@section('css')
<style>
    .plan-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        color: white;
        margin-bottom: 2rem;
    }
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
    .info-section {
        background: white;
        border-radius: 12px;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    .info-section-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    .tier-showcase {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        margin-bottom: 1rem;
        background: white;
        transition: all 0.3s ease;
    }
    .tier-showcase:hover {
        border-color: #0d6efd;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .tier-showcase-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        border-radius: 10px 10px 0 0;
    }
    .tier-badge {
        background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .investment-card {
        border-left: 4px solid #0d6efd;
        background: white;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        transition: all 0.2s ease;
    }
    .investment-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateX(4px);
    }
    .feature-list {
        list-style: none;
        padding: 0;
    }
    .feature-list li {
        padding: 0.5rem 0;
        border-bottom: 1px solid #f1f3f4;
        display: flex;
        align-items: center;
    }
    .feature-list li:last-child {
        border-bottom: none;
    }
    .analytics-chart {
        height: 300px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #dee2e6;
    }
    .quick-action-btn {
        border-radius: 8px;
        padding: 0.75rem 1rem;
        font-weight: 500;
        transition: all 0.2s ease;
    }
    .status-badge-lg {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.875rem;
    }
</style>
@endsection

@section('content')

{{-- Plan Header --}}
<div class="plan-header">
    <div class="p-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="avatar-xl bg-white bg-opacity-20 rounded-circle">
                        <iconify-icon icon="solar:{{ $investmentPlan->is_tiered ? 'layers-minimalistic' : 'star' }}-bold-duotone" 
                                     class="avatar-title text-white fs-1"></iconify-icon>
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1">{{ $investmentPlan->name }}</h2>
                        <div class="d-flex align-items-center gap-2">
                            <span class="status-badge-lg {{ $investmentPlan->status_badge_class }}">
                                {{ ucfirst($investmentPlan->status) }}
                            </span>
                            @if($investmentPlan->is_tiered)
                                <span class="badge bg-white bg-opacity-20 text-white">
                                    <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                                    {{ $investmentPlan->tiers->count() }} Tiers
                                </span>
                            @else
                                <span class="badge bg-white bg-opacity-20 text-white">
                                    <iconify-icon icon="solar:star-bold-duotone" class="me-1"></iconify-icon>
                                    Simple Plan
                                </span>
                            @endif
                            @if($investmentPlan->badge)
                                <span class="badge bg-warning">{{ $investmentPlan->badge }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                @if($investmentPlan->description)
                <p class="mb-0 text-white-50">{{ $investmentPlan->description }}</p>
                @endif
            </div>
            
            <div class="col-md-4 text-md-end">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('admin.investment.edit', $investmentPlan) }}" class="btn btn-light">
                        <iconify-icon icon="solar:pen-bold-duotone" class="me-1"></iconify-icon>
                        Edit Plan
                    </a>
                    <button class="btn btn-outline-light" onclick="togglePlanStatus({{ $investmentPlan->id }})">
                        <iconify-icon icon="solar:{{ $investmentPlan->isActive() ? 'pause' : 'play' }}-bold-duotone" class="me-1"></iconify-icon>
                        {{ $investmentPlan->isActive() ? 'Deactivate' : 'Activate' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Key Statistics --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-primary-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:users-group-two-rounded-bold-duotone" class="avatar-title text-primary fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($investmentPlan->user_investments_count ?? 0) }}</h4>
                <p class="text-muted mb-0">Total Investors</p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-success-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:wallet-money-bold-duotone" class="avatar-title text-success fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">${{ number_format($investmentPlan->user_investments_sum_amount ?? 0, 2) }}</h4>
                <p class="text-muted mb-0">Total Invested</p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-warning-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:chart-square-bold-duotone" class="avatar-title text-warning fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($investmentPlan->active_investments_count ?? 0) }}</h4>
                <p class="text-muted mb-0">Active Investments</p>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="avatar-lg bg-info-subtle rounded-3 mx-auto mb-3">
                    <iconify-icon icon="solar:graph-up-bold-duotone" class="avatar-title text-info fs-2"></iconify-icon>
                </div>
                <h4 class="fw-bold text-dark mb-1">{{ number_format($investmentPlan->completed_investments_count ?? 0) }}</h4>
                <p class="text-muted mb-0">Completed</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        {{-- Plan Configuration --}}
        <div class="info-section">
            <div class="info-section-header">
                <h5 class="mb-0 fw-bold">
                    <iconify-icon icon="solar:settings-bold-duotone" class="me-2"></iconify-icon>
                    Plan Configuration
                </h5>
            </div>
            <div class="p-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary-subtle rounded me-3">
                                <iconify-icon icon="solar:calendar-bold-duotone" class="avatar-title text-primary"></iconify-icon>
                            </div>
                            <div>
                                <div class="small text-muted">Interest Frequency</div>
                                <div class="fw-semibold">{{ ucfirst($investmentPlan->interest_type) }} Returns</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-success-subtle rounded me-3">
                                <iconify-icon icon="solar:clock-circle-bold-duotone" class="avatar-title text-success"></iconify-icon>
                            </div>
                            <div>
                                <div class="small text-muted">Duration</div>
                                <div class="fw-semibold">{{ $investmentPlan->formatted_duration }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-warning-subtle rounded me-3">
                                <iconify-icon icon="solar:percent-bold-duotone" class="avatar-title text-warning"></iconify-icon>
                            </div>
                            <div>
                                <div class="small text-muted">Interest Rate</div>
                                <div class="fw-semibold">{{ $investmentPlan->formatted_interest_rate }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-info-subtle rounded me-3">
                                <iconify-icon icon="solar:refresh-bold-duotone" class="avatar-title text-info"></iconify-icon>
                            </div>
                            <div>
                                <div class="small text-muted">Return Type</div>
                                <div class="fw-semibold">{{ ucfirst($investmentPlan->return_type) }} Interest</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-secondary-subtle rounded me-3">
                                <iconify-icon icon="solar:wallet-bold-duotone" class="avatar-title text-secondary"></iconify-icon>
                            </div>
                            <div>
                                <div class="small text-muted">Capital Return</div>
                                <div class="fw-semibold">
                                    @if($investmentPlan->capital_return)
                                        <span class="text-success">
                                            <iconify-icon icon="solar:check-circle-bold-duotone" class="me-1"></iconify-icon>
                                            Yes
                                        </span>
                                    @else
                                        <span class="text-danger">
                                            <iconify-icon icon="solar:close-circle-bold-duotone" class="me-1"></iconify-icon>
                                            No
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-primary-subtle rounded me-3">
                                <iconify-icon icon="solar:dollar-minimalistic-bold-duotone" class="avatar-title text-primary"></iconify-icon>
                            </div>
                            <div>
                                <div class="small text-muted">Investment Range</div>
                                <div class="fw-semibold">{{ $investmentPlan->formatted_minimum }} - {{ $investmentPlan->formatted_maximum }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tiers Information (for tiered plans) --}}
        @if($investmentPlan->is_tiered && $investmentPlan->tiers->count() > 0)
        <div class="info-section">
            <div class="info-section-header">
                <h5 class="mb-0 fw-bold">
                    <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-2"></iconify-icon>
                    Investment Tiers ({{ $investmentPlan->tiers->count() }})
                </h5>
            </div>
            <div class="p-4">
                @foreach($investmentPlan->tiers as $tier)
                <div class="tier-showcase">
                    <div class="tier-showcase-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="tier-badge">Tier {{ $tier->tier_level }}</span>
                                <h6 class="mb-0 fw-semibold">{{ $tier->tier_name }}</h6>
                            </div>
                            <span class="badge {{ $tier->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $tier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="small text-muted">Investment Range</div>
                                <div class="fw-semibold">{{ $tier->investment_range }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Interest Rate</div>
                                <div class="fw-semibold text-success">{{ $tier->formatted_interest_rate }}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Required Level</div>
                                <div class="fw-semibold">
                                    <span class="badge bg-info-subtle text-info">Level {{ $tier->min_user_level }}</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="small text-muted">Investments</div>
                                <div class="fw-semibold">{{ $tier->userInvestments()->count() }}</div>
                            </div>
                            
                            @if($tier->tier_description)
                            <div class="col-md-12">
                                <div class="small text-muted">Description</div>
                                <p class="mb-0">{{ $tier->tier_description }}</p>
                            </div>
                            @endif
                            
                            @if($tier->tier_features && count($tier->tier_features) > 0)
                            <div class="col-md-12">
                                <div class="small text-muted mb-2">Features</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($tier->tier_features as $feature)
                                        <span class="badge bg-warning-subtle text-warning">
                                            <iconify-icon icon="solar:star-bold-duotone" class="me-1"></iconify-icon>
                                            {{ $feature }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Plan Features --}}
        @if($investmentPlan->features && count($investmentPlan->features) > 0)
        <div class="info-section">
            <div class="info-section-header">
                <h5 class="mb-0 fw-bold">
                    <iconify-icon icon="solar:star-circle-bold-duotone" class="me-2"></iconify-icon>
                    Plan Features
                </h5>
            </div>
            <div class="p-4">
                <ul class="feature-list">
                    @foreach($investmentPlan->features as $feature)
                    <li>
                        <iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-2"></iconify-icon>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        {{-- Analytics Charts --}}
        <div class="info-section">
            <div class="info-section-header">
                <h5 class="mb-0 fw-bold">
                    <iconify-icon icon="solar:chart-square-bold-duotone" class="me-2"></iconify-icon>
                    Performance Analytics
                </h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="analytics-chart">
                            <div class="text-center text-muted">
                                <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-1 mb-2"></iconify-icon>
                                <div>Investment Trends</div>
                                <small>Chart integration needed</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="analytics-chart">
                            <div class="text-center text-muted">
                                <iconify-icon icon="solar:pie-chart-bold-duotone" class="fs-1 mb-2"></iconify-icon>
                                <div>User Level Distribution</div>
                                <small>Chart integration needed</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Analytics Summary --}}
                @if(isset($analytics))
                <div class="mt-4">
                    <h6 class="fw-semibold mb-3">Performance Summary</h6>
                    <div class="row g-3">
                        @if(isset($analytics['investment_stats']))
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Average Investment</h6>
                                    <h5 class="fw-bold">
                                        @php
                                            $totalAmount = $analytics['investment_stats']->sum('total_amount');
                                            $totalCount = $analytics['investment_stats']->sum('count');
                                            $average = $totalCount > 0 ? $totalAmount / $totalCount : 0;
                                        @endphp
                                        ${{ number_format($average, 2) }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Success Rate</h6>
                                    <h5 class="fw-bold text-success">
                                        @php
                                            $total = $investmentPlan->user_investments_count ?? 0;
                                            $completed = $investmentPlan->completed_investments_count ?? 0;
                                            $rate = $total > 0 ? ($completed / $total) * 100 : 0;
                                        @endphp
                                        {{ number_format($rate, 1) }}%
                                    </h5>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-1">Created</h6>
                                    <h5 class="fw-bold">{{ $investmentPlan->created_at->format('M j, Y') }}</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        {{-- Quick Actions --}}
        <div class="info-section">
            <div class="info-section-header">
                <h5 class="mb-0 fw-bold">
                    <iconify-icon icon="solar:bolt-bold-duotone" class="me-2"></iconify-icon>
                    Quick Actions
                </h5>
            </div>
            <div class="p-4">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.investment.edit', $investmentPlan) }}" class="btn btn-primary quick-action-btn">
                        <iconify-icon icon="solar:pen-bold-duotone" class="me-2"></iconify-icon>
                        Edit Plan
                    </a>
                    
                    <button class="btn btn-outline-primary quick-action-btn" onclick="togglePlanStatus({{ $investmentPlan->id }})">
                        <iconify-icon icon="solar:{{ $investmentPlan->isActive() ? 'pause' : 'play' }}-bold-duotone" class="me-2"></iconify-icon>
                        {{ $investmentPlan->isActive() ? 'Deactivate Plan' : 'Activate Plan' }}
                    </button>
                    
                    @if($investmentPlan->is_tiered)
                    <button class="btn btn-outline-info quick-action-btn" onclick="viewTierDetails()">
                        <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-2"></iconify-icon>
                        View Tier Stats
                    </button>
                    @endif
                    
                    <button class="btn btn-outline-success quick-action-btn" onclick="simulateInvestment()">
                        <iconify-icon icon="solar:calculator-bold-duotone" class="me-2"></iconify-icon>
                        Investment Simulator
                    </button>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="{{ route('admin.investment.user-investments', ['plan_id' => $investmentPlan->id]) }}" class="btn btn-outline-secondary quick-action-btn">
                        <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="me-2"></iconify-icon>
                        View All Investments
                    </a>
                    
                    @if($investmentPlan->activeInvestments()->count() === 0)
                    <button class="btn btn-outline-danger quick-action-btn" onclick="deletePlan({{ $investmentPlan->id }})">
                        <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" class="me-2"></iconify-icon>
                        Delete Plan
                    </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Recent Investments --}}
        <div class="info-section">
            <div class="info-section-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <iconify-icon icon="solar:clock-circle-bold-duotone" class="me-2"></iconify-icon>
                        Recent Investments
                    </h5>
                    <a href="{{ route('admin.investment.user-investments', ['plan_id' => $investmentPlan->id]) }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="p-4">
                @if($investmentPlan->userInvestments && $investmentPlan->userInvestments->count() > 0)
                    @foreach($investmentPlan->userInvestments->take(5) as $investment)
                    <div class="investment-card p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1 fw-semibold">{{ $investment->user->full_name ?? 'N/A' }}</h6>
                                <div class="small text-muted mb-2">
                                    <iconify-icon icon="solar:calendar-bold-duotone" class="me-1"></iconify-icon>
                                    {{ $investment->created_at->format('M j, Y') }}
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold">${{ number_format($investment->amount, 2) }}</span>
                                    @if($investmentPlan->is_tiered && isset($investment->tier_level))
                                        <span class="badge bg-info-subtle text-info">Tier {{ $investment->tier_level }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge {{ $investment->status === 'active' ? 'bg-success' : ($investment->status === 'completed' ? 'bg-primary' : 'bg-warning') }}">
                                {{ ucfirst($investment->status) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <div class="avatar-lg bg-light rounded-circle mx-auto mb-3">
                            <iconify-icon icon="solar:wallet-money-bold-duotone" class="avatar-title text-muted fs-2"></iconify-icon>
                        </div>
                        <h6 class="text-muted">No investments yet</h6>
                        <p class="text-muted small mb-0">This plan hasn't received any investments</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Plan Information --}}
        <div class="info-section">
            <div class="info-section-header">
                <h5 class="mb-0 fw-bold">
                    <iconify-icon icon="solar:info-circle-bold-duotone" class="me-2"></iconify-icon>
                    Plan Information
                </h5>
            </div>
            <div class="p-4">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="small text-muted">Plan ID</div>
                        <div class="fw-semibold">#{{ $investmentPlan->id }}</div>
                    </div>
                    
                    <div class="col-12">
                        <div class="small text-muted">Display Order</div>
                        <div class="fw-semibold">{{ $investmentPlan->sort_order ?? 0 }}</div>
                    </div>
                    
                    <div class="col-12">
                        <div class="small text-muted">Color Scheme</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-{{ $investmentPlan->color_scheme }}">{{ ucfirst($investmentPlan->color_scheme) }}</span>
                            <div class="badge bg-{{ $investmentPlan->color_scheme }}-subtle text-{{ $investmentPlan->color_scheme }}">Preview</div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="small text-muted">Created</div>
                        <div class="fw-semibold">{{ $investmentPlan->created_at->format('M j, Y \a\t g:i A') }}</div>
                    </div>
                    
                    <div class="col-12">
                        <div class="small text-muted">Last Updated</div>
                        <div class="fw-semibold">{{ $investmentPlan->updated_at->format('M j, Y \a\t g:i A') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Investment Simulator Modal --}}
<div class="modal fade" id="simulatorModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Investment Simulator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Investment Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="simulatorAmount" value="1000" min="1">
                        </div>
                    </div>
                    
                    @if($investmentPlan->is_tiered)
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Select Tier</label>
                        <select class="form-select" id="simulatorTier">
                            <option value="">Auto-select tier</option>
                            @foreach($investmentPlan->activeTiers as $tier)
                                <option value="{{ $tier->id }}">Tier {{ $tier->tier_level }} - {{ $tier->tier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
                
                <div class="mt-4" id="simulatorResults">
                    <div class="text-center text-muted">
                        <iconify-icon icon="solar:calculator-bold-duotone" class="fs-1 mb-2"></iconify-icon>
                        <p>Enter an amount to see investment simulation</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="runSimulation()">Calculate Returns</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Global variables
const planId = {{ $investmentPlan->id }};
const planData = @json($investmentPlan);

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
});

function setupEventListeners() {
    // Simulator amount input
    const simulatorAmount = document.getElementById('simulatorAmount');
    if (simulatorAmount) {
        simulatorAmount.addEventListener('input', updateSimulatorPreview);
    }
    
    // Simulator tier select
    const simulatorTier = document.getElementById('simulatorTier');
    if (simulatorTier) {
        simulatorTier.addEventListener('change', updateSimulatorPreview);
    }
}

function togglePlanStatus(planId) {
    if (!confirm('Are you sure you want to change this plan\'s status?')) return;
    
    fetch(`/admin/investment/${planId}/toggle-status`, {
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
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating plan status.', 'danger');
    });
}

function deletePlan(planId) {
    if (!confirm('Are you sure you want to delete this investment plan? This action cannot be undone.')) return;
    
    fetch(`/admin/investment/${planId}`, {
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
            setTimeout(() => {
                window.location.href = '{{ route("admin.investment.index") }}';
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

function viewTierDetails() {
    showAlert('Tier details functionality to be implemented with detailed analytics.', 'info');
}

function simulateInvestment() {
    const modal = new bootstrap.Modal(document.getElementById('simulatorModal'));
    modal.show();
    updateSimulatorPreview();
}

function updateSimulatorPreview() {
    const amount = parseFloat(document.getElementById('simulatorAmount')?.value) || 0;
    if (amount > 0) {
        runSimulation();
    }
}

function runSimulation() {
    const amount = parseFloat(document.getElementById('simulatorAmount').value);
    const tierId = document.getElementById('simulatorTier')?.value || null;
    
    if (!amount || amount < 1) {
        showAlert('Please enter a valid investment amount.', 'warning');
        return;
    }
    
    const resultsDiv = document.getElementById('simulatorResults');
    resultsDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Calculating...</span>
            </div>
        </div>
    `;
    
    fetch(`/admin/investment/simulate`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            plan_id: planId,
            user_id: 1, // Default admin user for simulation
            amount: amount,
            tier_id: tierId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displaySimulationResults(data.simulation);
        } else {
            resultsDiv.innerHTML = `
                <div class="alert alert-danger">
                    <iconify-icon icon="solar:danger-circle-bold-duotone" class="me-2"></iconify-icon>
                    ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <iconify-icon icon="solar:danger-circle-bold-duotone" class="me-2"></iconify-icon>
                Error running simulation
            </div>
        `;
    });
}

function displaySimulationResults(simulation) {
    const resultsDiv = document.getElementById('simulatorResults');
    
    let tierInfo = '';
    if (simulation.tier) {
        tierInfo = `
            <div class="col-md-6">
                <div class="card bg-info-subtle">
                    <div class="card-body text-center">
                        <h6 class="text-info mb-1">Selected Tier</h6>
                        <h5 class="fw-bold">Tier ${simulation.tier.level} - ${simulation.tier.name}</h5>
                        <small class="text-muted">${simulation.tier.interest_rate}% interest</small>
                    </div>
                </div>
            </div>
        `;
    }
    
    resultsDiv.innerHTML = `
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card bg-primary-subtle">
                    <div class="card-body text-center">
                        <h6 class="text-primary mb-1">Investment Amount</h6>
                        <h5 class="fw-bold">${simulation.investment.formatted_amount}</h5>
                    </div>
                </div>
            </div>
            ${tierInfo}
            <div class="col-md-6">
                <div class="card bg-success-subtle">
                    <div class="card-body text-center">
                        <h6 class="text-success mb-1">Total Returns</h6>
                        <h5 class="fw-bold">${simulation.returns.formatted_total_return}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-warning-subtle">
                    <div class="card-body text-center">
                        <h6 class="text-warning mb-1">Final Amount</h6>
                        <h5 class="fw-bold">${simulation.returns.formatted_maturity_amount}</h5>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <h6 class="fw-semibold mb-3">Return Schedule</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${simulation.schedule.map(item => `
                            <tr>
                                <td>${item.period}</td>
                                <td>${item.formatted_due_date}</td>
                                <td>
                                    <span class="badge ${item.type === 'capital' ? 'bg-primary' : 'bg-success'}">
                                        ${item.type === 'capital' ? 'Capital' : 'Interest'}
                                    </span>
                                </td>
                                <td class="fw-semibold">${item.formatted_amount}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    alertDiv.innerHTML = `
        <iconify-icon icon="solar:${type === 'success' ? 'check-circle' : type === 'warning' ? 'info-circle' : 'danger-circle'}-bold-duotone" class="me-2"></iconify-icon>
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