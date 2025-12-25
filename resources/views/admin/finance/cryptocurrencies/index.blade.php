@extends('admin.layouts.vertical', ['title' => 'Cryptocurrency Management', 'subTitle' => 'Manage supported cryptocurrencies'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Cryptocurrency Management</h4>
                            <p class="text-muted mb-0">Manage supported cryptocurrencies, networks, and withdrawal settings</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createCryptoModal">
                                <iconify-icon icon="tabler:coin-bitcoin" class="me-1"></iconify-icon>
                                Add Cryptocurrency
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center" onclick="toggleBulkMode()" id="bulkModeBtn">
                                <iconify-icon icon="streamline-ultimate:single-neutral-actions-check-2" class="me-1"></iconify-icon>
                                Bulk Actions
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center" onclick="refreshStats()">
                                <iconify-icon icon="material-symbols:refresh-rounded" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols-light:total-dissolved-solids-outline-rounded" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_cryptocurrencies']) }}</h5>
                    <small class="text-muted">Cryptocurrencies</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:check-circle" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['active_cryptocurrencies']) }}</h5>
                    <small class="text-muted">Available</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:account-balance-wallet-sharp" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Wallets</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_wallets']) }}</h5>
                    <small class="text-muted">Created</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:link-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">In Use</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['currencies_with_wallets']) }}</h5>
                    <small class="text-muted">With wallets</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    <div id="bulkActionsBar" class="alert alert-info d-none mb-4 rounded">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <strong>Bulk Actions:</strong>
                <span id="selectedCount">0</span> cryptocurrencies selected
            </div>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-success" onclick="bulkAction('activate')">
                    <iconify-icon icon="iconamoon:play-duotone" class="me-1"></iconify-icon>
                    Activate
                </button>
                <button type="button" class="btn btn-warning" onclick="bulkAction('deactivate')">
                    <iconify-icon icon="iconamoon:pause-duotone" class="me-1"></iconify-icon>
                    Deactivate
                </button>
                <button type="button" class="btn btn-danger" onclick="bulkAction('delete')">
                    <iconify-icon icon="iconamoon:trash-duotone" class="me-1"></iconify-icon>
                    Delete Unused
                </button>
            </div>
        </div>
    </div>

    {{-- Cryptocurrencies Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Supported Cryptocurrencies</h5>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">Drag to reorder</small>
                    </div>
                </div>

                @if($cryptocurrencies->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop Table View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-container">
                                <table class="table table-hover align-middle mb-0" id="cryptocurrenciesTable">
                                    <thead class="table-light">
                                        <tr>
                                            @if(false) {{-- Bulk mode checkbox --}}
                                            <th class="border-0" width="40">
                                                <input type="checkbox" class="form-check-input" onchange="toggleAllCryptos(this.checked)" id="selectAllCheckbox" style="display: none;">
                                            </th>
                                            @endif
                                            <th class="border-0" width="60">Order</th>
                                            <th class="border-0">Currency</th>
                                            <th class="border-0">Network</th>
                                            <th class="border-0">Withdrawal Settings</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Wallets</th>
                                            <th class="border-0 text-center" width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sortableCryptos">
                                        @foreach($cryptocurrencies as $crypto)
                                            <tr class="crypto-row sortable-row" data-id="{{ $crypto->id }}">
                                                @if(false) {{-- Bulk mode checkbox --}}
                                                <td>
                                                    <input type="checkbox" class="form-check-input crypto-checkbox" value="{{ $crypto->id }}" onchange="updateSelectedCount()" style="display: none;">
                                                </td>
                                                @endif
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <iconify-icon icon="iconamoon:menu-hamburger-duotone" class="text-muted me-2 drag-handle" style="cursor: grab;"></iconify-icon>
                                                        <span class="badge bg-secondary">{{ $crypto->sort_order }}</span>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ $crypto->icon_url }}" alt="{{ $crypto->symbol }}" class="me-3 rounded" width="32" height="32" style="object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $crypto->name }}</h6>
                                                            <small class="text-muted">{{ $crypto->symbol }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div>
                                                        <div class="fw-semibold">{{ $crypto->network }}</div>
                                                        @if($crypto->contract_address)
                                                            <small class="text-muted font-monospace">{{ Str::limit($crypto->contract_address, 20) }}</small>
                                                        @else
                                                            <small class="text-muted">Native Token</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <div class="small">
                                                        <div><strong>Min:</strong> {{ number_format($crypto->min_withdrawal, $crypto->decimal_places) }} {{ $crypto->symbol }}</div>
                                                        <div><strong>Max:</strong> {{ $crypto->max_withdrawal ? number_format($crypto->max_withdrawal, $crypto->decimal_places) . ' ' . $crypto->symbol : 'No limit' }}</div>
                                                        <div><strong>Fee:</strong> {{ number_format($crypto->withdrawal_fee, $crypto->decimal_places) }} {{ $crypto->symbol }}</div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $crypto->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                        {{ $crypto->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <div class="text-center">
                                                        <span class="badge {{ $crypto->crypto_wallets_count > 0 ? 'bg-info' : 'bg-light text-dark' }}">
                                                            {{ number_format($crypto->crypto_wallets_count) }}
                                                        </span>
                                                        <small class="d-block text-muted">wallets</small>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewCrypto({{ $crypto->id }})">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="editCrypto({{ $crypto->id }})">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Settings
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" onclick="toggleCryptoStatus({{ $crypto->id }})">
                                                                    <iconify-icon icon="iconamoon:{{ $crypto->is_active ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                                                                    {{ $crypto->is_active ? 'Deactivate' : 'Activate' }}
                                                                </a>
                                                            </li>
                                                            @if($crypto->crypto_wallets_count == 0)
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="deleteCrypto({{ $crypto->id }}, '{{ $crypto->name }}')">
                                                                    <iconify-icon icon="iconamoon:trash-duotone" class="me-2"></iconify-icon>Delete
                                                                </a>
                                                            </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile Card View --}}
                        <div class="d-lg-none p-3">
                            <div class="row g-3" id="mobileCryptos">
                                @foreach($cryptocurrencies as $crypto)
                                    <div class="col-12" data-id="{{ $crypto->id }}">
                                        <div class="card crypto-mobile-card border">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="{{ $crypto->icon_url }}" alt="{{ $crypto->symbol }}" class="rounded" width="32" height="32">
                                                        <div>
                                                            <h6 class="mb-0 fw-semibold">{{ $crypto->name }}</h6>
                                                            <small class="text-muted">{{ $crypto->symbol }} • {{ $crypto->network }}</small>
                                                        </div>
                                                    </div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="viewCrypto({{ $crypto->id }})">
                                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="editCrypto({{ $crypto->id }})">
                                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Edit Settings
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="toggleCryptoStatus({{ $crypto->id }})">
                                                                <iconify-icon icon="iconamoon:{{ $crypto->is_active ? 'pause' : 'play' }}-duotone" class="me-2"></iconify-icon>
                                                                {{ $crypto->is_active ? 'Deactivate' : 'Activate' }}
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-between mb-3">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge {{ $crypto->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                            {{ $crypto->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                        <span class="badge bg-info">{{ $crypto->crypto_wallets_count }} wallets</span>
                                                        <span class="badge bg-secondary">Order: {{ $crypto->sort_order }}</span>
                                                    </div>
                                                </div>

                                                <div class="row g-2 small">
                                                    <div class="col-6">
                                                        <div class="text-muted">Min Withdrawal</div>
                                                        <div class="fw-semibold">{{ number_format($crypto->min_withdrawal, 4) }} {{ $crypto->symbol }}</div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="text-muted">Withdrawal Fee</div>
                                                        <div class="fw-semibold">{{ number_format($crypto->withdrawal_fee, 4) }} {{ $crypto->symbol }}</div>
                                                    </div>
                                                    @if($crypto->contract_address)
                                                    <div class="col-12">
                                                        <div class="text-muted">Contract Address</div>
                                                        <div class="font-monospace small text-break">{{ $crypto->contract_address }}</div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:currency-bitcoin-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h5 class="text-muted">No Cryptocurrencies Found</h5>
                            <p class="text-muted">Add your first cryptocurrency to get started with wallet management.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCryptoModal">
                                <iconify-icon icon="iconamoon:currency-bitcoin-plus-duotone" class="me-1"></iconify-icon>
                                Add Cryptocurrency
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Create/Edit Cryptocurrency Modal --}}
<div class="modal fade" id="createCryptoModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:currency-bitcoin-plus-duotone" class="me-2"></iconify-icon>
                    <span id="modalTitle">Add New Cryptocurrency</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cryptoForm" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" id="cryptoId" name="crypto_id">
                <input type="hidden" id="formMethod" value="POST">
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="cryptoName" class="form-label">
                                <iconify-icon icon="iconamoon:currency-bitcoin-duotone" class="me-1"></iconify-icon>
                                Cryptocurrency Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="cryptoName" name="name" required placeholder="e.g., Bitcoin">
                            <div class="invalid-feedback">Please provide a cryptocurrency name.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="cryptoSymbol" class="form-label">
                                Symbol <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="cryptoSymbol" name="symbol" required placeholder="e.g., BTC" maxlength="10" style="text-transform: uppercase;">
                            <div class="invalid-feedback">Please provide a symbol.</div>
                        </div>
                        
                        <div class="col-md-8">
                            <label for="cryptoNetwork" class="form-label">
                                <iconify-icon icon="iconamoon:link-duotone" class="me-1"></iconify-icon>
                                Network <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="cryptoNetwork" name="network" required placeholder="e.g., Bitcoin, Ethereum, BSC">
                            <div class="invalid-feedback">Please provide the network.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="decimalPlaces" class="form-label">
                                Decimal Places <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="decimalPlaces" name="decimal_places" required min="0" max="18" value="8">
                            <div class="invalid-feedback">Please provide decimal places.</div>
                        </div>

                        <div class="col-12">
                            <label for="contractAddress" class="form-label">
                                <iconify-icon icon="iconamoon:code-duotone" class="me-1"></iconify-icon>
                                Contract Address
                            </label>
                            <input type="text" class="form-control font-monospace" id="contractAddress" name="contract_address" placeholder="Leave empty for native tokens">
                            <div class="form-text">For ERC-20, BEP-20 tokens, etc. Leave empty for native blockchain tokens.</div>
                        </div>

                        <div class="col-md-4">
                            <label for="minWithdrawal" class="form-label">
                                <iconify-icon icon="iconamoon:arrow-down-duotone" class="me-1"></iconify-icon>
                                Min Withdrawal <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="minWithdrawal" name="min_withdrawal" required min="0" step="0.00000001">
                            <div class="invalid-feedback">Please provide minimum withdrawal amount.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="maxWithdrawal" class="form-label">
                                Max Withdrawal
                            </label>
                            <input type="number" class="form-control" id="maxWithdrawal" name="max_withdrawal" min="0" step="0.00000001" placeholder="No limit">
                        </div>
                        <div class="col-md-4">
                            <label for="withdrawalFee" class="form-label">
                                <iconify-icon icon="iconamoon:coin-duotone" class="me-1"></iconify-icon>
                                Withdrawal Fee <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="withdrawalFee" name="withdrawal_fee" required min="0" step="0.00000001">
                            <div class="invalid-feedback">Please provide withdrawal fee.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="sortOrder" class="form-label">
                                <iconify-icon icon="iconamoon:sort-duotone" class="me-1"></iconify-icon>
                                Sort Order <span class="text-danger">*</span>
                            </label>
                            <input type="number" class="form-control" id="sortOrder" name="sort_order" required min="0" value="{{ ($cryptocurrencies->max('sort_order') ?? 0) + 1 }}">
                            <div class="invalid-feedback">Please provide sort order.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                <label class="form-check-label" for="isActive">Active</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="cryptoIcon" class="form-label">
                                <iconify-icon icon="iconamoon:image-duotone" class="me-1"></iconify-icon>
                                Icon Image
                            </label>
                            <input type="file" class="form-control" id="cryptoIcon" name="icon" accept="image/*">
                            <div class="form-text">Upload PNG, JPG, SVG, or WebP. Recommended: 64x64px or larger, square aspect ratio.</div>
                            <div id="currentIcon" class="mt-2" style="display: none;">
                                <small class="text-muted">Current icon:</small>
                                <img id="currentIconImage" src="" alt="Current icon" class="ms-2 rounded" width="32" height="32">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox" id="removeIcon" name="remove_icon">
                                    <label class="form-check-label text-danger" for="removeIcon">Remove current icon</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                        <span id="submitText">Add Cryptocurrency</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Crypto Details Modal --}}
<div class="modal fade" id="cryptoDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:currency-bitcoin-duotone" class="me-2"></iconify-icon>
                    Cryptocurrency Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="cryptoDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
let bulkModeActive = false;
let selectedCryptos = [];
let isSubmitting = false;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
});

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

function toggleLoading(form, isLoading) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    
    if (isLoading) {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
    } else {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
    }
}

function validateForm(form) {
    form.classList.add('was-validated');
    return form.checkValidity();
}

// Cryptocurrency Management Functions
function viewCrypto(cryptoId) {
    fetch(`{{ route('admin.finance.cryptocurrencies.show', ':id') }}`.replace(':id', cryptoId))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCryptoDetails(data.cryptocurrency);
        } else {
            showAlert(data.message || 'Failed to load details', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to load cryptocurrency details', 'danger');
    });
}

function displayCryptoDetails(crypto) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Basic Information</h6>
                <div class="d-flex align-items-center mb-3">
                    <img src="${crypto.icon_url}" alt="${crypto.symbol}" class="me-3 rounded" width="48" height="48">
                    <div>
                        <h5 class="mb-0">${crypto.name}</h5>
                        <small class="text-muted">${crypto.symbol}</small>
                    </div>
                </div>
                <div class="mb-2"><strong>Network:</strong> ${crypto.network}</div>
                <div class="mb-2"><strong>Decimal Places:</strong> ${crypto.decimal_places}</div>
                <div class="mb-2"><strong>Sort Order:</strong> ${crypto.sort_order}</div>
                <div class="mb-2">
                    <strong>Status:</strong> 
                    <span class="badge ${crypto.is_active ? 'bg-success' : 'bg-secondary'}">
                        ${crypto.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
                ${crypto.contract_address ? `<div class="mb-2"><strong>Contract:</strong><br><small class="font-monospace text-break">${crypto.contract_address}</small></div>` : ''}
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Withdrawal Settings</h6>
                <div class="mb-2">
                    <strong>Minimum Withdrawal:</strong><br>
                    ${parseFloat(crypto.min_withdrawal).toFixed(crypto.decimal_places)} ${crypto.symbol}
                </div>
                <div class="mb-2">
                    <strong>Maximum Withdrawal:</strong><br>
                    ${crypto.max_withdrawal ? parseFloat(crypto.max_withdrawal).toFixed(crypto.decimal_places) + ' ' + crypto.symbol : 'No limit'}
                </div>
                <div class="mb-2">
                    <strong>Withdrawal Fee:</strong><br>
                    ${parseFloat(crypto.withdrawal_fee).toFixed(crypto.decimal_places)} ${crypto.symbol}
                </div>
                
                <h6 class="fw-bold mb-3 mt-4">Usage Statistics</h6>
                <div class="mb-2">
                    <strong>Associated Wallets:</strong> ${crypto.crypto_wallets_count || 0}
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button class="btn btn-primary btn-sm" onclick="editCrypto(${crypto.id})">
                        <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                        Edit Cryptocurrency
                    </button>
                    <button class="btn btn-${crypto.is_active ? 'warning' : 'success'} btn-sm" onclick="toggleCryptoStatus(${crypto.id})">
                        <iconify-icon icon="iconamoon:${crypto.is_active ? 'pause' : 'play'}-duotone" class="me-1"></iconify-icon>
                        ${crypto.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('cryptoDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('cryptoDetailsModal')).show();
}

function editCrypto(cryptoId) {
    // Close details modal if open
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('cryptoDetailsModal'));
    if (detailsModal) {
        detailsModal.hide();
    }
    
    fetch(`{{ route('admin.finance.cryptocurrencies.show', ':id') }}`.replace(':id', cryptoId))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateEditForm(data.cryptocurrency);
        } else {
            showAlert(data.message || 'Failed to load cryptocurrency data', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to load cryptocurrency data', 'danger');
    });
}

function populateEditForm(crypto) {
    // Update modal title and button text
    document.getElementById('modalTitle').textContent = 'Edit Cryptocurrency';
    document.getElementById('submitText').textContent = 'Update Cryptocurrency';
    
    // Set form method and crypto ID
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('cryptoId').value = crypto.id;
    
    // Populate form fields
    document.getElementById('cryptoName').value = crypto.name;
    document.getElementById('cryptoSymbol').value = crypto.symbol;
    document.getElementById('cryptoNetwork').value = crypto.network;
    document.getElementById('contractAddress').value = crypto.contract_address || '';
    document.getElementById('decimalPlaces').value = crypto.decimal_places;
    document.getElementById('minWithdrawal').value = crypto.min_withdrawal;
    document.getElementById('maxWithdrawal').value = crypto.max_withdrawal || '';
    document.getElementById('withdrawalFee').value = crypto.withdrawal_fee;
    document.getElementById('sortOrder').value = crypto.sort_order;
    document.getElementById('isActive').checked = crypto.is_active;
    
    // Show current icon if exists
    if (crypto.icon) {
        document.getElementById('currentIcon').style.display = 'block';
        document.getElementById('currentIconImage').src = crypto.icon_url;
    } else {
        document.getElementById('currentIcon').style.display = 'none';
    }
    
    // Show modal
    new bootstrap.Modal(document.getElementById('createCryptoModal')).show();
}

function toggleCryptoStatus(cryptoId) {
    fetch(`{{ route('admin.finance.cryptocurrencies.toggle-status', ':id') }}`.replace(':id', cryptoId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update cryptocurrency status', 'danger');
    });
}

function deleteCrypto(cryptoId, cryptoName) {
    if (confirm(`Are you sure you want to delete "${cryptoName}"? This action cannot be undone.`)) {
        fetch(`{{ route('admin.finance.cryptocurrencies.destroy', ':id') }}`.replace(':id', cryptoId), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message || 'Failed to delete cryptocurrency', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to delete cryptocurrency', 'danger');
        });
    }
}

// Form Submission

// Form Submission with Boolean Fix
document.getElementById('cryptoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (isSubmitting || !validateForm(this)) return;
    
    isSubmitting = true;
    toggleLoading(this, true);
    
    const formData = new FormData(this);
    const method = document.getElementById('formMethod').value;
    const cryptoId = document.getElementById('cryptoId').value;
    
    // FIX: Handle boolean fields properly
    const isActiveCheckbox = document.getElementById('isActive');
    const removeIconCheckbox = document.getElementById('removeIcon');
    
    // Remove any existing boolean fields from FormData
    formData.delete('is_active');
    formData.delete('remove_icon');
    
    // Add proper boolean values
    formData.append('is_active', isActiveCheckbox.checked ? '1' : '0');
    formData.append('remove_icon', removeIconCheckbox.checked ? '1' : '0');
    
    // Debug: Log what we're sending
    console.log('Boolean values:');
    console.log('is_active:', formData.get('is_active'));
    console.log('remove_icon:', formData.get('remove_icon'));
    
    let url, httpMethod;
    if (method === 'PUT' && cryptoId) {
        url = `{{ route('admin.finance.cryptocurrencies.update', ':id') }}`.replace(':id', cryptoId);
        httpMethod = 'POST';
        formData.append('_method', 'PUT');
    } else {
        url = `{{ route('admin.finance.cryptocurrencies.store') }}`;
        httpMethod = 'POST';
    }
    
    fetch(url, {
        method: httpMethod,
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Non-JSON response:', text.substring(0, 500));
                throw new Error('Server returned non-JSON response');
            });
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createCryptoModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            if (data.errors) {
                displayValidationErrors(data.errors);
                showAlert('Please fix the validation errors below', 'danger');
            } else {
                showAlert(data.message || 'Operation failed', 'danger');
            }
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        showAlert('An error occurred: ' + error.message, 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(this, false);
    });
});

// Sortable functionality
function initializeSortable() {
    const sortableElement = document.getElementById('sortableCryptos');
    if (sortableElement && window.Sortable) {
        Sortable.create(sortableElement, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function(evt) {
                updateCryptoOrder();
            }
        });
    }
}

function updateCryptoOrder() {
    const rows = document.querySelectorAll('#sortableCryptos .sortable-row');
    const order = Array.from(rows).map(row => row.dataset.id);
    
    fetch(`{{ route('admin.finance.cryptocurrencies.update-order') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ cryptocurrencies: order })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
        } else {
            showAlert('Failed to update order', 'danger');
            location.reload(); // Reload to restore original order
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update order', 'danger');
        location.reload(); // Reload to restore original order
    });
}

// Bulk Actions
function toggleBulkMode() {
    bulkModeActive = !bulkModeActive;
    const btn = document.getElementById('bulkModeBtn');
    const bar = document.getElementById('bulkActionsBar');
    const checkboxes = document.querySelectorAll('.crypto-checkbox');
    const selectAll = document.getElementById('selectAllCheckbox');
    
    if (bulkModeActive) {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');
        bar.classList.remove('d-none');
        checkboxes.forEach(cb => cb.style.display = 'block');
        if (selectAll) selectAll.style.display = 'block';
    } else {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
        bar.classList.add('d-none');
        checkboxes.forEach(cb => {
            cb.style.display = 'none';
            cb.checked = false;
        });
        if (selectAll) {
            selectAll.style.display = 'none';
            selectAll.checked = false;
        }
        selectedCryptos = [];
        updateSelectedCount();
    }
}

function toggleAllCryptos(checked) {
    const checkboxes = document.querySelectorAll('.crypto-checkbox');
    checkboxes.forEach(cb => cb.checked = checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.crypto-checkbox:checked');
    selectedCryptos = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('selectedCount').textContent = selectedCryptos.length;
}

function bulkAction(action) {
    if (selectedCryptos.length === 0) {
        showAlert('Please select cryptocurrencies first', 'danger');
        return;
    }
    
    let confirmMessage = '';
    switch(action) {
        case 'activate':
            confirmMessage = `Activate ${selectedCryptos.length} selected cryptocurrencies?`;
            break;
        case 'deactivate':
            confirmMessage = `Deactivate ${selectedCryptos.length} selected cryptocurrencies?`;
            break;
        case 'delete':
            confirmMessage = `Delete ${selectedCryptos.length} selected cryptocurrencies? (Only unused cryptocurrencies will be deleted)`;
            break;
    }
    
    if (!confirm(confirmMessage)) return;
    
    fetch(`{{ route('admin.finance.cryptocurrencies.bulk-action') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            action: action,
            cryptocurrency_ids: selectedCryptos
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Bulk action failed', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Bulk action failed', 'danger');
    });
}

function refreshStats() {
    showAlert('Statistics refreshed', 'success');
    setTimeout(() => location.reload(), 500);
}

// Modal Reset
document.getElementById('createCryptoModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('cryptoForm');
    form.reset();
    form.classList.remove('was-validated');
    
    // Reset modal to create mode
    document.getElementById('modalTitle').textContent = 'Add New Cryptocurrency';
    document.getElementById('submitText').textContent = 'Add Cryptocurrency';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('cryptoId').value = '';
    document.getElementById('currentIcon').style.display = 'none';
    document.getElementById('removeIcon').checked = false;
});

// Auto-uppercase symbol field
document.getElementById('cryptoSymbol').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>

{{-- Include Sortable.js for drag-and-drop ordering --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<style>
/* Base Styles */
.card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
}

/* Table Styles */
.table-container {
    position: relative;
    overflow: visible;
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.crypto-row {
    transition: background-color 0.15s ease-in-out;
}

.crypto-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Drag Handle */
.drag-handle {
    cursor: grab;
    opacity: 0.6;
    transition: opacity 0.2s ease;
}

.drag-handle:hover {
    opacity: 1;
}

.sortable-ghost {
    opacity: 0.4;
    background-color: rgba(0, 123, 255, 0.1);
}

/* Dropdown Styles */
.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    min-width: 10rem;
    padding: 0.5rem 0;
    margin: 0.125rem 0 0;
    font-size: 0.875rem;
    color: #212529;
    text-align: left;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid rgba(0, 0, 0, 0.15);
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-menu.show {
    display: block;
}

.dropdown-menu-end {
    right: 0;
    left: auto;
}

.dropdown-item {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 1rem;
    clear: both;
    font-weight: 400;
    color: #212529;
    text-align: inherit;
    text-decoration: none;
    white-space: nowrap;
    background-color: transparent;
    border: 0;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out;
}

.dropdown-item:hover,
.dropdown-item:focus {
    color: #1e2125;
    background-color: #e9ecef;
}

.dropdown-item.text-danger:hover,
.dropdown-item.text-danger:focus {
    color: #fff;
    background-color: #dc3545;
}

.dropdown-divider {
    height: 0;
    margin: 0.5rem 0;
    overflow: hidden;
    border-top: 1px solid rgba(0, 0, 0, 0.15);
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
    display: inline-flex;
    align-items: center;
}

/* Mobile Card Styles */
.crypto-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.crypto-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Form Styles */
.form-control,
.form-select {
    border-radius: 0.5rem;
    border: 1px solid #ced4da;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
}

/* Button Styles */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Font styles */
.font-monospace {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
}

/* Text utilities */
.text-break {
    word-wrap: break-word !important;
    word-break: break-word !important;
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
    
    .table-container {
        overflow-x: auto;
    }
}

@media (max-width: 767.98px) {
    .crypto-mobile-card .card-body {
        padding: 1rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

/* Alert Positioning */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

/* Loading Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
</style>
@endsection