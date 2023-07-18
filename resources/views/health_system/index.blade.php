@php use function App\Start\is_super_user; @endphp
@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-globe fa-fw icon"></i> Health Systems
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        @if (is_super_user())
            <a class="btn btn-default" href="{{ URL::route('healthSystem.create') }}">
                <i class="fa fa-plus-circle fa-fw"></i> Add System
            </a>
        @endif
    </div>
</div>
@include('layouts/_flash')
<div class="actions" style="position: relative">
    {!! $table !!}
</div>
<div class="links">
    {!! $pagination !!}
</div>
@endsection
@section('scripts')
<script type="text/javascript">

    $(function () {
        $(document).on('click', '.pagination a', function (event) {
            event.preventDefault();
            ajaxUpdate($(this).attr('href'));
        });

        $(document).on('click', '.filters a', function (event) {
            event.preventDefault();
            ajaxUpdate($(this).attr('href'));
        });

        $(document).on('click', '.table th a', function (event) {
            event.preventDefault();
            ajaxUpdate($(this).attr('href'));
        });

        function ajaxUpdate(href) {
            $('.actions').block('show');

            $.ajax({
                dataType: 'json',
                url: href
            }).done(function (response) {
                $('.actions').html(response.table);
                $('.links').html(response.pagination);
                $('.index').html(response.index);
            }).always(function (response) {
                $(".actions").block('hide');
            });
        }
    });
</script>
@endsection
