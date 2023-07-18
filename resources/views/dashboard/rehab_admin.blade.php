@extends('layouts/_dashboard')
@section('main')
    @include('layouts/_flash')
    <div class="page-header">
        <h3><i class="fa fa-laptop fa-fw icon"></i> Rehab Management</h3>
{{--        @if(Session::get('user_is_switched'))--}}
{{--            <h3 class="welcomeHeading" style="color: red; display: inline-block; margin-bottom: 0;">YOU ARE CURRENTLY EMULATING A USER - <a href="{{ URL::route('userswitch.restoreuser') }}">Switch Back to Your Login</a></li></h3>--}}
{{--        @endif--}}
    </div>
    <div class="col-xs-8">
        <div class="quicklinks">
            <a href="{{ URL::route('dashboard.rehab_weekly_max') }}"><i class="fa fa-user fa-fw"></i> Add Weekly Max Hours</a>
            <a href="{{ URL::route('dashboard.rehab_admin_hours') }}"><i class="fa fa-question-circle fa-fw"></i> Add Additional Admin Hours</a>
        </div>
    </div>
    <div class="col-xs-4" style="border-left:1px solid #e2e2e2;">
        @include('dashboard/_sidebar')
    </div>
@endsection
