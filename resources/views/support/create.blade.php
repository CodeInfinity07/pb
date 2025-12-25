@extends('layouts.vertical', ['title' => 'Create Support Ticket', 'subTitle' => 'Help & Support'])

@section('content')

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card">
            <div class="card-header text-center">
                <div class="mb-3">
                    <iconify-icon icon="iconamoon:ticket-duotone" class="fs-1 text-primary"></iconify-icon>
                </div>
                <h4 class="card-title mb-1 h5 h-md-4">Create Support Ticket</h4>
                <p class="text-muted mb-0 small">We're here to help! Describe your issue and we'll get back to you soon.</p>
            </div>
            <div class="card-body p-3 p-md-4">
                <form id="supportTicketForm" enctype="multipart/form-data">
                    @csrf
                    
                    {{-- Subject --}}
                    <div class="mb-3">
                        <label for="subject" class="form-label fw-semibold">
                            Subject <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="subject" 
                               name="subject" 
                               required 
                               placeholder="Brief description of your issue" 
                               minlength="5" 
                               maxlength="255">
                        <div class="invalid-feedback"></div>
                        <div class="form-text small">Minimum 5 characters, maximum 255 characters</div>
                    </div>

                    {{-- Category and Priority Row --}}
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label for="category" class="form-label fw-semibold">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="technical">Technical Issue</option>
                                <option value="billing">Billing & Payments</option>
                                <option value="account">Account Management</option>
                                <option value="feature">Feature Request</option>
                                <option value="bug">Bug Report</option>
                                <option value="general">General Inquiry</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12 col-md-6 mb-3">
                            <label for="priority" class="form-label fw-semibold">
                                Priority <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="">Select priority level</option>
                                <option value="low">Low - General question</option>
                                <option value="medium" selected>Medium - Standard issue</option>
                                <option value="high">High - Important issue</option>
                                <option value="urgent">Urgent - Critical issue</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">
                            Description <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  rows="5" 
                                  required
                                  placeholder="Please provide detailed information about your issue. Include steps to reproduce if it's a bug, or specific questions you have."
                                  minlength="20"></textarea>
                        <div class="invalid-feedback"></div>
                        <div class="form-text small">Minimum 20 characters. Be as detailed as possible to help us assist you better.</div>
                    </div>

                    {{-- Attachments --}}
                    <div class="mb-4">
                        <label for="attachments" class="form-label fw-semibold">Attachments (Optional)</label>
                        <div class="row g-2">
                            <div class="col-12 col-sm-9">
                                <input type="file" 
                                       class="form-control" 
                                       id="attachments" 
                                       name="attachments[]" 
                                       multiple 
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt">
                            </div>
                            <div class="col-12 col-sm-3">
                                <button type="button" 
                                        class="btn btn-outline-secondary w-100" 
                                        onclick="clearAttachments()">
                                    <iconify-icon icon="iconamoon:close-duotone" class="me-1 d-none d-sm-inline"></iconify-icon>
                                    <span class="d-sm-none">Clear Files</span>
                                    <span class="d-none d-sm-inline">Clear</span>
                                </button>
                            </div>
                        </div>
                        <div class="invalid-feedback"></div>
                        <div class="form-text small">
                            Maximum 10MB per file. Supported: JPG, PNG, PDF, DOC, DOCX, TXT
                        </div>
                        
                        {{-- File Preview --}}
                        <div id="filePreview" class="mt-3" style="display: none;">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="mb-2 small fw-semibold">Selected Files:</h6>
                                <div id="fileList"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Buttons --}}
                    <div class="row g-2">
                        <div class="col-12 col-sm-6">
                            <button type="button" 
                                    class="btn btn-secondary w-100" 
                                    onclick="history.back()">
                                <iconify-icon icon="iconamoon:arrow-left-duotone" class="me-1"></iconify-icon>
                                Cancel
                            </button>
                        </div>
                        <div class="col-12 col-sm-6">
                            <button type="submit" 
                                    class="btn btn-primary w-100" 
                                    id="submitBtn">
                                <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                                <span id="submitText">Create Ticket</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Help Section --}}
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0 h6 h-md-5">
                    <iconify-icon icon="iconamoon:lightbulb-duotone" class="me-2"></iconify-icon>
                    Before Creating a Ticket
                </h5>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 g-md-4">
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-3">
                                <div class="btn btn-soft-info btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem;">
                                    <iconify-icon icon="iconamoon:document-duotone"></iconify-icon>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 small fw-semibold">Check FAQ</h6>
                                <p class="text-muted small mb-0">Many common questions are answered in our FAQ section.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-3">
                                <div class="btn btn-soft-success btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 2.5rem; height: 2.5rem;">
                                    <iconify-icon icon="iconamoon:document-duotone"></iconify-icon>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 small fw-semibold">Check Documentation</h6>
                                <p class="text-muted small mb-0">Our user guides might have the solution you need.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="text-muted">
                    <h6 class="mb-2 small fw-semibold">Tips for better support:</h6>
                    <ul class="mb-0 ps-3 small">
                        <li class="mb-1">Be specific about the problem you're experiencing</li>
                        <li class="mb-1">Include screenshots or error messages if applicable</li>
                        <li class="mb-1">Mention what device/browser you're using if it's a technical issue</li>
                        <li>Describe the steps you've already tried</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('supportTicketForm');
    const attachmentsInput = document.getElementById('attachments');
    const filePreview = document.getElementById('filePreview');
    const fileList = document.getElementById('fileList');

    // File upload preview
    attachmentsInput.addEventListener('change', function() {
        handleFilePreview(this.files);
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        submitTicket();
    });

    // Auto-resize textarea
    const descriptionTextarea = document.getElementById('description');
    descriptionTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 200) + 'px';
    });

    // Character counters
    document.getElementById('subject').addEventListener('input', function() {
        updateCharacterCounter(this, 255, 5);
    });

    document.getElementById('description').addEventListener('input', function() {
        updateCharacterCounter(this, null, 20);
    });
});

function handleFilePreview(files) {
    const filePreview = document.getElementById('filePreview');
    const fileList = document.getElementById('fileList');
    
    if (files.length === 0) {
        filePreview.style.display = 'none';
        return;
    }

    fileList.innerHTML = '';
    let totalSize = 0;
    
    Array.from(files).forEach((file, index) => {
        totalSize += file.size;
        
        const fileItem = document.createElement('div');
        fileItem.className = 'd-flex align-items-center justify-content-between p-2 border rounded mb-2 bg-white';
        fileItem.innerHTML = `
            <div class="d-flex align-items-center min-w-0 flex-grow-1">
                <iconify-icon icon="iconamoon:file-duotone" class="me-2 text-info flex-shrink-0"></iconify-icon>
                <div class="min-w-0 flex-grow-1">
                    <div class="fw-semibold small text-truncate">${file.name}</div>
                    <small class="text-muted">${formatFileSize(file.size)}</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" onclick="removeFile(${index})">
                <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
            </button>
        `;
        fileList.appendChild(fileItem);
    });
    
    // Check total file size
    if (totalSize > 50 * 1024 * 1024) { // 50MB total limit
        showAlert('Total file size exceeds 50MB limit', 'warning');
    }
    
    filePreview.style.display = 'block';
}

function removeFile(index) {
    const attachmentsInput = document.getElementById('attachments');
    const dt = new DataTransfer();
    const files = attachmentsInput.files;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }
    
    attachmentsInput.files = dt.files;
    handleFilePreview(dt.files);
}

function clearAttachments() {
    const attachmentsInput = document.getElementById('attachments');
    const filePreview = document.getElementById('filePreview');
    
    attachmentsInput.value = '';
    filePreview.style.display = 'none';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function submitTicket() {
    const form = document.getElementById('supportTicketForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    
    // Reset previous validation states
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    
    // Show loading state
    submitBtn.disabled = true;
    submitText.textContent = 'Creating...';
    submitBtn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2"></span>');
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Submit form
    fetch('{{ route("support.store") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Redirect to ticket view
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 1500);
        } else {
            // Handle validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = form.querySelector(`[name="${field}"], [name="${field}[]"]`);
                    if (input) {
                        input.classList.add('is-invalid');
                        const feedback = input.parentNode.querySelector('.invalid-feedback');
                        if (feedback) {
                            feedback.textContent = data.errors[field][0];
                        }
                    }
                });
            } else {
                showAlert(data.message || 'Failed to create ticket', 'danger');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while creating the ticket', 'danger');
    })
    .finally(() => {
        // Reset loading state
        submitBtn.disabled = false;
        submitText.textContent = 'Create Ticket';
        const spinner = submitBtn.querySelector('.spinner-border');
        if (spinner) spinner.remove();
    });
}

function updateCharacterCounter(input, maxLength, minLength) {
    const currentLength = input.value.length;
    let feedback = input.parentNode.querySelector('.form-text');
    
    if (feedback) {
        let text = '';
        let className = 'form-text small text-muted';
        
        if (minLength && maxLength) {
            text = `${currentLength}/${maxLength} characters (minimum ${minLength})`;
        } else if (maxLength) {
            text = `${currentLength}/${maxLength} characters`;
        } else if (minLength) {
            text = `${currentLength} characters (minimum ${minLength})`;
        }
        
        if (currentLength < minLength) {
            className = 'form-text small text-warning';
        } else if (maxLength && currentLength > maxLength * 0.9) {
            className = 'form-text small text-info';
        } else if (maxLength && currentLength > maxLength) {
            className = 'form-text small text-danger';
        }
        
        feedback.className = className;
        feedback.textContent = text;
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; left: 20px; right: 20px; z-index: 9999; max-width: calc(100% - 40px);';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 5000);
}
</script>

<style>
/* Base card styling */
.card {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: none;
}

/* Form improvements */
.form-control, 
.form-select {
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus, 
.form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-label {
    margin-bottom: 0.5rem;
    color: #495057;
}

/* File upload styling */
#attachments {
    cursor: pointer;
}

#filePreview {
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Button improvements */
.btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn:hover:not(.disabled):not(:disabled) {
    transform: translateY(-1px);
}

.btn-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(45deg, #0056b3, #004085);
}

.btn-soft-info {
    background-color: rgba(13, 202, 240, 0.1);
    border-color: transparent;
    color: #0dcaf0;
}

.btn-soft-success {
    background-color: rgba(25, 135, 84, 0.1);
    border-color: transparent;
    color: #198754;
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* File preview styling */
.bg-light {
    background-color: #f8f9fa !important;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.min-w-0 {
    min-width: 0;
}

/* Mobile-first responsive design */
@media (max-width: 575.98px) {
    /* Card adjustments */
    .card {
        margin: 0 0.5rem;
        border-radius: 8px;
    }
    
    .card-header {
        padding: 1rem 1rem 0.5rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    /* Typography scaling */
    .card-title {
        font-size: 1.1rem;
    }
    
    .fs-1 {
        font-size: 2rem !important;
    }
    
    /* Form elements */
    .form-control,
    .form-select {
        font-size: 0.9rem;
        padding: 0.6rem 0.75rem;
    }
    
    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .form-text {
        font-size: 0.75rem;
    }
    
    /* Textarea specific */
    #description {
        min-height: 100px;
        max-height: 150px;
    }
    
    /* Button adjustments */
    .btn {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    
    /* File preview on mobile */
    .bg-light {
        padding: 0.75rem;
    }
    
    #fileList .d-flex {
        padding: 0.5rem;
    }
    
    #fileList .fw-semibold {
        font-size: 0.85rem;
    }
    
    #fileList small {
        font-size: 0.75rem;
    }
    
    /* Help section mobile */
    .btn.rounded-circle {
        width: 2rem !important;
        height: 2rem !important;
        font-size: 0.8rem;
    }
    
    /* Alert positioning */
    .position-fixed {
        left: 10px !important;
        right: 10px !important;
        max-width: calc(100% - 20px) !important;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    /* Small tablet adjustments */
    .card-body {
        padding: 1.5rem;
    }
    
    .form-control,
    .form-select {
        font-size: 0.95rem;
    }
    
    #description {
        min-height: 120px;
        max-height: 180px;
    }
}

@media (min-width: 768px) {
    /* Desktop optimizations */
    .card-body {
        padding: 2rem;
    }
    
    .form-control,
    .form-select {
        padding: 0.75rem 1rem;
    }
    
    #description {
        min-height: 140px;
        max-height: 200px;
    }
}

/* Character counter styling */
.form-text.text-warning {
    color: #f0ad4e !important;
}

.form-text.text-danger {
    color: #dc3545 !important;
}

.form-text.text-info {
    color: #17a2b8 !important;
}

/* Invalid feedback styling */
.invalid-feedback {
    font-size: 0.85rem;
    font-weight: 500;
}

/* Auto-resize textarea */
textarea {
    resize: vertical;
    transition: height 0.2s ease;
}

/* Icon consistency */
iconify-icon {
    vertical-align: middle;
}

/* Smooth interactions */
* {
    transition: all 0.2s ease;
}

/* Priority option colors for visual feedback */
.form-select option[value="low"] {
    background-color: #f8f9fa;
}

.form-select option[value="medium"] {
    background-color: #e7f3ff;
}

.form-select option[value="high"] {
    background-color: #fff3cd;
}

.form-select option[value="urgent"] {
    background-color: #f8d7da;
}

/* Focus management for accessibility */
.form-control:focus,
.form-select:focus,
.btn:focus {
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Loading state improvements */
.btn:disabled {
    opacity: 0.8;
    cursor: not-allowed;
    transform: none;
}
</style>
@endsection