@php use function App\Start\is_super_user; @endphp
@extends('layouts/_healthSystemRegion', [ 'tab' => 3 ])
@section('actions')
    @if (is_super_user())
        <a class="btn btn-default" href="{{ URL::route('healthSystemRegion.add_hospital', [$system->id, $region->id]) }}">
            <i class="fa fa-plus-circle fa-fw"></i> Associate Hospital With Region
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

<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Disassociate this hospital from this region?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to disassociate this hospital from this region?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will disassociate this hospital from this region and any associated data.
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
            button: '.btn-disassociate',
            dialog: '#modal-confirm-delete',
            dialogButton: '.btn-primary'
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