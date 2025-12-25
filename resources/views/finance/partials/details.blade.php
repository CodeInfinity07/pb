<div class="transaction-details">
    <div class="row g-3">
        {{-- Transaction ID --}}
        <div class="col-12">
            <label class="form-label fw-semibold text-muted small">Transaction ID</label>
            <div class="d-flex align-items-center gap-2">
                <code class="flex-grow-1">{{ $transaction->transaction_id }}</code>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $transaction->transaction_id }}')">
                    <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                </button>
            </div>
        </div>

        {{-- Type and Status --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Type</label>
            <div>
                <span class="badge bg-{{ $transaction->type_color }}-subtle text-{{ $transaction->type_color }} p-2">
                    {{ ucfirst($transaction->type) }}
                </span>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Status</label>
            <div>
                <span class="badge bg-{{ $transaction->status_color }}-subtle text-{{ $transaction->status_color }} p-2">
                    {{ ucfirst($transaction->status) }}
                </span>
            </div>
        </div>

        {{-- Amount and Currency --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Amount</label>
            <div class="fs-5 fw-bold {{ $transaction->type === 'withdrawal' ? 'text-danger' : 'text-success' }}">
                {{ $transaction->type === 'withdrawal' ? '-' : '+' }}{{ $transaction->formatted_amount }}
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Currency</label>
            <div class="fs-6 fw-semibold">{{ $transaction->currency }}</div>
        </div>

        {{-- Description --}}
        <div class="col-12">
            <label class="form-label fw-semibold text-muted small">Description</label>
            <p class="mb-0">{{ $transaction->display_description }}</p>
        </div>

        {{-- Payment Method --}}
        @if($transaction->payment_method)
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Payment Method</label>
            <div>{{ ucfirst($transaction->payment_method) }}</div>
        </div>
        @endif

        {{-- Crypto Address --}}
        @if($transaction->crypto_address)
        <div class="col-12">
            <label class="form-label fw-semibold text-muted small">Crypto Address</label>
            <div class="d-flex align-items-center gap-2">
                <code class="flex-grow-1 small">{{ $transaction->crypto_address }}</code>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $transaction->crypto_address }}')">
                    <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                </button>
            </div>
        </div>
        @endif

        {{-- Crypto Transaction ID --}}
        @if($transaction->crypto_txid)
        <div class="col-12">
            <label class="form-label fw-semibold text-muted small">Blockchain Transaction ID</label>
            <div class="d-flex align-items-center gap-2">
                <code class="flex-grow-1 small">{{ $transaction->crypto_txid }}</code>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyText('{{ $transaction->crypto_txid }}')">
                    <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                </button>
            </div>
        </div>
        @endif

        {{-- Timestamps --}}
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Created At</label>
            <div>{{ $transaction->created_at->format('M d, Y H:i:s') }}</div>
            <small class="text-muted">{{ $transaction->created_at->diffForHumans() }}</small>
        </div>

        @if($transaction->processed_at)
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Processed At</label>
            <div>{{ $transaction->processed_at->format('M d, Y H:i:s') }}</div>
            <small class="text-muted">{{ $transaction->processed_at->diffForHumans() }}</small>
        </div>
        @endif

        {{-- Processed By --}}
        @if($transaction->processed_by)
        <div class="col-12">
            <label class="form-label fw-semibold text-muted small">Processed By</label>
            <div>{{ $transaction->processedBy->name ?? 'System' }}</div>
        </div>
        @endif

        {{-- Metadata --}}
        {{-- Metadata - Only visible during impersonation --}}
@if($transaction->metadata && count($transaction->metadata) > 0)
    @php
        $isImpersonating = Session::get('impersonation.original_admin_id') && Session::get('impersonation.target_user_id');
    @endphp
    
    @if($isImpersonating)
    <div class="col-12">
        <label class="form-label fw-semibold text-muted small">
            Additional Information
        </label>
        <div class="card bg-light">
            <div class="card-body p-2">
                <pre class="mb-0 small">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    </div>
    @endif
@endif
    </div>
</div>