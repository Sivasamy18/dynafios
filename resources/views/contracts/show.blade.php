@extends('layouts/_dashboard')
@section('main')
    @include('audits.audit-history', ['audits' => $contract->audits()->orderBy('created_at', 'desc')->with('user')->paginate(50)])
@endsection


