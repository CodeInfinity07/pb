<!doctype html>
<html lang="en">

<head>
    @include('admin.layouts.partials/title-meta', ['title' => $title])
    @yield('css')
    @include('admin.layouts.partials/head-css')

    <!-- ADD THIS: CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Add in the head section or before closing body --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>

<body>

    <div class="wrapper">
        @include('admin.layouts.partials/topbar')
        @include('admin.layouts.partials/left-sidebar')

        <div class="page-content">

            <div class="container-xxl">
                @include("admin.layouts.partials/page-title", ['title' => $title, 'subTitle' => $subTitle])
                @yield('content')
            </div>

            @include("admin.layouts.partials/footer")
        </div>

    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @include("admin.layouts.partials/right-sidebar")
    @include("admin.layouts.partials/footer-scripts")

    <!-- ADD THIS: Notifications JavaScript -->
    @yield('vite_scripts')
    @vite(['resources/js/notifications.js'])
    @vite(['resources/js/app.js'])

</body>

</html>