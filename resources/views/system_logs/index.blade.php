@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-list fa-fw icon"></i> System Logs
        <small id="index">{{ $index }}</small>
    </h3>    
</div>
@include('layouts/_flash')
<div id="actions" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>

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