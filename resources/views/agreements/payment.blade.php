@php use function App\Start\is_super_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 9])
@section('actions')
@if (is_super_user())
@if ($expiring)
<a class="btn btn-default btn-renew" href="{{ route('agreements.renew', $agreement->id) }}">
    <i class="fa fa-refresh fa-fw"></i> Renew
</a>
@endif
<a class="btn btn-default" href="{{ route('agreements.edit', $agreement->id) }}">
    <i class="fa fa-cogs fa-fw"></i> Settings
</a>
<a class="btn btn-default btn-delete" href="{{ route('agreements.delete', $agreement->id) }}">
    <i class="fa fa-trash-o fa-fw"></i> Delete
</a>
@endif
@endsection
@section('content')
<div class="panel panel-default">
    <div class="panel-heading">{{ $agreement->name }}</div>
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
                {!! $table !!}
            </div>
        </div>
    </div>
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
        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
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