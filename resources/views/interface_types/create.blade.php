@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-list fa-fw icon"></i> Interface Types
    </h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('interface_types.index') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-create-interface-type' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create Interface Type</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('name', Request::old('name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection