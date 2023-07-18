@extends("layouts/_hospital", ["tab" => 5])
@section("actions")
	<a class="btn btn-default btn-generate" href="#"><i class="fa fa-refresh fa-fw"></i> Generate</a>
@endsection
@section("content")
    <div class="filters clearfix">
        <a class="" href="{{ URL::route('hospitals.report', [$hospital->id,'']) }}">Hospital</a>
        <a class="" href="{{ URL::route('hospitals.activeContractReports', $hospital->id) }}">Active Contracts</a>
        <a class="" href="{{ URL::route('hospitals.paymentStatusReports', $hospital->id) }}">Payment Status</a>
        <a class="" href="{{ URL::route('hospitals.breakdown', $hospital->id) }}">Physician logs</a>
        @if($isLawsonInterfaceReady)
        <a class="" href="{{ URL::route('hospitals.lawsonInterfaceReports', $hospital->id) }}">Interface</a>
        @endif
        <a class="" href="{{ URL::route('hospitals.invoices', $hospital->id) }}">Invoices</a>
        <a style="display:block" class="" href="{{ URL::route('hospitals.attestation', $hospital->id) }}">Attestations</a>
        <a style="display:block" class="active" href="{{ URL::current() }}">Payment summary</a>

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
    {!! $pagination !!}
</div>
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Check Request?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this check request?</p>
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
            Dashboard.downloadUrl("{{ route('hospitals.report', [$hospital->id, $report_id]) }}");
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

        // $(document).on("click", "[name=apply_filter_date]", function(event) { Dashboard.updateReportsForm(); });
     
     //   $(document).on("change", "[name=end_date]", function(event) { Dashboard.updateReportsFormWithData(); });
        // $(document).on("change", "[name=show_deleted_physicians]", function(event) { Dashboard.updateReportsFormWithData(); });
        // $(document).on("click", ".agreement", function(event) { Dashboard.updateReportsFormWithData(); });
    //    $(document).on("change", ".select_dates", function(event) { Dashboard.updateReportsFormWithData(); });
    });
</script>
@endsection