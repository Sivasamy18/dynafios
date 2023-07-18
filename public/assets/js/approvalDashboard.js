if (!String.prototype.includes) {
    String.prototype.includes = function(search, start) {
      'use strict';
      if (typeof start !== 'number') {
        start = 0;
      }

      if (start + search.length > this.length) {
        return false;
      } else {
        return this.indexOf(search, start) !== -1;
      }
    };
  }

// https://tc39.github.io/ecma262/#sec-array.prototype.includes
if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, 'includes', {
      value: function(valueToFind, fromIndex) {

        if (this == null) {
          throw new TypeError('"this" is null or not defined');
        }

        // 1. Let O be ? ToObject(this value).
        var o = Object(this);

        // 2. Let len be ? ToLength(? Get(O, "length")).
        var len = o.length >>> 0;

        // 3. If len is 0, return false.
        if (len === 0) {
          return false;
        }

        // 4. Let n be ? ToInteger(fromIndex).
        //    (If fromIndex is undefined, this step produces the value 0.)
        var n = fromIndex | 0;

        // 5. If n ≥ 0, then
        //  a. Let k be n.
        // 6. Else n < 0,
        //  a. Let k be len + n.
        //  b. If k < 0, let k be 0.
        var k = Math.max(n >= 0 ? n : len - Math.abs(n), 0);

        function sameValueZero(x, y) {
          return x === y || (typeof x === 'number' && typeof y === 'number' && isNaN(x) && isNaN(y));
        }

        // 7. Repeat, while k < len
        while (k < len) {
          // a. Let elementK be the result of ? Get(O, ! ToString(k)).
          // b. If SameValueZero(valueToFind, elementK) is true, return true.
          if (sameValueZero(o[k], valueToFind)) {
            return true;
          }
          // c. Increase k by 1.
          k++;
        }

        // 8. Return false
        return false;
      }
    });
  }

$('.pane-hScroll').scroll(function() {
        $('.pane-vScroll').width($('.pane-hScroll').width() + $('.pane-hScroll').scrollLeft());
    });

// Example 2
$('.pane--table2').scroll(function() {
    $('.pane--table2 table').width($('.pane--table2').width() + $('.pane--table2').scrollLeft());
});
$('#manager_filter').on('change', function () {
    var redirectURL = "?manager_filter=" + this.value;
    window.location.href = redirectURL;
});
$('#hospital').on('change', function () {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+this.value;
     window.location.href = redirectURL;*/
    getLogsForApprovalByAjaxRequest($('#hospital').val(),0,0,0,0,0,'','','');
});

$('#agreement').on('change', function () {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+this.value;
     window.location.href = redirectURL;*/
    getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),0,0,0,0,'','','');
});
$('#practice').on('change', function () {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+this.value;
     window.location.href = redirectURL;*/
    getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),0,0,0,'','','');
});
$('#physician').on('change', function () {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+this.value;
     window.location.href = redirectURL;*/
    getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),0,0,'','','');
});
$('#payment_type').on('change', function () {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+this.value;
     window.location.href = redirectURL;*/
    getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),0,'','','');
});
$('#contract_type').on('change', function () {
    /*var redirectURL = "?manager_filter=" + $('#manager_filter').val()+"&hospital="+$('#hospital').val()+"&agreement="+$('#agreement').val()+"&practice="+$('#practice').val()+"&physician="+$('#physician').val()+"&contract_type="+this.value;
     window.location.href = redirectURL;*/
    getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),'','','');
});
$('#start_date').on('change', function () {
    var start = new Date($('#start_date').val());
    var end = new Date($('#end_date').val());
    if(start.getTime() < end.getTime()) {
        /*var redirectURL = "?manager_filter=" + $('#manager_filter').val() + "&hospital=" + $('#hospital').val() + "&agreement=" + $('#agreement').val() + "&practice=" + $('#practice').val() + "&physician=" + $('#physician').val() + "&contract_type=" + $('#contract_type').val()+"&start_date="+$('#start_date').val()+"&end_date="+$('#end_date').val();
         window.location.href = redirectURL;*/
        getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#start_date').val(),$('#end_date').val(),'');
    }
});
$('#end_date').on('change', function () {
    var start = new Date($('#start_date').val());
    var end = new Date($('#end_date').val());
    if(start.getTime() < end.getTime()) {
        /*var redirectURL = "?manager_filter=" + $('#manager_filter').val() + "&hospital=" + $('#hospital').val() + "&agreement=" + $('#agreement').val() + "&practice=" + $('#practice').val() + "&physician=" + $('#physician').val() + "&contract_type=" + $('#contract_type').val()+"&start_date="+$('#start_date').val()+"&end_date="+$('#end_date').val();
         window.location.href = redirectURL;*/
        getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#start_date').val(),$('#end_date').val(),'');
    }
});

$(".pagination li a").click(function(ev) {
    ev.preventDefault();
    var link = $(this). attr("href");
    var split_link = link.split("=");
    getLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#start_date').val(),$('#end_date').val(),split_link[1]);
});
$('#export').on('click', function (e) {
    e.preventDefault();
    $( "#export_submit" ).trigger( "click" );
    $('.overlay').show();
});
$(document).ready(function() {
    //$('.select_logs').attr("checked", "checked");

    /*$('#approveButton').click(function () {
     var addhtml = "<div style='background: #fff;position: absolute;width:50%;margin: 0 auto;padding: 2% 1%;position: absolute;margin: 8% 25%; max-height: 500px; overflow: auto;'>";

     var type = 1;
     var logs = [];
     var rejectedlogs = [];
     var rejectedlogs_with_reason = [];
     var contract_name = '';
     var log_count = 0;
     var display = 0;
     var contract_ids = $('#contract_ids').val().split(',');
     contract_ids.forEach(function (item) {
     if ($("input[name='" + item + "_logs']:checked").length > 0) {
     if (display == 0) {
     addhtml += "<p style='text-align: center;'><b>Are you sure you want to approve these logs?</b></p>";
     display++;
     }
     log_count++;
     var physician_name = $('#' + item + '_physician_name b').html();
     if ($("#" + item + '_contract_name').length != 0) {
     var contract_name = $('#' + item + '_contract_name b').html();
     var practice_name = $('#' + item + '_practice_name b').html();
     addhtml += "<p style='border-top: 2px solid #cfd7e5; padding-top: 15px; font-size: 18px; color: rgb(0, 77, 228);'>Contract Name: <b>" + contract_name + "</b></p>" +
     "<p>Practice Name: <b>" + practice_name + "</b></p>" +
     "<p'>Physician Name: <b>" + physician_name + "</b></p>";
     } else {
     if ($("#" + item + '_contract_name').length == 0 && $('#' + item + '_practice_name').length != 0) {
     var practice_name = $('#' + item + '_practice_name b').html();
     addhtml += "<p style='border-top: 2px solid #cfd7e5; padding-top: 15px;'>Practice Name: <b>" + practice_name + "</b></p>" +
     "<p'>Physician Name: <b>" + physician_name + "</b></p>";
     } else {
     addhtml += "<p style='border-top: 2px solid #cfd7e5; padding-top: 15px;'>Physician Name: <b>" + physician_name + "</b></p>";
     }
     }
     addhtml += "<table class='table table-striped table-hover'  style='border: 1px solid #cfd7e5;'>" +
     "<thead style='background-color: #F5F9FF;'><tr><th>Log Date</th><th>Action</th><th>Duration/Shift</th></tr></thead><tbody>";

     $.each($("input[name='" + item + "_logs']:checked"), function () {
     logs.push($(this).val());
     var log_id = $(this).val();
     var date = $('#' + log_id + '_date').html();
     var action = $('#' + log_id + '_action').html();
     var duration = $('#' + log_id + '_duration').html();
     addhtml += "<tr><td>" + date + "</td><td>" + action + "</td><td>" + duration + "</td></tr>";
     });
     $.each($("input[name='" + item + "_logs']:not(:checked)"), function () {
     rejectedlogs.push($(this).val());
     var rejectedlog_val = $(this).val();
     var rejectedlog_reason = $(this).parent().find('.reasondiv').text();
     rejectedlogs_with_reason.push(rejectedlog_val + '_' + rejectedlog_reason);
     });
     addhtml += "</tbody></table>";
     } else {
     $.each($("input[name='" + item + "_logs']:not(:checked)"), function () {
     rejectedlogs.push($(this).val());
     var rejectedlog_val = $(this).val();
     var rejectedlog_reason = $(this).parent().find('.reasondiv').text();
     rejectedlogs_with_reason.push(rejectedlog_val + '_' + rejectedlog_reason);
     });
     }
     });
     addhtml += "<form method='POST' action='#' accept-charset='UTF-8' >";
     if (logs.length == 0) {
     logs.push(0);
     type = 0;
     }
     if (rejectedlogs.length == 0) {
     rejectedlogs.push(0);
     }
     if (rejectedlogs_with_reason.length == 0) {
     rejectedlogs_with_reason.push(0);
     }
     addhtml += "<input type='hidden' value='" + logs + "' name='log_ids' />" +
     "<input type='hidden' value='" + type + "' name='type' />" +
     "<input type='hidden' value='{{$filter}}' name='filter' />" +
     "<input type='hidden' value='" + rejectedlogs_with_reason + "' name='rejectedlogs_with_reason' />" +
     "<input type='hidden' value='" + rejectedlogs + "' name='rejected' />";
     if (log_count > 0) {
     addhtml += "<div class='modal-footer'><button class='btn btn-default cancelMe' type='button'>CANCEL</button><button class='btn btn-primary' type='submit'>APPROVE</button></form></div></div>";
     } else {
     addhtml += "<p style='text-align: center;'><b>Are you sure you want to reject all logs?</b></p>";
     addhtml += "<div class='modal-footer'><button class='btn btn-default cancelMe' type='button'>CANCEL</button><button class='btn btn-primary' type='submit'>OK</button></form></div></div>";
     }
     $('.overlay').empty();
     $('.overlay').append(addhtml);
     $(".overlay").css('background-image', 'none');
     $('.overlay').show();
     $('.cancelMe').on('click', function () {
     $('.overlay').hide();
     });
     });*/

    $(".select_logs").change(function(){
        var log_id= this.value;
        if(this.checked) {
            $("#reason_"+log_id).val('');
            $("#reject_log_"+log_id).prop("checked", false);
            $('.submitApproval').attr("disabled",false);
            add_remove_logs(log_id,1,true);
        }
        else
        {
            add_remove_logs(log_id,-1,false);
            //$('.submitApproval').attr("disabled", !($('.appDashboardTable input[type="checkbox"]').is(":checked")));
            if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
                $('.submitApproval').attr("disabled",false);
            }else{
                $('.submitApproval').attr("disabled",true);
            }
        }
    });

});
 // function to get all log ids for approval
 function getAllLogsForApproval(){
     $('.overlay').show();
     $.ajax({
         url:'/getAllLogIdsForApproval',
         type:'get',
         data:{
             'hospital':$('#hospital').val(),
             'agreement':$('#agreement').val(),
             'practice':$('#practice').val(),
             'physician':$('#physician').val(),
             'payment_type':$('#payment_type').val(), //for new payment type scrollDown
             'contract_type':$('#contract_type').val(),
             'start_date':$('#start_date').val(),
             'end_date':$('#end_date').val()
         },
         success:function(response){
             $("#approve_reject_stats").html();
             var hidden_inputs ='<input type="hidden" name="approve_logs" id="approve_logs" value="'+response+'"><input type="hidden" name="reject_logs" id="reject_logs" value=""><input type="hidden" name="no_action" id="no_action" value="">';
             $("#approve_reject_stats").html(hidden_inputs);
             if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
                 $('.submitApproval').attr("disabled",false);
             }else{
                 $('.submitApproval').attr("disabled",true);
             }
         },
         complete:function(){
             $('.overlay').hide();
         }
     });
 }
// function to call log Details In Index page  by ajax request - starts
function getLogsForApprovalByAjaxRequest(hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,startDate,endDate,page){
    $('.overlay').show();
    $('#form_replace').html();
    $.ajax({
        url:'',
        type:'get',
        data:{
            'hospital':hospital_id,
            'agreement':agreement_id,
            'practice':practice_id,
            'physician':physician_id,
            'payment_type':payment_type_id, //for new scrolldown
            'contract_type':contract_type_id,
            'start_date':startDate,
            'end_date':endDate,
            'page': page
        },
        success:function(response){
            // console.log("Response From Approval Index Controller With Hospital,Agreement ID,Practice ID,Physician ID,Contract Type:",response);
            var approve_reject_stats = $("#approve_reject_stats").html();
            /*var parsed = $.parseHTML(response);
             result = $(parsed).find("tbody tr");
             console.log("index result:",result);
             if(result.length == 0){
             $('#indexDataToBeDisplayed table tbody').html('<tr class="odd_contract_class"><td class="text-center">No Data Available</td></tr>');
             }else{
             $('#indexDataToBeDisplayed').html(response);
             }*/
            $('#form_replace').html(response);
            if(page != ''){
                $("#approve_reject_stats").html(approve_reject_stats);
                if($("#reject_logs").val() != '') {
                    var rejecteded_logs = $("#reject_logs").val().split(",");
                    rejecteded_logs.forEach(function (item) {
                        if ($('#reject_log_'+item).length > 0) {
                            $('#reject_log_'+item).prop('checked', true);
                            $('#select_log_'+item).prop('checked', false);
                        }
                    });
                }else{
                    var rejecteded_logs = [];
                }

                if($("#no_action").val() != '') {
                    var no_action = $("#no_action").val().split(",");
                    no_action.forEach(function (item) {
                        if ($('#reject_log_'+item).length > 0) {
                            $('#reject_log_'+item).prop('checked', false);
                            $('#select_log_'+item).prop('checked', false);
                        }
                    });
                }else{
                    var no_action = [];
                }
            }else{
                getAllLogsForApproval();
            }
            if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
                $('.submitApproval').attr("disabled",false);
            }else{
                $('.submitApproval').attr("disabled",true);
            }
        },
        complete:function(){
            $('.overlay').hide();
        }
    });
}

$(function () {
    $('.submitApproval').attr("disabled", !($('.appDashboardTable input[type="checkbox"]').is(":checked")));
    $("#export_submit").hide();
});

//Below functions added by akash
var dataArrLevelOne = [];
var dataArrLevelTwo = [];


var contract_type_id_summation = '';
function getDataForContractType(contract_type_id){
    if(jQuery.inArray(contract_type_id, dataArrLevelOne) == -1){
        levelOneId = '#level-one-approve-' + contract_type_id;
        if($(levelOneId).prop('checked')){
            is_selected = true;
        } else {
            is_selected = false;
        }
        contract_type_id_summation = contract_type_id;
        getSummationLevelTwoDataByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),contract_type_id_summation,$('#start_date').val(),$('#end_date').val(),'',is_selected);
        $("level-one:a").addClass("collapsed");
    }
}

function getDataForPhysicianType(physician_id, payment_type, contract_type_id, contract_id, contract_name_id){
    var checkId = contract_type_id + '-' + contract_id + '-' + payment_type;
    levelTwoId = 'level-two-approve-' + contract_type_id + '-' + contract_id + '-' + payment_type;
    if($('[id^='+ levelTwoId + ']').prop('checked')){
        is_selected = true;
    } else {
        is_selected = false;
    }
    if(jQuery.inArray(checkId, dataArrLevelTwo) == -1){
        getSummationLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),physician_id,payment_type,contract_type_id,$('#start_date').val(),$('#end_date').val(),'',contract_id,contract_name_id,is_selected);
    }
    
}
//End akash

// function to call summation log Details In Index page  by ajax request - starts (By Akash)
function getSummationLevelOneDataByAjaxRequest(hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,startDate,endDate,page){
    $('.overlay').show();
    $('#form_replace').html();
    $.ajax({
        url:'getSummationDataLevelOne',
        type:'get',
        data:{
            'hospital':hospital_id,
            'agreement':agreement_id,
            'practice':practice_id,
            'physician':physician_id,
            'payment_type':payment_type_id, //for new scrolldown
            'contract_type':contract_type_id,
            'start_date':startDate,
            'end_date':endDate,
            'page': page
        },
        success:function(response){
            // console.log("Response From Approval Index Controller With Hospital,Agreement ID,Practice ID,Physician ID,Contract Type:",response);
            // var levelOneId = "#menu";
            var levelOneId = "#agreementDataByAjax";
            $(levelOneId).html(response);
        },
        complete:function(){
            $('.overlay').hide();
            dataArrLevelOne = [];
            dataArrLevelTwo = [];
        }
    });
}

// function to call summation log Details for level two In Index page  by ajax request - starts (By Akash)
function getSummationLevelTwoDataByAjaxRequest(hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,startDate,endDate,page,is_selected){
    $('.overlay').show();
    $('#form_replace').html();
    debugger;
    $.ajax({
        url:'getSummationDataLevelTwo',
        type:'get',
        data:{
            'hospital':hospital_id,
            'agreement':agreement_id,
            'practice':practice_id,
            'physician':physician_id,
            'payment_type':payment_type_id, //for new scrolldown
            'contract_type':contract_type_id,
            'start_date':startDate,
            'end_date':endDate,
            'page': page,
            'checked': is_selected
        },
        success:function(response){
            // console.log("Response From Approval Index Controller With Hospital,Agreement ID,Practice ID,Physician ID,Contract Type:",response);
            // var levelTwoId = "#hos-list-" + contract_type_id;
            var levelTwoId = "#collapse-" + contract_type_id;
            $(levelTwoId).html(response);
            dataArrLevelOne.push(contract_type_id);
        },
        complete:function(){
            $('.overlay').hide();
        }
    });
}

// function to call summation log Details for level three In Index page  by ajax request - starts (By Akash)
function getSummationLogsForApprovalByAjaxRequest(hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,startDate,endDate,page, contract_id, contract_name_id, is_selected_level_two, is_unapproved = false, unApprove){
    debugger;
    physician_id = $("#physician").val(); //Override the value by getting from the filter option.
    $('.overlay').css('background-image', '../img/overlay.gif');
    $('.overlay').show();
    $('#form_replace').html();

    $.ajax({
        url:'getSummationDataLevelThree',
        type:'get',
        data:{
            'hospital':hospital_id,
            'agreement':agreement_id,
            'practice':practice_id,
            'physician':physician_id,
            'payment_type':payment_type_id, //for new scrolldown
            'contract_type':contract_type_id,
            'start_date':startDate,
            'end_date':endDate,
            'page': page,
            'contract_id': contract_id,
            'contract_name_id':contract_name_id,
            'checked': is_selected_level_two
        },
        success:function(response){
            var levelThreeId = "#collapse-"+ contract_id + '-' + payment_type_id;
            $(levelThreeId).html(response);
            dataArrLevelTwo.push(contract_type_id + '-' + contract_id +'-' + payment_type_id);
        },
        complete:function(response){

            if(is_unapproved){
                var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
                var levelTwoApproveElemId = "level-two-approve-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
                var levelThreeApproveElemClass = "select_logs_" + contract_type_id + '_' + contract_id + '_' + payment_type_id;
                var levelThreeRejectElemClass = "reject_logs_" + contract_type_id + '_' + contract_id + '_' + payment_type_id;
                var levelOneElemId = "level-one-approve-" + contract_type_id;
                var levelTwoLikeId = "level-two-approve-" + contract_type_id + '-' + contract_id;
    
                var ifLevelThreeLoaded = $('[class^=' + levelThreeApproveElemClass + ']').length;
    
                if(ifLevelThreeLoaded > 0){
                    if(unApprove){
                        $('[id^='+ levelTwoApproveElemId + ']').prop('checked', false);
                        $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
                        $('[class^=' + levelThreeApproveElemClass + ']').prop('checked',false);
                        $('[id^='+ levelOneElemId + ']').prop('checked', false);
                        $('[id^='+ levelTwoApproveElemId + ']').attr("disabled", true);
                        $('[id^='+ levelTwoRejectElemId + ']').attr('disabled', true);
                        $('[class^=' + levelThreeApproveElemClass + ']').attr('disabled',true);
                        $('[class^=' + levelThreeRejectElemClass + ']').attr('disabled',true);
                        disabled = true;
                        $('[id^='+ levelTwoLikeId + ']').each(function(){
                            var disabledValTwo = $(this).prop('disabled');
                            if(disabledValTwo == false) {
                                disabled = false;
                            }
                        });
                        $('[id^='+ levelOneElemId + ']').prop('disabled', disabled);
                    } else {
                        $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
                    }
                } else {
                    if(unApprove){
                        $('[id^='+ levelTwoApproveElemId + ']').prop('checked', false);
                        $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
                        $('[class^=' + levelThreeApproveElemClass + ']').prop('checked',false);
                        $('[id^='+ levelOneElemId + ']').prop('checked', false);
                        $('[id^='+ levelTwoApproveElemId + ']').attr("disabled", true);
                        $('[id^='+ levelTwoRejectElemId + ']').attr('disabled', true);
                        $('[class^=' + levelThreeApproveElemClass + ']').attr('disabled',true);
                        $('[class^=' + levelThreeRejectElemClass + ']').attr('disabled',true);

                        disabled = true;
                        $('[id^='+ levelTwoLikeId + ']').each(function(){
                            var disabledValTwo = $(this).prop('disabled');
                            if(disabledValTwo == false) {
                                disabled = false;
                            }
                        });
                        $('[id^='+ levelOneElemId + ']').prop('disabled', disabled);
                    } else {
                        $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
                    }                
                }
            }
            $('.overlay').hide();
        }
    });
}

// object containing combination of contract_type and its logs for submission.
log_temp_ids = JSON.parse(JSON.stringify(log_ids)); //Hold original log_ids for approval.

// Function for select deselect on level one.
function selectLogLevelOne(contract_id){
    $(".overlay").show();
    var levelOne = '#level-one-approve-' + contract_id;
    // alert($(levelOne).prop('checked'));
    if($(levelOne).prop('checked')){
        var temp_selected_non_selected_log_ids = log_ids[contract_id];
            for (i = 0; i < temp_selected_non_selected_log_ids.length; i++) {
              var temp_log_id = temp_selected_non_selected_log_ids[i];
              add_remove_logs(temp_log_id,1,true);
            }
        log_temp_ids[contract_id] = log_ids[contract_id];
    } else {
        var temp_selected_non_selected_log_ids = log_temp_ids[contract_id];
        for (i = 0; i < temp_selected_non_selected_log_ids.length; i++) {
            var temp_log_id = temp_selected_non_selected_log_ids[i];
            log_temp_ids[contract_id] = jQuery.grep(log_temp_ids[contract_id], function(value) {
                return value != temp_log_id;
            });
            add_remove_logs(temp_log_id,-1,false);
        }
        // log_temp_ids[contract_id].splice(0, temp_selected_non_selected_log_ids.length);
    }

    console.log("original", log_ids);
    console.log("temp", log_temp_ids);
    
    // var levelOne = '#level-one-approve-' + contract_id;
    // // alert($(levelOne).prop('checked'));
    var levelTwoElem = 'level-two-approve-' + contract_id;
    var getElementLength = $('[id^='+ levelTwoElem + ']').length;
    if(getElementLength > 0){
        var levelThreeElem = 'select_logs_' + contract_id;
        checkLevelThree = $('[class^='+ levelThreeElem + ']').length;
        if(checkLevelThree > 0){

            if($(levelOne).prop('checked')){

                $('[class^='+ levelThreeElem + ']').each(function(){
                    var levelThreeElemId = $(this).attr('id');
    
                    var checkDisabled = $('#'+ levelThreeElemId).prop('disabled');
                    if(checkDisabled){
                        
                        $('#'+ levelThreeElemId).prop('checked', false);
                        
                    } else {
                        
                        $('#'+ levelThreeElemId).prop('checked', true);
                    }
                    $(levelOne).prop('checked', true);
                    $('[id^='+ levelTwoElem + ']').prop('checked', true);
                });


                // var checkDisabled = $('[class^='+ levelThreeElem + ']').is('[disabled=disabled]');
                // if(checkDisabled){
                //     console.log("Cannot select disabled logs.");
                //     $('[id^='+ levelTwoElem + ']').prop('checked', false);
                //     $('[class^='+ levelThreeElem + ']').prop('checked', false);
                //     $(levelOne).prop('checked', false);
                // } else {
                //     $('[id^='+ levelTwoElem + ']').prop('checked', true);
                //     $('[class^='+ levelThreeElem + ']').prop('checked', true);
                // }
                // $('[id^='+ levelTwoElem + ']').prop('checked', true);
                // $('[class^='+ levelThreeElem + ']').prop('checked', true);
            } else {
                $('[id^='+ levelTwoElem + ']').prop('checked', false);
                $('[class^='+ levelThreeElem + ']').prop('checked', false);
            }

        }else {
            console.log("Please load level three data.");
            // alert($(levelOne).prop('checked'));
            if($(levelOne).prop('checked')){
                checkIfChecked = $(levelOne).prop('checked', true);
                $('[id^='+ levelTwoElem + ']').prop('checked', true);
            } else {
                checkIfChecked = $(levelOne).prop('checked', false);
                $('[id^='+ levelTwoElem + ']').prop('checked', false);
            }
        }
    } else {
        console.log("Please load level two and level three data.");
        console.log("changed original", log_ids);
        console.log("changed temp", log_temp_ids);
        // alert($(levelOne).prop('checked'));
        if($(levelOne).prop('checked')){
            checkIfChecked = $(levelOne).prop('checked', true);
        } else {
            checkIfChecked = $(levelOne).prop('checked', false);
        }
    }

    if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
        $('.submitApproval').attr("disabled",false);
    }else{
        $('.submitApproval').attr("disabled",true);
    }
    $(".overlay").hide();
}

var logArrLevelTwo = [];

// Function for select deselect on level two.
function selectLogLevelTwo(contract_type_id,contract_id, payment_type_id, key, numberOfRows){
    $(".overlay").show();
    console.log("original", log_ids);
    console.log("level two temp", log_temp_ids);

    var levelThreeCheckboxClass = ".select_logs_" + contract_type_id + "_" + contract_id + "_" +payment_type_id;
    var levelTwo = "level-two-approve-" + contract_type_id + "-" + contract_id + "-" + payment_type_id;
    var levelTwoLikeId = "level-two-approve-" + contract_type_id;
    var levelOne = "#level-one-approve-" + contract_type_id;

    // var levelTwoCheckId = "#level-two-approve-" + contract_type_id + "-" + contract_id + '-' + physician_id + '-' + key;
    var getElementLength = $(levelThreeCheckboxClass).length;

    if(getElementLength > 0){
        checkIfChecked = $('[id^='+ levelTwo + ']').prop('checked');
        if(checkIfChecked){

            $(levelThreeCheckboxClass).each(function(){
                log_temp_ids[contract_type_id].push($(this).val());
                add_remove_logs($(this).val(),1,true);
                var levelThreeElemId = $(this).attr('id');

                // var checkDisabled = $(this).is('[disabled=disabled]');
                // var checkDisabled = $('[class^='+ levelThreeElemId + ']').is('[disabled=disabled]');

                var checkDisabled = $('#'+ levelThreeElemId).prop('disabled');
                if(checkDisabled){
                    console.log("Cannot select disabled logs.");
                    // checkIfChecked = $(levelTwoCheckId).prop('checked', checkIfChecked);
                    $('#'+ levelThreeElemId).prop('checked',false);
                } else {
                    $('#'+ levelThreeElemId).prop('checked',checkIfChecked);
                }
            });

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }

        } else {
            // Remove log_ids from approval object.
            // Add log_ids for approval object.

            // temp = [];
            $(levelThreeCheckboxClass).each(function(){
                var log_id = $(this).val();
                log_temp_ids[contract_type_id] = jQuery.grep(log_temp_ids[contract_type_id], function(value) {
                    return value != log_id;
                });
                add_remove_logs($(this).val(),-1,false);

                var levelThreeElemId = $(this).attr('id');

                // var checkDisabled = $(this).is('[disabled=disabled]');
                var checkDisabled = $('#'+ levelThreeElemId).prop('disabled');

                if(checkDisabled){
                    console.log("Cannot select disabled logs.");
                    // checkIfChecked = $(levelTwoCheckId).prop('checked', checkIfChecked);
                    $('#'+ levelThreeElemId).prop('checked',false);
                } else {
                    $('#'+ levelThreeElemId).prop('checked',checkIfChecked);
                }
            });

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
            console.log("level two unchecked", log_temp_ids);
        }
    
        // for(var i = 1; i<= numberOfRows; i++){
        //     var levelTwoCheckIdTemp = "#level-two-approve-" + contract_id + '-' + physician_id + '-' + i;
        //     checkIfLevelTwoIsChecked = $(levelTwoCheckIdTemp).prop('checked');
        //     var levelTwoCheckIdTemp = "#level-one-approve-" + contract_id;
        //     if(checkIfLevelTwoIsChecked){
        //         $(levelTwoCheckIdTemp).prop('checked',checkIfLevelTwoIsChecked);
        //     } else {
        //         $(levelTwoCheckIdTemp).prop('checked',checkIfLevelTwoIsChecked);
        //     }
        // }
        $('.overlay').hide();
    }
    else {
        console.log("leveltwoItmes", logArrLevelTwo);
        data_key = contract_type_id + "_" + contract_id;
        if( logArrLevelTwo[data_key] != undefined ) {
            var logArr = logArrLevelTwo[data_key];
            checkIfChecked = $('[id^='+ levelTwo + ']').prop('checked')
            if(checkIfChecked){
                var temp_selected_non_selected_log_ids = logArr;
                for (i = 0; i < temp_selected_non_selected_log_ids.length; i++) {
                    var temp_log_id = temp_selected_non_selected_log_ids[i].log_id;
                    add_remove_logs(temp_log_id,1,true);
                }
                $('[id^='+ levelTwo + ']').prop('checked', checkIfChecked);
                console.log("Please Load the logs from IF.");
            } else {
                var temp_selected_non_selected_log_ids = logArr;
                for (i = 0; i < temp_selected_non_selected_log_ids.length; i++) {
                    var temp_log_id = temp_selected_non_selected_log_ids[i].log_id;
                    add_remove_logs(temp_log_id,-1,false);
                }
                $('[id^='+ levelTwo + ']').prop('checked', checkIfChecked);
                console.log("Please Load the logs from Else.");
            }

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
            $('.overlay').hide();
        } else {
            $('.overlay').show();
            var logArr = [];
            $.ajax({
                url:'getLogsForApproval',
                type:'get',
                data:{
                    'hospital':$('#hospital').val(),
                    'agreement':$('#agreement').val(),
                    'practice':$('#practice').val(),
                    'physician':0,
                    'payment_type':payment_type_id, //for new scrolldown
                    'contract_type':contract_type_id,
                    'start_date':$('#start_date').val(),
                    'end_date':$('#end_date').val(),
                    'report': true,
                    'contract_id': contract_id
                },
                success:function(response){
                    data_key = contract_type_id + "_" + contract_id;
                    logArrLevelTwo[data_key] = response.items;
                    var logArr = response.items;
                    checkIfChecked = $('[id^='+ levelTwo + ']').prop('checked')
                    if(checkIfChecked){
                        var temp_selected_non_selected_log_ids = logArr;
                        for (i = 0; i < temp_selected_non_selected_log_ids.length; i++) {
                            var temp_log_id = temp_selected_non_selected_log_ids[i].log_id;
                            add_remove_logs(temp_log_id,1,true);
                        }
                        $('[id^='+ levelTwo + ']').prop('checked', checkIfChecked);
                        $('.submitApproval').attr("disabled",false);
                    } else {
                        var temp_selected_non_selected_log_ids = logArr;
                        for (i = 0; i < temp_selected_non_selected_log_ids.length; i++) {
                            var temp_log_id = temp_selected_non_selected_log_ids[i].log_id;
                            add_remove_logs(temp_log_id,-1,false);
                        }
                        $('[id^='+ levelTwo + ']').prop('checked', checkIfChecked);
                    }
    
                    var tempFlagLevelTwo = true;
                    $('[id^='+ levelTwoLikeId + ']').each(function(){
                        var checkedValTwo = $(this).prop('checked');
                        if(checkedValTwo == false){
                            tempFlagLevelTwo = checkedValTwo
                        }
                    });
    
                    if(tempFlagLevelTwo){
                        $(levelOne).prop('checked', true);
                    } else {
                        $(levelOne).prop('checked', false);
                    }
                },
                complete:function(){
                    $('.overlay').hide();
                    // return logArr;
                }
            });    
        }
        // console.log("Please Load the logs.");
    }
    console.log("leveltwoItmes from outside", logArrLevelTwo);
    if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
        $('.submitApproval').attr("disabled",false);
    }else{
        $('.submitApproval').attr("disabled",true);
    }
}

function unapproveLogLevelTwoWithReason(contract_type_id, contract_id, payment_type_id, contract_name_id){
    var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
    var levelTwoApproveElemId = "level-two-approve-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
    var levelThreeApproveElemClass = "select_logs_" + contract_type_id + '_' + contract_id + '_' + payment_type_id;
    var levelThreeRejectElemClass = "reject_logs_" + contract_type_id + '_' + contract_id + '_' + payment_type_id;
    var levelOneElemId = "level-one-approve-" + contract_type_id;

    var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
    var unApprove = $('[id^='+ levelTwoRejectElemId + ']').prop('checked');


    $('.overlay').show();
    $('#form_replace').html();
    var ifLevelThreeLoaded = $('[class^=' + levelThreeApproveElemClass + ']').length;

    if(unApprove){
        if(ifLevelThreeLoaded > 0){
            $('[class^=' + levelThreeApproveElemClass + ']').prop('checked', false);
            $('[class^=' + levelThreeRejectElemClass + ']').prop('checked', false);
            var reason_selected=add_Reason(0,'unapprove_all',0, contract_type_id, contract_id, payment_type_id, contract_name_id, unApprove);
        } else {
            var reason_selected=add_Reason(0,'unapprove_all',0, contract_type_id, contract_id, payment_type_id, contract_name_id, unApprove);
        }
    } else {
        $('.overlay').hide();
    }
}

function unapproveLogLevelTwo(contract_type_id, contract_id, payment_type_id, contract_name_id, reason_selected, selected_reason_text = '', unApprove){
    $('.overlay').css('background-image', '../img/overlay.gif');
    $('.overlay').show();
    $('#form_replace').html();
    $.ajax({
        url:'getLogsForApproval',
        type:'get',
        data:{
            'hospital':$('#hospital').val(),
            'agreement':$('#agreement').val(),
            'practice':$('#practice').val(),
            'physician':0,
            'payment_type':payment_type_id, //for new scrolldown
            'contract_type':contract_type_id,
            'start_date':$('#start_date').val(),
            'end_date':$('#end_date').val(),
            'report': true,
            'contract_id': contract_id,
            'contract_name_id': contract_name_id,
            'is_unapprove': true,
            'unapprove_reason': reason_selected,
            'unapprove_custome_reason': selected_reason_text
        },
        success:function(response){

            if(response.status == "success"){
                getSummationLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),0,payment_type_id,contract_type_id,$('#start_date').val(),$('#end_date').val(),'', contract_id, contract_name_id, false, true, unApprove)
                    
            } else if(response.status == "payment_eroor" || response.status == "log_error"){
                console.log("Something went wrong");
                $('.overlay').hide();
            }
            
        },
        complete:function(){
            // $('.overlay').hide();
            location.reload();
        }
    });
}

// Function for select deselect on Logs.
function selectDeselectLog(contract_type_id, contract_id, physician_id, payment_type_id, log_id){
    $('.overlay').show();
    // var ifChecked = $(levelThreeCheckboxClass).prop('checked', false);
    var levelThreeCheckboxClass = ".select_logs_" + contract_type_id + "_" + contract_id + "_" + physician_id + "_" +payment_type_id;
    var levelTwo = "level-two-approve-" + contract_type_id + "-" + contract_id + "-" + physician_id + "-" + payment_type_id;
    var levelTwoLikeId = "level-two-approve-" + contract_type_id;
    var levelOne = "#level-one-approve-" + contract_type_id;

    // console.log(log_temp_ids);
    var levelThreeCheckboxId = "#select_log_" + log_id;
    checkIfChecked = $(levelThreeCheckboxId).prop('checked');
    if(checkIfChecked){
        $(levelThreeCheckboxId).prop('checked', true);
        log_temp_ids[contract_type_id];
        const checkIndex = log_temp_ids[contract_type_id].indexOf(log_id);
        if (checkIndex <= -1) {
            log_temp_ids[contract_type_id].push(log_id);
        }
        console.log(log_temp_ids);
        $("#reason_"+log_id).val('');
        $("#reject_log_"+log_id).prop("checked", false);
        $('.submitApproval').attr("disabled",false);
        add_remove_logs(log_id,1,true);

        var tempFlag = true;
        $(levelThreeCheckboxClass).each(function(){
            var checkedVal = $(this).prop('checked');
            var DisabledVal = $(this).prop('disabled');
            if(checkedVal == false && DisabledVal == false){
                tempFlag = checkedVal;
            }
        });

        if(tempFlag){
            $('[id^='+ levelTwo + ']').prop('checked', true);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo;
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        } else {
            $('[id^='+ levelTwo + ']').prop('checked', false);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        }

    } else {
        $(levelThreeCheckboxId).prop('checked', false);
        log_temp_ids[contract_type_id];
        const checkIndex = log_temp_ids[contract_type_id].indexOf(log_id);
        if (checkIndex > -1) {
            log_temp_ids[contract_type_id].splice(checkIndex, 1);
        }
        console.log(log_temp_ids);
        add_remove_logs(log_id,-1,false);
        //$('.submitApproval').attr("disabled", !($('.appDashboardTable input[type="checkbox"]').is(":checked")));
        if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
            $('.submitApproval').attr("disabled",false);
        }else{
            $('.submitApproval').attr("disabled",true);
        }

        $(levelThreeCheckboxClass).each(function(){
            var log_id = $(this).val();
            log_temp_ids[contract_type_id] = jQuery.grep(log_temp_ids[contract_type_id], function(value) {
                return value != log_id;
            });
        });

        var tempFlag = true;
        $(levelThreeCheckboxClass).each(function(){
            var checkedVal = $(this).prop('checked');
            if(checkedVal == false){
                tempFlag = checkedVal
            }
        });

        if(tempFlag){
            $('[id^='+ levelTwo + ']').prop('checked', true);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        } else {
            $('[id^='+ levelTwo + ']').prop('checked', false);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        }
    }
    $('.overlay').hide();
}

//add logs for actions function
function add_remove_logs(log_id,approve,status){
    var log_id = log_id.toString()
    if($("#approve_logs").val() != '') {
        var approved_logs = $("#approve_logs").val().split(",");
    }else{
        var approved_logs = [];
    }
    if($("#reject_logs").val() != '') {
        var rejecteded_logs = $("#reject_logs").val().split(",");
    }else{
        var rejecteded_logs = [];
    }
    if($("#no_action").val() != '') {
        var no_action = $("#no_action").val().split(",");
    }else{
        var no_action = [];
    }
    if(status) {
        if (approve == 1) {
            if (!approved_logs.includes(log_id)) {
                approved_logs.push(log_id);
            }
            if (rejecteded_logs.includes(log_id)) {
                var index = rejecteded_logs.indexOf(log_id);
                if (index > -1) {
                    rejecteded_logs.splice(index, 1);
                }
            }
            if (no_action.includes(log_id)) {
                var index1 = no_action.indexOf(log_id);
                if (index1 > -1) {
                    no_action.splice(index1, 1);
                }
            }
        } else {
            if (!rejecteded_logs.includes(log_id)) {
                rejecteded_logs.push(log_id);
            }
            if (approved_logs.includes(log_id)) {
                var index = approved_logs.indexOf(log_id);
                if (index > -1) {
                    approved_logs.splice(index, 1);
                }
            }
            if (no_action.includes(log_id)) {
                var index1 = no_action.indexOf(log_id);
                if (index1 > -1) {
                    no_action.splice(index1, 1);
                }
            }
        }
    }else{
        if (approved_logs.includes(log_id)) {
            var index = approved_logs.indexOf(log_id);
            if (index > -1) {
                approved_logs.splice(index, 1);
            }
        }
        if (rejecteded_logs.includes(log_id)) {
            var index = rejecteded_logs.indexOf(log_id);
            if (index > -1) {
                rejecteded_logs.splice(index, 1);
            }
        }
        if (approve == -1) {
            if (!no_action.includes(log_id)) {
                no_action.push(log_id);
            }
        }
    }
    $("#approve_logs").val(approved_logs);
    $("#reject_logs").val(rejecteded_logs);
    $("#no_action").val(no_action);
}

function selectDeselectRejectLog(contract_type_id, physician_id, payment_type_id, log_id, contract_id){
    var log_id= log_id;
    var levelThreeCheckboxClass = ".select_logs_" + contract_type_id + "_" + physician_id + "_" +payment_type_id;
    var levelTwo = "level-two-approve-" + contract_type_id + "-" + physician_id + "-" + payment_type_id;
    var levelTwoLikeId = "level-two-approve-" + contract_type_id;
    var levelOne = "#level-one-approve-" + contract_type_id;

    var levelThreeCheckboxId = "#reject_log_" + log_id;
    checkIfChecked = $(levelThreeCheckboxId).prop('checked');
    if(checkIfChecked) {
        var check_id=0;
        $("#select_log_"+log_id).prop("checked", false);
        var reason_selected=add_Reason(log_id,'single_reject',check_id);

        var levelTwoLikeId = "level-two-approve-" + contract_type_id + "-" + contract_id + "-" + physician_id + "-" + payment_type_id;
        $('[id^=' + levelTwoLikeId + ']').prop('checked', false);
        var levelOneLikeId = "level-one-approve-" + contract_type_id;
        var checkLengthLevelTwo = $('[id^=level-two-approve-'+ contract_type_id + ']' + ':checked').length;
        if(checkLengthLevelTwo > 0){
            $('[id^=' + levelOneLikeId + ']').prop('checked', true);
        } else {
            $('[id^=' + levelOneLikeId + ']').prop('checked', false);
        }

        var tempFlag = true;
        $(levelThreeCheckboxClass).each(function(){
            var checkedVal = $(this).prop('checked');
            if(checkedVal == false){
                tempFlag = checkedVal
            }
        });

        if(tempFlag){
            $('[id^='+ levelTwo + ']').prop('checked', true);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        } else {
            $('[id^='+ levelTwo + ']').prop('checked', false);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        }

    }else{
        $("#reason_"+log_id).val('');
        add_remove_logs(log_id,-1,false);
        $('#reject_reason_'+log_id).remove();
        $('#reject_manager_type_'+log_id).remove();
        //$('.submitApproval').attr("disabled", !($('.appDashboardTable input[type="checkbox"]').is(":checked")));
        if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
            $('.submitApproval').attr("disabled",false);
        }else{
            $('.submitApproval').attr("disabled",true);
        }

        var tempFlag = true;
        $(levelThreeCheckboxClass).each(function(){
            var checkedVal = $(this).prop('checked');
            if(checkedVal == false){
                tempFlag = checkedVal
            }
        });

        if(tempFlag){
            $('[id^='+ levelTwo + ']').prop('checked', true);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        } else {
            $('[id^='+ levelTwo + ']').prop('checked', false);

            var tempFlagLevelTwo = true;
            $('[id^='+ levelTwoLikeId + ']').each(function(){
                var checkedValTwo = $(this).prop('checked');
                if(checkedValTwo == false){
                    tempFlagLevelTwo = checkedValTwo
                }
            });

            if(tempFlagLevelTwo){
                $(levelOne).prop('checked', true);
            } else {
                $(levelOne).prop('checked', false);
            }
        }
    }
}

//add reason function
function add_Reason(log_id,status,check_id, contract_type_id, contract_id, payment_type_id, contract_name_id, unApprove = '')
{
    var select_reason=$("#reasons").html();
    var reason_selected=1;
    var resonhtml= "<div style='background: #fff;position: absolute;width:50%;margin: 0 auto;padding: 2% 1%;position: absolute;margin: 12% 25%; max-height: 500px; overflow: auto;'>";
    var rejectButton = "<button class='btn btn-primary reasonok' id='reasonok' type='button' style='margin-left:5px;'>Ok</button></div>";
    
    if(status=='single_reject') {
        resonhtml+="<p style='text-align: center;'><b> Add reason for rejecting  log</b></p>";
    } else {
        if('unapprove_all'){
            resonhtml+="<p style='text-align: center;'><b> Add reason for unapprove  logs</b></p>";
            rejectButton = "<button class='btn btn-primary reasonok' id='reasonokunapprove' type='button' style='margin-left:5px;'>Ok</button></div>";
        } else {
            resonhtml+="<p style='text-align: center;'><b> Add reason for rejecting all logs</b></p>";
        }
    }
    resonhtml+="<p style='border-top: 2px solid #cfd7e5; padding-top: 15px; font-size: 18px; color: rgb(0, 77, 228);'></p>"+
        "<p> <label>Select Reason : </label>" + select_reason +"</p>" +
        "<p id='custom_reason_text_parent' style='display:none;'> <label>Custom Reason : </label><textarea maxlength='256' id='custom_reason_text' class='form-control' placeholder='Custom reason upto 256 characters...'></textarea></p>"+
        "<p id='error_reason' class='error_reason' style='text-align:center;display:none;color:red;'><b>Please select reason for rejected log.</b></p>"+
        "<p id='error_unapprove' class='error_reason' style='text-align:center;display:none;color:red;'><b>Please select reason for unapprove log.</b></p>"+
        "<p id='error_custom_reason_text' class='error_reason' style='text-align:center;display:none;color:red;'><b>Please add custom reason. Custom reason cannot be blank.</b></p>"+
        "<div class='modal-footer'><button class='btn btn-default reason_cancelMe' type='button'>Cancel</button>" +
        rejectButton +
        "</div>";
    $('.overlay').empty();
    $('.overlay').append(resonhtml);
    // $('.overlay').css('background-image', 'none');
    $('.overlay').show();
    var selectedReasonVal = 0;
    $('#select_reason').change(function(){
        if(this.value=='-1')
        {
        $('#error_reason').hide();
        $('#error_unapprove').hide();
        $('#error_custom_reason_text').hide();
        $('#custom_reason_text_parent').show();
        }
        else
        {
        $('#error_reason').hide();
        $('#error_custom_reason_text').hide();
        $('#custom_reason_text_parent').hide();
        }
        selectedReasonVal = this.value;
    });
    $('.reasonok').on('click',function(){
        reason_selected=$('#select_reason').val();
        var selected_reason_text='';
        selected_reason_text=$('#select_reason option:selected').html();
        if($('#select_reason').val()==0)
        {
            $('#error_custom_reason_text').hide();
            $('#error_reason').show();
        }
        else if((reason_selected=='-1') && ($('#custom_reason_text').val() == ''))
        {
            $('#error_reason').hide();
            $('#error_custom_reason_text').show();
        }
        else
        {
            $('#error_reason').hide();
            $('#error_custom_reason_text').hide();
            $('#reason_'+log_id).val(reason_selected);
            add_remove_logs(log_id,0,true);
            if(reason_selected=='-1')
            {
                selected_reason_text=$('#custom_reason_text').val();
            }
            var hidden_reasons ='<input type="hidden" name="reject_reason_'+log_id+'" id="reject_reason_'+log_id+'" value="'+reason_selected+'">'+
            '<input type="hidden" name="reject_reason_text_'+log_id+'" id="reject_reason_text_'+log_id+'" value="'+selected_reason_text+'">'+
            '<input type="hidden" name="reject_manager_type_'+log_id+'" id="reject_manager_type_'+log_id+'" value="'+$('#manager_type_'+log_id).val()+'">';
            $("#approve_reject_stats").append(hidden_reasons);
            $('.overlay').hide();
            $('.overlay').empty();
            $('.overlay').css('background-image', '../img/overlay.gif');
            $('.submitApproval').attr("disabled",false);
        }
    });
    $('.reason_cancelMe').on('click',function(){
        add_remove_logs(log_id,-1,false);
        $('#reject_reason_'+log_id).remove();
        $('#reject_manager_type_'+log_id).remove();
        /********** This is removed from below commented code and newly added to check the logs on cancel */
        // var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + physician_id + '-' + payment_type_id;
        // var levelTwoApproveElemId = "level-two-approve-" + contract_type_id + '-' + contract_id + '-' + physician_id + '-' + payment_type_id;
        // var logApprove = 'select_logs_' + contract_type_id;
        // $('[id^='+ levelTwoApproveElemId + ']').prop('checked');
        // $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
        // $('[class^='+ logApprove + ']').prop('checked', true);
        /********** End This is removed from below commented code and newly added to check the logs on cancel */

        $('.overlay').hide();
        $('.overlay').empty();
        $('.overlay').css('background-image', '../img/overlay.gif');
        $("#reject_log_"+log_id).prop("checked", false);
        //$('.submitApproval').attr("disabled", !($('.appDashboardTable input[type="checkbox"]').is(":checked")));
        if($("#approve_logs").val() != '' || $("#reject_logs").val() != ''){
            $('.submitApproval').attr("disabled",false);
        }else{
            $('.submitApproval').attr("disabled",true);
        }
        
        // Below code is commented to improve performance
        if(unApprove != ''){
            var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
            var levelTwoApproveElemId = "level-two-approve-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
            is_selected_level_two = $('[id^='+ levelTwoApproveElemId + ']').prop('checked')

            getSummationLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),0,payment_type_id,contract_type_id,$('#start_date').val(),$('#end_date').val(),'', contract_id, contract_name_id, is_selected_level_two, is_unapproved = false, unApprove)

            if(unApprove){
                $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
            } else {
                $('[id^='+ levelTwoRejectElemId + ']').prop('checked', true);
            }
        }
    });
    $('#reasonokunapprove').on('click',function(){

        var reason_selected = selectedReasonVal;
        var selected_unapprove_reason_text = '';
        if(selectedReasonVal==0 || selectedReasonVal=='0')
        {
            $('#error_custom_reason_text').hide();
            $('#error_reason').hide();
            $('#error_unapprove').show();
        }
        else if((selectedReasonVal=='-1' || selectedReasonVal== -1) && ($('#custom_reason_text').val() == ''))
        {
            $('#error_unapprove').hide();
            $('#error_custom_unapprove_reason_text').show();
        }
        else
        {
            reason_selected = selectedReasonVal;
            selected_unapprove_reason_text=$('#reject_reason_text_0').val(); //select reason for unapproval using log_id = 0
            // unapproveLogLevelTwo(contract_type_id, contract_id, physician_id, payment_type_id, reason_selected, selected_unapprove_reason_text, unApprove);
            warning_modal(contract_type_id, contract_id, payment_type_id, contract_name_id, reason_selected, selected_unapprove_reason_text, unApprove);
        }
        
    });
}

//add reason function
function warning_modal(contract_type_id, contract_id, payment_type_id, contract_name_id, reason_selected, selected_unapprove_reason_text, unApprove)
{
    var resonhtml= "<div id='confirm_unapprove' style='background: #fff;position: absolute;width:50%;margin: 0 auto;padding: 2% 1%;position: absolute;margin: 20% 25%; max-height: 500px; overflow: auto;'>";
    var rejectButton = "<button class='btn btn-primary reasonok' id='unapprove_btn' type='button' style='margin-left:5px;'>Ok</button></div>";
    
    resonhtml+="<p style='text-align: center;font-size: 20px;color: red;'><b> Are you sure you want to Reject & Unapprove all logs back to provider ?</b></p>";
    
    resonhtml+="<p style='border-top: 2px solid #cfd7e5; padding-top: 15px; font-size: 18px; color: rgb(0, 77, 228);'></p>"+
        "<div style='text-align: center;'><button class='btn btn-default unapprove_cancelMe' type='button'>Cancel</button>" +
        rejectButton +
        "</div>";
    $('.overlay').empty();
    $('.overlay').append(resonhtml);
    $('.overlay').css('background-image', '../img/overlay.gif');
    $('.overlay').show();
    var selectedReasonVal = 0;
    $('.unapprove_cancelMe').on('click',function(){
        var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
        var levelTwoApproveElemId = "level-two-approve-" + contract_type_id + '-' + contract_id + '-' + payment_type_id;
        var logApprove = 'select_logs_' + contract_type_id;
        $('[id^='+ levelTwoApproveElemId + ']').prop('checked');
        $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
        $('[class^='+ logApprove + ']').prop('checked', true);
        // $('[class^='+ logApprove + ']').each(function(i, obj) {
        //     add_remove_logs(obj.value,1,true);
        //     $('#reject_reason_'+obj.value).remove();
        //     $('#reject_manager_type_'+obj.value).remove();
        // });
        $('#confirm_unapprove').hide();
        $('.overlay').hide();
        // Below logic is commented for performance improvement.

        // if(unApprove != ''){
        //     var levelTwoRejectElemId = "level-two-reject-" + contract_type_id + '-' + contract_id + '-' + physician_id + '-' + payment_type_id;
        //     var levelTwoApproveElemId = "level-two-approve-" + contract_type_id + '-' + contract_id + '-' + physician_id + '-' + payment_type_id;
        //     is_selected_level_two = $('[id^='+ levelTwoApproveElemId + ']').prop('checked')

        //     getSummationLogsForApprovalByAjaxRequest($('#hospital').val(),$('#agreement').val(),$('#practice').val(),physician_id,payment_type_id,contract_type_id,$('#start_date').val(),$('#end_date').val(),'', contract_id, is_selected_level_two, is_unapproved = false, unApprove)

        //     if(unApprove){
        //         $('[id^='+ levelTwoRejectElemId + ']').prop('checked', false);
        //     } else {
        //         $('[id^='+ levelTwoRejectElemId + ']').prop('checked', true);
        //     }
        //     // $('.overlay').hide();
        // }
    });
    $('#unapprove_btn').on('click',function(){
        $('.overlay').css('background-image', '../img/overlay.gif');
        $('#confirm_unapprove').remove();
        unapproveLogLevelTwo(contract_type_id, contract_id, payment_type_id, contract_name_id, reason_selected, selected_unapprove_reason_text, unApprove);
        // window.location.reload();
        // location.reload();
    });
}
