@extends('layouts.vertical', ['title' => 'Apex Heatmap Charts', 'subTitle' => 'Charts'])

@section('content')

<div class="row">
    <div class="col-xl-9">

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="basic">
                    Basic Heatmap - Single Series
                </h4>
                <div dir="ltr">
                    <div id="basic-heatmap" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="multiple-series">
                    Heatmap - Multiple Series
                </h4>
                <div dir="ltr">
                    <div id="multiple-series-heatmap" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="color-range">
                    Heatmap - Color Range
                </h4>
                <div dir="ltr">
                    <div id="color-range-heatmap" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="rounded">
                    Heatmap - Range without Shades
                </h4>
                <div dir="ltr">
                    <div id="rounded-heatmap" class="apex-charts"></div>
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
                    <a href="#basic" class="nav-link">Basic Heatmap - Single Series</a>
                </li>
                <li class="nav-item">
                    <a href="#multiple-series" class="nav-link">Heatmap - Multiple Series</a>
                </li>
                <li class="nav-item">
                    <a href="#color-range" class="nav-link">Heatmap - Color Range</a>
                </li>
                <li class="nav-item">
                    <a href="#rounded" class="nav-link">Heatmap - Range without Shades</a>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection

@section('script')
@vite(['resources/js/components/apexchart-heatmap.js'])
@endsection
