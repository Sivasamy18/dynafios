@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        This is an automated message to let you know that a member of the Dynafios APP team has replied to your Help Center ticket submission.
    </p>
    <p>View Ticket: <a href="{{ $url }}">{{ $url }}</a></p>
    <p>
        <small>Thanks for choosing the Dynafios APP</small>
    </p>
    @endsection