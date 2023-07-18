@extends('layouts/email')
@section('body')
    <p>Dear {{ $name }},</p>

    <p>
        This message is to notify you that there are currently provider logs pending your approval related to your
        Dynafios APP hospital- provider contracts.
    </p>
    <p>
        Click <a href="{{ URL::route('approval.index') }}">here</a>  or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com/getLogsForApproval
        to go to the Dynafios APP dashboard to review and approve provider logs.
    </p>
    <p>
        <small>Thanks,<br/>The Dynafios APP Support Team<br/>{{ HTML::mailto('support@dynafiosapp.com') }}</small>
    </p>

@endsection