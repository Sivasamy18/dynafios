@php use function App\Start\is_physician; @endphp

<div class="panel panel-default">
    {{ Form::hidden('date_range', 0 , array('id' => 'date_range')) }}
    <select class="form-control hidden" id="hospitals_override_mandate_detail" name="hospitals_override_mandate_detail">
        @foreach($hospitals_override_mandate_details as $hospitals_override_mandate_detail)
            <option value="{{$hospitals_override_mandate_detail->hospital_id}}">{{ $hospitals_override_mandate_detail->action_id}}</option>
        @endforeach
    </select>

    <select class="form-control hidden" id="hospital_time_stamp_entries" name="hospital_time_stamp_entries">
        @foreach($hospital_time_stamp_entries as $hospital_time_stamp_entry)
            <option value="{{$hospital_time_stamp_entry->hospital_id}}">{{ $hospital_time_stamp_entry->action_id}}</option>
        @endforeach
    </select>

    <div class="panel-heading"
         @if(is_physician()) style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);" @endif>
        @if(is_physician())
            <b>@if($contract->payment_type_id == 4)
                    wRVU Approval
                @else
                    Log Approval
                @endif</b>
            @if(($annually_attestation_questions || $monthly_attestation_questions) && ($contract->contract_type_id == 20) && ($contract->state_attestations_annually || $contract->state_attestations_monthly))
                <a class="btn btn-primary" style="margin-top: -7px;" name="btnApprove" id="btnApprove"
                   href="{{ URL::route('attestations.physician', [$physician_id, $contract->id, '']) }}">
                    @if($contract->payment_type_id == 4)
                        Approve wRVUs
                    @else
                        {{Lang::get('physicians.title_text_approve_logs')}}
                    @endif
                </a>
            @else
                <a class="btn btn-primary" style="margin-top: -7px;" name="bntApprove" id="bntApprove"
                   href="{{ URL::route('physicians.signatureApprove', [$physician_id,$contract->id, '']) }}">
                    @if($contract->payment_type_id == 4)
                        Approve wRVUs
                    @else
                        {{Lang::get('physicians.title_text_approve_logs')}}
                    @endif
                </a>
            @endif
        @else
            <b>@if($contract->payment_type_id == 4)
                    wRVU Approval
                @else
                    Log Approval
                @endif</b>
            <!--button name="approve_logs" class="btn btn-primary" style="margin-top: -7px"
                    type="submit">
                {{Lang::get('physicians.title_text_approve_logs')}}
            </button-->
            <a class="btn btn-primary" style="margin-top: -7px;" name="bntApprove" id="bntApprove"
               href="{{ URL::route('physicians.signatureApprove', [$physician_id,$contract->id, '']) }}">
                @if($contract->payment_type_id == 4)
                    Approve wRVUs
                @else
                    {{Lang::get('physicians.title_text_approve_logs')}}
                @endif
            </a>
        @endif
    </div>
    <div class="panel-heading"
         @if(is_physician()) style="background-image: linear-gradient(to bottom,#F5F9FF 0,#F5F9FF 100%);" @endif>
        @if(is_physician())
            <!-- <b>Filter by Month:</b> -->
            <b>Filter by {{$payment_frequency_frequency}}:</b>
            <select class="form-control" id="dateSelector" name="dateSelector">

            </select>
        @else
            <!-- <b>Filter by Month:</b> -->
            <b>Filter by {{$payment_frequency_frequency}}:</b>
            <select class="form-control" id="dateSelector" name="dateSelector">

            </select>
        @endif
    </div>

    @if((count($hourly_summary) > 0) && ($contract->payment_type_id == 2))
        <div class="panel-body" style="height: 500px; overflow: scroll;overflow-x: hidden">
            <div class="approveLogs" id="approve_log">
                @foreach($hourly_summary as $hourly_summary)
                    <div class="col-xs-12"
                         style="padding-left: 0px; padding-right: 0px; background-color:black; color:white;">
                        <div class="col-xs-3 control-label" style="">Worked :
                            <span>{{$hourly_summary['worked_hours']}}</span></div>
                        <div class="col-xs-9 control-label" style="text-align:left;">Annual Remaining :
                            <span>{{$hourly_summary['annual_remaining']}}</span></div>
                    </div>
                    <div class="col-xs-12"
                         style="padding-left: 0px; padding-right: 0px; background-color:black; color:white;">
                        <div class="col-xs-3 control-label" style="">Remaining :
                            <span>{{$hourly_summary['remaining_hours']}}</span></div>
                    </div>

                    @foreach($results as $recent_log)
                            <?php $log_date = date('Y-m-d', strtotime($recent_log['date'])); ?>
                        @if( $log_date >= $hourly_summary['start_date'] && $log_date <= $hourly_summary['end_date'])
                            <input type="hidden" id="log_ids" name="approve_log_ids[]" value={{$recent_log['id']}}>
                            <div id="{{$recent_log['id']}}">
                                <div class="col-xs-10" style="padding-left: 0px;">
                                    <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4)
                                            wRVU Date:
                                        @else
                                            Log Date:
                                        @endif</div>
                                    <div class="col-xs-8 control-label_custom">{{$recent_log['date']}}</div>
                                    <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4)
                                            Activity Name:
                                        @else
                                            Action Name:
                                        @endif</div>
                                    <div class="col-xs-8 control-label_custom">@if($recent_log['action'] != "")
                                            {{$recent_log['action']}}
                                        @else
                                            -
                                        @endif</div>
                                    <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4 || $contract->payment_type_id == App\PaymentType::PER_UNIT)
                                            Units:
                                        @else
                                            Duration:
                                        @endif</div>
                                    <div class="col-xs-8 control-label_custom">@if($contract->payment_type_id == App\PaymentType::PER_UNIT)
                                            {{ round($recent_log['duration'], 0) }}
                                        @else
                                            {{(strpos($recent_log['duration'], 'Day') !== false)? str_replace('Day', '', $recent_log['duration']) : $recent_log['duration'] }}
                                        @endif
                                        @if( (($recent_log['partial_hours'] == 1) && ($recent_log['payment_type_id'] == 3  || $recent_log['payment_type_id'] == 5 )) || ($recent_log['partial_hours'] == 0) && ($recent_log['payment_type_id'] == 1  || $recent_log['payment_type_id'] == 2 || $recent_log['payment_type_id'] == 6 || $recent_log['payment_type_id'] == App\PaymentType::TIME_STUDY))
                                            <span>Hour(s)</span>
                                        @else
                                            @if($recent_log['duration'] == "AM" || $recent_log['duration'] == "PM" || $recent_log['payment_type_id'] == App\PaymentType::PER_UNIT)
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
                                    <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4)
                                            wRVU Details:
                                        @else
                                            Log Details:
                                        @endif</div>
                                    <div class="col-xs-8 control-label_custom"
                                         style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['note']!=""?$recent_log['note']:"-"}}</div>
                                </div>
                                <div class="col-xs-2">
                                    @if($contract->payment_type_id != 4 || !is_physician())
                                        <a
                                                id="{{$recent_log['id']}}"
                                                class="btn btn-default btn-delete log_delete_btn"
                                                style="margin-top: 60px"
                                                href=""
                                                data-toggle="modal" data-target="#modal-confirm-edit_approve"
                                                onclick="loadApproveEditModalData({{$recent_log['id']}});"

                                        >
                                            <i class="fa fa-pencil fa-fw"></i>
                                        </a>

                                        <a
                                                id="{{$recent_log['id']}}"
                                                class="btn btn-default btn-delete log_delete_btn"
                                                style="margin-top: 62px"
                                                href=""
                                                data-toggle="modal" data-target="#modal-confirm-delete"
                                                onclick="deleteModal($(this))"
                                                }}
                                        >
                                            <i class="fa fa-trash-o fa-fw"></i>
                                        </a>
                                    @endif
                                </div>
                                <div>&nbsp;</div>
                                <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                                    <hr style="width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                                </div>
                            </div>
                            <div id="modal-confirm-delete" class="modal fade">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">&times;
                                            </button>
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
                                                    id="modalDeleteLog"
                                                    onClick="">Delete
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /.modal-content -->
                                </div>
                            </div>

                            <div id="modal-confirm-edit_approve{{$recent_log['id']}}" class="modal fade">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                                                &times;
                                            </button>
                                            <h4 class="modal-title">Update Log</h4>

                                        </div>
                                        <div class="modal-body">
                                            <div class="panel-body">
                                                <div class="col-xs-12">
                                                    <div id="enterLogMessageLog_approve_{{$recent_log['id']}}"
                                                         class="alert" role="alert" style="display: none;">

                                                    </div>
                                                </div>

                                                <div class="col-xs-12 form-group">
                                                    <div class="col-xs-4 control-label">Action/Duty:</div>
                                                    <div class="col-xs-8">
                                                            <?php $action_status = 0;
                                                            $hideShift = 0;
                                                            ?>

                                                        <select class="form-control"
                                                                id="action_approve_{{$recent_log['id']}}" name="action"
                                                                onchange="action_change_approve({{$recent_log['id']}});">
                                                            @foreach($recent_log['actions'] as $actions)
                                                                @if($contract->mandate_details == 1 && !$actions['override_mandate'])
                                                                    <option style='font-weight:bold;'
                                                                            value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                        $action_status++;
                                                                    }
                                                                        if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                            $hideShift++;
                                                                        }
                                                                        ?>
                                                                @else
                                                                    <option value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                        $action_status++;
                                                                    }
                                                                        if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                            $hideShift++;
                                                                        }
                                                                        ?>
                                                                @endif
                                                            @endforeach
                                                            @if($recent_log['payment_type_id'] == 2)
                                                                @if($recent_log["custom_action_enabled"])
                                                                    @if($contract->mandate_details == 1)
                                                                        <option style='font-weight:bold;'
                                                                                value="-1" {{ $action_status != 0? "":"selected" }}>
                                                                            Custom Action
                                                                        </option>
                                                                    @else
                                                                        <option value="-1" {{ $action_status != 0? "":"selected" }}>
                                                                            Custom Action
                                                                        </option>
                                                                    @endif
                                                                @elseif($action_status == 0)
                                                                    <option value="-1" {{ $action_status != 0? "":"selected" }}>
                                                                        Custom Action
                                                                    </option>
                                                                    <script>
                                                                        $(document).ready(function () {
                                                                            $("#action_approve_{{$recent_log['id']}}").hide();
                                                                        });
                                                                    </script>
                                                                @endif
                                                            @endif
                                                        </select>
                                                        <input class="form-control" type="text"
                                                               id="custom_action_approve_{{$recent_log['id']}}"
                                                               name="custom_action_{{$recent_log['id']}}"
                                                               value="{{$recent_log["custom_action"]}}"
                                                               style="display: {{ $action_status != 0  && $recent_log["custom_action_enabled"]? "none;":"block;" }} ">
                                                    </div>
                                                </div>

                                                @if($recent_log['payment_type_id'] == 2)
                                                    <div class="col-xs-12 form-group co_mgmt_med_direct" id="slider">
                                                        <div class="col-xs-4 control-label">Duration: <br/>(Hours)
                                                            &nbsp;
                                                        </div>
                                                        <div class="col-xs-8">
                                                            <div class="rangeSliderDiv">
                                                                <input class="pull-left range_slider_approve"
                                                                       id="duration_approve_{{$recent_log['id']}}"
                                                                       type="range" step="0.25"
                                                                       value="{{$recent_log['duration']}}"
                                                                       data-rangeSlider>
                                                                <output class="pull-right range_slider_approve_output">{{$recent_log['duration']}}</output>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-xs-12 form-group time_stamp_approve">
                                                        <div class="col-xs-4 control-label">Start:</div>
                                                        <div id="start_timepicker_edit" class="col-xs-6 input-append">
                                                            <input id="start_time_approve_{{$recent_log['id']}}"
                                                                   name="start_time" class="form-control input-small"
                                                                   placeholder="Start Time" type="text"
                                                                   data-format="hh:mm"
                                                                   value="{{$recent_log['start_time']}}"
                                                                   autocomplete="off" style="width: 75%; float: left;">
                                                            <span class="form-control input-group-addon"
                                                                  style="width: 15%;"><i
                                                                        class="glyphicon glyphicon-time"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 form-group time_stamp_approve">
                                                        <div class="col-xs-4 control-label">End:</div>
                                                        <div id="end_timepicker_edit" class="col-xs-6 input-append">
                                                            <input id="end_time_approve_{{$recent_log['id']}}"
                                                                   name="end_time" class="form-control input-small"
                                                                   placeholder="End Time" type="text"
                                                                   data-format="hh:mm"
                                                                   value="{{$recent_log['end_time']}}"
                                                                   autocomplete="off" style="width: 75%; float: left;">
                                                            <span class="form-control input-group-addon"
                                                                  style="width: 15%;"><i
                                                                        class="glyphicon glyphicon-time"></i></span>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="col-xs-12 form-group">
                                                    <div class="col-xs-4 control-label">Date:</div>
                                                    <div class="col-xs-8">
                                                        <input lass="form-control" type="text" name="date"
                                                               id="date_approve_{{$recent_log['id']}}"
                                                               value="{{$recent_log['date']}}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 form-group">
                                                    <input type="hidden"
                                                           id="contract_type_approve_{{$recent_log['id']}}"
                                                           value="{{$recent_log['contract_type']}}">
                                                    <input type="hidden" id="payment_type_approve_{{$recent_log['id']}}"
                                                           value="{{$recent_log['payment_type_id']}}">
                                                    <input type="hidden" id="mandate_approve_{{$recent_log['id']}}"
                                                           value="{{$recent_log['mandate']}}">
                                                    <!-- call-coverage-duration  by 1254 : declare hidden field for partial hours -->
                                                    <input type="hidden" id="partial_hours_"
                                                           value="{{$recent_log['partial_hours']}}">
                                                    @if($recent_log['payment_type_id'] != 7)
                                                        <div class="col-xs-4 control-label">Log details:</div>
                                                        <div class="col-xs-8"><textarea name="log_details"
                                                                                        id="log_details_approve_{{$recent_log['id']}}"
                                                                                        class="form-control disable-resize">{{$recent_log['note']}}</textarea>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default"
                                                    data-dismiss="modal">
                                                Cancel
                                            </button>
                                            <button type="button" class="btn btn-primary"
                                                    onClick="reSubmitApprove({{$recent_log['id']}},{{$recent_log['log_physician_id']}});">
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    <div id="modal-confirm-edit_approve" class="modal fade">
                        <div class="modal-dialog">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="panel-body" style="height: 500px; overflow: scroll;overflow-x: hidden">
            <div class="approveLogs" id="approve_log">
                @foreach($results as $recent_log)
                    <input type="hidden" id="log_ids" name="approve_log_ids[]" value={{$recent_log['id']}}>
                    <div id="{{$recent_log['id']}}">
                        <div class="col-xs-10" style="padding-left: 0px;">
                            <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4)
                                    wRVU Date:
                                @else
                                    Log Date:
                                @endif</div>
                            <div class="col-xs-8 control-label_custom">{{$recent_log['date']}}</div>
                            <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4)
                                    Activity Name:
                                @else
                                    Action Name:
                                @endif</div>
                            <div class="col-xs-8 control-label_custom"
                                 style="word-wrap: break-word;">@if($recent_log['action'] != "")
                                    {{$recent_log['action']}}
                                @else
                                    -
                                @endif</div>

                            <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4 || $contract->payment_type_id == App\PaymentType::PER_UNIT)
                                    Units:
                                @else
                                    Duration:
                                @endif</div>
                            <div class="col-xs-8 control-label_custom">@if($contract->payment_type_id == App\PaymentType::PER_UNIT)
                                    {{ round($recent_log['duration'], 0) }}
                                @else
                                    {{(strpos($recent_log['duration'], 'Day') !== false)? str_replace('Day', '', $recent_log['duration']) : $recent_log['duration'] }}
                                @endif
                                @if( (($recent_log['partial_hours'] == 1) && ($recent_log['payment_type_id'] == 3  || $recent_log['payment_type_id'] == 5 )) || ($recent_log['partial_hours'] == 0) && ($recent_log['payment_type_id'] == 1  || $recent_log['payment_type_id'] == 2 || $recent_log['payment_type_id'] == 6 || $recent_log['payment_type_id'] == 7))
                                    <span>Hour(s)</span>
                                @else
                                    @if($recent_log['duration'] == "AM" || $recent_log['duration'] == "PM" || $recent_log['payment_type_id'] == App\PaymentType::PER_UNIT)
                                        <span> </span>
                                    @elseif($recent_log['payment_type_id'] == 9)
                                        <span>Hour(s)</span>
                                    @else
                                        <span>Day</span>
                                    @endif
                                @endif
                            </div>
                            <div class="col-xs-4 control-label">Entered By:</div>
                            <div class="col-xs-8 control-label_custom">{{$recent_log['enteredBy']!=""?$recent_log['enteredBy']:"Not Available"}}</div>
                            <div class="col-xs-4 control-label">Date Entered:</div>
                            <div class="col-xs-8 control-label_custom">{{$recent_log['created']}}</div>
                            <div class="col-xs-4 control-label">@if($contract->payment_type_id == 4)
                                    wRVU Details:
                                @else
                                    Log Details:
                                @endif</div>
                            <div class="col-xs-8 control-label_custom"
                                 style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['note']!=""?$recent_log['note']:"-"}}</div>
                        </div>
                        <div class="col-xs-2">
                            @if($contract->payment_type_id != 4 || !is_physician())
                                <a
                                        id="{{$recent_log['id']}}"
                                        class="btn btn-default btn-delete log_delete_btn"
                                        style="margin-top: 60px"
                                        href=""
                                        data-toggle="modal" data-target="#modal-confirm-edit_approve"
                                        onclick="loadApproveEditModalData({{$recent_log['id']}});"

                                >
                                    <i class="fa fa-pencil fa-fw"></i>
                                </a>

                                <a
                                        id="{{$recent_log['id']}}"
                                        class="btn btn-default btn-delete log_delete_btn"
                                        style="margin-top: 62px"
                                        href=""
                                        data-toggle="modal" data-target="#modal-confirm-delete"
                                        onclick="deleteModal($(this))"
                                        }}
                                >
                                    <i class="fa fa-trash-o fa-fw"></i>
                                </a>
                            @endif
                        </div>
                        <div>&nbsp;</div>
                        <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                            <hr style="width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                        </div>
                    </div>
                    <div id="modal-confirm-delete" class="modal fade">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal"
                                            aria-hidden="true">&times;
                                    </button>
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
                                            id="modalDeleteLog"
                                            onClick="">Delete
                                    </button>
                                </div>
                            </div>
                            <!-- /.modal-content -->
                        </div>
                    </div>

                    <div id="modal-confirm-edit_approve{{$recent_log['id']}}" class="modal fade">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                                        &times;
                                    </button>
                                    <h4 class="modal-title">Update Log</h4>

                                </div>
                                <div class="modal-body">
                                    <div class="panel-body">
                                        <div class="col-xs-12">
                                            <div id="enterLogMessageLog_approve_{{$recent_log['id']}}" class="alert"
                                                 role="alert" style="display: none;">

                                            </div>
                                        </div>

                                        <div class="col-xs-12 start_end_time_error_message" style="display: none">
                                            <div id="start_end_time_message_approve" class="alert" role="alert">
                                            </div>
                                        </div>

                                        <div class="col-xs-12 form-group">
                                            <div class="col-xs-4 control-label">Action/Duty:</div>
                                            <div class="col-xs-8">
                                                    <?php $action_status = 0;
                                                    $hideShift = 0;
                                                    ?>
                                                @if($recent_log['payment_type_id'] == App\PaymentType::PER_UNIT)
                                                    @foreach($recent_log['actions'] as $action)
                                                        {{ Form::label('action_approve_' . $recent_log['id'], Request::old('', $action['display_name']), array('class' => 'form-control', 'id' => 'action_approve_' . $recent_log['id'], 'action_id' => $action['id'], 'style' => 'text-overflow: ellipsis; white-space: nowrap; overflow: hidden;')) }}
                                                    @endforeach
                                                @else
                                                    <select class="form-control"
                                                            id="action_approve_{{$recent_log['id']}}" name="action"
                                                            onchange="action_change_approve({{$recent_log['id']}});">
                                                        @foreach($recent_log['actions'] as $actions)
                                                            @if($recent_log['payment_type_id'] == 1 || $recent_log['payment_type_id'] == 6)
                                                                @if($contract->mandate_details == 1 && !$actions['override_mandate'])
                                                                    <option style='font-weight:bold;'
                                                                            value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                        $action_status++;
                                                                    }
                                                                        if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                            $hideShift++;
                                                                        }
                                                                        ?>
                                                                @else
                                                                    <option value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                        $action_status++;
                                                                    }
                                                                        if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                            $hideShift++;
                                                                        }
                                                                        ?>
                                                                @endif
                                                            @else
                                                                <option value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                    <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                    $action_status++;
                                                                }
                                                                    if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                        $hideShift++;
                                                                    }
                                                                    ?>
                                                            @endif
                                                        @endforeach
                                                        @if($recent_log['payment_type_id'] != 3 && $recent_log['payment_type_id'] != 5 && $recent_log['payment_type_id'] != 7 && $recent_log['payment_type_id'] != 8)
                                                            @if($recent_log["custom_action_enabled"])
                                                                @if($contract->mandate_details == 1)
                                                                    <option style='font-weight:bold;'
                                                                            value="-1" {{ $action_status != 0? "":"selected" }}>
                                                                        Custom Action
                                                                    </option>
                                                                @else
                                                                    <option value="-1" {{ $action_status != 0? "":"selected" }}>
                                                                        Custom Action
                                                                    </option>
                                                                @endif
                                                            @elseif($action_status == 0)
                                                                <option value="-1" {{ $action_status != 0? "":"selected" }}>
                                                                    Custom Action
                                                                </option>
                                                                <script>
                                                                    $(document).ready(function () {
                                                                        $("#action_approve_{{$recent_log['id']}}").hide();
                                                                    });
                                                                </script>
                                                            @endif
                                                        @endif
                                                    </select>
                                                @endif

                                                @if($recent_log['payment_type_id'] == 3 || ($recent_log['payment_type_id'] == 5))
                                                    @foreach($recent_log['actions'] as $actions)
                                                        <input type="hidden"
                                                               name="on_call_duration_approve_{{$actions['id']}}_{{$recent_log['id']}}"
                                                               id="on_call_duration_approve_{{$actions['id']}}_{{$recent_log['id']}}"
                                                               value="{{$actions['duration']}}"/>
                                                    @endforeach
                                                @endif
                                                @if($recent_log['payment_type_id'] != App\PaymentType::TIME_STUDY && $recent_log['payment_type_id'] != App\PaymentType::PER_UNIT)
                                                    <input class="form-control" type="text"
                                                           id="custom_action_approve_{{$recent_log['id']}}"
                                                           name="custom_action_{{$recent_log['id']}}"
                                                           value="{{$recent_log["custom_action"]}}"
                                                           style="display: {{ $action_status != 0  && $recent_log["custom_action_enabled"]? "none;":"block;" }} ">
                                                @endif
                                            </div>
                                        </div>

                                        @if($recent_log['payment_type_id'] == 3 || $recent_log['payment_type_id'] == 5)
                                            @if($hideShift == 0 && $recent_log['payment_type_id'] != 5)
                                                <div class="col-xs-12 form-group"
                                                     id="divShift_approve_{{$recent_log['id']}}">
                                                    <div class="col-xs-4 control-label">Shift:</div>
                                                    <div class="col-xs-8">
                                                        <label class="radio-inline">
                                                            <input type="radio"
                                                                   name="shift_approve_{{$recent_log['id']}}"
                                                                   {{$recent_log['shift']==1 ? 'checked':''}} value="1" {{$recent_log['shift'] != 0 ? '':'disabled'}}>
                                                            AM
                                                        </label>
                                                        <label class="radio-inline">
                                                            <input type="radio"
                                                                   name="shift_approve_{{$recent_log['id']}}"
                                                                   {{$recent_log['shift']==2 ? 'checked':''}} value="2" {{$recent_log['shift'] != 0 ? '':'disabled'}}>
                                                            PM
                                                        </label>
                                                    </div>
                                                </div>
                                            @endif
                                            <!--call-coverage-duration  by 1254 : show duration for partial hours -->
                                            @if($recent_log['partial_hours'] == 1)
                                                <div class="col-xs-12 form-group co_mgmt_med_direct">
                                                    <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;
                                                    </div>
                                                    <div class="col-xs-8">
                                                        <div class="rangeSliderDiv">
                                                            <input class="pull-left range_slider_approve"
                                                                   id="duration_approve_{{$recent_log['id']}}"
                                                                   type="range" step="0.25"
                                                                   value="{{$recent_log['duration']}}" data-rangeSlider>
                                                            <output class="pull-right range_slider_approve_output">{{$recent_log['duration']}}</output>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                            <!-- // 6.1.14 Start-->
                                        @elseif($recent_log['payment_type_id'] == 8)
                                            <div class="col-xs-12 form-group per_unit_duration">
                                                <div class="col-xs-4 control-label">Number: &nbsp;</div>
                                                <div class="col-xs-4">
                                                    {{ Form::text('duration_approve_'. $recent_log['id'], Request::old('duration', round($recent_log['duration'], 0)), [ 'id' => 'duration_approve_' . $recent_log['id'], 'class' => 'form-control', 'maxlength' => 3, 'autocomplete' => "off", 'onkeypress' => "perUnitPaymentValidation(event, this)"]) }}
                                                </div>
                                            </div>
                                        @else
                                            @if($recent_log['payment_type_id'] == 7)
                                                <div class="col-xs-12 form-group co_mgmt_med_direct" id="log_slider">
                                                    <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;
                                                    </div>
                                                    <div class="col-xs-4">
                                                        <input id="duration_approve_{{$recent_log['id']}}"
                                                               value="{{$recent_log['duration']}}" class="form-control"
                                                               type="text" name="date"
                                                               onkeypress="timeStudyValidation(event, this)"
                                                               maxlength="5" autocomplete="off" placeholder="Hours"/>
                                                    </div>
                                                    <div class="col-xs-4"></div>
                                                </div>
                                            @else
                                                <div class="col-xs-12 form-group co_mgmt_med_direct" id="slider">
                                                    <div class="col-xs-4 control-label">Duration: <br/>(Hours) &nbsp;
                                                    </div>
                                                    <div class="col-xs-8">
                                                        <div class="rangeSliderDiv">
                                                            <input class="pull-left range_slider_approve"
                                                                   id="duration_approve_{{$recent_log['id']}}"
                                                                   type="range" step="0.25"
                                                                   value="{{$recent_log['duration']}}" data-rangeSlider>
                                                            <output class="pull-right range_slider_approve_output">{{$recent_log['duration']}}</output>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-xs-12 form-group time_stamp_approve">
                                                    <div class="col-xs-4 control-label">Start:</div>
                                                    <div id="start_timepicker_edit" class="col-xs-6 input-append">
                                                        <input id="start_time_approve_{{$recent_log['id']}}"
                                                               name="start_time" class="form-control input-small"
                                                               placeholder="Start Time" type="text"
                                                               data-format="hh:mm" value="{{$recent_log['start_time']}}"
                                                               autocomplete="off" style="width: 75%; float: left;">
                                                        <span class="form-control input-group-addon"
                                                              style="width: 15%;"><i
                                                                    class="glyphicon glyphicon-time"></i></span>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 form-group time_stamp_approve">
                                                    <div class="col-xs-4 control-label">End:</div>
                                                    <div id="end_timepicker_edit" class="col-xs-6 input-append">
                                                        <input id="end_time_approve_{{$recent_log['id']}}"
                                                               name="end_time" class="form-control input-small"
                                                               placeholder="End Time" type="text"
                                                               data-format="hh:mm" value="{{$recent_log['end_time']}}"
                                                               autocomplete="off" style="width: 75%; float: left;">
                                                        <span class="form-control input-group-addon"
                                                              style="width: 15%;"><i
                                                                    class="glyphicon glyphicon-time"></i></span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                        <div class="col-xs-12 form-group">
                                            <div class="col-xs-4 control-label">Date:</div>
                                            <div class="col-xs-8">
                                                <input class="form-control" type="text" name="date"
                                                       id="date_approve_{{$recent_log['id']}}"
                                                       value="{{$recent_log['date']}}" disabled/>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 form-group">
                                            <input type="hidden" id="contract_type_approve_{{$recent_log['id']}}"
                                                   value="{{$recent_log['contract_type']}}">
                                            <input type="hidden" id="payment_type_approve_{{$recent_log['id']}}"
                                                   value="{{$recent_log['payment_type_id']}}">
                                            <input type="hidden" id="mandate_approve_{{$recent_log['id']}}"
                                                   value="{{$recent_log['mandate']}}">
                                            <!-- call-coverage-duration  by 1254 : declare hidden field for partial hours -->
                                            <input type="hidden" id="partial_hours_"
                                                   value="{{$recent_log['partial_hours']}}">
                                            @if($recent_log['payment_type_id'] != 7)
                                                <div class="col-xs-4 control-label">Log details:</div>
                                                <div class="col-xs-8"><textarea name="log_details"
                                                                                id="log_details_approve_{{$recent_log['id']}}"
                                                                                class="form-control disable-resize">{{$recent_log['note']}}</textarea>
                                                </div>
                                            @endif
                                        </div>


                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default"
                                            data-dismiss="modal">
                                        Cancel
                                    </button>
                                    <button type="button" class="btn btn-primary"
                                            onClick="reSubmitApprove({{$recent_log['id']}},{{$recent_log['log_physician_id']}});">
                                        Submit
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                @endforeach
                <div id="modal-confirm-edit_approve" class="modal fade">
                    <div class="modal-dialog">
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

<script type="text/javascript" src="{{ asset('assets/js/rangeSlider.js') }}"></script>
<script type="text/javascript">
    sessionStorage.removeItem('annually_questions');
    sessionStorage.removeItem('monthly_questions');
    $(document).ready(function () {
        $('#dateSelector').change(function () {
            refreshApproveLogsView();
        });
    });

    function loadApproveEditModalData(id) {
        debugger;
        $('#modal-confirm-edit_approve' + id + ' .modal-dialog').find('.rangeSliderDiv').each(function () {
            $(this).find('.rangeSlider').each(function () {
                $(this).remove();
            });
        });
        var approve_panel_html = $('#modal-confirm-edit_approve' + id + ' .modal-dialog').html();
        $('#modal-confirm-edit_approve .modal-dialog').html(approve_panel_html);

        var partial_hours = $("#partial_hours_").val();
        if (partial_hours == 1) {
            $('#modal-confirm-edit_approve #divShift_' + id).hide();

            var partial_hours_calculation = $('#partial_hours_calculation').val();
            rangeSlide(0.25, partial_hours_calculation);
        } else {
            rangeSlide(0.25, 24);
        }
        //rangeSlide();
        var payment_type = $("#payment_type_approve_" + id).val();
        if (payment_type != 3 && payment_type != 5 && payment_type != 7) {
            getApproveTimeStampEntries();
        } else {
            $("#modal-confirm-edit_approve .co_mgmt_med_direct").show();
            $("#modal-confirm-edit_approve .time_stamp_approve").hide();
        }

        if (payment_type == 7) {
            $('#modal-confirm-edit_approve #action_approve_' + id).attr('disabled', 'disabled');
        }
    }

    //call-coverage-duration  by 1254

    function rangeSlide(min_val, max_val) {
        //printValue('slider1', 'rangeValue1');

        var selector = '[data-rangeSlider]',
            //var selector = 'input[type="range"]',
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

    /*    document.onreadystatechange = function () {
        var state = document.readyState;
        if (state == 'interactive') {
            $(".overlay").show();
        } else if (state == 'complete') {
            setTimeout(function(){
                document.getElementById('interactive');
                $(".overlay").hide();
            },2000);
            rangeSlide();
        }
    }*/
    //  rangeSlide();

    $('#contract').on('change', function () {
        var redirectURL = this.value;
        window.location.href = redirectURL;
    });

    function action_change_approve(log_id) {
        $('#enterLogMessage').hide();
        $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).hide();
        var action = $("#modal-confirm-edit_approve #action_approve_" + log_id).val();
        var contract_type = $("#modal-confirm-edit_approve #contract_type_approve_" + log_id).val();
        var payment_type = $("#modal-confirm-edit_approve #payment_type_approve_" + log_id).val();
        var on_call_duration = 0;

//            if(contract_type == 4){
        if (payment_type == 3 || payment_type == 5) {
            if (action == -1) {
                $("#modal-confirm-edit_approve #custom_action_approve_" + log_id).show();
            } else {
                $("#modal-confirm-edit_approve #custom_action_approve_" + log_id).hide();
                on_call_duration = $("#modal-confirm-edit_approve #on_call_duration_approve_" + action + "_" + log_id).val();
                if (on_call_duration == 0.50) {
                    $('#modal-confirm-edit_approve #divShift_approve_' + log_id + ' input:radio[name=shift_approve_' + log_id + ']').attr('disabled', false);
                } else {
                    $('#modal-confirm-edit_approve #divShift_approve_' + log_id + ' input:radio[name=shift_approve_' + log_id + ']').attr('disabled', true);
                    $('#modal-confirm-edit_approve #divShift_approve_' + log_id + ' input:radio[name=shift_approve_' + log_id + ']').attr('checked', false);
                }
            }
        } else if (action == -1) {
            $("#modal-confirm-edit_approve #custom_action_approve_" + log_id).show();
        } else {
            $("#modal-confirm-edit_approve #custom_action_approve_" + log_id).hide();
        }

        if (payment_type != 3 && payment_type != 5 && payment_type != 7) {
            getApproveTimeStampEntries();
        }
        // else{
        //     $("#modal-confirm-edit_approve .co_mgmt_med_direct").show();
        //     $("#modal-confirm-edit_approve .time_stamp_approve").hide();
        // }

    }

    function getApproveOverrideMandateDetails() {
        var selected_action_id = $('#modal-confirm-edit_approve').find('select[name=action]').val();
        override_mandate_details_flag = false;

        $("#hospitals_override_mandate_detail > option").each(function () {
            if ($(this).html() == selected_action_id) {
                override_mandate_details_flag = true;
            }
        });
    }

    function getApproveTimeStampEntries() {
        var selected_action_id = $('#modal-confirm-edit_approve').find('select[name=action]').val();
        time_stamp_entry_flag = false;

        $("#hospital_time_stamp_entries > option").each(function () {
            if ($(this).html() == selected_action_id) {
                time_stamp_entry_flag = true;
            }
        });

        if (time_stamp_entry_flag) {
            $("#modal-confirm-edit_approve .time_stamp_approve").show();
            $("#modal-confirm-edit_approve .co_mgmt_med_direct").hide();
        } else {
            $("#modal-confirm-edit_approve .co_mgmt_med_direct").show();
            $("#modal-confirm-edit_approve .time_stamp_approve").hide();
        }
    }

    function validationApproveTimeStampEntry(log_id) {
        var current_date = new Date();
        var start_t = Date.parse(current_date.toLocaleDateString() + ' ' + $('#modal-confirm-edit_approve #start_time_approve_' + log_id).val());
        var end_t = Date.parse(current_date.toLocaleDateString() + ' ' + $('#modal-confirm-edit_approve #end_time_approve_' + log_id).val());

        // $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-success");
        // $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-danger");
        // $('.start_end_time_error_message').hide();

        if ($('#modal-confirm-edit_approve #start_time_approve_' + log_id).val() == "" && $('#modal-confirm-edit_approve #end_time_approve_' + log_id).val() == "") {
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html('Please enter start and end time.');
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
            return false;
        } else if ($('#modal-confirm-edit_approve #start_time_approve_' + log_id).val() == "") {
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html('Please enter start time.');
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
            return false;
        } else if ($('#modal-confirm-edit_approve #end_time_approve_' + log_id).val() == "") {
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html('Please enter end time.');
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
            return false;
        } else {
            if (start_t >= end_t) {
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html('Start time should be less than end time.');
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
                return false;
            } else {
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html('');
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-danger");
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).hide();
                return true;
            }
        }
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

    function reSubmitApprove(log_id, physician_id) {
        $('#enterLogMessage').html("");
        $('#enterLogMessage').removeClass("alert-success");
        $('#enterLogMessage').removeClass("alert-danger");
        $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).hide();
        var basePath = "";
        var shift = 0;
        var on_call_duration = 0;
        var duration = 0;
        var mandate_details = false;
        var action = $("#modal-confirm-edit_approve #action_approve_" + log_id).val();
        var custom_action = "";

        var contract_type = $("#modal-confirm-edit_approve #contract_type_approve_" + log_id).val();
        var payment_type = $("#modal-confirm-edit_approve #payment_type_approve_" + log_id).val();
        //call-coverage-duration  by 1254 : get partial hours
        var partial_hours = $("#partial_hours_").val();
        if (payment_type == 8) {
            action = $('#modal-confirm-edit_approve #action_approve_' + log_id).attr('action_id');
        }
//            if(contract_type == 4){
        if (payment_type == 3 || payment_type == 5) {
            on_call_duration = $("#modal-confirm-edit_approve #on_call_duration_approve_" + action + "_" + log_id).val();
            if (on_call_duration == 0.50) {
                if (partial_hours == 1) {
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html("Can not edit half day activity for partial on contract.");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-success");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).focus();
                    setTimeout(function () {
                        //                            $('#enterLogMessageLog_'+log_id).hide();
                    }, 3000);
                    return false;
                }
                if ($('input[name=shift_approve_' + log_id + ']').is(':checked')) {
                    shift = $('input[name=shift_approve_' + log_id + ']:checked').val();
                } else {
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html("Please choose AM or PM shift.");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-success");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).focus();
                    setTimeout(function () {
                        //                            $('#enterLogMessageLog_'+log_id).hide();
                    }, 3000);
                    return false;
                }
            }
            //call-coverage-duration  by 1254 : get duration for partial hours
            if (partial_hours == 1) {
                var log_duration = $("#modal-confirm-edit_approve #duration_approve_" + log_id).val();
                duration = log_duration;
            } else {
                duration = on_call_duration;
            }
        } else {
            duration = $("#modal-confirm-edit_approve #duration_approve_" + log_id).val();
        }
        if (action == -1) {
            custom_action = $("#modal-confirm-edit_approve #custom_action_approve_" + log_id).val();
        }
        var mandate = $("#modal-confirm-edit_approve #mandate_approve_" + log_id).val();
        var details = $("#modal-confirm-edit_approve #log_details_approve_" + log_id).val();
        if (payment_type == 7) {
            details = "";
            if (duration == "") {
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html("Please enter duration.");
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-success");
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
                $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).focus();
                setTimeout(function () {
                }, 3000);
                return false;
            }
        }
        var date = $("#modal-confirm-edit_approve #date_approve_" + log_id).val();
        var physicianid = physician_id;
        var start_time = "";
        var end_time = "";

        if (payment_type != 3 && payment_type != 5 && payment_type != 7) {
            getApproveTimeStampEntries();

            if (time_stamp_entry_flag) {
                var check = validationApproveTimeStampEntry(log_id);
                if (!check) {
                    return false;
                } else {
                    start = timeobject($('#modal-confirm-edit_approve #start_time_approve_' + log_id).val());
                    end = timeobject($('#modal-confirm-edit_approve #end_time_approve_' + log_id).val());
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
                    duration = (total_minutes / 60);
                    // duration = hours + '.' + minutes;
                    start_time = $('#modal-confirm-edit_approve #start_time_approve_' + log_id).val();
                    end_time = $('#modal-confirm-edit_approve #end_time_approve_' + log_id).val();
                }
            }
        }

        getApproveOverrideMandateDetails();

        var current_url = basePath + "/reSubmitEditLog";
        if (mandate == 1 && details.length < 1) {
            mandate_details = true;
        }
        if (!mandate_details || override_mandate_details_flag == true) {
            $.ajax({
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: current_url,

                data: {
                    log_id: log_id,
                    action: action,
                    date: date,
                    shift: shift,
                    duration: duration,
                    details: details,
                    contract_type: contract_type,
                    custom_action: custom_action,
                    payment_type: payment_type,
                    start_time: start_time,
                    end_time: end_time
                }
            }).done(function (response) {
                if (response.status == 1) {
                    $('#enterLogMessage').html(response.message);
                    $('#enterLogMessage').removeClass("alert-danger");
                    $('#enterLogMessage').addClass("alert-success");
                    $('#enterLogMessage').show();
                    $('.modal-backdrop').remove();
                    $('.default').removeClass("modal-open");
                    $("#modal-confirm-edit_approve" + log_id).hide();
                    $('#enterLogMessage').focus();
                    //$("#" + log_id).remove();
                    //$("#" + log_id).remove();
                    setTimeout(function () {
//                            $('#enterLogMessage').hide();
                    }, 3000);
                    getContracts(physicianid);
                } else {
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html(response.message);
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-success");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
                    $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).focus();
                    setTimeout(function () {
//                            $('#enterLogMessageLog_'+log_id).hide();
                    }, 3000);
                    return false;
                }
            }).error(function (e) {
            });
        } else {
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).show();
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).html("Add log details.");
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).removeClass("alert-success");
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).addClass("alert-danger");
            $('#modal-confirm-edit_approve #enterLogMessageLog_approve_' + log_id).focus();
            setTimeout(function () {
//                    $('#enterLogMessageLog_'+log_id).hide();
            }, 3000);
            return false;
        }
    }
</script>
