@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        This is an automated message to let you know that your email has been associated with the Dynafios APP system
        as a practice manager for {{ $practice }}.
    </p>
    <p>
        You may login at:<br/>
        <strong>URL:</strong> {{ URL::route('auth.login') }}<br/>
        <strong>Password: {{ $password }}<br/>
    </p>
    <p>
        <small>Thanks for choosing the Dynafios APP</small>
    </p>
    @endsection