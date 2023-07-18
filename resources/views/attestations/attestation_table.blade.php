@if (count($attestation_questions) > 0)
	<table class="table table-striped table-hover attestation-table">
		<thead>
		<tr>
			<th>Questions</th>
			<th class="text-right" width="100">Actions</th>
		</tr>
		</thead>
		<tbody data-link="row" class="rowlink">
		@foreach ($attestation_questions as $attestation_question)
			<tr>
				<td>
					 <a href="{{ URL::route('question.edit', [$attestation_question->state_id, $attestation_question->attestation_id, $attestation_question->id]) }}">
						{!! $attestation_question->question !!}
					</a>
				</td>
				<td class="text-right rowlink-skip">
					<div class="btn-group btn-group-xs">
						<a class="btn btn-default btn-delete"
						   href="{{ URL::route('attestations.delete_attestation_question', [ $attestation_question->state_id, $attestation_question->attestation_id, $attestation_question->id ]) }}">
							<i class="fa fa-trash-o fa-fw"></i>
						</a>
					</div>
				</td>
			</tr>
		@endforeach
		</tbody>
	</table>
@else
	<div class="panel panel-default hide">
		<div class="panel-body">
			There are no state attestation to display at this time.
		</div>
	</div>
@endif
<!-- <script type="text/javascript" src="{{ asset('assets/js/ckeditor.js') }}"></script> -->
<script type="text/javascript">
	$( document ).ready(function() {
		$('.fa-edit').click(function(){
			var state_id = $(this).attr('state_id');
			var question_id = $(this).attr('attestation_id');
			var question_title = $(this).attr('text');

			$("#question").attr("state_id", state_id);
			$("#question").attr("question_id", question_id);
			$('#question').html(question_title);
			$('.ck-editor__editable').html(question_title);
		});
		
		$('.edit-question').click(function(){
			$('.edit-question').addClass("disabled");
			
			$.ajax({
				url:'/updateAttestation',
				type:'post',
				headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
				data:{state_id: $("#editor").attr("state_id"),
					question_id: $("#editor").attr("question_id"),
					question: $("#editor").val()
				},
				success:function(response){
					if(response > 0){
						location.reload();
					}
				},
				complete:function(){
						
				}
			});
		});
	});
</script>