@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', Request::is('payments/*')?[ 'tab' => 9]:[ 'tab' => 2])
@section('actions')
    @if (is_super_user() ||is_super_hospital_user())
        @if ($expiring)
            <a class="btn btn-default btn-renew" href="{{ route('agreements.renew', $agreement->id) }}">
                <i class="fa fa-refresh fa-fw"></i> Renew
            </a>
        @endif
        @if ($expired)
            <a class="btn btn-default btn-archive" href="" data-toggle="modal" data-target="#modal-confirm-archive">
                <i class="fa fa-cogs fa-fw"></i> Archive
            </a>
        @endif
        @if ($archived)
            <a class="btn btn-default btn-unarchive" href="" data-toggle="modal" data-target="#modal-confirm-unarchive">
                <i class="fa fa-cogs fa-fw"></i> Unarchive
            </a>
        @endif
        <a class="btn btn-default" href="{{ route('agreements.edit', $agreement->id) }}">
            <i class="fa fa-cogs fa-fw"></i> Agreement Settings
        </a>
        <a class="btn btn-default btn-copy" href="" data-toggle="modal" data-target="#modal-confirm-copy">
            <i class="fa fa-file-text-o fa-fw"></i> Copy
        </a>
        <a class="btn btn-default btn-delete" href="" data-toggle="modal" data-target="#modal-confirm-delete">
            <i class="fa fa-trash-o fa-fw"></i> Delete
        </a>
    @endif
@endsection
@section('content')

    <div class="panel panel-default" style="height: 56px;">
        <div class="panel-heading"><span class="on-call-panel-heading">{{ $agreement->name }}</span>
            @if(isset($contract_type_id))
                @if($contract_type_id == App\ContractType::ON_CALL && $agreement->archived!=1 && !Request::is('payments/*'))
                    <a class="btn btn-default float-right" href="{{ route('agreements.oncall', $agreement->id) }}">
                        On Call Scheduling
                    </a>
                @endif
            @endif
            <a class="btn btn-default float-right" href="{{ route('agreements.createContract', $agreement->id) }}"
               style="margin-right: 5px">
                <i class="fa fa-plus-circle fa-fw"></i> Contract
            </a>
            <div class="clear-both"></div>
        </div>
        <input type="hidden" id="agreement_id" name="agreement_id" value={{$agreement->id}}>
        <div class="panel-body">
            <div class="col-xs-4">

                @if(Request::is('payments/*'))
                    <div class="pendingPayment">
                        <span class="">Pending Payments</span>
                        <ul>
                            <li>
                                <button type="button" class="btn btn-default">Agreement_Contract date1</button>
                            </li>
                            <li class="active">
                                <button type="button" class="btn btn-default">Agreement_Contract date1</button>
                            </li>
                            <li>
                                <button type="button" class="btn btn-default">Agreement_Contract date1</button>
                            </li>
                            <li>
                                <button type="button" class="btn btn-default">Agreement_Contract date1</button>
                            </li>
                            <li>
                                <button type="button" class="btn btn-default">Agreement_Contract date1</button>
                            </li>
                            <li>
                                <button type="button" class="btn btn-default">Agreement_Contract date1</button>
                            </li>
                        </ul>
                    </div>
                @else
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
                            <td>Created Date</td>
                            <td>{{ format_date($agreement->created_at) }}</td>
                        </tr>
                        <tr>
                            <td>Days Remaining</td>
                            <td>{{ $remaining }}</td>
                        </tr>
                    </table>
                @endif

            </div>
            <div class="col-xs-8 invoiceDashboard">
                <div id="contracts">
                    <div class="alert alert-success ajax-success" style="display:none;">
                        @if(Request::is('payments/*'))
                            <strong>Success! </strong>Data saved successfully and an invoice has been sent to all the
                            delegated
                            recipients!.
                        @else
                            <strong>Success! </strong>Data saved successfully.
                        @endif
                    </div>
                    <div class="alert alert-danger ajax-failed" style="display:none;">

                        <strong>Error! </strong>There is a problem.Please try again after sometime.
                    </div>
                    <div class="alert alert-danger ajax-error" style="display:none;">

                        <strong>Error! </strong>You have already finalized reports for some physicians between this date
                        range.So you can not change data for them
                    </div>
                    <div>
                        <?php
                        $i = 1;

                        foreach ($dates->start_dates as $date) {
                            $new_date = explode(": ", $dates->end_dates[$i]);
                            $dates->start_dates[$i] .= " - " . $new_date[1];
                            $i++;
                        }
                        ?>
                        @if(Request::is('payments/*'))
                            <h3 class="" style="margin-top: 0;">Agreement Name</h3>
                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Agreement </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    <select class="form-control">
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Practice </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    <select class="form-control">
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Contract Type </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    <select class="form-control">
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group col-xs-12 paddingZero">
                                <div class="">
                                    <label class="control-label">Physician </label>
                                </div>
                                <div class="col-md-12 col-sm-12 col-xs-12 paddingZero">
                                    <select class="form-control">
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                        <option value="">Agreement1</option>
                                    </select>
                                </div>
                            </div>

                            Date :
                            {{ Form::select("agreement_{$agreement->id}_start_month", $dates->start_dates, $dates->current_month - 1, ['class' => 'form-control','id' => 'start_date']) }}
                            <div class="alert alert-danger"
                                 style="display:none;padding:5px;clear:both;margin-top:10px;">
                                <a class="close" data-dismiss="alert">&times;</a>
                                <strong>Error! </strong>Please Select Valid Dates.
                            </div>
                        @endif
                    </div>
                    {!! $table !!}
                </div>
            </div>
        </div>
        @include('audits.audit-history', ['audits' => $agreement->audits()->orderBy('created_at', 'desc')->with('user')->paginate(50)])
    </div>
    <div id="modal-confirm-delete" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Delete Agreement?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this agreement?</p>

                    <p><strong style="color: red">Warning!</strong><br>
                        This action will delete this agreement and any associated data. There is no way
                        to restore this data once this action has been completed.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a type="button" class="btn btn-primary" href="{{ route('agreements.delete', $agreement->id) }}">Delete</a>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div id="modal-confirm-copy" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Copy Agreement?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to copy this agreement?</p>

                    <p><strong style="color: red">Warning!</strong><br>
                        This action will duplicate this agreement and any associated data.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a type="button" href="{{ route('agreements.copy', $agreement->id) }}"
                       class="btn btn-primary">Copy</a>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div id="modal-confirm-archive" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Archive Agreement?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to archive this agreement?</p>

                    <p><strong style="color: red">Warning!</strong><br>
                        This action will archive this agreement and any associated data.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a type="button" class="btn btn-primary" href="{{ route('agreements.archive', $agreement->id) }}">Archive</a>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <div id="modal-confirm-unarchive" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Unarchive Agreement?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to unarchive this agreement?</p>

                    <p><strong style="color: red">Warning!</strong><br>
                        This action will unarchive this agreement and any associated data.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a type="button" href="{{ route('agreements.unarchive', $agreement->id) }}"
                       class="btn btn-primary">Unarchive</a>
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
            // $.preventDefault();
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

            Dashboard.confirm({
                button: '.btn-copy',
                dialog: '#modal-confirm-copy',
                dialogButton: '.btn-primary'
            });

            Dashboard.confirm({
                button: '.btn-unarchive',
                dialog: '#modal-confirm-unarchive',
                dialogButton: '.btn-primary'
            });
        });

        $("#amionImport").click(function () {
            $(".overlay").show();
        });

        function outlineZero() {
            $('.pendingPayment ul li button.btn ').css({
                'outline': '0',
            });
        }

        window.onload = outlineZero;
    </script>
@endsection
