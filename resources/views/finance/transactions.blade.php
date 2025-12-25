@extends('layouts.vertical', ['title' => 'Transactions', 'subTitle' => 'Finance'])

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="d-flex card-header justify-content-between align-items-center">
                <h4 class="card-title">Transactions</h4>
                <div class="flex-shrink-0">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" onchange="filterTransactions(this.value)">
                            <option value="" {{ !request('type') ? 'selected' : '' }}>All Types</option>
                            <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>Deposits</option>
                            <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>Withdrawals</option>
                            <option value="commission" {{ request('type') === 'commission' ? 'selected' : '' }}>Commissions</option>
                            <option value="roi" {{ request('type') === 'roi' ? 'selected' : '' }}>ROI</option>
                        </select>
                        <select class="form-select form-select-sm" onchange="filterStatus(this.value)">
                            <option value="" {{ !request('status') ? 'selected' : '' }}>All Status</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                </div>
            </div>

            @if($transactions->count() > 0)
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">Transaction ID</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Timestamp</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td>
                                        <code class="small">{{ Str::limit($transaction->transaction_id, 15) }}...</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }} p-1">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="{{ $transaction->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                            {{ $transaction->type === 'withdrawal' ? '-' : '+' }}{{ $transaction->formatted_amount }}
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
                                        <button class="btn btn-sm btn-outline-primary" onclick="showDetails('{{ $transaction->id }}')">
                                            <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none p-3">
                    <div class="row g-3">
                        @foreach($transactions as $transaction)
                        <div class="col-12">
                            <div class="card transaction-card border">
                                <div class="card-body p-3">
                                    {{-- Header Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}">
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

                                    {{-- Amount Row --}}
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div>
                                            <h6 class="mb-0 {{ $transaction->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                                                {{ $transaction->type === 'withdrawal' ? '-' : '+' }}{{ $transaction->formatted_amount }}
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

                                    {{-- Transaction ID --}}
                                    <div class="d-flex align-items-center">
                                        <code class="small flex-grow-1">{{ Str::limit($transaction->transaction_id, 20) }}...</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('{{ $transaction->transaction_id }}')">
                                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                        </button>
                                    </div>

                                    {{-- Expandable Details --}}
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Pagination Footer --}}
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
                            {{-- Previous Page Link --}}
                            @if ($transactions->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="bx bxs-chevron-left"></i></span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $transactions->previousPageUrl() }}"><i class="bx bxs-chevron-left"></i></a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                                @if ($page == $transactions->currentPage())
                                    <li class="page-item active">
                                        <span class="page-link">{{ $page }}</span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
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

            @else
            {{-- Empty State --}}
            <div class="card-body">
                <div class="text-center py-5">
                    <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Transactions Found</h6>
                    <p class="text-muted">You haven't made any transactions yet.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-success">
                            Deposit
                        </a>
                        <a href="{{ route('wallets.withdraw.wallet') }}" class="btn btn-warning">
                            Withdraw
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Desktop Details Modal --}}
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
<script>
function filterTransactions(type) {
    const url = new URL(window.location.href);
    if (type) {
        url.searchParams.set('type', type);
    } else {
        url.searchParams.delete('type');
    }
    window.location.href = url.toString();
}

function filterStatus(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
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
    // For desktop modal view
    fetch(`{{ url('transactions') }}/${transactionId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('detailsModal')).show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load transaction details', 'danger');
        });
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

// Close all open details when clicking outside
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

/* Mobile responsive fixes */
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
    
    .card-header .form-select {
        flex: 1;
        min-width: 120px;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    code {
        font-size: 0.75rem;
    }
    
    /* Pagination mobile */
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
    
    /* Hide some pagination numbers on very small screens */
    .pagination .page-item:not(.active):not(.disabled):nth-child(n+5):nth-last-child(n+5) {
        display: none;
    }
}

/* Badge subtle styling */
.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Smooth transitions */
.btn, .badge, .card {
    transition: all 0.2s ease;
}

.transaction-details code {
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
    display: block;
    word-break: break-all;
}

.transaction-details pre {
    background-color: transparent;
    color: inherit;
    max-height: 200px;
    overflow-y: auto;
}
</style>
@endsection