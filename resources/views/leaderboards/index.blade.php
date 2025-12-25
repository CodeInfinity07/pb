@extends('layouts.vertical', ['title' => 'Leaderboards', 'subTitle' => 'Referral Competitions'])

@section('content')
<div class="container-fluid">

    <!-- Error Display -->
    @if(isset($error))
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        {{ $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- User Dashboard Stats -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Your Performance</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-sm-3">
                    <div class="text-center p-2 border rounded">
                        <div class="fw-bold" id="total-earnings">$0.00</div>
                        <small class="text-muted">Earned</small>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="text-center p-2 border rounded">
                        <div class="fw-bold" id="best-rank">-</div>
                        <small class="text-muted">Best Rank</small>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="text-center p-2 border rounded">
                        <div class="fw-bold" id="active-competitions">0</div>
                        <small class="text-muted">Active</small>
                    </div>
                </div>
                <div class="col-6 col-sm-3">
                    <div class="text-center p-2 border rounded">
                        <div class="fw-bold" id="total-participations">0</div>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    <small class="text-muted">Pending Prizes:</small>
                    <strong class="text-success" id="pending-prizes">$0.00</strong>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshDashboard()">
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Target Qualifications Alert -->
    <div id="target-qualifications-alert" class="alert alert-success d-none mb-4">
        <h6>Target Achievements Available!</h6>
        <p class="mb-2">You're close to qualifying for prizes:</p>
        <div id="target-qualifications-list"></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Current Rankings -->
    @if($userRankings && count($userRankings) > 0)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Your Current Rankings</h5>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshRankings()">
                Update
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Competition</th>
                            <th>Position</th>
                            <th>Referrals</th>
                            <th class="d-none d-md-table-cell">Progress</th>
                            <th class="d-none d-sm-table-cell">Days Left</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="current-rankings-tbody">
                        @foreach($userRankings as $ranking)
                        <tr data-leaderboard-id="{{ $ranking['leaderboard']->id }}">
                            <td>
                                <div>
                                    <div class="fw-medium">{{ Str::limit($ranking['leaderboard']->title, 20) }}</div>
                                    <span class="badge badge-sm bg-{{ $ranking['leaderboard']->type === 'target' ? 'info' : 'primary' }}">
                                        {{ $ranking['leaderboard']->type_display }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                @if($ranking['position'])
                                    <span class="badge bg-{{ $ranking['position']->position <= 3 ? 'warning' : 'secondary' }}">
                                        {{ $ranking['position']->position_display }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-medium referral-count">{{ $ranking['referral_count'] }}</span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                @if($ranking['leaderboard']->type === 'target')
                                    @php
                                        $progress = $ranking['progress'] ?? (($ranking['referral_count'] / max($ranking['leaderboard']->target_referrals, 1)) * 100);
                                        $qualified = $ranking['referral_count'] >= $ranking['leaderboard']->target_referrals;
                                    @endphp
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $qualified ? 'success' : 'primary' }}" 
                                             style="width: {{ min(100, $progress) }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ number_format($progress, 0) }}%</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="d-none d-sm-table-cell">
                                @if($ranking['leaderboard']->days_remaining > 0)
                                    <span class="text-warning days-remaining">{{ $ranking['leaderboard']->days_remaining }}d</span>
                                @else
                                    <span class="text-danger">Ended</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('user.leaderboards.show', $ranking['leaderboard']) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Active Leaderboards -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Active Competitions</h5>
            <div>
                @if($activeLeaderboards->count() > 0)
                    <span class="badge bg-success me-2">{{ $activeLeaderboards->count() }}</span>
                @endif
                <small class="text-muted">Updated: <span id="last-updated">{{ now()->format('H:i') }}</span></small>
            </div>
        </div>
        <div class="card-body">
            @if($activeLeaderboards->count() > 0)
                <div class="row g-3" id="active-leaderboards-container">
                    @foreach($activeLeaderboards as $leaderboard)
                    <div class="col-12 col-md-6 col-lg-4" data-leaderboard-id="{{ $leaderboard->id }}">
                        <div class="card border h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-1">{{ Str::limit($leaderboard->title, 30) }}</h6>
                                    <span class="badge bg-{{ $leaderboard->type === 'target' ? 'info' : 'primary' }}">
                                        {{ $leaderboard->type_display }}
                                    </span>
                                </div>

                                @if($leaderboard->description)
                                    <p class="text-muted small mb-3">{{ Str::limit($leaderboard->description, 60) }}</p>
                                @endif

                                <!-- Progress -->
                                @php $progress = $leaderboard->getProgress(); @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Progress</small>
                                        <small class="text-muted progress-text">{{ $progress }}%</small>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: {{ $progress }}%"></div>
                                    </div>
                                </div>

                                <!-- Stats -->
                                <div class="row g-2 text-center mb-3">
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="fw-medium text-success prize-amount">
                                                ${{ number_format($leaderboard->total_prize_amount ?? 0) }}
                                            </div>
                                            <small class="text-muted">Prize</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="fw-medium participant-count">
                                                {{ $leaderboard->getParticipantsCount() }}
                                            </div>
                                            <small class="text-muted">Players</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="fw-medium text-warning days-remaining">
                                                {{ $leaderboard->days_remaining }}
                                            </div>
                                            <small class="text-muted">Days</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Target Info -->
                                @if($leaderboard->type === 'target')
                                <div class="bg-light rounded p-2 mb-3">
                                    <div class="d-flex justify-content-between">
                                        <small class="text-info fw-medium">
                                            Target: {{ $leaderboard->target_referrals }}
                                        </small>
                                        <small class="text-success fw-bold">
                                            ${{ number_format($leaderboard->target_prize_amount) }}
                                        </small>
                                    </div>
                                </div>
                                @endif

                                <a href="{{ route('user.leaderboards.show', $leaderboard) }}" 
                                   class="btn btn-primary btn-sm d-block mt-auto">
                                    View Leaderboard
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4">
                    <h6 class="text-muted">No Active Competitions</h6>
                    <p class="text-muted mb-3">Check back later for new competitions</p>
                    <button class="btn btn-outline-primary" onclick="refreshActiveLeaderboards()">
                        Check for Updates
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Completed Leaderboards -->
    @if($completedLeaderboards->count() > 0)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Completed</h5>
            <div>
                <span class="badge bg-secondary me-2">{{ $completedLeaderboards->count() }}</span>
                <button class="btn btn-sm btn-outline-secondary" onclick="showFullHistory()">
                    View All
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Competition</th>
                            <th class="d-none d-sm-table-cell">Type</th>
                            <th class="d-none d-md-table-cell">Participants</th>
                            <th>Prize</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($completedLeaderboards as $leaderboard)
                        <tr>
                            <td>
                                <div>
                                    <div class="fw-medium">{{ Str::limit($leaderboard->title, 25) }}</div>
                                    <small class="text-muted d-sm-none">
                                        {{ ucfirst($leaderboard->type) }} • {{ $leaderboard->end_date->diffForHumans() }}
                                    </small>
                                    <small class="text-muted d-none d-sm-block">
                                        {{ $leaderboard->end_date->diffForHumans() }}
                                    </small>
                                </div>
                            </td>
                            <td class="d-none d-sm-table-cell">
                                <span class="badge bg-{{ $leaderboard->type === 'target' ? 'info' : 'primary' }}">
                                    {{ ucfirst($leaderboard->type) }}
                                </span>
                            </td>
                            <td class="d-none d-md-table-cell">
                                {{ $leaderboard->getParticipantsCount() }}
                            </td>
                            <td>
                                <span class="fw-medium text-success">${{ number_format($leaderboard->total_prize_amount ?? 0) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('user.leaderboards.show', $leaderboard) }}" 
                                   class="btn btn-sm btn-outline-secondary">
                                    View
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Competition History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Filters -->
                <div class="row g-2 mb-3">
                    <div class="col-sm-6 col-md-4">
                        <select class="form-select form-select-sm" id="history-status-filter">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <select class="form-select form-select-sm" id="history-per-page">
                            <option value="10">10 per page</option>
                            <option value="25">25 per page</option>
                            <option value="50">50 per page</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-outline-primary d-block" onclick="applyHistoryFilters()">
                            Apply Filters
                        </button>
                    </div>
                </div>
                
                <!-- History Content -->
                <div id="history-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alert-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 9999; max-width: 300px;"></div>
@endsection

@section('script')
<script>
let dashboardData = {};
let activeLeaderboardIds = [];
let refreshInterval;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    startAutoRefresh();
    checkTargetQualifications();
});

// Dashboard data loading
function loadDashboardData() {
    fetch('{{ route("user.leaderboards.api.dashboard") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                dashboardData = data.data;
                updateDashboardStats(data.data.stats);
                activeLeaderboardIds = data.data.active_leaderboards.map(lb => lb.id);
            } else {
                console.error('Failed to load dashboard data:', data.message);
                showAlert('warning', 'Failed to load dashboard data');
            }
        })
        .catch(error => {
            console.error('Dashboard data error:', error);
            showAlert('danger', 'Network error loading dashboard');
        });
}

// Update dashboard statistics
function updateDashboardStats(stats) {
    document.getElementById('total-earnings').textContent = stats.total_prizes_won ? 
        `$${stats.total_prizes_won.toFixed(2)}` : '$0.00';
    document.getElementById('best-rank').textContent = stats.best_rank ? 
        `#${stats.best_rank}` : '-';
    document.getElementById('active-competitions').textContent = stats.active_competitions || 0;
    document.getElementById('total-participations').textContent = stats.total_participations || 0;
    document.getElementById('pending-prizes').textContent = stats.pending_prizes ? 
        `$${stats.pending_prizes.toFixed(2)}` : '$0.00';
}

// Auto refresh functionality
function startAutoRefresh() {
    if (activeLeaderboardIds.length > 0) {
        refreshInterval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                getLiveUpdates();
            }
        }, 30000);
    }
}

// Get live updates for active leaderboards
function getLiveUpdates() {
    if (activeLeaderboardIds.length === 0) return;

    fetch('{{ route("user.leaderboards.api.live-updates") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            leaderboard_ids: activeLeaderboardIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateLiveData(data.data.updates);
            updateLastUpdated();
        }
    })
    .catch(error => console.error('Live updates error:', error));
}

// Update live data in the interface
function updateLiveData(updates) {
    Object.entries(updates).forEach(([leaderboardId, update]) => {
        const card = document.querySelector(`[data-leaderboard-id="${leaderboardId}"]`);
        if (card) {
            const participantElement = card.querySelector('.participant-count');
            if (participantElement) {
                participantElement.textContent = update.participant_count;
            }
            
            const daysElement = card.querySelector('.days-remaining');
            if (daysElement) {
                daysElement.textContent = update.days_remaining;
            }
            
            const progressBar = card.querySelector('.progress-bar');
            const progressText = card.querySelector('.progress-text');
            if (progressBar && progressText) {
                progressBar.style.width = `${update.progress}%`;
                progressText.textContent = `${update.progress}%`;
            }
            
            const rankingRow = document.querySelector(`#current-rankings-tbody tr[data-leaderboard-id="${leaderboardId}"]`);
            if (rankingRow) {
                const referralCountElement = rankingRow.querySelector('.referral-count');
                if (referralCountElement && update.user_current_referrals !== undefined) {
                    referralCountElement.textContent = update.user_current_referrals;
                }
            }
        }
    });
}

// Check target qualifications
function checkTargetQualifications() {
    fetch('{{ route("user.leaderboards.api.target-qualifications") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.qualifications.length > 0) {
                displayTargetQualifications(data.data.qualifications);
            }
        })
        .catch(error => console.error('Target qualifications error:', error));
}

// Display target qualifications alert
function displayTargetQualifications(qualifications) {
    const alert = document.getElementById('target-qualifications-alert');
    const list = document.getElementById('target-qualifications-list');
    
    let html = '';
    
    qualifications.slice(0, 3).forEach(qual => {
        if (qual.progress_percentage >= 70) {
            html += `
                <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${qual.leaderboard_title}</strong>
                            <div class="progress mt-1" style="height: 4px;">
                                <div class="progress-bar bg-success" style="width: ${qual.progress_percentage}%"></div>
                            </div>
                            <small class="text-muted">${qual.current_referrals}/${qual.target_referrals} (${Math.round(qual.progress_percentage)}%)</small>
                        </div>
                        <div class="text-end">
                            <div class="text-success fw-bold">$${qual.prize_amount}</div>
                            <a href="${qual.leaderboard_url}" class="btn btn-sm btn-outline-success">View</a>
                        </div>
                    </div>
                </div>
            `;
        }
    });
    
    if (html) {
        list.innerHTML = html;
        alert.classList.remove('d-none');
    }
}

// Manual refresh functions
function refreshDashboard() {
    const btn = event.target;
    const originalText = btn.textContent;
    
    btn.textContent = 'Loading...';
    btn.disabled = true;
    
    Promise.all([
        loadDashboardData(),
        getLiveUpdates()
    ]).finally(() => {
        setTimeout(() => {
            btn.textContent = originalText;
            btn.disabled = false;
            showAlert('success', 'Dashboard refreshed');
        }, 1000);
    });
}

function refreshRankings() {
    const btn = event.target;
    const originalText = btn.textContent;
    
    btn.textContent = 'Updating...';
    btn.disabled = true;
    
    getLiveUpdates();
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        showAlert('success', 'Rankings updated');
    }, 1500);
}

function refreshActiveLeaderboards() {
    location.reload();
}

// History modal functionality
function showFullHistory() {
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    modal.show();
    loadHistory();
}

function loadHistory(page = 1, filters = {}) {
    const params = new URLSearchParams({
        page: page,
        per_page: filters.per_page || 10,
        ...(filters.status && { status: filters.status })
    });

    fetch(`{{ route("user.leaderboards.api.history") }}?${params}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHistory(data.data);
            } else {
                document.getElementById('history-content').innerHTML = `
                    <div class="text-center py-4">
                        <h6 class="text-muted">Failed to load history</h6>
                        <p class="text-muted">${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('History loading error:', error);
            document.getElementById('history-content').innerHTML = `
                <div class="text-center py-4">
                    <h6 class="text-muted">Error loading history</h6>
                    <p class="text-muted">Network error occurred</p>
                </div>
            `;
        });
}

function displayHistory(data) {
    const container = document.getElementById('history-content');
    
    if (data.history.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <h6 class="text-muted">No Competition History</h6>
                <p class="text-muted">You haven't participated yet</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Competition</th>
                        <th>Position</th>
                        <th class="d-none d-sm-table-cell">Referrals</th>
                        <th>Prize</th>
                        <th class="d-none d-md-table-cell">Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    data.history.forEach(item => {
        const positionBadge = item.position <= 3 ? 'warning' : 'secondary';
        const statusBadge = item.prize_awarded ? 'success' : (item.prize_amount > 0 ? 'warning' : 'secondary');
        
        html += `
            <tr>
                <td>
                    <div class="fw-medium">${item.leaderboard_title}</div>
                    <small class="text-muted">${item.leaderboard_type === 'target' ? 'Target' : 'Competitive'}</small>
                </td>
                <td>
                    <span class="badge bg-${positionBadge}">${item.position_display}</span>
                    ${item.qualified ? '<div><small class="text-success">✓ Qualified</small></div>' : ''}
                </td>
                <td class="d-none d-sm-table-cell">
                    <span class="fw-medium">${item.referral_count}</span>
                </td>
                <td>
                    <span class="fw-medium text-${item.prize_amount > 0 ? 'success' : 'muted'}">${item.formatted_prize}</span>
                </td>
                <td class="d-none d-md-table-cell">
                    <span class="badge bg-${statusBadge}">${item.prize_status}</span>
                </td>
                <td>
                    <a href="${item.leaderboard_url}" class="btn btn-sm btn-outline-primary">View</a>
                </td>
            </tr>
        `;
    });
    
    html += `</tbody></table></div>`;
    
    // Add pagination
    if (data.pagination.last_page > 1) {
        html += `
            <nav class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">
                    ${data.pagination.from || 0} to ${data.pagination.to || 0} of ${data.pagination.total}
                </small>
                <div class="btn-group btn-group-sm">
        `;
        
        if (data.pagination.current_page > 1) {
            html += `<button class="btn btn-outline-primary" onclick="loadHistory(${data.pagination.current_page - 1}, getCurrentFilters())">←</button>`;
        }
        
        const startPage = Math.max(1, data.pagination.current_page - 2);
        const endPage = Math.min(data.pagination.last_page, startPage + 4);
        
        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === data.pagination.current_page;
            html += `
                <button class="btn btn-${isActive ? 'primary' : 'outline-primary'}" 
                        ${isActive ? 'disabled' : `onclick="loadHistory(${i}, getCurrentFilters())"`}>
                    ${i}
                </button>
            `;
        }
        
        if (data.pagination.has_more_pages) {
            html += `<button class="btn btn-outline-primary" onclick="loadHistory(${data.pagination.current_page + 1}, getCurrentFilters())">→</button>`;
        }
        
        html += `</div></nav>`;
    }
    
    container.innerHTML = html;
}

function applyHistoryFilters() {
    loadHistory(1, getCurrentFilters());
}

function getCurrentFilters() {
    return {
        status: document.getElementById('history-status-filter').value,
        per_page: document.getElementById('history-per-page').value
    };
}

function updateLastUpdated() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { 
        hour12: false, 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    const element = document.getElementById('last-updated');
    if (element) {
        element.textContent = timeString;
    }
}

// Alert system
function showAlert(type, message) {
    const container = document.getElementById('alert-container');
    if (!container) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            try {
                const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                alert.close();
            } catch (e) {
                alertDiv.remove();
            }
        }
    }, 4000);
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<style>
/* Minimal custom styles */
.badge-sm {
    font-size: 0.75em;
}

.progress {
    height: 8px;
}

.card {
    border-radius: 8px;
}

.table-responsive {
    border-radius: 8px;
}

@media (max-width: 576px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .modal-lg {
        max-width: 95%;
    }
}

@media (max-width: 768px) {
    .alert-container {
        right: 10px;
        max-width: 280px;
    }
}
</style>
@endsection