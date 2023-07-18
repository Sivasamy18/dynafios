@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-list fa-fw icon"></i> Contract Names
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('contract_names.create') }}">
            <i class="fa fa-plus-circle fa-fw"></i> Contract Name
        </a>
    </div>
</div>
@include('layouts/_flash')
<div id="contract-names" style="position: relative">
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
                <h4 class="modal-title">Delete Contract Name?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this contract name?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this contract name and any associated data. There is no way to
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
            container: '#contract-names',
            filters: '#contract-names .filters a',
            sort: '#contract-names .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
    });
</script>
@endsection