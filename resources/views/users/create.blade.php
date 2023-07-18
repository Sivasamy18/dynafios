@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3><i class="fa fa-user fa-fw icon"></i> Users</h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('users.index') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-create-user' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create User</div>
    @include('users/_createForm')
</div>
{{ Form::close() }}
@endsection