@extends('admin.layouts.vertical', ['title' => 'Admin Dashboard', 'subTitle' => 'System Overview'])

@section('content')

    {{-- System Health Status --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-{{ $dashboardData['system_health']['status'] === 'healthy' ? 'success' : 'warning' }}">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            @if($dashboardData['system_health']['status'] === 'healthy')
                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <iconify-icon icon="iconamoon:check-circle-duotone" class="text-white fs-5"></iconify-icon>
                                </div>
                            @else
                                <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <iconify-icon icon="iconamoon:warning-duotone" class="text-white fs-5"></iconify-icon>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h5 class="card-title mb-0">System Health</h5>
                            <small class="text-muted">Last checked: {{ now()->format('M d, Y H:i') }}</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge bg-{{ $dashboardData['system_health']['status'] === 'healthy' ? 'success' : 'warning' }} px-3 py-2 d-none d-sm-inline-block">
                            <iconify-icon
                                icon="iconamoon:{{ $dashboardData['system_health']['status'] === 'healthy' ? 'check' : 'warning' }}-duotone"
                                class="me-1"></iconify-icon>
                            {{ ucfirst($dashboardData['system_health']['status']) }}
                        </span>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshDashboard()">
                            <iconify-icon icon="material-symbols:refresh" class="align-text-bottom"></iconify-icon>
                            <span class="d-none d-sm-inline ms-1">Refresh</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    {{-- Health Metrics Grid --}}
                    <div class="row g-3 mb-3">
                        {{-- Database Status --}}
                        <div class="col-6 col-md-3">
                            <div
                                class="d-flex align-items-center p-2 rounded {{ $dashboardData['system_health']['database']['status'] === 'connected' ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                <div class="me-2">
                                    <iconify-icon icon="material-symbols:database"
                                        class="fs-5 {{ $dashboardData['system_health']['database']['status'] === 'connected' ? 'text-success' : 'text-danger' }}"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">Database</div>
                                    <small
                                        class="text-muted">{{ ucfirst($dashboardData['system_health']['database']['status']) }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Disk Usage --}}
                        <div class="col-6 col-md-3">
                            <div
                                class="d-flex align-items-center p-2 rounded {{ $dashboardData['system_health']['disk_usage'] > 80 ? 'bg-danger-subtle' : ($dashboardData['system_health']['disk_usage'] > 60 ? 'bg-warning-subtle' : 'bg-success-subtle') }}">
                                <div class="me-2">
                                    <iconify-icon icon="material-symbols:hard-drive-outline"
                                        class="fs-5 {{ $dashboardData['system_health']['disk_usage'] > 80 ? 'text-danger' : ($dashboardData['system_health']['disk_usage'] > 60 ? 'text-warning' : 'text-success') }}"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $dashboardData['system_health']['disk_usage'] }}%
                                    </div>
                                    <small class="text-muted">Disk Usage</small>
                                </div>
                            </div>
                        </div>

                        {{-- Cache Status --}}
                        <div class="col-6 col-md-3">
                            <div
                                class="d-flex align-items-center p-2 rounded {{ $dashboardData['system_health']['cache']['status'] === 'active' ? 'bg-success-subtle' : 'bg-warning-subtle' }}">
                                <div class="me-2">
                                    <iconify-icon icon="material-symbols:flash-on"
                                        class="fs-5 {{ $dashboardData['system_health']['cache']['status'] === 'active' ? 'text-success' : 'text-warning' }}"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">Cache</div>
                                    <small
                                        class="text-muted">{{ ucfirst($dashboardData['system_health']['cache']['status']) }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- System Uptime --}}
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-2 rounded bg-info-subtle">
                                <div class="me-2">
                                    <iconify-icon icon="iconamoon:clock-duotone" class="fs-5 text-info"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $dashboardData['system_health']['uptime'] }}</div>
                                    <small class="text-muted">Uptime</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Additional Metrics Row --}}
                    <div class="row g-3">
                        {{-- Online Users --}}
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-2 rounded bg-primary-subtle">
                                <div class="me-2">
                                    <iconify-icon icon="iconamoon:profile-duotone" class="fs-5 text-primary"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $dashboardData['quick_stats']['online_users'] }}</div>
                                    <small class="text-muted">Online Now</small>
                                </div>
                            </div>
                        </div>

                        {{-- Pending KYC --}}
                        <div class="col-6 col-md-3">
                            <div
                                class="d-flex align-items-center p-2 rounded {{ $dashboardData['quick_stats']['pending_kyc'] > 0 ? 'bg-warning-subtle' : 'bg-success-subtle' }}">
                                <div class="me-2">
                                    <iconify-icon icon="material-symbols:document-search-sharp"
                                        class="fs-5 {{ $dashboardData['quick_stats']['pending_kyc'] > 0 ? 'text-warning' : 'text-success' }}"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $dashboardData['quick_stats']['pending_kyc'] }}</div>
                                    <small class="text-muted">Pending KYC</small>
                                </div>
                            </div>
                        </div>

                        {{-- Memory Usage --}}
                        <div class="col-6 col-md-3">
                            <div class="d-flex align-items-center p-2 rounded bg-info-subtle">
                                <div class="me-2">
                                    <iconify-icon icon="mdi:cpu-64-bit" class="fs-5 text-info"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">
                                        {{ $dashboardData['system_health']['memory_usage']['current'] }}
                                    </div>
                                    <small class="text-muted">Memory</small>
                                </div>
                            </div>
                        </div>

                        {{-- Queue Jobs --}}
                        <div class="col-6 col-md-3">
                            <div
                                class="d-flex align-items-center p-2 rounded {{ $dashboardData['system_health']['queue']['pending_jobs'] > 0 ? 'bg-warning-subtle' : 'bg-success-subtle' }}">
                                <div class="me-2">
                                    <iconify-icon icon="material-symbols:checklist-rtl"
                                        class="fs-5 {{ $dashboardData['system_health']['queue']['pending_jobs'] > 0 ? 'text-warning' : 'text-success' }}"></iconify-icon>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">
                                        {{ $dashboardData['system_health']['queue']['pending_jobs'] }}
                                    </div>
                                    <small class="text-muted">Queue Jobs</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Health Status Details --}}
                    @if($dashboardData['system_health']['status'] !== 'healthy')
                        <div class="mt-3 pt-3 border-top">
                            <div class="alert alert-warning d-flex align-items-center mb-0">
                                <iconify-icon icon="iconamoon:information-circle-duotone" class="fs-5 me-2"></iconify-icon>
                                <div>
                                    <strong>System Issues Detected:</strong>
                                    @if($dashboardData['system_health']['disk_usage'] > 80)
                                        High disk usage ({{ $dashboardData['system_health']['disk_usage'] }}%).
                                    @endif
                                    @if($dashboardData['system_health']['database']['status'] !== 'connected')
                                        Database connection issues.
                                    @endif
                                    <a href="{{ route('admin.system.health') }}" class="alert-link">View Details</a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main Statistics Cards --}}
    <div class="row mb-4">
        {{-- Users Overview --}}
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <iconify-icon icon="iconamoon:profile-duotone" class="fs-1 text-primary mb-2"></iconify-icon>
                    <h4 class="mb-1">{{ number_format($dashboardData['user_stats']['total']) }}</h4>
                    <h6 class="text-muted mb-3">Total Users</h6>
                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="small">
                                <div class="fw-semibold text-success">{{ $dashboardData['user_stats']['active'] }}</div>
                                <small class="text-muted">Active</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="small">
                                <div class="fw-semibold text-warning">{{ $dashboardData['user_stats']['inactive'] }}</div>
                                <small class="text-muted">Inactive</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="small">
                                <div class="fw-semibold text-danger">{{ $dashboardData['user_stats']['blocked'] }}</div>
                                <small class="text-muted">Blocked</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial Overview --}}
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <iconify-icon icon="material-symbols:account-balance-wallet"
                        class="fs-1 text-success mb-2"></iconify-icon>
                    <h4 class="mb-1">
                        ${{ number_format($dashboardData['financial_summary']['platform']['profit'], 2) }}</h4>
                    <h6 class="text-muted mb-3">Platform Profit</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-center">
                                <div class="fw-semibold text-success">
                                    ${{ number_format($dashboardData['financial_summary']['platform']['available_balance'], 2) }}
                                </div>
                                <small class="text-muted">Balances</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-center">
                                <div class="fw-semibold text-warning">
                                    ${{ number_format($dashboardData['financial_summary']['platform']['locked_balance'], 2) }}
                                </div>
                                <small class="text-muted">Wallet</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Deposits Overview --}}
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <iconify-icon icon="iconamoon:arrow-down-2-duotone" class="fs-1 text-info mb-2"></iconify-icon>
                    <h4 class="mb-1">${{ number_format($dashboardData['financial_summary']['deposits']['total'], 2) }}</h4>
                    <h6 class="text-muted mb-3">Total Deposits</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-center">
                                <div class="fw-semibold text-info">
                                    ${{ number_format($dashboardData['financial_summary']['deposits']['monthly'], 2) }}
                                </div>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-center">
                                <div class="fw-semibold text-success">
                                    ${{ number_format($dashboardData['financial_summary']['deposits']['today'], 2) }}</div>
                                <small class="text-muted">Today</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Withdrawals Overview --}}
        <div class="col-sm-6 col-lg-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="fs-1 text-warning mb-2"></iconify-icon>
                    <h4 class="mb-1">${{ number_format($dashboardData['financial_summary']['withdrawals']['total'], 2) }}
                    </h4>
                    <h6 class="text-muted mb-3">Total Withdrawals</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="small text-center">
                                <div class="fw-semibold text-warning">
                                    ${{ number_format($dashboardData['financial_summary']['withdrawals']['pending'], 2) }}
                                </div>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-center">
                                <div class="fw-semibold text-danger">
                                    {{ $dashboardData['financial_summary']['withdrawals']['pending_count'] }}
                                </div>
                                <small class="text-muted">Queue</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction Flow Chart Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="card-title mb-0">Transaction Flow</h5>
                        <small class="text-muted">Daily transaction volume by type (Last 30 days)</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn active" data-period="7d">7
                            Days</button>
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="30d">30
                            Days</button>
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="90d">90
                            Days</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="transactionFlowChart" style="height: 400px;"></div>

                    {{-- Transaction Summary Cards Below Chart --}}
                    <div class="row g-3 mt-3 pt-3 border-top">
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="text-center p-3 bg-success-subtle rounded">
                                <div class="fw-bold text-success mb-1">
                                    ${{ number_format($dashboardData['financial_summary']['deposits']['total'], 0) }}</div>
                                <small class="text-muted d-block">Deposits</small>
                                <span
                                    class="badge bg-success-subtle text-success mt-1">{{ $dashboardData['financial_summary']['deposits']['count'] }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="text-center p-3 bg-warning-subtle rounded">
                                <div class="fw-bold text-warning mb-1">
                                    ${{ number_format($dashboardData['financial_summary']['withdrawals']['total'], 0) }}
                                </div>
                                <small class="text-muted d-block">Withdrawals</small>
                                <span
                                    class="badge bg-warning-subtle text-warning mt-1">{{ $dashboardData['financial_summary']['withdrawals']['count'] }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="text-center p-3 bg-primary-subtle rounded">
                                <div class="fw-bold text-primary mb-1">
                                    ${{ number_format($dashboardData['financial_summary']['commissions']['total'], 0) }}
                                </div>
                                <small class="text-muted d-block">Commissions</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="text-center p-3 bg-info-subtle rounded">
                                <div class="fw-bold text-info mb-1">
                                    ${{ number_format($dashboardData['financial_summary']['roi']['total'], 0) }}</div>
                                <small class="text-muted d-block">ROI</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="text-center p-3 bg-secondary-subtle rounded">
                                <div class="fw-bold text-secondary mb-1">
                                    ${{ number_format($dashboardData['financial_summary']['bonus']['total'], 0) }}</div>
                                <small class="text-muted d-block">Bonus</small>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4 col-lg-2">
                            <div class="text-center p-3 bg-dark-subtle rounded">
                                <div class="fw-bold text-dark mb-1">
                                    ${{ number_format($dashboardData['financial_summary']['investments']['total'], 0) }}
                                </div>
                                <small class="text-muted d-block">Investments</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Registration & Performance Row --}}
    <div class="row mb-4">
        {{-- User Registrations --}}
        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">User Registrations</h5>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">View</a>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-primary mb-1">{{ $dashboardData['user_stats']['registrations']['today'] }}
                                </h5>
                                <small class="text-muted">Today</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-primary mb-1">
                                    {{ $dashboardData['user_stats']['registrations']['this_week'] }}
                                </h5>
                                <small class="text-muted">This Week</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-primary mb-1">
                                    {{ $dashboardData['user_stats']['registrations']['this_month'] }}
                                </h5>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <h6 class="mb-2">Activity Stats</h6>
                        <div class="row g-2 small">
                            <div class="col-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Verified:</span>
                                    <span class="fw-semibold">{{ $dashboardData['user_stats']['verified'] }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">KYC:</span>
                                    <span class="fw-semibold">{{ $dashboardData['user_stats']['kyc_verified'] }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Today Logins:</span>
                                    <span
                                        class="fw-semibold">{{ $dashboardData['user_stats']['activity']['today_logins'] }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Online:</span>
                                    <span
                                        class="fw-semibold text-success">{{ $dashboardData['user_stats']['activity']['online_now'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Performance Metrics --}}
        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Performance Metrics</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="exportDashboardData()">
                        <iconify-icon icon="iconamoon:download-duotone" class="align-text-bottom"></iconify-icon>
                        <span class="d-none d-sm-inline ms-1">Export</span>
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">User Growth Rate</span>
                            <span
                                class="fw-semibold {{ $dashboardData['performance_metrics']['user_growth']['percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $dashboardData['performance_metrics']['user_growth']['percentage'] >= 0 ? '+' : '' }}{{ number_format($dashboardData['performance_metrics']['user_growth']['percentage'], 1) }}%
                            </span>
                        </div>
                        <div class="progress mb-2" style="height: 4px;">
                            <div class="progress-bar bg-primary"
                                style="width: {{ min(abs($dashboardData['performance_metrics']['user_growth']['percentage']), 100) }}%">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted">Deposit Growth</span>
                            <span
                                class="fw-semibold {{ $dashboardData['performance_metrics']['deposit_growth']['percentage'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $dashboardData['performance_metrics']['deposit_growth']['percentage'] >= 0 ? '+' : '' }}{{ number_format($dashboardData['performance_metrics']['deposit_growth']['percentage'], 1) }}%
                            </span>
                        </div>
                        <div class="progress mb-2" style="height: 4px;">
                            <div class="progress-bar bg-success"
                                style="width: {{ min(abs($dashboardData['performance_metrics']['deposit_growth']['percentage']), 100) }}%">
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="text-center p-2 bg-primary-subtle rounded">
                                <div class="fw-semibold">
                                    {{ number_format($dashboardData['performance_metrics']['conversion_rate'], 1) }}%
                                </div>
                                <small class="text-muted">Conversion</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 bg-info-subtle rounded">
                                <div class="fw-semibold">
                                    {{ number_format($dashboardData['performance_metrics']['retention_rate'], 1) }}%
                                </div>
                                <small class="text-muted">Retention</small>
                            </div>
                        </div>
                    </div>

                    <div class="text-center p-3 bg-success-subtle rounded">
                        <h6 class="text-success mb-0">
                            ${{ number_format($dashboardData['financial_summary']['platform']['profit'], 2) }}</h6>
                        <small class="text-muted">Platform Profit</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions Table Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6 mb-2 mb-lg-0">
                            <h5 class="card-title mb-0">Recent Transactions</h5>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-md-4 col-sm-6">
                                    <select class="form-select form-select-sm"
                                        onchange="filterDashboardTransactions('status', this.value)"
                                        id="transactionStatusFilter">
                                        <option value="">All Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="processing">Processing</option>
                                        <option value="completed">Completed</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-sm-6">
                                    <select class="form-select form-select-sm"
                                        onchange="filterDashboardTransactions('per_page', this.value)"
                                        id="transactionPerPageFilter">
                                        <option value="15">15 per page</option>
                                        <option value="25">25 per page</option>
                                        <option value="50">50 per page</option>
                                        <option value="100">100 per page</option>
                                    </select>
                                </div>
                                <div class="col-md-4 col-12">
                                    <div class="d-flex gap-1">
                                        <input type="text" id="dashboardDateRange"
                                            class="form-control form-control-sm flex-grow-1" placeholder="Date range">
                                        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
                                            id="clearDashboardDateFilter" style="display: none;">
                                            <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Transaction Type Filter Buttons --}}
                <div class="card-body border-bottom py-3">
                    <div class="d-flex flex-wrap gap-2" id="transactionTypeButtons">
                        <button type="button" class="btn btn-sm btn-outline-secondary transaction-type-btn active"
                            data-type="" onclick="filterTransactionTypeCustom('')">
                            All Types
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success transaction-type-btn"
                            data-type="deposit" onclick="filterTransactionTypeCustom('deposit')">
                            Deposits
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning transaction-type-btn"
                            data-type="withdrawal" onclick="filterTransactionTypeCustom('withdrawal')">
                            Withdrawals
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary transaction-type-btn"
                            data-type="commission" onclick="filterTransactionTypeCustom('commission')">
                            Commissions
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info transaction-type-btn" data-type="roi"
                            onclick="filterTransactionTypeCustom('roi')">
                            ROI
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary transaction-type-btn"
                            data-type="bonus" onclick="filterTransactionTypeCustom('bonus')">
                            Bonus
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger transaction-type-btn"
                            data-type="investment" onclick="filterTransactionTypeCustom('investment')">
                            Investments
                        </button>
                    </div>
                </div>

                @if(isset($dashboardData['recent_activity']['transactions']) && count($dashboardData['recent_activity']['transactions']) > 0)
                    <div class="card-body p-0">
                        <div class="d-none d-lg-block">
                            <div class="table-responsive table-card">
                                <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50 thead-sm">
                                        <tr>
                                            <th scope="col">User & Transaction ID</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Sponsor Chain</th> {{-- ADD THIS --}}

                                            <th scope="col">Timestamp</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionsTableBody">
                                        @foreach($dashboardData['recent_activity']['transactions'] as $transaction)
                                            {{-- your existing transaction rows --}}
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Pagination Container --}}
                    <div id="transactionsPaginationContainer"></div>

                @else
                    <div class="card-body">
                        <div class="text-center py-5">
                            <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                            <h6 class="text-muted">No Recent Transactions</h6>
                            <p class="text-muted">Transactions will appear here once users start making deposits and
                                withdrawals.</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Users Management Section --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-lg-6 mb-2 mb-lg-0">
                            <h5 class="card-title mb-0">User Management</h5>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-2">
                                <div class="col-md-3 col-sm-6">
                                    <input type="text" class="form-control form-control-sm" id="userSearchInput"
                                        placeholder="Search users...">
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <select class="form-select form-select-sm"
                                        onchange="filterDashboardUsers('investment_status', this.value)"
                                        id="userInvestmentFilter">
                                        <option value="">All Users</option>
                                        <option value="has_investments">Has Investments</option>
                                        <option value="no_investments">No Investments</option>
                                        <option value="active_investments">Active Investments</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <select class="form-select form-select-sm"
                                        onchange="filterDashboardUsers('verification', this.value)"
                                        id="userVerificationFilter">
                                        <option value="">All Verification</option>
                                        <option value="email_verified">Email Verified</option>
                                        <option value="kyc_verified">KYC Verified</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="d-flex gap-1">
                                        <input type="text" id="userDateRange"
                                            class="form-control form-control-sm flex-grow-1"
                                            placeholder="Registration date">
                                        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0"
                                            id="clearUserDateFilter" style="display: none;">
                                            <iconify-icon icon="iconamoon:close-duotone"></iconify-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Status Filter Buttons --}}
                <div class="card-body border-bottom py-3">
                    <div class="d-flex flex-wrap gap-2" id="userStatusButtons">
                        <button type="button" class="btn btn-sm btn-outline-secondary user-status-btn active" data-status=""
                            onclick="filterUserStatus('')">
                            All Status
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success user-status-btn" data-status="active"
                            onclick="filterUserStatus('active')">
                            Active
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning user-status-btn" data-status="inactive"
                            onclick="filterUserStatus('inactive')">
                            Inactive
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger user-status-btn" data-status="blocked"
                            onclick="filterUserStatus('blocked')">
                            Blocked
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="d-none d-lg-block">
                        <div class="table-responsive table-card">
                            <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                                <thead class="bg-light bg-opacity-50 thead-sm">
                                    <tr>
                                        <th scope="col">User</th>
                                        <th scope="col">Investments</th>
                                        <th scope="col">Wallet Balance</th>
                                        <th scope="col">Sponsor Chain</th>
                                        <th scope="col">Joined</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    <!-- Users will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Pagination Container --}}
                <div id="usersPaginationContainer"></div>
            </div>
        </div>
    </div>

    {{-- Recent Activity Section --}}
    <div class="row">
        {{-- Recent Users and Logins --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    {{-- Activity Tabs --}}
                    <ul class="nav nav-pills nav-sm mb-3" id="activityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="users-tab" data-bs-toggle="pill" data-bs-target="#users"
                                type="button" role="tab">
                                New Users
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="logins-tab" data-bs-toggle="pill" data-bs-target="#logins"
                                type="button" role="tab">
                                Recent Logins
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="activityTabContent">
                        {{-- Recent Users --}}
                        <div class="tab-pane fade show active" id="users" role="tabpanel">
                            @if(isset($dashboardData['recent_activity']['users']) && count($dashboardData['recent_activity']['users']) > 0)
                                @foreach($dashboardData['recent_activity']['users'] as $recentUser)
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                                                <span class="avatar-title text-white">{{ $recentUser->initials }}</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $recentUser->full_name }}</div>
                                                <small class="text-muted">{{ $recentUser->email }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-{{ $recentUser->status === 'active' ? 'success' : 'warning' }}">
                                                {{ ucfirst($recentUser->status) }}
                                            </span>
                                            <div class="small text-muted">{{ $recentUser->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <iconify-icon icon="iconamoon:profile-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                                    <p class="text-muted mb-0">No recent user registrations</p>
                                </div>
                            @endif
                        </div>

                        {{-- Recent Logins --}}
                        <div class="tab-pane fade" id="logins" role="tabpanel">
                            @if(isset($dashboardData['recent_activity']['logins']) && count($dashboardData['recent_activity']['logins']) > 0)
                                @foreach($dashboardData['recent_activity']['logins'] as $recentUser)
                                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm rounded-circle bg-success me-3">
                                                <span class="avatar-title text-white">{{ $recentUser->initials }}</span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $recentUser->full_name }}</div>
                                                <small class="text-muted">{{ $recentUser->last_login_ip ?? 'Unknown IP' }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success">Online</span>
                                            <div class="small text-muted">
                                                {{ $recentUser->last_login_at ? $recentUser->last_login_at->diffForHumans() : 'Never' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-4">
                                    <iconify-icon icon="material-symbols-light:login"
                                        class="fs-1 text-muted mb-3"></iconify-icon>
                                    <p class="text-muted mb-0">No recent logins</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction Details Modal --}}
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    {{-- Include ApexCharts --}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>

    <script>
        let autoRefresh = true;
        let refreshInterval;
        let transactionChart;
        let currentPeriod = '30d';
        let dashboardDatePicker;

        // Users Management Variables
        let userDatePicker;
        let currentUserFilters = {
            investment_status: '',
            verification: '',
            status: '',
            search: '',
            per_page: 15,
            page: 1,
            start_date: '',
            end_date: ''
        };

        // Initialize User Date Picker
        document.addEventListener('DOMContentLoaded', function () {
            userDatePicker = flatpickr("#userDateRange", {
                mode: "range",
                dateFormat: "Y-m-d",
                maxDate: "today",
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        currentUserFilters.start_date = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                        currentUserFilters.end_date = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                        currentUserFilters.page = 1;
                        loadDashboardUsers();
                        document.getElementById('clearUserDateFilter').style.display = 'inline-block';
                    }
                }
            });

            // Clear date filter button
            document.getElementById('clearUserDateFilter').addEventListener('click', function () {
                userDatePicker.clear();
                currentUserFilters.start_date = '';
                currentUserFilters.end_date = '';
                currentUserFilters.page = 1;
                this.style.display = 'none';
                loadDashboardUsers();
            });

            // Search input with debounce
            let searchTimeout;
            document.getElementById('userSearchInput').addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentUserFilters.search = this.value;
                    currentUserFilters.page = 1;
                    loadDashboardUsers();
                }, 500);
            });

            // Load initial users data
            if (document.getElementById('usersTableBody')) {
                loadDashboardUsers();
            }
        });

        // Filter users by dropdown
        function filterDashboardUsers(filterType, value) {
            currentUserFilters[filterType] = value;
            currentUserFilters.page = 1;
            loadDashboardUsers();
        }

        // Filter by status button
        function filterUserStatus(status) {
            // Update button states
            const buttons = document.querySelectorAll('.user-status-btn');
            buttons.forEach(btn => {
                if (btn.getAttribute('data-status') === status) {
                    btn.classList.remove('btn-outline-secondary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-danger');
                    btn.classList.add('active');

                    if (status === 'active') btn.className = 'btn btn-sm btn-success user-status-btn active';
                    else if (status === 'inactive') btn.className = 'btn btn-sm btn-warning user-status-btn active';
                    else if (status === 'blocked') btn.className = 'btn btn-sm btn-danger user-status-btn active';
                    else btn.className = 'btn btn-sm btn-secondary user-status-btn active';
                } else {
                    btn.classList.remove('active', 'btn-success', 'btn-warning', 'btn-danger', 'btn-secondary');

                    const btnStatus = btn.getAttribute('data-status');
                    if (btnStatus === 'active') btn.className = 'btn btn-sm btn-outline-success user-status-btn';
                    else if (btnStatus === 'inactive') btn.className = 'btn btn-sm btn-outline-warning user-status-btn';
                    else if (btnStatus === 'blocked') btn.className = 'btn btn-sm btn-outline-danger user-status-btn';
                    else btn.className = 'btn btn-sm btn-outline-secondary user-status-btn';
                }
            });

            filterDashboardUsers('status', status);
        }

        // Load users page
        function loadDashboardUsersPage(page) {
            currentUserFilters.page = page;
            loadDashboardUsers();
        }

        // Main load users function
        function loadDashboardUsers() {
            const tableBody = document.getElementById('usersTableBody');
            const paginationContainer = document.getElementById('usersPaginationContainer');
            const tableWrapper = tableBody?.closest('.table-responsive');

            if (!tableBody) return;

            // Show loading overlay
            let loadingOverlay = document.getElementById('usersLoadingOverlay');

            if (!loadingOverlay) {
                loadingOverlay = document.createElement('div');
                loadingOverlay.id = 'usersLoadingOverlay';
                loadingOverlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
                loadingOverlay.style.cssText = 'background: rgba(255, 255, 255, 0.9); z-index: 10; min-height: 400px;';
                loadingOverlay.innerHTML = `
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-2 mb-0">Loading users...</p>
                                </div>
                            `;

                if (tableWrapper) {
                    tableWrapper.style.position = 'relative';
                    tableWrapper.appendChild(loadingOverlay);
                }
            } else {
                loadingOverlay.classList.remove('d-none');
            }

            // Clear pagination during load
            if (paginationContainer) {
                paginationContainer.style.opacity = '0.5';
            }

            // Build query params
            const params = new URLSearchParams();
            if (currentUserFilters.investment_status) params.append('investment_status', currentUserFilters.investment_status);
            if (currentUserFilters.verification) params.append('verification', currentUserFilters.verification);
            if (currentUserFilters.status) params.append('status', currentUserFilters.status);
            if (currentUserFilters.search) params.append('search', currentUserFilters.search);
            if (currentUserFilters.per_page) params.append('per_page', currentUserFilters.per_page);
            if (currentUserFilters.page) params.append('page', currentUserFilters.page);
            if (currentUserFilters.start_date) params.append('start_date', currentUserFilters.start_date);
            if (currentUserFilters.end_date) params.append('end_date', currentUserFilters.end_date);

            // Make AJAX request
            fetch(`{{ route('admin.dashboard.users.filter') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = data.html;

                        // Update pagination
                        if (paginationContainer) {
                            paginationContainer.innerHTML = data.pagination;
                            paginationContainer.style.opacity = '1';
                        }

                        // Hide loading overlay
                        if (loadingOverlay) {
                            loadingOverlay.classList.add('d-none');
                        }

                        // Scroll to top on page change
                        if (currentUserFilters.page > 1) {
                            tableWrapper?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } else {
                        if (loadingOverlay) {
                            loadingOverlay.classList.add('d-none');
                        }
                        showAlert('Failed to load users', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    if (loadingOverlay) {
                        loadingOverlay.classList.add('d-none');
                    }

                    tableBody.innerHTML = `
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <iconify-icon icon="iconamoon:alert-circle-duotone" class="fs-1 text-danger mb-3"></iconify-icon>
                                            <h6 class="text-danger">Error Loading Users</h6>
                                            <p class="text-muted mb-0">Please try again.</p>
                                        </td>
                                    </tr>
                                `;
                    showAlert('Failed to load users', 'danger');
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            dashboardDatePicker = flatpickr("#dashboardDateRange", {
                mode: "range",
                dateFormat: "Y-m-d",
                maxDate: "today",
                onChange: function (selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        currentTransactionFilters.start_date = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                        currentTransactionFilters.end_date = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                        currentTransactionFilters.page = 1;
                        loadDashboardTransactions();
                        document.getElementById('clearDashboardDateFilter').style.display = 'inline-block';
                    }
                }
            });

            // Clear date filter button
            document.getElementById('clearDashboardDateFilter').addEventListener('click', function () {
                dashboardDatePicker.clear();
                currentTransactionFilters.start_date = '';
                currentTransactionFilters.end_date = '';
                currentTransactionFilters.page = 1;
                this.style.display = 'none';
                loadDashboardTransactions();
            });

            // Load initial paginated data if the table exists
            if (document.getElementById('transactionsTableBody')) {
                loadDashboardTransactions();
            }
        });


        let currentTransactionFilters = {
            type: '',
            status: '',
            per_page: 15,
            page: 1,
            start_date: '',
            end_date: ''
        };

        // Chart colors for all transaction types
        const chartColors = {
            deposits: '#22c55e',
            withdrawals: '#ef4444',
            commissions: '#DCF005',
            roi: '#06b6d4',
            bonus: '#6b7280',
            investments: '#5A018F'
        };

        document.addEventListener('DOMContentLoaded', function () {
            initializeTransactionChart();
            loadTransactionChartData();
            startAutoRefresh();

            // Period filter buttons
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentPeriod = this.dataset.period;
                    loadTransactionChartData();
                });
            });
        });

        function initializeTransactionChart() {
            const options = {
                series: [],
                chart: {
                    height: 400,
                    type: 'area',
                    stacked: false,
                    toolbar: {
                        show: true,
                        offsetY: -30
                    },
                    zoom: {
                        enabled: false
                    }
                },
                colors: [
                    chartColors.deposits,
                    chartColors.withdrawals,
                    chartColors.commissions,
                    chartColors.roi,
                    chartColors.bonus,
                    chartColors.investments
                ],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    type: 'category',
                    categories: [],
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        formatter: function (val) {
                            return '$' + Math.round(val).toLocaleString();
                        }
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (val, opts) {
                            return '$' + val.toFixed(2);
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    offsetY: 10,
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 5
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 5
                },
                responsive: [{
                    breakpoint: 768,
                    options: {
                        chart: {
                            height: 300
                        },
                        legend: {
                            position: 'bottom',
                            offsetY: 5
                        }
                    }
                }]
            };

            transactionChart = new ApexCharts(document.querySelector("#transactionFlowChart"), options);
            transactionChart.render();
        }

        function loadTransactionChartData() {
            fetch(`{{ route('admin.dashboard.transaction-chart-data') }}?period=${currentPeriod}`)
                .then(response => response.json())
                .then(data => {
                    transactionChart.updateOptions({
                        series: data.series,
                        xaxis: {
                            categories: data.categories
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading chart data:', error);
                    showAlert('Failed to load chart data', 'error');
                });
        }

        // Filter by transaction type (button click)
        function filterTransactionTypeCustom(type) {
            // Update button states
            const buttons = document.querySelectorAll('.transaction-type-btn');
            buttons.forEach(btn => {
                if (btn.getAttribute('data-type') === type) {
                    btn.classList.remove('btn-outline-secondary', 'btn-outline-success', 'btn-outline-warning', 'btn-outline-primary', 'btn-outline-info', 'btn-outline-dark');
                    btn.classList.add('active');

                    // Add solid color based on type
                    if (type === 'deposit') btn.className = 'btn btn-sm btn-success transaction-type-btn active';
                    else if (type === 'withdrawal') btn.className = 'btn btn-sm btn-warning transaction-type-btn active';
                    else if (type === 'commission') btn.className = 'btn btn-sm btn-primary transaction-type-btn active';
                    else if (type === 'roi') btn.className = 'btn btn-sm btn-info transaction-type-btn active';
                    else if (type === 'bonus') btn.className = 'btn btn-sm btn-secondary transaction-type-btn active';
                    else if (type === 'investment') btn.className = 'btn btn-sm btn-dark transaction-type-btn active';
                    else btn.className = 'btn btn-sm btn-secondary transaction-type-btn active';
                } else {
                    btn.classList.remove('active', 'btn-success', 'btn-warning', 'btn-primary', 'btn-info', 'btn-secondary', 'btn-dark');

                    // Restore outline style
                    const btnType = btn.getAttribute('data-type');
                    if (btnType === 'deposit') btn.className = 'btn btn-sm btn-outline-success transaction-type-btn';
                    else if (btnType === 'withdrawal') btn.className = 'btn btn-sm btn-outline-warning transaction-type-btn';
                    else if (btnType === 'commission') btn.className = 'btn btn-sm btn-outline-primary transaction-type-btn';
                    else if (btnType === 'roi') btn.className = 'btn btn-sm btn-outline-info transaction-type-btn';
                    else if (btnType === 'bonus') btn.className = 'btn btn-sm btn-outline-secondary transaction-type-btn';
                    else if (btnType === 'investment') btn.className = 'btn btn-sm btn-outline-dark transaction-type-btn';
                    else btn.className = 'btn btn-sm btn-outline-secondary transaction-type-btn';
                }
            });

            // Apply filter
            filterDashboardTransactions('type', type);
        }

        // Keep the existing filterDashboardTransactions function as is
        function filterDashboardTransactions(filterType, value) {
            // Update current filter
            currentTransactionFilters[filterType] = value;
            currentTransactionFilters.page = 1; // Reset to page 1 when filtering

            loadDashboardTransactions();
        }

        function loadDashboardTransactionsPage(page) {
            currentTransactionFilters.page = page;
            loadDashboardTransactions();
        }


        function loadDashboardTransactions() {
            const tableBody = document.getElementById('transactionsTableBody');
            const paginationContainer = document.getElementById('transactionsPaginationContainer');
            const tableWrapper = tableBody?.closest('.table-responsive');

            if (!tableBody) return;

            // Show loading overlay instead of replacing content
            let loadingOverlay = document.getElementById('transactionsLoadingOverlay');

            if (!loadingOverlay) {
                loadingOverlay = document.createElement('div');
                loadingOverlay.id = 'transactionsLoadingOverlay';
                loadingOverlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center';
                loadingOverlay.style.cssText = 'background: rgba(255, 255, 255, 0.9); z-index: 10; min-height: 400px;';
                loadingOverlay.innerHTML = `
                                                            <div class="text-center">
                                                                <div class="spinner-border text-primary" role="status">
                                                                    <span class="visually-hidden">Loading...</span>
                                                                </div>
                                                                <p class="text-muted mt-2 mb-0">Loading transactions...</p>
                                                            </div>
                                                        `;

                if (tableWrapper) {
                    tableWrapper.style.position = 'relative';
                    tableWrapper.appendChild(loadingOverlay);
                }
            } else {
                loadingOverlay.classList.remove('d-none');
            }

            // Clear pagination during load
            if (paginationContainer) {
                paginationContainer.style.opacity = '0.5';
            }

            // Build query params
            const params = new URLSearchParams();
            if (currentTransactionFilters.type) params.append('type', currentTransactionFilters.type);
            if (currentTransactionFilters.status) params.append('status', currentTransactionFilters.status);
            if (currentTransactionFilters.per_page) params.append('per_page', currentTransactionFilters.per_page);
            if (currentTransactionFilters.page) params.append('page', currentTransactionFilters.page);
            if (currentTransactionFilters.start_date) params.append('start_date', currentTransactionFilters.start_date);
            if (currentTransactionFilters.end_date) params.append('end_date', currentTransactionFilters.end_date);

            // Make AJAX request
            fetch(`{{ route('admin.dashboard.transactions.filter') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        tableBody.innerHTML = data.html;

                        // Update pagination
                        if (paginationContainer) {
                            paginationContainer.innerHTML = data.pagination;
                            paginationContainer.style.opacity = '1';
                        }

                        // Hide loading overlay
                        if (loadingOverlay) {
                            loadingOverlay.classList.add('d-none');
                        }

                        // Scroll to top of table on page change
                        if (currentTransactionFilters.page > 1) {
                            tableWrapper?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    } else {
                        if (loadingOverlay) {
                            loadingOverlay.classList.add('d-none');
                        }
                        showAlert('Failed to load transactions', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    if (loadingOverlay) {
                        loadingOverlay.classList.add('d-none');
                    }

                    tableBody.innerHTML = `
                                                                <tr>
                                                                    <td colspan="6" class="text-center py-4">
                                                                        <iconify-icon icon="iconamoon:alert-circle-duotone" class="fs-1 text-danger mb-3"></iconify-icon>
                                                                        <h6 class="text-danger">Error Loading Transactions</h6>
                                                                        <p class="text-muted mb-0">Please try again.</p>
                                                                    </td>
                                                                </tr>
                                                            `;
                    showAlert('Failed to load transactions', 'danger');
                });
        }

        function showTransactionDetails(transactionId) {
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            const content = document.getElementById('modalContent');

            content.innerHTML = `
                                        <div class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    `;

            modal.show();

            fetch(`{{ url('admin/finance/transactions') }}/${transactionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = data.html;
                    } else {
                        content.innerHTML = `<div class="alert alert-danger">${data.message || 'Failed to load details'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    content.innerHTML = '<div class="alert alert-danger">Failed to load transaction details</div>';
                });
        }

        function updateTransactionStatusDashboard(transactionId, status) {
            if (confirm(`Are you sure you want to mark this transaction as ${status}?`)) {
                fetch(`{{ url('admin/finance/transactions') }}/${transactionId}/update-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        status: status
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        showAlert(data.message, data.success ? 'success' : 'danger');
                        if (data.success) {
                            setTimeout(() => location.reload(), 1000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Failed to update transaction status', 'danger');
                    });
            }
        }

        function toggleTransactionDetails(transactionId) {
            const detailsElement = document.getElementById(`details-${transactionId}`);
            const chevronElement = document.getElementById(`chevron-${transactionId}`);

            if (detailsElement && detailsElement.classList.contains('show')) {
                detailsElement.classList.remove('show');
                chevronElement.style.transform = 'rotate(0deg)';
            } else if (detailsElement) {
                detailsElement.classList.add('show');
                chevronElement.style.transform = 'rotate(180deg)';
            }
        }

        function copyText(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    showAlert('Copied to clipboard!', 'success');
                });
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showAlert('Copied to clipboard!', 'success');
            }
        }

        function startAutoRefresh() {
            if (autoRefresh) {
                refreshInterval = setInterval(() => {
                    if (document.visibilityState === 'visible') {
                        updateDashboardStats();
                    }
                }, 30000);
            }
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }

        function updateDashboardStats() {
            fetch('{{ route("admin.api.stats") }}')
                .then(response => response.json())
                .then(data => {
                    // Update last update time or other real-time stats
                })
                .catch(error => console.error('Error updating stats:', error));
        }

        function refreshDashboard() {
            updateDashboardStats();
            loadTransactionChartData();
            showAlert('Dashboard refreshed!', 'success');
        }

        function exportDashboardData() {
            window.open('{{ route("admin.export.data") }}', '_blank');
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            alertDiv.innerHTML = `
                                                        ${message}
                                                        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                                                    `;

            document.body.appendChild(alertDiv);

            setTimeout(() => {
                if (alertDiv.parentNode) alertDiv.remove();
            }, 4000);
        }

        // Handle window resize for chart responsiveness
        window.addEventListener('resize', function () {
            if (transactionChart) {
                transactionChart.resize();
            }
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && autoRefresh) {
                updateDashboardStats();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Load initial paginated data if the table exists
            if (document.getElementById('transactionsTableBody')) {
                loadDashboardTransactions();
            }
        });
    </script>

    <style>
        /* Simple, clean styling */
        .bg-success-subtle {
            background-color: rgba(25, 135, 84, 0.1) !important;
        }

        .bg-danger-subtle {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }

        .bg-warning-subtle {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }

        .bg-info-subtle {
            background-color: rgba(23, 162, 184, 0.1) !important;
        }

        .bg-primary-subtle {
            background-color: rgba(0, 123, 255, 0.1) !important;
        }

        .bg-secondary-subtle {
            background-color: rgba(108, 117, 125, 0.1) !important;
        }

        .bg-dark-subtle {
            background-color: rgba(33, 37, 41, 0.1) !important;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .nav-pills .nav-link {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            margin-right: 0.25rem;
            color: #6c757d;
            border: 1px solid transparent;
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
            color: white;
        }

        .progress {
            border-radius: 4px;
        }

        .badge {
            font-weight: 500;
        }

        .period-btn {
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
        }

        .period-btn.active {
            background-color: #007bff;
            border-color: #007bff;
            color: white;
        }

        .period-btn:hover:not(.active) {
            background-color: #f8f9fa;
        }

        .transaction-card {
            transition: all 0.2s ease;
            border-radius: 8px;
        }

        .transaction-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .fs-20 {
            font-size: 1.25rem;
        }

        .avatar-sm {
            width: 2rem;
            height: 2rem;
            font-size: 0.8rem;
        }

        .avatar-title {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
        }

        .table-card .table thead th {
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.75rem;
        }

        .table-card .table tbody td {
            padding: 0.75rem;
            vertical-align: middle;
        }

        code {
            background-color: #f8f9fa;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            word-break: break-all;
        }

        /* ApexCharts styling */
        .apexcharts-legend {
            padding: 5px 0 !important;
        }

        .apexcharts-toolbar {
            z-index: 10 !important;
        }

        /* Mobile improvements */
        @media (max-width: 768px) {
            .card-title {
                font-size: 1rem;
            }

            .btn {
                font-size: 0.875rem;
            }

            .nav-pills .nav-link {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                margin-right: 0.125rem;
            }

            .table-responsive {
                font-size: 0.875rem;
            }

            .period-btn {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }

            .card-header .d-flex {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch !important;
            }

            #transactionFlowChart {
                height: 300px !important;
            }

            .card-header .flex-shrink-0 {
                flex-shrink: 1;
            }

            .card-header .d-flex.gap-2 {
                flex-wrap: wrap;
            }

            .card-header .form-select {
                flex: 1;
                min-width: 120px;
            }
        }

        @media (max-width: 576px) {
            .d-grid .btn {
                padding: 0.5rem;
                font-size: 0.875rem;
            }

            .card-body {
                padding: 1rem;
            }

            h4 {
                font-size: 1.25rem;
            }

            h5 {
                font-size: 1.125rem;
            }

            .period-btn {
                flex: 1;
                text-align: center;
            }

            .row.g-3>.col-6 {
                margin-bottom: 1rem;
            }

            .transaction-card .card-body {
                padding: 0.75rem;
            }

            .badge {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
        }

        /* Transaction table loading state */
        .table-responsive {
            min-height: 400px;
        }

        #transactionsLoadingOverlay {
            border-radius: 8px;
        }

        #transactionsLoadingOverlay.d-none {
            display: none !important;
        }

        /* Smooth transition for pagination */
        #transactionsPaginationContainer {
            transition: opacity 0.2s ease;
        }

        /* Prevent layout shift during loading */
        .table-card {
            position: relative;
        }

        /* Transaction type filter buttons */
        .transaction-type-btn {
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .transaction-type-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .transaction-type-btn.active {
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .transaction-type-btn iconify-icon {
            font-size: 1rem;
            vertical-align: middle;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            #transactionTypeButtons {
                justify-content: center;
            }

            .transaction-type-btn {
                flex: 0 0 auto;
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }

            .transaction-type-btn iconify-icon {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .transaction-type-btn span {
                display: none;
            }

            .transaction-type-btn {
                padding: 0.5rem;
                min-width: 40px;
            }

            .transaction-type-btn iconify-icon {
                margin: 0 !important;
            }
        }

        /* Date range picker styling */
        #dashboardDateRange,
        #customDateRange {
            cursor: pointer;
        }

        #dashboardDateRange:hover,
        #customDateRange:hover {
            border-color: #007bff;
        }

        .flatpickr-calendar {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* Mobile responsive */
        @media (max-width: 768px) {

            #dashboardDateRange,
            #customDateRange {
                min-width: 160px !important;
                font-size: 0.875rem;
            }
        }

        /* Replace your existing .nav-pills styles with these enhanced versions */
        .nav-pills .nav-link {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            margin-right: 0.25rem;
            color: #6c757d;
            border: 1px solid #dee2e6;
            /* Changed from transparent */
            background-color: #fff;
            /* Add background */
            font-weight: 500;
            /* Make text slightly bolder */
            cursor: pointer;
            /* Show pointer cursor */
            transition: all 0.2s ease;
            /* Smooth transitions */
        }

        .nav-pills .nav-link:hover:not(.active) {
            /* Add hover effect */
            background-color: #f8f9fa;
            border-color: #adb5bd;
            transform: translateY(-1px);
            /* Subtle lift effect */
        }

        .nav-pills .nav-link.active {
            background-color: #007bff;
            border-color: #007bff;
            /* Match border to background */
            color: white;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.3);
            /* Add shadow for depth */
        }

        /* Add this for better mobile responsiveness */
        @media (max-width: 576px) {
            .nav-pills .nav-link {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
            }
        }

        /* User status buttons */
        .user-status-btn {
            font-weight: 500;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .user-status-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .user-status-btn.active {
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        /* Users table loading overlay */
        #usersLoadingOverlay {
            border-radius: 8px;
        }

        #usersLoadingOverlay.d-none {
            display: none !important;
        }
    </style>
@endsection