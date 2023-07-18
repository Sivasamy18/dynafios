<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Contract Type</label>
                <div class="col-xs-5">
                    {{ Form::select('contract_type', $contract_types, Request::old('contract_type', $contract_type), [ 'class' =>
                    'form-control' ]) }}
                </div>
            </div>
            @if (isset($showCheckbox))
                <div class="form-group">
                    <div class="col-xs-3 control-label">
                        <label>Show All Contracts</label>
                    </div>
                    <div class="col-xs-4" style="padding-top: 7px;">
                        {{ Form::checkbox('show_all_contracts','show',$isChecked ,['class'=>'show_all_contracts']) }}
                    </div>
                </div>
            @endif
            @if (isset($showDeletedPhysicianCheckbox))
                <div class="form-group">
                    <div class="col-xs-3 control-label">
                        <label>Show All Physicians</label>
                    </div>
                    <div class="col-xs-4" style="padding-top: 7px;">
                        <div class="col-xs-2" style="padding: 0px;">
                            {{ Form::checkbox('show_deleted_physicians','show',$isPhysiciansShowChecked ,['class'=>'show_deleted_physicians']) }}
                        </div>
                        <div class="help-block col-xs-10" style="padding: 0px;">
                            Checked to include deleted physicians.
                        </div>
                    </div>
                </div>
            @endif
            <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 10px;">
                <div class="col-xs-7">
                    <div class="col-xs-4"><label>Agreement</label></div>
                    <!-- <div class="col-xs-4"><label>Start Month</label></div>
                    <div class="col-xs-4"><label>End Month</label></div> -->
                    <div class="col-xs-4"><label>Start Period</label></div>
                    <div class="col-xs-4"><label>End Period</label></div>
                </div>
                <div class="col-xs-5"><label>
                        @if (isset($practices))
                            Practices
                        @endif
                        @if (isset($physicians))
                            Physicians
                        @endif
                    </label>
                </div>
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
                    @if (isset($practices))
                        <div class="form-group">
                            <div class="col-xs-11">
                                {{ Form::select('practices[]', $practices, Request::old('practices[]'), [ 'id' => 'practices', 'class' => 'form-control', 'multiple' => 'multiple' ]) }}
                                <p class="help-block">
                                    <input id="all" type="checkbox"/><span id="all-label">Select All (Control/Command + Click to select or deselect items)</span>
                                </p>
                            </div>
                        </div>
                    @endif
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

                <?php  /* if(isset($check) && $check == 1){ ?>
                <div class="col-xs-12">
                    {{ Form::checkbox('finalized', '1', false, ['class' => 'finalized']) }}Finalize Report</div>
                <?php } */ ?>
            </div>

        </div>
        <div class="panel-footer clearfix">
            <div class="help-block" style="float:left; margin-top: 8px;">
                Click the agreement(s) for your report or check request before pressing submit.
            </div>
            <button class="btn btn-primary btn-sm btn-submit">Submit</button>
        </div>
    </div>
    <input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
    <input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
    {{ Form::close() }}
</div>
<script>
    
$(document).ready(function () {
    function formatAMPM(date) {
                    var month = date.getMonth()+1;
                    var day = date.getDate();
                    var year = date.getFullYear();
                    var hours = date.getHours();
                    var minutes = date.getMinutes();
                    var ampm = hours >= 12 ? 'PM' : 'AM';
                    month = month > 9 ? month : '0'+month;
                    day = day > 9 ? day : '0'+day;
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    minutes = minutes < 10 ? '0'+minutes : minutes;
                    var strTime = month+'/'+day+'/'+year+' '+hours + ':' + minutes + ' ' + ampm;
                    return strTime;
    }
    $(".form-generate-report").submit(function(){

       var timeZone = formatAMPM(new Date());
       var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;

       if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }
       $("#current_timestamp").val(timeZone);
       $("#current_zoneName").val(zoneName);
});

});

</script>
