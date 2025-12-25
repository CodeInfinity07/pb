@extends('layouts.vertical', ['title' => 'Support Ticket #' . $ticket->ticket_number, 'subTitle' => 'Help & Support'])

@section('content')

<div class="row">
    {{-- Main Content --}}
    <div class="col-12 col-lg-8 mb-4 mb-lg-0">
        {{-- Ticket Details Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <h4 class="card-title mb-0 h5 h-md-4 text-truncate">{{ $ticket->subject }}</h4>
                            <span class="badge bg-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }} fs-6 text-nowrap">
                                {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-md-3 text-muted small flex-wrap">
                            <span class="text-nowrap">
                                <iconify-icon icon="iconamoon:ticket-duotone" class="me-1"></iconify-icon>
                                #{{ $ticket->ticket_number }}
                            </span>
                            <span class="text-nowrap">
                                <iconify-icon icon="iconamoon:calendar-duotone" class="me-1"></iconify-icon>
                                {{ $ticket->created_at->format('M d, Y') }}
                                <span class="d-none d-sm-inline">• {{ $ticket->created_at->format('H:i') }}</span>
                            </span>
                            <span class="text-nowrap">
                                <iconify-icon icon="iconamoon:user-duotone" class="me-1"></iconify-icon>
                                {{ $ticket->user->name }}
                            </span>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="dropdown">
                            <iconify-icon icon="iconamoon:menu-kebab-vertical-duotone"></iconify-icon>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('support.index') }}">
                                <iconify-icon icon="iconamoon:arrow-left-duotone" class="me-2"></iconify-icon>Back to Tickets
                            </a></li>
                            @if($ticket->status !== 'closed')
                                @if($ticket->status === 'resolved')
                                <li><a class="dropdown-item text-success" href="#" onclick="reopenTicket()">
                                    <iconify-icon icon="iconamoon:restart-duotone" class="me-2"></iconify-icon>Reopen Ticket
                                </a></li>
                                @endif
                                <li><a class="dropdown-item text-danger" href="#" onclick="closeTicket()">
                                    <iconify-icon icon="iconamoon:close-duotone" class="me-2"></iconify-icon>Close Ticket
                                </a></li>
                            @else
                                <li><a class="dropdown-item text-success" href="#" onclick="reopenTicket()">
                                    <iconify-icon icon="iconamoon:restart-duotone" class="me-2"></iconify-icon>Reopen Ticket
                                </a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4">
                <div class="ticket-description mb-3">
                    {!! nl2br(e($ticket->description)) !!}
                </div>
                
                {{-- Original Attachments --}}
                @if($ticket->attachments)
                <div class="mt-4">
                    <h6 class="mb-3 small fw-semibold">
                        <iconify-icon icon="iconamoon:attachment-duotone" class="me-1"></iconify-icon>
                        Attachments
                    </h6>
                    <div class="row g-2">
                        @foreach($ticket->attachments as $index => $attachment)
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center p-2 border rounded bg-light">
                                <div class="flex-shrink-0 me-2">
                                    <iconify-icon icon="iconamoon:file-duotone" class="fs-5 text-info"></iconify-icon>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate">{{ $attachment['name'] }}</div>
                                    <small class="text-muted">{{ $this->formatFileSize($attachment['size'] ?? 0) }}</small>
                                </div>
                                <a href="{{ route('support.download', [$ticket->id, 'original', $index]) }}" 
                                   class="btn btn-sm btn-outline-primary ms-2 flex-shrink-0">
                                    <iconify-icon icon="iconamoon:cloud-download-duotone"></iconify-icon>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Replies Section --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0 h6 h-md-5">
                    <iconify-icon icon="iconamoon:chat-duotone" class="me-2"></iconify-icon>
                    Conversation
                    <span class="badge bg-secondary-subtle text-secondary ms-2">
                        {{ $ticket->replies->count() }} {{ Str::plural('reply', $ticket->replies->count()) }}
                    </span>
                </h5>
            </div>
            <div class="card-body p-3 p-md-4">
                @if($ticket->replies->count() > 0)
                    <div class="timeline">
                        @foreach($ticket->replies as $reply)
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                @if($reply->user_id === $ticket->user_id)
                                    <div class="timeline-marker-icon bg-primary">
                                        <iconify-icon icon="iconamoon:user-duotone" class="text-white"></iconify-icon>
                                    </div>
                                @else
                                    <div class="timeline-marker-icon bg-success">
                                        <iconify-icon icon="iconamoon:headphones-duotone" class="text-white"></iconify-icon>
                                    </div>
                                @endif
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <strong class="text-truncate">{{ $reply->user->name }}</strong>
                                            @if($reply->user_id !== $ticket->user_id)
                                                <span class="badge bg-success-subtle text-success text-nowrap">Support Staff</span>
                                            @endif
                                        </div>
                                    </div>
                                    <small class="text-muted text-nowrap">
                                        {{ $reply->created_at->format('M d, Y') }}
                                        <span class="d-none d-sm-inline">• {{ $reply->created_at->format('H:i') }}</span>
                                    </small>
                                </div>
                                <div class="timeline-body">
                                    <div class="reply-content">
                                        {!! nl2br(e($reply->message)) !!}
                                    </div>
                                    
                                    {{-- Reply Attachments --}}
                                    @if($reply->attachments)
                                    <div class="mt-3">
                                        <div class="row g-2">
                                            @foreach($reply->attachments as $index => $attachment)
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-center p-2 border rounded bg-light">
                                                    <div class="flex-shrink-0 me-2">
                                                        <iconify-icon icon="iconamoon:file-duotone" class="text-info"></iconify-icon>
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="small fw-semibold text-truncate">{{ $attachment['name'] }}</div>
                                                        <small class="text-muted">{{ $this->formatFileSize($attachment['size'] ?? 0) }}</small>
                                                    </div>
                                                    <a href="{{ route('support.download', [$ticket->id, $reply->id, $index]) }}" 
                                                       class="btn btn-sm btn-outline-primary ms-2 flex-shrink-0">
                                                        <iconify-icon icon="iconamoon:cloud-download-duotone"></iconify-icon>
                                                    </a>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <iconify-icon icon="iconamoon:chat-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                        <h6 class="text-muted">No replies yet</h6>
                        <p class="text-muted small">Our support team will respond to your ticket soon.</p>
                    </div>
                @endif

                {{-- Reply Form --}}
                @if($ticket->status !== 'closed')
                <div class="mt-4 pt-4 border-top">
                    <h6 class="mb-3 small fw-semibold">Add Reply</h6>
                    <form id="replyForm" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="message" class="form-label fw-semibold">
                                Your Message <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" 
                                      id="message" 
                                      name="message" 
                                      rows="4" 
                                      required
                                      placeholder="Type your reply here..." 
                                      minlength="10"></textarea>
                            <div class="invalid-feedback"></div>
                            <div class="form-text small">Minimum 10 characters</div>
                        </div>

                        <div class="mb-3">
                            <label for="replyAttachments" class="form-label fw-semibold">
                                Attachments (Optional)
                            </label>
                            <div class="row g-2">
                                <div class="col-12 col-sm-9">
                                    <input type="file" 
                                           class="form-control" 
                                           id="replyAttachments" 
                                           name="attachments[]" 
                                           multiple 
                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt">
                                </div>
                                <div class="col-12 col-sm-3">
                                    <button type="button" 
                                            class="btn btn-outline-secondary w-100" 
                                            onclick="clearReplyAttachments()">
                                        <span class="d-sm-none">Clear Files</span>
                                        <span class="d-none d-sm-inline">Clear</span>
                                    </button>
                                </div>
                            </div>
                            <div class="invalid-feedback"></div>
                            <div class="form-text small">Maximum 10MB per file. Supported: JPG, PNG, PDF, DOC, DOCX, TXT</div>
                            
                            {{-- File Preview --}}
                            <div id="replyFilePreview" class="mt-3" style="display: none;">
                                <div class="border rounded p-3 bg-light">
                                    <h6 class="mb-2 small fw-semibold">Selected Files:</h6>
                                    <div id="replyFileList"></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary" id="replyBtn">
                                <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                                <span id="replyText">Send Reply</span>
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="mt-4 pt-4 border-top">
                    <div class="alert alert-info d-flex align-items-start">
                        <iconify-icon icon="iconamoon:information-circle-duotone" class="fs-4 me-3 flex-shrink-0 mt-1"></iconify-icon>
                        <div class="flex-grow-1">
                            <strong>This ticket is closed.</strong>
                            <p class="mb-0 mt-1 small">You cannot add new replies to closed tickets. If you need further assistance, please create a new ticket.</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-12 col-lg-4">
        {{-- Ticket Information --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0 h6 h-md-5">
                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                    Ticket Information
                </h5>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Status</span>
                            <span class="badge bg-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }}-subtle text-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'in_progress' ? 'info' : ($ticket->status === 'resolved' ? 'success' : 'secondary')) }}">
                                {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Priority</span>
                            <span class="badge bg-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }}-subtle text-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : ($ticket->priority === 'medium' ? 'info' : 'secondary')) }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Category</span>
                            <span class="badge bg-info-subtle text-info">{{ ucfirst($ticket->category) }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Created</span>
                            <span class="small">{{ $ticket->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Last Updated</span>
                            <span class="small">{{ $ticket->updated_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    @if($ticket->assignedTo)
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Assigned To</span>
                            <span class="fw-semibold small">{{ $ticket->assignedTo->name }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0 h6 h-md-5">
                    <iconify-icon icon="iconamoon:flash-duotone" class="me-2"></iconify-icon>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="{{ route('support.index') }}" class="btn btn-outline-primary btn-sm">
                        <iconify-icon icon="iconamoon:arrow-left-duotone" class="me-1"></iconify-icon>
                        Back to All Tickets
                    </a>
                    <a href="{{ route('support.create') }}" class="btn btn-outline-success btn-sm">
                        <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-1"></iconify-icon>
                        Create New Ticket
                    </a>
                    @if($ticket->status !== 'closed')
                        @if($ticket->status === 'resolved')
                        <button class="btn btn-outline-warning btn-sm" onclick="reopenTicket()">
                            <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                            Reopen Ticket
                        </button>
                        @endif
                        <button class="btn btn-outline-danger btn-sm" onclick="closeTicket()">
                            <iconify-icon icon="iconamoon:close-duotone" class="me-1"></iconify-icon>
                            Close Ticket
                        </button>
                    @else
                        <button class="btn btn-outline-success btn-sm" onclick="reopenTicket()">
                            <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                            Reopen Ticket
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const replyForm = document.getElementById('replyForm');
    const replyAttachmentsInput = document.getElementById('replyAttachments');

    // File upload preview for replies
    if (replyAttachmentsInput) {
        replyAttachmentsInput.addEventListener('change', function() {
            handleReplyFilePreview(this.files);
        });
    }

    // Reply form submission
    if (replyForm) {
        replyForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitReply();
        });
    }

    // Auto-resize textarea
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        messageTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });
    }
});

function handleReplyFilePreview(files) {
    const filePreview = document.getElementById('replyFilePreview');
    const fileList = document.getElementById('replyFileList');
    
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
            <button type="button" class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" onclick="removeReplyFile(${index})">
                <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
            </button>
        `;
        fileList.appendChild(fileItem);
    });
    
    if (totalSize > 50 * 1024 * 1024) {
        showAlert('Total file size exceeds 50MB limit', 'warning');
    }
    
    filePreview.style.display = 'block';
}

function removeReplyFile(index) {
    const attachmentsInput = document.getElementById('replyAttachments');
    const dt = new DataTransfer();
    const files = attachmentsInput.files;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }
    
    attachmentsInput.files = dt.files;
    handleReplyFilePreview(dt.files);
}

function clearReplyAttachments() {
    const attachmentsInput = document.getElementById('replyAttachments');
    const filePreview = document.getElementById('replyFilePreview');
    
    attachmentsInput.value = '';
    filePreview.style.display = 'none';
}

function submitReply() {
    const form = document.getElementById('replyForm');
    const replyBtn = document.getElementById('replyBtn');
    const replyText = document.getElementById('replyText');
    
    // Reset previous validation states
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    
    // Show loading state
    replyBtn.disabled = true;
    replyText.textContent = 'Sending...';
    replyBtn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2"></span>');
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Submit reply
    fetch('{{ route("support.reply", $ticket) }}', {
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
            // Reload page to show new reply
            setTimeout(() => {
                window.location.reload();
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
                showAlert(data.message || 'Failed to send reply', 'danger');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while sending the reply', 'danger');
    })
    .finally(() => {
        // Reset loading state
        replyBtn.disabled = false;
        replyText.textContent = 'Send Reply';
        const spinner = replyBtn.querySelector('.spinner-border');
        if (spinner) spinner.remove();
    });
}

function closeTicket() {
    if (confirm('Are you sure you want to close this ticket? You won\'t be able to add new replies once it\'s closed.')) {
        fetch('{{ route("support.close", $ticket) }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showAlert(data.message || 'Failed to close ticket', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to close ticket', 'danger');
        });
    }
}

function reopenTicket() {
    fetch('{{ route("support.reopen", $ticket) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert(data.message || 'Failed to reopen ticket', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to reopen ticket', 'danger');
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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

/* Timeline styling - Mobile First */
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -1.25rem;
    top: 2rem;
    width: 2px;
    height: calc(100% - 0.5rem);
    background: linear-gradient(to bottom, #e9ecef, transparent);
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
}

.timeline-marker-icon {
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    font-size: 0.8rem;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.timeline-content:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

/* Button improvements */
.btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn:hover:not(.disabled):not(:disabled) {
    transform: translateY(-1px);
}

/* Badge styling */
.badge {
    font-weight: 500;
    white-space: nowrap;
}

.badge.fs-6 {
    font-size: 0.9rem !important;
}

/* Utility classes */
.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.text-nowrap {
    white-space: nowrap;
}

.min-w-0 {
    min-width: 0;
}

/* File preview styling */
#replyFilePreview {
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

/* Mobile-first responsive design */
@media (max-width: 575.98px) {
    /* Card adjustments */
    .card-header {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    /* Typography scaling */
    .card-title {
        font-size: 1rem;
    }
    
    .fs-1 {
        font-size: 2rem !important;
    }
    
    /* Timeline mobile adjustments */
    .timeline {
        padding-left: 1rem;
    }
    
    .timeline-marker {
        left: -1.25rem;
    }
    
    .timeline-marker-icon {
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.7rem;
        border-width: 1px;
    }
    
    .timeline-item:not(:last-child):before {
        left: -0.75rem;
        width: 1px;
    }
    
    .timeline-content {
        padding: 0.75rem;
        border-radius: 8px;
    }
    
    .timeline-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start !important;
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
    
    /* Textarea adjustments */
    #message {
        min-height: 80px;
        max-height: 150px;
    }
    
    /* Button adjustments */
    .btn {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }
    
    .btn-sm {
        padding: 0.4rem 0.75rem;
        font-size: 0.8rem;
    }
    
    /* Badge adjustments */
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .badge.fs-6 {
        font-size: 0.75rem !important;
    }
    
    /* File attachments mobile */
    .bg-light {
        padding: 0.5rem;
    }
    
    .fs-5 {
        font-size: 1rem !important;
    }
    
    /* Sidebar stacking */
    .row.g-3 .col-12 {
        border-bottom: 1px solid #f1f1f1;
        padding-bottom: 0.5rem;
    }
    
    .row.g-3 .col-12:last-child {
        border-bottom: none;
        padding-bottom: 0;
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
    .timeline-content {
        padding: 1rem;
    }
    
    .form-control,
    .form-select {
        font-size: 0.95rem;
    }
    
    #message {
        min-height: 100px;
        max-height: 180px;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    /* Medium tablet adjustments */
    .timeline {
        padding-left: 2rem;
    }
    
    .timeline-marker {
        left: -2rem;
    }
    
    .timeline-marker-icon {
        width: 2.25rem;
        height: 2.25rem;
    }
    
    .timeline-item:not(:last-child):before {
        left: -1.5rem;
    }
}

@media (min-width: 992px) {
    /* Desktop optimizations */
    .timeline {
        padding-left: 2.5rem;
    }
    
    .timeline-marker {
        left: -2.25rem;
    }
    
    .timeline-marker-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-width: 3px;
        font-size: 0.9rem;
    }
    
    .timeline-item:not(:last-child):before {
        left: -1.75rem;
        width: 2px;
    }
    
    .timeline-content {
        padding: 1.25rem;
    }
    
    #message {
        min-height: 120px;
        max-height: 200px;
    }
}

/* Ticket description styling */
.ticket-description, 
.reply-content {
    line-height: 1.6;
    color: #495057;
    word-wrap: break-word;
}

/* Alert styling */
.alert {
    border-radius: 8px;
    border: none;
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Icon consistency */
iconify-icon {
    vertical-align: middle;
}

/* Smooth scrolling behavior */
html {
    scroll-behavior: smooth;
}

/* Focus management */
.form-control:focus,
.form-select:focus,
.btn:focus {
    outline: none;
}

/* Dropdown improvements */
.dropdown-menu {
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    border: none;
}

@media (max-width: 575.98px) {
    .dropdown-menu {
        font-size: 0.85rem;
    }
}

/* Auto-resize textarea */
textarea {
    resize: vertical;
    transition: height 0.2s ease;
}

/* Loading and disabled states */
.btn:disabled {
    opacity: 0.8;
    cursor: not-allowed;
    transform: none;
}
</style>

@php
// Helper function for file size formatting
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 Bytes';
        $k = 1024;
        $sizes = ['Bytes', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}
@endphp
@endsection