@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', ['tab' => 2])
@section('actions')
    <a class="btn btn-default" href="{{ route('agreements.show', $agreement->id) }}">
        <i class="fa fa-arrow-circle-left fa-fw"></i> Back
    </a>
@endsection
@section('content')
    <div class="panel panel-default">
        {{ Form::open([ 'class' => 'form form-horizontal form-create-agreement' ]) }}
        {{ Form::hidden('id', $agreement->id) }}
        <div class="panel-heading">Renew Agreement</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Name</label>

                <div class="col-xs-4">
                    {{ Form::text('name', Request::old('name', $agreement->name), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Start Date</label>

                <div class="col-xs-4">
                    <div id="start-date" class="input-group">
                        {{ Form::text('start_date', Request::old('start_date', format_date($agreement->start_date)), [ 'class'
                        => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('start_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">End Date</label>

                <div class="col-xs-4">
                    <div id="end-date" class="input-group">
                        {{ Form::text('end_date', Request::old('end_date', format_date($agreement->end_date)), [ 'class' =>
                        'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('end_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Valid Up To Date</label>

                <div class="col-xs-4">
                    <div id="valid-upto" class="input-group">
                        @if($agreement->valid_upto!="0000-00-00")
                            {{ Form::text('valid_upto', Request::old('valid_upto', format_date($agreement->valid_upto)), [ 'class' => 'form-control' ]) }}
                        @else
                            {{ Form::text('valid_upto', Request::old('valid_upto'), [ 'class' => 'form-control' ]) }}
                        @endif
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('valid_upto', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Payment Frequency Type</label>
                <div class="col-xs-4">
                    <div id="payment-frequency-option">
                    {{ Form::select('payment_frequency_option', $payment_frequency_option, Request::old('payment_frequency_option',$agreement->payment_frequency_type), [ 'class' => 'form-control payment-frequency-option', 'disabled' => true ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Frequency Start Date</label>

                <div class="col-xs-4">
                    <div id="payment-start-date" class="input-group">
                        {{ Form::text('frequency_start_date', Request::old('frequency_start_date', format_date($agreement->payment_frequency_start_date)), [ 'class'
                        => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('frequency_start_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Approval Process</label>

                <div class="col-xs-4">
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            <!--<input id="on_off" name="on_off" type="checkbox" checked>-->
                            {{ Form::checkbox('on_off', 1, Request::old('on_off',$agreement->approval_process), ['id' => 'on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>

            <div id="approval_feilds" class="approvalContainer" style="display: none;">
                <div class="tableHeading">
                    <label class="col-xs-3 control-label"></label>
{{--                    <div class="col-md-3 col-sm-3 col-xs-3">--}}
{{--                        <strong>Approval Manager Type</strong>--}}
{{--                    </div>--}}
                    <div class="col-md-3 col-sm-3 col-xs-3">
                        <strong>Approval Manager</strong>
                    </div>
                    <div class="col-md-2 col-sm-1 col-xs-1">
                        <strong>Initial Review Day</strong>
                    </div>
                    <div class="col-md-2 col-sm-1 col-xs-1">
                        <strong>Final Review Day</strong>
                    </div>
                    <div class="col-md-2 col-sm-1 col-xs-1">
                        <strong>Opt-in email</strong>
                    </div>

                </div>

                @for($i = 1; $i <= 6; $i++)
                    <div class="form-group">
                        <label class="col-xs-3 control-label">Approval Level{{$i}}</label>

{{--                        <div class="col-md-3 col-sm-3 col-xs-3">--}}
{{--                            {{ Form::select('approverTypeforLevel'.$i, $approval_manager_type, Request::old('approverTypeforLevel'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->type_id : 0), [ 'class' => 'form-control approval_type' ]) }}--}}
{{--                        </div>--}}

                        <div class="col-md-3 col-sm-3 col-xs-3 paddingLeft">
                            {{ Form::select('approval_manager_level'.$i, $users, Request::old('approval_manager_level'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->user_id : '0'), [ 'class' => 'form-control select-managers' ]) }}
                        </div>

                        <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                            {{ Form::selectRange('initial_review_day_level'.$i, 1, $review_day_range_limit, Request::old('initial_review_day_level'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->initial_review_day : 10), [ 'class' => 'form-control' ]) }}
                        </div>

                        <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                            {{ Form::selectRange('final_review_day_level'.$i, 1, $review_day_range_limit, Request::old('final_review_day_level'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->final_review_day : 20), [ 'class' => 'form-control' ]) }}
                        </div>
                        <div class="col-md-2 col-sm-1 col-xs-1">
                            <input type="checkbox" name="emailCheck[]" value="level{{$i}}" @if(count($ApprovalManagerInfo) >= $i) @if($ApprovalManagerInfo[$i-1]->opt_in_email_status ==1) checked @endif @else checked @endif>
                        </div>

                        <div class="col-md-3 col-sm-3 col-xs-3"></div>
                        <div class="col-md-9">
                            <p class="validationFieldErr">{!! $errors->first('approverTypeforLevel'.$i, '<p class="validation-error">:message</p>') !!}</p>
                            <p class="validationFieldErr">{!! $errors->first('approval_manager_level'.$i, '<p class="validation-error">:message</p>') !!}</p>
                            <p class="validationFieldErr">{!! $errors->first('initial_review_day_level'.$i, '<p class="validation-error">:message</p>') !!}</p>
                            <p class="validationFieldErr">{!! $errors->first('final_review_day_level'.$i, '<p class="validation-error">:message</p>') !!}</p>
                        </div>
                    </div>
                    @endfor
            </div>

            @if ((is_super_user() || is_super_hospital_user()) && $hospital->invoice_dashboard_on_off == 1)
            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Reminder Day</label>

                <div class="col-xs-4">
                    {{ Form::selectRange('send_invoice_reminder_day', 1, $review_day_range_limit, Request::old('send_invoice_reminder_day',$agreement->send_invoice_day), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('send_invoice_reminder_day', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label paddingRight">Invoice Reminder Recipient #1</label>

                <div class="col-xs-4">
                    {{ Form::select('invoice_reminder_recipient1', $users, Request::old('invoice_reminder_recipient1', $agreement->	invoice_reminder_recipient_1), [ 'class' => 'form-control' ]) }}
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3">
                    <label style="margin-right: 20px;">Opt-in email</label>
                    <input type="checkbox" name="emailCheck_recipient_1" value="emailCheck_recipient1" @if($agreement->invoice_reminder_recipient_1_opt_in_email ==1) checked @endif>
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3"></div>
                <div class="col-md-9"><p class="validationFieldErr">{!! $errors->first('invoice_reminder_recipient1', '<p class="validation-error">:message</p>') !!}</p>

                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label paddingRight">Invoice Reminder Recipient #2</label>

                <div class="col-xs-4">
                    {{ Form::select('invoice_reminder_recipient2', $users, Request::old('invoice_reminder_recipient2', $agreement->invoice_reminder_recipient_2), [ 'class' => 'form-control' ]) }}
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3">
                    <label style="margin-right: 20px;">Opt-in email</label>
                    <input type="checkbox" name="emailCheck_recipient_2" value="emailCheck_recipient2" @if($agreement->invoice_reminder_recipient_2_opt_in_email ==1) checked @endif>
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3"></div>
                <div class="col-md-9"><p class="validationFieldErr">{!! $errors->first('invoice_reminder_recipient2', '<p class="validation-error">:message</p>') !!}</p>

                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Recipient #1</label>

                <div class="col-xs-4">
                    @if(count($invoice_receipient)>0)
                        {{ Form::text('invoice_receipient1', Request::old('invoice_receipient1',$invoice_receipient[0]), [ 'class' => 'form-control' ]) }}
                    @else
                        {{ Form::text('invoice_receipient1', Request::old('invoice_receipient1'), [ 'class' => 'form-control' ]) }}
                    @endif
                </div>
                <div class="col-xs-5">{!! $errors->first('invoice_receipient1', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Recipient #2</label>

                <div class="col-xs-4">
                    @if(count($invoice_receipient)>1)
                        {{ Form::text('invoice_receipient2', Request::old('invoice_receipient2',$invoice_receipient[1]), [ 'class' => 'form-control' ]) }}
                    @else
                        {{ Form::text('invoice_receipient2', Request::old('invoice_receipient2'), [ 'class' => 'form-control' ]) }}
                    @endif
                </div>
                <div class="col-xs-5">{!! $errors->first('invoice_receipient2', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Recipient #3</label>

                <div class="col-xs-4">
                    @if(count($invoice_receipient)>2)
                        {{ Form::text('invoice_receipient3', Request::old('invoice_receipient3',$invoice_receipient[2]), [ 'class' => 'form-control' ]) }}
                    @else
                        {{ Form::text('invoice_receipient3', Request::old('invoice_receipient3'), [ 'class' => 'form-control' ]) }}
                    @endif
                </div>
                <div class="col-xs-5">{!! $errors->first('invoice_receipient3', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            @endif


        </div>
        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
        </div>
        {{ Form::close() }}

        <div id="modal-add-user" class="modal fade" style="background: rgba(0,0,0,0.5); overflow: hidden;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"
                                aria-hidden="true" onclick="closeModel();">&times;</button>
                        <h4 class="modal-title">Add New User</h4>
                    </div>
                    <div class="modal-body">
                        <div class="panel-body">
                            <div class="col-xs-12">
                                <div id="enterLogMessageLog" class="alert" role="alert" style="display: none;">

                                </div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <label class="col-xs-2 control-label">Email</label>

                                <div class="col-xs-5">
                                    {{ Form::text('email', Request::old('email'), [ 'class' => 'form-control' ,'id'=>'user_email']) }}
                                </div>
                                <div class="col-xs-5"><p id="error-message" class="validation-error validation-error-email"> </p></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <label class="col-xs-2 control-label">First Name</label>

                                <div class="col-xs-5">
                                    {{ Form::text('first_name', Request::old('first_name'), [ 'class' => 'form-control','id'=>'user_first_name' ]) }}
                                </div>
                                <div class="col-xs-5"><p class="validation-error validation-error-first_name"> </p></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <label class="col-xs-2 control-label">Last Name</label>

                                <div class="col-xs-5">
                                    {{ Form::text('last_name', Request::old('last_name'), [ 'class' => 'form-control' ,'id'=>'user_last_name']) }}
                                </div>
                                <div class="col-xs-5"><p class="validation-error validation-error-last_name"> </p></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <label class="col-xs-2 control-label">Title</label>

                                <div class="col-xs-5">
                                    {{ Form::text('title', Request::old('title'), [ 'class' => 'form-control' ,'id'=>'user_title']) }}
                                </div>
                                <div class="col-xs-5"><p class="validation-error validation-error-title"> </p></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <label class="col-xs-2 control-label">Phone</label>

                                <div class="col-xs-5">
                                    {{ Form::text('phone', Request::old('phone'), [ 'class' => 'form-control','id'=>'user_phone', 'placeholder' => '(999) 999-9999' ]) }}
                                </div>
                                <div class="col-xs-5"><p class="validation-error validation-error-phone"> </p></div>
                            </div>
                            <div class="col-xs-12 form-group">
                                <label class="col-xs-2 control-label">Group</label>

                                <div class="col-xs-5">
                                    {{ Form::select('group', $groups, Request::old('group'), [ 'class' => 'form-control' ,'id'=>'user_group']) }}
                                    <input type="hidden" id="selected_value" name="selected_value" value="">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default"
                                    data-dismiss="modal" onclick="closeModel();">
                                Cancel
                            </button>
                            <button type="button" class="btn btn-primary" data-dismiss="modal"
                                    onClick="createUser();">Submit
                            </button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
            </div>
        </div>
    </div>


@endsection

@section('scripts')
    <!--<script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>-->
    <script type="text/javascript">
        $( document ).ready(function() {
            if($('#on_off'). prop("checked") == true){
                $("#approval_feilds").show();
            }else{
                $("#approval_feilds").hide();
            }

            $('.select-managers').each(function(){
                var name=$(this).attr('name');
                var select_number=name.match(/\d+/);
                if($(this).val()>0)
                {
                    // $('[name=approval_manager_level'+select_number+']').attr("disabled", false);
                    $('[name=initial_review_day_level'+select_number[0]+']').attr("disabled", false);
                    $('[name=final_review_day_level'+select_number[0]+']').attr("disabled", false);
                    $('[value=level'+select_number[0]+']').attr("disabled", false);
                }
                else
                {
                    // $('[name=approval_manager_level'+select_number+']').attr("disabled", true);
                    $('[name=initial_review_day_level'+select_number[0]+']').attr("disabled", true);
                    $('[name=final_review_day_level'+select_number[0]+']').attr("disabled", true);
                    $('[value=level'+select_number[0]+']').attr("disabled", true);
                }

            });

            // $('.approval_type').change(function(){
            //     var name=$(this).attr('name');
            //     var select_number=name.match(/\d+/);
            //     if($(this).val()>0)
            //     {
            //         $('[name=approval_manager_level'+select_number+']').attr("disabled", false);
            //         $('[name=initial_review_day_level'+select_number+']').attr("disabled", false);
            //         $('[name=final_review_day_level'+select_number+']').attr("disabled", false);
            //         $('[value=level'+select_number+']').attr("disabled", false);
            //     }
            //     else
            //     {
            //         $('[name=approval_manager_level'+select_number+']').attr("disabled", true);
            //         $('[name=initial_review_day_level'+select_number+']').attr("disabled", true);
            //         $('[name=final_review_day_level'+select_number+']').attr("disabled", true);
            //         $('[value=level'+select_number+']').attr("disabled", true);
            //     }
            //
            // });

        });

        $("#on_off").change(function(){
            if($(this). prop("checked") == true){
                $("#approval_feilds").show("slow");
            }
            else if($(this). prop("checked") == false){
                $("#approval_feilds").hide("slow");
            }
        });

        $(".select-managers").change(function(){
            //alert($(this).val());
            var name=$(this).attr('name');
            var select_number=name.match(/\d+/);

            if($(this).val() > 0){
                $('[name=initial_review_day_level'+select_number[0]+']').attr("disabled", false);
                $('[name=final_review_day_level'+select_number[0]+']').attr("disabled", false);
                $('[value=level'+select_number[0]+']').attr("disabled", false);
            }
            else if($(this).val() == -1){
                $('#selected_value').val($(this).attr('name'));
                $("#modal-add-user").show();
                $("#modal-add-user").addClass("in");
            }else{
                $('[name=initial_review_day_level'+select_number[0]+']').attr("disabled", true);
                $('[name=final_review_day_level'+select_number[0]+']').attr("disabled", true);
                $('[value=level'+select_number[0]+']').attr("disabled", true);
                return true;
            }
        });



        /*$("select[name='contract_manager']").change(function(){
         //alert($(this).val());
         if($(this).val() == -1){
         //alert("add new user");
         $("#modal-add-user").show();
         $("#modal-add-user").addClass("in");
         }else{
         return true;
         }
         });

         $("select[name='financial_manager']").change(function(){
         //alert($(this).val());
         if($(this).val() == -1){
         //alert("add new user");
         $("#modal-add-user").show();
         $("#modal-add-user").addClass("in");
         }else{
         return true;
         }
         });*/
        function createUser(){
            $(".overlay").show();
            var basePath ="";
            var group = $("#user_group").val();
            var email = $("#user_email").val();
            var first_name = $("#user_first_name").val();
            var last_name = $("#user_last_name").val();
            var title = $("#user_title").val();
            var phone = $("#user_phone").val();
            var current_url = "{{URL::route('hospitals.create_admin', $hospital->id)}}";
            var type = "ajax";
            $.post(current_url,{
                group: group,
                email: email,
                first_name: first_name,
                last_name: last_name,
                title: title,
                phone: phone,
                type: type
            },function (response) {
                $(".overlay").hide();
                if(response.success){
                    $(".validation-error").html('');
                    $('#enterLogMessageLog').show();
                    $('#enterLogMessageLog').html(response.success);
                    $('#enterLogMessageLog').removeClass("alert-danger");
                    $('#enterLogMessageLog').addClass("alert-success");
                    $('#enterLogMessageLog').focus();
                    /*$("select[name='contract_manager']").append("<option value='"+response.user_id+"'>"+response.name+"</option>");
                     $("select[name='financial_manager']").append("<option value='"+response.user_id+"'>"+response.name+"</option>");
                     if($("select[name='contract_manager']").val() == -1){
                     $("select[name='contract_manager']").val(response.user_id);
                     }else if($("select[name='financial_manager']").val() == -1){
                     $("select[name='financial_manager']").val(response.user_id);
                     }
                     $("select[name='contract_manager'] option[value='-1']").each(function() {
                     $(this).remove();
                     });
                     $("select[name='financial_manager'] option[value='-1']").each(function() {
                     $(this).remove();
                     });
                     $("select[name='contract_manager']").append("<option value='-1'>Add New User</option>");
                     $("select[name='financial_manager']").append("<option value='-1'>Add New User</option>");*/
                    $(".select-managers").append("<option value='"+response.user_id+"'>"+response.name+"</option>");
                    var selected_value=$('#selected_value').val();
                    $("select[name="+selected_value+"").val(response.user_id);

                    $(".select-managers option[value='-1']").each(function() {
                        $(this).remove();
                    });
                    $(".select-managers").each(function() {
                        $(this).append("<option value='-1'>Add New User</option>");
                    });
                    setTimeout(function () {
                        $('#modal-add-user').hide();
                        $('#enterLogMessageLog').hide();
                        $("#user_email").val('');
                        $("#user_first_name").val('');
                        $("#user_last_name").val('');
                        $("#user_phone").val('');
                        $("#enterLogMessageLog").html('');
                    }, 3000);
                }else if(response.error){
                    $(".validation-error").html('');
                    $('#enterLogMessageLog').show();
                    $('#enterLogMessageLog').html(response.error[0]);
                    $('#enterLogMessageLog').removeClass("alert-success");
                    $('#enterLogMessageLog').addClass("alert-danger");
                    $('#enterLogMessageLog').focus();
                    setTimeout(function () {
                        $('#enterLogMessageLog').hide();
                        $("#enterLogMessageLog").html('');
                    }, 3000);
                }else{
                    if (response.email) {
                        console.log("present");
                        $(".validation-error-email").html(response.email[0]);
                    } else {
                        console.log("absent");
                    }
                    if (response.first_name) {
                        console.log("present");
                        $(".validation-error-first_name").html(response.first_name[0]);
                    } else {
                        console.log("absent");
                    }
                    if (response.last_name) {
                        console.log("present");
                        $(".validation-error-last_name").html(response.last_name[0]);
                    } else {
                        console.log("absent");
                    }
                    if (response.phone) {
                        console.log("present");
                        $(".validation-error-phone").html(response.phone[0]);
                    } else {
                        console.log("absent");
                    }
                }
            });
        }

        function closeModel(){
            $('#modal-add-user').hide();
            /*if($("select[name='contract_manager']").val() == -1){
             $("select[name='contract_manager']").val('');
             }else if($("select[name='financial_manager']").val() == -1){
             $("select[name='financial_manager']").val('');
             }*/
            var selected_value=$('#selected_value').val();
            $("select[name="+selected_value+"").val('');
            $('#enterLogMessageLog').hide();
            $("#user_email").val('');
            $("#user_first_name").val('');
            $("#user_last_name").val('');
            $("#user_phone").val('');
            $("#enterLogMessageLog").html('');
        }

        $.ajaxSetup({
             headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
          });

    </script>
@endsection
