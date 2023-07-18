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
    performanceFilterFunction($('#hospital').val(),0,0,0,0,0,0,'','','');
});

$('#agreement').on('change', function () {
      performanceFilterFunction($('#hospital').val(),$('#agreement').val(),0,0,0,0,0,'','','');
});
$('#agreementChk').on('change', function () {
    performanceFilterFunction($('#hospital').val(),$('#agreement').val(),0,0,0,0,0,'','','');
});
$('#practice').on('change', function () {
     performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),0,0,0,0,'','','');
});
$('#pracChk').on('change', function () {
    performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),0,0,0,0,'','','');
});
$('#physician').on('change', function () {
     performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),0,0,0,'','','');
});
$('#phyChk').on('change', function () {
    performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),0,0,0,'','','');
});
$('#payment_type').on('change', function () {
     performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),0,0,'','','');
});
$('#contract_type').on('change', function () {
    console.log("on contract type change func");
     performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),0,'','','');
});
$('#typeChk').on('change', function () {
    console.log("on contract type change func");
     performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),0,'','','');
});

$('#contract_name').on('change', function () {
    performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#contract_name').val(),'','','');
});
$('#nameChk').on('change', function () {
    performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#contract_name').val(),'','','');
});
$('#start_date').on('change', function () {
    var start = new Date($('#start_date').val());
    var end = new Date($('#end_date').val());
    if(start.getTime() < end.getTime()) {
        performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#contract_name').val(),$('#start_date').val(),$('#end_date').val(),'');
    }
});
$('#end_date').on('change', function () {
    var start = new Date($('#start_date').val());
    var end = new Date($('#end_date').val());
    if(start.getTime() < end.getTime()) {
        performanceFilterFunction($('#hospital').val(),$('#agreement').val(),$('#practice').val(),$('#physician').val(),$('#payment_type').val(),$('#contract_type').val(),$('#contract_name').val(),$('#start_date').val(),$('#end_date').val(),'');
    }
});

$('#export').on('click', function (e) {
    e.preventDefault();
    $( "#export_submit" ).trigger( "click" );
    $('.overlay').show();
});
$(document).ready(function() {

});

// function to call log Details In Index page  by ajax request - starts
function performanceFilterFunction(hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,contract_name_id,startDate,endDate,page){
    console.log("performanceFilterFunction parameters call",hospital_id,agreement_id,practice_id,physician_id,payment_type_id,contract_type_id,contract_name_id,startDate,endDate,page);

    var selectAllChkBoxArr = [];
    $(".selectAll:checked").each(function(){
        selectAllChkBoxArr.push($(this).attr("id"));
    });
    console.log(selectAllChkBoxArr);
    $('.overlay').show();
    $('#form_replace').html();
    var payload = {'start_date':startDate,
                    'end_date':endDate,
                    'page': page};
    if(agreement_id != 0){
        // agreement_id.forEach(myFunction);
        // function myFunction(item) {
        //     payload.agreement += item;
        //   }
         payload.agreement = agreement_id;
    }
    if(practice_id != 0){
        payload.practice = practice_id;
    }
    if(physician_id != 0){
        payload.physician = physician_id;
    }
    if(payment_type_id != 0){
        payload.payment_type = payment_type_id;
    }
    if(contract_type_id != 0){
        payload.contract_type = contract_type_id;
    }
    if(hospital_id != 0){
        payload.hospital = hospital_id;
    }
    if(contract_name_id != 0){
        payload.contract_name = contract_name_id;
    }
    console.log("payload===",payload)
    $.ajax({
        url:'',
        type:'get',
        data:
        payload,
        success:function(response){
            $('#form_replace').html(response);
          
            for (var prop in selectAllChkBoxArr) {
                $('#'+selectAllChkBoxArr[prop]).prop('checked',true);
            }
        },
        complete:function(){
            $('.overlay').hide();
        }
    });
}


