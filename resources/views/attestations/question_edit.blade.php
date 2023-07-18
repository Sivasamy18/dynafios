@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-list fa-fw icon"></i> Question
    </h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('attestations.index') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open(['class' => 'form form-horizontal form-update-question']) }}
{{ Form::hidden('state_id', $question->state_id , array('id' => $question->state_id)) }}
{{ Form::hidden('attestation_id', $question->attestation_id , array('id' => $question->attestation_id)) }}
{{ Form::hidden('question_id', $question->id , array('id' => $question->id)) }}
<div class="panel panel-default">
    <div class="panel-heading">Update Question</div>
    <div class="panel-body">
		<div class="form-group">
            <label class="col-xs-3 control-label">State</label>

            <div class="col-xs-5">
				{{ Form::select('state', $states, Request::old('state', $question->state_id), ['class' => 'form-control', 'id'=> 'state' ]) }}
				<!--{{ Form::text('state', Request::old('state', $question->state_name), [ 'class' => 'form-control', 'disabled']) }}-->
            </div>
        </div>
		
		<div class="form-group">
            <label class="col-xs-3 control-label">Attestation Type</label>

            <div class="col-xs-5">
				{{ Form::select('attestation_type', $attestation_types, Request::old('attestation_type', $question->attestation_type), ['class' => 'form-control', 'id'=> 'attestation_type' ]) }}
				<!--{{ Form::text('state', Request::old('state', $question->state_name), [ 'class' => 'form-control', 'disabled']) }}-->
            </div>
        </div>
		
		<div class="form-group">
            <label class="col-xs-3 control-label">Attestation</label>

            <div class="col-xs-5">
				{{ Form::select('attestation', $attestations, Request::old('attestation', $question->attestation_id), ['class' => 'form-control', 'id'=> 'attestation' ]) }}
				<!--{{ Form::text('attestation', Request::old('attestation', $question->attestation_name), [ 'class' => 'form-control', 'disabled']) }}-->
            </div>
        </div>
		
		<div class="form-group">
            <label class="col-xs-3 control-label">Question</label>

            <div class="col-xs-8">
                <textarea id="editor" name="editor"rows="10" cols="60" maxlength="3000">{{$question->question}}</textarea>
            </div>
        </div>

		<div class="form-group">
            <label class="col-xs-3 control-label">Question Type</label>

            <div class="col-xs-5">
				{{ Form::select('question_type', $question_types, Request::old('question_type', $question->question_type_id), ['class' => 'form-control', 'id'=> 'question_type' ]) }}
            </div>
        </div>
		
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection

@section('scripts')
<script type="text/javascript" src="{{ asset('assets/js/ckeditor.js') }}"></script>
<script type="text/javascript">

	ClassicEditor
		.create(document.querySelector('#editor'),{
			removePlugins: ['IncreaseIndent', 'Table', 'Link', 'BlockQuote', 'undo', 'redo', 'CKFinderUploadAdapter', 'CKFinder', 'EasyImage', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'MediaEmbed'],
		})
		.catch(error => {
			console.error(error)
		});
		
	$(document).on("change", "#attestation", function(event) { Dashboard.updateQuestionType(); });
</script>
@endsection