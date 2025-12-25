{{-- Lead Details Modal Content --}}

<div class="row g-4">
    {{-- Lead Information Card --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                    Lead Information
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar avatar-lg rounded-circle bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }} me-3">
                        <span class="avatar-title text-white fs-4">{{ strtoupper(substr($lead->first_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h5 class="mb-1">{{ $lead->full_name }}</h5>
                        <span class="badge bg-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}-subtle text-{{ $lead->status === 'hot' ? 'danger' : ($lead->status === 'warm' ? 'warning' : ($lead->status === 'cold' ? 'info' : ($lead->status === 'converted' ? 'success' : 'secondary'))) }}">
                            {{ ucfirst($lead->status) }}
                        </span>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Email</div>
                        <div class="fw-medium">{{ $lead->email ?: 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Mobile</div>
                        <div class="fw-medium">{{ $lead->mobile ?: 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">WhatsApp</div>
                        <div class="fw-medium">{{ $lead->whatsapp ?: 'Not provided' }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Country</div>
                        <div class="fw-medium">{{ $lead->country }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Source</div>
                        <div class="fw-medium">{{ $lead->source }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Interest Level</div>
                        <span class="badge bg-{{ $lead->interest === 'High' ? 'danger' : ($lead->interest === 'Medium' ? 'warning' : 'info') }}-subtle text-{{ $lead->interest === 'High' ? 'danger' : ($lead->interest === 'Medium' ? 'warning' : 'info') }}">
                            {{ $lead->interest }}
                        </span>
                    </div>
                    @if($lead->notes)
                    <div class="col-12">
                        <div class="text-muted small mb-1">Notes</div>
                        <div class="fw-medium">{{ $lead->notes }}</div>
                    </div>
                    @endif
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Created</div>
                        <div class="fw-medium">{{ $lead->created_at->format('M d, Y \a\t g:i A') }}</div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">Created By</div>
                        <div class="fw-medium">{{ $lead->createdBy->full_name ?? 'System' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Follow-ups Card --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="arcticons:chieffollow" class="me-2"></iconify-icon>
                    Follow-ups ({{ $lead->followups->count() }})
                </h6>
                <button class="btn btn-sm btn-primary" onclick="showFollowupModal('{{ $lead->id }}', '{{ $lead->full_name }}')">
                    <iconify-icon icon="iconamoon:calendar-add-duotone" class="me-1"></iconify-icon>
                    Add
                </button>
            </div>
            <div class="card-body p-0">
                @if($lead->followups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <tbody>
                            @foreach($lead->followups->take(5) as $followup)
                            <tr class="{{ $followup->followup_date->isPast() && !$followup->completed ? 'table-danger bg-danger bg-opacity-10' : ($followup->followup_date->isToday() && !$followup->completed ? 'table-warning bg-warning bg-opacity-10' : '') }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fs-5">{{ $followup->type_icon }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $followup->followup_date->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ ucfirst($followup->type) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    @if($followup->completed)
                                    <span class="badge bg-success-subtle text-success">
                                        <iconify-icon icon="iconamoon:check-circle-duotone"></iconify-icon>
                                    </span>
                                    @else
                                    <button class="btn btn-sm btn-success" onclick="completeFollowup('{{ $followup->id }}')">
                                        <iconify-icon icon="iconamoon:check-duotone"></iconify-icon>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <iconify-icon icon="arcticons:chieffollow" class="fs-1 text-muted mb-2"></iconify-icon>
                    <p class="text-muted mb-0">No follow-ups scheduled</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Assignments Card --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="material-symbols:assignment-turned-in-outline" class="me-2"></iconify-icon>
                    Assignments ({{ $lead->assignments->count() }})
                </h6>
                <button class="btn btn-sm btn-info" onclick="showAssignModal('{{ $lead->id }}', '{{ $lead->full_name }}')">
                    <iconify-icon icon="iconamoon:user-arrow-duotone" class="me-1"></iconify-icon>
                    Assign
                </button>
            </div>
            <div class="card-body p-0">
                @if($lead->assignments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                        <tbody>
                            @foreach($lead->assignments as $assignment)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-sm rounded-circle bg-{{ $assignment->assignedTo->role === 'admin' ? 'danger' : ($assignment->assignedTo->role === 'support' ? 'warning' : 'info') }} me-2">
                                            <span class="avatar-title text-white">{{ $assignment->assignedTo->initials }}</span>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $assignment->assignedTo->full_name }}</div>
                                            <small class="text-muted">{{ $assignment->assigned_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $assignment->status === 'active' ? 'warning' : 'success' }}-subtle text-{{ $assignment->status === 'active' ? 'warning' : 'success' }}">
                                        {{ ucfirst($assignment->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4">
                    <iconify-icon icon="material-symbols:assignment-turned-in-outline" class="fs-1 text-muted mb-2"></iconify-icon>
                    <p class="text-muted mb-0">No assignments</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Activity Timeline Card --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="iconamoon:history-duotone" class="me-2"></iconify-icon>
                    Recent Activity
                </h6>
            </div>
            <div class="card-body">
                @if($lead->activities->count() > 0)
                <div class="timeline-container">
                    @foreach($lead->activities->take(8) as $activity)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-{{ $activity->activity_type === 'created' ? 'success' : ($activity->activity_type === 'updated' ? 'info' : ($activity->activity_type === 'status_updated' ? 'warning' : 'secondary')) }}"></div>
                        <div class="timeline-content">
                            <div class="fw-medium">{{ $activity->description }}</div>
                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-4">
                    <iconify-icon icon="iconamoon:history-duotone" class="fs-1 text-muted mb-2"></iconify-icon>
                    <p class="text-muted mb-0">No activity recorded</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* Timeline styling */
.timeline-container {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-container::before {
    content: '';
    position: absolute;
    left: 0.375rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -1.375rem;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
    border: 2px solid #fff;
    z-index: 1;
}

.timeline-content {
    margin-left: 0.5rem;
}

.avatar-lg {
    width: 4rem;
    height: 4rem;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
    font-size: 0.8rem;
}

.avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.badge[class*="-subtle"] {
    font-weight: 500;
    padding: 0.35em 0.65em;
}

.fs-4 {
    font-size: 1.5rem;
}

.fs-5 {
    font-size: 1.25rem;
}

/* Table styling */
.table-danger {
    --bs-table-bg: rgba(220, 53, 69, 0.05);
}

.table-warning {
    --bs-table-bg: rgba(255, 193, 7, 0.05);
}
</style>