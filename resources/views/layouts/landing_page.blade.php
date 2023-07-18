@php use function App\Start\is_physician; @endphp
@php use function App\Start\is_contract_manager; @endphp
@php use function App\Start\is_financial_manager; @endphp
@extends('layouts/default')
@section('body')
    <div class="overlay" style="@if(!is_physician() || Request::is('tickets') || Request::is('tickets/*') || Request::is('users/*') ) display: none; @endif position: fixed; z-index: 999999;  margin: 0 auto; width: 100%; height: 100%;background-color: rgba(0, 0, 0, 0.5);"></div>
<div id="default" class="wrapper">
    <nav class="navbar navbar-default navbar-static-top">
        <div class="container-fluid" style="padding: 0 50px;">
            <div class="navbar-header">
                @if(is_physician())
                    <a class="navbar-brand" href="{{route('physician.dashboard', $physician->id)}}"><img class="img-responsive"
                                                      src="{{ asset('assets/img/default/dynafios.png') }}"
                                                      alt="DYNAFIOS Logo"/></a>
                @else
                    <a class="navbar-brand" href="/"><img class="img-responsive"
                                                          src="{{ asset('assets/img/default/dynafios.png') }}"
                                                          alt="DYNAFIOS Logo"/></a>
                @endif

            </div>
            <div class="navbar-collapse collapse">

                <ul class="nav navbar-nav navbar-right">
                    <li><a style="{{ Request::is('tickets') ? 'background-color:#a09284' : '' }};" href="{{ URL::route('tickets.index') }}">Help Center</a></li>
                    <li class="dropdown user-dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            {{ $current_user->initials }} <b class="caret"></b>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="{{ URL::route('users.show', $current_user->id) }}"><i
                                        class="fa fa-user fa-fw"></i> My Profile</a></li>
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
        
        <div class="landing_page_main">
            <div class="container-fluid" style="background: #efefef;">
                @yield('main')
            </div>
        </div>
        <div class="featured">
            <div class="container-fluid">
                @yield('featured')
            </div>
        </div>
    </div>
    @include('layouts/_footer')
</div>
@endsection
