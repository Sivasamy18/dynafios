@extends('layouts/_practice', [ 'tab' => 3 ])
@section('actions')
<!-- Physician to All Hospital by 1254 -->
<a class="btn btn-default" href="{{ URL::route('practices.physicians', $practice->id) }}">
    <i class="fa fa-list fa-fw"></i> Index
</a>
<a class="btn btn-default" href="{{ URL::route('practices.add_physician', $practice->id) }}">
    <i class="fa fa-user fa-fw"></i> Existing
</a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create Physician</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Email</label>

            <div class="col-xs-5">
                {{ Form::text('email', Request::old('email'), [ 'class' => 'form-control' ]) }} 
            </div>
            <div class="col-xs-5">{!! $errors->first($errors->has('emailDeleted') ? 'emailDeleted':'email', '<p id="error-message" class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">First Name</label>

            <div class="col-xs-5">
                {{ Form::text('first_name', Request::old('first_name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('first_name', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Last Name</label>

            <div class="col-xs-5">
                {{ Form::text('last_name', Request::old('last_name'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('last_name', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">NPI</label>

            <div class="col-xs-5">
                {{ Form::text('npi', Request::old('npi'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('npi', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Phone</label>

            <div class="col-xs-5">
                {{ Form::text('phone', Request::old('phone'), [ 'class' => 'form-control', 'placeholder' => '(999) 999-9999' ]) }}
            </div>
            <div class="col-xs-5">
                {!! $errors->first('phone', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Specialty</label>

            <div class="col-xs-5">
                {{ Form::select('specialty', $specialties, Request::old('specialty'), [ 'class' => 'form-control' ]) }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Practice Start Date</label>

            <div class="col-xs-5">
                <div id="start-date" class="input-group">
                    {{ Form::text('practice_start_date', Request::old('practice_start_date'), [ 'class' => 'form-control' ]) }}
                    <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                </div>
            </div>
            <div class="col-xs-5">
                {!! $errors->first('practice_start_date', '<p class="validation-error">:message</p>') !!}
            </div>
        </div>
        <?php
            // if($invoice_type == 1){
            //     $note_count = App\InvoiceNote::PHYSICIANCOUNT;
            // } else {
            //     $note_count = 1;
            // }
            $note_count = 1;
        ?>

        {{ Form::hidden('note_count',Request::old('note_count',$note_count),['id' => 'note_count']) }}
        <div id="notes">
            @for($i = 0; $i < Request::old('note_count',$note_count); $i++ )
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
        $("input[name=npi]").inputmask({ mask: '9999999999' });
        $('input[name=phone]').inputmask({ mask: '(999) 999-9999' });
    });
</script>
@endsection