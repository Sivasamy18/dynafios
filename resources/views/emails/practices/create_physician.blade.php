@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        This is an automated message to let you know that your email has been associated with the Dynafios APP system
        as a physician for {{ $practice }}.
    </p>
    <p>
        Your credentials are:<br/>
        <strong>Email:</strong> {{ $email }}<br/>
        <strong>Password:</strong> {{ $password }}
    </p>
    <p>
        For complete instructions regarding installation of the Dynafios APP on your mobile device, please click
        here:<br>
        {{ URL::to('guide') }}
    </p>
    <p>
        <small>Thanks for choosing the Dynafios AP</small>
    </p>
    @endsection