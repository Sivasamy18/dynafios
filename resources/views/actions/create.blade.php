@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3><i class="fa fa-exclamation-circle fa-fw icon"></i> Actions</h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('actions.index') }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create Action</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('name', Request::old('name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
 <!-- Action-Redesign by 1254 -->
        <div class="form-group">
            <label class="col-xs-2 control-label">Category</label>

            <div class="col-xs-5">
                {{ Form::select('category', $categories, Request::old('category'), [ 'class' => 'form-control' ])
                }}
            </div>
        </div>
     <!--
         <div class="form-group">
            <label class="col-xs-2 control-label">Action Type</label>

            <div class="col-xs-5">
               {{-- Form::select('action_type', $actionTypes, Request::old('action_type'), [ 'class' => 'form-control' ])
               --}}
            </div>
        </div>
        
        <div class="form-group">
            <label class="col-xs-2 control-label">Payment Type</label>

            <div class="col-xs-5">
                {{-- Form::select('payment_type', $paymentTypes, Request::old('payment_type'), [ 'class' =>
                'form-control' ]) --}}
            </div>
        </div> 
    </div> !-->
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection