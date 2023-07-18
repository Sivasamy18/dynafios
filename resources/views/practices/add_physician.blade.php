@extends('layouts/_practice', [ 'tab' => 3 ])
@section('actions')
<a class="btn btn-default" href="{{ URL::route('practices.create_physician', $practice->id) }}">
    <i class="fa fa-arrow-circle-left fa-fw"></i> Back
</a>
<a class="btn btn-default" href="{{ URL::route('practices.physicians', $practice->id) }}">
    <i class="fa fa-list fa-fw"></i> Index
</a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Add Physician</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Email</label>

            <div class="col-xs-5">
                {{ Form::text('email', Request::old('emails'), [ 'class' => 'form-control' ]) }}
                <p class="help-block">
                    You must enter an email address for a physician belonging to the other Hospital practice.
                </p>
            </div>
            <input type="hidden" value="{{$practice->hospital_id}}" name="hospital_id">
            <div class="col-xs-5">{!! $errors->first('email', '<p id="error-message" class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
 <!-- Physician to Multiple Hosptial by 1254 -->
    <div class="form-group">
            <label class="col-xs-2 control-label">Practice Start Date</label>

            <div class="col-xs-5">
                <div id="start-date" class="input-group">
                    {!! Form::text('practice_start_date', Request::old('practice_start_date'), [ 'class' => 'form-control' ]) !!}
                    <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                </div>
            </div>
            <div class="col-xs-5">
                {!! $errors->first('practice_start_date', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-submit" type="submit" onclick="return validateEmailField(email_domains)">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    let email_domains = '{{ env("EMAIL_DOMAIN_REJECT_LIST") }}';
    $(function () {
        $('input[name=phone]').inputmask({
            mask: '(999) 999-9999'
        });
    });
</script>
@endsection
