@extends('layouts.vertical', ['title' => 'Apex Boxplot Chart', 'subTitle' => 'CHarts'])

@section('content')

<div class="row">
    <div class="col-xl-9">

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="basic">
                    Basic Boxplot
                </h4>
                <div dir="ltr">
                    <div id="basic-boxplot" class="apex-charts"></div>
                </div>
            </div>
            <!-- end card body-->
        </div>
        <!-- end card -->

        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3 anchor" id="scatter">
                    Scatter Boxplot
                </h4>
                <div dir="ltr">
                    <div id="scatter-boxplot" class="apex-charts"></div>
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
                    <a href="#basic" class="nav-link">Basic Boxplot</a>
                </li>
                <li class="nav-item">
                    <a href="#scatter" class="nav-link">Scatter Boxplot</a>
                </li>
            </ul>
        </div>
    </div>
</div>

@endsection

@section('script')
@vite(['resources/js/components/apexchart-boxplot.js'])
@endsection
