/**
 * Push Notification Manager
 * Handles service worker registration, subscription management, and notification handling
 */
class PushNotificationManager {
    constructor(options = {}) {
        this.options = {
            swPath: '/sw.js',
            scope: '/',
            apiBase: '/push',
            debug: false,
            autoInit: true,
            retryAttempts: 3,
            retryDelay: 2000,
            ...options
        };

        this.swRegistration = null;
        this.subscription = null;
        this.publicKey = null;
        this.isSupported = this.checkSupport();
        this.isInitialized = false;
        this.eventListeners = {};

        // Automatically initialize if enabled
        if (this.options.autoInit && this.isSupported) {
            this.init().catch(error => this.log('Auto-init failed:', error));
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INITIALIZATION
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize the push manager
     */
    async init() {
        if (this.isInitialized) {
            this.log('Already initialized');
            return true;
        }

        if (!this.isSupported) {
            this.log('Push notifications not supported');
            this.emit('unsupported');
            return false;
        }

        try {
            this.log('Initializing push manager...');

            // Register service worker
            await this.registerServiceWorker();

            // Get VAPID public key
            await this.getVapidKey();

            // Check existing subscription
            await this.checkSubscription();

            this.isInitialized = true;
            this.emit('initialized');
            this.log('Push manager initialized successfully');

            return true;

        } catch (error) {
            this.log('Initialization failed:', error);
            this.emit('error', error);
            return false;
        }
    }

    /**
     * Check if push notifications are supported
     */
    checkSupport() {
        const hasServiceWorker = 'serviceWorker' in navigator;
        const hasPushManager = 'PushManager' in window;
        const hasNotification = 'Notification' in window;

        this.log('Support check:', {
            serviceWorker: hasServiceWorker,
            pushManager: hasPushManager,
            notification: hasNotification
        });

        return hasServiceWorker && hasPushManager && hasNotification;
    }

    /**
     * Register service worker
     */
    async registerServiceWorker() {
        try {
            this.swRegistration = await navigator.serviceWorker.register(
                this.options.swPath,
                { scope: this.options.scope }
            );

            this.log('Service worker registered:', this.swRegistration.scope);

            // Wait for service worker to be ready
            await navigator.serviceWorker.ready;

            // Listen for service worker messages
            this.setupServiceWorkerMessages();

            return this.swRegistration;

        } catch (error) {
            this.log('Service worker registration failed:', error);
            throw new Error(`Service worker registration failed: ${error.message}`);
        }
    }

    /**
     * Setup service worker message handling
     */
    setupServiceWorkerMessages() {
        navigator.serviceWorker.addEventListener('message', (event) => {
            this.log('Service worker message:', event.data);
            this.emit('serviceWorkerMessage', event.data);
        });
    }

    /**
     * Get VAPID public key from server
     */
    async getVapidKey() {
        try {
            const response = await this.fetchWithRetry(`${this.options.apiBase}/vapid-public-key`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to get VAPID key');
            }

            this.publicKey = data.public_key;
            this.log('VAPID key retrieved:', this.publicKey.substring(0, 20) + '...');

            return this.publicKey;

        } catch (error) {
            this.log('Failed to get VAPID key:', error);
            throw error;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SUBSCRIPTION MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Check current subscription status
     */
    async checkSubscription() {
        if (!this.swRegistration) {
            throw new Error('Service worker not registered');
        }

        try {
            this.subscription = await this.swRegistration.pushManager.getSubscription();
            this.log('Current subscription:', this.subscription ? 'Active' : 'None');
            return this.subscription;
        } catch (error) {
            this.log('Failed to check subscription:', error);
            throw error;
        }
    }

    /**
     * Subscribe to push notifications
     */
    async subscribe() {
        if (!this.swRegistration || !this.publicKey) {
            throw new Error('Service worker not registered or VAPID key missing');
        }

        try {
            this.log('Starting subscription process...');

            // Request notification permission
            const permission = await this.requestPermission();
            if (permission !== 'granted') {
                throw new Error('Notification permission denied');
            }

            // Create push subscription
            this.subscription = await this.swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.publicKey)
            });

            this.log('Push subscription created');

            // Send subscription to server
            const result = await this.sendSubscriptionToServer();

            this.emit('subscribed', {
                subscription: this.subscription,
                result: result
            });

            this.log('Successfully subscribed to push notifications');
            return result;

        } catch (error) {
            this.log('Subscription failed:', error);
            this.emit('subscriptionError', error);
            throw error;
        }
    }

    /**
     * Unsubscribe from push notifications
     */
    async unsubscribe() {
        if (!this.subscription) {
            this.log('No active subscription to unsubscribe from');
            return { success: true, message: 'Not subscribed' };
        }

        try {
            this.log('Starting unsubscribe process...');

            // Store endpoint for server removal
            const endpoint = this.subscription.endpoint;

            // Unsubscribe from push manager
            const unsubscribed = await this.subscription.unsubscribe();

            if (unsubscribed) {
                // Remove from server
                const result = await this.removeSubscriptionFromServer(endpoint);
                this.subscription = null;

                this.emit('unsubscribed', result);
                this.log('Successfully unsubscribed from push notifications');
                return result;
            } else {
                throw new Error('Failed to unsubscribe from push manager');
            }

        } catch (error) {
            this.log('Unsubscribe failed:', error);
            this.emit('unsubscribeError', error);
            throw error;
        }
    }

    /**
     * Send subscription to server
     */
    async sendSubscriptionToServer() {
        try {
            const response = await this.fetchWithAuth(`${this.options.apiBase}/subscribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.subscription.toJSON())
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Server subscription failed');
            }

            return result;

        } catch (error) {
            this.log('Failed to send subscription to server:', error);
            throw error;
        }
    }

    /**
     * Remove subscription from server
     */
    async removeSubscriptionFromServer(endpoint) {
        try {
            const response = await this.fetchWithAuth(`${this.options.apiBase}/unsubscribe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ endpoint })
            });

            const result = await response.json();
            return result;

        } catch (error) {
            this.log('Failed to remove subscription from server:', error);
            throw error;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATION MANAGEMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Request notification permission
     */
    async requestPermission() {
        if (!('Notification' in window)) {
            throw new Error('Notifications not supported');
        }

        const permission = await Notification.requestPermission();
        this.log('Notification permission:', permission);

        this.emit('permissionChanged', permission);
        return permission;
    }

    /**
     * Get current notification permission
     */
    getPermission() {
        return 'Notification' in window ? Notification.permission : 'unsupported';
    }

    /**
     * Send test notification
     */
    async sendTestNotification(data = {}) {
        try {
            const response = await this.fetchWithAuth(`${this.options.apiBase}/test`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.emit('testNotificationSent', result);
                this.log('Test notification sent successfully');
            } else {
                throw new Error(result.message || 'Test notification failed');
            }

            return result;

        } catch (error) {
            this.log('Failed to send test notification:', error);
            this.emit('testNotificationError', error);
            throw error;
        }
    }

    /**
     * Get user's subscriptions from server
     */
    async getSubscriptions() {
        try {
            const response = await this.fetchWithAuth(`${this.options.apiBase}/subscriptions`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Failed to get subscriptions');
            }

            return result;

        } catch (error) {
            this.log('Failed to get subscriptions:', error);
            throw error;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UTILITY METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Check if user is currently subscribed
     */
    isSubscribed() {
        return !!this.subscription;
    }

    /**
     * Get subscription status object
     */
    getStatus() {
        return {
            supported: this.isSupported,
            initialized: this.isInitialized,
            subscribed: this.isSubscribed(),
            permission: this.getPermission(),
            subscription: this.subscription,
            publicKey: this.publicKey
        };
    }

    /**
     * Convert VAPID key to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    /**
     * Make authenticated fetch request
     */
    async fetchWithAuth(url, options = {}) {
        const token = this.getAuthToken();
        const csrfToken = this.getCsrfToken();

        const headers = {
            'Accept': 'application/json',
            ...(options.headers || {})
        };

        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        return this.fetchWithRetry(url, {
            ...options,
            headers
        });
    }

    /**
     * Fetch with retry logic
     */
    async fetchWithRetry(url, options = {}, attempt = 1) {
        try {
            const response = await fetch(url, options);

            if (!response.ok) {
                if (response.status >= 500 && attempt < this.options.retryAttempts) {
                    this.log(`Request failed (${response.status}), retrying... (${attempt}/${this.options.retryAttempts})`);
                    await this.delay(this.options.retryDelay * attempt);
                    return this.fetchWithRetry(url, options, attempt + 1);
                }
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response;

        } catch (error) {
            if (attempt < this.options.retryAttempts && (
                error.name === 'TypeError' ||
                error.message.includes('Failed to fetch')
            )) {
                this.log(`Network error, retrying... (${attempt}/${this.options.retryAttempts})`);
                await this.delay(this.options.retryDelay * attempt);
                return this.fetchWithRetry(url, options, attempt + 1);
            }
            throw error;
        }
    }

    /**
     * Get authentication token
     */
    getAuthToken() {
        return localStorage.getItem('auth_token') ||
               sessionStorage.getItem('auth_token') ||
               document.querySelector('meta[name="auth-token"]')?.getAttribute('content') ||
               document.querySelector('input[name="_token"]')?.value;
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /**
     * Delay helper
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Debug logging
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[PushManager]', ...args);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT SYSTEM
    |--------------------------------------------------------------------------
    */

    /**
     * Add event listener
     */
    on(event, callback) {
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (!this.eventListeners[event]) return;

        const index = this.eventListeners[event].indexOf(callback);
        if (index > -1) {
            this.eventListeners[event].splice(index, 1);
        }
    }

    /**
     * Emit event
     */
    emit(event, data = null) {
        if (!this.eventListeners[event]) return;

        this.eventListeners[event].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                this.log('Event callback error:', error);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC METHODS
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new instance with default options
     */
    static create(options = {}) {
        return new PushNotificationManager(options);
    }

    /**
     * Quick setup for common use cases
     */
    static async quickSetup(options = {}) {
        const manager = new PushNotificationManager({
            debug: true,
            autoInit: true,
            ...options
        });

        // Auto-setup event listeners
        manager.on('initialized', () => {
            console.log('✅ Push notifications initialized');
        });

        manager.on('subscribed', (data) => {
            console.log('✅ Subscribed to push notifications:', data);
        });

        manager.on('unsubscribed', (data) => {
            console.log('✅ Unsubscribed from push notifications:', data);
        });

        manager.on('error', (error) => {
            console.error('❌ Push notification error:', error);
        });

        // Wait for initialization
        await manager.init();

        return manager;
    }
}

// Auto-initialize if in browser environment
if (typeof window !== 'undefined') {
    window.PushNotificationManager = PushNotificationManager;

    // Auto-create global instance
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.pushManager) {
            window.pushManager = new PushNotificationManager({
                debug: window.location.hostname === 'localhost'
            });
        }
    });
}