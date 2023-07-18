@extends('layouts/_hospital', ['tab' => 2])
@section('actions')
<a class="btn btn-default" href="{{ route('agreements.show', $agreement->id) }}">
    <i class="fa fa-arrow-circle-left fa-fw"></i> Back
</a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-agreement' ]) }}
{{ Form::hidden('id', $agreement->id) }}
<div class="panel panel-default">
    <div class="panel-heading">Edit Agreement</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('name', Request::old('name', $agreement->name), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Start Date</label>

            <div class="col-xs-5">
                <div id="start-date" class="input-group">
                    {{ Form::text('start_date', Request::old('start_date', format_date($agreement->start_date)), [ 'class'
                    => 'form-control' ]) }}
                    <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                </div>
            </div>
            <div class="col-xs-5">{!! $errors->first('start_date', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">End Date</label>

            <div class="col-xs-5">
                <div id="end-date" class="input-group">
                    {{ Form::text('end_date', Request::old('end_date', format_date($agreement->end_date)), [ 'class' =>
                    'form-control' ]) }}
                    <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                </div>
            </div>
            <div class="col-xs-5">{!! $errors->first('end_date', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection