/**
 * Created by ajaques on 07/3/2019.
 */

var actionsForCurrentPhysician;
var basePath = "";
var physicians;
var isSelectedActionHalfDay = false;
var contractsDataForOtherPhysicians = [];
var physicianActions = [];
var maxDateFor = 0;
/**
 * Bellow vars are used for calendar functionality
 */

var calendarData = {
    dates: [],
    scheduleDates: [],
    holidays: [],
    duration: 0,
    shift: "",
    isDisabled: true
}

var calendarStyles = {}

var calendarFlags = {
    enableAMShifts: false,
    enablePMShifts: false,
    enableWeekDays: false,
    enableWeekEnds: false,
    enableHolidays: false,
    enableOnCallBurden: false
}

function save_log() {
    var dates = $('#select_date').multiDatesPicker('getDates');
    $('#selected_dates').val(dates);
}

function delete_log(log_id) {
    var current_url = basePath + "/deleteLog/" + log_id;
    $.ajax({
        url: current_url
    }).done(function (response) {
        $("#" + log_id).remove();
        $("#" + log_id).remove();
        getContracts($('#physician_id').val());
    }).error(function (e) {
    });

}

function deleteModal(thisObject) {
    var deleteId = thisObject.attr("id");
    $('#modalDeleteLog').attr("onClick", "delete_log(" + deleteId + ")");
}

function initMultiDatesPicker() {
    //fetch physician id
    var physicianId = $('#physician_id').val();
    var contractId;

    for (var i = 0; i < physicians.length; i++) {
        if (physicianId == physicians[i].id) {
            contractId = physicians[i].contract;
        }
    }
    
    $('#contract_name').val(contractId);

    /*set min days & max days on multidates picker as per agreement's start date & end date */
    $.post(basePath + "/getContractPeriod", {
        physicianId: physicianId,
        contractId: contractId
    }, function (contractPeriodData) {

        var contractType = $('#contract_type_' + contractId).val();
        var paymentType = $('#payment_type_' + contractId).val();

        // console.log(contractType);

        maxDateFor = contractPeriodData.max_date;
        $('#select_date').multiDatesPicker({
            maxPicks: 2,
            minDate: contractPeriodData.min_date,
            maxDate: contractPeriodData.max_date
        });

    });
}

function destroyMultiDatesPicker() {
    $('#select_date').multiDatesPicker('destroy');
}

function resetPageData() {
    destroyMultiDatesPicker();
    //clearLogMessage();
    $('#period').val($('#current_period').val());
    initMultiDatesPicker();
}

function clearLogMessage() {
    $('#enterLogMessage').html("");
    $('#enterLogMessage').removeClass("alert-danger");
    $('#enterLogMessage').removeClass("alert-success");
}

function updateCalendar(dates) {
    if(dates===undefined){
        dates = [];
    }
    var contractId = $('#contract_name').val();
    var contractType = $('#contract_type_' + contractId).val();
    var paymentType = $('#payment_type_' + contractId).val();

    if(dates.length>0){
        $('#select_date').multiDatesPicker('addDates',dates,'picked');
    }
    $('#select_date').multiDatesPicker({
        inline: true,
        onSelect: function (dateText, inst) {
            var day =inst.currentDay;
            var month =inst.currentMonth;
            var year =inst.currentYear;
            var dateYear = (month+ 1) + '-' + year;
            var approve = $.inArray(dateYear, approved_logs_months);
            if (approve >= 0){
                $('#submitLog').addClass("disabled");
                $('#enterLogMessage').html("Can not select date from the month of approved logs.");
                $('#enterLogMessage').removeClass("alert-success");
                $('#enterLogMessage').addClass("alert-danger");
                $('#select_date').multiDatesPicker('resetDates', 'picked');
                setTimeout(function() {
                    $('#submitLog').removeClass("disabled");
                    clearLogMessage();
                },3000);
            }
            var days = Math.abs(maxDateFor); // Days you want to subtract
            var date = new Date();
            var last = new Date(date.getTime() - (days * 24 * 60 * 60 * 1000));
            if( (new Date(last).getTime() < new Date(dateText).getTime()))
            {
                $('#submitLog').addClass("disabled");
                $('#enterLogMessage').html("Contract not exist for date so you can not add log.");
                $('#enterLogMessage').removeClass("alert-success");
                $('#enterLogMessage').addClass("alert-danger");
                $('#select_date').multiDatesPicker('resetDates', 'picked');
                setTimeout(function() {
                    $('#submitLog').removeClass("disabled");
                    clearLogMessage();
                },3000);
            }
            if (this.multiDatesPicker.dates.picked.length > 1) {
                //var lastSelected = this.multiDatesPicker.dates.picked[1];
                //$('#select_date').multiDatesPicker('removeDates', lastSelected);
                // $('#select_date').multiDatesPicker('addDates',lastSelected);
                $('#select_date').multiDatesPicker('resetDates', 'picked');
                this.multiDatesPicker.dates.picked[0]= dateText;
            }
        }
    });
    $(".overlay").hide();
}

function refreshApproveLogsView() {
    var contractId = $('#contract_name').val();
    var dateSelector = $('#dateSelector').val();
    $.post(basePath + "/getApproveLogsViewRefresh", {
        contract: contractId,
        dateSelector: dateSelector
    }, function (data) { 
        $('#approveLogs').html(data.html);
        $('#dateSelector')
        .find('option')
        .remove()
        .end();
        dateSelectors = data.date_selectors;
        $.each(data.date_selectors, function () {
            $('#dateSelector').append($("<option></option>")
            .attr("value", this)
            .text(this));
        });
        $('#dateSelector').val(data.date_selector);
        $('#bntApprove').attr('href',$('#bntApprove').attr('href')+'/'+$('#dateSelector').val());
        console.log($('#bntApprove').attr('href'));
    });
}

function updateFieldsForAction() {
    var action = $('#action').val();
    $('input:radio[name=shift]').attr('checked', false);
    var lastSelected = $('#select_date').multiDatesPicker('getDates');
    $('#select_date').multiDatesPicker('resetDates', 'picked');
    clearLogMessage();

    var contractId = $('#contract_name').val();

    if(action == "" || action == null || action == -1){
        $('#divShift input:radio[name=shift]').attr('disabled', true);
        isSelectedActionHalfDay = false;
        calendarFlags.enableAMShifts = false;
        calendarFlags.enablePMShifts = false;
        calendarData.isDisabled = false;
        if(action == -1) {
            $('#custom_action').show();
            $('#custom_action').focus();
        }
        updateCalendar(lastSelected);
    }else {
        $('#custom_action').hide();
        for (var i = 0; i < actionsForCurrentPhysician.length; i++) {
            if (actionsForCurrentPhysician[i].id == action) {
                $('#divShift input:radio[name=shift]').attr('disabled', true);
                isSelectedActionHalfDay = false;
                calendarFlags.enableAMShifts = false;
                calendarFlags.enablePMShifts = false;
                calendarData.isDisabled = false;
                updateCalendar(lastSelected);
                break;
            }
        }
    }
    $(".overlay").hide();
}

function updateFieldsForPeriod() {
    clearLogMessage();
    var lastSelected = $( "#period option:selected" ).text();
    var day =parseInt(lastSelected.split(" ")[3].split("/")[1]);
    var month =parseInt(lastSelected.split(" ")[3].split("/")[0]);
    var year =parseInt(lastSelected.split(" ")[3].split("/")[2]);
    var dateYear = month + '-' + year;
    var approve = $.inArray(dateYear, approved_logs_months);
    if (approve >= 0){
        $('#submitLog').addClass("disabled");
        $('#enterLogMessage').html("Can not select date from the month of approved logs.");
        $('#enterLogMessage').removeClass("alert-success");
        $('#enterLogMessage').addClass("alert-danger");
        $('#select_date').multiDatesPicker('resetDates', 'picked');
        setTimeout(function() {
            $('#submitLog').removeClass("disabled");
            clearLogMessage();
        },3000);
        $('#period').val($('#current_period').val())
    }

    $(".overlay").hide();
}

function getContracts(val) {
    $(".overlay").show();
    var physicianId = val;
    contractsDataForOtherPhysicians = [];

    var contractId;

    for (var i = 0; i < physicians.length; i++) {
        if (physicianId == physicians[i].id) {
            contractId = physicians[i].contract;
        }
    }

    $('#contract_name').val(contractId);

    $.post(basePath + "/getContracts", {
        physicianId: physicianId,
        contractId: contractId
    }, function (data) {
        resetPageData();
        //as we are giving ajax call in initialize calender, we have to delay some time for response function execution
        setTimeout(function () {

            // recent logs
            if (!jQuery.isEmptyObject(data) && !jQuery.isEmptyObject(data.statistics)) {
                $('.onCallLogEntryPanel').css("height","834px");
                $('.onCallLogEntryPanel').css("max-height","834px");
                $('.calendarColorList').hide();
                $('#divShift').hide();
                $('.psa_wrvu').show();
                if(data.enter_by_day) {
                    $('#divEnterByDay').show();
                    $('#divEnterByMonth').hide();
                }
                else {
                    $('#divEnterByDay').hide();
                    $('#divEnterByMonth').show();
                }
                $('#contract_min_hours').html(data.statistics.min_hours);
                $('#contract_max_hours').html(data.statistics.max_hours);
                $('#contract_annual_max_hours').html(data.statistics.annual_cap);
                $('#contract_worked_hours').html(data.statistics.worked_hours);
                $('#contract_prior_worked_hours').html(data.priorMonthStatistics.prior_worked_hours);
                $('#recentLogs').html(data.recent_logs_view);
                $('#log_entry_deadline').val(data.log_entry_deadline);
                $('#enter_by_day').val(data.enter_by_day);
                calendarFlags.enableOnCallBurden = data.burden_of_call;

                /**
                 * key - 'physiciansContractData' contains
                 * 'recent logs' for the physicians in 'current agreement'
                 *
                 * we need to exclude current physician from these logs
                 * because, current physician data is already available as data object
                 */

                var contractsDataForOtherPhysiciansLength = 0;

                var consolidatedRecentLogs = [];
                for (var i = 0; i < data.recent_logs.length; i++) {
                    data.recent_logs[i].isOwner = true;
                    consolidatedRecentLogs.push(data.recent_logs[i])
                }

                var consolidatedSchedule = [];

                $('#action')
                    .find('option')
                    .remove()
                    .end();

                /*
                - added On : 2018/12/20
                - check if custom_action_enabled flag is 1 or 0
                - if 1 - include custom action input else do not include */

                // actions
                if (data.actions.length > 0) {

                    physicianActions = data.actions;
                    $.each(data.actions, function () {
                        $('#action').append($("<option></option>")
                            .attr("value", this.id)
                            .attr("data-action-name", this.name)
                            .text(this.name));
                    });

                    if(data.custom_action_enabled == 1) {
                        $('#custom_action').attr('disabled', false);
                        $('#action').css("margin-bottom", "10px");
                        $('#action').append($("<option></option>")
                            .attr("value", "-1")
                            .attr("data-action-name", "Custom Action")
                            .text("Custom Action"));
                    }else{
                        $('#action').css("margin-bottom","10px");
                    }

                    $('input:radio[name=shift]').attr('checked', false);
                    $('#custom_action').hide();
                    $('#action').show();

                    //$('#divShift').hide();
                    $('#divShift input:radio[name=shift]').attr('disabled', true);
                    isSelectedActionHalfDay = false;
                    calendarData.isDisabled = false;

                } else {
                    physicianActions = [];
                    $('#custom_action').show();
                    $('#action').hide();
                    if(data.custom_action_enabled == 0){
                        $('#custom_action').attr('value','');
                        $('#custom_action').attr('disabled',true);
                    }else{
                        $('#action').append($("<option></option>")
                            .attr("value", "-1")
                            .attr("data-action-name", "Custom Action")
                            .text("Custom Action"));
                        $('#action').val(-1);
                    }
                    isSelectedActionHalfDay = false;
                    calendarData.isDisabled = false;
                }

                // created common variable to loop through for getting duration for action
                actionsForCurrentPhysician = data.actions;
                //approved logs months for disabled selection
                approved_logs_months = data.approved_logs_months;

                // disable existing dates and update calendar
                $('#select_date').multiDatesPicker('resetDates', 'picked');
                $('#select_date').multiDatesPicker('resetDates', 'disabled');
                calendarData.dates = consolidatedRecentLogs; //data.recent_logs;
                calendarData.scheduleDates = consolidatedSchedule; //data.schedule;
                calendarData.holidays = data.holidays; //data.holidays;
                var date_array = [];

                /*
                 * keeping disable dates code for record purpose
                 * */
                /* if (selected_dates.length > 0) {
                 for (var i = 0; i < selected_dates.length; i++) {
                 var dateParts = selected_dates[i].date.split("/"); // Current format MM/DD/YYYY
                 date_array[i] = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                 }

                 $('#select_date').multiDatesPicker({
                 addDisabledDates: date_array
                 });
                 }*/

                updateFieldsForAction();

                // show or hide approve logs div
                $('#approveLogs').html(data.approve_logs_view);
                $('#dateSelector')
                .find('option')
                .remove()
                .end();
                dateSelectors = data.date_selectors;
                $.each(data.date_selectors, function () {
                    $('#dateSelector').append($("<option></option>")
                    .attr("value", this)
                    .text(this));
                });
                $('#bntApprove').attr('href',$('#bntApprove').attr('href')+'/'+$('#dateSelector').val());
            } else {
                $('#action').append('<option data-action-name ="" value="">No actions available.</option>');
                $('#recentLogs').html("There are no recent logs.");
                $(".overlay").hide();
            }
        }, 3000);
    });
    //$(".overlay").hide();
}

     $.ajaxSetup({
         headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
       }
     });
