{{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
<div class="appDashboardFilters">
    <div class="col-md-6">
        <div class="form-group col-xs-12">
            <label class="col-xs-3 control-label">Organization: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('hospital', $hospitals, Request::old('hospital',$hospital), [ 'id'=>'hospital','class' => 'form-control' ]) }}
            </div>
        </div>

        <div class="form-group col-xs-12">
            <label class="col-xs-3 control-label">Practice: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('practice', $practices, Request::old('practice',$practice), ['id'=>'practice', 'class' => 'form-control' ]) }}
            </div>
        </div>

        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Payment Type: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('payment_types', $payment_types, Request::old('payment_type',$payment_type), ['id'=>'payment_type', 'class' => 'form-control' ]) }}
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Agreement: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('agreement', $agreements, Request::old('agreement', $agreement), ['id'=> 'agreement', 'class' => 'form-control' ]) }}
            </div>
        </div>

        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Physician: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('physician', $physicians, Request::old('physician',$physician), [ 'id'=> 'physician','class' => 'form-control' ]) }}
            </div>
        </div>

        <div class="form-group  col-xs-12">
            <label class="col-xs-3 control-label">Contract Type: </label>
            <div class="col-md-9 col-sm-9 col-xs-9">
                {{ Form::select('contract_types', $contract_types, Request::old('contract_type',$contract_type), ['id'=>'contract_type', 'class' => 'form-control' ]) }}
            </div>
        </div>
    </div>

    @if(count($level_one) > 0)
        <div class="form-group col-xs-offset-4 col-xs-5" style="margin: 0 auto 50px; float: none; clear:both;">
            <label class="col-xs-4 control-label" style="margin-top: 35px;">Time Period:</label>
            <div class="col-md-8 col-sm-8 col-xs-8 paddingZero">
                <div class="col-md-6 col-sm-6 col-xs-6 paddingLeft">
                    <!-- <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Month</label> -->
                    <label class="col-xs-12 control-label paddingLeft "
                           style="font-weight: normal; text-align: center;">Start Period</label>
                    {{ Form::select('start_dates', $dates['start_dates'], Request::old('start_date',$start_date), [ 'id'=> 'start_date','class' => 'form-control' ]) }}
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6 paddingRight">
                    <!-- <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Month</label> -->
                    <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End
                        Period</label>
                    {{ Form::select('end_dates', $dates['end_dates'], Request::old('end_date',$end_date), [ 'id'=> 'end_date','class' => 'form-control' ]) }}
                </div>

            </div>
        </div>
    @endif

</div>

<div class="appDashboardTable">
    <div id="table-wrapper"></div>

    <!-- Akash Start -->
    <script>
        var log_ids = {};
    </script>

    @if(count($level_one) > 0)
        <div class="facilityAgreement col-xs-12">
            <!-- <span class="facilityAgreementHeading">List of Contract Specifics</span> -->
            <div class="loaderAgreement" style="margin:auto;"></div>
            <span id="agreementDataByAjax">
            <div class="row" style="padding:0px; {{ (count($level_one) > 0) ? '' : 'display:none' }} ">
                <div class="col-xs-11" style="padding:0px" id="level-one">
                </div>
                <div class="col-xs-1" style="padding:0px" id="level-one">
                    <table style="line-height:10px">
                        <tr class="approve-image-actionCheckbox">
                            <!-- <th style="color:#f68a1f;text-align: center;padding: 7px 0px 7px 5px !important;background: #efefef !important"> -->
                            <th class="th-level">
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-square-fill" viewBox="0 0 16 16">
                                <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm10.03 4.97a.75.75 0 0 1 .011 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.75.75 0 0 1 1.08-.022z"/>
                                </svg> -->
                                {{ HTML::image('assets/img/approved.svg', 'Approve image', array('class' => 'css-class','style'=>'margin-left:15px')) }}
                            </th>
                            <!-- <th style="color:#f68a1f;text-align: center;padding: 7px 0px 7px 0px !important;background: #efefef !important"> -->
                            <th class="th-level">
                                <!-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-square-fill" viewBox="0 0 16 16">
                                <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.354 4.646L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z"/>
                                </svg> -->
                                {{ HTML::image('assets/img/reject.svg', 'Reject image', array('class' => 'css-class','style'=>'margin-left:-3px')) }}
                            </th>
                        </tr>
                    </table>
                </div>
            </div>
            @foreach ($level_one as $level_one_obj)
                    <div id="accordion">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <div class="row" style="padding:0px;">
                                <div class="col-xs-11" style="padding:0px;border-bottom: 1px solid #b8b8b8;"
                                     id="level-one">
                                <div class="collapse-circle"></div>
                                    <a onClick="getDataForContractType({{$level_one_obj->id}})" class="collapsed"
                                       role="button" data-toggle="collapse" href="#collapse-{{$level_one_obj->id}}"
                                       aria-expanded="true" aria-controls="collapse-{{$level_one_obj->id}}"
                                       style="height: 60px;-webkit-box-shadow: 0 0 0 0 !important;">
                                        <div style="margin-left:10px">
                                            <div class="col-xs-2">
                                                <span title="{{ $level_one_obj->name }}"
                                                      class="dashboardSummationHeading1"><b
                                                            class="level-one-heading">{{ $level_one_obj->name }}</b></span>
                                            </div>
                                            <div class="col-xs-3">
                                                <span title="Total Contracts to Approve {{ $level_one_obj->level_two_count }}"
                                                      class="dashboardSummationHeading1"> <b class="level-one-heading">Total Contracts to Approve </b> <span
                                                            class="level-one-heading">{{ $level_one_obj->level_two_count }}</span></span>
                                            </div>
                                            <?php
                                            if ($level_one_obj->period_min == $level_one_obj->period_max) {
                                                $display_date = $level_one_obj->period_min;
                                            } else {
                                                $display_date = $level_one_obj->period_min . " - " . $level_one_obj->period_max;
                                            }
                                            ?>
                                            <div class="col-xs-3">
                                                <span title="Period(s) ( {{ $display_date }})"
                                                      class="dashboardSummationHeading1"><b class="level-one-heading">Period(s)</b> <span
                                                            class="level-one-heading"> ( {{ $display_date }})</span></span>
                                            </div>
                                            <div class="col-xs-3">
                                                <span title="Calculated Payments: {{ ($level_one_obj->calculated_payment != 0.00) ? '$'.$level_one_obj->calculated_payment : 'NA' }}"
                                                      class="dashboardSummationHeading1"><b class="level-one-heading">Calculated Payments: </b><span
                                                            class="level-one-heading">{{ ($level_one_obj->calculated_payment != 0.00) ? '$'.$level_one_obj->calculated_payment : 'NA' }}</span></span>
                                            </div>
                                            
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xs-1 approve-reject-inputs">
                                    <table style="line-height:10px;">
                                        <tr style="background:#524a42;">
                                            <td style="text-align: center;">
                                                <input type="checkbox"
                                                       onChange="selectLogLevelOne({{$level_one_obj->id}})"
                                                       style="margin-right: 0em !important;margin-bottom: 1px;"
                                                       id="level-one-approve-{{$level_one_obj->id}}" {{  ($level_one_obj->flagApprove == true ? 'checked' : 'disabled') }}>
                                            </td>
                                            <td style="text-align: center;">
                                                <input type="checkbox" style="margin: 0em !important;"
                                                       id="level-one-reject-{{$level_one_obj->id}}" disabled>
                                            </td>
                                        </tr>
                                        <tr style="background:#524a42;">
                                            <td style="text-align: center;"></td>
                                            <td style="text-align: center;"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </h5>
                    </div>
                    <div id="collapse-{{$level_one_obj->id}}" class="collapse" data-parent="#accordion"
                         aria-labelledby="heading-{{$level_one_obj->id}}">
                    </div>
                </div>
            </div>
                    <script>
                var tempArr = [];    
            </script>

                    @foreach($level_one_obj->approval_log_ids as $log_id)
                        <script>
                    tempArr.push({{$log_id}});
                </script>

                    @endforeach

                    <script>
                log_ids[{{$level_one_obj->id}}] = tempArr;
            </script>

                @endforeach
        </span>
        </div>
        <!-- Akash End -->

        <div class="text-center approvalButtons">
            <div id="approve_reject_stats"></div>
            <ul>
                <li>
                    <button class="actionBtn" id="export">Export To Excel</button>
                </li>
                <li><a href="{{ URL::route('approval.columnPreferencesApprovalDashboard') }}">Column Display
                        Preferences</a></li>
                <li><input class="submitApproval actionBtn" type="submit" value="Submit for Approval"
                           disabled="disabled"/></li>
            </ul>
        </div>
    @else
        <div class="facilityAgreement col-xs-12">
            <center> No logs for approval.</center>
        </div>
    @endif

</div>
{{ Form::close() }}
{{ Form::open(array('url' => 'approvalStatusReport')) }}
<input type="hidden" id="current_timestamp" name="current_timestamp" value="">
<input type="hidden" id="current_zoneName" name="current_zoneName" value="">
<input type="hidden" name="export_manager_filter" value="{{$manager_filter}}">
<input type="hidden" name="export_payment_type" value="{{$payment_type}}">
<input type="hidden" name="export_contract_type" value="{{$contract_type}}">
<input type="hidden" name="export_hospital" value="{{$hospital}}">
<input type="hidden" name="export_agreement" value="{{$agreement}}">
<input type="hidden" name="export_practice" value="{{$practice}}">
<input type="hidden" name="export_physician" value="{{$physician}}">
<input type="hidden" name="export_start_date" value="{{$start_date}}">
<input type="hidden" name="export_end_date" value="{{$end_date}}">
<input type="hidden" name="export_report_type" value="0">
<input type="submit" id="export_submit" value="">
{{ Form::close() }}
<div id="reasons" style="display: none;">
    {{ Form::select('select_reason', $reasons,0,[ 'class' =>'form-control select_reason' , 'id' => 'select_reason' ]) }}
</div>

<script type="text/javascript" src="{{ asset('assets/js/approvalDashboard.js') }}"></script>
<script type="text/javascript">
    @if($start_date == '')
    $(function () {
        $("#start_date").val($("#start_date option:first").val());
        $("#end_date option:last").attr("selected", "selected");
    });
    @endif

    $(document).ready(function () {
        var timeZone = formatAMPM(new Date());
        var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (typeof zoneName === "undefined") {
            timeZone = '';
            zoneName = '';
        }
        $("#current_timestamp").val(timeZone);
        $("#current_zoneName").val(zoneName);
    });

    function formatAMPM(date) {
        var month = date.getMonth() + 1;
        var day = date.getDate();
        var year = date.getFullYear();
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        month = month > 9 ? month : '0' + month;
        day = day > 9 ? day : '0' + day;
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        minutes = minutes < 10 ? '0' + minutes : minutes;
        var strTime = month + '/' + day + '/' + year + ' ' + hours + ':' + minutes + ' ' + ampm;
        return strTime;
    }

</script>
