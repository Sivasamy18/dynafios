@extends('layouts/_dashboard')
@section('main')
    @include('layouts/_flash')
    <div class="col-xs-12 form-group">
        <div class="panel">
        <select  class="form-control hidden" id="hospitals_override_mandate_detail" name="hospitals_override_mandate_detail">
            @foreach($hospital_override_mandate_details as $hospitals_override_mandate_detail)
                <option value="{{$hospitals_override_mandate_detail->hospital_id}}">{{ $hospitals_override_mandate_detail->action_id }}</option>
            @endforeach
        </select>

		<select  class="form-control hidden" id="hospital_time_stamp_entries" name="hospital_time_stamp_entries">
            @foreach($hospital_time_stamp_entries as $hospital_time_stamp_entry)
                <option value="{{$hospital_time_stamp_entry->hospital_id}}">{{ $hospital_time_stamp_entry->action_id }}</option>
            @endforeach
        </select>

            <div class="filters">
                <a class="active" href="#"> Rejected Logs for Review </a>
            </div>

            
            <div class="col-xs-12" style="padding-top: 10px;">
                <div id="enterLogMessage" class="alert" role="alert" style="display: none;">

                </div>
            </div>
            <div class="clearfix"></div>

            <div class="panel-body">
                <!--<div class="col-xs-12 form-group">
                    <div class="col-xs-3 control-label"><b>Select Contract: </b></div>
                    <div class="col-xs-5">
                        {{ Form::select('contract', $contracts, Request::old('contract', $contract), [ 'class' =>
                         'form-control' , 'id' => 'contract' ]) }}
                    </div>

                </div>-->

                <div class="col-xs-12 form-group">

                <!-- issue -4 physican to multiple hospital by 1254 : physician log submission  -->
                <div class="col-xs-6" style="" id="facility-div">
                        <div class="col-xs-4 control-label" style="text-align: left;padding-left: 0px;padding-right: 0px;"  ><b>Select Organization: </b></div>
                        <div class="col-xs-8">
                        <input type="hidden" id="hospitalphysician_id" name="hospitalphysician_id" value="{{ $physician->id }}">

                        {{ Form::select('hospital', $hospitals, Request::old('hospital', $hospital), [ 'class' =>
                         'form-control' , 'id' => 'hospital' ]) }}
                        </div>
                        {{-- <div class="col-xs-1"></div>--}}
                </div>


                    <div class="col-xs-6" id="contractdiv">
                    <div class="col-xs-4 control-label" style="text-align: left;padding-right: 0px;padding-left: 0px;"  ><b>Select Contract: </b></div>
                        <div class="col-xs-8">
                            <input type="hidden" id="physician_id" name="physician_id" value="{{ $physician->id }}">
                            {{ Form::select('contract', $contracts, Request::old('contract', $contract), [ 'class' =>
                             'form-control' , 'id' => 'contract' ]) }}
                        </div>
                        <!-- <div class="col-xs-1"></div>-->
                    </div>
                  </div>
            </div>
            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                <hr style="width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                @if(count($contracts) == 0)
                    <p>There is no contract present for rejected logs.</p>
                @endif
                @if(count($contracts) != 0 && count($logs) == 0)
                    <p>No rejected logs found.</p>
                @endif
            </div>
            <div class="panel-body" style="height: 800px; overflow: auto;">
                <div>
                    @foreach($logs as $recent_log)
                        <input type="hidden" id="log_ids" name="approve_log_ids[]" value={{$recent_log['id']}}>
                        <div id="{{$recent_log['id']}}">
                            <div class="col-xs-10" style="padding-left: 0px;">
                                @if (Request::is('practiceManager/*'))
                                <div class="col-xs-4 control-label">Physician Name:</div>
                                <div class="col-xs-8 control-label_custom">{{$recent_log['physician_name']}}</div>
                                @endif
                                <div class="col-xs-4 control-label">Log Date:</div>
                                <div class="col-xs-8 control-label_custom">{{$recent_log['date']}}</div>
                                <div class="col-xs-4 control-label">Action Name:</div>
                                <div class="col-xs-8 control-label_custom" style="word-wrap: break-word;">@if($recent_log['action'] != "") {{$recent_log['action']}} @else - @endif</div>

                                <div class="col-xs-4 control-label">@if($recent_log['payment_type'] == App\PaymentType::PER_UNIT) Units: @else Duration: @endif</div>
                                <div class="col-xs-8 control-label_custom">@if($recent_log['payment_type'] == App\PaymentType::PER_UNIT) {{ round($recent_log['duration'], 0) }} @else {{(strpos($recent_log['duration'], 'Day') !== false)? str_replace('Day', '', $recent_log['duration']) : $recent_log['duration'] }} @endif
                                @if( (($recent_log['partial_hours'] == 1) && ($recent_log['payment_type'] == 3  || $recent_log['payment_type'] == 5 )) || ($recent_log['partial_hours'] == 0) && ($recent_log['payment_type'] == 1  || $recent_log['payment_type'] == 2  ||  $recent_log['payment_type'] == 6 ||  $recent_log['payment_type'] == App\PaymentType::TIME_STUDY))
                                    <span>Hour(s)</span>   
                                @else
                                    @if($recent_log['duration'] == "AM" || $recent_log['duration'] == "PM" || $recent_log['payment_type'] == App\PaymentType::PER_UNIT)
                                    <span> </span> 
                                @else
                                <span>Day</span> 
                                    @endif
                                @endif
                                </div>
                                <div class="col-xs-4 control-label">Entered By:</div>
                                <div class="col-xs-8 control-label_custom">{{$recent_log['enteredBy']!=""?$recent_log['enteredBy']:"Not Available"}}</div>
                                <div class="col-xs-4 control-label">Date Entered:</div>
                                <div class="col-xs-8 control-label_custom">{{$recent_log['created']}}</div>
                                <div class="col-xs-4 control-label">Log Details:</div>
                                <div class="col-xs-8 control-label_custom"
                                     style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['note']!=""?$recent_log['note']:"-"}}</div>
                                <div class="col-xs-4 control-label">Reason:</div>
                                <div class="col-xs-8 control-label_custom"
                                     style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['reason']!=""?$recent_log['reason']:"-"}}</div>
                                <div class="col-xs-4 control-label">Rejected By:</div>
                                <div class="col-xs-8 control-label_custom"
                                     style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['rejectedBy']!=""?$recent_log['rejectedBy']:"-"}}</div>
                            </div>
                            <div class="col-xs-2">
                                <a
                                        id="{{$recent_log['id']}}"
                                        class="btn btn-default btn-delete log_delete_btn"
                                        style="margin-top: 62px"
                                        href=""
                                        data-toggle="modal" data-target="#modal-confirm-edit_{{$recent_log['id']}}"
                                        
                                        onclick="loadEditModalData({{$recent_log['id']}}, {{$recent_log['action_id']}});"
                                        }}
                                > <!-- issue fixes for clear cache data  on rejected log: function added loadEditModalData by 1254 -->
                                    <i class="fa fa-pencil fa-fw"></i>
                                </a>
                                <a
                                        id="{{$recent_log['id']}}"
                                        class="btn btn-default btn-delete log_delete_btn"
                                        style="margin-top: 62px"
                                        href=""
                                        data-toggle="modal" data-target="#modal-confirm-delete_{{$recent_log['id']}}"
                                        onclick=""
                                        }}
                                >
                                    <i class="fa fa-trash-o fa-fw"></i>
                                </a>
                            </div>
                            <div>&nbsp;</div>
                            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                                <hr style="width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                            </div>
                        </div>
                        <div id="modal-confirm-delete_{{$recent_log['id']}}" class="modal fade">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-hidden="true">&times;</button>
                                        <h4 class="modal-title">Confirm delete log</h4>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to delete this log?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default"
                                                data-dismiss="modal" onclick="">
                                            Cancel
                                        </button>
                                        <button type="button" class="btn btn-primary" data-dismiss="modal"
                                                onClick="delete_log({{$recent_log['id']}});">Delete
                                        </button>
                                    </div>
                                </div>
                                <!-- /.modal-content -->
                            </div>
                        </div>

                        <div id="modal-confirm-edit_{{$recent_log['id']}}" class="modal fade">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title">Resubmit Log</h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="panel-body">
                                            <div class="col-xs-12">
                                                <div id="enterLogMessageLog_{{$recent_log['id']}}" class="alert" role="alert" style="display: none;">

                                                </div>
                                            </div>
                                            <!-- <div class="col-xs-12 start_end_time_error_message">
                                                <div id="start_end_time_message" class="alert" role="alert">
                                                </div>
                                            </div> -->
                                            <div class="col-xs-12 form-group">
                                                <div class="col-xs-4 control-label">Action/Duty:</div>
                                                <div class="col-xs-8">
                                                    <?php $action_status= 0;
                                                    $hideShift = 0;
                                                    ?>
                                                    @if($recent_log['payment_type'] == App\PaymentType::PER_UNIT)
                                                        @foreach($recent_log['actions'] as $action)
                                                            {{ Form::label('action_' . $recent_log['id'], Request::old('', $action['display_name']), array('class' => 'form-control', 'id' => 'action_' . $recent_log['id'], 'action_id' => $action['id'], 'style' => 'text-overflow: ellipsis; white-space: nowrap; overflow: hidden;')) }}
                                                        @endforeach
                                                    @else
                                                        <select class="form-control" id="action_{{$recent_log['id']}}" name="action" onchange="action_change({{$recent_log['id']}});">
                                                            @foreach($recent_log['actions'] as $actions)
                                                                @if($recent_log['payment_type'] == 1 || $recent_log['payment_type'] == 2 || $recent_log['payment_type'] == 6)
                                                                    @if($recent_log['mandate'] == 1 && !$actions['override_mandate'])
                                                                        <option style='font-weight:bold;' value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if($recent_log['action_id'] == $actions['id']){ $action_status++; }
                                                                        if($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' ||$actions['name'] == 'Called-In'){ $hideShift++; }
                                                                        ?>
                                                                    @else
                                                                        <option value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if($recent_log['action_id'] == $actions['id']){ $action_status++; }
                                                                        if($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' ||$actions['name'] == 'Called-In'){ $hideShift++; }
                                                                        ?>
                                                                    @endif
                                                                @else
                                                                    <option value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                    <?php if($recent_log['action_id'] == $actions['id']){ $action_status++; }
                                                                    if($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' ||$actions['name'] == 'Called-In'){ $hideShift++; }
                                                                    ?>
                                                                @endif
                                                            @endforeach
                                                            @if($recent_log['payment_type'] != 3 && $recent_log['payment_type'] != 5 && $recent_log['payment_type'] != 7 && $recent_log['payment_type'] != 8)
                                                                @if($recent_log["custom_action_enabled"])
                                                                    @if($recent_log['mandate'] == 1)
                                                                        <option style='font-weight:bold;' value="-1" {{ $action_status != 0? "":"selected" }}>Custom Action</option>
                                                                    @else
                                                                        <option value="-1" {{ $action_status != 0? "":"selected" }}>Custom Action</option>
                                                                    @endif
                                                                @elseif($action_status == 0)
                                                                    <option value="-1" {{ $action_status != 0? "":"selected" }}>Custom Action</option>
                                                                    <script>
                                                                        $(document).ready(function () {
                                                                            $("#action_{{$recent_log['id']}}").hide();
                                                                        });
                                                                    </script>
                                                                @endif
                                                            @endif
                                                        </select>
                                                    @endif
                                                    
                                                    @if($recent_log['payment_type'] == 3)
                                                        @foreach($recent_log['actions'] as $actions)
                                                            <input type="hidden" name="on_call_duration_{{$actions['id']}}_{{$recent_log['id']}}" id="on_call_duration_{{$actions['id']}}_{{$recent_log['id']}}" value="{{$actions['duration']}}"/>
                                                        @endforeach
                                                    @endif

                                                    @if($recent_log['payment_type'] != App\PaymentType::TIME_STUDY && $recent_log['payment_type'] != App\PaymentType::PER_UNIT)
                                                        <input class="form-control" type="text" id="custom_action_{{$recent_log['id']}}" name="custom_action_{{$recent_log['id']}}"
                                                           value="{{$recent_log["custom_action"]}}" style="display: {{ $action_status != 0  && $recent_log["custom_action_enabled"]? "none;":"block;" }} ">
                                                    @endif
                                                </div>
                                            </div>
                                            @if($recent_log['payment_type'] == 3 || $recent_log['payment_type'] == 5)
                                                @if($hideShift == 0 && $recent_log['payment_type'] != 5)
                                                    <div class="col-xs-12 form-group" id="divShift_{{$recent_log['id']}}">
                                                        <div class="col-xs-4 control-label">Shift:</div>
                                                        <div class="col-xs-8">
                                                            <label class="radio-inline">
                                                                <input type="radio" name="shift_{{$recent_log['id']}}" {{$recent_log['shift']==1 ? 'checked':''}} value="1" {{$recent_log['shift'] != 0 ? '':'disabled'}}> AM
                                                            </label>
                                                            <label class="radio-inline">
                                                                <input type="radio" name="shift_{{$recent_log['id']}}" {{$recent_log['shift']==2 ? 'checked':''}} value="2" {{$recent_log['shift'] != 0 ? '':'disabled'}}> PM
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endif
													<!--call-coverage-duration  by 1254 : show duration for partial hours -->
                                                    @if($recent_log['partial_hours'] == 1)
                                                        <div class="col-xs-12 form-group co_mgmt_med_direct" id="log_slider">
                                                            <div class="col-xs-4 control-label">Durations: <br/>(Hours) &nbsp;</div>
                                                            <div class="col-xs-8">
                                                                <div class="rangeSliderDiv">
                                                                    <input class="pull-left" id="duration_{{$recent_log['id']}}" type="range" step="0.25"
                                                                           value="{{$recent_log['duration']}}" data-rangeSlider>
                                                                    <output class="pull-right range_slider_recentlog_output">{{$recent_log['duration']}}</output>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    @endif
                                            @elseif($recent_log['payment_type'] == App\PaymentType::PER_UNIT)
                                                <div class="col-xs-12 form-group per_unit_duration">
                                                    <div class="col-xs-4 control-label">Number: &nbsp;</div>
                                                    <div class="col-xs-4">
                                                        {{ Form::text('duration_'. $recent_log['id'], Request::old('duration', round($recent_log['duration'], 0)), [ 'id' => 'duration_' . $recent_log['id'], 'class' => 'form-control', 'maxlength' => 3, 'autocomplete' => "off", 'onkeypress' => "perUnitPaymentValidation(event, this)"]) }}
                                                    </div>
                                                </div>
                                            @else
												@if($recent_log['payment_type'] == 7)
													<div class="col-xs-12 form-group co_mgmt_med_direct"  id="log_slider">
                                                        <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                                                        <div class="col-xs-4">
                                                            <input id="duration_{{$recent_log['id']}}" value="{{$recent_log['duration']}}" class="form-control" type="text" name="date" onkeypress="timeStudyValidation(event, this)" maxlength="5" autocomplete="off" placeholder="Hours"/>
                                                        </div>
                                                        <div class="col-xs-4"></div>
                                                    </div>
                                                @else
                                                    <div class="col-xs-12 form-group co_mgmt_med_direct">
														<div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
														<div class="col-xs-8">
															<div class="rangeSliderDiv">
																<input class="pull-left" id="duration_{{$recent_log['id']}}" type="range" min="0.25" max="12" step="0.25"
																	   value="{{$recent_log['duration']}}" data-rangeSlider>
																<output class="pull-right">{{$recent_log['duration']}}</output>
															</div>
														</div>
													</div>
                                                    <!-- Sprint 6.1.12 Time Stamp Entry Start-->
                                                    <div class="col-xs-12 form-group time_stamp_edit_log">
                                                        <div class="col-xs-4 control-label">Start:</div>
                                                        <div id="start_timepicker" class="col-xs-6 input-append">
                                                            <input id="start_time_rejected_edit_log_{{$recent_log['id']}}" name="start_time_rejected_edit_log" class="form-control input-small" placeholder="Start Time" type="text" 
                                                            data-format="hh:mm" value="{{$recent_log['start_time']}}" autocomplete="off" style="width: 75%; float: left;">
                                                            <span class="form-control input-group-addon" style="width: 15%;"><i class="glyphicon glyphicon-time"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 form-group time_stamp_edit_log">
                                                        <div class="col-xs-4 control-label">End:</div>
                                                        <div id="end_timepicker" class="col-xs-6 input-append">
                                                            <input id="end_time_rejected_edit_log_{{$recent_log['id']}}" name="end_time_rejected_edit_log" class="form-control input-small" placeholder="End Time" type="text" 
                                                            data-format="hh:mm" value="{{$recent_log['end_time']}}" autocomplete="off" style="width: 75%; float: left;">
                                                            <span class="form-control input-group-addon" style="width: 15%;"><i class="glyphicon glyphicon-time"></i></span>
                                                        </div>
                                                    </div>
                                                    <!-- Sprint 6.1.12 Time Stamp Entry End-->
												@endif
                                            @endif
                                            <div class="col-xs-12 form-group">
                                                <div class="col-xs-4 control-label">Date: </div>
                                                <div class="col-xs-4">
                                                    <input class="form-control" type="text" name="date" id="date_{{$recent_log['id']}}" value="{{$recent_log['date']}}" readonly="true"/>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 form-group">
                                                <div class="col-xs-4 control-label">Rejected By: </div>
                                                <div class="col-xs-8">
                                                    {{$recent_log["rejectedBy"]}}
                                                </div>
                                            </div>
                                            <div class="col-xs-12 form-group">
                                                <div class="col-xs-4 control-label">Reason: </div>
                                                <div class="col-xs-8">
                                                    {{$recent_log["reason"]}}
                                                </div>
                                            </div>
                                            <div class="col-xs-12 form-group">
                                                <input type="hidden" id="contract_type_{{$recent_log['id']}}"
                                                       value="{{$recent_log['contract_type']}}">
                                                <input type="hidden" id="payment_type_{{$recent_log['id']}}"
                                                       value="{{$recent_log['payment_type']}}">
                                                <input type="hidden" id="mandate_{{$recent_log['id']}}"
                                                       value="{{$recent_log['mandate']}}">
												<!-- call-coverage-duration  by 1254 : declare hidden field for partial hours -->
                                                <input type="hidden" id="partial_hours_"
                                                       value="{{$recent_log['partial_hours']}}">
                                                <input type="hidden" id="partial_hours_calculation"
                                                       value="{{$recent_log['partial_hours_calculation']}}">
												
												@if($recent_log['payment_type'] != 7)
													<div class="col-xs-4 control-label">Log details:</div>
													<div class="col-xs-8">
														<textarea name="log_details" id="log_details_{{$recent_log['id']}}" class="form-control disable-resize">{{$recent_log['note']}}</textarea>
													</div>
												
												@endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default"
                                                data-dismiss="modal" onclick="">
                                            Cancel
                                        </button>
                                        <button type="button" class="btn btn-primary"
                                                 onClick="reSubmit({{$recent_log['id']}});">Submit
                                        </button>
                                    </div>
                                </div>
                                <!-- /.modal-content -->
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

@endsection
@section('scripts')
    <script type="text/javascript" src="{{ asset('assets/js/rangeSlider.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/moment.min.js') }}"></script>
    <link type="text/css" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}"/>
    <script type="text/javascript" src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script type="text/javascript">
    $(document).ready(function () {

      $('#hospital').on('change', function () {
        var contract_id= 0;
        var hospital_id=$('#hospital').val();
        //window.location.href= redirectURL;
        var physician_id=$('#hospitalphysician_id').val();
        var basePath ="";
        var pathname = window.location.pathname;
        
        if (pathname.indexOf("practiceManager") >= 0){
            // Get the practice_mgr_id from url.
            var urlSplitArr = pathname.split("/");
            var practice_mgr_id = urlSplitArr[2];
            // alert(practice_mgr_id);
            // This will load the practice managers rejected logs page
            var current_url = basePath+'/practiceManager/'+practice_mgr_id+'/getRejected/'+contract_id+'/'+hospital_id;
            window.location.href=current_url;
        } else {
            // This will load the physicians rejected logs page
            var current_url = basePath+'/physician/'+physician_id+'/getRejected/'+contract_id+'/'+hospital_id;
            window.location.href=current_url;
        }
        
      });
    });

        //call-coverage-duration  by 1254
        function rangeSlide(min_val,max_val) {
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

        }
        //issue fixes for clear cache data  on rejected log: function added loadEditModalData by 1254 
    function loadEditModalData(id,action_id)
    {
       $('#enterLogMessageLog_'+id).hide();
       //document.getElementById('action_'+id).value = action_id;
       $('#action_'+id).val(action_id);
       //rangeSlide();
 //call-coverage-duration  by 1254 : get partial hours
       var payment_type = $("#payment_type_"+id).val();
        var partial_hours = $("#partial_hours_").val();
        var partial_hours_calculation = $("#partial_hours_calculation").val();
        if(payment_type == 7){
            $('#action_'+ id).attr('disabled', 'disabled');
        }
        if( (payment_type==3 || payment_type ==5) && partial_hours == 1) {
            $('#divShift_'+id).hide();
            rangeSlide(0.25, partial_hours_calculation);
        } else {
            rangeSlide(0.25, 24);
        }
        if(payment_type == 1 || payment_type == 2 || payment_type == 6){
            getTimeStampEntries(action_id);
        }else{
            $(".co_mgmt_med_direct").show();
            $(".time_stamp_edit_log").hide();
        }
        
    }
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == 'interactive') {
                $(".overlay").show();
            } else if (state == 'complete') {
                // setTimeout(function(){
                    document.getElementById('interactive');
                    $(".overlay").hide();
                // },2000);
                //rangeSlide();
                 //call-coverage-duration  by 1254
                var partial_hours = $("#partial_hours_").val();
                var partial_hours_calculation = $("#partial_hours_calculation").val(); //Change added to solve partial_hours_calculation issue by akash.
                if(partial_hours == 1) {
                    rangeSlide(0.25, partial_hours_calculation);
                }
                else {
                    rangeSlide(0.25, 24);
                }
            }
        }
        $('#contract').on('change', function() {
            var contract_id= this.value;
            var hospital_id=$('#hospital').val();
            //window.location.href= redirectURL;
            var physician_id=$('#hospitalphysician_id').val();
            var basePath ="";
            var pathname = window.location.pathname;
            if (pathname.indexOf("practiceManager") >= 0){
                // Get the practice_mgr_id from url.
                var urlSplitArr = pathname.split("/");
                var practice_mgr_id = urlSplitArr[2];
                // alert(practice_mgr_id);
                // This will load the practice managers rejected logs page
                var current_url = basePath+'/practiceManager/'+practice_mgr_id+'/getRejected/'+contract_id+'/'+hospital_id;
                window.location.href=current_url;
            } else {
                // This will load the physicians rejected logs page
                var current_url = basePath+'/physician/'+physician_id+'/getRejected/'+contract_id+'/'+hospital_id;
                window.location.href=current_url;
            }
        });
        function delete_log(log_id) {

            var basePath ="";
            var current_url = basePath + "/deleteLog/" + log_id;
            $.ajax({
                url: current_url
            }).done(function (response) {
               
                //issue fixes : added failer  if block for burden_on_call true by #1254
             
                if(response != "SUCCESS"){
                    $('#enterLogMessage').html(response);
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                    $('#enterLogMessage').show();

                    $('html,body').animate({scrollTop: 0}, '3000');

                    // // setTimeout(function () {
                    //     $('#enterLogMessage').hide();
                    // }, 500);
                }
                else{
                        $("#" + log_id).remove();
                        $("#" + log_id).remove();
                        $('#enterLogMessage').hide();

                } //end issue fixes : added failer if block for burden_on_call true by #1254




            }).error(function (e) {
            });
        }
        function action_change(log_id) {
            $('#enterLogMessage').hide();
            $('#enterLogMessageLog_'+log_id).hide();
            var action = $("#action_"+log_id).val();
            var contract_type = $("#contract_type_"+log_id).val();
            var payment_type = $("#payment_type_"+log_id).val();
            var on_call_duration = 0;
//            if(contract_type == 4){
            if(payment_type == 3){
                if(action == -1){
                    $("#custom_action_"+log_id).show();
                }else{
                    $("#custom_action_"+log_id).hide();
                    on_call_duration = $("#on_call_duration_"+action+"_"+log_id).val();
                    if(on_call_duration == 0.50){
                        $('#divShift_'+log_id+' input:radio[name=shift_'+log_id+']').attr('disabled', false);
                    }else{
                        $('#divShift_'+log_id+' input:radio[name=shift_'+log_id+']').attr('disabled', true);
                        $('#divShift_'+log_id+' input:radio[name=shift_'+log_id+']').attr('checked', false);
                    }
                }
            }else if(action == -1){
                $("#custom_action_"+log_id).show();
            }else{
                $("#custom_action_"+log_id).hide();
            }
            if(payment_type != 3 && payment_type != 5){
                getTimeStampEntries(action);
            }else{
                $(".co_mgmt_med_direct").show();
                $(".time_stamp_edit_log").hide();
            }
        }

        function getRejectedOverrideMandateDetails(action)
        {
            var selected_hospital_id = $('#hospital').val();
            var selected_action_id = action ; 
            override_mandate_details_flag = false;
            
            $("#hospitals_override_mandate_detail > option").each(function() {
                if($(this).val() == selected_hospital_id && $(this).html() == selected_action_id){
                    override_mandate_details_flag = true;
                }
			});
        }
		
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

        function getTimeStampEntries(action_id)
        {
            var selected_action_id = action_id;
            time_stamp_entry_flag = false;

            $("#hospital_time_stamp_entries > option").each(function() {
                if($(this).html() == selected_action_id){
                    time_stamp_entry_flag = true;
                }
			});
            
            if(time_stamp_entry_flag){
                $(".time_stamp_edit_log").show();
                $(".co_mgmt_med_direct").hide();
                $(".start_end_time_error_message").hide();
            }else{
                $(".co_mgmt_med_direct").show();
                $(".time_stamp_edit_log").hide();
                $(".start_end_time_error_message").hide();
            }
        }

        function validationTimeStampEntry(log_id){
            var current_date = new Date();
            var start_t = Date.parse(current_date.toLocaleDateString() +' '+ $('#start_time_rejected_edit_log_' + log_id).val());
            var end_t = Date.parse(current_date.toLocaleDateString() +' '+ $('#end_time_rejected_edit_log_' + log_id).val());

            if($('#start_time_rejected_edit_log_' + log_id).val() == "" && $('#end_time_rejected_edit_log_' + log_id).val() == ""){
                $('#enterLogMessageLog_' + log_id).html('Please enter start and end time.');
                $('#enterLogMessageLog_' + log_id).addClass("alert-danger");
                $('#enterLogMessageLog_' + log_id).show();
                return false;
            }else if($('#start_time_rejected_edit_log_' + log_id).val() == ""){
                $('#enterLogMessageLog_' + log_id).html('Please enter start time.');
                $('#enterLogMessageLog_' + log_id).addClass("alert-danger");
                $('#enterLogMessageLog_' + log_id).show();
                return false;
            }else if($('#end_time_rejected_edit_log_' + log_id).val() == ""){
                $('#enterLogMessageLog_' + log_id).html('Please enter end time.');
                $('#enterLogMessageLog_' + log_id).addClass("alert-danger");
                $('#enterLogMessageLog_' + log_id).show();
                return false;
            }else{
                if (start_t >= end_t){
                    $('#enterLogMessageLog_' + log_id).html('Start time should be less than end time.');
                    $('#enterLogMessageLog_' + log_id).addClass("alert-danger");
                    $('#enterLogMessageLog_' + log_id).show();
                    return false;
                } else {
                    $('#enterLogMessageLog_' + log_id).html('');
                    $('#enterLogMessageLog_' + log_id).removeClass("alert-danger");
                    $('#enterLogMessageLog_' + log_id).hide();
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

        function perUnitPaymentValidation(e, data){
            if ((event.keyCode >= 48 && event.keyCode <= 57) || 
                event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 ||
                event.keyCode == 39 || event.keyCode == 190) {

            } else {
                event.preventDefault();
            }
        }

        function reSubmit(log_id) {
            $('#enterLogMessage').hide();
            $('#enterLogMessageLog_'+log_id).hide();
            var basePath ="";
            var shift = 0;
            var on_call_duration = 0;
            var duration = 0;
            var mandate_details = false;
            var action = $("#action_"+log_id).val();
            var custom_action = "";
            var contract_type = $("#contract_type_"+log_id).val();
            var payment_type = $("#payment_type_"+log_id).val();

            getRejectedOverrideMandateDetails(action);

			//call-coverage-duration  by 1254 : get partial hours
            var partial_hours = $("#partial_hours_").val();
            if(payment_type == 8){
                action = $('#action_' + log_id).attr('action_id');
            }
            
//            if(contract_type == 4){
            if(payment_type == 3){
                on_call_duration = $("#on_call_duration_"+action+"_"+log_id).val();
                if(on_call_duration == 0.50){
                    if(partial_hours == 1)
                    {
                        $('#enterLogMessageLog_'+log_id).show();
                        $('#enterLogMessageLog_'+log_id).html("Can not edit half day activity for partial on contract.");
                        $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                        $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                        $('#enterLogMessageLog_'+log_id).focus();
                        return false;
                        
                      
                    }
                    if($('input[name=shift_'+log_id+']').is(':checked')) {
                        shift = $('input[name=shift_' + log_id + ']:checked').val();
                    }else{
                        $('#enterLogMessageLog_'+log_id).show();
                        $('#enterLogMessageLog_'+log_id).html("Please choose AM or PM shift.");
                        $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                        $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                        $('#enterLogMessageLog_'+log_id).focus();
                        return false;
                    }
                }
                 //call-coverage-duration  by 1254 : get duration for partial hours
                if(partial_hours ==1)
                {
                    var log_duration = $("#duration_"+log_id).val();
                    duration = log_duration;

                }else {
                    duration = on_call_duration;
                }
            }else if(payment_type == 5){
                if(partial_hours ==1){
                    duration = $("#duration_"+log_id).val();
                } else {
                    duration = 1;
                }
            }else {
                duration = $("#duration_"+log_id).val();
            }
            if(action == -1){
                custom_action = $("#custom_action_"+log_id).val();
            }
            var mandate = $("#mandate_"+log_id).val();
            var details = $("#log_details_"+log_id).val();
			
			if(payment_type == 7){
				details = "";
				if(duration == ""){
					$('#enterLogMessageLog_'+log_id).show();
                    $('#enterLogMessageLog_'+log_id).html("Please enter duration.");
                    $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                    $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                    $('#enterLogMessageLog_'+log_id).focus();
                    return false;
				}
			}
            var date = $("#date_"+log_id).val();
            var start_time = "";
            var end_time = "";

            if(payment_type == 1 || payment_type == 2 || payment_type == 6){
                if(time_stamp_entry_flag){
                    var check = validationTimeStampEntry(log_id);
                    if(!check){
                        return false;
                    }
                    else{
                        start = timeobject($('#start_time_rejected_edit_log_' + log_id).val());
                        end = timeobject($('#end_time_rejected_edit_log_' + log_id).val());
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
                        duration = (total_minutes / 60);
                        // duration = hours + '.' + minutes;
                        start_time = $('#start_time_rejected_edit_log_' + log_id).val();
                        end_time = $('#end_time_rejected_edit_log_' + log_id).val();
                    }
                }
            }

            var current_url = basePath+"/reSubmitLog";
            if(mandate == 1 && details.length < 1) {
                mandate_details = true;
            }
            if(!mandate_details || override_mandate_details_flag == true) {
                $.ajax({
                    type: "POST",
                    url: current_url,
                    data: {
                        log_id: log_id,
                        action: action,
                        date: date,
                        shift: shift,
                        duration: duration,
                        details: details,
                        contract_type: contract_type,
                        payment_type: payment_type,
                        custom_action: custom_action,
                        start_time: start_time,
                        end_time: end_time
                    }
                }).done(function (response) {
                    if(response.status == 1) {
                        $('#enterLogMessage').html(response.message);
                        $('#enterLogMessage').removeClass("alert-danger");
                        $('#enterLogMessage').addClass("alert-success");
                        $('#enterLogMessage').show();
                        $('.modal-backdrop').remove();
                        $('.default').removeClass("modal-open");
                        $("#modal-confirm-edit_" + log_id).hide();
                        $('#enterLogMessage').focus();
                        $("#" + log_id).remove();
                        $("#" + log_id).remove();
                    }else{
                        $('#enterLogMessageLog_'+log_id).show();
                        $('#enterLogMessageLog_'+log_id).html(response.message);
                        $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                        $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                        $('#enterLogMessageLog_'+log_id).focus();
                        return false;
                    }
                }).error(function (e) {
                });
            }else{
                $('#enterLogMessageLog_'+log_id).show();
                $('#enterLogMessageLog_'+log_id).html("Add log details.");
                $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                $('#enterLogMessageLog_'+log_id).focus();
                return false;
            }
        }
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    </script>
@endsection
