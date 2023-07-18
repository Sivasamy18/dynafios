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
                    <a style="float: right; margin-top: -27px;  margin-right: 10px;" class="btn btn-primary"
                       href="{{ route('practices.onCallEntry', [$send_practice_id, $send_contract_id]) }}">
                        Log Entry / Approval
                    </a>
                </div>
            </div>

            <div class="panel-body">
                <div class="col-xs-12 form-group">
                    <div class="col-xs-5 control-label">Physician Name :</div>
                    <div class="col-xs-7 ">
                        <select style="width: 255px;" class="form-control" id="physician_name"
                                name="physician_name">
                            @foreach($physicians as $physician)
                                <option value="{{$physician['id']}}" {{ $physician['id'] == $physician_id ? "selected" : '' }}>{{$physician['name']}}</option>
                            @endforeach

                        </select>
                    </div>
                    <!-- <div class="col-xs-1"></div>-->
                </div>


                <div class="col-xs-12">
                    <div id="enterLogMessage" class="alert" role="alert" style="display:none;">

                    </div>
                </div>


                <div class="col-xs-12 no-side-margin-padding">

                        <!-- issue fixed : added new error div log delete on approve log for burden_on_call true by 1254 -->
                    <div class="col-xs-12" style="padding-top: 10px;">
                        <div id="log-error-delete-message" class="alert" role="alert" style="display: none;">

                        </div>
                    </div> <!-- end issue fixed : added new error div log delete on approve log for burden_on_call true by 1254 -->

                    <div class="panel panel-default" style="margin-left: 5px;">
                        <div class="panel-heading">
                            Rejected Logs
                        </div>
                        <div class="panel-body pre-scrollable_log_approve" id="recentLogs">
                            <!-- Recent Logs -->
                            <div>
                                @foreach($rejected_logs as $recent_log)
                                    <input type="hidden" id="log_ids" name="approve_log_ids[]" value={{$recent_log['id']}}>
                                    <div id="{{$recent_log['id']}}">
                                        <div class="col-xs-10" style="padding-left: 0px;">
                                            <div class="col-xs-4 control-label">Log Date:</div>
                                            <div class="col-xs-8 control-label_custom">{{$recent_log['date']}}</div>
                                            <div class="col-xs-4 control-label">Action Name:</div>
                                            <div class="col-xs-8 control-label_custom">@if($recent_log['action'] != "") {{$recent_log['action']}} @else - @endif</div>
                                            <div class="col-xs-4 control-label">Duration: </div>
                                            <div class="col-xs-8 control-label_custom">{{(strpos($recent_log['duration'], 'Day') !== false)? str_replace('Day', '', $recent_log['duration']) : $recent_log['duration'] }}
                                            @if( (($recent_log['partial_hours'] == 1) && ($recent_log['payment_type'] == 3  || $recent_log['payment_type'] == 5  )) || ($recent_log['partial_hours'] == 0) && ($recent_log['payment_type'] == 1  || $recent_log['payment_type'] == 2  ||  $recent_log['payment_type'] == 6))  
                                            <span>Hour(s)</span>   
                                            @else
                                                @if($recent_log['duration'] == "AM" || $recent_log['duration'] == "PM")
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
                                                    onclick=""
                                                    }}
                                            >
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
                                                            data-dismiss="modal">
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
                                                        <div class="col-xs-12 form-group">
                                                            <div class="col-xs-4 control-label">Action/Duty:</div>
                                                            <div class="col-xs-8">
                                                                <?php $action_status= 0; ?>
                                                                <select class="form-control" id="action_{{$recent_log['id']}}" name="action" onchange="action_change({{$recent_log['id']}});">
                                                                    @foreach($recent_log['actions'] as $actions)
                                                                        <option value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if($recent_log['action_id'] == $actions['id']){ $action_status++; } ?>
                                                                    @endforeach
                                                                        @if($recent_log['contract_type'] != 4)
                                                                            <option value="-1" {{ $action_status != 0? "":"selected" }}>Custom Action</option>
                                                                        @endif
                                                                </select>
                                                                @if($recent_log['contract_type'] == 4  || $recent_log['payment_type'] == 3)
                                                                    @foreach($recent_log['actions'] as $actions)
                                                                        <input type="hidden" name="on_call_duration_{{$actions['id']}}_{{$recent_log['id']}}" id="on_call_duration_{{$actions['id']}}_{{$recent_log['id']}}" value="{{$actions['duration']}}"/>
                                                                    @endforeach
                                                                @endif
                                                                <input class="form-control" type="text" id="custom_action_{{$recent_log['id']}}" name="custom_action_{{$recent_log['id']}}"
                                                                       value="{{$recent_log["custom_action"]}}" style="display: {{ $action_status != 0? "none":"block" }} ;">
                                                            </div>
                                                        </div>
                                                        @if($recent_log['contract_type'] == 4 || $recent_log['payment_type'] == 3 )
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
                                                        
                                                        @if($recent_log['payment_type'] == 3 || $recent_log['payment_type'] == 5)
                                                        <!-- Duration for add call duation and uncompensated payment type is added by akash -->
                                                            @if($recent_log['partial_hours'] == 1)
                                                                <script>
                                                                    var partial_hours_calculation =  "{{$recent_log['partial_hours_calculation']}}";
                                                                </script>
                                                                <div class="col-xs-12 form-group co_mgmt_med_direct" id="log_slider">
                                                                    <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                                                                    <div class="col-xs-8">
                                                                        <div class="rangeSliderDiv">
                                                                            <input class="pull-left" id="duration_{{$recent_log['id']}}" type="range" step="0.25" min="0.25"
                                                                                    value="{{$recent_log['duration']}}" data-rangeSlider>
                                                                            <output class="pull-right range_slider_recentlog_output">{{$recent_log['duration']}}</output>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="col-xs-12 form-group co_mgmt_med_direct">
                                                                <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;</div>
                                                                <div class="col-xs-8">
                                                                    <div class="rangeSliderDiv">
                                                                        <input class="pull-left" id="duration_{{$recent_log['id']}}" type="range" min="0.5" max="8" step="0.5"
                                                                               value="{{$recent_log['duration']}}" data-rangeSlider>
                                                                        <output class="pull-right">{{$recent_log['duration']}}</output>
                                                                    </div>
                                                                </div>

                                                            </div>
                                                        @endif
                                                        <div class="col-xs-12 form-group">
                                                            <div class="col-xs-4 control-label">Date: </div>
                                                            <div class="col-xs-8">
                                                                <input lass="form-control" type="text" name="date" id="date_{{$recent_log['id']}}" value="{{$recent_log['date']}}" disabled/>
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
                                                            <input type="hidden" id="mandate_{{$recent_log['id']}}"
                                                                   value="{{$recent_log['mandate']}}">
                                                            <!-- call-coverage-duration  by 1254 : declare hidden field for partial hours -->
                                                            <input type="hidden" id="partial_hours_"
                                                                   value="{{$recent_log['partial_hours']}}">
                                                            <input type="hidden" id="payment_type_{{$recent_log['id']}}"
                                                                   value="{{$recent_log['payment_type']}}">
                                                            <div class="col-xs-4 control-label">Log details:</div>
                                                            <div class="col-xs-8"><textarea name="log_details" id="log_details_{{$recent_log['id']}}"
                                                                                            class="form-control disable-resize">{{$recent_log['note']}}</textarea>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default"
                                                            data-dismiss="modal">
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
            </div>
        </div>
    </div>

    </div>
    {{ Form::close() }}
@endsection
@section('scripts')
    <script type="text/javascript" src="{{ asset('assets/js/rangeSlider.js') }}"></script>
    <script type="text/javascript">
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
                /*min: 0.5,
                max: 8,*/
                min:min_val,
                max:max_val,
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
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == 'interactive') {
                $(".overlay").show();
            } else if (state == 'complete') {
                setTimeout(function(){
                    document.getElementById('interactive');
                    $(".overlay").hide();
                },2000);
                //rangeSlide();
                //call-coverage-duration  by 1254 : get partial hours


                var partial_hours = $("#partial_hours_").val();
                if(partial_hours == 1) {
                    // $('#modal-confirm-edit #divShift_'+id).hide();
                    rangeSlide(0.25, partial_hours_calculation);
                }
                else {
                    rangeSlide(0.25, 24);
                }
            }
        }
        $('#physician_name').on('change', function() {
            var redirectURL= this.value;
            window.location.href= redirectURL;
        });
        function delete_log(log_id) {
            var basePath ="";
            var current_url = basePath + "/deleteLog/" + log_id;
            $.ajax({
                url: current_url
            }).done(function (response) {

                // $("#" + log_id).remove();
                // $("#" + log_id).remove();
                // $('#enterLogMessage').hide();
                  //issue fixes : added failer  if block for burden_on_call true by #1254

                  if(response != "SUCCESS"){
                    $('#enterLogMessage').html(response);
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                    $('#enterLogMessage').show();

                    $('html,body').animate({scrollTop: 0}, '3000');

                    setTimeout(function () {
                        $('#enterLogMessage').hide();
                    }, 4000);
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
            var on_call_duration = 0;
            if(contract_type == 4){
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
            var partial_hours = $("#partial_hours_").val();
            var payment_type = $("#payment_type_"+log_id).val();

            if(payment_type == 3){
                on_call_duration = $("#on_call_duration_"+action+"_"+log_id).val();
                console.log("on call duration",on_call_duration);
                if(on_call_duration == 0.50){
                    if(partial_hours == 1)
                    {
                        $('#enterLogMessageLog_'+log_id).show();
                        $('#enterLogMessageLog_'+log_id).html("Can not edit half day activity for partial on contract.");
                        $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                        $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                        $('#enterLogMessageLog_'+log_id).focus();
                        setTimeout(function () {
                            //$('#enterLogMessageLog_'+log_id).hide();
                        }, 3000);
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
                        setTimeout(function () {
                            //$('#enterLogMessageLog_'+log_id).hide();
                        }, 3000);
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
                    console.log("on call duration...",on_call_duration);
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
            //call-coverage-duration  by 1254 : get duration for partial hours
           // var partial_hours = $("#partial_hours_").val();

            var mandate = $("#mandate_"+log_id).val();
            var details = $("#log_details_"+log_id).val();
            var date = $("#date_"+log_id).val();
            var current_url = basePath+"/reSubmitLog";
            if(mandate == 1 && details.length < 1) {
                mandate_details = true;
            }
            if(!mandate_details) {
                console.log("duration",duration);
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
                        custom_action: custom_action
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
                        setTimeout(function () {
//                            $('#enterLogMessage').hide();
                        }, 3000);
                    }else{
                        $('#enterLogMessageLog_'+log_id).show();
                        $('#enterLogMessageLog_'+log_id).html(response.message);
                        $('#enterLogMessageLog_'+log_id).removeClass("alert-success");
                        $('#enterLogMessageLog_'+log_id).addClass("alert-danger");
                        $('#enterLogMessageLog_'+log_id).focus();
                        setTimeout(function () {
//                            $('#enterLogMessageLog_'+log_id).hide();
                        }, 3000);
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
                setTimeout(function () {
//                    $('#enterLogMessageLog_'+log_id).hide();
                }, 3000);
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
