@extends('dashboard/_index_landing_page')
<style>
    .landing_page_main .container-fluid .welcomeHeading {
        font-size: 20px;
        font-family: 'open sans';
        font-weight: normal;
    }

    table {
        border-collapse: collapse;
        background: white;
        table-layout: fixed;
        width: 100%;
    }
    th, td {
        padding: 7px 10px !important;
        /* border: 1px solid #ddd; */
        /* width: 160px;  */
        /*overflow: hidden;*/
        text-overflow: ellipsis;
        white-space: nowrap;
        font-family: 'open sans';
        font-size: 14px;
        color: #221f1f;
    }

    th {
        white-space: normal;
        background: #000000;
        color: #fff;
        font-family: 'open sans';
        font-size: 14px;
        font-weight: normal;
        text-align: center;

    }

    table tbody tr {
        background: #eaeaea;
        /* border: solid 1px #b8b8b8; */
    }

    table tbody tr:nth-child(odd) {
        background: #dfdfdf;
    }

    .pane {
        background: #eee;
    }
    .pane-hScroll {
        overflow: auto;
        width: 100%;
        background: transparent;
    }
    .pane-vScroll {
        overflow-y: auto;
        overflow-x: hidden;
        max-height: 560px;
        background: transparent;
    }

    .pane--table2 {
        width: 100%;
        overflow-x: scroll;
    }
    .pane--table2 th, .pane--table2 td {
        width: auto;
        min-width: 160px;
    }
    .pane--table2 tbody {
        overflow-y: scroll;
        overflow-x: hidden;
        display: block;
        height: 200px;
    }
    .pane--table2 thead {
        display: table-row;
    }

    label {
        margin-top: 5px;
    }

    .odd_contract_class
    {
        background: #dfdfdf !important;

    }
    .even_contract_class
    {
        background: #fdfdfd !important;
    }

    .pagination a {
        width: auto !important;
        height: auto !important;
        margin: 0 6px !important;
    }

    .pagination span {
        margin: 0 6px;
        -webkit-box-shadow: -3px 3px 0 0 #c4c4c4;
        -moz-box-shadow: -3px 3px 0 0 #c4c4c4;
        -ms-box-shadow: -3px 3px 0 0 #c4c4c4;
        -o-box-shadow: -3px 3px 0 0 #c4c4c4;
        box-shadow: -3px 3px 0 0 #c4c4c4;
    }
    .pagination>li>a:hover{
        color: #fff !important;
    }

    .approved-text{
        color : #f68a1f;
    }

    .rejected-text{
        color : red;
    }
    .proxy_approver
    {
      color:red;
    }

    .dashboardSummationHeading{
        float:left;
        margin-left:40px;
        font-size: 15px;
    }

    .dashboardSummationHeading1{
        float:left;
        margin: -10px 0px 0px 35px;
        font-size: 15px;
    }

    .mb-0 {
        height: 60px !important;
        margin: 0px 0px 0px 0px !important;
    }
    .mb-0 > a {
        display: block;
        position: relative;
        height:60px;
    }

    #level-one > a {
        width:100%;
        background:#524a42;
        margin: 0px 0px 0px 0px !important;
        padding:0px;
        line-height: 80px;border:none;
    }

    #level-one > a:before {
        /* content: "\f055"; /* fa-chevron-plus-circle */
        content: "\f068"; /* fa-chevron-minus-circle */
        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        /* color: #000000; */
        color:#f68a1f;
        margin-left: 5px;
        margin: -9px 0px 0px 15px !important;

        line-height: 80px;
    }

    #level-one > a.collapsed:before {
        content: "\f067"; /* fa-chevron-plus-circle */
        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        /* color: #000000; */
        color:#f68a1f;
        margin-left: 5px;
        margin: -9px 0px 0px 15px !important;
        line-height: 80px;
    }

    #level-two > a:before {
        content: "\f068" !important; /* fa-chevron-minus-circle */
      /*  font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 30px;
        color: #f68a1f;
        margin-left: 5px;
        margin: -7px 0px 0px 25px !important;
        line-height: 80px;*/


        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        /* color: #000000; */
        color:#f68a1f;
        margin-left: 5px;
        margin: -9px 0px 0px 31px !important;

        line-height: 80px;


    }

    #level-two > a.collapsed:before {
        content: "\f067" !important; /* fa-chevron-plus-circle */
     /*   font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 30px;
        color: #f68a1f;
        margin-left: 5px;
        margin: -7px 0px 0px 25px !important;
        line-height: 80px;*/

        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        /* color: #000000; */
        color:#f68a1f;
        margin-left: 5px;
        margin: -9px 0px 0px 31px !important;

        line-height: 80px;


    }

    .facilityAgreement a .agreementHeading1 {
        float: left;
        /* margin: 0 0.5%; */
        /* margin: 10px 10px; */
        font-size: 1.0625em;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: normal;
        /*color:#333;*/

       /* font-weight: 800;*/
        color: #fff;
    font-family: 'open sans';

    }

    .facilityAgreement a .dashboardSummationHeading1 {
        float: left;
        /* margin: 0 0.5%; */
        /* margin: 10px 10px; */
        font-size: 1.0625em;
        font-weight: 600;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: normal;
    }

    .collapse-circle{

        position: absolute;
    left: 9px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    /* color: #000000; */
    color:#f68a1f;
    transition: .5s;
    line-height: 18px;
    /* border: solid 3px #000000; */
    border:solid 3px #f68a1f;
    border-radius: 20px;
    width: 24px;
    height: 24px;
    }

    .collapse-level-two-circle{
        position: absolute;
    left: 25px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    /* color: #000000; */
    color:#f68a1f;
    transition: .5s;
    line-height: 18px;
    /* border: solid 3px #000000; */
    border:solid 3px #f68a1f;
    border-radius: 20px;
    width: 24px;
    height: 24px;

    }

   .level-one-heading{
        color: #fff;
        font-family: 'open sans';
        font-size: 1.0625em;
        font-weight: normal;
   }

    input[type=checkbox] {
		margin-right: 5px;
		cursor: pointer;
		font-size: 14px;
		width: 15px;
		height: 12px;
		position: relative;
	}

		input[type=checkbox]:after {
			position: absolute;
			width: 10px;
			height: 15px;
			top: 0;
			content: " ";
			background-color: #544d4d;
			color: #fff;
			display: inline-block;
			visibility: visible;
			padding: 0px 3px;
			border-radius: 3px;
		}

		input[type=checkbox]:checked:after {
			content: "✓";
			font-size: 15px;
		}

		input[type=checkbox]:after {
			background-color: #544d4d;
			width:20px;
			height:20px;
            border: 1px solid #fff;
		}
	input[type="checkbox"]:checked {
		background: #544d4d;
	}

 .th-level{
    color:#f68a1f;
    text-align: center;
    padding: 7px 0px 7px 0px !important;
    background: #efefef !important;
 }

 .level-two-heading{
    background:#8e8174;
    color:#333;
    padding: 3px 0px 3px 15px !important;
    text-align:center;
 }

 .level-two-physician-heading{
    background:#8e8174;
    color:#333;
    padding: 3px 0px 3px 50px !important;
    text-align:center;
 }

 .input-level-one-checkbox{
    background:#8e8174;
    padding-left:0px;
    padding-right:0px;
    border-left: 1px solid #b8b8b8;padding-top: 10px;
    border-bottom: 1px solid #efefef;
    height: 61px;
 }

.approve-image-actionCheckbox{

    border:2px dotted;;
    line-height:16px;
    border-bottom: 0px dotted #000;
}

.table-arpprove-reject-checkbox{
    background:#524a42;
    padding-left:0px;
    padding-right:0px;
    border-left: 1px solid #b8b8b8;
    padding-top: 15px;
    border-bottom: 1px solid #b8b8b8;
}

.reject-checkbox{
    margin-right: 0em !important;height: 21px;width: 14px;margin-left: 6px;
}

.approve-checkbox {
    margin-right: 0em !important;
    height: 21px !important;
    width: 14px !important;
    margin-left: -15px !important;
    margin-bottom: 9px !important;
}

.approve-reject-inputs{
    background:#524a42;
    padding-left:0px;
    padding-right:0px;
    border-left: 1px solid #b8b8b8;
    padding-top: 12px;
    padding-bottom: 3px;
    border-bottom: 1px solid #b8b8b8;
}

.approve-level-two-value{
    color:#fff;font-size: 1.0625em;font-weight: normal; font-weight: 800;
}


</style>
@section('links')
    <div id="form_replace" class="approvalDashboard">
        {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
        <div class="appDashboardFilters">
            <div class="col-md-6">
                <div class="form-group col-xs-12">
                    <label class="col-xs-3 control-label">Organization: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('hospital', $hospitals, Request::old('hospital',$hospital), [ 'id'=>'hospital','class' => 'form-control']) }}
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
                        {{ Form::select('contract_types', $contract_types, Request::old('contract_type',$contract_type), ['id'=>'contract_type', 'class' => 'form-control']) }}
                    </div>
                </div>
            </div>

            @if(count($level_one) > 0)
                <div class="form-group col-xs-offset-4 col-xs-5" style="margin: 0 auto 50px; float: none; clear:both;">
                    <label class="col-xs-4 control-label" style="margin-top: 35px;">Time Period:</label>
                    <div class="col-md-8 col-sm-8 col-xs-8 paddingZero">
                        <div class="col-md-6 col-sm-6 col-xs-6 paddingLeft">
                            <!-- <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Month</label> -->
                            <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Period</label>
                            {{ Form::select('start_dates', $dates['start_dates'], Request::old('start_date',$start_date), [ 'id'=> 'start_date','class' => 'form-control' ]) }}
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-6 paddingRight">
                            <!-- <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Month</label> -->
                            <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Period</label>
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
                    
                    <div class="row" style="padding:0px; {{ (count($level_one) > 0) ? '' : 'display:none' }}">
                        <div class="col-xs-11" style="padding:0px" id="level-one">
                        </div>
                        <div class="col-xs-1" style="padding:0px" id="level-one">
                            <table style="line-height:10px">
                                <tr class="approve-image-actionCheckbox">
                                    <!-- <th style="color:#f68a1f;text-align: center;padding: 7px 0px 7px 0px !important;background: #efefef !important"> -->
                                    <th class="th-level">
                                        <!-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-check-square-fill" viewBox="0 0 16 16">
                                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm10.03 4.97a.75.75 0 0 1 .011 1.05l-3.992 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.75.75 0 0 1 1.08-.022z"/>
                                        </svg> -->
                                        <!-- <img src="{{URL::asset('/img/approve.svg')}}" alt="Approve" height="50" width="50"> -->
                                        {{ HTML::image('assets/img/approved.svg', 'Approve image', array('class' => 'css-class', 'style'=>'margin-left:15px')) }}
                                    </th>
                                    <!-- <th style="color:#f68a1f;text-align: center;padding: 7px 0px 7px 0px !important;background: #efefef !important"> -->
                                    <th class="th-level">
                                        <!-- <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-square-fill" viewBox="0 0 16 16">
                                        <path d="M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2zm3.354 4.646L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 1 1 .708-.708z"/>
                                        </svg> -->
                                        {{ HTML::image('assets/img/reject.svg', 'Reject image', array('class' => 'css-class', 'style'=>'margin-left:-3px')) }}
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
                                        <div class="col-xs-11" style="padding:0px;border-bottom: 1px solid #b8b8b8;" id="level-one">
                                        <div class="collapse-circle"></div>
                                            <a onClick="getDataForContractType({{$level_one_obj->id}})" class="collapsed " role="button" data-toggle="collapse" href="#collapse-{{$level_one_obj->id}}" aria-expanded="true" aria-controls="collapse-{{$level_one_obj->id}}"  style="height: 60px;-webkit-box-shadow: 0 0 0 0 !important;">
                                                <div style="margin-left:10px">
                                                    <div class="col-xs-2">
                                                        <span title="{{ $level_one_obj->name }}" class="dashboardSummationHeading1"><b class="level-one-heading">{{ $level_one_obj->name }}</b></span>
                                                    </div>
                                                    <div class="col-xs-3">
                                                        <span title="Total Contracts to Approve {{ $level_one_obj->level_two_count }}" class="dashboardSummationHeading1"> <b class="level-one-heading"">Total Contracts to Approve </b> <span style="color: #fff;font-family: 'open sans';font-weight: 600;">{{ $level_one_obj->level_two_count }}</span></span>
                                                    </div>
                                                    <?php
                                                    if( $level_one_obj->period_min == $level_one_obj->period_max ){
                                                        $display_date = $level_one_obj->period_min;
                                                    }else {
                                                        $display_date = $level_one_obj->period_min . " - " . $level_one_obj->period_max;
                                                    }
                                                    ?>
                                                    <div class="col-xs-3">
                                                        <span title="Period(s) ( {{ $display_date }})" class="dashboardSummationHeading1"><b style="color: #fff;font-family: 'open sans';font-size:1.0625em;font-weight: normal;">Period(s)</b> <span class="level-one-heading"> ( {{ $display_date }})</span></span>
                                                    </div>
                                                    <div class="col-xs-3">
                                                        <!-- <span title="Calculated Payments: {{ ($level_one_obj->calculated_payment != 0.00) ? '$'.$level_one_obj->calculated_payment : 'NA' }}" class="dashboardSummationHeading1"><b class="level-one-heading">Calculated Payments: </b><span class="level-one-heading">{{ ($level_one_obj->calculated_payment != 0.00) ? "$".$level_one_obj->calculated_payment : 'NA' }}</span></span> -->
                                                        <span title="Calculated Payments: {{ $level_one_obj->calculated_payment }}" class="dashboardSummationHeading1"><b class="level-one-heading">Calculated Payments: </b><span class="level-one-heading">{{ $level_one_obj->calculated_payment }}</span></span>
                                                    </div>

                                                </div>
                                            </a>
                                        </div>
                                        <div class="col-xs-1 approve-reject-inputs" >
                                            <table style="line-height:10px;">
                                                <tr style="background:#524a42;">
                                                    <td style="text-align: center;">
                                                        <input type="checkbox" onChange="selectLogLevelOne({{$level_one_obj->id}})" style="margin-right: 0em !important; margin-bottom: 1px;" id="level-one-approve-{{$level_one_obj->id}}" {{  ($level_one_obj->flagApprove == true ? 'checked' : 'disabled') }}>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <input type="checkbox" style="margin: 0em !important;" id="level-one-reject-{{$level_one_obj->id}}" disabled>
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
                            <div id="collapse-{{$level_one_obj->id}}" class="collapse" data-parent="#accordion" aria-labelledby="heading-{{$level_one_obj->id}}">
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
                    <li><button class="actionBtn" id="export">Export To Excel</button></li>
                    <li><a href="{{ URL::route('approval.columnPreferencesApprovalDashboard') }}">Column Display Preferences</a></li>
                    <li><input class="submitApproval actionBtn" type="submit" value="Submit for Approval" disabled="disabled" /></li>
                </ul>
            </div>

            @else
                <div class="facilityAgreement col-xs-12">
                    <center> No logs for approval. </center>
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

        <!-- Modal -->
<div id="approvalModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Confirm Submission.</h4>
      </div>
      <div class="modal-body">
        <p>{{$assertation_popup_text}}</p>
      </div>
      <div class="modal-footer">
        <button id="assert_submit_accept" type="button" class="btn btn-default" data-dismiss="modal">Accept</button>
        <button id="assert_submit_cancel" type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>

      </div>
    </div>

  </div>
</div>

        <script type="text/javascript" src="{{ asset('assets/js/approvalDashboard.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/js/numeral.js') }}"></script>
        <script type="text/javascript">
            $(function () {
                var report_id = "{{ $report_id }}";
                @isset($report_id)
                    Dashboard.downloadUrl("{{ route('approval.report', $report_id) }}");
                @endisset
                $("#export_submit").hide();
            });
            $(function () {
                @if($start_date == '')
                $("#start_date").val($("#start_date option:first").val());
                $("#end_date option:last").attr("selected", "selected");
                @endif
                getAllLogsForApproval();
            });


            
    $( document ).ready(function() {
        var forms = [];

        var timeZone = formatAMPM(new Date());
        var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if(typeof zoneName === "undefined")
        {
            timeZone = '';
            zoneName ='';
        }
        $("#current_timestamp").val(timeZone);
        $("#current_zoneName").val(zoneName);

        var apptext="{{$assertation_popup_text}}";
        
        if(Boolean(apptext)){
            $(".submitApproval").click(function(e){
                e.preventDefault();

                $('#approvalModal').modal({
                    show: 'false'
                }); 

            });
        }

        $("#assert_submit_accept").click(function(){
            var forms = [];
            $("form").each(function() {
                if($(this).attr('action').indexOf("getLogsForApproval")!== -1){
                    $(this).trigger('submit', true);
                }
            });
        });
    });

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

        </script>
    </div>
@endsection