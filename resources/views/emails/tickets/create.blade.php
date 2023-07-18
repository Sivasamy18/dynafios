@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        This is an automated message to let you know that the Dynafios APP team has received your
        Help Center ticket submission and will respond to you as soon as possible.
    </p>
    <p>View Ticket: <a href="{{ $url }}">{{ $url }}</a></p>
    <p>
        <small>Thanks for choosing the Dynafios APP</small>
    </p>
    @endsection