@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-envelope fa-fw icon"></i> Mass Emailer
    </h3>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-emailer' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">&nbsp;</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Subject</label>

            <div class="col-xs-5">
                {{ Form::text('subject', Request::old('subject'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('subject', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Groups</label>

            <div class="col-xs-5">
                <div class="row">
                    <div class="col-xs-6">
                        {{ Form::checkbox('super_users', '1', Request::old('super_users', true)) }} Super Users<br/>
                        {{ Form::checkbox('practice_managers', '1', Request::old('practice_managers', true)) }} Practice
                        Managers
                    </div>
                    <div class="col-xs-6">
                        {{ Form::checkbox('hospital_admins', '1', Request::old('hospital_admins', true)) }} Hospital
                        Admins<br/>
                        {{ Form::checkbox('physicians', '1', Request::old('physicians', true)) }} Physicians
                    </div>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Body</label>

            <div class="col-xs-5">
                {{ Form::textarea('body', Request::old('body'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('body', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection