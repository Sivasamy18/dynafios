@extends('layouts/_hospital', Request::is('payments/*')?[ 'tab' => 9]:[ 'tab' => 2])

@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
    @if (Session::has('report_id'))
        <div>
            <input type="hidden" id="report_id" name="report_id" value={{ Session::get('report_id') }}>
            <input type="hidden" id="hospital_id" name="hospital_id" value={{ Session::get('hospital_id') }}>
        </div>
    @endif
    <div class="panel panel-default">
        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement->id}}>
        <input type="hidden" id="selected_dates" name="selected_dates" value="">
        <div class="panel-heading">
           <div >{{$agreement->name}}</div><!--   Log Entry-->
        </div>
        <div class="panel-body">
            <div class="col-xs-12 form-group">

                <div class="col-xs-2 control-label">Physician Name :</div>
                <div class="col-xs-5 "><select class="form-control" id="physician_name" name="physician_name" onchange="getPreLogdates(this.value);">
                        @foreach($physicians as $physician)
                            <option value="{{$physician['id']}}">{{$physician['name']}}</option>
                        @endforeach

                    </select>
                </div>
                <div class="col-xs-5">&nbsp;</div>
            </div>
            <div class="col-xs-12 form-group">

                <div class="col-xs-2 control-label">Action/Duty :</div>
                <div class="col-xs-5"><select class="form-control" name="action">
                    @foreach($activities as $activity)
                            <option value="{{$activity->id}}">{{$activity->name}}</option>
                        @endforeach

                    </select>
                </div>
                <div class="col-xs-5">&nbsp;</div>
            </div>
            <div class="col-xs-12 form-group">
                <div class="col-xs-2 control-label">Select Date:</div>
                <div class="col-xs-5" id="select_date" name="select_date"></div>
                <div class="col-xs-5">&nbsp;</div>
            </div>
            <div class="col-xs-12 form-group">
                <div class="col-xs-2 control-label">Add log details:</div>
                <div class="col-xs-5"><textarea  name="log_details" id="log_detailis" class="form-control"></textarea></div>
                <div class="col-xs-5">&nbsp;</div>

            </div>
            <div class="col-xs-12 ">
                <div >
                    <div >
                        <button name="submit" class="btn btn-primary btn-submit" onclick="save_log()" type="submit">Submit</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="panel panel-default">
        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement->id}}>

        <div class="panel-heading">
            Log Approval
        </div>
        <div class="panel-body pre-scrollable">

            @if(count($recent_logs)>0)
                @foreach($recent_logs as $recent_log)
                    <input type="hidden" id="log_ids" name="log_ids[]" value={{$recent_log['id']}}>
                    <div id="{{$recent_log['id']}}">
                        <div class="col-xs-10">
                            <div class="col-xs-3">Physician Name:</div>
                            <div class="col-xs-9">{{$recent_log['physician_name']}}</div>
                            <div class="col-xs-3">Action Name:</div>
                            <div class="col-xs-9">{{$recent_log['action']}}</div>
                            <div class="col-xs-3">Duration:</div>
                            <div class="col-xs-9">{{$recent_log['duration']}} Hour(s)</div>
                            <div class="col-xs-3">Date:</div>
                            <div class="col-xs-9">{{$recent_log['date']}}</div>
                            <div class="col-xs-3">Created:</div>
                            <div class="col-xs-9">{{$recent_log['created']}}</div>
                        </div>
                        <div><a style="margin-top:25px;margin-left:17px;width: 90px; color: darkgrey; height: 30px;"
                                onClick="delete_log(this.id);" id="{{$recent_log['id']}}"
                                class="btn btn-default btn-delete"
                                href="">
                                <i class="fa fa-trash-o fa-fw"></i>
                            </a></div>
                        <div>
                            <hr style="margin-top:65px;width: 100%; color: darkgrey; height: 1px; background-color:darkgrey;"/>
                        </div>
                    </div>

                @endforeach
            @else
                <div>{{Lang::get('agreements.noLogs')}}</div>
            @endif


        </div>
        <div class="col-xs-12 ">&nbsp;</div>
        <div class="col-xs-12 ">
            <div >
                <button name="approve_logs" class="btn btn-primary btn-submit" type="submit">Approve Logsssss</button>
            </div>
        </div>
    </div>

@endsection
{{ Form::close() }}
@section('scripts')
    <script type="text/javascript">

        function getPreLogdates(val) {

            var current_url = "{{ URL::current()}}" + "/getPreLogDate/" + val;
            $.ajax({
                url: current_url,
            }).done(function (response) {
                $('#select_date').multiDatesPicker('resetDates', 'disabled');
                var selected_date = response;
                var date_array = [];
                for (var i = 0; i < selected_date.length; i++) {
                    var dateParts = selected_date[i].split("-");
                    date_array[i] = new Date(dateParts[0], dateParts[1] - 1, dateParts[2].substr(0, 2));
                }
                $('#select_date').multiDatesPicker({
                    addDisabledDates: date_array
                });
            }).error(function () {
            });
        }

        function save_log() {
            var dates = $('#select_date').multiDatesPicker('getDates');
            $('#selected_dates').val(dates);
        }

        function delete_log(log_id) {
            var current_url = "{{ URL::current()}}" + "/Delete/" + log_id;
            $.ajax({
                url: current_url,
            }).done(function (response) {
                $("#" + log_id).remove();
            }).error(function () {
            });
        }

        $(function () {
            $('#select_date').multiDatesPicker({
                minDate: -90,
                maxDate: 0
            });
            var physician_id = $('#physician_name').val();
            getPreLogdates(physician_id);

            Dashboard.confirm({
                button: '.btn-delete',
                dialog: '#modal-confirm-delete',
                dialogButton: '.btn-primary'
            });

            Dashboard.confirm({
                button: '.btn-archive',
                dialog: '#modal-confirm-archive',
                dialogButton: '.btn-primary'
            });

            Dashboard.pagination({
                container: '#actions',
                filters: '#actions .filters a',
                sort: '#actions .table th a',
                links: '#links',
                pagination: '#links .pagination a'
            });
        });
    </script>
@endsection