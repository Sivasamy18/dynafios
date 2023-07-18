@extends('layouts/_expired')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-user fa-fw icon"></i> {{ "$physician->first_name $physician->last_name" }} - Your password has expired. Please change it below.
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('auth.logout') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Sign Out
        </a>
    </div>
</div>
<div id="alert" style="display: none;padding: 15px;  margin-bottom: 20px; border: 1px solid transparent;  border-radius: 4px;"></div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-change-password' ]) }}
{{ Form::hidden('id', $physician->id) }}
{{ Form::hidden('user_id', Auth::user()->id) }}
{{ Form::hidden('token', "dashboard") }}
<div class="panel panel-default">
    <div class="panel-heading">Change Password</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-3 control-label">New Password</label>

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
            <label class="col-xs-3 control-label">Confirm Password</label>

            <div class="col-xs-5">
                {{ Form::password('confirmed_password', [ 'class' => 'form-control','id' => 'confirmed_password'  ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('confirmed_password', '<p class="validation-error">:message</p>') !!}
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
                    $('#new_password').attr('type','text');
                    $('#confirmed_password').attr('type','text');
                },
                mouseleave: function () {
                    $('#new_password').attr('type','password');
                    $('#confirmed_password').attr('type','password');
                }
            });
        });
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == 'interactive') {
                $(".overlay").show();
            } else if (state == 'complete') {
                setTimeout(function(){
                    document.getElementById('interactive');
                    $(".overlay").hide();
                },2000);
            }
        }
        $( ".form-change-password" ).submit(function( event ) {
            $('#alert').hide();
            $('.overlay').show();
            var new_password = $('#new_password').val();
            var confirmed_password = $('#confirmed_password').val();
            if(new_password === ""|| new_password.length < 8 ){
                $('#alert').show();
                $('#alert').addClass("alert-danger");
                $('#alert').removeClass("alert-success");
                $('#alert').html("Your password must be atleast eight characters long.");
                $('.overlay').hide();
                event.preventDefault();
            }else if(new_password != confirmed_password){
                $('#alert').show();
                $('#alert').addClass("alert-danger");
                $('#alert').removeClass("alert-success");
                $('#alert').html("The password confirmation does not match.");
                $('.overlay').hide();
                event.preventDefault();
            }else{
                return true;
            }
        });
    </script>
@endsection