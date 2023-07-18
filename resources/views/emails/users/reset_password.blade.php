@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        This is an automated message to let you know that your password for the Dynafios APP system has been reset.Â 
        Please see your new password below:
    </p>
    <p>
        Your login credentials:<br/>
        <strong>Username:</strong> {{ $email }}<br/>
        <strong>Password:</strong> {{ $password }}
    <p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>
    @endsection