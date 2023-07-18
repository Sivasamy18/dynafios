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
                    <!--div class="col-xs-11">
                        <button name="email" class="btn btn-primary btn-submit" id="email" style="margin-top: -35px;"
                                type="submit">
                            Send Approval Reminder Email
                        </button>
                    </div-->
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
                        <input type="hidden" id="current_period" name="current_period" value={{$current_month}}>
                        <input type="hidden" id="enter_by_day" name="enter_by_day" value="">
                        <input type="hidden" id="log_entry_deadline" name="log_entry_deadline" value="">
                        <input type="hidden" id="physician_id_post" name="physician_id_post"
                               value={{ Session::get('physician_id_post') }}>
                        <div class="panel-heading"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
                            <b> wRVU Entry </b>
                        </div>
                        <div class="panel-body onCallLogEntryPanel">
                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4 control-label">Activity/Duty:</div>
                                <div class="col-xs-8">
                                    <select class="form-control" id="action" name="action">

                                    </select>
                                    <input class="form-control" type="text" id="custom_action" name="custom_action"
                                           value="" style="display: none;">
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

                                <div id="divEnterByDay">

                                    <div class="col-xs-12 form-group">
                                        <div class="col-xs-4 control-label" style="padding-right: 0;">
                                            <h5 style="padding-right: 15px;">Select Date: </h5>

                                            <ul class="calendarColorList" style="display: none;">
                                                <li style="border-color: #23347E;">
                                                    <span>Schedule</span>
                                                </li>
                                                <li style="border-color: #E28E2C;">
                                                    <span>wRVU</span>
                                                </li>
                                                <li style="border-color: #4AAF19;">
                                                    <span>wRVU On Schedule</span>
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

                                </div>

                            <div class="col-xs-12 form-group" id="divEnterByMonth">
                                <div class="col-xs-4 control-label">Period:</div>
                                <div class="col-xs-8">
                                    {{ Form::select('period', $dates->dates, Request::old('period',
                                    $current_month), ['class' => 'form-control','id' => 'period' ]) }}
                                </div>
                            </div>

                            <!--div class="col-xs-12 form-group psa_wrvu">
                                <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                                <div class="col-xs-8">
                                    <div class="rangeSliderDiv">
                                        <input class="pull-left" id="duration" type="range" min="0.25" max="12" step="0.25"
                                               value="0.25" data-rangeSlider>
                                        <output class="pull-right"></output>
                                    </div>
                                </div>

                            </div-->

                            <div class="col-xs-12 form-group psa_wrvu">
                                <div class="col-xs-4 control-label">Units:</div>
                                <div class="col-xs-8">
                                    <input class="form-control" type="text" id="duration"
                                           value="">
                                </div>

                            </div>

                            <div class="col-xs-12 form-group">
                                @foreach($physicians as $physician)
                                    <input type="hidden" id="mandate_{{$physician['contract']}}"  value="{{$physician['mandate_details']}}" >
                                    <input type="hidden" id="contract_type_{{$physician['contract']}}"
                                           value="{{$send_contract_type_id}}">
                                    <input type="hidden" id="payment_type_{{$physician['contract']}}"
                                           value="{{$send_payment_type_id}}">
                                @endforeach

                                <div class="col-xs-4 control-label">wRVU details:</div>
                                <div class="col-xs-8"><textarea name="log_details" id="log_details"
                                                                class="form-control disable-resize"></textarea>
                                </div>

                            </div>

                            <div class="col-xs-12 ">
                                <div class="col-xs-9" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                                    <div id="enterLogMessage" class="alert" role="alert">

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

                        <div class="panel-heading"
                             style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%); border-top: 1px solid #d4d4d0;">
                            <b>Recent wRVUs</b>
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

    <div class="col-xs-12 form-group panel-approve-logs" id="approveLogs">
        <!-- Approve Logs -->
    </div>

    {{ Form::close() }}
    <script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/practicePsaWrvuLogEntry.js') }}"></script>
        <script type="text/javascript">
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
                physicians = {!! json_encode($physicians)!!};
                basePath = "{{ URL::to('/')}}";

                $('#physician_id').change(function () {
                resetPageData();
                getContracts($('#physician_id').val());
            });

                $('#action').change(function () {
                    updateFieldsForAction();
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

                $('#period').change(function () {
                    updateFieldsForPeriod();
                });

                $('#logForms').submit(function (event) {
                    clearLogMessage();
                    if($('#enter_by_day').val()=='true') {
                        var dates = $('#select_date').multiDatesPicker('getDates');
                    }
                    else {
                        var lastSelected = $( "#period option:selected" ).text();
                        var dates =[];
                        dates.push(lastSelected.split(" ")[3]);
                    }
                    var notes = $('#log_details').val();
                    var notelength = notes.length;
                    var action = $('#action').val();
                    var physicianId = $('#physician_id').val();
                    var contractId = $('#contract_name').val();
                    var duration = $('#duration').val();
                    var contract_type_id = $('#contract_type_' + contractId).val();
                    var payment_type_id = $('#payment_type_' + contractId).val();
                    var shift = $('input[name=shift]:checked', '#logForms').val();
                    var timeZone = formatAMPM(new Date());
                    var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
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
                    if ($('#payment_type_' + contractId).val() != 3) {
                        // submitURL = 'postPracticeSaveLog';
                        submitURL = 'postSaveLog';
                        if (action == -1) {
                            action_name = $('#custom_action').val();
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
                                        if (dataDuration === 'Excess monthly') {
                                            excess_monthly = true;
                                        }
                                    });
                        }
                    }

                    if ($('#mandate_' + contractId).val() == 1 && notelength < 1) {
                        log_details_mandate = true;
                    }
                    if ((isSelectedActionHalfDay && $('#divShift input[name=shift]').is(":checked")) || !isSelectedActionHalfDay) {
                        $('#submitLog').addClass("disabled");
                        if (dates.length > 0) {
                            if (!excess_annual) {
                                if (!excess) {
                                    if (!excess_monthly) {
                                        if (!log_details_mandate) {
                                            if (notelength < 255) {
                                                if (!isNaN(parseFloat(duration)) && duration >= 0.25) {
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
                                                                current: new Date()
                                                            }, function (data) {
                                                                $('#select_date').multiDatesPicker('resetDates', 'picked');
                                                                getContracts($('#physician_id').val());
                                                                $('#log_details').val("");
                                                                if (data === 'Error') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("Contract not exist for date so you can not add log.");
                                                                } else if (data === 'Excess monthly') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You Have Exceeded Annual Max Hours");
                                                                }else if (data === 'Excess 24') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("Cannot submit a wRVU for time in excess of 24 hours in a day.");
                                                                } else if (data === 'Excess annual') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You Have Exceeded Annual Max Hours");
                                                                } else if (data === 'Error 90_days') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You are not allowed to enter wRVU before "+$('#log_entry_deadline').val()+" days.");
                                                                } else if (data === 'Excess 365') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You are not allowed to enter wRVU before "+$('#log_entry_deadline').val()+" days.");
                                                                } else if (data === 'practice_error') {
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').html("You are not allowed to enter wRVU for the date before practice start date.");
                                                                } else if(data == "both_actions_empty_error"){
                                                                    $('#enterLogMessage').html("Not Allowed To Submit Log!");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                } else if(data == "no_duration"){
                                                                    $('#enterLogMessage').html("wRVU not saved! Please enter a valid wRVU count.");
                                                                    $('#enterLogMessage').removeClass("alert-success");
                                                                    $('#enterLogMessage').addClass("alert-danger");
                                                                } else{
                                                                    $('#enterLogMessage').removeClass("alert-danger");
                                                                    $('#enterLogMessage').addClass("alert-success");
                                                                    $('#enterLogMessage').html("Log(s) entered successfully.");
                                                                }
                                                                $('#duration').val("");
                                                                setTimeout(function () {
                                                                    $('#submitLog').removeClass("disabled");
                                                                }, 3000);
                                                                setTimeout(function () {
                                                                    $(".overlay").show();
                                                                }, 4500);
                                                                setTimeout(function () {
                                                                    initMultiDatesPicker();
                                                                }, 5000);
                                                                setTimeout(function () {
                                                                    getContracts(physicianId);
                                                                }, 5500);
                                                            });
                                                    } else {
                                                    $('#enterLogMessage').html("wRVU not saved! Please enter a valid wRVU count.");
                                                    $('#enterLogMessage').removeClass("alert-success");
                                                    $('#enterLogMessage').addClass("alert-danger");
                                                    setTimeout(function () {
                                                        $('#submitLog').removeClass("disabled");
                                                    }, 3000);
                                                    }
                                                } else {
                                                $('#enterLogMessage').html("wRVU details max limit 255 characters.");
                                                $('#enterLogMessage').removeClass("alert-success");
                                                $('#enterLogMessage').addClass("alert-danger");
                                                setTimeout(function () {
                                                    $('#submitLog').removeClass("disabled");
                                                }, 3000);
                                            }
                                        } else {
                                            $('#enterLogMessage').html("Please Enter wRVU details.");
                                            $('#enterLogMessage').removeClass("alert-success");
                                            $('#enterLogMessage').addClass("alert-danger");
                                            setTimeout(function () {
                                                $('#submitLog').removeClass("disabled");
                                            }, 3000);
                                        }
                                    }else {
                                        $('#duration').val("");
                                        $('#enterLogMessage').html("You Have Exceeded Annual Max Hours");
                                        $('#enterLogMessage').removeClass("alert-success");
                                        $('#enterLogMessage').addClass("alert-danger");
                                        setTimeout(function () {
                                            $('#submitLog').removeClass("disabled");
                                        }, 3000);
                                    }
                                } else {
                                    $('#duration').val("");
                                    $('#enterLogMessage').html("Cannot submit a wRVU for time in excess of 24 hours in a day.");
                                    $('#enterLogMessage').removeClass("alert-success");
                                    $('#enterLogMessage').addClass("alert-danger");
                                    setTimeout(function () {
                                        $('#submitLog').removeClass("disabled");
                                    }, 3000);
                                }
                            } else {
                                $('#duration').val("");
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

                //initMultiDatesPicker();
                getContracts($('#physician_id').val());
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