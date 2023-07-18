@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-hospital-o fa-fw icon"></i> Practices
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
    </div>
</div>
@include('layouts/_flash')
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