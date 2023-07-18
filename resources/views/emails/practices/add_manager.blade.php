@extends('layouts/email')
@section('body')
    <p>Hello {{ $name }},</p>
    <p>
        This is an automated message to let you know that your email has been associated with the Dynafios APP system
        as a practice manager for {{ $practice }}.
    </p>
    <p>
        <small>Thanks for choosing the Dynafios APP<br/>A Dynafios Product</small>
    </p>
    @endsection