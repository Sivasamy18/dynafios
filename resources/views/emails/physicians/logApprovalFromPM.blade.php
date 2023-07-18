@extends('layouts/email')
@section('body')

    <p>Dear Dr. {{ $name }},</p>
    <p>
        This is a reminder to let you know that time logs have been submitted on your behalf and are ready for your
        approval.
    </p>

    <p>
        To ensure timely and accurate payment, please select <b>Approve Logs</b> for each associated contract(s) and
        <b>submit electronic signature</b>.
    </p>

    <p>
        Login to your Dynafios APP on your mobile device or click <a href="https://dynafiosapp.com/">here</a> or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com
        to Approve logs.
    </p>

    <p>
        <small>Thanks,<br/>{{ $requested_by }}<br/>{{ HTML::mailto($requested_by_email) }}</small>
    </p>

    @endsection