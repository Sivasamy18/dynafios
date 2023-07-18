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
                    <i class="fa fa-globe fa-fw icon"></i> Health System : {{ "{$system->health_system_name}" }}
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
                    <a href="{{ URL::route('healthSystem.show', $system->id) }}">Overview</a>
                </li>
                <li class="{{ HTML::active($tab == 2) }}">
                    <a href="{{ URL::route('healthSystem.regions', $system->id) }}">Regions</a>
                </li>
                <li class="{{ HTML::active($tab == 3) }}">
                    <a href="{{ URL::route('healthSystem.users', $system->id) }}">Users</a>
                </li>
                <li class="{{ HTML::active($tab == 4) }}">
                    <a href="{{ URL::route('healthSystem.edit', $system->id) }}">Settings</a>
                </li>
            @endif
        </ul>
    </div>
    @include('layouts/_flash')
    @yield('content')
@endsection
