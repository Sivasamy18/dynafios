<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Organization</label>
                <div class="col-xs-5">
                    {{ Form::select('facility', $facilities, Request::old('facility', $facility), [ 'id'=>'hospital', 'name'=>'hospital', 'class' =>'form-control' ]) }}
                </div>
            </div>
			
			<div class="form-group">
                <label class="col-xs-3 control-label">Contract Type</label>
                <div class="col-xs-5">
                    {{ Form::select('contract_type', $contract_types, Request::old('contract_types', $contract_type), [ 'id'=>'contract_type', 'name'=>'contract_type', 'class' =>'form-control' ]) }}
                </div>
            </div>

            <!-- <div id="time_period" class="form-group hidden">
                <label class="col-xs-3 control-label" style="margin-top: 27px;">Time Period:</label>
                <div class="col-md-8 col-sm-8 col-xs-8 paddingZero">
                    <div class="col-md-3 col-sm-3 col-xs-3 paddingLeft">
                        <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center; margin-left: 17%;">Start Month</label>
                        <select id="start_month" name="start_month" class="form-control" style="margin-left: 12%;"></select>
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-3 paddingRight">
                        <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center; margin-left: 4%;">End Month</label>
                        <select id="end_month" name="end_month" class="form-control"></select>
                    </div>
                </div>
            </div> -->

            <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 10px;">
                @if (isset($physicians))
                    <div class="col-xs-7"><label>Physicians</label></div>
					<input type="hidden" id="selected_report" name="selected_report" value="physicians">
                @elseif(isset($approvers))
                    <div class="col-xs-7"><label>Approvers</label></div>
					<input type="hidden" id="selected_report" name="selected_report" value="approvers">
                @endif
                <div class="col-xs-5"><label>Agreements</label></div>
            </div>
            <div class="physicians" style="border-bottom: 1px solid #ddd; margin-bottom: 20px;">

                <div id="physiciansInfo" class="col-xs-7" style="margin-top: 20px;">
                    @if (isset($physicians))
                        @foreach ($physicians as $physician)

                            <div class="form-group">
                                <div class="col-xs-5">
                                {{ Form::checkbox('physicians[]', $physician->id, false, ['id'=>'physicianchk_' . $physician->id, 'class' => 'physician']) }}{{ $physician->first_name }} {{ $physician->last_name }}
                                </div>
                            </div>
                        @endforeach
                    @elseif(isset($approvers))
                        @foreach ($approvers as $approver)

                            <div class="form-group">
                                <div class="col-xs-5">
                                {{ Form::checkbox('approvers[]', $approver->id, false, ['id'=>'approverchk_' . $approver->id, 'class' => 'approvers']) }}{{ $approver->first_name }} {{ $approver->last_name }}
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div id="agreementInfo" class="col-xs-5" style="margin-top: 20px;">
                    @if (isset($agreements))
                        <div class="form-group">
                            <div class="col-xs-12">
                                {{ Form::select('agreements[]', $agreements, Request::old('agreements[]'), [ 'id' => 'agreement', 'class' => 'form-control', 'multiple' => 'multiple' ]) }}
                                <p class="help-block">
                                    <input id="chk_all" type="checkbox"/><span id="spn_all-label">Select All (Control/Command + Click to select or deselect items)</span>
                                </p>
                            </div>
                            <div class="col-xs-12 hidden">
                                <div id="time_period" class="form-group hidden">
                                    <label class="col-xs-4 control-label" style="margin-top: 27px;">Time Period:</label>
                                    <div class="col-md-8 col-sm-8 col-xs-8 paddingZero">
                                        <div class="col-md-6 col-sm-6 col-xs-6 paddingLeft">
                                            <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center; margin-left: 17%;">Start Month</label>
                                            <select id="start_month" name="start_month" class="form-control" style="margin-left: 12%;"></select>
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-6 paddingRight">
                                            <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center; margin-left: 4%;">End Month</label>
                                            <select id="end_month" name="end_month" class="form-control"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
		var hospital_value = $('#hospital').val();
		getContractTypes(hospital_value);
		var contract_type = $('#contract_type').val(-1);
		var selected_report = $('#selected_report').val();
		getPhysicianApprover(hospital_value, -1, selected_report);
	});
	
	$("#contract_type").change(function (){
		var hospital_value = $("#hospital").val();
		var contract_type = $("#contract_type").val();
		var selected_report = $('#selected_report').val();
		getPhysicianApprover(hospital_value, contract_type, selected_report);
	});
  
	$('#agreement').change(function() {
		$("#time_period").removeClass("hidden");
		getTimePeriod();
	});
	
	$('#chk_all').click(function(e){
		if ($(this).is(':checked')) {
			$("#agreement > option").each(function() {
				$(this).prop("selected", true);
			});

			$("#spn_all-label").html('');
			$("#spn_all-label").html('Deselect All (Control/Command + Click to select or deselect items)');
            if( $('#agreement').has('option').length > 0 ) {
                getTimePeriod();
			    $("#time_period").removeClass("hidden");
            }
		}else{
			$("#agreement > option").each(function() {
				$(this).prop("selected", false);
			});

			$("#spn_all-label").html('');
			$("#spn_all-label").html('Select All (Control/Command + Click to select or deselect items)');
			$("#time_period").addClass("hidden");
		} 
	});
	
	function getTimePeriod(){
		var hospital_value = $("#hospital").val();
		var selected_agrements = $('#agreement').val();
		
		$.ajax({
		url:'/getTimePeriodByAgreements/'+ hospital_value +'/' + selected_agrements,
		dataType: 'json',
        success:function(response){
            var html_start_month = '';
			var html_end_month = '';

            $.each(response, function (index, details) {
				$.each(details.start_dates, function(index, value) {
                    if(index != '1970-01-01'){
                        html_start_month = html_start_month + '<option value="' + index + '">'+ value +'</option>';
                    }else{
                        var current_year = new Date().getFullYear();
                        html_start_month = html_start_month + '<option value="' + current_year + '-01-01">1: 01/01/'+ current_year +'</option>';
                    }
				});
				$.each(details.end_dates, function(index, value) {
                    if(index != '1970-01-31'){
					    html_end_month = html_end_month + '<option value="' + index + '-01-31">'+ value +'</option>';
                    }else{
                        var current_year = new Date().getFullYear();
                        html_end_month = html_end_month + '<option value="' + current_year + '">1: 01/31/'+ current_year +'</option>';
                    }
				});
            });
			
            $('#start_month').html('');
            $('#start_month').html(html_start_month);
			$('#end_month').html('');
            $('#end_month').html(html_end_month);
        }
		})
	}
	
	function getPhysicianApprover(hospital_value, contract_type, selected_report){
        if(selected_report == 'approvers'){
            $.ajax({
                url:'/getApproversByHospital/'+ hospital_value + '/' + contract_type,
                dataType: 'json',
                success:function(response){
                    var html = '';
                    $.each(response['approvers'], function (index, details) {
                        html = html + '<div class="form-group"><div class="col-xs-5"><input id="approverchk_' + details.id + '" class="approvers" name="approvers[]" type="checkbox" value="' + details.id + '">'+ details.first_name +' ' + details.last_name +'</div></div>';
                    });
                    $('#physiciansInfo').html('');
                    $('#physiciansInfo').html(html);
                    $('#agreement option').remove();
                }
            })
        }else{
            $.ajax({
                url:'/getPhysiciansByHospital/'+ hospital_value + '/' + contract_type,
                dataType: 'json',
                success:function(response){
                    var html = '';
                    $.each(response['physicians'], function (index, details) {
                        html = html + '<div class="form-group"><div class="col-xs-5"><input id="physicianchk_' + details.id + '" class="physician" name="physicians[]" type="checkbox" value="' + details.id + '">'+ details.first_name +' ' + details.last_name +'</div></div>';
                    });
                    $('#physiciansInfo').html('');
                    $('#physiciansInfo').html(html);
                    $('#agreement option').remove();
                }
            })
		}
	}
	
	function getContractTypes(hospital_id){
        $.ajax({
            url:'/getContractTypesForPerformanceReport/'+ hospital_id,
            dataType: 'json',
            success:function(response){
                var html = '';
                html = html +'<option value="-1">All</option>';
                $.each(response['contract_types'], function (index, details) {
                    html = html +'<option value="' + index +'">' + details +'</option>';
                });
                $('#contract_type').html('');
                $('#contract_type').html(html);
            }
        })
    }

    Dashboard.selectAll({
        toggle:    "#all",
        label:     "#all-label",
        values:    "#agreement option"
    });
});
</script>