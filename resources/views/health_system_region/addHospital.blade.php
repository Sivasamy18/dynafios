@extends('layouts/_healthSystemRegion', [ 'tab' => 3 ])
@section('actions')
    <a class="btn btn-default" href="{{ URL::route('healthSystemRegion.hospitals', [$system->id,$region->id]) }}">
        <i class="fa fa-list fa-fw"></i> Index
    </a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-health-system-region' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Associate hospital with region</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Hospital</label>

            <div class="col-xs-5">
                {{ Form::select('hospital', $hospitals, Request::old('hospital'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('hospital', '<p class="validation-error">:message</p>') !!}</div>
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