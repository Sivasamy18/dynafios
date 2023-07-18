@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>

    <p>
        The attached invoice includes all the approved time logs for {{$month}} {{$year}} for each of your provider
        contracts that require payment for services rendered.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

@endsection