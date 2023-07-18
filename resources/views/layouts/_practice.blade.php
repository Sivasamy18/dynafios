@php use function App\Start\is_hospital_owner; @endphp
@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\is_practice_manager; @endphp
@extends('layouts/_dashboard')
@section('main')
<div class="page-header practice-page-header">
    <div class="row">
        <div class="col-xs-1">
            <i class="fa fa-user-md fa-fw icon"></i>
        </div>
        <div class="col-xs-7">
            <h3>{{ $practice->name }}</h3>
            <ul class="info">
                <li><i class="fa fa-hospital-o"></i>
                    @if (is_hospital_owner($practice->hospital->id))
                    <a href="{{ route('hospitals.show', $practice->hospital->id) }}">
                        {{ $practice->hospital->name }}
                    </a>
                    @else
                    {{ $practice->hospital->name }}
                    @endif
                </li>
                <li>
                    <i class="fa fa-user"></i>
                    @if ($practice->getPrimaryManager())
                        {{ $practice->getPrimaryManager()->getFullName() }}
                    @else
                        No Primary Manager
                    @endif
                </li>
            </ul>
        </div>
        <div class="col-xs-4">
            <div class="btn-group btn-group-sm">
                @yield('actions')
            </div>
        </div>
    </div>
    <ul class="nav nav-tabs">
        <li class="{{ HTML::active($tab == 1) }}">
            <a href="{{ URL::route('practices.show', $practice->id) }}">Overview</a>
        </li>
        @if (is_super_user() || is_super_hospital_user() || is_practice_manager())
            <li class="{{ HTML::active($tab == 6) }}">
                <a href="{{ URL::route('practices.contracts', $practice->id) }}">Contracts</a>
            </li>
        @endif
        <li class="{{ HTML::active($tab == 2) }}">
            <a href="{{ URL::route('practices.managers', $practice->id) }}">Managers</a>
        </li>
        <li class="{{ HTML::active($tab == 3) }}">
            <a href="{{ URL::route('practices.physicians', $practice->id) }}">Physicians</a>
        </li>
        @if (is_super_user() || is_super_hospital_user())
            <li class="{{ HTML::active($tab == 4) }}">
                <a href="{{ URL::route('practices.reports', $practice->id) }}">Reports</a>
            </li>
            <li class="{{ HTML::active($tab == 5) }}">
                <a href="{{ URL::route('practices.edit', $practice->id) }}">Settings</a>
            </li>
        @endif
        @if (is_practice_manager())
            <li class="{{ HTML::active($tab == 4) }}">
                <a href="{{ URL::route('practiceManager.reports', [$practice->id,$practice->hospital->id]) }}">Reports</a>
            </li>
        @endif
    </ul>
</div>
@include('layouts/_flash')
@yield('content')
@endsection
