
@php use function App\Start\is_practice_manager; @endphp
@if(Request::is('practices/{id}/agreements*'))
@extends('layouts/_practice', [ 'tab' => 6])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
    @if (Session::has('report_id'))
        <div>
            <input type="hidden" id="report_id" name="report_id" value={{ Session::get('report_id') }}>
            <input type="hidden" id="hospital_id" name="hospital_id" value={{ Session::get('hospital_id') }}>
        </div>
    @endif
    <div class="panel panel-default">
        <div class="panel-heading"><i class="fa fa-file-text-o"></i>&nbsp;{{$contract_name}}
        </div>
        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement->id}}>
        <input type="hidden" id="practice_id" name="practice_id" value={{$practice->id}}>
    </div>

    @if (count($contracts) > 0)
        <ul style="list-style:none;padding-top:15px;">
            <?php $i = 0; ?>
            @foreach ($contracts as $contract)
                <li>
                    <ul style="list-style:none">
                        <div class="form-group">
                            <div class="col-xs-1 control-label">
                                Month:
                            </div>
                            <div class="col-xs-5">
                                <select name="select_date" id="select_date" class="form-control">
                                    @for($j=1;$j<count($dates->dates);$j++)
                                        <option value="{{$j}}">{{$dates->dates[$j]}}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                    </ul>
                    <div class="form-group panel panel-default panel-background">
                        <div class="col-xs-2 panel-heading text-center">Date</div>
                        <div class="col-xs-4 panel-heading text-center" >AM Physician</div>

                        <div class="col-xs-4 panel-heading text-center">PM Physician</div>
                    </div>
                    <div id="showData" class="dynamic-dates">

                    </div>

                    <div class="panel-footer clearfix">
                        @if(!is_practice_manager())
                            <button name="save" class="btn btn-primary btn-submit" type="submit">Save</button>
                        @endif
                            <button name="export" class="btn btn-primary btn-submit margin-export" type="submit">Export</button>
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
                </li>
            @endforeach
        </ul>
        @if(Request::is('payments/*'))
            <button type="button" id="ajax_submit" class="btn btn-success" style="float: right;margin-top:15px;">Submit
                Payment
            </button>
        @endif
    @else
        <p>There are currently no contracts available for display.</p>
    @endif
    {{ Form::close() }}
@endsection
@section('scripts')
    <script type="text/javascript">

        if(($('#hospital_id').val()!="" && $('#hospital_id').val()!=undefined)&& ($('#report_id').val()!="" && $('#report_id').val()!=undefined))
        {
            Dashboard.downloadUrl("{{ route('hospitals.report', [Session::get('hospital_id'), Session::get('report_id')]) }}");
        }
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

            Dashboard.confirm({
                button: '.btn-error',
                dialog: '#modal-error-delete',
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
            $(document).on('change', '[name=select_date]', function(event) {
                var value = $(this).val();
                var practice_id=$('#practice_id').val();
                var one=value+"~"+practice_id;
                $.ajax({
                    url: '{{ URL::current()}}'+"/getDataOnCall/"+one,
                    // dataType: 'json'
                }).done(function (response) {
                    $('#showData').html(response.table);
                    $(".form-generate-report").replaceWith(response.form);
                }).error(function(){
                });
            });
        });
        $( document ).ready(function() {
            var selectedDateIndex ="{{Session::get('date_select_index')}}"
            var value;
            if (selectedDateIndex == "") {
                value = $('[name=select_date]').val();
            } else {
                value = selectedDateIndex;
            }
            $('#select_date').val(value);
            var practice_id=$('#practice_id').val();
            var one=value+"~"+practice_id;

            $.ajax({
                url: '{{ URL::current()}}'+"/getDataOnCall/"+one,
                // dataType: 'json'
            }).done(function (response) {
                $('#showData').html(response.table);
                $(".form-generate-report").replaceWith(response.form);
            }).error(function(){
            });
        });
    </script>
@endsection