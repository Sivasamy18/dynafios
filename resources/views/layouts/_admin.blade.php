@extends('layouts/default')

@section('body')
  <div id="default" class="wrapper">
    <nav class="navbar navbar-default navbar-static-top">
      <div class="container">
        <div class="navbar-header">
          <a class="navbar-brand" href="/">
            <img class="img-responsive" src="{{ asset('assets/img/default/dynafios.png') }}" alt="DYNAFIOS Logo"/>
          </a>
        </div>
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-left">
            <li><a style="{{ Request::is('/') || Request::is('tickets/*') ? 'background-color:#a09284' : '' }};"
                   href="/">Dashboard</a></li>

            <li class="dropdown">
              <a href="#"
                 style="{{ Request::is('actions') || Request::is('actions/*') || Request::is('contract_names') || Request::is('contract_names/*') || Request::is('contract_types') || Request::is('contract_types/*') || Request::is('practice-types') || Request::is('practice-types/*') || Request::is('specialties') || Request::is('specialties/*') || Request::is('users') || Request::is('users/*') || Request::is('physicians') || Request::is('physicians/*') || Request::is('reports') || Request::is('emailer') || Request::is('system_logs') ? 'background-color:#a09284' : '' }};"
                 class="dropdown-toggle" data-toggle="dropdown">System <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <li>
                  <a style="{{ Request::is('actions') || Request::is('actions/*') ? 'background-color:#a09284' : '' }};"
                     href="{{ URL::route('actions.index') }}">
                    <i class="fa fa-exclamation-circle fa-fw"></i> Actions
                  </a>
                </li>
                <li><a
                      style="{{ Request::is('contract_names') || Request::is('contract_names/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('contract_names.index') }}"><i class="fa fa-list fa-fw"></i> Contract
                    Names</a>
                </li>
                <li><a
                      style="{{ Request::is('contract_types') || Request::is('contract_types/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('contract_types.index') }}"><i class="fa fa-list fa-fw"></i> Contract
                    Types</a>
                </li>
                <li><a
                      style="{{ Request::is('payment_types') || Request::is('payment_types/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('payment_types.index') }}"><i class="fas fa-credit-card fa-fw"></i> Payment
                    Types</a></li>
                <li><a
                      style="{{ Request::is('practice-types') || Request::is('practice-types/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('practice_types.index') }}"><i class="fa fa-hospital-o fa-fw"></i> Practice
                    Types</a></li>
                <li><a
                      style="{{ Request::is('interface_types') || Request::is('interface_types/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('interface_types.index') }}"><i class="fa fa-arrows-alt fa-fw"></i>
                    Interface Types</a></li>
                <li><a
                      style="{{ Request::is('specialties') || Request::is('specialties/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('specialties.index') }}"><i class="fa fa-star fa-fw"></i> Specialties</a>
                </li>
                <li class="divider"></li>
                <li><a style="{{ Request::is('users') || Request::is('users/*') ? 'background-color:#a09284' : '' }};"
                       href="{{ URL::route('users.index') }}"><i class="fa fa-user fa-fw"></i> Users</a></li>
                <li><a
                      style="{{ Request::is('physicians') || Request::is('physicians/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('physicians.index') }}"><i class="fa fa-user-md fa-fw"></i> Physicians</a>
                </li>
                <li><a href="{{ URL::route('audits.index') }}"><i class="fa fa-history fa-fw"></i> History</a></li>
                <li class="divider"></li>
                <li><a style="{{ Request::is('reports') ? 'background-color:#a09284' : '' }};"
                       href="{{ URL::route('reports.index') }}"><i class="fa fa-file-text fa-fw"></i> Admin
                    Reports</a></li>
                <li><a style="{{ Request::is('emailer') ? 'background-color:#a09284' : '' }};"
                       href="{{ URL::route('dashboard.emailer') }}"><i class="fa fa-envelope fa-fw"></i> Mass
                    Emailer</a>
                </li>
                <li><a style="{{ Request::is('system_logs') ? 'background-color:#a09284' : '' }};"
                       href="{{ URL::route('system_logs.index') }}"><i class="fa fa-list fa-fw"></i> System Logs</a>
                </li>
                <li><a style="{{ Request::is('sso_clients') ? 'background-color:#a09284' : '' }};"
                       href="{{ URL::route('sso_clients.index') }}"><i class="fa fa-laptop"></i> SSO Clients</a></li>
                <li><a style="{{ Request::is('attestation') ? 'background-color:#a09284' : '' }};"
                       href="{{ URL::route('attestations.index') }}"><i class="fa fa-star fa-fw"></i> Attestation</a>
                </li>
                <li>
                  <a href="{{ URL::route('admin.dashboard.index') }}">
                    <i class="fa fa-wrench fa-fw"></i> Maintenance Dashboard
                  </a>
                </li>
                <li class="divider"></li>
                <li><a
                      style="{{ Request::is('contract_names') || Request::is('contract_names/*') ? 'background-color:#a09284' : '' }};"
                      href="{{ URL::route('tickets.index') }}"><i class="fa fa-question-circle fa-fw"></i> Help
                    Center</a>
                </li>
              </ul>
            </li>
            <li>
            <li><a
                  style="{{ Request::is('healthSystem') || Request::is('healthSystem/*') ? 'background-color:#a09284' : '' }};"
                  href="{{ URL::route('healthSystem.index') }}">Health Systems</a></li>
            </li>
            <li><a
                  style="{{ Request::is('hospitals') || Request::is('hospitals/*') || Request::is('payment/*') ? 'background-color:#a09284' : '' }};"
                  href="{{ URL::route('hospitals.index') }}">Hospitals</a></li>
            <li><a
                  style="{{ Request::is('practices') || Request::is('practices/*') ? 'background-color:#a09284' : '' }};"
                  href="{{ URL::route('practices.index') }}">Practices</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li><a style="{{ Request::is('tickets') ? 'background-color:#a09284' : '' }};"
                   href="{{ URL::route('tickets.index') }}">Help Center</a></li>

            <li class="dropdown user-dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                {{ Auth::user()->initials }} <b class="caret"></b>
              </a>
              <ul class="dropdown-menu">
                <li><a href="{{ URL::route('users.show', $current_user->id) }}"><i
                        class="fa fa-user fa-fw"></i> My Profile</a></li>
                <li><a href="{{ URL::route('auth.logout') }}"><i class="fa fa-sign-out fa-fw"></i> Sign Out</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>
    <div class="content">
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