@php use function App\Start\is_super_user; @endphp
@extends('layouts/_healthSystem', [ 'tab' => 2 ])
@section('actions')
    @if (is_super_user())
        <a class="btn btn-default" href="{{ URL::route('healthSystem.create_region', $system->id) }}">
            <i class="fa fa-plus-circle fa-fw"></i> Add Region
        </a>
    @endif
@endsection
@section('content')
<div class="admins" style="position: relative">
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
            $('.admins').block('show');

            $.ajax({
                dataType: 'json',
                url: href
            }).done(function (response) {
                $('.admins').html(response.table);
                $('.links').html(response.pagination);
                $('.index').html(response.index);
            }).always(function (response) {
                $(".admins").block('hide');
            });
        }
    });
</script>
@endsection