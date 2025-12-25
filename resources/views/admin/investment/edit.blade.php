{{-- resources/views/admin/investment/edit.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'Edit Investment Plan', 'subTitle' => 'Update Investment Plan'])

@section('css')
<style>
    .plan-card {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 1.5rem;
        cursor: pointer;
        transition: border-color 0.2s;
        text-align: center;
    }
    .plan-card:hover {
        border-color: #0d6efd;
    }
    .plan-card.active {
        border-color: #0d6efd;
        background: #f8f9ff;
    }
    .section-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .section-header {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #dee2e6;
        border-radius: 7px 7px 0 0;
    }
    .tier-item {
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 1rem;
        background: white;
    }
    .tier-header {
        background: #f1f3f4;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    .commission-item {
        background: #fff9e6;
        border: 1px solid #ffc107;
        border-radius: 6px;
        margin-bottom: 1rem;
    }
    .commission-header {
        background: #fff3cd;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #ffc107;
    }
    .color-box {
        padding: 1rem;
        border: 2px solid #e9ecef;
        border-radius: 6px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s;
    }
    .color-box:hover {
        border-color: #0d6efd;
    }
    .color-box.selected {
        border-color: #0d6efd;
        background: #f8f9ff;
    }
    .hidden { display: none !important; }
    .commission-valid { color: #198754; font-weight: 600; }
    .commission-invalid { color: #dc3545; font-weight: 600; }
    .avatar-xl {
        width: 4rem;
        height: 4rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .avatar-title {
        font-size: 2rem;
    }
    .bg-primary-subtle {
        background-color: rgba(13, 110, 253, 0.1);
    }
    .bg-info-subtle {
        background-color: rgba(13, 202, 240, 0.1);
    }
    .danger-zone {
        background: #fff5f5;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
    }
    .mlm-enabled {
        background: #d1e7dd;
        border-color: #198754;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    
    <!-- Plan Header Info -->
    <div class="section-card mb-4">
        <div class="p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="fw-bold mb-1">{{ $investmentPlan->name }}</h4>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge {{ $investmentPlan->status_badge_class }}">
                            {{ ucfirst($investmentPlan->status) }}
                        </span>
                        @if($investmentPlan->is_tiered)
                            <span class="badge bg-info text-white">
                                <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-1"></iconify-icon>
                                Tiered Plan ({{ $investmentPlan->tiers->count() }} levels)
                            </span>
                        @else
                            <span class="badge bg-secondary text-white">
                                <iconify-icon icon="solar:star-bold-duotone" class="me-1"></iconify-icon>
                                Simple Plan
                            </span>
                        @endif
                        @if($investmentPlan->profit_sharing_enabled)
                            <span class="badge bg-warning text-dark">
                                <iconify-icon icon="solar:users-group-two-rounded-bold-duotone" class="me-1"></iconify-icon>
                                MLM Enabled
                            </span>
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <div class="small text-muted mb-1">Active Investments</div>
                    <div class="fw-bold">{{ number_format($investmentPlan->activeInvestments()->count() ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Type Selection -->
    <div class="section-card">
        <div class="section-header">
            <h5 class="mb-1">Plan Type Selection</h5>
            <small class="text-muted">You can change the plan type, but be careful with active plans</small>
        </div>
        <div class="p-4">
            @if($investmentPlan->activeInvestments()->count() > 0)
                <div class="alert alert-warning mb-3">
                    <iconify-icon icon="solar:warning-circle-bold-duotone" class="me-2"></iconify-icon>
                    <strong>Warning:</strong> This plan has {{ $investmentPlan->activeInvestments()->count() }} active investments. 
                    Changing the plan type may affect existing investors.
                </div>
            @endif
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="plan-card {{ !$investmentPlan->is_tiered ? 'active' : '' }}" id="simplePlanCard" onclick="selectPlanType('simple')">
                        <div class="avatar-xl bg-primary-subtle rounded-circle mx-auto mb-3">
                            <iconify-icon icon="solar:star-bold-duotone" class="avatar-title text-primary"></iconify-icon>
                        </div>
                        <h5>Simple Plan</h5>
                        <p class="text-muted">Single tier with fixed rate</p>
                        <ul class="list-unstyled small">
                            <li><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-1"></iconify-icon> Easy setup</li>
                            <li><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-1"></iconify-icon> Single interest rate</li>
                            <li><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-1"></iconify-icon> No MLM features</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="plan-card {{ $investmentPlan->is_tiered ? 'active' : '' }}" id="tieredPlanCard" onclick="selectPlanType('tiered')">
                        <div class="avatar-xl bg-info-subtle rounded-circle mx-auto mb-3">
                            <iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="avatar-title text-info"></iconify-icon>
                        </div>
                        <h5>Tiered Plan</h5>
                        <p class="text-muted">Multiple tiers with MLM support</p>
                        <ul class="list-unstyled small">
                            <li><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-1"></iconify-icon> Multiple tiers</li>
                            <li><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-1"></iconify-icon> Progressive rates</li>
                            <li><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success me-1"></iconify-icon> MLM commissions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            
            <!-- Basic Information -->
            <div class="section-card">
                <div class="section-header">
                    <h6 class="mb-0"><iconify-icon icon="solar:document-text-bold-duotone" class="me-2"></iconify-icon>Basic Information</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="planName" 
                                   value="{{ $investmentPlan->name }}" placeholder="Enter plan name" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="planDescription" rows="3" 
                                      placeholder="Plan description">{{ $investmentPlan->description }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Badge Text</label>
                            <input type="text" class="form-control" id="planBadge" 
                                   value="{{ $investmentPlan->badge }}" placeholder="e.g., Popular">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="planSortOrder" 
                                   value="{{ $investmentPlan->sort_order ?? 0 }}" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Configuration -->
            <div class="section-card">
                <div class="section-header">
                    <h6 class="mb-0"><iconify-icon icon="solar:settings-bold-duotone" class="me-2"></iconify-icon>Plan Configuration</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Interest Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="interestType" required>
                                <option value="">Select Type</option>
                                <option value="daily" {{ $investmentPlan->interest_type === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $investmentPlan->interest_type === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ $investmentPlan->interest_type === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="yearly" {{ $investmentPlan->interest_type === 'yearly' ? 'selected' : '' }}>Yearly</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration (Days) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="durationDays" 
                                   value="{{ $investmentPlan->duration_days }}" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Return Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="returnType" required>
                                <option value="">Select Type</option>
                                <option value="fixed" {{ $investmentPlan->return_type === 'fixed' ? 'selected' : '' }}>Fixed Interest</option>
                                <option value="compound" {{ $investmentPlan->return_type === 'compound' ? 'selected' : '' }}>Compound Interest</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="capitalReturn" 
                                       {{ $investmentPlan->capital_return ? 'checked' : '' }}>
                                <label class="form-check-label" for="capitalReturn">
                                    Return principal at maturity
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simple Plan Settings -->
            <div id="simplePlanSettings" class="section-card {{ $investmentPlan->is_tiered ? 'hidden' : '' }}">
                <div class="section-header">
                    <h6 class="mb-0"><iconify-icon icon="solar:wallet-money-bold-duotone" class="me-2"></iconify-icon>Simple Plan Settings</h6>
                </div>
                <div class="p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Minimum Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="minimumAmount" 
                                       value="{{ $investmentPlan->minimum_amount ?? '' }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Maximum Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="maximumAmount" 
                                       value="{{ $investmentPlan->maximum_amount ?? '' }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Interest Rate <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="interestRate" 
                                       value="{{ $investmentPlan->interest_rate ?? '' }}" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tiered Plan Settings -->
            <div id="tieredPlanSettings" class="section-card {{ !$investmentPlan->is_tiered ? 'hidden' : '' }}">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><iconify-icon icon="solar:layers-minimalistic-bold-duotone" class="me-2"></iconify-icon>Tiered Plan Settings</h6>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addTier()">
                        <iconify-icon icon="solar:add-circle-bold-duotone" class="me-1"></iconify-icon>Add Tier
                    </button>
                </div>
                <div class="p-4">
                    <div id="tiersContainer">
                        <!-- Existing tiers will be loaded here -->
                    </div>
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addDefaultTiers()">
                            <iconify-icon icon="solar:magic-stick-3-bold-duotone" class="me-1"></iconify-icon>Add Default Tiers
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearTiers()">
                            <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" class="me-1"></iconify-icon>Clear All
                        </button>
                    </div>
                </div>
            </div>

            <!-- MLM Commission Settings -->
            <div id="mlmSettings" class="section-card {{ !$investmentPlan->is_tiered ? 'hidden' : '' }}">
                <div class="section-header {{ $investmentPlan->profit_sharing_enabled ? 'mlm-enabled' : '' }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <iconify-icon icon="solar:users-group-two-rounded-bold-duotone" class="me-2"></iconify-icon>
                                MLM Commission Settings
                                @if($investmentPlan->profit_sharing_enabled)
                                    <span class="badge bg-success text-white ms-2">Currently Active</span>
                                @endif
                            </h6>
                            <small class="text-muted">Configure commission rates for referral levels</small>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @if($investmentPlan->profit_sharing_enabled && $investmentPlan->activeInvestments()->count() > 0)
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="quickToggleMLM()">
                                    <iconify-icon icon="solar:settings-bold-duotone" class="me-1"></iconify-icon>
                                    Quick Toggle
                                </button>
                            @endif
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enableMLM" 
                                       {{ $investmentPlan->profit_sharing_enabled ? 'checked' : '' }} onchange="toggleMLM()">
                                <label class="form-check-label" for="enableMLM">Enable MLM</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    
                    <!-- Legal Warning -->
                    <div class="alert alert-warning">
                        <iconify-icon icon="solar:danger-triangle-bold-duotone" class="me-2"></iconify-icon>
                        <strong>Legal Warning:</strong> MLM structures with investments may require regulatory approval.
                    </div>

                    @if($investmentPlan->activeInvestments()->count() > 0)
                        <div class="alert alert-info">
                            <iconify-icon icon="solar:info-circle-bold-duotone" class="me-2"></iconify-icon>
                            <strong>Active Investments:</strong> This plan has {{ $investmentPlan->activeInvestments()->count() }} 
                            active investments. Disabling MLM will affect future commissions only.
                        </div>
                    @endif

                    <div id="mlmContent" class="{{ $investmentPlan->profit_sharing_enabled ? '' : 'hidden' }}">
                        <!-- Commission Structure Overview -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-primary text-center">
                                    <div class="card-body py-3">
                                        <iconify-icon icon="solar:user-bold-duotone" class="text-primary fs-2 mb-2"></iconify-icon>
                                        <h6>Level 1</h6>
                                        <small class="text-muted">Direct Referral</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success text-center">
                                    <div class="card-body py-3">
                                        <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-success fs-2 mb-2"></iconify-icon>
                                        <h6>Level 2</h6>
                                        <small class="text-muted">Indirect Referral</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-info text-center">
                                    <div class="card-body py-3">
                                        <iconify-icon icon="solar:crown-bold-duotone" class="text-info fs-2 mb-2"></iconify-icon>
                                        <h6>Level 3</h6>
                                        <small class="text-muted">Third Level</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Commission Rates -->
                        <h6>Commission Rates by Tier</h6>
                        <p class="text-muted small mb-3">Set commission percentages for each tier (max 50% total per tier)</p>
                        
                        <div id="commissionsContainer">
                            <!-- Commission cards will be loaded here -->
                        </div>

                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDefaultCommissions()">
                                <iconify-icon icon="solar:magic-stick-3-bold-duotone" class="me-1"></iconify-icon>Default Rates
                            </button>
                        </div>

                        <!-- Commission Calculator -->
                        <div class="card border-info mt-4">
                            <div class="card-header">
                                <h6 class="mb-0"><iconify-icon icon="solar:calculator-bold-duotone" class="me-2"></iconify-icon>Commission Calculator</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small">Test Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="testAmount" value="1000" onchange="calculateCommissions()">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Select Tier</label>
                                        <select class="form-select" id="testTier" onchange="calculateCommissions()">
                                            <option value="">Choose tier...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small">Total Commission</label>
                                        <div class="alert alert-light mb-0" id="commissionResult">
                                            Select tier to calculate
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Plan Features -->
            <div class="section-card">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><iconify-icon icon="solar:star-circle-bold-duotone" class="me-2"></iconify-icon>Plan Features</h6>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addFeature()">
                        <iconify-icon icon="solar:add-circle-bold-duotone" class="me-1"></iconify-icon>Add Feature
                    </button>
                </div>
                <div class="p-4">
                    <div id="featuresContainer">
                        <!-- Existing features will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Status -->
            <div class="section-card">
                <div class="section-header">
                    <h6 class="mb-0"><iconify-icon icon="solar:eye-bold-duotone" class="me-2"></iconify-icon>Plan Status</h6>
                </div>
                <div class="p-4">
                    <label class="form-label">Plan Status</label>
                    <select class="form-select" id="planStatus">
                        <option value="active" {{ $investmentPlan->status === 'active' ? 'selected' : '' }}>Active - Live and accepting investments</option>
                        <option value="inactive" {{ $investmentPlan->status === 'inactive' ? 'selected' : '' }}>Inactive - Hidden from users</option>
                        <option value="paused" {{ $investmentPlan->status === 'paused' ? 'selected' : '' }}>Paused - Visible but closed</option>
                    </select>

                    @if($investmentPlan->isActive() && $investmentPlan->activeInvestments()->count() > 0)
                        <div class="alert alert-warning mt-3 small">
                            <iconify-icon icon="solar:warning-circle-bold-duotone" class="me-2"></iconify-icon>
                            <strong>Active Investments:</strong> Be careful when making changes to live plans.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Color Scheme -->
            <div class="section-card">
                <div class="section-header">
                    <h6 class="mb-0"><iconify-icon icon="solar:palette-bold-duotone" class="me-2"></iconify-icon>Color Scheme</h6>
                </div>
                <div class="p-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="color-box {{ $investmentPlan->color_scheme === 'primary' ? 'selected' : '' }}" 
                                 onclick="selectColor('primary')" data-color="primary">
                                <div class="badge bg-primary w-100 mb-1">Primary</div>
                                <small>Blue</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="color-box {{ $investmentPlan->color_scheme === 'success' ? 'selected' : '' }}" 
                                 onclick="selectColor('success')" data-color="success">
                                <div class="badge bg-success w-100 mb-1">Success</div>
                                <small>Green</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="color-box {{ $investmentPlan->color_scheme === 'warning' ? 'selected' : '' }}" 
                                 onclick="selectColor('warning')" data-color="warning">
                                <div class="badge bg-warning w-100 mb-1">Warning</div>
                                <small>Yellow</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="color-box {{ $investmentPlan->color_scheme === 'danger' ? 'selected' : '' }}" 
                                 onclick="selectColor('danger')" data-color="danger">
                                <div class="badge bg-danger w-100 mb-1">Danger</div>
                                <small>Red</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="section-card">
                <div class="p-4">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg" onclick="updatePlan()">
                            <iconify-icon icon="solar:check-circle-bold-duotone" class="me-1"></iconify-icon>Update Plan
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="saveDraft()">
                            <iconify-icon icon="solar:diskette-bold-duotone" class="me-1"></iconify-icon>Save as Draft
                        </button>
                        <a href="{{ route('admin.investment.index') }}" class="btn btn-outline-secondary">
                            <iconify-icon icon="solar:arrow-left-bold-duotone" class="me-1"></iconify-icon>Back to Plans
                        </a>
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            @if($investmentPlan->activeInvestments()->count() === 0)
                <div class="section-card danger-zone">
                    <div class="section-header bg-danger text-white">
                        <h6 class="mb-0"><iconify-icon icon="solar:danger-triangle-bold-duotone" class="me-2"></iconify-icon>Danger Zone</h6>
                    </div>
                    <div class="p-4">
                        <p class="text-muted mb-3">Permanently delete this investment plan. This action cannot be undone.</p>
                        <button type="button" class="btn btn-danger" onclick="deletePlan()">
                            <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone" class="me-1"></iconify-icon>Delete Plan
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
// Global variables
let currentPlanType = '{{ $investmentPlan->is_tiered ? "tiered" : "simple" }}';
let tierCounter = 0;
let selectedColorScheme = '{{ $investmentPlan->color_scheme }}';
let mlmEnabled = {{ $investmentPlan->profit_sharing_enabled ? 'true' : 'false' }};
let planId = {{ $investmentPlan->id }};

// Existing data
const existingPlan = @json($investmentPlan);
const existingTiers = @json($investmentPlan->tiers ?? []);
const existingFeatures = @json($investmentPlan->features ?? []);
const existingProfitSharing = @json($investmentPlan->profitSharingConfigs ?? []);

document.addEventListener('DOMContentLoaded', function() {
    initializeEditPage();
});

function initializeEditPage() {
    // Set the current plan type
    selectPlanType(currentPlanType);
    
    // Load existing data
    loadExistingTiers();
    loadExistingFeatures();
    
    if (mlmEnabled && currentPlanType === 'tiered') {
        loadExistingCommissions();
    }
    
    updateTierSelector();
}

function selectPlanType(type) {
    // Check for active investments before allowing change
    const activeInvestments = {{ $investmentPlan->activeInvestments()->count() }};
    if (activeInvestments > 0 && type !== currentPlanType) {
        if (!confirm(`This plan has ${activeInvestments} active investments. Changing the plan type may affect existing investors. Continue?`)) {
            return;
        }
    }
    
    // Update plan cards
    document.querySelectorAll('.plan-card').forEach(card => {
        card.classList.remove('active');
    });
    
    if (type === 'simple') {
        document.getElementById('simplePlanCard').classList.add('active');
        document.getElementById('simplePlanSettings').classList.remove('hidden');
        document.getElementById('tieredPlanSettings').classList.add('hidden');
        document.getElementById('mlmSettings').classList.add('hidden');
        mlmEnabled = false;
        document.getElementById('enableMLM').checked = false;
    } else {
        document.getElementById('tieredPlanCard').classList.add('active');
        document.getElementById('simplePlanSettings').classList.add('hidden');
        document.getElementById('tieredPlanSettings').classList.remove('hidden');
        document.getElementById('mlmSettings').classList.remove('hidden');
        
        if (document.getElementById('tiersContainer').children.length === 0) {
            loadExistingTiers();
        }
    }
    
    currentPlanType = type;
    updateCommissions();
}

function loadExistingTiers() {
    const container = document.getElementById('tiersContainer');
    container.innerHTML = '';
    tierCounter = 0;
    
    if (existingTiers && existingTiers.length > 0) {
        existingTiers.forEach(tierData => {
            addTierWithData(tierData);
        });
    }
}

function loadExistingFeatures() {
    const container = document.getElementById('featuresContainer');
    container.innerHTML = '';
    
    if (existingFeatures && existingFeatures.length > 0) {
        existingFeatures.forEach(feature => {
            addFeatureWithValue(feature);
        });
    } else {
        addFeatureWithValue('');
    }
}

function loadExistingCommissions() {
    setTimeout(() => {
        if (existingProfitSharing && existingProfitSharing.length > 0) {
            const profitSharingMap = {};
            existingProfitSharing.forEach(config => {
                profitSharingMap[config.investment_plan_tier_id] = config;
            });

            const commissionItems = document.querySelectorAll('.commission-item');
            commissionItems.forEach((commissionItem, index) => {
                const tierId = existingTiers[index]?.id;
                const profitConfig = profitSharingMap[tierId];
                
                if (profitConfig) {
                    commissionItem.querySelector('.commission-l1').value = profitConfig.level_1_commission || 0;
                    commissionItem.querySelector('.commission-l2').value = profitConfig.level_2_commission || 0;
                    commissionItem.querySelector('.commission-l3').value = profitConfig.level_3_commission || 0;
                    if (commissionItem.querySelector('.commission-cap')) {
                        commissionItem.querySelector('.commission-cap').value = profitConfig.max_commission_cap || '';
                    }
                    validateCommission(commissionItem.querySelector('.commission-l1'));
                }
            });
        }
    }, 500);
}

function addTierWithData(tierData) {
    const container = document.getElementById('tiersContainer');
    const tierLevel = tierData.tier_level || (container.children.length + 1);
    
    const tierDiv = document.createElement('div');
    tierDiv.className = 'tier-item';
    tierDiv.setAttribute('data-tier-db-id', tierData.id || '');
    tierDiv.innerHTML = `
        <div class="tier-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">Tier ${tierLevel}</span>
                <input type="text" class="form-control form-control-sm tier-name" 
                       value="${tierData.tier_name || `Tier ${tierLevel}`}" style="width: 120px;">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTier(this)">
                <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
            </button>
        </div>
        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small">Min Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control tier-min" value="${tierData.minimum_amount || ''}" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Max Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control tier-max" value="${tierData.maximum_amount || ''}" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Interest Rate</label>
                    <div class="input-group">
                        <input type="number" class="form-control tier-rate" value="${tierData.interest_rate || ''}" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">User Level Required</label>
                    <select class="form-select tier-level">
                        <option value="0" ${tierData.min_user_level == 0 ? 'selected' : ''}>TL-0</option>
                        <option value="1" ${tierData.min_user_level == 1 ? 'selected' : ''}>TL-1</option>
                        <option value="2" ${tierData.min_user_level == 2 ? 'selected' : ''}>TL-2</option>
                        <option value="3" ${tierData.min_user_level == 3 ? 'selected' : ''}>TL-3</option>
                        <option value="4" ${tierData.min_user_level == 4 ? 'selected' : ''}>TL-4</option>
                        <option value="5" ${tierData.min_user_level == 5 ? 'selected' : ''}>TL-5</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small">Description</label>
                    <textarea class="form-control tier-description" rows="2" 
                              placeholder="Tier description">${tierData.tier_description || ''}</textarea>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(tierDiv);
    tierCounter = Math.max(tierCounter, tierLevel);
}

function addTier() {
    const container = document.getElementById('tiersContainer');
    tierCounter = container.children.length + 1;
    
    const tierDiv = document.createElement('div');
    tierDiv.className = 'tier-item';
    tierDiv.innerHTML = `
        <div class="tier-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-secondary">Tier ${tierCounter}</span>
                <input type="text" class="form-control form-control-sm tier-name" 
                       value="Tier ${tierCounter}" style="width: 120px;">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTier(this)">
                <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
            </button>
        </div>
        <div class="p-3">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small">Min Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control tier-min" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Max Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control tier-max" step="0.01">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Interest Rate</label>
                    <div class="input-group">
                        <input type="number" class="form-control tier-rate" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">User Level Required</label>
                    <select class="form-select tier-level">
                        <option value="0">TL-0</option>
                        <option value="1">TL-1</option>
                        <option value="2">TL-2</option>
                        <option value="3">TL-3</option>
                        <option value="4">TL-4</option>
                        <option value="5">TL-5</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small">Description</label>
                    <textarea class="form-control tier-description" rows="2" placeholder="Tier description"></textarea>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(tierDiv);
    updateCommissions();
    updateTierSelector();
}

function removeTier(button) {
    const container = document.getElementById('tiersContainer');
    if (container.children.length <= 1) {
        showAlert('At least one tier is required', 'warning');
        return;
    }
    
    if (confirm('Remove this tier?')) {
        button.closest('.tier-item').remove();
        
        // Renumber remaining tiers
        const remainingTiers = container.querySelectorAll('.tier-item');
        remainingTiers.forEach((tier, index) => {
            const badge = tier.querySelector('.badge');
            const tierName = tier.querySelector('.tier-name');
            const newTierNumber = index + 1;
            
            badge.textContent = `Tier ${newTierNumber}`;
            if (tierName.value.startsWith('Tier ')) {
                tierName.value = `Tier ${newTierNumber}`;
            }
        });
        
        tierCounter = remainingTiers.length;
        updateCommissions();
        updateTierSelector();
    }
}

function addDefaultTiers() {
    document.getElementById('tiersContainer').innerHTML = '';
    tierCounter = 0;
    
    const defaultTiers = [
        { tier_level: 1, tier_name: 'Starter', minimum_amount: '1', maximum_amount: '99', interest_rate: '5', min_user_level: '0' },
        { tier_level: 2, tier_name: 'Bronze', minimum_amount: '100', maximum_amount: '499', interest_rate: '8', min_user_level: '1' },
        { tier_level: 3, tier_name: 'Silver', minimum_amount: '500', maximum_amount: '999', interest_rate: '12', min_user_level: '2' },
        { tier_level: 4, tier_name: 'Gold', minimum_amount: '1000', maximum_amount: '4999', interest_rate: '15', min_user_level: '3' }
    ];
    
    defaultTiers.forEach(tierData => {
        addTierWithData(tierData);
    });
    
    updateCommissions();
    updateTierSelector();
}

function clearTiers() {
    if (confirm('Clear all tiers?')) {
        document.getElementById('tiersContainer').innerHTML = '';
        document.getElementById('commissionsContainer').innerHTML = '';
        tierCounter = 0;
        updateTierSelector();
    }
}

function toggleMLM() {
    mlmEnabled = document.getElementById('enableMLM').checked;
    const mlmContent = document.getElementById('mlmContent');
    const header = document.querySelector('#mlmSettings .section-header');
    
    if (mlmEnabled) {
        mlmContent.classList.remove('hidden');
        header.classList.add('mlm-enabled');
        updateCommissions();
        showAlert('MLM commissions enabled', 'success');
    } else {
        mlmContent.classList.add('hidden');
        header.classList.remove('mlm-enabled');
        showAlert('MLM commissions disabled', 'info');
    }
}

function quickToggleMLM() {
    if (confirm(`Are you sure you want to ${mlmEnabled ? 'disable' : 'enable'} MLM for this plan?`)) {
        fetch(`/admin/investment/${planId}/toggle-profit-sharing`, {
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
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Error toggling MLM', 'danger');
        });
    }
}

function updateCommissions() {
    if (!mlmEnabled || currentPlanType !== 'tiered') return;
    
    const container = document.getElementById('commissionsContainer');
    container.innerHTML = '';
    
    const tiers = document.querySelectorAll('.tier-item');
    tiers.forEach((tier, index) => {
        const tierName = tier.querySelector('.tier-name').value || `Tier ${index + 1}`;
        
        const commissionDiv = document.createElement('div');
        commissionDiv.className = 'commission-item';
        commissionDiv.innerHTML = `
            <div class="commission-header">
                <h6 class="mb-0">
                    <span class="badge bg-warning text-dark me-2">${index + 1}</span>
                    ${tierName} - Commission Rates
                </h6>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Level 1 (%)</label>
                        <input type="number" class="form-control commission-l1" value="5" 
                               min="0" max="25" step="0.1" onchange="validateCommission(this)">
                        <small class="text-muted">Direct referral</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Level 2 (%)</label>
                        <input type="number" class="form-control commission-l2" value="3" 
                               min="0" max="25" step="0.1" onchange="validateCommission(this)">
                        <small class="text-muted">Indirect referral</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Level 3 (%)</label>
                        <input type="number" class="form-control commission-l3" value="2" 
                               min="0" max="25" step="0.1" onchange="validateCommission(this)">
                        <small class="text-muted">Third level</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Total</label>
                        <input type="text" class="form-control commission-total" value="10%" readonly>
                        <small class="commission-status commission-valid">Valid</small>
                    </div>
                </div>
                <div class="row g-3 mt-2">
                    <div class="col-md-6">
                        <label class="form-label small">Commission Cap (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control commission-cap" step="0.01" placeholder="No limit">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(commissionDiv);
    });
    
    // Load existing commission data
    loadExistingCommissions();
    updateTierSelector();
}

function validateCommission(input) {
    const commissionItem = input.closest('.commission-item');
    const l1 = parseFloat(commissionItem.querySelector('.commission-l1').value) || 0;
    const l2 = parseFloat(commissionItem.querySelector('.commission-l2').value) || 0;
    const l3 = parseFloat(commissionItem.querySelector('.commission-l3').value) || 0;
    const total = l1 + l2 + l3;
    
    const totalField = commissionItem.querySelector('.commission-total');
    const statusField = commissionItem.querySelector('.commission-status');
    
    totalField.value = total.toFixed(1) + '%';
    
    if (total > 50) {
        statusField.textContent = 'Exceeds 50% limit!';
        statusField.className = 'commission-status commission-invalid';
        commissionItem.style.borderColor = '#dc3545';
    } else {
        statusField.textContent = 'Valid';
        statusField.className = 'commission-status commission-valid';
        commissionItem.style.borderColor = '#ffc107';
    }
}

function setDefaultCommissions() {
    const commissions = document.querySelectorAll('.commission-item');
    const defaultRates = [
        { l1: 5, l2: 3, l3: 2 },
        { l1: 7, l2: 4, l3: 3 },
        { l1: 9, l2: 5, l3: 3 },
        { l1: 12, l2: 7, l3: 4 }
    ];
    
    commissions.forEach((commission, index) => {
        const rates = defaultRates[Math.min(index, 3)];
        commission.querySelector('.commission-l1').value = rates.l1;
        commission.querySelector('.commission-l2').value = rates.l2;
        commission.querySelector('.commission-l3').value = rates.l3;
        validateCommission(commission.querySelector('.commission-l1'));
    });
    
    showAlert('Default commission rates applied', 'success');
}

function updateTierSelector() {
    const selector = document.getElementById('testTier');
    if (!selector) return;
    
    selector.innerHTML = '<option value="">Choose tier...</option>';
    
    const tiers = document.querySelectorAll('.tier-item');
    tiers.forEach((tier, index) => {
        const tierName = tier.querySelector('.tier-name').value || `Tier ${index + 1}`;
        selector.innerHTML += `<option value="${index}">${tierName}</option>`;
    });
}

function calculateCommissions() {
    const amount = parseFloat(document.getElementById('testAmount').value) || 0;
    const tierIndex = document.getElementById('testTier').value;
    const result = document.getElementById('commissionResult');
    
    if (!amount || tierIndex === '' || !mlmEnabled) {
        result.innerHTML = 'Select tier to calculate';
        return;
    }
    
    const commissionItems = document.querySelectorAll('.commission-item');
    if (tierIndex < commissionItems.length) {
        const commission = commissionItems[tierIndex];
        const l1 = parseFloat(commission.querySelector('.commission-l1').value) || 0;
        const l2 = parseFloat(commission.querySelector('.commission-l2').value) || 0;
        const l3 = parseFloat(commission.querySelector('.commission-l3').value) || 0;
        
        const total = ((l1 + l2 + l3) * amount) / 100;
        
        result.innerHTML = `
            <strong>$${total.toFixed(2)}</strong><br>
            <small>L1: $${((l1 * amount) / 100).toFixed(2)} | L2: $${((l2 * amount) / 100).toFixed(2)} | L3: $${((l3 * amount) / 100).toFixed(2)}</small>
        `;
    }
}

function addFeatureWithValue(value) {
    const container = document.getElementById('featuresContainer');
    const featureDiv = document.createElement('div');
    featureDiv.className = 'input-group mb-2';
    featureDiv.innerHTML = `
        <span class="input-group-text"><iconify-icon icon="solar:check-circle-bold-duotone" class="text-success"></iconify-icon></span>
        <input type="text" class="form-control feature-input" value="${value}" placeholder="Enter feature">
        <button type="button" class="btn btn-outline-danger" onclick="removeFeature(this)">
            <iconify-icon icon="solar:trash-bin-minimalistic-bold-duotone"></iconify-icon>
        </button>
    `;
    container.appendChild(featureDiv);
}

function addFeature() {
    addFeatureWithValue('');
}

function removeFeature(button) {
    const container = document.getElementById('featuresContainer');
    if (container.children.length > 1) {
        button.closest('.input-group').remove();
    }
}

function selectColor(color) {
    document.querySelectorAll('.color-box').forEach(box => {
        box.classList.remove('selected');
    });
    event.target.closest('.color-box').classList.add('selected');
    selectedColorScheme = color;
}

function updatePlan() {
    const planData = {
        name: document.getElementById('planName').value.trim(),
        description: document.getElementById('planDescription').value.trim(),
        badge: document.getElementById('planBadge').value.trim(),
        sort_order: parseInt(document.getElementById('planSortOrder').value) || 0,
        interest_type: document.getElementById('interestType').value,
        duration_days: parseInt(document.getElementById('durationDays').value),
        return_type: document.getElementById('returnType').value,
        capital_return: document.getElementById('capitalReturn').checked,
        status: document.getElementById('planStatus').value,
        color_scheme: selectedColorScheme,
        is_tiered: currentPlanType === 'tiered',
        profit_sharing_enabled: mlmEnabled
    };
    
    // Validate basic fields
    if (!planData.name || !planData.interest_type || !planData.duration_days || !planData.return_type) {
        showAlert('Please fill in all required fields', 'danger');
        return;
    }
    
    // Get features
    const features = [];
    document.querySelectorAll('.feature-input').forEach(input => {
        if (input.value.trim()) {
            features.push(input.value.trim());
        }
    });
    planData.features = features;
    
    if (currentPlanType === 'simple') {
        planData.minimum_amount = parseFloat(document.getElementById('minimumAmount').value);
        planData.maximum_amount = parseFloat(document.getElementById('maximumAmount').value);
        planData.interest_rate = parseFloat(document.getElementById('interestRate').value);
        
        if (!planData.minimum_amount || !planData.maximum_amount || !planData.interest_rate) {
            showAlert('Please fill in all investment fields', 'danger');
            return;
        }
    } else {
        // Tiered plan data
        const tiers = [];
        const tierItems = document.querySelectorAll('.tier-item');
        
        if (tierItems.length === 0) {
            showAlert('Please add at least one tier', 'danger');
            return;
        }
        
        let validTiers = true;
        tierItems.forEach((tierItem, index) => {
            const tierName = tierItem.querySelector('.tier-name').value.trim();
            const minAmount = parseFloat(tierItem.querySelector('.tier-min').value);
            const maxAmount = parseFloat(tierItem.querySelector('.tier-max').value);
            const rate = parseFloat(tierItem.querySelector('.tier-rate').value);
            const level = parseInt(tierItem.querySelector('.tier-level').value);
            const description = tierItem.querySelector('.tier-description').value.trim();
            const dbId = tierItem.getAttribute('data-tier-db-id');
            
            if (!tierName || !minAmount || !maxAmount || !rate) {
                validTiers = false;
                return;
            }
            
            tiers.push({
                id: dbId || null,
                tier_level: index + 1, // Store as 1-based
                tier_name: tierName,
                minimum_amount: minAmount,
                maximum_amount: maxAmount,
                interest_rate: rate,
                min_user_level: level,
                tier_description: description,
                is_active: true,
                sort_order: index
            });
        });
        
        if (!validTiers) {
            showAlert('Please complete all tier information', 'danger');
            return;
        }
        
        planData.tiers = tiers;
        
        // MLM commissions
        if (mlmEnabled) {
            const commissions = [];
            const commissionItems = document.querySelectorAll('.commission-item');
            
            commissionItems.forEach((commissionItem, index) => {
                const l1 = parseFloat(commissionItem.querySelector('.commission-l1').value) || 0;
                const l2 = parseFloat(commissionItem.querySelector('.commission-l2').value) || 0;
                const l3 = parseFloat(commissionItem.querySelector('.commission-l3').value) || 0;
                const cap = parseFloat(commissionItem.querySelector('.commission-cap').value) || null;
                
                if (l1 + l2 + l3 > 50) {
                    showAlert(`Tier ${index + 1} commission exceeds 50% limit`, 'danger');
                    return;
                }
                
                commissions.push({
                    tier_index: index,
                    tier_id: existingTiers[index]?.id || null,
                    level_1_commission: l1,
                    level_2_commission: l2,
                    level_3_commission: l3,
                    max_commission_cap: cap
                });
            });
            
            planData.profit_sharing_configs = commissions;
        }
    }
    
    // Submit data
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    button.disabled = true;
    
    fetch(`{{ route("admin.investment.index") }}/${planId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(planData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Investment plan updated successfully!', 'success');
            setTimeout(() => {
                window.location.href = '{{ route("admin.investment.index") }}';
            }, 1500);
        } else {
            showAlert(data.message || 'Error updating plan', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error occurred', 'danger');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function saveDraft() {
    document.getElementById('planStatus').value = 'inactive';
    updatePlan();
}

function deletePlan() {
    if (!confirm('Are you sure you want to delete this investment plan? This action cannot be undone.')) return;
    
    fetch(`{{ route("admin.investment.index") }}/${planId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Plan deleted successfully!', 'success');
            setTimeout(() => {
                window.location.href = '{{ route("admin.investment.index") }}';
            }, 1500);
        } else {
            showAlert(data.message || 'Error deleting plan', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Network error occurred', 'danger');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 350px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 4000);
}
</script>
@endsection