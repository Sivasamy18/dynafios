            @foreach ($PaymentStatusLevelOne as $level_one_obj)
            <div id="accordion">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <div class="row" style="padding:0px;margin: 0px !important;">
                                <div class="col-xs-12" style="padding:0px;border-bottom: 1px solid #b8b8b8;" id="level-one">
                                <div class="collapse-circle"></div>
                                    <a onClick="getDataForLevelSecond({{$level_one_obj->physician_id}}, {{$level_one_obj->practice_id}}, '{{$level_one_obj->period_min_date}}', '{{$level_one_obj->period_max_date}}')" class="collapsed " role="button" data-toggle="collapse" href="#collapse-{{$level_one_obj->physician_id}}-{{$level_one_obj->practice_id}}-{{$level_one_obj->period_min_date}}" aria-expanded="true" aria-controls="collapse-level-two"  style="height: 60px;-webkit-box-shadow: 0 0 0 0 !important;">
                                        <div style="margin-left:10px">
                                            <div class="col-xs-3">
                                                <span class="dashboardSummationHeading1" title="{{$level_one_obj->full_name}}"><b class="level-one-heading">{{$level_one_obj->full_name}}</b></span>
                                            </div>
                                            <div class="col-xs-3">
                                                <span class="dashboardSummationHeading1" title="{{$level_one_obj->practice_name}}"> <b class="level-one-heading">{{$level_one_obj->practice_name}} </b></span>
                                            </div>
                                            <!-- <div class="col-xs-2">
                                                <span class="dashboardSummationHeading1"><b style="color: #fff;font-family: 'open sans';font-size:1.0625em;font-weight: normal;">{{$level_one_obj->speciality_name}}</b> <span class="level-one-heading"> {{$level_one_obj->speciality_name}}</span></span>
                                            </div> -->
                                            <div class="col-xs-3">
                                            <span class="dashboardSummationHeading1" title="Total Contracts Pending: {{$level_one_obj->level_two_count}}"> <b class="level-one-heading">Total Contracts Pending: {{$level_one_obj->level_two_count}}</b></span>
                                            </div>
                                            <div class="col-xs-3">
                                            <span class="dashboardSummationHeading1" title="Period(s): {{ date('m/d/Y', strtotime($level_one_obj->period_min_date))}} to {{ date('m/d/Y', strtotime($level_one_obj->period_max_date)) }}"><b class="level-one-heading"></b><span class="level-one-heading">{{ date("m/d/Y", strtotime($level_one_obj->period_min_date))}} to {{ date("m/d/Y", strtotime($level_one_obj->period_max_date)) }}</span></span>
                                            </div>

                                        </div>
                                    </a>
                                </div>
                            </div>
                        </h5>
                    </div>
                    <div id="collapse-{{$level_one_obj->physician_id}}-{{$level_one_obj->practice_id}}-{{$level_one_obj->period_min_date}}" class="collapse" data-parent="#accordion" aria-labelledby="heading-level-two" style="margin-left: 15px;height: auto;">
                    </div>
                </div>
            </div>
            @endforeach
			
			@if(count($PaymentStatusLevelOne) > 0)
				<script>
					$(document).ready(function () {
						$(".approvalButtons").show();
					});
				</script>
			@else
				<script>
					$(document).ready(function () {
						$(".approvalButtons").hide();
					});
				</script>
			@endif

    {{ Form::close() }}
    {{ Form::open(array('url' => 'paymentStatusReport')) }}
    <input type="hidden" name="export_manager_filter" value="{{$manager_filter}}">
    <input type="hidden" name="export_payment_type" value="{{$payment_type}}">
    <input type="hidden" name="export_contract_type" value="{{$contract_type}}">
    <input type="hidden" name="export_hospital" value="{{$hospital}}">
    <input type="hidden" name="export_agreement" value="{{$agreement}}">
    <input type="hidden" name="export_practice" value="{{$practice}}">
    <input type="hidden" name="export_physician" value="{{$physician}}">
    <input type="hidden" name="export_start_date" value="{{$start_date}}">
    <input type="hidden" name="export_end_date" value="{{$end_date}}">
    <input type="hidden" name="export_report_type" value="1">
    <input type="hidden" name="export_status" value="{{$status}}">
    <input type="submit" id="export_submit" value="" hidden>
    <input type="hidden" name="export_approver" value="{{$approver}}">
    <input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
    <input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
    {{ Form::close() }}

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
    </script>
