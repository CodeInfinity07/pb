@extends('layouts.vertical', ['title' => 'Deposit', 'subTitle' => 'Finance'])

@section('content')

<div class="row justify-content-center">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title mb-0">
                        Deposit
                    </h4>
                    <a href="{{ route('wallets.index') }}" class="btn btn-outline-secondary">
                        Wallets
                    </a>
                </div>
            </div>
            <div class="card-body">
                
                @if(isset($selectedWallet) && $selectedWallet)
                {{-- Specific Wallet Deposit Page --}}
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        {{-- Selected Currency Header --}}
                        <div class="alert alert-info d-flex align-items-center mb-4">
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
                                <p class="text-muted mb-2">Current Balance</p>
                                <h4 class="text-success">${{ number_format($selectedWallet->usd_value, 2) }} USD</h4>
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

                        @if(!isset($paymentData) || !$paymentData)
                        {{-- Deposit Amount Form --}}
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Enter Deposit Amount</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('wallets.deposit.generate', $selectedWallet->id) }}" method="POST" id="depositForm">
                                    @csrf
                                    
                                    <div class="mb-4">
                                        <label for="amount" class="form-label fw-semibold">Amount (USD)</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text">$</span>
                                            <input type="number" 
                                                   class="form-control @error('amount') is-invalid @enderror" 
                                                   id="amount" 
                                                   name="amount" 
                                                   value="{{ old('amount') }}"
                                                   min="0.01" 
                                                   max="100000" 
                                                   step="0.01" 
                                                   placeholder="Enter amount"
                                                   required>
                                        </div>
                                        @error('amount')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Quick Amount Buttons --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Quick Select</label>
                                        <div class="btn-group-grid">
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="10">$10</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="25">$25</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="50">$50</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="100">$100</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="250">$250</button>
                                            <button type="button" class="btn btn-outline-primary quick-amount" data-amount="500">$500</button>
                                        </div>
                                    </div>

                                    {{-- Payment Info --}}
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100">
                                                <h6 class="text-muted mb-2">Payment Currency</h6>
                                                <div class="d-flex align-items-center">
                                                    <img src="{{ $selectedWallet->cryptocurrency->icon_url }}" 
                                                         alt="{{ $selectedWallet->currency }}" 
                                                         class="me-2 rounded-circle"
                                                         style="width: 32px; height: 32px;">
                                                    <div>
                                                        <div class="fw-semibold">{{ $selectedWallet->currency }}</div>
                                                        <small class="text-muted">{{ $selectedWallet->cryptocurrency->network }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="border rounded p-3 h-100">
                                                <h6 class="text-muted mb-2">Processing</h6>
                                                <div>
                                                    <div class="fw-semibold">1-30 minutes</div>
                                                    <small class="text-muted">Network confirmations</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Submit Button --}}
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg" id="generateBtn">
                                            <iconify-icon icon="iconamoon:arrow-down-duotone" class="me-2"></iconify-icon>
                                            Generate Payment
                                        </button>
                                
                                    </div>
                                </form>
                            </div>
                        </div>

                        @else
                        {{-- Payment Instructions --}}
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h5 class="mb-0">Payment Instructions</h5>
                                <span class="badge fs-6 px-3 py-2" id="statusBadge">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Checking Status...
                                </span>
                            </div>
                            <div class="card-body">
                                
                                {{-- Payment Summary --}}
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="text-center text-md-start">
                                            <p class="text-muted mb-1">Amount to Deposit</p>
                                            <h4 class="text-primary mb-1">${{ number_format($requestedAmount, 2) }}</h4>
                                            <small class="text-muted">{{ $selectedWallet->currency }}</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center text-md-end">
                                            <p class="text-muted mb-1">Order ID</p>
                                            <div class="d-flex align-items-center justify-content-center justify-content-md-end">
                                                <code class="me-2">{{ $orderId }}</code>
                                                <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $orderId }}')">
                                                    <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if(isset($paymentData['address']))
                                {{-- QR Code and Address Section --}}
                                <div id="paymentSection">
                                    <div class="row">
                                        <div class="col-lg-5">
                                            {{-- QR Code --}}
                                            <div class="text-center mb-4">
                                                <div class="bg-white border rounded p-3 d-inline-block mb-3" style="min-width: 200px; min-height: 200px;">
                                                    <div id="qrContainer" class="d-flex align-items-center justify-content-center h-100">
                                                        <div class="text-center">
                                                            <div class="spinner-border text-primary mb-2"></div>
                                                            <p class="text-muted mb-0">Generating QR...</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="refreshQR()" title="Refresh QR">
                                                        <iconify-icon icon="iconoir:refresh-double"></iconify-icon>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="downloadQR()" title="Download QR">
                                                        <iconify-icon icon="iconamoon:download-duotone"></iconify-icon>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-7">
                                            {{-- Payment Address --}}
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold">Send to this address:</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control payment-address" 
                                                           value="{{ $paymentData['address'] }}" 
                                                           readonly>
                                                    <button class="btn btn-outline-success" onclick="copyText('{{ $paymentData['address'] }}')">
                                                        <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Exact Amount --}}
                                            <div class="alert alert-warning mb-0">
                                                <h6 class="alert-heading mb-2">
                                                    Exact Amount Required
                                                </h6>
                                                <p class="mb-1"><strong>{{ number_format($paymentData['amount'], 2) }} {{ $selectedWallet->currency }}</strong></p>
                                                <small>Send exactly this amount. Partial payments might not be processed.</small>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                {{-- Success State (Hidden Initially) --}}
                                <div id="successSection" class="d-none">
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                                 style="width: 80px; height: 80px;">
                                                <iconify-icon icon="iconamoon:check-duotone" style="font-size: 2.5rem;"></iconify-icon>
                                            </div>
                                            <h4 class="text-success mb-2">Payment Confirmed!</h4>
                                            <p class="text-muted mb-3">Your deposit has been processed successfully.</p>
                                            <div class="alert alert-success">
                                                <strong>+{{ number_format($paymentData['amount'], 3) }} {{ $selectedWallet->currency }}</strong>
                                                <br><small>Added to your wallet</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @elseif(isset($paymentData['invoice_url']))
                                {{-- Plisio Redirect Payment --}}
                                <div id="paymentSection">
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                                 style="width: 80px; height: 80px;">
                                                <iconify-icon icon="iconamoon:link-external-duotone" style="font-size: 2.5rem;"></iconify-icon>
                                            </div>
                                            <h4 class="text-primary mb-2">Complete Your Payment</h4>
                                            <p class="text-muted mb-4">Click the button below to complete your payment securely through our payment processor.</p>
                                        </div>
                                        
                                        <div class="d-grid gap-3 mb-4" style="max-width: 400px; margin: 0 auto;">
                                            <a href="{{ $paymentData['invoice_url'] }}" 
                                               target="_blank" 
                                               class="btn btn-success btn-lg">
                                                <iconify-icon icon="iconamoon:arrow-top-right-duotone" class="me-2"></iconify-icon>
                                                Pay Now - ${{ number_format($requestedAmount, 2) }}
                                            </a>
                                            <a href="{{ $paymentData['invoice_url'] }}" 
                                               target="_blank" 
                                               class="btn btn-outline-secondary">
                                                Open in New Tab
                                            </a>
                                        </div>

                                        <div class="alert alert-warning">
                                            <h6 class="alert-heading mb-2">Payment Instructions</h6>
                                            <ol class="text-start mb-0 small">
                                                <li>Click "Pay Now" to open the secure payment page</li>
                                                <li>Select your preferred cryptocurrency</li>
                                                <li>Send the exact amount shown to the provided address</li>
                                                <li>Wait for blockchain confirmations (1-30 minutes)</li>
                                                <li>Your wallet will be credited automatically</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>

                                {{-- Success State (Hidden Initially) --}}
                                <div id="successSection" class="d-none">
                                    <div class="text-center">
                                        <div class="mb-4">
                                            <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                                 style="width: 80px; height: 80px;">
                                                <iconify-icon icon="iconamoon:check-duotone" style="font-size: 2.5rem;"></iconify-icon>
                                            </div>
                                            <h4 class="text-success mb-2">Payment Confirmed!</h4>
                                            <p class="text-muted mb-3">Your deposit has been processed successfully.</p>
                                            <div class="alert alert-success">
                                                <strong>+${{ number_format($requestedAmount, 2) }}</strong>
                                                <br><small>Added to your {{ $selectedWallet->currency }} wallet</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @elseif(isset($paymentData['form']))
                                {{-- External Payment Form --}}
                                <div class="alert alert-info text-center">
                                    <h6>External Payment Required</h6>
                                    <p>Complete your payment using the secure gateway below.</p>
                                    {!! $paymentData['form'] !!}
                                </div>
                                @endif

                                {{-- Important Notes --}}
                                <div class="alert alert-info mt-4">
                                    <h6 class="alert-heading">Important Notes</h6>
                                    <ul class="mb-0 small">
                                        <li>Use {{ $selectedWallet->cryptocurrency->network }} network only</li>
                                        <li>Processing time: 1-30 minutes</li>
                                        <li>Keep order ID {{ $orderId }} for reference</li>
                                        <li>Contact support if payment isn't credited within 1 hour</li>
                                    </ul>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="row g-2 mt-3">
                                    <div class="col-md-4">
                                        <a href="{{ route('wallets.deposit.wallet', $selectedWallet->id) }}" class="btn btn-outline-secondary w-100">
                                            New Payment
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-outline-primary w-100">
                                            Other Currency
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a href="{{ route('wallets.index') }}" class="btn btn-success w-100" id="walletsBtn">
                                            View Wallets
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>

                @else
                {{-- Currency Selection Page --}}
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="text-center mb-4">
                            <iconify-icon icon="ph:hand-deposit" class="fs-48 text-info mb-3"></iconify-icon>
                            <h5>Select Currency to Deposit</h5>
                            <p class="text-muted">Choose which Currency you want to deposit</p>
                        </div>

                        <div class="row g-3">
                            @forelse($wallets as $wallet)
                            <div class="col-lg-4 col-md-6">
                                <a href="{{ route('wallets.deposit.wallet', $wallet->id) }}" class="text-decoration-none">
                                    <div class="card h-100 wallet-card">
                                        <div class="card-body text-center p-4">
                                            <img src="{{ $wallet->cryptocurrency->icon_url }}" 
                                                 alt="{{ $wallet->currency }}" 
                                                 class="mb-3 rounded-circle"
                                                 style="width: 64px; height: 64px;">
                                            
                                            <h6 class="fw-semibold mb-2">{{ $wallet->name }}</h6>
                                            <span class="badge bg-primary mb-3">{{ $wallet->currency }}</span>
                                            
                                            <div class="text-muted small mb-3">
                                                <div><strong>Network:</strong> {{ $wallet->cryptocurrency->network }}</div>
                                            </div>
                                            
                                            <div class="bg-light rounded p-3">
                                                <div class="small text-muted">Balance</div>
                                                <div class="fw-semibold text-success">${{ number_format($wallet->usd_value, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <iconify-icon icon="iconamoon:wallet-off-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                                    <h6 class="text-muted">No Wallets Available</h6>
                                    <p class="text-muted">No wallets are available for deposits.</p>
                                </div>
                            </div>
                            @endforelse
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
const PAYMENT_DATA = @json($paymentData ?? null);
const SELECTED_WALLET = @json($selectedWallet ?? null);
const ORDER_ID = @json($orderId ?? '');

let statusCheckInterval = null;
let isConfirmed = false;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    console.log('Deposit page initialized');
    
    setupQuickAmounts();
    setupFormValidation();
    
    if (PAYMENT_DATA && PAYMENT_DATA.address) {
        generateQR();
        startStatusChecking();
    }
});

// Quick amount buttons
function setupQuickAmounts() {
    const amountInput = document.getElementById('amount');
    const buttons = document.querySelectorAll('.quick-amount');
    
    if (!amountInput || !buttons.length) return;
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const amount = this.dataset.amount;
            amountInput.value = amount;
            
            // Update button states
            buttons.forEach(b => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-primary');
        });
    });
    
    // Clear selection on manual input
    amountInput.addEventListener('input', function() {
        buttons.forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-primary');
        });
    });
}

// Form validation
function setupFormValidation() {
    const form = document.getElementById('depositForm');
    const submitBtn = document.getElementById('generateBtn');
    
    if (!form || !submitBtn) return;
    
    form.addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amount').value);
        
        if (amount < 0.01 || amount > 100000) {
            e.preventDefault();
            showAlert('Please enter a valid amount between $0.01 and $100,000', 'danger');
            return;
        }
        
        // Show loading
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
        submitBtn.disabled = true;
    });
}

// QR Code generation
function generateQR() {
    if (!PAYMENT_DATA?.address) return;
    
    const container = document.getElementById('qrContainer');
    if (!container) return;
    
    const address = PAYMENT_DATA.address;
    const amount = PAYMENT_DATA.amount;
    const currency = SELECTED_WALLET.currency;
    
    // Create QR data
    let qrData = address;
    if (['BTC', 'LTC', 'DOGE'].includes(currency)) {
        qrData = `${currency.toLowerCase()}:${address}?amount=${amount}`;
    }
    
    // Generate QR image
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(qrData)}`;
    
    const img = new Image();
    img.onload = function() {
        container.innerHTML = '';
        img.className = 'img-fluid';
        img.alt = 'Payment QR Code';
        img.dataset.url = qrUrl;
        container.appendChild(img);
    };
    
    img.onerror = function() {
        container.innerHTML = `
            <div class="text-center text-danger">
                <iconify-icon icon="iconamoon:close-circle-duotone" class="fs-1 mb-2"></iconify-icon>
                <p>QR generation failed</p>
                <button class="btn btn-sm btn-outline-primary" onclick="generateQR()">Retry</button>
            </div>
        `;
    };
    
    img.src = qrUrl;
}

// Status checking
function startStatusChecking() {
    if (!ORDER_ID || isConfirmed) return;
    
    // Initial status
    updateStatusBadge('waiting', 'Waiting');
    
    // Check immediately after 10 seconds
    setTimeout(() => {
        if (!isConfirmed) checkStatus(false);
    }, 10000);
    
    // Then check every 20 seconds
    statusCheckInterval = setInterval(() => {
        if (!isConfirmed) {
            checkStatus(false);
        } else {
            clearInterval(statusCheckInterval);
        }
    }, 20000);
}

function checkStatus(showLoading = true) {
    if (!ORDER_ID || isConfirmed) return;
    
    const checkBtn = document.getElementById('checkBtn');
    
    if (showLoading && checkBtn) {
        checkBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Checking...';
        checkBtn.disabled = true;
    }
    
    fetch('{{ route("wallets.payment.status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ order_id: ORDER_ID })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Status:', data.status);
        handleStatusUpdate(data.status, data.message);
    })
    .catch(error => {
        console.error('Status check failed:', error);
        if (showLoading) showAlert('Failed to check status', 'warning');
    })
    .finally(() => {
        if (showLoading && checkBtn) {
            checkBtn.innerHTML = 'Check';
            checkBtn.disabled = false;
        }
    });
}

function handleStatusUpdate(status, message) {
    switch(status) {
        case 'completed':
        case 'confirmed':
        case 'success':
            if (!isConfirmed) {
                isConfirmed = true;
                clearInterval(statusCheckInterval);
                
                updateStatusBadge('success', 'Confirmed');
                showSuccessState();
                showAlert('Payment confirmed! Redirecting to wallets...', 'success');
            }
            break;
            
        case 'pending':
            updateStatusBadge('warning', 'Pending');
            break;
            
        case 'failed':
            updateStatusBadge('danger', 'Failed');
            showAlert(message || 'Payment failed', 'danger');
            break;
            
        default:
            updateStatusBadge('info', 'Checking...');
    }
}

function updateStatusBadge(type, text) {
    const badge = document.getElementById('statusBadge');
    if (!badge) return;
    
    const classes = {
        waiting: 'badge bg-warning text-dark',
        success: 'badge bg-success',
        warning: 'badge bg-warning text-dark', 
        danger: 'badge bg-danger',
        info: 'badge bg-info'
    };
    
    const icons = {
        waiting: 'clock-duotone',
        success: 'check-circle-duotone',
        warning: 'warning-duotone',
        danger: 'close-circle-duotone',
        info: 'information-circle-duotone'
    };
    
    badge.className = `${classes[type]} fs-6 px-3 py-2`;
    badge.innerHTML = `${text}`;
}

function showSuccessState() {
    const paymentSection = document.getElementById('paymentSection');
    const successSection = document.getElementById('successSection');
    const walletsBtn = document.getElementById('walletsBtn');
    
    if (paymentSection) paymentSection.classList.add('d-none');
    if (successSection) successSection.classList.remove('d-none');
    if (walletsBtn) walletsBtn.classList.replace('btn-success', 'btn-primary');
}

// Utility functions
function refreshQR() {
    const container = document.getElementById('qrContainer');
    if (container) {
        container.innerHTML = '<div class="text-center"><div class="spinner-border text-primary"></div><p class="mt-2">Refreshing...</p></div>';
        setTimeout(generateQR, 1000);
    }
}

function downloadQR() {
    const img = document.querySelector('#qrContainer img');
    if (img?.dataset?.url) {
        const link = document.createElement('a');
        link.href = img.dataset.url;
        link.download = `qr-${ORDER_ID}.png`;
        link.click();
        showAlert('Download started', 'info');
    }
}

function copyText(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showAlert('Copied to clipboard!', 'success');
        });
    } else {
        // Fallback
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showAlert('Copied to clipboard!', 'success');
    }
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

// Cleanup
window.addEventListener('beforeunload', () => {
    if (statusCheckInterval) clearInterval(statusCheckInterval);
});
</script>

<style>
/* Clean, functional styles */
.btn-group-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
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

.quick-amount:hover {
    transform: translateY(-1px);
}

/* Mobile responsive */
@media (max-width: 768px) {
    .btn-group-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .payment-address {
        font-size: 0.8rem;
    }
    
    #qrContainer {
        min-height: 200px !important;
    }
    
    .card-body {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .btn-group-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .input-group-lg .form-control {
        font-size: 1rem;
    }
    
    .row.g-2 > * {
        margin-bottom: 0.5rem;
    }
}

/* Status badge animation */
.badge {
    transition: all 0.3s ease;
}

/* Success state styling */
#successSection .bg-success {
    animation: successPulse 2s ease-in-out;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Print styles */
@media print {
    .btn, .alert-info, .alert-warning {
        display: none !important;
    }
    
    #qrContainer {
        border: 2px solid #000;
    }
}
</style>
@endsection