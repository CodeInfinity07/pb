<header class="topbar">
    <div class="container-xxl">
        <div class="navbar-header">
            <div class="d-flex align-items-center gap-2">
                <!-- Menu Toggle Button -->
                <div class="topbar-item">
                    <button type="button" class="button-toggle-menu">
                        <iconify-icon icon="iconamoon:menu-burger-horizontal" class="fs-22"></iconify-icon>
                    </button>
                </div>

                <!-- App Search-->
                <form class="app-search d-none d-md-block me-auto">
                    <div class="position-relative">
                        <input type="search" class="form-control" placeholder="Search..." autocomplete="off" value="" />
                        <iconify-icon icon="iconamoon:search-duotone" class="search-widget-icon"></iconify-icon>
                    </div>
                </form>
            </div>

            <div class="d-flex align-items-center gap-1">
                <!-- Theme Color (Light/Dark) -->
                <div class="topbar-item">
                    <button type="button" class="topbar-button" id="light-dark-mode">
                        <iconify-icon icon="iconamoon:mode-dark-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div>

                <!-- Notification -->
                <!-- Notification -->
                <div class="dropdown topbar-item">
                    <button type="button" class="topbar-button position-relative"
                        id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <iconify-icon icon="iconamoon:notification-duotone" class="fs-24 align-middle"></iconify-icon>
                        <span id="notification-badge"
                            class="position-absolute topbar-badge fs-10 translate-middle badge bg-danger rounded-pill {{ auth()->user()->unreadNotifications->count() > 0 ? '' : 'd-none' }}">
                            {{ auth()->user()->unreadNotifications->count() }}
                            <span class="visually-hidden">unread messages</span>
                        </span>
                    </button>
                    <div class="dropdown-menu py-0 dropdown-lg dropdown-menu-end"
                        aria-labelledby="page-header-notifications-dropdown"
                        style="width: 320px; max-height: 400px; overflow: hidden;">

                        <!-- Header -->
                        <div class="p-3 border-bottom bg-light">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-16 fw-semibold">
                                        Notifications
                                    </h6>
                                </div>
                                <div class="col-auto">
                                    <a href="javascript:void(0);" id="clear-all-notifications"
                                        class="text-dark text-decoration-underline">
                                        <small>Clear All</small>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Scrollable Content Area -->
                        <div id="notifications-container" data-simplebar style="max-height: 280px; overflow-y: auto;">
                            @forelse(auth()->user()->notifications->take(5) as $notification)
                                @php
                                    $data = $notification->data;
                                @endphp
                                <a href="javascript:void(0);"
                                    class="dropdown-item py-3 border-bottom text-wrap notification-item {{ $notification->read_at ? '' : 'bg-primary bg-opacity-10 border-start border-primary border-3' }}"
                                    data-id="{{ $notification->id }}" style="white-space: normal;">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0">
                                            @if(isset($data['user']) && $data['user']['avatar'])
                                                <img src="{{ asset('storage/' . $data['user']['avatar']) }}"
                                                    class="img-fluid me-2 avatar-sm rounded-circle" alt="avatar" />
                                            @elseif(isset($data['user']))
                                                <div class="avatar-sm me-2">
                                                    <span class="avatar-title bg-soft-info text-info fs-20 rounded-circle">
                                                        {{ substr($data['user']['name'], 0, 1) }}
                                                    </span>
                                                </div>
                                            @else
                                                <div class="avatar-sm me-2">
                                                    <span
                                                        class="avatar-title bg-soft-primary text-primary fs-20 rounded-circle">
                                                        <iconify-icon
                                                            icon="{{ $data['icon'] ?? 'iconamoon:notification-duotone' }}"></iconify-icon>
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="mb-1 fw-semibold">{{ $data['title'] }}</p>
                                            <p class="mb-0 text-wrap">{{ $data['message'] }}</p>
                                            <small
                                                class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="text-center p-4">
                                    <iconify-icon icon="iconamoon:notification-off-duotone"
                                        class="fs-48 text-muted"></iconify-icon>
                                    <p class="text-muted mt-2">No notifications yet</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Fixed Footer Button -->
                        <div class="border-top bg-light">
                            <div class="text-center py-2">
                                <a href="javascript:void(0);" id="view-all-notifications"
                                    class="btn btn-primary btn-sm">
                                    View All Notifications
                                    <i class="bx bx-right-arrow-alt ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Theme Setting -->
                <div class="topbar-item">
                    <button type="button" class="topbar-button" id="theme-settings-btn" data-bs-toggle="offcanvas"
                        data-bs-target="#theme-settings-offcanvas" aria-controls="theme-settings-offcanvas">
                        <iconify-icon icon="iconamoon:settings-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div>

                {{-- <!-- Activity -->
                <div class="topbar-item d-none d-md-flex">
                    <button type="button" class="topbar-button" id="theme-settings-btn" data-bs-toggle="offcanvas"
                        data-bs-target="#theme-activity-offcanvas" aria-controls="theme-settings-offcanvas">
                        <iconify-icon icon="iconamoon:history-duotone" class="fs-24 align-middle"></iconify-icon>
                    </button>
                </div> --}}

                <!-- User -->
                <div class="dropdown topbar-item">
                    <a type="button" class="topbar-button" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle" width="32"
                                src="{{ $user->profile->avatar ? asset('storage/' . $user->profile->avatar) : '/images/users/avatar-1.jpg' }}"
                                alt="avatar-3" />
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">

                        <a href="{{ route('admin.profile') }}" class="dropdown-item">
                            <iconify-icon icon="iconamoon:profile-duotone"
                                class="fs-18 align-middle me-2"></iconify-icon>
                            <span class="align-middle">Edit Profile</span>
                        </a>

                        <!-- Divider -->
                        <div class="dropdown-divider"></div>

                        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit"
                                class="dropdown-item text-danger border-0 bg-transparent w-100 text-start p-0"
                                style="cursor: pointer; padding: 0px 17px !important;">
                                <i class="bx bx-log-out fs-18 align-middle me-1"></i>
                                <span class="align-middle">Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Activity Timeline -->
<div>
    <div class="offcanvas offcanvas-end border-0" tabindex="-1" id="theme-activity-offcanvas"
        style="max-width: 450px; width: 100%">
        <div class="d-flex align-items-center bg-primary p-3 offcanvas-header">
            <h5 class="text-white m-0 fw-semibold">Activity Stream</h5>
            <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="offcanvas"
                aria-label="Close"></button>
        </div>

        <div class="offcanvas-body p-0">
            <div data-simplebar class="h-100 p-4">
                <div class="position-relative ms-2">
                    <span class="position-absolute start-0 top-0 border border-dashed h-100"></span>
                    <div class="position-relative ps-4">
                        <div class="mb-4">
                            <span
                                class="position-absolute start-0 avatar-sm translate-middle-x bg-danger d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-20"><iconify-icon
                                    icon="iconamoon:folder-check-duotone"></iconify-icon></span>
                            <div class="ms-2">
                                <h5 class="mb-1 text-dark fw-semibold fs-15 lh-base">
                                    Report-Fix / Update
                                </h5>
                                <p class="d-flex align-items-center">
                                    Add 3 files to
                                    <span class="d-flex align-items-center text-primary ms-1"><iconify-icon
                                            icon="iconamoon:file-light"></iconify-icon>
                                        Tasks</span>
                                </p>
                                <div class="bg-light bg-opacity-50 rounded-2 p-2">
                                    <div class="row">
                                        <div class="col-lg-6 border-end border-light">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bx bxl-figma fs-20 text-red"></i>
                                                <a href="#!" class="text-dark fw-medium">Concept.fig</a>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bx bxl-file-doc fs-20 text-success"></i>
                                                <a href="#!" class="text-dark fw-medium">reback.docs</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h6 class="mt-2 text-muted">
                                    Monday , 4:24 PM
                                </h6>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative ps-4">
                        <div class="mb-4">
                            <span
                                class="position-absolute start-0 avatar-sm translate-middle-x bg-success d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-20"><iconify-icon
                                    icon="iconamoon:check-circle-1-duotone"></iconify-icon></span>
                            <div class="ms-2">
                                <h5 class="mb-1 text-dark fw-semibold fs-15 lh-base">
                                    Project Status
                                </h5>
                                <p class="d-flex align-items-center mb-0">
                                    Marked<span class="d-flex align-items-center text-primary mx-1"><iconify-icon
                                            icon="iconamoon:file-light"></iconify-icon>
                                        Design
                                    </span>
                                    as
                                    <span class="badge bg-success-subtle text-success px-2 py-1 ms-1">
                                        Completed</span>
                                </p>
                                <div class="d-flex align-items-center gap-3 mt-1 bg-light bg-opacity-50 p-2 rounded-2">
                                    <a href="#!" class="fw-medium text-dark">UI/UX Figma Design</a>
                                    <div class="ms-auto">
                                        <a href="#!" class="fw-medium text-primary fs-18" data-bs-toggle="tooltip"
                                            data-bs-title="Download" data-bs-placement="bottom"><iconify-icon
                                                icon="iconamoon:cloud-download-duotone"></iconify-icon></a>
                                    </div>
                                </div>
                                <h6 class="mt-3 text-muted">
                                    Monday , 3:00 PM
                                </h6>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative ps-4">
                        <div class="mb-4">
                            <span
                                class="position-absolute start-0 avatar-sm translate-middle-x bg-primary d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-16">UI</span>
                            <div class="ms-2">
                                <h5 class="mb-1 text-dark fw-semibold fs-15">
                                    Reback Application UI v2.0.0
                                    <span class="badge bg-primary-subtle text-primary px-2 py-1 ms-1">
                                        Latest</span>
                                </h5>
                                <p>
                                    Get access to over 20+ pages including a
                                    dashboard layout, charts, kanban board,
                                    calendar, and pre-order E-commerce &
                                    Marketing pages.
                                </p>
                                <div class="mt-2">
                                    <a href="#!" class="btn btn-light btn-sm">Download Zip</a>
                                </div>
                                <h6 class="mt-3 text-muted">
                                    Monday , 2:10 PM
                                </h6>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative ps-4">
                        <div class="mb-4">
                            <span
                                class="position-absolute start-0 translate-middle-x bg-success bg-gradient d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-20"><img
                                    src="/images/users/avatar-7.jpg" alt="avatar-5"
                                    class="avatar-sm rounded-circle" /></span>
                            <div class="ms-2">
                                <h5 class="mb-0 text-dark fw-semibold fs-15 lh-base">
                                    Alex Smith Attached Photos
                                </h5>
                                <div class="row g-2 mt-2">
                                    <div class="col-lg-4">
                                        <a href="#!">
                                            <img src="/images/small/img-6.jpg" alt="" class="img-fluid rounded" />
                                        </a>
                                    </div>
                                    <div class="col-lg-4">
                                        <a href="#!">
                                            <img src="/images/small/img-3.jpg" alt="" class="img-fluid rounded" />
                                        </a>
                                    </div>
                                    <div class="col-lg-4">
                                        <a href="#!">
                                            <img src="/images/small/img-4.jpg" alt="" class="img-fluid rounded" />
                                        </a>
                                    </div>
                                </div>
                                <h6 class="mt-3 text-muted">Monday 1:00 PM</h6>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative ps-4">
                        <div class="mb-4">
                            <span
                                class="position-absolute start-0 translate-middle-x bg-success bg-gradient d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-20"><img
                                    src="/images/users/avatar-6.jpg" alt="avatar-5"
                                    class="avatar-sm rounded-circle" /></span>
                            <div class="ms-2">
                                <h5 class="mb-0 text-dark fw-semibold fs-15 lh-base">
                                    Rebecca J. added a new team member
                                </h5>
                                <p class="d-flex align-items-center gap-1">
                                    <iconify-icon icon="iconamoon:check-circle-1-duotone"
                                        class="text-success"></iconify-icon>
                                    Added a new member to Front Dashboard
                                </p>
                                <h6 class="mt-3 text-muted">Monday 10:00 AM</h6>
                            </div>
                        </div>
                    </div>
                    <div class="position-relative ps-4">
                        <div class="mb-4">
                            <span
                                class="position-absolute start-0 avatar-sm translate-middle-x bg-warning d-inline-flex align-items-center justify-content-center rounded-circle text-light fs-20"><iconify-icon
                                    icon="iconamoon:certificate-badge-duotone"></iconify-icon></span>
                            <div class="ms-2">
                                <h5 class="mb-0 text-dark fw-semibold fs-15 lh-base">
                                    Achievements
                                </h5>
                                <p class="d-flex align-items-center gap-1 mt-1">
                                    Earned a
                                    <iconify-icon icon="iconamoon:certificate-badge-duotone"
                                        class="text-danger fs-20"></iconify-icon>" Best Product Award"
                                </p>
                                <h6 class="mt-3 text-muted">Monday 9:30 AM</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <a href="#!" class="btn btn-outline-dark w-100">View All</a>
            </div>
        </div>
    </div>
</div>