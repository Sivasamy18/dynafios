@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>

    <p>
        The attached invoice was interfaced to Lawson via AP520. Please use attached invoice for ImageNow documentation.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

@endsection