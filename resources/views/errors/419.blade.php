@extends('layouts.auth', ['title' => 'Page Expired - 419'])

@section('content')

<div class="col-xl-5">
    <div class="card auth-card">
        <div class="card-body px-3 py-5">
            <div class="p-4">
                <div class="mx-auto mb-4 text-center">
                    <div class="mx-auto mb-4 text-center auth-logo">
                        <a href="{{ route('dashboard')}}" class="logo-dark">
                            <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                        </a>

                        <a href="{{ route('dashboard')}}" class="logo-light">
                            <img src="/images/logo-light.png" height="60" alt="logo light" />
                        </a>
                    </div>

                    <h1 class="mt-5 mb-3 fw-bold fs-60">
                        419
                    </h1>
                    <h2 class="fs-22 lh-base">
                        Page Expired !
                    </h2>
                    <p class="text-muted mt-1 mb-4">
                        Your session has expired. <br />
                        Please refresh the page and try again.
                    </p>

                    <div class="text-center">
                        <a href="javascript:location.reload()" class="btn btn-success">Refresh Page</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- end card-body -->
    </div>
    <!-- end card -->
</div>

@endsection