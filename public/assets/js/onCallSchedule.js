/**
 * Created by Paritosh on 2/2/2016.
 */

var actionsForCurrentPhysician;
var basePath = "";
var physicians;
var isSelectedActionHalfDay = false;
var contractsDataForOtherPhysicians = [];
var physicianActions = [];
/**
 * Bellow vars are used for calendar functionality
 */

var calendarData = {
    dates: [],
    holidays: [],
    duration: 0,
    shift: "",
    isDisabled: true,
    total_duration_log_details: []
}

var calendarStyles = {}

var calendarFlags = {
    enableAMShifts: false,
    enablePMShifts: false,
    enableWeekDays: false,
    enableWeekEnds: false,
    enableHolidays: false,
    enableOnCallBurden: false,
    //call-coverage by 1254
    enablePartialHours: false,
    partial_hours_calculation:24
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

        // $("#" + log_id).remove();
        // $("#" + log_id).remove();
        // getContracts($('#physician_name').val());

                //issue fixing : on call log should not be delete before called-ack and called-in in case of burden_on_call true by 1254
            if(response != "SUCCESS"){

                $('#log-error-delete-message').html(response);
                $('#log-error-delete-message').removeClass("alert-success");
                $('#log-error-delete-message').addClass("alert-danger");
                $('#log-error-delete-message').show();

                $('html,body').animate({scrollTop: 0}, '3000');
            
                setTimeout(function () {
                    $('#log-error-delete-message').hide();
                }, 4000);
            } else {   // remove() and getcontracts added in else for issue fixing
                $("#" + log_id).remove();
                $("#" + log_id).remove();
                combinedCallGetContractsRecentLogs($('#physician_name').val());
            } //end issue fixing : on call log should not be delete before called-ack and called-in in case of burden_on_call true by 1254


    }).error(function (e) {
    });
}

function deleteModal(thisObject) {
    var deleteId = thisObject.attr("id");
    $('#modalDeleteLog').attr("onClick", "delete_log(" + deleteId + ")");
}

function initMultiDatesPicker() {
    //fetch physician id
    var physicianId = $('#physician_name').val();
    var contractId;
    //fetch contract id from physician id
    for (var i = 0; i < physicians.length; i++) {
        if (physicianId == physicians[i].id) {
            contractId = physicians[i].contract;
        }
    }
    /*set min days & max days on multidates picker as per agreement's start date & end date */
    $.post(basePath + "/getContractPeriod", {
        physicianId: physicianId,
        contractId: contractId
    }, function (contractPeriodData) {
        $('#select_date').multiDatesPicker({
            minDate: contractPeriodData.min_date,
            maxDate: contractPeriodData.max_date
        });
    });
}

function destroyMultiDatesPicker() {
    // $('#select_date-usage').multiDatesPicker('destroy'); //This line is commented because calender with this id is not present.
    $('#select_date').multiDatesPicker('destroy');
}

function resetPageData() {
    destroyMultiDatesPicker();
    clearLogMessage();
    // initMultiDatesPicker();
}

function clearLogMessage() {
    $('#enterLogMessage').html("");
    $('#enterLogMessage').removeClass("alert-danger");
    $('#enterLogMessage').removeClass("alert-success");
}

function updateCalendar() {

    if (calendarData.dates.length > 0) {

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
            onSelect: function () {
                var partial24hoursflag = false;
                    //if any date hrs exceed than 24 and slider is disabled then make it enabled for other dates
                    var cid= $('#contract_id_').val();
                    var partial_hours =$('#partial_hours_'+cid).val();
   
                    if(calendarFlags.enablePartialHours ==1 && partial_hours == 1) {
                        if(document.querySelector('input[type="range"]')){
                            var inputRange = document.querySelector('input[type="range"]');
                            // inputRange.rangeSlider.update({min: 0.25, max: 24, step: 0.25, value:24, buffer: 0});
                           
                            // var partial_hours_calculation = calendarData.partial_hours_calculation;
                            inputRange.rangeSlider.update({min: 0.25, max: calendarData.partial_hours_calculation, step: 0.25, value:calendarData.partial_hours_calculation, buffer: 0});
                        }
                        $('#submitLog').prop('disabled',false);
                        var selected_dates = $('#select_date').multiDatesPicker('getDates');
                       
                        if (selected_dates.length > 0) {
                            var partialHoursData = {};
                           
                            $.each(selected_dates, function (index, date_value) {
                                var sum_duration =0.00;
                                var action = $('#action').val();
                                $.each(calendarData.total_duration_log_details, function () {
                                    if (date_value== this.date && (action == this.action)) {
                                    sum_duration = sum_duration + this.duration;
                                }
                            });
                            partialHoursData[ date_value] = sum_duration;
                            });

                           
                            if(Object.entries(partialHoursData).length > 0 ) {
                                const all_min_duration = Object.values(partialHoursData);
                                duration = Math.max(...all_min_duration);
                               
                               if(duration == calendarData.partial_hours_calculation)
                                {  //disabled duration slide if hours exceed than 24hours

                                    if(document.querySelector('input[type="range"]')){
                                        var inputRange = document.querySelector('input[type="range"]');
                                        inputRange.rangeSlider.update({min: 0, max: 0, step: 0.25, value: 0, buffer: 0});

                                    }
                                    $('#submitLog').prop('disabled',true);
                                    //return false;
                                } else {
                                    duration = calendarData.partial_hours_calculation - duration;
                                    if (document.querySelector('input[type="range"]')) {
                                        var inputRange = document.querySelector('input[type="range"]');
                                        inputRange.rangeSlider.update({
                                            min: 0.25,
                                            max: duration,
                                            step: 0.25,
                                            value: duration,
                                            buffer: 0
                                        });

                                    }
                                }
                            }else
                            {
                                partial24hoursflag = true;
                            }
                        }else {
                            partial24hoursflag = true;
                        }
                    }

                    if( partial24hoursflag==true ){
                        // $('#duration').prop("disabled",false);
                        if (document.querySelector('input[type="range"]')) {
                            var inputRange = document.querySelector('input[type="range"]');
                            inputRange.rangeSlider.update({
                                min: 0.25,
                                max: calendarData.partial_hours_calculation,
                                step: 0.25,
                                value: calendarData.partial_hours_calculation,
                                buffer: 0
                            });
                        }
                    }
            },
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
			   /* call-coverage-duration  by 1254 : added flag for partial shift*/
                var disabilityOnActionPartialShiftHour=false;

                /**
                 * create vars for style
                 */
                var amOwnFlag = false;
                var pmOwnFlag = false;
                var fullOwnFlag = false;
                var amOtherFlag = false;
                var pmOtherFlag = false;
                var fullOtherFlag = false;
				var partialOnCallDayOtherFlag = false;
                var style = "";

                var isOwnerLogDate=[];
                for (var i = 0; i < calendarData.dates.length; i++) {
                    var dateParts = calendarData.dates[i].date.split("/"); // Current format MM/DD/YYYY
                    var selectedDateObj = new Date(dateParts[2], dateParts[0] - 1, dateParts[1]); // YYYY-MM-DD
                    var selectedDateMDY = (selectedDateObj.getMonth() + 1) + '/' +
                        selectedDateObj.getDate() + '/' +
                        selectedDateObj.getFullYear();
                        var partialhalfdayflag=false;

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
                            }else if (calendarData.dates[i].duration == "On call Uncompensated Day") {
                                fullOwnFlag = true;
                                style = "on-call-uncompensated-full-day";
                            }else if (calendarData.dates[i].partial_hours == 1 ){  /* call-coverage-duration  by 1254 : added style for partial shift -modified by akash.*/ 
                                partialOnCallDayFlag = true;
                              
                                if(calendarData.dates[i].total_duration  == calendarData.dates[i].partial_hours_calculation) {
                                    style = "partial-on-call-day";
                                } else {
                                    style = "partial-on-call-half-day";
                                }
                                isOwnerLogDate.push(calendarData.dates[i].date);
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
                            } else if (calendarData.dates[i].duration == "On call full Day" || calendarData.dates[i].duration == "On call Uncompensated Day") {
                                fullOtherFlag = true;
                                style = "full-day-other";
                            }else if (calendarData.dates[i].partial_hours == 1 ) {  /* call-coverage-duration  by 1254 : added style for partial shift -modified by akash.*/
                                if (!(isOwnerLogDate.includes(calendarData.dates[i].date))) {
                                    partialOnCallDayOtherFlag = true;
                                   
                                    if(calendarData.dates[i].total_duration  == calendarData.dates[i].partial_hours_calculation) {
                                        style = "full-day-other";
                                    } else {
                                        style = "partial-on-call-half-day-other";
                                    }
                                   
                                }
                            }
                        }
                    }
                    else
                    {
                        /*disable dates in aprrove month*/
                        // var dateYear=(date.getMonth() + 1) + '-' + date.getFullYear();
                        // var approve=$.inArray(dateYear,approved_logs_months);

                        var inputDate = date.getFullYear() + '-' + ("0" + (date.getMonth() + 1)).slice(-2) + '-' + ("0" + date.getDate()).slice(-2);

                        for (var rangeIndex in approved_logs_months) {
                            if (approved_logs_months.hasOwnProperty(rangeIndex)) {
                                var rangeObj = approved_logs_months[rangeIndex];
                                for (var prop in rangeObj) {
                                    // skip loop if the property is from prototype
                                    if (!rangeObj.hasOwnProperty(prop)) continue;

                                    var rangeStartDate = new Date(rangeObj["start_date"]);
                                    rangeStartDate = rangeStartDate.setHours(0,0,0,0);
                                    var rangeEndDate = new Date(rangeObj["end_date"]);
                                    rangeEndDate = rangeEndDate.setHours(0,0,0,0);
                                    var inputDate = new Date(inputDate);
                                    inputDate = inputDate.setHours(0,0,0,0);
                                    if(inputDate >= rangeStartDate && inputDate <= rangeEndDate){
                                        var approve = true;
                                    }
                                    
                                }
                            }
                        }

                        /*if(approve>=0)
                         {
                         console.log(selectedDateMDY);
                         //return [false, ""];
                         //return [calendarData.dates[i].isDisabled, true];
                         }*/

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
 				/* call-coverage-duration  by 1254 : added flag for partial shift*/
                    // if(calendarData.dates[i].partial_hours == 1 && calendarData.dates[i].total_duration == calendarData.partial_hours_calculation ) {
                    //     disabilityOnActionPartialShiftHour = true;

                    // } else {
                    //     disabilityOnActionPartialShiftHour = true;
                    // }

                    if(calendarData.dates[i].partial_hours == 1 && calendarData.dates[i].total_duration  < calendarData.partial_hours_calculation ) {
                        partialhalfdayflag = true;

                    }
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
                            disabilityOnActionPartialShiftHour=false;
                            break;
                        }else{
                            disabilityOnAction =true;
                            disabilityOnActionPartialShiftHour=true;
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
                            disabilityOnActionPartialShiftHour=false;
                            break;
                        }else{
                            disabilityOnAction =true;
                            disabilityOnActionPartialShiftHour=true;
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
                        //enable holidays for weekday and weekend   :code commented   

                        // if (!calendarFlags.enableHolidays && DateMDY == selectedDateMDY) {
                        //     disabilityOnAction =true;
                        //     disabilityOnActionPartialShiftHour=true;
                        //     break;
                        // }

                        //enable holidays for weekday and weekend   :code commented   

                    }
                }

                if(calendarFlags.enableOnCallBurden) { /*add condition to check burden for on call activity*/
                    //if($("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In'){
                    if ($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In') {
                      /*  if(calendarFlags.enablePartialHours) {
                            disabilityOnAction = false;
                        }else */
                        {
                            disabilityOnAction = true;

                        }
                    }
                }

                /**
                 * add style and enable flag
                 */
                switch (style) {
			/* call-coverage-duration  by 1254 : set  and return enable or disable flag and style for partial shift*/
                    case "partial-on-call-day" :
                        
                        if (approve >= 0 || disabilityOnActionPartialShiftHour ||!(calendarFlags.enablePartialHours)) {
                            return [false, "partial-on-call-day"];
                        }
                        else {
                            return [true, "partial-on-call-day"];
                        }        
                    case "partial-on-call-half-day" :

                        if(approve>=0 || disabilityOnActionPartialShiftHour ||!(calendarFlags.enablePartialHours))  {
                            return [false, "partial-on-call-half-day"];
                        } else {
                            return [true, "partial-on-call-half-day"];
                        }
                    case "partial-on-call-half-day-other" :

                        if(approve>=0 || disabilityOnActionPartialShiftHour ||!(calendarFlags.enablePartialHours))  {
                            return [false, "partial-on-call-half-day-other"];
                        } else {
                            return [true, "partial-on-call-half-day-other"];
                        }
                    case "am-half-day":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "am-half-day"];
                        }
                        else{
                            return [calendarFlags.enableAMShifts, "am-half-day"];
                        }
                    case "pm-half-day":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "pm-half-day"];
                        }
                        else{
                            return [calendarFlags.enablePMShifts, "pm-half-day"];
                        }
                    case "full-day":
                        if(approve>=0 || disabilityOnAction )
                        {
                            return [false, "full-day"];
                        }
                        else{
                            return [false, "full-day"];
                        }
                    case "am-half-day-other":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "am-half-day-other"];
                        }
                        else{
                            return [calendarFlags.enableAMShifts, "am-half-day-other"];
                        }
                    case "pm-half-day-other":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "pm-half-day-other"];
                        }
                        else{
                            return [calendarFlags.enablePMShifts, "pm-half-day-other"];
                        }
                    case "full-day-other":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "full-day-other"];
                        }
                        else{
                            return [false, "full-day-other"];
                        }
                    case "am-half-day-other-pm-own":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "am-half-day-other-pm-own"];
                        }
                        else{
                            return [false, "am-half-day-other-pm-own"];
                        }
                    case "pm-half-day-other-am-own":
                        if(approve>=0 || disabilityOnAction)
                        {
                            return [false, "pm-half-day-other-am-own"];
                        }
                        else{
                            return [false, "pm-half-day-other-am-own"];
                        }
                    case "on_callfull-day":
                        /*if(!(approve >= 0) && ($("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In')) {*/
                        if(!(approve >= 0) && ($("#action option:selected").attr('data-action-name') == 'Called-Back'|| $("#action option:selected").attr('data-action-name') == 'Called-In' || !calendarFlags.enableOnCallBurden)) {
                            return [true, "full-day"];
                        }
                        /*else if (approve >= 0 || $("#action option:selected").text() == 'On-Call'){*/
                        else if (approve >= 0 || $("#action option:selected").attr('data-action-name') == 'On-Call'){
                            return [false, "full-day"];
                        }
                        else {
                            return [true, "full-day"];
                        }
                    case "on-call-uncompensated-full-day":
                        //if(!(approve >= 0) && ($("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In')) {
                        if (approve >= 0 || $("#action option:selected").attr('data-action-name') == 'On-Call/Uncompensated'){
                            return [false, "full-day"];
                        }
                        else {
                            return [true, "full-day"];
                        }
                    case "on-call-uncompensated-full-day-other":
                        //if(!(approve >= 0) && ($("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In')) {
                        if (approve >= 0 || $("#action option:selected").attr('data-action-name') == 'On-Call/Uncompensated'){
                            return [false, "full-day-other"];
                        }
                        else {
                            return [true, "full-day-other"];
                        }
                    default:

                        /**
                         * [0]: true/false indicating whether or not this date is selectable
                         * as isDisabled contains reverse data for above line,
                         * data is negated using not (!) operator
                         */

                        if(approve>=0 || disabilityOnAction)
                        {
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
        /*if ($("#action option:selected").text() == 'On-Call' || $("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In') {*/
        if ($("#action option:selected").attr('data-action-name') == 'On-Call' || $("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In' || $("#action option:selected").attr('data-action-name') == 'On-Call/Uncompensaed') {
            $('#select_date').multiDatesPicker({
                inline: true,
                beforeShowDay: function (date) {
                    /*if ($("#action option:selected").text() == 'Called-Back' || $("#action option:selected").text() == 'Called-In') {*/

                    if(calendarFlags.enableOnCallBurden) { /*add condition to check burden for on call activity*/
                        if ($("#action option:selected").attr('data-action-name') == 'Called-Back' || $("#action option:selected").attr('data-action-name') == 'Called-In') {
                            return [false, ""];
                        } else {
                            return [true, ""];
                        }
                    } else {
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
    if(selected_date != undefined){
        $('#select_date').datepicker('setDate', selected_date);
    }
    $(".overlay").hide();
}

function updateFieldsForAction(){
    var action = $('#action').val();
    $('input:radio[name=shift]').attr('checked', false);
    $('#select_date').multiDatesPicker('resetDates', 'picked');
    clearLogMessage();
    var contractId = $('#contract_id').val();

    for (var i = 0; i < actionsForCurrentPhysician.length; i++) {
        if (actionsForCurrentPhysician[i].id == action) {
            if (actionsForCurrentPhysician[i].duration == 0.5) {
                $('#divShift input:radio[name=shift]').attr('disabled', false);
                isSelectedActionHalfDay = true;
                calendarData.isDisabled= true;
                // updateCalendar();
            } else {
                $('#divShift input:radio[name=shift]').attr('disabled', true);
                isSelectedActionHalfDay = false;
                calendarFlags.enableAMShifts = false;
                calendarFlags.enablePMShifts = false;
                calendarData.isDisabled = false;
                // updateCalendar();
            }
            break;
        }
    }

    /*var text =$("#action option:selected").text().split("-");*/
    var text =$("#action option:selected").attr('data-action-name').split("-");
    var holiday_on_off = $("#holiday_on_off").val();
    if(text[0].replace(/\s/g,'') == 'Holiday'){
        if(holiday_on_off == 1 )
        {
            calendarFlags.enableHolidays = true;
            calendarFlags.enableWeekDays = true;
            calendarFlags.enableWeekEnds = true;
        }
        else{
            calendarFlags.enableHolidays = true;
            calendarFlags.enableWeekDays = false;
            calendarFlags.enableWeekEnds = false;
        }
        // updateCalendar();
    }else if(text[0].replace(/\s/g,'') == 'Weekday'){
        calendarFlags.enableHolidays = false;
        calendarFlags.enableWeekDays = true;
        calendarFlags.enableWeekEnds = false;
        // updateCalendar();
    }else if(text[0].replace(/\s/g,'') == 'Weekend'){
        calendarFlags.enableHolidays = false;
        calendarFlags.enableWeekDays = false;
        calendarFlags.enableWeekEnds = true;
        // updateCalendar();
    /*}else if(($("#action option:selected").text() == 'On-Call' || $("#action option:selected").text() == 'Called-Back'|| $("#action option:selected").text() == 'Called-In')){*/
    }else if(($("#action option:selected").attr('data-action-name') == 'On-Call' || $("#action option:selected").attr('data-action-name') == 'Called-Back'|| $("#action option:selected").attr('data-action-name') == 'Called-In' || $("#action option:selected").attr('data-action-name') == 'On-Call/Uncompensated')){
        /* Added for new on call rates*/
        calendarFlags.enableHolidays = true;
        calendarFlags.enableWeekDays = true;
        calendarFlags.enableWeekEnds = true;
        $('#divShift').hide();
        // updateCalendar();
    }
    updateCalendar();
    $(".overlay").hide();
}

function refreshApproveLogsView() {
    var physicianId = $('#physician_name').val();
    var contractId;
    //fetch contract id from physician id
    for (var i = 0; i < physicians.length; i++) {
        if (physicianId == physicians[i].id) {
            contractId = physicians[i].contract;
        }
    }
    var dateSelector = $('#dateSelector').val();
    $.post(basePath + "/getApproveLogsViewRefresh", {
        contract: contractId,
        physician: physicianId,
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
        var date_selector = $('#dateSelector').val();
        date_selector = date_selector.replace(/\//gi, "-");
        $('#bntApprove').attr('href',$('#bntApprove').attr('href')+'/'+date_selector);
        console.log($('#bntApprove').attr('href'));
    });
}
//call-coverage-duration : added new function to set update range of duration slider  by 1254
function setSlider()
{

    var cid= $('#contract_id').val();
    var payment_type = $('#payment_type_'+cid).val();
    var partial_hours = $('#partial_hours_'+cid).val();

    if((payment_type==3 || payment_type==5) && partial_hours == 1) {

        if(document.querySelector('input[type="range"]')){
            var inputRange = document.querySelector('input[type="range"]');
            inputRange.rangeSlider.update({min: 0, max: calendarData.partial_hours_calculation, step: 0.25, value: 0.25, buffer: 0});
        }
    }else
    {
        if(document.querySelector('input[type="range"]')){
            var inputRange = document.querySelector('input[type="range"]');
            inputRange.rangeSlider.update({min: 0, max: 12, step: 0.25, value: 0.25, buffer: 0});
            $('#submitLog').prop('disabled',false);
        }
    }
}

function getRecentLogs(physicianid, contract_id)
{
    resetPageData();
    var contract_id = $('#contract_id').val() == 0 ? contract_id : $('#contract_id').val();
    var physicianid = $('#physician_name').val() == 0 ? physicianid : $('#physician_name').val();

    $.ajax({
        'type':'GET',
        'url': '/getRecentLogs/'+physicianid+'/'+contract_id,
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
        }
    });
}

function getPendingForApprovalLogs(physicianid, contract_id)
{
    $.ajax({
        'type':'GET',
        'url': '/getApprovedLogs/'+physicianid+'/'+contract_id,
        success:function(response) {
            if (response.approve_logs_count > 0) {

                $('#approveLogs').html(response.approve_logs_view); // append approve log view.

            } else {

                // $('#approveLogs').html("There are no approve logs.");

            }
            dateSelectors = response.date_selectors;

            $('#bntApprove').attr('href',$('#bntApprove').attr('href')+'/'+$('#dateSelector').val());

            $('#date_range').val(JSON.stringify(dateSelectors));

            var date_range = $('#date_range').val();

            sessionStorage.removeItem('date_range');

            sessionStorage.setItem('date_range', date_range);
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

    $(".overlay").show();

    var physicianId = physicianId;

    // client issue fixed : Delete log is not working when logged in as Practice Manager by 1254
    if(physicianId==null)
    {
        physicianId = $('#physician_name').val();
    } //end client issue fixed : Delete log is not working when logged in as Practice Manager by 1254

    contractsDataForOtherPhysicians = [];

    var contractId;

    for (var i = 0; i < physicians.length; i++) {
        if (physicianId == physicians[i].id) {
            contractId = physicians[i].contract;
        }
    }

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
        var old_contract_id= $('#contract_id').val();

        if (!jQuery.isEmptyObject(getContractsResponse)) {
            contract_period_data = getContractsResponse.contract_period_data;
            $('#contract_id').val(getContractsResponse.id);

            $('#payment_type_'+old_contract_id).attr('id', 'payment_type_'+getContractsResponse.id);
            $('#payment_type_'+getContractsResponse.id).val(getContractsResponse.payment_type_id);
            $('#partial_hours_'+old_contract_id).attr('id', 'partial_hours_'+getContractsResponse.id);
            $('#partial_hours_'+getContractsResponse.id).val(getContractsResponse.partial_hours);
            $('#contract_id_').val(getContractsResponse.id);
            $('#partial_hours_calculation_'+getContractsResponse.id).val(getContractsResponse.partial_hours_calculation);
            $('#log_entry_deadline'+getContractsResponse.id).val(getContractsResponse.log_entry_deadline);
        }

        //issue fixed : to hide duration slider for payment  type 3 having partial hour 0.
        var cid= $('#contract_id').val();
        var payment_type = $('#payment_type_'+cid).val();
        var partial_hours = $('#partial_hours_'+cid).val();
        if((payment_type == 3|| payment_type == 5) && partial_hours ==0)
        {
            $('.co_mgmt_med_direct').hide();
        } else if((payment_type == 3|| payment_type == 5) && partial_hours ==1){
            var partial_hours_calculation =$('#partial_hours_calculation_'+cid).val();
            $('.co_mgmt_med_direct').show();
            rangeSlide(0.25, partial_hours_calculation);
        }

        // recent logs
        if (!jQuery.isEmptyObject(getContractsResponse)) {
            // $('#recentLogs').html(data.recent_logs_view);
            $('#log_entry_deadline').val(getContractsResponse.log_entry_deadline);
            calendarFlags.enableOnCallBurden = getContractsResponse.burden_of_call;
            calendarFlags.enablePartialHours = getContractsResponse.partial_hours;
            calendarData.partial_hours_calculation =getContractsResponse.partial_hours_calculation;


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

                    //skipping physician if getContractsResponse already exist as root object

                    if ( (physiciansContractData[i].id != getContractsResponse.id && physiciansContractData[i] != undefined) ||
                        (physiciansContractData[i].id == getContractsResponse.id && physiciansContractData[i].contract_physician_id != physicianId) )  {
                        contractsDataForOtherPhysiciansLength = contractsDataForOtherPhysicians.length;
                        contractsDataForOtherPhysicians[contractsDataForOtherPhysiciansLength] = physiciansContractData[i];
                    }
                }
            }

            var consolidateTotalDurationLogs = [];
            for (var i = 0; i < getContractsResponse.total_duration_log_details.length; i++) {

                consolidateTotalDurationLogs.push(getContractsResponse.total_duration_log_details[i])
            }

            $('#action')
                .find('option')
                .remove()
                .end();

            // actions
            if (getContractsResponse.actions.length > 0) {
                physicianActions = getContractsResponse.actions;
                $.each(getContractsResponse.actions, function () {
                    //if (data.contract_type_id != 4) {
                    if (getContractsResponse.payment_type_id != 3 && getContractsResponse.payment_type_id != 5) {
                        $('#action').append($("<option></option>")
                            .attr("value", this.id)
                            .attr("data-action-name", this.name)
                            .text(this.name));
                    }else{
                        $('#action').append($("<option></option>")
                            .attr("value", this.id)
                            .attr("data-action-name", this.name)
                            .text(this.display_name));
                    }
                });

                $('input:radio[name=shift]').attr('checked', false);

                if (getContractsResponse.actions[0].duration == 0.5) {
                    // $('#divShift').show();
                    $('#divShift input:radio[name=shift]').attr('disabled', false);
                    isSelectedActionHalfDay = true;
                    calendarData.isDisabled = true;
                } else {
                    // $('#divShift').hide();
                    $('#divShift input:radio[name=shift]').attr('disabled', true);
                    isSelectedActionHalfDay = false;
                    calendarData.isDisabled = false;
                }
            } else {
                physicianActions = [];
                $('#action').append('<option data-action-name ="" value="">No actions available.</option>');
            }

            // created common variable to loop through for getting duration for action
            actionsForCurrentPhysician = getContractsResponse.actions;
            //approved logs months for disabled selection
            approved_logs_months = getContractsResponse.approved_logs_months;

            // disable existing dates and update calendar
            $('#select_date').multiDatesPicker('resetDates', 'picked');
            $('#select_date').multiDatesPicker('resetDates', 'disabled');
            // calendarData.dates = consolidatedRecentLogs; //data.recent_logs;
            calendarData.holidays = getContractsResponse.holidays; //data.holidays;
            calendarData.total_duration_log_details = consolidateTotalDurationLogs;

            // $('#bntApprove').attr('href',$('#bntApprove').attr('href')+'/'+$('#dateSelector').val());
        } else {
            $('#action').append('<option data-action-name ="" value="">No actions available.</option>');
            $('#recentLogs').html("There are no recent logs.");
            $(".overlay").hide();
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
        // getRecentLogsResponse ends here.
        renderCalendar();
    });

    getPendingForApprovalLogs(physicianId,contractId);
}
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
