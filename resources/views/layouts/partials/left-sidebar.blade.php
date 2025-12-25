<!-- User Sidebar -->

<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="#" class="logo-dark">
            <img src="/images/logo-dark.png" class="logo-sm" alt="logo sm" />
        </a>

        <a href="#" class="logo-light">
            <img src="/images/logo-light.png" class="logo-sm" alt="logo sm" />
        </a>
    </div>

    <!-- Menu Toggle Button (sm-hover) -->
    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <iconify-icon icon="iconamoon:arrow-left-4-square-duotone" class="button-sm-hover-icon"></iconify-icon>
    </button>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">
            <li class="menu-title">General</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Home </span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link" href="{{ route('kyc.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:shield-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> KYC Verification </span>
                    @php
                        $kycStatus = auth()->user()->profile->kyc_status ?? 0; // Assuming user has kyc status
                    @endphp

                    @if($kycStatus == 'verified')
                        <span class="badge bg-success badge-pill text-end">Verified</span>
                    @elseif($kycStatus == 'pending' || $kycStatus == 'session_created')
                        <span class="badge bg-warning badge-pill text-end">Pending</span>
                    @elseif($kycStatus == 'rejected')
                        <span class="badge bg-danger badge-pill text-end">Required</span>
                    @elseif($kycStatus == 'under_review' || $kycStatus == 'submitted')
                        <span class="badge bg-info badge-pill text-end">Submitted</span>
                    @endif
                </a>
            </li>

            <li class="menu-title">Financial</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('transactions.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:trend-up-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Transactions </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('wallets.deposit.wallet') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="ph:hand-deposit-bold"></iconify-icon>
                    </span>
                    <span class="nav-text"> Deposit </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('wallets.withdraw.wallet') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="uil:money-withdrawal"></iconify-icon>
                    </span>
                    <span class="nav-text"> Withdraw </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('wallets.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:wallet-sharp"></iconify-icon>
                    </span>
                    <span class="nav-text"> Wallets </span>
                </a>
            </li>

            <li class="menu-title">Tools & Features</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('bot.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:android"></iconify-icon>
                    </span>
                    <span class="nav-text"> Bot </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('crm.dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="cib:civicrm"></iconify-icon>
                    </span>
                    <span class="nav-text"> CRM </span>
                </a>
            </li>

            <li class="menu-title">Promotions</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.leaderboards.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="carbon:promote"></iconify-icon>
                    </span>
                    <span class="nav-text"> Promotions </span>
                </a>
            </li>

            <li class="menu-title">Team</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('referrals.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="icon-park-twotone:peoples-two"></iconify-icon>
                    </span>
                    <span class="nav-text"> Referrals </span>
                </a>
            </li>

             <li class="nav-item">
                <a class="nav-link" href="{{ route('referrals.create-direct') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="streamline-sharp:user-add-plus"></iconify-icon>
                    </span>
                    <span class="nav-text"> Add Referral </span>
                </a>
            </li>

            <li class="menu-title">Account</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.profile') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:profile-circle-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Profile </span>
                </a>
            </li>

            <li class="menu-title">App</li>

            <li class="nav-item" id="pwa-install-sidebar-item">
                <a class="nav-link" href="javascript:void(0);" id="pwa-install-sidebar-btn">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:install-mobile"></iconify-icon>
                    </span>
                    <span class="nav-text"> Install App </span>
                    <span class="badge bg-primary badge-pill text-end">New</span>
                </a>
            </li>

            <li class="menu-title">Support & Information</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('support.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:comment-dots-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Contact </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.faq.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:question-mark-circle-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> FAQ </span>
                </a>
            </li>

            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}" style="display: inline; width: 100%;">
                    @csrf
                    <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start text-danger"
                        style="cursor: pointer;">
                        <span class="nav-icon">
                            <iconify-icon icon="bx:log-out"></iconify-icon>
                        </span>
                        <span class="nav-text">Logout</span>
                    </button>
                </form>
            </li>
            {{--
            <li class="nav-item">
                <a class="nav-link" href="{{ route('support.news') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:news-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> News </span>
                </a>
            </li> --}}
        </ul>
    </div>
</div>