<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="form-group">
                <div class="col-xs-7">
                    <div class="col-xs-4"><label>Agreement</label></div>
                    <div class="col-xs-4"><label style="padding:0px 26px">Start Period</label></div>
                    <div class="col-xs-4"><label style="padding:0px 26px">End Period</label></div>
                </div>
				@if (isset($physicians))
					<div class="col-xs-5"><label>
						Physicians
						</label>
					</div>
				@endif
            </div>
			<div class="agreements" style="border-bottom: 1px solid #ddd; margin-bottom: 20px;">
				<div class="col-xs-7" style="margin-top: 20px;">
					@foreach ($agreements as $agreement)

                        <div class="form-group">
                            <div class="col-xs-4">
								@if($agreement->disable)
									{{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement','disabled'=>'disabled']) }}{{ $agreement->name }}
                                @else
									{{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement']) }}{{ $agreement->name }}
                                @endif
                            </div>
                            <div class="col-xs-8">
                                @if(!empty($selected_start_date) && !empty($selected_end_date) && array_key_exists($agreement->id,$selected_start_date) && array_key_exists($agreement->id,$selected_end_date))
                                    @if($selected_start_date[$agreement->id] != null && $selected_end_date[$agreement->id] != null)
										<div class="col-xs-6" style="padding-right: 5px; padding-left: 5px;">
											{{ Form::select("start_{$agreement->id}_start_month", $agreement->start_dates, $selected_start_date[$agreement->id], ['class' => 'form-control select_dates']) }}
										</div>
										<div class="col-xs-6" style="padding-right: 5px; padding-left: 5px;">
											{{ Form::select("end_{$agreement->id}_start_month", $agreement->end_dates, $selected_end_date[$agreement->id] , ['class' => 'form-control select_dates']) }}
										</div>
                                    @endif
                                @else
                                    <div class="col-xs-6" style="padding-right: 5px; padding-left: 5px;">
                                        {{ Form::select("start_{$agreement->id}_start_month", $agreement->start_dates, $agreement->current_month - 1, ['class' => 'form-control select_dates']) }}
                                    </div>
                                    <div class="col-xs-6" style="padding-right: 5px; padding-left: 5px;">
                                        {{ Form::select("end_{$agreement->id}_start_month", $agreement->end_dates, $agreement->current_month - 1, ['class' => 'form-control select_dates']) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
				</div>
				<div class="col-xs-5" style="margin-top: 20px;">
                    @if (isset($physicians))
                        <div class="form-group">
                            <div class="col-xs-11">
                                {{ Form::select('physicians[]', $physicians, Request::old('physicians[]'), [ 'id' => 'physicians', 'class' => 'form-control', 'multiple' => 'multiple' ]) }}
                                <p class="help-block">
                                    <input id="all" type="checkbox"/><span id="all-label">Select All (Control/Command + Click to select or deselect items)</span>
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
			</div>
        </div>

		<input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
		<input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
    </div>
    {{ Form::close() }}
</div>
<style>
.form .panel
{
  border:0px;
}
.form .panel-heading
{
  border-radius:0px;
}
</style>


