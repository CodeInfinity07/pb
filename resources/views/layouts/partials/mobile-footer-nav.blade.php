<!-- Mobile Footer Navigation -->
<div class="mobile-footer-nav d-md-none">
    <div class="footer-nav-container">


        <a href="{{ route('wallets.index') }}" class="footer-nav-item {{ request()->routeIs('wallets.*') ? 'active' : '' }}">
            <span class="footer-nav-icon">
                <iconify-icon icon="material-symbols:wallet-sharp"></iconify-icon>
            </span>
            <span class="footer-nav-text">Wallets</span>
        </a>

        <a href="{{ route('transactions.index') }}" class="footer-nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
            <span class="footer-nav-icon">
                <iconify-icon icon="iconamoon:trend-up-duotone"></iconify-icon>
            </span>
            <span class="footer-nav-text">Transactions</span>
        </a>
        
        <a href="{{ route('dashboard') }}" class="footer-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="footer-nav-icon">
                <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
            </span>
            <span class="footer-nav-text">Home</span>
        </a>

        <a href="{{ route('user.leaderboards.index') }}" class="footer-nav-item {{ request()->routeIs('user.profile') ? 'active' : '' }}">
            <span class="footer-nav-icon">
                <iconify-icon icon="iconoir:leaderboard-star"></iconify-icon>
            </span>
            <span class="footer-nav-text">Leaderboard</span>
        </a>

        <a href="{{ route('support.index') }}" class="footer-nav-item {{ request()->routeIs('support.*') ? 'active' : '' }}">
            <span class="footer-nav-icon">
                <iconify-icon icon="iconamoon:comment-dots-duotone"></iconify-icon>
            </span>
            <span class="footer-nav-text">Support</span>
        </a>
    </div>
</div>