@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-globe fa-fw icon"></i> Health System
    </h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('healthSystem.index') }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-create-health-system' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">General</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('health_system_name', Request::old('health_system_name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('health_system_name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
</div>
<button class="btn btn-primary btn-submit" type="submit">Submit</button>
{{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
        $("input[name=npi]").inputmask({ mask: '9999999999' });
        $("input[name=expiration]").inputmask({ mask: '99/99/9999' });
    })
</script>
@endsection