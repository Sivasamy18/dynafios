@extends('layouts/email')
@section('body')
    <p>Dear {{$name}},</p>


    <p>
        This email is being sent to notify you that the agreement(s) listed below are expiring in the next 30 days for
        {{ $hospital }}.</br>

        @foreach ($agreement_by_type as $index => $type)
        @if(count($type['agreement']) >0)

        </br><b>{{ $type['type'] }}</b></br>

        @foreach ($type['agreement'] as $agreement)
        {{format_date($agreement->start_date)}} â€“ {{format_date($agreement->end_date)}} &nbsp;&nbsp;&nbsp;&nbsp;
        {{$agreement->name}}</br>
        @endforeach

        @endif
        @endforeach
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>
    <!--<p>-->
    <!--Use this link to log into Dynafios APP <a href="http://dynafiosapp.com/login">http://www.dynafiosapp.com/login</a>.-->
    <!--</p>-->

    <!--<p>-->
    <!--<small>Thank you for choosing the Dynafios APP</br>-->
    <!--An Innovative Product by Dynafios-->
    <!--</small>-->
    <!--</p>-->
@endsection