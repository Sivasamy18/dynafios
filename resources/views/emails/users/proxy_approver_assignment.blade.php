@extends('layouts/email')
@section('body')
    <p>Hello {{$proxy_user_name}},</p>

    <p>
        This is to inform you that -
        You have been assigned as a Proxy Approver by {{$name}} from {{$start_date}} to {{$end_date}}.

    </p>

    <p>
        To ensure appropriate and accurate payments, all approved provider logs must be processed for payment in a
        timely manner.
    </p>
    <p>
        Click <a href="https://dynafiosapp.com">here</a> to login to the Dynafios APP Dashboard and process payments.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

    <!--<p>-->
    <!--<small>Thank you for choosing the Dynafios APP</br>-->
    <!--An Innovative Product by Dynafios-->
    <!--</small>-->
    <!--</p>-->
    @endsection
