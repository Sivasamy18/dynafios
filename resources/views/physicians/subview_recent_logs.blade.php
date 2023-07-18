@if($recent_logs_count>0)
    @if($hourly_summary_count > 0)
        @foreach($hourly_summary as $hourly_summary)
            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px; background-color:black; color:white;">
                <div class="col-xs-5 control-label" style="text-align:left;">Worked :
                    <span>{{$hourly_summary['worked_hours']}}</span></div>
                <div class="col-xs-7 control-label">Annual Remaining :
                    <span>{{$hourly_summary['annual_remaining']}}</span></div>
            </div>
            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px; background-color:black; color:white;">
                <div class="col-xs-6 control-label" style="text-align:left;">Remaining :
                    <span>{{$hourly_summary['remaining_hours']}}</span></div>
            </div>

            @foreach($recent_logs as $recent_log)
                <?php $log_date = date('Y-m-d', strtotime($recent_log['date'])); ?>
                @if( $log_date >= $hourly_summary['start_date'] && $log_date <= $hourly_summary['end_date'])
                    @if($recent_log['log_physician_id'] == $physician_id)
                        <input type="hidden" id="log_ids" name="log_ids[]" value={{$recent_log['id']}}>
                        <div id="{{$recent_log['id']}}">
                            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                                <div class="clear-both clearfix"></div>
                                <div class="col-xs-4 control-label">Log Date:</div>
                                <div class="col-xs-8 control-label_custom">{{$recent_log['date']}}</div>
                                <div class="col-xs-4 control-label">Action Name:</div>
                                <div class="col-xs-8 control-label_custom">@if($recent_log['action'] != "") {{$recent_log['action']}} @else
                                        - @endif</div>

                                @if($recent_log['payment_type_id'] == 8)
                                    <div class="col-xs-4 control-label">Units:</div>
                                    <div class="col-xs-8 control-label_custom">{{ round($recent_log['duration'], 0) }}
                                        @else
                                            <div class="col-xs-4 control-label">Duration:</div>
                                            <div class="col-xs-8 control-label_custom">{{(strpos($recent_log['duration'], 'Day') !== false)? str_replace('Day', '', $recent_log['duration']) : $recent_log['duration'] }}
                                                @endif

                                                @if( (($recent_log['partial_hours'] == 1) && ($recent_log['payment_type_id'] == 3  || $recent_log['payment_type_id'] == 5 )) || ($recent_log['partial_hours'] == 0) && ($recent_log['payment_type_id'] == 1  || $recent_log['payment_type_id'] == 2 ||  $recent_log['payment_type_id'] == 6 || $recent_log['payment_type_id'] == App\PaymentType::TIME_STUDY))
                                                    <span>Hour(s)</span>
                                                @else
                                                    @if($recent_log['duration'] == "AM" || $recent_log['duration'] == "PM" || $recent_log['payment_type_id'] == App\PaymentType::PER_UNIT)
                                                        <span> </span>
                                                    @else
                                                        <span>Day</span>
                                                    @endif
                                                @endif </div>



                                            <div class="col-xs-4 control-label">Entered By:</div>
                                            <div class="col-xs-8 control-label_custom">{{$recent_log['enteredBy']!=""?$recent_log['enteredBy']:"Not Available"}}</div>
                                            <div class="col-xs-4 control-label">Date Entered:</div>
                                            <div class="col-xs-8 control-label_custom">{{$recent_log['created']}}</div>
                                            <div class="col-xs-4 control-label">Log Details:</div>
                                            <div class="col-xs-8 control-label_custom"
                                                 style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['note']!=""?$recent_log['note']:"-"}}</div>
                                            <div class="control-label col-xs-12"></div>
                                            <div class="col-xs-4 control-label">Approved By:</div>
                                            <div class="col-xs-8 control-label_custom">{{$recent_log['approvedBy']!=""?$recent_log['approvedBy']:"-"}}</div>
                                            <div class="col-xs-8">

                                            </div>
                                            @if($recent_log['isSigned'] == true || $recent_log['approvedDate'] ==='Approved')
                                                <div class="col-xs-2">

                                                </div>
                                                <div class="col-xs-2">
                                                    <a
                                                            id="{{$recent_log['id']}}"
                                                            class="btn btn-default btn-error log_delete_btn float-right"
                                                            href=""
                                                            data-toggle="modal" data-target="#modal-error-delete"
                                                            style="padding-right: 0px; padding-left: 0px; margin-top: 20px"
                                                            }}
                                                    >
                                                        <i class="fa fa-trash-o fa-fw"></i>
                                                    </a>
                                                </div>
                                            @else
                                                @if($recent_log['isApproved']== false)
                                                    <div class="col-xs-2" style="padding-left:10px">
                                                        <a id="{{$recent_log['id']}}"
                                                           class="btn btn-default btn-delete log_delete_btn"
                                                           style="margin-top: 20px"
                                                           href=""
                                                           data-toggle="modal" data-target="#modal-confirm-edit"
                                                           onclick="loadEditModalData({{$recent_log['id']}});">
                                                            <i class="fa fa-pencil fa-fw"></i>
                                                        </a>
                                                    </div>
                                                @else
                                                    <div class="col-xs-2">

                                                    </div>
                                                @endif
                                                <div class="col-xs-2">
                                                    <a
                                                            id="{{$recent_log['id']}}"
                                                            class="btn btn-default btn-delete log_delete_btn float-right"
                                                            href=""
                                                            data-toggle="modal" data-target="#modal-confirm-delete"
                                                            onclick="deleteModal($(this))"
                                                            style="padding-right: 0px; padding-left: 0px; margin-top: 20px"
                                                            }}
                                                    >
                                                        <i class="fa fa-trash-o fa-fw"></i>
                                                    </a>
                                                </div>
                                            @endif
                                            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                                                <hr style="width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                                            </div>
                                    </div>


                            </div>
                            <div id="modal-confirm-delete" class="modal">
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
                            <div id="modal-error-delete" class="modal">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">&times;
                                            </button>
                                            <h4 class="modal-title">Delete unsuccesfull </h4>
                                        </div>
                                        <div class="modal-body">
                                            <p>Can not delete Approved Log</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary"
                                                    data-dismiss="modal">
                                                Ok
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /.modal-content -->
                                </div>
                            </div>

                            <div id="modal-confirm-edit_{{$recent_log['id']}}" class="modal fade recentLogModal">
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
                                                    <div id="enterLogMessageLog_{{$recent_log['id']}}" class="alert"
                                                         role="alert" style="display: none;">

                                                    </div>
                                                </div>

                                                <div class="col-xs-12 form-group">
                                                    <div class="col-xs-4 control-label">Action/Duty:</div>
                                                    <div class="col-xs-8">
                                                        <?php $action_status = 0;
                                                        $hideShift = 0; ?>

                                                        <select class="form-control" id="action_{{$recent_log['id']}}"
                                                                name="action"
                                                                onchange="action_change_recent({{$recent_log['id']}});">
                                                            @foreach($recent_log['actions'] as $actions)
                                                                @if($recent_log['payment_type_id'] == 2)
                                                                    @if($mandate_details && !$actions['override_mandate'])
                                                                        <option style='font-weight:bold;'
                                                                                id="{{$actions['id']}}"
                                                                                override_mandate="{{($actions['override_mandate']) ? '1' : '0'}}"
                                                                                time_stamp_entry="{{($actions['time_stamp_entry']) ? '1' : '0'}}"
                                                                                value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                            $action_status++;
                                                                        }
                                                                        if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                            $hideShift++;
                                                                        }
                                                                        ?>
                                                                    @else
                                                                        <option value="{{$actions['id']}}"
                                                                                id="{{$actions['id']}}"
                                                                                override_mandate="{{($actions['override_mandate']) ? '1' : '0'}}"
                                                                                time_stamp_entry="{{($actions['time_stamp_entry']) ? '1' : '0'}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                        <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                            $action_status++;
                                                                        }
                                                                        if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                            $hideShift++;
                                                                        }
                                                                        ?>
                                                                    @endif
                                                                @endif
                                                            @endforeach
                                                            @if($recent_log['payment_type_id'] == 2)
                                                                @if($recent_log["custom_action_enabled"])
                                                                    @if($mandate_details)
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
                                                                            $("#action_{{$recent_log['id']}}").hide();
                                                                        });
                                                                    </script>
                                                                @endif
                                                            @endif
                                                        </select>
                                                        <input class="form-control" type="text"
                                                               id="custom_action_{{$recent_log['id']}}"
                                                               name="custom_action_{{$recent_log['id']}}"
                                                               value="{{$recent_log["custom_action"]}}"
                                                               style="display: {{ $action_status != 0  && $recent_log["custom_action_enabled"]? "none;":"block;" }} ">
                                                    </div>
                                                </div>

                                                @if($recent_log['payment_type_id'] == 2)
                                                    <div class="col-xs-12 form-group co_mgmt_med_direct"
                                                         id="log_slider">
                                                        <div class="col-xs-4 control-label">Duration: <br/>(Hours)
                                                            &nbsp;
                                                        </div>
                                                        <div class="col-xs-8">
                                                            <div class="rangeSliderDiv">
                                                                <input class="pull-left"
                                                                       id="duration_{{$recent_log['id']}}" type="range"
                                                                       step="0.25"
                                                                       value="{{$recent_log['duration']}}"
                                                                       data-rangeSlider>
                                                                <output class="pull-right range_slider_recentlog_output">{{$recent_log['duration']}}</output>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Time stamp entry start-->
                                                    <div class="col-xs-12 form-group time_stamp_edit_log">
                                                        <div class="col-xs-4 control-label">Start:</div>
                                                        <div id="start_timepicker_edit" class="col-xs-6 input-append">
                                                            <input id="start_time_edit_log_{{$recent_log['id']}}"
                                                                   name="start_time_edit_log"
                                                                   class="form-control input-small col-xs-10"
                                                                   placeholder="Start Time" type="text"
                                                                   data-format="hh:mm"
                                                                   value="{{$recent_log['start_time']}}"
                                                                   autocomplete="off" style="width: 75%; float: left;">
                                                            <span class="form-control input-group-addon"
                                                                  style="width: 15%;"><i
                                                                        class="glyphicon glyphicon-time"></i></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-xs-12 form-group time_stamp_edit_log">
                                                        <div class="col-xs-4 control-label">End:</div>
                                                        <div id="end_timepicker_edit" class="col-xs-6 input-append">
                                                            <input id="end_time_edit_log_{{$recent_log['id']}}"
                                                                   name="end_time_edit_log"
                                                                   class="form-control input-small"
                                                                   placeholder="End Time" type="text"
                                                                   data-format="hh:mm"
                                                                   value="{{$recent_log['end_time']}}"
                                                                   autocomplete="off" style="width: 75%; float: left;">
                                                            <span class="form-control input-group-addon"
                                                                  style="width: 15%;"><i
                                                                        class="glyphicon glyphicon-time"></i></span>
                                                        </div>
                                                    </div>
                                                    <!-- Time stamp entry end-->
                                                @endif
                                                <div class="col-xs-12 form-group">
                                                    <div class="col-xs-4 control-label">Date:</div>
                                                    <div class="col-xs-8">
                                                        <input lass="form-control" type="text" name="date"
                                                               id="date_{{$recent_log['id']}}"
                                                               value="{{$recent_log['date']}}" disabled/>
                                                    </div>
                                                </div>
                                                <div class="col-xs-12 form-group">
                                                    <input type="hidden" id="contract_type_{{$recent_log['id']}}"
                                                           value="{{$recent_log['contract_type']}}">
                                                    <input type="hidden" id="payment_type_{{$recent_log['id']}}"
                                                           value="{{$recent_log['payment_type_id']}}">
                                                    <input type="hidden" id="mandate_{{$recent_log['id']}}"
                                                           value="{{$recent_log['mandate']}}">
                                                    <!-- call-coverage-duration  by 1254 : declare hidden field for partial hours -->
                                                    <input type="hidden" id="partial_hours_"
                                                           value="{{$recent_log['partial_hours']}}">
                                                    <input type="hidden" id="partial_hours_calculation"
                                                           value="{{$recent_log['partial_hours_calculation']}}">
                                                    <div class="col-xs-4 control-label">Log details:</div>
                                                    <div class="col-xs-8"><textarea name="log_details"
                                                                                    id="log_details_{{$recent_log['id']}}"
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
                                                    onClick="reSubmit({{$recent_log['id']}},{{$recent_log['log_physician_id']}});">
                                                Submit
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        @endif
                        @endif
                        @endforeach
                        @endforeach
                        @else
                            @foreach($recent_logs as $recent_log)
                                <!-- call-coverage-duration  by 1254  : condition for physician id is added to show only log for owner or current physician -->
                                    @if($recent_log['log_physician_id'] == $physician_id)
                                        <input type="hidden" id="log_ids" name="log_ids[]" value={{$recent_log['id']}}>
                                        <div id="{{$recent_log['id']}}">
                                            <div class="col-xs-12" style="padding-left: 0px; padding-right: 0px;">
                                                <div class="clear-both clearfix"></div>
                                                <div class="col-xs-4 control-label">Log Date:</div>
                                                <div class="col-xs-8 control-label_custom">{{$recent_log['date']}}</div>
                                                <div class="col-xs-4 control-label">Action Name:</div>
                                                <div class="col-xs-8 control-label_custom"
                                                     style="word-wrap: break-word;">@if($recent_log['action'] != "") {{$recent_log['action']}} @else
                                                        - @endif</div>
                                                @if($recent_log['payment_type_id'] == 8)
                                                    <div class="col-xs-4 control-label">Units:</div>
                                                    <div class="col-xs-8 control-label_custom">{{ round($recent_log['duration'], 0) }}
                                                        @else
                                                            <div class="col-xs-4 control-label">Duration:</div>
                                                            <div class="col-xs-8 control-label_custom">{{(strpos($recent_log['duration'], 'Day') !== false)? str_replace('Day', '', $recent_log['duration']) : $recent_log['duration'] }}
                                                                @endif

                                                                @if( (($recent_log['partial_hours'] == 1) && ($recent_log['payment_type_id'] == 3  || $recent_log['payment_type_id'] == 5 )) || ($recent_log['partial_hours'] == 0) && ($recent_log['payment_type_id'] == 1  || $recent_log['payment_type_id'] == 2 ||  $recent_log['payment_type_id'] == 6 || $recent_log['payment_type_id'] == 7))
                                                                    <span>Hour(s)</span>
                                                                @else
                                                                    @if($recent_log['duration'] == "AM" || $recent_log['duration'] == "PM" || $recent_log['payment_type_id'] == 8)
                                                                        <span> </span>
                                                                    @elseif($recent_log['payment_type_id'] == 9)
                                                                        <span>Hour(s)</span>
                                                                    @else
                                                                        <span>Day</span>
                                                                    @endif
                                                                @endif </div>



                                                            <div class="col-xs-4 control-label">Entered By:</div>
                                                            <div class="col-xs-8 control-label_custom">{{$recent_log['enteredBy']!=""?$recent_log['enteredBy']:"Not Available"}}</div>
                                                            <div class="col-xs-4 control-label">Date Entered:</div>
                                                            <div class="col-xs-8 control-label_custom">{{$recent_log['created']}}</div>
                                                            <div class="col-xs-4 control-label">Log Details:</div>
                                                            <div class="col-xs-8 control-label_custom"
                                                                 style="word-wrap: break-word; max-height: 100px; overflow: auto">{{$recent_log['note']!=""?$recent_log['note']:"-"}}</div>
                                                            <div class="control-label col-xs-12"></div>
                                                            <div class="col-xs-4 control-label">Approved By:</div>
                                                            <div class="col-xs-8 control-label_custom">{{$recent_log['approvedBy']!=""?$recent_log['approvedBy']:"-"}}</div>
                                                            <div class="col-xs-8">

                                                            </div>
                                                            @if($recent_log['isSigned'] == true || $recent_log['approvedDate'] ==='Approved')
                                                                <div class="col-xs-2">

                                                                </div>
                                                                <div class="col-xs-2">
                                                                    <a
                                                                            id="{{$recent_log['id']}}"
                                                                            class="btn btn-default btn-error log_delete_btn float-right"
                                                                            href=""
                                                                            data-toggle="modal"
                                                                            data-target="#modal-error-delete"
                                                                            style="padding-right: 0px; padding-left: 0px; margin-top: 20px"
                                                                            }}
                                                                    >
                                                                        <i class="fa fa-trash-o fa-fw"></i>
                                                                    </a>
                                                                </div>
                                                            @else
                                                                @if($recent_log['isApproved']== false)
                                                                    <div class="col-xs-2" style="padding-left:10px">
                                                                        <a id="{{$recent_log['id']}}"
                                                                           class="btn btn-default btn-delete log_delete_btn"
                                                                           style="margin-top: 20px"
                                                                           href=""
                                                                           data-toggle="modal"
                                                                           data-target="#modal-confirm-edit"
                                                                           onclick="loadEditModalData({{$recent_log['id']}});">
                                                                            <i class="fa fa-pencil fa-fw"></i>
                                                                        </a>
                                                                    </div>
                                                                @else
                                                                    <div class="col-xs-2">

                                                                    </div>
                                                                @endif
                                                                <div class="col-xs-2">
                                                                    <a
                                                                            id="{{$recent_log['id']}}"
                                                                            class="btn btn-default btn-delete log_delete_btn float-right"
                                                                            href=""
                                                                            data-toggle="modal"
                                                                            data-target="#modal-confirm-delete"
                                                                            onclick="deleteModal($(this))"
                                                                            style="padding-right: 0px; padding-left: 0px; margin-top: 20px"
                                                                            }}
                                                                    >
                                                                        <i class="fa fa-trash-o fa-fw"></i>
                                                                    </a>
                                                                </div>
                                                            @endif
                                                            <div class="col-xs-12"
                                                                 style="padding-left: 0px; padding-right: 0px;">
                                                                <hr style="width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                                                            </div>
                                                    </div>


                                            </div>
                                            <div id="modal-confirm-delete" class="modal">
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
                                                            <button type="button" class="btn btn-primary"
                                                                    data-dismiss="modal" id="modalDeleteLog"
                                                                    onClick="">Delete
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- /.modal-content -->
                                                </div>
                                            </div>
                                            <div id="modal-error-delete" class="modal">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                    aria-hidden="true">&times;
                                                            </button>
                                                            <h4 class="modal-title">Delete unsuccesfull </h4>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Can not delete Approved Log</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-primary"
                                                                    data-dismiss="modal">
                                                                Ok
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <!-- /.modal-content -->
                                                </div>
                                            </div>

                                            <div id="modal-confirm-edit_{{$recent_log['id']}}"
                                                 class="modal fade recentLogModal">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                    aria-hidden="true">&times;
                                                            </button>
                                                            <h4 class="modal-title">Update Log</h4>

                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="panel-body">
                                                                <div class="col-xs-12">
                                                                    <div id="enterLogMessageLog_{{$recent_log['id']}}"
                                                                         class="alert" role="alert"
                                                                         style="display: none;">

                                                                    </div>
                                                                </div>

                                                                <div class="col-xs-12 form-group">
                                                                    <div class="col-xs-4 control-label">Action/Duty:
                                                                    </div>
                                                                    <div class="col-xs-8">
                                                                        <?php $action_status = 0;
                                                                        $hideShift = 0; ?>
                                                                        @if($recent_log['payment_type_id'] == App\PaymentType::PER_UNIT)
                                                                            @foreach($recent_log['actions'] as $action)
                                                                                {{ Form::label('action_' . $recent_log['id'], Request::old('', $action['display_name']), array('class' => 'form-control', 'id' => 'action_' . $recent_log['id'], 'action_id' => $action['id'], 'style' => 'text-overflow: ellipsis; white-space: nowrap; overflow: hidden;')) }}
                                                                            @endforeach
                                                                        @else
                                                                            <select class="form-control"
                                                                                    id="action_{{$recent_log['id']}}"
                                                                                    name="action"
                                                                                    onchange="action_change_recent({{$recent_log['id']}});">
                                                                                @foreach($recent_log['actions'] as $actions)
                                                                                    @if($recent_log['payment_type_id'] == 1 || $recent_log['payment_type_id'] == 6)
                                                                                        @if($mandate_details && !$actions['override_mandate'])
                                                                                            <option style='font-weight:bold;'
                                                                                                    id="{{$actions['id']}}"
                                                                                                    override_mandate="{{($actions['override_mandate']) ? '1' : '0'}}"
                                                                                                    time_stamp_entry="{{($actions['time_stamp_entry']) ? '1' : '0'}}"
                                                                                                    value="{{$actions['id']}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
                                                                                            <?php if ($recent_log['action_id'] == $actions['id']) {
                                                                                                $action_status++;
                                                                                            }
                                                                                            if ($actions['name'] == 'On-Call' || $actions['name'] == 'Called-Back' || $actions['name'] == 'Called-In') {
                                                                                                $hideShift++;
                                                                                            }
                                                                                            ?>
                                                                                        @else
                                                                                            <option value="{{$actions['id']}}"
                                                                                                    id="{{$actions['id']}}"
                                                                                                    override_mandate="{{($actions['override_mandate']) ? '1' : '0'}}"
                                                                                                    time_stamp_entry="{{($actions['time_stamp_entry']) ? '1' : '0'}}" {{$recent_log['action_id'] == $actions['id']?"selected":""}}>{{$actions['display_name']}}</option>
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
                                                                                        @if($mandate_details)
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
                                                                                                $("#action_{{$recent_log['id']}}").hide();
                                                                                            });
                                                                                        </script>
                                                                                    @endif
                                                                                @endif
                                                                            </select>
                                                                        @endif

                                                                        @if($recent_log['payment_type_id'] == 3 || $recent_log['payment_type_id'] == 5)
                                                                            @foreach($recent_log['actions'] as $actions)
                                                                                <input type="hidden"
                                                                                       name="on_call_duration_{{$actions['id']}}_{{$recent_log['id']}}"
                                                                                       id="on_call_duration_{{$actions['id']}}_{{$recent_log['id']}}"
                                                                                       value="{{$actions['duration']}}"/>
                                                                            @endforeach
                                                                        @endif

                                                                        @if($recent_log['payment_type_id'] != App\PaymentType::TIME_STUDY && $recent_log['payment_type_id'] != App\PaymentType::PER_UNIT)
                                                                            <input class="form-control" type="text"
                                                                                   id="custom_action_{{$recent_log['id']}}"
                                                                                   name="custom_action_{{$recent_log['id']}}"
                                                                                   value="{{$recent_log["custom_action"]}}"
                                                                                   style="display: {{ $action_status != 0  && $recent_log["custom_action_enabled"]? "none;":"block;" }} ">
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                @if($recent_log['payment_type_id'] == 3 || $recent_log['payment_type_id'] == 5)
                                                                    @if($hideShift == 0)
                                                                        <div class="col-xs-12 form-group"
                                                                             id="divShift_{{$recent_log['id']}}">
                                                                            <div class="col-xs-4 control-label">Shift:
                                                                            </div>
                                                                            <div class="col-xs-8">
                                                                                <label class="radio-inline">
                                                                                    <input type="radio"
                                                                                           name="shift_{{$recent_log['id']}}"
                                                                                           {{$recent_log['shift']==1 ? 'checked':''}} value="1" {{$recent_log['shift'] != 0 ? '':'disabled'}}>
                                                                                    AM
                                                                                </label>
                                                                                <label class="radio-inline">
                                                                                    <input type="radio"
                                                                                           name="shift_{{$recent_log['id']}}"
                                                                                           {{$recent_log['shift']==2 ? 'checked':''}} value="2" {{$recent_log['shift'] != 0 ? '':'disabled'}}>
                                                                                    PM
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                <!--call-coverage-duration  by 1254 : show duration for partial hours -->
                                                                    @if($recent_log['partial_hours'] == 1)
                                                                        <div class="col-xs-12 form-group co_mgmt_med_direct"
                                                                             id="log_slider">
                                                                            <div class="col-xs-4 control-label">
                                                                                Duration: <br/>(Hours) &nbsp;
                                                                            </div>
                                                                            <div class="col-xs-8">
                                                                                <div class="rangeSliderDiv">
                                                                                    <input class="pull-left"
                                                                                           id="duration_{{$recent_log['id']}}"
                                                                                           type="range" step="0.25"
                                                                                           value="{{$recent_log['duration']}}"
                                                                                           data-rangeSlider>
                                                                                    <output class="pull-right range_slider_recentlog_output">{{$recent_log['duration']}}</output>
                                                                                </div>
                                                                            </div>

                                                                        </div>
                                                                    @endif
                                                                <!-- // 6.1.14 Start-->
                                                                @elseif($recent_log['payment_type_id'] == App\PaymentType::PER_UNIT)
                                                                    <div class="col-xs-12 form-group per_unit_duration">
                                                                        <div class="col-xs-4 control-label">Number:
                                                                            &nbsp;
                                                                        </div>
                                                                        <div class="col-xs-4">
                                                                            {{ Form::text('duration_'. $recent_log['id'], Request::old('duration', round($recent_log['duration'], 0)), [ 'id' => 'duration_' . $recent_log['id'], 'class' => 'form-control', 'maxlength' => 3, 'autocomplete' => "off", 'onkeypress' => "perUnitPaymentValidation(event, this)"]) }}
                                                                        </div>
                                                                    </div>
                                                                @else
                                                                    @if($recent_log['payment_type_id'] == 7)
                                                                        <div class="col-xs-12 form-group co_mgmt_med_direct"
                                                                             id="log_slider">
                                                                            <div class="col-xs-4 control-label">
                                                                                Duration: <br/>(Hours) &nbsp;
                                                                            </div>
                                                                            <div class="col-xs-4">
                                                                                <input id="duration_{{$recent_log['id']}}"
                                                                                       value="{{$recent_log['duration']}}"
                                                                                       class="form-control" type="text"
                                                                                       name="date"
                                                                                       onkeypress="timeStudyValidation(event, this)"
                                                                                       maxlength="5" autocomplete="off"
                                                                                       placeholder="Hours"/>
                                                                            </div>
                                                                            <div class="col-xs-4"></div>
                                                                        </div>
                                                                    @else
                                                                        <div class="col-xs-12 form-group co_mgmt_med_direct"
                                                                             id="log_slider">
                                                                            <div class="col-xs-4 control-label">
                                                                                Duration: <br/>(Hours) &nbsp;
                                                                            </div>
                                                                            <div class="col-xs-8">
                                                                                <div class="rangeSliderDiv">
                                                                                    <input class="pull-left"
                                                                                           id="duration_{{$recent_log['id']}}"
                                                                                           type="range" step="0.25"
                                                                                           value="{{$recent_log['duration']}}"
                                                                                           data-rangeSlider>
                                                                                    <output class="pull-right range_slider_recentlog_output">{{$recent_log['duration']}}</output>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <!-- Time stamp entry start-->
                                                                        <div class="col-xs-12 form-group time_stamp_edit_log">
                                                                            <div class="col-xs-4 control-label">Start:
                                                                            </div>
                                                                            <div id="start_timepicker_edit"
                                                                                 class="col-xs-6 input-append">
                                                                                <input id="start_time_edit_log_{{$recent_log['id']}}"
                                                                                       name="start_time_edit_log"
                                                                                       class="form-control input-small col-xs-10"
                                                                                       placeholder="Start Time"
                                                                                       type="text"
                                                                                       data-format="hh:mm"
                                                                                       value="{{$recent_log['start_time']}}"
                                                                                       autocomplete="off"
                                                                                       style="width: 75%; float: left;">
                                                                                <span class="form-control input-group-addon"
                                                                                      style="width: 15%;"><i
                                                                                            class="glyphicon glyphicon-time"></i></span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-xs-12 form-group time_stamp_edit_log">
                                                                            <div class="col-xs-4 control-label">End:
                                                                            </div>
                                                                            <div id="end_timepicker_edit"
                                                                                 class="col-xs-6 input-append">
                                                                                <input id="end_time_edit_log_{{$recent_log['id']}}"
                                                                                       name="end_time_edit_log"
                                                                                       class="form-control input-small"
                                                                                       placeholder="End Time"
                                                                                       type="text"
                                                                                       data-format="hh:mm"
                                                                                       value="{{$recent_log['end_time']}}"
                                                                                       autocomplete="off"
                                                                                       style="width: 75%; float: left;">
                                                                                <span class="form-control input-group-addon"
                                                                                      style="width: 15%;"><i
                                                                                            class="glyphicon glyphicon-time"></i></span>
                                                                            </div>
                                                                        </div>
                                                                        <!-- Time stamp entry end-->
                                                                    @endif
                                                                @endif

                                                                <div class="col-xs-12 form-group">
                                                                    <div class="col-xs-4 control-label">Date:</div>
                                                                    <div class="col-xs-4">
                                                                        <input class="form-control" type="text"
                                                                               name="date"
                                                                               id="date_{{$recent_log['id']}}"
                                                                               value="{{$recent_log['date']}}"
                                                                               disabled/>
                                                                    </div>
                                                                </div>
                                                                <div class="col-xs-12 form-group">
                                                                    <input type="hidden"
                                                                           id="contract_type_{{$recent_log['id']}}"
                                                                           value="{{$recent_log['contract_type']}}">
                                                                    <input type="hidden"
                                                                           id="payment_type_{{$recent_log['id']}}"
                                                                           value="{{$recent_log['payment_type_id']}}">
                                                                    <input type="hidden"
                                                                           id="mandate_{{$recent_log['id']}}"
                                                                           value="{{$recent_log['mandate']}}">
                                                                    <!-- call-coverage-duration  by 1254 : declare hidden field for partial hours -->
                                                                    <input type="hidden" id="partial_hours_"
                                                                           value="{{$recent_log['partial_hours']}}">
                                                                    <input type="hidden" id="partial_hours_calculation"
                                                                           value="{{$recent_log['partial_hours_calculation']}}">

                                                                    @if($recent_log['payment_type_id'] != 7)
                                                                        <div class="col-xs-4 control-label">Log
                                                                            details:
                                                                        </div>
                                                                        <div class="col-xs-8"><textarea
                                                                                    name="log_details"
                                                                                    id="log_details_{{$recent_log['id']}}"
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
                                                                    onClick="reSubmit({{$recent_log['id']}},{{$recent_log['log_physician_id']}});">
                                                                Submit
                                                            </button>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                            @endforeach
                                            @endif
                                            @else
                                                <div>{{Lang::get('practices.noRecentLogs')}}</div>
                                            @endif

                                            <div id="modal-confirm-edit" class="modal fade">
                                                <div class="modal-dialog">
                                                </div>
                                            </div>

                                            <script type="text/javascript"
                                                    src="{{ asset('assets/js/rangeSlider.js') }}"></script>
                                            <script type="text/javascript">

                                                function loadEditModalData(id) {
                                                    $('#modal-confirm-edit_' + id + ' .modal-dialog').find('.rangeSliderDiv').each(function () {
                                                        $(this).find('.rangeSlider').each(function () {
                                                            $(this).remove();
                                                        });
                                                    });
                                                    var panel_html = $('#modal-confirm-edit_' + id + ' .modal-dialog').html();
                                                    $('#modal-confirm-edit .modal-dialog').html(panel_html);
                                                    var partial_hours = $("#partial_hours_").val();

                                                    if (partial_hours == 1) {
                                                        $('#modal-confirm-edit #divShift_' + id).hide();
                                                        var partial_hours_calculation = $('#partial_hours_calculation').val();
                                                        rangeSlide(0.25, partial_hours_calculation);
                                                    } else {
                                                        rangeSlide(0.25, 24);
                                                    }
                                                    //rangeSlide();
                                                    var payment_type = $("#modal-confirm-edit #payment_type_" + id).val();
                                                    if (payment_type != 3 && payment_type != 5 && payment_type != 7) {
                                                        getRecentLogsTimeStampEntries();
                                                    } else {
                                                        $("#modal-confirm-edit .co_mgmt_med_direct").show();
                                                        $("#modal-confirm-edit .time_stamp_edit_log").hide();
                                                    }

                                                    if (payment_type == 7) {
                                                        $('#modal-confirm-edit #action_' + id).attr('disabled', 'disabled');
                                                    }

                                                }

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
                                                      alert('here');
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

                                                $('#contract').on('change', function () {
                                                    var redirectURL = this.value;
                                                    window.location.href = redirectURL;
                                                });

                                                function action_change_recent(log_id) {
                                                    // $('#enterLogMessage').hide();
                                                    $('#modal-confirm-edit #enterLogMessageLog_' + log_id).hide();
                                                    var action = $("#modal-confirm-edit #action_" + log_id).val();
                                                    var contract_type = $("#modal-confirm-edit #contract_type_" + log_id).val();
                                                    var payment_type = $("#modal-confirm-edit #payment_type_" + log_id).val();
                                                    var on_call_duration = 0;
//            if(contract_type == 4){
                                                    if (payment_type == 3 || payment_type == 5) {
                                                        if (action == -1) {
                                                            $("#modal-confirm-edit #custom_action_" + log_id).show();
                                                        } else {
                                                            $("#modal-confirm-edit #custom_action_" + log_id).hide();
                                                            on_call_duration = $("#modal-confirm-edit #on_call_duration_" + action + "_" + log_id).val();
                                                            if (on_call_duration == 0.50) {
                                                                $('#modal-confirm-edit #divShift_' + log_id + ' input:radio[name=shift_' + log_id + ']').attr('disabled', false);
                                                            } else {
                                                                $('#modal-confirm-edit #divShift_' + log_id + ' input:radio[name=shift_' + log_id + ']').attr('disabled', true);
                                                                $('#modal-confirm-edit #divShift_' + log_id + ' input:radio[name=shift_' + log_id + ']').attr('checked', false);
                                                            }
                                                        }
                                                    } else if (action == -1) {
                                                        $("#modal-confirm-edit #custom_action_" + log_id).show();
                                                    } else {
                                                        $("#modal-confirm-edit #custom_action_" + log_id).hide();
                                                    }

                                                    if (payment_type != 3 && payment_type != 5 && payment_type != 7) {
                                                        getRecentLogsTimeStampEntries();
                                                    }
                                                }

                                                function getRecentOverrideMandateDetails() {
                                                    var selected_action_id = $('#modal-confirm-edit').find('select[name=action]').val();
                                                    var override_mandate = $('#' + selected_action_id).attr('override_mandate');
                                                    if (override_mandate == undefined) {
                                                        override_mandate = false;
                                                    }
                                                    override_mandate_details_flag = JSON.parse(override_mandate);
                                                }

                                                function getRecentLogsTimeStampEntries() {
                                                    var selected_action_id = $('#modal-confirm-edit').find('select[name=action]').val();
                                                    var time_stamp_entry = $('#' + selected_action_id).attr('time_stamp_entry');
                                                    if (time_stamp_entry == undefined) {
                                                        time_stamp_entry = false;
                                                    }
                                                    time_stamp_entry_flag = JSON.parse(time_stamp_entry);

                                                    if (time_stamp_entry_flag) {
                                                        $("#modal-confirm-edit .time_stamp_edit_log").show();
                                                        $("#modal-confirm-edit .co_mgmt_med_direct").hide();
                                                        $("#modal-confirm-edit .start_end_time_error_message").hide();
                                                    } else {
                                                        $("#modal-confirm-edit .co_mgmt_med_direct").show();
                                                        $("#modal-confirm-edit .co_mgmt_med_direct").removeClass('hidden');
                                                        $("#modal-confirm-edit .time_stamp_edit_log").hide();
                                                        $("#modal-confirm-edit .start_end_time_error_message").hide();
                                                    }
                                                }

                                                function validationTimeStampEntry(log_id) {
                                                    var current_date = new Date();
                                                    var start_t = Date.parse(current_date.toLocaleDateString() + ' ' + $('#modal-confirm-edit #start_time_edit_log_' + log_id).val());
                                                    var end_t = Date.parse(current_date.toLocaleDateString() + ' ' + $('#modal-confirm-edit #end_time_edit_log_' + log_id).val());

                                                    if ($('#modal-confirm-edit #start_time_edit_log_' + log_id).val() == "" && $('#modal-confirm-edit #end_time_edit_log_' + log_id).val() == "") {
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html('Please enter start and end time.');
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                        return false;
                                                    } else if ($('#modal-confirm-edit #start_time_edit_log_' + log_id).val() == "") {
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html('Please enter start time.');
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                        return false;
                                                    } else if ($('#modal-confirm-edit #end_time_edit_log_' + log_id).val() == "") {
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html('Please enter end time.');
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                        return false;
                                                    } else {
                                                        if (start_t >= end_t) {
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html('Start time should be less than end time.');
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                            return false;
                                                        } else {
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html('');
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).removeClass("alert-danger");
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).hide();
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

                                                function reSubmit(log_id, physician_id) {
                                                    $('#enterLogMessage').html("");
                                                    $('#enterLogMessage').removeClass("alert-success");
                                                    $('#enterLogMessage').removeClass("alert-danger");
                                                    $('#modal-confirm-edit #enterLogMessageLog_' + log_id).hide();
                                                    var basePath = "";
                                                    var shift = 0;
                                                    var on_call_duration = 0;
                                                    var duration = 0;
                                                    var mandate_details = false;
                                                    var action = $("#modal-confirm-edit #action_" + log_id).val();
                                                    var custom_action = "";

                                                    var contract_type = $("#modal-confirm-edit #contract_type_" + log_id).val();
                                                    var payment_type = $("#modal-confirm-edit #payment_type_" + log_id).val();
                                                    //call-coverage-duration  by 1254 : get partial hours
                                                    var partial_hours = $("#modal-confirm-edit #partial_hours_").val();
                                                    if (payment_type == 8) {
                                                        action = $('#modal-confirm-edit #action_' + log_id).attr('action_id');
                                                    }

//            if(contract_type == 4){
                                                    if (payment_type == 3 || payment_type == 5) {
                                                        on_call_duration = $("#modal-confirm-edit #on_call_duration_" + action + "_" + log_id).val();
                                                        console.log(on_call_duration);
                                                        if (on_call_duration == 0.50) {
                                                            if (partial_hours == 1) {
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html("Can not edit half day activity for partial on contract.");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).removeClass("alert-success");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).focus();
                                                                setTimeout(function () {
//                            $('#enterLogMessageLog_'+log_id).hide();
                                                                }, 3000);
                                                                return false;
                                                            }
                                                            if ($('input[name=shift_' + log_id + ']').is(':checked')) {
                                                                shift = $('input[name=shift_' + log_id + ']:checked').val();
                                                            } else {
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html("Please choose AM or PM shift.");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).removeClass("alert-success");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).focus();
                                                                setTimeout(function () {
//                            $('#enterLogMessageLog_'+log_id).hide();
                                                                }, 3000);
                                                                return false;
                                                            }
                                                        }
                                                        //call-coverage-duration  by 1254 : get duration for partial hours
                                                        if (partial_hours == 1) {
                                                            var log_duration = $("#modal-confirm-edit #duration_" + log_id).val();
                                                            duration = log_duration;

                                                        } else {
                                                            duration = on_call_duration;
                                                        }
                                                    } else {
                                                        duration = $("#modal-confirm-edit #duration_" + log_id).val();
                                                    }
                                                    if (action == -1) {
                                                        custom_action = $("#modal-confirm-edit #custom_action_" + log_id).val();
                                                    }
                                                    var mandate = $("#modal-confirm-edit #mandate_" + log_id).val();
                                                    var details = $("#modal-confirm-edit #log_details_" + log_id).val();
                                                    if (payment_type == 7) {
                                                        details = "";
                                                        if (duration == "") {
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html("Please enter duration.");
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).removeClass("alert-success");
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                            $('#modal-confirm-edit #enterLogMessageLog_' + log_id).focus();
                                                            setTimeout(function () {
                                                            }, 3000);
                                                            return false;
                                                        }
                                                    }
                                                    var date = $("#modal-confirm-edit #date_" + log_id).val();
                                                    var physicianid = physician_id;
                                                    var start_time = "";
                                                    var end_time = "";

                                                    if (payment_type != 3 && payment_type != 5 && payment_type != 7) {
                                                        if (time_stamp_entry_flag) {
                                                            var check = validationTimeStampEntry(log_id);
                                                            if (!check) {
                                                                return false;
                                                            } else {
                                                                start = timeobject($('#modal-confirm-edit #start_time_edit_log_' + log_id).val());
                                                                end = timeobject($('#modal-confirm-edit #end_time_edit_log_' + log_id).val());
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
                                                                start_time = $('#modal-confirm-edit #start_time_edit_log_' + log_id).val();
                                                                end_time = $('#modal-confirm-edit #end_time_edit_log_' + log_id).val();
                                                            }
                                                        }
                                                    }

                                                    getRecentOverrideMandateDetails();

                                                    var current_url = basePath + "/reSubmitEditLog";
                                                    if (mandate == 1 && details.length < 1) {
                                                        mandate_details = true;
                                                    }

                                                    if (!mandate_details || override_mandate_details_flag) {
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
                                                                //$("#modal-confirm-edit_" + log_id).hide();
                                                                $("#modal-confirm-edit").hide();
                                                                $('#enterLogMessage').focus();
                                                                //$("#" + log_id).remove();
                                                                //$("#" + log_id).remove();
                                                                combinedCallGetContractsRecentLogs(physicianid);
                                                            } else {
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html(response.message);
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).removeClass("alert-success");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                                $('#modal-confirm-edit #enterLogMessageLog_' + log_id).focus();
                                                                setTimeout(function () {
//                            $('#enterLogMessageLog_'+log_id).hide();
                                                                }, 3000);
                                                                return false;
                                                            }
                                                        }).error(function (e) {
                                                        });
                                                    } else {
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).show();
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).html("Add log details.");
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).removeClass("alert-success");
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).addClass("alert-danger");
                                                        $('#modal-confirm-edit #enterLogMessageLog_' + log_id).focus();
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
