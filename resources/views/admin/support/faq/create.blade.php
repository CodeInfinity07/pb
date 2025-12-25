{{-- resources/views/admin/faq/create.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'Create FAQ', 'subTitle' => 'Add a new frequently asked question'])

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
                                <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-2"></iconify-icon>
                                Create New FAQ
                            </h4>
                            <p class="text-muted mb-0">Add a frequently asked question to help users</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('admin.faq.faqs') }}" class="btn btn-outline-secondary btn-sm">
                                <iconify-icon icon="iconamoon:arrow-left-2-duotone" class="me-1"></iconify-icon>
                                Back to FAQs
                            </a>
                            <a href="{{ route('admin.faq.index') }}" class="btn btn-outline-info btn-sm">
                                <iconify-icon icon="iconamoon:apps-duotone" class="me-1"></iconify-icon>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Form --}}
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:question-mark-circle-duotone" class="me-2"></iconify-icon>
                        FAQ Information
                    </h5>
                </div>
                <form id="faqForm" novalidate>
                    @csrf
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Question --}}
                            <div class="col-12">
                                <label for="question" class="form-label">
                                    Question <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="question" name="question" rows="2" required 
                                          placeholder="Enter the frequently asked question..." maxlength="500"></textarea>
                                <div class="form-text">
                                    <span id="questionCount">0</span>/500 characters
                                </div>
                                <div class="invalid-feedback">Please enter a valid question (maximum 500 characters).</div>
                            </div>

                            {{-- Answer --}}
                            <div class="col-12">
                                <label for="answer" class="form-label">
                                    Answer <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="answer" name="answer" rows="8" required 
                                          placeholder="Provide a comprehensive answer to the question..."></textarea>
                                <div class="form-text">You can use HTML formatting for better presentation.</div>
                                <div class="invalid-feedback">Please provide a detailed answer.</div>
                            </div>

                            {{-- Category and Status --}}
                            <div class="col-md-6">
                                <label for="category" class="form-label">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">Please select a category.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div class="invalid-feedback">Please select a status.</div>
                            </div>

                            {{-- Sort Order and Featured --}}
                            <div class="col-md-6">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="0" min="0" placeholder="0">
                                <div class="form-text">Lower numbers appear first. Default is 0.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Display Options</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured">
                                    <label class="form-check-label" for="is_featured">
                                        <iconify-icon icon="iconamoon:star-duotone" class="me-1"></iconify-icon>
                                        Mark as Featured
                                    </label>
                                    <div class="form-text">Featured FAQs are highlighted and shown prominently.</div>
                                </div>
                            </div>

                            {{-- Tags --}}
                            <div class="col-12">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags" 
                                       placeholder="Enter tags separated by commas (e.g., login, password, security)">
                                <div class="form-text">
                                    Add relevant tags to help users find this FAQ. Separate multiple tags with commas.
                                </div>
                            </div>

                            {{-- Preview Section --}}
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h6 class="card-title mb-0">
                                            <iconify-icon icon="iconamoon:eye-duotone" class="me-1"></iconify-icon>
                                            Live Preview
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="preview-question" class="fw-bold mb-2 text-muted">
                                            <em>Question will appear here as you type...</em>
                                        </div>
                                        <div id="preview-answer" class="text-muted">
                                            <em>Answer will appear here as you type...</em>
                                        </div>
                                        <div id="preview-meta" class="mt-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-text mb-0">
                                <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                All fields marked with <span class="text-danger">*</span> are required.
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                    <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                    Reset
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                                    <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-1"></iconify-icon>
                                    <span id="submitText">Create FAQ</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Alert Container --}}
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

@endsection

@section('script')
<script>
let isSubmitting = false;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupFormValidation();
    setupLivePreview();
    setupCharacterCount();
});

// Form validation
function setupFormValidation() {
    const form = document.getElementById('faqForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting || !validateForm(this)) return;
        
        submitForm(this);
    });
}

function validateForm(form) {
    form.classList.add('was-validated');
    let isValid = form.checkValidity();
    
    // Additional custom validation
    const question = document.getElementById('question').value.trim();
    const answer = document.getElementById('answer').value.trim();
    
    if (question.length < 10) {
        document.getElementById('question').setCustomValidity('Question must be at least 10 characters long');
        isValid = false;
    } else {
        document.getElementById('question').setCustomValidity('');
    }
    
    if (answer.length < 20) {
        document.getElementById('answer').setCustomValidity('Answer must be at least 20 characters long');
        isValid = false;
    } else {
        document.getElementById('answer').setCustomValidity('');
    }
    
    return isValid;
}

// Live preview
function setupLivePreview() {
    const questionInput = document.getElementById('question');
    const answerInput = document.getElementById('answer');
    const categorySelect = document.getElementById('category');
    const statusSelect = document.getElementById('status');
    const featuredCheckbox = document.getElementById('is_featured');

    [questionInput, answerInput, categorySelect, statusSelect, featuredCheckbox].forEach(element => {
        element.addEventListener('input', updatePreview);
        element.addEventListener('change', updatePreview);
    });

    updatePreview();
}

function updatePreview() {
    const question = document.getElementById('question').value.trim();
    const answer = document.getElementById('answer').value.trim();
    const category = document.getElementById('category').value;
    const status = document.getElementById('status').value;
    const featured = document.getElementById('is_featured').checked;

    // Update question preview
    const questionPreview = document.getElementById('preview-question');
    if (question) {
        questionPreview.innerHTML = question;
        questionPreview.className = 'fw-bold mb-2';
    } else {
        questionPreview.innerHTML = '<em>Question will appear here as you type...</em>';
        questionPreview.className = 'fw-bold mb-2 text-muted';
    }

    // Update answer preview
    const answerPreview = document.getElementById('preview-answer');
    if (answer) {
        // Simple HTML rendering (be careful in production)
        answerPreview.innerHTML = answer.replace(/\n/g, '<br>');
        answerPreview.className = '';
    } else {
        answerPreview.innerHTML = '<em>Answer will appear here as you type...</em>';
        answerPreview.className = 'text-muted';
    }

    // Update meta information
    const metaPreview = document.getElementById('preview-meta');
    let metaHtml = '';
    
    if (category) {
        const categoryText = document.querySelector(`#category option[value="${category}"]`).textContent;
        metaHtml += `<span class="badge bg-primary me-1">${categoryText}</span>`;
    }
    
    if (status) {
        const statusClass = status === 'active' ? 'bg-success' : 'bg-secondary';
        metaHtml += `<span class="badge ${statusClass} me-1">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    }
    
    if (featured) {
        metaHtml += `<span class="badge bg-warning me-1">Featured</span>`;
    }
    
    metaPreview.innerHTML = metaHtml;
}

// Character count
function setupCharacterCount() {
    const questionInput = document.getElementById('question');
    const countElement = document.getElementById('questionCount');
    
    questionInput.addEventListener('input', function() {
        const count = this.value.length;
        countElement.textContent = count;
        
        if (count > 450) {
            countElement.className = 'text-warning';
        } else if (count > 500) {
            countElement.className = 'text-danger';
        } else {
            countElement.className = '';
        }
    });
}

// Submit form
function submitForm(form) {
    isSubmitting = true;
    toggleLoading(form, true);
    
    const formData = new FormData(form);
    
    // Handle boolean checkbox properly
    const isFeatured = document.getElementById('is_featured').checked;
    formData.delete('is_featured');
    formData.append('is_featured', isFeatured ? '1' : '0');
    
    fetch('{{ route('admin.faq.store') }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
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
            showAlert(data.message || 'Failed to create FAQ', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while creating the FAQ.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(form, false);
    });
}

// Reset form
function resetForm() {
    if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
        document.getElementById('faqForm').reset();
        document.getElementById('faqForm').classList.remove('was-validated');
        updatePreview();
        document.getElementById('questionCount').textContent = '0';
        showAlert('Form has been reset', 'info');
    }
}

// Utility functions
function toggleLoading(form, isLoading) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    const submitText = document.getElementById('submitText');
    
    if (isLoading) {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitText.textContent = 'Creating...';
    } else {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        submitText.textContent = 'Create FAQ';
    }
}

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

.form-control,
.form-select {
    border-radius: 0.5rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
    border-radius: 0.5rem;
    transition: all 0.15s ease-in-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
}

.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
}

#preview-question {
    font-size: 1.1rem;
}

#preview-answer {
    line-height: 1.6;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}
</style>
@endsection