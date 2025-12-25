@extends('admin.layouts.vertical', ['title' => 'Notification Details', 'subTitle' => 'Notification Management'])

@section('content')

{{-- Header Section --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">{{ $notification }} Notification</h4>
                        <p class="text-muted mb-0">
                            @if($settings)
                                {{ $settings['description'] }}
                            @else
                                View and manage notification class details
                            @endif
                        </p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary btn-sm">
                            <iconify-icon icon="iconamoon:arrow-left-duotone" class="me-1"></iconify-icon>
                            Back to Notifications
                        </a>
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="showTestModal()">
                            <iconify-icon icon="iconamoon:send-duotone" class="me-1"></iconify-icon>
                            Send Test
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteNotification()">
                            <iconify-icon icon="iconamoon:trash-duotone" class="me-1"></iconify-icon>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Main Content --}}
<div class="row">
    {{-- Notification Details --}}
    <div class="col-lg-8">
        {{-- Configuration --}}
        @if($settings)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:settings-duotone" class="me-2"></iconify-icon>
                    Configuration
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Channels</h6>
                        @foreach($settings['channels'] as $channel)
                            <span class="badge bg-primary me-1 mb-1">{{ ucfirst($channel) }}</span>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Description</h6>
                        <p class="mb-0">{{ $settings['description'] }}</p>
                    </div>
                </div>
                
                @if(isset($settings['settings']))
                <hr>
                <div class="row g-3">
                    @if(in_array('mail', $settings['channels']) && isset($settings['settings']['mail_subject']))
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Email Subject</h6>
                        <p class="mb-0"><code>{{ $settings['settings']['mail_subject'] }}</code></p>
                    </div>
                    @endif
                    
                    @if(in_array('database', $settings['channels']) && isset($settings['settings']['database_title']))
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Database Title</h6>
                        <p class="mb-0"><code>{{ $settings['settings']['database_title'] }}</code></p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Source Code --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:file-code-duotone" class="me-2"></iconify-icon>
                    Source Code
                </h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard()">
                        <iconify-icon icon="iconamoon:copy-duotone" class="me-1"></iconify-icon>
                        Copy
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="downloadFile()">
                        <iconify-icon icon="iconamoon:download-duotone" class="me-1"></iconify-icon>
                        Download
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <pre id="source-code" class="bg-dark text-light p-3 mb-0" style="border-radius: 0; height: 400px; overflow-y: auto;"><code class="language-php">{{ $notification_content }}</code></pre>
            </div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        {{-- Usage Statistics --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:chart-duotone" class="me-2"></iconify-icon>
                    Usage Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <h4 class="text-primary mb-1">{{ $usage_stats['total'] ?? 0 }}</h4>
                            <small class="text-muted">Total Sent</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <h4 class="text-success mb-1">{{ $usage_stats['thisMonth'] ?? 0 }}</h4>
                            <small class="text-muted">This Month</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                            <h4 class="text-warning mb-1">{{ $usage_stats['unread'] ?? 0 }}</h4>
                            <small class="text-muted">Unread Notifications</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- File Information --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:file-duotone" class="me-2"></iconify-icon>
                    File Information
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Class Name</h6>
                    <code>{{ $notification }}</code>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted mb-1">Namespace</h6>
                    <code>App\Notifications\{{ $notification }}</code>
                </div>
                <div class="mb-3">
                    <h6 class="text-muted mb-1">File Path</h6>
                    <code>app/Notifications/{{ $notification }}.php</code>
                </div>
                <div class="mb-0">
                    <h6 class="text-muted mb-1">File Size</h6>
                    <span>{{ number_format(strlen($notification_content)) }} bytes</span>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:lightning-duotone" class="me-2"></iconify-icon>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" onclick="showTestModal()">
                        <iconify-icon icon="iconamoon:send-duotone" class="me-2"></iconify-icon>
                        Send Test Notification
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="viewUsageLogs()">
                        <iconify-icon icon="iconamoon:file-document-duotone" class="me-2"></iconify-icon>
                        View Usage Logs
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="generateUsage()">
                        <iconify-icon icon="iconamoon:file-code-duotone" class="me-2"></iconify-icon>
                        Generate Usage Example
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Test Notification Modal --}}
<div class="modal fade" id="testNotificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Test Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="testNotificationForm">
                <div class="modal-body">
                    <input type="hidden" name="notification_class" value="{{ $notification }}">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recipient Type</label>
                        <select class="form-select" id="recipient_type" name="recipient_type" onchange="toggleRecipientFields()">
                            <option value="user">Existing User</option>
                            <option value="email">Email Address</option>
                        </select>
                    </div>

                    <div class="mb-3" id="user_select_field">
                        <label for="recipient_id" class="form-label fw-semibold">Select User</label>
                        <select class="form-select" id="recipient_id" name="recipient_id">
                            <option value="">Choose a user...</option>
                            {{-- Users will be loaded via AJAX or passed from controller --}}
                        </select>
                    </div>

                    <div class="mb-3" id="email_input_field" style="display: none;">
                        <label for="recipient_email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="recipient_email" name="recipient_email" placeholder="user@example.com">
                    </div>

                    <div class="mb-3">
                        <label for="test_data" class="form-label fw-semibold">Test Data (JSON)</label>
                        <textarea class="form-control" id="test_data" name="test_data" rows="4" placeholder='{"name": "John Doe", "amount": 100}'></textarea>
                        <div class="form-text">Optional: JSON data to pass to the notification</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Test Notification</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Usage Example Modal --}}
<div class="modal fade" id="usageExampleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Usage Example - {{ $notification }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <h6>Basic Usage:</h6>
                    <pre class="bg-dark text-light p-3 rounded"><code>use App\Notifications\{{ $notification }};

// Send to a user
$user = User::find(1);
$user->notify(new {{ $notification }}($data));

// Send to multiple users
$users = User::where('active', true)->get();
Notification::send($users, new {{ $notification }}($data));

// Send to email address
Notification::route('mail', 'user@example.com')
           ->notify(new {{ $notification }}($data));</code></pre>
                </div>
                
                <div class="mb-3">
                    <h6>With Data:</h6>
                    <pre class="bg-dark text-light p-3 rounded"><code>$data = [
    'user_name' => 'John Doe',
    'amount' => 100,
    // Add your custom data here
];

$user->notify(new {{ $notification }}($data));</code></pre>
                </div>

                @if($settings && in_array('database', $settings['channels']))
                <div class="mb-3">
                    <h6>Reading Database Notifications:</h6>
                    <pre class="bg-dark text-light p-3 rounded"><code>// Get unread notifications
$notifications = auth()->user()->unreadNotifications;

// Mark as read
auth()->user()->unreadNotifications->markAsRead();

// Get specific notification type
$notifications = auth()->user()->notifications()
    ->where('type', 'App\\Notifications\\{{ $notification }}')
    ->get();</code></pre>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyUsageExample()">Copy Example</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Test notification modal
function showTestModal() {
    new bootstrap.Modal(document.getElementById('testNotificationModal')).show();
}

// Toggle recipient fields
function toggleRecipientFields() {
    const type = document.getElementById('recipient_type').value;
    const userField = document.getElementById('user_select_field');
    const emailField = document.getElementById('email_input_field');
    
    if (type === 'user') {
        userField.style.display = 'block';
        emailField.style.display = 'none';
    } else {
        userField.style.display = 'none';
        emailField.style.display = 'block';
    }
}

// Send test notification
document.getElementById('testNotificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<div class="spinner-border spinner-border-sm me-1"></div> Sending...';
    submitBtn.disabled = true;
    
    fetch('{{ route("admin.notifications.test") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('testNotificationModal')).hide();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to send test notification', 'danger');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Copy source code to clipboard
function copyToClipboard() {
    const sourceCode = document.getElementById('source-code').textContent;
    navigator.clipboard.writeText(sourceCode).then(() => {
        showAlert('Source code copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Could not copy text: ', err);
        showAlert('Failed to copy to clipboard', 'danger');
    });
}

// Download file
function downloadFile() {
    const sourceCode = document.getElementById('source-code').textContent;
    const blob = new Blob([sourceCode], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = '{{ $notification }}.php';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    showAlert('File downloaded successfully!', 'success');
}

// Delete notification
function deleteNotification() {
    if (confirm('Are you sure you want to delete this notification class? This action cannot be undone.')) {
        fetch('{{ route("admin.notifications.destroy", $notification) }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("admin.notifications.index") }}';
                }, 2000);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to delete notification', 'danger');
        });
    }
}

// View usage logs
function viewUsageLogs() {
    window.location.href = '{{ route("admin.notifications.logs") }}?notification_type={{ $notification }}';
}

// Generate usage example
function generateUsage() {
    new bootstrap.Modal(document.getElementById('usageExampleModal')).show();
}

// Copy usage example
function copyUsageExample() {
    const examples = document.querySelectorAll('#usageExampleModal pre code');
    let allExamples = '';
    examples.forEach(example => {
        allExamples += example.textContent + '\n\n';
    });
    
    navigator.clipboard.writeText(allExamples).then(() => {
        showAlert('Usage examples copied to clipboard!', 'success');
    }).catch(err => {
        console.error('Could not copy text: ', err);
        showAlert('Failed to copy to clipboard', 'danger');
    });
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) alertDiv.remove();
    }, 5000);
}

// Load users for testing (you might want to make this an AJAX call)
document.addEventListener('DOMContentLoaded', function() {
    // This could be populated via AJAX to get recent users
    const userSelect = document.getElementById('recipient_id');
    // Add users here or load via AJAX
});
</script>

{{-- Add syntax highlighting CSS --}}
<style>
.language-php {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 14px;
    line-height: 1.4;
}

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
@endsection