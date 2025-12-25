{{-- resources/views/faq/index.blade.php --}}
@extends('layouts.vertical', ['title' => 'Frequently Asked Questions', 'subTitle' => 'Find answers to common questions'])

@section('content')
<div class="row">
    <div class="col">

        {{-- Statistics Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <i class="bx bx-help-circle text-primary fs-2 mb-2"></i>
                        <h5 class="text-primary mb-1">{{ number_format($stats['total_faqs']) }}</h5>
                        <small class="text-muted">Total FAQs</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <i class="bx bx-category text-success fs-2 mb-2"></i>
                        <h5 class="text-success mb-1">{{ number_format($stats['total_categories']) }}</h5>
                        <small class="text-muted">Categories</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <i class="bx bx-star text-warning fs-2 mb-2"></i>
                        <h5 class="text-warning mb-1">{{ number_format($stats['featured_count']) }}</h5>
                        <small class="text-muted">Featured</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <i class="bx bx-show text-info fs-2 mb-2"></i>
                        <h5 class="text-info mb-1">{{ number_format($totalViews) }}</h5>
                        <small class="text-muted">Total Views</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main FAQ Content --}}
        <div class="card">
            <div class="card-body p-4">
                {{-- Featured FAQs Section --}}
                @if($featuredFaqs->count() > 0)
                    <div class="mb-5">
                        <div class="d-flex align-items-center mb-4">
                            <i class="bx bx-star text-warning fs-3 me-2"></i>
                            <h4 class="mb-0 fw-semibold">Featured Questions</h4>
                        </div>
                        
                        <div class="row g-3">
                            @foreach($featuredFaqs as $faq)
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border h-100 featured-faq-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-warning">Featured</span>
                                                <span class="badge {{ $faq->category_badge }}">{{ $faq->category_text }}</span>
                                            </div>
                                            <h6 class="card-title">{{ Str::limit($faq->question, 60) }}</h6>
                                            <p class="card-text text-muted small">{{ Str::limit(strip_tags($faq->answer), 80) }}</p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">{{ number_format($faq->views) }} views</small>
                                                <button class="btn btn-outline-primary btn-sm" onclick="showFaqModal({{ $faq->id }})">
                                                    Read More
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Category Tabs --}}
                @if($faqsByCategory->count() > 0)
                    <div class="mb-4">
                        <ul class="nav nav-pills justify-content-center flex-wrap" id="categoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all-content" 
                                        type="button" role="tab" aria-controls="all-content" aria-selected="true">
                                    <i class="bx bx-grid-alt me-1"></i> All Categories
                                </button>
                            </li>
                            @foreach($faqsByCategory as $category => $categoryFaqs)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="{{ $category }}-tab" data-bs-toggle="pill" 
                                            data-bs-target="#{{ $category }}-content" type="button" role="tab" 
                                            aria-controls="{{ $category }}-content" aria-selected="false">
                                        <i class="bx bx-tag me-1"></i> 
                                        {{ \App\Models\Faq::getCategories()[$category] ?? ucfirst($category) }}
                                        <span class="badge bg-secondary ms-1">{{ $categoryFaqs->count() }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- FAQ Content by Category --}}
                    <div class="tab-content" id="categoryTabContent">
                        {{-- All FAQs Tab --}}
                        <div class="tab-pane fade show active" id="all-content" role="tabpanel" aria-labelledby="all-tab">
                            <div class="row g-xl-4">
                                @foreach($faqsByCategory as $category => $categoryFaqs)
                                    <div class="col-xl-6">
                                        <h4 class="mb-3 fw-semibold fs-16 d-flex align-items-center">
                                            <i class="bx bx-category me-2 text-primary"></i>
                                            {{ \App\Models\Faq::getCategories()[$category] ?? ucfirst($category) }}
                                            <span class="badge bg-primary ms-2">{{ $categoryFaqs->count() }}</span>
                                        </h4>
                                        
                                        <div class="accordion" id="accordion{{ ucfirst($category) }}">
                                            @foreach($categoryFaqs->take(5) as $index => $faq)
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button fw-medium {{ $index === 0 ? '' : 'collapsed' }}" 
                                                                type="button" data-bs-toggle="collapse" 
                                                                data-bs-target="#faq{{ $faq->id }}" 
                                                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                                                aria-controls="faq{{ $faq->id }}">
                                                            {{ $faq->question }}
                                                            @if($faq->is_featured)
                                                                <i class="bx bx-star text-warning ms-2"></i>
                                                            @endif
                                                        </button>
                                                    </h2>
                                                    <div id="faq{{ $faq->id }}" 
                                                         class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                                         data-bs-parent="#accordion{{ ucfirst($category) }}">
                                                        <div class="accordion-body">
                                                            {!! nl2br(e($faq->answer)) !!}
                                                            
                                                            {{-- FAQ Meta --}}
                                                            <div class="mt-3 pt-2 border-top">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="d-flex gap-1">
                                                                        @if($faq->tags)
                                                                            @foreach(array_slice($faq->tags, 0, 3) as $tag)
                                                                                <span class="badge bg-light text-dark">#{{ $tag }}</span>
                                                                            @endforeach
                                                                        @endif
                                                                    </div>
                                                                    <small class="text-muted">
                                                                        <i class="bx bx-show me-1"></i>{{ number_format($faq->views) }} views
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            
                                            {{-- Show more button if there are more FAQs --}}
                                            @if($categoryFaqs->count() > 5)
                                                <div class="text-center mt-3">
                                                    <button class="btn btn-outline-primary btn-sm" onclick="loadMoreFaqs('{{ $category }}')">
                                                        <i class="bx bx-down-arrow-alt me-1"></i>
                                                        Show {{ $categoryFaqs->count() - 5 }} more questions
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Individual Category Tabs --}}
                        @foreach($faqsByCategory as $category => $categoryFaqs)
                            <div class="tab-pane fade" id="{{ $category }}-content" role="tabpanel" aria-labelledby="{{ $category }}-tab">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <h4 class="mb-4 text-center">
                                            <i class="bx bx-category text-primary me-2"></i>
                                            {{ \App\Models\Faq::getCategories()[$category] ?? ucfirst($category) }} FAQs
                                        </h4>
                                        
                                        <div class="accordion" id="accordion{{ ucfirst($category) }}Single">
                                            @foreach($categoryFaqs as $index => $faq)
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header">
                                                        <button class="accordion-button fw-medium {{ $index === 0 ? '' : 'collapsed' }}" 
                                                                type="button" data-bs-toggle="collapse" 
                                                                data-bs-target="#faqSingle{{ $faq->id }}" 
                                                                aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                                                aria-controls="faqSingle{{ $faq->id }}">
                                                            {{ $faq->question }}
                                                            @if($faq->is_featured)
                                                                <i class="bx bx-star text-warning ms-2"></i>
                                                            @endif
                                                        </button>
                                                    </h2>
                                                    <div id="faqSingle{{ $faq->id }}" 
                                                         class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                                         data-bs-parent="#accordion{{ ucfirst($category) }}Single">
                                                        <div class="accordion-body">
                                                            {!! nl2br(e($faq->answer)) !!}
                                                            
                                                            {{-- FAQ Meta --}}
                                                            <div class="mt-3 pt-2 border-top">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="d-flex gap-1">
                                                                        @if($faq->tags)
                                                                            @foreach(array_slice($faq->tags, 0, 3) as $tag)
                                                                                <span class="badge bg-light text-dark">#{{ $tag }}</span>
                                                                            @endforeach
                                                                        @endif
                                                                    </div>
                                                                    <small class="text-muted">
                                                                        <i class="bx bx-show me-1"></i>{{ number_format($faq->views) }} views
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- No FAQs Available --}}
                    <div class="text-center py-5">
                        <i class="bx bx-help-circle text-muted" style="font-size: 4rem;"></i>
                        <h4 class="text-muted mt-3">No FAQs Available</h4>
                        <p class="text-muted">No frequently asked questions have been added yet. Check back later!</p>
                    </div>
                @endif

                {{-- Contact Support Section --}}
                <div class="row my-5">
                    <div class="col-12 text-center">
                        <div class="card bg-light border-0">
                            <div class="card-body py-4">
                                <i class="bx bx-help-circle text-primary" style="font-size: 3rem;"></i>
                                <h4 class="mt-3 mb-3">Can't find what you're looking for?</h4>
                                <p class="text-muted mb-4">Our support team is here to help you with any questions or concerns.</p>
                                
                                <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                                    @if(Route::has('support.create'))
                                        <a href="{{ route('support.create') }}" class="btn btn-success">
                                            <i class="bx bx-envelope me-1"></i>
                                            Create Support Ticket
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- FAQ Modal for Quick View --}}
<div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="faqModalLabel">FAQ Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="faqModalContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let searchTimeout;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupSearch();
    incrementPageViews();
});

// Search functionality
function setupSearch() {
    const searchInput = document.getElementById('faqSearch');
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 3) {
            searchTimeout = setTimeout(() => {
                performLiveSearch(query);
            }, 300);
        } else {
            hideSearchResults();
        }
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
}

function performLiveSearch(query) {
    fetch(`{{ route('user.faq.search') }}?q=${encodeURIComponent(query)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.faqs.length > 0) {
            showSearchResults(data.faqs);
        } else {
            showNoResults(query);
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        hideSearchResults();
    });
}

function showSearchResults(faqs) {
    const resultsContainer = document.getElementById('searchResults');
    const resultsList = document.getElementById('searchResultsList');
    
    let html = '';
    faqs.forEach(faq => {
        html += `
            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                <div>
                    <h6 class="mb-1">${faq.question}</h6>
                    <div class="small text-muted">
                        <span class="badge bg-light text-dark">${faq.category}</span>
                        <span class="ms-1">${faq.views} views</span>
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm" onclick="showFaqFromSearch(${faq.id})">View</button>
            </div>
        `;
    });
    
    resultsList.innerHTML = html;
    resultsContainer.style.display = 'block';
}

function showNoResults(query) {
    const resultsContainer = document.getElementById('searchResults');
    const resultsList = document.getElementById('searchResultsList');
    
    resultsList.innerHTML = `
        <div class="text-center py-3">
            <i class="bx bx-search text-muted fs-2"></i>
            <p class="mb-2">No results found for "${query}"</p>
            <button class="btn btn-sm btn-outline-primary" onclick="requestNewFaq('${query}')">
                Request this as a new FAQ
            </button>
        </div>
    `;
    resultsContainer.style.display = 'block';
}

function hideSearchResults() {
    document.getElementById('searchResults').style.display = 'none';
}

function performSearch() {
    const query = document.getElementById('faqSearch').value.trim();
    if (query) {
        // You can implement full search page navigation here
        performLiveSearch(query);
    }
}

// FAQ Modal functions
function showFaqModal(faqId) {
    const modal = new bootstrap.Modal(document.getElementById('faqModal'));
    const content = document.getElementById('faqModalContent');
    
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Simulate loading FAQ details (you can make an AJAX call here)
    setTimeout(() => {
        // This would normally fetch FAQ details via AJAX
        content.innerHTML = `
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                FAQ modal content would be loaded here. You can implement AJAX loading for full FAQ details.
            </div>
        `;
    }, 1000);
}

function showFaqFromSearch(faqId) {
    hideSearchResults();
    showFaqModal(faqId);
}

// Load more FAQs functionality
function loadMoreFaqs(category) {
    // Implement AJAX loading for more FAQs in the category
    console.log('Loading more FAQs for category:', category);
    // You can implement this with AJAX to load additional FAQs
}

// Handle FAQ request form submission
document.getElementById('requestFaqForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const question = document.getElementById('requestQuestion').value.trim();
    const category = document.getElementById('requestCategory').value;
    const email = document.getElementById('requestEmail').value.trim();
    
    if (!question) {
        alert('Please enter your question.');
        return;
    }
    
    // Here you would normally send the request to your backend
    // For now, we'll just show a success message
    alert('Thank you for your FAQ request! We will review it and add it to our FAQ section if appropriate.');
    
    bootstrap.Modal.getInstance(document.getElementById('requestFaqModal')).hide();
    this.reset();
});

// Track page views (optional analytics)
function incrementPageViews() {
    // You can implement page view tracking here
    console.log('FAQ page viewed');
}

// Click outside to hide search results
document.addEventListener('click', function(e) {
    const searchResults = document.getElementById('searchResults');
    const searchInput = document.getElementById('faqSearch');
    
    if (!searchResults.contains(e.target) && e.target !== searchInput) {
        hideSearchResults();
    }
});

// Auto-expand accordion items when searching
function expandMatchingAccordions(searchTerm) {
    if (!searchTerm) return;
    
    document.querySelectorAll('.accordion-button').forEach(button => {
        const text = button.textContent.toLowerCase();
        if (text.includes(searchTerm.toLowerCase())) {
            const target = button.getAttribute('data-bs-target');
            const collapse = document.querySelector(target);
            if (collapse && !collapse.classList.contains('show')) {
                button.click();
            }
        }
    });
}
</script>

<style>
.featured-faq-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.featured-faq-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.nav-pills .nav-link {
    border-radius: 50px;
    margin: 0 0.25rem 0.5rem 0;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:hover {
    transform: translateY(-2px);
}

.accordion-button {
    font-weight: 500;
    border-radius: 0.5rem 0.5rem 0 0;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(13, 110, 253, 0.1);
    border-color: rgba(13, 110, 253, 0.25);
}

.accordion-item {
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.accordion-body {
    line-height: 1.6;
}

.badge {
    font-weight: 500;
}

#searchResults {
    position: absolute;
    z-index: 1000;
    width: 100%;
    max-height: 400px;
    overflow-y: auto;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.175);
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .nav-pills {
        justify-content: flex-start !important;
    }
    
    .nav-pills .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .fs-16 {
        font-size: 1.1rem !important;
    }
}

/* Animation for accordion expand */
.accordion-collapse {
    transition: all 0.3s ease;
}

/* Custom scrollbar for search results */
#searchResults::-webkit-scrollbar {
    width: 6px;
}

#searchResults::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#searchResults::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

#searchResults::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endsection