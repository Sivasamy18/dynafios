@extends('layouts/_hospital', [ 'tab' => 4 ])
@section('actions')
<a class="btn btn-default" href="{{ URL::route('hospitals.practices', $hospital->id) }}">
    <i class="fa fa-list fa-fw"></i> Practices Index
</a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create Practice</div>
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
            <label class="col-xs-2 control-label">Type</label>

            <div class="col-xs-5">
                {{ Form::select('practice_type', $practiceTypes, Request::old('practice_type'), [ 'class' =>
                'form-control' ]) }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">State</label>

            <div class="col-xs-5">
                {{ Form::select('state', $states, Request::old('state'), [ 'class' => 'form-control' ]) }}
            </div>
        </div>

        <?php
            // if($invoice_type == 1){
            //     $note_count = App\InvoiceNote::PRACTICECOUNT;
            // } else {
            //     $note_count = 1;
            // }
        ?>

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
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
        $("input[name='npi']").inputmask({ mask: '9999999999' });
    });
</script>
@endsection