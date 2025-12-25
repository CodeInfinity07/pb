@extends('layouts.vertical', ['title' => 'Withdraw', 'subTitle' => 'Finance'])

@section('content')

<div class="row justify-content-center">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">Withdraw</h4>
                    <a href="{{ route('wallets.index') }}" class="btn btn-outline-secondary">Wallets</a>
                </div>
            </div>
            <div class="card-body">
                
                @if(!$selectedWallet)
                {{-- Currency Selection Page --}}
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="text-center mb-4">
                            <iconify-icon icon="ph:hand-deposit" class="fs-48 text-warning mb-3"></iconify-icon>
                            <h5>Select Currency to Withdraw</h5>
                            <p class="text-muted">Choose which cryptocurrency you want to withdraw</p>
                        </div>

                        @if($wallets->count() > 0)
                        <div class="row g-3">
                            @foreach($wallets as $wallet)
                            <div class="col-lg-4 col-md-6">
                                <a href="{{ route('wallets.withdraw.wallet', ['wallet' => $wallet->id]) }}" class="text-decoration-none">
                                    <div class="card h-100 wallet-card">
                                        <div class="card-body text-center p-4">
                                            <img src="{{ $wallet->cryptocurrency->icon_url }}" 
                                                 alt="{{ $wallet->currency }}" 
                                                 class="mb-3 rounded-circle"
                                                 style="width: 64px; height: 64px;">
                                            
                                            <h6 class="fw-semibold mb-2">{{ $wallet->name }}</h6>
                                            <div class="mb-3">
                                                <span class="badge bg-primary mb-2">{{ $wallet->currency }}</span>
                                                @if($wallet->hasAddress())
                                                    <br><span class="badge bg-success">Ready</span>
                                                @else
                                                    <br><span class="badge bg-warning text-dark">No Address</span>
                                                @endif
                                            </div>
                                            
                                            <div class="text-muted small mb-3">
                                                <div><strong>Network:</strong> {{ $wallet->cryptocurrency->network }}</div>
                                                <div><strong>Fee:</strong> 10%</div>
                                            </div>
                                            
                                            <div class="bg-light rounded p-3">
                                                <div class="small text-muted">Available Balance</div>
                                                <div class="fw-semibold text-success">${{ number_format($wallet->usd_value, 2) }}</div>
                                            </div>

                                            @if(!$wallet->hasAddress())
                                            <div class="alert alert-warning mt-3 mb-0 small">
                                                <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                                Set withdrawal address first
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:wallet-off-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Wallets with Balance</h6>
                            <p class="text-muted">You don't have any cryptocurrencies with balance available for withdrawal.</p>
                            <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:arrow-down-duotone" class="me-1"></iconify-icon>
                                Deposit
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                @else
                {{-- Withdrawal Form Page --}}
                <div class="row justify-content-center">
                    <div class="col-lg-8">

                        {{-- Selected Currency Header --}}
                        <div class="alert alert-warning d-flex align-items-center mb-4">
                            <img src="{{ $selectedWallet->cryptocurrency->icon_url }}" 
                                 alt="{{ $selectedWallet->currency }}" 
                                 class="me-3 rounded-circle"
                                 style="width: 48px; height: 48px;">
                            <div>
                                <h6 class="mb-1 fw-semibold">{{ $selectedWallet->name }}</h6>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-primary">{{ $selectedWallet->currency }}</span>
                                    <span class="badge bg-secondary">{{ $selectedWallet->cryptocurrency->network }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Current Balance --}}
                        <div class="card bg-light mb-4">
                            <div class="card-body text-center">
                                <p class="text-muted mb-2">Available Balance</p>
                                <h4 class="text-success">${{ number_format($selectedWallet->usd_value, 2) }} USD</h4>
                                <small class="text-muted">{{ number_format($selectedWallet->balance, min(3, $selectedWallet->cryptocurrency->decimal_places)) }} {{ $selectedWallet->currency }}</small>
                            </div>
                        </div>

                        {{-- Messages --}}
                        @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <h6><iconify-icon icon="iconamoon:close-circle-duotone" class="me-1"></iconify-icon>Validation Errors</h6>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <iconify-icon icon="iconamoon:close-circle-duotone" class="me-2"></iconify-icon>{{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif
                        
                        @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        @endif

                        @if(!$selectedWallet->hasAddress())
                        {{-- No Address Warning --}}
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0 text-danger">
                                    <iconify-icon icon="iconamoon:close-circle-duotone" class="me-1"></iconify-icon>
                                    Withdrawal Address Required
                                </h5>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted mb-4">You need to set a withdrawal address for {{ $selectedWallet->currency }} before you can withdraw.</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addressModal">
                                    <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                    Add Withdrawal Address
                                </button>
                            </div>
                        </div>

                        @else
                        {{-- Withdrawal Form --}}
                        <form action="{{ route('wallets.process-withdraw', $selectedWallet) }}" method="POST" id="withdrawalForm">
                            @csrf
                            
                            {{-- Withdrawal Address Info --}}
                            <div class="card mb-4">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0">Withdrawal Address</h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#addressModal">
                                        <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                                        Change
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <input type="text" class="form-control payment-address me-2" 
                                               value="{{ $selectedWallet->address }}" readonly>
                                        <button type="button" class="btn btn-outline-success" 
                                                onclick="copyText('{{ $selectedWallet->address }}')">
                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                        </button>
                                    </div>
                                    <small class="text-muted">Network: {{ $selectedWallet->cryptocurrency->network }}</small>
                                </div>
                            </div>

                            {{-- Withdrawal Amount --}}
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">Enter Withdrawal Amount</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <label for="amount" class="form-label fw-semibold">Amount to Withdraw ({{ $selectedWallet->currency }})</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" 
                                                   class="form-control @error('amount') is-invalid @enderror" 
                                                   id="withdrawAmount" 
                                                   name="amount" 
                                                   value="{{ old('amount') }}"
                                                   min="{{ $selectedWallet->cryptocurrency->min_withdrawal }}"
                                                   max="{{ $selectedWallet->balance }}"
                                                   step="0.{{ str_repeat('0', min(3, $selectedWallet->cryptocurrency->decimal_places) - 1) }}1"
                                                   placeholder="Enter amount"
                                                   required>
                                        </div>
                                        @error('amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                       <small class="text-muted">
    Min: ${{ number_format($dynamicMinWithdrawal, 2) }} | 
    Max: {{ number_format($selectedWallet->balance, 2) }} {{ $selectedWallet->currency }}
</small>
                                    </div>

                                    {{-- Quick Amount Buttons --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Quick Select</label>
                                        <div class="btn-group-grid">
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-percentage="25">25%</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-percentage="50">50%</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-percentage="75">75%</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-percentage="100">Max</button>
                                        </div>
                                    </div>

                                    {{-- Transaction Summary --}}
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100">
                                                <h6 class="text-muted mb-3">Deduction Breakdown</h6>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="small">Withdraw Amount:</span>
                                                    <span class="small fw-semibold" id="summaryAmount">0.000 {{ $selectedWallet->currency }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="small">Fee (10%):</span>
                                                    <span class="small text-danger" id="feeDisplay">0.000 {{ $selectedWallet->currency }}</span>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-semibold">Deducted from Wallet:</span>
                                                    <span class="fw-semibold text-danger" id="totalDeducted">0.000 {{ $selectedWallet->currency }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100 bg-light">
                                                <h6 class="text-muted mb-3">You Will Receive</h6>
                                                <div class="text-center">
                                                    <div class="fw-bold text-success h4 mb-2" id="youReceive">0.000 {{ $selectedWallet->currency }}</div>
                                                    <small class="text-muted">Sent to your address</small>
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="small">Remaining Balance:</span>
                                                    <span class="small fw-semibold" id="remainingBalance">{{ number_format($selectedWallet->balance, min(3, $selectedWallet->cryptocurrency->decimal_places)) }} {{ $selectedWallet->currency }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Important Notice --}}
                                    <div class="alert alert-info mb-0">
                                        <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                        <strong>Note:</strong> A 10% fee will be deducted from your withdrawal amount. The remaining amount will be sent to your wallet address.
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden field for the address --}}
                            <input type="hidden" name="to_address" value="{{ $selectedWallet->address }}">

                            {{-- Action Buttons --}}
                            <div class="row g-2">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-warning w-100" id="submitBtn" disabled>
                                        <iconify-icon icon="iconamoon:sign-out-duotone" class="me-1"></iconify-icon>
                                        Withdraw
                                    </button>
                                </div>
                            </div>
                        </form>
                        @endif

                        {{-- Address Modal --}}
                        <div class="modal fade" id="addressModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('wallets.update-address', $selectedWallet) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                @if($selectedWallet->hasAddress())
                                                    Update {{ $selectedWallet->currency }} Withdrawal Address
                                                @else
                                                    Add {{ $selectedWallet->currency }} Withdrawal Address
                                                @endif
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if($selectedWallet->hasAddress())
                                            <div class="alert alert-warning">
                                                <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                                Changing your withdrawal address will affect all future withdrawals.
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Current Address</label>
                                                <input type="text" class="form-control" value="{{ $selectedWallet->address }}" readonly>
                                            </div>
                                            @else
                                            <div class="alert alert-info">
                                                <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                                This address will be used for all {{ $selectedWallet->currency }} withdrawals.
                                            </div>
                                            @endif
                                            <div class="mb-3">
                                                <label class="form-label">{{ $selectedWallet->hasAddress() ? 'New' : '' }} {{ $selectedWallet->currency }} Address <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="address" 
                                                       placeholder="Enter {{ $selectedWallet->hasAddress() ? 'new' : 'your' }} {{ $selectedWallet->currency }} withdrawal address" required>
                                                <div class="form-text">Network: {{ $selectedWallet->cryptocurrency->network }}</div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                {{ $selectedWallet->hasAddress() ? 'Update' : 'Add' }} Address
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Configuration
const balance = {{ $selectedWallet->balance ?? 0 }};
const feePercentage = {{ $selectedWallet->cryptocurrency->withdrawal_fee ?? 0 }};
const decimals = {{ min(3, $selectedWallet->cryptocurrency->decimal_places ?? 8) }};
const currency = '{{ $selectedWallet->currency ?? '' }}';
const minWithdrawal = {{ $dynamicMinWithdrawal ?? 0 }};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupQuickAmounts();
    setupFormValidation();
    updateSummary();
});

// Quick amount buttons
function setupQuickAmounts() {
    const amountInput = document.getElementById('withdrawAmount');
    const buttons = document.querySelectorAll('.quick-amount');
    
    if (!amountInput || !buttons.length) return;
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const percentage = parseInt(this.dataset.percentage);
            const amount = (balance * percentage / 100);
            amountInput.value = amount.toFixed(decimals);
            
            // Update button states
            buttons.forEach(b => b.classList.replace('btn-primary', 'btn-outline-primary'));
            this.classList.replace('btn-outline-primary', 'btn-primary');
            
            updateSummary();
        });
    });
    
    // Clear selection on manual input
    amountInput.addEventListener('input', function() {
        buttons.forEach(b => b.classList.replace('btn-primary', 'btn-outline-primary'));
        updateSummary();
    });
}

// Form validation
function setupFormValidation() {
    const withdrawalForm = document.getElementById('withdrawalForm');
    
    if (withdrawalForm) {
        withdrawalForm.addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('withdrawAmount').value) || 0;
            
            if (amount > balance) {
                e.preventDefault();
                showAlert('Insufficient balance', 'danger');
                return;
            }
            
            if (amount < minWithdrawal) {
                e.preventDefault();
                showAlert(`Minimum withdrawal is ${minWithdrawal} ${currency}`, 'danger');
                return;
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                submitBtn.disabled = true;
            }
        });
    }
}

function updateSummary() {
    const amount = parseFloat(document.getElementById('withdrawAmount')?.value) || 0;
    const fee = (amount * feePercentage) / 100;
    const youReceiveAmount = amount - fee;
    const remaining = balance - amount;
    
    // Update summary displays
    document.getElementById('summaryAmount').textContent = amount.toFixed(decimals) + ' ' + currency;
    document.getElementById('feeDisplay').textContent = fee.toFixed(decimals) + ' ' + currency;
    document.getElementById('totalDeducted').textContent = amount.toFixed(decimals) + ' ' + currency;
    document.getElementById('youReceive').textContent = youReceiveAmount.toFixed(decimals) + ' ' + currency;
    document.getElementById('remainingBalance').textContent = remaining.toFixed(decimals) + ' ' + currency;
    
    // Update submit button state
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = !(amount > 0 && amount <= balance && amount >= minWithdrawal);
    }
}

// Copy text utility
function copyText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert('Copied to clipboard!', 'success');
        });
    } else {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Copied to clipboard!', 'success');
    }
}

// Show alert utility
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}
</script>

<style>
.btn-group-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}

.wallet-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid #dee2e6;
}

.wallet-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #007bff;
}

.payment-address {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    background-color: #f8f9fa;
}

.quick-amount {
    transition: all 0.2s ease;
}

@media (max-width: 768px) {
    .btn-group-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .payment-address {
        font-size: 0.8rem;
    }
}
</style>
@endsection