@extends('layouts/email')
@section('body')
    <p>
        This is an automated message to let you know that a Dynafios APP user has responded
        to their Help Center support ticket.
    </p>
    <p>View Ticket: <a href="{{ $url }}">{{ $url }}</a></p>
    @endsection