{{-- admin/finance/transactions/details.blade.php --}}
<div class="container-fluid">
    {{-- Transaction Overview --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    {{-- Mobile-first responsive layout --}}
                    <div
                        class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center w-100 w-md-auto">
                            @if($transaction->type === 'deposit')
                                <div class="avatar avatar-lg rounded-circle bg-success-subtle me-3 flex-shrink-0">
                                    <iconify-icon icon="iconamoon:arrow-down-duotone" class="text-success"
                                        style="font-size: 2rem;"></iconify-icon>
                                </div>
                            @elseif($transaction->type === 'withdrawal')
                                <div class="avatar avatar-lg rounded-circle bg-warning-subtle me-3 flex-shrink-0">
                                    <iconify-icon icon="iconamoon:arrow-up-duotone" class="text-warning"
                                        style="font-size: 2rem;"></iconify-icon>
                                </div>
                            @else
                                <div class="avatar avatar-lg rounded-circle bg-info-subtle me-3 flex-shrink-0">
                                    <iconify-icon icon="material-symbols:account-balance-wallet" class="text-info"
                                        style="font-size: 2rem;"></iconify-icon>
                                </div>
                            @endif
                            <div class="min-w-0 flex-grow-1">
                                <h5 class="mb-1 fs-6 fs-md-5">{{ $transactionDetails['basic_info']['type'] }}
                                    Transaction</h5>
                                <p class="text-muted mb-1 small text-truncate">
                                    {{ $transactionDetails['basic_info']['transaction_id'] }}</p>
                                <small class="text-muted">{{ $transactionDetails['basic_info']['created_at'] }}</small>
                            </div>
                        </div>
                        <div class="text-start text-md-end w-100 w-md-auto">
                            <div
                                class="h4 h3-md mb-1 {{ in_array($transaction->type, ['withdrawal', 'debit_adjustment']) ? 'text-danger' : 'text-success' }}">
                                {{ in_array($transaction->type, ['withdrawal', 'debit_adjustment']) ? '-' : '+' }}{{ $transactionDetails['basic_info']['amount'] }}
                            </div>
                            <span
                                class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }} px-2 px-md-3 py-1 py-md-2">
                                <iconify-icon
                                    icon="iconamoon:{{ $transaction->status === 'completed' ? 'check-circle' : ($transaction->status === 'pending' ? 'clock' : ($transaction->status === 'failed' ? 'close-circle' : 'file')) }}-duotone"
                                    class="me-1"></iconify-icon>
                                {{ $transactionDetails['basic_info']['status'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Transaction Information --}}
        <div class="col-12 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0 fs-6">
                        <iconify-icon icon="iconamoon:file-duotone" class="me-2"></iconify-icon>
                        Transaction Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-muted small mb-1">Transaction ID</div>
                            <div class="d-flex align-items-center gap-2">
                                <code
                                    class="small flex-grow-1 text-break bg-light p-2 rounded border">{{ $transactionDetails['basic_info']['transaction_id'] }}</code>
                                <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                    onclick="copyText('{{ $transactionDetails['basic_info']['transaction_id'] }}')"
                                    title="Copy">
                                    <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                </button>
                            </div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="text-muted small mb-1">Type</div>
                            <div class="fw-medium small">{{ $transactionDetails['basic_info']['type'] }}</div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="text-muted small mb-1">Amount</div>
                            <div class="fw-medium small">{{ $transactionDetails['basic_info']['amount'] }}</div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="text-muted small mb-1">Currency</div>
                            <div class="fw-medium small">{{ $transactionDetails['basic_info']['currency'] }}</div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="text-muted small mb-1">Status</div>
                            <span
                                class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : ($transaction->status === 'failed' ? 'danger' : 'secondary')) }} small">
                                {{ $transactionDetails['basic_info']['status'] }}
                            </span>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="text-muted small mb-1">Created At</div>
                            <div class="fw-medium small">{{ $transactionDetails['basic_info']['created_at'] }}</div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="text-muted small mb-1">Processed At</div>
                            <div class="fw-medium small">{{ $transactionDetails['basic_info']['processed_at'] }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small mb-1">Description</div>
                            <div class="fw-medium small">{{ $transactionDetails['payment_info']['description'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- User Information --}}
        <div class="col-12 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0 fs-6">
                        <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                        User Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-md avatar-lg-lg rounded-circle bg-primary me-3 flex-shrink-0">
                            <span
                                class="avatar-title text-white fs-6 fs-md-5">{{ $transaction->user ? $transaction->user->initials : 'U' }}</span>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <h6 class="mb-0 fs-6 text-truncate">{{ $transactionDetails['user_info']['name'] }}</h6>
                            <p class="text-muted mb-0 small text-truncate">
                                {{ $transactionDetails['user_info']['email'] }}</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <div class="text-muted small mb-1">User ID</div>
                            <div class="fw-medium small">#{{ $transactionDetails['user_info']['user_id'] }}</div>
                        </div>
                        @if($transaction->user)
                            <div class="col-6 col-sm-6">
                                <div class="text-muted small mb-1">User Status</div>
                                <span
                                    class="badge bg-{{ $transaction->user->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $transaction->user->status === 'active' ? 'success' : 'secondary' }} small">
                                    {{ ucfirst($transaction->user->status) }}
                                </span>
                            </div>
                            <div class="col-6 col-sm-6">
                                <div class="text-muted small mb-1">User Role</div>
                                <span class="badge bg-info-subtle text-info small">
                                    {{ ucfirst($transaction->user->role) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Information --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0 fs-6">
                        <iconify-icon icon="iconamoon:credit-card-duotone" class="me-2"></iconify-icon>
                        Payment Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <div class="text-muted small mb-1">Payment Method</div>
                            <div class="fw-medium small">{{ $transactionDetails['payment_info']['payment_method'] }}
                            </div>
                        </div>
                        @if($transactionDetails['payment_info']['crypto_address'] !== 'N/A')
                            <div class="col-12 col-md-8">
                                <div class="text-muted small mb-1">Crypto Address</div>
                                <div class="d-flex align-items-center gap-2">
                                    <code
                                        class="small flex-grow-1 text-break bg-light p-2 rounded border">{{ $transactionDetails['payment_info']['crypto_address'] }}</code>
                                    <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        onclick="copyText('{{ $transactionDetails['payment_info']['crypto_address'] }}')"
                                        title="Copy">
                                        <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        @endif
                        @if($transactionDetails['payment_info']['crypto_txid'] !== 'N/A')
                            <div class="col-12">
                                <div class="text-muted small mb-1">Transaction Hash</div>
                                <div class="d-flex align-items-center gap-2">
                                    <code
                                        class="small flex-grow-1 text-break bg-light p-2 rounded border">{{ $transactionDetails['payment_info']['crypto_txid'] }}</code>
                                    <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        onclick="copyText('{{ $transactionDetails['payment_info']['crypto_txid'] }}')"
                                        title="Copy">
                                        <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    {{-- Mobile-first button layout --}}
                    <div class="d-grid gap-2 d-md-flex flex-md-wrap justify-content-md-center">
                        @if($transaction->status !== 'completed')
                            <button type="button" class="btn btn-success btn-sm"
                                onclick="updateTransactionStatusFromModal('{{ $transaction->id }}', 'completed')">
                                <iconify-icon icon="iconamoon:check-circle-duotone" class="me-1"></iconify-icon>
                                <span class="d-none d-sm-inline">Mark as </span>Completed
                            </button>
                        @endif
                        @if($transaction->status === 'pending')
                            <button type="button" class="btn btn-info btn-sm"
                                onclick="updateTransactionStatusFromModal('{{ $transaction->id }}', 'processing')">
                                <iconify-icon icon="iconamoon:clock-duotone" class="me-1"></iconify-icon>
                                <span class="d-none d-sm-inline">Mark as Processing</span>
                            </button>
                        @endif
                        @if(!in_array($transaction->status, ['failed', 'cancelled']))
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="updateTransactionStatusFromModal('{{ $transaction->id }}', 'failed')">
                                <iconify-icon icon="iconamoon:close-circle-duotone" class="me-1"></iconify-icon>
                                <span class="d-none d-sm-inline">Mark as Failed</span>
                            </button>
                        @endif
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            onclick="showStatusModalFromDetails('{{ $transaction->id }}', '{{ $transaction->status }}')">
                            <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                            Change Status
                        </button>
                        @if($transaction->crypto_txid && $transactionDetails['payment_info']['crypto_txid'] !== 'N/A')
                            <a href="https://bscscan.com/tx/{{ $transaction->crypto_txid }}"
                                class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener noreferrer">
                                <iconify-icon icon="iconamoon:link-external-duotone" class="me-1"></iconify-icon>
                                View Blockchain
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>