<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    @include('layouts.partials/title-meta', ['title' => $title])

    <!-- Viewport -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="{{ config('app.description', 'Laravel PWA Application') }}">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1f2937">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Laravel') }}">

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- App Icons -->
    <link rel="icon" type="image/png" sizes="72x72" href="/images/icons/72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/icons/96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/images/icons/144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/icons/192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/images/icons/512.png">
    <link rel="apple-touch-icon" href="/images/icons/192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="/images/icons/512.png">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @yield('css')
    @include('layouts.partials/head-css')
    @vite(['resources/css/mobile-nav.css'])

    <style>
        /* Preloader Styles */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #000;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease, visibility 0.5s ease;
        }

        #preloader.fade-out {
            opacity: 0;
            visibility: hidden;
        }

        .preloader-content {
            text-align: center;
            color: white;
        }

        .preloader-spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .preloader-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }
        }

        .preloader-text {
            font-size: 18px;
            font-weight: 600;
            margin-top: 15px;
            animation: fadeInOut 1.5s ease-in-out infinite;
        }

        @keyframes fadeInOut {

            0%,
            100% {
                opacity: 0.5;
            }

            50% {
                opacity: 1;
            }
        }

        /* Back Button Styles */
        #back-button {
            transition: all 0.2s ease;
        }

        #back-button:hover {
            background-color: #e5e7eb !important;
            transform: translateX(-2px);
        }

        #back-button:active {
            transform: translateX(0);
        }

        /* PWA Styles */
        #offline-indicator {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
            }

            to {
                transform: translateY(0);
            }
        }

        #install-prompt,
        #push-subscribe-prompt {
            animation: slideUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideUp {
            from {
                transform: translateY(calc(100% + 20px));
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Standalone Mode */
        @media (display-mode: standalone) {
            body {
                overscroll-behavior-y: contain;
            }

            #install-prompt,
            #pwa-install-sidebar-item {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <!-- Preloader -->
    <div id="preloader">
        <div class="preloader-content">
            <img src="/images/icons/192.png" alt="Loading" class="preloader-logo">
            <div class="preloader-spinner"></div>
            <div class="preloader-text">Loading...</div>
        </div>
    </div>

    <div class="wrapper">
        @include('components.impersonation-banner')

        <!-- Offline Indicator -->
        <div id="offline-indicator" class="d-none position-fixed w-100 text-center py-2 text-white"
            style="top: 0; z-index: 9998; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
            <small><strong>⚠️ You're offline</strong> - Some features may be limited</small>
        </div>

        <!-- Push Notification Prompt -->
        <div id="push-subscribe-prompt" class="d-none position-fixed"
            style="bottom: 90px; left: 20px; right: 20px; max-width: 450px; margin: 0 auto; z-index: 9999;">
            <div class="card shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 56px; height: 56px; font-size: 28px; flex-shrink: 0;">
                            🔔
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold">Enable Notifications</h6>
                                <button id="push-close-btn" class="btn-close" style="font-size: 0.75rem;"></button>
                            </div>
                            <p class="mb-3 small text-muted">Stay updated with instant notifications</p>
                            <div class="d-flex gap-2">
                                <button id="push-enable-btn" class="btn btn-primary btn-sm px-3">Enable</button>
                                <button id="push-dismiss-btn" class="btn btn-outline-secondary btn-sm px-3">Not
                                    Now</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PWA Install Prompt -->
        <div id="install-prompt" class="d-none position-fixed"
            style="bottom: 20px; left: 20px; right: 20px; max-width: 450px; margin: 0 auto; z-index: 9999;">
            <div class="card shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3">
                        <img src="/images/icons/192.png" alt="App Icon" width="56" height="56"
                            style="border-radius: 12px; flex-shrink: 0;">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0 fw-bold">Install {{ config('app.name') }}</h6>
                                <button id="close-btn" class="btn-close" style="font-size: 0.75rem;"></button>
                            </div>
                            <p class="mb-3 small text-muted" id="install-message">Install our app for faster access</p>

                            <div id="install-instructions" class="d-none mb-3 p-3 bg-light rounded">
                                <p class="small fw-semibold mb-2">Installation Steps:</p>
                                <ol class="small mb-0 ps-3" style="line-height: 1.8;">
                                    <li id="step-1"></li>
                                    <li id="step-2"></li>
                                    <li id="step-3"></li>
                                </ol>
                            </div>

                            <div class="d-flex gap-2">
                                <button id="install-btn" class="btn btn-primary btn-sm px-3">Install</button>
                                <button id="dismiss-btn" class="btn btn-outline-secondary btn-sm px-3">Not Now</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.partials/topbar')
        @include('layouts.partials/left-sidebar')

        <div class="page-content">
            <div class="container-xxl">
                {{-- @include("layouts.partials/page-title", ['title' => $title, 'subTitle' => $subTitle]) --}}

                <!-- Back Button -->
                <div id="back-button-container" class="mb-3" style="display: none;">
                    <button id="back-button" class="btn btn-light d-inline-flex align-items-center gap-2 shadow-sm">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Back</span>
                    </button>
                </div>

                @yield('content')
            </div>
            @include('layouts.partials/mobile-footer-nav')
            @include("layouts.partials/footer")
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @include("layouts.partials/right-sidebar")
    @include("layouts.partials/footer-scripts")
    @yield('vite_scripts')
    @vite(['resources/js/notifications.js', 'resources/js/push-notifications.js', 'resources/js/app.js'])

    <script>
        (function () {
            'use strict';

            // ================================================================
            // PRELOADER
            // ================================================================
            window.addEventListener('load', function () {
                setTimeout(function () {
                    const preloader = document.getElementById('preloader');
                    if (preloader) {
                        preloader.classList.add('fade-out');
                        setTimeout(() => preloader.remove(), 500);
                    }
                }, 300);
            });

            // Fallback
            setTimeout(function () {
                const preloader = document.getElementById('preloader');
                if (preloader) {
                    preloader.classList.add('fade-out');
                    setTimeout(() => preloader.remove(), 500);
                }
            }, 3000);

            // ================================================================
            // BACK BUTTON WITH SIDEBAR CLOSE
            // ================================================================
            document.addEventListener('DOMContentLoaded', function () {
                const currentPath = window.location.pathname;
                const excludedPaths = ['/', '/dashboard', '/home'];
                const backButtonContainer = document.getElementById('back-button-container');
                const backButton = document.getElementById('back-button');

                if (!backButtonContainer || !backButton) return;

                // Show back button if not on excluded paths
                if (!excludedPaths.includes(currentPath)) {
                    backButtonContainer.style.display = 'block';
                }

                // Back button click handler
                backButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('[BACK] Button clicked, closing sidebar...');

                    // Method 1: Remove all possible sidebar-related classes from body
                    const body = document.body;
                    const classesToRemove = [
                        'sidebar-enable',
                        'sidebar-open',
                        'sidebar-show',
                        'menu-open',
                        'vertical-sidebar-enable',
                        'sidebar-toggle'
                    ];

                    classesToRemove.forEach(cls => {
                        if (body.classList.contains(cls)) {
                            console.log('[BACK] Removing class:', cls);
                            body.classList.remove(cls);
                        }
                    });

                    // Method 2: Remove show class from sidebar
                    const sidebars = document.querySelectorAll('.left-sidebar, .sidebar, #sidebar, [data-sidebar]');
                    sidebars.forEach(sidebar => {
                        sidebar.classList.remove('show');
                    });

                    // Method 3: Click on overlay to trigger sidebar close
                    const overlay = document.querySelector('.sidebar-overlay, .overlay, [data-overlay]');
                    if (overlay) {
                        console.log('[BACK] Clicking overlay');
                        overlay.click();
                        setTimeout(() => overlay.remove(), 50);
                    }

                    // Method 4: Try to trigger close button
                    const closeBtn = document.querySelector('.sidebar-close, .close-sidebar, [data-sidebar-close]');
                    if (closeBtn) {
                        console.log('[BACK] Clicking close button');
                        closeBtn.click();
                    }

                    // Method 5: Try jQuery method if available
                    if (typeof $ !== 'undefined') {
                        $('body').removeClass('sidebar-enable sidebar-open menu-open');
                        $('.sidebar-overlay').fadeOut(100, function () {
                            $(this).remove();
                        });
                    }

                    // Method 6: Dispatch custom event for sidebar close
                    document.dispatchEvent(new Event('sidebar-close'));
                    window.dispatchEvent(new Event('sidebar-close'));

                    // Method 7: Try to call global sidebar close function if exists
                    if (typeof window.toggleSidebar === 'function') {
                        console.log('[BACK] Calling window.toggleSidebar()');
                        window.toggleSidebar();
                    }
                    if (typeof window.closeSidebar === 'function') {
                        console.log('[BACK] Calling window.closeSidebar()');
                        window.closeSidebar();
                    }

                    // Wait a bit then navigate
                    setTimeout(function () {
                        console.log('[BACK] Navigating back...');
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            window.location.href = '/dashboard';
                        }
                    }, 150);
                });
            });

            // ================================================================
            // PWA FUNCTIONALITY
            // ================================================================
            $(document).ready(function () {
                console.log('[PWA] Initializing...');

                const CONFIG = {
                    dismissDuration: 7 * 24 * 60 * 60 * 1000,
                    showDelay: 3000,
                    storageKey: 'pwa-install-dismissed',
                    eventWaitTime: 8000
                };

                const userAgent = navigator.userAgent.toLowerCase();
                const isIOS = /iphone|ipad|ipod/.test(userAgent);
                const isSafari = /safari/.test(userAgent) && !/chrome/.test(userAgent);
                const isChrome = /chrome/.test(userAgent) && !/edge/.test(userAgent);
                const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                    window.navigator.standalone === true;
                const isSecure = window.location.protocol === 'https:' ||
                    window.location.hostname === 'localhost';

                const elements = {
                    installPrompt: $('#install-prompt'),
                    installBtn: $('#install-btn'),
                    dismissBtn: $('#dismiss-btn'),
                    closeBtn: $('#close-btn'),
                    installMessage: $('#install-message'),
                    installInstructions: $('#install-instructions'),
                    sidebarItem: $('#pwa-install-sidebar-item'),
                    sidebarBtn: $('#pwa-install-sidebar-btn'),
                    offlineIndicator: $('#offline-indicator')
                };

                let deferredPrompt = null;
                let installMode = 'waiting';

                if (isStandalone) {
                    console.log('[PWA] Already installed');
                    elements.sidebarItem?.hide();
                }

                // Service Worker
                if ('serviceWorker' in navigator && isSecure) {
                    navigator.serviceWorker.register('/sw.js')
                        .then(reg => console.log('[PWA] SW registered:', reg.scope))
                        .catch(err => console.error('[PWA] SW failed:', err));
                }

                // Online/Offline
                function updateOnlineStatus() {
                    elements.offlineIndicator.toggleClass('d-none', navigator.onLine);
                }
                window.addEventListener('online', updateOnlineStatus);
                window.addEventListener('offline', updateOnlineStatus);
                updateOnlineStatus();

                // Manual Instructions
                function showManualInstructions() {
                    installMode = 'manual';
                    elements.installInstructions.removeClass('d-none');
                    elements.installBtn.text('Got It');

                    if (isIOS || isSafari) {
                        elements.installMessage.text('Install this app:');
                        $('#step-1').html('Tap the <strong>Share</strong> button');
                        $('#step-2').html('Tap <strong>"Add to Home Screen"</strong>');
                        $('#step-3').html('Tap <strong>"Add"</strong>');
                    } else {
                        elements.installMessage.text('To install:');
                        $('#step-1').html('Tap menu <strong>⋮</strong>');
                        $('#step-2').html('Select <strong>"Install app"</strong>');
                        $('#step-3').html('Tap <strong>"Install"</strong>');
                    }
                }

                // Auto Install
                function setupAutoInstall() {
                    installMode = 'auto';
                    elements.installMessage.text('Install for faster access');
                    elements.installInstructions.addClass('d-none');
                    elements.installBtn.text('Install App');
                }

                // Install Click
                elements.installBtn.on('click', function () {
                    if (installMode === 'manual') {
                        elements.installPrompt.addClass('d-none');
                        localStorage.setItem(CONFIG.storageKey, Date.now() + CONFIG.dismissDuration);
                        return;
                    }

                    if (deferredPrompt) {
                        deferredPrompt.prompt();
                        deferredPrompt.userChoice.then(result => {
                            if (result.outcome === 'accepted') {
                                elements.installPrompt.addClass('d-none');
                                elements.sidebarItem?.hide();
                            }
                            deferredPrompt = null;
                        });
                    } else {
                        showManualInstructions();
                    }
                });

                // Dismiss
                elements.dismissBtn.add(elements.closeBtn).on('click', function () {
                    elements.installPrompt.addClass('d-none');
                    localStorage.setItem(CONFIG.storageKey, Date.now() + CONFIG.dismissDuration);
                });

                // beforeinstallprompt
                window.addEventListener('beforeinstallprompt', (e) => {
                    console.log('[PWA] beforeinstallprompt');
                    e.preventDefault();
                    deferredPrompt = e;
                    setupAutoInstall();

                    setTimeout(() => {
                        const dismissed = localStorage.getItem(CONFIG.storageKey);
                        if (!dismissed || Date.now() > parseInt(dismissed)) {
                            elements.installPrompt.removeClass('d-none');
                        }
                    }, CONFIG.showDelay);
                });

                // App Installed
                window.addEventListener('appinstalled', () => {
                    console.log('[PWA] Installed');
                    elements.installPrompt.addClass('d-none');
                    elements.sidebarItem?.hide();
                    localStorage.removeItem(CONFIG.storageKey);
                });

                // Initialize
                if (!isStandalone) {
                    if (isIOS || isSafari) {
                        elements.sidebarItem?.show();
                        showManualInstructions();
                    } else {
                        elements.sidebarItem?.show();
                        setTimeout(() => {
                            if (!deferredPrompt) showManualInstructions();
                        }, CONFIG.eventWaitTime);
                    }
                }

                // Sidebar Button
                elements.sidebarBtn?.on('click', function (e) {
                    e.preventDefault();
                    if (deferredPrompt && installMode === 'auto') {
                        deferredPrompt.prompt();
                    } else {
                        if (installMode === 'waiting') showManualInstructions();
                        elements.installPrompt.removeClass('d-none');
                    }
                });

                // ================================================================
                // PUSH NOTIFICATIONS
                // ================================================================
                const PUSH_CONFIG = {
                    dismissDuration: 30 * 24 * 60 * 60 * 1000,
                    showDelay: 8000,
                    storageKey: 'push-notification-dismissed'
                };

                const pushElements = {
                    prompt: $('#push-subscribe-prompt'),
                    enableBtn: $('#push-enable-btn'),
                    dismissBtn: $('#push-dismiss-btn'),
                    closeBtn: $('#push-close-btn')
                };

                if ('Notification' in window && 'serviceWorker' in navigator) {
                    const permission = Notification.permission;

                    if (permission === 'default') {
                        const dismissed = localStorage.getItem(PUSH_CONFIG.storageKey);
                        if (!dismissed || Date.now() > parseInt(dismissed)) {
                            setTimeout(() => pushElements.prompt.removeClass('d-none'), PUSH_CONFIG.showDelay);
                        }
                    }

                    pushElements.enableBtn.on('click', async function () {
                        try {
                            $(this).prop('disabled', true).text('Enabling...');

                            const permission = await Notification.requestPermission();
                            if (permission !== 'granted') throw new Error('Permission denied');

                            const registration = await navigator.serviceWorker.ready;

                            const vapidResponse = await fetch('/push/vapid-public-key');
                            const vapidData = await vapidResponse.json();
                            if (!vapidData.success) throw new Error('Failed to get VAPID key');

                            const urlBase64ToUint8Array = (base64String) => {
                                const padding = '='.repeat((4 - base64String.length % 4) % 4);
                                const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                                const rawData = atob(base64);
                                const outputArray = new Uint8Array(rawData.length);
                                for (let i = 0; i < rawData.length; ++i) {
                                    outputArray[i] = rawData.charCodeAt(i);
                                }
                                return outputArray;
                            };

                            const subscription = await registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(vapidData.public_key)
                            });

                            const csrfToken = $('meta[name="csrf-token"]').attr('content');
                            const response = await fetch('/push/subscribe', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify(subscription.toJSON())
                            });

                            const result = await response.json();
                            if (!result.success) throw new Error(result.message);

                            pushElements.prompt.addClass('d-none');
                            alert('✅ Notifications enabled!');

                        } catch (error) {
                            console.error('[PUSH] Error:', error);
                            alert('Failed: ' + error.message);
                        } finally {
                            pushElements.enableBtn.prop('disabled', false).text('Enable');
                        }
                    });

                    pushElements.dismissBtn.add(pushElements.closeBtn).on('click', function () {
                        pushElements.prompt.addClass('d-none');
                        localStorage.setItem(PUSH_CONFIG.storageKey, Date.now() + PUSH_CONFIG.dismissDuration);
                    });
                }

                console.log('[PWA] Initialization complete');
            });
        })();
    </script>
</body>

</html>