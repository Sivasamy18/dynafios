@extends('layouts/_practice', [ 'tab' => 6 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
    {{ Form::hidden('id', $practice->id) }}
    @if($send_payment_type_id == App\PaymentType::PER_DIEM || $send_payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
        <div class="col-xs-3 form-group">
            <div class="panel panel-default" style="margin-right: 6px;height: 702px;">
                <div class="panel-heading">Physician(s):</div>
                <div class="panel-body" style="overflow: auto; max-height: 660px;">
                    @foreach($physicians as $physician)
                        <div style="margin-bottom: 10px">
                            <a href="{{ route('physicians.show',  [$physician['id'],$practiceId]) }}"
                               class="text-center">
                                <i class="fa fa-user-md fa-fw"></i>{{$physician['name']}}</a>
                        </div>
                    @endforeach
                </div>
                <!-- </div>-->
            </div>
        </div>
        <div class="col-xs-9 form-group">
            <div class="panel panel-default" style="width: 715px;">
                <div class="panel-heading">
                    On Call Schedule - {{$contract_name}}
                    <a style="float: right; margin-top: -7px" class="btn btn-primary"
                       href="{{ route('practices.onCallEntry', [$send_practice_id, $send_contract_id]) }}">
                        Log Entry / Approval
                    </a>
                </div>
                <div class="panel-body text-center">
                    <div class="form-group">
                        <div class="col-xs-2">

                        </div>
                        <div class="col-xs-2 control-label">
                            Month:
                        </div>
                        <div class="col-xs-5">
                            <select name="select_date" id="select_date" class="form-control">
                                @for($j=1;$j<=count($dates->dates);$j++)
                                    <?php
                                    if ($j == $current_month) {
                                        $selected = "selected";
                                    } else {
                                        $selected = "";
                                    }
                                    ?>
                                    <option value="{{$j}}" {{$selected}}>{{$dates->dates[$j]}}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-xs-2">

                        </div>

                    </div>
                    <div id="showData" style="padding-top: 20px"></div>
                </div>
                <div class="panel-footer clearfix">
                    <a class="btn btn-primary" style="margin-top: -5px;"
                       href="{{route('practices.contracts',[$send_practice_id]) }}">
                        &nbsp;Exit&nbsp;
                    </a>
                </div>
            </div>

        </div>
        @elseif($send_payment_type_id == App\PaymentType::PSA)
        <div class="col-xs-3 form-group">
            <div class="panel panel-default" style="margin-right: 6px;">
                <div class="panel-heading">
                    Physician(s):
                </div>
                <div class="panel-body" style="height: 450px; overflow: auto">
                    @foreach($physicians as $physician)
                        <div style="margin-bottom: 10px">
                            <a href="{{ route('physicians.show', [$physician['id'],$practiceId]) }}"
                               class="text-center">
                                <i class="fa fa-user-md fa-fw"></i>{{$physician['name']}}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-xs-9 form-group">
            <div class="panel panel-default" style="width: 715px;">
                <div class="panel-heading">
                    {{$contract_name}}
                    <a style="float: right; margin-top: -7px" class="btn btn-primary"
                       href="{{ route('practices.physicianPsaWrvuLogEntry', [$send_practice_id, $send_contract_id]) }}">
                        Log Entry
                    </a>
                </div>
                <div class="panel-body text-center"
                     style="height: 400px; padding-left: 0px; padding-right: 0px; margin-right: 0px">

                </div>
                <div class="panel-footer clearfix">
                    <a class="btn btn-primary" style="margin-top: -5px;"
                       href="{{route('practices.contracts',[$send_practice_id]) }}">
                        &nbsp;Exit&nbsp;
                    </a>
                </div>
            </div>

        </div>
    @else
        <div class="col-xs-3 form-group">
            <div class="panel panel-default" style="margin-right: 6px;">
                <div class="panel-heading">
                    Physician(s):
                </div>
                <div class="panel-body" style="height: 450px; overflow: auto">
                    @foreach($physicians as $physician)
                        <div style="margin-bottom: 10px">
                            <a href="{{ route('physicians.show', [$physician['id'],$practiceId]) }}"
                               class="text-center">
                                <i class="fa fa-user-md fa-fw"></i>{{$physician['name']}}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-xs-9 form-group">
            <div class="panel panel-default" style="width: 715px;">
                <div class="panel-heading">
                    {{$contract_name}}
                    <a style="float: right; margin-top: -7px" class="btn btn-primary"
                       href="{{ route('practices.physicianLogEntry', [$send_practice_id, $send_contract_id]) }}">
                        Log Entry
                    </a>
                </div>
                <div class="panel-body text-center"
                     style="height: 400px; padding-left: 0px; padding-right: 0px; margin-right: 0px">

                </div>
                <div class="panel-footer clearfix">
                    <a class="btn btn-primary" style="margin-top: -5px;"
                       href="{{route('practices.contracts',[$send_practice_id]) }}">
                        &nbsp;Exit&nbsp;
                    </a>
                </div>
            </div>

        </div>
    @endif
    {{ Form::close() }}
@endsection
@section('scripts')
    <script type="text/javascript">
        $(document).on('change', '[name=select_date]', function (event) {
            var selected_date = $(this).val();
            var current_url = "{{ URL::to('/')}}" + "/contract/" + {{$send_contract_id}} +"/getOnCallScheduleData/" + selected_date;
            $.ajax({
                url: current_url,
            }).done(function (response) {
                $('#showData').html(response.table);
            }).error(function () {
            });
        });

        $(document).ready(function () {
            var selected_date = $("#select_date").val();
            var current_url = "{{ URL::to('/')}}" + "/contract/" + {{$send_contract_id}} +"/getOnCallScheduleData/" + selected_date;
            if ("{{$send_payment_type_id}}" == "{{App\PaymentType::PER_DIEM}}")
                $.ajax({
                    url: current_url,
                }).done(function (response) {
                    $('#showData').html(response.table);
                }).error(function () {
                });
        });
    </script>
@endsection