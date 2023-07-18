@extends("layouts/_physician_hospital", ["tab" => 5])
@section("actions")
    <a class="btn btn-default btn-generate" href="#"><i class="fa fa-refresh fa-fw"></i> Generate</a>
@endsection
@section("content")
    <div class="filters clearfix">
        <a class="" href="{{ URL::route('physician.reports', $physician->id) }}?p_id={{$physician->practice_id}}">Hospital Report</a>
        <a class="active" href="{{ URL::current() }}?p_id={{$physician->practice_id}}">Payment Status Report</a>
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
	document.onreadystatechange = function () {
                var state = document.readyState;
                if (state == 'interactive') {
                    $(".overlay").show();
                } else if (state == 'complete') {
                    setTimeout(function(){
                        document.getElementById('interactive');
                        $(".overlay").hide();
                    },2000);
                }
            }
        $(function () {
            var report_id = "{{ $report_id }}";
            @isset($report_id)
				Dashboard.downloadUrl("{{ route('physician.report', [$physician->id, $report_id]) }}");
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

            $(document).on("change", "[name=hospital]", function(event) { 
                var physician_id = "{{ $physician->id }}";
                var hospital_id = $(this).val();
                var count = 0;

                $.ajax({
                    url:'/getContractTypesByPhysician/'+ physician_id + '/' + hospital_id + '/' + 1,
                    dataType: 'json',
                    success:function(response){
                        $("[name=contract_type]").html('');
                        $.each(response['contract_types'], function (data, value) {  
                            if(count == 0){
                                $("[name=contract_type]").append($("<option selected='selected'></option>").val(data).html(value));  
                            } else{
                                $("[name=contract_type]").append($("<option></option>").val(data).html(value));  
                            }
                            count ++;
                        }) 
                        Dashboard.updateReportsForm(); 
                    }
                })
            });
			
            $(document).on("change", "[name=contract_type]", function(event) { Dashboard.updateReportsForm(); });
            // $(document).on("click", ".agreement", function(event) { Dashboard.updateReportsFormWithData(); });
            $(document).on("click", ".agreement", function(event) { debugger; Dashboard.updateReportsForm(); });
        });
    </script>
@endsection
