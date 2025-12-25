{{-- resources/views/admin/support/show.blade.php --}}
@extends('admin.layouts.vertical', ['title' => 'Ticket #'.$ticket->ticket_number, 'subTitle' => 'View and manage support ticket'])

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
                                <h4 class="mb-0 text-dark">Ticket #{{ $ticket->ticket_number }}</h4>
                                @if($ticket->is_overdue)
                                    <span class="badge bg-danger">Overdue</span>
                                @endif
                            </div>
                            <p class="text-muted mb-0">{{ $ticket->subject }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('admin.support.tickets') }}" class="btn btn-outline-secondary btn-sm">
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
        {{-- Ticket Details Sidebar --}}
        <div class="col-lg-4 order-lg-2 mb-4">
            {{-- Ticket Information --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                        Ticket Information
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Status</label>
                        <div>
                            <span class="badge {{ $ticket->status_badge }} status-badge" 
                                  style="cursor: pointer;" onclick="showStatusModal()">
                                {{ $ticket->status_text }}
                            </span>
                        </div>
                    </div>

                    {{-- Priority --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Priority</label>
                        <div>
                            <span class="badge {{ $ticket->priority_badge }} priority-badge" 
                                  style="cursor: pointer;" onclick="showPriorityModal()">
                                {{ $ticket->priority_text }}
                            </span>
                        </div>
                    </div>

                    {{-- Category --}}
                    @if($ticket->category)
                    <div class="mb-3">
                        <label class="form-label small text-muted">Category</label>
                        <div>
                            <span class="badge bg-light text-dark">{{ Str::title($ticket->category) }}</span>
                        </div>
                    </div>
                    @endif

                    {{-- Assignment --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Assigned To</label>
                        <div class="d-flex align-items-center justify-content-between">
                            <div id="assignmentDisplay">
                                @if($ticket->assignedTo)
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title rounded-circle bg-success text-white">
                                                {{ substr($ticket->assignedTo->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <span class="text-success">{{ $ticket->assignedTo->name }}</span>
                                    </div>
                                @else
                                    <span class="text-muted">Unassigned</span>
                                @endif
                            </div>
                            <button class="btn btn-outline-primary btn-xs" onclick="showAssignmentModal()">
                                <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>

                    {{-- Timestamps --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Created</label>
                        <div class="small">{{ $ticket->created_at->format('M j, Y g:i A') }}</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Last Updated</label>
                        <div class="small">
                            {{ $ticket->updated_at->format('M j, Y g:i A') }}
                            @if($ticket->lastReplyBy)
                                <br><span class="text-muted">by {{ $ticket->lastReplyBy->name }}</span>
                            @endif
                        </div>
                    </div>

                    @if($ticket->last_reply_at)
                    <div class="mb-0">
                        <label class="form-label small text-muted">Last Reply</label>
                        <div class="small">{{ $ticket->last_reply_at->diffForHumans() }}</div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- User Information --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                        Customer Information
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-sm me-3">
                            <span class="avatar-title rounded-circle bg-primary text-white">
                                {{ substr($ticket->user->name, 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $ticket->user->name }}</h6>
                            <small class="text-muted">{{ $ticket->user->email }}</small>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label small text-muted">User ID</label>
                        <div class="small">#{{ $ticket->user->id }}</div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small text-muted">Member Since</label>
                        <div class="small">{{ $ticket->user->created_at->format('M j, Y') }}</div>
                    </div>

                    @if($ticket->user->email_verified_at)
                    <div class="mb-2">
                        <label class="form-label small text-muted">Email Status</label>
                        <div><span class="badge bg-success">Verified</span></div>
                    </div>
                    @endif

                    <div class="d-grid mt-3">
                        <a href="{{ route('admin.users.show', $ticket->user) }}" class="btn btn-outline-primary btn-sm">
                            <iconify-icon icon="iconamoon:profile-circle-duotone" class="me-1"></iconify-icon>
                            View User Profile
                        </a>
                    </div>
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
                        @if($ticket->assigned_to !== auth()->id())
                            <button class="btn btn-primary btn-sm" onclick="assignToMe()">
                                <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-1"></iconify-icon>
                                Assign to Me
                            </button>
                        @endif

                        @if($ticket->status !== 'resolved')
                            <button class="btn btn-success btn-sm" onclick="quickStatusUpdate('resolved')">
                                <iconify-icon icon="iconamoon:check-circle-1-duotone" class="me-1"></iconify-icon>
                                Mark Resolved
                            </button>
                        @endif

                        @if($ticket->status !== 'closed')
                            <button class="btn btn-secondary btn-sm" onclick="quickStatusUpdate('closed')">
                                <iconify-icon icon="iconamoon:close-circle-1-duotone" class="me-1"></iconify-icon>
                                Close Ticket
                            </button>
                        @endif

                        @if(in_array($ticket->status, ['resolved', 'closed']))
                            <button class="btn btn-warning btn-sm" onclick="quickStatusUpdate('open')">
                                <iconify-icon icon="iconamoon:restart-duotone" class="me-1"></iconify-icon>
                                Reopen Ticket
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Conversation Thread --}}
        <div class="col-lg-8 order-lg-1">
            {{-- Original Ticket --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm me-3">
                                <span class="avatar-title rounded-circle bg-primary text-white">
                                    {{ substr($ticket->user->name, 0, 1) }}
                                </span>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $ticket->user->name }}</h6>
                                <small class="text-muted">{{ $ticket->created_at->format('M j, Y g:i A') }}</small>
                            </div>
                        </div>
                        <span class="badge bg-primary">Original Request</span>
                    </div>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">{{ $ticket->subject }}</h5>
                    <div class="ticket-content">
                        {!! nl2br(e($ticket->description)) !!}
                    </div>

                    {{-- Original Attachments --}}
                    @if($ticket->attachments)
                        <div class="mt-3">
                            <h6 class="text-muted mb-2">
                                <iconify-icon icon="iconamoon:attachment-duotone" class="me-1"></iconify-icon>
                                Attachments
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($ticket->attachments as $index => $attachment)
                                    <a href="{{ route('admin.support.download-attachment', [$ticket, 'original', $index]) }}" 
                                       class="btn btn-outline-secondary btn-sm">
                                        <iconify-icon icon="iconamoon:file-duotone" class="me-1"></iconify-icon>
                                        {{ $attachment['name'] }}
                                        <small class="text-muted ms-1">({{ number_format($attachment['size'] / 1024, 1) }}KB)</small>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Replies --}}
            @if($ticket->replies->count() > 0)
                <div id="repliesContainer">
                    @foreach($ticket->replies as $reply)
                        <div class="card shadow-sm mb-3 reply-card {{ $reply->is_internal_note ? 'internal-note' : '' }}">
                            <div class="card-header {{ $reply->is_internal_note ? 'bg-warning bg-opacity-10' : '' }}">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title rounded-circle {{ $reply->user->hasStaffPrivileges() ? 'bg-success' : 'bg-primary' }} text-white">
                                                {{ substr($reply->user->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">
                                                {{ $reply->user->name }}
                                                @if($reply->user->hasStaffPrivileges())
                                                    <span class="badge bg-success ms-1">Staff</span>
                                                @endif
                                            </h6>
                                            <small class="text-muted">{{ $reply->created_at->format('M j, Y g:i A') }}</small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($reply->is_internal_note)
                                            <span class="badge bg-warning">Internal Note</span>
                                        @else
                                            <span class="badge {{ $reply->user->hasStaffPrivileges() ? 'bg-success' : 'bg-primary' }}">
                                                {{ $reply->user->hasStaffPrivileges() ? 'Staff Reply' : 'Customer Reply' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="reply-content">
                                    {!! nl2br(e($reply->message)) !!}
                                </div>

                                {{-- Reply Attachments --}}
                                @if($reply->attachments)
                                    <div class="mt-3">
                                        <h6 class="text-muted mb-2">
                                            <iconify-icon icon="iconamoon:attachment-duotone" class="me-1"></iconify-icon>
                                            Attachments
                                        </h6>
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($reply->attachments as $index => $attachment)
                                                <a href="{{ route('admin.support.download-attachment', [$ticket, $reply->id, $index]) }}" 
                                                   class="btn btn-outline-secondary btn-sm">
                                                    <iconify-icon icon="iconamoon:file-duotone" class="me-1"></iconify-icon>
                                                    {{ $attachment['name'] }}
                                                    <small class="text-muted ms-1">({{ number_format($attachment['size'] / 1024, 1) }}KB)</small>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Reply Form --}}
            @if(!in_array($ticket->status, ['closed']))
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <iconify-icon icon="iconamoon:comment-add-duotone" class="me-1"></iconify-icon>
                            Add Reply
                        </h6>
                    </div>
                    <form id="replyForm" enctype="multipart/form-data" novalidate>
                        @csrf
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="message" class="form-label">
                                        Message <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required 
                                              placeholder="Type your reply here..." minlength="10"></textarea>
                                    <div class="invalid-feedback">Please enter your reply (minimum 10 characters).</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Reply Options</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isInternalNote" name="is_internal_note">
                                        <label class="form-check-label" for="isInternalNote">
                                            <iconify-icon icon="iconamoon:eye-off-duotone" class="me-1"></iconify-icon>
                                            Internal Note (Not visible to customer)
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="changeStatus" class="form-label">Change Status (Optional)</label>
                                    <select class="form-select" id="changeStatus" name="change_status">
                                        <option value="">Keep Current Status</option>
                                        <option value="open">Open</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="pending_user">Pending User Response</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="attachments" class="form-label">
                                        <iconify-icon icon="iconamoon:attachment-duotone" class="me-1"></iconify-icon>
                                        Attachments (Optional)
                                    </label>
                                    <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                           accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt" multiple>
                                    <div class="form-text">
                                        Maximum 10MB per file. Supported: JPG, PNG, PDF, DOC, DOCX, TXT
                                    </div>
                                    
                                    {{-- File Preview --}}
                                    <div id="filePreview" class="mt-2" style="display: none;">
                                        <small class="text-muted">Selected files:</small>
                                        <div id="fileList" class="mt-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-text mb-0">
                                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-1"></iconify-icon>
                                    Customer will be notified unless this is marked as an internal note.
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <span class="spinner-border spinner-border-sm me-1 d-none" role="status"></span>
                                    <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                                    <span id="submitText">Send Reply</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <div class="alert alert-info">
                    <iconify-icon icon="iconamoon:information-circle-duotone" class="me-2"></iconify-icon>
                    This ticket is closed. Reopen it to add new replies.
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Status Update Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Ticket Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="newStatus" class="form-label">Select New Status</label>
                    <select class="form-select" id="newStatus">
                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="pending_user" {{ $ticket->status === 'pending_user' ? 'selected' : '' }}>Pending User Response</option>
                        <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">Update Status</button>
            </div>
        </div>
    </div>
</div>

{{-- Priority Update Modal --}}
<div class="modal fade" id="priorityModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Ticket Priority</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="newPriority" class="form-label">Select New Priority</label>
                    <select class="form-select" id="newPriority">
                        <option value="low" {{ $ticket->priority === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ $ticket->priority === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ $ticket->priority === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ $ticket->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updatePriority()">Update Priority</button>
            </div>
        </div>
    </div>
</div>

{{-- Assignment Modal --}}
<div class="modal fade" id="assignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="assignTo" class="form-label">Assign To</label>
                    <select class="form-select" id="assignTo">
                        <option value="">Unassigned</option>
                        @foreach($assignableUsers as $user)
                            <option value="{{ $user->id }}" {{ $ticket->assigned_to == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateAssignment()">Update Assignment</button>
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
    setupFilePreview();
    setupFormValidation();
    scrollToBottom();
});

// File preview functionality
function setupFilePreview() {
    const fileInput = document.getElementById('attachments');
    const filePreview = document.getElementById('filePreview');
    const fileList = document.getElementById('fileList');

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            filePreview.style.display = 'block';
            fileList.innerHTML = '';

            Array.from(this.files).forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'badge bg-secondary me-1 mb-1';
                fileItem.innerHTML = `
                    ${file.name} (${formatFileSize(file.size)})
                    <button type="button" class="btn-close btn-close-white ms-1" onclick="removeFile(${index})" style="font-size: 0.65em;"></button>
                `;
                fileList.appendChild(fileItem);
            });
        } else {
            filePreview.style.display = 'none';
        }
    });
}

function removeFile(index) {
    const fileInput = document.getElementById('attachments');
    const dt = new DataTransfer();
    
    Array.from(fileInput.files).forEach((file, i) => {
        if (i !== index) {
            dt.items.add(file);
        }
    });
    
    fileInput.files = dt.files;
    fileInput.dispatchEvent(new Event('change'));
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form validation
function setupFormValidation() {
    const form = document.getElementById('replyForm');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting || !validateForm(this)) return;
        
        submitReply(this);
    });

    // Real-time validation for file uploads
    const fileInput = document.getElementById('attachments');
    fileInput.addEventListener('change', function() {
        validateFiles(this);
    });
}

function validateForm(form) {
    form.classList.add('was-validated');
    let isValid = form.checkValidity();
    
    // Custom file validation
    const fileInput = document.getElementById('attachments');
    if (!validateFiles(fileInput)) {
        isValid = false;
    }
    
    return isValid;
}

function validateFiles(fileInput) {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    
    let isValid = true;
    
    Array.from(fileInput.files).forEach(file => {
        if (file.size > maxSize) {
            showAlert(`File "${file.name}" is too large. Maximum size is 10MB.`, 'danger');
            isValid = false;
        }
        
        if (!allowedTypes.includes(file.type)) {
            showAlert(`File "${file.name}" has an unsupported format.`, 'danger');
            isValid = false;
        }
    });
    
    if (!isValid) {
        fileInput.classList.add('is-invalid');
    } else {
        fileInput.classList.remove('is-invalid');
    }
    
    return isValid;
}

// Submit reply
function submitReply(form) {
    isSubmitting = true;
    toggleLoading(form, true);
    
    const formData = new FormData(form);
    
    // Handle boolean checkbox properly
    const isInternalNote = document.getElementById('isInternalNote').checked;
    formData.delete('is_internal_note');
    formData.append('is_internal_note', isInternalNote ? '1' : '0');
    
    fetch('{{ route('admin.support.reply', $ticket) }}', {
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
            form.reset();
            form.classList.remove('was-validated');
            document.getElementById('filePreview').style.display = 'none';
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to send reply', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while sending your reply.', 'danger');
    })
    .finally(() => {
        isSubmitting = false;
        toggleLoading(form, false);
    });
}

// Modal functions
function showStatusModal() {
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function showPriorityModal() {
    new bootstrap.Modal(document.getElementById('priorityModal')).show();
}

function showAssignmentModal() {
    new bootstrap.Modal(document.getElementById('assignmentModal')).show();
}

function updateStatus() {
    const newStatus = document.getElementById('newStatus').value;
    
    fetch('{{ route('admin.support.update-status', $ticket) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
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

function updatePriority() {
    const newPriority = document.getElementById('newPriority').value;
    
    fetch('{{ route('admin.support.update-priority', $ticket) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ priority: newPriority })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('priorityModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update priority', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update priority', 'danger');
    });
}

function updateAssignment() {
    const assignTo = document.getElementById('assignTo').value;
    
    fetch('{{ route('admin.support.assign', $ticket) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ assigned_to: assignTo || null })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('assignmentModal')).hide();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update assignment', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to update assignment', 'danger');
    });
}

// Quick actions
function assignToMe() {
    fetch('{{ route('admin.support.assign', $ticket) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ assigned_to: {{ auth()->id() }} })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to assign ticket', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to assign ticket', 'danger');
    });
}

function quickStatusUpdate(status) {
    fetch('{{ route('admin.support.update-status', $ticket) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: status })
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

// Utility functions
function toggleLoading(form, isLoading) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const spinner = submitBtn.querySelector('.spinner-border');
    const submitText = document.getElementById('submitText');
    
    if (isLoading) {
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitText.textContent = 'Sending...';
    } else {
        submitBtn.disabled = false;
        spinner.classList.add('d-none');
        submitText.textContent = 'Send Reply';
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

function scrollToBottom() {
    // Scroll to the reply form for better UX
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        setTimeout(() => {
            replyForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
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

.avatar-xs {
    width: 1.5rem;
    height: 1.5rem;
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
.priority-badge {
    cursor: pointer;
    transition: all 0.2s ease;
}

.status-badge:hover,
.priority-badge:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.reply-card {
    transition: all 0.2s ease;
}

.reply-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.internal-note {
    border-left: 4px solid #ffc107;
}

.internal-note .card-header {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.ticket-content,
.reply-content {
    line-height: 1.6;
    word-wrap: break-word;
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

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.2;
}

.badge {
    position: relative;
}

.btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
}

#alertContainer {
    max-width: 350px;
}

#alertContainer .alert {
    margin-bottom: 0.5rem;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

.was-validated .form-control:valid,
.was-validated .form-select:valid {
    border-color: #198754;
}

.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: #dc3545;
}

/* Custom scrollbar for better UX */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
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