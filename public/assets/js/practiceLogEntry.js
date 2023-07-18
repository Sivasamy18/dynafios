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

var selected_date;
var contract_period_data = [];
var selectedGlobalContractId;

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
        // getContracts($('#physician_id').val());
        getRecentLogs($('#physician_name').val(), $('#contract_name').val());
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

        //if (contractType == 4) {
        if (paymentType == 3) {
            $('#select_date').multiDatesPicker({
                maxPicks: 90,
                minDate: contractPeriodData.min_date,
                maxDate: contractPeriodData.max_date
            });
        } else {
            maxDateFor = contractPeriodData.max_date;
            $('#select_date, #select_date_time_study').multiDatesPicker({
                maxPicks: 2,
                minDate: contractPeriodData.min_date,
                maxDate: contractPeriodData.max_date
            });

        }
    });
}

function destroyMultiDatesPicker() {
    $('#select_date').multiDatesPicker('destroy');
    $('#select_date_time_study').multiDatesPicker('destroy');
}

function resetPageData() {
    destroyMultiDatesPicker();
    //clearLogMessage();
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

    //if (contractType == 4) {
    if (paymentType == 3) {
        if (calendarData.dates.length > 0 || calendarData.scheduleDates.length > 0) {

            //calendarFlags.enableAMShifts = false;
            //calendarFlags.enablePMShifts = false;
            //var enableAMShifts = false;

            /*if (calendarData.duration = 0.5) {
             if (calendarData.shift == "AM") {
             calendarFlags.enableAMShifts = true;
             } else if (calendarData.shift == "PM") {
             calendarFlags.enablePMShifts = true;
             }
             } else if (calendarData.duration == 1) {
             calendarFlags.enableAMShifts = true;
             calendarFlags.enablePMShifts = true;
             }*/

            $('#select_date').multiDatesPicker({
                inline: true,
                beforeShowDay: function (date) {
                    /**
                     * beforeShowDay
                     * Type: Function( Date date )
                     * Default: null
                     * A function that takes a date as a parameter and must return an array with:
                     * [0]: true/false indicating whether or not this date is selectable
                     * [1]: a CSS class name to add to the date's cell or "" for the default presentation
                     * [2]: an optional popup tooltip for this date
                     * The function is called for each day in the datepicker before it is displayed.
                     */

                    var DateMDY = (date.getMonth() + 1) + '/' +
                        date.getDate() + '/' +
                        date.getFullYear();
                    var disabilityOnAction=false;

                    /**
                     * create vars for style
                     */
                    var amOwnFlag = false;
                    var pmOwnFlag = false;
                    var amSheduleOwnFlag = false;
                    var pmSheduleOwnFlag = false;
                    var fullOwnFlag = false;
                    var fullSheduleOwnFlag = false;
                    var amOtherFlag = false;
                    var pmOtherFlag = false;
                    var fullOtherFlag = false;
                    var style = "";

                    for (var i = 0; i < calendarData.scheduleDates.length; i++) {
                        var dateParts = calendarData.scheduleDates[i].date.split("/"); // Current format MM/DD/YYYY
                        var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                        var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                            selectedDateObj.getDate() + '/' +
                            selectedDateObj.getFullYear();
                        if (DateMDY == selectedDateMDY) {
                            if (calendarData.scheduleDates[i].duration == "AM") {
                                amSheduleOwnFlag = true;
                                style = "am-half-day-schedule";
                            } else if (calendarData.scheduleDates[i].duration == "PM") {
                                pmSheduleOwnFlag = true;
                                style = "pm-half-day-schedule";
                            }
                        }

                        if (amSheduleOwnFlag && pmSheduleOwnFlag) {
                            fullSheduleOwnFlag = true;
                            style = "full-day-schedule";
                        }
                        if (calendarFlags.enableAMShifts && fullSheduleOwnFlag ) { //radio button with value 'PM' selected
                            style = "fullShedule_pm-selected";
                        } else if (calendarFlags.enablePMShifts && fullSheduleOwnFlag ) { //radio button with value 'AM' selected
                            style = "fullShedule_am-selected";
                        } else if (calendarFlags.enableAMShifts && pmSheduleOwnFlag ) { //radio button with value 'PM' selected
                            style =  "pmShedule_pm-selected";
                        } else if (calendarFlags.enablePMShifts && pmSheduleOwnFlag ) { //radio button with value 'AM' selected
                            style =  "pmShedule_am-selected";
                        }else if (calendarFlags.enableAMShifts && amSheduleOwnFlag ) { //radio button with value 'PM' selected
                            style = "amShedule_pm-selected";
                        } else if (calendarFlags.enablePMShifts && amSheduleOwnFlag ) { //radio button with value 'AM' selected
                            style = "amShedule_am-selected";
                        }


                    }
                    for (var i = 0; i < calendarData.dates.length; i++) {
                        var dateParts = calendarData.dates[i].date.split("/"); // Current format MM/DD/YYYY
                        var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                        var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                            selectedDateObj.getDate() + '/' +
                            selectedDateObj.getFullYear();

                        if (DateMDY == selectedDateMDY) {
                            if ("isOwner" in calendarData.dates[i]) {
                                if (calendarData.dates[i].duration == "AM") {
                                    amOwnFlag = true;
                                    style = "am-half-day";
                                } else if (calendarData.dates[i].duration == "PM") {
                                    pmOwnFlag = true;
                                    style = "pm-half-day";
                                } else if (calendarData.dates[i].duration == "Full Day") {
                                    fullOwnFlag = true;
                                    style = "full-day";
                                }else if (calendarData.dates[i].duration == "On call full Day") {
                                    fullOwnFlag = true;
                                    style = "on_callfull-day";
                                }
                            } else {
                                if (calendarData.dates[i].duration == "AM") {
                                    amOtherFlag = true;
                                    style = "am-half-day-other";
                                } else if (calendarData.dates[i].duration == "PM") {
                                    pmOtherFlag = true;
                                    style = "pm-half-day-other";
                                } else if (calendarData.dates[i].duration == "Full Day") {
                                    fullOtherFlag = true;
                                    style = "full-day-other";
                                } else if (calendarData.dates[i].duration == "On call full Day") {
                                    fullOtherFlag = true;
                                    style = "full-day-other";
                                }
                            }
                        }
                        else {
                            /*disable dates in aprrove month*/
                            var dateYear = (date.getMonth() + 1) + '-' + date.getFullYear();
                            // var approve = $.inArray(dateYear, approved_logs_months);
                            /*if(approve>=0)
                             {
                             console.log(selectedDateMDY);
                             //return [false, ""];
                             //return [calendarData.dates[i].isDisabled, true];
                             }*/

                            var inputDate = date.getFullYear() + '-' + ("0" + (date.getMonth() + 1)).slice(-2) + '-' + ("0" + date.getDate()).slice(-2);

                            for (var rangeIndex in approved_logs_months) {
                                if (approved_logs_months.hasOwnProperty(rangeIndex)) {
                                    var rangeObj = approved_logs_months[rangeIndex];
                                    for (var prop in rangeObj) {
                                        // skip loop if the property is from prototype
                                        if (!rangeObj.hasOwnProperty(prop)) continue;

                                        var rangeStartDate = new Date(rangeObj["start_date"]);
                                        var rangeEndDate = new Date(rangeObj["end_date"]);
                                        var inputDate = new Date(inputDate);
                                        if(inputDate >= rangeStartDate && inputDate <= rangeEndDate){
                                            var approve = true;
                                        }
                                        
                                    }
                                }
                            }

                        }

                        if (amOwnFlag && pmOwnFlag) {
                            style = "full-day";
                        }
                        if (amOtherFlag && pmOtherFlag) {
                            style = "full-day-other";
                        }
                        if (amOwnFlag && pmOtherFlag) {
                            style = "pm-half-day-other-am-own";
                        }
                        if (amOtherFlag && pmOwnFlag) {
                            style = "am-half-day-other-pm-own";
                        }
                    }
                    if (fullOwnFlag && pmSheduleOwnFlag && !fullSheduleOwnFlag) {
                        style = "full-day-pmSchedule-log";
                    }else if (fullOwnFlag && amSheduleOwnFlag && !fullSheduleOwnFlag) {
                        style = "full-day-amSchedule-log";
                    }else if (fullOwnFlag && fullSheduleOwnFlag) {
                        style = "full-day-schedule-log";
                    }else if (amOwnFlag && pmOwnFlag && fullSheduleOwnFlag) {
                        style = "full-day-schedule-log";
                    }else if (amOwnFlag && !pmOwnFlag && fullSheduleOwnFlag) {
                        style = "full-day-schedule-log-am";
                    }else if (!amOwnFlag && pmOwnFlag && fullSheduleOwnFlag) {
                        style = "full-day-schedule-log-pm";
                    }else if (amOwnFlag && pmOwnFlag && pmSheduleOwnFlag) {
                        style = "full-day-pmSchedule-log";
                    }else if (amOwnFlag && !pmOwnFlag && pmSheduleOwnFlag) {
                        style = "full-day-pmSchedule-log-am";
                    }else if (!amOwnFlag && pmOwnFlag && pmSheduleOwnFlag) {
                        style = "full-day-pmSchedule-log-pm";
                    }else if (amOwnFlag && pmOwnFlag && amSheduleOwnFlag) {
                        style = "full-day-amSchedule-log";
                    }else if (amOwnFlag && !pmOwnFlag && amSheduleOwnFlag) {
                        style = "full-day-amSchedule-log-am";
                    }else if (!amOwnFlag && pmOwnFlag && amSheduleOwnFlag) {
                        style = "full-day-amSchedule-log-pm";
                    }else if (amOtherFlag && pmOtherFlag && fullSheduleOwnFlag) {
                        style = "full-day-other";
                    }else if (amOtherFlag && !pmOtherFlag && fullSheduleOwnFlag && !pmOwnFlag) {
                        style = "full-day-schedule-other-am";
                    }else if (amOtherFlag && !pmOtherFlag && fullSheduleOwnFlag && pmOwnFlag) {
                        style = "full-day-schedule-other-am-pm-own";
                    }else if (!amOtherFlag && pmOtherFlag && fullSheduleOwnFlag && !amOwnFlag) {
                        style = "full-day-schedule-other-pm";
                    }else if (!amOtherFlag && pmOtherFlag && fullSheduleOwnFlag && amOwnFlag) {
                        style = "full-day-schedule-other-pm-am-own";
                    }else if (amOtherFlag && pmOtherFlag && pmSheduleOwnFlag) {
                        style = "full-day-other";
                    }else if (amOtherFlag && !pmOtherFlag && pmSheduleOwnFlag && !pmOwnFlag) {
                        style = "full-day-pmSchedule-other-am";
                    }else if (amOtherFlag && !pmOtherFlag && pmSheduleOwnFlag && pmOwnFlag) {
                        style = "full-day-pmSchedule-other-am-pm-own";
                    }else if (!amOtherFlag && pmOtherFlag && pmSheduleOwnFlag && !amOwnFlag) {
                        style = "pm-half-day-other";
                    }else if (!amOtherFlag && pmOtherFlag && pmSheduleOwnFlag && amOwnFlag) {
                        style = "pm-half-day-other-am-own";
                    }else if (amOtherFlag && pmOtherFlag && amSheduleOwnFlag) {
                        style = "full-day-other";
                    }else if (amOtherFlag && !pmOtherFlag && amSheduleOwnFlag && !pmOwnFlag) {
                        style = "am-half-day-other";
                    }else if (amOtherFlag && !pmOtherFlag && amSheduleOwnFlag && pmOwnFlag) {
                        style = "am-half-day-other-pm-own";
                    }else if (!amOtherFlag && pmOtherFlag && amSheduleOwnFlag && !amOwnFlag) {
                        style = "full-day-amSchedule-other-pm";
                    }else if (!amOtherFlag && pmOtherFlag && amSheduleOwnFlag && amOwnFlag) {
                        style = "full-day-amSchedule-other-pm-am-own";
                    }else if (fullOtherFlag) {
                        style = "full-day-other";
                    }
                    //add week weeken and holiday condition
                    disabilityOnAction = false;
                    if(!calendarFlags.enableWeekDays && date.getDay()>0 && date.getDay() < 6){
                        for (var k = 0; k < calendarData.holidays.length; k++) {
                            var dateParts = calendarData.holidays[k].split("/"); // Current format MM/DD/YYYY
                            var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                            var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                                selectedDateObj.getDate() + '/' +
                                selectedDateObj.getFullYear();
                            if (calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                                disabilityOnAction =false;
                                break;
                            }else{
                                disabilityOnAction =true;
                            }
                        }
                    }else if(!calendarFlags.enableWeekEnds && (date.getDay()==0 || date.getDay() == 6)){
                        for (var l = 0; l < calendarData.holidays.length; l++) {
                            var dateParts = calendarData.holidays[l].split("/"); // Current format MM/DD/YYYY
                            var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                            var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                                selectedDateObj.getDate() + '/' +
                                selectedDateObj.getFullYear();
                            if (calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                                disabilityOnAction =false;
                                break;
                            }else{
                                disabilityOnAction =true;
                            }
                        }
                    }

                    if(!calendarFlags.enableHolidays){
                        for (var h = 0; h < calendarData.holidays.length; h++) {
                            var dateParts = calendarData.holidays[h].split("/"); // Current format MM/DD/YYYY
                            var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                            var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                                selectedDateObj.getDate() + '/' +
                                selectedDateObj.getFullYear();
                            if (!calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                                disabilityOnAction =true;
                                break;
                            }
                        }
                    }

                    if(calendarFlags.enableOnCallBurden) { /*add condition to check burden for on call activity*/
                        //if($("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In'){
                        if ($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In') {
                            disabilityOnAction = true;
                        }
                    }


                    /**
                     * add style and enable flag
                     */
                    switch (style) {
                        case "am-half-day":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "am-half-day"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "am-half-day"];
                            }
                        case "pm-half-day":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "pm-half-day"];
                            }
                            else {
                                return [calendarFlags.enablePMShifts, "pm-half-day"];
                            }
                        case "full-day":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day"];
                            }
                            else {
                                return [false, "full-day"];
                            }
                        case "am-half-day-other":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "am-half-day-other"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "am-half-day-other"];
                            }
                        case "pm-half-day-other":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "pm-half-day-other"];
                            }
                            else {
                                return [calendarFlags.enablePMShifts, "pm-half-day-other"];
                            }
                        case "full-day-other":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-other"];
                            }
                            else {
                                return [false, "full-day-other"];
                            }
                        case "am-half-day-other-pm-own":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "am-half-day-other-pm-own"];
                            }
                            else {
                                return [false, "am-half-day-other-pm-own"];
                            }
                        case "pm-half-day-other-am-own":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "pm-half-day-other-am-own"];
                            }
                            else {
                                return [false, "pm-half-day-other-am-own"];
                            }
                        case "am-half-day-schedule":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "am-half-day-schedule"];
                            }
                            else {
                                return [true, "am-half-day-schedule"];
                            }
                        case "pm-half-day-schedule":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "pm-half-day-schedule"];
                            }
                            else {
                                return [true, "pm-half-day-schedule"];
                            }
                        case "full-day-schedule":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule"];
                            }
                            else {
                                return [true, "full-day-schedule"];
                            }
                        case "fullShedule_pm-selected":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "fullShedule_pm-selected"];
                            }
                            else {
                                return [true, "fullShedule_pm-selected"];
                            }
                        case "fullShedule_am-selected":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "fullShedule_am-selected"];
                            }
                            else {
                                return [true, "fullShedule_am-selected"];
                            }
                        case "pmShedule_pm-selected":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "pmShedule_pm-selected"];
                            }
                            else {
                                return [true, "pmShedule_pm-selected"];
                            }
                        case "pmShedule_am-selected":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "pmShedule_am-selected"];
                            }
                            else {
                                return [true, "pmShedule_am-selected"];
                            }
                        case "amShedule_pm-selected":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "amShedule_pm-selected"];
                            }
                            else {
                                return [true, "amShedule_pm-selected"];
                            }
                        case "amShedule_am-selected":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "amShedule_am-selected"];
                            }
                            else {
                                return [true, "amShedule_am-selected"];
                            }
                        case "full-day-schedule-log":
                            if (approve >= 0) {
                                return [false, "full-day-schedule-log"];
                            }
                            else if (disabilityOnAction) {
                                //if (($("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In')) {
                                if (($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In' || !calendarFlags.enableOnCallBurden)) {
                                    return [true, "full-day-schedule-log"];
                                }
                                else {
                                    return [false, "full-day-schedule-log"];
                                }
                            }
                            else {
                                return [false, "full-day-schedule-log"];
                            }
                        case "full-day-schedule-log-am":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule-log-am"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "full-day-schedule-log-am"];
                            }
                        case "full-day-schedule-log-pm":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule-log-pm"];
                            }
                            else {
                                return [calendarFlags.enablePMShifts, "full-day-schedule-log-pm"];
                            }
                        case "full-day-pmSchedule-log":
                            if (approve >= 0) {
                                return [false, "full-day-pmSchedule-log"];
                            }
                            else if (disabilityOnAction) {
                                //if (($("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In')) {
                                if (($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In' || !calendarFlags.enableOnCallBurden)) {
                                    return [true, "full-day-pmSchedule-log"];
                                }
                                else {
                                    return [false, "full-day-pmSchedule-log"];
                                }
                            }
                            else {
                                return [false, "full-day-pmSchedule-log"];
                            }
                        case "full-day-pmSchedule-log-am":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-pmSchedule-log-am"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "full-day-pmSchedule-log-am"];
                            }
                        case "full-day-pmSchedule-log-pm":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-pmSchedule-log-pm"];
                            }
                            else {
                                return [calendarFlags.enablePMShifts, "full-day-pmSchedule-log-pm"];
                            }
                        case "full-day-amSchedule-log":
                            if (approve >= 0) {
                                return [false, "full-day-amSchedule-log"];
                            }
                            else if (disabilityOnAction) {
                                //if (($("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In')) {
                                if (($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In' || !calendarFlags.enableOnCallBurden)) {
                                    return [true, "full-day-amSchedule-log"];
                                }
                                else {
                                    return [false, "full-day-amSchedule-log"];
                                }
                            }
                            else {
                                return [false, "full-day-amSchedule-log"];
                            }
                        case "full-day-amSchedule-log-am":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-amSchedule-log-am"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "full-day-amSchedule-log-am"];
                            }
                        case "full-day-amSchedule-log-pm":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-amSchedule-log-pm"];
                            }
                            else {
                                return [calendarFlags.enablePMShifts, "full-day-amSchedule-log-pm"];
                            }
                        case "full-day-schedule-other-am":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule-other-am"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "full-day-schedule-other-am"];
                            }
                        case "full-day-schedule-other-am-pm-own":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule-other-am-pm-own"];
                            }
                            else {
                                return [false, "full-day-schedule-other-am-pm-own"];
                            }
                        case "full-day-schedule-other-pm":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule-other-pm"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "full-day-schedule-other-pm"];
                            }
                        case "full-day-schedule-other-pm-am-own":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-schedule-other-pm-am-own"];
                            }
                            else {
                                return [false, "full-day-schedule-other-pm-am-own"];
                            }
                        case "full-day-pmSchedule-other-am":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-pmSchedule-other-am"];
                            }
                            else {
                                return [calendarFlags.enablePMShifts, "full-day-pmSchedule-other-am"];
                            }
                        case "full-day-pmSchedule-other-am-pm-own":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-pmSchedule-other-am-pm-own"];
                            }
                            else {
                                return [false, "full-day-pmSchedule-other-am-pm-own"];
                            }
                        case "full-day-amSchedule-other-pm":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-amSchedule-other-pm"];
                            }
                            else {
                                return [calendarFlags.enableAMShifts, "full-day-amSchedule-other-pm"];
                            }
                        case "full-day-amSchedule-other-pm-am-own":
                            if (approve >= 0 || disabilityOnAction) {
                                return [false, "full-day-amSchedule-other-pm-am-own"];
                            }
                            else {
                                return [false, "full-day-amSchedule-other-pm-am-own"];
                            }
                        case "on_callfull-day":
                            //if(!(approve >= 0) && ($("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In')) {
                            if(!(approve >= 0) && ($("#action option:selected").attr('data-action-name') == 'Called-Back'|| $("#action option:selected").attr('data-action-name') == 'Called-In' || !calendarFlags.enableOnCallBurden)) {
                                return [true, "full-day"];
                            }
                            //else if (approve >= 0 || $("#action option:selected").text() == 'On-Call'){
                            else if (approve >= 0 || $("#action option:selected").attr('data-action-name') == 'On-Call'){
                                return [false, "full-day"];
                            }
                            else {
                                return [true, "full-day"];
                            }
                        default:

                            /**
                             * [0]: true/false indicating whether or not this date is selectable
                             * as isDisabled contains reverse data for above line,
                             * data is negated using not (!) operator
                             */

                            if (approve >= 0 || disabilityOnAction) {
                                return [false, ""];
                            }
                            else if (calendarFlags.enableAMShifts) { //radio button with value 'PM' selected
                                return [!calendarData.isDisabled, "pm-selected"];
                            } else if (calendarFlags.enablePMShifts) { //radio button with value 'AM' selected
                                return [!calendarData.isDisabled, "am-selected"];
                            } else {
                                return [!calendarData.isDisabled, ""];
                            }
                    }
                }
            });
        }else {
            //if ($("#action option:selected").text() == 'On-Call' || $("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In') {
            if ($("#action option:selected").attr('data-action-name') == 'On-Call' || $("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In') {
                $('#select_date').multiDatesPicker({
                    inline: true,
                    beforeShowDay: function (date) {
                        //if ($("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In') {
                        if(calendarFlags.enableOnCallBurden) { /*add condition to check burden for on call activity*/
                            if ($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In') {
                                return [false, ""];
                            } else {
                                return [true, ""];
                            }
                        }else {
                            return [true, ""];
                        }
                    }
                });
            }else{
                $('#select_date').multiDatesPicker({
                    inline: true,
                    beforeShowDay: function (date) {
                        var DateMDY = (date.getMonth() + 1) + '/' +
                            date.getDate() + '/' +
                            date.getFullYear();
                        if (!calendarFlags.enableWeekDays && date.getDay() > 0 && date.getDay() < 6) {
                            for (var k = 0; k < calendarData.holidays.length; k++) {
                                var dateParts = calendarData.holidays[k].split("/"); // Current format MM/DD/YYYY
                                var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                                var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                                    selectedDateObj.getDate() + '/' +
                                    selectedDateObj.getFullYear();
                                if (calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                                    return [true, ""];
                                } else {
                                    return [false, ""];
                                }
                            }
                        } else if (!calendarFlags.enableWeekEnds && (date.getDay() == 0 || date.getDay() == 6)) {
                            for (var l = 0; l < calendarData.holidays.length; l++) {
                                var dateParts = calendarData.holidays[l].split("/"); // Current format MM/DD/YYYY
                                var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                                var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                                    selectedDateObj.getDate() + '/' +
                                    selectedDateObj.getFullYear();
                                if (calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                                    return [true, ""];
                                } else {
                                    return [false, ""];
                                }
                            }
                        }

                        if (!calendarFlags.enableHolidays) {
                            for (var h = 0; h < calendarData.holidays.length; h++) {
                                var dateParts = calendarData.holidays[h].split("/"); // Current format MM/DD/YYYY
                                var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                                var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                                    selectedDateObj.getDate() + '/' +
                                    selectedDateObj.getFullYear();
                                if (!calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                                    return [true, ""];
                                }
                            }
                        }
                        return [true, ""];
                    }
                });
            }
        }
    } else {
        if(dates.length>0){
            $('#select_date').multiDatesPicker('addDates',dates,'picked');
            $('#select_date_time_study').multiDatesPicker('addDates',dates,'picked');
        }
        $('#select_date, #select_date_time_study').multiDatesPicker({
            inline: true,
            onSelect: function (dateText, inst) {
                var day =inst.currentDay;
                var month =inst.currentMonth;
                var year =inst.currentYear;
                var dateYear = (month+ 1) + '-' + year;
                // var approve = $.inArray(dateYear, approved_logs_months);

                // var inputDate = (month+ 1) + '/' + day + '/' + year;
                var inputDate = year + '-' + ("0" + (month + 1)).slice(-2) + '-' + ("0" + day).slice(-2);

                for (var rangeIndex in approved_logs_months) {
                    if (approved_logs_months.hasOwnProperty(rangeIndex)) {
                        var rangeObj = approved_logs_months[rangeIndex];
                        for (var prop in rangeObj) {
                            // skip loop if the property is from prototype
                            if (!rangeObj.hasOwnProperty(prop)) continue;

                            var rangeStartDate = new Date(rangeObj["start_date"]);
                            var rangeEndDate = new Date(rangeObj["end_date"]);
                            var inputDate = new Date(inputDate);
                            if(inputDate >= rangeStartDate && inputDate <= rangeEndDate){
                                var approve = true;
                                console.log("rangeStart", rangeObj["start_date"]);
                                console.log("rangeEnd", rangeObj["end_date"]);
                                var rangeString = rangeObj["start_date"] + ' - ' + rangeObj["end_date"];
                            }
                            
                        }
                    }
                }

                if (approve >= 0){
                    $('#submitLog').addClass("disabled");
                    $('#enterLogMessage').html("Can not select date from the range of approved logs. ("  + rangeString + ")");
                    $('#enterLogMessage').removeClass("alert-success");
                    $('#enterLogMessage').addClass("alert-danger");
                    $('#select_date').multiDatesPicker('resetDates', 'picked');
                    $('#select_date_time_study').multiDatesPicker('resetDates', 'picked');
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
                    $('#select_date_time_study').multiDatesPicker('resetDates', 'picked');
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
                    $('#select_date_time_study').multiDatesPicker('resetDates', 'picked');
                    this.multiDatesPicker.dates.picked[0]= dateText;
                }
            }
        });
    }
    if(selected_date != undefined){
        $('#select_date').datepicker('setDate', selected_date);
    }
    $(".overlay").hide();
}

function updateFieldsForAction() {
    var action = $('#action').val();
    $('input:radio[name=shift]').attr('checked', false);
    var lastSelected = $('#select_date').multiDatesPicker('getDates');
    $('#select_date').multiDatesPicker('resetDates', 'picked');
    clearLogMessage();

    var contractId = $('#physician_id').val();
    var physicianId = $('#physician_id').val();

    if($('#payment_type_' + physicianId).val() == 7){
        var lastSelected = $('#select_date_time_study').multiDatesPicker('getDates');
        $('#select_date_time_study').multiDatesPicker('resetDates', 'picked');
    }

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
        // updateCalendar(lastSelected);
    }else {
        $('#custom_action').hide();
        for (var i = 0; i < actionsForCurrentPhysician.length; i++) {
            if (actionsForCurrentPhysician[i].id == action) {
                //if (actionsForCurrentPhysician[i].duration == 0.5 && $('#contract_type_' + contractId).val() == 4) {
                if (actionsForCurrentPhysician[i].duration == 0.5 && $('#payment_type_' + contractId).val() == 3) {
                    $('#divShift input:radio[name=shift]').attr('disabled', false);
                    isSelectedActionHalfDay = true;
                    calendarData.isDisabled = true;
                    // updateCalendar(lastSelected);
                } else {
                    $('#divShift input:radio[name=shift]').attr('disabled', true);
                    isSelectedActionHalfDay = false;
                    calendarFlags.enableAMShifts = false;
                    calendarFlags.enablePMShifts = false;
                    calendarData.isDisabled = false;
                    // updateCalendar(lastSelected);
                }
                break;
            }
        }
    }


   // var text =$("#action option:selected").text().split("-");
   if($('#payment_type_' + physicianId).val() == 7){
    var text = ["",""];
   }else{
    var text =$("#action option:selected").attr('data-action-name').split("-");
   }
    
    //if(text[0].replace(/\s/g,'') == 'Holiday' && $('#contract_type_' + contractId).val() == 4){
    if(text[0].replace(/\s/g,'') == 'Holiday' && $('#payment_type_' + contractId).val() == 3){
        calendarFlags.enableHolidays = true;
        calendarFlags.enableWeekDays = false;
        calendarFlags.enableWeekEnds = false;
        // updateCalendar(lastSelected);
    //}else if(text[0].replace(/\s/g,'') == 'Weekday' && $('#contract_type_' + contractId).val() == 4){
    }else if(text[0].replace(/\s/g,'') == 'Weekday' && $('#payment_type_' + contractId).val() == 3){
        calendarFlags.enableHolidays = false;
        calendarFlags.enableWeekDays = true;
        calendarFlags.enableWeekEnds = false;
        // updateCalendar(lastSelected);
    //}else if(text[0].replace(/\s/g,'') == 'Weekend' && $('#contract_type_' + contractId).val() == 4){
    }else if(text[0].replace(/\s/g,'') == 'Weekend' && $('#payment_type_' + contractId).val() == 3){
        calendarFlags.enableHolidays = false;
        calendarFlags.enableWeekDays = false;
        calendarFlags.enableWeekEnds = true;
        // updateCalendar(lastSelected);
    //}else if(($("#action option:selected").text() == 'On-Call' || $("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In') && ($('#contract_type_' + contractId).val() == 4)){
    }else if(($("#action option:selected").attr('data-action-name') == 'On-Call' || $("#action option:selected").attr('data-action-name') == 'Called-Back'|| $("#action option:selected").attr('data-action-name') == 'Called-In') && ($('#payment_type_' + contractId).val() == 3)){
        /* Added for new on call rates*/
        calendarFlags.enableHolidays = true;
        calendarFlags.enableWeekDays = true;
        calendarFlags.enableWeekEnds = true;
        $('#divShift').hide();
        // updateCalendar(lastSelected);
    }
    updateCalendar(lastSelected);
    $(".overlay").hide();
}

function getTimeStampEntries(){
    var selected_action_id = $('#action').val();
    var time_stamp_entry = $('#' + selected_action_id).attr('time_stamp_entry');
    if(time_stamp_entry == undefined){
        time_stamp_entry = false;
    }
    time_stamp_entry_flag = JSON.parse(time_stamp_entry);

    if(time_stamp_entry_flag){
        $('.time_stamp').show();
        $('.not_time_stamp').hide();
    }else{
        $('.not_time_stamp').show();
        $('.time_stamp').hide();
    }
    
    $('#enterLogMessage').html("");
    $('#enterLogMessage').removeClass("alert-danger");
}

function getRecentLogs(physicianId, contractId)
{
    resetPageData();
    var contractId = $('#contract_name').val() == 0 ? contractId : $('#contract_name').val();
    var physicianId = $('#physician_id').val() == 0 ? physicianId : $('#physician_id').val();

    $.ajax({
        'type':'GET',
        'url': '/getRecentLogs/'+physicianId+'/'+contractId,
        success:function(data) {
            var consolidatedRecentLogs = [];
            if (data.recent_logs_count > 0) {
                $('#recentLogs').html(data.recent_logs_view);
                for (var i = 0; i < data.recent_logs.length; i++) {
                    data.recent_logs[i].isOwner = true;
                    consolidatedRecentLogs.push(data.recent_logs[i]);
                }
            } else {
                $('#recentLogs').html("There are no recent logs.");
            }

            for (var i = 0; i < contractsDataForOtherPhysicians.length; i++) {
                if (!jQuery.isEmptyObject(contractsDataForOtherPhysicians[i])) {
                    for (var j = 0; j < contractsDataForOtherPhysicians[i].recent_logs.length; j++) {
                        consolidatedRecentLogs.push(contractsDataForOtherPhysicians[i].recent_logs[j]);
                    }
                }
            }

            calendarData.dates = consolidatedRecentLogs;
            setTimeout(function () {
                $('#select_date').multiDatesPicker({
                    minDate: contract_period_data.min_date,
                    maxDate: contract_period_data.max_date
                });
                updateFieldsForAction();
            }, 500);

            if (!jQuery.isEmptyObject(data) && !jQuery.isEmptyObject(data.statistics)) {
                $('#action').show();
                $('#lbl_action_duty').hide();
                $('#div_duration').show();
                $('#med_direct').text('Contract Hours');
                $('#summary_of_logged').html('Summary Of Hours Logged');
                //if($('#contract_type_' + physicianId).val() == 4){
                if($('#payment_type_' + physicianId).val() == 3){
                    $('.onCallLogEntryPanel').css("height","594px");
                    $('.onCallLogEntryPanel').css("max-height","594px");
                    $('#current_days_on_call').show();
                    $('.calendarColorList').show();
                    // $('#current_days_on_call span').html(0.50 * data.schedules.length);
                    $('#divShift').show();
                    $('.co_mgmt_med_direct').hide();
                    $('.time_stamp').hide();
                    $('.start_end_time_error_message').hide();
                    $('.per_unit_duration').hide();
                }else{
                    $('.onCallLogEntryPanel').css("height","834px");
                    $('.onCallLogEntryPanel').css("max-height","834px");
                    $('#current_days_on_call').hide();
                    $('.calendarColorList').hide();
                    $('#divShift').hide();
                    $('.co_mgmt_med_direct').show();
                    $('.time_stamp').show();
                    $('.start_end_time_error_message').show();
                    $('.per_unit_duration').hide();
                    //if($('#contract_type_' + physicianId).val() == 2){
                    if($('#payment_type_' + physicianId).val() == 2){
                        $('.co_mgmt').hide();
                        $('.med_direct').show();
                        $('#contract_min_hours').html(data.statistics.min_hours);
                        $('#contract_max_hours').html(data.statistics.max_hours);
                        $('#contract_annual_max_hours').html(data.statistics.annual_cap);
                        $('#contract_worked_hours').html(data.statistics.worked_hours);
                        $('#contract_potential_remaining_hours').html(data.statistics.remaining_hours);
                        $('#contract_prior_worked_hours').html(data.priorMonthStatistics.prior_worked_hours);
                    //}else if($('#contract_type_' + physicianId).val() == 1){
                    }else if($('#payment_type_' + physicianId).val() == 1 || $('#payment_type_' + physicianId).val() == 7){
                        $('.co_mgmt').show();
                        $('.med_direct').hide();
                        $('#contract_expected_total').html(data.statistics.expected_hours);
                        $('#contract_expected').html(data.priorMonthStatistics.expected_hours);
                        $('#contract_worked_total').html(data.statistics.total_hours);
                        $('#contract_worked_hours').html(data.priorMonthStatistics.worked_hours);
                        $('#contract_remaining_hours').html(data.priorMonthStatistics.remaining_hours);
                        $('#contract_prior_worked_hours').html(data.priorMonthStatistics.prior_worked_hours);
                    }
                    //Chaitraly::Added to show hours details on physician dashboard for monthly Stipend payment type
                    else if($('#payment_type_' + physicianId).val() == 6){
                        $('.co_mgmt').show();
                        $('.med_direct').hide();
                        $('#contract_expected_total').html(data.statistics.expected_hours);
                        $('#contract_expected').html(data.priorMonthStatistics.expected_hours);
                        $('#contract_worked_total').html(data.statistics.total_hours);
                        $('#contract_worked_hours').html(data.priorMonthStatistics.worked_hours);
                        $('#contract_remaining_hours').html(data.priorMonthStatistics.remaining_hours);
                        $('#contract_prior_worked_hours').html(data.priorMonthStatistics.prior_worked_hours);
                    }else if($('#payment_type_' + physicianId).val() == 8){
                        $('.co_mgmt').hide();
                        $('.med_direct').show();
                        $('#med_direct').html('Contract Units');
                        $('#summary_of_logged').html('Summary Of Units Logged');
                        $('#contract_min_hours').html(data.statistics.min_hours);
                        $('#contract_max_hours').html(data.statistics.max_hours);
                        $('#contract_annual_max_hours').html(data.statistics.annual_cap);
                        $('#contract_worked_hours').html(data.statistics.worked_hours);
                        $('#contract_potential_remaining_hours').html(data.statistics.remaining_hours);
                        $('#contract_prior_worked_hours').html(data.priorMonthStatistics.prior_worked_hours);
                        $('.per_unit_duration').show();
                        $('#div_duration').hide();
                        $('.time_stamp').hide();
                        $('.start_end_time_error_message').hide();
                    }else if($('#payment_type_' + physicianId).val() == 9){
                        $('.co_mgmt').show();
                        $('.med_direct').hide();
                        $('.co_mgmt_med_direct').hide();
                        $('#contract_expected_total').html(data.statistics.expected_hours);
                        $('#contract_expected').html(data.priorMonthStatistics.expected_hours);
                        $('#contract_worked_total').html(data.statistics.total_hours);
                        $('#contract_worked_hours').html(data.priorMonthStatistics.worked_hours);
                        $('#contract_remaining_hours').html(data.priorMonthStatistics.remaining_hours);
                        $('#contract_prior_worked_hours').html(data.priorMonthStatistics.prior_worked_hours);
                        if(data.actions[0].time_stamp_entry){
                            $('#div_duration').hide();
                            $('.time_stamp').show();
                        } else {
                            $('#div_duration').show();
                            $('.time_stamp').hide();
                        }
                    }
                }
            }
            if (data.payment_type_id == 1 || data.payment_type_id == 2 || data.payment_type_id == 6) {
                getTimeStampEntries();
            }
        }
    });
}

function renderCalendar (){
    setTimeout(function () {
        if ($('#payment_type_' + selectedGlobalContractId).val() == 3 || $('#payment_type_' + selectedGlobalContractId).val() == 5) {
            $('#select_date').datepicker('option',{
                maxPicks: 90,
                minDate: contract_period_data.min_date,
                maxDate: contract_period_data.max_date
            });
        } else {
            maxDateFor = contract_period_data.max_date;
            $('#select_date, #select_date_time_study').datepicker('option',{
                maxPicks: 2,
                minDate: contract_period_data.min_date,
                maxDate: contract_period_data.max_date
            });

        }

        updateFieldsForAction();
    }, 500);
    $(".overlay").hide();
}

function combinedCallGetContractsRecentLogs(physicianId){
    var time_stamp_entry_flag = false;

    $(".overlay").show();
    var physicianId = physicianId;
    contractsDataForOtherPhysicians = [];

    var contractId;

    for (var i = 0; i < physicians.length; i++) {
        if (physicianId == physicians[i].id) {
            contractId = physicians[i].contract;
        }
    }

    $('#contract_name').val(contractId);

    $.when(
        $.ajax(basePath + "/getContracts",
            {
                dataType: 'json',
                type: "POST",
                data: {
                    physicianId: physicianId,
                    contractId: contractId
                }
            })
        ,
        $.ajax(basePath + "/getRecentLogs/"+physicianId+'/'+contractId,
            {
                type: "GET"
            }
        )
    ).done(function(getContractsResponse, getRecentLogsResponse){
        getContractsResponse = getContractsResponse[0];
        getRecentLogsResponse = getRecentLogsResponse[0];

        $('#action').empty();

        selectedGlobalContractId = contractId;

        // getContractsResponse starts here.
        if (!jQuery.isEmptyObject(getContractsResponse) && !jQuery.isEmptyObject(getContractsResponse.statistics)) {
            contract_period_data = getContractsResponse.contract_period_data;
            $('#log_entry_deadline').val(getContractsResponse.log_entry_deadline);
            calendarFlags.enableOnCallBurden = getContractsResponse.burden_of_call;

            /**
             * key - 'physiciansContractData' contains
             * 'recent logs' for the physicians in 'current agreement'
             *
             * we need to exclude current physician from these logs
             * because, current physician data is already available as data object
             */

            var contractsDataForOtherPhysiciansLength = 0;
            if (!jQuery.isEmptyObject(getContractsResponse.physiciansContractData)) {
                var physiciansContractData = getContractsResponse.physiciansContractData;
                for (var i = 0; i < physiciansContractData.length; i++) {

                    //skipping physician if data already exist as root object

                    if ( (physiciansContractData[i].id != getContractsResponse.id && physiciansContractData[i] != undefined) ||
                        (physiciansContractData[i].id == getContractsResponse.id && physiciansContractData[i].contract_physician_id != physicianId) ) {
                        contractsDataForOtherPhysiciansLength = contractsDataForOtherPhysicians.length;
                        contractsDataForOtherPhysicians[contractsDataForOtherPhysiciansLength] = physiciansContractData[i];
                    }
                }
            }

            var consolidatedRecentLogs = [];

            var consolidatedSchedule = [];
            var schedule_count=0.00;
            for (var i = 0; i < getContractsResponse.schedules.length; i++) {
                getContractsResponse.schedules[i].isOwner = true;
                consolidatedSchedule.push(getContractsResponse.schedules[i]);
                var dateParts = getContractsResponse.schedules[i].date.split("/");
                var current_date = new Date();
                var current_month = current_date.getMonth()+1;
                if(dateParts[0] == current_month){
                    schedule_count = schedule_count+0.50;
                }
            }
            $('#current_days_on_call span').html(schedule_count);

            $('#action')
                .find('option')
                .remove()
                .end();

            /*
            - added On : 2018/12/20
            - check if custom_action_enabled flag is 1 or 0
            - if 1 - include custom action input else do not include */

            // actions
            $('#timestudyactions').empty();
            if (getContractsResponse.actions.length > 0) {

                physicianActions = getContractsResponse.actions;

                if(getContractsResponse.payment_type_id == 7){
                    $.each(getContractsResponse.categories, function () {
                        var category_id = this.category_id;
                        $('#timestudyactions').append('<h5 style="font-weight: bold; word-wrap: break-word;">' + this.category_name + '</h5>');
                        $.each(getContractsResponse.actions, function () {
                            if(this.category_id == category_id){
                                $('#timestudyactions').append('<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9" style="padding: 0px 0px 0px 4px; word-wrap: break-word;"><span>' + this.display_name + '</span></div><div class="col-lg-3 col-md-3 col-sm-3 col-xs-3"><input id="'+ this.id +'" name="actions" onkeypress="timeStudyValidation(event, this)" class="form-control" type="text" maxlength="5" autocomplete="off" placeholder="Hours"></div>');
                                // $('#timestudyactions').append('<div style="border-top: 2px #d6d6d6 solid; margin-top: 50px; margin-bottom: 7px; width: 100%; margin-left: 0%;"></div>');
                                $('#timestudyactions').append('<hr style="background-color: darkgrey; height: 1px; width: 99%; margin-right: 1%; margin-bottom: 7px; margin-top: 51px;"></hr>');
                            }
                        });
                    });
                }else if(getContractsResponse.payment_type_id == 8){
                    $.each(getContractsResponse.actions, function () {
                        $('#action').hide();
                        $('#lbl_action_duty').show();
                        $('#lbl_action_duty').html(this.name);
                        $('#action').append($("<option></option>")
                            .attr("value", this.id)
                            .attr("data-action-name", this.name)
                            .attr("id", this.id)
                            .attr("override_mandate", this.override_mandate)
                            .attr("time_stamp_entry", this.time_stamp_entry)
                            .text(this.name));
                    });
                }else{
                    $.each(getContractsResponse.actions, function () {
                        //if (data.contract_type_id != 4) {
                        if (getContractsResponse.payment_type_id != 3) {
                            if(getContractsResponse.payment_type_id == 1 || getContractsResponse.payment_type_id == 2 || getContractsResponse.payment_type_id == 6){
                                if(getContractsResponse.mandate_details && !this.override_mandate){
                                    $('#action').append($("<option style='font-weight:bold;'></option>")
                                        .attr("value", this.id)
                                        .attr("data-action-name", this.name)
                                        .attr("id", this.id)
                                        .attr("override_mandate", this.override_mandate)
                                        .attr("time_stamp_entry", this.time_stamp_entry)
                                        .text(this.name));
                                }else{
                                    $('#action').append($("<option></option>")
                                        .attr("value", this.id)
                                        .attr("data-action-name", this.name)
                                        .attr("id", this.id)
                                        .attr("override_mandate", this.override_mandate)
                                        .attr("time_stamp_entry", this.time_stamp_entry)
                                        .text(this.name));
                                }
                            }else{
                                $('#action').append($("<option></option>")
                                    .attr("value", this.id)
                                    .attr("data-action-name", this.name)
                                    .attr("id", this.id)
                                    .attr("override_mandate", this.override_mandate)
                                    .attr("time_stamp_entry", this.time_stamp_entry)
                                    .text(this.name));
                            }
                        }else{
                            $('#action').append($("<option></option>")
                                .attr("value", this.id)
                                .attr("data-action-name", this.name)
                                .attr("id", this.id)
                                .attr("override_mandate", this.override_mandate)
                                .attr("time_stamp_entry", this.time_stamp_entry)
                                .text(this.display_name));
                        }
                    });
                }

                if(getContractsResponse.custom_action_enabled == 1) {
                    //if ($('#contract_type_' + contractId).val() != 4) {
                    if ($('#payment_type_' + contractId).val() != 3 && $('#payment_type_' + contractId).val() != 7 && $('#payment_type_' + contractId).val() != 8) {
                        $('#custom_action').attr('disabled', false);
                        $('#action').css("margin-bottom", "10px");
                        if(getContractsResponse.payment_type_id == 1 || getContractsResponse.payment_type_id == 2 || getContractsResponse.payment_type_id == 6){
                            if(getContractsResponse.mandate_details){
                                $('#action').append($("<option style='font-weight:bold;'></option>></option>")
                                    .attr("value", "-1")
                                    .attr("data-action-name", "Custom Action")
                                    .text("Custom Action"));
                            }else{
                                $('#action').append($("<option></option>")
                                    .attr("value", "-1")
                                    .attr("data-action-name", "Custom Action")
                                    .text("Custom Action"));
                            }
                        }else{
                            $('#action').append($("<option></option>")
                                .attr("value", "-1")
                                .attr("data-action-name", "Custom Action")
                                .text("Custom Action"));
                        }
                    }
                }else{
                    //if($('#contract_type_' + contractId).val() != 4){
                    if($('#payment_type_' + contractId).val() != 3){
                        $('#action').css("margin-bottom","10px");
                    }
                }

                if($('#payment_type_' + physicianId).val() != 8){
                    $('input:radio[name=shift]').attr('checked', false);
                    $('#custom_action').hide();
                    $('#action').show();
                }

                if (getContractsResponse.actions[0].duration == 0.5) {
                    //$('#divShift').show();
                    $('#divShift input:radio[name=shift]').attr('disabled', false);
                    isSelectedActionHalfDay = true;
                    calendarData.isDisabled = true;
                } else {
                    //$('#divShift').hide();
                    $('#divShift input:radio[name=shift]').attr('disabled', true);
                    isSelectedActionHalfDay = false;
                    calendarData.isDisabled = false;
                }

            } else {
                physicianActions = [];
                //if($('#contract_type_' + contractId).val() == 4) {
                if($('#payment_type_' + contractId).val() == 3) {
                    $('#action').append('<option data-action-name ="" value="">No actions available.</option>');
                }else{
                    $('#custom_action').show();
                    $('#action').hide();
                    if(getContractsResponse.custom_action_enabled == 0){
                        $('#custom_action').attr('value','');
                        $('#custom_action').attr('disabled',true);
                    }else{
                        $('#action').append($("<option></option>")
                            .attr("value", "-1")
                            .attr("data-action-name", "Custom Action")
                            .text("Custom Action"));
                        $('#action').val(-1);
                    }
                }
                isSelectedActionHalfDay = false;
                calendarData.isDisabled = false;
                $('.time_stamp').hide();
                time_stamp_entry_flag = false;
            }

            // created common variable to loop through for getting duration for action
            actionsForCurrentPhysician = getContractsResponse.actions;
            //approved logs months for disabled selection
            approved_logs_months = getContractsResponse.approved_logs_months;

            // disable existing dates and update calendar
            $('#select_date').multiDatesPicker('resetDates', 'picked');
            $('#select_date_time_study').multiDatesPicker('resetDates', 'disabled');
            // calendarData.dates = consolidatedRecentLogs; //data.recent_logs;
            calendarData.scheduleDates = consolidatedSchedule; //data.schedule;
            calendarData.holidays = getContractsResponse.holidays; //data.holidays;
        } else {
            $('#action').append('<option data-action-name ="" value="">No actions available.</option>');
            $('#recentLogs').html("There are no recent logs.");
            $(".overlay").hide();
        }

        if(getContractsResponse.payment_type_id == 7){
            $('.onCallLogEntryPanel').hide();
            // $('#timeStudyLogEntry').show();
            $('#error_log_message').html('');
            $('#error_log_message').append('<div id="enterLogMessage" class="alert" role="alert"></div>');
        }else{
            // $('#onCallLogEntryPanel').show();
            $('#error_log_message').html('');
            $('#timeStudyLogEntry').hide();
        }
        // Get Contracts response ends here.

        // getRecentLogsResponse starts here.
        var consolidatedRecentLogs = [];
        if (getRecentLogsResponse.recent_logs_count > 0) {
            $('#recentLogs').html(getRecentLogsResponse.recent_logs_view);
            for (var i = 0; i < getRecentLogsResponse.recent_logs.length; i++) {
                getRecentLogsResponse.recent_logs[i].isOwner = true;
                consolidatedRecentLogs.push(getRecentLogsResponse.recent_logs[i]);
            }
        } else {
            $('#recentLogs').html("There are no recent logs.");
        }

        for (var i = 0; i < contractsDataForOtherPhysicians.length; i++) {
            if (!jQuery.isEmptyObject(contractsDataForOtherPhysicians[i])) {
                for (var j = 0; j < contractsDataForOtherPhysicians[i].recent_logs.length; j++) {
                    consolidatedRecentLogs.push(contractsDataForOtherPhysicians[i].recent_logs[j]);
                }
            }
        }

        calendarData.dates = consolidatedRecentLogs;
        if (!jQuery.isEmptyObject(getRecentLogsResponse) && !jQuery.isEmptyObject(getRecentLogsResponse.statistics)) {
            $('#action').show();
            $('#lbl_action_duty').hide();
            $('#div_duration').show();
            $('#med_direct').text('Contract Hours');
            $('#summary_of_logged').html('Summary Of Hours Logged');
            //if($('#contract_type_' + physicianId).val() == 4){
            if($('#payment_type_' + physicianId).val() == 3){
                $('.onCallLogEntryPanel').css("height","594px");
                $('.onCallLogEntryPanel').css("max-height","594px");
                $('#current_days_on_call').show();
                $('.calendarColorList').show();
                // $('#current_days_on_call span').html(0.50 * data.schedules.length);
                $('#divShift').show();
                $('.co_mgmt_med_direct').hide();
                $('.time_stamp').hide();
                $('.start_end_time_error_message').hide();
                $('.per_unit_duration').hide();
            }else{
                $('.onCallLogEntryPanel').css("height","834px");
                $('.onCallLogEntryPanel').css("max-height","834px");
                $('#current_days_on_call').hide();
                $('.calendarColorList').hide();
                $('#divShift').hide();
                $('.co_mgmt_med_direct').show();
                $('.time_stamp').show();
                $('.start_end_time_error_message').show();
                $('.per_unit_duration').hide();
                //if($('#contract_type_' + physicianId).val() == 2){
                if($('#payment_type_' + physicianId).val() == 2){
                    $('.co_mgmt').hide();
                    $('.med_direct').show();
                    $('#contract_min_hours').html(getRecentLogsResponse.statistics.min_hours);
                    $('#contract_max_hours').html(getRecentLogsResponse.statistics.max_hours);
                    $('#contract_annual_max_hours').html(getRecentLogsResponse.statistics.annual_cap);
                    $('#contract_worked_hours').html(getRecentLogsResponse.statistics.worked_hours);
                    $('#contract_potential_remaining_hours').html(getRecentLogsResponse.statistics.remaining_hours);
                    $('#contract_prior_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.prior_worked_hours);
                    //}else if($('#contract_type_' + physicianId).val() == 1){
                }else if($('#payment_type_' + physicianId).val() == 1 || $('#payment_type_' + physicianId).val() == 7){
                    $('.co_mgmt').show();
                    $('.med_direct').hide();
                    $('#contract_expected_total').html(getRecentLogsResponse.statistics.expected_hours);
                    $('#contract_expected').html(getRecentLogsResponse.priorMonthStatistics.expected_hours);
                    $('#contract_worked_total').html(getRecentLogsResponse.statistics.total_hours);
                    $('#contract_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.worked_hours);
                    $('#contract_remaining_hours').html(getRecentLogsResponse.priorMonthStatistics.remaining_hours);
                    $('#contract_prior_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.prior_worked_hours);
                }
                //Chaitraly::Added to show hours details on physician dashboard for monthly Stipend payment type
                else if($('#payment_type_' + physicianId).val() == 6){
                    $('.co_mgmt').show();
                    $('.med_direct').hide();
                    $('#contract_expected_total').html(getRecentLogsResponse.statistics.expected_hours);
                    $('#contract_expected').html(getRecentLogsResponse.priorMonthStatistics.expected_hours);
                    $('#contract_worked_total').html(getRecentLogsResponse.statistics.total_hours);
                    $('#contract_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.worked_hours);
                    $('#contract_remaining_hours').html(getRecentLogsResponse.priorMonthStatistics.remaining_hours);
                    $('#contract_prior_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.prior_worked_hours);
                }else if($('#payment_type_' + physicianId).val() == 8){
                    $('.co_mgmt').hide();
                    $('.med_direct').show();
                    $('#med_direct').html('Contract Units');
                    $('#summary_of_logged').html('Summary Of Units Logged');
                    $('#contract_min_hours').html(getRecentLogsResponse.statistics.min_hours);
                    $('#contract_max_hours').html(getRecentLogsResponse.statistics.max_hours);
                    $('#contract_annual_max_hours').html(getRecentLogsResponse.statistics.annual_cap);
                    $('#contract_worked_hours').html(getRecentLogsResponse.statistics.worked_hours);
                    $('#contract_potential_remaining_hours').html(getRecentLogsResponse.statistics.remaining_hours);
                    $('#contract_prior_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.prior_worked_hours);
                    $('.per_unit_duration').show();
                    $('#div_duration').hide();
                    $('.time_stamp').hide();
                    $('.start_end_time_error_message').hide();
                }else if($('#payment_type_' + physicianId).val() == 9){
                    $('.co_mgmt').show();
                    $('.med_direct').hide();
                    $('.co_mgmt_med_direct').hide();
                    $('#contract_expected_total').html(getRecentLogsResponse.statistics.expected_hours);
                    $('#contract_expected').html(getRecentLogsResponse.priorMonthStatistics.expected_hours);
                    $('#contract_worked_total').html(getRecentLogsResponse.statistics.total_hours);
                    $('#contract_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.worked_hours);
                    $('#contract_remaining_hours').html(getRecentLogsResponse.priorMonthStatistics.remaining_hours);
                    $('#contract_prior_worked_hours').html(getRecentLogsResponse.priorMonthStatistics.prior_worked_hours);
                    if(getRecentLogsResponse.actions[0].time_stamp_entry){
                        $('#div_duration').hide();
                        $('.time_stamp').show();
                    } else {
                        $('#div_duration').show();
                        $('.time_stamp').hide();
                    }
                }
            }
        }
        if (getRecentLogsResponse.payment_type_id == 1 || getRecentLogsResponse.payment_type_id == 2 || getRecentLogsResponse.payment_type_id == 6) {
            getTimeStampEntries();
        }
        // getRecentLogsResponse ends here.
        renderCalendar();
    });

    if(time_stamp_entry_flag){
        $('.time_stamp').show();
        $('.not_time_stamp').hide();
    }else{
        $('.not_time_stamp').show();
        $('.time_stamp').hide();
    }

    // getPendingForApprovalLogs(0,0);
}
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
