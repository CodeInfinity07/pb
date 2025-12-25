{{-- resources/views/faq/show.blade.php --}}
@extends('layouts.vertical', ['title' => 'FAQ - ' . Str::limit($faq->question, 50), 'subTitle' => 'Frequently Asked Question'])

@section('content')
<div class="container-fluid">
    {{-- Breadcrumb --}}
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb bg-light p-3 rounded">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}" class="text-decoration-none">
                            <iconify-icon icon="iconamoon:home-duotone" class="me-1"></iconify-icon>
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('faq.index') }}" class="text-decoration-none">FAQs</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('faq.index', ['category' => $faq->category]) }}" class="text-decoration-none">
                            {{ $faq->category_text }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ Str::limit($faq->question, 50) }}
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        {{-- Main FAQ Content --}}
        <div class="col-lg-8 mb-4">
            {{-- FAQ Card --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge {{ $faq->category_badge }}">{{ $faq->category_text }}</span>
                                @if($faq->is_featured)
                                    <span class="badge bg-warning">
                                        <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>
                                        Featured
                                    </span>
                                @endif
                                <span class="badge bg-light text-dark">
                                    <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                    {{ number_format($faq->views) }} views
                                </span>
                            </div>
                            <h1 class="h4 mb-0 text-primary">{{ $faq->question }}</h1>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="shareFaq()" title="Share this FAQ">
                                <iconify-icon icon="iconamoon:share-duotone"></iconify-icon>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="printFaq()" title="Print this FAQ">
                                <iconify-icon icon="iconamoon:printer-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    {{-- FAQ Answer --}}
                    <div class="faq-answer mb-4">
                        {!! nl2br(e($faq->answer)) !!}
                    </div>

                    {{-- Tags --}}
                    @if($faq->tags && count($faq->tags) > 0)
                        <div class="mb-4">
                            <h6 class="text-muted mb-2">
                                <iconify-icon icon="iconamoon:hashtag-duotone" class="me-1"></iconify-icon>
                                Related Tags
                            </h6>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($faq->tags as $tag)
                                    <a href="{{ route('faq.index', ['search' => $tag]) }}" 
                                       class="badge bg-light text-dark text-decoration-none">
                                        #{{ $tag }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- FAQ Meta Information --}}
                    <div class="border-top pt-3">
                        <div class="row text-center">
                            <div class="col-6 col-md-3">
                                <div class="border-end">
                                    <div class="text-primary fw-bold">{{ number_format($faq->views) }}</div>
                                    <small class="text-muted">Views</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border-end">
                                    <div class="text-success fw-bold">{{ $faq->category_text }}</div>
                                    <small class="text-muted">Category</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border-end">
                                    <div class="text-info fw-bold">{{ $faq->created_at->format('M j, Y') }}</div>
                                    <small class="text-muted">Published</small>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-warning fw-bold">{{ $faq->updated_at->format('M j, Y') }}</div>
                                <small class="text-muted">Updated</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Helpful Section --}}
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h6 class="mb-3">Was this FAQ helpful?</h6>
                    <div class="d-flex justify-content-center gap-2">
                        <button class="btn btn-success btn-sm" onclick="rateFaq(true)" id="helpfulBtn">
                            <iconify-icon icon="iconamoon:thumb-up-duotone" class="me-1"></iconify-icon>
                            Yes, helpful
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="rateFaq(false)" id="notHelpfulBtn">
                            <iconify-icon icon="iconamoon:thumb-down-duotone" class="me-1"></iconify-icon>
                            Not helpful
                        </button>
                    </div>
                    <div id="ratingMessage" class="mt-3" style="display: none;">
                        <div class="alert alert-info mb-0">
                            Thank you for your feedback! It helps us improve our FAQ section.
                        </div>
                    </div>
                </div>
            </div>

            {{-- Related FAQs --}}
            @if($relatedFaqs->count() > 0)
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="iconamoon:link-duotone" class="me-2"></iconify-icon>
                            Related Questions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($relatedFaqs as $relatedFaq)
                                <a href="{{ route('faq.show', $relatedFaq) }}" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $relatedFaq->question }}</h6>
                                        <small class="text-muted">{{ Str::limit(strip_tags($relatedFaq->answer), 100) }}</small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">{{ number_format($relatedFaq->views) }} views</small>
                                        <br>
                                        <iconify-icon icon="iconamoon:arrow-right-2-duotone" class="text-primary"></iconify-icon>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
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
                        <a href="{{ route('faq.index') }}" class="btn btn-outline-primary btn-sm">
                            <iconify-icon icon="iconamoon:arrow-left-2-duotone" class="me-1"></iconify-icon>
                            Back to All FAQs
                        </a>
                        <a href="{{ route('faq.index', ['category' => $faq->category]) }}" class="btn btn-outline-success btn-sm">
                            <iconify-icon icon="iconamoon:folder-duotone" class="me-1"></iconify-icon>
                            More in {{ $faq->category_text }}
                        </a>
                        <button class="btn btn-outline-info btn-sm" onclick="shareFaq()">
                            <iconify-icon icon="iconamoon:share-duotone" class="me-1"></iconify-icon>
                            Share this FAQ
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="printFaq()">
                            <iconify-icon icon="iconamoon:printer-duotone" class="me-1"></iconify-icon>
                            Print FAQ
                        </button>
                    </div>
                </div>
            </div>

            {{-- Still Need Help? --}}
            <div class="card shadow-sm mb-4 bg-light">
                <div class="card-body text-center">
                    <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="fs-1 text-primary mb-3"></iconify-icon>
                    <h6 class="mb-3">Still Need Help?</h6>
                    <p class="text-muted mb-3">Can't find the answer you're looking for? Contact our support team!</p>
                    <div class="d-grid gap-2">
                        <a href="{{ route('support.create') }}" class="btn btn-primary btn-sm">
                            <iconify-icon icon="iconamoon:comment-add-duotone" class="me-1"></iconify-icon>
                            Create Support Ticket
                        </a>
                        <a href="{{ route('support.contact-us') }}" class="btn btn-outline-primary btn-sm">
                            <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>

            {{-- Search FAQs --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:search-duotone" class="me-1"></iconify-icon>
                        Search FAQs
                    </h6>
                </div>
                <div class="card-body">
                    <div class="input-group">
                        <input type="text" class="form-control" id="sidebarSearch" placeholder="Search for answers...">
                        <button class="btn btn-outline-primary" type="button" onclick="performSidebarSearch()">
                            <iconify-icon icon="iconamoon:search-duotone"></iconify-icon>
                        </button>
                    </div>
                    <div id="sidebarSearchResults" class="mt-3" style="display: none;">
                        <div class="list-group" id="sidebarSearchList"></div>
                    </div>
                </div>
            </div>

            {{-- Categories --}}
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:folder-duotone" class="me-1"></iconify-icon>
                        Browse Categories
                    </h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @php
                            $categories = \App\Models\Faq::active()->select('category')->distinct()->get()->pluck('category');
                        @endphp
                        @foreach($categories as $category)
                            <a href="{{ route('faq.index', ['category' => $category]) }}" 
                               class="list-group-item list-group-item-action {{ $faq->category === $category ? 'active' : '' }}">
                                <iconify-icon icon="iconamoon:tag-duotone" class="me-2"></iconify-icon>
                                {{ \App\Models\Faq::getCategories()[$category] ?? ucfirst($category) }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Share Modal --}}
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="shareUrl" class="form-label">FAQ URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareUrl" value="{{ url()->current() }}" readonly>
                        <button class="btn btn-outline-primary" type="button" onclick="copyToClipboard()">
                            <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                        </button>
                    </div>
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-sm" onclick="shareViaEmail()">
                        <iconify-icon icon="iconamoon:email-duotone" class="me-1"></iconify-icon>
                        Share via Email
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="shareOnSocial('twitter')">
                        <iconify-icon icon="iconamoon:share-duotone" class="me-1"></iconify-icon>
                        Share on Twitter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="copyToast" class="toast" role="alert">
        <div class="toast-body">
            <iconify-icon icon="iconamoon:check-circle-1-duotone" class="text-success me-2"></iconify-icon>
            URL copied to clipboard!
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let searchTimeout;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupSidebarSearch();
    setupKeyboardShortcuts();
});

// FAQ rating
function rateFaq(helpful) {
    // Disable buttons
    document.getElementById('helpfulBtn').disabled = true;
    document.getElementById('notHelpfulBtn').disabled = true;
    
    // Show thank you message
    document.getElementById('ratingMessage').style.display = 'block';
    
    // You can add AJAX call here to record the rating
    console.log('FAQ rated as:', helpful ? 'helpful' : 'not helpful');
}

// Share functionality
function shareFaq() {
    new bootstrap.Modal(document.getElementById('shareModal')).show();
}

function copyToClipboard() {
    const urlInput = document.getElementById('shareUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(urlInput.value).then(() => {
        const toast = new bootstrap.Toast(document.getElementById('copyToast'));
        toast.show();
    }).catch(err => {
        console.error('Failed to copy URL:', err);
        // Fallback for older browsers
        document.execCommand('copy');
        const toast = new bootstrap.Toast(document.getElementById('copyToast'));
        toast.show();
    });
}

function shareViaEmail() {
    const subject = encodeURIComponent('FAQ: {{ $faq->question }}');
    const body = encodeURIComponent(`I thought you might find this FAQ helpful:\n\n{{ $faq->question }}\n\n{{ url()->current() }}`);
    window.open(`mailto:?subject=${subject}&body=${body}`);
}

function shareOnSocial(platform) {
    const url = encodeURIComponent(window.location.href);
    const text = encodeURIComponent('{{ $faq->question }}');
    
    let shareUrl = '';
    switch(platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
    }
}

// Print functionality
function printFaq() {
    window.print();
}

// Sidebar search
function setupSidebarSearch() {
    const searchInput = document.getElementById('sidebarSearch');
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length >= 3) {
            searchTimeout = setTimeout(() => {
                performSidebarLiveSearch(query);
            }, 300);
        } else {
            hideSidebarSearchResults();
        }
    });

    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSidebarSearch();
        }
    });
}

function performSidebarLiveSearch(query) {
    fetch(`{{ route('faq.search') }}?q=${encodeURIComponent(query)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.faqs.length > 0) {
            showSidebarSearchResults(data.faqs);
        } else {
            hideSidebarSearchResults();
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        hideSidebarSearchResults();
    });
}

function showSidebarSearchResults(faqs) {
    const resultsContainer = document.getElementById('sidebarSearchResults');
    const resultsList = document.getElementById('sidebarSearchList');
    
    let html = '';
    faqs.forEach(faq => {
        html += `
            <a href="${faq.url}" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-1">${faq.question}</h6>
                    <small class="text-muted">${faq.views} views</small>
                </div>
                <small class="text-muted">${faq.category}</small>
            </a>
        `;
    });
    
    resultsList.innerHTML = html;
    resultsContainer.style.display = 'block';
}

function hideSidebarSearchResults() {
    document.getElementById('sidebarSearchResults').style.display = 'none';
}

function performSidebarSearch() {
    const query = document.getElementById('sidebarSearch').value.trim();
    if (query) {
        window.location.href = `{{ route('faq.index') }}?search=${encodeURIComponent(query)}`;
    }
}

// Keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search focus
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('sidebarSearch').focus();
        }
        
        // Ctrl/Cmd + P for print
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            printFaq();
        }
        
        // Escape to hide search results
        if (e.key === 'Escape') {
            hideSidebarSearchResults();
        }
    });
}

// Click outside to hide search results
document.addEventListener('click', function(e) {
    const searchResults = document.getElementById('sidebarSearchResults');
    const searchInput = document.getElementById('sidebarSearch');
    
    if (searchResults && !searchResults.contains(e.target) && e.target !== searchInput) {
        hideSidebarSearchResults();
    }
});
</script>

<style>
.faq-answer {
    line-height: 1.7;
    font-size: 1.1rem;
}

.faq-answer p {
    margin-bottom: 1rem;
}

.card {
    border-radius: 0.75rem;
    transition: all 0.2s ease;
}

.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
}

.list-group-item {
    border: none;
    border-radius: 0.5rem !important;
    margin-bottom: 0.25rem;
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transform: translateX(5px);
}

.list-group-item.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
}

.breadcrumb {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    font-weight: bold;
}

.btn {
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.modal-content {
    border-radius: 0.75rem;
}

.toast {
    border-radius: 0.5rem;
}

#sidebarSearchResults {
    max-height: 300px;
    overflow-y: auto;
}

/* Print styles */
@media print {
    .col-lg-4,
    .btn,
    .modal,
    .toast-container,
    nav {
        display: none !important;
    }
    
    .col-lg-8 {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
    }
    
    .badge {
        border: 1px solid #dee2e6;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .faq-answer {
        font-size: 1rem;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endsection