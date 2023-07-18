@extends('layouts/email')
@section('body')
    <p>Dear {{$physician_name_custom}},</p>

    @if($is_final_pmt == "true")
    <p>
        Your {{$hospital_invoice_period}} hours have been submitted for final payment for {{$hospital}} on
        {{$hospital_invoice_period_approval_date}}.
    </p>
    @else
    <p>
        Your {{$hospital_invoice_period}} hours have been submitted for final payment for {{$hospital}} on
        {{$hospital_invoice_period_approval_date}}.
    </p>
    @endif
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

@endsection