{{-- admin/kyc/details.blade.php --}}
<div class="container-fluid">
    {{-- User Overview Card --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-lg rounded-circle bg-primary me-3">
                                <span class="avatar-title text-white fs-5">{{ $user->initials }}</span>
                            </div>
                            <div>
                                <h5 class="mb-1">{{ $kycDetails['user']['name'] }}</h5>
                                <p class="text-muted mb-0">{{ $kycDetails['user']['email'] }}</p>
                                <small class="text-muted">Member since {{ $kycDetails['user']['joined'] }}</small>
                            </div>
                        </div>
                        <div class="text-end d-none d-md-block">
                            <span class="badge bg-{{ $kycDetails['profile']['kyc_status'] === 'verified' ? 'success' : ($kycDetails['profile']['kyc_status'] === 'rejected' ? 'danger' : ($kycDetails['profile']['kyc_status'] === 'under_review' ? 'warning' : 'secondary')) }}-subtle text-{{ $kycDetails['profile']['kyc_status'] === 'verified' ? 'success' : ($kycDetails['profile']['kyc_status'] === 'rejected' ? 'danger' : ($kycDetails['profile']['kyc_status'] === 'under_review' ? 'warning' : 'secondary')) }} px-3 py-2 fs-6">
                                {{ ucwords(str_replace('_', ' ', $kycDetails['profile']['kyc_status'])) }}
                            </span>
                        </div>
                    </div>
                    
                    {{-- Mobile Status --}}
                    <div class="d-md-none mt-3">
                        <span class="badge bg-{{ $kycDetails['profile']['kyc_status'] === 'verified' ? 'success' : ($kycDetails['profile']['kyc_status'] === 'rejected' ? 'danger' : ($kycDetails['profile']['kyc_status'] === 'under_review' ? 'warning' : 'secondary')) }}-subtle text-{{ $kycDetails['profile']['kyc_status'] === 'verified' ? 'success' : ($kycDetails['profile']['kyc_status'] === 'rejected' ? 'danger' : ($kycDetails['profile']['kyc_status'] === 'under_review' ? 'warning' : 'secondary')) }} px-3 py-2">
                            <iconify-icon icon="iconamoon:{{ $kycDetails['profile']['kyc_status'] === 'verified' ? 'check-circle' : ($kycDetails['profile']['kyc_status'] === 'rejected' ? 'close-circle' : ($kycDetails['profile']['kyc_status'] === 'under_review' ? 'clock' : 'file')) }}-duotone" class="me-1"></iconify-icon>
                            {{ ucwords(str_replace('_', ' ', $kycDetails['profile']['kyc_status'])) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Personal Information --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:profile-duotone" class="me-2"></iconify-icon>
                        Personal Information
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Desktop View --}}
                    <div class="d-none d-lg-block">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="text-muted small mb-1">Full Name</div>
                                <div class="fw-medium">{{ $kycDetails['user']['name'] }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small mb-1">Email Address</div>
                                <div class="fw-medium">{{ $kycDetails['user']['email'] }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small mb-1">Phone Number</div>
                                <div class="fw-medium">{{ $kycDetails['user']['phone'] ?: 'Not provided' }}</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small mb-1">Date of Birth</div>
                                <div class="fw-medium">{{ $kycDetails['profile']['date_of_birth'] }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted small mb-1">Country</div>
                                <div class="fw-medium d-flex align-items-center">
                                    @if($user->profile && $user->profile->country)
                                        <img src="https://flagcdn.com/24x18/{{ strtolower($user->profile->country) }}.png" alt="{{ $user->profile->country }}" class="me-2" style="width: 20px;">
                                    @endif
                                    {{ $kycDetails['profile']['country'] }}
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small mb-1">City</div>
                                <div class="fw-medium">{{ $kycDetails['profile']['city'] }}</div>
                            </div>
                            @if($kycDetails['profile']['address'] !== 'Not provided')
                            <div class="col-12">
                                <div class="text-muted small mb-1">Address</div>
                                <div class="fw-medium">{{ $kycDetails['profile']['address'] }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Mobile View --}}
                    <div class="d-lg-none">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Full Name</span>
                                    <span class="fw-medium">{{ $kycDetails['user']['name'] }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Email</span>
                                    <span class="fw-medium text-end">{{ Str::limit($kycDetails['user']['email'], 20) }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Phone</span>
                                    <span class="fw-medium">{{ $kycDetails['user']['phone'] ?: 'Not provided' }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Date of Birth</span>
                                    <span class="fw-medium">{{ $kycDetails['profile']['date_of_birth'] }}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Country</span>
                                    <div class="d-flex align-items-center">
                                        @if($user->profile && $user->profile->country)
                                            <img src="https://flagcdn.com/24x18/{{ strtolower($user->profile->country) }}.png" alt="{{ $user->profile->country }}" class="me-2" style="width: 16px;">
                                        @endif
                                        <span class="fw-medium">{{ $kycDetails['profile']['country'] }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">City</span>
                                    <span class="fw-medium">{{ $kycDetails['profile']['city'] }}</span>
                                </div>
                            </div>
                            @if($kycDetails['profile']['address'] !== 'Not provided')
                            <div class="col-12">
                                <div class="text-muted small mb-1">Address</div>
                                <div class="fw-medium small">{{ $kycDetails['profile']['address'] }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KYC Status & Timeline --}}
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:shield-duotone" class="me-2"></iconify-icon>
                        KYC Status & Timeline
                    </h6>
                </div>
                <div class="card-body">
                    {{-- Status Timeline --}}
                    <div class="timeline-container">
                        @if($kycDetails['profile']['kyc_submitted_at'])
                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <div class="fw-medium">Documents Submitted</div>
                                <small class="text-muted">{{ $kycDetails['profile']['kyc_submitted_at'] }}</small>
                            </div>
                        </div>
                        @endif

                        @if($kycDetails['profile']['kyc_status'] === 'under_review')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <div class="fw-medium">Under Review</div>
                                <small class="text-muted">Currently being processed</small>
                            </div>
                        </div>
                        @endif

                        @if($kycDetails['profile']['kyc_verified_at'])
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="fw-medium text-success">Verified</div>
                                <small class="text-muted">{{ $kycDetails['profile']['kyc_verified_at'] }}</small>
                            </div>
                        </div>
                        @endif

                        @if($kycDetails['profile']['kyc_rejection_reason'])
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <div class="fw-medium text-danger">Rejected</div>
                                <small class="text-muted">{{ $kycDetails['profile']['kyc_rejection_reason'] }}</small>
                            </div>
                        </div>
                        @endif

                        @if(!$kycDetails['profile']['kyc_submitted_at'] && $kycDetails['profile']['kyc_status'] === 'not_submitted')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <div class="fw-medium text-muted">Not Submitted</div>
                                <small class="text-muted">No documents uploaded yet</small>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex flex-wrap gap-2">
                            @if($kycDetails['profile']['kyc_status'] !== 'verified')
                            <button type="button" class="btn btn-sm btn-success" onclick="updateKycStatus('{{ $user->id }}', 'verified')">
                                Approve
                            </button>
                            @endif
                            @if($kycDetails['profile']['kyc_status'] !== 'under_review')
                            <button type="button" class="btn btn-sm btn-warning" onclick="updateKycStatus('{{ $user->id }}', 'under_review')">
                                Under Review
                            </button>
                            @endif
                            @if($kycDetails['profile']['kyc_status'] !== 'rejected')
                            <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal('{{ $user->id }}', '{{ $user->full_name }}')">
                                Reject
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Verification History --}}
    @if(count($kycDetails['verifications']) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <iconify-icon icon="iconamoon:history-duotone" class="me-2"></iconify-icon>
                        Verification History ({{ count($kycDetails['verifications']) }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    {{-- Desktop Table View --}}
                    <div class="d-none d-lg-block">
                        <div class="table-responsive table-card">
                            <table class="table table-borderless table-hover table-nowrap align-middle mb-0">
                                <thead class="bg-light bg-opacity-50 thead-sm">
                                    <tr>
                                        <th scope="col">Date & Time</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Decision</th>
                                        <th scope="col">Document Info</th>
                                        <th scope="col">Verified Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kycDetails['verifications'] as $verification)
                                    <tr>
                                        <td>
                                            <div class="fw-medium">{{ \Carbon\Carbon::parse($verification['created_at'])->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($verification['created_at'])->format('g:i A') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $verification['status'] === 'success' ? 'success' : ($verification['status'] === 'failed' ? 'danger' : 'warning') }}-subtle text-{{ $verification['status'] === 'success' ? 'success' : ($verification['status'] === 'failed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($verification['status']) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($verification['decision'])
                                            <span class="badge bg-{{ $verification['decision'] === 'approved' ? 'success' : ($verification['decision'] === 'declined' ? 'danger' : 'info') }}-subtle text-{{ $verification['decision'] === 'approved' ? 'success' : ($verification['decision'] === 'declined' ? 'danger' : 'info') }}">
                                                {{ ucfirst($verification['decision']) }}
                                            </span>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($verification['document_type'])
                                            <div class="fw-medium">{{ $verification['document_type'] }}</div>
                                            @if($verification['document_number'])
                                            <code class="small">{{ $verification['document_number'] }}</code>
                                            @endif
                                            @if($verification['document_country'])
                                            <div class="d-flex align-items-center mt-1">
                                                <img src="https://flagcdn.com/16x12/{{ strtolower($verification['document_country']) }}.png" alt="{{ $verification['document_country'] }}" class="me-1">
                                                <small class="text-muted">{{ $verification['document_country'] }}</small>
                                            </div>
                                            @endif
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($verification['verified_name'])
                                            <div class="fw-medium">{{ $verification['verified_name'] }}</div>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Mobile Card View --}}
                    <div class="d-lg-none p-3">
                        <div class="row g-3">
                            @foreach($kycDetails['verifications'] as $verification)
                            <div class="col-12">
                                <div class="card verification-card border">
                                    <div class="card-body p-3">
                                        {{-- Header Row --}}
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div class="d-flex gap-2">
                                                <span class="badge bg-{{ $verification['status'] === 'success' ? 'success' : ($verification['status'] === 'failed' ? 'danger' : 'warning') }}-subtle text-{{ $verification['status'] === 'success' ? 'success' : ($verification['status'] === 'failed' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($verification['status']) }}
                                                </span>
                                                @if($verification['decision'])
                                                <span class="badge bg-{{ $verification['decision'] === 'approved' ? 'success' : ($verification['decision'] === 'declined' ? 'danger' : 'info') }}-subtle text-{{ $verification['decision'] === 'approved' ? 'success' : ($verification['decision'] === 'declined' ? 'danger' : 'info') }}">
                                                    {{ ucfirst($verification['decision']) }}
                                                </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Verification Details --}}
                                        <div class="border-top pt-3">
                                            <div class="row g-2 small">
                                                @if($verification['verified_name'])
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Verified Name</span>
                                                        <span class="fw-medium">{{ $verification['verified_name'] }}</span>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($verification['document_type'])
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Document Type</span>
                                                        <span class="fw-medium">{{ $verification['document_type'] }}</span>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($verification['document_number'])
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Document Number</span>
                                                        <code class="small">{{ $verification['document_number'] }}</code>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($verification['document_country'])
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="text-muted">Document Country</span>
                                                        <div class="d-flex align-items-center">
                                                            <img src="https://flagcdn.com/16x12/{{ strtolower($verification['document_country']) }}.png" alt="{{ $verification['document_country'] }}" class="me-1">
                                                            <span>{{ $verification['document_country'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                @if($verification['verified_at'])
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between">
                                                        <span class="text-muted">Verified At</span>
                                                        <span>{{ $verification['verified_at'] }}</span>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    {{-- No Verification History --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <iconify-icon icon="iconamoon:file-text-duotone" class="fs-1 text-muted mb-3"></iconify-icon>
                    <h6 class="text-muted">No Verification Attempts</h6>
                    <p class="text-muted mb-0">This user has not attempted any identity verifications yet.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>