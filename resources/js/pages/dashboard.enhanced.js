/**
 * Enhanced Dashboard JavaScript - Clean & Simple
 * Handles real-time updates, transaction interactions, and user feedback
 */

class DashboardManager {
    constructor() {
        this.isActive = true;
        this.refreshInterval = null;
        this.refreshRate = 30000; // 30 seconds
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        this.init();
    }

    /**
     * Initialize dashboard functionality
     */
    init() {
        this.bindEventHandlers();
        this.startAutoRefresh();
        this.handleVisibilityChanges();
        
        // Log initialization
        console.log('Dashboard Manager initialized');
    }

    /**
     * Bind all event handlers
     */
    bindEventHandlers() {
        // Referral link copy
        const copyBtn = document.getElementById('copyReferralBtn');
        if (copyBtn) {
            copyBtn.addEventListener('click', () => this.copyReferralLink());
        }

        // Transaction interactions (using event delegation)
        document.addEventListener('click', (e) => {
            // Handle toggle details buttons
            if (e.target.closest('[onclick*="toggleDetails"]')) {
                e.preventDefault();
                const onclick = e.target.closest('[onclick*="toggleDetails"]').getAttribute('onclick');
                const transactionId = onclick.match(/'([^']+)'/)?.[1];
                if (transactionId) this.toggleTransactionDetails(transactionId);
            }
            
            // Handle copy text buttons
            if (e.target.closest('[onclick*="copyText"]')) {
                e.preventDefault();
                const onclick = e.target.closest('[onclick*="copyText"]').getAttribute('onclick');
                const text = onclick.match(/'([^']+)'/)?.[1];
                if (text) this.copyToClipboard(text);
            }

            // Close transaction details when clicking outside
            if (!e.target.closest('.transaction-card')) {
                this.closeAllTransactionDetails();
            }
        });

        // Manual refresh buttons
        document.querySelectorAll('[data-refresh]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const section = btn.dataset.refresh;
                this.refreshSection(section);
            });
        });
    }

    /**
     * Start automatic refresh interval
     */
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            if (this.isActive && document.visibilityState === 'visible') {
                this.refreshDashboard();
            }
        }, this.refreshRate);
    }

    /**
     * Handle page visibility changes
     */
    handleVisibilityChanges() {
        document.addEventListener('visibilitychange', () => {
            this.isActive = !document.hidden;
            if (this.isActive) {
                // Refresh when page becomes visible
                setTimeout(() => this.refreshDashboard(), 1000);
            }
        });

        window.addEventListener('focus', () => {
            this.isActive = true;
            this.refreshDashboard();
        });

        window.addEventListener('blur', () => {
            this.isActive = false;
        });
    }

    /**
     * Refresh entire dashboard
     */
    async refreshDashboard() {
        try {
            await Promise.all([
                this.updateBalances(),
                this.updateRecentTransactions(),
                this.updateReferralStats()
            ]);
            
            this.updateLastRefreshTime();
            console.log('Dashboard refreshed successfully');
        } catch (error) {
            console.error('Dashboard refresh failed:', error);
        }
    }

    /**
     * Refresh specific section
     */
    async refreshSection(section) {
        const btn = document.querySelector(`[data-refresh="${section}"]`);
        
        // Show loading state
        if (btn) {
            const originalContent = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Refreshing...';
            
            try {
                switch (section) {
                    case 'balance':
                        await this.updateBalances();
                        break;
                    case 'transactions':
                        await this.updateRecentTransactions();
                        break;
                    case 'referrals':
                        await this.updateReferralStats();
                        break;
                    default:
                        await this.refreshDashboard();
                }
                
                this.showAlert('Section refreshed successfully', 'success');
            } catch (error) {
                console.error(`Failed to refresh ${section}:`, error);
                this.showAlert('Failed to refresh data', 'danger');
            } finally {
                // Restore button state
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            }
        }
    }

    /**
     * Update balance information
     */
    async updateBalances() {
        try {
            const response = await this.fetchData('/dashboard/api/balance');
            
            if (response) {
                this.updateBalanceDisplay(response);
            }
        } catch (error) {
            console.error('Balance update failed:', error);
        }
    }

    /**
     * Update recent transactions
     */
    async updateRecentTransactions() {
        try {
            const response = await this.fetchData('/dashboard/api/activity?limit=10');
            
            if (response && response.transactions) {
                this.updateTransactionsDisplay(response.transactions);
            }
        } catch (error) {
            console.error('Transactions update failed:', error);
        }
    }

    /**
     * Update referral statistics
     */
    async updateReferralStats() {
        try {
            const response = await this.fetchData('/dashboard/api/referrals');
            
            if (response) {
                this.updateReferralDisplay(response);
            }
        } catch (error) {
            console.error('Referral stats update failed:', error);
        }
    }

    /**
     * Generic fetch data function
     */
    async fetchData(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.csrfToken,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Update balance display elements
     */
    updateBalanceDisplay(data) {
        // Update available balance
        const availableBalance = document.querySelector('[data-balance="available"]');
        if (availableBalance && data.available_balance !== undefined) {
            availableBalance.textContent = `$${this.formatNumber(data.available_balance, 2)}`;
            this.animateUpdate(availableBalance);
        }

        // Update total balance
        const totalBalance = document.querySelector('[data-balance="total"]');
        if (totalBalance && data.total_balance !== undefined) {
            totalBalance.textContent = `Total Balance: $${this.formatNumber(data.total_balance, 2)}`;
        }

        // Update locked balance
        const lockedBalance = document.querySelector('[data-balance="locked"]');
        if (lockedBalance && data.locked_balance !== undefined) {
            lockedBalance.textContent = `$${this.formatNumber(data.locked_balance, 2)}`;
        }
    }

    /**
     * Update transactions display
     */
    updateTransactionsDisplay(transactions) {
        // Update desktop table
        const desktopTable = document.querySelector('.d-none.d-lg-block tbody');
        if (desktopTable) {
            desktopTable.innerHTML = transactions.map(t => this.createDesktopTransactionRow(t)).join('');
            this.animateUpdate(desktopTable);
        }

        // Update mobile cards
        const mobileContainer = document.querySelector('.d-lg-none .row.g-3');
        if (mobileContainer) {
            mobileContainer.innerHTML = transactions.map(t => this.createMobileTransactionCard(t)).join('');
            this.animateUpdate(mobileContainer);
        }
    }

    /**
     * Update referral display
     */
    updateReferralDisplay(data) {
        // Update total referrals
        const totalReferrals = document.querySelector('[data-referral="total"]');
        if (totalReferrals && data.total_referrals !== undefined) {
            totalReferrals.textContent = data.total_referrals;
            this.animateUpdate(totalReferrals);
        }

        // Update referral earnings
        const referralEarnings = document.querySelector('[data-referral="earnings"]');
        if (referralEarnings && data.total_referral_earnings !== undefined) {
            referralEarnings.textContent = `$${this.formatNumber(data.total_referral_earnings, 2)}`;
            this.animateUpdate(referralEarnings);
        }

        // Update pending commissions
        const pendingCommissions = document.querySelector('[data-referral="pending"]');
        if (pendingCommissions && data.pending_commissions !== undefined) {
            pendingCommissions.textContent = `$${this.formatNumber(data.pending_commissions, 2)}`;
            this.animateUpdate(pendingCommissions);
        }
    }

    /**
     * Create desktop transaction row HTML
     */
    createDesktopTransactionRow(transaction) {
        return `
            <tr>
                <td><code class="small">${this.truncateText(transaction.transaction_id, 15)}...</code></td>
                <td><span class="badge bg-${this.getTypeColor(transaction.type)}-subtle text-${this.getTypeColor(transaction.type)} p-1">${this.capitalize(transaction.type)}</span></td>
                <td><strong class="${transaction.type === 'withdrawal' ? 'text-danger' : 'text-success'}">${transaction.type === 'withdrawal' ? '-' : '+'}${transaction.formatted_amount || '$' + this.formatNumber(transaction.amount, 2)}</strong></td>
                <td>${this.formatDate(transaction.created_at)}<small class="text-muted d-block">${this.formatTime(transaction.created_at)}</small></td>
                <td><span class="badge bg-${this.getStatusColor(transaction.status)}-subtle text-${this.getStatusColor(transaction.status)} p-1">${this.capitalize(transaction.status)}</span></td>
            </tr>
        `;
    }

    /**
     * Create mobile transaction card HTML
     */
    createMobileTransactionCard(transaction) {
        return `
            <div class="col-12">
                <div class="card transaction-card border">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex gap-2">
                                <span class="badge bg-${this.getTypeColor(transaction.type)}-subtle text-${this.getTypeColor(transaction.type)}">${this.capitalize(transaction.type)}</span>
                                <span class="badge bg-${this.getStatusColor(transaction.status)}-subtle text-${this.getStatusColor(transaction.status)}">${this.capitalize(transaction.status)}</span>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleDetails('${transaction.id}')">
                                <iconify-icon icon="iconamoon:eye-duotone" id="chevron-${transaction.id}"></iconify-icon>
                            </button>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <h6 class="mb-0 ${transaction.type === 'withdrawal' ? 'text-danger' : 'text-success'}">${transaction.type === 'withdrawal' ? '-' : '+'}${transaction.formatted_amount || '$' + this.formatNumber(transaction.amount, 2)}</h6>
                                <small class="text-muted">${this.formatDateTime(transaction.created_at)}</small>
                            </div>
                            ${this.getTransactionIcon(transaction.type)}
                        </div>
                        <div class="d-flex align-items-center">
                            <code class="small flex-grow-1">${this.truncateText(transaction.transaction_id, 20)}...</code>
                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('${transaction.transaction_id}')">
                                <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                            </button>
                        </div>
                        <div class="collapse mt-3" id="details-${transaction.id}">
                            <div class="border-top pt-3">
                                <div class="row g-2 small">
                                    <div class="col-12">
                                        <div class="text-muted">Full ID</div>
                                        <code class="small">${transaction.transaction_id}</code>
                                    </div>
                                    ${transaction.crypto_address ? `
                                    <div class="col-12">
                                        <div class="text-muted">Address</div>
                                        <div class="d-flex align-items-center">
                                            <code class="small flex-grow-1">${this.truncateText(transaction.crypto_address, 30)}...</code>
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('${transaction.crypto_address}')">
                                                <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                    </div>
                                    ` : ''}
                                    ${transaction.crypto_txid ? `
                                    <div class="col-12">
                                        <div class="text-muted">TxID</div>
                                        <div class="d-flex align-items-center">
                                            <code class="small flex-grow-1">${this.truncateText(transaction.crypto_txid, 30)}...</code>
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyText('${transaction.crypto_txid}')">
                                                <iconify-icon icon="iconamoon:copy-duotone"></iconify-icon>
                                            </button>
                                        </div>
                                    </div>
                                    ` : ''}
                                    ${transaction.processed_at ? `
                                    <div class="col-6">
                                        <div class="text-muted">Processed</div>
                                        <div>${this.formatDateTime(transaction.processed_at)}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Toggle transaction details
     */
    toggleTransactionDetails(transactionId) {
        const detailsElement = document.getElementById(`details-${transactionId}`);
        const chevronElement = document.getElementById(`chevron-${transactionId}`);
        
        if (detailsElement && chevronElement) {
            if (detailsElement.classList.contains('show')) {
                detailsElement.classList.remove('show');
                chevronElement.style.transform = 'rotate(0deg)';
            } else {
                detailsElement.classList.add('show');
                chevronElement.style.transform = 'rotate(180deg)';
            }
        }
    }

    /**
     * Close all transaction details
     */
    closeAllTransactionDetails() {
        document.querySelectorAll('.collapse.show').forEach(element => {
            element.classList.remove('show');
        });
        document.querySelectorAll('[id^="chevron-"]').forEach(chevron => {
            chevron.style.transform = 'rotate(0deg)';
        });
    }

    /**
     * Copy referral link to clipboard
     */
    copyReferralLink() {
        const referralInput = document.getElementById('referralLink');
        if (!referralInput) return;

        referralInput.select();
        referralInput.setSelectionRange(0, 99999);

        this.copyToClipboard(referralInput.value, 'Referral link copied to clipboard!');
    }

    /**
     * Copy text to clipboard with feedback
     */
    async copyToClipboard(text, customMessage = 'Copied to clipboard!') {
        try {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'absolute';
                textArea.style.left = '-9999px';
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
            }
            
            this.showAlert(customMessage, 'success');
        } catch (error) {
            console.error('Copy failed:', error);
            this.showAlert('Failed to copy to clipboard', 'danger');
        }
    }

    /**
     * Show alert notification
     */
    showAlert(message, type = 'info', duration = 4000) {
        const alertId = 'alert-' + Date.now();
        const alertHTML = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                ${message}
                <button type="button" class="btn-close" onclick="document.getElementById('${alertId}').remove()"></button>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', alertHTML);
        
        // Auto-remove after duration
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) alert.remove();
        }, duration);
    }

    /**
     * Animate element update
     */
    animateUpdate(element) {
        if (!element) return;
        
        element.style.transition = 'background-color 0.3s ease';
        element.style.backgroundColor = 'rgba(25, 135, 84, 0.1)';
        
        setTimeout(() => {
            element.style.backgroundColor = '';
        }, 1000);
    }

    /**
     * Update last refresh time indicator
     */
    updateLastRefreshTime() {
        const refreshIndicator = document.querySelector('[data-last-refresh]');
        if (refreshIndicator) {
            refreshIndicator.textContent = `Last updated: ${new Date().toLocaleTimeString()}`;
        }
    }

    /**
     * Utility functions
     */
    formatNumber(num, decimals = 2) {
        if (num === null || num === undefined || isNaN(num)) return '0.00';
        return parseFloat(num).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            day: '2-digit',
            month: 'short',
            year: '2-digit'
        });
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }

    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        }) + ' • ' + date.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        });
    }

    truncateText(text, length) {
        if (!text) return '';
        return text.length > length ? text.substring(0, length) : text;
    }

    capitalize(str) {
        return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
    }

    getTypeColor(type) {
        const colors = {
            deposit: 'success',
            withdrawal: 'warning', 
            commission: 'info',
            roi: 'primary',
            investment: 'primary',
            bonus: 'success'
        };
        return colors[type] || 'info';
    }

    getStatusColor(status) {
        const colors = {
            completed: 'success',
            pending: 'warning',
            processing: 'info',
            failed: 'danger',
            cancelled: 'secondary'
        };
        return colors[status] || 'secondary';
    }

    getTransactionIcon(type) {
        const icons = {
            deposit: '<iconify-icon icon="iconamoon:arrow-down-duotone" class="text-success fs-20"></iconify-icon>',
            withdrawal: '<iconify-icon icon="iconamoon:arrow-up-duotone" class="text-warning fs-20"></iconify-icon>',
            commission: '<iconify-icon icon="iconamoon:users-duotone" class="text-info fs-20"></iconify-icon>',
            roi: '<iconify-icon icon="iconamoon:chart-growth-duotone" class="text-primary fs-20"></iconify-icon>',
            investment: '<iconify-icon icon="iconamoon:chart-growth-duotone" class="text-primary fs-20"></iconify-icon>',
            bonus: '<iconify-icon icon="iconamoon:gift-duotone" class="text-success fs-20"></iconify-icon>'
        };
        return icons[type] || '<iconify-icon icon="material-symbols:account-balance-wallet" class="text-info fs-20"></iconify-icon>';
    }

    /**
     * Cleanup method
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
        this.isActive = false;
        console.log('Dashboard Manager destroyed');
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardManager = new DashboardManager();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.dashboardManager) {
        window.dashboardManager.destroy();
    }
});

// Global functions for onclick handlers (backward compatibility)
function toggleDetails(transactionId) {
    if (window.dashboardManager) {
        window.dashboardManager.toggleTransactionDetails(transactionId);
    }
}

function copyText(text) {
    if (window.dashboardManager) {
        window.dashboardManager.copyToClipboard(text);
    }
}

function copyReferralLink() {
    if (window.dashboardManager) {
        window.dashboardManager.copyReferralLink();
    }
}