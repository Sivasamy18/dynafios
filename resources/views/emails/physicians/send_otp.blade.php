@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        Your One Time Password is: {{$otp}}
    </p>
    <p>
        <small>Thanks!<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>
    @endsection