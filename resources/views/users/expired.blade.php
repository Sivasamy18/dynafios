@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_owner; @endphp
@extends('layouts/_expired')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-user fa-fw icon"></i> {{ "$user->first_name $user->last_name" }} - Your password has expired. Please change it below.
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('auth.logout') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Sign Out
        </a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-edit-user' ]) }}
{{ Form::hidden('id', $user->id) }}
<div class="panel panel-default">
    <div class="panel-heading">Change Password</div>
    <div class="panel-body">
        @if (!is_super_user() || is_owner($user->id))
        <div class="form-group">
            <label class="col-xs-2 control-label">Current Password</label>

            <div class="col-xs-5">
                {{ Form::password('current_password', [ 'class' => 'form-control','id' => 'current_password' ]) }}
            </div>
        </div>
        @endif
        <div class="form-group">
            <label class="col-xs-2 control-label">New Password</label>

            <div class="col-xs-5">
                {{ Form::password('new_password', [ 'class' => 'form-control','id' => 'new_password' ]) }}
            </div>
            <div class="col-xs-1">
                <span class="fa fa-fw fa-eye toggle-password-new"></span>
            </div>
            <div class="col-xs-5">
                {!! $errors->first('new_password', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">New Password</label>

            <div class="col-xs-5">
                {{ Form::password('new_password_confirmation', [ 'class' => 'form-control','id' => 'new_password_confirmation' ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('new_password_confirmation', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}

@endsection

@section('scripts')
<script type="text/javascript">
    $(function () {
        $(".toggle-password-new").on({
            mouseenter: function () {
                $('#current_password').attr('type','text');
                $('#new_password').attr('type','text');
                $('#new_password_confirmation').attr('type','text');
            },
            mouseleave: function () {
                $('#current_password').attr('type','password');
                $('#new_password').attr('type','password');
                $('#new_password_confirmation').attr('type','password');
            }
        });
    });
</script>
@endsection
