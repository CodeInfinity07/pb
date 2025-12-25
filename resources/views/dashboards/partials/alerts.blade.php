{{-- Dashboard Alert Notifications Partial --}}
{{-- File: resources/views/dashboards/partials/alerts.blade.php --}}

@if(!$user->profile->phone_verified)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-alert-triangle fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Phone Verification Required</h6>
                    <p class="mb-2">Your phone number is not verified. Please verify your phone number to access all features and ensure account security.</p>
                    <a href="{{ route('phone.verify') }}" class="btn btn-warning btn-sm">
                        <i class="ti ti-phone-check me-1"></i>
                        Verify Phone Now
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

@if($user->profile->kyc_status !== 'verified')
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-{{ $user->profile->kyc_status === 'pending' ? 'info' : 'warning' }} alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-id-badge-2 fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">
                        KYC Verification 
                        @if($user->profile->kyc_status === 'pending')
                            Pending
                        @elseif($user->profile->kyc_status === 'under_review')
                            Under Review
                        @elseif($user->profile->kyc_status === 'submitted')
                            Submitted
                        @else
                            Required
                        @endif
                    </h6>
                    <p class="mb-2">
                        @if(in_array($user->profile->kyc_status, ['under_review', 'submitted', 'pending']))
                            Your KYC verification is currently being reviewed by our team. You'll be notified once the verification process is complete.
                        @else
                            Complete your KYC (Know Your Customer) verification to unlock all platform features including higher withdrawal limits and enhanced security.
                        @endif
                    </p>
                    @if(!in_array($user->profile->kyc_status, ['under_review', 'submitted']))
                    <a href="{{ route('kyc.index') }}" class="btn btn-info btn-sm">
                        <i class="ti ti-upload me-1"></i>
                        Complete KYC Verification
                    </a>
                    @endif
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Welcome Message for New Users --}}
@if($user->created_at->diffInDays(now()) <= 7)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-confetti fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Welcome to the Platform! 🎉</h6>
                    <p class="mb-2">
                        Thank you for joining us! To get started, consider making your first deposit or exploring our investment opportunities. 
                        Don't forget to invite friends using your referral link to earn additional commissions.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-success btn-sm">
                            <i class="ti ti-plus me-1"></i>
                            Make First Deposit
                        </a>
                        <a href="{{ route('user.investments') }}" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-chart-line me-1"></i>
                            Explore Investments
                        </a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Pending Withdrawals Alert --}}
@if(isset($dashboardData['pending_withdrawals']) && $dashboardData['pending_withdrawals'] > 0)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-clock-hour-4 fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Pending Withdrawals</h6>
                    <p class="mb-2">
                        You have pending withdrawals totaling <strong>${{ number_format($dashboardData['pending_withdrawals'], 2) }}</strong>. 
                        These are currently being processed and should be completed within 24-48 hours.
                    </p>
                    <a href="{{ route('transactions.index') }}?type=withdrawal&status=pending" class="btn btn-info btn-sm">
                        <i class="ti ti-eye me-1"></i>
                        View Pending Withdrawals
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Session-based Flash Messages --}}
@if(session('success'))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-check-circle fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Success!</strong> {{ session('success') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-x-circle fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Error!</strong> {{ session('error') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('warning'))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-alert-triangle fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Warning!</strong> {{ session('warning') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('info'))
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="ti ti-info-circle fs-20 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <strong>Info!</strong> {{ session('info') }}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
@endif