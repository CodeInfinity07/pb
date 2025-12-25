@extends('admin.layouts.vertical', ['title' => 'Commission Settings', 'subTitle' => 'Admin'])

@section('content')

{{-- Header Section --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Commission Settings</h4>
                        <p class="text-muted mb-0">Manage referral commission tiers and requirements</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-info btn-sm" onclick="updateUserTiers()">
                            Update User Tiers
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="showAddTierModal()">
                            Add New Tier
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="exportSettings()">
                                    <iconify-icon icon="iconamoon:download-duotone" class="me-2"></iconify-icon>Export CSV
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="showCalculator()">
                                    <iconify-icon icon="iconamoon:calculator-duotone" class="me-2"></iconify-icon>Commission Calculator
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-warning" href="#" onclick="seedDefaultTiers()">
                                    <iconamoon:database-duotone" class="me-2"></iconify-icon>Seed Defaults
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Platform Statistics --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="iconamoon:profile-duotone" class="text-primary" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Total Users</h6>
                <h5 class="mb-0">{{ number_format($totalUsers ?? 0) }}</h5>
                <small class="text-muted">Platform wide</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:check-box-rounded" class="text-success" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Active Users</h6>
                <h5 class="mb-0">{{ number_format($totalActiveUsers ?? 0) }}</h5>
                <small class="text-success">{{ $totalUsers > 0 ? number_format(($totalActiveUsers / $totalUsers) * 100, 1) : 0 }}% active</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="material-symbols:attach-money" class="text-warning" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Commissions Paid</h6>
                <h5 class="mb-0">${{ number_format($totalCommissionsPaid ?? 0, 2) }}</h5>
                <small class="text-muted">All time</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <iconify-icon icon="mdi:certificate" class="text-info" style="font-size: 2rem;"></iconify-icon>
                </div>
                <h6 class="text-muted mb-1">Commission Tiers</h6>
                <h5 class="mb-0">{{ $commissionTiers->count() ?? 0 }}</h5>
                <small class="text-muted">{{ $commissionTiers->where('is_active', true)->count() ?? 0 }} active</small>
            </div>
        </div>
    </div>
</div>

{{-- Commission Simulation --}}
@if(isset($commissionSimulation) && is_array($commissionSimulation))
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Commission Simulation (${{ number_format($simulationAmount ?? 1000) }} Transaction)</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($commissionSimulation as $level => $simulation)
                        @php $tier = $commissionTiers->where('level', $level)->first(); @endphp
                        @if($tier)
                        <div class="col-lg-3 col-md-6">
                            <div class="simulation-card border rounded p-3" style="border-color: {{ $tier->tier_color ?? '#6c757d' }} !important;">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="tier-indicator me-2" style="background-color: {{ $tier->tier_color ?? '#6c757d' }};"></div>
                                    <h6 class="mb-0">{{ $tier->name ?? 'Tier ' . $level }}</h6>
                                </div>
                                <div class="simulation-details">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Level 1:</small>
                                        <strong>${{ number_format($simulation['level_1'] ?? 0, 2) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Level 2:</small>
                                        <strong>${{ number_format($simulation['level_2'] ?? 0, 2) }}</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Level 3:</small>
                                        <strong>${{ number_format($simulation['level_3'] ?? 0, 2) }}</strong>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong class="text-success">${{ number_format($simulation['total'] ?? 0, 2) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Commission Tiers Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Commission Tiers</h5>
            </div>
            
            @if($commissionTiers && $commissionTiers->count() > 0)
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th>Tier</th>
                                <th>Requirements</th>
                                <th>Commission Rates</th>
                                <th>Users</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commissionTiers as $tier)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="tier-indicator me-2" style="background-color: {{ $tier->tier_color ?? '#6c757d' }};"></div>
                                        <div>
                                            <h6 class="mb-1">{{ $tier->name ?? 'Tier ' . $tier->level }}</h6>
                                            <small class="text-muted">Level {{ $tier->level }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="requirements-list">
                                        <div class="requirement-item">
                                            <iconify-icon icon="material-symbols:attach-money" class="text-success me-1"></iconify-icon>
                                            <small>${{ number_format($tier->min_investment ?? 0, 0) }} investment</small>
                                        </div>
                                        <div class="requirement-item">
                                            <iconify-icon icon="iconamoon:profile-add-duotone" class="text-primary me-1"></iconify-icon>
                                            <small>{{ $tier->min_direct_referrals ?? 0 }} direct referrals</small>
                                        </div>
                                        <div class="requirement-item">
                                            <iconify-icon icon="iconamoon:hierarchy-duotone" class="text-info me-1"></iconify-icon>
                                            <small>{{ $tier->min_indirect_referrals ?? 0 }} indirect referrals</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="commission-rates">
                                        <span class="badge bg-primary-subtle text-primary">L1: {{ $tier->commission_level_1 ?? 0 }}%</span>
                                        <span class="badge bg-secondary-subtle text-secondary">L2: {{ $tier->commission_level_2 ?? 0 }}%</span>
                                        <span class="badge bg-info-subtle text-info">L3: {{ $tier->commission_level_3 ?? 0 }}%</span>
                                        <div class="mt-1">
                                            <small class="text-muted">Total: {{ number_format(($tier->commission_level_1 ?? 0) + ($tier->commission_level_2 ?? 0) + ($tier->commission_level_3 ?? 0), 1) }}%</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-center">
                                        <h6 class="mb-0">{{ number_format($tier->users_count ?? 0) }}</h6>
                                        <small class="text-muted">users</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" 
                                               {{ ($tier->is_active ?? false) ? 'checked' : '' }}
                                               onchange="toggleTierStatus({{ $tier->id }}, this)">
                                        <label class="form-check-label">
                                            <span class="badge {{ ($tier->is_active ?? false) ? 'bg-success' : 'bg-secondary' }}-subtle text-{{ ($tier->is_active ?? false) ? 'success' : 'secondary' }}">
                                                {{ ($tier->is_active ?? false) ? 'Active' : 'Inactive' }}
                                            </span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="editTier({{ $tier->id }})">
                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Tier
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="calculatePreview({{ $tier->id }})">
                                                <iconify-icon icon="iconamoon:calculator-duotone" class="me-2"></iconify-icon>Calculate Preview
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteTier({{ $tier->id }}, '{{ $tier->name ?? 'Tier' }}')">
                                                <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:certificate-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Commission Tiers Found</h6>
                    <p class="text-muted">Create your first commission tier to get started.</p>
                    <button type="button" class="btn btn-primary" onclick="showAddTierModal()">
                        Add First Tier
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Add/Edit Tier Modal --}}
<div class="modal fade" id="tierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tierModalTitle">Add New Commission Tier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="tierForm">
                @csrf
                <input type="hidden" id="tierId" name="id">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tier Level <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tierLevel" name="level" min="1" required>
                            <small class="form-text text-muted">Unique tier level number</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tier Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tierName" name="name" placeholder="e.g., Bronze, Silver, Gold" required>
                        </div>
                        
                        <div class="col-12"><hr></div>
                        <div class="col-12"><h6>Requirements</h6></div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Minimum Investment <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="minInvestment" name="min_investment" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Direct Referrals <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="minDirectReferrals" name="min_direct_referrals" min="0" required>
                            <small class="form-text text-muted">Level 1 referrals required</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Indirect Referrals <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="minIndirectReferrals" name="min_indirect_referrals" min="0" required>
                            <small class="form-text text-muted">Level 2+3 referrals required</small>
                        </div>
                        
                        <div class="col-12"><hr></div>
                        <div class="col-12"><h6>Commission Rates (%)</h6></div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Level 1 Commission <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="commissionLevel1" name="commission_level_1" min="0" max="100" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">Direct referrals commission</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Level 2 Commission <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="commissionLevel2" name="commission_level_2" min="0" max="100" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">2nd level referrals commission</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Level 3 Commission <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="commissionLevel3" name="commission_level_3" min="0" max="100" step="0.01" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="form-text text-muted">3rd level referrals commission</small>
                        </div>
                        
                        <div class="col-12"><hr></div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tier Color</label>
                            <input type="color" class="form-control form-control-color" id="tierColor" name="color" value="#6c757d">
                            <small class="form-text text-muted">Color for UI display</small>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                <label class="form-check-label">
                                    Active tier
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="tierDescription" name="description" rows="3" placeholder="Optional tier description..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">Create Tier</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Commission Calculator Modal --}}
<div class="modal fade" id="calculatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Commission Calculator</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Transaction Amount</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="calculatorAmount" min="0.01" step="0.01" value="1000">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Tier</label>
                    <select class="form-select" id="calculatorTier">
                        <option value="">Select a tier...</option>
                        @if(isset($commissionTiers))
                            @foreach($commissionTiers->where('is_active', true) as $tier)
                            <option value="{{ $tier->id }}">{{ $tier->name ?? 'Tier' }} (Level {{ $tier->level }})</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div id="calculatorResults" class="d-none">
                    <div class="alert alert-info">
                        <h6>Commission Breakdown:</h6>
                        <div id="commissionBreakdown"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="calculateCommission()">Calculate</button>
            </div>
        </div>
    </div>
</div>

{{-- Hidden Tier Data for JavaScript --}}
<script type="application/json" id="tierData">
{!! json_encode($commissionTiers->map(function($tier) {
    return [
        'id' => $tier->id,
        'level' => $tier->level,
        'name' => $tier->name,
        'min_investment' => $tier->min_investment,
        'min_direct_referrals' => $tier->min_direct_referrals,
        'min_indirect_referrals' => $tier->min_indirect_referrals,
        'commission_level_1' => $tier->commission_level_1,
        'commission_level_2' => $tier->commission_level_2,
        'commission_level_3' => $tier->commission_level_3,
        'color' => $tier->color ?? '#6c757d',
        'is_active' => $tier->is_active,
        'description' => $tier->description
    ];
})->keyBy('id')) !!}
</script>

@endsection

@section('script')
<script>
// Global variables
let isEditMode = false;
let currentTierId = null;
let tierDataStore = {};

// Initialize tier data store
document.addEventListener('DOMContentLoaded', function() {
    const tierDataScript = document.getElementById('tierData');
    if (tierDataScript) {
        try {
            tierDataStore = JSON.parse(tierDataScript.textContent);
        } catch (e) {
            console.error('Failed to parse tier data:', e);
            tierDataStore = {};
        }
    }
});

// Show add tier modal
function showAddTierModal() {
    isEditMode = false;
    currentTierId = null;
    
    document.getElementById('tierModalTitle').textContent = 'Add New Commission Tier';
    document.getElementById('submitBtn').textContent = 'Create Tier';
    document.getElementById('tierForm').reset();
    document.getElementById('tierId').value = '';
    
    // Reset form values
    document.getElementById('tierColor').value = '#6c757d';
    document.getElementById('isActive').checked = true;
    
    const modal = new bootstrap.Modal(document.getElementById('tierModal'));
    modal.show();
}

// Edit tier function
function editTier(tierId) {
    isEditMode = true;
    currentTierId = tierId;
    
    // Get tier data from store
    const tierData = tierDataStore[tierId];
    if (!tierData) {
        showAlert('Tier data not found', 'danger');
        return;
    }
    
    document.getElementById('tierModalTitle').textContent = 'Edit Commission Tier';
    document.getElementById('submitBtn').textContent = 'Update Tier';
    document.getElementById('tierId').value = tierId;
    
    // Populate form with tier data
    document.getElementById('tierLevel').value = tierData.level || '';
    document.getElementById('tierName').value = tierData.name || '';
    document.getElementById('minInvestment').value = tierData.min_investment || 0;
    document.getElementById('minDirectReferrals').value = tierData.min_direct_referrals || 0;
    document.getElementById('minIndirectReferrals').value = tierData.min_indirect_referrals || 0;
    document.getElementById('commissionLevel1').value = tierData.commission_level_1 || 0;
    document.getElementById('commissionLevel2').value = tierData.commission_level_2 || 0;
    document.getElementById('commissionLevel3').value = tierData.commission_level_3 || 0;
    document.getElementById('tierColor').value = tierData.color || '#6c757d';
    document.getElementById('isActive').checked = tierData.is_active || false;
    document.getElementById('tierDescription').value = tierData.description || '';
    
    const modal = new bootstrap.Modal(document.getElementById('tierModal'));
    modal.show();
}

// Handle form submission
document.getElementById('tierForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.textContent;
    
    try {
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        
        const formData = new FormData(this);
        
        // Convert FormData to regular object
        const data = {};
        for (let [key, value] of formData.entries()) {
            if (key !== '_token' && key !== 'id') {
                if (key === 'is_active') {
                    data[key] = document.getElementById('isActive').checked;
                } else {
                    data[key] = value;
                }
            }
        }
        
        // Ensure is_active is set
        data.is_active = document.getElementById('isActive').checked;
        
        // Build URL and method
        let url, method;
        if (isEditMode && currentTierId) {
            url = '/admin/commission/' + currentTierId;
            method = 'PUT';
        } else {
            url = '/admin/commission';
            method = 'POST';
        }
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                ...(method === 'PUT' && { 'X-HTTP-Method-Override': 'PUT' })
            },
            body: JSON.stringify(data)
        });
        
        let result;
        try {
            result = await response.json();
        } catch (jsonError) {
            console.error('Failed to parse JSON response:', jsonError);
            throw new Error('Invalid response format');
        }
        
        if (response.ok) {
            if (result.success) {
                // Close modal
                const modalInstance = bootstrap.Modal.getInstance(document.getElementById('tierModal'));
                if (modalInstance) {
                    modalInstance.hide();
                }
                
                showAlert(result.message || 'Operation completed successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert(result.message || 'Operation failed', 'danger');
            }
        } else {
            // Handle different error status codes
            if (response.status === 422 && result && result.errors) {
                const errors = result.errors;
                let errorMessage = 'Validation failed:\n';
                for (const [field, messages] of Object.entries(errors)) {
                    if (Array.isArray(messages)) {
                        errorMessage += `${field}: ${messages.join(', ')}\n`;
                    } else {
                        errorMessage += `${field}: ${messages}\n`;
                    }
                }
                showAlert(errorMessage, 'danger');
            } else if (response.status === 419) {
                showAlert('Session expired. Please refresh the page and try again.', 'warning');
            } else if (response.status === 404) {
                showAlert('Tier not found. Please refresh the page and try again.', 'danger');
            } else {
                showAlert(result?.message || `Error: ${response.status} ${response.statusText}`, 'danger');
            }
        }
        
    } catch (error) {
        console.error('Form submission error:', error);
        
        if (error.name === 'TypeError' && error.message.includes('fetch')) {
            showAlert('Network error. Please check your connection and try again.', 'danger');
        } else {
            showAlert(error.message || 'An unexpected error occurred', 'danger');
        }
    } finally {
        // Always restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});

// Toggle tier status
function toggleTierStatus(tierId, checkbox) {
    const originalChecked = checkbox.checked;
    
    fetch('/admin/commission/' + tierId + '/toggle-status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Update badge
            const badge = checkbox.parentElement.querySelector('.badge');
            if (data.is_active) {
                badge.className = 'badge bg-success-subtle text-success';
                badge.textContent = 'Active';
                checkbox.checked = true;
            } else {
                badge.className = 'badge bg-secondary-subtle text-secondary';
                badge.textContent = 'Inactive';
                checkbox.checked = false;
            }
        } else {
            checkbox.checked = originalChecked; // Revert
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        checkbox.checked = originalChecked; // Revert
        showAlert('Failed to update tier status', 'danger');
    });
}

// Delete tier
function deleteTier(tierId, tierName) {
    if (confirm(`Are you sure you want to delete the "${tierName}" tier? This action cannot be undone.`)) {
        fetch('/admin/commission/' + tierId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to delete tier', 'danger');
        });
    }
}

// Update user tiers
function updateUserTiers() {
    if (confirm('This will update all users to their appropriate tiers based on their qualifications. Continue?')) {
        const btn = event.target;
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
        
        fetch("{{ route('admin.commission.update-user-tiers') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(() => location.reload(), 2000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to update user tiers', 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        });
    }
}

// Show calculator
function showCalculator() {
    const modal = new bootstrap.Modal(document.getElementById('calculatorModal'));
    modal.show();
}

// Calculate commission
function calculateCommission() {
    const amount = document.getElementById('calculatorAmount').value;
    const tierId = document.getElementById('calculatorTier').value;
    
    if (!amount || !tierId) {
        showAlert('Please enter amount and select tier', 'warning');
        return;
    }
    
    fetch("{{ route('admin.commission.calculate-preview') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ 
            amount: parseFloat(amount), 
            tier_id: parseInt(tierId) 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCalculationResults(data.data);
        } else {
            showAlert(data.message || 'Calculation failed', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to calculate commission', 'danger');
    });
}

// Display calculation results
function displayCalculationResults(data) {
    const breakdown = `
        <div class="row g-2 mb-2">
            <div class="col-6"><strong>Transaction Amount:</strong></div>
            <div class="col-6 text-end">$${parseFloat(data.amount).toFixed(2)}</div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">Level 1 Commission:</div>
            <div class="col-6 text-end">$${parseFloat(data.commissions.level_1).toFixed(2)}</div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">Level 2 Commission:</div>
            <div class="col-6 text-end">$${parseFloat(data.commissions.level_2).toFixed(2)}</div>
        </div>
        <div class="row g-2 mb-2">
            <div class="col-6">Level 3 Commission:</div>
            <div class="col-6 text-end">$${parseFloat(data.commissions.level_3).toFixed(2)}</div>
        </div>
        <hr>
        <div class="row g-2 mb-2">
            <div class="col-6"><strong>Total Commission:</strong></div>
            <div class="col-6 text-end"><strong class="text-success">$${parseFloat(data.commissions.total).toFixed(2)}</strong></div>
        </div>
        <div class="row g-2">
            <div class="col-6"><strong>Remaining Amount:</strong></div>
            <div class="col-6 text-end"><strong>$${parseFloat(data.commissions.remaining).toFixed(2)}</strong></div>
        </div>
    `;
    
    document.getElementById('commissionBreakdown').innerHTML = breakdown;
    document.getElementById('calculatorResults').classList.remove('d-none');
}

// Calculate preview for specific tier
function calculatePreview(tierId) {
    document.getElementById('calculatorTier').value = tierId;
    showCalculator();
}

// Export settings
function exportSettings() {
    window.open("{{ route('admin.commission.export') }}", '_blank');
}

// Seed default tiers
function seedDefaultTiers() {
    if (confirm('This will create default commission tiers. Existing tiers will be updated. Continue?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<iconify-icon icon="iconamoon:loading-duotone" class="me-2"></iconify-icon>Seeding...';
        
        fetch("{{ route('admin.commission.seed-default') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to seed default tiers', 'danger');
        })
        .finally(() => {
            btn.innerHTML = originalText;
        });
    }
}

// Alert function
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-floating');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed alert-floating`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px;';
    alertDiv.innerHTML = `
        <div>${message}</div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }
    }, 5000);
}
</script>

<style>
/* Tier indicator */
.tier-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Simulation cards */
.simulation-card {
    transition: all 0.2s ease;
    border-width: 2px !important;
}

.simulation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Requirements list */
.requirements-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.requirement-item {
    display: flex;
    align-items: center;
    font-size: 0.875rem;
}

/* Commission rates */
.commission-rates {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

/* Badge subtle styling */
.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
    border: 1px solid transparent;
}

.bg-primary-subtle { 
    background-color: rgba(13, 110, 253, 0.1) !important; 
    border-color: rgba(13, 110, 253, 0.2) !important; 
}

.bg-secondary-subtle { 
    background-color: rgba(108, 117, 125, 0.1) !important; 
    border-color: rgba(108, 117, 125, 0.2) !important; 
}

.bg-success-subtle { 
    background-color: rgba(25, 135, 84, 0.1) !important; 
    border-color: rgba(25, 135, 84, 0.2) !important; 
}

.bg-info-subtle { 
    background-color: rgba(13, 202, 240, 0.1) !important; 
    border-color: rgba(13, 202, 240, 0.2) !important; 
}

/* Form improvements */
.form-control-color {
    width: 3rem;
    height: calc(2.25rem + 2px);
}

/* Card enhancements */
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

/* Modal improvements */
.modal-lg {
    max-width: 800px;
}

.modal-content {
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.125em;
}

/* Alert improvements */
.alert-floating {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: none;
    border-radius: 8px;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .commission-rates {
        flex-direction: column;
    }
    
    .requirements-list {
        font-size: 0.8rem;
    }
    
    .simulation-card {
        margin-bottom: 1rem;
    }
    
    .modal-lg {
        max-width: 95%;
        margin: 1rem auto;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Animation improvements */
.btn {
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}
</style>
@endsection