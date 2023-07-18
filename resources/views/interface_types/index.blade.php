@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-list fa-fw icon"></i> Interface Types
        <small class="index">{{ $index }}</small>
    </h3>
</div>
@include('layouts/_flash')
<div id="interface-types" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {

        Dashboard.pagination({
            container: '#interface-types',
            filters: '#interface-types .filters a',
            sort: '#interface-types .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
    });
</script>
@endsection