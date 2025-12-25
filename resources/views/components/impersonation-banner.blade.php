{{-- Create this file: resources/views/components/impersonation-banner.blade.php --}}

@php
    $originalAdminId = Session::get('impersonation.original_admin_id');
    $targetUserId = Session::get('impersonation.target_user_id'); 
    $startedAt = Session::get('impersonation.started_at');
    
    $isImpersonating = $originalAdminId && $targetUserId;
    
    if ($isImpersonating) {
        try {
            $originalAdmin = \App\Models\User::find($originalAdminId);
            $currentUser = auth()->user();
            $duration = $startedAt ? now()->diffForHumans($startedAt, true) : 'Unknown';
        } catch (Exception $e) {
            $isImpersonating = false;
        }
    }
@endphp

@if($isImpersonating && isset($originalAdmin) && isset($currentUser))
<div class="impersonation-banner bg-warning text-dark py-2 sticky-top" style="z-index: 1030;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col">
                <div class="d-flex align-items-center">
                    <iconify-icon icon="iconamoon:shield-warning-duotone" class="fs-5 me-2"></iconify-icon>
                    <div class="fw-semibold me-2">IMPERSONATING:</div>
                    <div class="me-3">
                        <strong>{{ $currentUser->full_name }}</strong>
                        <span class="text-muted">({{ $currentUser->email }})</span>
                    </div>
                    <div class="small text-muted">
                        Started {{ $duration }} ago as {{ $originalAdmin->full_name }}
                    </div>
                </div>
            </div>
            <div class="col-auto">
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('admin.impersonation.stop') }}" class="btn btn-dark btn-sm d-flex align-items-center">
                        <iconify-icon icon="iconamoon:exit-duotone" class="me-1"></iconify-icon>
                        Stop Impersonation
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.impersonation-banner {
    border-bottom: 2px solid #ffc107;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.impersonation-banner .btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

@media (max-width: 768px) {
    .impersonation-banner .row {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .impersonation-banner .col-auto {
        width: 100%;
    }
    
    .impersonation-banner .d-flex.gap-2 {
        justify-content: center;
    }
}
</style>
@endif