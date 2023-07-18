@php use function App\Start\is_practice_owner; @endphp
@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_hospital_admin; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\has_invoice_access; @endphp

@extends('layouts/_dashboard')
@section('main')
    @if(Request::is('agreements/*')) {{-- This will load the hospical screen view --}}
        <div class="page-header hospital-page-header">
            <div class="row">
                <div class="col-xs-1">
                    <i class="fa fa-hospital-o fa-fw icon"></i>
                </div>
                <div class="col-xs-5">
                    <h3>{{ "{$hospital->name}" }}</h3>
                    <ul class="info">
                        <li>
                            <i class="fa fa-user"></i>
                            @if ($hospital->getPrimaryUser())
                                {{ $hospital->getPrimaryUser()->getFullName() }}
                            @else
                                No Primary Admin
                            @endif
                        </li>
                    </ul>
                </div>
                <div class="col-xs-6">
                    <div class="btn-group btn-group-sm">
                        @yield('actions')
                    </div>
                </div>
            </div>
            <ul class="nav nav-tabs">
                <li class="{{ HTML::active($tab == 1) }}">
                    <a href="{{ URL::route('hospitals.show', $hospital->id) }}">Overview</a>
                </li>
                @if (is_super_user() || is_hospital_admin() || is_super_hospital_user())
                    <li class="{{ HTML::active($tab == 2) }}">
                        <a href="{{ URL::route('hospitals.agreements', $hospital->id) }}">Agreements</a>
                    </li>
                @endif
            <!-- # added new contract approvers tab by #1254 -->
                @if ( is_super_hospital_user())
                    <li class="{{ HTML::active($tab == 11) }}">
                        <a href="{{ URL::route('hospitals.approvers', $hospital->id) }}">Contract Approvers</a>
                    </li>
                @endif
                <li class="{{ HTML::active($tab == 3) }}">
                    <a href="{{ URL::route('hospitals.admins', $hospital->id) }}">Users</a>
                </li>
                <li class="{{ HTML::active($tab == 4) }}">
                    <a href="{{ URL::route('hospitals.practices', $hospital->id) }}">Practices</a>
                </li>
                <li class="{{ HTML::active($tab == 5) }}">
                    <a href="{{ URL::route('hospitals.reports', $hospital->id) }}">Reports</a>
                </li>
                @if(has_invoice_access() && $hospital->invoice_dashboard_on_off == 1)
                    <li class="{{ HTML::active($tab == 9) }}">
                        <a href="{{ URL::route('agreements.payment', $hospital->id) }}">Invoice</a>
                    </li>
                @endif
                @if (is_super_user() || is_super_hospital_user())
                    <li class="{{ HTML::active($tab == 7) }}">
                        <a href="{{ URL::route('hospitals.edit', $hospital->id) }}">Settings</a>
                    </li>
                @endif
            </ul>
        </div>
    @else {{-- This will load the physician screen view --}}
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
    @endif
@include('layouts/_flash')
@yield('content')
@endsection
