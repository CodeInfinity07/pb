@extends('layouts.vertical', ['title' => 'Apex Polar Area Charts', 'subTitle' => 'Charts'])

@section('content')

<div class="row">
    <div class="col-xl-9">

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="basic">
                    Basic Polar Area Chart
                </h4>
                <div dir="ltr">
                    <div id="basic-polar-area" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="monochrome">
                    Monochrome Polar Area
                </h4>
                <div dir="ltr">
                    <div id="monochrome-polar-area" class="apex-charts"></div>
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
                    <a href="#basic" class="nav-link">Basic Polar Area Chart</a>
                </li>
                <li class="nav-item">
                    <a href="#monochrome" class="nav-link">Monochrome Polar Area</a>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection

@section('script')
@vite(['resources/js/components/apexchart-polar-area.js'])
@endsection
