@extends('layouts/_physician', ['tab' => 3])
@section('content')
<div id="logs" style="position: relative">
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
                <p>Are you sure you want to delete this log?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this log and any associated data. There is no way to
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
            container: '#logs',
            filters: '#logs .filters a',
            sort: '#logs .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });

        $('#all').on('click', function (event) {
            $('#practices option').prop('selected', this.checked);
        });
    });
</script>
@endsection