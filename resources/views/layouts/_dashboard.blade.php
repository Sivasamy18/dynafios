@php use function App\Start\is_physician; @endphp
@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\is_hospital_admin; @endphp
@php use function App\Start\is_hospital_cfo; @endphp
@php use function App\Start\is_practice_manager; @endphp
@php use function App\Start\is_contract_manager; @endphp
@php use function App\Start\is_financial_manager; @endphp
@extends('layouts/default')
@section('body')
    <div class="overlay" style="@if(!is_physician() || Request::is('tickets') || Request::is('tickets/*') || Request::is('users/*') ) display: none; @endif position: fixed; z-index: 999999;  margin: 0 auto; width: 100%; height: 100%;background-color: rgba(0, 0, 0, 0.5);"></div>
    <div id="default" class="wrapper">
        <nav class="navbar navbar-default navbar-static-top">
            <div class="container">
                <div class="navbar-header">
                    @if(is_physician())
                        <a class="navbar-brand" href="{{route('physician.dashboard', $physician->id)}}">
                            <img class="img-responsive" src="{{ asset('assets/img/default/dynafios.png') }}" alt="DYNAFIOS Logo"/>
                        </a>
                    @else
                        <a class="navbar-brand" href="/">
                            <img class="img-responsive" src="{{ asset('assets/img/default/dynafios.png') }}" alt="DYNAFIOS Logo"/>
                        </a>
                    @endif

                </div>
                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav navbar-left">

                        @if(is_physician())
                            <li><a style="{{ Request::is('physician') || Request::is('physician/*') && !Request::is('physician/*/getRejected/*') ? 'background-color:#a09284' : '' }};'' }};" href="{{route('physician.dashboard', $physician->id)}}">Home</a></li>
                            <!-- One to many :issue-5 submit signature by 1254 : 16022021-->
                            <!-- added practice id of physician to route  -->
                            <li><a style="{{ Request::is('physicians') || Request::is('physicians/*')  ? 'background-color:#a09284' : '' }};'' }};"  href="{{ URL::route('physicians.signature', [$physician->id,$physician->practice_id]) }}">Submit Signature</a></li>
                            <li><a style="{{ Request::is('physician-report') || Request::is('physician-report/*') ? 'background-color:#a09284' : '' }};'' }};" href="{{ URL::route('physician.reports', $physician->id) }}?p_id={{$physician->practice_id}}">Reports</a></li>
                        @else
                            <li><a style="{{ Request::is('/') || Request::is('tickets/*') ? 'background-color:#a09284' : '' }};" href="/">Dashboard</a></li>

                        @endif
                        @if (is_super_user())
                            <li class="dropdown">
                                <a href="#" style="{{ Request::is('actions') || Request::is('actions/*') || Request::is('contract_names') || Request::is('contract_names/*') || Request::is('contract_types') || Request::is('contract_types/*') || Request::is('practice-types') || Request::is('practice-types/*') || Request::is('specialties') || Request::is('specialties/*') || Request::is('users') || Request::is('users/*') || Request::is('physicians') || Request::is('physicians/*') || Request::is('reports') || Request::is('emailer') || Request::is('system_logs') ? 'background-color:#a09284' : '' }};" class="dropdown-toggle" data-toggle="dropdown">System <b class="caret"></b></a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a style="{{ Request::is('actions') || Request::is('actions/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('actions.index') }}">
                                            <i class="fa fa-exclamation-circle fa-fw"></i> Actions
                                        </a>
                                    </li>
                                    <li><a style="{{ Request::is('contract_names') || Request::is('contract_names/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('contract_names.index') }}"><i class="fa fa-list fa-fw"></i> Contract Names</a></li>
                                    <li><a style="{{ Request::is('contract_types') || Request::is('contract_types/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('contract_types.index') }}"><i class="fa fa-list fa-fw"></i> Contract Types</a></li>
                                    <li><a style="{{ Request::is('payment_types') || Request::is('payment_types/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('payment_types.index') }}"><i class="fas fa-credit-card fa-fw"></i> Payment Types</a></li>
                                    <li><a style="{{ Request::is('practice-types') || Request::is('practice-types/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('practice_types.index') }}"><i class="fa fa-hospital-o fa-fw"></i> Practice Types</a></li>
                                    <li><a style="{{ Request::is('interface_types') || Request::is('interface_types/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('interface_types.index') }}"><i class="fa fa-arrows-alt fa-fw"></i> Interface Types</a></li>
                                    <li><a style="{{ Request::is('specialties') || Request::is('specialties/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('specialties.index') }}"><i class="fa fa-star fa-fw"></i> Specialties</a></li>
                                    <li class="divider"></li>
                                    <li><a style="{{ Request::is('users') || Request::is('users/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('users.index') }}"><i class="fa fa-user fa-fw"></i> Users</a></li>
                                    <li><a style="{{ Request::is('physicians') || Request::is('physicians/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('physicians.index') }}"><i class="fa fa-user-md fa-fw"></i> Physicians</a></li>
                                    <li><a href="{{ URL::route('audits.index') }}"><i class="fa fa-history fa-fw"></i> History</a></li>
                                    <li class="divider"></li>
                                    <li><a style="{{ Request::is('reports') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('reports.index') }}"><i class="fa fa-file-text fa-fw"></i> Admin Reports</a></li>
                                    <li><a style="{{ Request::is('emailer') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('dashboard.emailer') }}"><i class="fa fa-envelope fa-fw"></i> Mass Emailer</a></li>
                                    <li><a style="{{ Request::is('system_logs') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('system_logs.index') }}"><i class="fa fa-list fa-fw"></i> System Logs</a></li>
                                    <li><a style="{{ Request::is('sso_clients') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('sso_clients.index') }}"><i class="fa fa-laptop"></i> SSO Clients</a></li>
                                    <li><a style="{{ Request::is('attestation') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('attestations.index') }}"><i class="fa fa-star fa-fw"></i> Attestation</a></li>
                                    <li><a href="{{ URL::route('admin.dashboard.index') }}"><i class="fa fa-wrench fa-fw"></i> Maintenance Dashboard</a></li>
                                    <li class="divider"></li>
                                    <li><a style="{{ Request::is('contract_names') || Request::is('contract_names/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('tickets.index') }}"><i class="fa fa-question-circle fa-fw"></i> Help Center</a></li>
                                </ul>
                            </li>
                            <li>
                                <li><a style="{{ Request::is('healthSystem') || Request::is('healthSystem/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('healthSystem.index') }}">Health Systems</a></li>
                            </li>
                        @endif
                        @if (is_super_user() || is_super_hospital_user() || is_hospital_admin() || is_hospital_cfo())
                            <li><a style="{{ Request::is('hospitals') || Request::is('hospitals/*') || Request::is('payment/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('hospitals.index') }}">Hospitals</a></li>
                        @endif
                        @if (is_super_user() || is_super_hospital_user() || is_hospital_admin() || is_hospital_cfo() || is_practice_manager())
                            <li><a style="{{ Request::is('practices') || Request::is('practices/*') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('practices.index') }}">Practices</a></li>
                        @endif
                        @if (is_physician())
                            <li class="rejectedLogs"><a  class="blink_me btn btn-primary" style="display: none;padding: 10px 10px; margin-top: 4px;" href="{{ URL::route('physician.rejected',[$physician->id,0,0]) }}">Rejected</a></li>
                        @endif
                        @if (Request::is('practiceManager/*'))
                            <li class="rejectedLogs"><a  class="blink_me btn btn-primary" style="color:black; display: none;padding: 10px 10px; margin-top: 4px;" href="{{ URL::route('practicemanager.rejected',[$user->id,0,0]) }}">Rejected Logs</a></li>
                        @endif
                    </ul>
                    <ul class="nav navbar-nav navbar-right">
                        @if(is_physician())
                            <li><a style="{{ Request::is('tickets') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('tickets.index') }}?p_id={{$physician->practice_id}}">Help Center</a></li>
                        @else
                            <li><a style="{{ Request::is('tickets') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('tickets.index') }}">Help Center</a></li>
                        @endif

                        <li class="dropdown user-dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                {{ $current_user->initials }} <b class="caret"></b>
                            </a>
                            <ul class="dropdown-menu">
                            @if(is_physician())
                                <li><a href="{{ URL::route('users.show', $current_user->id) }}?p_id={{$physician->practice_id}}"><i
                                                class="fa fa-user fa-fw"></i> My Profile</a></li>
                            @else
                                <li><a href="{{ URL::route('users.show', $current_user->id) }}"><i
                                                class="fa fa-user fa-fw"></i> My Profile</a></li>
                            @endif
                                @if (is_contract_manager() || is_financial_manager())
                                    <li><a href="{{ URL::route('approval.signature') }}"><i class="fa fa-edit fa-fw"></i> Submit Signature</a></li>
                                @endif
                                <li><a href="{{ URL::route('auth.logout') }}"><i class="fa fa-sign-out fa-fw"></i> Sign Out</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="content">
            <div class="container">
                @if(!is_physician())
                    <div class="drawer">
                        <div class="drawer-content" style="display: none">
                            <form class="form form-horizontal search-form" action="{{ URL::to('search') }}" method="post">
                                @csrf
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-search fa-fw"></i></span>
                                    <input class="form-control" name="query" type="text" placeholder="Search Query"/>
                                </div>
                            </form>
                        </div>
                        <a class="drawer-handle" href="#"><img src="{{ asset('assets/img/default/drawer-handle.png') }}"/></a>
                    </div>
                @endif
            </div>
            <div class="main">
                <div class="container">
                    @yield('main')
                </div>
            </div>
            <div class="featured">
                <div class="container">
                    @yield('featured')
                </div>
            </div>
        </div>
        @include('layouts/_footer')
    </div>
@endsection
