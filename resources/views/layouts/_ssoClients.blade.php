@php use function App\Start\is_super_user; @endphp
@extends('layouts/_dashboard')
@section('main')
    <div class="page-header">
        <div class="row">
            <!--<div class="col-xs-1">-->
                <!--<i class="fa fa-globe fa-fw icon"></i>-->
            <!--</div>-->
            <div class="col-xs-6">
                <h3>
                    <i class="fa fa-laptop icon"></i> SSO Client : {{ "{$system->health_system_name}" }}
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
        <ul class="nav nav-tabs">
            @if (is_super_user())
                <li class="{{ HTML::active($tab == 1) }}">
                    <a href="{{ URL::route('healthSystem.show', $system->id) }}">Add what you may</a>
                </li>
            @endif
        </ul>
    </div>
    @include('layouts/_flash')
    @yield('content')
@endsection
