@extends('layouts/email')
@section('body')
    <p>Dear {{$name}},</p>

    <p>
        This is a reminder to review and approve all submitted provider logs for {{$contract_list}} for <b>{{date("F",
        strtotime("-1 months"))}} {{date("Y", strtotime("-1 months"))}} and prior periods (as applicable)</b> on the
        Dynafios APP Dashboard.
    </p>
    <p>
        To ensure timely and accurate payments for all applicable contracts, please login and approve all pending
        provider logs.
    </p>
    <p>
        Click <a href="https://dynafiosapp.com/getLogsForApproval">here</a> or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com/getLogsForApproval
        to go to the Dynafios APP dashboard and approve provider logs.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

@endsection