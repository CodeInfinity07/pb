<!doctype html>
<html lang="en">
<head>
    @include('admin.layouts.partials/title-meta', ['title' => $title])
    @yield('css')
    @include('admin.layouts.partials/head-css')
</head>

<body class="authentication-bg">
<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
    <div class="container">
        <div class="row justify-content-center">
            @yield('content')
        </div>

    </div>
</div>

@include('admin.layouts.partials/footer-scripts')
</body>
</html>
