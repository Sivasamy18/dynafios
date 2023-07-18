@php use function App\Start\is_super_user; @endphp
@extends('layouts/_dashboard')
@section('main')
    <div class="page-header hospital-page-header">
        <div class="row">
            <div class="col-xs-1">
                <i class="fa fa-globe fa-fw icon"></i>
            </div>
            <div class="col-xs-5">
                <h3>Health System : {{ "{$system->health_system_name}" }}</h3>
                <h3>Region : {{ "{$region->region_name}" }}</h3>
            </div>
            <div class="col-xs-6">
                <div class="btn-group btn-group-sm">
                    @yield('actions')
                </div>
            </div>
        </div>
        <ul class="nav nav-tabs">
            <li class="{{ HTML::active($tab == 1) }}">
                <a href="{{ URL::route('healthSystemRegion.show', [$system->id,$region->id]) }}">Overview</a>
            </li>
            @if (is_super_user())
                <li class="{{ HTML::active($tab == 2) }}">
                    <a href="{{ URL::route('healthSystemRegion.users', [$system->id,$region->id]) }}">Users</a>
                </li>
                <li class="{{ HTML::active($tab == 3) }}">
                    <a href="{{ URL::route('healthSystemRegion.hospitals', [$system->id,$region->id]) }}">Hospitals</a>
                </li>
                <li class="{{ HTML::active($tab == 4) }}">
                    <a href="{{ URL::route('healthSystemRegion.edit', [$system->id ,$region->id]) }}">Settings</a>
                </li>
            @endif
        </ul>
    </div>
    @include('layouts/_flash')
    @yield('content')
@endsection
