@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap-duallistbox.css') }}"/>
<!-- <script type="text/javascript" src="{{ asset('assets/js/jquery.bootstrap-duallistbox.js') }}"></script> -->
<link rel="stylesheet"  href="{{ asset('assets/css/bootstrap.min.css') }}"/>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js"></script>
<style>
@import url("https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css");

  .panel-title1 > a:before {
		float: left !important;
		font-family: FontAwesome;
		content:"\f068";
		font-size: 16px;
		font-weight: 100;
		padding-right: 4px;
		border-radius: 60px;
		color: #f68a1f;
		padding-left: 10px;
		margin-left: 1px;
		width: 5%;
    }
    .panel-title1 > a.collapsed:before {
		float: left !important;
		content: "\f067";
		font-family: FontAwesome;
		padding-right: 4px;
		border-radius: 60px;
		color: #f68a1f;
		padding-left: 10px;
		margin-left: 1px;
		font-size: 16px;
		font-weight: 100;
		width: 5%;
	}
    .panel-title > a:hover,
    .panel-title > a:active,
    .panel-title > a:focus  {
        text-decoration:none;

    }

    .panel-heading1 {
        background-color: #8e8174 !important;
        color: #fff !important;
        background-image: none !important;
        padding: 1%;
        position: relative
    }

    .panel-title1 {
        margin-top: 0;
        margin-bottom: 0;
        font-size: 16px;
        color: inherit;
        line-height: 36px;
    }
    .action-container {
        width:50% !important;
        float:left;
    }

    .actionCheckbox {
        float:left !important;
    }

	.actionCheckbox1 {
        float:right !important;
    }
    .actionWrap {
        max-width: 80%;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        float:left;
    }

    input.form-control.check {
        height: 20px;
        width: 20px;
    }

    .collapse-level-two-circle {
		position: absolute;
		left: 25px;
		/* top: 50%; */
		transform: translateY(-50%);
		font-size: 14px;
		color: #f68a1f;
		transition: .5s;
		/* line-height: 18px; */
		border: solid 3px #f68a1f;
		border-radius: 20px;
		width: 27px;
		height: 28px;
		margin-top: 16px;
		margin-left: -13px;
	}

	.panel-select-all {
		padding: 10px 15px;
		border-bottom: 1px solid transparent;
		border-top-left-radius: 3px;
		border-top-right-radius: 3px;
		float: right;
	}

	
</style>

@extends('layouts/_hospital', [ 'tab' => 7 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('hospital_id', $hospital->id) }}
	{{ Form::hidden('categories_count', $categories_count) }}

    <div class="panel panel-default">
        <div class="panel-heading">
            Hospital Duty Management
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('hospitals.edit', [$hospital->id]) }}">
                Back
            </a>
        </div>

		<div class="container" style="width: 100%;padding: 0px 0px 0px 0px;margin: 0px 0px 0px 0px;" id="action_category">
			<div id="activities" class="panel panel-default">
				<div class="panel-select-all hide">
					<span style="color:black;font-weight:600;text-decoration: none;">Select All</span>
					{{ Form::checkbox('', '', true,['id' => 'chk_select_all']) }}
				</div>
				<div class="row">
					<div class="col-md-3" style="width: 100%;">
						<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
							<div class="panel panel-default">
								@foreach($categories as $category)
									<div class="panel panel-default">
										<div class="panel-heading1" role="tab" id="{{$category->id}}" name="action-div" value="{{$category->id}}" >
											<input type="hidden" name="categoryid" value="{{$category->id}}">
											<input type="hidden" id = "hospital_actions_count" name="hospital_actions_count" value="{{$hospital_actions_count}}">
											<input type="hidden" id = "hospital_actions_is_active_false" name="hospital_actions_is_active_false" value="{{$hospital_actions_is_active_false}}">
											<h4 class="panel-title1">
												<a class="collapsed" data-toggle="collapse" style="color:black;font-weight:600;text-decoration: none;margin-left: 10px;" href="#category_{{$category->id}}" aria-expanded="false" aria-controls="category_{{$category->id}}">
													<div class="collapse-level-two-circle" ></div>
													{{ $category->name}}
												</a>
												
												{{ Form::checkbox('categoriescheckbox[]', $category->id, false, ['class' => 'actionCheckbox1', 'id' => 'chk_category_'.$category->id]) }}
												<div class="alert alert-danger" style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                    <a class="close" id="duplicateaction">&times;</a>
                                                    Action already present under category.
                    							</div>
											</h4>
										</div>
										<?php
											$category_show = false;
											$action_error_arr = [];
											if (Session::has('action_error')){
												$action_error_arr = Session::get('action_error');
											}
										?>

										<div id="category_{{$category->id}}" class="panel-collapse {{ (isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? 'in' : '' }} collapse" role="tabpanel" aria-labelledby="headingOne" value="{{$category->id}}">
											<div class="panel-body">
												@if($hospital_actions_count > 0)
													@foreach($actions as $action)
														@if($action->category_id == $category->id )
															<div class="col-xs-4" class="action-container" >
															{{ Form::hidden('action_category_id_'.$action->id,$category->id,['id' => 'action_category_id_'.$action->id]) }}
																@if($action->name!="")
																	<?php $checked = false; ?>
																	@foreach($hospital_actions as $hospital_action)
																		@if($action->id == $hospital_action->id)
																			<?php $checked = true; ?>
																		@endif
																	@endforeach
																	@if(in_array($action->id, $action_contract))
																		{{ Form::checkbox('actions[]', $action->id, $checked, array('class'=>'actionCheckbox','disabled')) }}
																	@else
																		{{ Form::checkbox('actions[]', $action->id, $checked,['class' => 'actionCheckbox']) }}
																	@endif

																	<!-- {{ Form::checkbox('actions[]', $action->id, $checked,['class' => 'actionCheckbox']) }} -->
																	<span id="span_{{$action->id}}" title="{{$action->name}}" class="actionWrap">{{ $action->name }}</span>
																@endif
															</div>
														@endif
													@endforeach
												@else
													<?php $checked = true; ?>
													@if($hospital_actions_is_active_false > 0)
														<?php $checked = false; ?>
													@endif
													@foreach($actions as $action)
														@if($action->category_id == $category->id )
															<div class="col-xs-4" class="action-container" >
																@if($action->name!="")
																	{{ Form::checkbox('actions[]', $action->id, $checked,['class' => 'actionCheckbox']) }}
																	<span id="span_{{$action->id}}" title="{{$action->name}}" class="actionWrap">{{ $action->name }}</span>
																@endif
															</div>
														@endif
													@endforeach
												@endif

												@if(is_super_user()||is_super_hospital_user())
												<div class="col-xs-12">
													{{ Form::hidden('[custom_count]',Request::old('custom_count',1),['id' => 'custom_count_'.$category->id]) }}

													<div id="customaction_{{$category->id}}" style="padding-top: 35px;">
														@if(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0)
															@foreach( $action_error_arr['customaction_name_'.$category->id] as $action_name=>$flag)
																@if($flag == true)
																	<div class="form-group" id="custom_action_div_{{preg_replace('/\s+/', '', $action_name)}}_{{$category->id}}_1"  style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
																		<label class="col-xs-2">Custom Action </label>
																		<div class="col-xs-5">
																			<input  type="text" name="customaction_name_{{$category->id}}[]" class="form-control custom_name_input" value="{{$action_name}}"/>
																			<p class="validation-error">Action already exist under this category.</p>
																		</div>
																		<div class="col-xs-2"><button type="button" name="remove" class="btn btn-primary btn-submit btn_remove" referId="custom_action_div_{{preg_replace('/\s+/', '', $action_name)}}_{{$category->id}}_1" >-</button></div>
																	</div>
																@else
																	<div class="form-group" id="custom_action_div_{{$category->id}}_1"  style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
																		<label class="col-xs-2">Custom Action </label>
																		<div class="col-xs-5">
																			<input  type="text" name="customaction_name_{{$category->id}}[]" class="form-control custom_name_input" value="{{$action_name}}"/>
																		</div>
																		<div class="col-xs-2"><button type="button" name="remove" class="btn btn-primary btn-submit btn_remove" referId="custom_action_div_{{$category->id}}_1" >-</button></div>
																	</div>
																@endif
															@endforeach
														@else
															<div class="form-group" id="custom_action_div_{{$category->id}}_1" style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}" >
																<label class="col-xs-2">Custom Action </label>
																<div class="col-xs-5">
																	<input  type="text"   name="customaction_name_{{$category->id}}[]"  class="form-control custom_name_input" />
																</div>
																<div class="col-xs-2"><button type="button" name="remove" class="btn btn-primary btn-submit btn_remove" referId="custom_action_div_{{$category->id}}_1" >-</button></div>
															</div>
														@endif
														<button class="btn btn-primary btn-submit add_custom" id="add_custom_{{$category->id}}" type="button" addcustrefId="{{$category->id}}" >Add Custom Action</button>
													</div>
												</div>
												@endif
											</div>
										</div>
									</div>
								@endforeach
							</div>
						</div>
					</div>
				</div>
			</div>
			<div id="mandate_details_div" class="col-md-12">
				<div class="bootstrap-duallistbox-container row moveonselect moveondoubleclick">
				<div class="box1 col-md-5">
					<div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
						<span>Mandate Details</span>
					</div>
					<div style="padding:2% 0% 0% 0%">	
						<select multiple="multiple" id="mandate_details" name="mandatedetails[]" class="form-control" title="" style="height: 254px;">
							@if($hospital_actions_count > 0)
								@foreach($mandate_details as $mandate_detail)
									<?php $check_record = false; ?>
									@foreach($override_mandate_details as $override_mandate_detail)
										@if($mandate_detail->id == $override_mandate_detail->id)
											<?php $check_record = true; ?>
										@endif
									@endforeach

									@if($mandate_detail->name !="" && !$check_record))
										<option value="{{ $mandate_detail->id }}">{{ $mandate_detail->name}}</option>
									@endif
								@endforeach
							@elseif($hospital_actions_is_active_false > 0)
								
							@else
								@foreach($categories as $category)
									@foreach($actions as $action)
										@if($action->category_id == $category->id )
											@if($action->name!="")
												<option value="{{ $action->id }}">{{ $action->name}}</option>
											@endif
										@endif
									@endforeach	
								@endforeach		
							@endif
						</select>
					</div>
				</div>

				<div class="button-box col-md-2">
					<div style="padding:31% 28%">
						<div style="padding:19%">
							<input class="btn btn-default" type="button" id="btnRight" value="  >  ">
						</div>
						<div style="padding:20% 13%">
							<input class="btn btn-default" type="button" id="btnRightAll" value="  >>  ">
						</div>
						<div style="padding:19%">
							<input class="btn btn-default" type="button" id="btnLeft" value="  <  ">
						</div>
						<div style="padding:20% 13%">
							<input class="btn btn-default" type="button" id="btnLeftAll" value="  <<  ">
						</div>
					</div>	
				</div>

				<div class="box2 col-md-5">
					<div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
						<span>Override Mandate Details</span>
					</div>	
					<div style="padding:2% 0% 0% 0%"> 
						<select multiple="multiple" id="override_mandate_details" name="overridemandatedetails[]" class="form-control" title="" style="height: 254px;">
							@foreach($override_mandate_details as $override_mandate_detail)
								@if($override_mandate_detail->name!="")
									<option selected value="{{ $override_mandate_detail->id }}">{{ $override_mandate_detail->name}}</option>
								@endif
							@endforeach
						</select>
					</div>
					<p class="help-block">
						@if($override_mandate_details_count > 0)
							{{ Form::checkbox('', '', true,['id' => 'chk_override_mandate_details']) }}
							<span id="span_override_mandate_details" style="font-size: 14px">Deselect All</span>
						@else
							{{ Form::checkbox('', '', false,['id' => 'chk_override_mandate_details']) }}
							<span id="span_override_mandate_details" style="font-size: 14px">Select All</span>
						@endif	
					</p>
				</div>
			</div>
			</div>
			<div class="col-md-12">
			</div>
			<div id="quarter_hour_entry_div" class="col-md-12">
				<div class="bootstrap-duallistbox-container row moveonselect moveondoubleclick">
					<div class="box3 col-md-5">
						<div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
							<span>Quarter Hour Entry</span>
						</div>
						<div style="padding:2% 0% 0% 0%">
							<select multiple="multiple" id="quarter_hour_entry" name="quarterhourentry[]" class="form-control" title="" style="height: 254px;">
								@if($hospital_actions_count > 0)
									@foreach($quarter_hour_entries as $quarter_hour_entry)
										<?php $check_exist = false; ?>
										@foreach($time_stamp_entries as $time_stamp_entry)
											@if($quarter_hour_entry->id == $time_stamp_entry->id)
												<?php $check_exist = true; ?>
											@endif
										@endforeach

										@if($quarter_hour_entry->name!="" && !$check_exist)
											<option value="{{ $quarter_hour_entry->id }}">{{ $quarter_hour_entry->name}}</option>
										@endif
									@endforeach
								@elseif($hospital_actions_is_active_false > 0)

								@else 
									@foreach($categories as $category)
										@foreach($actions as $action)
											@if($action->category_id == $category->id )
												@if($action->name!="")
													<option value="{{ $action->id }}">{{ $action->name}}</option>
												@endif
											@endif
										@endforeach	
									@endforeach
								@endif
							</select>
						</div>
					</div>

					<div class="button-box col-md-2">
						<div style="padding:31% 28%">
							<div style="padding:19%">
								<input class="btn btn-default" type="button" id="btnRight_1" value="  >  ">
							</div>
							<div style="padding:20% 13%">
								<input class="btn btn-default" type="button" id="btnRightAll_1" value="  >>  ">
							</div>
							<div style="padding:19%">
								<input class="btn btn-default" type="button" id="btnLeft_1" value="  <  ">
							</div>
							<div style="padding:20% 13%">
								<input class="btn btn-default" type="button" id="btnLeftAll_1" value="  <<  ">
							</div>
						</div>	
					</div>

					<div class="box4 col-md-5">
						<div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
							<span>Time Stamp Entry</span>
						</div>	
						<div style="padding:2% 0% 0% 0%">
							<select multiple="multiple" id="time_stamp_entry" name="timestampentry[]" class="form-control" title="" style="height: 254px;">
								@foreach($time_stamp_entries as $time_stamp_entry)
									@if($time_stamp_entry->name!="")
										<option selected value="{{ $time_stamp_entry->id }}">{{ $time_stamp_entry->name}}</option>
									@endif
								@endforeach
							</select>
						</div>
						<p class="help-block">
							@if($time_stamp_entries_count > 0)
								{{ Form::checkbox('', '', true,['id' => 'chk_time_stamp_entry']) }}	
								<span id="span_time_stamp_entry" style="font-size: 14px">Deselect All</span>
							@else
								{{ Form::checkbox('', '', false,['id' => 'chk_time_stamp_entry']) }}	
								<span id="span_time_stamp_entry" style="font-size: 14px">Select All</span>
							@endif
							
						</p>
					</div>
				</div>
			</div>
		</div></br></br>

        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit btn_submit" type="submit">Submit</button>
        </div>
    </div>
    {{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
		/* $('input[type=checkbox]').each(function() {
			$(this).prop( "checked", true );
		});*/

		var categories_count = $('input[name="categories_count"]').val();
		var hospital_actions_count = $('#hospital_actions_count').val();
		var hospital_actions_is_active_false = $('#hospital_actions_is_active_false').val();

		// if(hospital_actions_count == 0 && hospital_actions_is_active_false == 0){
		// 	$('input[type=checkbox]').each(function() {
		// 		$(this).prop( "checked", true );
		// 	});
		// }else if(hospital_actions_count == 0 && hospital_actions_is_active_false == 0){
		// 	$('input[type=checkbox]').each(function() {
		// 		$(this).prop( "checked", true );
		// 	});
		// }
		// else{
			if(hospital_actions_count > 0 || hospital_actions_count == 0 && hospital_actions_is_active_false == 0){
				for(var j = 1; j<= categories_count; j++){
					$('#category_' + j + ' input[type=checkbox]').each(function() {
						if ($(this).is(':checked')) {
							$('#chk_category_' + j).prop( "checked", true );	
						}
					});
				}
			}
		// }

		$('#chk_select_all').on("click", function () {
			if ($(this).is(':checked')) {
				$('input[type=checkbox]').each(function() {
					$(this).prop( "checked", true );
					$('#mandate_details').append('<option value='+ $(this).val() +'>' + $('#span_' + $(this).val()).html() + '</option>');
					$('#quarter_hour_entry').append('<option value='+ $(this).val() +'>' + $('#span_' + $(this).val()).html() + '</option>');  
				});
			}else{
				// $('input[type=checkbox]').each(function() {
				// 	$(this).prop( "checked", false );
				// 	$('#mandate_details option[value='+ $(this).val() +']').remove();
				// 	$('#override_mandate_details option[value='+ $(this).val() +']').remove();
				// 	$('#quarter_hour_entry option[value='+ $(this).val() +']').remove();
				// 	$('#time_stamp_entry option[value='+ $(this).val() +']').remove();
				// });
			}
		});

		var checkboxes = $('input[name="categoriescheckbox[]"]');
		checkboxes.on("click", function () {

			var category_id = $(this).val();
			if ($(this).is(':checked')) {
				$('#category_' + this.value + ' input[type=checkbox]').each(function() {
					$(this).prop( "checked", true );
					// if(category_id != 9 && category_id != 10 && category_id != 11 && category_id != 12){
						if ($("#mandate_details option[value='" + $(this).val() + "']").length == 0) {
							$('#mandate_details').append('<option value='+ $(this).val() +'>' + $('#span_' + $(this).val()).html() + '</option>');
						}
						if ($("#quarter_hour_entry option[value='" + $(this).val() + "']").length == 0) {
							$('#quarter_hour_entry').append('<option value='+ $(this).val() +'>' + $('#span_' + $(this).val()).html() + '</option>');
						}
					// }
				});

			}
			else{
				$('#category_' + this.value + ' input[type=checkbox]').each(function() {
					$(this).prop( "checked", false );
					$('#mandate_details option[value='+ $(this).val() +']').remove();
					$('#override_mandate_details option[value='+ $(this).val() +']').remove();
					$('#quarter_hour_entry option[value='+ $(this).val() +']').remove();
					$('#time_stamp_entry option[value='+ $(this).val() +']').remove();
				});

				if( $('#override_mandate_details').has('option').length == 0 ) {
					$('#chk_override_mandate_details').prop( "checked", false );
					$("#span_override_mandate_details").html('');
					$("#span_override_mandate_details").html('Select All');
				}

				if( $('#time_stamp_entry').has('option').length == 0 ) {
					$('#chk_time_stamp_entry').prop( "checked", false );
					$("#span_time_stamp_entry").html('');
					$("#span_time_stamp_entry").html('Select All');
				}
			}
		});

		var value = $('input[name="actions[]"]');
		value.on("click", function () {
			// alert($('#action_category_id_' + $(this).val()).val());
			var category_id = $('#action_category_id_' + $(this).val()).val();
			// if(category_id != 9 && category_id != 10 && category_id != 11 && category_id != 12){
				if ($(this).is(':checked')) {
					$('#mandate_details').append('<option value='+ $(this).val() +'>' + $('#span_' + $(this).val()).html() + '</option>');
					$('#quarter_hour_entry').append('<option value='+ $(this).val() +'>' + $('#span_' + $(this).val()).html() + '</option>');
					// $('#chk_category_6').prop( "checked", true );
				}else{
					$('#mandate_details option[value='+ $(this).val() +']').remove();
					$('#override_mandate_details option[value='+ $(this).val() +']').remove();
					$('#quarter_hour_entry option[value='+ $(this).val() +']').remove();
					$('#time_stamp_entry option[value='+ $(this).val() +']').remove();
				}
			// }
		});
		

		$('.add_custom').click(function(){
            var add_btn_id = $(this).attr('addcustrefId');
            var i = $('#custom_count_'+ add_btn_id).val();
            i = parseInt(i)+1;
            $('#customaction_'+ add_btn_id).append('<div class="form-group invoive-note" id="custom_action_div_'+ add_btn_id +'_'+i+'"><label class="col-xs-2" id="label_'+ i +'">Custom Action </label><div class="col-xs-5"><input  type="text" name="customaction_name_'+ add_btn_id +'[]" class="form-control custom_name_input"/></div><div class="col-xs-2"><button type="button" name="remove" id="remove_'+ i +'" class="btn btn-primary btn-submit btn_remove"  referId="custom_action_div_'+ add_btn_id +'_'+i+'">-</button></div></div>');
            $('#custom_count_'+ add_btn_id).val(i);
        });

		$(document).on('click', '.btn_remove', function(){
            var category_id = $(this).attr("referId");
        	$('#'+ category_id).remove();

            var category_sub_id = $(this).attr("refcatId");
            $('#'+ category_id).remove();
        });

		$('#btnRight').click(function(e) {  
            $('#mandate_details > option:selected').appendTo('#override_mandate_details');  

			if( $('#override_mandate_details').has('option').length > 0 ) {
				$('#chk_override_mandate_details').prop( "checked", true );
				$("#span_override_mandate_details").html('');
				$("#span_override_mandate_details").html('Deselect All');
			}
            e.preventDefault(); 
        });  
  
        $('#btnRightAll').click(function(e) {  
            $('#mandate_details > option').appendTo('#override_mandate_details');

			$("#override_mandate_details > option").each(function() {
				$(this).prop("selected", true);
			});

			if( $('#override_mandate_details').has('option').length > 0 ) {
				$('#chk_override_mandate_details').prop( "checked", true );
				$("#span_override_mandate_details").html('');
				$("#span_override_mandate_details").html('Deselect All');
			}  
            e.preventDefault();  
        });  
  
        $('#btnLeft').click(function(e) {  
            $('#override_mandate_details > option:selected').appendTo('#mandate_details');   

			$("#override_mandate_details > option").each(function() {
				$(this).prop("selected", true);
			});

			if( $('#override_mandate_details').has('option').length == 0 ) {
				$('#chk_override_mandate_details').prop( "checked", false );
				$("#span_override_mandate_details").html('');
				$("#span_override_mandate_details").html('Select All');
			}
			
			e.preventDefault(); 
        });  
  
        $('#btnLeftAll').click(function(e) {  
            $('#override_mandate_details > option').appendTo('#mandate_details');  
			
			$('#chk_override_mandate_details').prop( "checked", false );
			$("#span_override_mandate_details").html('');
			$("#span_override_mandate_details").html('Select All');
			
            e.preventDefault();  
        });
		
		$('#btnRight_1').click(  
            function(e) {  
                $('#quarter_hour_entry > option:selected').appendTo('#time_stamp_entry');  

				if( $('#time_stamp_entry').has('option').length > 0 ) {
					$('#chk_time_stamp_entry').prop( "checked", true );
					$("#span_time_stamp_entry").html('');
					$("#span_time_stamp_entry").html('Deselect All');
				}
                e.preventDefault();  
        });  
  
        $('#btnRightAll_1').click(  
            function(e) {  
                $('#quarter_hour_entry > option').appendTo('#time_stamp_entry');  

				$("#time_stamp_entry > option").each(function() {
					$(this).prop("selected", true);
				});

				if( $('#time_stamp_entry').has('option').length > 0 ) {
					$('#chk_time_stamp_entry').prop( "checked", true );
					$("#span_time_stamp_entry").html('');
					$("#span_time_stamp_entry").html('Deselect All');
				}  

                e.preventDefault();  
        });  
  
        $('#btnLeft_1').click(  
            function(e) {  
                $('#time_stamp_entry > option:selected').appendTo('#quarter_hour_entry');  

				$("#time_stamp_entry > option").each(function() {
					$(this).prop("selected", true);
				});

				if( $('#time_stamp_entry').has('option').length == 0 ) {
					$('#chk_time_stamp_entry').prop( "checked", false );
					$("#span_time_stamp_entry").html('');
					$("#span_time_stamp_entry").html('Select All');
				}

                e.preventDefault();  
				
        });  
  
        $('#btnLeftAll_1').click(  
            function(e) {  
                $('#time_stamp_entry > option').appendTo('#quarter_hour_entry');  

				$('#chk_time_stamp_entry').prop( "checked", false );
				$("#span_time_stamp_entry").html('');
				$("#span_time_stamp_entry").html('Select All');

                e.preventDefault();  
        });

		$('#chk_override_mandate_details').click(function(e){
			if ($(this).is(':checked')) {
				$("#override_mandate_details > option").each(function() {
					$(this).prop("selected", true);
				});

				$("#span_override_mandate_details").html('');
				$("#span_override_mandate_details").html('Deselect All');
			}else{
				$("#override_mandate_details > option").each(function() {
					$(this).prop("selected", false);
				});

				$("#span_override_mandate_details").html('');
				$("#span_override_mandate_details").html('Select All');
			} 
		});

		$('#chk_time_stamp_entry').click(function(e){
			if ($(this).is(':checked')) {
				$("#time_stamp_entry > option").each(function() {
					$(this).prop("selected", true);
				});

				$("#span_time_stamp_entry").html('');
				$("#span_time_stamp_entry").html('Deselect All');
			}else{
				$("#time_stamp_entry > option").each(function() {
					$(this).prop("selected", false);
				});

				$("#span_time_stamp_entry").html('');
				$("#span_time_stamp_entry").html('Select All');
			} 
		});	

		$('.btn_submit').click(function () {
			for(var k = 1; k <= categories_count; k++){
				$('#category_' + k + ' input[type=checkbox]').each(function() {
					if($(this).is(':checked') && $(this).is(':disabled')) {
						$(this).removeAttr("disabled");
					}
				});
			}
		});
	})
	
</script>
@endsection