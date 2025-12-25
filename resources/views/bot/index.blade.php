@extends('layouts.vertical', ['title' => 'Bot', 'subTitle' => 'Tools'])

@section('content')

<div class="row">
    <div class="col-12">
        <!-- Game Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title d-flex align-items-center mb-0">
                    <iconify-icon icon="material-symbols:info" class="me-2"></iconify-icon>
                    Game Info
                </h4>
            </div>
            <div class="card-body">
                <div class="text-sm">
                    <!-- URL and Status -->
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 border-bottom border-light pb-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-medium">1 -</span>
                            <a
                                href="https://winlottery9.com/"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-primary text-decoration-none"
                            >
                                https://winlottery9.com/
                            </a>
                        </div>

                        <span class="badge bg-success-subtle text-success d-inline-flex align-items-center">
                            <span class="badge-dot bg-success rounded-circle me-2" style="width: 8px; height: 8px;"></span>
                            Active
                        </span>
                    </div>

                    <!-- Invite Code Row -->
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <span class="fw-medium text-body">Invite Code:</span>
                            <span class="bg-light-subtle px-3 py-2 rounded text-sm font-monospace fw-semibold user-select-all">
                                700962871184
                            </span>
                        </div>

                        <button
                            id="copyInviteBtn"
                            onclick="copyInviteCode()"
                            class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
                            title="Copy invite code"
                        >
                            <iconify-icon icon="iconamoon:copy-duotone" class="fs-16" id="copyIcon"></iconify-icon>
                            <span id="copyText">Copy</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Selection -->
        <div class="card">
            <div class="card-header">
                <h4 class="card-title d-flex align-items-center mb-0">
                    <iconify-icon icon="material-symbols:arrow-selector-tool-outline-rounded" class="me-2"></iconify-icon>
                    Select a Game
                </h4>
            </div>
            <div class="card-body p-0">
                @php
                    $games = [
                        [
                            'name' => 'Color Trading',
                            'description' => 'Predict Red, Green, or Violet and win!',
                            'href' => '/bot/color-trading',
                            'image' => 'color-trading.png',
                            'disabled' => false
                        ],
                        [
                            'name' => 'Aviator',
                            'description' => 'Watch the plane fly, cash out before it crashes!',
                            'href' => '/bot/aviator',
                            'image' => 'aviator.png',
                            'disabled' => true
                        ]
                    ];
                @endphp

                @if(count($games) === 0)
                <div class="text-center py-5">
                    <iconify-icon icon="material-symbols:account-balance-wallet" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h5 class="fw-medium text-muted mb-2">No Games Found</h5>
                    <p class="text-muted small">Currently, there are no available games.</p>
                </div>
                @else
                <div class="d-flex justify-content-center">
                    <div class="w-100" style="max-width: 70%;">
                        <div class="row g-4 p-4">
                            @foreach($games as $game)
                            <div class="col-md-6">
                                <div class="game-card position-relative rounded-3 shadow-sm p-4 {{ $game['disabled'] ? 'disabled' : '' }}"
                                     onclick="{{ !$game['disabled'] ? "window.location.href='" . $game['href'] . "'" : '' }}"
                                     style="cursor: {{ $game['disabled'] ? 'not-allowed' : 'pointer' }};">
                                    
                                    <!-- Coming Soon Overlay -->
                                    @if($game['disabled'])
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center rounded-3 coming-soon-overlay">
                                        <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill">
                                            Coming Soon
                                        </span>
                                    </div>
                                    @endif

                                    <div class="text-center">
                                        <div class="game-image-container mb-3">
                                            <img
                                                src="{{ asset('images/games/' . $game['image']) }}"
                                                alt="{{ $game['name'] }}"
                                                class="img-fluid rounded-3 bg-white"
                                                style="width: 100%; aspect-ratio: 1; object-fit: contain;"
                                            />
                                        </div>
                                        <h5 class="fw-semibold mb-2">{{ $game['name'] }}</h5>
                                        <p class="text-muted small mb-0">{{ $game['description'] }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
let copyTimeout;

function copyInviteCode() {
    const inviteCode = '700962871184';
    const copyBtn = document.getElementById('copyInviteBtn');
    const copyIcon = document.getElementById('copyIcon');
    const copyText = document.getElementById('copyText');
    
    // Clear any existing timeout
    if (copyTimeout) {
        clearTimeout(copyTimeout);
    }
    
    navigator.clipboard.writeText(inviteCode).then(() => {
        // Success - update button appearance
        copyBtn.classList.remove('btn-outline-primary');
        copyBtn.classList.add('btn-success');
        copyIcon.setAttribute('icon', 'iconamoon:check-duotone');
        copyText.textContent = 'Copied!';
        
        showAlert('Invite code copied!', 'success');
        
        // Reset after 2 seconds
        copyTimeout = setTimeout(() => {
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-outline-primary');
            copyIcon.setAttribute('icon', 'iconamoon:copy-duotone');
            copyText.textContent = 'Copy';
        }, 2000);
        
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = inviteCode;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        // Update button appearance
        copyBtn.classList.remove('btn-outline-primary');
        copyBtn.classList.add('btn-success');
        copyIcon.setAttribute('icon', 'iconamoon:check-duotone');
        copyText.textContent = 'Copied!';
        
        showAlert('Invite code copied!', 'success');
        
        // Reset after 2 seconds
        copyTimeout = setTimeout(() => {
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-outline-primary');
            copyIcon.setAttribute('icon', 'iconamoon:copy-duotone');
            copyText.textContent = 'Copy';
        }, 2000);
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
</script>

<style>
/* Game card styling */
.game-card {
    background: #fff;
    border: 1px solid #e3e6f0;
    transition: all 0.3s ease;
    position: relative;
}

.game-card:not(.disabled):hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    border-color: rgba(var(--bs-primary-rgb), 0.3);
}

.game-card.disabled {
    opacity: 0.6;
}

/* Coming soon overlay */
.coming-soon-overlay {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 10;
}

/* Dark mode styles */
[data-bs-theme="dark"] .game-card {
    background: var(--bs-dark);
    border-color: var(--bs-gray-700);
}

[data-bs-theme="dark"] .game-card:not(.disabled):hover {
    border-color: rgba(var(--bs-primary-rgb), 0.5);
}

/* Badge dot styling */
.badge-dot {
    display: inline-block;
}

/* Font size utilities */
.fs-16 {
    font-size: 1rem;
}

/* Game image container */
.game-image-container {
    overflow: hidden;
    border-radius: 0.75rem;
}

/* Light subtle background */
.bg-light-subtle {
    background-color: #f8f9fa !important;
}

[data-bs-theme="dark"] .bg-light-subtle {
    background-color: var(--bs-gray-800) !important;
}

/* User select utilities */
.user-select-all {
    user-select: all;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .game-card {
        padding: 1rem !important;
    }
    
    .d-flex.gap-3 {
        gap: 1rem !important;
    }
    
    .d-flex.flex-column.flex-sm-row {
        flex-direction: column !important;
        align-items: stretch !important;
    }
    
    .d-flex.align-items-center.justify-content-between {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }
    
    /* Full width on mobile */
    .w-100[style*="max-width"] {
        max-width: 100% !important;
    }
}

@media (max-width: 576px) {
    .game-card h5 {
        font-size: 1.1rem;
    }
    
    .game-card p {
        font-size: 0.875rem;
    }
    
    .card-body .text-sm {
        font-size: 0.875rem;
    }
}

/* Button transitions */
.btn {
    transition: all 0.2s ease;
}

/* Alert positioning */
.alert.position-fixed {
    z-index: 1050;
}

/* Ensure proper spacing */
.gap-2 {
    gap: 0.5rem !important;
}

.gap-3 {
    gap: 1rem !important;
}

.gap-4 {
    gap: 1.5rem !important;
}
</style>
@endsection