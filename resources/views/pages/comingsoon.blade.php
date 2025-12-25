@extends('layouts.auth', ['title' => 'Coming Soon'])

@section('content')

    <div class="col-lg-10">
        <div class="card auth-card text-center">
            <div class="card-body">
                <div class="mx-auto text-center auth-logo my-5">
                    <a href="{{ route('home')}}" class="logo-dark">
                        <img src="/images/logo-dark.png" height="60" alt="logo dark" />
                    </a>

                    <a href="{{ route('home')}}" class="logo-light">
                        <img src="/images/logo-light.png" height="60" alt="logo light" />
                    </a>
                </div>

                <h2 class="fw-semibold">
                    We Are Launching Soon...
                </h2>
                <p class="lead mt-3 w-75 mx-auto pb-4 fst-italic">
                    Exciting news is on the horizon! We're
                    thrilled to announce that something
                    incredible is coming your way very soon. Our
                    team has been hard at work behind the
                    scenes, crafting something special just for
                    you.
                </p>

                <div class="row my-5">
                    <div class="col">
                        <h3 id="days" class="fw-bold fs-60">
                            00
                        </h3>
                        <p class="text-uppercase fw-semibold">
                            Days
                        </p>
                    </div>
                    <div class="col">
                        <h3 id="hours" class="fw-bold fs-60">
                            00
                        </h3>
                        <p class="text-uppercase fw-semibold">
                            Hours
                        </p>
                    </div>
                    <div class="col">
                        <h3 id="minutes" class="fw-bold fs-60">
                            00
                        </h3>
                        <p class="text-uppercase fw-semibold">
                            Minutes
                        </p>
                    </div>
                    <div class="col">
                        <h3 id="seconds" class="fw-bold fs-60">
                            00
                        </h3>
                        <p class="text-uppercase fw-semibold">
                            Seconds
                        </p>
                    </div>
                </div>

                <a href="{{ route('second', ['pages', 'contact-us'])}}" class="btn btn-success mb-5">Contact Us</a>
            </div>
        </div>
    </div>
    <!-- end col -->

@endsection

@section('script')
    @vite(['resources/js/pages/coming-soon.js'])
@endsection