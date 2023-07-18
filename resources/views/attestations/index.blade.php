@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-list fa-fw icon"></i> Questions
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('attestations.create') }}">
            <i class="fa fa-plus-circle fa-fw"></i> Add Question
        </a>
    </div>
</div>
@include('layouts/_flash')

<div class="panel-body">
	<div class="form-group col-md-12">
		<div class="col-xs-1"></div>
		<label class="col-xs-3 control-label">States</label>
		<div class="col-xs-5" style="padding:0px">
			{{ Form::select('state', $states, Request::old('state'), [ 'class' => 'form-control', 'id'=> 'state' ]) }}
		</div>
	</div>
	
	<div class="form-group col-md-12">
		<div class="col-xs-1"></div>
		<label class="col-xs-3 control-label">Attestation Types</label>
		<div class="col-xs-5" style="padding:0px">
			{{ Form::select('attestation_type', $attestation_types, Request::old('attestation_type'), [ 'class' => 'form-control', 'id'=> 'attestation_type' ]) }}
		</div>
	</div>

	<div class="form-group col-md-12" style="border-bottom: 1px solid #eee; padding-bottom: 10px;">
		<div class="col-xs-1"></div>
		<label class="col-xs-3 control-label">Attestations</label>
		<div class="col-xs-5" style="padding:0px">
			{{ Form::select('attestation', $attestations, Request::old('attestation'), [ 'class' => 'form-control', 'id'=> 'attestation' ]) }}
		</div>
	</div>
</div>

<div id="state-list" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Question?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this question?</p>

                <!-- <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this contract name and any associated data. There is no way to
                    restore this data once this action has been completed.
                </p> -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Delete</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<!-- <script type="text/javascript" src="{{ asset('assets/js/ckeditor.js') }}"></script> -->
<script type="text/javascript">
    $(function () {
        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
            dialogButton: '.btn-primary'
        });

        Dashboard.pagination({
            container: '#contract-names',
            filters: '#contract-names .filters a',
            sort: '#contract-names .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
		
		$(document).on("change", "[name=state]", function(event) { Dashboard.updateAttestationForm(); });
		$(document).on("change", "[name=attestation]", function(event) { Dashboard.updateAttestationForm(); });
		$(document).on("change", "[name=attestation_type]", function(event) { Dashboard.updateAttestationForm(); });
    });
	
</script>
@endsection