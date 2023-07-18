@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-hospital-o fa-fw icon"></i> Hospital
    </h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('hospitals.index') }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-create-hospital' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">General</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('name', Request::old('name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">NPI</label>

            <div class="col-xs-5">
                {{ Form::text('npi', Request::old('npi'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('npi', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Facility Type</label>

            <div class="col-xs-5">
                {{ Form::select('facility_type', $facility_types, Request::old('facility_types'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('facility_type', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Address</label>

            <div class="col-xs-5">
                {{ Form::text('address', Request::old('address'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('address', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">City</label>

            <div class="col-xs-5">
                {{ Form::text('city', Request::old('city'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('city', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">State</label>

            <div class="col-xs-5">
                {{ Form::select('state', $states, Request::old('states'), [ 'class' => 'form-control' ]) }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Benchmark Rejection Percentage</label>

            <div class="col-xs-5">
                {{ Form::text('benchmark', Request::old('benchmark'), [ 'class' => 'form-control' ]) }}
            </div>
        </div>
        {{ Form::hidden('note_count',Request::old('note_count',1),['id' => 'note_count']) }}
        <div id="notes">
            @for($i = 0; $i < Request::old('note_count',1); $i++ )
                <div class="form-group invoive-note">
                    <label class="col-xs-2 control-label">Invoice Note {{ $i+1 }}</label>

                    <div class="col-xs-5">
                        {{ Form::textarea("note".($i+1), Request::old("note".($i+1)), [ 'class' => 'form-control','id' => "note".($i+1),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                    </div>

                    <div class="col-xs-2"><button class="btn btn-primary btn-submit remove-note" type="button"> - </button></div>

                    <div class="col-xs-3">{!! $errors->first('note'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                </div>
            @endfor
        </div>
        <button class="btn btn-primary btn-submit add-note" type="button">Add Invoice Note</button>
    </div>
</div>
<button class="btn btn-primary btn-submit" type="submit">Submit</button>
{{ Form::close() }}
@endsection
@section('scripts')
@endsection