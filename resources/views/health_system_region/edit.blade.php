@extends('layouts/_healthSystemRegion', [ 'tab' => 4 ])
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-health-system' ]) }}
{{ Form::hidden('id', $region->id) }}
<div class="panel panel-default">
    <div class="panel-heading">
        Region Settings
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>
            <div class="col-xs-5">
                {{ Form::text('region_name', Request::old('region_name', $region->region_name), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('region_name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection