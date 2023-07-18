<div id="contracts">
	<div class="alert alert-success ajax-success" style="display:none;">
			<strong>Success! </strong>Data saved successfully and an invoice has been sent to all the delegated recipients!.
	</div>
	<div class="alert alert-danger ajax-failed" style="display:none;">

		<strong>Error! </strong>There is a problem.Please try again after sometime.
	</div>
	<div class="alert alert-danger ajax-error" style="display:none;">

		<strong>Error! </strong>You have already finalized reports for some physicians between this date
		range.So you can not change data for them
	</div>
	<div>
		<div class="form-group col-xs-12 paddingZero hide">
			<div class="">
				<label class="control-label">Agreement </label>
			</div>
			<div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
				{{ Form::select("agreements", $agreements, $selected_agreement_id, ['class' => 'form-control dataFilters','id' => 'agreement_id']) }}
			</div>
		</div>

		<div class="form-group col-xs-12 paddingZero">
			<div class="">
				<label class="control-label">Practice </label>
			</div>
			<div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
				{{ Form::select("practices", $practice_list, $practice_id, ['class' => 'form-control dataFilters','id' => 'practice_id']) }}
			</div>
		</div>

		<div class="form-group col-xs-12 paddingZero">
			<div class="">
				<label class="control-label">Payment Type </label>
			</div>
			<div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
				{{ Form::select("payment_types", $payment_type_list, $payment_type_id, ['class' => 'form-control dataFilters','id' => 'payment_type_id']) }}
			</div>
		</div>

		<div class="form-group col-xs-12 paddingZero">
			<div class="">
				<label class="control-label">Contract Type </label>
			</div>
			<div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
				{{ Form::select("contract_types", $contract_type_list, $contract_type_id, ['class' => 'form-control dataFilters','id' => 'contract_type_id']) }}
			</div>
		</div>

		<div class="form-group col-xs-12 paddingZero">
			<div class="">
				<label class="control-label">Physician </label>
			</div>
			<div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
				{{ Form::select("physicians", $physician_list, $physician_id, ['class' => 'form-control dataFilters','id' => 'physician_id']) }}
			</div>
		</div>

		
			
		<div class="form-group col-xs-12 paddingZero">
			<label class="col-xs-1 control-label" style="padding-left: 5px !important; padding-right: 5px !important; margin-top: -3px !important;">Start Date</label>
			<div class="col-xs-4 paddingZero">
				<div id="start-date" class="input-group">
					{{ Form::text('start_date', Request::old('start_date', $start_date), [ 'class' => 'form-control dataFilters', 'id' => 'start_date' ]) }}
					<span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
				</div>
			</div>
			
			<label class="col-xs-1 control-label" style="padding-left: 5px !important; padding-right: 5px !important; margin-top: -3px !important;">End Date</label>
			<div class="col-xs-4 paddingZero">
				<div id="end-date" class="input-group">
					{{ Form::text('end_date', Request::old('end_date', $end_date), [ 'class' => 'form-control dataFilters', 'id' => 'end_date' ]) }}
					<span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
				</div>
			</div>
			<div class="col-xs-2" style="padding: 0px !important">
				<button type="button" id="apply_filter" class="btn btn-success" style="float: right;">Apply</button>
			</div>
		</div>
		
		
	</div>
	{!! $table !!}
</div>

<input type="hidden" id="min_date" name="min_date" value="{{ $min_date }}">
<input type="hidden" id="max_date" name="max_date" value="{{ $max_date }}">

<script type="text/javascript">
    $(function () {
		var start_date = $('#min_date').val();
		var end_date = $('#max_date').val();
		$("#start-date").datetimepicker({ language: 'en_US', pickTime: false, minDate: start_date, maxDate: end_date });
		$("#end-date").datetimepicker({ language: 'en_US', pickTime: false, minDate: start_date, maxDate: end_date });
	})
</script>
