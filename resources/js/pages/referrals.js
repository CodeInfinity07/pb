// resources/js/pages/referrals.js

class ReferralManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadReferralStats();
        this.setupSearch();
        this.setupFilters();
    }

    setupEventListeners() {
        // Copy referral link functionality
        document.getElementById('copyReferralLink')?.addEventListener('click', this.copyReferralLink);
        
        // Export functionality
        document.getElementById('exportReferrals')?.addEventListener('click', this.exportReferrals);
        
        // Tab switching with data loading
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', (e) => {
                const tier = e.target.getAttribute('href').replace('#', '').replace('-referrals', '');
                this.loadTierData(tier);
            });
        });

        // Pagination for each tier
        document.addEventListener('click', '.tier-pagination a', (e) => {
            e.preventDefault();
            const url = e.target.href;
            const tier = e.target.closest('.tab-pane').id.replace('-referrals', '');
            this.loadTierData(tier, url);
        });
    }

    copyReferralLink() {
        const referralLink = document.getElementById('referralLink');
        if (!referralLink) return;

        referralLink.select();
        referralLink.setSelectionRange(0, 99999);
        
        navigator.clipboard.writeText(referralLink.value).then(() => {
            // Show success notification
            this.showNotification('Referral link copied to clipboard!', 'success');
            
            // Add visual feedback
            const button = document.querySelector('#copyReferralLink');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="iconamoon:check-duotone"></i> Copied!';
            button.classList.add('btn-success');
            button.classList.remove('btn-light');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.classList.remove('btn-success');
                button.classList.add('btn-light');
            }, 2000);
        }).catch(err => {
            console.error('Could not copy text: ', err);
            this.showNotification('Failed to copy link', 'error');
        });
    }

    loadReferralStats() {
        fetch('/referrals/stats')
            .then(response => response.json())
            .then(data => {
                this.updateStatsCards(data);
            })
            .catch(error => {
                console.error('Error loading stats:', error);
            });
    }

    updateStatsCards(stats) {
        // Update stats cards with real-time data
        const statsMap = {
            'direct_referrals': '.direct-referrals-count',
            'total_earnings': '.total-earnings',
            'pending_commissions': '.pending-commissions',
            'active_referrals': '.active-referrals-count'
        };

        Object.entries(statsMap).forEach(([key, selector]) => {
            const element = document.querySelector(selector);
            if (element && stats[key] !== undefined) {
                if (key.includes('earnings') || key.includes('commissions')) {
                    element.textContent = '$' + this.formatNumber(stats[key]);
                } else {
                    element.textContent = this.formatNumber(stats[key]);
                }
            }
        });
    }

    loadTierData(tier, url = null) {
        const endpoint = url || `/referrals/tier/${tier}`;
        const tableContainer = document.querySelector(`#${tier}-referrals .table-container`);
        
        if (!tableContainer) return;

        // Show loading state
        this.showLoading(tableContainer);

        fetch(endpoint)
            .then(response => response.json())
            .then(data => {
                this.renderTierTable(tier, data);
            })
            .catch(error => {
                console.error('Error loading tier data:', error);
                this.showError(tableContainer, 'Failed to load referral data');
            });
    }

    renderTierTable(tier, data) {
        const container = document.querySelector(`#${tier}-referrals .table-container`);
        if (!container) return;

        let html = '';
        
        if (data.data && data.data.length > 0) {
            html = `
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                ${tier !== 'direct' ? '<th>Sponsor</th>' : ''}
                                <th>Status</th>
                                <th>Joined Date</th>
                                <th>Investment</th>
                                ${tier === 'direct' ? '<th>Sub-Referrals</th>' : ''}
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.map(referral => this.renderReferralRow(referral, tier)).join('')}
                        </tbody>
                    </table>
                </div>
                ${this.renderPagination(data)}
            `;
        } else {
            html = this.renderEmptyState(tier);
        }

        container.innerHTML = html;
    }

    renderReferralRow(referral, tier) {
        const statusBadge = this.getStatusBadge(referral.status);
        
        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm rounded-circle ${this.getTierBgClass(tier)} me-2">
                            <span class="avatar-title">
                                ${this.getInitials(referral.first_name, referral.last_name)}
                            </span>
                        </div>
                        <div>
                            <h6 class="mb-0">${referral.first_name} ${referral.last_name}</h6>
                            <small class="text-muted">${referral.username}</small>
                        </div>
                    </div>
                </td>
                <td>${referral.email}</td>
                ${tier !== 'direct' ? `<td><small class="text-primary">${referral.sponsor?.first_name || ''} ${referral.sponsor?.last_name || ''}</small></td>` : ''}
                <td>${statusBadge}</td>
                <td>${this.formatDate(referral.created_at)}</td>
                <td>$${this.formatNumber(referral.total_investment_amount || 0)}</td>
                ${tier === 'direct' ? `<td>${referral.direct_referrals_count || 0}</td>` : ''}
                <td>
                    <div class="dropdown">
                        <a href="#" class="dropdown-toggle arrow-none" data-bs-toggle="dropdown">
                            <i class="iconamoon:menu-kebab-vertical-circle-duotone"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#" class="dropdown-item" onclick="viewReferralProfile(${referral.id})">View Profile</a>
                            <a href="#" class="dropdown-item" onclick="viewReferralTransactions(${referral.id})">View Transactions</a>
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    renderPagination(data) {
        if (!data.last_page || data.last_page <= 1) return '';

        let html = '<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">';
        
        // Previous button
        if (data.current_page > 1) {
            html += `<li class="page-item"><a class="page-link tier-pagination" href="${data.prev_page_url}">Previous</a></li>`;
        }
        
        // Page numbers
        for (let i = 1; i <= data.last_page; i++) {
            const active = i === data.current_page ? 'active' : '';
            html += `<li class="page-item ${active}"><a class="page-link tier-pagination" href="${data.path}?page=${i}">${i}</a></li>`;
        }
        
        // Next button
        if (data.current_page < data.last_page) {
            html += `<li class="page-item"><a class="page-link tier-pagination" href="${data.next_page_url}">Next</a></li>`;
        }
        
        html += '</ul></nav>';
        return html;
    }

    setupSearch() {
        const searchInput = document.getElementById('referralSearch');
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });
    }

    performSearch(query) {
        if (query.length < 2) {
            document.getElementById('searchResults')?.classList.add('d-none');
            return;
        }

        fetch(`/referrals/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(results => {
                this.displaySearchResults(results);
            })
            .catch(error => {
                console.error('Search error:', error);
            });
    }

    displaySearchResults(results) {
        const container = document.getElementById('searchResults');
        if (!container) return;

        if (results.length === 0) {
            container.innerHTML = '<div class="p-3 text-muted">No referrals found</div>';
        } else {
            container.innerHTML = results.map(result => `
                <div class="search-result-item p-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${result.name}</strong> <span class="text-muted">(${result.username})</span>
                            <br><small class="text-muted">${result.email}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-info">${result.tier}</span>
                            <br><small class="text-muted">${result.joined_at}</small>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        container.classList.remove('d-none');
    }

    setupFilters() {
        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.applyFilters();
        });

        // Date range filter
        document.getElementById('dateFilter')?.addEventListener('change', (e) => {
            this.applyFilters();
        });
    }

    applyFilters() {
        const activeTab = document.querySelector('.tab-pane.active');
        if (!activeTab) return;

        const tier = activeTab.id.replace('-referrals', '');
        const filters = this.getFilterValues();
        
        const url = `/referrals/tier/${tier}?${new URLSearchParams(filters).toString()}`;
        this.loadTierData(tier, url);
    }

    getFilterValues() {
        return {
            status: document.getElementById('statusFilter')?.value || '',
            date_range: document.getElementById('dateFilter')?.value || '',
            // Add more filters as needed
        };
    }

    exportReferrals() {
        const format = document.getElementById('exportFormat')?.value || 'csv';
        window.location.href = `/referrals/export/${format}`;
    }

    // Utility methods
    showLoading(container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
    }

    showError(container, message) {
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="iconamoon:warning-duotone me-2"></i>
                ${message}
            </div>
        `;
    }

    showNotification(message, type = 'info') {
        // You can integrate with your existing toast/notification system
        // For now, using a simple alert
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge bg-success-subtle text-success">Active</span>',
            'pending_verification': '<span class="badge bg-warning-subtle text-warning">Pending</span>',
            'inactive': '<span class="badge bg-danger-subtle text-danger">Inactive</span>',
            'blocked': '<span class="badge bg-dark-subtle text-dark">Blocked</span>'
        };
        return badges[status] || '<span class="badge bg-secondary">Unknown</span>';
    }

    getTierBgClass(tier) {
        const classes = {
            'direct': 'bg-primary-subtle text-primary',
            't2': 'bg-success-subtle text-success',
            't3': 'bg-info-subtle text-info'
        };
        return classes[tier] || 'bg-primary-subtle text-primary';
    }

    getInitials(firstName, lastName) {
        return `${firstName?.charAt(0) || ''}${lastName?.charAt(0) || ''}`.toUpperCase();
    }

    formatNumber(number) {
        return new Intl.NumberFormat().format(number);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    renderEmptyState(tier) {
        const messages = {
            'direct': 'No Direct Referrals Yet',
            't2': 'No T-2 Referrals Yet',
            't3': 'No T-3 Referrals Yet'
        };

        const descriptions = {
            'direct': 'Start sharing your referral link to build your network',
            't2': 'T-2 referrals will appear when your direct referrals start referring others',
            't3': 'T-3 referrals will appear when your T-2 referrals start referring others'
        };

        const icons = {
            'direct': 'iconamoon:profile-duotone',
            't2': 'iconamoon:profile-circle-duotone',
            't3': 'iconamoon:profile-check-duotone'
        };

        return `
            <div class="text-center py-5">
                <iconify-icon icon="${icons[tier]}" class="fs-48 text-muted mb-3"></iconify-icon>
                <h5 class="text-muted">${messages[tier]}</h5>
                <p class="text-muted">${descriptions[tier]}</p>
            </div>
        `;
    }
}

// Global functions for dropdown actions
window.viewReferralProfile = function(userId) {
    // Implement profile view functionality
    console.log('View profile for user:', userId);
};

window.viewReferralTransactions = function(userId) {
    // Implement transaction view functionality
    console.log('View transactions for user:', userId);
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new ReferralManager();
});