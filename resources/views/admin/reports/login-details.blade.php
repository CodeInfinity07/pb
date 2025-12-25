<div class="row g-2">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-3">
                <h6 class="card-title mb-2 fw-semibold">User Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted py-1">Name:</td>
                        <td class="py-1">{{ $loginLog->user ? $loginLog->user->full_name : 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Email:</td>
                        <td class="py-1">{{ $loginLog->user ? $loginLog->user->email : 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Username:</td>
                        <td class="py-1">{{ $loginLog->user ? $loginLog->user->username : 'Unknown' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-3">
                <h6 class="card-title mb-2 fw-semibold">Device Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted py-1">Device Type:</td>
                        <td class="py-1">
                            <iconify-icon icon="{{ $loginLog->device_icon }}" class="me-1"></iconify-icon>
                            {{ ucfirst($loginLog->device_type ?? 'Unknown') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Browser:</td>
                        <td class="py-1">{{ $loginLog->browser ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Platform:</td>
                        <td class="py-1">{{ $loginLog->platform ?? 'Unknown' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-3">
                <h6 class="card-title mb-2 fw-semibold">Location Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted py-1">IP Address:</td>
                        <td class="py-1"><code class="small">{{ $loginLog->ip_address }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Country:</td>
                        <td class="py-1">{{ $loginLog->country ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">City:</td>
                        <td class="py-1">{{ $loginLog->city ?? 'Unknown' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body p-3">
                <h6 class="card-title mb-2 fw-semibold">Session Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td width="40%" class="text-muted py-1">Status:</td>
                        <td class="py-1">
                            @if($loginLog->is_successful)
                                <span class="badge bg-success-subtle text-success">Success</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Failed</span>
                            @endif
                        </td>
                    </tr>
                    @if(!$loginLog->is_successful && $loginLog->failure_reason)
                    <tr>
                        <td class="text-muted py-1">Failure Reason:</td>
                        <td class="py-1">{{ $loginLog->failure_reason }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted py-1">Login Time:</td>
                        <td class="py-1">{{ $loginLog->login_at->format('M d, Y g:i:s A') }}</td>
                    </tr>
                    @if($loginLog->logout_at)
                    <tr>
                        <td class="text-muted py-1">Logout Time:</td>
                        <td class="py-1">{{ $loginLog->logout_at->format('M d, Y g:i:s A') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted py-1">Session Duration:</td>
                        <td class="py-1"><span class="badge bg-info-subtle text-info">{{ $loginLog->session_duration }}</span></td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    @if($loginLog->user_agent)
    <div class="col-12">
        <div class="card">
            <div class="card-body p-3">
                <h6 class="card-title mb-2 fw-semibold">User Agent</h6>
                <code class="small d-block" style="word-break: break-all;">{{ $loginLog->user_agent }}</code>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.table-borderless td {
    font-size: 0.875rem;
}
</style>