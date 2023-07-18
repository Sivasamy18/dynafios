@extends('layouts/_practice', [ 'tab' => 6])

@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action', 'id'=> 'logForms' ]) }} 
    @if (Session::has('report_id'))
        <div>
            <input type="hidden" id="report_id" name="report_id" value={{ Session::get('report_id') }}>
            <input type="hidden" id="hospital_id" name="hospital_id" value={{ Session::get('hospital_id') }}>
        </div>
    @endif

    <div class="col-xs-12 form-group">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="fa fa-file-text-o"></i>&nbsp;{{ $contract_name }}
                <div>
                    <a class="btn btn-primary" style="margin-top: -27px;"
                       href="{{route('practices.contracts_show',
                                [$practice_id, $contract_id]) }}"
                    >
                        Back
                    </a>
                </div>
            </div>

            <div class="panel-body">
                <div class="col-xs-12 form-group">
                    <div class="col-xs-3 control-label">Physician Name :</div>
                    <div class="col-xs-5 ">
                        <select style="width: 255px;" class="form-control" id="physician_id"
                                name="physician_id">
                            @foreach($physicians as $physician)
                                <option value="{{$physician['id']}}">{{$physician['name']}}</option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-xs-11">
                        <button name="email" class="btn btn-primary btn-submit" id="email" style="margin-top: -35px;"
                                type="submit">
                            Send Approval Reminder Email
                        </button>
                    </div>
                    <div class="col-xs-12" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                        <div id="approvalEmailMessage" class="alert" role="alert">

                        </div>
                    </div>
                    <!-- <div class="col-xs-1"></div>-->
                </div>
                <div class="col-xs-6 no-side-margin-padding" id="logEntry">
                    <!-- Log Entry -->
                    <div class="panel panel-default">
                        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement_id}}>
                        <input type="hidden" id="contract_id_post" name="contract_id_post" value={{$contract_id}}>
                        <input type="hidden" id="contract_name" name="contract_name" value={{$contract_id}}>
                        <input type="hidden" id="selected_dates" name="selected_dates" value="">
                        <input type="hidden" id="log_entry_deadline" name="log_entry_deadline" value="">
                        <input type="hidden" id="physician_id_post" name="physician_id_post"
                               value={{ Session::get('physician_id_post') }}>

                        <div class="panel-heading"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
                            <b> Log Entry </b>
                        </div>

                        <!-- Sprint 6.1.15 Start-->
                        <div class="col-xs-12 no-side-margin-padding" id="timeStudyLogEntry"  style="margin-top:0px;">
                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4 control-label" style="padding-right: 0;">
                                    <h5 style="padding-right: 15px;">Select Date: </h5>
                                </div>
                                <div class="col-xs-8" id="select_date_time_study" name="select_date_time_study"></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4">&nbsp;</div>
                                <div class="col-xs-8 help-block ">
                                    Click to select or deselect dates
                                </div>
                            </div>
                            <div style="border-top: 2px #d6d6d6 solid; margin-top: 290px; margin-bottom: 7px; width: 100%; margin-left: 0%;"></div>
                            <div id="timestudyactions" class="timestudyactions" style="width: 100%; height: 455px; overflow-y: auto; list-style-type:none; padding-left:0px;" class="col-xs-12 form-group">
                                
                            </div>
                            <div class="col-xs-12 form-group">
                                @foreach($physicians as $physician)
                                    <input type="hidden" id="mandate_{{$physician['id']}}"  value="{{$physician['mandate_details']}}" >
                                    <input type="hidden" id="contract_type_{{$physician['id']}}"
                                           value="{{$send_contract_type_id}}">
                                    <input type="hidden" id="payment_type_{{$physician['id']}}"
                                           value="{{$send_payment_type_id}}">
                                @endforeach
                            </div>
                            <div class="col-xs-12 ">
                                <div id="error_log_message" class="col-xs-9" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                                
                                </div>
                                <div class="col-xs-3">
                                    <button name="submit" class="btn btn-primary btn-submit" id="submitLog" type="submit">
                                        Submit
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Sprint 6.1.15 End -->

                        <div class="panel-body onCallLogEntryPanel">
                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4 control-label">Action/Duty:</div>
                                <div class="col-xs-8">
                                    <select class="form-control" id="action" name="action">

                                    </select>
                                    <input class="form-control" type="text" id="custom_action" name="custom_action"
                                           value="" style="display: none;">

                                    {{ Form::label('lbl_action_duty', Request::old('lbl_action_duty'), array('class' => 'form-control', 'id' => 'lbl_action_duty', 'style' => 'text-overflow: ellipsis; white-space: nowrap; overflow: hidden; display:none;')) }}
                                </div>
                            </div>

                            <div class="col-xs-12 form-group" id="divShift">
                                <div class="col-xs-4 control-label">Shift:</div>
                                <div class="col-xs-8">
                                    <label class="radio-inline">
                                        <input type="radio" name="shift" value="AM"> AM
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="shift" value="PM"> PM
                                    </label>
                                </div>
                            </div>

                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4 control-label" style="padding-right: 0;">
                                    <h5 style="padding-right: 15px;">Select Date: </h5>

                                    <ul class="calendarColorList" style="display: none;">
                                        <li style="border-color: #23347E;">
                                            <span>Schedule</span>
                                        </li>
                                        <li style="border-color: #E28E2C;">
                                            <span>Log</span>
                                        </li>
                                        <li style="border-color: #4AAF19;">
                                            <span>Log On Schedule</span>
                                        </li>
                                        <li style="border-color: #FF4040;">
                                            <span>Todays Date</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-xs-8" id="select_date" name="select_date"></div>
                            </div>

                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4">&nbsp;</div>
                                <div class="col-xs-8 help-block ">
                                    Click to select or deselect dates
                                </div>

                            </div>

                            <div class="col-xs-12 form-group co_mgmt_med_direct not_time_stamp" id="div_duration">
                                <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                                <div class="col-xs-8">
                                    <div class="rangeSliderDiv">
                                        <input class="pull-left" id="duration" type="range" min="0.25" max="24" step="0.25"
                                               value="0.25" data-rangeSlider>
                                        <output class="pull-right"></output>
                                    </div>
                                </div>
                            </div>

                            <!-- Time stamp entry -->
                            <div class="col-xs-12 form-group time_stamp">
                                <div class="col-xs-4 control-label">Start:</div>
                                <div id="start_timepicker" class="col-xs-8 input-append">
                                    <input id="start_time" name="start_time" class="form-control input-small" placeholder="Start Time" type="text" data-format="hh:mm" autocomplete="off"
                                        style="width: 75%; float: left;">
                                    <span class="form-control input-group-addon" style="width: 15%;"><i class="glyphicon glyphicon-time"></i></span>
                                </div>
                            </div>
                            <div class="col-xs-12 form-group time_stamp">
                                <div class="col-xs-4 control-label">End:</div>
                                <div id="end_timepicker" class="col-xs-8 input-append">
                                    <input id="end_time" name="end_time" class="form-control input-small" placeholder="End Time" type="text" data-format="hh:mm" autocomplete="off"
                                        style="width: 75%; float: left;">
                                    <span class="form-control input-group-addon" style="width: 15%;"><i class="glyphicon glyphicon-time"></i></span>
                                </div>
                            </div> 

                            <!-- // 6.1.14 Start-->
                            <div class="col-xs-12 form-group per_unit_duration" style="display:block">
                                <div class="col-xs-4 control-label">Number: &nbsp;</div>
                                <div class="col-xs-8">
                                    {{ Form::text('per_unit_duration', Request::old('per_unit_duration'), [ 'id' => 'per_unit_duration', 'class' => 'form-control', 'maxlength' => 3, 'autocomplete' => "off", 'onkeypress' => "perUnitPaymentValidation(event, this)"]) }}
                                </div>
                            </div>
                            <!-- // 6.1.14 End-->

                            <div class="col-xs-12 form-group">
                                @foreach($physicians as $physician)
                                    <input type="hidden" id="mandate_{{$physician['id']}}"  value="{{$physician['mandate_details']}}" >
                                    <input type="hidden" id="contract_type_{{$physician['id']}}"
                                           value="{{$send_contract_type_id}}">
                                    <input type="hidden" id="payment_type_{{$physician['id']}}"
                                           value="{{$send_payment_type_id}}">
                                @endforeach

                                <div class="col-xs-4 control-label">Log details:</div>
                                <div class="col-xs-8"><textarea name="log_details" id="log_details"
                                                                class="form-control disable-resize"></textarea>
                                </div>

                            </div>

                            <div class="col-xs-12 start_end_time_error_message hidden">
                                <div class="col-xs-12" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                                    <div id="start_end_time_message" class="alert" role="alert">
                                    </div>
                                </div>
                            </div>

                            <div class="col-xs-12 ">

                                <div class="col-xs-9" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                                    <div id="enterLogMessage-success" class="alert" role="alert" style="box-shadow: none;">

                                    </div>
                                    <div id="enterLogMessage" class="alert" role="alert" style="box-shadow: none;">

                                    </div>

                                    <!--<div class="bs-callout bs-callout-danger" id="callout-badges-ie8-empty">

                                    </div>-->
                                </div>
                                <div class="col-xs-3">
                                    <button name="submit" class="btn btn-primary btn-submit" id="submitLog"
                                            type="submit">
                                        Submit
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-xs-6 no-side-margin-padding">
                    <div class="panel panel-default" style="margin-left: 5px;">
                        <div class="panel-heading" id="current_days_on_call"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
                            <b>Current {{$payment_frequency_lable}}  Days On Call: </b> <span></span>
                        </div>

                        <div class="panel-heading co_mgmt_med_direct"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
                            <b id="med_direct" class="med_direct">Contract Hours</b>
                            <b class="co_mgmt">Contract {{$payment_frequency_lable}} To Date Totals</b>
                        </div>
                        <div class="panel-body co_mgmt_med_direct">
                            <!-- Recent Logs -->
                            <div class="col-xs-6 med_direct">{{$period_min_lable}} Min: <span id="contract_min_hours"></span></div>
                            <div class="col-xs-6 med_direct">{{$period_max_lable}} Max: <span id="contract_max_hours"></span></div>
                            <div class="col-xs-6 med_direct">Annual Max: <span id="contract_annual_max_hours"></span></div>
                            <div class="col-xs-6 co_mgmt">Expected: <span id="contract_expected_total"></span></div>
                            <div class="col-xs-6 co_mgmt">Worked: <span id="contract_worked_total"></span></div>
                        </div>
                        <div class="panel-heading co_mgmt_med_direct"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%); border-top: 1px solid #d4d4d0;">
                            <b id="summary_of_logged">Summary Of Hours Logged</b>
                        </div>
                        <div class="panel-body co_mgmt_med_direct">
                            <!-- Recent Logs -->
                            <div class="col-xs-12" style="padding-bottom: 9px;font-size: 12px;"><b>CURRENT {{($quarterly_max_hours == 1) ? 'QUARTER': 'PERIOD'}}</b>
                            </div>
                            <div class="col-xs-6 co_mgmt" style="padding-bottom: 9px;">Expected: <span
                                        id="contract_expected"></span></div>
                            <div class="col-xs-4" style="padding-bottom: 9px;">Worked: <span
                                        id="contract_worked_hours"></span></div>
                            <div class="col-xs-6 co_mgmt" style="padding-bottom: 9px;">Remaining: <span
                                        id="contract_remaining_hours"></span></div>
                            <div class="col-xs-8 med_direct" style="padding-bottom: 9px;">Potential Remaining: <span
                                        id="contract_potential_remaining_hours"></span></div>
                            <div class="col-xs-12"
                                 style="padding-top: 9px; padding-bottom:  9px; border-top: 1px solid #eee;font-size: 12px;">
                                <b>PRIOR {{($quarterly_max_hours == 1) ? 'QUARTER': 'PERIOD'}}</b></div>
                            <div class="col-xs-6" style="padding-bottom: 9px;">Worked: <span
                                        id="contract_prior_worked_hours"></span></div>
                        </div>
                        <div class="panel-heading"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%); border-top: 1px solid #d4d4d0;">
                            <b>Recent Logs</b>
                        </div>
                        <div class="panel-body pre-scrollable_log_approve" id="recentLogs">
                            <!-- Recent Logs -->
                            <div class="">Loading...</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    {{ Form::close() }}
{{--    <script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>--}}
    <script type="text/javascript" src="{{ asset('assets/js/practiceLogEntry.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/js/rangeSlider.js') }}"></script>
{{--        <script type="text/javascript" src="{{ asset('assets/js/moment.min.js') }}"></script>--}}
{{--        <link type="text/css" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}"/>--}}
{{--        <script type="text/javascript" src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>--}}
        <script type="text/javascript">
        $(function() {
            $('#start_timepicker').datetimepicker({
                pickDate: false
            });
            $('#end_timepicker').datetimepicker({
                pickDate: false
            });
        });
         function rangeSlide() {
                //printValue('slider1', 'rangeValue1');


                var selector = '[data-rangeSlider]',
                        elements = document.querySelectorAll(selector);

                // Example functionality to demonstrate a value feedback
                function valueOutput(element) {
                    var value = element.value,
                            output = element.parentNode.getElementsByTagName('output')[0];
                    output.innerHTML = value;
                }

                for (var i = elements.length - 1; i >= 0; i--) {
                    valueOutput(elements[i]);
                }

                Array.prototype.slice.call(document.querySelectorAll('input[type="range"]')).forEach(function (el) {
                    el.addEventListener('input', function (e) {
                        valueOutput(e.target);
                    }, false);
                });


                // Basic rangeSlider initialization
                rangeSlider.create(elements, {
                    min: 0.25,
                    max: 24,
                    value: 0.25,
                    borderRadius: 3,
                    buffer: 0,
                    minEventInterval: 1000,

                    // Callback function
                    onInit: function () {
                    },

                    // Callback function
                    onSlideStart: function (value, percent, position) {
                        if (position == null || position == undefined) {
                            position = 0;
                        }
                        console.info('onSlideStart', 'value: ' + value, 'percent: ' + percent, 'position: ' + position);
                    },

                    // Callback function
                    onSlide: function (value, percent, position) {
                        console.log('onSlide', 'value: ' + value, 'percent: ' + percent, 'position: ' + position);
                    },

                    // Callback function
                    onSlideEnd: function (value, percent, position) {
                        console.warn('onSlideEnd', 'value: ' + value, 'percent: ' + percent, 'position: ' + position);
                    }
                });

}
           //call-coverage-duration  by 1254
          /*  function rangeSlide(min_val,max_val) {

                var selector = '[data-rangeSlider]',
                    elements = document.querySelectorAll(selector);

                // Example functionality to demonstrate a value feedback
                 function valueOutput(element) {
                     var value = element.value,
                             output = element.parentNode.getElementsByTagName('output')[0];
                     output.innerHTML = value;
                 }

                for (var i = elements.length - 1; i >= 0; i--) {
                    valueOutput(elements[i]);
                }

                Array.prototype.slice.call(document.querySelectorAll('input[type="range"]')).forEach(function (el) {
                    el.addEventListener('input', function (e) {
                        valueOutput(e.target);
                    }, false);
                });


                // Basic rangeSlider initialization
                rangeSlider.create(elements, {
                    min: min_val,
                    max: max_val,
                    value: elements.value,
                    borderRadius: 3,
                    buffer: 0,
                    minEventInterval: 1000,

                    // Callback function
                    onInit: function () {
                    },

                    // Callback function
                    onSlideStart: function (value, percent, position) {
                        if (position == null || position == undefined) {
                            position = 0;
                        }
                        console.info('onSlideStart', 'value: ' + value, 'percent: ' + percent, 'position: ' + position);
                    },

                    // Callback function
                    onSlide: function (value, percent, position) {
                        console.log('onSlide', 'value: ' + value, 'percent: ' + percent, 'position: ' + position);
                    },

                    // Callback function
                    onSlideEnd: function (value, percent, position) {
                        console.warn('onSlideEnd', 'value: ' + value, 'percent: ' + percent, 'position: ' + position);
                    }
                });
            } */

            function timeStudyValidation(e, data){
                if(e.keyCode == 46){
                    var val = data.value;
                    if(val.indexOf('.') != -1 || val == '') {
                        event.preventDefault();
                    }
                }

                if ((event.keyCode >= 48 && event.keyCode <= 57) || 
                    event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 ||
                    event.keyCode == 39 || event.keyCode == 46 || event.keyCode == 190) {

                } else {
                    event.preventDefault();
                }
            }

            function perUnitPaymentValidation(e, data){
                if ((event.keyCode >= 48 && event.keyCode <= 57) || 
                    event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 ||
                    event.keyCode == 39 || event.keyCode == 190) {

                } else {
                    event.preventDefault();
                }
            }

            $(document).ready(function () {

                $('body').on('focus',"#start_timepicker_edit", function(){
                    $(this).datetimepicker({
                        pickDate: false
                    });
                });

                $('body').on('focus',"#end_timepicker_edit", function(){
                    $(this).datetimepicker({
                        pickDate: false
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
                    //alert(Intl.DateTimeFormat().resolvedOptions().timeZone);
                    //var zoneArray = new Date().toLocaleTimeString('en-us',{timeZoneName:'short'}).split(' ');
                    //alert(new Date().toString().match(/([A-Z]+[\+-][0-9]+)/)[1]);
                    //alert(new Date());
                    /*var zone = '';
                     for(var t=2 ; t< zoneArray.length ; t++){
                     zone = zone+' '+zoneArray[t];
                     }*/
                    var strTime = month+'/'+day+'/'+year+' '+hours + ':' + minutes + ' ' + ampm;
                    return strTime;
                }
                //var dt = new Date();
                //var utcDate = dt.toUTCString();
                //alert(Intl.DateTimeFormat().resolvedOptions().timeZone);
                //alert(formatAMPM(dt));
                //vars declared in onCallSchedule.js
                physicians = {!! json_encode($physicians) !!};
                basePath = "{{ URL::to('/')}}";

                $('#physician_id').change(function () {
                resetPageData();
                combinedCallGetContractsRecentLogs($('#physician_id').val());
            });

                $('#action').change(function () {
                    updateFieldsForAction();

                    var physicianId = $('#physician_id').val();
                    var payment_type_id = $('#payment_type_' + physicianId).val();

                    if(payment_type_id != 3 && payment_type_id != 5 && payment_type_id != 7){
                        getTimeStampEntries();
                    }else{
                        $('.not_time_stamp').show();
                        $('.time_stamp').hide();
                    }
                });

                $('#divShift input:radio[name=shift]').click(function () {
                    if (!$('#divShift input:radio[name=shift]').attr('disabled')) {
                        if ($(this).is(':checked')) {
                            clearLogMessage();
                            /*
                             * check for shift to enable or disable dates
                             */

                            var shift = $('input[name=shift]:checked', '#logForms').val()

                            if (shift == "AM") {
                                $('#select_date').multiDatesPicker('resetDates', 'picked');
                                calendarData.isDisabled = false;
                                calendarFlags.enableAMShifts = false;
                                calendarFlags.enablePMShifts = true;
                                updateCalendar();
                            } else if (shift == "PM") {
                                $('#select_date').multiDatesPicker('resetDates', 'picked');
                                calendarData.isDisabled = false;
                                calendarFlags.enableAMShifts = true;
                                calendarFlags.enablePMShifts = false;
                                updateCalendar();
                            }
                        }
                    }
                });

                $("#select_date").click(function () {
                    if (physicianActions.length > 0) {
                        var selectedAction = $('#action').val();
                        var contractId = $('#contract_name').val();
                        var physicianId = $('#physician_id').val();
                        if ($('#contract_type_' + physicianId).val() == 4) {
                            for (var i = 0; i < physicianActions.length; i++) {
                                if (physicianActions[i].id == selectedAction) {
                                    if (physicianActions[i].duration == 0.5 && !$('#divShift input[name=shift]').is(":checked")) {
                                        $('#enterLogMessage').html("Please choose AM or PM shift.");
                                        $('#enterLogMessage').removeClass("alert-success");
                                        $('#enterLogMessage').addClass("alert-danger");
                                    }
                                }
                            }
                        }
                    }
                });

                $('#email').click(function (event) {
                    $(".overlay").show();
                    $('#approvalEmailMessage').html("");
                    var physician = $('#physician_id').val();
                    $.post("/physicianApprovalEmail",
                    {
                        physician: physician
                    }, function (sent) {
                        if (sent === 'sent') {
                            $(".overlay").hide();
                            $('#approvalEmailMessage').addClass("alert-success");
                            $('#approvalEmailMessage').removeClass("alert-danger");
                            $('#approvalEmailMessage').html("Approval reminder email successfully sent to physician.");
                            setTimeout(function () {
                                    $('#approvalEmailMessage').removeClass("alert-success");
                                }, 4000);
                            setTimeout(function () {
                                    $('#approvalEmailMessage').html("");
                                }, 4000);
                        }
                        if (sent === 'not sent') {
                            $(".overlay").hide();
                            $('#approvalEmailMessage').addClass("alert-danger");
                            $('#approvalEmailMessage').removeClass("alert-success");
                            $('#approvalEmailMessage').html("Approval reminder email not sent to physician. Please contact DYNAFIOS support.");
                            setTimeout(function () {
                                    $('#approvalEmailMessage').removeClass("alert-danger");
                                }, 3000);
                            setTimeout(function () {
                                    $('#approvalEmailMessage').html("");
                                }, 3000);
                        }
                    });

                });
				
				function getOverrideMandateDetails()
                {
					var selected_action_id = $('#action').val();
                    var override_mandate = $('#' + selected_action_id).attr('override_mandate');
                    if(override_mandate == undefined){
                        override_mandate = false;
                    }
					override_mandate_details_flag = JSON.parse(override_mandate);
                }

                function validationTimeStampEntry(){
                    var current_date = new Date();
                    var start_t = Date.parse(current_date.toLocaleDateString() +' '+ $('#start_time').val());
                    var end_t = Date.parse(current_date.toLocaleDateString() +' '+ $('#end_time').val());

                    if($('#start_time').val() == "" && $('#end_time').val() == ""){
                        $('#enterLogMessage').html("Please enter start and end time.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        return false;
                        
                    }else if($('#start_time').val() == ""){
                        $('#enterLogMessage').html("Please enter start time.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        return false;
                    }else if($('#end_time').val() == ""){
                        $('#enterLogMessage').html("Please enter end time.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        return false;
                    }else{
                        if (start_t >= end_t){
                            $('#enterLogMessage').html("Start time should be less than end time.");
                            $('#enterLogMessage').removeClass("alert-success");
                            $('#enterLogMessage').addClass("alert-danger");
                            return false;
                        } else {
                            $('#enterLogMessage').html("");
                            $('#enterLogMessage').removeClass("alert-success");
                            $('#enterLogMessage').removeClass("alert-danger");
                            return true;
                        }
                    }
                }

                function timeobject(time){
                    a = time.replace('AM','').replace('PM','').split(':');
                    h = parseInt(a[0]);
                    m = parseInt(a[1]);
                    ampm = (time.indexOf('AM') !== -1 ) ? 'AM' : 'PM';

                    return {hour:h,minute:m,ampm:ampm};
                }

                $('#logForms').submit(function (event) {
                    var contractId = $('#contract_name').val();
                    var physicianId = $('#physician_id').val();
                    var payment_type_id = $('#payment_type_' + physicianId).val();
                    
                    var durations = 0;
                    time_study_array = [];
                    if(payment_type_id == 7){
                        var dates = $('#select_date_time_study').multiDatesPicker('getDates');
                        
                        if (dates.length > 0) {
                            $('input[name=actions]').each(function() {
                                if($(this).val() != "" && $(this).val() != null && $(this).val() != undefined){
                                    if(parseFloat($(this).val()) > 0){
                                        durations += parseFloat($(this).val());
                                        time_study_array.push({
                                            action_id: $(this).attr('id'),
                                            duration: $(this).val()
                                        });
                                    }
                                }
                            });

                            if(durations == 0){
                                $('#enterLogMessage').html("Please enter hours.");
                                $('#enterLogMessage').removeClass("alert-success");
                                $('#enterLogMessage').addClass("alert-danger");
                                setTimeout(function () {
                                    $('#submitLog').removeClass("disabled");
                                }, 3000);
                                return false;
                            }else{
                                if(durations > 24){
                                    $('#enterLogMessage').html("Total durations should be less than or equal to 24.");
                                    $('#enterLogMessage').removeClass("alert-success");
                                    $('#enterLogMessage').addClass("alert-danger");
                                    setTimeout(function () {
                                        $('#submitLog').removeClass("disabled");
                                    }, 3000);
                                    return false;
                                }else{
                                    // No need
                                }
                            }
                        }else{
                            $('#enterLogMessage').html("Please select date(s) for log.");
                            $('#enterLogMessage').removeClass("alert-success");
                            $('#enterLogMessage').addClass("alert-danger");
                            setTimeout(function () {
                                $('#submitLog').removeClass("disabled");
                            }, 3000);
                            return false;
                        }
                        
                    }else if(payment_type_id == 8){
                        var action = $('#action').val();
                        duration = $('#per_unit_duration').val();
                        if(duration <= 0){
                            $('#enterLogMessage').addClass("alert-danger");
                            $('#enterLogMessage').removeClass("alert-success");
                            if(duration == ""){
                                $('#enterLogMessage').html("Please enter number(s).");
                            }else{
                                $('#enterLogMessage').html("Number should be greater than zero(0).");
                            }
                            return false;
                        }
                        time_study_array.push({
                            action_id: action,
                            duration: duration
                        });
                    }else{
                        var action = $('#action').val();
                        var duration = $('#duration').val();

                        time_study_array.push({
                            action_id: action,
                            duration: duration
                        });
                    }
                    clearLogMessage();
                    var dates = $('#select_date').multiDatesPicker('getDates');
                    selected_date = dates[0];
                    var notes = $('#log_details').val();
                    var notelength = notes.length;
                    // var action = $('#action').val();
                    // var physicianId = $('#physician_id').val();
                    // var contractId = $('#contract_name').val();
                    // var duration = $('#duration').val();
                    var contract_type_id = $('#contract_type_' + physicianId).val();
                    // var payment_type_id = $('#payment_type_' + physicianId).val();
                    var shift = $('input[name=shift]:checked', '#logForms').val();
                    var timeZone = formatAMPM(new Date());
                    var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    var start_time = "";
                    var end_time = "";

                    getOverrideMandateDetails();
                    
                    if(payment_type_id == 1 || payment_type_id == 2 || payment_type_id == 6 || payment_type_id == 9){
                        getTimeStampEntries();
                        if(time_stamp_entry_flag){
                            var check = validationTimeStampEntry();
                            if(!check){
                                return false;
                            }else{
                                start = timeobject($('#start_time').val());
                                end = timeobject($('#end_time').val());
                                end.hour = (end.ampm === 'PM' &&  start.ampm !== 'PM' && end.hour < 12) ? end.hour + 12 : end.hour;
                                if(((end.ampm === 'PM' && start.ampm === 'PM') || (end.ampm === 'AM' && start.ampm === 'AM')) && start.hour == 12){
                                    start.hour = 0;
                                } 
                                if(((end.ampm === 'PM' && start.ampm === 'PM') || (end.ampm === 'AM' && start.ampm === 'AM')) && end.hour == 12){
                                    end.hour = 0;
                                }
                                hours = Math.abs(end.hour - start.hour);
                                minutes = end.minute - start.minute;

                                if(minutes < 0){
                                    minutes = Math.abs(60 + minutes);
                                    hours --;
                                }

                                if(minutes < 10){
                                    minutes = '0' + minutes;
                                }

                                total_minutes = (hours * 60 ) + parseInt(minutes);
                                var duration = (total_minutes / 60);
                                // var duration = hours + '.' + minutes;
                                start_time = $('#start_time').val();
                                end_time = $('#end_time').val();

                                var action = $('#action').val();
                                time_study_array = [];
                                time_study_array.push({
                                    action_id: action,
                                    duration: duration
                                });
                            }
                        }
                    }

					getOverrideMandateDetails();
					
                    if(typeof zoneName === "undefined")
                    {
                        timeZone = '';
                        zoneName ='';
                    }
                    $('#enterLogMessage').html("");

                    if (shift == "AM") {
                        shift = 1;
                    } else if (shift == "PM") {
                        shift = 2;
                    } else {
                        shift = 0;
                    }

                    var log_details_mandate = false;
                    var excess = false;
                    var excess_annual = false;
                    var excess_monthly = false;
                    var action_name = "";
                    var submitURL = 'submitLogForMultipleDates';//'submitLogForOnCall';
                    // if ($('#contract_type_' + physicianId).val() != 4) { // old condition on contract type
                    // if (physicianId != 3 && physicianId != 5) { //new condition on the basis of payment type
                    if(payment_type_id != 3 && payment_type_id != 5) {
                        // submitURL = 'postPracticeSaveLog';
                        submitURL = 'postSaveLog';
                        if (action == -1) {
                            action_name = $('#custom_action').val();
                        }
                        if($('#payment_type_' + physicianId).val() == 7){
                            duration = durations;
                            dates = $('#select_date_time_study').multiDatesPicker('getDates');
                        }
                        if (dates.length > 0) {
                            $.post("/checkDuretion",
                                    {
                                        dates: dates,
                                        contractId: contractId,
                                        physicianId: physicianId,
                                        duration: duration
                                    }, function (dataDuration) {
                                        if (dataDuration === 'Excess annual') {
                                            excess_annual = true;
                                        }
                                        if (dataDuration === 'Excess 24') {
                                            excess = true;
                                        }
                                        if (dataDuration === 'Excess monthly') {
                                            excess_monthly = true;
                                        }
                                    });
                        }else{
                            $('#enterLogMessage').html("Please select date(s) for log.");
                            $('#enterLogMessage').removeClass("alert-success");
                            $('#enterLogMessage').addClass("alert-danger");
                            setTimeout(function () {
                                $('#submitLog').removeClass("disabled");
                            }, 3000);
                            return false;
                        }
                    }

                    if ($('#mandate_' + physicianId).val() == 1 && notelength < 1) {
                        log_details_mandate = true;
                    }
                    if ((isSelectedActionHalfDay && $('#divShift input[name=shift]').is(":checked")) || !isSelectedActionHalfDay) {
                        $('#submitLog').addClass("disabled");
                        if (dates.length > 0) {
                            if (!excess_annual) {
                                if (!excess) {
                                    if (!excess_monthly) {
                                        if (!log_details_mandate || override_mandate_details_flag) {
                                            if (notelength < 255) {
                                                if(time_study_array.length > 0){
                                                    for (var j = 0; j < time_study_array.length; j++){
                                                        action = time_study_array[j]["action_id"];
                                                        duration = time_study_array[j]["duration"];

                                                        $.post("/" + submitURL,
                                                            {
                                                                dates: dates,
                                                                notes: notes,
                                                                action: action,
                                                                contractId: contractId,
                                                                physicianId: physicianId,
                                                                duration: duration,
                                                                contract_type_id: contract_type_id,
                                                                payment_type_id: payment_type_id,
                                                                action_name: action_name,
                                                                shift: shift,
                                                                zoneName: zoneName,
                                                                timeStamp: timeZone,
                                                                current: new Date(),
                                                                start_time: start_time,
                                                                end_time: end_time
                                                            }, function (data) {
                                                                $('#select_date').multiDatesPicker('resetDates', 'picked');
                                                                $('#select_date_time_study').multiDatesPicker('resetDates', 'picked');
                                                                $('#log_details').val("");
                                                                $('#per_unit_duration').val("");
                                                                $('#enterLogMessage').html("");
                                                                if (data === 'Error') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("Contract not exist for date so you can not add log.");
                                                                } else if (data === 'Excess monthly') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    // $('#enterLogMessage').html("You Have Exceeded Monthly Max Hours");
                                                                    if(payment_type_id == 8){
                                                                        $('#enterLogMessage').html("You Have Exceeded Max Units.");
                                                                    }else{
                                                                        $('#enterLogMessage').html("You Have Exceeded Max Hours.");
                                                                    }
                                                                }else if (data === 'Excess 24') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("Cannot submit a log for time in excess of 24 hours in a day.");
                                                                } else if (data === 'Excess annual') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    if(payment_type_id == 8){
                                                                        $('#enterLogMessage').html("You Have Exceeded Annual Max Units.");
                                                                    }else{
                                                                        $('#enterLogMessage').html("You Have Exceeded Annual Max Hours.");
                                                                    }
                                                                } else if (data === 'Error 90_days') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You are not allowed to enter log before "+$('#log_entry_deadline').val()+" days.");
                                                                } else if (data === 'Excess 365') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You are not allowed to enter log before "+$('#log_entry_deadline').val()+" days.");
                                                                } else if (data === 'practice_error') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You are not allowed to enter log for the date before practice start date.");
                                                                } else if(data == "both_actions_empty_error"){
                                                                    $('#enterLogMessage').html("Not Allowed To Submit Log!");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                } else if(data == "Log Exist"){
                                                                    $('#enterLogMessage').html("Log is already exist between start time and end time.");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                } else if(data == "Start And End Time"){
                                                                    $('#enterLogMessage').html("Start time should be less than end time.");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                } else if(data == "Success"){
                                                                    $('#enterLogMessage-success').addClass("alert-success");
                                                                    $('#enterLogMessage-success').html("Log(s) entered successfully.");
                                                                    setTimeout(function() {
                                                                        $('#enterLogMessage-success').html("");
                                                                        $('#enterLogMessage-success').removeClass('alert-success');
                                                                    }, 4000);
                                                                    $('#enterLogMessage').removeClass("alert-danger");
                                                                    //$('#enterLogMessage').addClass("alert-success");
                                                                   // $('#enterLogMessage').html("Log(s) entered successfully.");
                                                                } else if(data != ""){
                                                                    $('#enterLogMessage').html(data);
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                } else {
                                                                    $('#enterLogMessage').html("Something went wrong.");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                }

                                                                combinedCallGetContractsRecentLogs($('#physician_id').val());

                                                                $('#duration').val(0.25);
                                                                $(".rangeSlider__handle").css("transform", "translateX(0px)");
                                                                $(".rangeSlider__fill").css("width", "0");
                                                                var slicedata = data.slice(0,16);
                                                                rangeSlide();
                                                                $('#submitLog').removeClass("disabled");
                                                            });
                                                    }
                                                }else{
                                                    $('#submitLog').removeClass("disabled");
                                                    $('#enterLogMessage').html("Not Allowed To Submit Log!");
                                                    $('#enterLogMessage').removeClass("alert-success");
                                                    $('#enterLogMessage').addClass("alert-danger");
                                                    return false;
                                                }
                                            } else {
                                                $('#enterLogMessage').html("Log details max limit 255 characters.");
                                                $('#enterLogMessage').removeClass("alert-success");
                                                $('#enterLogMessage').addClass("alert-danger");
                                                setTimeout(function () {
                                                    $('#submitLog').removeClass("disabled");
                                                }, 3000);
                                            }
                                        } else {
                                            $('#enterLogMessage').html("Please Enter Log details.");
                                            $('#enterLogMessage').removeClass("alert-success");
                                            $('#enterLogMessage').addClass("alert-danger");
                                            setTimeout(function () {
                                                $('#submitLog').removeClass("disabled");
                                            }, 3000);
                                        }
                                    }else {
                                        $('#duration').val(0);
                                        $('#enterLogMessage').html("You Have Exceeded Annual Max Hours");
                                        $('#enterLogMessage').removeClass("alert-success");
                                        $('#enterLogMessage').addClass("alert-danger");
                                        setTimeout(function () {
                                            $('#submitLog').removeClass("disabled");
                                        }, 3000);
                                    }
                                } else {
                                    $('#duration').val(0);
                                    $('#enterLogMessage').html("Cannot submit a log for time in excess of 24 hours in a day.");
                                    $('#enterLogMessage').removeClass("alert-success");
                                    $('#enterLogMessage').addClass("alert-danger");
                                    setTimeout(function () {
                                        $('#submitLog').removeClass("disabled");
                                    }, 3000);
                                }
                            } else {
                                $('#duration').val(0);
                                $('#enterLogMessage').html("You Have Exceeded Annual Max Hours");
                                $('#enterLogMessage').removeClass("alert-success");
                                $('#enterLogMessage').addClass("alert-danger");
                                setTimeout(function () {
                                    $('#submitLog').removeClass("disabled");
                                }, 3000);
                            }
                        } else {
                            $('#enterLogMessage').html("Please select date(s) for log.");
                            $('#enterLogMessage').removeClass("alert-success");
                            $('#enterLogMessage').addClass("alert-danger");
                            setTimeout(function () {
                                $('#submitLog').removeClass("disabled");
                            }, 3000);
                        }
                    } else {
                        $('#enterLogMessage').html("Please choose AM or PM shift.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                    }
                    event.preventDefault();
                });

                // initMultiDatesPicker();
                // getContracts($('#physician_id').val());
                // getRecentLogs($('#physician_id').val(), $('#contract_name').val());
                combinedCallGetContractsRecentLogs($('#physician_id').val());
                rangeSlide();
                // var physicianId = $('#physician_id').val();
                // var payment_type_id = $('#payment_type_' + physicianId).val();

                // if(payment_type_id != 3 && payment_type_id != 5){
                //     getTimeStampEntries();
                // }else{
                //     $('.co_mgmt_med_direct').removeClass('hidden');
                //     $('.time_stamp').addClass('hidden');
                // }
            });

            $('html').click(function () {
                $(document.body).removeClass('modal-open');
                $(".modal-backdrop").hide();
            });

          $.ajaxSetup({
             headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
          });   


        </script>
@endsection
