@extends('layouts/email')
@section('body')
    <p>Hello {{$name}},</p>


    <p>
        This is a reminder to review and approve provider logs that have been approved by all parties and submit
        payments for invoice.
        <!--<b>You currently have the following active agreements in the Dynafios APP that are ready for report generation.</b></br>-->
    </p>
    <p>
        To ensure appropriate and accurate payments, all approved provider logs must be processed for payment in a
        timely manner.
    </p>
    <p>
        Click <a href="https://dynafiosapp.com">here</a> or cut and paste the following URL into your browser’s navigation bar: https://dynafiosapp.com
        to login to the Dynafios APP Dashboard and process payments.
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