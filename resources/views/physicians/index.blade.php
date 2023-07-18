@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-user-md fa-fw icon"></i> Physicians
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('physicians.index_show_all') }}">
            <i class="fa fa-eye fa-fw"></i> Show Physicians in Archived Hospitals
        </a>
        <a class="btn btn-default" href="{{ URL::route('physicians.deleted') }}">
            <i class="fa fa-undo"></i> Restore Physicians
        </a>
    </div>
</div>
@include('layouts/_flash')
<div id="physicians" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Log?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this physician?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this physician and any associated data. There is no way to
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
        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
            dialogButton: '.btn-primary'
        });

        Dashboard.pagination({
            container: '#physicians',
            filters: '#physicians .filters a',
            sort: '#physicians .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
    });
</script>
@endsection