@extends('layouts/_dashboard')
@section('main')
    <div class="page-header">
        <div class="row">

            <div class="col-xs-6">
                <h3>
                    <i class="fa fa-globe fa-fw icon"></i> Health System
                </h3>
            </div>
            <div class="col-xs-6">
                <div class="btn-group btn-group-sm">
                    @yield('actions')
                </div>
            </div>
        </div>
    </div>
    <div class="page-header hospital-page-header">
  
    </div>
    @include('layouts/_flash')
    @yield('content')
@endsection
