<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
	<div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
			<input type="hidden" id="hospital_id" name="hospital_id" value={{$hospital->id}}>
            <div class="form-group">
                <label class="col-xs-3 control-label">Contract Type</label>
                <div class="col-xs-5">
                    {{ Form::select('contract_type', $contract_types, Request::old('contract_type', $contract_type), [ 'class' =>
                    'form-control', 'id' => 'contract_type']) }}
                </div>
            </div>

            <div class="form-group col-xs-12 paddingZero" style="margin-top:10px">
				<label class="col-xs-2 control-label" style="padding-left: 5px !important; padding-right: 5px !important; text-align:center">Start Date</label>
				<div class="col-xs-3 paddingZero">
					<div id="start-date" class="input-group" style="margin-right:20px; margin-left:10px;">
						{{ Form::text('start_date', Request::old('start_date',$start_date), [ 'class' => 'form-control dataFilters datepicker', 'id' => 'start_date' ]) }}
						<span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
					</div>
				</div>
			
				<label class="col-xs-2 control-label" style="padding-left: 5px !important; padding-right: 5px !important; text-align:center;">End Date</label>
				<div class="col-xs-3">
					<div id="end-date" class="input-group">
						{{ Form::text('end_date', Request::old('end_date',$end_date), [ 'class' => 'form-control dataFilters', 'id' => 'end_date' ]) }}
						<span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
					</div>
				</div>
            
				<div class="col-xs-2" style="padding: 0px !important">
					<button type="button" id="apply_filter_date"  name="apply_filter_date"  class="btn btn-success"  style="float: right;">Apply</button>
				</div>
				
			</div>
			
			<div class="form-group col-xs-12 paddingZero" style="text-align:center">
				<span id="date_error_msg" style="display:none; color:red"></span>
			</div>

            <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 75px;">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="form-group">
							<div class="col-xs-7">
								<div class="col-xs-4"><label>Agreement</label></div>
							</div>
							@if (isset($physicians))
								<div class="col-xs-5"><label>
									Physicians
									</label>
								</div>
							@endif
						</div>
						<div id="select_all_div" class="form-group" style="display: none">
							<div class="col-xs-12">
								{{ Form::checkbox('select_all', '', false, ['class' => 'selectAll', 'style' => 'margin-left: 15px;']) }} <label>Select All</label>
							</div>
						</div>
						<div class="agreements" style="border-bottom: 1px solid #ddd; margin-bottom: 20px;">
							<div class="col-xs-7" style="margin-top: 20px;" id="agreements_div">
								@foreach ($agreements as $agreement)
									<div class="form-group">
										<div class="col-xs-12">
											@if($agreement->disable)
												{{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement','disabled'=>'disabled']) }}{{ $agreement->name }}
												{{ Form::select("start_{$agreement->id}_start_month", $agreement->start_dates, $selected_start_date[$agreement->id], ['class' => 'form-control select_dates']) }}
											@else
												{{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement']) }}{{ $agreement->name }}
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
            </div>
      
			<div class="panel-footer clearfix">
				<div class="help-block" style="float:left; margin-top: 8px;">
					Click the agreement(s) for your report or check request before pressing submit.
				</div>
				<button class="btn btn-primary btn-sm btn-submit">Submit</button>
			</div>
		</div>
	</div>
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
                    console.log(" strtime",strTime);
    }
    $(".form-generate-report").submit(function(){
       var timeZone = formatAMPM(new Date());
       var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
       console.log(zoneName);

       if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }
       $("#current_timestamp").val(timeZone);
       $("#current_zoneName").val(zoneName);
	});
});

	$('#apply_filter_date').click(function () {
		$('#select_all_div').hide();
        var start_date = new Date($("#start_date").val());
        var end_date = new Date($("#end_date").val());
        if((start_date !="" ) && (end_date !="")){
			if(start_date < end_date){
				$('#date_error_msg').hide();
				$('#date_error_msg').html("");
				getAgreements();
				$('#select_all_div').show();
			}
			else{
				$('#date_error_msg').show();
				$('#date_error_msg').html("Start date should be less than end date.");
				return false;
			}
        }else
        {
            $('#date_error_msg').show();
            $('#date_error_msg').html("Please enter the start date and end date.");
            return false;  
        }
    });
	
	$(document).on("click", ".agreement", function(event) { 
		var start_date = new Date($("#start_date").val());
        var end_date = new Date($("#end_date").val());
        if((start_date !="" ) && (end_date !="")){
			if(start_date < end_date){
				$('#date_error_msg').hide();
				$('#date_error_msg').html("");
				
				getAgreements();
			}
			else{
				$('#date_error_msg').show();
				$('#date_error_msg').html("Start date should be less than end date.");
				return false;
			}
        }else
        {
            $('#date_error_msg').show();
            $('#date_error_msg').html("Please enter the start date and end date.");
            return false;  
        } 
	});

	$(".selectAll").click(function(){
        if(this.checked){
            $('.agreement').each(function(){
                $(".agreement").prop('checked', true);
            })
			getAgreements();
        }else{
            $('.agreement').each(function(){
                $(".agreement").prop('checked', false);
            })
			$("#physicians").html("");
        }
    });

	function getAgreements() {
		var agreements = [];
		var agreement = 0;
		$(".overlay").show();
			$.each($("input.agreement:checkbox:checked"), function () {
				agreements.push($(this).val());
				agreement = 1;
            });
			$("#physicians").html("");

        $.ajax({
			url:'/getAgreements',
			type:'get',
			data:{start_date: $("#start_date").val(),
				end_date: $("#end_date").val(),
				contract_type: $("#contract_type").val(),
				hospital_id: $("#hospital_id").val(),
				agreement: agreement,
				agreements: agreements
			},
			success:function(response){
				$("#agreements_div").html("");
				var html_agreement = "";
				$.each(response.agreements, function(index, value) {
					var check = true;
					if(agreements.length > 0){
						for(var i = 0; i < agreements.length; i++){
							if(agreements[i] == value.id){
								html_agreement += "<div class='form-group'><div class='col-xs-12'><input class='agreement' name='agreements[]' type='checkbox' value=" + value.id + " checked> " + value.name +" </div></div>";
								check = false
								break;
							}
						}
						
						if(check){
							html_agreement += "<div class='form-group'><div class='col-xs-12'><input class='agreement' name='agreements[]' type='checkbox' value=" + value.id + "> " + value.name +" </div></div>";
						}
					}else{
						html_agreement += "<div class='form-group'><div class='col-xs-12'><input class='agreement' name='agreements[]' type='checkbox' value=" + value.id + "> " + value.name +" </div></div>";
					}
				});
				
				$("#agreements_div").append(html_agreement);
				
				if(agreements.length > 0){
					$("#physicians").html("");
					var html_physician = "";
					$.each(response.physicians, function(key, val) {
						html_physician += "<optgroup label='" + key + "'>";
						$.each(val, function(key, value) {
							html_physician +="<option value=" + key + ">" + value + "</option>";
						})
						html_physician += "</optgroup>";
					});
					$("#physicians").append(html_physician);
				}
			},
			complete:function(){
				$(".overlay").hide();
			}
		});
    }

    
	
</script>
