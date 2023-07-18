@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 2 ])
@section('actions')
    <a class="btn btn-default" href="{{ URL::route('hospitals.agreements', $hospital->id) }}">
        <i class="fa fa-list fa-fw"></i> Index
    </a>
@endsection
@section('content')
    <div class="panel panel-default">
        {{ Form::open([ 'class' => 'form form-horizontal form-create-agreement' ]) }}
        <div class="panel-heading">Create Agreement</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Name</label>

                <div class="col-xs-4">
                    {{ Form::text('name', Request::old('name'), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Start Date</label>

                <div class="col-xs-4">
                    <div id="start-date" class="input-group">
                        {{ Form::text('start_date', Request::old('start_date'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('start_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">End Date</label>

                <div class="col-xs-4">
                    <div id="end-date" class="input-group">
                        {{ Form::text('end_date', Request::old('end_date'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('end_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Valid Up To Date</label>

                <div class="col-xs-4">
                    <div id="valid-upto" class="input-group">
                        {{ Form::text('valid_upto', Request::old('valid_upto'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('valid_upto', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Internal Notes</label>
                <div class="col-xs-4">
                    <div id="internal_notes" class="input-group">
                        {{ Form::textarea('internal_notes', Request::old('internal_notes'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Payment Frequency Type</label>
                <div class="col-xs-4">
                    <div id="payment-frequency-option">
                    {{ Form::select('payment_frequency_option', $payment_frequency_option, Request::old('payment_frequency_option',0), [ 'class' => 'form-control payment-frequency-option' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Frequency Start Date</label>

                <div class="col-xs-4">
                    <div id="payment-start-date" class="input-group">
                        {{ Form::text('frequency_start_date', Request::old('frequency_start_date'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('frequency_start_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <!-- <div class="form-group prior-payment-start-date" style="display: none;">
                <label class="col-xs-3 control-label">Payment Start Date</label>
                <div class="col-xs-4">
                    <div id="payment-start-date" class="input-group">
                        {{ Form::text('payment-start-date', Request::old('payment_start_date'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('payment_start_date', '<p class="validation-error">:message</p>') !!}</div>
            </div> -->
            <div class="form-group">
                <label class="col-xs-3 control-label">Approval Process</label>

                <div class="col-xs-4">
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            <!--<input id="on_off" name="on_off" type="checkbox" checked>-->
                            {{ Form::checkbox('on_off', 1, Request::old('on_off',1), ['id' => 'on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>
            <div id="approval_feilds" class="approvalProcess" style="display: none;">
                <div class="approvalContainer">
                    <div class="tableHeading">
                        <label class="col-xs-3 control-label"></label>
                        <!-- <div class="col-md-3 col-sm-3 col-xs-3">
                            <strong>Approval Manager Type</strong>
                        </div> -->
                        <div class="col-md-3 col-sm-3 col-xs-3">
                            <strong>Approval Manager</strong>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-2">
                            <strong>Initial Review Day</strong>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-2">
                            <strong>Final Review Day</strong>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-2">
                            <strong>Opt-in email</strong>
                        </div>

                    </div>

                    <?php if($errors->first('review_day_range_limit')) $review_day_range_limit = $errors->first('review_day_range_limit') ?>

                    @for($i = 1; $i <= 6; $i++)
                    <div class="form-group">
                            <label class="col-xs-3 control-label">Approval Level {{$i}}</label>

{{--                            <div class="col-md-3 col-sm-3 col-xs-3">--}}
{{--                                {{ Form::select('approverTypeforLevel'.$i, $approval_manager_type, Request::old('approverTypeforLevel'.$i,0), [ 'class' => 'form-control approval_type' ]) }}--}}
{{--                            </div>--}}

                            <div class="col-md-3 col-sm-3 col-xs-3 paddingLeft">
                                {{ Form::select('approval_manager_level'.$i, $users, Request::old('approval_manager_level'.$i, 0), [ 'class' => 'form-control select-managers approver' ]) }}
                            </div>

                            <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                                {{ Form::selectRange('initial_review_day_level'.$i, 1, $review_day_range_limit, Request::old('initial_review_day_level'.$i,10), [ 'id' => 'initial_review_day_level'.$i, 'class' => 'form-control','disabled'=>'disabled' ]) }}
                            </div>

                            <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                                {{ Form::selectRange('final_review_day_level'.$i, 1, $review_day_range_limit, Request::old('final_review_day_level'.$i,20), [ 'id' => 'final_review_day_level'.$i, 'class' => 'form-control','disabled'=>'disabled' ]) }}
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-2">
                                <input type="checkbox" name="emailCheck[]" value="level{{$i}}" checked disabled='disabled'>
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
            </div>
            @if ((is_super_user() || is_super_hospital_user()) && $hospital->invoice_dashboard_on_off == 1)
            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Reminder Day</label>

                <div class="col-xs-4">
                    {{ Form::selectRange('send_invoice_reminder_day', 1, 28, Request::old('send_invoice_reminder_day',28), ['id' => 'send_invoice_reminder_day', 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('send_invoice_reminder_day', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label paddingRight">Invoice Reminder Recipient #1</label>

                <div class="col-xs-4">
                    {{ Form::select('invoice_reminder_recipient1', $users_for_invoice_recipients, Request::old('invoice_reminder_recipient1'), [ 'class' => 'form-control select-managers' ]) }}
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3">
                    <label style="margin-right: 20px;">Opt-in email</label>
                    <input type="checkbox" name="emailCheck_recipient_1" value="emailCheck_recipient1" checked>
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3"></div>
                <div class="col-md-9"><p class="validationFieldErr">{!! $errors->first('invoice_reminder_recipient1', '<p class="validation-error">:message</p>') !!}</p>

                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label paddingRight">Invoice Reminder Recipient #2</label>

                <div class="col-xs-4">
                    {{ Form::select('invoice_reminder_recipient2', $users_for_invoice_recipients, Request::old('invoice_reminder_recipient2'), [ 'class' => 'form-control select-managers' ]) }}
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3">
                    <label style="margin-right: 20px;">Opt-in email</label>
                    <input type="checkbox" name="emailCheck_recipient_2" value="emailCheck_recipient2" checked>
                </div>

                <div class="col-md-3 col-sm-3 col-xs-3"></div>
                <div class="col-md-9"><p class="validationFieldErr">{!! $errors->first('invoice_reminder_recipient2', '<p class="validation-error">:message</p>') !!}</p>

                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Recipient #1</label>

                <div class="col-xs-4">
                    {{ Form::text('invoice_receipient1', Request::old('invoice_receipient1'), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('invoice_receipient1', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Recipient #2</label>

                <div class="col-xs-4">
                    {{ Form::text('invoice_receipient2', Request::old('invoice_receipient2'), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('invoice_receipient2', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Invoice Recipient #3</label>

                <div class="col-xs-4">
                    {{ Form::text('invoice_receipient3', Request::old('invoice_receipient3'), [ 'class' => 'form-control' ]) }}
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

            $('#payment-frequency-option').change(function(){
                var start_day_option = 1;
                var end_day_option = 28;
                var initial_review_day = 10;
                var final_review_day = 20;
                if($('.payment-frequency-option').val() == 1){
                    $(".prior-payment-start-date").hide();
                } else {
                    $(".prior-payment-start-date").show();

                    if($('.payment-frequency-option').val() == 2){
                        var start_day_option = 1;
                        var end_day_option = 6;
                        var initial_review_day = 2;
                        var final_review_day = 6;
                    }
                    if($('.payment-frequency-option').val() == 3){
                        var start_day_option = 1;
                        var end_day_option = 14;
                        var initial_review_day = 2;
                        var final_review_day = 12;
                    }
                    if($('.payment-frequency-option').val() == 4){
                        var start_day_option = 1;
                        var end_day_option = 85;
                        var initial_review_day = 10;
                        var final_review_day = 20;
                    }
                }

                for( var i=1; i<=6; i++){
                    var $initial_review_day_level = $("#initial_review_day_level" + i);
                    var $final_review_day_level = $("#final_review_day_level" + i);
                    var $send_invoice_reminder_day = $("#send_invoice_reminder_day");
                    
                    $initial_review_day_level.find('option')
                    .remove()
                    .end();

                    $final_review_day_level.find('option')
                    .remove()
                    .end();

                    $send_invoice_reminder_day.find('option')
                    .remove()
                    .end();

                    for( var j=start_day_option; j<=end_day_option; j++){
                        $initial_review_day_level.append($("<option />").val(j).text(j));
                        $final_review_day_level.append($("<option />").val(j).text(j));
                        $send_invoice_reminder_day.append($("<option />").val(j).text(j));
                    }
                    $initial_review_day_level.val(initial_review_day);
                    $final_review_day_level.val(final_review_day);
                    $send_invoice_reminder_day.val(final_review_day);
                }
            })


            if($('select[name="invoice_reminder_recipient1"]').val() == 0) {
                $("input[name='emailCheck_recipient_1']:checkbox").prop('checked',false);
                $("input[name='emailCheck_recipient_1']:checkbox").attr("disabled", true);
            }else {
                $("input[name='emailCheck_recipient_1']:checkbox").removeAttr("disabled");
            }
            if($('select[name="invoice_reminder_recipient2"]').val() == 0) {
                $("input[name='emailCheck_recipient_2']:checkbox").prop('checked',false);
                $("input[name='emailCheck_recipient_2']:checkbox").attr("disabled", true);
            }else {
                $("input[name='emailCheck_recipient_2']:checkbox").removeAttr("disabled");
            }


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

            for(var i = 1; i <= 6; i++){
                var temp_level = i;
                // console.log(temp_level);
                if($('[name=approval_manager_level'+temp_level+']').val()==0){
                    $('[name=initial_review_day_level'+temp_level+']').attr("disabled", true);
                    $('[name=final_review_day_level'+temp_level+']').attr("disabled", true);
                    $('[value=level'+temp_level+']').attr("disabled", true);
                } else {
                    $('[name=initial_review_day_level'+temp_level+']').attr("disabled", false);
                    $('[name=final_review_day_level'+temp_level+']').attr("disabled", false);
                    $('[value=level'+temp_level+']').attr("disabled", false);
                }
            }

            // $('.approval_type').each(function(){
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
            if($(this).val() == -1){
                $('#selected_value').val($(this).attr('name'));
                $("#modal-add-user").show();
                $("#modal-add-user").addClass("in");
            }else{
                if($(this).hasClass("approver"))
                {
                    if($(this).val() == 0) {

                        // $('[name=approval_manager_level' + select_number[0] + ']').attr("disabled", true);
                        $('[name=initial_review_day_level' + select_number[0] + ']').attr("disabled", true);
                        $('[name=final_review_day_level' + select_number[0] + ']').attr("disabled", true);
                        $('[value=level' + select_number[0] + ']').attr("disabled", true);
                    } else {
                        // $('[name=approval_manager_level'+select_number[0]+']').attr("disabled", false);
                        $('[name=initial_review_day_level'+select_number[0]+']').attr("disabled", false);
                        $('[name=final_review_day_level'+select_number[0]+']').attr("disabled", false);
                        $('[value=level'+select_number[0]+']').attr("disabled", false);
                    }
                }
                if($(this).attr('name') == 'invoice_reminder_recipient1' || $(this).attr('name') == 'invoice_reminder_recipient2'){
                    if($(this).val() == 0){
                        if($(this).attr('name') == 'invoice_reminder_recipient1') {
                            $("input[name='emailCheck_recipient_1']:checkbox").prop('checked',false);
                            $("input[name='emailCheck_recipient_1']:checkbox").attr("disabled", true);
                        }
                        if($(this).attr('name') == 'invoice_reminder_recipient2') {
                            $("input[name='emailCheck_recipient_2']:checkbox").prop('checked',false);
                            $("input[name='emailCheck_recipient_2']:checkbox").attr("disabled", true);
                        }
                    }else{
                        if($(this).attr('name') == 'invoice_reminder_recipient1') {
                            $("input[name='emailCheck_recipient_1']:checkbox").removeAttr("disabled");
                        }
                        if($(this).attr('name') == 'invoice_reminder_recipient2') {
                            $("input[name='emailCheck_recipient_2']:checkbox").removeAttr("disabled");
                        }
                    }
                }
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
            var title = $("#user_title").val();
            var last_name = $("#user_last_name").val();
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
                    //$("select[name='contract_manager']").append("<option value='"+response.user_id+"'>"+response.name+"</option>");
                    //$("select[name='financial_manager']").append("<option value='"+response.user_id+"'>"+response.name+"</option>");



                    /*if($("select[name='contract_manager']").val() == -1){
                     $("select[name='contract_manager']").val(response.user_id);
                     }else if($("select[name='financial_manager']").val() == -1){
                     $("select[name='financial_manager']").val(response.user_id);
                     }
                     $("select[name='contract_manager'] option[value='-1']").each(function() {
                     $(this).remove();
                     });
                     $("select[name='financial_manager'] option[value='-1']").each(function() {
                     $(this).remove();
                     });*/

                    $(".select-managers").append("<option value='"+response.user_id+"'>"+response.name+"</option>");
                    //$("select[value='-1']").val(response.user_id);
                    var selected_value=$('#selected_value').val();
                    $("select[name="+selected_value+"").val(response.user_id);
                    $(".select-managers option[value='-1']").each(function() {
                        $(this).remove();
                    });
                    $(".select-managers").each(function() {
                        $(this).append("<option value='-1'>Add New User</option>");
                    });

                    for(var i = 1; i <= 6; i++){
                        var temp_level = i;
                        // console.log(temp_level);
                        if($('[name=approval_manager_level'+temp_level+']').val()==0){
                            $('[name=initial_review_day_level'+temp_level+']').attr("disabled", true);
                            $('[name=final_review_day_level'+temp_level+']').attr("disabled", true);
                            $('[value=level'+temp_level+']').attr("disabled", true);
                        } else {
                            $('[name=initial_review_day_level'+temp_level+']').attr("disabled", false);
                            $('[name=final_review_day_level'+temp_level+']').attr("disabled", false);
                            $('[value=level'+temp_level+']').attr("disabled", false);
                        }
                    }
                    
                    /*$("select[name='contract_manager']").append("<option value='-1'>Add New User</option>");
                     $("select[name='financial_manager']").append("<option value='-1'>Add New User</option>");*/
                    setTimeout(function () {
                        $('#modal-add-user').hide();
                        $('#enterLogMessageLog').hide();
                        $("#user_email").val('');
                        $("#user_first_name").val('');
                        $("#user_last_name").val('');
                        $("#user_phone").val('');
                        $("#enterLogMessageLog").html('');
                        $("#user_title").val('');
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
                    if (response.title) {
                        console.log("present");
                        $(".validation-error-title").html(response.title[0]);
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
