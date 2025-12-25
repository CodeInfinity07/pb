@extends('admin.layouts.vertical', ['title' => 'Wallet Management', 'subTitle' => 'Manage user crypto wallets'])

@section('content')
<div class="container-fluid">
    {{-- Key Alerts Section --}}
    @if(isset($stats['high_balance_wallets']) && $stats['high_balance_wallets'] > 0)
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <iconify-icon icon="material-symbols:account-balance-wallet" class="fs-5 me-2"></iconify-icon>
                <div class="flex-grow-1">
                    <strong>High Value Wallets!</strong> 
                    {{ $stats['high_balance_wallets'] }} wallets have balances over $1,000.
                    <a href="#" onclick="filterHighBalance()" class="alert-link ms-2">View Details</a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    @endif

    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">Wallet Management</h4>
                            <p class="text-muted mb-0">Manage user crypto wallets and balances</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-success btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#createWalletModal">
                                <iconify-icon icon="mdi:wallet-plus-outline" class="me-1"></iconify-icon>
                                Create Wallet
                            </button>
                            <select class="form-select form-select-sm" id="currencyFilter" name="currency" onchange="loadWallets()" style="width: auto;">
                                <option value="">All Currencies</option>
                                @foreach($cryptocurrencies as $crypto)
                                <option value="{{ $crypto->symbol }}">{{ $crypto->name }} ({{ $crypto->symbol }})</option>
                                @endforeach
                            </select>
                            <select class="form-select form-select-sm" id="statusFilter" name="status" onchange="loadWallets()" style="width: auto;">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <select class="form-select form-select-sm" id="balanceFilter" name="balance_filter" onchange="loadWallets()" style="width: auto;">
                                <option value="">All Balances</option>
                                <option value="with_balance">With Balance</option>
                                <option value="zero_balance">Zero Balance</option>
                                <option value="high_balance">High Balance ($1K+)</option>
                            </select>
                            <div class="input-group" style="width: 250px;">
                                <input type="text" class="form-control form-control-sm" id="searchInput" name="search" 
                                       placeholder="Search users, emails, addresses..." 
                                       onkeyup="debounceSearch(this.value)">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadWallets()">
                                    <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols-light:total-dissolved-solids-outline-rounded" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['total_wallets']) }}</h5>
                    <small class="text-muted">All wallets</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:check-circle-outline" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['active_wallets']) }}</h5>
                    <small class="text-muted">Active wallets</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="solar:dollar-minimalistic-linear" class="text-warning mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">USD Value</h6>
                    <h5 class="mb-0 fw-bold">${{ number_format($stats['total_usd_value'], 0) }}</h5>
                    <small class="text-muted">Total value</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="mdi:bitcoin" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Currencies</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['currencies_count']) }}</h5>
                    <small class="text-muted">Available</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:profile-duotone" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Users</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['users_with_wallets']) }}</h5>
                    <small class="text-muted">With wallets</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card h-100 text-center border-0 shadow-sm">
                <div class="card-body">
                    <iconify-icon icon="material-symbols:brightness-empty-outline" class="text-danger mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Empty</h6>
                    <h5 class="mb-0 fw-bold">{{ number_format($stats['zero_balance_wallets']) }}</h5>
                    <small class="text-muted">Zero balance</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Wallets Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Crypto Wallets</h5>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" id="perPageSelect" onchange="loadWallets()" style="width: auto;">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                            <option value="100">100 per page</option>
                        </select>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="toggleBulkMode()" id="bulkModeBtn">
                                <iconify-icon icon="iconamoon:check-square-duotone"></iconify-icon>
                                Bulk Actions
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="exportWallets()">
                                <iconify-icon icon="iconamoon:download-duotone"></iconify-icon>
                                Export
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Bulk Actions Bar --}}
                <div id="bulkActionsBar" class="alert alert-info d-none mb-0 rounded-0 border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <strong>Bulk Actions:</strong>
                            <span id="selectedCount">0</span> wallets selected
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
                                Delete Empty
                            </button>
                        </div>
                    </div>
                </div>

                <div id="walletsContainer">
                    {{-- Loading state --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="mt-2 text-muted">Loading wallets...</div>
                        </div>
                    </div>
                </div>

                {{-- Pagination --}}
                <div id="paginationContainer" class="card-footer border-top bg-light"></div>
            </div>
        </div>
    </div>
</div>

{{-- Create Wallet Modal --}}
<div class="modal fade" id="createWalletModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:wallet-plus-duotone" class="me-2"></iconify-icon>
                    Create New Wallet
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createWalletForm" novalidate>
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="userSearch" class="form-label">
                            <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                            User <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="userSearch" placeholder="Search users by name or email..." onkeyup="searchUsersForWallet(this.value)" required>
                        <div id="userSearchResults" class="mt-2"></div>
                        <input type="hidden" id="selectedUserId" name="user_id" required>
                        <div id="selectedUserDisplay" class="mt-2"></div>
                        <div class="invalid-feedback">Please select a user.</div>
                    </div>

                    <div class="mb-3">
                        <label for="walletCurrency" class="form-label">
                            <iconify-icon icon="iconamoon:currency-bitcoin-duotone" class="me-1"></iconify-icon>
                            Currency <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="walletCurrency" name="currency" required>
                            <option value="">Select Currency</option>
                            @foreach($cryptocurrencies as $crypto)
                            <option value="{{ $crypto->symbol }}" data-decimals="{{ $crypto->decimal_places }}">
                                {{ $crypto->name }} ({{ $crypto->symbol }})
                            </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback">Please select a currency.</div>
                    </div>

                    <div class="mb-3">
                        <label for="initialBalance" class="form-label">
                            <iconify-icon icon="material-symbols:account-balance-wallet" class="me-1"></iconify-icon>
                            Initial Balance
                        </label>
                        <input type="number" class="form-control" id="initialBalance" name="balance" step="0.00000001" min="0" placeholder="0.00000000">
                        <div class="form-text">Leave empty for zero balance</div>
                    </div>

                    <div class="mb-3">
                        <label for="walletAddress" class="form-label">
                            <iconify-icon icon="iconamoon:link-duotone" class="me-1"></iconify-icon>
                            Wallet Address
                        </label>
                        <input type="text" class="form-control" id="walletAddress" name="address" placeholder="Optional wallet address for withdrawals">
                        <div class="form-text">Address where withdrawals will be sent</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <iconify-icon icon="iconamoon:wallet-plus-duotone" class="me-1"></iconify-icon>
                        Create Wallet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Wallet Details Modal --}}
<div class="modal fade" id="walletDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="material-symbols:account-balance-wallet" class="me-2"></iconify-icon>
                    Wallet Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="walletDetailsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

{{-- Balance Adjustment Modal --}}
<div class="modal fade" id="adjustBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>
                    Adjust Balance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustBalanceForm" novalidate>
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="adjustWalletId">
                    
                    <div class="mb-3">
                        <div id="currentBalanceDisplay" class="alert alert-info"></div>
                    </div>

                    <div class="mb-3">
                        <label for="adjustmentType" class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="adjustmentType" name="type" required>
                            <option value="">Select Action</option>
                            <option value="add">Add to Balance</option>
                            <option value="subtract">Subtract from Balance</option>
                            <option value="set">Set Balance</option>
                        </select>
                        <div class="invalid-feedback">Please select an adjustment type.</div>
                    </div>

                    <div class="mb-3">
                        <label for="adjustmentAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="adjustmentAmount" name="amount" step="0.00000001" min="0.00000001" required>
                        <div class="invalid-feedback">Please enter a valid amount.</div>
                    </div>

                    <div class="mb-3">
                        <label for="adjustmentDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="adjustmentDescription" name="description" rows="3" placeholder="Optional description for this adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                        <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                        Adjust Balance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
let searchTimeout;
let bulkModeActive = false;
let selectedWallets = [];
let isSubmitting = false;

// Load wallets on page load
document.addEventListener('DOMContentLoaded', function() {
    loadWallets();
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

function debounceSearch(value) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        loadWallets();
    }, 500);
}

function loadWallets(page = 1) {
    const container = document.getElementById('walletsContainer');
    const formData = new FormData();
    
    // Collect filter values
    const searchValue = document.getElementById('searchInput').value;
    const currencyValue = document.getElementById('currencyFilter').value;
    const statusValue = document.getElementById('statusFilter').value;
    const balanceValue = document.getElementById('balanceFilter').value;
    const perPageValue = document.getElementById('perPageSelect').value;
    
    if (searchValue) formData.append('search', searchValue);
    if (currencyValue) formData.append('currency', currencyValue);
    if (statusValue) formData.append('status', statusValue);
    if (balanceValue) formData.append('balance_filter', balanceValue);
    formData.append('page', page);
    formData.append('per_page', perPageValue);
    
    // Show loading
    container.innerHTML = `
        <div class="card-body">
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 text-muted">Loading wallets...</div>
            </div>
        </div>
    `;

    const params = new URLSearchParams();
    for (let [key, value] of formData) {
        params.append(key, value);
    }

    fetch(`{{ route('admin.finance.wallets.get-wallets') }}?${params}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayWallets(data.wallets);
            updatePagination(data.wallets);
        } else {
            container.innerHTML = `
                <div class="card-body">
                    <div class="text-center py-5">
                        <iconify-icon icon="iconamoon:close-circle-duotone" class="fs-1 text-danger mb-3"></iconify-icon>
                        <h5 class="text-muted">Error Loading Wallets</h5>
                        <p class="text-muted">${data.message}</p>
                        <button class="btn btn-primary" onclick="loadWallets()">Try Again</button>
                    </div>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        container.innerHTML = `
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:wifi-off-duotone" class="fs-1 text-warning mb-3"></iconify-icon>
                    <h5 class="text-muted">Connection Error</h5>
                    <p class="text-muted">Please check your internet connection and try again.</p>
                    <button class="btn btn-primary" onclick="loadWallets()">Retry</button>
                </div>
            </div>
        `;
    });
}

function displayWallets(walletsData) {
    const container = document.getElementById('walletsContainer');
    
    if (!walletsData.data || walletsData.data.length === 0) {
        container.innerHTML = `
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="material-symbols:account-balance-wallet" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h5 class="text-muted">No wallets found</h5>
                    <p class="text-muted">Try adjusting your search criteria or create a new wallet.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createWalletModal">
                        <iconify-icon icon="iconamoon:wallet-plus-duotone" class="me-1"></iconify-icon>
                        Create Wallet
                    </button>
                </div>
            </div>
        `;
        return;
    }

    // Desktop view
    let desktopTable = `
        <div class="card-body p-0">
            <div class="d-none d-lg-block">
                <div class="table-container">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                ${bulkModeActive ? '<th class="border-0" width="40"><input type="checkbox" class="form-check-input" onchange="toggleAllWallets(this.checked)"></th>' : ''}
                                <th class="border-0">User</th>
                                <th class="border-0">Currency</th>
                                <th class="border-0">Balance</th>
                                <th class="border-0">USD Value</th>
                                <th class="border-0">Address</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Created</th>
                                <th class="border-0 text-center" width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    `;

    walletsData.data.forEach(wallet => {
        const usdValue = wallet.balance * wallet.usd_rate;
        const statusBadge = wallet.is_active ? 'bg-success' : 'bg-secondary';
        const statusText = wallet.is_active ? 'Active' : 'Inactive';
        
        desktopTable += `
            <tr class="wallet-row">
                ${bulkModeActive ? `<td><input type="checkbox" class="form-check-input wallet-checkbox" value="${wallet.id}" onchange="updateSelectedCount()"></td>` : ''}
                <td class="py-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                            <span class="avatar-title text-white fw-semibold">${wallet.user.first_name.charAt(0).toUpperCase()}</span>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">${wallet.user.first_name} ${wallet.user.last_name}</h6>
                            <small class="text-muted">${wallet.user.email}</small>
                        </div>
                    </div>
                </td>
                <td class="py-3">
                    <div class="d-flex align-items-center">
                        <img src="https://predictor.guru/images/icons/19.svg" alt="${wallet.currency}" class="me-2" width="24" height="24" style="border-radius: 50%;">
                        <div>
                            <div class="fw-semibold">${wallet.currency}</div>
                            <small class="text-muted">${wallet.cryptocurrency.name}</small>
                        </div>
                    </div>
                </td>
                <td class="py-3">
                    <div class="fw-semibold">${parseFloat(wallet.balance).toFixed(wallet.cryptocurrency.decimal_places || 8)}</div>
                </td>
                <td class="py-3">
                    <div class="fw-semibold ${usdValue > 1000 ? 'text-warning' : 'text-success'}">$${usdValue.toFixed(2)}</div>
                </td>
                <td class="py-3">
                    <div class="font-monospace small" style="max-width: 120px; overflow: hidden; text-overflow: ellipsis;">
                        ${wallet.address ? wallet.address : '<span class="text-muted">Not Set</span>'}
                    </div>
                </td>
                <td class="py-3">
                    <span class="badge ${statusBadge}">${statusText}</span>
                </td>
                <td class="py-3">
                    <div class="small">
                        <div class="fw-semibold">${new Date(wallet.created_at).toLocaleDateString()}</div>
                        <small class="text-muted">${new Date(wallet.created_at).toLocaleTimeString()}</small>
                    </div>
                </td>
                <td class="py-3 text-center">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Actions
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0">
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="viewWallet(${wallet.id})">
                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="adjustBalance(${wallet.id}, '${wallet.currency}', ${wallet.balance})">
                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Adjust Balance
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="updateAddress(${wallet.id})">
                                    <iconify-icon icon="iconamoon:link-duotone" class="me-2"></iconify-icon>Update Address
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="toggleWalletStatus(${wallet.id})">
                                    <iconify-icon icon="iconamoon:${wallet.is_active ? 'pause' : 'play'}-duotone" class="me-2"></iconify-icon>
                                    ${wallet.is_active ? 'Deactivate' : 'Activate'}
                                </a>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        `;
    });

    desktopTable += `
                        </tbody>
                    </table>
                </div>
            </div>
    `;

    // Mobile view
    let mobileView = `
            <div class="d-lg-none p-3">
                <div class="row g-3">
    `;

    walletsData.data.forEach(wallet => {
        const usdValue = wallet.balance * wallet.usd_rate;
        const statusBadge = wallet.is_active ? 'bg-success' : 'bg-secondary';
        const statusText = wallet.is_active ? 'Active' : 'Inactive';
        
        mobileView += `
                    <div class="col-12">
                        <div class="card wallet-mobile-card border">
                            <div class="card-body p-3">
                                ${bulkModeActive ? `<div class="mb-2"><input type="checkbox" class="form-check-input wallet-checkbox me-2" value="${wallet.id}" onchange="updateSelectedCount()">Select</div>` : ''}
                                
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm rounded-circle bg-primary">
                                            <span class="avatar-title text-white fw-semibold">${wallet.user.first_name.charAt(0).toUpperCase()}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-semibold">${wallet.user.first_name} ${wallet.user.last_name}</h6>
                                            <small class="text-muted">${wallet.user.email}</small>
                                        </div>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="viewWallet(${wallet.id})">
                                                <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                            </a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="adjustBalance(${wallet.id}, '${wallet.currency}', ${wallet.balance})">
                                                <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Adjust Balance
                                            </a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="toggleWalletStatus(${wallet.id})">
                                                <iconify-icon icon="iconamoon:${wallet.is_active ? 'pause' : 'play'}-duotone" class="me-2"></iconify-icon>
                                                ${wallet.is_active ? 'Deactivate' : 'Activate'}
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge ${statusBadge}">${statusText}</span>
                                        <span class="badge bg-secondary">${wallet.currency}</span>
                                        ${usdValue > 1000 ? '<span class="badge bg-warning">High Value</span>' : ''}
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" onclick="toggleWalletDetails('${wallet.id}')">
                                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" id="chevron-${wallet.id}"></iconify-icon>
                                    </button>
                                </div>

                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div>
                                        <small class="text-muted d-block">${wallet.cryptocurrency.name}</small>
                                        <div class="fw-semibold">${parseFloat(wallet.balance).toFixed(4)} ${wallet.currency}</div>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block">USD Value</small>
                                        <div class="fw-semibold ${usdValue > 1000 ? 'text-warning' : 'text-success'}">$${usdValue.toFixed(2)}</div>
                                    </div>
                                </div>

                                <div class="collapse mt-3" id="details-${wallet.id}">
                                    <div class="border-top pt-3">
                                        <div class="row g-2 small">
                                            <div class="col-12">
                                                <div class="text-muted">Wallet Address</div>
                                                <div class="font-monospace small text-break">${wallet.address || 'Not Set'}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Created</div>
                                                <div class="fw-semibold">${new Date(wallet.created_at).toLocaleDateString()}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-muted">Decimals</div>
                                                <div class="fw-semibold">${wallet.cryptocurrency.decimal_places}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        `;
    });

    mobileView += `
                </div>
            </div>
        </div>
    `;

    container.innerHTML = desktopTable + mobileView;
}

function updatePagination(walletsData) {
    const container = document.getElementById('paginationContainer');
    
    if (walletsData.last_page <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHtml = `
        <div class="d-flex align-items-center justify-content-between">
            <div class="text-muted small">
                Showing <span class="fw-semibold">${walletsData.from || 0}</span> to <span class="fw-semibold">${walletsData.to || 0}</span> of <span class="fw-semibold">${walletsData.total}</span> wallets
            </div>
            <nav aria-label="Wallets pagination">
                <ul class="pagination pagination-sm mb-0">
    `;

    // Previous button
    if (walletsData.current_page > 1) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadWallets(${walletsData.current_page - 1})">Previous</a>
            </li>
        `;
    }

    // Page numbers (show max 5 pages around current)
    const startPage = Math.max(1, walletsData.current_page - 2);
    const endPage = Math.min(walletsData.last_page, walletsData.current_page + 2);

    if (startPage > 1) {
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadWallets(1)">1</a></li>`;
        if (startPage > 2) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        paginationHtml += `
            <li class="page-item ${i === walletsData.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadWallets(${i})">${i}</a>
            </li>
        `;
    }

    if (endPage < walletsData.last_page) {
        if (endPage < walletsData.last_page - 1) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="loadWallets(${walletsData.last_page})">${walletsData.last_page}</a></li>`;
    }

    // Next button
    if (walletsData.current_page < walletsData.last_page) {
        paginationHtml += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadWallets(${walletsData.current_page + 1})">Next</a>
            </li>
        `;
    }

    paginationHtml += `
                </ul>
            </nav>
        </div>
    `;

    container.innerHTML = paginationHtml;
}

function viewWallet(walletId) {
    fetch(`{{ route('admin.finance.wallets.show', ':id') }}`.replace(':id', walletId))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayWalletDetails(data.wallet, data.recent_transactions);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to load wallet details', 'error');
    });
}

function displayWalletDetails(wallet, transactions) {
    const usdValue = wallet.balance * wallet.usd_rate;
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Wallet Information</h6>
                <div class="mb-2">
                    <strong>User:</strong> ${wallet.user.first_name} ${wallet.user.last_name}
                </div>
                <div class="mb-2">
                    <strong>Email:</strong> ${wallet.user.email}
                </div>
                <div class="mb-2">
                    <strong>Currency:</strong> 
                    <img src="${wallet.cryptocurrency.icon_url}" alt="${wallet.currency}" width="20" height="20" class="me-1 rounded">
                    ${wallet.cryptocurrency.name} (${wallet.currency})
                </div>
                <div class="mb-2">
                    <strong>Balance:</strong> ${parseFloat(wallet.balance).toFixed(wallet.cryptocurrency.decimal_places || 8)} ${wallet.currency}
                </div>
                <div class="mb-2">
                    <strong>USD Value:</strong> <span class="${usdValue > 1000 ? 'text-warning fw-bold' : 'text-success'}">$${usdValue.toFixed(2)}</span>
                </div>
                <div class="mb-2">
                    <strong>Status:</strong> 
                    <span class="badge ${wallet.is_active ? 'bg-success' : 'bg-secondary'}">
                        ${wallet.is_active ? 'Active' : 'Inactive'}
                    </span>
                </div>
                <div class="mb-2">
                    <strong>Created:</strong> ${new Date(wallet.created_at).toLocaleString()}
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold mb-3">Withdrawal Address</h6>
                <div class="mb-3">
                    <div class="font-monospace small p-2 bg-light rounded text-break">
                        ${wallet.address || 'No address set'}
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Quick Actions</h6>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-sm" onclick="adjustBalance(${wallet.id}, '${wallet.currency}', ${wallet.balance})">
                        <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                        Adjust Balance
                    </button>
                    <button class="btn btn-${wallet.is_active ? 'warning' : 'success'} btn-sm" onclick="toggleWalletStatus(${wallet.id})">
                        <iconify-icon icon="iconamoon:${wallet.is_active ? 'pause' : 'play'}-duotone" class="me-1"></iconify-icon>
                        ${wallet.is_active ? 'Deactivate' : 'Activate'} Wallet
                    </button>
                </div>
            </div>
        </div>
        
        ${transactions.length > 0 ? `
        <hr>
        <h6 class="fw-bold mb-3">Recent Transactions</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    ${transactions.map(tx => `
                        <tr>
                            <td>${tx.type}</td>
                            <td>${tx.amount}</td>
                            <td><span class="badge bg-${tx.status === 'completed' ? 'success' : 'warning'}">${tx.status}</span></td>
                            <td>${new Date(tx.created_at).toLocaleDateString()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ` : ''}
    `;
    
    document.getElementById('walletDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('walletDetailsModal')).show();
}

function adjustBalance(walletId, currency, currentBalance) {
    document.getElementById('adjustWalletId').value = walletId;
    document.getElementById('currentBalanceDisplay').innerHTML = `
        <strong>Current Balance:</strong> ${parseFloat(currentBalance).toFixed(8)} ${currency}
    `;
    
    // Reset form
    const form = document.getElementById('adjustBalanceForm');
    form.classList.remove('was-validated');
    form.reset();
    document.getElementById('adjustWalletId').value = walletId;
    
    new bootstrap.Modal(document.getElementById('adjustBalanceModal')).show();
}

function submitBalanceAdjustment() {
    const form = document.getElementById('adjustBalanceForm');
    
    if (isSubmitting || !validateForm(form)) return;
    
    isSubmitting = true;
    toggleLoading(form, true);

    const walletId = document.getElementById('adjustWalletId').value;
    const formData = new FormData(form);

    fetch(`{{ route('admin.finance.wallets.adjust-balance', ':id') }}`.replace(':id', walletId), {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('adjustBalanceModal')).hide();
            loadWallets(); // Refresh the wallets list
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to adjust balance', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(form, false);
    });
}

function toggleWalletStatus(walletId) {
    fetch(`{{ route('admin.finance.wallets.toggle-status', ':id') }}`.replace(':id', walletId), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadWallets(); // Refresh the wallets list
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update wallet status', 'danger');
    });
}

function updateAddress(walletId) {
    const address = prompt('Enter new wallet address (leave empty to clear):');
    if (address !== null) {
        fetch(`{{ route('admin.finance.wallets.update-address', ':id') }}`.replace(':id', walletId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ address: address.trim() })
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                loadWallets();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to update address', 'danger');
        });
    }
}

function searchUsersForWallet(query) {
    if (query.length < 2) {
        document.getElementById('userSearchResults').innerHTML = '';
        return;
    }

    fetch(`{{ route('admin.finance.wallets.search-users') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ search: query })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayUserSearchResults(data.users);
        }
    })
    .catch(error => console.error('Error:', error));
}

function displayUserSearchResults(users) {
    const container = document.getElementById('userSearchResults');
    container.innerHTML = '';
    
    if (users.length === 0) {
        container.innerHTML = '<div class="text-muted small">No users found</div>';
        return;
    }
    
    users.forEach(user => {
        const div = document.createElement('div');
        div.className = 'user-search-item p-2 border rounded mb-1 cursor-pointer';
        div.innerHTML = `
            <div class="fw-semibold">${user.first_name} ${user.last_name}</div>
            <div class="text-muted small">${user.email}</div>
        `;
        div.onclick = () => selectUserForWallet(user);
        container.appendChild(div);
    });
}

function selectUserForWallet(user) {
    document.getElementById('selectedUserId').value = user.id;
    document.getElementById('userSearch').value = '';
    document.getElementById('userSearchResults').innerHTML = '';
    document.getElementById('selectedUserDisplay').innerHTML = `
        <div class="alert alert-success">
            <strong>Selected User:</strong> ${user.first_name} ${user.last_name} (${user.email})
        </div>
    `;
}

function createWallet() {
    const form = document.getElementById('createWalletForm');
    
    if (isSubmitting || !validateForm(form)) return;
    
    isSubmitting = true;
    toggleLoading(form, true);

    const formData = new FormData(form);

    fetch(`{{ route('admin.finance.wallets.create') }}`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('createWalletModal')).hide();
            loadWallets(); // Refresh the wallets list
            
            // Reset form
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('selectedUserDisplay').innerHTML = '';
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to create wallet', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(form, false);
    });
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('currencyFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('balanceFilter').value = '';
    loadWallets();
}

function exportWallets() {
    const formData = new FormData();
    
    const currencyValue = document.getElementById('currencyFilter').value;
    const statusValue = document.getElementById('statusFilter').value;
    
    if (currencyValue) formData.append('currency', currencyValue);
    if (statusValue) formData.append('status', statusValue);
    
    const params = new URLSearchParams(formData);
    window.location.href = `{{ route('admin.finance.wallets.export') }}?${params}`;
}

function filterHighBalance() {
    document.getElementById('balanceFilter').value = 'high_balance';
    loadWallets();
}

function toggleBulkMode() {
    bulkModeActive = !bulkModeActive;
    const btn = document.getElementById('bulkModeBtn');
    const bar = document.getElementById('bulkActionsBar');
    
    if (bulkModeActive) {
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-primary');
        bar.classList.remove('d-none');
    } else {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
        bar.classList.add('d-none');
        selectedWallets = [];
    }
    
    loadWallets(); // Refresh to show/hide checkboxes
}

function toggleAllWallets(checked) {
    const checkboxes = document.querySelectorAll('.wallet-checkbox');
    checkboxes.forEach(cb => cb.checked = checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.wallet-checkbox:checked');
    selectedWallets = Array.from(checkboxes).map(cb => cb.value);
    document.getElementById('selectedCount').textContent = selectedWallets.length;
}

function bulkAction(action) {
    if (selectedWallets.length === 0) {
        showAlert('Please select wallets first', 'danger');
        return;
    }
    
    let confirmMessage = '';
    switch(action) {
        case 'activate':
            confirmMessage = `Activate ${selectedWallets.length} selected wallets?`;
            break;
        case 'deactivate':
            confirmMessage = `Deactivate ${selectedWallets.length} selected wallets?`;
            break;
        case 'delete':
            confirmMessage = `Delete ${selectedWallets.length} selected empty wallets? (Only wallets with zero balance will be deleted)`;
            break;
    }
    
    if (!confirm(confirmMessage)) return;
    
    fetch(`{{ route('admin.finance.wallets.bulk-action') }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            action: action,
            wallet_ids: selectedWallets
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            loadWallets();
            selectedWallets = [];
            updateSelectedCount();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Bulk action failed', 'danger');
    });
}

// Mobile specific functions
function toggleWalletDetails(walletId) {
    const detailsElement = document.getElementById(`details-${walletId}`);
    const chevronElement = document.getElementById(`chevron-${walletId}`);
    
    // Close all other open details
    document.querySelectorAll('.collapse.show').forEach(element => {
        if (element.id !== `details-${walletId}`) {
            element.classList.remove('show');
        }
    });
    
    document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
        if (chevron.id !== `chevron-${walletId}`) {
            chevron.style.transform = 'rotate(0deg)';
        }
    });
    
    // Toggle current details
    if (detailsElement.classList.contains('show')) {
        detailsElement.classList.remove('show');
        chevronElement.style.transform = 'rotate(0deg)';
    } else {
        detailsElement.classList.add('show');
        chevronElement.style.transform = 'rotate(180deg)';
    }
}

// Event Listeners
document.getElementById('createWalletForm').addEventListener('submit', function(e) {
    e.preventDefault();
    createWallet();
});

document.getElementById('adjustBalanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    submitBalanceAdjustment();
});

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        loadWallets();
    }
});

// Reset modals when closed
document.getElementById('createWalletModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('createWalletForm');
    form.reset();
    form.classList.remove('was-validated');
    document.getElementById('selectedUserDisplay').innerHTML = '';
    document.getElementById('userSearchResults').innerHTML = '';
});

document.getElementById('adjustBalanceModal').addEventListener('hidden.bs.modal', function() {
    const form = document.getElementById('adjustBalanceForm');
    form.reset();
    form.classList.remove('was-validated');
});

// Close mobile details when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.wallet-mobile-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});
</script>

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

/* Avatar Styles */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    border-radius: 50%;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.875rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
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

.wallet-row {
    transition: background-color 0.15s ease-in-out;
}

.wallet-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
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
.wallet-mobile-card {
    transition: all 0.2s ease;
    border-radius: 0.75rem;
}

.wallet-mobile-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.collapse {
    transition: height 0.35s ease;
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

/* User search item styles */
.user-search-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-search-item:hover {
    background-color: #f8f9fa !important;
}

.cursor-pointer {
    cursor: pointer;
}

/* Loading Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Font styles */
.font-monospace {
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
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
    .wallet-mobile-card .card-body {
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

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Alert Positioning */
#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

/* Text utilities */
.text-break {
    word-wrap: break-word !important;
    word-break: break-word !important;
}
</style>
@endsection