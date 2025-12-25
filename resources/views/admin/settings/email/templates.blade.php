@extends('admin.layouts.vertical', ['title' => 'Email Templates', 'subTitle' => 'System Management'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h4 class="mb-1">
                            <iconify-icon icon="iconamoon:file-document-duotone" class="me-2"></iconify-icon>
                            Email Templates
                        </h4>
                        <p class="text-muted mb-0">Manage email notification templates</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-warning btn-sm" onclick="seedDefaults()">
                            Seed Defaults
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="showCreateModal()">
                            Create Template
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Category Filter Tabs --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="btn-group-horizontal d-flex flex-wrap gap-2" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary active" onclick="filterCategory('all')" id="filter-all">
                        <iconify-icon icon="iconamoon:category-duotone" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">All</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterCategory('transaction')" id="filter-transaction">
                        <iconify-icon icon="iconamoon:invoice-duotone" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Transaction</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterCategory('investment')" id="filter-investment">
                        <iconify-icon icon="material-symbols-light:bar-chart-4-bars" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Investment</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterCategory('kyc')" id="filter-kyc">
                        <iconify-icon icon="mdi:shield-check-outline" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">KYC</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterCategory('referral')" id="filter-referral">
                        <iconify-icon icon="iconamoon:profile-duotone" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Referral</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterCategory('support')" id="filter-support">
                        <iconify-icon icon="iconamoon:comment-duotone" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Support</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterCategory('account')" id="filter-account">
                        <iconify-icon icon="iconamoon:lock-duotone" style="vertical-align: middle;"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Account</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Templates Grid --}}
<div class="row g-3" id="templatesGrid">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Loading templates...</p>
    </div>
</div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:eye-duotone" class="me-2"></iconify-icon>
                    Template Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:edit-duotone" class="me-2"></iconify-icon>
                    Edit Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_template_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" id="edit_subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Body</label>
                        <textarea class="form-control" id="edit_body" rows="10" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit_is_active">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <iconify-icon icon="iconamoon:sign-plus-duotone" class="me-2"></iconify-icon>
                    Create Template
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" id="create_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" id="create_slug" required>
                        <small class="text-muted">Lowercase, hyphen-separated (e.g., my-template)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="create_category" required>
                            <option value="transaction">Transaction</option>
                            <option value="investment">Investment</option>
                            <option value="kyc">KYC</option>
                            <option value="referral">Referral</option>
                            <option value="support">Support</option>
                            <option value="account">Account</option>
                            <option value="system">System</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" id="create_subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Body</label>
                        <textarea class="form-control" id="create_body" rows="10" required></textarea>
                        <small class="text-muted">Use {variable_name} for dynamic content</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <iconify-icon icon="iconamoon:check-duotone" class="me-1"></iconify-icon>
                        Create Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let allTemplates = [];
let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', function() {
    loadTemplates();
    
    // Create form submit
    document.getElementById('createForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createTemplate();
    });
    
    // Edit form submit
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateTemplate();
    });
});

function loadTemplates() {
    fetch('/admin/email-templates/get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allTemplates = data.templates;
                displayTemplates(allTemplates);
            }
        })
        .catch(error => {
            document.getElementById('templatesGrid').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        <iconify-icon icon="iconamoon:alert-triangle-duotone" class="me-2"></iconify-icon>
                        Failed to load templates
                    </div>
                </div>
            `;
        });
}

function filterCategory(category) {
    currentFilter = category;
    
    // Update active button
    document.querySelectorAll('[id^="filter-"]').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById('filter-' + category).classList.add('active');
    
    // Filter templates
    const filtered = category === 'all' 
        ? allTemplates 
        : allTemplates.filter(t => t.category === category);
    
    displayTemplates(filtered);
}

function displayTemplates(templates) {
    const grid = document.getElementById('templatesGrid');
    
    if (templates.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <iconify-icon icon="iconamoon:file-document-duotone" style="font-size: 4rem; opacity: 0.3;"></iconify-icon>
                <p class="text-muted mt-3">No templates found. Click "Seed Defaults" to create default templates.</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    templates.forEach(template => {
        html += `
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge ${template.is_active ? 'bg-success' : 'bg-secondary'}">
                                Active
                            </span>
                        </div>
                        
                        <h6 class="card-title mb-2">${template.name}</h6>
                        <p class="card-text small flex-grow-1">
                            <strong>Subject:</strong><br>
                            <span class="text-muted">${truncate(template.subject, 60)}</span>
                        </p>
                        
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button class="btn btn-sm btn-outline-primary flex-fill" onclick="previewTemplate(${template.id})" title="Preview">
                                <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                            </button>
                            <button class="btn btn-sm btn-outline-info flex-fill" onclick="sendTest(${template.id})" title="Send Test">
                                <iconify-icon icon="iconamoon:send-duotone"></iconify-icon>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary flex-fill" onclick="editTemplate(${template.id})" title="Edit">
                                <iconify-icon icon="iconamoon:edit-duotone"></iconify-icon>
                            </button>
                            ${!template.is_system ? `
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTemplate(${template.id})" title="Delete">
                                    <iconify-icon icon="iconamoon:trash-duotone"></iconify-icon>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    grid.innerHTML = html;
}

function getCategoryIcon(category) {
    const icons = {
        transaction: '<iconify-icon icon="iconamoon:invoice-duotone"></iconify-icon>',
        investment: '<iconify-icon icon="iconamoon:graph-up-duotone"></iconify-icon>',
        kyc: '<iconify-icon icon="iconamoon:shield-check-duotone"></iconify-icon>',
        referral: '<iconify-icon icon="iconamoon:profile-duotone"></iconify-icon>',
        support: '<iconify-icon icon="iconamoon:comment-duotone"></iconify-icon>',
        account: '<iconify-icon icon="iconamoon:lock-duotone"></iconify-icon>',
        system: '<iconify-icon icon="iconamoon:settings-duotone"></iconify-icon>'
    };
    return icons[category] || '<iconify-icon icon="iconamoon:file-document-duotone"></iconify-icon>';
}

function getCategoryBadgeClass(category) {
    const classes = {
        transaction: 'bg-primary',
        investment: 'bg-success',
        kyc: 'bg-warning',
        referral: 'bg-info',
        support: 'bg-secondary',
        account: 'bg-danger',
        system: 'bg-dark'
    };
    return classes[category] || 'bg-secondary';
}

function truncate(str, length) {
    return str.length > length ? str.substring(0, length) + '...' : str;
}

function seedDefaults() {
    if (confirm('This will create default email templates. Continue?')) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Seeding...';
        btn.disabled = true;
        
        fetch('/admin/email-templates/seed-defaults', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                loadTemplates();
            }
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
}

function previewTemplate(id) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
    
    document.getElementById('previewContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
        </div>
    `;
    
    fetch(`/admin/email-templates/${id}/preview`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('previewContent').innerHTML = `
                    <div class="mb-4">
                        <h6><iconify-icon icon="iconamoon:label-duotone" class="me-2"></iconify-icon>Subject:</h6>
                        <div class="alert alert-info">${data.preview.subject}</div>
                    </div>
                    <div>
                        <h6><iconify-icon icon="iconamoon:file-document-duotone" class="me-2"></iconify-icon>Body:</h6>
                        <div class="border rounded p-3 bg-light" style="white-space: pre-wrap;">${data.preview.body}</div>
                    </div>
                `;
            }
        });
}

function editTemplate(id) {
    const template = allTemplates.find(t => t.id === id);
    if (!template) return;
    
    document.getElementById('edit_template_id').value = template.id;
    document.getElementById('edit_name').value = template.name;
    document.getElementById('edit_subject').value = template.subject;
    document.getElementById('edit_body').value = template.body;
    document.getElementById('edit_is_active').checked = template.is_active;
    
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function updateTemplate() {
    const id = document.getElementById('edit_template_id').value;
    const formData = {
        name: document.getElementById('edit_name').value,
        subject: document.getElementById('edit_subject').value,
        body: document.getElementById('edit_body').value,
        is_active: document.getElementById('edit_is_active').checked
    };
    
    fetch(`/admin/email-templates/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadTemplates();
        }
    });
}

function showCreateModal() {
    document.getElementById('createForm').reset();
    const modal = new bootstrap.Modal(document.getElementById('createModal'));
    modal.show();
}

function createTemplate() {
    const formData = {
        name: document.getElementById('create_name').value,
        slug: document.getElementById('create_slug').value,
        category: document.getElementById('create_category').value,
        subject: document.getElementById('create_subject').value,
        body: document.getElementById('create_body').value,
        is_active: true
    };
    
    fetch('/admin/email-templates', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        showAlert(data.message, data.success ? 'success' : 'danger');
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('createModal')).hide();
            loadTemplates();
        }
    });
}

function sendTest(id) {
    const email = prompt('Enter email address to send test:');
    if (email && email.includes('@')) {
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled = true;
        
        fetch(`/admin/email-templates/${id}/send-test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ test_email: email })
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
        })
        .finally(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        });
    }
}

function deleteTemplate(id) {
    if (confirm('Are you sure you want to delete this template?')) {
        fetch(`/admin/email-templates/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (data.success) {
                loadTemplates();
            }
        });
    }
}

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; max-width: 90vw;';
    alertDiv.innerHTML = `
        <iconify-icon icon="iconamoon:${type === 'success' ? 'check-circle' : 'alert-triangle'}-duotone" class="me-2"></iconify-icon>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<style>
/* Mobile Responsive Styles */
@media (max-width: 576px) {
    .btn-group-horizontal .btn {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .card-title {
        font-size: 0.9rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
}

.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
</style>
@endsection