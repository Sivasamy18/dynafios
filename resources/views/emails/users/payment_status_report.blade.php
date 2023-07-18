@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        
    </p>
	<p>Please find attached payment status report for the filtered data selected on dashboard.</p>
    <p>
        
    </p>
    <p>
        <small>Thanks!<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>
    @endsection