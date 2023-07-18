@extends('layouts/_auth')
@section('main')
{{ Form::open([ 'class' => 'form form-auth login-form' ]) }}
<div class="form-header">
    <h1><span>DYNAFIOS</span> Password Reset</h1>

    <p>
        We can help you reset your password and security info. First, enter your email associated with the
        <strong>DYNAFIOS</strong> System and press submit and we will email you a link to reset your password.
    </p>
</div>
@include('layouts/_flash')
<div class="form-group">
    <div class="input-group">
        <span class="input-group-addon"><i class="fa fa-user fa-fw"></i> </span>
        {{ Form::email('email', Request::old('email'), [ 'class' => 'form-control', 'placeholder' => 'Email' ]) }}
    </div>
    {!! $errors->first('email', '<p id="error-message" class="validation-error">:message</p>') !!}
</div>
<div class="links">
    <a href="{{ URL::route('auth.login') }}">Return to Sign In</a>
</div>
{{ Form::button('Submit', [ 'class' => 'btn btn-default btn-submit', 'type' => 'submit']) }}
{{ Form::close() }}
@endsection