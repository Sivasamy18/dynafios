@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_practice', [ 'tab' => 3])
@section('actions')
    @if (is_super_user() || is_super_hospital_user())
        <a class="btn btn-default" href="{{ URL::route('practices.create_physician', $practice->id) }}">
            <i class="fa fa-plus-circle fa-fw"></i> Physician
        </a>
    @endif
@endsection
@section('content')
<div class="physicians" style="position: relative">
    {!! $table !!}
</div>
<div class="links">
    {!! $pagination !!}
</div>
<div class="modal modal-delete-confirmation fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete this Physician?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this physician?</p>
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
        $("[name=start_date]").inputmask({ mask: '99/99/9999' });
        $("[name=end_date").inputmask({ mask: '99/99/9999' });
        $("#start-date").datetimepicker({ language: 'en_US', pickTime: false });
        $("#end-date").datetimepicker({ language: 'en_US', pickTime: false });

        $(document).on('click', '.btn-generate', function (event) {
            $('.generate-drawer').drawer('toggle');
            event.preventDefault();
        });

        $(document).on('click', '.btn-delete', function (event) {
            var href = $(this).attr('href');

            $('.modal-delete-confirmation').data('href', href);
            $('.modal-delete-confirmation').modal('show');

            event.preventDefault();
        });

        $('.modal-delete-confirmation .btn-primary').on('click', function (event) {
            location.assign($('.modal-delete-confirmation').data('href'));
        });

        $('#all').on('click', function (event) {
            $('#practices option').prop('selected', this.checked);
        });

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
            $('.physicians').block('show');

            $.ajax({
                dataType: 'json',
                url: href
            }).done(function (response) {
                $('.physicians').html(response.table);
                $('.links').html(response.pagination);
                $('.index').html(response.index);
            }).always(function (response) {
                $(".physicians").block('hide');
            });
        }
    });
</script>
@endsection
