@extends('admin.layouts.vertical', ['title' => 'All Transactions', 'subTitle' => 'Finance'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            {{-- Header with Filters --}}
            <div class="d-flex card-header justify-content-between align-items-center">
                <h4 class="card-title mb-0">All Transactions</h4>
                <div class="flex-shrink-0">
                    <div class="d-flex gap-2 flex-wrap">
                        <select class="form-select form-select-sm" onchange="filterTransactions('status', this.value)" id="statusFilter">
                            <option value="">All Status</option>
                            @foreach($filterOptions['statuses'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="form-select form-select-sm" onchange="handleDateRangeChange(this.value)" id="predefinedDateRange">
                            @foreach($filterOptions['date_ranges'] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                            <option value="custom">Custom Range</option>
                        </select>
                        <input type="text" id="customDateRange" class="form-control form-control-sm" placeholder="Select custom dates" style="min-width: 200px; display: none;">
                        <button type="button" class="btn btn-sm btn-outline-danger" id="clearCustomDateFilter" style="display: none;">
                            <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                            <span class="d-none d-sm-inline ms-1">Clear</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Transaction Type Filter Buttons --}}
            <div class="card-body border-bottom py-3">
                <div class="d-flex flex-wrap gap-2" id="transactionTypeButtons">
                    <button type="button" class="btn btn-sm btn-secondary transaction-type-btn active" data-type="" onclick="filterTransactionType('')">
                        <iconify-icon icon="iconamoon:category-duotone" class="me-1"></iconify-icon>
                        <span>All Types</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success transaction-type-btn" data-type="deposit" onclick="filterTransactionType('deposit')">
                        <iconify-icon icon="iconamoon:arrow-down-2-duotone" class="me-1"></iconify-icon>
                        <span>Deposits</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning transaction-type-btn" data-type="withdrawal" onclick="filterTransactionType('withdrawal')">
                        <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-1"></iconify-icon>
                        <span>Withdrawals</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary transaction-type-btn" data-type="commission" onclick="filterTransactionType('commission')">
                        <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                        <span>Commissions</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info transaction-type-btn" data-type="roi" onclick="filterTransactionType('roi')">
                        <iconify-icon icon="solar:dollar-bold" class="me-1"></iconify-icon>
                        <span>ROI</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary transaction-type-btn" data-type="bonus" onclick="filterTransactionType('bonus')">
                        <iconify-icon icon="iconamoon:gift-duotone" class="me-1"></iconify-icon>
                        <span>Bonus</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger transaction-type-btn" data-type="investment" onclick="filterTransactionType('investment')">
                        <iconify-icon icon="material-symbols:account-balance-wallet" class="me-1"></iconify-icon>
                        <span>Investments</span>
                    </button>
                </div>
            </div>

            {{-- Transactions Table --}}
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">User & Transaction ID</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsTableBody">
                                @if($transactions->count() > 0)
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                                    <span class="avatar-title text-white">{{ $transaction->user ? $transaction->user->initials : 'U' }}</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $transaction->user ? $transaction->user->full_name : 'Unknown User' }}</h6>
                                                    <code class="small">{{ Str::limit($transaction->transaction_id, 15) }}...</code>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : ($transaction->type === 'commission' ? 'primary' : ($transaction->type === 'roi' ? 'info' : ($transaction->type === 'bonus' ? 'secondary' : 'dark')))) }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : ($transaction->type === 'commission' ? 'primary' : ($transaction->type === 'roi' ? 'info' : ($transaction->type === 'bonus' ? 'secondary' : 'dark')))) }} p-1">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <strong class="{{ in_array($transaction->type, ['withdrawal']) ? 'text-danger' : 'text-success' }}">
                                                {{ in_array($transaction->type, ['withdrawal']) ? '-' : '+' }}{{ $transaction->formatted_amount }}
                                            </strong>
                                        </td>
                                        <td>
                                            {{ $transaction->created_at->format('d M, y') }}
                                            <small class="text-muted d-block">{{ $transaction->created_at->format('h:i:s A') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }} p-1">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="showDetails('{{ $transaction->id }}')">
                                                        <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>View Details
                                                    </a></li>
                                                    @if($transaction->status !== 'completed')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-success" href="javascript:void(0)" onclick="updateTransactionStatus('{{ $transaction->id }}', 'completed')">
                                                        <iconify-icon icon="iconamoon:check-circle-duotone" class="me-2"></iconify-icon>Mark Completed
                                                    </a></li>
                                                    @endif
                                                    @if($transaction->status === 'pending')
                                                    <li><a class="dropdown-item text-info" href="javascript:void(0)" onclick="updateTransactionStatus('{{ $transaction->id }}', 'processing')">
                                                        <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>Mark Processing
                                                    </a></li>
                                                    @endif
                                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="showStatusModal('{{ $transaction->id }}', '{{ $transaction->status }}')">
                                                        <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>Change Status
                                                    </a></li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                                            <h6 class="text-muted">No Transactions Found</h6>
                                            <p class="text-muted mb-0">No transactions match your current filter criteria.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none p-3">
                    <div class="row g-3" id="transactionsMobileView">
                        @foreach($transactions as $transaction)
                        <div class="col-12">
                            <div class="card transaction-card border">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : ($transaction->type === 'commission' ? 'primary' : ($transaction->type === 'roi' ? 'info' : ($transaction->type === 'bonus' ? 'secondary' : 'dark')))) }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : ($transaction->type === 'commission' ? 'primary' : ($transaction->type === 'roi' ? 'info' : ($transaction->type === 'bonus' ? 'secondary' : 'dark')))) }}">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                            <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </div>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails('{{ $transaction->id }}')">
                                            <iconify-icon icon="iconamoon:eye-duotone" id="chevron-{{ $transaction->id }}"></iconify-icon>
                                        </button>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <h6 class="mb-0 {{ in_array($transaction->type, ['withdrawal']) ? 'text-danger' : 'text-success' }}">
                                                {{ in_array($transaction->type, ['withdrawal']) ? '-' : '+' }}{{ $transaction->formatted_amount }}
                                            </h6>
                                            <small class="text-muted">{{ $transaction->created_at->format('M d, Y • H:i') }}</small>
                                        </div>
                                        @if($transaction->type === 'deposit')
                                            <iconify-icon icon="iconamoon:arrow-down-duotone" class="text-success fs-20"></iconify-icon>
                                        @elseif($transaction->type === 'withdrawal')
                                            <iconify-icon icon="iconamoon:arrow-up-duotone" class="text-warning fs-20"></iconify-icon>
                                        @else
                                            <iconify-icon icon="material-symbols:account-balance-wallet" class="text-info fs-20"></iconify-icon>
                                        @endif
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary me-2">
                                                <span class="avatar-title text-white">{{ $transaction->user ? $transaction->user->initials : 'U' }}</span>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $transaction->user ? $transaction->user->full_name : 'Unknown User' }}</div>
                                                <small class="text-muted">{{ $transaction->user ? Str::limit($transaction->user->email, 25) : 'Unknown' }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <code class="small flex-grow-1">{{ Str::limit($transaction->transaction_id, 20) }}...</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('{{ $transaction->transaction_id }}')">
                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                        </button>
                                    </div>

                                    <div class="collapse mt-3" id="details-{{ $transaction->id }}">
                                        <div class="border-top pt-3">
                                            <div class="row g-2 small">
                                                <div class="col-12">
                                                    <div class="text-muted">Full ID</div>
                                                    <code class="small">{{ $transaction->transaction_id }}</code>
                                                </div>
                                                @if($transaction->crypto_address)
                                                <div class="col-12">
                                                    <div class="text-muted">Address</div>
                                                    <div class="d-flex align-items-center">
                                                        <code class="small flex-grow-1">{{ Str::limit($transaction->crypto_address, 30) }}...</code>
                                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('{{ $transaction->crypto_address }}')">
                                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                                        </button>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($transaction->crypto_txid)
                                                <div class="col-12">
                                                    <div class="text-muted">TxID</div>
                                                    <div class="d-flex align-items-center">
                                                        <code class="small flex-grow-1">{{ Str::limit($transaction->crypto_txid, 30) }}...</code>
                                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('{{ $transaction->crypto_txid }}')">
                                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                                        </button>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($transaction->processed_at)
                                                <div class="col-6">
                                                    <div class="text-muted">Processed</div>
                                                    <div>{{ $transaction->processed_at->format('M d, Y H:i') }}</div>
                                                </div>
                                                @endif
                                            </div>
                                            <div class="mt-3 pt-2 border-top">
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="showDetails('{{ $transaction->id }}')">
                                                        <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                                        Details
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" onclick="showStatusModal('{{ $transaction->id }}', '{{ $transaction->status }}')">
                                                        <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                                                        Status
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Pagination Container --}}
            <div id="transactionsPaginationContainer">
                @if($transactions->hasPages())
                <div class="card-footer border-top border-light">
                    <div class="align-items-center justify-content-between row text-center text-sm-start">
                        <div class="col-sm">
                            <div class="text-muted">
                                Showing
                                <span class="fw-semibold text-body">{{ $transactions->firstItem() }}</span>
                                to
                                <span class="fw-semibold text-body">{{ $transactions->lastItem() }}</span>
                                of
                                <span class="fw-semibold">{{ $transactions->total() }}</span>
                                Transactions
                            </div>
                        </div>
                        <div class="col-sm-auto mt-3 mt-sm-0">
                            <ul class="pagination pagination-boxed pagination-sm mb-0 justify-content-center">
                                @if ($transactions->onFirstPage())
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $transactions->previousPageUrl() }}"><i class="bx bxs-chevron-left"></i></a>
                                    </li>
                                @endif

                                @php
                                    $currentPage = $transactions->currentPage();
                                    $lastPage = $transactions->lastPage();
                                    $pagesToShow = [];
                                    
                                    if ($lastPage <= 7) {
                                        $pagesToShow = range(1, $lastPage);
                                    } else {
                                        $pagesToShow[] = 1;
                                        if ($currentPage > 4) $pagesToShow[] = '...';
                                        
                                        $start = max(2, $currentPage - 1);
                                        $end = min($lastPage - 1, $currentPage + 1);
                                        
                                        if ($currentPage <= 4) {
                                            $start = 2;
                                            $end = min(6, $lastPage - 1);
                                        }
                                        
                                        if ($currentPage >= $lastPage - 3) {
                                            $start = max(2, $lastPage - 5);
                                            $end = $lastPage - 1;
                                        }
                                        
                                        for ($i = $start; $i <= $end; $i++) {
                                            $pagesToShow[] = $i;
                                        }
                                        
                                        if ($currentPage < $lastPage - 3) $pagesToShow[] = '...';
                                        $pagesToShow[] = $lastPage;
                                    }
                                @endphp

                                @foreach($pagesToShow as $page)
                                    @if($page === '...')
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    @elseif($page == $currentPage)
                                        <li class="page-item active">
                                            <span class="page-link">{{ $page }}</span>
                                        </li>
                                    @else
                                        <li class="page-item">
                                            <a class="page-link" href="{{ $transactions->url($page) }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach

                                @if ($transactions->hasMorePages())
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $transactions->nextPageUrl() }}"><i class="bx bxs-chevron-right"></i></a>
                                    </li>
                                @else
                                    <li class="page-item disabled">
                                        <span class="page-link"><i class="bx bxs-chevron-right"></i></span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Transaction Status Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Transaction Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="statusForm">
                <div class="modal-body">
                    <input type="hidden" id="transactionId">
                    <div class="mb-3">
                        <label class="form-label">Transaction ID</label>
                        <div id="transactionIdDisplay" class="form-control-plaintext fw-semibold"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Status</label>
                        <div id="currentStatus" class="form-control-plaintext"></div>
                    </div>
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">New Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="newStatus" required>
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusNotes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="statusNotes" rows="3" placeholder="Additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
{{-- Flatpickr CSS and JS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
// Store current filters
let currentFilters = {
    type: '',
    status: '',
    date_range: '30',
    start_date: '',
    end_date: '',
    per_page: 25,
    page: 1
};

let customDatePicker;

document.addEventListener('DOMContentLoaded', function() {
    initializeDatePicker();
});

// Initialize custom date range picker
function initializeDatePicker() {
    const predefinedSelect = document.getElementById('predefinedDateRange');
    const customInput = document.getElementById('customDateRange');
    const clearBtn = document.getElementById('clearCustomDateFilter');
    
    customDatePicker = flatpickr("#customDateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        maxDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                currentFilters.start_date = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                currentFilters.end_date = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                currentFilters.date_range = 'custom';
                currentFilters.page = 1;
                clearBtn.style.display = 'inline-block';
                loadTransactions();
            }
        }
    });
    
    clearBtn.addEventListener('click', function() {
        customDatePicker.clear();
        currentFilters.start_date = '';
        currentFilters.end_date = '';
        currentFilters.date_range = '30';
        currentFilters.page = 1;
        this.style.display = 'none';
        customInput.style.display = 'none';
        predefinedSelect.value = '30';
        loadTransactions();
    });
}

function handleDateRangeChange(value) {
    const customInput = document.getElementById('customDateRange');
    const clearBtn = document.getElementById('clearCustomDateFilter');
    
    if (value === 'custom') {
        customInput.style.display = 'block';
        customDatePicker.open();
    } else {
        customInput.style.display = 'none';
        clearBtn.style.display = 'none';
        currentFilters.start_date = '';
        currentFilters.end_date = '';
        filterTransactions('date_range', value);
    }
}

function filterTransactionType(type) {
    currentFilters.type = type;
    currentFilters.page = 1;
    updateTypeButtonStates(type);
    loadTransactions();
}

function updateTypeButtonStates(activeType) {
    const buttons = document.querySelectorAll('.transaction-type-btn');
    buttons.forEach(btn => {
        const btnType = btn.getAttribute('data-type');
        btn.classList.remove('active', 'btn-success', 'btn-warning', 'btn-primary', 'btn-info', 'btn-secondary', 'btn-danger');
        
        if (btnType === activeType) {
            btn.classList.add('active');
            if (btnType === 'deposit') btn.classList.add('btn-success');
            else if (btnType === 'withdrawal') btn.classList.add('btn-warning');
            else if (btnType === 'commission') btn.classList.add('btn-primary');
            else if (btnType === 'roi') btn.classList.add('btn-info');
            else if (btnType === 'bonus') btn.classList.add('btn-secondary');
            else if (btnType === 'investment') btn.classList.add('btn-danger');
            else btn.classList.add('btn-secondary');
        } else {
            if (btnType === 'deposit') btn.classList.add('btn-outline-success');
            else if (btnType === 'withdrawal') btn.classList.add('btn-outline-warning');
            else if (btnType === 'commission') btn.classList.add('btn-outline-primary');
            else if (btnType === 'roi') btn.classList.add('btn-outline-info');
            else if (btnType === 'bonus') btn.classList.add('btn-outline-secondary');
            else if (btnType === 'investment') btn.classList.add('btn-outline-danger');
            else btn.classList.add('btn-outline-secondary');
        }
    });
}

function filterTransactions(filterType, value) {
    currentFilters[filterType] = value;
    currentFilters.page = 1;
    loadTransactions();
}

function loadTransactionsPage(page) {
    currentFilters.page = page;
    loadTransactions();
}

function loadTransactions() {
    const tableBody = document.getElementById('transactionsTableBody');
    const paginationContainer = document.getElementById('transactionsPaginationContainer');
    const tableWrapper = tableBody?.closest('.table-responsive');
    
    if (!tableBody) return;
    
    // Show loading overlay
    let loadingOverlay = document.getElementById('transactionsLoadingOverlay');
    
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'transactionsLoadingOverlay';
        loadingOverlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
        loadingOverlay.style.cssText = 'background: rgba(255, 255, 255, 0.9); z-index: 10; min-height: 400px; border-radius: 8px;';
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-2 mb-0">Loading transactions...</p>
            </div>
        `;
        
        if (tableWrapper) {
            tableWrapper.style.position = 'relative';
            tableWrapper.appendChild(loadingOverlay);
        }
    } else {
        loadingOverlay.classList.remove('d-none');
    }
    
    if (paginationContainer) {
        paginationContainer.style.opacity = '0.5';
    }
    
    // Build query params
    const params = new URLSearchParams();
    if (currentFilters.type) params.append('type', currentFilters.type);
    if (currentFilters.status) params.append('status', currentFilters.status);
    if (currentFilters.date_range && currentFilters.date_range !== 'custom') params.append('date_range', currentFilters.date_range);
    if (currentFilters.start_date) params.append('start_date', currentFilters.start_date);
    if (currentFilters.end_date) params.append('end_date', currentFilters.end_date);
    if (currentFilters.per_page) params.append('per_page', currentFilters.per_page);
    if (currentFilters.page) params.append('page', currentFilters.page);
    
    // Make AJAX request
    fetch(`{{ route('admin.finance.transactions.filter-ajax') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tableBody.innerHTML = data.html;
                
                if (paginationContainer) {
                    paginationContainer.innerHTML = data.pagination;
                    paginationContainer.style.opacity = '1';
                }
                
                if (loadingOverlay) {
                    loadingOverlay.classList.add('d-none');
                }
                
                if (currentFilters.page > 1) {
                    tableWrapper?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                if (currentFilters.page === 1) {
                    showAlert(`Showing ${data.count} of ${data.total} transaction(s)`, 'success');
                }
            } else {
                if (loadingOverlay) {
                    loadingOverlay.classList.add('d-none');
                }
                showAlert('Failed to load transactions', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            if (loadingOverlay) {
                loadingOverlay.classList.add('d-none');
            }
            
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <iconify-icon icon="iconamoon:alert-circle-duotone" class="fs-1 text-danger mb-3"></iconify-icon>
                        <h6 class="text-danger">Error Loading Transactions</h6>
                        <p class="text-muted mb-0">Please try again.</p>
                    </td>
                </tr>
            `;
            showAlert('Failed to load transactions', 'danger');
        });
}

function toggleDetails(transactionId) {
    const detailsElement = document.getElementById(`details-${transactionId}`);
    const chevronElement = document.getElementById(`chevron-${transactionId}`);
    
    if (detailsElement.classList.contains('show')) {
        detailsElement.classList.remove('show');
        chevronElement.style.transform = 'rotate(0deg)';
    } else {
        detailsElement.classList.add('show');
        chevronElement.style.transform = 'rotate(180deg)';
    }
}

function showDetails(transactionId) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const content = document.getElementById('modalContent');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`{{ url('admin/finance/transactions') }}/${transactionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
            } else {
                content.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Failed to load transaction details</div>';
        });
}

function updateTransactionStatus(transactionId, status) {
    if (confirm(`Are you sure you want to mark this transaction as ${status}?`)) {
        fetch(`{{ url('admin/finance/transactions') }}/${transactionId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                loadTransactions();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to update transaction status', 'danger');
        });
    }
}

function showStatusModal(transactionId, currentStatus) {
    document.getElementById('transactionId').value = transactionId;
    document.getElementById('transactionIdDisplay').textContent = transactionId;
    document.getElementById('currentStatus').innerHTML = `<span class="badge bg-secondary">${currentStatus.toUpperCase()}</span>`;
    
    const select = document.getElementById('newStatus');
    Array.from(select.options).forEach(option => {
        option.style.display = option.value === currentStatus ? 'none' : 'block';
    });
    
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

document.getElementById('statusForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const transactionId = document.getElementById('transactionId').value;
    const newStatus = document.getElementById('newStatus').value;
    const notes = document.getElementById('statusNotes').value;
    
    fetch(`{{ url('admin/finance/transactions') }}/${transactionId}/update-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            status: newStatus,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            loadTransactions();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update transaction status', 'danger');
    });
});

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

document.addEventListener('click', function(e) {
    if (!e.target.closest('.transaction-card')) {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }
});

document.getElementById('statusModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('statusForm').reset();
});

window.copyText = copyText;
</script>

<style>
/* Transaction card styling */
.transaction-card {
    transition: all 0.2s ease;
    border-radius: 8px;
}

.transaction-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.fs-20 {
    font-size: 1.25rem;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

code {
    background-color: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    word-break: break-all;
}

.collapse {
    transition: height 0.3s ease;
}

/* Transaction type filter buttons */
.transaction-type-btn {
    font-weight: 500;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.transaction-type-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.transaction-type-btn.active {
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.transaction-type-btn iconify-icon {
    font-size: 1rem;
    vertical-align: middle;
}

/* Date range picker styling */
#customDateRange {
    cursor: pointer;
}

#customDateRange:hover {
    border-color: #007bff;
}

.flatpickr-calendar {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Table improvements */
.table-card .table thead th {
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
    font-size: 0.875rem;
    padding: 0.75rem;
}

.table-card .table tbody td {
    padding: 0.75rem;
    vertical-align: middle;
}

/* Loading overlay */
.table-responsive {
    min-height: 400px;
    position: relative;
}

#transactionsLoadingOverlay.d-none {
    display: none !important;
}

#transactionsPaginationContainer {
    transition: opacity 0.2s ease;
}

/* Pagination styling */
.pagination-boxed .page-link {
    border: 1px solid #dee2e6;
    border-radius: 6px;
    margin: 0 2px;
    padding: 0.375rem 0.75rem;
}

.pagination-boxed .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

/* Badge subtle styling */
.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Smooth transitions */
.btn, .badge, .card {
    transition: all 0.2s ease;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
    
    .card-header .flex-shrink-0 {
        flex-shrink: 1;
    }
    
    .card-header .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    
    .card-header .form-select,
    .card-header .form-control {
        flex: 1;
        min-width: 120px;
    }
    
    #transactionTypeButtons {
        justify-content: center;
    }
    
    .transaction-type-btn {
        flex: 0 0 auto;
        font-size: 0.8rem;
        padding: 0.375rem 0.75rem;
    }
    
    .transaction-type-btn iconify-icon {
        font-size: 0.9rem;
    }
    
    #customDateRange {
        min-width: 160px !important;
        font-size: 0.875rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    code {
        font-size: 0.75rem;
    }
    
    .card-footer .row {
        flex-direction: column;
        gap: 1rem;
        text-align: center !important;
    }
    
    .pagination-boxed .page-link {
        padding: 0.25rem 0.5rem;
        margin: 0 1px;
    }
}

@media (max-width: 576px) {
    .card-header .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .transaction-card .card-body {
        padding: 0.75rem;
    }
    
    .transaction-type-btn span {
        display: none;
    }
    
    .transaction-type-btn {
        padding: 0.5rem;
        min-width: 40px;
    }
    
    .transaction-type-btn iconify-icon {
        margin: 0 !important;
    }
    
    .pagination .page-item:not(.active):not(.disabled):nth-child(n+5):nth-last-child(n+5) {
        display: none;
    }
}

/* Modal improvements */
@media (max-width: 768px) {
    .modal-lg {
        max-width: 95%;
    }
    
    .modal-body,
    .modal-header,
    .modal-footer {
        padding: 1rem;
    }
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.min-w-0 {
    min-width: 0;
}

.flex-grow-1 {
    flex-grow: 1;
}

.flex-shrink-0 {
    flex-shrink: 0;
}
</style>
@endsection