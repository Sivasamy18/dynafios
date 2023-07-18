@php use function App\Start\is_practice_owner; @endphp
@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp

@extends('layouts/_dashboard')
@section('main')
<div class="page-header physician-page-header">
    <div class="row">
        <div class="col-xs-1">
            <i class="fa fa-user-md fa-fw icon"></i>
        </div>
        <div class="col-xs-7">
            <h3>{{ "{$physician->first_name} {$physician->last_name}" }}</h3>
            <ul class="info">
                <li><i class="fa fa-hospital-o"></i>
                <!-- physician to multiple hospital by 1254 -->
                @if (is_practice_owner($practice->id))
                <a href="{{ route('practices.show',$practice->id) }}">
                {{ $practice->name  }}
                </a>
                @else
                {{ $practice->name }}
                @endif
                </li>
                <li><i class="fa fa-star"></i> {{ $physician->specialty->name }}</li>
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
            <a href="{{ URL::route('physicians.show', [$physician->id,$practice->id]) }}">Overview</a>
        </li>
        <li class="{{ HTML::active($tab == 2) }}">
            
              <a href="{{ URL::route('physicians.contracts', [$physician->id,$practice->id]) }}">Contracts</a>
        </li>
        <li class="{{ HTML::active($tab == 3) }}">
            
            <a href="{{ URL::route('physicians.logs', [$physician->id,$practice->id]) }}">Logs</a>
        </li>
        @if (is_super_user() || is_super_hospital_user())
            <li class="{{ HTML::active($tab == 4) }}">
                
                <a href="{{ URL::route('physicians.reports', [$physician->id,$practice->id]) }}">Reports</a>
            </li>
        @endif
        @if (is_super_user() || is_super_hospital_user())
            <li class="{{ HTML::active($tab == 6) }}">
                
                <a href="{{ URL::route('physicians.edit', [$physician->id,$practice->id]) }}">Settings</a>
            </li>
        @endif
    </ul>
</div>
@include('layouts/_flash')
@yield('content')
@endsection
