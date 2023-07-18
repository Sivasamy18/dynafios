<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Organization</label>
                <div class="col-xs-5">
                    {{ Form::select('facility', $facilities, Request::old('facility', $facility), [ 'id'=>'hospital', 'class' =>'form-control' ]) }}
                </div>
            </div>
			<div class="form-group">
                <label class="col-xs-3 control-label">Contract Type</label>
                <div class="col-xs-5">
                    {{ Form::select('contract_type', $contract_types, Request::old('contract_type', $contract_type), [ 'id'=>'contract_type', 'class' =>'form-control' ]) }}
                </div>
            </div>
            <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 10px;">
                <div class="col-xs-4"><label>Agreement</label></div>
                <div class="col-xs-3"><label>Month</label></div>
            </div>
            <div class="agreements" style="border-bottom: 1px solid #ddd; margin-bottom: 20px;">

                <div id="agreementsInfo" class="col-xs-7" style="margin-top: 20px;">
                    @foreach ($agreements as $agreement)

                        <div class="form-group">
                            <div class="col-xs-5">
                                {{ Form::checkbox('agreements[]', $agreement->id, false, ['id'=>'agreementchk_' . $agreement->id, 'class' => 'agreement']) }}{{ $agreement->name }}
                            </div>
                            <div class="col-xs-7">
                                {{ Form::select("agreement_{$agreement->id}_start_month", $agreement->dates, $agreement->current_month - 1, ['class' => 'form-control ']) }}

                            </div>

                        </div>
                    @endforeach
                </div>
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
$(document).ready(function (){


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
        console.log("timeZone",timeZone);
       if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }
       $("#current_timestamp").val(timeZone);
       $("#current_zoneName").val(zoneName);


});
  /*Change of option in facility dropdown*/
  $("#hospital").change(function (){
    var hospital_value = $("#hospital").val();
    getContractTypes(hospital_value);
    var contract_type = $("#contract_type").val(0);
    getAgreements(hospital_value, contract_type);
  });

  $("#contract_type").change(function (){
    var hospital_value = $("#hospital").val();
    var contract_type = $("#contract_type").val();
    getAgreements(hospital_value, contract_type);
  });
});

function getAgreements(hospital_value, contract_type){
    $.ajax({
		url:'/getComplianceAgreementsByHospital/'+ hospital_value + '/' + contract_type,
		dataType: 'json',
        success:function(response){
            var html = '';
            $.each(response['agreement'], function (index, details) {
                html = html + '<div class="form-group"><div class="col-xs-5"><input id="agreementchk_' + details.id +'" class="agreement" name="agreements[]" type="checkbox" value="' + details.id +'">'+ details.name +' </div>';
                html = html + '<div class="col-xs-7"><select class="form-control " name="agreement_' + details.id +'_start_month">';
                $.each(details.dates, function(mnum,range){
                    html = html +'<option value="' + mnum +'">' + range +'</option>';
                });
                html = html +'</select></div></div>';
            });
            $('#agreementsInfo').html('');
            $('#agreementsInfo').html(html);
        }
    })
}

function getContractTypes(hospital_id){
    $.ajax({
		url:'/getContractTypesForComplianceReport/'+ hospital_id,
		dataType: 'json',
        success:function(response){
            var html = '';
            $.each(response['contract_types'], function (index, details) {
                html = html +'<option value="' + index +'">' + details +'</option>';
            });
            $('#contract_type').html('');
            $('#contract_type').html(html);
        }
    })
}
</script>