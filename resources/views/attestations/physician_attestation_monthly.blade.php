@extends('layouts/_dashboard')
@section('main')
	<div class="page-header">
		<h3>
			<i class="fa fa-list fa-fw icon"></i> Attestations
		</h3>

		<div class="btn-group btn-group-sm">
			<!--<a class="btn btn-default" href="{{ URL::route('attestations.physician', [$contract->physician_id, $contract->id, $dateSelector]) }}">
				<i class="fa fa-arrow-circle-left fa-fw"></i> Back
			</a>-->
			<a class="btn btn-default" href="javascript: history.go(-1)"><i class="fa fa-arrow-circle-left fa-fw"></i> Back </a>
		</div>
	</div>
{{ Form::open(['class' => 'form form-horizontal form-question-answer']) }}
	{{ Form::hidden('physician_id', $physician->id , array('id' => 'physician_id')) }}
	{{ Form::hidden('contract_id', $contract->id , array('id' => 'contract_id')) }}
	{{ Form::hidden('dateSelector', $dateSelector , array('id' => 'dateSelector')) }}
	
    <div class="panel panel-default">
        <div class="panel-heading">
            Monthly Attestations
        </div>
        <div class="panel-body">
            @foreach ($attestations as $attestation)
				<div class="col-md-12" style="padding:0px; font-size: 17px; text-align:center; "><b>{{ $attestation['attestation_name'] }}</b></div>
				@foreach ($attestation['questions'] as $question)
					<div class="col-md-12">{!! $question->question !!}</div>
					@if($question->question_type == 1 || $question->question_type == 3)
						<div class="col-md-12 form-check">
							<input type="radio" class="form-check-input" id="radio_yes" name="{{ $question->id }}" value="Yes" checked> Yes
							<label class="form-check-label" for="radio1"></label>
						</div>
						<div class="col-md-12 form-check">
							<input type="radio" class="form-check-input" id="radio_no" name="{{ $question->id }}" value="No"> No
							<label class="form-check-label" for="radio2"></label>
						</div>

						@if($question->question_type == 3)
							<div class="col-md-12 form-check">
								<input type="radio" class="form-check-input" id="radio_na" name="{{ $question->id }}" value="NA"> NA
								<label class="form-check-label" for="radio3"></label>
							</div>
						@endif
					@elseif($question->question_type == 2)
						<div class="col-md-12">
							{{ Form::textarea($question->id, Request::old($question->id), [ 'class' => 'form-control txtanswers','id' => "txtanswer_".$question->id,'maxlength' => 3000, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
						</div>
					@else
						
					@endif
					<div class="col-md-12">
						<div style="margin-top: 10px; margin-bottom: 10px; border: 0;border-top: 2px solid #eee;"></div>
					</div>
				@endforeach
			@endforeach
        </div>
        <div class="panel-footer clearfix">
			<button class="btn btn-primary btn-sm btn-submit btn_next" style="float: right; type="submit">Submit</button>
			<!--<a class="btn btn-primary" style="float: right;" name="bntApprove" id="bntApprove" href="{{ URL::route('physicians.signatureApprove', [$contract->physician_id,$contract->id, $dateSelector]) }}">
				Submit
            </a>-->
		</div>
    </div>
{{ Form::close() }}
@endsection
@section('scripts')
	<script type="text/javascript" src="{{ asset('assets/js/logEntry.js') }}"></script>
    <script type="text/javascript">
        function submitLog(){
            $('.save').prop('disabled',true);
            $(".overlay").show();
        }
        document.onreadystatechange = function () {
            var state = document.readyState;
            if (state == 'interactive') {
                $(".overlay").show();
            } else if (state == 'complete') {
                var timeZone = new Date();
                var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;
                if(typeof zoneName === "undefined")
                {
                    timeZone = '';
                    zoneName ='';
                }
                $('#timeZone').val(timeZone);
                $('#localTimeZone').val(zoneName);
                setTimeout(function(){
                    document.getElementById('interactive');
                    $(".overlay").hide();
                },2000);
            }
        }
        $("#edit_signature").click(function (){
            $("#signature-pad").show();
            $("#signature_view").hide();
        });
        $(document).ready(function () {
            $(".overlay").hide();
			$('.btn_next').click(function(){
				var physician_id = $('#physician_id').val();
				var contract_id = $('#contract_id').val();
				
				sessionStorage.removeItem('monthly_questions');
				var questions_answere_monthly = [];
				
				$('input[type=radio]').each(function() {
					if($(this).is(":checked")){
						questions_answere_monthly.push({
							"question_id": $(this).attr('name'),
							"answer": $(this).val()
						});
					}
				});

				$('.txtanswers').each(function() {
					questions_answere_monthly.push({
						"question_id": $(this).attr('name'),
						"answer": $(this).val()
					});
				});
				
				$.ajax({
					url:'',
					type:'get',
					data : { questions_answere: questions_answere_monthly},
					success:function(){
						if(questions_answere_monthly.length > 0){
							// Call Approve signature route
							sessionStorage.setItem('monthly_questions', JSON.stringify(questions_answere_monthly));
							window.location.href = "{{ URL::route('physicians.signatureApprove', [$physician->id, $contract->id, $dateSelector]) }}"
						}else{
							// 
						}
					}
				});
			});
		});
    </script>
@endsection
