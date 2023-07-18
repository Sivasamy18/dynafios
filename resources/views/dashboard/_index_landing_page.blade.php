@php use function App\Start\has_invoice_dashboard_access; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\is_hospital_admin; @endphp
@php use function App\Start\is_practice_manager; @endphp
@php use function App\Start\is_hospital_user_healthSystem_user; @endphp
@php use function App\Start\is_hospital_user_healthSystem_region_user; @endphp
@extends('layouts/landing_page')
@section('main')
    @include('layouts/_flash')
<div class="page-header">

  @if(Request::is('getLogsForApproval'))
    <h3 class="welcomeHeading" style="display: inline-block; margin-bottom: 0;"><i class="fa fa-laptop fa-fw icon"></i>Welcome to the
        <span class="" style="display:none;">
            {{ Form::select('manager_filter', $manager_filters, Request::old('manager_filter',$manager_filter), ['id' => 'manager_filter', 'class' => 'form-control  manager_filter', 'style' => 'border: 0; box-shadow: none; font-size: 20px; font-family: "open sans"; font-weight: 600; padding-top: 0;' ]) }}
        </span>
        Approval Dashboard</h3>
       <div class="landingPageNavbar">
         <ul>
           <li><a href="/">Home</a></li>
             @if (count($current_user->hospitals) == 1)
                 <li><a href="{{ URL::route('hospitals.reports', $current_user->hospitals[0]->id) }}">Reporting</a></li>

                 @if(has_invoice_dashboard_access())
                    @if($invoice_dashboard_display == 1)
                        <li><a href="{{ URL::route('agreements.payment', $current_user->hospitals[0]->id) }}">Invoices</a></li>
                    @endif
                  @endif

             @else
                 <li><a href="{{ URL::route('hospitals.index') }}?type=1">Reporting</a></li>
                 
                 @if(has_invoice_dashboard_access())
                    @if($invoice_dashboard_display == 1)
                        <li><a href="{{ URL::route('hospitals.index') }}?type=2">Invoices</a></li>
                    @endif
                  @endif


             @endif
             @if(is_super_hospital_user() || is_hospital_admin())
                 <li><a href="{{ URL::route('approval.paymentStatus') }}">Payment Status</a></li>
             @endif
         </ul>
       </div>
        @elseif(Request::is('showPerformanceDashboard'))
            <h3 class="welcomeHeading" style="display: inline-block; margin-bottom: 0;"><i class="fa fa-laptop fa-fw icon"></i>Welcome to the
                <span class="" style="display:none;">
        </span>
                Provider Performance Dashboard</h3>
            <div class="landingPageNavbar">
                <ul style="float:left;">
                    @if(Session::get('user_is_switched'))
                        <li><a href="{{ URL::route('userswitch.restoreuser') }}">Switch Back to Your Login</a></li>
                    @endif
                    <li><a href="/">Hospital Dashboard</a></li>
                </ul>
                <a href="{{ URL::to('/assets/pdf/guide.pdf') }}" target="_blank">DYNAFIOS Guide</a>
            </div>

  @elseif(Request::is('paymentStatus')  && !is_practice_manager() && (is_super_hospital_user() || is_hospital_admin()))
        <h3 class="welcomeHeading" style="display: inline-block; margin-bottom: 0;"><i class="fa fa-laptop fa-fw icon"></i>Welcome to the Payment Status Dashboard</h3>
        <div class="landingPageNavbar">
            <ul>
                <li><a href="/">Home</a></li>
                @if (count($current_user->hospitals) == 1)
                    <li><a href="{{ URL::route('hospitals.reports', $current_user->hospitals[0]->id) }}">Reporting</a></li>

                    @if(has_invoice_dashboard_access())
                        @if($invoice_dashboard_display == 1)
                          <li><a href="{{ URL::route('agreements.payment', $current_user->hospitals[0]->id) }}">Invoices</a></li>
                        @endif
                    @endif

                @else
                    <li><a href="{{ URL::route('hospitals.index') }}?type=1">Reporting</a></li>

                    @if(has_invoice_dashboard_access())
                        @if($invoice_dashboard_display == 1)
                          <li><a href="{{ URL::route('hospitals.index') }}?type=2">Invoices</a></li>
                        @endif
                    @endif


                @endif
                @if(is_super_hospital_user() || is_hospital_admin())
                 <li><a href="{{ URL::route('approval.index') }}">Approval Dashboard</a></li>
                @endif

            </ul>
        </div>
  @elseif(Request::is('showComplianceDashboard'))
            <h3 class="welcomeHeading" style="display: inline-block; margin-bottom: 0;"><i class="fa fa-laptop fa-fw icon"></i>Welcome to the
                <span class="" style="display:none;">
                </span>
              Compliance Dashboard
            </h3>
            <div class="landingPageNavbar">
            <ul style="float:left;">
                @if(Session::get('user_is_switched'))
                  <li><a href="{{ URL::route('userswitch.restoreuser') }}">Switch Back to Your Login</a></li>
                @endif
                  <li><a href="/">Hospital Dashboard</a></li>
            </ul>
            <a href="{{ URL::to('/assets/pdf/guide.pdf') }}" target="_blank">DYNAFIOS Guide</a>
        </div>
  @else
    <h3 class="welcomeHeading" style="display: inline-block; margin-bottom: 0;"><i class="fa fa-laptop fa-fw icon"></i>Welcome to Your DYNAFIOS Dashboard</h3>
    @if(Session::get('user_is_switched'))
    <h3 class="welcomeHeading" style="color: red; display: inline-block; margin-bottom: 0;"> - YOU ARE CURRENTLY EMULATING A USER</h3>
    @endif
        @if(!Request::is('getLogsForApproval') && !Request::is('paymentStatus'))

            <div class="landingPageNavbar">
            <ul style="float:left;">
              @if(Session::get('user_is_switched'))
              <li><a href="{{ URL::route('userswitch.restoreuser') }}">Switch Back to Your Login</a></li>
              @endif
              @if(Request::is('showHealthSystemDashboard')||Request::is('showHealthSystemRegionDashboard'))
                <li><a href="/">Hospital Dashboard</a></li>
                <li><a href="{{ URL::route('performance_dashboard.display') }}" >Provider Performance Dashboard</a></li>
              @else
                @if(is_hospital_user_healthSystem_user())
                  <li><a href="{{ URL::route('healthsystem_dashboard.display') }}">Health System Dashboard</a></li>
                @elseif(is_hospital_user_healthSystem_region_user())
                <li><a href="{{ URL::route('healthsystemregion_dashboard.display') }}">Health System Region Dashboard</a></li>
                @endif
              @endif
            </ul>
                <a href="{{ URL::to('/assets/pdf/guide.pdf') }}" target="_blank">DYNAFIOS Guide</a>

            </div>
        @endif
  @endif

</div>
<div class="col-xs-12" style="padding: 0 50px;">
    <div class="quicklinks"  id="linksDiv">
        @yield('links')
    </div>
</div>

@endsection
