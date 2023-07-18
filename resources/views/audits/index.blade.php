@extends('layouts/_dashboard')

@section('main')
    @include('audits.audit-history', ['audits' => $audits])
@endsection