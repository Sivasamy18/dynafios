@php use function App\Start\is_super_user; @endphp
@extends('layouts/_healthSystemRegion', [ 'tab' => 2 ])
@section('actions')
    @if (is_super_user())
        <a class="btn btn-default" href="{{ URL::route('healthSystemRegion.create_user', [$system->id, $region->id]) }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
        <a class="btn btn-default" href="{{ URL::route('healthSystemRegion.users',  [$system->id, $region->id]) }}">
            <i class="fa fa-list fa-fw"></i> Index
        </a>
    @endif
@endsection

@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Add Existing User</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Email</label>

            <div class="col-xs-5">
                {{ Form::text('email', Request::old('emails'), [ 'class' => 'form-control' ]) }}
                <p class="help-block">
                    You must enter an email address for a user belonging to the hospital.
                </p>
            </div>
            <div class="col-xs-5">{!! $errors->first('email', '<p id="error-message" class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Group</label>

            <div class="col-xs-5">
                {{ Form::select('group', $groups, Request::old('group'), [ 'class' => 'form-control' ]) }}
            </div>
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
