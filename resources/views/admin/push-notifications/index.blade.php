@extends('admin.layouts.vertical', ['title' => 'Push Notifications', 'subTitle' => 'System Management'])

@section('content')

    {{-- Header --}}
    <div class="mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Push Notifications</h4>
                        <p class="text-muted mb-0 small">Send notifications to users</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="showTestModal()">
                            <iconify-icon icon="iconamoon:send-duotone" style="vertical-align: middle;"></iconify-icon>
                            <span class="d-none d-sm-inline ms-1">Test</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="performCleanup()">
                            <iconify-icon icon="iconamoon:trash-duotone" style="vertical-align: middle;"></iconify-icon>
                            <span class="d-none d-sm-inline ms-1">Cleanup</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="iconamoon:notification-duotone" class="fs-3 text-primary me-2"></iconify-icon>
                        <div>
                            <div class="text-muted small">Total</div>
                            <h5 class="mb-0" id="stat-total">{{ number_format($stats['total'] ?? 0) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="iconamoon:check-circle-duotone" class="fs-3 text-success me-2"></iconify-icon>
                        <div>
                            <div class="text-muted small">Active</div>
                            <h5 class="mb-0" id="stat-active">{{ number_format($stats['active'] ?? 0) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="iconamoon:profile-duotone" class="fs-3 text-info me-2"></iconify-icon>
                        <div>
                            <div class="text-muted small">Users</div>
                            <h5 class="mb-0" id="stat-users">{{ number_format($stats['users_with_subscriptions'] ?? 0) }}
                            </h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <iconify-icon icon="iconamoon:chart-duotone" class="fs-3 text-warning me-2"></iconify-icon>
                        <div>
                            <div class="text-muted small">Rate</div>
                            <h5 class="mb-0" id="stat-rate">{{ number_format($stats['activity_rate'] ?? 0, 1) }}%</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="pushTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#send" type="button">
                        <iconify-icon icon="iconamoon:send-duotone" class="d-md-none"></iconify-icon>
                        <span class="d-none d-md-inline">Send</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#analytics" type="button">
                        <iconify-icon icon="iconamoon:chart-duotone" class="d-md-none"></iconify-icon>
                        <span class="d-none d-md-inline">Analytics</span>
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#subscriptions" type="button">
                        <iconify-icon icon="iconamoon:profile-duotone" class="d-md-none"></iconify-icon>
                        <span class="d-none d-md-inline">Subscriptions</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- Send Tab --}}
                <div class="tab-pane fade show active" id="send">
                    <form id="sendForm">
                        @csrf

                        <div class="mb-4">
                            <h6 class="mb-3">Target Audience</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Send To <span class="text-danger">*</span></label>
                                    <select class="form-select" id="target_type" name="target_type" required>
                                        <option value="">Select audience</option>
                                        <option value="all">All Users</option>
                                        <option value="role">By Role</option>
                                        <option value="status">By Status</option>
                                        <option value="kyc">KYC Verified</option>
                                        <option value="active">Recently Active</option>
                                        <option value="recent">Recently Registered</option>
                                        <option value="specific">Specific Users</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="roleFilter" style="display:none;">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="target_role">
                                        <option value="">All Roles</option>
                                        <option value="user">Users</option>
                                        <option value="admin">Admins</option>
                                        <option value="support">Support</option>
                                        <option value="moderator">Moderators</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="statusFilter" style="display:none;">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="target_status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="blocked">Blocked</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="daysFilter" style="display:none;">
                                    <label class="form-label">Days</label>
                                    <input type="number" class="form-control" name="days_active" value="30" min="1"
                                        max="365">
                                </div>

                                <div class="col-12" id="specificUsers" style="display:none;">
                                    <label class="form-label">Search Users</label>
                                    <input type="text" class="form-control" id="userSearch" placeholder="Type to search...">
                                    <div id="searchResults" class="mt-2"></div>
                                    <div id="selectedUsers" class="mt-2"></div>
                                    <input type="hidden" name="user_ids" id="userIds">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="mb-3">Content</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" maxlength="100"
                                        required>
                                    <small class="text-muted"><span id="titleCount">0</span>/100</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">URL</label>
                                    <input type="text" class="form-control" name="url" value="/dashboard">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="body" name="body" rows="3" maxlength="300"
                                        required></textarea>
                                    <small class="text-muted"><span id="bodyCount">0</span>/300</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Icon URL</label>
                                    <input type="text" class="form-control" id="icon" name="icon"
                                        value="/images/icons/192.png">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Badge URL</label>
                                    <input type="text" class="form-control" name="badge" value="/images/icons/72.png">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="mb-3">Preview</h6>
                            <div class="border rounded p-3">
                                <div class="d-flex gap-3">
                                    <img id="previewIcon" src="/images/icons/192.png" class="rounded"
                                        style="width:48px;height:48px;">
                                    <div>
                                        <div class="fw-bold" id="previewTitle">Notification Title</div>
                                        <div class="text-muted small" id="previewBody">Message here...</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <iconify-icon icon="iconamoon:send-duotone"></iconify-icon>
                                Send Notification
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Analytics Tab --}}
                <div class="tab-pane fade" id="analytics">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Browser Distribution</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="browserChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Subscription Status</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Recent Notifications</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Recipients</th>
                                            <th class="d-none d-md-table-cell">Sent By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentNotifications ?? [] as $notif)
                                            <tr>
                                                <td class="small">
                                                    {{ \Carbon\Carbon::parse($notif->created_at)->format('M d, Y') }}</td>
                                                <td>{{ $notif->title }}</td>
                                                <td><span class="badge bg-primary">{{ ucfirst($notif->type) }}</span></td>
                                                <td>{{ $notif->recipients_count ?? 0 }}</td>
                                                <td class="d-none d-md-table-cell small">{{ $notif->first_name }}
                                                    {{ $notif->last_name }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">No notifications sent yet
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Subscriptions Tab --}}
                <div class="tab-pane fade" id="subscriptions">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-muted small">Total Subscriptions</div>
                                    <h4 class="mb-0" id="sub-total">{{ number_format($stats['total'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-muted small">Valid Subscriptions</div>
                                    <h4 class="mb-0 text-success" id="sub-valid">{{ number_format($stats['valid'] ?? 0) }}
                                    </h4>
                                    <small class="text-muted"
                                        id="sub-rate">{{ number_format($stats['validity_rate'] ?? 0, 1) }}%</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-muted small">Expired Subscriptions</div>
                                    <h4 class="mb-0 text-warning" id="sub-expired">
                                        {{ number_format($stats['expired'] ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="mb-3">Browser Breakdown</h6>
                    <div class="row g-3">
                        @foreach($stats['browsers'] ?? [] as $browser => $count)
                            <div class="col-sm-6 col-lg-3">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <iconify-icon
                                                icon="{{ $browser === 'Chrome/Edge' ? 'iconamoon:chrome-duotone' : ($browser === 'Firefox' ? 'iconamoon:firefox-duotone' : 'iconamoon:globe-duotone') }}"
                                                class="fs-3 text-primary me-2"></iconify-icon>
                                            <div>
                                                <div class="fw-semibold">{{ $browser }}</div>
                                                <small class="text-muted">{{ number_format($count) }}</small>
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
    </div>

    {{-- Test Modal --}}
    <div class="modal fade" id="testModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Send Test</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="testForm">
                    <div class="modal-body">
                        <div class="alert alert-info small">Sends to your devices only</div>
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" name="title" value="Test Notification">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="body" rows="3">This is a test message</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm btn-primary">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let selectedUserIds = [];

        // Form submit
        document.getElementById('sendForm').addEventListener('submit', function (e) {
            e.preventDefault();
            sendNotification();
        });

        document.getElementById('testForm').addEventListener('submit', function (e) {
            e.preventDefault();
            sendTest();
        });

        // Character counters
        document.getElementById('title').addEventListener('input', function (e) {
            document.getElementById('titleCount').textContent = e.target.value.length;
            updatePreview();
        });

        document.getElementById('body').addEventListener('input', function (e) {
            document.getElementById('bodyCount').textContent = e.target.value.length;
            updatePreview();
        });

        document.getElementById('icon').addEventListener('input', updatePreview);

        // User search
        let searchTimeout;
        document.getElementById('userSearch')?.addEventListener('input', function (e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => searchUsers(e.target.value), 300);
        });

        // Target type change
        document.getElementById('target_type').addEventListener('change', function () {
            const type = this.value;

            document.getElementById('roleFilter').style.display = 'none';
            document.getElementById('statusFilter').style.display = 'none';
            document.getElementById('daysFilter').style.display = 'none';
            document.getElementById('specificUsers').style.display = 'none';

            if (type === 'role') document.getElementById('roleFilter').style.display = 'block';
            else if (type === 'status') document.getElementById('statusFilter').style.display = 'block';
            else if (type === 'active' || type === 'recent') document.getElementById('daysFilter').style.display = 'block';
            else if (type === 'specific') document.getElementById('specificUsers').style.display = 'block';
        });

        function searchUsers(query) {
            if (query.length < 2) {
                document.getElementById('searchResults').innerHTML = '';
                return;
            }

            fetch(`/admin/push/search-users?search=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('searchResults').innerHTML = data.users.map(u => `
                        <div class="border rounded p-2 mb-2" onclick='selectUser(${u.id}, "${u.name}", ${u.devices})' style="cursor:pointer;">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div>${u.name}</div>
                                    <small class="text-muted">${u.email}</small>
                                </div>
                                <span class="badge bg-primary">${u.devices}</span>
                            </div>
                        </div>
                    `).join('');
                    }
                })
                .catch(err => console.error('Error:', err));
        }

        function selectUser(id, name, devices) {
            if (selectedUserIds.includes(id)) return;

            selectedUserIds.push(id);
            document.getElementById('userIds').value = JSON.stringify(selectedUserIds);

            const badge = document.createElement('span');
            badge.className = 'badge bg-primary me-1 mb-1';
            badge.innerHTML = `${name} (${devices}) <iconify-icon icon="iconamoon:close-duotone" onclick="removeUser(${id}, this)" style="cursor:pointer;"></iconify-icon>`;
            document.getElementById('selectedUsers').appendChild(badge);

            document.getElementById('searchResults').innerHTML = '';
            document.getElementById('userSearch').value = '';
        }

        function removeUser(id, el) {
            selectedUserIds = selectedUserIds.filter(uid => uid !== id);
            document.getElementById('userIds').value = JSON.stringify(selectedUserIds);
            el.parentElement.remove();
        }

        function updatePreview() {
            document.getElementById('previewTitle').textContent = document.getElementById('title').value || 'Notification Title';
            document.getElementById('previewBody').textContent = document.getElementById('body').value || 'Message here...';
            document.getElementById('previewIcon').src = document.getElementById('icon').value || '/images/icons/192.png';
        }

        function sendNotification() {
            const form = document.getElementById('sendForm');
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const html = btn.innerHTML;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            const targetType = document.getElementById('target_type').value;
            const endpoint = targetType === 'specific' && selectedUserIds.length === 1
                ? '/admin/push/send-to-user'
                : '/admin/push/broadcast';

            if (targetType === 'specific' && selectedUserIds.length === 1) {
                formData.append('user_id', selectedUserIds[0]);
            }

            fetch(endpoint, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
                .then(r => r.json())
                .then(data => {
                    showAlert(data.success ? data.message : 'Failed to send', data.success ? 'success' : 'danger');
                    if (data.success) {
                        form.reset();
                        selectedUserIds = [];
                        document.getElementById('selectedUsers').innerHTML = '';
                        updatePreview();
                        loadStats();
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showAlert('Failed to send', 'danger');
                })
                .finally(() => {
                    btn.innerHTML = html;
                    btn.disabled = false;
                });
        }

        function sendTest() {
            const modal = bootstrap.Modal.getInstance(document.getElementById('testModal'));
            const form = document.getElementById('testForm');
            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const html = btn.innerHTML;

            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            btn.disabled = true;

            fetch('/admin/push/send-test', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
                .then(r => r.json())
                .then(data => {
                    modal.hide();
                    showAlert(data.success ? data.message : 'Failed', data.success ? 'success' : 'danger');
                })
                .catch(err => {
                    console.error('Error:', err);
                    showAlert('Failed to send test', 'danger');
                    modal.hide();
                })
                .finally(() => {
                    btn.innerHTML = html;
                    btn.disabled = false;
                });
        }

        function showTestModal() {
            new bootstrap.Modal(document.getElementById('testModal')).show();
        }

        function performCleanup() {
            if (!confirm('Remove expired subscriptions?')) return;

            fetch('/admin/push/cleanup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(r => r.json())
                .then(data => {
                    showAlert(data.success ? `Removed ${data.results.total_removed} subscriptions` : 'Failed', data.success ? 'success' : 'danger');
                    if (data.success) loadStats();
                })
                .catch(err => {
                    console.error('Error:', err);
                    showAlert('Cleanup failed', 'danger');
                });
        }

        function loadStats() {
            fetch('/admin/push/statistics')
                .then(r => r.json())
                .then(data => {
                    console.log('Stats response:', data);
                    if (data.success && data.statistics) {
                        const s = data.statistics;

                        // Update top stats
                        document.getElementById('stat-total').textContent = (s.total_subscriptions || 0).toLocaleString();
                        document.getElementById('stat-active').textContent = (s.subscriptions_today || 0).toLocaleString();
                        document.getElementById('stat-users').textContent = (s.active_users || 0).toLocaleString();

                        // Calculate rate
                        const rate = s.total_subscriptions > 0 ? ((s.active_users / s.total_subscriptions) * 100) : 0;
                        document.getElementById('stat-rate').textContent = rate.toFixed(1) + '%';

                        // Update subscription tab stats
                        document.getElementById('sub-total').textContent = (s.total_subscriptions || 0).toLocaleString();
                        document.getElementById('sub-valid').textContent = (s.subscriptions_this_month || 0).toLocaleString();

                        const validRate = s.total_subscriptions > 0 ? ((s.subscriptions_this_month / s.total_subscriptions) * 100) : 0;
                        document.getElementById('sub-rate').textContent = validRate.toFixed(1) + '%';

                        const expired = (s.total_subscriptions || 0) - (s.subscriptions_this_month || 0);
                        document.getElementById('sub-expired').textContent = expired.toLocaleString();
                    }
                })
                .catch(err => console.error('Error:', err));
        }

        function loadCharts() {
            fetch('/admin/push/browser-distribution')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const dist = data.distribution;
                        const labels = Object.keys(dist);
                        const counts = labels.map(l => dist[l].count);

                        new Chart(document.getElementById('browserChart'), {
                            type: 'doughnut',
                            data: {
                                labels: labels,
                                datasets: [{
                                    data: counts,
                                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d']
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: true
                            }
                        });
                    }
                });

            const stats = @json($stats ?? []);
            new Chart(document.getElementById('statusChart'), {
                type: 'bar',
                data: {
                    labels: ['Total', 'Valid', 'Active', 'Expired'],
                    datasets: [{
                        label: 'Subscriptions',
                        data: [stats.total || 0, stats.valid || 0, stats.active || 0, stats.expired || 0],
                        backgroundColor: ['#0d6efd', '#198754', '#0dcaf0', '#ffc107']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        }

        function showAlert(msg, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.cssText = 'top:20px;right:20px;z-index:9999;max-width:90%;width:300px;';
            alert.innerHTML = `${msg}<button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadStats();
            loadCharts();
            updatePreview();
        });
    </script>

    <style>
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 0.5rem 1rem;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
        }

        @media (max-width: 768px) {
            .nav-tabs .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 1.25rem;
            }
        }
    </style>
@endsection