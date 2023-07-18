@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3><i class="fa fa-star fa-fw icon"></i> Specialties</h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('specialties.index') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-edit-specialty' ]) }}
{{ Form::hidden('id', $specialty->id) }}
<div class="panel panel-default">
    <div class="panel-heading">Edit Specialty</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('name', Request::old('name', $specialty->name), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">FMV Rate</label>

            <div class="col-xs-5">
                <div class="input-group">
                    {{ Form::text('fmv_rate', Request::old('fmv_rate', $specialty->fmv_rate), [ 'class' => 'form-control'
                    ]) }}
                    <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                </div>
            <span class="help-block">
              The FMV rate must include the dollar amount followed by cents, for example
              <strong>50.75</strong>.
            </span>
            </div>
            <div class="col-xs-5">{!! $errors->first('fmv_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection