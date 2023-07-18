@extends('layouts/_dashboard')
@section('main')
  @include('layouts/_flash')

  <style>
      #default .main {
          padding: 0px !important;
      }

      #default .main .container {
          padding: 10px 30px !important;
      }

      .physician-panel-body {
          padding: 5px !important;
      }

      #facility-div {
          width: 51% !important;
          padding: 0px !important;
      }

      #contractdiv {
          width: 49% !important;
          padding: 0px !important;
      }

      .form-group {
          margin-bottom: 5px !important;
      }

      .panel-body {
          padding: 5px;
      }

      .panel {
          margin-bottom: 0px !important;
      }

      .pre-scrollable_log_approve {
          height: 560px !important;
          max-height: 560px !important;
          overflow-y: scroll !important;
          overflow-x: hidden !important;
      }

      .form-horizontal .form-group {
          /*margin-right: -15px;*/
          margin-left: 0px !important;
      }

      .ui-datepicker {
          width: 16.5em !important;
          padding: 0em 0em 0 !important;
          display: none;
      }

  </style>

  {{ Form::open([ 'class' => 'form form-horizontal form-create-action', 'id'=> 'logForms' ]) }}
    <select  class="form-control hidden" id="hospitals_override_mandate_detail" name="hospitals_override_mandate_detail">
        @foreach($hospitals_override_mandate_details as $hospitals_override_mandate_detail)
            <option value="{{$hospitals_override_mandate_detail->hospital_id}}">{{ $hospitals_override_mandate_detail->action_id }}</option>
        @endforeach
    </select>

    <select  class="form-control hidden" id="time_stamp_entry" name="time_stamp_entry">
        @foreach($time_stamp_entries as $time_stamp_entry)
            <option value="{{$time_stamp_entry->hospital_id}}">{{ $time_stamp_entry->action_id }}</option>
        @endforeach
    </select>

  <div class="col-xs-12 form-group">
    <div class="panel panel-default">
      <div class="panel-body">

        <!-- issue fixed : added new error div log delete on approve log for burden_on_call true by 1254 -->
        <div class="col-xs-12" style="padding-top: 10px;">
          <div id="log-error-delete-message" class="alert" role="alert" style="display: none;">

          </div>
        </div> <!-- end issue fixed : added new error div log delete on approve log for burden_on_call true by 1254 -->


        <!-- Physician to mutilple hospital by 1254 -->
        <div class="col-xs-12 form-group" style="">

          <input type="hidden" id="hospitalcount" value="{{ $hospital_count }}">
          <!-- issue -4 physican to multiple hospital by 1254 : physician log submission  -->
          <div class="col-xs-6" id="facility-div">
            <div class="col-xs-4 control-label" style="text-align: left;padding-left: 0px;padding-right: 0px;"><b>Select
                Organization: </b></div>
            <div class="col-xs-8">
              <input type="hidden" id="hospitalphysician_id" name="hospitalphysician_id" value="{{ $physician->id }}">

              <select class="form-control" id="hospital_name" name="hospital_name">
                @foreach($hospital_names as $name)
                  <option value="{{$name['hospitalid']}}"
                          @if(request()->get('h_id') != null) @if(request()->get('h_id') == $name['hospitalid']) selected="selected" @endif
                      @endif >{{$name['hospitalname']}}
                  </option>
                @endforeach

              </select>
            </div>
            {{-- <div class="col-xs-1"></div>--}}
          </div>

          <div class="col-xs-6" id="contractdiv">
            <div class="col-xs-4 control-label" style="text-align: left;padding-right: 0px;padding-left: 0px;"><b>Select
                Contract: </b></div>
            <div class="col-xs-8">
              <input type="hidden" id="physician_id" name="physician_id" value="{{ $physician->id }}">
              <select class="form-control" id="contract_name"
                      name="contract_name">
                @foreach($contracts as $contract)
                  <option value="{{$contract['id']}}"
                          @if(request()->get('c_id') != null) @if(request()->get('c_id') == $contract['id']) selected="selected" @endif
                      @endif >{{$contract['name']}}</option>
                @endforeach

              </select>
            </div>
            <!-- <div class="col-xs-1"></div>-->
          </div>
        </div>

        <!-- Sprint 6.1.15 Start-->

        <div class="col-xs-6 no-side-margin-padding hide" id="timeStudyLogEntry" style="margin-top:5px;">
          <div class="panel panel-default">
            <input type="hidden" id="selected_dates" name="selected_dates" value="">
            <input type="hidden" id="log_entry_deadline" name="log_entry_deadline" value="">
            <input type="hidden" id="contract_id_post" name="contract_id_post"
                   value={{ Session::get('contract_id_post') }}>
            <div class="panel-heading"
                 style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
              <b> Log Entry </b>
            </div>
            <div class="col-xs-12 form-group">
              <div class="col-xs-4 control-label" style="padding-right: 0;">
                            <span style="font-size: 9px">
                                <h5 style="padding-right: 15px;">Select Date: </h5>
                                (Click to select/deselect dates)
                            </span>
              </div>
              <div class="col-xs-8" id="select_date_time_study" name="select_date_time_study"></div>
            </div>
            <div
                style="border-top: 2px #d6d6d6 solid; margin-top: 290px; margin-bottom: 7px; width: 96%; margin-left: 2%;"></div>
            <div id="timestudyactions" class="timestudyactions"
                 style="width: 100%; height: 504px; overflow-y: auto; list-style-type:none; padding-left:0px;"
                 class="col-xs-12 form-group">

            </div>

            <div class="col-xs-12 form-group">
              @foreach($contracts as $contract)
                <input type="hidden" id="contract_type_{{$contract['id']}}"
                       value="{{$contract['contract_type_id']}}" name='contract_type'>
                <!-- 1254 -->
                <input type="hidden" id="payment_type_{{$contract['id']}}"
                       value="{{$contract['payment_type_id']}}" name='payment_type'>
                <input type="hidden" id="mandate_{{$contract['id']}}"
                       value="{{$contract['mandate_details']}}" name='madate_'>
                <!-- physicians log the hours for holiday activity on any day -->
                <input type="hidden" id="holiday_on_off_{{$contract['id']}}"
                       value="{{$contract['holiday_on_off']}}" name='holiday_on_off'>
                <!-- //call-coverage-duration  by 1254 :added hidden field partial hours -->
                <input type="hidden" id="partial_hours_{{$contract['id']}}"
                       value="{{$contract['partial_hours']}}" name='partial_hours_'>
                <!-- Hour for calculation is taken from db for slider -->
                <input type="hidden" id="hours_for_calculation_{{$contract['id']}}"
                       value="{{$contract['partial_hours_calculation']}}" name='hours_for_calculation'>
              @endforeach
            </div>

            <div class="col-xs-12">
              <div id="error_log_message" class="col-xs-9"
                   style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">

              </div>
              <div class="col-xs-3">
                <button name="submit" class="btn btn-primary btn-submit" id="submitLog" type="submit">
                  Submit
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sprint 6.1.15 End-->

        <div class="col-xs-6 no-side-margin-padding" id="logEntry" style="margin-top:5px;">
          <!-- Log Entry -->
          <div class="panel panel-default">
            <input type="hidden" id="selected_dates" name="selected_dates" value="">
            <input type="hidden" id="log_entry_deadline" name="log_entry_deadline" value="">
            <input type="hidden" id="contract_id_post" name="contract_id_post"
                   value={{ Session::get('contract_id_post') }}>
            <div class="panel-heading"
                 style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
              <b> Log Entry </b>
            </div>
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

                                    <span style="font-size: 9px">
                                        <h5 style="padding-right: 15px;">Select Date: </h5>
                                        (Click to select/deselect dates)
                                    </span>

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
              <div class="col-xs-12 form-group co_mgmt_med_direct_duration">
                <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                <div class="col-xs-8">
                  <div class="rangeSliderDiv">
                    <input class="pull-left" id="duration" type="range" min="0.25" max="12" step="0.25"
                           value="0.25" data-rangeSlider>
                    <output class="pull-right"></output>
                  </div>
                </div>
              </div>

              @if(count($contracts) > 0)
                <div class="col-xs-12 form-group time_stamp">
                  <div class="col-xs-4 control-label">Start:</div>
                  <div id="start_timepicker" class="col-xs-8 input-append">
                    <input id="start_time" name="start_time" class="form-control input-small" placeholder="Start Time"
                           type="text" data-format="hh:mm" autocomplete="off"
                           style="width: 75%; float: left;">
                    <span class="form-control input-group-addon" style="width: 15%;"><i
                          class="glyphicon glyphicon-time"></i></span>
                  </div>
                </div>
                <div class="col-xs-12 form-group time_stamp">
                  <div class="col-xs-4 control-label">End:</div>
                  <div id="end_timepicker" class="col-xs-8 input-append">
                    <input id="end_time" name="end_time" class="form-control input-small" placeholder="End Time"
                           type="text" data-format="hh:mm" autocomplete="off"
                           style="width: 75%; float: left;">
                    <span class="form-control input-group-addon" style="width: 15%;"><i
                          class="glyphicon glyphicon-time"></i></span>
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
              @endif

              <div class="col-xs-12 form-group">
                @foreach($contracts as $contract)
                  <input type="hidden" id="contract_type_{{$contract['id']}}"
                         value="{{$contract['contract_type_id']}}" name='contract_type'>
                  <!-- 1254 -->
                  <input type="hidden" id="payment_type_{{$contract['id']}}"
                         value="{{$contract['payment_type_id']}}" name='payment_type'>
                  <input type="hidden" id="mandate_{{$contract['id']}}"
                         value="{{$contract['mandate_details']}}" name='madate_'>
                  <!-- physicians log the hours for holiday activity on any day -->
                  <input type="hidden" id="holiday_on_off_{{$contract['id']}}"
                         value="{{$contract['holiday_on_off']}}" name='holiday_on_off'>
                  <!-- //call-coverage-duration  by 1254 :added hidden field partial hours -->
                  <input type="hidden" id="partial_hours_{{$contract['id']}}"
                         value="{{$contract['partial_hours']}}" name='partial_hours_'>
                  <!-- Hour for calculation is taken from db for slider -->
                  <input type="hidden" id="hours_for_calculation_{{$contract['id']}}"
                         value="{{$contract['partial_hours_calculation']}}" name='hours_for_calculation'>
                @endforeach
                <div class="col-xs-4 control-label">Log details:</div>
                <div class="col-xs-8"><textarea name="log_details" id="log_details"
                                                class="form-control disable-resize"></textarea>
                </div>

              </div>
              <div class="col-xs-12 start_end_time_message hidden">
                <div class="col-xs-12" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                  <div id="start_end_time_message" class="alert" role="alert">
                  </div>
                </div>
              </div>

              <div class="col-xs-12 ">
                <div class="col-xs-9" style="padding-top: 0; padding-bottom: 0; margin-bottom: 0px">
                  <div id="enterLogMessage" class="alert" role="alert" style="box-shadow: none;">

                  </div>

                  <!--<div class="bs-callout bs-callout-danger" id="callout-badges-ie8-empty">

                  </div>-->
                </div>
                <div class="col-xs-3">
                  <button name="submit" class="btn btn-primary btn-submit submitLog" id="submitLog"
                          type="submit">
                    {{Lang::get('physicians.title_text_create_log')}}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-xs-6 no-side-margin-padding" id="divStats" style="margin-top:5px;">
          <div class="panel panel-default" style="margin-left: 5px;">
            <div class="panel-heading" id="current_days_on_call"
                 style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
              <!-- <b>Current Month Days On Call: </b> <span></span>  This is commented by akash because it is setting dynamically from logEntry.js -->
              <b> </b> <span></span>
            </div>

            <div class="panel-heading co_mgmt_med_direct"
                 style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);">
              <b id="med_direct" class="med_direct">Contract Hours</b>
              <b class="co_mgmt" id="log_detail_lable">Contract Month To Date Totals</b>
            </div>
            <div class="panel-body co_mgmt_med_direct">
              <!-- Recent Logs -->
              <div class="col-xs-6 med_direct"><span id="contract_min_hours"></span></div>
              <div class="col-xs-6 med_direct"><span id="contract_max_hours"></span></div>
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
              <div class="col-xs-12" style="padding-bottom: 9px;font-size: 12px;">
                <span id="current_period_lable"></span>
                {{--                                <b>CURRENT PERIOD</b>--}}
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
                <span id="prior_period_lable"></span>
                {{--                                <b>PRIOR PERIODS</b>--}}
              </div>
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

  <div class="col-xs-12 form-group panel-approve-logs" id="approveLogs">
  </div>

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
          <button type="button" class="btn btn-default" onclick="returnLogScreen()">Ok</button>

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

    @if(!$rejected)
      <script type="text/javascript">
          $('.blink_me').hide()
      </script>
    @else
      <script type="text/javascript">
          $('.blink_me').show()
      </script>
    @endif

    <script type="text/javascript" src="{{ asset('assets/js/logEntry.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/rangeSlider.js') }}"></script>
    <script type="text/javascript">
        $(function () {
            $('#start_timepicker').datetimepicker({
                pickDate: false
            });
            $('#end_timepicker').datetimepicker({
                pickDate: false
            });
        });

        //call-coverage-duration  by 1254

        function rangeSlide(min_val, max_val) {
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

        //call-coverage-duration  by 1254 : flag is added for  allow to contine if selecting multiple dates else not
        var isPartialShiftMultiDatesLog = false;

        function returnLogScreen() {
            isPartialShiftMultiDatesLog = true;
            $('#modal-confirm').hide();
            $('#logForms').submit();
            isPartialShiftMultiDatesLog = false;
            $('#modal-confirm').modal('hide');
        }

        function timeStudyValidation(e, data) {
            if (e.keyCode == 46) {
                var val = data.value;
                if (val.indexOf('.') != -1 || val == '') {
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

        function perUnitPaymentValidation(e, data) {
            if ((event.keyCode >= 48 && event.keyCode <= 57) ||
                event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 ||
                event.keyCode == 39 || event.keyCode == 190) {

            } else {
                event.preventDefault();
            }
        }

        $(document).ready(function () {

            $('body').on('focus', "#start_timepicker_edit", function () {
                $(this).datetimepicker({
                    pickDate: false
                });
            });

            $('body').on('focus', "#end_timepicker_edit", function () {
                $(this).datetimepicker({
                    pickDate: false
                });
            });
            // resetPageData()
            // var dt = new Date();
            // var h =  dt.getHours(), m = dt.getMinutes();
            // var _time = (h > 12) ? (h-12 + ':' + m +' PM') : (h + ':' + m +' AM');
            // $('#start_time').val(_time);
            // $('#end_time').val(_time);

            //Physician to multiple hospital by 1254 : added to display contract only for selected hospital on page load

            //    var hospitalcount = $('#hospitalcount').val();

            //    if(hospitalcount>1)  //added to show hospital dropdown when physician has multiple hospital
            //    {
            //         $('#facility-div').css("display", "block");
            //         $('#contractdiv').removeClass("col-xs-12");
            //         $('#contractdiv').addClass("col-xs-6");
            //    }else
            //    {
            //         $('#contractdiv').removeClass("col-xs-6");
            //         $('#contractdiv').addClass("col-xs-12");
            //    }

            getHospitalContracts();

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
                //alert(Intl.DateTimeFormat().resolvedOptions().timeZone);
                //var zoneArray = new Date().toLocaleTimeString('en-us',{timeZoneName:'short'}).split(' ');
                //alert(new Date().toString().match(/([A-Z]+[\+-][0-9]+)/)[1]);
                //alert(new Date());
                /*var zone = '';
                 for(var t=2 ; t< zoneArray.length ; t++){
                 zone = zone+' '+zoneArray[t];
                 }*/
                var strTime = month + '/' + day + '/' + year + ' ' + hours + ':' + minutes + ' ' + ampm;
                return strTime;
            }

            //var dt = new Date();
            //var utcDate = dt.toUTCString();
            //alert(Intl.DateTimeFormat().resolvedOptions().timeZone);
            //alert(formatAMPM(dt));
            //vars declared in onCallSchedule.js
          {{--physicians = {!! json_encode($physician)!!};--}}
              basePath = "{{ URL::to('/')}}";

            $('#contract_name').change(function () {
                //$(".overlay").show();
                // resetPageData();

                // Physician to Multiple hospital by 1254 : code to change payment type id with selected  contract id
                // issue fixed :unable to select more than two dates for per-diem payment types by 1254
                // var contract_id = $("*[name='contract_name']").val();
                // var new_payment_type_id = 'payment_type_'+contract_id;
                // $("*[name='payment_type']").attr('id',new_payment_type_id);

                // getContracts($('#physician_id').val());
                //$(".overlay").hide();
                var contract_id = $("*[name='contract_name']").val();
                var payment_type_id = $('#payment_type_' + contract_id).val();
                // getRecentLogs($('#physician_id').val(), contract_id);

                combinedCallGetContractsRecentLogs($('#physician_id').val());

                // getPendingForApprovalLogs($('#physician_id').val(), contract_id);
                if (payment_type_id == 1 || payment_type_id == 2 || payment_type_id == 6 || payment_type_id == 9) {
                    getTimeStampEntries();
                } else {
                    // $('.time_stamp').addClass('hidden');
                    // $('.start_end_time_message').addClass('hidden');
                    // $('.co_mgmt_med_direct_duration').removeClass('hidden');
                }

                setSlider();
            });

            //physician to all hosptials by 1254
            $('#hospital_name').on('change', function () {
                resetPageData();
                getHospitalContracts();
            });

            function getOverrideMandateDetails() {
                var hospital_id = $('#hospital_name').val();
                var selected_action_id = $('#action').val();
                var override_mandate = $('#' + selected_action_id).attr('override_mandate');
                if (override_mandate == undefined) {
                    override_mandate = false;
                }
                override_mandate_details_flag = JSON.parse(override_mandate);
            }

            function validationTimeStampEntry() {
                var current_date = new Date();

                var start_t = Date.parse(current_date.toLocaleDateString() + ' ' + $('#start_time').val());
                var end_t = Date.parse(current_date.toLocaleDateString() + ' ' + $('#end_time').val());

                $('#enterLogMessage').html("");
                $('#enterLogMessage').removeClass("alert-success");
                $('#enterLogMessage').removeClass("alert-danger");

                if ($('#start_time').val() == "" && $('#end_time').val() == "") {
                    $('#enterLogMessage').html("Please enter start and end time.");
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                    return false;
                } else if ($('#start_time').val() == "") {
                    $('#enterLogMessage').html("Please enter start time.");
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                    return false;
                } else if ($('#end_time').val() == "") {
                    $('#enterLogMessage').html("Please enter end time.");
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                    return false;
                } else {
                    if (start_t >= end_t) {
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

            function timeobject(time) {
                a = time.replace('AM', '').replace('PM', '').split(':');
                h = parseInt(a[0]);
                m = parseInt(a[1]);
                ampm = (time.indexOf('AM') !== -1) ? 'AM' : 'PM';

                return {hour: h, minute: m, ampm: ampm};
            }

            function getHospitalContracts() {
                var c_id = "";
              @if(request()->get("c_id") != null)
                var c_id = <?php echo request()->get("c_id") ?>;
              @endif

                var id = $('#hospital_name').val();
                var physicianid = $('#hospitalphysician_id').val();
                $.ajax({
                    'type': 'GET',
                    'url': '/physician/hospitalcontracts/' + id + '/' + physicianid,
                    'dataType': 'json',
                    success: function (response) {

                        $('#contract_name').empty();
                        console.log(response.contractname);
                        $.each(response.contractname, function (index, value) {
                            var selected = "";
                            if (c_id == value.contractid) {
                                selected = "selected=selected";
                            }
                            $('[name=contract_name]').append('<option value="' + value.contractid + '" ' + selected + '>' + value.contractname + '</option>');

                        });

                        setSlider();

                        // getContracts($('#hospitalphysician_id').val());
                        // getRecentLogs(0, 0);
                        combinedCallGetContractsRecentLogs($('#hospitalphysician_id').val());
                    }
                });
            }

            $('#action').change(function () {
                updateFieldsForAction();

                var contract_id = $("*[name='contract_name']").val();
                var payment_type_id = $('#payment_type_' + contract_id).val();
                if (payment_type_id == 1 || payment_type_id == 2 || payment_type_id == 6 || payment_type_id == 9) {
                    getTimeStampEntries();
                }// else{
                // $('.time_stamp').hide();
                // $('.start_end_time_error_message').hide();
                // $('.co_mgmt_med_direct_duration').removeClass('hidden');
                // }
            });

            //call-coverage-duration  by 1254 : added to show confirmation modal for partial shift
            function checkForHours() {
                $('#modal-confirm').modal('show');
            }

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

                    //if ($('#contract_type_' + contractId).val() == 4) {
                    if ($('#payment_type_' + contractId).val() == 3) {
                        for (var i = 0; i < physicianActions.length; i++) {
                            if (physicianActions[i].id == selectedAction) {
                                if ($('#partial_hours_' + contractId).val() == 1 && physicianActions[i].duration == 0.5) {
                                    $('#enterLogMessage').html("Can not add logs against half day activity for contract with partial on.");
                                    $('#enterLogMessage').removeClass("alert-success");
                                    $('#enterLogMessage').addClass("alert-danger");

                                    setTimeout(function () {
                                    }, 3000);
                                    return false;


                                }

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

            $('#logForms').submit(function (event) {
                $('#submitLog').attr("disabled");
                $('.submitLog').addClass("disabled");
                var contractId = $('#contract_name').val();
                var payment_type_id = $('#payment_type_' + contractId).val();

                var durations = 0;
                time_study_array = [];
                if (payment_type_id == 7) {
                    var dates = $('#select_date_time_study').multiDatesPicker('getDates');

                    if (dates.length > 0) {
                        $('input[name=actions]').each(function () {
                            if ($(this).val() != "" && $(this).val() != null && $(this).val() != undefined) {
                                if (parseFloat($(this).val()) > 0) {
                                    durations += parseFloat($(this).val());
                                    time_study_array.push({
                                        action_id: $(this).attr('id'),
                                        duration: $(this).val()
                                    });
                                }
                            }
                        });

                        if (durations == 0) {
                            $('#enterLogMessage').html("Please enter hours.");
                            $('#enterLogMessage').removeClass("alert-success");
                            $('#enterLogMessage').addClass("alert-danger");
                            setTimeout(function () {
                                $('#submitLog').removeClass("disabled");
                            }, 3000);
                            return false;
                        } else {
                            if (durations > 24) {
                                $('#enterLogMessage').html("Total durations should be less than or equal to 24.");
                                $('#enterLogMessage').removeClass("alert-success");
                                $('#enterLogMessage').addClass("alert-danger");
                                setTimeout(function () {
                                    $('#submitLog').removeClass("disabled");
                                }, 3000);
                                return false;
                            } else {
                                // No need
                            }
                        }
                    } else {
                        $('#enterLogMessage').html("Please select date(s) for log.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        setTimeout(function () {
                            $('#submitLog').removeClass("disabled");
                        }, 3000);
                        return false;
                    }

                } else if (payment_type_id == 8) {
                    var action = $('#action').val();
                    duration = $('#per_unit_duration').val();
                    if (duration <= 0) {
                        $('#enterLogMessage').addClass("alert-danger");
                        $('#enterLogMessage').removeClass("alert-success");
                        if (duration == "") {
                            $('#enterLogMessage').html("Please enter number(s).");
                        } else {
                            $('#enterLogMessage').html("Number should be greater than zero(0).");
                        }
                        return false;
                    }
                    time_study_array.push({
                        action_id: action,
                        duration: duration
                    });
                } else {
                    var action = $('#action').val();
                    var duration = $('#duration').val();

                    time_study_array.push({
                        action_id: action,
                        duration: duration
                    });
                }

                clearLogMessage();
                var dates = $('#select_date').multiDatesPicker('getDates');
                selected_date = dates[0]; //last selected date for log submission.
                var notes = $('#log_details').val();
                var notelength = notes.length;
                // var action = $('#action').val();
                var physicianId = $('#physician_id').val();
                // var contractId = $('#contract_name').val();
                var hospitalId = $('#hospital_name').val();
                console.log(hospitalId);
                // var duration = $('#duration').val();
                var contract_type_id = $('#contract_type_' + contractId).val();
                // var payment_type_id = $('#payment_type_' + contractId).val();
                var shift = $('input[name=shift]:checked', '#logForms').val();
                var timeZone = formatAMPM(new Date());
                var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
                var start_time = "";
                var end_time = "";

                if (payment_type_id == 1 || payment_type_id == 2 || payment_type_id == 6) {
                    if (time_stamp_entry_flag) {
                        var check = validationTimeStampEntry();
                        if (!check) {
                            return false;
                        } else {
                            start = timeobject($('#start_time').val());
                            end = timeobject($('#end_time').val());
                            end.hour = (end.ampm === 'PM' && start.ampm !== 'PM' && end.hour < 12) ? end.hour + 12 : end.hour;
                            if (((end.ampm === 'PM' && start.ampm === 'PM') || (end.ampm === 'AM' && start.ampm === 'AM')) && start.hour == 12) {
                                start.hour = 0;
                            }
                            if (((end.ampm === 'PM' && start.ampm === 'PM') || (end.ampm === 'AM' && start.ampm === 'AM')) && end.hour == 12) {
                                end.hour = 0;
                            }
                            hours = Math.abs(end.hour - start.hour);
                            minutes = end.minute - start.minute;

                            if (minutes < 0) {
                                minutes = Math.abs(60 + minutes);
                                hours--;
                            }

                            if (minutes < 10) {
                                minutes = '0' + minutes;
                            }

                            total_minutes = (hours * 60) + parseInt(minutes);
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

                //call-coverage-duration  by 1254 : condition added to check for partial shift to show confirmation modal
                if (($('#partial_hours_' + contractId).val() == 1 && !isSelectedActionHalfDay) && ($('#payment_type_' + contractId).val() == 3 || $('#payment_type_' + contractId).val() == 5) && dates.length > 1 && isPartialShiftMultiDatesLog == false) {
                    checkForHours();
                    return false;
                }
                if (typeof zoneName === "undefined") {
                    timeZone = '';
                    zoneName = '';
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
                var submitURL = 'submitLogForOnCall';//'submitLogForMultipleDates';
                //if ($('#contract_type_' + contractId).val() != 4) {
                if ($('#payment_type_' + contractId).val() != 3 && $('#payment_type_' + contractId).val() != 5) {
                    submitURL = 'postSaveLog';
                    if (action == -1) {
                        action_name = $('#custom_action').val();
                    }
                    if ($('#payment_type_' + contractId).val() == 7) {
                        duration = durations;
                        dates = $('#select_date_time_study').multiDatesPicker('getDates');
                    }
                    if (dates.length > 0) {
                        $.post("/checkDuretion",
                            {
                                dates: dates,
                                contractId: contractId,
                                physicianId: physicianId,
                                hospitalId: hospitalId,
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
                    } else {
                        $('#enterLogMessage').html("Please select date(s) for log.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        setTimeout(function () {
                            $('#submitLog').removeClass("disabled");
                        }, 3000);
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
                                    if (!log_details_mandate || override_mandate_details_flag) {
                                        if (notelength < 255) {

                                            if (time_study_array.length > 0) {
                                                for (var j = 0; j < time_study_array.length; j++) {
                                                    action = time_study_array[j]["action_id"];
                                                    duration = time_study_array[j]["duration"];

                                                    $.post("/" + submitURL,
                                                        {
                                                            dates: dates,
                                                            notes: notes,
                                                            action: action,
                                                            contractId: contractId,
                                                            physicianId: physicianId,
                                                            hospitalId: hospitalId,
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
                                                            // getContracts($('#physician_name').val());
                                                            $('#log_details').val("");
                                                            $('#per_unit_duration').val("");

                                                            if (data === 'Error') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').html("Contract not exist for date so you can not add log.");
                                                            } else if (data === 'Excess monthly') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                // $('#enterLogMessage').html("You Have Exceeded Monthly Max Hours");
                                                                if (payment_type_id == 8) {
                                                                    $('#enterLogMessage').html("You Have Exceeded Max Units.");
                                                                } else {
                                                                    $('#enterLogMessage').html("You Have Exceeded Max Hours.");
                                                                }
                                                            } else if (data === 'Excess 24') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').html("Cannot submit a log for time in excess of 24 hours in a day.");
                                                            } else if (data === 'Excess annual') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                if (payment_type_id == 8) {
                                                                    $('#enterLogMessage').html("You Have Exceeded Annual Max Units.");
                                                                } else {
                                                                    $('#enterLogMessage').html("You Have Exceeded Annual Max Hours.");
                                                                }
                                                            } else if (data === 'Error 90_days') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').html("You are not allowed to enter log before " + $('#log_entry_deadline').val() + " days.");
                                                            } else if (data === 'Excess 365') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').html("You are not allowed to enter log before " + $('#log_entry_deadline').val() + " days.");
                                                            } else if (data === 'practice_error') {
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').html("You are not allowed to enter log for the date before practice start date.");
                                                            } else if (data == "both_actions_empty_error") {
                                                                $('#enterLogMessage').html("Not Allowed To Submit Log!");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                            } else if (data == "Log Exist") {
                                                                $('#enterLogMessage').html("Log is already exist between start time and end time.");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                            } else if (data == "Start And End Time") {
                                                                $('#enterLogMessage').html("Start time should be less than end time.");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                            } else if (data == "annual_max_shifts_error") {
                                                                $('#enterLogMessage').html("You Have Exceeded Annual Max Shifts.");
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                            } else if (data === 'Success') {
                                                                $('#enterLogMessage').removeClass("alert-danger");
                                                                $('#enterLogMessage').addClass("alert-success");
                                                                $('#enterLogMessage').html("Log(s) entered successfully.");

                                                                setTimeout(function () {
                                                                    // getContracts(physicianId);
                                                                    console.log(contractId)
                                                                    getRecentLogs(physicianId, contractId);
                                                                    getPendingForApprovalLogs(physicianId, contractId);
                                                                }, 500);
                                                            } else {
                                                                $('#enterLogMessage').html(data);
                                                                $('#enterLogMessage').removeClass("alert-success");
                                                                $('#enterLogMessage').addClass("alert-danger");
                                                            }
                                                            $('#duration').val(0.25);
                                                            $(".rangeSlider__handle").css("transform", "translateX(0px)");
                                                            $(".rangeSlider__fill").css("width", "0");
                                                            //call-coverage-duration  by 1254  : rangeSlide() commented and added setSlider()
                                                            //It will check for hours duration either for partial-shift on and for other payment type contract
                                                            setSlider();
                                                            $('#submitLog').removeClass("disabled");
                                                            $('.submitLog').removeClass("disabled");
                                                        });
                                                }
                                            } else {
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
                    var cid = $('#contract_name').val();

                    if ($('#partial_hours_' + cid).val() == 1 && isSelectedActionHalfDay) {
                        $('#enterLogMessage').html("Can not add log against half day activity for contract with partial on.");
                        $('#enterLogMessage').removeClass("alert-success");
                        $('#enterLogMessage').addClass("alert-danger");
                        return false;
                        setTimeout(function () {
                            $('#submitLog').removeClass("disabled");
                        }, 3000);


                    }

                    $('#enterLogMessage').html("Please choose AM or PM shift.");
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");

                }
                event.preventDefault();
            });

            // initMultiDatesPicker();
            // getContracts($('#physician_id').val());
            // rangeSlide();
            //call-coverage-duration  by 1254 : added for partial shift on and for other payment type on page load
            var cid = $('#contract_name').val();
            var payment_type = $('#payment_type_' + cid).val();
            var partial_hours = $('#partial_hours_' + cid).val();
            var partial_hours_calculation = $('#hours_for_calculation_' + cid).val();
            if (payment_type == 3 && partial_hours == 1) {
                rangeSlide(0.25, partial_hours_calculation);
            } else {
                rangeSlide(0.25, 12);
            }

            $('#select_date').multiDatesPicker();
        });
        //call-coverage-duration  by 1254  : added for partial-shift on contract change
        // $('#contract_name').change(function () {
        //     //$(".overlay").show();
        //     resetPageData();
        //     getContracts($('#physician_id').val());
        //     //$(".overlay").hide();
        //     setSlider();
        //
        // });

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
