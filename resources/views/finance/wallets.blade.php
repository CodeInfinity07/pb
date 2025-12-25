@extends('layouts.vertical', ['title' => 'Wallets', 'subTitle' => 'Finance'])

@section('content')

{{-- Wallets Section --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-1">Balances</h4>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @if($wallets->count() > 0)
                <div class="row g-3">
                    @foreach($wallets as $wallet)
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card h-100 wallet-card {{ $wallet->balance > 0 ? 'border-success' : '' }}">
                            <div class="card-body">
                                {{-- Wallet Header --}}
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $wallet->cryptocurrency->icon_url ?? 'https://predictor.guru/images/icons/19.svg' }}" 
                                             alt="{{ $wallet->currency }}" 
                                             class="me-3 rounded-circle"
                                             style="width: 48px; height: 48px;">
                                        <div>
                                            <h6 class="mb-1">{{ $wallet->name }}</h6>
                                            <span class="badge bg-primary">{{ $wallet->currency }}</span>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="{{ route('wallets.deposit.wallet') }}">
                                                <iconify-icon icon="iconamoon:arrow-down-duotone" class="me-2"></iconify-icon>
                                                Deposit
                                            </a>
                                            @if($wallet->hasAddress() && $wallet->balance > 0)
                                            <a class="dropdown-item" href="{{ route('wallets.withdraw.wallet') }}">
                                                <iconify-icon icon="iconamoon:arrow-up-duotone" class="me-2"></iconify-icon>
                                                Withdraw
                                            </a>
                                            @else
                                            <span class="dropdown-item text-muted">
                                                <iconify-icon icon="iconamoon:arrow-up-duotone" class="me-2"></iconify-icon>
                                                Withdraw
                                                <small class="d-block">{{ !$wallet->hasAddress() ? 'Address required' : 'No balance' }}</small>
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Balance --}}
                                <div class="text-center mb-3">
                                    <h4 class="text-success mb-1">${{ number_format($wallet->usd_value, 2) }} USD</h4>
                                </div>

                                {{-- Withdrawal Address --}}
                                <div class="mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small">Withdrawal Address</span>
                                        @if($wallet->hasAddress())
                                            <span class="badge bg-success">Set</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Not Set</span>
                                        @endif
                                    </div>
                                    @if($wallet->hasAddress())
                                        <div class="input-group">
                                            <input type="text" class="form-control payment-address" 
                                                   value="{{ Str::limit($wallet->address, 25) }}..." 
                                                   readonly>
                                            <button class="btn btn-outline-secondary" onclick="copyText('{{ $wallet->address }}')">
                                                <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                    @else
                                        <p class="text-muted small mb-0">Required for withdrawals</p>
                                    @endif
                                </div>

                                {{-- Network Info --}}
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="text-center border rounded p-2">
                                            <div class="small text-muted">Network</div>
                                            <div class="fw-semibold">{{ $wallet->cryptocurrency->network ?? 'N/A' }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center border rounded p-2">
                                            <div class="small text-muted">Fee</div>
                                            <div class="fw-semibold">10%</div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="row g-2">
                                    <div class="col-6">
                                        @if($wallet->hasAddress())
                                            <button type="button" class="btn btn-outline-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal{{ $wallet->id }}">
                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                                                Update
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-primary w-100 btn-sm" data-bs-toggle="modal" data-bs-target="#addressModal{{ $wallet->id }}">
                                                <iconify-icon icon="iconamoon:plus-duotone" class="me-1"></iconify-icon>
                                                Add Address
                                            </button>
                                        @endif
                                    </div>
                                    <div class="col-6">
                                        @if($wallet->balance > 0)
                                            <a href="{{ route('wallets.withdraw.wallet') }}" class="btn btn-warning w-100 btn-sm">
                                                <iconify-icon icon="iconamoon:arrow-up-duotone" class="me-1"></iconify-icon>
                                                Withdraw
                                            </a>
                                        @else
                                            <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-success w-100 btn-sm">
                                                <iconify-icon icon="iconamoon:arrow-down-duotone" class="me-1"></iconify-icon>
                                                Deposit
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Address Modal --}}
                    <div class="modal fade" id="addressModal{{ $wallet->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('wallets.update-address', $wallet->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">
                                            {{ $wallet->hasAddress() ? 'Update' : 'Add' }} {{ $wallet->currency }} Withdrawal Address
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        @if($wallet->hasAddress())
                                        <div class="alert alert-warning">
                                            <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                            Changing your withdrawal address will affect all future withdrawals.
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Current Address</label>
                                            <input type="text" class="form-control payment-address" value="{{ $wallet->address }}" readonly>
                                        </div>
                                        @else
                                        <div class="alert alert-info">
                                            <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                            This address will be used for all {{ $wallet->currency }} withdrawals.
                                        </div>
                                        @endif
                                        <div class="mb-3">
                                            <label class="form-label">{{ $wallet->hasAddress() ? 'New' : '' }} {{ $wallet->currency }} Address <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="address" 
                                                   placeholder="Enter {{ $wallet->hasAddress() ? 'new' : 'your' }} {{ $wallet->currency }} withdrawal address" required>
                                            <div class="form-text">Network: {{ $wallet->cryptocurrency->network }}</div>
                                            @if($wallet->cryptocurrency->contract_address)
                                            <div class="form-text text-warning">
                                                <iconify-icon icon="iconamoon:warning-duotone" class="me-1"></iconify-icon>
                                                Token Contract: {{ Str::limit($wallet->cryptocurrency->contract_address, 20) }}...
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            {{ $wallet->hasAddress() ? 'Update' : 'Add' }} Address
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:wallet-off-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Wallets Available</h6>
                    <p class="text-muted">No cryptocurrencies are currently available. Please contact support for assistance.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
function copyText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert('Address copied to clipboard!', 'success');
        });
    } else {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Address copied to clipboard!', 'success');
    }
}

function toggleWallet(walletId) {
    fetch(`{{ url('wallets') }}/${walletId}/toggle`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Wallet status updated!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('Failed to update wallet status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred', 'danger');
    });
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 4000);
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const addressModals = document.querySelectorAll('[id^="addressModal"]');
    
    addressModals.forEach(modal => {
        const form = modal.querySelector('form');
        const addressInput = form.querySelector('input[name="address"]');
        
        if (form && addressInput) {
            form.addEventListener('submit', function(e) {
                const address = addressInput.value.trim();
                
                if (!address) {
                    e.preventDefault();
                    showAlert('Please enter a wallet address', 'danger');
                    addressInput.focus();
                    return;
                }
                
                if (address.length < 20) {
                    e.preventDefault();
                    showAlert('Please enter a valid wallet address', 'danger');
                    addressInput.focus();
                    return;
                }
                
                // Show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
                submitBtn.disabled = true;
            });
        }
    });
});
</script>

<style>
/* Clean, functional styles matching deposit/withdraw pages */
.wallet-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.wallet-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.payment-address {
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    background-color: #f8f9fa;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .d-flex.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .d-flex.gap-2 .btn {
        width: 100%;
    }
    
    .payment-address {
        font-size: 0.8rem;
    }
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>
@endsection