@extends('layouts/_hospital', ['tab' => 11])
@section('actions')
    <a class="btn btn-default" href="{{ route('hospitals.approvers', $hospital->id) }}">
        <i class="fa fa-arrow-circle-left fa-fw"></i> Back
    </a>
@endsection
<style>
    input.form-control.check {
        height: 20px;
        width: 20px;
    }
</style>
@section('content')
    <div class="panel panel-default">
        {{ Form::open([ 'class' => 'form form-horizontal form-create-agreement' ]) }}
        {{ Form::hidden('id', $agreement->id) }}
        <div class="panel-heading">Edit Contract Approver </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Contract Name</label>

                <div class="col-xs-4">
                    {{ Form::text('name', Request::old('name', $contractName), [ 'class' => 'form-control', 'readonly' ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group">
                <label class="col-xs-3 control-label">Physician Name</label>

                <div class="col-xs-4" style="padding-top: 5px;">
                    <span>{{$physicianfullname}}</span>
                </div>
                <div class="col-xs-5">{!! $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <div class="form-group">
                <label class="col-xs-3 control-label">Approval Process</label>


            </div>
            <!--CM FM For Contract-->
            <?php // $default = 0;
            $default=$contract->default_to_agreement;
            $managercount=count($ApprovalManagerInfo);
            ?>


            <div id="approval_feilds" style="">
              <div class="form-group">
                  <input type="hidden" name="default" value="0"/>
              </div>
                <div class="approvalContainer">
                    <div class="tableHeading">
                        <label class="col-xs-3 control-label"></label>
{{--                        <div class="col-md-3 col-sm-3 col-xs-3">--}}
{{--                            <strong>Approval Manager Type</strong>--}}
{{--                        </div>--}}
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
                            <label class="col-xs-3 control-label">Approval Level {{$i}}</label>

{{--                            <div class="col-md-3 col-sm-3 col-xs-3">--}}
{{--                                {{ Form::select('approverTypeforLevel'.$i, $approval_manager_type, Request::old('approverTypeforLevel'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->type_id : 0), [ 'class' => 'form-control approval_type' ]) }}--}}
{{--                            </div>--}}

                            <div class="col-md-3 col-sm-3 col-xs-3 paddingLeft">
                                {{ Form::select('approval_manager_level'.$i, $users, Request::old('approval_manager_level'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->user_id : '0'), [ 'class' => 'form-control select-managers' ]) }}
                            </div>

                            <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                                {{ Form::selectRange('initial_review_day_level'.$i, 1, 28, Request::old('initial_review_day_level'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->initial_review_day : 10), [ 'class' => 'form-control' ]) }}
                            </div>

                            <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                                {{ Form::selectRange('final_review_day_level'.$i, 1, 28, Request::old('final_review_day_level'.$i,count($ApprovalManagerInfo) >= $i ? $ApprovalManagerInfo[$i-1]->final_review_day : 20), [ 'class' => 'form-control' ]) }}
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
            </div>
            <input type="hidden" name="agreement_id" id="agreement_id" value="{{$contract->agreement_id}}">
            <input type="hidden" name="contract_id" id="contract_id" value="{{$contract->id}}">

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

                                <div class="col-xs-5" >
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

    <!-- Modal logs in approval queue-->
    <div class="modal fade" id="logsInApprovalQueue" tabindex="-1" role="dialog" aria-labelledby="logsInApprovalQueueTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <!-- <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button> -->
                </div>
                <div class="modal-body">
                    There are logs currently in the approval process. Changing approvers is not permitted until all logs have had their final approval. Please contact support if you require assistance.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="(function (){$('.overlay').show(); location.reload();})()">Close</button>
                    <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                </div>
            </div>
        </div>
    </div>


@endsection

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function() {
             //As we are removing this default to agrrement checkbox, we will not consider for disabling the approver level on the default to agrremnet base
             // for(var i = 1; i <= 6; i++){
             //     $('[name=approverTypeforLevel'+i+']').attr("disabled", false);
             //     if($('[name=approverTypeforLevel'+i+']').val()>0)
             //     {
             //         // $('[name=approval_manager_level'+i+']').attr("disabled", false);
             //         $('[name=initial_review_day_level'+i+']').attr("disabled", false);
             //         $('[name=final_review_day_level'+i+']').attr("disabled", false);
             //         $('[value=level'+i+']').attr("disabled", false);
             //     }
             //     else
             //     {
             //         // $('[name=approval_manager_level'+i+']').attr("disabled", true);
             //         $('[name=initial_review_day_level'+i+']').attr("disabled", true);
             //         $('[name=final_review_day_level'+i+']').attr("disabled", true);
             //         $('[value=level'+i+']').attr("disabled", true);
             //     }
             // }

            for(var i = 1; i <= 6; i++){
                // $('[name=approverTypeforLevel'+i+']').attr("disabled", false);
                if($('[name=approval_manager_level'+i+']').val()>0)
                {
                    // $('[name=approval_manager_level'+i+']').attr("disabled", false);
                    $('[name=initial_review_day_level'+i+']').attr("disabled", false);
                    $('[name=final_review_day_level'+i+']').attr("disabled", false);
                    $('[value=level'+i+']').attr("disabled", false);
                }
                else
                {
                    // $('[name=approval_manager_level'+i+']').attr("disabled", true);
                    $('[name=initial_review_day_level'+i+']').attr("disabled", true);
                    $('[name=final_review_day_level'+i+']').attr("disabled", true);
                    $('[value=level'+i+']').attr("disabled", true);
                }
            }


            // $('.approval_type').change(function(){
            //     var name=$(this).attr('name');
            //     var select_number=name.match(/\d+/);
            //     if($(this).val()>0)
            //     {
            //         // $('[name=approval_manager_level'+select_number+']').attr("disabled", false);
            //         $('[name=initial_review_day_level'+select_number+']').attr("disabled", false);
            //         $('[name=final_review_day_level'+select_number+']').attr("disabled", false);
            //         $('[value=level'+select_number+']').attr("disabled", false);
            //     }
            //     else
            //     {
            //         // $('[name=approval_manager_level'+select_number+']').attr("disabled", true);
            //         $('[name=initial_review_day_level'+select_number+']').attr("disabled", true);
            //         $('[name=final_review_day_level'+select_number+']').attr("disabled", true);
            //         $('[value=level'+select_number+']').attr("disabled", true);
            //     }
            //
            // });

        });

        $(".select-managers").change(function(){

            var name=$(this).attr('name');
            var select_number=name.match(/\d+/);

            if($(this).val() == 0){
                $('[name=initial_review_day_level'+select_number+']').attr("disabled", true);
                $('[name=final_review_day_level'+select_number+']').attr("disabled", true);
                $('[value=level'+select_number+']').attr("disabled", true);
            }
            else if($(this).val() == -1){
                $('#selected_value').val($(this).attr('name'));
                $("#modal-add-user").show();
                $("#modal-add-user").addClass("in");
            }
            else{
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

                $('[name=initial_review_day_level'+select_number+']').attr("disabled", false);
                $('[name=final_review_day_level'+select_number+']').attr("disabled", false);
                $('[value=level'+select_number+']').attr("disabled", false);
                return true;
            }
        });

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
            var selected_value=$('#selected_value').val();
            $("select[name="+selected_value+"").val('');
            $('#enterLogMessageLog').hide();
            $("#user_email").val('');
            $("#user_first_name").val('');
            $("#user_last_name").val('');
            $("#user_phone").val('');
            $("#enterLogMessageLog").html('');
        }

        $('[name=approval_manager_level1],[name=approval_manager_level2], [name=approval_manager_level3], [name=approval_manager_level4], [name=approval_manager_level5], [name=approval_manager_level6]').change(function(e){
            $(".overlay").show();
            $.ajax({
                url:'/getPhysicianLogsInApprovalQueue/' + $('#agreement_id').val() +'/'+ $('#contract_id').val(),
                type:'get',
                success:function(response){
                    if(response > 0){
                        $('#logsInApprovalQueue').modal({backdrop: 'static', keyboard: false});
                        // setTimeout(function(){
                        //     location.reload();
                        // }, 2000);

                    }
                    $(".overlay").hide();
                }
            });
        });

        $.ajaxSetup({
             headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
           }
          });

    </script>
@endsection
