@extends('layouts/email')
@section('body')
    <p><b>Dynafios APP EXTRACT REPORT: Ready to download</b></p>
    <p>Hello {{$name}},</p>
    <p>
        The Dynafios APP Extract Report is ready.
    </p>
    <p>
        Please click <a href="https://dynafiosapp.com/getDownload">here</a> to download.
    </p>
    <p>
        <small>Thanks for choosing the Dynafios APP</small>
    </p>
    @endsection