@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-credit-card fa-fw icon"></i> Payment Types
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('payment_types.create') }}">
            <i class="fa fa-plus-circle fa-fw"></i> Payment Type
        </a>
    </div>
</div>
@include('layouts/_flash')
<div id="payment-types" style="position: relative">
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
                <h4 class="modal-title">Delete Payment Type?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this payment type?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this payment type and any associated data. There is no way to
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
            container: '#payment-types',
            filters: '#payment-types .filters a',
            sort: '#payment-types .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
    });
</script>
@endsection