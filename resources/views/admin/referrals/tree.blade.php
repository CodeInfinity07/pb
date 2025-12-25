@extends('admin.layouts.vertical', ['title' => 'Referral Tree', 'subTitle' => 'Admin'])

@section('content')

{{-- Header --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="{{ route('admin.referrals.index') }}">Referrals</a></li>
                                <li class="breadcrumb-item active">Tree View</li>
                            </ol>
                        </nav>
                        <h4 class="mb-1">Referral Tree Visualization</h4>
                        <p class="text-muted mb-0">Interactive hierarchical view of referral networks</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="expandAllBtn">
                            Expand All
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="collapseAllBtn">
                            Collapse All
                        </button>
                        <a href="{{ route('admin.referrals.index') }}" class="btn btn-primary btn-sm">
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Controls Panel --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Tree Controls</h5>
            </div>
            <div class="card-body">
                <form id="treeForm" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="userSelect" class="form-label">Select User</label>
                        <select class="form-select" id="userSelect" name="user_id">
                            <option value="">Choose a user to view their network...</option>
                            @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $userId == $user->id ? 'selected' : '' }}>
                                {{ $user->full_name }} ({{ $user->email }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="maxDepth" class="form-label">Max Levels</label>
                        <select class="form-select" id="maxDepth" name="max_depth">
                            <option value="2" {{ $maxDepth == 2 ? 'selected' : '' }}>2 Levels</option>
                            <option value="3" {{ $maxDepth == 3 ? 'selected' : '' }}>3 Levels</option>
                            <option value="4" {{ $maxDepth == 4 ? 'selected' : '' }}>4 Levels</option>
                            <option value="5" {{ $maxDepth == 5 ? 'selected' : '' }}>5 Levels</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="showInactive" name="show_inactive" {{ $showInactive ? 'checked' : '' }}>
                            <label class="form-check-label" for="showInactive">
                                Include inactive referrals
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3 ms-auto">
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="submit" class="btn btn-primary" id="loadTreeBtn">
                                <span class="btn-text">Load Tree</span>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="refreshBtn">
                                <iconify-icon icon="material-symbols:refresh"></iconify-icon>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Statistics Row --}}
@if($selectedUser)
<div class="row mb-4" id="statsRow">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Network Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row g-3" id="networkStats">
                    <div class="col-6 col-lg-3">
                        <div class="stats-card text-center">
                            <div class="stats-value text-primary" id="stat-direct">{{ $treeData['total_referrals'] ?? 0 }}</div>
                            <div class="stats-label">Direct Referrals</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-card text-center">
                            <div class="stats-value text-success" id="stat-active">{{ $treeData['active_referrals'] ?? 0 }}</div>
                            <div class="stats-label">Active Direct</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-card text-center">
                            <div class="stats-value text-warning" id="stat-commission">${{ number_format($treeData['total_commission'] ?? 0, 2) }}</div>
                            <div class="stats-label">Total Commission</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="stats-card text-center">
                            <div class="stats-value text-info" id="stat-network">-</div>
                            <div class="stats-label">Network Size</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Status Legend</h5>
            </div>
            <div class="card-body">
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-indicator active"></div>
                        <span>Active User</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-indicator inactive"></div>
                        <span>Inactive User</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-indicator blocked"></div>
                        <span>Blocked User</span>
                    </div>
                    <div class="legend-item">
                        <iconify-icon icon="mdi:chevron-double-down" class="text-primary"></iconify-icon>
                        <span>Expandable</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Tree Visualization --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Referral Network Tree</h5>
            </div>
            <div class="card-body p-0">
                {{-- Loading State --}}
                <div id="loadingState" class="tree-state {{ $treeData ? 'd-none' : '' }}">
                    <div class="state-content">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h6>Building referral tree...</h6>
                        <p class="text-muted mb-0">Please wait while we load the network data.</p>
                    </div>
                </div>

                {{-- Empty State --}}
                <div id="emptyState" class="tree-state {{ $treeData || !$selectedUser ? '' : 'd-none' }}">
                    <div class="state-content">
                        <iconify-icon icon="tabler:hierarchy" class="state-icon text-muted"></iconify-icon>
                        <h6 id="emptyTitle">{{ $selectedUser ? 'No Referral Network' : 'Select a User to Begin' }}</h6>
                        <p class="text-muted mb-0" id="emptyMessage">
                            {{ $selectedUser ? 'This user has no referrals in their network.' : 'Choose a user from the dropdown above to visualize their referral tree.' }}
                        </p>
                    </div>
                </div>

                {{-- Tree Container --}}
                <div id="treeContainer" class="tree-viewport {{ $treeData ? '' : 'd-none' }}">
                    <div id="treeCanvas" class="tree-canvas">
                        <!-- Tree nodes will be rendered here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- User Details Modal --}}
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewFullProfileBtn">View Full Profile</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
class ReferralTreeManager {
    constructor() {
    this.currentTreeData = @json($treeData);
    this.currentUserId = {{ $userId ?? 'null' }};
    this.isLoading = false;
    
    this.initializeElements();
    this.bindEvents();
    
    // Handle initial state properly
    if (this.currentTreeData) {
        this.renderTree();
        this.loadStatistics();
    } else if (this.currentUserId) {
        // User selected but no tree data - show empty state
        this.showEmptyState('No Referral Network', 'This user has no referrals in their network.');
    } else {
        // No user selected - show initial selection state
        this.showEmptyState('Select a User to Begin', 'Choose a user from the dropdown above to visualize their referral tree.');
    }
}
    
    initializeElements() {
        // Form elements
        this.form = document.getElementById('treeForm');
        this.userSelect = document.getElementById('userSelect');
        this.loadBtn = document.getElementById('loadTreeBtn');
        this.refreshBtn = document.getElementById('refreshBtn');
        
        // Tree controls
        this.expandAllBtn = document.getElementById('expandAllBtn');
        this.collapseAllBtn = document.getElementById('collapseAllBtn');

        // Tree elements
        this.treeContainer = document.getElementById('treeContainer');
        this.treeCanvas = document.getElementById('treeCanvas');
        this.loadingState = document.getElementById('loadingState');
        this.emptyState = document.getElementById('emptyState');
        
        // Modal
        this.modal = document.getElementById('userDetailsModal');
        this.modalContent = document.getElementById('userDetailsContent');
        this.viewProfileBtn = document.getElementById('viewFullProfileBtn');
    }
    
    bindEvents() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.loadTree();
        });
        
        this.userSelect.addEventListener('change', () => {
            if (this.userSelect.value) {
                this.loadTree();
            }
        });
        
        this.refreshBtn.addEventListener('click', () => this.refreshTree());
        this.expandAllBtn.addEventListener('click', () => this.expandAll());
        this.collapseAllBtn.addEventListener('click', () => this.collapseAll());
 }
    
    async loadTree() {
        if (this.isLoading) return;
        
        const formData = new FormData(this.form);
        const userId = formData.get('user_id');
        
        if (!userId) {
            this.showEmptyState('Select a User to Begin', 'Choose a user from the dropdown above to visualize their referral tree.');
            return;
        }
        
        this.setLoadingState(true);
        this.updateUrl(formData);
        
        try {
            const params = new URLSearchParams(formData);
            const response = await fetch(`{{ route("admin.referrals.tree.data") }}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.currentTreeData = data.data;
                this.currentUserId = parseInt(userId);
                this.renderTree();
                this.loadStatistics();
            } else {
                this.showEmptyState('No Referral Network', data.message || 'This user has no referrals in their network.');
                this.showAlert(data.message, 'warning');
            }
        } catch (error) {
            console.error('Error loading tree:', error);
            this.showAlert('Failed to load referral tree', 'danger');
            this.showEmptyState('Error Loading Tree', 'There was an error loading the referral tree. Please try again.');
        } finally {
            this.setLoadingState(false);
        }
    }
    
    refreshTree() {
        if (this.currentUserId) {
            this.loadTree();
        }
    }
    
    renderTree() {
        if (!this.currentTreeData || !this.currentTreeData.children?.length) {
            this.showEmptyState('No Referral Network', 'This user has no referrals in their network.');
            return;
        }
        
        this.showTreeView();
        this.treeCanvas.innerHTML = this.buildTreeHTML(this.currentTreeData, 0, true);
        this.bindTreeEvents();
    }
    
    buildTreeHTML(nodeData, level, isRoot = false) {
        const safeData = this.sanitizeNodeData(nodeData);
        const hasChildren = safeData.children && safeData.children.length > 0;
        const statusClass = this.getStatusClass(safeData);
        
        let html = `<div class="tree-node level-${level} ${isRoot ? 'root-node' : ''}" data-user-id="${safeData.id}">`;
        
        // Node content
        html += `<div class="node-content ${statusClass}" data-user-id="${safeData.id}">`;
        html += this.buildNodeAvatar(safeData, hasChildren);
        html += this.buildNodeInfo(safeData, isRoot);
        html += `</div>`;
        
        // Children container
        if (hasChildren) {
            html += `<div class="node-children" id="children-${safeData.id}">`;
            safeData.children.forEach(child => {
                html += this.buildTreeHTML(child, level + 1);
            });
            html += `</div>`;
        }
        
        html += `</div>`;
        return html;
    }
    
    buildNodeAvatar(nodeData, hasChildren) {
        const initials = this.getInitials(nodeData.name);
        
        let html = `<div class="node-avatar">`;
        html += `<div class="avatar">`;
        html += `<span class="avatar-text">${initials}</span>`;
        html += `</div>`;
        
        if (hasChildren) {
            html += `<button class="expand-toggle" data-expanded="true" data-user-id="${nodeData.id}">`;
            html += `<iconify-icon icon="mdi:chevron-double-down"></iconify-icon>`;
            html += `</button>`;
        }
        
        html += `</div>`;
        return html;
    }
    
    buildNodeInfo(nodeData, isRoot) {
        let html = `<div class="node-info">`;
        html += `<div class="node-name">${this.escapeHtml(nodeData.name)}</div>`;
        html += `<div class="node-stats">`;
        
        if (nodeData.referral_data && !isRoot) {
            const commission = this.formatCurrency(nodeData.referral_data.commission_earned);
            html += `<small class="text-muted">Joined ${nodeData.referral_data.created_ago} • ${commission} commission</small>`;
        } else if (isRoot) {
            const totalCommission = this.formatCurrency(nodeData.total_commission);
            html += `<small class="text-muted">${nodeData.total_referrals} referrals • ${totalCommission} total</small>`;
        }
        
        html += `</div>`;
        html += `</div>`;
        return html;
    }
    
    bindTreeEvents() {
        // Node click events
        this.treeCanvas.addEventListener('click', (e) => {
            const nodeContent = e.target.closest('.node-content');
            const expandToggle = e.target.closest('.expand-toggle');
            
            if (expandToggle) {
                e.stopPropagation();
                this.toggleNode(expandToggle.dataset.userId);
            } else if (nodeContent) {
                this.showUserDetails(nodeContent.dataset.userId);
            }
        });
    }
    
    toggleNode(userId) {
        const childrenContainer = document.getElementById(`children-${userId}`);
        const toggleBtn = document.querySelector(`[data-user-id="${userId}"] .expand-toggle`);
        
        if (!childrenContainer || !toggleBtn) return;
        
        const isExpanded = toggleBtn.dataset.expanded === 'true';
        
        if (isExpanded) {
            childrenContainer.style.display = 'none';
            toggleBtn.querySelector('iconify-icon').setAttribute('icon', 'iconamoon:chevron-right-duotone');
            toggleBtn.dataset.expanded = 'false';
        } else {
            childrenContainer.style.display = 'block';
            toggleBtn.querySelector('iconify-icon').setAttribute('icon', 'mdi:chevron-double-down');
            toggleBtn.dataset.expanded = 'true';
        }
    }
    
    expandAll() {
        document.querySelectorAll('.expand-tomdi:chevron-double-downggle').forEach(btn => {
            const userId = btn.dataset.userId;
            const childrenContainer = document.getElementById(`children-${userId}`);
            
            if (childrenContainer) {
                childrenContainer.style.display = 'block';
                btn.querySelector('iconify-icon').setAttribute('icon', 'mdi:chevron-double-down');
                btn.dataset.expanded = 'true';
            }
        });
    }
    
    collapseAll() {
        document.querySelectorAll('.expand-toggle').forEach(btn => {
            const userId = btn.dataset.userId;
            const childrenContainer = document.getElementById(`children-${userId}`);
            
            // Don't collapse root node
            if (childrenContainer && !btn.closest('.root-node')) {
                childrenContainer.style.display = 'none';
                btn.querySelector('iconify-icon').setAttribute('icon', 'iconamoon:chevron-right-duotone');
                btn.dataset.expanded = 'false';
            }
        });
    }
    
    centerTree() {
        this.treeContainer.scrollTo({
            left: (this.treeCanvas.scrollWidth - this.treeContainer.clientWidth) / 2,
            top: 0,
            behavior: 'smooth'
        });
    }
    
    fitToScreen() {
        this.treeContainer.scrollTo({
            left: 0,
            top: 0,
            behavior: 'smooth'
        });
    }
    
    async showUserDetails(userId) {
        const userData = this.findUserInTree(this.currentTreeData, userId);
        
        if (!userData) {
            this.showAlert('User data not found', 'error');
            return;
        }
        
        // Show modal with loading state
        const modal = new bootstrap.Modal(this.modal);
        this.modalContent.innerHTML = this.getLoadingHTML();
        modal.show();
        
        // Load user details
        setTimeout(() => {
            this.modalContent.innerHTML = this.buildUserDetailsHTML(userData);
            this.viewProfileBtn.onclick = () => {
                window.open(`{{ url('admin/users') }}/${userId}`, '_blank');
            };
        }, 300);
    }
    
    async loadStatistics() {
        if (!this.currentUserId) return;
        
        try {
            const response = await fetch(`{{ route("admin.referrals.tree.stats") }}?user_id=${this.currentUserId}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStatistics(data.data);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }
    
    updateStatistics(stats) {
        const elements = {
            'stat-direct': stats.direct_referrals.total,
            'stat-active': stats.direct_referrals.active,
            'stat-commission': this.formatCurrency(stats.direct_referrals.commission),
            'stat-network': stats.total_downline
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }
    
    // Utility methods
    sanitizeNodeData(nodeData) {
        return {
            id: nodeData.id || 0,
            name: nodeData.name || 'Unknown User',
            email: nodeData.email || '',
            status: nodeData.status || 'inactive',
            is_active: nodeData.is_active || false,
            total_referrals: nodeData.total_referrals || 0,
            active_referrals: nodeData.active_referrals || 0,
            total_commission: nodeData.total_commission || 0,
            children: nodeData.children || [],
            referral_data: nodeData.referral_data || null
        };
    }
    
    getStatusClass(nodeData) {
        if (nodeData.is_active) return 'status-active';
        if (nodeData.status === 'blocked') return 'status-blocked';
        return 'status-inactive';
    }
    
    getInitials(name) {
        return name.split(' ')
            .map(n => n.charAt(0))
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }
    
    formatCurrency(value) {
        const num = parseFloat(value || 0);
        return isNaN(num) ? '$0.00' : `$${num.toFixed(2)}`;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    findUserInTree(treeData, userId) {
        if (treeData.id == userId) return treeData;
        
        if (treeData.children) {
            for (let child of treeData.children) {
                const found = this.findUserInTree(child, userId);
                if (found) return found;
            }
        }
        
        return null;
    }
    
    // State management
    setLoadingState(loading) {
        this.isLoading = loading;
        
        if (loading) {
            this.loadBtn.disabled = true;
            this.loadBtn.querySelector('.btn-text').textContent = 'Loading...';
            this.showLoadingState();
        } else {
            this.loadBtn.disabled = false;
            this.loadBtn.querySelector('.btn-text').textContent = 'Load Tree';
        }
    }
    
    showLoadingState() {
        this.loadingState.classList.remove('d-none');
        this.emptyState.classList.add('d-none');
        this.treeContainer.classList.add('d-none');
    }
    
    showEmptyState(title, message) {
        document.getElementById('emptyTitle').textContent = title;
        document.getElementById('emptyMessage').textContent = message;
        
        this.emptyState.classList.remove('d-none');
        this.loadingState.classList.add('d-none');
        this.treeContainer.classList.add('d-none');
    }
    
    showTreeView() {
        this.treeContainer.classList.remove('d-none');
        this.loadingState.classList.add('d-none');
        this.emptyState.classList.add('d-none');
    }
    
    updateUrl(formData) {
        const params = new URLSearchParams(formData);
        const url = new URL(window.location.href);
        url.search = params.toString();
        window.history.pushState({}, '', url);
    }
    
    // HTML builders
    getLoadingHTML() {
        return `
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="text-muted">Loading user details...</p>
            </div>
        `;
    }
    
    buildUserDetailsHTML(userData) {
        const safeData = this.sanitizeNodeData(userData);
        
        return `
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-lg me-3">
                            <span class="avatar-text">${this.getInitials(safeData.name)}</span>
                        </div>
                        <div>
                            <h6 class="mb-1">${this.escapeHtml(safeData.name)}</h6>
                            <span class="badge badge-${safeData.is_active ? 'success' : 'warning'}">${safeData.status}</span>
                        </div>
                    </div>
                    
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value">${this.escapeHtml(safeData.email)}</span>
                        </div>
                        ${safeData.referral_data ? `
                        <div class="info-item">
                            <span class="info-label">Commission:</span>
                            <span class="info-value text-success">${this.formatCurrency(safeData.referral_data.commission_earned)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Joined:</span>
                            <span class="info-value">${safeData.referral_data.created_at}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6 class="mb-3">Network Statistics</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-value text-primary">${safeData.total_referrals}</div>
                                <div class="stat-label">Referrals</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-value text-success">${safeData.active_referrals}</div>
                                <div class="stat-label">Active</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="stat-card">
                                <div class="stat-value text-warning">${this.formatCurrency(safeData.total_commission)}</div>
                                <div class="stat-label">Total Commission</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 350px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.treeManager = new ReferralTreeManager();
});
</script>

<style>
/* Base Styles */
.card {
    border-radius: 12px;
    border: 1px solid #e3e6f0;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

/* Statistics Cards */
.stats-card {
    padding: 1rem;
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.stats-card:hover {
    border-color: #d1d3e2;
    box-shadow: 0 0.25rem 0.5rem rgba(58, 59, 69, 0.1);
}

.stats-value {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stats-label {
    font-size: 0.8rem;
    color: #858796;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Legend Styles */
.legend-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.legend-indicator {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 2px solid;
}

.legend-indicator.active {
    background-color: #d4edda;
    border-color: #28a745;
}

.legend-indicator.inactive {
    background-color: #fff3cd;
    border-color: #ffc107;
}

.legend-indicator.blocked {
    background-color: #f8d7da;
    border-color: #dc3545;
}

/* Tree Viewport */
.tree-viewport {
    height: 600px;
    overflow: auto;
    background: #f8f9fa;
    border-radius: 8px;
}

.tree-canvas {
    min-width: 100%;
    min-height: 100%;
    padding: 2rem;
    position: relative;
}

/* Tree States */
.tree-state {
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.state-content {
    text-align: center;
    max-width: 400px;
    padding: 2rem;
}

.state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Tree Nodes */
.tree-node {
    position: relative;
    margin: 1rem 0;
    margin-left: 0;
}

.tree-node.level-1 { margin-left: 3rem; }
.tree-node.level-2 { margin-left: 6rem; }
.tree-node.level-3 { margin-left: 9rem; }
.tree-node.level-4 { margin-left: 12rem; }

/* Connection Lines */
.tree-node:not(.root-node)::before {
    content: '';
    position: absolute;
    left: -2.5rem;
    top: 2rem;
    width: 2rem;
    height: 2px;
    background: #d1d3e2;
    z-index: 1;
}

.tree-node:not(.root-node)::after {
    content: '';
    position: absolute;
    left: -2.5rem;
    top: -1rem;
    width: 2px;
    height: 3rem;
    background: #d1d3e2;
    z-index: 1;
}

.tree-node:last-child::after {
    height: 3rem;
}

/* Node Content */
.node-content {
    display: flex;
    align-items: center;
    background: white;
    border: 2px solid #e3e6f0;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    min-width: 300px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}

.node-content:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(58, 59, 69, 0.15);
}

.node-content.status-active {
    border-color: #28a745;
    background: linear-gradient(135deg, #fff 0%, #f8fff9 100%);
}

.node-content.status-inactive {
    border-color: #ffc107;
    background: linear-gradient(135deg, #fff 0%, #fffbf0 100%);
}

.node-content.status-blocked {
    border-color: #dc3545;
    background: linear-gradient(135deg, #fff 0%, #fff5f5 100%);
}

/* Node Avatar */
.node-avatar {
    position: relative;
    margin-right: 1rem;
    flex-shrink: 0;
}

.avatar {
    width: 3rem;
    height: 3rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

.avatar-text {
    font-size: 0.9rem;
    font-weight: 600;
}

.expand-toggle {
    position: absolute;
    bottom: -4px;
    right: -4px;
    width: 24px;
    height: 24px;
    background: #007bff;
    color: white;
    border: 2px solid white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 12px;
}

.expand-toggle:hover {
    background: #0056b3;
    transform: scale(1.1);
}

/* Node Info */
.node-info {
    flex: 1;
    min-width: 0;
}

.node-name {
    font-weight: 600;
    font-size: 1rem;
    color: #2c3e50;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.node-stats {
    font-size: 0.8rem;
    color: #6c757d;
    line-height: 1.2;
}

/* Node Children */
.node-children {
    margin-top: 1rem;
    transition: all 0.3s ease;
}

/* Modal Enhancements */
.avatar-lg {
    width: 4rem;
    height: 4rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f1f1f1;
}

.info-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.info-value {
    font-weight: 500;
    font-size: 0.9rem;
}

.stat-card {
    text-align: center;
    padding: 1rem;
    background: #f8f9fc;
    border-radius: 8px;
    border: 1px solid #e3e6f0;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #858796;
    text-transform: uppercase;
}

/* Badge Styles */
.badge-success {
    background-color: #28a745;
    color: white;
}

.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

/* Scrollbar Styling */
.tree-viewport::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.tree-viewport::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.tree-viewport::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.tree-viewport::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Responsive Design */
@media (max-width: 768px) {
    .tree-viewport {
        height: 500px;
    }
    
    .tree-canvas {
        padding: 1rem;
    }
    
    .node-content {
        min-width: 260px;
        padding: 0.875rem 1rem;
    }
    
    .avatar {
        width: 2.5rem;
        height: 2.5rem;
        font-size: 0.875rem;
    }
    
    .node-name {
        font-size: 0.9rem;
    }
    
    .node-stats {
        font-size: 0.75rem;
    }
    
    .tree-node.level-1 { margin-left: 2rem; }
    .tree-node.level-2 { margin-left: 4rem; }
    .tree-node.level-3 { margin-left: 6rem; }
    .tree-node.level-4 { margin-left: 8rem; }
    
    .tree-node:not(.root-node)::before {
        left: -1.75rem;
        width: 1.5rem;
    }
    
    .tree-node:not(.root-node)::after {
        left: -1.75rem;
    }
}

@media (max-width: 576px) {
    .node-content {
        min-width: 240px;
        padding: 0.75rem 0.875rem;
    }
    
    .avatar {
        width: 2.25rem;
        height: 2.25rem;
        font-size: 0.8rem;
    }
    
    .expand-toggle {
        width: 20px;
        height: 20px;
        font-size: 10px;
    }
    
    .stats-value {
        font-size: 1.1rem;
    }
    
    .state-content {
        padding: 1rem;
    }
    
    .state-icon {
        font-size: 3rem;
    }
}

/* Loading States */
.spinner-border {
    width: 2rem;
    height: 2rem;
}

/* Button Enhancements */
.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:disabled {
    transform: none;
}

/* Form Improvements */
.form-check {
    padding-top: 0.5rem;
}

/* Alert Positioning */
.alert.position-fixed {
    z-index: 9999;
}
</style>
@endsection