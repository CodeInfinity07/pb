@extends('layouts.vertical', ['title' => 'Referrals', 'subTitle' => 'Team'])

@section('content')

    <div class="row">
        <div class="col-12">
            <!-- Quick Stats -->
            @if($siteData['infobox_settings']['affiliates_show_stats'])
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title d-flex align-items-center mb-0">
                            <iconify-icon icon="material-symbols:bar-chart-4-bars" class="me-2"></iconify-icon>
                            Quick Stats
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="card border-info border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="mb-0 fw-bold mb-2 text-info">
                                                    {{ $stats['total_referrals'] }}
                                                </h3>
                                                <p class="text-muted" style="margin: 0;">
                                                    {{ $siteData['content']['affiliates']['total_referrals'] }}
                                                </p>
                                            </div>
                                            <div>
                                                <div class="avatar-lg d-inline-block me-1">
                                                    <span class="avatar-title bg-info-subtle text-info rounded-circle">
                                                        <iconify-icon icon="iconamoon:profile-circle-duotone"
                                                            class="fs-32"></iconify-icon>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="mb-0 fw-bold mb-2 text-warning">
                                                    ${{ $stats['today_earnings'] }}
                                                </h3>
                                                <p class="text-muted" style="margin: 0;">
                                                    {{ $siteData['content']['affiliates']['today_commission'] }}
                                                </p>
                                            </div>
                                            <div>
                                                <div class="avatar-lg d-inline-block me-1">
                                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle">
                                                        <iconify-icon icon="solar:dollar-linear" class="fs-32"></iconify-icon>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="mb-0 fw-bold mb-2 text-success">
                                                    ${{ $stats['total_earnings'] }}
                                                </h3>
                                                <p class="text-muted" style="margin: 0;">
                                                    {{ $siteData['content']['affiliates']['total_commission'] }}
                                                </p>
                                            </div>
                                            <div>
                                                <div class="avatar-lg d-inline-block me-1">
                                                    <span class="avatar-title bg-success-subtle text-success rounded-circle">
                                                        <iconify-icon icon="solar:dollar-bold" class="fs-32"></iconify-icon>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Commission Structure -->
            @if($siteData['infobox_settings']['affiliates_show_levels'])
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title d-flex align-items-center mb-0">
                            <iconify-icon icon="material-symbols:trending-up" class="me-2"></iconify-icon>
                            Profit Sharing %
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @php
                                $userLevel = $user->profile->level ?? 'TL-2';
                                $levelNumber = (int) substr($userLevel, 3);
                                $commissionTable = [
                                    2 => [11, 4, 1],
                                    3 => [12, 5, 2],
                                    4 => [14, 6, 4],
                                    5 => [15, 7, 6],
                                    6 => [17, 8, 7],
                                ];
                                $commissions = $commissionTable[$levelNumber] ?? [0, 0, 0];
                            @endphp

                            <div class="col-md-4">
                                <div class="card border-info border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="mb-0 fw-bold mb-2 text-info">
                                                    {{ $commissions[0] }}%
                                                </h3>
                                                <p class="text-muted" style="margin: 0;">
                                                    Tier-1 Commission
                                                </p>
                                            </div>
                                            <div>
                                                <div class="avatar-lg d-inline-block me-1">
                                                    <span class="avatar-title bg-info-subtle text-info rounded-circle">
                                                        <iconify-icon icon="carbon:skill-level-basic"
                                                            class="fs-32"></iconify-icon>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-warning border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="mb-0 fw-bold mb-2 text-warning">
                                                    {{ $commissions[1] }}%
                                                </h3>
                                                <p class="text-muted" style="margin: 0;">
                                                    Tier-2 Commission
                                                </p>
                                            </div>
                                            <div>
                                                <div class="avatar-lg d-inline-block me-1">
                                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle">
                                                        <iconify-icon icon="carbon:skill-level-intermediate"
                                                            class="fs-32"></iconify-icon>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-success border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h3 class="mb-0 fw-bold mb-2 text-success">
                                                    {{ $commissions[2] }}%
                                                </h3>
                                                <p class="text-muted" style="margin: 0;">
                                                    Tier-3 Commission
                                                </p>
                                            </div>
                                            <div>
                                                <div class="avatar-lg d-inline-block me-1">
                                                    <span class="avatar-title bg-success-subtle text-success rounded-circle">
                                                        <iconify-icon icon="carbon:skill-level-advanced"
                                                            class="fs-32"></iconify-icon>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Referral Link -->
            @if($siteData['infobox_settings']['affiliates_show_reflink'])
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title d-flex align-items-center mb-0">
                            <iconify-icon icon="iconamoon:link-duotone" class="me-2"></iconify-icon>
                            {{ $siteData['content']['affiliates']['referral_link'] }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" class="form-control" id="referralLink" value="{{ $user->referral_link }}"
                                readonly>
                            <button class="btn btn-outline-primary" onclick="copyReferralLink()">
                                <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Pending Commissions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="card-title d-flex align-items-center mb-0">
                        <iconify-icon icon="iconamoon:clock-duotone" class="me-2"></iconify-icon>
                        Pending Referral Commissions
                        <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="loadPendingCommissions()">
                            <iconify-icon icon="ci:refresh" id="refreshIcon"></iconify-icon>
                        </button>
                    </h4>
                </div>
                <div class="card-body">
                    <div id="pendingCommissionsContainer">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Level Statistics -->
            @if($siteData['infobox_settings']['affiliates_show_levels_stats'])
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="card-title d-flex align-items-center mb-0">
                            <iconify-icon icon="ion:stats-chart" class="me-2"></iconify-icon>
                            {{ $siteData['content']['affiliates']['level_statistics'] }}
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @foreach($levels as $level)
                                <div class="col-md-4">
                                    <div class="card border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0 fw-semibold">Tier {{ $level['level'] }}</h6>
                                                <span class="badge bg-info-subtle text-info">
                                                    {{ $level['active_users'] }}/{{ $level['users'] }} Active
                                                </span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <div class="text-center">
                                                        <h6 class="mb-1 text-muted small">
                                                            {{ $siteData['content']['affiliates']['total_deposits'] }}</h6>
                                                        <div class="fw-semibold">{{ $level['deposit'] }}</div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-center">
                                                        <h6 class="mb-1 text-muted small">
                                                            {{ $siteData['content']['affiliates']['earnings'] }}</h6>
                                                        <div class="fw-semibold text-success">{{ $level['earning'] }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Referred Users -->
            @if($siteData['infobox_settings']['affiliates_show_affiliates_users'])
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title d-flex align-items-center mb-0">
                            <iconify-icon icon="iconamoon:profile-circle-duotone" class="me-2"></iconify-icon>
                            {{ $siteData['content']['affiliates']['referred_users'] }}
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <div id="referredUsersContainer">
                            <!-- Desktop Table View -->
                            <div class="d-none d-lg-block">
                                <div class="table-responsive">
                                    <table class="table table-borderless table-hover mb-0">
                                        <thead class="bg-light bg-opacity-50">
                                            <tr>
                                                <th class="px-3 py-3">User</th>
                                                <th class="px-3 py-3">Contact</th>
                                                <th class="px-3 py-3">Status</th>
                                                <th class="px-3 py-3">Deposits</th>
                                                <th class="px-3 py-3">Joined</th>
                                                <th class="px-3 py-3">Verification</th>
                                            </tr>
                                        </thead>
                                        <tbody id="usersTableBody">
                                            <!-- Users will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="d-lg-none p-3">
                                <div id="usersMobileContainer">
                                    <!-- Mobile cards will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <!-- Loading state -->
                        <div id="usersLoadingState" class="text-center py-4 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div id="usersEmptyState" class="text-center py-5 d-none">
                            <iconify-icon icon="iconamoon:profile-circle-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">{{ $siteData['content']['affiliates']['no_referrals'] }}</h6>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer border-top" id="paginationContainer" style="display: none;">
                        <nav aria-label="Users pagination">
                            <ul class="pagination pagination-sm justify-content-center mb-0" id="paginationList">
                                <!-- Pagination will be generated here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            @endif
        </div>
    </div>

@endsection

@section('script')
    <script>
        let currentPage = 1;
        let paginationDetails = null;

        document.addEventListener('DOMContentLoaded', function () {
            // Load initial data
            loadPendingCommissions();
            loadUsersForPage(1);
        });

        function copyReferralLink() {
            const referralLink = document.getElementById('referralLink');
            referralLink.select();
            referralLink.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(referralLink.value).then(() => {
                showAlert('{{ $siteData["content"]["affiliates"]["copied_text"] }}', 'success');
            }).catch(() => {
                document.execCommand('copy');
                showAlert('{{ $siteData["content"]["affiliates"]["copied_text"] }}', 'success');
            });
        }

        function loadPendingCommissions() {
            const refreshIcon = document.getElementById('refreshIcon');
            if (refreshIcon) {
                refreshIcon.style.animation = 'spin 1s linear infinite';
            }

            fetch('{{ route("referrals.pending-commissions") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderPendingCommissions(data.data);
                    } else {
                        showError('Failed to load pending commissions');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load pending commissions');
                })
                .finally(() => {
                    if (refreshIcon) {
                        refreshIcon.style.animation = '';
                    }
                });
        }

        function renderPendingCommissions(data) {
            const container = document.getElementById('pendingCommissionsContainer');
            const { commissions, summary } = data;

            if (commissions.length === 0) {
                container.innerHTML = `
                <div class="text-center py-5 text-muted">
                    <iconify-icon icon="iconamoon:clock-duotone" class="fs-1 mb-3"></iconify-icon>
                    <p>No pending commissions at the moment</p>
                </div>
            `;
                return;
            }

            let html = `
            <!-- Summary Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h4 class="mb-0 fw-bold mb-2 text-primary">
                                        $${summary.total_pending_amount.toFixed(2)}
                                    </h4>
                                    <p class="text-muted" style="margin: 0;">
                                        Total Pending Amount
                                    </p>
                                </div>
                                <div>
                                    <div class="avatar-lg d-inline-block me-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <iconify-icon icon="iconamoon:coin-duotone" class="fs-32"></iconify-icon>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commission Details -->
            <div class="row g-3">
        `;

            commissions.forEach(commission => {
                const progressPercentage = Math.min((commission.pending_days / commission.total_wait_days) * 100, 100);

                html += `
                <div class="col-12">
                    <div class="card border">
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <div class="fw-semibold">${commission.referral_name}</div>
                                    <div class="small text-muted">${commission.referral_email}</div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="h5 fw-bold text-success mb-0">$${commission.commission_amount.toFixed(2)}</div>
                                    <div class="small text-muted">Commission</div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-2">
                                    <span>Progress: ${commission.pending_days}/${commission.total_wait_days} days</span>
                                    <span>${commission.remaining_days} days remaining</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" style="width: ${progressPercentage}%"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-muted">Available on: ${formatDate(commission.date_eligible)}</span>
                                <span class="countdown-timer badge ${commission.remaining_days === 0 ? 'bg-success' : 'bg-warning'}" 
                                      data-target="${commission.date_eligible}">
                                    ${commission.remaining_days === 0 ? 'Ready' : `${commission.remaining_days} days`}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            html += `
            </div>

            <!-- Info Note -->
            <div class="card border-info mt-4">
                <div class="card-body bg-info-subtle">
                    <div class="d-flex align-items-start gap-2">
                        <iconify-icon icon="iconamoon:information-circle-duotone" class="text-info mt-1 fs-20"></iconify-icon>
                        <div class="small text-info">
                            <strong>Note:</strong> Referral commissions are held for ${summary.wait_period_days} days after the referral transaction to ensure account stability. Once the waiting period is complete, commissions will be automatically added to your balance.
                        </div>
                    </div>
                </div>
            </div>
        `;

            container.innerHTML = html;
            startCountdownTimers();
        }

        function loadUsersForPage(page) {
            currentPage = page;

            // Show loading state
            document.getElementById('usersLoadingState').classList.remove('d-none');
            document.getElementById('usersEmptyState').classList.add('d-none');

            fetch(`{{ route("referrals.users-page") }}?page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderUsers(data.data);
                    } else {
                        showError(data.message || 'Failed to load users');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to load users');
                })
                .finally(() => {
                    document.getElementById('usersLoadingState').classList.add('d-none');
                });
        }

        function renderUsers(data) {
            const { users, details } = data;
            paginationDetails = details;

            if (users.length === 0) {
                document.getElementById('usersEmptyState').classList.remove('d-none');
                document.getElementById('paginationContainer').style.display = 'none';
                return;
            }

            renderDesktopTable(users);
            renderMobileCards(users);
            renderPagination(details);

            document.getElementById('paginationContainer').style.display = details.totalPages > 1 ? 'block' : 'none';
        }

        function renderDesktopTable(users) {
            const tbody = document.getElementById('usersTableBody');
            let html = '';

            users.forEach(user => {
                html += `
                <tr>
                    <td class="px-3 py-3">
                        <div>
                            <div class="fw-semibold">${user.fullname}</div>
                            <div class="small text-muted">@${user.username}</div>
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        <div class="small">
                            <div>${user.email}</div>
                            ${user.phone ? `<div class="text-muted">${user.phone}</div>` : ''}
                        </div>
                    </td>
                    <td class="px-3 py-3">
                        <span class="badge ${user.status === '1' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${user.status === '1' ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="px-3 py-3 fw-semibold">${user.deposit}</td>
                    <td class="px-3 py-3 small text-muted">${formatDate(user.created_at)}</td>
                    <td class="px-3 py-3">
                        <div class="d-flex gap-2">
                            <iconify-icon icon="iconamoon:email-duotone" 
                                          class="${user.email_verified ? 'text-success' : 'text-danger'}"
                                          title="Email ${user.email_verified ? 'Verified' : 'Not Verified'}"></iconify-icon>
                            <iconify-icon icon="iconamoon:phone-duotone" 
                                          class="${user.phone_verified ? 'text-success' : 'text-danger'}"
                                          title="Phone ${user.phone_verified ? 'Verified' : 'Not Verified'}"></iconify-icon>
                        </div>
                    </td>
                </tr>
            `;
            });

            tbody.innerHTML = html;
        }

        function renderMobileCards(users) {
            const container = document.getElementById('usersMobileContainer');
            let html = '';

            users.forEach(user => {
                html += `
                <div class="card mb-3 border">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="fw-semibold">${user.fullname}</div>
                                <div class="small text-muted">@${user.username}</div>
                            </div>
                            <span class="badge ${user.status === '1' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                                ${user.status === '1' ? 'Active' : 'Inactive'}
                            </span>
                        </div>

                        <div class="row g-2 small mb-3">
                            <div class="col-12">
                                <div class="text-muted">Email</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span>${user.email}</span>
                                    <iconify-icon icon="iconamoon:email-duotone" 
                                                  class="${user.email_verified ? 'text-success' : 'text-danger'}"></iconify-icon>
                                </div>
                            </div>
                            ${user.phone ? `
                            <div class="col-12">
                                <div class="text-muted">Phone</div>
                                <div class="d-flex align-items-center gap-2">
                                    <span>${user.phone}</span>
                                    <iconify-icon icon="iconamoon:phone-duotone" 
                                                  class="${user.phone_verified ? 'text-success' : 'text-danger'}"></iconify-icon>
                                </div>
                            </div>
                            ` : ''}
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small text-muted">Total Deposits</div>
                                <div class="fw-semibold">${user.deposit}</div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted">Joined</div>
                                <div class="small">${formatDate(user.created_at)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            });

            container.innerHTML = html;
        }

        function renderPagination(details) {
            const paginationList = document.getElementById('paginationList');
            let html = '';

            // Previous button
            if (details.currentPage > 1) {
                html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadUsersForPage(${details.currentPage - 1})">
                        <iconify-icon icon="iconamoon:arrow-left-2-duotone"></iconify-icon>
                    </a>
                </li>
            `;
            }

            // Page numbers
            for (let i = 1; i <= details.totalPages; i++) {
                html += `
                <li class="page-item ${i === details.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadUsersForPage(${i})">${i}</a>
                </li>
            `;
            }

            // Next button
            if (details.currentPage < details.totalPages) {
                html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadUsersForPage(${details.currentPage + 1})">
                        <iconify-icon icon="iconamoon:arrow-right-2-duotone"></iconify-icon>
                    </a>
                </li>
            `;
            }

            paginationList.innerHTML = html;
        }

        function formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

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
            }, 4000);
        }

        function showError(message) {
            showAlert(message, 'danger');
        }

        function startCountdownTimers() {
            const timers = document.querySelectorAll('.countdown-timer[data-target]');

            timers.forEach(timer => {
                const targetDate = new Date(timer.dataset.target + 'T23:59:59');

                const updateTimer = () => {
                    const now = new Date();
                    const difference = targetDate.getTime() - now.getTime();

                    if (difference <= 0) {
                        timer.textContent = 'Ready';
                        timer.className = timer.className.replace('bg-warning', 'bg-success');
                        return;
                    }

                    const days = Math.floor(difference / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((difference % (1000 * 60)) / 1000);

                    if (days > 0) {
                        timer.textContent = `${days}d ${hours}h`;
                    } else if (hours > 0) {
                        timer.textContent = `${hours}h ${minutes}m`;
                    } else if (minutes > 0) {
                        timer.textContent = `${minutes}m ${seconds}s`;
                    } else {
                        timer.textContent = `${seconds}s`;
                    }
                };

                updateTimer();
                setInterval(updateTimer, 1000);
            });
        }
    </script>

    <style>
        /* Card hover effects */
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        /* Avatar styling */
        .avatar-lg {
            width: 48px;
            height: 48px;
        }

        .avatar-title {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .fs-32 {
            font-size: 2rem;
        }

        .fs-20 {
            font-size: 1.25rem;
        }

        /* Progress bar styling */
        .progress {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
        }

        .progress-bar {
            transition: width 0.3s ease;
            border-radius: 4px;
        }

        /* Loading animation */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Table improvements */
        .table th {
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 2px solid #dee2e6;
        }

        .table td {
            vertical-align: middle;
        }

        /* Badge improvements */
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        /* Alert positioning */
        .alert.position-fixed {
            z-index: 1050;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .card {
                margin-bottom: 1rem;
            }

            .avatar-lg {
                width: 40px;
                height: 40px;
            }

            .fs-32 {
                font-size: 1.5rem;
            }

            h3.fw-bold {
                font-size: 1.5rem;
            }

            .pagination .page-item .page-link {
                padding: 0.25rem 0.5rem;
                margin: 0 1px;
            }
        }

        /* Icon styling */
        iconify-icon[title] {
            cursor: help;
        }

        /* Card border hover effect */
        .card.border:hover {
            border-color: rgba(var(--bs-primary-rgb), 0.3) !important;
        }
    </style>
@endsection