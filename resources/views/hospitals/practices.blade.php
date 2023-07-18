@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 4 ])
@section('actions')
     @if (is_super_user() || is_super_hospital_user())
        <a class="btn btn-default" href="{{ URL::route('hospitals.create_practice', $hospital->id) }}">
            <i class="fa fa-plus-circle fa-fw"></i> Practice
        </a>
    @endif
@endsection
@section('content')
<div class="practices" style="position: relative">
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
            $('.practices').block('show');

            $.ajax({
                dataType: 'json',
                url: href
            }).done(function (response) {
                $('.practices').html(response.table);
                $('.links').html(response.pagination);
                $('.index').html(response.index);
            }).always(function (response) {
                $(".practices").block('hide');
            });
        }
    });
</script>
@endsection
