@extends('layouts/_physician', ['tab' => 2])
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-physician' ]) }}
{{ Form::hidden('id', $contract->id) }}
<div class="panel panel-default">
    <div class="panel-heading">General</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Min Hours</label>

            <div class="col-xs-5">
                {{ Form::text('min_hours', Request::old('min_hours', $contract->min_hours), [ 'class' => 'form-control' ])
                }}
            </div>
            <div class="col-xs-5">{!! $errors->first('min_hours', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Max Hours</label>

            <div class="col-xs-5">
                {{ Form::text('max_hours', Request::old('max_hours', $contract->max_hours), [ 'class' => 'form-control' ])
                }}
            </div>
            <div class="col-xs-5">{!! $errors->first('max_hours', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label" id="rateID">FMV Rate</label>

            <div class="col-xs-5">
                <div class="input-group">
                    {{ Form::text('rate', Request::old('rate', $contract->rate), [ 'class' => 'form-control' ]) }}
                    <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                </div>
              <span class="help-block">
                The rate must include the dollar amount followed by cents, for example
                <strong>50.75</strong>.
              </span>
            </div>
            <div class="col-xs-5">{!! $errors->first('rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Activities<span class="badge">42</span></div>
    <div class="panel-body">
        <div class="row">
            @foreach ($activities as $action)
            <div class="col-xs-6">
                <div class="form-group">
                    <div class="col-xs-8">
                        {{ Form::checkbox('actions[]', $action->id, $action->checked) }} {{ $action->name }}
                    </div>
                    <div class="col-xs-4">
                        {{ Form::text($action->field, $action->hours, ['class' => 'form-control']) }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">Management Duties<span class="badge">42</span></div>
    <div class="panel-body">
        <div class="row">
            @foreach ($duties as $action)
            <div class="col-xs-6">
                <div class="form-group">
                    <div class="col-xs-8">
                        {{ Form::checkbox('actions[]', $action->id, $action->checked) }} {{ $action->name }}
                    </div>
                    <div class="col-xs-4">
                        {{ Form::text($action->field, $action->hours, ['class' => 'form-control']) }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<button class="btn btn-default btn-primary btn-submit" type="submit">Submit</button>
{{ Form::close() }}
@endsection