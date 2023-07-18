@extends($group == App\Group::HOSPITAL_ADMIN || App\Group::SUPER_HOSPITAL_USER ? 'layouts/_complianceDashboard' : 'layouts/_complianceDashboard', [ 'tab' => 5 ])
@section('actions')
    <a class="btn btn-default btn-generate" href="#"><i class="fa fa-refresh fa-fw"></i> Generate</a>
@endsection
@section('content')
    <div class="filters clearfix">
        <a href="{{ URL::route('compliance.complianceReport', $group) }}">Physician</a>
        <a class="" href="{{ URL::route('compliance.practiceReport', $group) }}">Practice</a>
        <a class="active" href="{{ URL::current() }}">Contract Type</a>
    </div>
    <div class="generate-drawer">
        <div class="drawer-content" style="{{ HTML::hidden($errors->count() == 0) }}">
            {!! $form !!}
        </div>
    </div>
    <div class="reports" style="position: relative">
        {!! $table !!}
    </div>
    <div class="links">
        
    </div>
    <div id="modal-confirm-delete" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Delete this Report?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this report?</p>
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
            var report_id = "{{ $report_id }}";
            @isset($report_id)
                Dashboard.downloadUrl("{{ route('compliance.report',[$report_id]) }}");
            @endisset

            Dashboard.confirm({
                button: '.btn-delete',
                dialog: '#modal-confirm-delete',
                dialogButton: '.btn-primary'
            });

            Dashboard.pagination({
                container: '#reports',
                filters: '#reports .filters a',
                sort: '#reports .table th a',
                links: '#links',
                pagination: '#links .pagination a'
            });

            Dashboard.selectAll({
                toggle:    "#all",
                label:     "#all-label",
                values:    "#physicians option"
            });


        });
    </script>
@endsection
