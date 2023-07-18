@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_physician', ['tab' => 2])
@section('actions')
@if (is_super_user() || is_super_hospital_user())
<button type="button" id="sort_order" name="sort_order" class="btn btn-primary" data-toggle="modal" data-target="#contract_name_sorting_modal">Edit Order</button>
<a class="btn btn-default" href="{{ URL::route('physicians.create_contract', [$physician->id,$practice->id]) }}">
    <i class="fa fa-plus-circle fa-fw"></i> Contract
</a>
<a class="btn btn-default btn-welcome" href="{{ URL::route('physicians.welcome', [$physician->id,$practice->id]) }}">
    <i class="fa fa-envelope fa-fw"></i> Welcome Package
</a>
@endif
@endsection
@section('content')
<div id="contracts" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
<div id="modal-confirm-welcome" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Send Welcome Packet?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to send the welcome packet to this physician?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    The welcome packet should be sent once per physician after all contracts have been setup
                    successfully. An email will be sent to the physician each time this feature is used.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Send Packet</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete this Contract?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this contract?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this contract and any associated data. There is no way to
                    restore this data once this action has been completed.
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

<!-- Add modal contract sort popup start-->
<div class="modal fade" id="contract_name_sorting_modal" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="contract_sorting_modal_title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h4 class="modal-title" style="font-weight: bold;">Active Contracts</h4>
        </div>
        <div class="modal-body">
            <ul class='ul_contract_names' id="ul_li_contract_names" name="ul_li_contract_names" style="width: 100%; height: 200px; overflow-y: auto; list-style-type:none; padding-left:0px;">
                    
            </ul>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="button" id="btn_sorting_submit" class="btn btn-primary">Save changes</button>
        </div>
        </div>
    </div>
</div>
<!-- Added modal popup end-->

@endsection
@section('scripts')
<script type="text/javascript">
	$(document).ready(function(){
		var contract_count = 0;
		$('#contracts').find('tbody tr').each(function() {
			contract_count ++;
		});
		
		if(contract_count > 1){
			$('#sort_order').show();
		}else{
			$('#sort_order').hide();
		}
			
		$('#sort_order').click(function(){
			$('ul.ul_contract_names').empty();

            var practice_id = $('#practice_id').val();
			var physician_id = $('#physician_id').val();

            $.ajax({
                type:"get",
                // dataType: "json",
                // data: {'practice_id': practice_id, 'physician_id': physician_id},
                url:'/getsortingcontractnames/' + practice_id + '/' + physician_id,
                success:function(response){

                    $.each(response, function(key, data) {
                        $('#ul_li_contract_names').append('<li sort_order='+ data.sort_order +' value='+ data.contract_id +'>' + data.contract_name + '</li>');
					});
                    $('#ul_li_contract_names li').css( {'padding':'5px', 'margin':'5px', 'border':'1px solid #ccc', 'border-radius':'6px'});
                }
            });
   
		});
		
		$('#btn_sorting_submit').click(function(){
			var sorting_contract_array = [];
			var practice_id = $('#practice_id').val();
			var physician_id = $('#physician_id').val();
			$('#ul_li_contract_names li').each(function(i){

				var index = i + 1;
				var contract_id = $(this).val();
				
				sorting_contract_array.push({
					practice_id: practice_id,
					physician_id: physician_id,
					contract_id: contract_id,
					sort_order: index
				});
			});
			
			// AJAX Call save contract sort order
			if(sorting_contract_array.length > 0){
				saveSortingContractNames(sorting_contract_array);
			}

            $('#contract_name_sorting_modal').modal('hide');
			
		});
	});
	
	function saveSortingContractNames(sorting_contract_array){
		$.ajax({
			type:"POST",
			dataType: "json",
			data: {contract_list: sorting_contract_array},
			url:'/savesortingcontractnames',
			success:function(response){
				// alert('Contract sort order changed successfully.');
                location.reload();
			}
		});
	}
	
	$(function  () {
		$("ul.ul_contract_names").sortable();
	});
	
    $(function () {
        Dashboard.confirm({
            button: '.btn-welcome',
            dialog: '#modal-confirm-welcome',
            dialogButton: '#modal-confirm-welcome .btn-primary'
        });

        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
            dialogButton: '#modal-confirm-delete .btn-primary'
        });

        Dashboard.pagination({
            container: '#contracts',
            filters: '#contracts .filters a',
            sort: '#contracts .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });

        $('#all').on('click', function (event) {
            $('#practices option').prop('selected', this.checked);
        });
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection
