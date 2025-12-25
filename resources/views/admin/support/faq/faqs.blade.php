{{-- resources/views/admin/faq/faqs.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'All FAQs', 'subTitle' => 'Manage frequently asked questions'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1 text-dark">All FAQs</h4>
                            <p class="text-muted mb-0">View and manage all frequently asked questions</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleFilters()" id="filterToggleBtn">
                                <iconify-icon icon="iconamoon:funnel-duotone" class="me-1"></iconify-icon>
                                Filters
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="refreshFaqs()">
                                <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                Refresh
                            </button>
                            <a href="{{ route('admin.faq.index') }}" class="btn btn-outline-info btn-sm">
                                <iconify-icon icon="iconamoon:apps-duotone" class="me-1"></iconify-icon>
                                Dashboard
                            </a>
                            <a href="{{ route('admin.faq.create') }}" class="btn btn-success btn-sm">
                                <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                                Add FAQ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Panel --}}
    <div id="filtersPanel" class="row mb-4" style="display: none;">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:funnel-duotone" class="me-1"></iconify-icon>
                        Filter FAQs
                    </h6>
                </div>
                <form method="GET" action="{{ route('admin.faq.faqs') }}" id="filterForm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="">All Statuses</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" name="category" id="category">
                                    <option value="">All Categories</option>
                                    @foreach(\App\Models\Faq::getCategories() as $key => $label)
                                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="is_featured" class="form-label">Featured</label>
                                <select class="form-select" name="is_featured" id="is_featured">
                                    <option value="">All FAQs</option>
                                    <option value="1" {{ request('is_featured') === '1' ? 'selected' : '' }}>Featured Only</option>
                                    <option value="0" {{ request('is_featured') === '0' ? 'selected' : '' }}>Non-Featured</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="sort_by" class="form-label">Sort By</label>
                                <select class="form-select" name="sort_by" id="sort_by">
                                    <option value="sort_order" {{ request('sort_by') === 'sort_order' ? 'selected' : '' }}>Sort Order</option>
                                    <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Date Created</option>
                                    <option value="views" {{ request('sort_by') === 'views' ? 'selected' : '' }}>Most Viewed</option>
                                    <option value="question" {{ request('sort_by') === 'question' ? 'selected' : '' }}>Question (A-Z)</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" name="search" id="search" 
                                       value="{{ request('search') }}" placeholder="Search questions, answers, or tags">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between">
                            <div>
                                @if(request()->hasAny(['status', 'category', 'is_featured', 'search']))
                                    <a href="{{ route('admin.faq.faqs') }}" class="btn btn-outline-secondary btn-sm">
                                        <iconify-icon icon="iconamoon:close-circle-1-duotone" class="me-1"></iconify-icon>
                                        Clear Filters
                                    </a>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <iconify-icon icon="iconamoon:discover-duotone" class="me-1"></iconify-icon>
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-primary mb-1">{{ number_format($faqs->total()) }}</h6>
                    <small class="text-muted">Total Found</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-success mb-1">{{ $faqs->where('status', 'active')->count() }}</h6>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-secondary mb-1">{{ $faqs->where('status', 'inactive')->count() }}</h6>
                    <small class="text-muted">Inactive</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-warning mb-1">{{ $faqs->where('is_featured', true)->count() }}</h6>
                    <small class="text-muted">Featured</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-info mb-1">{{ number_format($faqs->sum('views')) }}</h6>
                    <small class="text-muted">Total Views</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body py-3">
                    <h6 class="text-muted mb-1">{{ $faqs->unique('category')->count() }}</h6>
                    <small class="text-muted">Categories</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Bulk Actions --}}
    @if($faqs->count() > 0)
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-primary" id="bulkActionsCard" style="display: none;">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="text-primary me-3">
                                <span id="selectedCount">0</span> FAQ(s) selected
                            </span>
                            <button class="btn btn-outline-primary btn-sm me-2" onclick="selectAll()">Select All</button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">Clear</button>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="bulkAction('activate')">
                                <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-1"></iconify-icon>
                                Activate
                            </button>
                            <button class="btn btn-secondary btn-sm" onclick="bulkAction('deactivate')">
                                <iconify-icon icon="iconamoon:close-circle-1-duotone" class="me-1"></iconify-icon>
                                Deactivate
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="bulkAction('feature')">
                                <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>
                                Feature
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="bulkAction('delete')">
                                <iconify-icon icon="iconamoon:delete-duotone" class="me-1"></iconify-icon>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- FAQs Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        FAQs Management
                        @if(request()->hasAny(['status', 'category', 'is_featured', 'search']))
                            <small class="text-muted">(Filtered Results)</small>
                        @endif
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-muted">{{ $faqs->total() }} FAQs found</small>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="viewMode" id="tableView" checked>
                            <label class="btn btn-outline-primary btn-sm" for="tableView">
                                <iconify-icon icon="iconamoon:menu-burger-horizontal-duotone"></iconify-icon>
                            </label>
                            <input type="radio" class="btn-check" name="viewMode" id="cardView">
                            <label class="btn btn-outline-primary btn-sm" for="cardView">
                                <iconify-icon icon="iconamoon:apps-duotone"></iconify-icon>
                            </label>
                        </div>
                    </div>
                </div>

                @if($faqs->count() > 0)
                    <div class="card-body p-0">
                        {{-- Table View --}}
                        <div id="tableViewContent">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="border-0">
                                                <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()">
                                            </th>
                                            <th class="border-0">Question</th>
                                            <th class="border-0">Category</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Views</th>
                                            <th class="border-0">Created</th>
                                            <th class="border-0 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($faqs as $faq)
                                            <tr class="faq-row" data-faq-id="{{ $faq->id }}">
                                                <td class="py-3">
                                                    <input type="checkbox" class="faq-checkbox" value="{{ $faq->id }}" onchange="updateSelection()">
                                                </td>
                                                <td class="py-3">
                                                    <div class="d-flex align-items-center">
                                                        @if($faq->is_featured)
                                                            <iconify-icon icon="iconamoon:star-duotone" class="text-warning me-2" title="Featured"></iconify-icon>
                                                        @endif
                                                        <div>
                                                            <span class="fw-semibold">{{ Str::limit($faq->question, 60) }}</span>
                                                            @if($faq->tags)
                                                                <br><small class="text-muted">Tags: {{ implode(', ', array_slice($faq->tags, 0, 3)) }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $faq->category_badge }}">
                                                        {{ $faq->category_text }}
                                                    </span>
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge {{ $faq->status_badge }} status-badge" 
                                                          data-faq-id="{{ $faq->id }}" style="cursor: pointer;" 
                                                          title="Click to toggle status">
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
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.faq.show', $faq) }}">
                                                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                                                                    View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.faq.edit', $faq) }}">
                                                                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>
                                                                    Edit FAQ
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                                   onclick="toggleStatus({{ $faq->id }})">
                                                                    <iconify-icon icon="iconamoon:restart-duotone" class="me-2"></iconify-icon>
                                                                    Toggle Status
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                                   onclick="toggleFeatured({{ $faq->id }})">
                                                                    <iconify-icon icon="iconamoon:star-duotone" class="me-2"></iconify-icon>
                                                                    {{ $faq->is_featured ? 'Remove Featured' : 'Mark Featured' }}
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                                   onclick="deleteFaq({{ $faq->id }})">
                                                                    <iconify-icon icon="iconamoon:delete-duotone" class="me-2"></iconify-icon>
                                                                    Delete FAQ
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Card View --}}
                        <div id="cardViewContent" style="display: none;" class="p-3">
                            <div class="row g-3">
                                @foreach($faqs as $faq)
                                    <div class="col-lg-6 col-xl-4">
                                        <div class="card border h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <input type="checkbox" class="faq-checkbox" value="{{ $faq->id }}" onchange="updateSelection()">
                                                    <div class="d-flex gap-1">
                                                        @if($faq->is_featured)
                                                            <span class="badge bg-warning">Featured</span>
                                                        @endif
                                                        <span class="badge {{ $faq->status_badge }}">{{ $faq->status_text }}</span>
                                                    </div>
                                                </div>
                                                
                                                <h6 class="card-title mb-2">{{ Str::limit($faq->question, 80) }}</h6>
                                                <p class="text-muted small mb-2">{{ Str::limit(strip_tags($faq->answer), 100) }}</p>
                                                
                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    <span class="badge {{ $faq->category_badge }}">{{ $faq->category_text }}</span>
                                                    <span class="badge bg-light text-dark">{{ number_format($faq->views) }} views</span>
                                                </div>

                                                @if($faq->tags)
                                                    <div class="mb-2">
                                                        @foreach(array_slice($faq->tags, 0, 3) as $tag)
                                                            <span class="badge bg-light text-dark me-1">#{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">{{ $faq->created_at->diffForHumans() }}</small>
                                                    <div class="btn-group">
                                                        <a href="{{ route('admin.faq.show', $faq) }}" class="btn btn-outline-primary btn-sm">
                                                            <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                                        </a>
                                                        <a href="{{ route('admin.faq.edit', $faq) }}" class="btn btn-outline-secondary btn-sm">
                                                            <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Pagination --}}
                    @if($faqs->hasPages())
                        <div class="card-footer">
                            {{ $faqs->links() }}
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h5 class="text-muted">No FAQs Found</h5>
                            <p class="text-muted">
                                @if(request()->hasAny(['status', 'category', 'is_featured', 'search']))
                                    Try adjusting your search filters or 
                                    <a href="{{ route('admin.faq.faqs') }}" class="text-primary">clear all filters</a>
                                @else
                                    No frequently asked questions have been created yet.
                                @endif
                            </p>
                            @if(!request()->hasAny(['status', 'category', 'is_featured', 'search']))
                                <a href="{{ route('admin.faq.create') }}" class="btn btn-primary">
                                    <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                                    Create First FAQ
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
let selectedFaqs = new Set();

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    setupViewModeToggle();
});

// Event listeners
function setupEventListeners() {
    // Status badge clicks
    document.querySelectorAll('.status-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const faqId = this.dataset.faqId;
            toggleStatus(faqId);
        });
    });
}

function setupViewModeToggle() {
    document.getElementById('tableView').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('tableViewContent').style.display = 'block';
            document.getElementById('cardViewContent').style.display = 'none';
        }
    });

    document.getElementById('cardView').addEventListener('change', function() {
        if (this.checked) {
            document.getElementById('tableViewContent').style.display = 'none';
            document.getElementById('cardViewContent').style.display = 'block';
        }
    });
}

// Filter functions
function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    const btn = document.getElementById('filterToggleBtn');
    
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        btn.classList.add('active');
    } else {
        panel.style.display = 'none';
        btn.classList.remove('active');
    }
}

function refreshFaqs() {
    showAlert('Refreshing FAQs...', 'info');
    setTimeout(() => location.reload(), 500);
}

// Selection functions
function updateSelection() {
    const checkboxes = document.querySelectorAll('.faq-checkbox:checked');
    selectedFaqs = new Set(Array.from(checkboxes).map(cb => cb.value));
    
    document.getElementById('selectedCount').textContent = selectedFaqs.size;
    document.getElementById('bulkActionsCard').style.display = selectedFaqs.size > 0 ? 'block' : 'none';
    
    // Update select all checkbox
    const allCheckboxes = document.querySelectorAll('.faq-checkbox');
    document.getElementById('selectAllCheckbox').checked = selectedFaqs.size === allCheckboxes.length;
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllCheckbox').checked;
    document.querySelectorAll('.faq-checkbox').forEach(checkbox => {
        checkbox.checked = selectAll;
    });
    updateSelection();
}

function selectAll() {
    document.querySelectorAll('.faq-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelection();
}

function clearSelection() {
    document.querySelectorAll('.faq-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    selectedFaqs.clear();
    updateSelection();
}

// FAQ actions
function toggleStatus(faqId) {
    fetch(`{{ url('admin/faq') }}/${faqId}/toggle-status`, {
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

function toggleFeatured(faqId) {
    fetch(`{{ url('admin/faq') }}/${faqId}/toggle-featured`, {
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

function deleteFaq(faqId) {
    if (!confirm('Are you sure you want to delete this FAQ? This action cannot be undone.')) {
        return;
    }

    fetch(`{{ url('admin/faq') }}/${faqId}`, {
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
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to delete FAQ', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to delete FAQ', 'danger');
    });
}

// Bulk actions
function bulkAction(action) {
    if (selectedFaqs.size === 0) {
        showAlert('Please select at least one FAQ', 'warning');
        return;
    }

    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = `Activate ${selectedFaqs.size} FAQ(s)?`;
            break;
        case 'deactivate':
            confirmMessage = `Deactivate ${selectedFaqs.size} FAQ(s)?`;
            break;
        case 'feature':
            confirmMessage = `Mark ${selectedFaqs.size} FAQ(s) as featured?`;
            break;
        case 'unfeature':
            confirmMessage = `Remove ${selectedFaqs.size} FAQ(s) from featured?`;
            break;
        case 'delete':
            confirmMessage = `Delete ${selectedFaqs.size} FAQ(s)? This action cannot be undone.`;
            break;
    }

    if (!confirm(confirmMessage)) return;

    fetch('{{ route('admin.faq.bulk-action') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            faq_ids: Array.from(selectedFaqs)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to perform bulk action', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to perform bulk action', 'danger');
    });
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

// Show filters if any are applied
@if(request()->hasAny(['status', 'category', 'is_featured', 'search']))
    document.addEventListener('DOMContentLoaded', function() {
        toggleFilters();
    });
@endif
</script>

<style>
.card {
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.status-badge {
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-badge:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.faq-row {
    transition: all 0.2s ease;
}

.dropdown-menu {
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.dropdown-item {
    transition: all 0.15s ease-in-out;
}

.dropdown-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
}

#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

.form-control,
.form-select {
    border-radius: 0.5rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.btn {
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.badge {
    transition: all 0.2s ease;
}

.btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}
</style>
@endsection