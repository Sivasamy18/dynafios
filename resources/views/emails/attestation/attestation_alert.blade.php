@extends('layouts/email')
@section('body')
    <p>Dear,</p>
    <p>
        {{ $name }} has responded ‘no’ to attestation questions related to advanced practice provider APP supervision for the month of {{ $months }}.
    </p>
    <p>
        <small>Thanks!<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>
    @endsection