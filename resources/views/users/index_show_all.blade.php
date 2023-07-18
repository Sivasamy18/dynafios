@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-user fa-fw icon"></i> Users
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('users.index') }}">
            <i class="fa fa-eye-slash fa-fw"></i> Hide Users in Archived Hospitals
        </a>
        <a class="btn btn-default" href="{{ URL::route('users.deleted') }}">
            <i class="fa fa-undo"></i> Restore Users
        </a>
        <a class="btn btn-default" href="{{ URL::route('users.create') }}">
            <i class="fa fa-plus-circle fa-fw"></i> User
        </a>
    </div>
</div>
@include('layouts/_flash')
<div id="users" style="position: relative">
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
            container: '#users',
            filters: '#users .filters a',
            sort: '#users .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
    });
</script>
@endsection