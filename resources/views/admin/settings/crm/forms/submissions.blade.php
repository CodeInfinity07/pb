{{-- Form Submissions Modal Content --}}

<div class="row g-4">
    {{-- Form Info Card --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-lg rounded-circle bg-{{ $form->is_active ? 'success' : 'secondary' }} me-3">
                            <iconify-icon icon="material-symbols:forms-add-on" class="text-white fs-3"></iconify-icon>
                        </div>
                        <div>
                            <h5 class="mb-1">{{ $form->title }}</h5>
                            <p class="text-muted mb-0">{{ $form->description }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold fs-4">{{ $submissions->count() }}</div>
                        <small class="text-muted">Total Submissions</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Submissions List --}}
    <div class="col-12">
        @if($submissions->count() > 0)
        <div class="row g-3">
            @foreach($submissions as $submission)
            <div class="col-12">
                <div class="card submission-card">
                    <div class="card-body p-3">
                        {{-- Header Row --}}
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm rounded-circle bg-{{ $submission->status === 'converted' ? 'success' : ($submission->status === 'processed' ? 'info' : 'warning') }}">
                                    <iconify-icon icon="iconamoon:{{ $submission->status === 'converted' ? 'check-circle' : ($submission->status === 'processed' ? 'edit' : 'file') }}-duotone" class="text-white"></iconify-icon>
                                </div>
                                <div>
                                    <div class="fw-semibold">
                                        @if($submission->lead)
                                        {{ $submission->lead->full_name }}
                                        @else
                                        {{ ($submission->form_data['firstName'] ?? $submission->form_data['first_name'] ?? 'Unknown') }} {{ ($submission->form_data['lastName'] ?? $submission->form_data['last_name'] ?? '') }}
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $submission->created_at->format('M d, Y \a\t g:i A') }}</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $submission->status === 'converted' ? 'success' : ($submission->status === 'processed' ? 'info' : 'warning') }}-subtle text-{{ $submission->status === 'converted' ? 'success' : ($submission->status === 'processed' ? 'info' : 'warning') }}">
                                    {{ ucfirst($submission->status) }}
                                </span>
                                @if($submission->status === 'new')
                                <button class="btn btn-sm btn-success" onclick="convertToLead('{{ $submission->id }}')">
                                    <iconify-icon icon="iconamoon:user-add-duotone" class="me-1"></iconify-icon>
                                    Convert to Lead
                                </button>
                                @endif
                            </div>
                        </div>

                        {{-- Submission Data --}}
                        <div class="border rounded p-3 bg-light">
                            <div class="row g-2">
                                @foreach($submission->form_data as $field => $value)
                                @if($value && !in_array($field, ['_token', 'submit']))
                                <div class="col-md-6">
                                    <div class="small">
                                        <span class="text-muted fw-medium">{{ ucwords(str_replace('_', ' ', $field)) }}:</span>
                                        <span class="ms-2">{{ is_array($value) ? implode(', ', $value) : $value }}</span>
                                    </div>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Additional Info --}}
                        <div class="row g-2 mt-2 small text-muted">
                            <div class="col-md-4">
                                <strong>IP Address:</strong> {{ $submission->ip_address }}
                            </div>
                            <div class="col-md-4">
                                <strong>Referrer:</strong> {{ $submission->referrer ? Str::limit($submission->referrer, 30) : 'Direct' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Submitted:</strong> {{ $submission->created_at->diffForHumans() }}
                            </div>
                        </div>

                        @if($submission->lead)
                        {{-- Lead Info if converted --}}
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar avatar-sm rounded-circle bg-{{ $submission->lead->status === 'hot' ? 'danger' : ($submission->lead->status === 'warm' ? 'warning' : 'info') }}">
                                        <span class="avatar-title text-white">{{ strtoupper(substr($submission->lead->first_name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">Converted to Lead</div>
                                        <small class="text-muted">{{ $submission->lead->full_name }} - {{ ucfirst($submission->lead->status) }} lead</small>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewLead('{{ $submission->lead->id }}')">
                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                    View Lead
                                </button>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        {{-- Empty State --}}
        <div class="card">
            <div class="card-body text-center py-5">
                <iconify-icon icon="iconamoon:send-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                <h6 class="text-muted">No Submissions Yet</h6>
                <p class="text-muted mb-3">This form hasn't received any submissions.</p>
                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ $form->public_url }}" target="_blank" class="btn btn-primary btn-sm">
                        <iconify-icon icon="iconamoon:link-external-duotone" class="me-1"></iconify-icon>
                        Preview Form
                    </a>
                    <button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('{{ $form->public_url }}')">
                        <iconify-icon icon="iconamoon:copy-duotone" class="me-1"></iconify-icon>
                        Copy URL
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// Convert submission to lead
function convertToLead(submissionId) {
    if (confirm('Convert this form submission to a lead?')) {
        fetch(`{{ url('admin/crm/forms/submissions') }}/${submissionId}/convert`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to convert submission', 'danger');
        });
    }
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        showAlert('URL copied to clipboard!', 'success');
    }, function() {
        showAlert('Failed to copy URL', 'danger');
    });
}

// View lead (placeholder)
function viewLead(leadId) {
    showAlert('View lead functionality coming soon', 'info');
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 4000);
}
</script>

<style>
.submission-card {
    transition: all 0.2s ease;
    border-radius: 8px;
}

.submission-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.avatar-lg {
    width: 4rem;
    height: 4rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.fs-3 {
    font-size: 1.75rem;
}

.fs-4 {
    font-size: 1.5rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .submission-card .card-body {
        padding: 1rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

.btn, .badge, .card {
    transition: all 0.2s ease;
}
</style>