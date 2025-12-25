@extends('admin.layouts.vertical', ['title' => 'Analytics', 'subTitle' => 'Admin Dashboard'])

@section('content')

{{-- Header with Period Filter --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="mb-1">Analytics</h4>
                        <p class="text-muted mb-0">Overview of transactions, users, and leads</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn active" data-period="7d">7 Days</button>
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="30d">30 Days</button>
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="90d">90 Days</button>
                        <button type="button" class="btn btn-sm btn-outline-primary period-btn" data-period="1y">1 Year</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Summary Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fas fa-arrow-down text-success" style="font-size: 2rem;"></i>
                </div>
                <h6 class="text-muted mb-1">Deposits</h6>
                <h5 class="mb-0">${{ number_format($summaryData['transactions']['deposits'], 2) }}</h5>
                <small class="text-muted">{{ $summaryData['transactions']['deposits_count'] }} transactions</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fas fa-arrow-up text-danger" style="font-size: 2rem;"></i>
                </div>
                <h6 class="text-muted mb-1">Withdrawals</h6>
                <h5 class="mb-0">${{ number_format($summaryData['transactions']['withdrawals'], 2) }}</h5>
                <small class="text-muted">{{ $summaryData['transactions']['withdrawals_count'] }} transactions</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fas fa-users text-primary" style="font-size: 2rem;"></i>
                </div>
                <h6 class="text-muted mb-1">Total Users</h6>
                <h5 class="mb-0">{{ number_format($summaryData['users']['total']) }}</h5>
                <small class="text-muted">{{ $summaryData['users']['active'] }} active</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fas fa-user-plus text-info" style="font-size: 2rem;"></i>
                </div>
                <h6 class="text-muted mb-1">New Users</h6>
                <h5 class="mb-0">{{ number_format($summaryData['users']['new_registrations']) }}</h5>
                <small class="text-muted">This period</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fas fa-bullseye text-warning" style="font-size: 2rem;"></i>
                </div>
                <h6 class="text-muted mb-1">Total Leads</h6>
                <h5 class="mb-0">{{ number_format($summaryData['leads']['total']) }}</h5>
                <small class="text-muted">{{ $summaryData['leads']['hot'] }} hot</small>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-lg-2">
        <div class="card text-center">
            <div class="card-body">
                <div class="mb-2">
                    <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                </div>
                <h6 class="text-muted mb-1">Conversions</h6>
                <h5 class="mb-0">{{ number_format($summaryData['leads']['converted']) }}</h5>
                <small class="text-muted">{{ number_format($summaryData['leads']['conversion_rate'], 1) }}% rate</small>
            </div>
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row g-4 mb-4">
    {{-- Main Transaction Chart --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction Flow</h5>
            </div>
            <div class="card-body">
                <div id="transaction-area-chart" class="apex-charts"></div>
            </div>
        </div>
    </div>

    {{-- Secondary Charts --}}
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Growth</h5>
            </div>
            <div class="card-body">
                <div id="user-growth-chart" class="apex-charts"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Lead Status Distribution</h5>
            </div>
            <div class="card-body">
                <div id="lead-donut-chart" class="apex-charts"></div>
            </div>
        </div>
    </div>
</div>

{{-- Data Tables --}}
<div class="row g-4 mb-4">
    {{-- Transaction Breakdown --}}
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction Summary</h5>
            </div>
            <div class="card-body p-0">
                {{-- Desktop Table --}}
                <div class="d-none d-md-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td>
                                        <i class="fas fa-arrow-down text-success me-2"></i>
                                        Deposits
                                    </td>
                                    <td class="text-end">${{ number_format($summaryData['transactions']['deposits'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-arrow-up text-danger me-2"></i>
                                        Withdrawals
                                    </td>
                                    <td class="text-end">${{ number_format($summaryData['transactions']['withdrawals'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-coins text-warning me-2"></i>
                                        Commissions
                                    </td>
                                    <td class="text-end">${{ number_format($summaryData['transactions']['commissions'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-chart-line text-info me-2"></i>
                                        ROI
                                    </td>
                                    <td class="text-end">${{ number_format($summaryData['transactions']['roi'], 2) }}</td>
                                </tr>
                                <tr class="table-active fw-bold">
                                    <td>Net Volume</td>
                                    <td class="text-end">${{ number_format($summaryData['transactions']['deposits'] - $summaryData['transactions']['withdrawals'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-md-none p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-arrow-down text-success me-2"></i>
                                    <span>Deposits</span>
                                </div>
                                <span class="fw-semibold">${{ number_format($summaryData['transactions']['deposits'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-arrow-up text-danger me-2"></i>
                                    <span>Withdrawals</span>
                                </div>
                                <span class="fw-semibold">${{ number_format($summaryData['transactions']['withdrawals'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-coins text-warning me-2"></i>
                                    <span>Commissions</span>
                                </div>
                                <span class="fw-semibold">${{ number_format($summaryData['transactions']['commissions'], 2) }}</span>
                            </div>
                        </div>
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Net Volume</span>
                                <span class="fw-bold">${{ number_format($summaryData['transactions']['deposits'] - $summaryData['transactions']['withdrawals'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- User Statistics --}}
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Statistics</h5>
            </div>
            <div class="card-body p-0">
                {{-- Desktop Table --}}
                <div class="d-none d-md-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td>
                                        <i class="fas fa-users text-primary me-2"></i>
                                        Total Users
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['users']['total']) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-check text-success me-2"></i>
                                        Active Users
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['users']['active']) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-user-plus text-info me-2"></i>
                                        New This Period
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['users']['new_registrations']) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-id-card text-warning me-2"></i>
                                        KYC Verified
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['users']['kyc_verified']) }}</td>
                                </tr>
                                <tr class="table-active fw-bold">
                                    <td>Activation Rate</td>
                                    <td class="text-end">{{ number_format($summaryData['users']['activation_rate'], 1) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-md-none p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <span>Total Users</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($summaryData['users']['total']) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-check text-success me-2"></i>
                                    <span>Active Users</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($summaryData['users']['active']) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-plus text-info me-2"></i>
                                    <span>New Users</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($summaryData['users']['new_registrations']) }}</span>
                            </div>
                        </div>
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Activation Rate</span>
                                <span class="fw-bold">{{ number_format($summaryData['users']['activation_rate'], 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lead Performance --}}
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Lead Performance</h5>
            </div>
            <div class="card-body p-0">
                {{-- Desktop Table --}}
                <div class="d-none d-md-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <tbody>
                                <tr>
                                    <td>
                                        <i class="fas fa-bullseye text-primary me-2"></i>
                                        Total Leads
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['leads']['total']) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-fire text-danger me-2"></i>
                                        Hot Leads
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['leads']['hot']) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-thermometer-half text-warning me-2"></i>
                                        Warm Leads
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['leads']['warm']) }}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Converted
                                    </td>
                                    <td class="text-end">{{ number_format($summaryData['leads']['converted']) }}</td>
                                </tr>
                                <tr class="table-active fw-bold">
                                    <td>Conversion Rate</td>
                                    <td class="text-end">{{ number_format($summaryData['leads']['conversion_rate'], 1) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Cards --}}
                <div class="d-md-none p-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-bullseye text-primary me-2"></i>
                                    <span>Total Leads</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($summaryData['leads']['total']) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-fire text-danger me-2"></i>
                                    <span>Hot Leads</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($summaryData['leads']['hot']) }}</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <span>Converted</span>
                                </div>
                                <span class="fw-semibold">{{ number_format($summaryData['leads']['converted']) }}</span>
                            </div>
                        </div>
                        <div class="col-12 border-top pt-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Conversion Rate</span>
                                <span class="fw-bold">{{ number_format($summaryData['leads']['conversion_rate'], 1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Activity --}}
<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Transactions</h5>
            </div>
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">User</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentData['transactions'] as $transaction)
                                <tr>
                                    <td>{{ $transaction->user->full_name ?? 'Unknown' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }} p-1">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($transaction->amount, 2) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }} p-1">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none p-3">
                    <div class="row g-3">
                        @foreach($recentData['transactions'] as $transaction)
                        <div class="col-12">
                            <div class="card transaction-card border">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}-subtle text-{{ $transaction->type === 'deposit' ? 'success' : ($transaction->type === 'withdrawal' ? 'warning' : 'info') }}">
                                                {{ ucfirst($transaction->type) }}
                                            </span>
                                            <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }}-subtle text-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-0">${{ number_format($transaction->amount, 2) }}</h6>
                                            <small class="text-muted">{{ $transaction->user->full_name ?? 'Unknown' }}</small>
                                        </div>
                                        @if($transaction->type === 'deposit')
                                            <i class="fas fa-arrow-down text-success fs-20"></i>
                                        @elseif($transaction->type === 'withdrawal')
                                            <i class="fas fa-arrow-up text-warning fs-20"></i>
                                        @else
                                            <i class="fas fa-wallet text-info fs-20"></i>
                                        @endif
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

    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Users & Leads</h5>
            </div>
            <div class="card-body p-0">
                {{-- Desktop Table View --}}
                <div class="d-none d-lg-block">
                    <div class="table-responsive table-card">
                        <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                            <thead class="bg-light bg-opacity-50 thead-sm">
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentData['users'] as $current_user)
                                <tr>
                                    <td>{{ $current_user->full_name }}</td>
                                    <td><span class="badge bg-primary-subtle text-primary p-1">User</span></td>
                                    <td>
                                        <span class="badge bg-{{ $current_user->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $current_user->status === 'active' ? 'success' : 'secondary' }} p-1">
                                            {{ ucfirst($current_user->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $current_user->created_at->format('M d') }}</td>
                                </tr>
                                @endforeach
                                @foreach($recentData['leads'] as $lead)
                                <tr>
                                    <td>{{ $lead->full_name }}</td>
                                    <td><span class="badge bg-info-subtle text-info p-1">Lead</span></td>
                                    <td>
                                        <span class="badge bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'converted' ? 'success' : 'secondary')) }}-subtle text-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'converted' ? 'success' : 'secondary')) }} p-1">
                                            {{ ucfirst($lead->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $lead->created_at->format('M d') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile Card View --}}
                <div class="d-lg-none p-3">
                    <div class="row g-3">
                        @foreach($recentData['users'] as $current_user)
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-primary-subtle text-primary">User</span>
                                            <span class="badge bg-{{ $current_user->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $current_user->status === 'active' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($current_user->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-0">{{ $current_user->full_name }}</h6>
                                            <small class="text-muted">{{ $current_user->created_at->format('M d, Y') }}</small>
                                        </div>
                                        <i class="fas fa-user text-primary fs-20"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @foreach($recentData['leads'] as $lead)
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex gap-2">
                                            <span class="badge bg-info-subtle text-info">Lead</span>
                                            <span class="badge bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'converted' ? 'success' : 'secondary')) }}-subtle text-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'converted' ? 'success' : 'secondary')) }}">
                                                {{ ucfirst($lead->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-0">{{ $lead->full_name }}</h6>
                                            <small class="text-muted">{{ $lead->created_at->format('M d, Y') }}</small>
                                        </div>
                                        <i class="fas fa-bullseye text-info fs-20"></i>
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
</div>

@endsection

@section('script')
<script src="https://apexcharts.com/samples/assets/stock-prices.js"></script>
<script src="https://apexcharts.com/samples/assets/series1000.js"></script>
<script src="https://apexcharts.com/samples/assets/github-data.js"></script>
<script src="https://apexcharts.com/samples/assets/irregular-data-series.js"></script>
@vite(['resources/js/components/apexchart-area.js'])

<script>
let currentPeriod = '7d';
let transactionChart, userGrowthChart, leadDonutChart;

const chartColors = {
    deposits: '#22c55e',
    withdrawals: '#ef4444',
    commissions: '#3b82f6',
    roi: '#f59e0b',
    bonus: '#06b6d4',
    investments: '#6b7280',
    users: '#8b5cf6',
    leads: '#f97316'
};

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadTransactionData();
    
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentPeriod = this.dataset.period;
            loadTransactionData();
        });
    });
});

function initializeCharts() {
    // Transaction Area Chart
    const transactionOptions = {
        series: [],
        chart: {
            height: 350,
            type: 'area',
            stacked: false,
            toolbar: {
                show: true,
                offsetY: -30
            }
        },
        colors: [chartColors.deposits, chartColors.withdrawals, chartColors.commissions],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            type: 'category',
            categories: []
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return '$' + val.toFixed(0);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return '$' + val.toFixed(2);
                }
            }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            offsetY: 5
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
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 300
                }
            }
        }]
    };
    
    transactionChart = new ApexCharts(document.querySelector("#transaction-area-chart"), transactionOptions);
    transactionChart.render();

    // User Growth Chart
    const userGrowthOptions = {
        series: [],
        chart: {
            height: 300,
            type: 'line',
            toolbar: {
                show: false
            }
        },
        colors: [chartColors.users],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        xaxis: {
            type: 'category',
            categories: []
        },
        yaxis: {
            labels: {
                formatter: function (val) {
                    return val.toFixed(0);
                }
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + ' users';
                }
            }
        }
    };
    
    userGrowthChart = new ApexCharts(document.querySelector("#user-growth-chart"), userGrowthOptions);
    userGrowthChart.render();

    // Lead Donut Chart
    const leadDonutOptions = {
        series: [],
        chart: {
            height: 300,
            type: 'donut'
        },
        colors: ['#ef4444', '#f59e0b', '#06b6d4', '#22c55e', '#6b7280'],
        labels: ['Hot', 'Warm', 'Cold', 'Converted', 'Lost'],
        legend: {
            position: 'bottom'
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val.toFixed(1) + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + ' leads';
                }
            }
        },
        responsive: [{
            breakpoint: 768,
            options: {
                chart: {
                    height: 250
                }
            }
        }]
    };
    
    leadDonutChart = new ApexCharts(document.querySelector("#lead-donut-chart"), leadDonutOptions);
    leadDonutChart.render();
}

function loadTransactionData() {
    fetch(`{{ route('admin.comprehensive-chart-data') }}?period=${currentPeriod}&type=transactions`)
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
            console.error('Error loading transaction data:', error);
        });
        
    fetch(`{{ route('admin.comprehensive-chart-data') }}?period=${currentPeriod}&type=users`)
        .then(response => response.json())
        .then(data => {
            userGrowthChart.updateOptions({
                series: [{
                    name: 'New Users',
                    data: data.new_users
                }],
                xaxis: {
                    categories: data.categories
                }
            });
        })
        .catch(error => {
            console.error('Error loading user data:', error);
        });
        
    fetch(`{{ route('admin.comprehensive-chart-data') }}?period=${currentPeriod}&type=leads`)
        .then(response => response.json())
        .then(data => {
            leadDonutChart.updateSeries(data.status_breakdown);
        })
        .catch(error => {
            console.error('Error loading lead data:', error);
        });
}

// Handle window resize
window.addEventListener('resize', function() {
    if (transactionChart) transactionChart.resize();
    if (userGrowthChart) userGrowthChart.resize();
    if (leadDonutChart) leadDonutChart.resize();
});
</script>

<style>
/* Simple, clean styling */
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

/* Transaction card styling */
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

/* Table improvements */
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

/* Badge subtle styling */
.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

/* Charts */
.apex-charts {
    width: 100% !important;
}

/* ApexCharts toolbar fix */
.apexcharts-toolbar {
    z-index: 10 !important;
}

.apexcharts-legend {
    padding: 5px 0 !important;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
    
    .card-title {
        font-size: 1rem;
    }
    
    h4 {
        font-size: 1.25rem;
    }
    
    h5 {
        font-size: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .period-btn {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 0.75rem;
    }
    
    h5 {
        font-size: 0.9rem;
    }
    
    .period-btn {
        flex: 1;
        text-align: center;
    }
    
    .transaction-card .card-body {
        padding: 0.75rem;
    }
}

/* Smooth transitions */
.btn, .badge, .card {
    transition: all 0.2s ease;
}
</style>
@endsection