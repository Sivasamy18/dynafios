@extends('layouts/_healthSystem', [ 'tab' => 2 ])
@section('actions')
    <a class="btn btn-default" href="{{ URL::route('healthSystem.regions', $system->id) }}">
        <i class="fa fa-list fa-fw"></i> Index
    </a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-health-system-region' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create Region</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>

            <div class="col-xs-5">
                {{ Form::text('region_name', Request::old('region_name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('region_name', '<p class="validation-error">:message</p>') !!}</div>
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