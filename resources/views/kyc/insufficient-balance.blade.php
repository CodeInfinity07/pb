@extends('layouts.vertical', ['title' => 'KYC Verification', 'subTitle' => 'Insufficient Balance'])
@section('content')
    <div class="container-fluid">
        <!-- Insufficient Balance Alert -->
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center py-4 py-md-5 px-3 px-md-4">

                        <!-- Warning Icon -->
                        <div class="mb-3 mb-md-4">
                            <div
                                class="avatar-xl bg-warning-subtle rounded-circle mx-auto d-flex align-items-center justify-content-center">
                                <iconify-icon icon="material-symbols:wallet-outline" class="text-warning"
                                    style="font-size: 3rem;"></iconify-icon>
                            </div>
                        </div>

                        <!-- Title -->
                        <h2 class="text-warning mb-2 mb-md-3 fs-20 fs-md-28">Insufficient Balance for KYC Verification</h2>

                        <!-- Description -->
                        <p class="text-muted mb-3 mb-md-4 fs-14 fs-md-16 lh-base px-2 px-lg-5">
                            To complete KYC verification, you need to have at least <strong>$1.00</strong> in your wallet.
                            This is a one-time verification fee that helps us maintain our security standards.
                        </p>

                        <!-- Balance Information Card -->
                        <div class="row justify-content-center mb-3 mb-md-4">
                            <div class="col-12 col-md-8">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="row text-center g-3">
                                            <div class="col-6">
                                                <div class="border-end pe-3">
                                                    <small class="text-muted d-block mb-1">Current Balance</small>
                                                    <h4 class="mb-0 text-danger fs-18 fs-md-24">
                                                        ${{ number_format($currentBalance, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="ps-3">
                                                    <small class="text-muted d-block mb-1">Required Amount</small>
                                                    <h4 class="mb-0 text-success fs-18 fs-md-24">
                                                        ${{ number_format($requiredAmount, 2) }}
                                                    </h4>
                                                </div>
                                            </div>
                                        </div>

                                        @if($shortfall > 0)
                                            <div class="alert alert-warning border-0 mb-0 mt-3" role="alert">
                                                <iconify-icon icon="material-symbols:info-outline" class="me-2"></iconify-icon>
                                                <small>You need to deposit <strong>${{ number_format($shortfall, 2) }}</strong>
                                                    more</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- What the Fee Covers -->
                        <div class="row justify-content-center mb-3 mb-md-4">
                            <div class="col-12 col-md-10">
                                <div class="card bg-info-subtle border-0">
                                    <div class="card-body p-3 p-md-4">
                                        <h6 class="mb-3 fs-14 fs-md-16">
                                            <iconify-icon icon="iconamoon:shield-duotone" class="me-2"></iconify-icon>
                                            What the $1 Fee Covers
                                        </h6>
                                        <div class="row g-3 text-start">
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <iconify-icon icon="material-symbols:check-circle-outline"
                                                        class="text-success me-2 mt-1 flex-shrink-0"></iconify-icon>
                                                    <small class="text-muted">Identity verification processing</small>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <iconify-icon icon="material-symbols:check-circle-outline"
                                                        class="text-success me-2 mt-1 flex-shrink-0"></iconify-icon>
                                                    <small class="text-muted">Document security checks</small>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <iconify-icon icon="material-symbols:check-circle-outline"
                                                        class="text-success me-2 mt-1 flex-shrink-0"></iconify-icon>
                                                    <small class="text-muted">Fraud prevention systems</small>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <iconify-icon icon="material-symbols:check-circle-outline"
                                                        class="text-success me-2 mt-1 flex-shrink-0"></iconify-icon>
                                                    <small class="text-muted">Compliance & security</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-center gap-2 gap-md-3 flex-wrap mb-3 mb-md-4">
                            <a href="{{ route('wallets.deposit.wallet') }}" class="btn btn-primary btn-lg px-4 px-md-5 py-2 py-md-3">
                                <iconify-icon icon="material-symbols:wallet-outline" class="me-2"></iconify-icon>
                                Deposit Now
                            </a>
                            <a href="{{ route('dashboard') }}"
                                class="btn btn-outline-secondary btn-lg px-4 px-md-5 py-2 py-md-3">
                                <iconify-icon icon="material-symbols:arrow-back" class="me-2"></iconify-icon>
                                Back to Dashboard
                            </a>
                        </div>

                        <!-- Benefits After Verification -->
                        <div class="row justify-content-center">
                            <div class="col-12 col-md-10">
                                <div class="card border-0 bg-success-subtle">
                                    <div class="card-body p-3 p-md-4">
                                        <h6 class="mb-3 fs-14 fs-md-16">
                                            <iconify-icon icon="iconamoon:star-duotone" class="me-2"></iconify-icon>
                                            Benefits After Verification
                                        </h6>
                                        <div class="row g-2 text-start">
                                            <div class="col-12 col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <iconify-icon icon="material-symbols:check-circle"
                                                        class="text-success me-2 fs-18"></iconify-icon>
                                                    <small class="text-muted">Unlimited deposits</small>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <iconify-icon icon="material-symbols:check-circle"
                                                        class="text-success me-2 fs-18"></iconify-icon>
                                                    <small class="text-muted">Higher withdrawal limits</small>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="d-flex align-items-center">
                                                    <iconify-icon icon="material-symbols:check-circle"
                                                        class="text-success me-2 fs-18"></iconify-icon>
                                                    <small class="text-muted">Premium features</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Help Text -->
                        <div class="mt-3 mt-md-4">
                            <small class="text-muted">
                                <iconify-icon icon="material-symbols:info-outline" class="me-1"></iconify-icon>
                                Once you deposit the required amount, you can return here to complete your KYC verification.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <style>
        .avatar-xl {
            width: 4rem;
            height: 4rem;
        }

        @media (min-width: 768px) {
            .avatar-xl {
                width: 5rem;
                height: 5rem;
            }
        }
    </style>
@endsection