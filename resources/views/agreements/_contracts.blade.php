@php use function App\Start\is_practice_manager; @endphp
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
<!--for payments tab-->
@if(Request::is('payments/*'))
    @if (count($contracts) > 0)
        <ul style="list-style:none;padding-top:15px;">
            <?php $i = 0; ?>
            @foreach ($contracts as $contract)
                <li>
                    <i class="fa fa-file-text-o"></i> <strong>{{ $contract->name }}</strong>
                    <input type="hidden" class="contractArray pull-right" name="contractArray[]"
                           value="{{ $contract->contract_type_id }}">
                    <input type="hidden" class="practiceArray pull-right" name="practiceArray[]"
                           value="{{ count($contract->practices) }}">
                    <ul style="list-style:none">
                        <?php $contract_physician_count = 0; ?>
                        @foreach ($contract->practices as $practice)
                            <li>
                                @if(Request::is('payments/*'))
                                    <div class="col-md-7" style="margin-top:15px;"><strong>Expected Practice Total : </strong><span
                                                class="dynaminc_expected_total">0</span></div>
                                @endif
                                @if(Request::is('payments/*'))
                                    <div class="col-md-5" style="margin-top:15px;"><strong>Practice Total : </strong><span
                                                class="dynaminc_paid_total">0</span></div>
                                @endif
                                <div class="col-md-7">
                                    @if(is_practice_manager())
                                        <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                                    @else
                                        <a href="{{ route('practices.show', $practice->id) }}">
                                            <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                                        </a>
                                    @endif
                                </div>
                                <ul style="list-style:none">
                                    <?php $physician_count = 0; ?>
                                    @foreach ($practice->physicians as $physician)
                                        <input type="hidden" class="practiceId pull-right" name="practiceId"
                                               value="{{ $practice->id }}">
                                        <input type="hidden" class="contractId pull-right" name="contractId"
                                               value="{{ $physician->contract_id }}">
                                        <li style="clear: both;">
                                            @if(Request::is('payments/*'))
                                            <!-- physician to multiple hospital by 1254 : -->
                                                <a href="{{ route('physicians.show', [$physician->id, $practice->id]) }}">
                                                    <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                    [<span id="workedHour{{$i}}"></span>]
                                                </a>
                                                <input type="hidden" name="amountPaidID" value="{{$physician->id}}">
                                                <input type="text" class="amountPaid pull-right" name="amountPaid[]"
                                                       value="" style="margin-top:5px;margin-bottom:5px;" placeholder="$">
                                                <input type="hidden" name="prevval" value="0" id="{{$physician->id}}">
                                                <input type="hidden" class="paymentDone" id="paymentDone{{$physician->id}}" name="paymentDone" value="0">
                                                <div class="alert alert-danger"
                                                     style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                    <a class="close" id="paymentDismiss">&times;</a>
                                                    <strong>Error!</strong> Please Enter Valid Payment.
                                                </div>
                                                <input type="hidden" class="workedHours pull-right" name="amountPaid[]"
                                                       value="">
                                                <div class="alert alert-danger"
                                                     style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                    <a class="close" id="hoursDismiss">&times;</a>
                                                    Payment Not Allowed –Minimum Hours not achieved for Selected Period.
                                                </div>
                                                <input type="hidden" class="maxHours pull-right" name="maxHours[]" value="">
                                                <div class="alert alert-danger"
                                                     style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                    <a class="close" id="maxHoursDismiss">&times;</a>
                                                    This user has reached their Maximum Paid Amount for this period. Max
                                                    possible payment: <span id="physician{{$i}}"></span>
                                                </div>
                                                @if(($contract->contract_type_id)==2)
                                                    <input type="hidden" class="pull-right" name="maxAnnual{{$i}}" id="maxAnnual{{$i}}" value="">
                                                    <div class="alert alert-danger"
                                                         style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                        <a class="close" id="maxHoursDismiss">&times;</a>
                                                        This user has reached their Maximum Paid Amount for this Year. Max
                                                        possible payment: <span id="physicianAnnual{{$i}}"></span>
                                                    </div>
                                                @endif
                                                <div class="col-md-7" style="padding-left: 0px;"><strong>Expected Physician Total : </strong><span
                                                            class="remaining {{$physician->id}}">0</span></div>
                                                <div style="clear: both;"><br/></div>
                                            @else
                                                @if(is_practice_manager())
                                                    <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                @else
                                                <!-- physician to multiple hospital by 1254 : -->
                                                    <a href="{{ route('physicians.show', [$physician->id, $practice->id]) }}">
                                                        <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                    </a>
                                                @endif

                                            @endif
                                        </li>
                                        <?php $i++; ?>
                                        <?php $physician_count++; $contract_physician_count++; ?>
                                    @endforeach
                                </ul>
                            </li>
                        @endforeach
                    </ul>
                    <input type="hidden" class="physicianCount pull-right" name="physicianCount[]"
                           value="{{ $contract_physician_count }}">
                </li>
            @endforeach
        </ul>
        @if(Request::is('payments/*'))
            <button type="button" id="ajax_submit" class="btn btn-primary" style="float: right;margin-top:15px;">Submit
                Payment
            </button>
        @endif
    @else
        <p>There are currently no contracts available for display.</p>
    @endif
@else
    @if (count($agreement_contracts) > 0)
        <ul style="list-style:none;padding-top:15px;">
            <?php
            $i = 0;
            $temp_physician_arr = [];
            ?>
            @foreach ($agreement_contracts as $agreement_contract)
                <li>
                    <i class="fa fa-file-text-o"></i> <strong>{{ $agreement_contract->name }}</strong>
                    <input type="hidden" class="contractArray pull-right" name="contractArray[]"
                           value="{{ $agreement_contract->contract_type_id }}">
                    <input type="hidden" class="practiceArray pull-right" name="practiceArray[]"
                           value="{{ count($agreement_contract->practices) }}">
                    <ul style="list-style:none">

                        @foreach ($agreement_contract->practices as $practice)
                            <?php
                            $temp_physician_arr = [];
                            ?>
                            @if(count($practice->physicians) > 0)
                                <li>
                                    @if(Request::is('payments/*'))
                                        <div class="col-md-7" style="margin-top:15px;"><strong>Expected Practice Total : </strong><span
                                                    class="dynaminc_expected_total">0</span></div>
                                    @endif
                                    @if(Request::is('payments/*'))
                                        <div class="col-md-5" style="margin-top:15px;"><strong>Practice Total : </strong><span
                                                    class="dynaminc_paid_total">0</span></div>
                                    @endif
                                    <div class="col-md-7">
                                        @if(is_practice_manager())
                                            <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                                        @else
                                            <a href="{{ route('practices.show', $practice->id) }}">
                                                <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                                            </a>
                                        @endif
                                    </div>
                                    <ul style="list-style:none">
                                        <?php $physician_count = 0; ?>
                                        @foreach ($practice->physicians as $physician)
                                            @if(!in_array($physician->id,$temp_physician_arr))
                                                <input type="hidden" class="practiceId pull-right" name="practiceId"
                                                       value="{{ $practice->id }}">
                                                <input type="hidden" class="contractId pull-right" name="contractId"
                                                       value="{{ $physician->contract_id }}">
                                                <li style="clear: both;">
                                                    @if(Request::is('payments/*'))
                                                        <a href="{{ route('physicians.show', [$physician->id, $practice->id]) }}">
                                                            <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                            [<span id="workedHour{{$i}}"></span>]
                                                        </a>
                                                        <input type="hidden" name="amountPaidID" value="{{$physician->id}}">
                                                        <input type="text" class="amountPaid pull-right" name="amountPaid[]"
                                                               value="" style="margin-top:5px;margin-bottom:5px;" placeholder="$">
                                                        <div class="alert alert-danger"
                                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                            <a class="close" id="paymentDismiss">&times;</a>
                                                            <strong>Error!</strong> Please Enter Valid Payment.
                                                        </div>
                                                        <input type="hidden" class="workedHours pull-right" name="amountPaid[]"
                                                               value="">
                                                        <div class="alert alert-danger"
                                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                            <a class="close" id="hoursDismiss">&times;</a>
                                                            Payment Not Allowed –Minimum Hours not achieved for Selected Period.
                                                        </div>
                                                        <input type="hidden" class="maxHours pull-right" name="maxHours[]" value="">
                                                        <div class="alert alert-danger"
                                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                            <a class="close" id="maxHoursDismiss">&times;</a>
                                                            This user has reached their Maximum Paid Amount for this period. Max
                                                            possible payment: <span id="physician{{$physician_count}}"></span>
                                                        </div>
                                                        <div class="col-md-7" style="padding-left: 0px;"><strong>Expected Physician Total : </strong><span
                                                                    class="remaining">0</span></div>
                                                        <div style="clear: both;"><br/></div>
                                                    @else
                                                        @if(is_practice_manager())
                                                            <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                        @else
                                                            <a href="{{ route('physicians.show', [$physician->id, $practice->id]) }}">
                                                                <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                            </a>
                                                        @endif

                                                    @endif
                                                </li>
                                                <?php $i++; ?>
                                                <?php
                                                    $physician_count++;
                                                    $temp_physician_arr[] = $physician->id;
                                                ?>
                                            @endif
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ul>
        @if(Request::is('payments/*'))
            <button type="button" id="ajax_submit" class="btn btn-primary" style="float: right;margin-top:15px;">Submit
                Payment
            </button>
        @endif
    @else
        <p>There are currently no contracts available for display.</p>
    @endif

@endif

<script>
    $(document).ready(fetch_data = function () {
        //var contract_type_id = $("#contractTypeID").val();
        $(".contractArray").each(function () {
            console.log("contract_type_id : ", $(this).val());
        });
        $(".practiceArray").each(function () {
            console.log("practiceCount : ", $(this).val());
        });

        $("#hoursDismiss, #paymentDismiss, #maxHoursDismiss").click(function () {
            $(this).parent().css('display', 'none');
        });
        $("#hoursDismiss, #paymentDismiss, #maxHoursDismiss").click();

        //alert("hi");
        var check_payment_url = "{{Request::is('payments/*')}}";
        var start_date_a = $('#start_date option:selected').text();
        if (check_payment_url == 1) {
            var date_all = start_date_a.split(" - ");
            var start_date = date_all[0].split(": ");
            var end_date = date_all[1].split(": ");
            var idsArr = new Array();
            var contract_idsArr = new Array();
            var practice_idsArr = new Array();
            $(".amountPaid").each(function () {
                idsArr.push($(this).prev().val());
                contract_idsArr.push($(this).parent().prev().val());
                practice_idsArr.push($(this).parent().prev().prev().val());
            });
            $.ajax({
                url: "{{ route('agreements.getHoursAndPaymentDetails') }}",
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {
                    ids: idsArr,
                    contract_ids: contract_idsArr,
                    practice_ids: practice_idsArr,
                    start_date: start_date[1],
                    end_date: end_date[0],
                    contract_month: start_date[0],
                    agreement_id: $("#agreement_id").val()
                },
                success: function (data) {
                    var obj = $.parseJSON(data);
                    var workedHoursArray = obj.worked_hours;
                    var paymentDetailsArray = obj.payment_details;
                    var maxHoursArray = obj.monthly_max_hours;
                    var remainingAmt = obj.remaining_amount;
                    var payment_done = obj.payment_done;
                    var annual_max_pay = obj.annual_max_pay;
                    console.log("workedHours: ", workedHoursArray);
                    console.log("paymentDetails: ", paymentDetailsArray);
                    console.log("maxHuurs: ", maxHoursArray);
                    console.log("remainingAmt: ", remainingAmt);
                    var count = 0;
                    $(".paymentDone").each(function () {
                        $(this).val(payment_done[count]);
                        /*var workedHours = "#workedHour" + count;
                         $(workedHours).html(workedHoursArray[count]);*/
                        count++;
                    });
                    var count = 0;
                    $(".workedHours").each(function () {
                        $(this).val(workedHoursArray[count]);
                        var workedHours = "#workedHour" + count;
                        $(workedHours).html(workedHoursArray[count]);
                        count++;
                    });
                    if (/*contract_type_id == 2*/1) {
                        var count = 0;
                        $(".maxHours").each(function () {
                            $(this).val(maxHoursArray[count]);
                            var maxHours = "#maxHour" + count;
                            $(maxHours).html(maxHoursArray[count]);
                            if($('#maxAnnual'+count).length > 0){
                                $('#maxAnnual'+count).val(annual_max_pay[count]);
                                $('#physicianAnnual'+count).html(annual_max_pay[count]);
                            }
                            count++;
                        });
                    }
                    var count = 0;
                    $(".remaining").each(function () {
                        $(this).html(remainingAmt[count]);
                        /*var workedHours = "#workedHour" + count;
                         $(workedHours).html(workedHoursArray[count]);*/
                        count++;
                    });
                    var count = 0;
                    $(".remaining").each(function () {
                        if(remainingAmt[count] >= 0) {
                            $(this).html(remainingAmt[count]);
                            $(this).css("color", "black");
                        }else{
                            $(this).html(remainingAmt[count]);
                            $(this).css("color", "red");
                        }
                        /*var workedHours = "#workedHour" + count;
                         $(workedHours).html(workedHoursArray[count]);dynaminc_expected_total*/
                        var total = 0;
                        //alert($(this).val());
                        if ($(this).html() != "" && !isNaN($(this).html())) {
                            total = parseFloat($(this).html());
                        }
                        //console.log("**remaining*",$(this).parent().parent());
                        $(this).parent().parent().siblings('li').each(function (index, el) {
                            //alert($(this).children('.remaining').html());
                            console.log("**remaining*",$(this).children('.col-md-7').children('span'));
                            if ($(this).children('.col-md-7').children('span.remaining').html() != "" && !isNaN($(this).children('.col-md-7').children('span.remaining').html())) {
                                total += parseFloat($(this).children('.col-md-7').children('span.remaining').html());
                            }
                        });
                        if (total == "" || isNaN(total)) {
                            total = 0;
                        }

                        $(this).parent().parent().parent().prev().prev().prev().children('span').html(total.toFixed(2));
                        count++;
                    });

                    count = 0;
                    $(".amountPaid").each(function () {
                        //$(this).val(remainingAmt[count]);
                        $(this).val(paymentDetailsArray[count]);
                        //amountTotal();
                        var total = 0;
                        //alert($(this).val());
                        if ($(this).val() != "" && !isNaN($(this).val())) {
                            total = parseFloat($(this).val());
                        }
                        $(this).parent().siblings('li').each(function (index, el) {
                            //alert($(this).children('input.amountPaid').val());
                            if ($(this).children('input.amountPaid').val() != "" && !isNaN($(this).children('input.amountPaid').val())) {
                                total += parseFloat($(this).children('input.amountPaid').val());
                            }
                        });
                        if (total == "" || isNaN(total)) {
                            total = 0;
                        }
                        $(this).parent().parent().prev().prev().children('span').html(total.toFixed(2));
                        count++;
                    });
                    //called when successful
                    //$('.ajax-failed').hide();
                }
            });
        }
    });
    //alert(end_date);
    $('#start_date').change(function () {
        fetch_data();
    })

    $("#ajax_submit").click(function (event) {
        $(".overlay").show();
        $("#hoursDismiss, #paymentDismiss, #maxHoursDismiss").click();
        var error = 0;
        var dataArr = new Array();
        var idsArr = new Array();
        var start_date_v = $('#start_date option:selected').val();
        var start_date_a = $('#start_date option:selected').text();
        var date_all = start_date_a.split(" - ");
        var start_date = date_all[0].split(": ");
        //alert(date_all[0].split(": "));
        //alert(start_date_a);
        //var end_date_a = $('#end_date option:selected').text();
        var end_date = date_all[1].split(": ");
        //alert(end_date[1]);

        if (Date.parse(start_date[1]) > Date.parse(end_date[1])) {
            $('#end_date').next().show();
            $('html,body').animate({scrollTop: 0}, '3000');
            error++;
            $(".overlay").hide();
            return false;
        }
        //var scrollVal  =5000;
        var errorShown = true;
        var amountPaidArray = new Array();
        var workedHoursArray = new Array();
        var contractArray = new Array();
        var contractIdArray = new Array();
        var practiceIdArray = new Array();
        var practiceArray = new Array();
        var maxHoursArray = new Array();
        var physicianCountArray = new Array();
        $(".amountPaid").each(function () {
            amountPaidArray.push($(this).val());
        });
        $(".workedHours").each(function () {
            workedHoursArray.push($(this).val());
        });
        $(".contractArray").each(function () {
            contractArray.push($(this).val());
        });
        $(".contractId").each(function () {
            contractIdArray.push($(this).val());
        });
        $(".practiceId").each(function () {
            practiceIdArray.push($(this).val());
        });
        $(".practiceArray").each(function () {
            practiceArray.push($(this).val());
        });
        $(".maxHours").each(function () {
            maxHoursArray.push($(this).val());
        });
        $(".physicianCount").each(function () {
            physicianCountArray.push($(this).val());
        });
        $(".amountPaid").each(function () {
            if (($(this).val() != "" && !$.isNumeric($(this).val())) || parseInt($(this).val()) < 0) {
                $(this).next().next().next().show();
                error++;
                //$('html,body').animate({scrollTop:450}, '3000');
                $('html,body').animate({scrollTop: $(this).next().next().next().offset().top - '50'}, '3000');
                errorShown = false;
                $(".overlay").hide();
                return false;
            }
            else if ($(this).val() === "") {
                $(this).next().hide();
                /*var id=$(this).prev().val();
                 var cid=$("#contractId["+id+"]").val();*/
                idsArr.push($(this).prev().val());
                dataArr.push(0);
                /*contractIdArray.push($("#contractId["+id+"]").val());*/
            } else {
                $(this).next().hide();
                /*var id=$(this).prev().val();
                 var cid=$("#contractId["+id+"]").val();*/
                idsArr.push($(this).prev().val());
                dataArr.push($(this).val());
                /*contractIdArray.push();*/
            }

        });
        //console.log("errorShown : ",errorShown);

        if (errorShown) {
            var count = 0;
            var errorFlag = true;
            var contractFlag = 0;
            var practiceFlag = 0;
            $(".workedHours").each(function () {

                //console.log("workedHours :"+$(this).val());
                //console.log("Top : "+$(this).offset().top);
                // var paymentValue = 0;
                var paymentErrorFlag = false;
                if (amountPaidArray[count] === '') {
                    paymentValue = 0;
                } else {
                    //console.log("inside else: ",amountPaidArray[count]);
                    var paymentValue = amountPaidArray[count];
                }
                // console.log("paymentValue :",paymentValue);
                if ($(this).val() === '0' && paymentValue != 0) {
                    // console.log("Inside zero");
                    paymentErrorFlag = true;
                    $(this).next().show();
                    error++;
                    if (errorFlag) {
                        $('html,body').animate({scrollTop: $(this).next().offset().top - '50'}, '3000');
                        errorFlag = false;
                    }
                    errorShown = true;
                }
                if (parseInt(practiceFlag) + parseInt(physicianCountArray[contractFlag]) <= count) {
                    contractFlag++;
                    practiceFlag = count;
                }
                var contractType = parseInt(contractArray[contractFlag]);
                /*console.log("contractType : ",contractType);
                 console.log("worked hours:",workedHoursArray[count]);
                 console.log("max hours:",$(this).val());
                 console.log("comparisn:",(parseInt(workedHoursArray[count]) < parseInt($(this).val())));*/
                if (!paymentErrorFlag && contractType == 2) {

                    if (parseFloat(paymentValue) > parseFloat($('#maxAnnual'+count).val()) && paymentValue != 0) {
                        //var errorMessage = "This user has reached their Maximum Paid Amount for this period. Max hours paid: "+maxHoursArray[count];
                        var physician = "#physicianAnnual" + count;
                        console.log("physician: " + physician);
                        $(physician).html("$" + $('#maxAnnual'+count).val().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                        //$(this).next().next().next().empty();
                        $('#maxAnnual'+count).next().show();
                        error++;
                        //console.log("errorFlag : ",errorFlag);
                        //console.log("count : ",count);
                        if (errorFlag) {
                            $('html,body').animate({scrollTop: $(this).next().next().next().offset().top - '50'}, '3000');
                            errorFlag = false;
                        }
                        errorShown = true;
                    }else if (parseFloat(paymentValue) > parseFloat(maxHoursArray[count]) && paymentValue != 0) {
                        //var errorMessage = "This user has reached their Maximum Paid Amount for this period. Max hours paid: "+maxHoursArray[count];
                        var physician = "#physician" + count;
                        console.log("physician: " + physician);
                        $(physician).html("$" + maxHoursArray[count].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                        //$(this).next().next().next().empty();
                        $(this).next().next().next().show();
                        error++;
                        //console.log("errorFlag : ",errorFlag);
                        //console.log("count : ",count);
                        if (errorFlag) {
                            $('html,body').animate({scrollTop: $(this).next().next().next().offset().top - '50'}, '3000');
                            errorFlag = false;
                        }
                        errorShown = true;
                    }
                }
                count++;
            });
        }

        if (error == 0) {
            $.ajax({
                url: "{{ route('agreements.addPayment') }}",
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {ids: idsArr, vals: dataArr,contract_ids: contractIdArray,practice_ids: practiceIdArray, start_date: start_date[1], end_date: end_date[0], selected_date: start_date_v},
                success: function (data) {
                    //called when successful
                    //$('.ajax-failed').hide();
                    $(".overlay").hide();

                    $('.ajax-success').show();

                    $('html,body').animate({scrollTop: 0}, '3000');
                    setTimeout(function () {
                        $('.ajax-success').hide();
                        $('.ajax-error').hide();
                        location.reload();
                    }, 3000);
                },
                error: function (xhr, textStatus, errorThrown) {
                    $(".overlay").hide();
                    $('.ajax-failed').show();

                    $('html,body').animate({scrollTop: 0}, '3000');
                    setTimeout(function () {
                        $('.ajax-failed').hide();
                        $('.ajax-error').hide();
                    }, 5000);
                }
            });
        } else {
            $(".overlay").hide();
            return false;
        }
        /* Act on the event */

    });
    $(".amountPaid").blur(amountTotal = function (event) {

        var total = 0;
        var prevalIdAndClass= $(this).prev().val();
        var prevval= $("#"+prevalIdAndClass).val();
        //alert($(this).val());
        if(/^\-?([0-9]+(\.[0-9]+)?|Infinity)$/
                        .test($(this).val()))
        {
            $(this).next().next().next().fadeOut();
            if ($(this).val() != "" && !isNaN($(this).val())) {
                total = parseFloat($(this).val());
            }

            $(this).parent().siblings('li').each(function (index, el) {
                //alert($(this).children('input.amountPaid').val());
                if ($(this).children('input.amountPaid').val() != "" && !isNaN($(this).children('input.amountPaid').val())) {
                    total += parseFloat($(this).children('input.amountPaid').val());
                }

            });
            if (total == "" || isNaN(total)) {
                total = 0;
            }
            /*console.log("parents class",$(this).parent().parent().prev().prev().prev().children('span'));*/
            var practice_total_remain=parseFloat($(this).parent().parent().prev().prev().prev().children('span').html()) - parseFloat($("."+prevalIdAndClass).html());
            var remain=((parseFloat($("."+prevalIdAndClass).html())+parseFloat($("#"+prevalIdAndClass).val()))-parseFloat($(this).val()));
            $("."+prevalIdAndClass).html(remain);
            practice_total_remain = practice_total_remain + remain;
            $(this).parent().parent().prev().prev().children('span').html(total.toFixed(2));
            if(remain >= 0){
                $("."+prevalIdAndClass).css("color", "black");
            }else{
                $("."+prevalIdAndClass).css("color", "red");
            }
            $(this).parent().parent().prev().prev().prev().children('span').html(practice_total_remain.toFixed(2))
            $("#paymentDone"+prevalIdAndClass).val(1);
        }else{
            $(this).next().next().next().show();
            $('html,body').animate({scrollTop: $(this).next().next().next().offset().top - '50'}, '3000');
            errorShown = false;
            //$(this).val(prevval);
            $(this).focus();
            return false;
        }
    });
    $(".amountPaid").focus(amountTotal = function (event) {
        var prevval = 0;
        var prevalId= $(this).prev().val();
        //alert($(this).val());
        if(/^\-?([0-9]+(\.[0-9]+)?|Infinity)$/
                        .test($(this).val()))
        {
            if ($(this).val() != "" && !isNaN($(this).val()) && $("#paymentDone"+prevalId).val() != 0) {
                prevval = parseFloat($(this).val());
            }
            $("#"+prevalId).val(prevval);
        }else{
            $(this).next().next().next().show();
            $('html,body').animate({scrollTop: $(this).next().next().next().offset().top - '50'}, '3000');
            errorShown = false;
            //$(this).val(0);
            //$(this).focus();
            return false;
        }
    });


</script>
