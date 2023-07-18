@extends('layouts/email')
@section('body')
        <p>Dear {{$name}}</p>
        <p>
            This is an automated message to let you know that we recently received a request to reset your password.
            If you made this request, you may reset your password by following the link below. If you did not make this
            request, please disregard this notice or contact us at: <a href="mailto:support@dynafiosapp.com">support@dynafiosapp.com</a>.
        </p>
        <p>{{ URL::to('password/reset', array($token)) }}</p>
        <p>
            <small>Thanks for choosing the Dynafios APP</small>
        </p>
@endsection