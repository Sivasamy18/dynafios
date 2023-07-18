@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 2 ])
@section('actions')
@if (is_super_user()|| is_super_hospital_user())
<a class="btn btn-default" href="{{ URL::route('hospitals.create_agreement', $hospital->id) }}">
    <i class="fa fa-plus-circle fa-fw"></i> Create
</a>
@endif
@endsection
@section('content')
<div id="agreements" style="position: relative">
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
            container: '#agreements',
            filters: '#agreements .filters a',
            sort: '#agreements .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });
    });
</script>
@endsection
