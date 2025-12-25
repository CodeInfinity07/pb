@extends('admin.layouts.vertical', ['title' => 'Referral Details', 'subTitle' => 'Admin'])

@section('content')
<div class="container-fluid">
    {{-- Page Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <a href="{{ route('admin.referrals.index') }}" class="text-decoration-none">
                                            <iconify-icon icon="material-symbols:arrow-back-rounded" class="me-1"></iconify-icon>
                                            Referrals
                                        </a>
                                    </li>
                                    <li class="breadcrumb-item active">User Details</li>
                                </ol>
                            </nav>
                            <h4 class="mb-1 text-dark">{{ $referral->full_name }}</h4>
                            <p class="text-muted mb-0">Referral relationship and user information</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('admin.users.show', $referral) }}" class="btn btn-outline-primary btn-sm">
                                <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                View Full Profile
                            </a>
                            <a href="{{ route('admin.referrals.index') }}" class="btn btn-primary btn-sm">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Main Content --}}
        <div class="col-xl-8 col-lg-7 mb-4">
            {{-- Referral Relationship --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:hierarchy-duotone" class="me-2"></iconify-icon>
                        Referral Relationship
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Sponsor Information --}}
                        <div class="col-md-6">
                            <div class="participant-card border rounded-3 p-4 h-100 bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg rounded-circle bg-primary me-3 shadow-sm">
                                        <span class="avatar-title text-white fs-3 fw-bold">
                                            {{ $referral->sponsor ? $referral->sponsor->initials : 'D' }}
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">Sponsor (Referred By)</h6>
                                        <span class="badge bg-primary">{{ $referral->sponsor ? 'User Referral' : 'Direct Registration' }}</span>
                                    </div>
                                </div>
                                
                                @if($referral->sponsor)
                                    <div class="participant-details">
                                        <div class="detail-item mb-3">
                                            <label class="detail-label">Full Name</label>
                                            <div class="detail-value fw-semibold">{{ $referral->sponsor->full_name }}</div>
                                        </div>
                                        <div class="detail-item mb-3">
                                            <label class="detail-label">Email Address</label>
                                            <div class="detail-value">
                                                <a href="mailto:{{ $referral->sponsor->email }}" class="text-decoration-none">
                                                    {{ $referral->sponsor->email }}
                                                </a>
                                            </div>
                                        </div>
                                        <div class="detail-item mb-3">
                                            <label class="detail-label">Member Since</label>
                                            <div class="detail-value">{{ $referral->sponsor->created_at->format('M d, Y') }}</div>
                                        </div>
                                        <div class="detail-item mb-3">
                                            <label class="detail-label">Total Referrals</label>
                                            <div class="detail-value">
                                                <span class="badge bg-secondary">{{ $referralStats['sponsor_referral_count'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4 pt-3 border-top">
                                        <a href="{{ route('admin.users.show', $referral->sponsor) }}" class="btn btn-outline-primary btn-sm">
                                            <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                            View Profile
                                        </a>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <iconify-icon icon="material-symbols:person-add-outline" class="fs-1 text-muted mb-3"></iconify-icon>
                                        <h6 class="text-muted mb-2">Direct Registration</h6>
                                        <p class="text-muted small mb-0">This user registered directly without a referral link</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- User Information --}}
                        <div class="col-md-6">
                            <div class="participant-card border rounded-3 p-4 h-100 bg-light bg-opacity-50">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="avatar avatar-lg rounded-circle bg-info me-3 shadow-sm">
                                        <span class="avatar-title text-white fs-3 fw-bold">
                                            {{ $referral->initials }}
                                        </span>
                                    </div>
                                    <div>
                                        <h6 class="mb-1 fw-bold">User Details</h6>
                                        <span class="badge bg-info">{{ $referral->sponsor ? 'Referred User' : 'Direct User' }}</span>
                                    </div>
                                </div>
                                
                                <div class="participant-details">
                                    <div class="detail-item mb-3">
                                        <label class="detail-label">Full Name</label>
                                        <div class="detail-value fw-semibold">{{ $referral->full_name }}</div>
                                    </div>
                                    <div class="detail-item mb-3">
                                        <label class="detail-label">Email Address</label>
                                        <div class="detail-value">
                                            <a href="mailto:{{ $referral->email }}" class="text-decoration-none">
                                                {{ $referral->email }}
                                            </a>
                                        </div>
                                    </div>
                                    <div class="detail-item mb-3">
                                        <label class="detail-label">Registration Date</label>
                                        <div class="detail-value">{{ $referral->created_at->format('M d, Y') }}</div>
                                    </div>
                                    <div class="detail-item mb-3">
                                        <label class="detail-label">Last Login</label>
                                        <div class="detail-value">
                                            {{ $referral->last_login_at ? $referral->last_login_at->format('M d, Y') : 'Never' }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-3 border-top">
                                    <a href="{{ route('admin.users.show', $referral) }}" class="btn btn-outline-info btn-sm">
                                        <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                                        Full Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Referral Chain --}}
            @if(count($referralChain) > 0)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-2"></iconify-icon>
                            Upline Chain
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chain-container">
                            @foreach($referralChain as $chainUser)
                                <div class="chain-item d-flex align-items-center mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="chain-level me-3">
                                        <span class="badge bg-secondary">L{{ $chainUser['level'] }}</span>
                                    </div>
                                    <div class="avatar avatar-sm rounded-circle bg-primary me-3">
                                        <span class="avatar-title text-white fw-semibold">
                                            {{ $chainUser['user']->initials }}
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-semibold">{{ $chainUser['user']->full_name }}</h6>
                                        <small class="text-muted">{{ $chainUser['user']->email }}</small>
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $chainUser['user']) }}" class="btn btn-sm btn-outline-secondary">
                                            View
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- User's Referrals --}}
            @if($userReferrals->count() > 0)
                <div class="card shadow-sm">
                    <div class="card-header bg-light border-bottom d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            <iconify-icon icon="iconamoon:arrow-down-2-duotone" class="me-2"></iconify-icon>
                            Users Referred by {{ $referral->first_name }}
                        </h5>
                        <span class="badge bg-primary fs-6">{{ $userReferrals->count() }} referral(s)</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" class="border-0">User</th>
                                        <th scope="col" class="border-0">Email</th>
                                        <th scope="col" class="border-0">Registration</th>
                                        <th scope="col" class="border-0">Last Login</th>
                                        <th scope="col" class="border-0 text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($userReferrals as $userReferral)
                                        <tr class="referral-row">
                                            <td class="py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm rounded-circle bg-info me-3">
                                                        <span class="avatar-title text-white fw-semibold">
                                                            {{ $userReferral->initials }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 fw-semibold">{{ $userReferral->full_name }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <a href="mailto:{{ $userReferral->email }}" class="text-decoration-none">
                                                    {{ $userReferral->email }}
                                                </a>
                                            </td>
                                            <td class="py-3">
                                                <div class="small">
                                                    <div class="fw-semibold">{{ $userReferral->created_at->format('M d, Y') }}</div>
                                                    <small class="text-muted">{{ $userReferral->created_at->diffForHumans() }}</small>
                                                </div>
                                            </td>
                                            <td class="py-3">
                                                <div class="small">
                                                    @if($userReferral->last_login_at)
                                                        <div class="fw-semibold">{{ $userReferral->last_login_at->format('M d, Y') }}</div>
                                                        <small class="text-muted">{{ $userReferral->last_login_at->diffForHumans() }}</small>
                                                    @else
                                                        <div class="text-muted">Never</div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="py-3 text-center">
                                                <a href="{{ route('admin.referrals.show', $userReferral) }}" class="btn btn-sm btn-outline-primary">
                                                    <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="col-xl-4 col-lg-5">
            {{-- User Summary --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                        User Summary
                    </h5>
                </div>
                <div class="card-body">
                    <div class="summary-item d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="solar:hashtag-bold" class="text-muted me-2"></iconify-icon>
                            <span class="text-muted">User ID</span>
                        </div>
                        <code class="fw-bold">#{{ $referral->id }}</code>
                    </div>
                    <div class="summary-item d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:email-duotone" class="text-muted me-2"></iconify-icon>
                            <span class="text-muted">Email</span>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ $referral->email }}</div>
                        </div>
                    </div>
                    <div class="summary-item d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:profile-circle-duotone" class="text-muted me-2"></iconify-icon>
                            <span class="text-muted">Username</span>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ $referral->username ?? 'Not set' }}</div>
                        </div>
                    </div>
                    <div class="summary-item d-flex justify-content-between align-items-center py-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="material-symbols:calendar-today" class="text-muted me-2"></iconify-icon>
                            <span class="text-muted">Registered</span>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ $referral->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $referral->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    <div class="summary-item d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <iconify-icon icon="iconamoon:clock-duotone" class="text-muted me-2"></iconify-icon>
                            <span class="text-muted">Last Login</span>
                        </div>
                        <div class="text-end">
                            @if($referral->last_login_at)
                                <div class="fw-semibold">{{ $referral->last_login_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $referral->last_login_at->diffForHumans() }}</small>
                            @else
                                <div class="text-muted">Never</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Referral Statistics --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:graph-duotone" class="me-2"></iconify-icon>
                        Referral Statistics
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card text-center p-3 border rounded">
                                <div class="stat-number fs-4 fw-bold text-primary">{{ $referralStats['direct_referrals'] ?? 0 }}</div>
                                <div class="stat-label small text-muted">Direct Referrals</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card text-center p-3 border rounded">
                                <div class="stat-number fs-4 fw-bold text-info">{{ $referralStats['total_downline'] ?? 0 }}</div>
                                <div class="stat-label small text-muted">Total Downline</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stat-card text-center p-3 border rounded">
                                <div class="stat-number fs-4 fw-bold text-success">{{ $referralStats['recent_referrals'] ?? 0 }}</div>
                                <div class="stat-label small text-muted">Recent Referrals (30 days)</div>
                            </div>
                        </div>
                    </div>

                    @if($referral->sponsor)
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted">Referral Position</span>
                                <span class="badge bg-primary">Level {{ count($referralChain) + 1 }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card shadow-sm">
                <div class="card-header bg-light border-bottom">
                    <h5 class="card-title mb-0">
                        <iconify-icon icon="mynaui:lightning-solid" class="me-2"></iconify-icon>
                        Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.users.show', $referral) }}" class="btn btn-primary">
                            <iconify-icon icon="iconamoon:profile-duotone" class="me-1"></iconify-icon>
                            View Full Profile
                        </a>
                        
                        @if($referral->sponsor)
                            <a href="{{ route('admin.referrals.show', $referral->sponsor) }}" class="btn btn-outline-primary">
                                <iconify-icon icon="iconamoon:arrow-up-2-duotone" class="me-1"></iconify-icon>
                                View Sponsor
                            </a>
                        @endif
                        
                        @if($userReferrals->count() > 0)
                            <a href="{{ route('admin.referrals.tree') }}?user_id={{ $referral->id }}" class="btn btn-outline-info">
                                <iconify-icon icon="iconamoon:hierarchy-duotone" class="me-1"></iconify-icon>
                                View Tree
                            </a>
                        @endif

                        <a href="{{ route('admin.referrals.index') }}" class="btn btn-outline-secondary">
                            <iconify-icon icon="material-symbols:arrow-back-rounded" class="me-1"></iconify-icon>
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
// Simple page interactions - no complex status management needed for simplified version
document.addEventListener('DOMContentLoaded', function() {
    // Add any simple interactions here if needed
    console.log('Referral details page loaded');
});
</script>

<style>
/* Base Styles */
.card {
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-radius: 0.75rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.15s ease-in-out;
}

.card:hover {
    box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
}

/* Avatar Styles */
.avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    border-radius: 50%;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.875rem;
}

.avatar-lg {
    width: 4rem;
    height: 4rem;
    font-size: 1.25rem;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Participant Cards */
.participant-card {
    transition: all 0.2s ease;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.8));
}

.participant-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.detail-item {
    transition: all 0.15s ease;
}

.detail-label {
    display: block;
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    font-weight: 500;
}

.detail-value {
    font-size: 0.95rem;
    color: #212529;
}

/* Summary Items */
.summary-item {
    transition: background-color 0.15s ease;
}

.summary-item:hover {
    background-color: rgba(0, 123, 255, 0.05);
    border-radius: 0.5rem;
    margin: 0 -0.5rem;
    padding-left: 1rem !important;
    padding-right: 1rem !important;
}

/* Chain Styles */
.chain-container {
    position: relative;
}

.chain-item {
    transition: all 0.15s ease;
}

.chain-item:hover {
    background-color: rgba(0, 123, 255, 0.05);
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin: 0 -0.75rem 0.75rem;
}

.chain-level {
    min-width: 40px;
}

/* Statistics Cards */
.stat-card {
    transition: all 0.2s ease;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.8));
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    border-color: #0d6efd !important;
}

.stat-number {
    line-height: 1;
}

.stat-label {
    margin-top: 0.5rem;
}

/* Table Styles */
.table-container {
    position: relative;
    overflow: visible;
}

.table {
    margin-bottom: 0;
}

.table th {
    font-weight: 600;
    font-size: 0.875rem;
    color: #495057;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    padding: 1rem 0.75rem;
}

.table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #dee2e6;
}

.referral-row {
    transition: background-color 0.15s ease-in-out;
}

.referral-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Badge Styles */
.badge {
    font-weight: 500;
    padding: 0.375em 0.75em;
    border-radius: 0.375rem;
    font-size: 0.75em;
    display: inline-flex;
    align-items: center;
}

/* Button Styles */
.btn {
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
}

/* Code styling */
code {
    background-color: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #6f42c1;
    border: 1px solid #e9ecef;
}

/* Breadcrumb improvements */
.breadcrumb-item a {
    color: #6c757d;
    transition: color 0.15s ease;
}

.breadcrumb-item a:hover {
    color: #0d6efd;
}

/* Responsive Styles */
@media (max-width: 991.98px) {
    .card-header .d-flex {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }
}

@media (max-width: 767.98px) {
    .participant-card {
        margin-bottom: 1rem;
    }
    
    .detail-item {
        margin-bottom: 1rem !important;
    }
    
    .summary-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }
    
    .avatar-lg {
        width: 3rem;
        height: 3rem;
        font-size: 1rem;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }
    
    .chain-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.5rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 1rem;
    }
    
    .participant-card {
        padding: 1rem !important;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection