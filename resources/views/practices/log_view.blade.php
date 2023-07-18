@if(count($recent_logs)>0)
    @foreach($recent_logs as $recent_log)
        <input type="hidden" id="log_ids" name="log_ids[]" value={{$recent_log['id']}}>
        <div id="log_{{$recent_log['id']}}">
            <div class="col-xs-10" style="padding-left: 0px;">
                <div class="col-xs-6 control-label" style="margin-left: -46px;">Action Name:</div>
                <div class="col-xs-6 control-label_custom">{{$recent_log['action']}}</div>
                <div class="col-xs-6 control-label">Duration:</div>
                <div class="col-xs-6 control-label_custom">{{$recent_log['duration']}} Day</div>
                <div class="col-xs-6 control-label">Log Date:</div>
                <div class="col-xs-6 control-label_custom">{{$recent_log['date']}}</div>
                <div class="col-xs-6 control-label">Created:</div>
                <div class="col-xs-6 control-label_custom">{{$recent_log['created']}}</div>
                <div class="col-xs-6 control-label">Entered By:</div>
                <div class="col-xs-6 control-label_custom">{{$recent_log['entered_by']}}</div>
                <div class="col-xs-6 control-label">Log Details:</div>
                <div class="col-xs-6 control-label_custom">{{$recent_log['details']!=""?$recent_log['details']:"-"}}</div>
            </div>
            <div class="col-xs-2">
                <a  class="btn btn-default btn-delete"  style="margin-top: 100px;" href="{{ URL::route('log.delete', [$practice_id,$agreement_id,$contract_id,$recent_log['id']]) }}">
                    <i class="fa fa-trash-o fa-fw"></i>
                </a>
            </div>
            <div>&nbsp;</div>
            <div>
                <hr style="margin-top:65px;width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
            </div>
        </div>
    @endforeach
    <div class="modal modal-delete-confirmation fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Delete this Log?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this log?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel
                    </button>
                    <button type="button" class="btn btn-primary">Delete
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
    </div>
@elseif($logsTypeFlag == "recentLogs")
    <div>{{Lang::get('practices.noRecentLogs')}}</div>

@elseif($logsTypeFlag == "approveLogs")
    <div>{{Lang::get('practices.noLogsPendingForApproval')}}</div>
@endif