@extends('layouts/email')
@section('body')
    <p>Dear Dr. {{ $name }},</p>

    <p>
        <b> This is a reminder to log your Co-Management hours in the Dynafios APP.</b>
        To ensure accurate time reporting and compliance,
        you need to submit all of your hours for {{date('M')}}. by the end of
        the month to allow for TIMELY and ACCURATE payment.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Team</small>
    </p>

    @endsection