@extends('layouts/_physician', [ 'tab' => 2 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('contract_id', $contract->id) }}
 <!-- physician to multiple hospital by 1254 -->
    <div class="panel panel-default">
        <div class="panel-heading">
            Unapprove Logs
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('contracts.edit', [$contract->id,$practice->id,$physician->id]) }}">
                Back
            </a>
        </div>

        <div class="panel-body">

            <div class="form-group">
                <label class="col-xs-2 control-label">Periods</label>
                <div class="col-xs-5">
                    {{ Form::select('period', $periods->dates, Request::old('period', $period), [ 'class' => 'form-control'
                    ]) }}
                </div>
            </div>

			<div class="form-group">
                <label class="col-xs-2 control-label">Reasons</label>
                <div class="col-xs-5">
                    {{ Form::select('reason', $reasons, Request::old('reason'), [ 'class' => 'form-control', 'id' => 'reason' ]) }}
                </div>
            </div>
			
			<div class="form-group custom-reason" style="display:none">
                <label class="col-xs-2 control-label">Custom Reason</label>
                <div class="col-xs-5">
                    {{ Form::textarea("unapprove_custom_reason_text", Request::old("unapprove_custom_reason_text"), [ 'class' => 'form-control','id' => "unapprove_custom_reason_text",'maxlength' => 256, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none', 'placeholder' => 'Custom reason upto 256 characters...' ]) }}
					<p id="error_custom_reason_text" class="error_reason" style="text-align: center; color: red; display:none"><b>Please add custom reason. Custom reason cannot be blank.</b></p>
                </div>
            </div>

            <div class="panel-footer clearfix">
                <button class="btn btn-primary btn-sm btn-submit" type="submit">Unapprove</button>
            </div>

    </div>
    {{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
	$( document ).ready(function() {
		$("#reason").change(function() {
			$("#unapprove_custom_reason_text").val('');
			$(".custom-reason").hide();
			$("#error_custom_reason_text").hide();
			if($(this).val() == '-1'){
				$(".custom-reason").show();
			}
        });

		$(".btn-submit").click(function() {
			var text = $("#unapprove_custom_reason_text").val();
			var reason = $('#reason').val();
			$("#error_custom_reason_text").hide();
			if(reason == '-1' && text == ''){
				$("#error_custom_reason_text").show();
				return false;
			}
        });
	});
</script>
@endsection