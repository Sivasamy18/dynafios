<!--if condition is added to check agreements are present or not-->
@if($agreements!='')
<div class="form-wrapper" style="position: relative;">
	{{ Form::open([ 'class' => 'form form-horizontal form-payment' ]) }}
	    <div class="panel panel-default">
	        <div class="panel-heading">Add Payment</div>
	        <div class="panel-body">
	        	<div class="form-group">
	                <label class="col-xs-2 control-label">Agreement</label>
	                <div class="col-xs-5">
	                    {{ Form::select('agreement', $agreements, Request::old('agreement', $agreement), [ 'class' => 'form-control' ]) }}
	                </div>
	            </div>
	            <div class="form-group">
	                <label class="col-xs-2 control-label">Month</label>
	                <div class="col-xs-5">
	                    {{ Form::select('month', $months, Request::old('month', $month), [ 'class' => 'form-control' ]) }}
	                </div>
	            </div>
				<div class="form-group">
					<label class="col-xs-2 control-label">Expected Amount</label>
					<div class="col-xs-5">
						<div class="input-group">
							<input type="text" class="form-control" name="remaining" value="{{ $remaining }}" placeholder="$" readonly>
							<span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
						</div>
					</div>
				</div>
	            <div class="form-group">
	                <label class="col-xs-2 control-label">Amount</label>
	                <div class="col-xs-5">
	                    <div class="input-group">
	                    	{{ Form::text('amount', Request::old('amount'), [ 'class' => 'form-control']) }}
	                    	<span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
	                	</div>
	                </div>
	                <div class="col-xs-5">{!! $errors->first('amount', '<p class="validation-error">:message</p>') !!}</div>
	            </div> 
	            
	        </div>
	        <div class="panel-footer clearfix">            
	            <button class="btn btn-primary btn-sm btn-submit">Submit</button>
	        </div>
	    </div>
	{{ Form::close() }}
</div>
@else
	<div class="panel panel-default">
		<div class="panel-body">There are currently no agreements available to add payments.</div>
	</div>
@endif