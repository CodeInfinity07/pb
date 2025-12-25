{{-- resources/views/admin/faq/show.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'FAQ Details', 'subTitle' => 'View FAQ information and statistics'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <h4 class="mb-0 text-dark">FAQ Details</h4>
                                @if($faq->is_featured)
                                    <span class="badge bg-warning">Featured</span>
                                @endif
                                <span class="badge {{ $faq->status_badge }}">{{ $faq->status_text }}</span>
                            </div>
                            <p class="text-muted mb-0">{{ Str::limit($faq->question, 80) }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('admin.faq.edit', $faq) }}" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                                Edit FAQ
                            </a>
                            <a href="{{ route('admin.faq.faqs') }}" class="btn btn-outline-secondary btn-sm">
                                <iconify-icon icon="iconamoon:arrow-left-2-duotone" class="me-1"></iconify-icon>
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- FAQ Content --}}
        <div class="col-lg-8 order-lg-1">
            {{-- Question and Answer --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="me-2"></iconify-icon>
                            Question & Answer
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="toggleStatus()" id="statusToggleBtn">
                                <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                Toggle Status
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="toggleFeatured()" id="featuredToggleBtn">
                                <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>
                                {{ $faq->is_featured ? 'Remove Featured' : 'Mark Featured' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5 class="text-primary mb-3">{{ $faq->question }}</h5>
                        <div class="faq-answer">
                            {!! nl2br(e($faq->answer)) !!}
                        </div>
                    </div>

                    {{-- Tags --}}
                    @if($faq->tags && count($faq->tags) > 0)
                        <div class="mb-3">
                            <h6 class="text-muted mb-2">
                                <iconify-icon icon="iconamoon:hashtag-duotone" class="me-1"></iconify-icon>
                                Tags
                            </h6>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($faq->tags as $tag)
                                    <span class="badge bg-light text-dark">#{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Metadata --}}
                    <div class="mt-4 pt-3 border-top">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <h6 class="text-muted mb-1">Category</h6>
                                <span class="badge {{ $faq->category_badge }}">{{ $faq->category_text }}</span>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted mb-1">Sort Order</h6>
                                <span class="text-dark">{{ $faq->sort_order }}</span>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted mb-1">Total Views</h6>
                                <span class="text-primary fw-bold">{{ number_format($faq->views) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- FAQ Analytics --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:chart-line-duotone" class="me-2"></iconify-icon>
                        FAQ Analytics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-primary">{{ number_format($faq->views) }}</h4>
                                <small class="text-muted">Total Views</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-success">{{ $faq->status === 'active' ? 'Active' : 'Inactive' }}</h4>
                                <small class="text-muted">Status</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-warning">{{ $faq->is_featured ? 'Yes' : 'No' }}</h4>
                                <small class="text-muted">Featured</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center p-3 bg-light rounded">
                                <h4 class="mb-1 text-info">{{ $faq->sort_order }}</h4>
                                <small class="text-muted">Sort Order</small>
                            </div>
                        </div>
                    </div>

                    {{-- Performance metrics --}}
                    <div class="mt-4">
                        <h6 class="mb-3">Performance Metrics</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Average Views per Day</span>
                                    <span class="fw-bold">
                                        {{ $faq->created_at->diffInDays(now()) > 0 ? number_format($faq->views / $faq->created_at->diffInDays(now()), 1) : $faq->views }}
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Days Online</span>
                                    <span class="fw-bold">{{ $faq->created_at->diffInDays(now()) }} days</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Question Length</span>
                                    <span class="fw-bold">{{ strlen($faq->question) }} chars</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted">Answer Length</span>
                                    <span class="fw-bold">{{ strlen($faq->answer) }} chars</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FAQ Information Sidebar --}}
        <div class="col-lg-4 order-lg-2 mb-4">
            {{-- FAQ Information --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                        FAQ Information
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Status</label>
                        <div>
                            <span class="badge {{ $faq->status_badge }} status-badge" 
                                  style="cursor: pointer;" onclick="toggleStatus()" title="Click to toggle">
                                {{ $faq->status_text }}
                            </span>
                        </div>
                    </div>

                    {{-- Category --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Category</label>
                        <div>
                            <span class="badge {{ $faq->category_badge }}">{{ $faq->category_text }}</span>
                        </div>
                    </div>

                    {{-- Featured Status --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Featured</label>
                        <div>
                            <span class="badge {{ $faq->is_featured ? 'bg-warning' : 'bg-light text-dark' }} featured-badge" 
                                  style="cursor: pointer;" onclick="toggleFeatured()" title="Click to toggle">
                                {{ $faq->is_featured ? 'Featured' : 'Not Featured' }}
                            </span>
                        </div>
                    </div>

                    {{-- Sort Order --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Sort Order</label>
                        <div class="text-dark">{{ $faq->sort_order }}</div>
                    </div>

                    {{-- Views --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Total Views</label>
                        <div class="text-primary fw-bold">{{ number_format($faq->views) }}</div>
                    </div>

                    {{-- Created --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Created</label>
                        <div class="small">{{ $faq->created_at->format('M j, Y g:i A') }}</div>
                        <div class="small text-muted">{{ $faq->created_at->diffForHumans() }}</div>
                    </div>

                    {{-- Last Updated --}}
                    <div class="mb-0">
                        <label class="form-label small text-muted">Last Updated</label>
                        <div class="small">{{ $faq->updated_at->format('M j, Y g:i A') }}</div>
                        <div class="small text-muted">{{ $faq->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>

            {{-- Creator Information --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                        Creator Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Created By</label>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-2">
                                <span class="avatar-title rounded-circle bg-primary text-white">
                                    {{ $faq->creator ? substr($faq->creator->name, 0, 1) : 'U' }}
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $faq->creator->name ?? 'Unknown User' }}</h6>
                                <small class="text-muted">{{ $faq->creator->email ?? 'No email' }}</small>
                            </div>
                        </div>
                    </div>

                    @if($faq->updater)
                        <div class="mb-0">
                            <label class="form-label small text-muted">Last Updated By</label>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm me-2">
                                    <span class="avatar-title rounded-circle bg-success text-white">
                                        {{ substr($faq->updater->name, 0, 1) }}
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $faq->updater->name }}</h6>
                                    <small class="text-muted">{{ $faq->updater->email }}</small>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:lightning-1-duotone" class="me-1"></iconify-icon>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.faq.edit', $faq) }}" class="btn btn-primary btn-sm">
                            <iconify-icon icon="iconamoon:edit-duotone" class="me-1"></iconify-icon>
                            Edit FAQ
                        </a>

                        <button class="btn btn-outline-primary btn-sm" onclick="toggleStatus()">
                            <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                            {{ $faq->status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>

                        <button class="btn btn-outline-warning btn-sm" onclick="toggleFeatured()">
                            <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>
                            {{ $faq->is_featured ? 'Remove Featured' : 'Mark Featured' }}
                        </button>

                        <button class="btn btn-outline-danger btn-sm" onclick="deleteFaq()">
                            <iconify-icon icon="iconamoon:delete-duotone" class="me-1"></iconify-icon>
                            Delete FAQ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
// FAQ actions
function toggleStatus() {
    fetch('{{ route('admin.faq.toggle-status', $faq) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
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
        showAlert('Failed to update status', 'danger');
    });
}

function toggleFeatured() {
    fetch('{{ route('admin.faq.toggle-featured', $faq) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update featured status', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update featured status', 'danger');
    });
}

function deleteFaq() {
    if (!confirm('Are you sure you want to delete this FAQ? This action cannot be undone.')) {
        return;
    }

    fetch('{{ route('admin.faq.destroy', $faq) }}', {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.href = '{{ route('admin.faq.faqs') }}';
            }, 1500);
        } else {
            showAlert(data.message || 'Failed to delete FAQ', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to delete FAQ', 'danger');
    });
}

// Utility functions
function showAlert(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const iconMap = {
        success: 'check-circle-1',
        danger: 'close-circle-1',
        warning: 'attention-circle',
        info: 'information-circle'
    };
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show shadow-sm" id="${alertId}" role="alert">
            <iconify-icon icon="iconamoon:${iconMap[type]}-duotone" class="me-2"></iconify-icon>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
</script>

<style>
.card {
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-weight: 600;
    font-size: 0.875rem;
}

.status-badge,
.featured-badge {
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-badge:hover,
.featured-badge:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.faq-answer {
    line-height: 1.6;
    word-wrap: break-word;
}

.btn {
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
}

#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

@media (max-width: 991.98px) {
    .order-lg-2 {
        order: 2;
    }
    
    .order-lg-1 {
        order: 1;
    }
}
</style>
@endsection