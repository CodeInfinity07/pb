@extends('layouts.vertical', ['title' => 'Apex Bubble Charts', 'subTitle' => 'Charts'])

@section('content')

<div class="row">
    <div class="col-xl-9">

        <div class="card">
            <div class="card-body">
                <h4 class="card-title anchor" id="simple">
                    Simple Bubble Chart
                </h4>
                <div dir="ltr">
                    <div id="simple-bubble" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->

        <div class="card">
            <div class="card-body">
                <h4 class="card-title anchor" id="3d-bubble">
                    3D Bubble Chart
                </h4>
                <div dir="ltr">
                    <div id="second-bubble" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
    </div>
    <!-- end col -->

    <div class="col-xl-3">
        <div class="card docs-nav">
            <ul class="nav bg-transparent flex-column">
                <li class="nav-item">
                    <a href="#simple" class="nav-link">Simple Bubble Chart</a>
                </li>
                <li class="nav-item">
                    <a href="#3d-bubble" class="nav-link">3D Bubble Chart</a>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection

@section('script')
@vite(['resources/js/components/apexchart-bubble.js'])
@endsection
