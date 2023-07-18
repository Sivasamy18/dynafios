@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_practice', [ 'tab' => 6])
@section('actions')

@endsection
@section('content')
    <div class="managers" style="position: relative">
       {!! /*$table*/ !!}
    </div>
    <div class="panel panel-default" style="height: 56px;">
        <div class="panel-heading"><span class="on-call-panel-heading">{{ $agreement->name }}</span>
            @if(isset($payment_type_id))
                @if($payment_type_id == App\PaymentType::PER_DIEM && $agreement->archived!=1 && !Request::is('payments/*'))
                    <a class="btn btn-default float-right"
                       href="{{ route('practices.scheduling', [$practice->id,$agreement->id]) }}">
                        On Call Scheduling
                    </a>
                    @if(!is_super_hospital_user())
                        <a style="float: right; margin-right: 20px;" class="btn btn-default"
                           href="{{ route('practices.onCallEntry', [$practice->id,$agreement->id]) }}">
                            Log Entry / Approval
                        </a>
                    @endif
                @endif
            @endif
            <div class="clear-both"></div>
        </div>
        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement->id}}>
        <div class="panel-body">
            <div class="col-xs-4">

                <table class="table">
                    <tr>
                        <td>Start Date</td>
                        <td>{{ format_date($agreement->start_date) }}</td>
                    </tr>
                    <tr>
                        <td>End Date</td>
                        <td>{{ format_date($agreement->end_date) }}</td>
                    </tr>
                    <tr>
                        <td>Days Remaining</td>
                        <td>{{ $remaining }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-xs-8">
                <div id="contracts">
                    <div class="alert alert-success ajax-success" style="display:none;">

                        <strong>Success! </strong>Data saved successfully.
                    </div>
                    <div class="alert alert-danger ajax-failed" style="display:none;">

                        <strong>Error! </strong>There is a problem.Please try again after sometime.
                    </div>
                    <div class="alert alert-danger ajax-error" style="display:none;">

                        <strong>Error! </strong>You have already finalized reports for some physicians between this date range.So you can not change data for them
                    </div>
                    <div>
                        <?php
                        $i = 1;

                        foreach($dates->start_dates as $date)
                        {
                            $new_date = explode(": ",$dates->end_dates[$i]);
                            $dates->start_dates[$i] .=" - ".$new_date[1];
                            $i++;
                        }
                        ?>
                        @if(Request::is('payments/*'))
                            Date : {{ Form::select("agreement_{$agreement->id}_start_month", $dates->start_dates, $dates->current_month - 1, ['class' => 'form-control','id' => 'start_date']) }}
                                    <!--End Date : {{ Form::select("agreement_{$agreement->id}_start_month", $dates->end_dates, $dates->current_month - 1, ['class' => 'form-control','id' => 'end_date']) }} -->
                            <div class="alert alert-danger" style="display:none;padding:5px;clear:both;margin-top:10px;">
                                <a class="close" data-dismiss="alert">&times;</a>
                                <strong>Error! </strong>Please Select Valid Dates.
                            </div>
                        @endif
                    </div>
                    {!! $table !!}
                </div>
            </div>
        </div>
    </div>
    <div class="modal modal-delete-confirmation fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Delete this Manager?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this manager?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Delete</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->
@endsection
@section('scripts')
    <script type="text/javascript">
        $(function () {
            $("[name=start_date]").inputmask({ mask: '99/99/9999' });
            $("[name=end_date]").inputmask({ mask: '99/99/9999' });
            $("#start-date").datetimepicker({ language: 'en_US', pickTime: false });
            $("#end-date").datetimepicker({ language: 'en_US', pickTime: false });

            $(document).on('click', '.btn-generate', function (event) {
                $('.generate-drawer').drawer('toggle');
                event.preventDefault();
            });

            $(document).on('click', '.btn-delete', function (event) {
                var href = $(this).attr('href');

                $('.modal-delete-confirmation').data('href', href);
                $('.modal-delete-confirmation').modal('show');

                event.preventDefault();
            });

            $('.modal-delete-confirmation .btn-primary').on('click', function (event) {
                location.assign($('.modal-delete-confirmation').data('href'));
            });

            $('#all').on('click', function (event) {
                $('#practices option').prop('selected', this.checked);
            });

            $(document).on('click', '.pagination a', function (event) {
                event.preventDefault();
                ajaxUpdate($(this).attr('href'));
            });

            $(document).on('click', '.filters a', function (event) {
                event.preventDefault();
                ajaxUpdate($(this).attr('href'));
            });

            $(document).on('click', '.table th a', function (event) {
                event.preventDefault();
                ajaxUpdate($(this).attr('href'));
            });

            function ajaxUpdate(href) {
                $('.managers').block('show');

                $.ajax({
                    dataType: 'json',
                    url: href
                }).done(function (response) {
                    $('.managers').html(response.table);
                    $('.links').html(response.pagination);
                    $('.index').html(response.index);
                }).always(function (response) {
                    $(".managers").block('hide');
                });
            }
        });
    </script>
@endsection