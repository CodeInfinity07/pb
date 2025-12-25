{{-- resources/views/admin/faq/index.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'FAQ Management', 'subTitle' => 'Manage frequently asked questions'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">
                                <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="me-2"></iconify-icon>
                                FAQ Management
                            </h4>
                            <p class="text-muted mb-0">Manage frequently asked questions and knowledge base</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-info btn-sm d-flex align-items-center" onclick="refreshStats()" id="refreshBtn">
                                <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                            <a href="{{ route('admin.faq.faqs') }}" class="btn btn-primary btn-sm d-flex align-items-center">
                                <iconify-icon icon="iconamoon:menu-burger-horizontal-duotone" class="me-1"></iconify-icon>
                                All FAQs
                            </a>
                            <a href="{{ route('admin.faq.create') }}" class="btn btn-success btn-sm d-flex align-items-center">
                                <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                                Add FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="text-primary mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total FAQs</h6>
                    <h4 class="mb-0 fw-bold text-primary">{{ number_format($stats['total_faqs']) }}</h4>
                    <small class="text-muted">All questions</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-success mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Active FAQs</h6>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['active_faqs']) }}</h4>
                    <small class="text-muted">Published</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:star-duotone" class="text-warning mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Featured FAQs</h6>
                    <h4 class="mb-0 fw-bold text-warning">{{ number_format($stats['featured_faqs']) }}</h4>
                    <small class="text-muted">Highlighted</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body">
                    <iconify-icon icon="iconamoon:eye-duotone" class="text-info mb-2" style="font-size: 2.5rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Total Views</h6>
                    <h4 class="mb-0 fw-bold text-info">{{ number_format($stats['total_views']) }}</h4>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Statistics --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:close-circle-1-duotone" class="text-secondary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Inactive</h6>
                    <h5 class="mb-0 fw-bold text-secondary">{{ number_format($stats['inactive_faqs']) }}</h5>
                    <small class="text-muted">Draft/Hidden</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:eye-duotone" class="text-primary mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Recent Views</h6>
                    <h5 class="mb-0 fw-bold text-primary">{{ number_format($stats['recent_views']) }}</h5>
                    <small class="text-muted">Last 7 days</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:folder-duotone" class="text-info mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Categories</h6>
                    <h5 class="mb-0 fw-bold text-info">{{ count($stats['faqs_by_category']) }}</h5>
                    <small class="text-muted">Different topics</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 text-center border-0 shadow-sm stats-card">
                <div class="card-body py-3">
                    <iconify-icon icon="iconamoon:trend-up-duotone" class="text-success mb-2" style="font-size: 2rem;"></iconify-icon>
                    <h6 class="text-muted mb-1">Avg Views</h6>
                    <h5 class="mb-0 fw-bold text-success">
                        {{ $stats['total_faqs'] > 0 ? number_format($stats['total_views'] / $stats['total_faqs'], 1) : '0' }}
                    </h5>
                    <small class="text-muted">Per FAQ</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Recent FAQs --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                        Recent FAQs
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.faq.faqs') }}" class="btn btn-outline-primary btn-sm">
                            View All
                        </a>
                    </div>
                </div>

                @if($recentFaqs->count() > 0)
                    <div class="card-body p-0">
                        {{-- Desktop View --}}
                        <div class="d-none d-lg-block">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">Question</th>
                                            <th class="border-0">Category</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Views</th>
                                            <th class="border-0">Created</th>
                                            <th class="border-0 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentFaqs->take(8) as $faq)
                                            <tr class="faq-row" style="cursor: pointer;" 
                                                onclick="window.location='{{ route('admin.faq.show', $faq) }}'">
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        @if($faq->is_featured)
                                                            <iconify-icon icon="iconamoon:star-duotone" class="text-warning me-2" title="Featured"></iconify-icon>
                                                        @endif
                                                        <span class="fw-semibold">{{ Str::limit($faq->question, 50) }}</span>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $faq->category_badge }}">
                                                        {{ $faq->category_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $faq->status_badge }}">
                                                        {{ $faq->status_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="text-muted">{{ number_format($faq->views) }}</span>
                                                </td>
                                                <td class="py-3">
                                                    <div>
                                                        <span class="text-muted small">{{ $faq->created_at->diffForHumans() }}</span>
                                                        <br><small class="text-muted">by {{ $faq->creator->name ?? 'Unknown' }}</small>
                                                    </div>
                                                </td>
                                                <td class="py-3 text-center">
                                                    <a href="{{ route('admin.faq.show', $faq) }}" class="btn btn-outline-primary btn-sm" onclick="event.stopPropagation()">
                                                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile View --}}
                        <div class="d-lg-none p-3">
                            <div class="row g-3">
                                @foreach($recentFaqs->take(6) as $faq)
                                    <div class="col-12">
                                        <div class="card border faq-mobile-card" onclick="window.location='{{ route('admin.faq.show', $faq) }}'">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        @if($faq->is_featured)
                                                            <span class="badge bg-warning ms-1">Featured</span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted">{{ $faq->created_at->diffForHumans() }}</small>
                                                </div>
                                                
                                                <h6 class="mb-1">{{ Str::limit($faq->question, 60) }}</h6>
                                                <p class="text-muted small mb-2">by {{ $faq->creator->name ?? 'Unknown' }}</p>
                                                
                                                <div class="d-flex flex-wrap gap-1">
                                                    <span class="badge {{ $faq->status_badge }}">{{ $faq->status_text }}</span>
                                                    <span class="badge {{ $faq->category_badge }}">{{ $faq->category_text }}</span>
                                                    <span class="badge bg-light text-dark">{{ number_format($faq->views) }} views</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-4">
                            <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h5 class="text-muted">No FAQs Created</h5>
                            <p class="text-muted">No frequently asked questions have been created yet.</p>
                            <a href="{{ route('admin.faq.create') }}" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                                Create First FAQ
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions & Stats --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:lightning-1-duotone" class="me-1"></iconify-icon>
                        Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.faq.create') }}" class="btn btn-success btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-2"></iconify-icon>
                                Add New FAQ
                            </span>
                        </a>
                        
                        <a href="{{ route('admin.faq.faqs', ['status' => 'active']) }}" class="btn btn-outline-success btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-2"></iconify-icon>
                                Active FAQs
                            </span>
                            <span class="badge bg-success">{{ $stats['active_faqs'] }}</span>
                        </a>
                        
                        <a href="{{ route('admin.faq.faqs', ['status' => 'inactive']) }}" class="btn btn-outline-secondary btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:close-circle-1-duotone" class="me-2"></iconify-icon>
                                Inactive FAQs
                            </span>
                            <span class="badge bg-secondary">{{ $stats['inactive_faqs'] }}</span>
                        </a>
                        
                        <a href="{{ route('admin.faq.faqs', ['is_featured' => '1']) }}" class="btn btn-outline-warning btn-sm d-flex justify-content-between align-items-center">
                            <span>
                                <iconify-icon icon="iconamoon:star-duotone" class="me-2"></iconify-icon>
                                Featured FAQs
                            </span>
                            <span class="badge bg-warning">{{ $stats['featured_faqs'] }}</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Categories Overview --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:folder-duotone" class="me-1"></iconify-icon>
                        Categories Overview
                    </h6>
                </div>
                <div class="card-body">
                    @if(count($stats['faqs_by_category']) > 0)
                        @foreach($stats['faqs_by_category'] as $category => $count)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">{{ \App\Models\Faq::getCategories()[$category] ?? ucfirst($category) }}</span>
                                <span class="badge bg-light text-dark">{{ $count }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No categories with FAQs yet.</p>
                    @endif
                </div>
            </div>

            {{-- Most Viewed FAQs --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:trend-up-duotone" class="me-1"></iconify-icon>
                        Most Viewed FAQs
                    </h6>
                </div>
                <div class="card-body">
                    @if($stats['most_viewed']->count() > 0)
                        @foreach($stats['most_viewed'] as $faq)
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        <a href="{{ route('admin.faq.show', $faq) }}" class="text-decoration-none">
                                            {{ Str::limit($faq->question, 40) }}
                                        </a>
                                    </h6>
                                    <small class="text-muted">{{ $faq->category_text }}</small>
                                </div>
                                <span class="badge bg-primary ms-2">{{ number_format($faq->views) }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0">No view data available yet.</p>
                    @endif
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
// Auto-refresh functionality
let autoRefreshInterval;
const REFRESH_INTERVAL = 300000; // 5 minutes

// Initialize dashboard
document.addEventListener('DOMContentLoaded', function() {
    startAutoRefresh();
    setupEventListeners();
});

function setupEventListeners() {
    // Add hover effects to FAQ rows
    document.querySelectorAll('.faq-row').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(0, 123, 255, 0.05)';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });

    // Add click handler for mobile FAQ cards
    document.querySelectorAll('.faq-mobile-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 0.5rem 1rem rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(() => {
        refreshStats(true); // Silent refresh
    }, REFRESH_INTERVAL);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
}

function refreshStats(silent = false) {
    const refreshBtn = document.getElementById('refreshBtn');
    
    if (!silent) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<iconify-icon icon="iconamoon:restart-duotone" class="me-1 spinning"></iconify-icon>Refreshing...';
        showAlert('Refreshing FAQ statistics...', 'info', 2000);
    }
    
    // Reload the page for now - you can implement AJAX refresh later
    setTimeout(() => {
        location.reload();
    }, silent ? 100 : 1000);
}

// Utility functions
function showAlert(message, type = 'info', duration = 4000) {
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

// Handle page visibility change to pause/resume auto-refresh
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoRefresh();
    } else {
        startAutoRefresh();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopAutoRefresh();
});
</script>

<style>
/* Use the same styling as the support dashboard */
.card {
    border-radius: 0.75rem;
    border: 1px solid rgba(0, 0, 0, 0.125);
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
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

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.faq-row {
    transition: all 0.2s ease;
}

.faq-mobile-card {
    transition: all 0.2s ease;
    cursor: pointer;
}

.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
}

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

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinning {
    animation: spin 1s linear infinite;
}

#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
    border-radius: 0.5rem;
}

@media (max-width: 991.98px) {
    .stats-card .card-body {
        padding: 1rem 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 575.98px) {
    .container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
    
    .stats-card h4 {
        font-size: 1.5rem;
    }
}
</style>
@endsection