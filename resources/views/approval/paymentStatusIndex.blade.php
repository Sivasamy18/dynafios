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
        padding: 8px 16px !important;
        /* border: 1px solid #ddd; */
        /* width: 160px; */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-family: 'open sans';
        font-size: 14px;
        color: #221f1f;
    }

    th {
        white-space: normal;
        background: #221f1f;
        color: #fff;
        font-family: 'open sans';
        font-size: 14px;
        font-weight: 600;
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
        margin-top: 10px;
    }

    .odd_contract_class {
        background: #dfdfdf !important;
    }

    .even_contract_class {
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

    .pagination > li > a:hover {
        color: #fff !important;
    }

    .approved-text {
        color: #f68a1f;
    }

    .rejected-text {
        color: red;
    }

    .dashboardSummationHeading {
        float: left;
        margin-left: 40px;
        font-size: 15px;
    }

    .dashboardSummationHeading1 {
        float: left;
        margin: -10px 0px 0px 20px;
        font-size: 15px;
    }

    .mb-0 {
        height: 60px !important;
        margin: 0px 0px 0px 0px !important;
    }

    .mb-0 > a {
        display: block;
        position: relative;
        height: 60px;
    }

    #level-one > a {
        width: 100%;
        background: #524a42;
        margin: 0px 0px 0px 0px !important;
        padding: 0px;
        line-height: 80px;
        border: none;
    }

    #level-one > a:before {
        /* content: "\f055"; /* fa-chevron-plus-circle */
        content: "\f068"; /* fa-chevron-minus-circle */
        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        /* color: #000000; */
        color: #f68a1f;
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
        color: #f68a1f;
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
        color: #f68a1f;
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
        color: #f68a1f;
        margin-left: 5px;
        margin: -9px 0px 0px 31px !important;

        line-height: 80px;


    }

    .agreementHeading1 {
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

    .collapse-circle {

        position: absolute;
        left: 9px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        /* color: #000000; */
        color: #f68a1f;
        transition: .5s;
        line-height: 18px;
        /* border: solid 3px #000000; */
        border: solid 3px #f68a1f;
        border-radius: 20px;
        width: 24px;
        height: 24px;
    }

    .collapse-level-two-circle {
        position: absolute;
        left: 25px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        /* color: #000000; */
        color: #f68a1f;
        transition: .5s;
        line-height: 18px;
        /* border: solid 3px #000000; */
        border: solid 3px #f68a1f;
        border-radius: 20px;
        width: 24px;
        height: 24px;

    }

    .level-one-heading {
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
        width: 20px;
        height: 20px;
        border: 1px solid #fff;
    }

    input[type="checkbox"]:checked {
        background: #544d4d;
    }

    .th-level {
        color: #f68a1f;
        text-align: center;
        padding: 7px 0px 7px 0px !important;
        background: #efefef !important;
    }

    .level-two-heading {
        background: #8e8174;
        color: #333;
        padding: 3px 0px 3px 15px !important;
        text-align: center;
    }

    .level-two-physician-heading {
        background: #8e8174;
        color: #333;
        padding: 3px 0px 3px 50px !important;
        text-align: center;
    }

    .input-level-one-checkbox {
        background: #8e8174;
        padding-left: 0px;
        padding-right: 0px;
        border-left: 1px solid #b8b8b8;
        padding-top: 10px;
        border-bottom: 1px solid #efefef;
        height: 61px;
    }

    .approve-image-actionCheckbox {

        border: 2px dotted;;
        line-height: 16px;
        border-bottom: 0px dotted #000;
    }

    .table-arpprove-reject-checkbox {
        background: #524a42;
        padding-left: 0px;
        padding-right: 0px;
        border-left: 1px solid #b8b8b8;
        padding-top: 15px;
        border-bottom: 1px solid #b8b8b8;
    }

    .reject-checkbox {
        margin-right: 0em !important;
        height: 21px;
        width: 14px;
        margin-left: 6px;
    }

    .approve-checkbox {
        margin-right: 0em !important;
        height: 21px !important;
        width: 14px !important;
        margin-left: -15px !important;
        margin-bottom: 9px !important;
    }

    .approve-reject-inputs {
        background: #524a42;
        padding-left: 0px;
        padding-right: 0px;
        border-left: 1px solid #b8b8b8;
        padding-top: 14px;
        padding-bottom: 3px;
        border-bottom: 1px solid #b8b8b8;
    }

    .approve-level-two-value {
        color: #fff;
        font-size: 1.0625em;
        font-weight: normal;
        font-weight: 800;
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

                <div class="form-group  col-xs-12">
                    <label class="col-xs-3 control-label">Status: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        <select class="form-control" id="status">
                            <option value="0">All</option>
                            <option value="1">Pending Physician</option>
                            <option value="2">Approved</option>
                            <option value="3">Rejected</option>
                            <option value="4">Pending (Hospital User)</option>
                        </select>
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

                <div class="form-group  col-xs-12" id="approver" style="display:none">
                    <label class="col-xs-3 control-label">Approver: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('approver', [], Request::old('approver', 0), ['id'=>'approver', 'class' => 'form-control approver']) }}
                    </div>
                </div>
            </div>

            <div class="form-group col-xs-offset-4 col-xs-5"
                 style="margin: 0 auto 50px; float: none; clear:both;">
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
                        <label class="col-xs-12 control-label paddingLeft"
                               style="font-weight: normal; text-align: center;">End Period</label>
                        {{ Form::select('end_dates', $dates['end_dates'], Request::old('end_date',$end_date), [ 'id'=> 'end_date','class' => 'form-control' ]) }}
                    </div>

                </div>
            </div>
        </div>

        <div class="appDashboardTable">
            <div id="table-wrapper"></div>

            <div class="facilityAgreement pane pane--table1">
                <!-- <div class="pane-hScroll"> -->
                @if(count($PaymentStatusLevelOne) > 0)
                    <script>
                        $(document).ready(function () {
                            $(".approvalButtons").show();
                        });
                    </script>
                    @foreach ($PaymentStatusLevelOne as $level_one_obj)
                        <div id="accordion">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <div class="row" style="padding:0px;margin: 0px !important;">
                                            <div class="col-xs-12" style="padding:0px;border-bottom: 1px solid #b8b8b8;"
                                                 id="level-one">
                                                <div class="collapse-circle"></div>
                                                <a onClick="getDataForLevelSecond({{$level_one_obj->physician_id}}, {{$level_one_obj->practice_id}}, '{{$level_one_obj->period_min_date}}', '{{$level_one_obj->period_max_date}}')"
                                                   class="collapsed " role="button" data-toggle="collapse"
                                                   href="#collapse-{{$level_one_obj->physician_id}}-{{$level_one_obj->practice_id}}-{{$level_one_obj->period_min_date}}"
                                                   aria-expanded="true" aria-controls="collapse-level-two"
                                                   style="height: 60px;-webkit-box-shadow: 0 0 0 0 !important;">
                                                    <div style="margin-left:10px">
                                                        <div class="col-xs-3">
                                                            <span class="dashboardSummationHeading1"
                                                                  title="{{$level_one_obj->full_name}}"><b
                                                                        class="level-one-heading">{{$level_one_obj->full_name}}</b></span>
                                                        </div>
                                                        <div class="col-xs-3">
                                                            <span class="dashboardSummationHeading1"
                                                                  title="{{$level_one_obj->practice_name}}"> <b
                                                                        class="level-one-heading">{{$level_one_obj->practice_name}} </b></span>
                                                        </div>
                                                    <!-- <div class="col-xs-2">
                                                <span class="dashboardSummationHeading1"><b style="color: #fff;font-family: 'open sans';font-size:1.0625em;font-weight: normal;">{{$level_one_obj->speciality_name}}</b> <span class="level-one-heading"> {{$level_one_obj->speciality_name}}</span></span>
                                            </div> -->
                                                        <div class="col-xs-3">
                                                            <span class="dashboardSummationHeading1"
                                                                  title="Total Contracts Pending: {{$level_one_obj->level_two_count}}"> <b
                                                                        class="level-one-heading">Total Contracts Pending: {{$level_one_obj->level_two_count}}</b></span>
                                                        </div>
                                                        <div class="col-xs-3">
                                                            <span class="dashboardSummationHeading1"
                                                                  title="Period(s): {{ date('m/d/Y', strtotime($level_one_obj->period_min_date))}} to {{ date('m/d/Y', strtotime($level_one_obj->period_max_date)) }}"><b
                                                                        class="level-one-heading"></b><span
                                                                        class="level-one-heading">{{ date("m/d/Y", strtotime($level_one_obj->period_min_date))}} to {{ date("m/d/Y", strtotime($level_one_obj->period_max_date)) }}</span></span>
                                                        </div>

                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                    </h5>
                                </div>
                                <div id="collapse-{{$level_one_obj->physician_id}}-{{$level_one_obj->practice_id}}-{{$level_one_obj->period_min_date}}"
                                     class="collapse" data-parent="#accordion" aria-labelledby="heading-level-two"
                                     style="margin-left: 15px;height: auto;">
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <center>
                        <span>No data found.</span>
                    </center>
                    <script>
                        $(document).ready(function () {
                            $(".approvalButtons").hide();
                        });
                    </script>
            @endif
            <!-- </div> -->
            </div>

            <div class="text-center approvalButtons" style="display:none">
                <ul>
                    <li><a href="{{ URL::route('approval.columnPreferencesPaymentStatus') }}">Column Display
                            Preferences</a></li>
                    <li>
                        <button class="actionBtn" id="export">Export To Excel</button>
                    </li>
                </ul>
            </div>

        </div>

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
        <input type="submit" id="export_submit" value="">
        <input type="hidden" id="current_timestamp" name="current_timestamp" value=" ">
        <input type="hidden" id="current_zoneName" name="current_zoneName" value=" ">
        <input type="hidden" name="export_approver" value="{{$approver}}">
        {{ Form::close() }}


        <script>
            var changeHospitalEventFired = false;
            $('.pane-hScroll').scroll(function () {
                $('.pane-vScroll').width($('.pane-hScroll').width() + $('.pane-hScroll').scrollLeft());
            });

            // Example 2
            $('.pane--table2').scroll(function () {
                $('.pane--table2 table').width($('.pane--table2').width() + $('.pane--table2').scrollLeft());
            });
            $('#manager_filter').on('change', function () {
                var redirectURL = "?manager_filter=" + this.value;
                window.location.href = redirectURL;
            });
            $('#hospital').on('change', function () {
                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+this.value;
                 window.location.href = redirectURL;*/
                changeHospitalEventFired = true;
                $('#status').val(0);
                $("#approver").hide();
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), 0, 0, 0, 0, 0, '', '', $('#status').val());
            });

            $('#agreement').on('change', function () {
                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+this.value;
                 window.location.href = redirectURL;*/
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), '', '', $('#status').val());
            });
            $('#practice').on('change', function () {
                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+this.value;
                 window.location.href = redirectURL;*/
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), '', '', $('#status').val());
            });
            $('#physician').on('change', function () {
                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+this.value;
                 window.location.href = redirectURL;*/
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), '', '', $('#status').val());
            });
            $('#payment_type').on('change', function () {
                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+this.value;
                 window.location.href = redirectURL;*/
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), '', '', $('#status').val());
            });
            $('#contract_type').on('change', function () {
                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+this.value;
                 window.location.href = redirectURL;*/
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), '', '', $('#status').val());
            });
            $('#start_date').on('change', function () {
                var start = new Date($('#start_date').val());
                var end = new Date($('#end_date').val());
                if (start.getTime() < end.getTime()) {
                    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val() + "&hospital=" + $('#hospital').val() + "&agreement=" + $('#agreement').val() + "&practice=" + $('#practice').val() + "&physician=" + $('#physician').val() + "&contract_type=" + $('#contract_type').val()+"&start_date="+$('#start_date').val()+"&end_date="+$('#end_date').val();
                     window.location.href = redirectURL;*/
                    getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), $('#start_date').val(), $('#end_date').val(), $('#status').val());
                }
            });
            $('#end_date').on('change', function () {
                var start = new Date($('#start_date').val());
                var end = new Date($('#end_date').val());
                if (start.getTime() < end.getTime()) {
                    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val() + "&hospital=" + $('#hospital').val() + "&agreement=" + $('#agreement').val() + "&practice=" + $('#practice').val() + "&physician=" + $('#physician').val() + "&contract_type=" + $('#contract_type').val()+"&start_date="+$('#start_date').val()+"&end_date="+$('#end_date').val();
                     window.location.href = redirectURL;*/
                    getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), $('#start_date').val(), $('#end_date').val(), $('#status').val());
                }
            });
            $('#status').on('change', function () {

                /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+$('#contract_type').val()+"&status="+this.value;
                window.location.href = redirectURL;*/
                // getLogsForApprovalStatusByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),'','',$('#status').val());
                if ($(this).val() == 4) {
                    var html = "";
                    $(".approver").html('');
                    $.ajax({
                        url: '/getPendingApprovers/' + $('#hospital').val(),
                        type: 'get',
                        success: function (response) {
                            $.each(response, function (key, value) {
                                html += '<option value="' + key + '">' + value + '</option>';
                            });
                        },
                        complete: function () {
                            $('#approver').show();
                            $(".approver").append(html);
                            getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), $('#start_date').val(), $('#end_date').val(), $('#status').val(), $("#approver option:first").val());
                        }
                    });

                } else {
                    $('#approver').hide();
                    getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), $('#start_date').val(), $('#end_date').val(), $('#status').val());
                }
            });

            $('#approver').on('change', function () {
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), $('#start_date').val(), $('#end_date').val(), $('#status').val(), $('#approver option:selected').val());
            });

            $(".pagination li a").click(function (ev) {
                ev.preventDefault();
                var link = $(this).attr("href");
                var split_link = link.split("=");
                getLogsForApprovalStatusByAjaxRequest($('#hospital').val(), $('#agreement').val(), $('#practice').val(), $('#physician').val(), $('#payment_type').val(), $('#contract_type').val(), $('#start_date').val(), $('#end_date').val(), $('#status').val());
            });
            $('#export').on('click', function (e) {
                e.preventDefault();
                $("#export_submit").trigger("click");
                $('.overlay').show();
            });

            // function to call log Details In Index page  by ajax request - starts
            function getLogsForApprovalStatusByAjaxRequest(hospital_id, agreement_id, practice_id, physician_id, payment_type_id, contract_type_id, startDate, endDate, status, approver = 0) {
                $('.overlay').show();
                $('#form_replace').html();
                $.ajax({
                    url: '',
                    type: 'get',
                    data: {
                        'hospital': hospital_id,
                        'agreement': agreement_id,
                        'practice': practice_id,
                        'physician': physician_id,
                        'payment_type': payment_type_id,
                        'contract_type': contract_type_id,
                        'start_date': startDate,
                        'end_date': endDate,
                        'status': status,
                        'approver': approver
                    },
                    success: function (response) {

                        if (changeHospitalEventFired) {

                            var $agreement_list = $("#agreement");
                            $agreement_list.empty();

                            $.each(response.data.agreements, function (value, key) {
                                $agreement_list.append($("<option></option>")
                                    .attr("value", value).text(key));
                            });

                            var $practice_list = $("#practice");
                            $practice_list.empty();

                            $.each(response.data.practices, function (value, key) {
                                $practice_list.append($("<option></option>")
                                    .attr("value", value).text(key));
                            });

                            var $physician_list = $("#physician");
                            $physician_list.empty();

                            $.each(response.data.physicians, function (value, key) {
                                $physician_list.append($("<option></option>")
                                    .attr("value", value).text(key));
                            });

                            var $payment_type_list = $("#payment_type");
                            $payment_type_list.empty();

                            $.each(response.data.payment_types, function (value, key) {
                                $payment_type_list.append($("<option></option>")
                                    .attr("value", value).text(key));
                            });

                            var $contract_type_list = $("#contract_type");
                            $contract_type_list.empty();

                            $.each(response.data.contract_types, function (value, key) {
                                $contract_type_list.append($("<option></option>")
                                    .attr("value", value).text(key));
                            });

                            var $start_date_list = $("#start_date");
                            $start_date_list.empty();

                            $.each(response.data.dates.start_dates, function (value, key) {
                                $start_date_list.append($("<option></option>")
                                    .attr("value", value).text(key));
                            });

                            var $end_date_list = $("#end_date");
                            $end_date_list.empty();

                            let selected_end_date = Object.keys(response.data.dates.end_dates)[Object.keys(response.data.dates.end_dates).length - 1];
                            $.each(response.data.dates.end_dates, function (value, key) {
                                if(selected_end_date == value){
                                    $end_date_list.append($("<option></option>")
                                        .attr("selected", "selected").attr("value", value).text(key));
                                }else {
                                    $end_date_list.append($("<option></option>")
                                        .attr("value", value).text(key));
                                }
                            });

                        }

                        changeHospitalEventFired = false;
                        $('.facilityAgreement').html(response.level_one_view);
                    },
                    complete: function () {
                        level_one_arr = [];
                        level_two_arr = [];
                        $('.overlay').hide();
                    }
                });
            }
        </script>
        <script type="text/javascript">
            $(function () {
                $('#status').val({{$status}});
                var report_id = "{{ $report_id }}";

                @isset($report_id)
                Dashboard.downloadUrl("{{ route('approval.report', $report_id) }}");
                @endisset
                $("#export_submit").hide();

                @if($start_date == '')
                $(function () {
                    var start_date_temp = $("#start_date").find("option:first-child").val();
                    var end_date_temp = $("#end_date").find("option:last-child").val();
                    $("input[name='export_start_date']").val(start_date_temp);
                    $("input[name='export_end_date']").val(end_date_temp);
                    $("#start_date").val($("#start_date option:first").val());
                    $("#end_date option:last").attr("selected", "selected");
                });
                @endif
            });

        </script>
        <script>
            $(document).ready(function () {
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

                // $(".form-generate-report").submit(function(){

                var timeZone = formatAMPM(new Date());
                var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if (typeof zoneName === "undefined") {
                    timeZone = '';
                    zoneName = '';
                }
                $("#current_timestamp").val(timeZone);
                $("#current_zoneName").val(zoneName);
                // });

            });


            var level_one_arr = [];
            var level_two_arr = [];

            function getDataForLevelSecond(physician_id, practice_id, period_min_date, period_max_date) {

                var check_combination = physician_id + '-' + practice_id + '-' + period_min_date;

                if (jQuery.inArray(check_combination, level_one_arr) == -1) { //If already not loaded the level then only load it using ajax.
                    $('.overlay').show();
                    // $('#form_replace').html();

                    var hospital_id = $('#hospital').val();
                    var agreement_id = $('#agreement').val();
                    // var practice = $('#practice').val();
                    // var physician = $('#physician').val();
                    var payment_type = $('#payment_type').val();
                    var contract_type = $('#contract_type').val();
                    var status = $('#status').val();
                    var approver = status == 4 ? $('#approver option:selected').val() : 0;

                    $.ajax({
                        url: 'paymentStatusLevelTwo',
                        type: 'get',
                        data: {
                            'physician_id': physician_id,
                            'practice_id': practice_id,
                            'period_min_date': period_min_date,
                            'period_max_date': period_max_date,
                            'status': status,
                            'payment_type': payment_type,
                            'contract_type': contract_type,
                            'hospital_id': hospital_id,
                            'agreement_id': agreement_id,
                            'approver': approver
                        },
                        success: function (response) {
                            // console.log("Response From Approval Index Controller With Hospital,Agreement ID,Practice ID,Physician ID,Contract Type:",response);
                            // var levelTwoId = "#hos-list-" + contract_type_id;
                            var levelTwoId = "#collapse-" + physician_id + '-' + practice_id + '-' + period_min_date;
                            $(levelTwoId).html(response);
                        },
                        complete: function () {
                            level_one_arr.push(check_combination);
                            $('.overlay').hide();
                        }
                    });
                }
            }

            function getDataForLevelThree(contract_id, physician_id, practice_id, period_min_date, period_max_date, payment_type_id) {

                var check_combination = physician_id + '-' + contract_id + '-' + practice_id + '-' + period_min_date;

                if (jQuery.inArray(check_combination, level_two_arr) == -1) { //If already not loaded the level then only load it using ajax.
                    $('.overlay').show();
                    // $('#form_replace').html();
                    var hospital = $('#hospital').val();
                    var agreement = $('#agreement').val();
                    // var practice = $('#practice').val();
                    // var physician = $('#physician').val();
                    var payment_type = $('#payment_type').val();
                    var contract_type = $('#contract_type').val();
                    var status = $('#status').val();
                    var approver = status == 4 ? $('#approver option:selected').val() : 0;
                    $.ajax({
                        url: 'paymentStatusLevelThree',
                        type: 'get',
                        data: {
                            'physician_id': physician_id,
                            'contract_id': contract_id,
                            'practice_id': practice_id,
                            'period_min_date': period_min_date,
                            'period_max_date': period_max_date,
                            'payment_type_id': payment_type_id,
                            'contract_type': contract_type,
                            'hospital': hospital,
                            'agreement': agreement,
                            'status': status,
                            'approver': approver

                        },
                        success: function (response) {
                            // console.log("Response From Approval Index Controller With Hospital,Agreement ID,Practice ID,Physician ID,Contract Type:",response);
                            // var levelTwoId = "#hos-list-" + contract_type_id;
                            var levelTwoId = "#collapse-" + contract_id + '-' + physician_id + '-' + period_min_date;
                            $(levelTwoId).html(response);
                        },
                        complete: function () {
                            level_two_arr.push(check_combination);
                            $('.overlay').hide();
                        }
                    });
                }
            }

        </script>
    </div>
@endsection



