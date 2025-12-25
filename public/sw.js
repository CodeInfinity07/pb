// Service Worker for Laravel PWA
const CACHE_VERSION = 'v1.0.2';
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;
const IMAGE_CACHE = `images-${CACHE_VERSION}`;

// Static assets to cache immediately
const STATIC_ASSETS = [
    '/',
    '/manifest.json',
    '/images/icons/72.png',
    '/images/icons/96.png',
    '/images/icons/144.png',
    '/images/icons/192.png',
    '/images/icons/512.png'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    console.log('[SW] Installing service worker...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .catch(error => {
                console.error('[SW] Cache failed:', error);
            })
            .then(() => {
                console.log('[SW] Skip waiting');
                return self.skipWaiting();
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('[SW] Activating service worker...');
    
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cache => {
                        if (!cache.includes(CACHE_VERSION)) {
                            console.log('[SW] Deleting old cache:', cache);
                            return caches.delete(cache);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] Claiming clients');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip Chrome extensions and browser requests
    if (url.protocol === 'chrome-extension:' || 
        url.protocol === 'moz-extension:' ||
        url.protocol === 'safari-extension:') {
        return;
    }

    // Skip authentication, API, and dynamic routes
    if (url.pathname.includes('_token') ||
        url.pathname.includes('/api/') ||
        url.pathname.includes('/admin/') ||
        url.pathname.includes('/logout') ||
        url.pathname.includes('/login') ||
        url.pathname.includes('/csrf-token')) {
        event.respondWith(fetch(request));
        return;
    }

    // Handle navigation requests (HTML pages)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then(response => {
                    // Cache successful responses
                    if (response.status === 200) {
                        const responseClone = response.clone();
                        caches.open(DYNAMIC_CACHE)
                            .then(cache => cache.put(request, responseClone));
                    }
                    return response;
                })
                .catch(() => {
                    // Network failed - try cache
                    return caches.match(request)
                        .then(cachedResponse => {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            
                            // Return offline page
                            return new Response(
                                `<!DOCTYPE html>
                                <html lang="en">
                                <head>
                                    <meta charset="UTF-8">
                                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                    <title>Offline - Prediction Bot</title>
                                    <style>
                                        body {
                                            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            min-height: 100vh;
                                            margin: 0;
                                            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
                                            color: white;
                                            text-align: center;
                                            padding: 20px;
                                        }
                                        .container {
                                            max-width: 400px;
                                        }
                                        .icon {
                                            width: 80px;
                                            height: 80px;
                                            margin: 0 auto 20px;
                                            opacity: 0.8;
                                        }
                                        h1 {
                                            font-size: 24px;
                                            margin: 0 0 10px;
                                        }
                                        p {
                                            opacity: 0.8;
                                            margin: 0 0 20px;
                                        }
                                        button {
                                            background: white;
                                            color: #1f2937;
                                            border: none;
                                            padding: 12px 24px;
                                            border-radius: 8px;
                                            font-size: 16px;
                                            font-weight: 600;
                                            cursor: pointer;
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="container">
                                        <img src="/images/icons/192.png" alt="App Icon" class="icon">
                                        <h1>You're Offline</h1>
                                        <p>Please check your internet connection and try again.</p>
                                        <button onclick="window.location.reload()">Try Again</button>
                                    </div>
                                </body>
                                </html>`,
                                {
                                    status: 200,
                                    statusText: 'OK',
                                    headers: { 'Content-Type': 'text/html; charset=utf-8' }
                                }
                            );
                        });
                })
        );
        return;
    }

    // Handle static assets (CSS, JS, images, fonts)
    event.respondWith(
        caches.match(request)
            .then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Fetch from network
                return fetch(request)
                    .then(response => {
                        // Only cache successful responses
                        if (!response || response.status !== 200 || response.type === 'error') {
                            return response;
                        }

                        // Determine which cache to use
                        let cacheName = DYNAMIC_CACHE;
                        if (url.pathname.includes('/images/')) {
                            cacheName = IMAGE_CACHE;
                        }

                        // Cache assets
                        if (url.pathname.includes('/images/') ||
                            url.pathname.includes('/fonts/') ||
                            url.pathname.includes('.css') ||
                            url.pathname.includes('.js') ||
                            url.pathname.includes('.woff') ||
                            url.pathname.includes('.woff2')) {
                            
                            const responseClone = response.clone();
                            caches.open(cacheName)
                                .then(cache => cache.put(request, responseClone))
                                .catch(err => console.error('[SW] Cache put failed:', err));
                        }

                        return response;
                    })
                    .catch(() => {
                        // Return placeholder for images
                        if (url.pathname.includes('/images/')) {
                            return caches.match('/images/icons/192.png');
                        }
                        return new Response('Network Error', { 
                            status: 503,
                            statusText: 'Service Unavailable' 
                        });
                    });
            })
    );
});

// Background sync
self.addEventListener('sync', event => {
    console.log('[SW] Background sync:', event.tag);
    
    if (event.tag === 'sync-data') {
        event.waitUntil(
            console.log('[SW] Syncing data...')
        );
    }
});

// Push notifications
self.addEventListener('push', event => {
    console.log('[SW] Push notification received');

    let notification = {
        title: 'New Notification',
        body: 'You have a new notification',
        icon: '/images/icons/192.png',
        badge: '/images/icons/72.png',
        data: { url: '/' }
    };

    if (event.data) {
        try {
            const data = event.data.json();
            notification = {
                title: data.title || notification.title,
                body: data.body || notification.body,
                icon: data.icon || notification.icon,
                badge: data.badge || notification.badge,
                image: data.image,
                data: {
                    url: data.data?.url || data.url || '/',
                    notification_id: data.data?.notification_id,
                    ...data.data
                },
                tag: data.tag || 'general',
                renotify: true,
                requireInteraction: data.requireInteraction || false,
                vibrate: data.vibrate || [200, 100, 200],
                timestamp: Date.now()
            };
        } catch (e) {
            console.error('[SW] Failed to parse push data:', e);
        }
    }

    event.waitUntil(
        self.registration.showNotification(notification.title, notification)
    );
});

// Notification click
self.addEventListener('notificationclick', event => {
    console.log('[SW] Notification clicked');
    
    event.notification.close();

    if (event.action === 'dismiss') {
        return;
    }

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(clientList => {
                // Focus existing window if found
                for (const client of clientList) {
                    if (client.url.includes(urlToOpen) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );

    // Track click (optional)
    if (event.notification.data?.notification_id) {
        fetch('/api/notifications/clicked', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                notification_id: event.notification.data.notification_id,
                action: event.action || 'default'
            })
        }).catch(err => console.error('[SW] Failed to track click:', err));
    }
});

// Notification close
self.addEventListener('notificationclose', event => {
    console.log('[SW] Notification closed');
    
    if (event.notification.data?.notification_id) {
        fetch('/api/notifications/dismissed', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                notification_id: event.notification.data.notification_id
            })
        }).catch(err => console.error('[SW] Failed to track dismissal:', err));
    }
});

console.log('[SW] Service Worker loaded');