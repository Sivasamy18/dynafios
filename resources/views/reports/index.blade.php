@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-file-text fa-fw icon"></i> Admin Reports
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default btn-generate" href="#">
            <i class="fa fa-refresh fa-fw"></i> Generate
        </a>
    </div>
</div>
<div class="generate-drawer">
    <div class="drawer-content" style="{{ HTML::hidden($errors->count() == 0) }}">
        {{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
        <div class="panel panel-default">
            <div class="panel-heading">Generate Report</div>
            <div style="padding-left: 15px;">
                {{ Form::checkbox('show_all','1',null,['class'=>'show_all']) }}
                <!--/reports/allAgreements-->
                <label>Show All Agreements</label>
                <div id="user_jobs"></div>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="col-xs-4"><label>Agreement</label></div>
                    <div class="col-xs-3"><label>Start Date</label></div>
                    <div class="col-xs-3"><label>End Date</label></div>
                </div>
                @foreach ($agreements as $agreement)
                <div class="form-group">
                    <div class="col-xs-4">
                        {{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement']) }}{{ $agreement->name }}
                    </div>
                    <div class="col-xs-3">
                        {{ Form::select("agreement_{$agreement->id}_start_month", $agreement->start_dates,
                        $agreement->current_month - 1, ['class' => 'form-control']) }}

                    </div>
                    <div class="col-xs-3">
                        {{ Form::select("agreement_{$agreement->id}_end_month", $agreement->end_dates,
                        $agreement->current_month - 1, ['class' => 'form-control']) }}

                    </div>
                </div>
                @endforeach
            </div>
            <div class="panel-footer clearfix">
                <div class="select">
                    <a href="#">Select/Deselect All</a>
                </div>
                <button class="btn btn-primary btn-sm btn-submit">Submit</button>
            </div>
        </div>
        {{ Form::close() }}
    </div>
</div>
@include('layouts/_flash')
<div id="reports" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Report?</h4>
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
<input type="hidden" value="0" id="selectStatus" />
@endsection
@section('scripts')
<script type="text/javascript">
    var report_id = "{{ $report_id }}";
    @isset($report_id)
        Dashboard.downloadUrl("{{ route('reports.download', $report_id) }}");
    @endisset

    $(function () {
        $(document).on("click", ".select a", function(event) {
            if($('#selectStatus').val() == "0")
			{
				$(".agreement").prop( "checked", true );
				$('#selectStatus').val('1');
			}
			else
			{
				$(".agreement").prop( "checked", false );
				$('#selectStatus').val('0');
			}
            event.preventDefault();
        });
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

        $('.show_all').click(function () {
            //-->this will alert id of checked checkbox.
            if (this.checked) {

                $.ajax({
                    type: "POST",
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    url: '/reports/allAgreements',
                    dataType: 'json',
                    success: function (alldata) {
                        $('.panel-body').html(alldata.panel_body);
                    },
                    error: function () {
                    },
                    complete: function () {
                    }
                });
            }
            else {

                $.ajax({
                    type: "GET",
                    url: '/reports',
                    dataType: 'json',
                    success: function (alldata) {
                        $('.panel-body').html(alldata.panel_body);
                        console.log(alldata.agreements[0]);
                    },
                    error: function () {
                    },
                    complete: function () {
                    }
                });
                this.checked = false;
            }
        });
    });
</script>
@endsection