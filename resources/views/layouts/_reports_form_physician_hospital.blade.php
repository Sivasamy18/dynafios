<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
			<div class="form-group">
                <label class="col-xs-3 control-label">Organization</label>
                <div class="col-xs-5">
					{{ Form::select('hospital', $hospitals, Request::old('hospital', $hospital), [ 'id'=>'hospital','class' => 'form-control' ]) }}
                </div>
            </div>
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
            
            <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 10px;">
                <div class="col-xs-5"><label>Agreement</label></div>
                <!-- <div class="col-xs-3"><label>Month</label></div> -->
                <div class="col-xs-3"><label>Period</label></div>
            </div>
            <div class="agreements" style="border-bottom: 1px solid #ddd; margin-bottom: 20px;">

                <div class="col-xs-8" style="margin-top: 20px;">
                    @foreach ($agreements as $agreement)

                        <div class="form-group">
                            <div class="col-xs-5">
                                {{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement']) }}{{ $agreement->name }}
                            </div>
                            <div class="col-xs-7">
                                {{ Form::select("agreement_{$agreement->id}_start_month", $agreement->dates, $agreement->current_month - 1, ['class' => 'form-control ']) }}

                            </div>

                        </div>
                    @endforeach
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
