@extends('layouts/_dashboard')
@section('main')
    @include('layouts/_flash')
<div class="page-header">
    <h3><i class="fa fa-laptop fa-fw icon"></i> Welcome to the DYNAFIOS Dashboard</h3>
    @if(Session::get('user_is_switched'))
    <h3 class="welcomeHeading" style="color: red; display: inline-block; margin-bottom: 0;">YOU ARE CURRENTLY EMULATING A USER - <a href="{{ URL::route('userswitch.restoreuser') }}">Switch Back to Your Login</a></li></h3>
    @endif
</div>
<div class="col-xs-8">
    <div class="quicklinks">
        @yield('links')
    </div>
</div>
<div class="col-xs-4" style="border-left:1px solid #e2e2e2;">
    @include('dashboard/_sidebar')
</div>
@endsection