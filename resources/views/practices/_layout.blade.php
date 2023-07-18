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
                <li>
                    <i class="fa fa-user"></i>
                    @if (count($practice->users) > 0)
                        {{ "{$practice->users[0]->first_name} {$practice->users[0]->last_name}" }}
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
    <li class="{{ HTML::active($tab == 2) }}">
        <a href="{{ URL::route('practices.managers', $practice->id) }}">Managers</a>
    </li>
    <li class="{{ HTML::active($tab == 3) }}">
        <a href="{{ URL::route('practices.physicians', $practice->id) }}">Physicians</a>
    </li>
    <li class="{{ HTML::active($tab == 4) }}">
        <a href="{{ URL::route('practices.reports', $practice->id) }}">Reports</a>
    </li>    
    <li class="{{ HTML::active($tab == 5) }}">
        <a href="{{ URL::route('practices.edit', $practice->id) }}">Settings</a>
    </li>
</ul>
</div>
@include('layouts/_flash')
@yield('content')
@endsection