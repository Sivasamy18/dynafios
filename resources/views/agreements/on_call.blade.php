@extends('layouts/_hospital', Request::is('payments/*')?[ 'tab' => 9]:[ 'tab' => 2])

@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
    @if (Session::has('report_id'))
        <div>
            <input type="hidden" id="report_id" name="report_id" value={{ Session::get('report_id') }}>
            <input type="hidden" id="hospital_id" name="hospital_id" value={{ Session::get('hospital_id') }}>
        </div>
    @endif
    <div class="panel panel-default panel-group">
        <div class="panel-heading">
            {{$agreement->name}}
        </div>

        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement->id}}>

        <div class="panel-body" style="padding-bottom: 0px">
            @if (count($contracts) > 0)
                <div style="padding-top:15px;">
                    <?php $i = 0; ?>

                    <div class="form-group">
                        @foreach ($contracts as $contract)
                            <div style="padding-left: 40px">
                                <i class="fa fa-file-text-o"></i>
                                <strong style="margin-bottom: 12px; display: inline-block;">
                                    {{$contract->name }}
                                </strong>
                            </div>
                        @endforeach
                    </div>
                    <div class="form-group" hidden>
                        <div class="col-xs-1 control-label">
                            Practice:
                        </div>
                        <div class="col-xs-5">
                            <select name="practice_id" id="practice_id" class="form-control">
                                @foreach ($contract->practices as $practice)
                                    <option value="{{$practice->id}}">{{$practice->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <?php
                        $period = "Period";
                        if ($agreement->payment_frequency_type==1){
                            $period = "Month";
                        } else if ($agreement->payment_frequency_type==2){
                            $period = "Week";
                        } else if ($agreement->payment_frequency_type==3){
                            $period = "Bi-Week";
                        } else if ($agreement->payment_frequency_type==4){
                            $period = "Quarter";
                        }
                    ?>

                    <div class="form-group">
                        <div class="col-xs-1"></div>
                        <div class="col-xs-1 control-label">
                            {{$period}}:
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
                    </div>
                    <div class="form-group"></div>
                    <div class="form-group panel panel-default">
                        <div class="col-xs-1 panel-heading text-center">&nbsp;</div>
                        <div class="col-xs-2 panel-heading text-center">Date</div>
                        <div class="col-xs-4 panel-heading text-center">AM Physician</div>

                        <div class="col-xs-4 panel-heading text-center">PM Physician</div>
                        <div class="col-xs-1 panel-heading text-center">&nbsp;</div>

                        <div class="panel-body"
                             style="padding-bottom: 0px; padding-right: 0px; padding-left: 0px">
                            <div id="showData" class="dynamic-dates" style="padding-top: 10px;">
                            </div>

                            @if(Request::is('payments/*'))
                                <div class="col-md-5"><strong>Practice Total : </strong><span
                                            class="dynaminc_paid_total">0</span></div>
                            @endif
                            <ul style="list-style:none">
                                <?php $physician_count = 0; ?>
                                @foreach ($practice->physicians as $physician)
                                    <li style="clear: both;">
                                        @if(Request::is('payments/*'))
                                            <a href="{{ route('physicians.show', $physician->id) }}">
                                                <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                [<span id="workedHour{{$i}}"></span>]
                                            </a>
                                            <div class="alert alert-danger"
                                                 style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                <a class="close" id="paymentDismiss">&times;</a>
                                                <strong>Error!</strong> Please Enter Valid Payment.
                                            </div>
                                            <input type="hidden" class="workedHours pull-right"
                                                   name="amountPaid[]" value="">
                                            <div class="alert alert-danger"
                                                 style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                <a class="close" id="hoursDismiss">&times;</a>
                                                Payment Not Allowed –Minimum Hours not achieved for Selected
                                                Period.
                                            </div>
                                            <input type="hidden" class="maxHours pull-right" name="maxHours[]"
                                                   value="">
                                            <div class="alert alert-danger"
                                                 style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                <a class="close" id="maxHoursDismiss">&times;</a>
                                                This user has reached their Maximum Paid Amount for this period.
                                                Max possible payment: <span
                                                        id="physician{{$physician_count}}"></span>
                                            </div>
                                        @endif
                                    </li>
                                    <?php $i++; ?>
                                    <?php $physician_count++; ?>
                                @endforeach

                            </ul>
                            <div class="panel-footer clearfix">
                                <a class="btn btn-primary"
                                   href="{{route('agreements.show',[$agreement->id]) }}">
                                    &nbsp;Exit&nbsp;
                                </a>
                                <button name="save" class="btn btn-primary btn-submit margin-export disabled"
                                        id="btnSave" type="submit">
                                    Save
                                </button>
                                <button name="export" class="btn btn-primary btn-submit margin-export disabled"
                                        id="btnExport" type="submit">
                                    Export
                                </button>
                                <button name="renew" class="btn btn-primary btn-submit margin-export"
                                        id="btnRenew" type="submit">
                                    Copy Previous Schedule
                                </button>
                                <button name="renew_full" class="btn btn-primary btn-submit margin-export"
                                        id="btnRenewFull" type="submit">
                                    Copy Previous Schedule For All {{$period}}
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                @if(Request::is('payments/*'))
                    <button type="button" id="ajax_submit" class="btn btn-success"
                            style="float: right; margin-top:15px;">
                        Submit Payment
                    </button>
                @endif
            @else
                <p>There are currently no contracts available for display.</p>
            @endif
        </div>
    </div>
    {{ Form::close() }}
@endsection
{{ Form::close() }}
@section('scripts')
    <script type="text/javascript">
        $(function () {

            if (($('#hospital_id').val() != "" && $('#hospital_id').val() != undefined) && ($('#report_id').val() != "" && $('#report_id').val() != undefined)) {
                
                @if(Session::get('hospital_id') && Session::get('report_id'))
                Dashboard.downloadUrl("{{ route('hospitals.report', [Session::get('hospital_id'), Session::get('report_id')]) }}");
                @endif
                
            }
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
            // Dashboard.month_date({});
            $(document).on('change', '[name=select_date]', function (event) {
                $('#btnExport').addClass('disabled');
                $('#btnSave').addClass('disabled');
                var value = $(this).val();
                var one = value;
                $.ajax({
                    url: '{{ URL::current()}}' + "/getDataOnCall/" + one
                    // dataType: 'json'
                }).done(function (response) {

                    $('#showData').html(response.table);
                    $(".form-generate-report").replaceWith(response.form);
                    $('#btnExport').removeClass('disabled');
                    $('#btnSave').removeClass('disabled');
                }).error(function () {
                });
            });
        });
        $(document).ready(function () {
            var selectedDateIndex = "{{Session::get('date_select_index')}}";
            var value;
            if (selectedDateIndex == "") {
                value = $('[name=select_date]').val();
            } else {
                value = selectedDateIndex;
            }
            $('#select_date').val(value);
            var one = value;
            $.ajax({
                url: '{{ URL::current()}}' + "/getDataOnCall/" + one,
                // dataType: 'json'
            }).done(function (response) {
                $('#showData').html(response.table);
                $(".form-generate-report").replaceWith(response.form);
                $('#btnExport').removeClass('disabled');
                $('#btnSave').removeClass('disabled');
            }).error(function () {
            });

            $('#btnSave').click(function(){
                $('#btnSave').addClass('disabled');
            });
        });
    </script>
@endsection