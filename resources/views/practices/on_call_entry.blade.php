@extends('layouts/_practice', [ 'tab' => 6])

@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action', 'id'=> 'logForm' ]) }}
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
                        On Call Schedule
                    </a>
                </div>
                <div>
                    <a class="btn btn-primary" style="margin-top: -27px; margin-right: 10px; "
                       href="{{route('practices.show_rejected_logs',
                                [$practice_id, $contract_id, count($physicians) > 0 ? $physicians[0]['id'] : 0]) }}"
                    >
                        Rejected Logs
                    </a>
                </div>
            </div>

            <div class="panel-body">
                <div class="col-xs-12 form-group">
                    <div class="col-xs-3 control-label">Physician Name :</div>
                    <div class="col-xs-5 ">
                        <select style="width: 255px;" class="form-control" id="physician_name"
                                name="physician_name">
                            @foreach($physicians as $physician)
                                <option value="{{$physician['id']}}">{{$physician['name']}}</option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-xs-11">
                        <a name="email" class="btn btn-primary btn-submit" id="email" style="margin-top: -35px;"
                                >
                            Send Approval Reminder Email
                        </a>
                    </div>
                    <div class="col-xs-12" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                        <div id="approvalEmailMessage" class="alert" role="alert">

                        </div>
                    </div>
                    <!-- <div class="col-xs-1"></div>-->
                </div>

                <!-- issue fixed : added new error div log delete on approve log for burden_on_call true by 1254 -->
                <div class="col-xs-12" style="padding-top: 10px;">
                    <div id="log-error-delete-message" class="alert" role="alert" style="display: none;">

                    </div>
                </div> <!-- end issue fixed : added new error div log delete on approve log for burden_on_call true by 1254 -->

                <div class="col-xs-6 no-side-margin-padding" id="logEntry">
                    <!-- Log Entry -->
                    <div class="panel panel-default">
                        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement_id}}>
                        <input type="hidden" id="contract_id" name="contract_id" value={{$contract_id}}>
                        <input type="hidden" id="selected_dates" name="selected_dates" value="">
                        <input type="hidden" id="log_entry_deadline" name="log_entry_deadline" value="">
                        <input type="hidden" id="physician_id_post" name="physician_id_post"
                               value={{ Session::get('physician_id_post') }}>
<!-- //call-coverage-duration  by 1254 :added hidden field partial hours -->
                        <input type="hidden" id="payment_type_{{$contract['id']}}"
                               value="{{$contract['payment_type_id']}}" name='payment_type'>

                        <input type="hidden" id="partial_hours_{{$contract['id']}}"
                               value="{{$contract['partial_hours']}}"  name='partial_hours_'>
                        <input type="hidden" id="partial_hours_calculation_{{$contract['id']}}"
                        value="{{$contract['partial_hours_calculation']}}"  name='partial_hours_calculation_'>
                        <input type="hidden" id="contract_id_"
                               value="{{$contract['id']}}"  name='contract_id_'>
                        <div class="panel-heading">
                            Log Entry
                        </div>
                        <div class="panel-body onCallLogEntryPanel">
                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4 control-label">Action/Duty :</div>
                                <div class="col-xs-8">
                                    <select class="form-control" id="action" name="action">

                                    </select>
                                </div>
                            </div>

                            <div class="col-xs-12 form-group" id="divShift">
                                <div class="col-xs-4 control-label">Shift :</div>
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
                                <div class="col-xs-4 control-label">Select Date:</div>
                                <div class="col-xs-8" id="select_date" name="select_date"></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <div class="col-xs-4">&nbsp;</div>
                                <div class="col-xs-8 help-block ">
                                    Select All (Click to select or deselect items)
                                </div>

                            </div>
                            <!-- call-coverage by 1254 : added duration slider -->

                            <div class="col-xs-12 form-group co_mgmt_med_direct" >
                                <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                                <div class="col-xs-8">
                                    <div class="rangeSliderDiv">
                                        <input class="pull-left" id="duration" type="range" min="0.25" max="12" step="0.25"
                                               value="0.25" data-rangeSlider>
                                        <output class="pull-right"></output>
                                    </div>
                                </div>

                            </div>

                            <div class="col-xs-12 form-group">
                                @foreach($physicians as $physician)
                                    <input type="hidden" id="mandate_{{$physician['id']}}"  value="{{$physician['mandate_details']}}" >
                                    <!-- physicians log the hours for holiday activity on any day -->
                                    <input type="hidden" id="holiday_on_off"  value="{{$physician['holiday_on_off']}}" >
                                @endforeach
                                <div class="col-xs-4 control-label">Log details:</div>
                                <div class="col-xs-8"><textarea name="log_details" id="log_details"
                                                                class="form-control disable-resize"></textarea>
                                </div>

                            </div>
                            <div class="col-xs-12 ">
                                <div class="col-xs-9" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                                    <div id="enterLogMessage" class="alert" style="box-shadow: none; vertical-align: 50%; line-height: 22px;
                                      padding-top: 0; padding-bottom: 0;
                                      margin-bottom: 0px" role="alert">

                                    </div>

                                    <!--<div class="bs-callout bs-callout-danger" id="callout-badges-ie8-empty">

                                    </div>-->
                                </div>
                                <div class="col-xs-3">
                                    <button name="submit" class="btn btn-primary btn-submit" id="submitLog"
                                            type="button">
                                        Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xs-6 no-side-margin-padding">
                    <div class="panel panel-default" style="margin-left: 5px;">
                        <div class="panel-heading">
                            Recent Logs
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
    @if($contract->contract_type_id != 16)
        <div class="col-xs-12 form-group panel-approve-logs" id="approveLogs">
            <!-- Approve Logs -->
        </div>
    @endif

    </div>
<!-- //call-coverage-duration  by 1254:added new modal for confirmation message for selecting multiple dates to partial shift -->
    <div id="modal-confirm" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Confirm multiple logs</h4>

                </div>
                <div class="modal-body">
                    <p>Are you sure you want to add logs for multiple days?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default"  onclick="returnLogScreen()">Ok</button>

                    <button type="button" class="btn btn-default"
                            data-dismiss="modal">
                        Cancel
                    </button>

                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
    {{ Form::close() }}
    <script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/onCallSchedule.js') }}"></script>
	<!-- call-coverage-duration  by 1254 : added js for range slider -->
    <script type="text/javascript" src="{{ asset('assets/js/rangeSlider.js') }}"></script>
    <script type="text/javascript">
//call-coverage-duration  by 1254
        function rangeSlide(min_val,max_val) {

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
                value: elements.val,
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

        var isPartialShiftMultiDatesLog = false;
        function returnLogScreen()
        {
            isPartialShiftMultiDatesLog = true;
            $('#modal-confirm').hide();
            $('#submitLog').click();
        }
        $(document).ready(function () {
         
			//call-coverage-duration  by 1254 : added to show confirmation modal for partial shift
            function checkForHours()
            {
                $('#modal-confirm').modal('show');
            }
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
                /*var zoneArray = new Date().toLocaleTimeString('en-us',{timeZoneName:'long'}).split(' ');
                 var zone = '';
                 for(var t=2 ; t< zoneArray.length ; t++){
                 zone = zone+' '+zoneArray[t];
                 }*/
                var strTime = month+'/'+day+'/'+year+' '+hours + ':' + minutes + ' ' + ampm;
                return strTime;
            }
            //vars declared in onCallSchedule.js
            physicians = {!! json_encode($physicians) !!};
            basePath = "{{ URL::to('/')}}";
			//call-coverage-duration  by 1254 : added to hide shift am/pm for partial_hours on

            var cid= $('#contract_id_').val();
            var payment_type = $('#payment_type_'+cid).val();
            var partial_hours = $('#partial_hours_'+cid).val();
            if((payment_type==3 || payment_type==5) && partial_hours == 1) {
                $('#divShift').hide();
            }

            $('#physician_name').change(function () {
                resetPageData();
                // getContracts($('#physician_name').val());
                // getRecentLogs($('#physician_name').val(), $('#contract_id').val());

                var contractId;
                var physicianId = $('#physician_name').val();

                for (var i = 0; i < physicians.length; i++) {
                    if (physicianId == physicians[i].id) {
                        contractId = physicians[i].contract;
                    }
                }

                getPendingForApprovalLogs(physicianId, contractId);
                combinedCallGetContractsRecentLogs(physicianId);
            });

            $('#action').change(function () {
                updateFieldsForAction();
            });

            $('#divShift input:radio[name=shift]').click(function () {
                if (!$('#divShift input:radio[name=shift]').attr('disabled')) {
                    if ($(this).is(':checked')) {
                        clearLogMessage();
                        /*
                         * check for shift to enable or disable dates
                         */

                        var shift = $('input[name=shift]:checked', '#logForm').val()

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
                if(physicianActions.length > 0){
                    var selectedAction = $('#action').val();
                    var cid= $('#contract_id_').val();
                    var partial_hours = $('#partial_hours_'+cid).val();

                    for(var i =0; i<physicianActions.length; i++){
                        if(physicianActions[i].id == selectedAction ){
                            if(physicianActions[i].duration == 0.5 && !$('#divShift input[name=shift]').is(":checked")){
                                if( $('#partial_hours_'+cid).val() ==1 && isSelectedActionHalfDay){
                                    $('#enterLogMessage').css("line-height:21px; !important;");
                                    $('#enterLogMessage').html("Can not add log against half day activity for contract with partial on.");
                                    $('#enterLogMessage').removeClass("alert-success");
                                    $('#enterLogMessage').addClass("alert-danger");
                                    setTimeout(function () {
                                        $('#submitLog').removeClass("disabled");
                                    }, 3000);
                                    return false;

                                }

                                $('#enterLogMessage').html("Please choose AM or PM shift.");
                                $('#enterLogMessage').removeClass("alert-success");
                                $('#enterLogMessage').addClass("alert-danger");
                            }
                        }
                    }
                }
            });

            $('#email').click(function (event) {
                    $(".overlay").show();
                    $('#approvalEmailMessage').html("");
                    var physician = $('#physician_name').val();
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

            $('#submitLog').click(function () {
                
                clearLogMessage();
                var dates = $('#select_date').multiDatesPicker('getDates');
                selected_date = dates[0];
                var notes = $('#log_details').val();
                var notelength = notes.length;
                var action = $('#action').val();
                var agreementId = $('#agreement_id').val();
                var physicianId = $('#physician_name').val();
                var shift = $('input[name=shift]:checked', '#logForm').val();
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

                var contractId;
                var log_details_mandate = false;

                for (var i = 0; i < physicians.length; i++) {
                    if (physicianId == physicians[i].id) {
                        contractId = physicians[i].contract;
                    }
                }
                if($('#mandate_'+physicianId).val() == 1 && notelength < 1){
                    log_details_mandate = true;
                }
				//call-coverage-duration  by 1254 : passed duration for partial hours on
                
                var duration =0;
                var cid= $('#contract_id_').val();
                var payment_type = $('#payment_type_'+cid).val();
                var partial_hours = $('#partial_hours_'+cid).val();
                

                if(( payment_type==3 || payment_type==5) && partial_hours == 1) {
                    duration = $('#duration').val();
                    if(isPartialShiftMultiDatesLog == false && dates.length >1){
                        checkForHours();
                        return false;
                    }
                }
                if ((isSelectedActionHalfDay && $('#divShift input[name=shift]').is(":checked")) || !isSelectedActionHalfDay) {
                    $('#submitLog').addClass("disabled");
                    if (dates.length > 0) {
                        if(!log_details_mandate) {
                            if (notelength < 255) {
                                $.post("{{ URL::to('/')}}" + "/submitLogForMultipleDates",
                                        {
                                            dates: dates,
                                            notes: notes,
                                            action: action,
                                            contractId: contractId,
                                            physicianId: physicianId,
                                            shift: shift,
											//call-coverage-duration  by 1254 : passed duration
                                            duration:duration,
                                            zoneName: zoneName,
                                            timeStamp : timeZone,
                                            current: new Date()
                                        }, function (data) {
                                            $('#select_date').multiDatesPicker('resetDates', 'picked');
                                            // getContracts($('#physician_name').val());
                                            // getRecentLogs($('#physician_name').val(), $('#contract_id').val());
                                            getPendingForApprovalLogs($('#physician_name').val(), $('#contract_id').val());
                                            combinedCallGetContractsRecentLogs($('#physician_name').val());

                                            $('#log_details').val("");
                                            if (data === 'Error') {
                                                $('#enterLogMessage').addClass("alert-danger");
                                                $('#enterLogMessage').removeClass("alert-success");
                                                $('#enterLogMessage').html("Contract not exist for date so you can not add log.");
                                            } else if (data === 'Error 90_days') {
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
                                            } else if(data === 'annual_max_shifts_error'){
                                                $('#enterLogMessage').html("You Have Exceeded Annual Max Shifts.");
                                                $('#enterLogMessage').removeClass("alert-success");
                                                $('#enterLogMessage').addClass("alert-danger");
                                            } else if(data === 'Success'){
                                                $('#enterLogMessage').removeClass("alert-danger");
                                                $('#enterLogMessage').addClass("alert-success");
                                                $('#enterLogMessage').html("Log(s) entered successfully.");
                                            } else {
                                                $('#enterLogMessage').html(data);
                                                $('#enterLogMessage').removeClass("alert-success");
                                                $('#enterLogMessage').addClass("alert-danger");
                                            }
                                            setTimeout(function () {
                                                $('#submitLog').removeClass("disabled");
                                            }, 3000);
                                        });
                                setSlider();
                            } else {
                                $('#enterLogMessage').html("Log details max limit 255 characters.");
                                $('#enterLogMessage').removeClass("alert-success");
                                $('#enterLogMessage').addClass("alert-danger");
                                setTimeout(function () {
                                    $('#submitLog').removeClass("disabled");
                                }, 3000);
                            }
                        }else {
                            $('#enterLogMessage').html("Please Enter Log details.");
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
                        setTimeout(function() {
                            $('#submitLog').removeClass("disabled");
                        },3000);
                    }
                } else {
                    if( $('#partial_hours_'+cid).val() ==1 && isSelectedActionHalfDay)
                    {
                        $('#enterLogMessage').html("Can not add log against half day activity for contract with partial on.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        setTimeout(function () {
                            $('#submitLog').removeClass("disabled");
                        }, 3000);
                        return false;

                    }

                    $('#enterLogMessage').html("Please choose AM or PM shift.");
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                }
            });

            initMultiDatesPicker();
            //call-coverage-duration  by 1254 : added to show 24 hours on duration slider
            var partial_hours_calculation =$('#partial_hours_calculation_'+cid).val();
			if((payment_type==3 || payment_type==5)  && partial_hours == 1) {
               rangeSlide(0.25, partial_hours_calculation);
              
            }
            // getContracts($('#physician_name').val());
            // getRecentLogs($('#physician_name').val(), $('#contract_id').val());
            // getPendingForApprovalLogs($('#physician_name').val(), $('#contract_id').val());
            combinedCallGetContractsRecentLogs($('#physician_name').val());
        });

        $('html').click(function() {
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