@extends('admin.layouts.vertical', ['title' => 'Create Notification', 'subTitle' => 'Notification Management'])

@section('content')

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1">Create New Notification</h4>
                        <p class="text-muted mb-0">Generate a new Laravel notification class</p>
                    </div>
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                        Back to Notifications
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <form id="createNotificationForm">
            @csrf
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Notification Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="form-text">Class name (e.g., WelcomeUser, OrderConfirmation)</div>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Notification Channels</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="channel_mail" name="channels[]" value="mail" onchange="toggleChannelConfig('mail')">
                                <label class="form-check-label" for="channel_mail">
                                    Email
                                </label>
                                <div class="form-text">Send notifications via email</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="channel_database" name="channels[]" value="database" onchange="toggleChannelConfig('database')">
                                <label class="form-check-label" for="channel_database">
                                    Database
                                </label>
                                <div class="form-text">Store notifications in database</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4" id="email_config" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Email Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="mail_subject" class="form-label">Email Subject <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mail_subject" name="mail_subject">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="mail_greeting" class="form-label">Greeting</label>
                            <input type="text" class="form-control" id="mail_greeting" name="mail_greeting" placeholder="Hello!">
                        </div>
                        
                        <div class="col-12">
                            <label for="mail_content" class="form-label">Email Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="mail_content" name="mail_content" rows="4"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="mail_action_text" class="form-label">Action Button Text</label>
                            <input type="text" class="form-control" id="mail_action_text" name="mail_action_text" placeholder="View Details">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="mail_action_url" class="form-label">Action Button URL</label>
                            <input type="url" class="form-control" id="mail_action_url" name="mail_action_url" placeholder="https://example.com/action">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4" id="database_config" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">Database Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="database_title" class="form-label">Notification Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="database_title" name="database_title">
                        </div>
                        
                        <div class="col-12">
                            <label for="database_message" class="form-label">Notification Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="database_message" name="database_message" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            Create Notification Class
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script>
function toggleChannelConfig(channel) {
    const checkbox = document.getElementById('channel_' + channel);
    const configSection = document.getElementById(channel + '_config');
    
    if (configSection) {
        configSection.style.display = checkbox.checked ? 'block' : 'none';
    }
}

document.getElementById('createNotificationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = 'Creating...';
    submitBtn.disabled = true;
    
    fetch('{{ route("admin.notifications.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '{{ route("admin.notifications.index") }}';
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create notification');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>
@endsection