@extends('layouts/email')
@section('body')
    <p><b>Dynafios APP : Invoice and Provider Log Reports</b></p>
    <p>Hello {{ $name }},</p>

    <p>
        The attached invoice and provider log report includes all the approved time logs for {{$month}} {{$year}} for
        each of your provider contracts that require payment for services rendered.
    </p>
    <p>
        <small>Thanks!<br/>The Dynafios APP Support Team</small>
    </p>

@endsection