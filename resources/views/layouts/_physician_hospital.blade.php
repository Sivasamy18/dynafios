@extends('layouts/_dashboard')
@section('main')
<div class="page-header physician-page-header">
    <div class="row">
        <div class="col-xs-1">
            <i class="fa fa-user-md fa-fw icon"></i>
        </div>
        <div class="col-xs-7">
            <h3>{{ "{$physician->first_name} {$physician->last_name}" }}</h3>
        </div>
        <div class="col-xs-4">
            <div class="btn-group btn-group-sm">
                @yield('actions')
            </div>
        </div>
    </div>
</div>
@include('layouts/_flash')
@yield('content')
@endsection
