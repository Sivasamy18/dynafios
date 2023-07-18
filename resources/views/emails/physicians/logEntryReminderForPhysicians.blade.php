@extends('layouts/email')
@section('body')
    <p>Dear Dr. {{ $name }} {{ $last_name }},</p>

    <b>IF YOU HAVE NOT DONE SO ALREADY:</b>

    <p>
        This is a reminder to login to the Dynafios APP to add and approve your contract logs for <b>{{date("F",
        strtotime ( "-1 month" , strtotime ( date("F") ) ))}} {{date("Y", strtotime ( "-1 month" , strtotime ( date("F")
        ) ))}} and any prior periods that need attention.</b>
    </p>
    <p>
        To ensure timely and accurate payment for each contract, please submit & approve all activity logs.
    </p>
    <p>
        Login to your Dynafios APP app on your mobile device or click <a href="https://dynafiosapp.com">here</a> or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com
        to Add & Approve logs.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

    @endsection