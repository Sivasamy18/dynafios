@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 1 ])
@section('actions')
@if (is_super_user())
<a class="btn btn-default" href="{{ URL::route('hospitals.edit', $hospital->id) }}">
    <i class="fa fa-cogs fa-fw"></i> Settings
</a>
@if ($hospital->archived)
<a class="btn btn-default btn-unarchive" href="{{ URL::route('hospitals.unarchive', $hospital->id) }}">
    <i class="fa fa-unlock fa-fw"></i> Unarchive
</a>
@else
<a class="btn btn-default btn-archive" href="{{ URL::route('hospitals.archive', $hospital->id) }}">
    <i class="fa fa-lock fa-fw"></i> Archive
</a>
@endif
<a class="btn btn-default btn-delete" href="{{ URL::route('hospitals.delete', $hospital->id) }}">
    <i class="fa fa-trash-o fa-fw"></i> Delete
</a>
@endif
@endsection
@section('content')
<div class="row">
    <div class="col-xs-8">
        <h4>Recent Activity</h4>

        <div class="recent-activity">
            {!! $table !!}
        </div>
    </div>
    <div class="col-xs-4">
        <div class="panel panel-default">
            <div class="panel-heading">Hospital Information</div>
            <div class="panel-body">
                <table class="table" style="font-size: 12px">
                    <tr>
                        <td>NPI</td>
                        <td>{{ $hospital->npi }}</td>
                    </tr>
                    <tr>
                        <td>Address</td>
                        <td>{{ $hospital->address }}</td>
                    </tr>
                    <tr>
                        <td>City</td>
                        <td>{{ $hospital->city }}</td>
                    </tr>
                    <tr>
                        <td>State</td>
                        <td>{{ $hospital->state->name }}</td>
                    </tr>
                    <tr>
                        <td>Created</td>
                        <td>{{ format_date($hospital->created_at) }}</td>
                    </tr>
                    <tr>
                        <td>Updated</td>
                        <td>{{ format_date($hospital->updated_at) }}</td>
                    </tr>
                    <tr>
                        <td>Physician Count</td>
                        <td>{{ $physician_count_exclude_one }}</td>
                    </tr>
                    <tr>
                        <td>Hospital User Count</td>
                        <td>{{ $hospital_user_count_exclude_one }}</td>
                    </tr>
                    <tr>
                        <td>Practice Manager Count</td>
                        <td>{{ $practice_user_count_exclude_one }}</td>
                    </tr>
                    <tr>
                        <td>Users Added Last Month</td>
                        <td>{{ $added_users }}</td>
                    </tr>
                    <tr>
                        <td>Active Contract Count</td>
                        <td>{{ $contract_count_exclude_one }}</td>
                    </tr>
                    @if (is_super_user() || is_super_hospital_user())
                        <tr>
                            <td>Contracts Expiring within 90 Days</td>
                            @if ($note_display_count>0)
                                <td><a href="{{ URL::route('hospitals.expiringContracts', ['id'=>$hospital->id]) }}">{{ $note_display_count }}</a></td>
                            @else
                                <td>{{ $note_display_count }}</td>
                            @endif
                        </tr>
                    @endif
                    @if (is_super_user() || is_super_hospital_user())
                        <tr>
                            <td>Lawson Interfaced Contracts Count</td>
                            @if ($lawson_interfaced_contracts_count_exclude_one>0)
                                <td><a href="{{ URL::route('hospitals.isLawsonInterfacedContracts', ['id'=>$hospital->id]) }}">{{ $lawson_interfaced_contracts_count_exclude_one }}</a></td>
                            @else
                                <td>{{ $lawson_interfaced_contracts_count_exclude_one }}</td>
                            @endif
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@include('audits.audit-history', ['audits' => $hospital->audits()->orderBy('created_at', 'desc')->with('user')->paginate(50)])

<div class="modal modal-archive-confirmation fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Archive this Hospital?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to archive this hospital?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will archive this hospital and any associated data. All associated hospital
                    administrators, practice managers and physicians will be unable to access the DYNAFIOS dashboard.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Archive</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal modal-unarchive-confirmation fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Unarchive this Hospital?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to unarchive this hospital?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will unarchive this hospital and any associated data. All associated hospital
                    administrators, practice managers and physicians will be able to access the DYNAFIOS dashboard.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Unarchive</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<div class="modal modal-delete-confirmation fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete this Hospital?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this hospital?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this hospital and any associated data. There is no way to
                    restore this data once this action has been completed.
                </p>
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
        $('.btn-archive').on('click', function (event) {
            $('.modal-archive-confirmation').modal('show');
            event.preventDefault();
        });

        $('.modal-archive-confirmation .btn-primary').on('click', function (event) {
            location.assign($('.btn-archive').attr('href'));
        });

        $('.btn-unarchive').on('click', function (event) {
            $('.modal-unarchive-confirmation').modal('show');
            event.preventDefault();
        });

        $('.modal-unarchive-confirmation .btn-primary').on('click', function (event) {
            location.assign($('.btn-unarchive').attr('href'));
        });

        $('.btn-delete').on('click', function (event) {
            $('.modal-delete-confirmation').modal('show');
            event.preventDefault();
        });

        $('.modal-delete-confirmation .btn-primary').on('click', function (event) {
            location.assign($('.btn-delete').attr('href'));
        });
    });
</script>
@endsection
