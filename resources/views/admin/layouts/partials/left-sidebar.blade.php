<!-- ADMIN Sidebar - Styled like User Sidebar -->

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

            <li class="menu-title">Dashboard</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols-light:overview-key-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Overview </span>
                </a>
            </li>

            <li class="menu-title">Reports</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.analytics') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="hugeicons:analytics-03"></iconify-icon>
                    </span>
                    <span class="nav-text"> Analytics </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.reports.login.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="icon-park-outline:sales-report"></iconify-icon>
                    </span>
                    <span class="nav-text"> Logins </span>
                </a>
            </li>

            <li class="menu-title">User Management</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.users.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:users-group-two-rounded-linear"></iconify-icon>
                    </span>
                    <span class="nav-text"> All Users </span>
                    <span class="badge bg-primary badge-pill text-end">{{ \App\Models\User::count() }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.users.create') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="tabler:users-plus"></iconify-icon>
                    </span>
                    <span class="nav-text"> Add New User </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.staff.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:shield-user-linear"></iconify-icon>
                    </span>
                    <span class="nav-text"> Staff Management </span>
                    <span class="badge bg-info badge-pill text-end">{{ \App\Models\User::staff()->count() }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.blocked-users.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="mage:user-cross"></iconify-icon>
                    </span>
                    <span class="nav-text"> Blocked Users </span>
                    <span
                        class="badge bg-danger badge-pill text-end">{{ \App\Models\User::where('status', 'blocked')->count() }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.kyc.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="arcticons:laokyc"></iconify-icon>
                    </span>
                    <span class="nav-text"> KYC Management </span>
                    @php
                        $pendingKyc = \App\Models\User::whereHas('profile', function ($q) {
                            $q->where('kyc_status', 'pending');
                        })->count();
                    @endphp
                    @if($pendingKyc > 0)
                        <span class="badge bg-warning badge-pill text-end">{{ $pendingKyc }}</span>
                    @endif
                </a>
            </li>

            <li class="menu-title">Financial Management</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.finance.transactions.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:invoice-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> All Transactions </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.investment.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:package-2-sharp"></iconify-icon>
                    </span>
                    <span class="nav-text"> Packages & Plans </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.finance.withdrawals.pending') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:clock-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Pending Approvals </span>
                    @php
                        $pendingTransactions = \App\Models\Transaction::where('status', 'pending')
                            ->where('type', 'withdrawal')
                            ->count();
                    @endphp
                    <span class="badge bg-danger badge-pill text-end">{{ $pendingTransactions }}</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.finance.wallets.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:wallet-sharp"></iconify-icon>
                    </span>
                    <span class="nav-text"> Wallet Management </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.finance.cryptocurrencies.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:send-money-rounded"></iconify-icon>
                    </span>
                    <span class="nav-text"> Currency Management </span>
                </a>
            </li>

            <li class="menu-title">System Management</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.settings.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:settings-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> System Settings </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.email-settings.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:email-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Email Settings </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.notifications.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:notification-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Notifications </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.maintenance.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="ix:maintenance"></iconify-icon>
                    </span>
                    <span class="nav-text"> Maintenance Mode </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:backup-rounded"></iconify-icon>
                    </span>
                    <span class="nav-text"> Backup & Restore </span>
                </a>
            </li>

            <li class="menu-title">CRM System</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.crm.dashboard') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="cib:civicrm"></iconify-icon>
                    </span>
                    <span class="nav-text"> CRM Dashboard </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.crm.leads.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="simple-icons:googleads"></iconify-icon>
                    </span>
                    <span class="nav-text"> All Leads </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.crm.forms.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:forms-add-on"></iconify-icon>
                    </span>
                    <span class="nav-text"> Forms Management </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.crm.followups.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="arcticons:chieffollow"></iconify-icon>
                    </span>
                    <span class="nav-text"> Follow-ups </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.crm.assignments.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:assignment-turned-in-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Assignments </span>
                </a>
            </li>

            <li class="menu-title">Referral System</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.referrals.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="icon-park-twotone:peoples-two"></iconify-icon>
                    </span>
                    <span class="nav-text"> Referral Overview </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.referrals.tree') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:account-tree-outline-rounded"></iconify-icon>
                    </span>
                    <span class="nav-text"> Referral Tree </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.commission.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="carbon:deploy-rules"></iconify-icon>
                    </span>
                    <span class="nav-text"> Commission Rules </span>
                </a>
            </li>

            <li class="menu-title">Communication</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.announcements.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="mingcute:announcement-line"></iconify-icon>
                    </span>
                    <span class="nav-text"> Announcements </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.mass-email.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:attach-email-outline-sharp"></iconify-icon>
                    </span>
                    <span class="nav-text"> Mass Email </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.leaderboards.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconoir:leaderboard-star"></iconify-icon>
                    </span>
                    <span class="nav-text"> Leaderboards </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.push.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="emojione-monotone:pushpin"></iconify-icon>
                    </span>
                    <span class="nav-text"> Push Notifications </span>
                </a>
            </li>

            <li class="menu-title">Quick Actions</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.impersonation.index') }}" onclick="viewAsUser()">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:eye-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> View as User </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.support.index') }}" onclick="viewAsUser()">
                    <span class="nav-icon">
                        <iconify-icon icon="material-symbols:contact-support-outline-rounded"></iconify-icon>
                    </span>
                    <span class="nav-text"> Support </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.faq.index') }}" onclick="viewAsUser()">
                    <span class="nav-icon">
                        <iconify-icon icon="streamline-ultimate:contact-us-faq"></iconify-icon>
                    </span>
                    <span class="nav-text"> FAQ </span>
                </a>
            </li>

        </ul>
    </div>
</div>

@section('script')
    <script>
        function viewAsUser() {
            // Functionality to switch to user view
            console.log('Switching to user view...');
        }

        function toggleMaintenanceMode() {
            if (confirm('Are you sure you want to toggle maintenance mode?')) {
                // Add AJAX call to toggle maintenance mode
                console.log('Toggling maintenance mode...');
            }
        }

        function clearCache() {
            if (confirm('Are you sure you want to clear the application cache?')) {
                // Add AJAX call to clear cache
                console.log('Clearing cache...');
            }
        }
    </script>
@endsection