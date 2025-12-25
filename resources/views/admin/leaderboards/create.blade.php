@extends('admin.layouts.vertical', ['title' => 'Create Leaderboard', 'subTitle' => 'Create New Referral Leaderboard'])

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
                            <iconify-icon icon="iconamoon:trophy-duotone" class="text-white fs-5"></iconify-icon>
                        </div>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Create New Leaderboard</h5>
                        <small class="text-muted">Setup a referral competition with prizes</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.leaderboards.index') }}" class="btn btn-sm btn-outline-secondary">
                        <iconify-icon icon="iconamoon:arrow-left-duotone" class="align-text-bottom"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Back to List</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Create Form --}}
<div class="row">
    <div class="col-12">
        <form action="{{ route('admin.leaderboards.store') }}" method="POST" id="leaderboardForm" novalidate>
            @csrf
            
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
                                <input type="text" 
                                       class="form-control @error('title') is-invalid @enderror" 
                                       id="title" 
                                       name="title" 
                                       value="{{ old('title') }}" 
                                       maxlength="255" 
                                       placeholder="Enter leaderboard title..." 
                                       required>
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
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          placeholder="Describe the leaderboard competition rules and details...">{{ old('description') }}</textarea>
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
                                                           {{ old('type', 'competitive') === 'competitive' ? 'checked' : '' }}>
                                                    <div class="flex-grow-1">
                                                        <iconify-icon icon="iconamoon:trophy-duotone" class="text-primary fs-1 mb-2 d-block"></iconify-icon>
                                                        <h6 class="fw-bold mb-2">Competitive Ranking</h6>
                                                        <p class="small text-muted mb-0">Traditional ranking system where top performers win prizes based on their position</p>
                                                        <ul class="list-unstyled small mt-2 text-start">
                                                            <li>• Winners based on rank</li>
                                                            <li>• Different prizes per position</li>
                                                            <li>• Limited number of winners</li>
                                                        </ul>
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
                                                           {{ old('type') === 'target' ? 'checked' : '' }}>
                                                    <div class="flex-grow-1">
                                                        <iconify-icon icon="iconamoon:target-duotone" class="text-info fs-1 mb-2 d-block"></iconify-icon>
                                                        <h6 class="fw-bold mb-2">Target Achievement</h6>
                                                        <p class="small text-muted mb-0">Goal-based system where all users reaching the target get the same prize</p>
                                                        <ul class="list-unstyled small mt-2 text-start">
                                                            <li>• Winners based on target</li>
                                                            <li>• Same prize for all qualifiers</li>
                                                            <li>• Unlimited winners (optional limit)</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                        <input type="datetime-local" 
                                               class="form-control @error('start_date') is-invalid @enderror" 
                                               id="start_date" 
                                               name="start_date" 
                                               value="{{ old('start_date', now()->format('Y-m-d\TH:i')) }}" 
                                               required>
                                        @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label fw-semibold">End Date & Time <span class="text-danger">*</span></label>
                                        <input type="datetime-local" 
                                               class="form-control @error('end_date') is-invalid @enderror" 
                                               id="end_date" 
                                               name="end_date" 
                                               value="{{ old('end_date', now()->addDays(7)->format('Y-m-d\TH:i')) }}" 
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
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="referral_type" 
                                                   id="type_all" 
                                                   value="all" 
                                                   {{ old('referral_type', 'all') === 'all' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_all">
                                                <iconify-icon icon="iconamoon:people-duotone" class="text-primary me-2"></iconify-icon>
                                                All Referrals
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="referral_type" 
                                                   id="type_first_level" 
                                                   value="first_level" 
                                                   {{ old('referral_type') === 'first_level' ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex align-items-center" for="type_first_level">
                                                <iconify-icon icon="iconamoon:profile-duotone" class="text-info me-2"></iconify-icon>
                                                First Level Only
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="radio" 
                                                   name="referral_type" 
                                                   id="type_verified" 
                                                   value="verified_only" 
                                                   {{ old('referral_type') === 'verified_only' ? 'checked' : '' }}>
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
                                </h6>
                                
                                <div id="prizeContainer">
                                    {{-- First prize row --}}
                                    <div class="prize-row mb-3 p-3 border rounded bg-light">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Position</label>
                                                <input type="number" 
                                                       class="form-control" 
                                                       name="prize_structure[0][position]" 
                                                       value="1" 
                                                       min="1" 
                                                       max="100"
                                                       readonly>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Prize Amount ($)</label>
                                                <input type="number" 
                                                       step="0.01" 
                                                       class="form-control" 
                                                       name="prize_structure[0][amount]" 
                                                       placeholder="100.00" 
                                                       min="0">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-semibold">Actions</label>
                                                <div>
                                                    <span class="badge bg-primary">1st Place</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm" id="addPrizeBtn">
                                    <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                    Add Prize Position
                                </button>

                                <div class="form-text mt-2">Define prizes for different positions. Leave amount empty for no prize.</div>
                            </div>

                            {{-- Target Prize Structure --}}
                            <div class="mb-4" id="targetPrizes" style="display: none;">
                                <h6 class="fw-bold border-bottom pb-2 mb-3">
                                    <iconify-icon icon="iconamoon:target-duotone" class="me-2"></iconify-icon>
                                    Target Configuration
                                </h6>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="target_referrals" class="form-label fw-semibold">
                                            Target Referrals <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" 
                                               class="form-control @error('target_referrals') is-invalid @enderror" 
                                               id="target_referrals" 
                                               name="target_referrals" 
                                               value="{{ old('target_referrals') }}" 
                                               min="1" 
                                               max="1000"
                                               placeholder="10">
                                        @error('target_referrals')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Number of referrals needed to qualify</div>
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
                                               value="{{ old('target_prize_amount') }}" 
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
                                               value="{{ old('max_winners') }}" 
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
                                <input type="number" 
                                       class="form-control @error('max_positions') is-invalid @enderror" 
                                       id="max_positions" 
                                       name="max_positions" 
                                       value="{{ old('max_positions', 10) }}" 
                                       min="1" 
                                       max="100" 
                                       required>
                                @error('max_positions')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Maximum users to show on leaderboard</div>
                            </div>

                            <!-- Replace the status radio buttons section in create.blade.php with this: -->

<div class="mb-3">
    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
    <div class="row g-2">
        <div class="col-6">
            <div class="form-check">
                <input class="form-check-input" 
                       type="radio" 
                       name="status" 
                       id="status_active" 
                       value="active" 
                       {{ old('status', 'active') === 'active' ? 'checked' : '' }}>
                <label class="form-check-label" for="status_active">Active</label>
            </div>
        </div>
        <div class="col-6">
            <div class="form-check">
                <input class="form-check-input" 
                       type="radio" 
                       name="status" 
                       id="status_inactive" 
                       value="inactive" 
                       {{ old('status', 'active') === 'inactive' ? 'checked' : '' }}>
                <label class="form-check-label" for="status_inactive">Inactive</label>
            </div>
        </div>
    </div>
    @error('status')
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
    <div class="form-text">Start as active or inactive</div>
</div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           name="show_to_users" 
                                           id="show_to_users" 
                                           value="1" 
                                           {{ old('show_to_users', '1') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="show_to_users">
                                        Show to users
                                    </label>
                                </div>
                                <div class="form-text">Make leaderboard visible to users</div>
                            </div>
                        </div>
                    </div>

                    {{-- Preview Card --}}
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                                Preview
                            </h6>
                        </div>
                        <div class="card-body" id="previewContent">
                            <div class="text-center text-muted">
                                <iconify-icon icon="iconamoon:trophy-duotone" class="fs-1 mb-2"></iconify-icon>
                                <p class="small">Fill in the form to see a preview</p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="card">
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <iconify-icon icon="iconamoon:trophy-duotone" class="me-1"></iconify-icon>
                                    Create Leaderboard
                                </button>
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
document.addEventListener('DOMContentLoaded', function() {
    let prizeCount = 1;
    
    // Elements
    const form = document.getElementById('leaderboardForm');
    const submitBtn = document.getElementById('submitBtn');
    const addPrizeBtn = document.getElementById('addPrizeBtn');
    const prizeContainer = document.getElementById('prizeContainer');
    const competitivePrizes = document.getElementById('competitivePrizes');
    const targetPrizes = document.getElementById('targetPrizes');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    const typeOptions = document.querySelectorAll('.leaderboard-type-option');
    
    // Initialize form based on selected type
    function updateFormFields() {
        const selectedType = document.querySelector('input[name="type"]:checked')?.value || 'competitive';
        
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
            document.getElementById('target_referrals').value = '';
            document.getElementById('target_prize_amount').value = '';
            document.getElementById('max_winners').value = '';
        } else if (selectedType === 'target') {
            competitivePrizes.style.display = 'none';
            targetPrizes.style.display = 'block';
            
            // Clear competitive fields
            const prizeInputs = document.querySelectorAll('input[name*="prize_structure"]');
            prizeInputs.forEach(input => input.value = '');
        }
        
        updatePreview();
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
            if (radio) {
                radio.checked = true;
                updateFormFields();
            }
        });
    });
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        console.log('Form submission started');
        
        const selectedType = document.querySelector('input[name="type"]:checked')?.value;
        
        // Basic validation
        const title = document.getElementById('title').value.trim();
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const maxPositions = document.getElementById('max_positions').value;
        
        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the leaderboard');
            return false;
        }
        
        if (!selectedType) {
            e.preventDefault();
            alert('Please select a leaderboard type');
            return false;
        }
        
        if (!startDate || !endDate) {
            e.preventDefault();
            alert('Please set both start and end dates');
            return false;
        }
        
        if (new Date(endDate) <= new Date(startDate)) {
            e.preventDefault();
            alert('End date must be after start date');
            return false;
        }
        
        if (!maxPositions || maxPositions < 1) {
            e.preventDefault();
            alert('Please enter a valid number for max positions');
            return false;
        }
        
        // Type-specific validation
        if (selectedType === 'target') {
            const targetReferrals = document.getElementById('target_referrals').value;
            const targetPrizeAmount = document.getElementById('target_prize_amount').value;
            
            if (!targetReferrals || targetReferrals < 1) {
                e.preventDefault();
                alert('Please enter a valid target referrals number');
                return false;
            }
            
            if (!targetPrizeAmount || targetPrizeAmount <= 0) {
                e.preventDefault();
                alert('Please enter a valid prize amount');
                return false;
            }
        }
        
        // Check if at least one referral type is selected
        const referralTypeSelected = document.querySelector('input[name="referral_type"]:checked');
        if (!referralTypeSelected) {
            e.preventDefault();
            alert('Please select a referral type');
            return false;
        }
        
        // Check if at least one status is selected
        const statusSelected = document.querySelector('input[name="status"]:checked');
        if (!statusSelected) {
            e.preventDefault();
            alert('Please select a status');
            return false;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating...';
        
        console.log('Form validation passed, submitting...');
        
        // Log form data for debugging
        const formData = new FormData(form);
        console.log('Form data:', Array.from(formData.entries()));
    });
    
    // Add prize row function (for competitive type)
    // Replace your addPrizeBtn event listener with this corrected version:

addPrizeBtn.addEventListener('click', function() {
    // Calculate the next position based on existing rows
    const existingRows = document.querySelectorAll('.prize-row');
    const nextPosition = existingRows.length + 1;
    
    prizeCount++; // This is just for unique naming of form fields
    
    const newRow = document.createElement('div');
    newRow.className = 'prize-row mb-3 p-3 border rounded bg-light';
    newRow.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Position</label>
                <input type="number" 
                       class="form-control" 
                       name="prize_structure[${prizeCount}][position]" 
                       value="${nextPosition}" 
                       min="1" 
                       max="100"
                       readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Prize Amount ($)</label>
                <input type="number" 
                       step="0.01" 
                       class="form-control" 
                       name="prize_structure[${prizeCount}][amount]" 
                       placeholder="50.00" 
                       min="0">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Actions</label>
                <div>
                    <span class="badge bg-secondary me-2">${getPositionSuffix(nextPosition)} Place</span>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-prize-btn">
                        <iconify-icon icon="iconamoon:trash-duotone"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    prizeContainer.appendChild(newRow);
    updatePreview();
});

    // Remove prize row (event delegation)
    prizeContainer.addEventListener('click', function(e) {
        if (e.target.closest('.remove-prize-btn')) {
            const row = e.target.closest('.prize-row');
            if (prizeContainer.children.length > 1) {
                row.remove();
                updatePreview();
            }
        }
    });
    
    // Update preview function
    function updatePreview() {
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        const maxPositions = document.getElementById('max_positions').value;
        const selectedType = document.querySelector('input[name="type"]:checked')?.value || 'competitive';
        
        const previewContent = document.getElementById('previewContent');
        
        if (!title) {
            previewContent.innerHTML = `
                <div class="text-center text-muted">
                    <iconify-icon icon="iconamoon:trophy-duotone" class="fs-1 mb-2"></iconify-icon>
                    <p class="small">Fill in the form to see a preview</p>
                </div>
            `;
            return;
        }

        const formatDate = (dateStr) => {
            if (!dateStr) return 'Not set';
            return new Date(dateStr).toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };

        let prizeInfo = '';
        let totalPrize = 0;
        let winnerCount = 0;

        if (selectedType === 'competitive') {
            const prizeInputs = document.querySelectorAll('input[name*="[amount]"]');
            prizeInputs.forEach(input => {
                const amount = parseFloat(input.value) || 0;
                if (amount > 0) {
                    totalPrize += amount;
                    winnerCount++;
                }
            });
            prizeInfo = winnerCount > 0 ? `${winnerCount} prizes, $${totalPrize.toFixed(2)} total` : 'No prizes configured';
        } else if (selectedType === 'target') {
            const targetReferrals = document.getElementById('target_referrals').value;
            const targetPrizeAmount = parseFloat(document.getElementById('target_prize_amount').value) || 0;
            const maxWinners = document.getElementById('max_winners').value;
            
            if (targetReferrals && targetPrizeAmount) {
                prizeInfo = `Target: ${targetReferrals} referrals = $${targetPrizeAmount.toFixed(2)}`;
                if (maxWinners) {
                    prizeInfo += ` (Max ${maxWinners} winners)`;
                } else {
                    prizeInfo += ` (Unlimited winners)`;
                }
            } else {
                prizeInfo = 'Target not configured';
            }
        }

        const typeIcon = selectedType === 'target' ? 'iconamoon:target-duotone' : 'iconamoon:trophy-duotone';
        const typeBadgeClass = selectedType === 'target' ? 'bg-info' : 'bg-primary';
        const typeDisplay = selectedType === 'target' ? 'Target Achievement' : 'Competitive Ranking';

        previewContent.innerHTML = `
            <div class="text-center">
                <iconify-icon icon="${typeIcon}" class="text-primary fs-1 mb-2"></iconify-icon>
                <span class="badge ${typeBadgeClass} mb-2">${typeDisplay}</span>
                <h6 class="fw-bold mb-2">${title}</h6>
                ${description ? `<p class="small text-muted mb-3">${description.substring(0, 100)}${description.length > 100 ? '...' : ''}</p>` : ''}
                
                <div class="row g-2 text-center">
                    <div class="col-12">
                        <div class="small text-muted">Duration</div>
                        <div class="fw-semibold small">${formatDate(startDate)} - ${formatDate(endDate)}</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Max Positions</div>
                        <div class="fw-semibold small">${maxPositions || 10}</div>
                    </div>
                    <div class="col-6">
                        <div class="small text-muted">Prize Structure</div>
                        <div class="fw-semibold small text-success">${prizeInfo}</div>
                    </div>
                </div>
            </div>
        `;
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
    
    // Event listeners for real-time updates
    const formInputs = document.querySelectorAll('#leaderboardForm input, #leaderboardForm textarea, #leaderboardForm select');
    formInputs.forEach(input => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    document.getElementById('start_date').addEventListener('change', validateDates);
    document.getElementById('end_date').addEventListener('change', validateDates);

    // Initialize
    updateFormFields();
    updatePreview();
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

.was-validated .form-control:invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.was-validated .form-control:valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.alert {
    border-left: 4px solid;
}

.alert-danger {
    border-left-color: #dc3545;
}

.alert-success {
    border-left-color: #28a745;
}
</style>
@endsection