{{--<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>--}}
@php use function App\Start\is_practice_manager; @endphp
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
@if (count($contracts) > 0)
    <ul style="list-style:none;padding-top:15px;">
        <?php $i = 0; ?>
        @foreach ($contracts as $contract)
            <li>
                <i class="fa fa-file-text-o"></i> <strong>{{ $contract->name }}</strong> <span style="color: #f68a1f;"><strong>{{ $contract->start_date }} - {{ $contract->end_date }}</strong></span>
                <input type="hidden" class="start_end_date_array" name="start_end_date_array[]"
                       value="{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}">
				<input type="hidden" class="contractPaymentArray pull-right" name="contractPaymentArray[]"
                       value="{{ $contract->payment_type_id }}">
                <input type="hidden" class="contractArray pull-right" name="contractArray[]"
                       value="{{ $contract->contract_type_id }}">
                <input type="hidden" class="practiceArray pull-right" name="practiceArray[]"
                       value="{{ count($contract->practices) }}">
                <input type="hidden" class="contract_name pull-right" id="contract_name_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="contract_name"
                       value="{{ $contract->name }}">
                <input type="hidden" class="contractId pull-right" name="contractId"
                       value="{{ $contract->id }}">
                <input type="hidden" class="is_shared_contract_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }} pull-right" name="is_shared_contract"
                       value="{{ $contract->is_shared_contract }}">
                <ul style="list-style:none">
                    <?php $contract_physician_count = 0; ?>
                    @foreach ($contract->practices as $practice)
                        <input type="hidden" class="contract_id pull-right" id="contract_id" name="contract_id"
                               value="{{ $contract->id }}">
                        <input type="hidden" class="practice_id_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }} pull-right" id="practice_id_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="practice_id_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                               value="{{ $practice->id }}">
                        <input type="hidden" class="practice_name_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }} pull-right" id="practice_name_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="practice_name_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                               value="{{ $practice->name }}">
                        <input type="hidden" class="practiceId pull-right" name="practiceId"
                               value="{{ $practice->id }}">

                        <?php $paymentDone = 0; $preval = 0; $final_payment = 0; $disabled = false; $checked = false ?>
                        <li>
                            <div class="col-md-12" style="margin-top:15px;"><strong>Expected Practice Total : </strong><span
                                        class="dynaminc_expected_total">${{number_format($practice->expected_practice_total,2,'.','')}}</span></div>
                            <div class="col-md-12" style="margin-top:15px;"><strong>Practice Total : </strong><span
                                        class="dynaminc_paid_total">${{number_format($practice->practice_total,2,'.','')}}</span></div>
                            <ul style="list-style:none">
                                <li style="clear: both;">
                                    <?php
                                    $practice_width = "55%" ;
                                    $physician_width = "100%";
                                    $final_payment_column_row = '10';
                                    $expected_contract_total_col_row = '12';
                                    if($contract->is_shared_contract == 0){
                                        $practice_width = "100%" ;
                                        $physician_width = "50%";
                                        $final_payment_column_row = '5';
                                        $expected_contract_total_col_row = '7';
                                    }
                                    ?>
                                    <div style="width: {{$practice_width}}; float: left;">
                                        @if(is_practice_manager())
                                            <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                                        @else
                                            <a href="{{ route('practices.show', $practice->id) }}">
                                                <i class="fa fa-hospital-o fa-fw"></i> {{ $practice->name}}
                                            </a>
                                        @endif
                                        <div class="alert alert-danger" id="invoiceDismissPractice_{{$practice->id}}_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                            <a class="close" id="invoiceErrorDismiss">&times;</a>
                                            <strong>Error!</strong> There is no invoice note present for this practice.
                                        </div>
                                        <input type="hidden" class="maxHours pull-right" name="maxHours[]" value="{{$practice->monthly_max_hours}}">
                                        <div class="alert alert-danger"
                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                            <a class="close" id="maxHoursDismiss">&times;</a>
                                            This user has reached their Maximum Paid Amount for this period. Max
                                            possible payment: <span id="physician{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}"></span>
                                        </div>

                                        <ul style="list-style:none">
                                            <?php $physician_count = 0; ?>
                                            @foreach ($practice->physicians as $index => $physician)

                                                <input type="hidden" class="physician_id_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }} pull-right" id="physician_id_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="physician_id_{{ $contract->id }}"
                                                       value="{{ $physician->id }}">
                                                <input type="hidden" class="contract_id_{{ $physician->id }} pull-right" id="contract_id_{{ $physician->id }}" name="contract_id_{{ $physician->id }}"
                                                       value="{{ $physician->contract_id }}">
                                                <input type="hidden" class="physician_name_{{$physician->id}} pull-right" id="physician_name_{{$physician->id}}" name="physician_name_{{$physician->id}}"
                                                       value="{{ $physician->name }}">
{{--                                                <input type="hidden" class="practiceId pull-right" name="practiceId"--}}
{{--                                                       value="{{ $practice->id }}">--}}
                                                <input type="hidden" class="physician_contract_id pull-right" name="contractId"
                                                       value="{{ $physician->contract_id }}">
                                                <li style="clear: both;">
                                                    <div style="width: {{$physician_width}}; float: left;">
                                                        <!-- Physician to multiple hospital by 1254 -->
                                                        <a href="{{ route('physicians.show', [$physician->id,$practice->id]) }}">
                                                            <i class="fa fa-user-md fa-fw"></i> {{ $physician->name }}
                                                            [<span id="workedHour{{$i}}">{{$physician->worked_hours}} {{$contract->payment_type_id == 4 ? "Units" : ""}}</span>]

                                                        </a>
                                                    </div>


                                                    <!--<input type="text" class="amountPaid pull-right" name="amountPaid[]"
                                                           value="" style="margin-top:5px;margin-bottom:5px;" placeholder="$">-->
                                                    <?php $paymentDone = 0; $preval = 0; $final_payment = 0; $disabled = false; $checked = false ?>
                                                    <div style="width: 50%; float: left;">
                                                        @if($contract->is_shared_contract == 0)
                                                            @foreach ($physician->amountPaid as $amount_paid)
                                                                @if($amount_paid->final_payment != 0)
                                                                    <?php $final_payment = $amount_paid->final_payment ?>
                                                                    <?php $disabled = "disabled"; ?>
                                                                    <?php $checked = "checked"; ?>
                                                                    @break
                                                                @endif
                                                            @endforeach
                                                            @foreach ($physician->amountPaid as $amount_paid)
                                                                <?php $paymentDone = 1; $preval = $preval+ $amount_paid->amountPaid; ?>
                                                                <input type="hidden" name="amountPaidID" value="{{$contract->id}}">
                                                                <input type="text" id="amountPaidId_{{$amount_paid->id}}" class="prevAmount prevAmount_{{ $physician->contract_id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="amountPaidt"
                                                                       value="${{number_format($amount_paid->amountPaid, 2,'.','')}}" style="margin-top:5px;margin-bottom:5px; width: 85%;" placeholder="$" title="Amount Paid" {{$disabled}}>
                                                                <?php /* @if($physician->remaining_amount > 0)*/ ?>
                                                                <input type="checkbox" name="invoice_print_checkbox_paid[]" id="printPaidAmount_{{$amount_paid->id}}" value="{{$amount_paid->id}}" {{$disabled}}>
                                                                <?php /*@else
                                                    <input style="display:none;" type="checkbox" name="invoice_print_checkbox_paid[]" id="printPaidAmount_{{$amount_paid->id}}" value="{{$amount_paid->id}}" checked>
                                                @endif
                                                */ ?>
                                                                <input type="hidden" class="amtPaidId_{{$physician->contract_id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" id="amtPaidId_{{$physician->contract_id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" value="{{$amount_paid->id}}">
                                                                <input type="hidden" id="invoiceNo_{{$amount_paid->id}}" value="{{$amount_paid->invoice_no}}">
                                                                <input type="hidden" id="amountPaidIdOld_{{$amount_paid->id}}" class="prevAmountOld prevAmountOld_{{ $physician->contract_id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="amountPaidOld"
                                                                       value="${{number_format($amount_paid->amountPaid, 2,'.','')}}" style="margin-top:5px;margin-bottom:5px; width: 90%;" placeholder="$" title="Amount Paid" {{$disabled}}>
                                                            @endforeach

                                                            <input type="hidden" name="amountPaidID" value="{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}">
                                                            <input type="text" class="amountPaid" name="amountPaid[]" s_date="{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" start_period="{{ $contract->start_date }}" end_period="{{ $contract->end_date }}"
                                                                   value="${{number_format($physician->remaining_amount, 2,'.','')}}" style="margin-top:5px;margin-bottom:5px;width: 85%;color:{{$physician->color}}; {{$physician->remaining_amount == 0 && count($physician->amountPaid) > 0 ? "display:none;":""}}" placeholder="$" title="Remaining Amount Owed" {{$disabled}}>
                                                            <input style="{{$physician->remaining_amount == 0 ? "display:none;":""}}" type="checkbox" name="invoice_print_checkbox_new[]" id="printNewAmount_{{ $physician->contract_id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" value="{{$physician->id}}" checked {{$disabled}}>
                                                        @endif
                                                        @if($index == 0)
                                                            <input type="hidden" class="remaining_amt_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" id="remaining_amt_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                                                   value="${{number_format($physician->remaining_amount, 2,'.','')}}">
                                                            <input type="hidden" class="remaining_amt_changed_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" id="remaining_amt_changed_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                                                   value="${{number_format($physician->remaining_amount, 2,'.','')}}">
                                                        @endif
                                                    </div>
                                                    <input type="hidden" name="prevval" value="{{$preval}}" id="{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}">
                                                    <input type="hidden" class="paymentDone" id="paymentDone{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="paymentDone" value="{{$paymentDone}}">
                                                    <div class="alert alert-danger"
                                                         style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                        <a class="close" id="paymentDismiss">&times;</a>
                                                        <strong>Error!</strong> Please Enter Valid Payment.
                                                    </div>
                                                    <input type="hidden" class="worked_hour_contract_id_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }} pull-right" name="amountPaid[]"
                                                           value="{{$physician->worked_hours}}">
                                                    <div class="alert alert-danger"
                                                         style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                        <a class="close" id="hoursDismiss">&times;</a>
                                                        Payment Not Allowed –Minimum Hours not achieved for Selected Period.
                                                    </div>
                                                    <div class="alert alert-danger" id="invoiceDismissContract_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                                         style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                        <a class="close" id="invoiceErrorDismiss">&times;</a>
                                                        <strong>Error!</strong> There is no invoice note present for this contract.
                                                    </div>
                                                    <div class="alert alert-danger" id="invoiceDismissPhysician_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                                         style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                        <a class="close" id="invoiceErrorDismiss">&times;</a>
                                                        <strong>Error!</strong> There is no invoice note present for this physician.
                                                    </div>
                                                    @if(($contract->payment_type_id)==2)
                                                        <input type="hidden" class="pull-right" name="maxAnnual_{{$physician->contract_id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" id="maxAnnual_{{$physician->contract_id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" value="{{$physician->annual_max_pay}}">
                                                        <div class="alert alert-danger"
                                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                            <a class="close" id="maxHoursDismiss">&times;</a>
                                                            This user has reached their Maximum Paid Amount for this Year. Max
                                                            possible payment: <span id="physicianAnnual_{{$physician->contract_id}}_{{ $contract->start_date }}_{{ $contract->end_date }}">{{$physician->annual_max_pay}}</span>
                                                        </div>
                                                    @endif

                                                </li>
                                                <?php $i++; ?>
                                                <?php $physician_count++; $contract_physician_count++; ?>
                                            @endforeach

                                            @if($contract->is_shared_contract == 1)
                                                <div style="clear: both;"><br/></div>
                                            @endif

                                            <div class="col-md-{{$final_payment_column_row}}" style="padding-left: 0px; color: black;"><strong>Final Payment : </strong></div>
                                            <input style="" type="checkbox" name="invoice_final_payment_checkbox_new[]" id="finalPayment_{{ $physician->contract_id }}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                                   value="{{$physician->id}}" {{$checked}} {{$disabled}}>
                                            <div style="clear: both;"></div>

                                            <div class="col-md-{{$expected_contract_total_col_row}}" style="padding-left: 0px; padding-right: 0px; color:{{$physician->remaining_amount >= 0 ? "black": "red"}};">
                                                <strong>Expected Contract Total : </strong>
                                                <span class="remaining {{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}">${{number_format($physician->remaining_amount,2,'.','')}}</span>
                                            </div>
                                            @if($contract->is_shared_contract == 0)
                                                <input type="hidden" class="expected_total_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" value="{{number_format($physician->remaining_amount + $preval,2,'.','') }}">
                                            @endif
                                            <div style="clear: both;"><br/></div>
                                        </ul>
                                    </div>
                                    @foreach ($practice->amountPaid as $amount_paid)
                                        @if($amount_paid->final_payment != 0)
                                            <?php $final_payment = $amount_paid->final_payment ?>
                                            <?php $disabled = "disabled"; ?>
                                            <?php $checked = "checked"; ?>
                                            @break
                                        @endif
                                    @endforeach
                                    @if($contract->is_shared_contract == 1)
                                        <div style="width: 45%; float: left;">

                                            @foreach ($practice->amountPaid as $amount_paid)
                                                <?php $paymentDone = 1; $preval = $preval+ $amount_paid->amountPaid; ?>
                                                {{--                                                <input type="hidden" name="phy_id" value="{{$physician->id}}">--}}
                                                <input type="text" id="amountPaidId_{{$amount_paid->id}}" class="prevAmount prevAmount_{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="amountPaidt"
                                                       value="${{number_format($amount_paid->amountPaid, 2,'.','')}}" style="margin-top:5px;margin-bottom:5px; width: 85%;" placeholder="$" title="Amount Paid" {{$disabled}}>
                                                <?php /* @if($physician->remaining_amount > 0)*/ ?>
                                                <input type="checkbox" name="invoice_print_checkbox_paid[]" id="printPaidAmount_{{$amount_paid->id}}" value="{{$amount_paid->id}}" {{$disabled}}>
                                                <?php /*@else
                                                <input style="display:none;" type="checkbox" name="invoice_print_checkbox_paid[]" id="printPaidAmount_{{$amount_paid->id}}" value="{{$amount_paid->id}}" checked>
                                            @endif
                                            */ ?>
                                                <input type="hidden" class="amtPaidId_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" id="amtPaidId_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" value="{{$amount_paid->id}}">
                                                <input type="hidden" id="invoiceNo_{{$amount_paid->id}}" value="{{$amount_paid->invoice_no}}">
                                                <input type="hidden" id="amountPaidIdOld_{{$amount_paid->id}}" class="prevAmountOld prevAmountOld_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="amountPaidOld"
                                                       value="${{number_format($amount_paid->amountPaid, 2,'.','')}}" style="margin-top:5px;margin-bottom:5px; width: 90%;" placeholder="$" title="Amount Paid" {{$disabled}}>
                                            @endforeach

                                            <input type="hidden" name="amountPaidID" value="{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}">
                                            <input type="text" class="amountPaid" name="amountPaid[]" s_date="{{ $contract->id }}_{{ $contract->start_date }}_{{ $contract->end_date }}" start_period="{{ $contract->start_date }}" end_period="{{ $contract->end_date }}"
                                                   value="${{number_format($practice->remaining_amount, 2,'.','')}}" style="margin-top:5px;margin-bottom:5px;width: 85%;color:{{$practice->color}}; {{$practice->remaining_amount == 0 && count($practice->amountPaid) > 0 ? "display:none;":""}}" placeholder="$" title="Remaining Amount Owed" >
                                            <input style="{{$practice->remaining_amount == 0 ? "display:none;":""}}" type="checkbox" name="invoice_print_checkbox_new[]" id="printNewAmount_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}"
                                                   value="{{$contract->id}}" checked {{$disabled}}>
                                        </div>
                                        <input type="hidden" name="prevval" value="{{$preval}}" id="{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}">
                                        <input type="hidden" class="paymentDone" id="paymentDone{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" name="paymentDone" value="{{$paymentDone}}">
                                        <input type="hidden" class="expected_total_{{$contract->id}}_{{ $contract->start_date }}_{{ $contract->end_date }}" value="{{number_format($practice->remaining_amount + $preval,2,'.','') }}">
                                        <div class="alert alert-danger"
                                             style="display:none;padding:5px;clear:both;margin-top:10px;">
                                            <a class="close" id="paymentDismiss">&times;</a>
                                            <strong>Error!</strong> Please Enter Valid Payment.
                                        </div>
                                    @endif



                                </li>

                            </ul>
                        </li>
                        <div style="clear: both;"><br/></div>
                    @endforeach
                </ul>
                <input type="hidden" class="physicianCount pull-right" name="physicianCount[]"
                       value="{{ $contract_physician_count }}">
            </li>
        @endforeach
    </ul>
    <input type="hidden" id="previnputval" name="previnputval"  value="0">
    <div class="form-group">
        <div class="col-xs-5 control-label">
            <label>Print combine invoice :</label>
        </div>
        <div class="col-xs-1">
            {{ Form::checkbox('print_all_invoice','0',false ,['class'=>'print_all_invoice', 'id'=>'print_all_invoice']) }}
        </div>
        <div class="col-xs-6" style="float: right;">
            <button type="button" id="submit_pmt" class="btn btn-primary actionBtn" style="float: right;margin-top:0px !important;">Submit
                Payment
            </button>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="invoicePreview" tabindex="-1" role="dialog" aria-labelledby="invoicePreviewlLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: #DCDCDC;">
                    <h5 class="modal-title" id="invoicePreviewlLabel">Invoice Details</h5>
                    <span> <i class="bi bi-square-fill" style='color: #FFFFFF; margin-right: 3px;'></i> No Change</span>
                    <span style='margin-left: 3px;'> <i class="bi bi-square-fill" style='color: #F3DC0D; margin-right: 3px;'></i> Modified</span>
                    <span style='margin-left: 3px;'> <i class="bi bi-square-fill" style='color: #04AA6D; margin-right: 3px;'></i>  New</span>
                </div>
                <div class="modal-body">
                    No preview is available
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: #d43f3a">Cancel</button>
                    <button type="button" id="ajax_submit" class="btn btn-primary" style="background: #f68a1f">Confirm</button>
                </div>
            </div>
        </div>
    </div>
@else
    <p>There are currently no contracts available for display.</p>
@endif

<style>
    #invoice_preview td, #invoice_preview th {
        border: 1px solid #ddd;
        padding: 8px;
    }

    #invoice_preview th {
        text-align: left;
        background-color: #000000;
        color: white;
    }
    #invoice_preview td {
        text-align: left;
        font-weight: bold;
    }
</style>

<script>
    function fetch_selected_data(id){
        var agrrement_id =$("#agreement_id").val();
        var practice_id =$("#practice_id").val();
        var type_id =$("#payment_type_id").val();
        var contract_type_id =$("#contract_type_id").val();
        var physician_id =$("#physician_id").val();
        // var monthNumber =$("#start_date").val();
        var start_date = $("#start_date").val();
        var end_date = $("#end_date").val();

        var redirectURL = "?p_id=" + practice_id + "&t_id=" + type_id + "&ct_id=" + contract_type_id + "&phy_id=" + physician_id + "&start_date=" + start_date + "&end_date=" + end_date;

        //window.location.href = redirectURL;
        getDataforselected(redirectURL);
    }
    function getAmount(amounttobepaid)
    {
      if(amounttobepaid != undefined){
          amounttobepaid=amounttobepaid.replace("$","");
          //amounttobepaid=amounttobepaid.replace("-Amount to be paid","");
          //amounttobepaid=amounttobepaid.replace("-Amount paid","");
          amounttobepaid=amounttobepaid.trim();
          return amounttobepaid;
      }
    }
    $(document).ready(fetch_data = function () {
        //var contract_type_id = $("#contractTypeID").val();
        $(".contractArray").each(function () {
            console.log("contract_type_id : ", $(this).val());
        });
        $(".practiceArray").each(function () {
            console.log("practiceCount : ", $(this).val());
        });

        $("#hoursDismiss, #paymentDismiss, #maxHoursDismiss, #invoiceErrorDismiss").click(function () {
            $(this).parent().css('display', 'none');
        });
        $("#hoursDismiss, #paymentDismiss, #maxHoursDismiss").click();

        //alert("hi");
        var check_payment_url = 1;
        var start_date_a = $('#start_date option:selected').text();
        if (check_payment_url == 1) {     
            var date_all = start_date_a.split(" - ");
            //var start_date = date_all[0].split(": ");     // Vishvajeet
            // var end_date = date_all[1].split(": ");      // Vishvajeet
            var idsArr = new Array();
            var contract_idsArr = new Array();
            var practice_idsArr = new Array();
            $(".amountPaid").each(function () {
                idsArr.push($(this).prev().val());//physician array
                contract_idsArr.push($(this).parent().prev().val()); // contract id array
                practice_idsArr.push($(this).parent().prev().prev().val()); // practice id array
            });

        }
    });
    //alert(end_date);
    //remove on 30oct2018
    //    $('#start_date').change(function () {
    //        fetch_data();
    //    })
    $('#apply_filter').click(function () {
        fetch_selected_data($(this).attr('id'));
    })

    $("#submit_pmt").click(function (event) {
        var start_end_date = "";
        
        var agreement_name =  $('#agreement_id option:selected').text();
        var period =  $('#start_date option:selected').text();
        period = period.split(":").pop();
        var table_main = "";

        if($("#print_all_invoice").prop('checked') == true){
            print_all_invoice = "Yes";
        } else{
            print_all_invoice = "No";
        }

        $(".start_end_date_array").each(function(){
            start_end_date = $(this).val();

            var physician_name = "";

            $(".physician_id_"+start_end_date).each(function (key, value) {
                var physician_id = $(this).val();
                if(key > 0){
                    physician_name += ', ';
                }
                physician_name += $("#physician_name_"+physician_id).val();
            });

            var table = "";
            var table_data_present = false;

            var contract_name = $("#contract_name_" + start_end_date).val();
            var start_end_date_array = start_end_date.split("_");
            table += '<br/><div style="text-align: center; color: #ffa500;">' + start_end_date_array[1].replace(/[ -]+/g, "/") + ' - ' + start_end_date_array[2].replace(/[ -]+/g, "/") + '</div>'

            table += '<br/><table id="invoice_preview" style="width:100%">' +
                '<thead>' +
                '<tr>' +
                '<th><span>Physician Name</span> </th>' +
                '<th><span>Practice Name </span></th>' +
                '<th><span>Contract Name </span></th>' +
                '<th><span>Invoice No </span></th>' +
                '<th><span> Paid Amount </span></th>' +
                '<th><span> New Amount </span></th>' +
                '<th><span> Print Invoice </span></th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>';

            var physician_id = $(this).val();
            // var contract_id = $("#contract_id_"+ physician_id + "_" + practice_id + "_" + start_end_date).val();
            // var physician_name = $("#physician_name_" + start_end_date).val();
            var final_pmt_done = $("#finalPayment_" + start_end_date).prop('disabled');
            var practice_name = $("#practice_name_" + start_end_date).val();

            var invoice_no = '-';

            $(".amtPaidId_"+ start_end_date).each(function () {
                debugger;
                var amt_paid_id = $(this).val();

                invoice_no = $("#invoiceNo_"+amt_paid_id).val();
                var new_amount_paid = $("#amountPaidId_"+amt_paid_id).val();
                var amount_paid = $("#amountPaidIdOld_"+amt_paid_id).val();
                print_old_invoice = $("#printPaidAmount_"+amt_paid_id).prop('checked');
                if(print_old_invoice == true){
                    print_old_invoice = "Yes";
                } else {
                    print_old_invoice = "No";
                }

                if(!final_pmt_done){
                    table_data_present = true;
                    if(amount_paid != new_amount_paid){
                        table += '<tr style="background-color: #F3DC0D;">';
                    } else{
                        table += '<tr style="background-color: #FFFFFF;">';
                    }
                    // table += '<tr style="background-color: bg_color;">';
                    table += '<td><span>' + physician_name + '</span></td>';
                    table += '<td><span>' + practice_name + '</span></td>';
                    table += '<td><span>' + contract_name + '</span></td>';
                    table += '<td><span>' + invoice_no + '</span></td>';
                    table += '<td><span>' + amount_paid + '</span></td>';
                    table += '<td><span>' + new_amount_paid + '</span></td>';
                    table += '<td><span>' + print_old_invoice + '</span></td>';
                    table += '</tr>';


                }
            });

            var owed_amount = $(".remaining_amt_" + start_end_date).val();
            var remaining_amount = $(".remaining_amt_changed_" + start_end_date).val();
            var print_invoice_new = $("#printNewAmount_"+start_end_date).prop('checked');
            // var is_final_pmt = $("#finalPayment_"+start_end_date).prop('checked');
            var prevAmt = '-';

            if(print_invoice_new){
                print_invoice_new = "Yes";
            } else {
                print_invoice_new = "No";
            }

            var check_amt = owed_amount.replace('$','');
            var check_amt = parseFloat(check_amt);
            if(check_amt > 0 && !final_pmt_done){
                table_data_present = true;
                table += '<tr style="background-color: #04AA6D;">';
                table += '<td><span>' + physician_name + '</span></td>';
                table += '<td><span>' + practice_name + '</span></td>';
                table += '<td><span>' + contract_name + '</span></td>';
                table += '<td><span>' + invoice_no + '</span></td>';
                table += '<td><span>' + prevAmt + '</span></td>';
                table += '<td><span>' + remaining_amount + '</span></td>';
                table += '<td><span>' + print_invoice_new + '</span></td>';
                table += '</tr>';
                table += '</tbody></table>';
            }else{
                table += '</tbody></table>';
            }

            if(!table_data_present){
                table = '';
            } else {
                table_main += table;
            }
        });

        $("#invoicePreviewlLabel").html('<h5 class="modal-title" id="invoicePreviewlLabel">Invoice Details :' + '<br/>' + 'Print Combined Invoice : '+ print_all_invoice +'</h5>');

        $(".modal-body").html(table_main);
        $('#invoicePreview').modal('show');
    })

    $("#ajax_submit").click(function (event) {
        debugger;
        $('#invoicePreview').modal('hide');
        $(".overlay").show();
        $("#hoursDismiss, #paymentDismiss, #maxHoursDismiss").click();
        var error = 0;
        var dataArr = new Array();
        var idsArr = new Array();
        var start_date_v = $('#start_date option:selected').val();

        //var scrollVal  =5000;
        var errorShown = true;
        var amountPaidArray = new Array();
        var workedHoursArray = new Array();
        var contractArray = new Array();
        var contractPaymentArray = new Array();
        var tempContractIdArray = new Array();
        var contractIdArray = new Array();
        var tempPracticeIdArray = new Array();
        var practiceIdArray = new Array();
        var practiceArray = new Array();
        var maxHoursArray = new Array();
        var physicianCountArray = new Array();
        var prevAmountArray = new Array();
        var printNewArray = new Array();
        var finalPaymentArray = new Array();

        var start_date_arr = new Array();
        var end_date_arr = new Array();

        

        // $(".amountPaid").each(function () {
        //     amountPaidArray.push(getAmount($(this).val()));//remove $ sign & other wording to get amount value
        // });
        $(".prevAmount").each(function(){
            var paidAttrId=$(this).attr('id').split('_');
            var paidId=paidAttrId[1];
            var print = false;
            var start_period = $(this).attr('start_period');
            var end_period = $(this).attr('end_period');
            print = $('#printPaidAmount_'+paidId+':checkbox:checked').length > 0;
            console.log('paid paidAttrId'+paidAttrId);
            console.log('paid ID'+paidId);
            prevAmountArray.push({id:paidId,amount:getAmount($(this).val()),print:print, startPeriod:start_period, endPeriod:end_period});

        });

        $(".workedHours").each(function () {
            workedHoursArray.push($(this).val());
        });
        $(".contractPaymentArray").each(function () {
            contractPaymentArray.push($(this).val());//payment type id array
        });
        $(".contractArray").each(function () {
            contractArray.push($(this).val());
        });
        $(".contractId").each(function () {
            tempContractIdArray.push($(this).val());
        });
        $(".practiceId").each(function () {
            tempPracticeIdArray.push($(this).val());
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
        var amt_count =0;
        debugger;
        $(".amountPaid").each(function () {
            var start_period = $(this).attr('start_period');
            var end_period = $(this).attr('end_period');

            var finalpayment = $('#finalPayment_'+tempContractIdArray[amt_count]+'_'+start_period+'_'+end_period+':checkbox:checked').length > 0;
            var finalpayment_disabled = $('#finalPayment_'+tempContractIdArray[amt_count]+'_'+start_period+'_'+end_period+':checkbox:disabled').length > 0;
            
            if(finalpayment_disabled == false){
                 amountPaidArray.push(getAmount($(this).val()));
            
                var print = $('#printNewAmount_'+tempContractIdArray[amt_count]+'_'+start_period+'_'+end_period+':checkbox:checked').length > 0;

                if ((getAmount($(this).val()) != "" && !$.isNumeric(getAmount($(this).val()))) || parseInt(getAmount($(this).val())) < 0) {
                    $(this).parent().next().next().next().show();
                    error++;
                    //$('html,body').animate({scrollTop:450}, '3000');
                    $('html,body').animate({scrollTop: $(this).parent().next().next().next().offset().top - '50'}, '3000');
                    errorShown = false;
                    $(".overlay").hide();
                    return false;
                }
                else if (getAmount($(this).val()) === "" || parseFloat(getAmount($(this).val())) == 0) {
                    $(this).parent().next().hide();
                    if(parseFloat(getAmount($("."+$(this).prev().val()).html())) > 0) {
                        idsArr.push($(this).prev().val());
                        dataArr.push(0);
                        contractIdArray.push(tempContractIdArray[amt_count]);
                        practiceIdArray.push(tempPracticeIdArray[amt_count]);
                        printNewArray.push(print);
                        finalPaymentArray.push(finalpayment);
                        start_date_arr.push(start_period);
                        end_date_arr.push(end_period);
                    }
                    /*contractIdArray.push($("#contractId["+id+"]").val());*/
                } else {
                    $(this).parent().next().hide();
                    /*var id=$(this).prev().val();
                    var cid=$("#contractId["+id+"]").val();*/
                    idsArr.push($(this).prev().val());
                    dataArr.push(getAmount($(this).val()));
                    contractIdArray.push(tempContractIdArray[amt_count]);
                    practiceIdArray.push(tempPracticeIdArray[amt_count]);
                    printNewArray.push(print);
                    finalPaymentArray.push(finalpayment);
                    /*contractIdArray.push();*/
                    start_date_arr.push(start_period);
                    end_date_arr.push(end_period);
                }
            }
            amt_count++;
        });
        //console.log("errorShown : ",errorShown);

        if (errorShown) {
            var count = 0;
            var errorFlag = true;
            var contractFlag = 0;
            var practiceFlag = 0;

            $(".start_end_date_array").each(function (index) {
                var contract_start_end_date = $(this).val();

                var cont_worked_count = $(".worked_hour_contract_id_"+contract_start_end_date).length;
                var paymentType = parseInt(contractPaymentArray[index]);
                var paymentErrorFlag = false;
                if (amountPaidArray[index] === '' || amountPaidArray[index] === undefined) {
                    paymentValue = 0;
                } else {
                    var paymentValue = amountPaidArray[index];
                }

                // This condition checks for worked hours not 0 for all payment type except monthly stipend.
                $(".worked_hour_contract_id_"+contract_start_end_date).each(function (worked_index, worked_val) {
                    var tempWorkedHour = $(this).val();
                    if (tempWorkedHour === '0' && paymentValue != 0 && paymentType != 1) {
                        if(cont_worked_count == (worked_index+1)){
                            paymentErrorFlag = true;
                            $(this).next().show();
                            error++;
                            if (errorFlag) {
                                $('html,body').animate({scrollTop: $(this).next().offset().top - '50'}, '3000');
                                errorFlag = false;
                            }
                            errorShown = true;
                        } else {
                            paymentErrorFlag = false;
                            errorFlag = true;
                            errorShown = true;
                        }
                    } else {
                        return false;
                    }
                });

                // This condition checks for annual max pay and monthly max hours for hourly payment type only.
                if (!paymentErrorFlag && paymentType == 2) {

                    if (parseFloat(paymentValue) > parseFloat($('#maxAnnual_'+contract_start_end_date).val()) && paymentValue != 0) {
                        //var errorMessage = "This user has reached their Maximum Paid Amount for this period. Max hours paid: "+maxHoursArray[count];
                        var physician = "#physicianAnnual_" + contract_start_end_date;
                        $(physician).html("$" + $('#maxAnnual_'+contract_start_end_date).val().toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
                        $('#maxAnnual_'+contract_start_end_date).next().show();
                        error++;
                        if (errorFlag) {
                            $('html,body').animate({scrollTop: $(this).next().next().next().offset().top - '50'}, '3000');
                            errorFlag = false;
                        }
                        errorShown = true;
                    }else if (parseFloat(paymentValue) > parseFloat(maxHoursArray[index]) && paymentValue != 0) {
                        //var errorMessage = "This user has reached their Maximum Paid Amount for this period. Max hours paid: "+maxHoursArray[count];
                        var physician = "#physician" + contract_start_end_date;
                        $(physician).html("$" + maxHoursArray[index].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","));
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
            });
        }
        console.log('prevAmountArray'+prevAmountArray);
        console.log('amountPaidArray'+amountPaidArray);
        hospitalId = <?php echo $hospital->id; ?>;
        var timeZone = formatAMPM(new Date());
        var zoneName = Intl.DateTimeFormat().resolvedOptions().timeZone;

        var print_all_invoice_flag = false;
        if($("#print_all_invoice").prop('checked') == true){
            print_all_invoice_flag = true;
        } else{
            print_all_invoice_flag = false;
        }

        if (error == 0) {
            $.ajax({
                url: "{{ route('agreements.addPaymentAll') }}",
                type: 'POST',
                headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                data: {ids: idsArr, vals: dataArr,contract_ids: contractIdArray,practice_ids: practiceIdArray, start_date: start_date_arr, end_date: end_date_arr, selected_date: start_date_v, prev_values:prevAmountArray,printNew:printNewArray, hospital_id:hospitalId, finalPayment:finalPaymentArray, timestamp:timeZone,timeZone:zoneName, print_all_invoice_flag: print_all_invoice_flag },
                success: function (data) {
                    //called when successful
                    //$('.ajax-failed').hide();
                    $(".overlay").hide();
                    if(data.status == true){

                        if(data.errorCount == 0){
                            $('.ajax-success').show();
                            $('html,body').animate({scrollTop: 0}, '3000');
                            setTimeout(function () {
                                $('.ajax-success').hide();
                                $('.ajax-error').hide();
                                //location.reload();
                                window.location='{{ URL::route('agreements.payment', $hospital->id) }}';
                            }, 3000);
                        } else {
                            $('.ajax-success-with-validation').show();
                            if(Object.keys(data.data.physician_err).length > 0){
                                Object.entries(data.data.physician_err).forEach(([key, value]) => {
                                    $('#invoiceDismissPhysician_'+key).css("display", "block");
                                })

                            }
                            if(Object.keys(data.data.contract_err).length > 0){
                                Object.entries(data.data.contract_err).forEach(([key, value]) => {
                                    $('#invoiceDismissContract_'+key).css("display", "block");
                                })
                            }
                            if(Object.keys(data.data.practice_err).length > 0){
                                Object.entries(data.data.practice_err).forEach(([key, value]) => {
                                    $('#invoiceDismissPractice_'+key).css("display", "block");
                                })
                            }
                        }
                        $('html,body').animate({scrollTop: 0}, '1000');
                        $('#ajax_submit').css("background","#f68a1f");
                        $('#ajax_submit').css("border","#f68a1f");
                        $('#ajax_submit').prop("disabled",true);
                    } else if(data.status == false){
                        // console.log(data.data);
                        if(Object.keys(data.data.hospital_err).length > 0){
                            Object.entries(data.data.hospital_err).forEach(([key, value]) => {
                                $('#invoiceDismissHospital').css("display", "block");
                            })

                        }
                        if(Object.keys(data.data.physician_err).length > 0){
                            Object.entries(data.data.physician_err).forEach(([key, value]) => {
                                $('#invoiceDismissPhysician_'+key).css("display", "block");
                            })

                        }
                        if(Object.keys(data.data.contract_err).length > 0){
                            Object.entries(data.data.contract_err).forEach(([key, value]) => {
                                $('#invoiceDismissContract_'+key).css("display", "block");
                            })
                        }
                        if(Object.keys(data.data.practice_err).length > 0){
                            Object.entries(data.data.practice_err).forEach(([key, value]) => {
                                $('#invoiceDismissPractice_'+key).css("display", "block");
                            })
                        }
                        $('html,body').animate({scrollTop: 0}, '1000');
                    } else {
                        // $('.ajax-success').show();

                        // $('html,body').animate({scrollTop: 0}, '3000');
                        // setTimeout(function () {
                        //     $('.ajax-success').hide();
                        //     $('.ajax-error').hide();
                        //     //location.reload();
                        //     window.location='{{ URL::route('agreements.payment', $hospital->id) }}';
                        // }, 3000);

                        $(".overlay").hide();
                        $('.ajax-failed').show();

                        $('html,body').animate({scrollTop: 0}, '3000');
                        setTimeout(function () {
                            $('.ajax-failed').hide();
                            $('.ajax-error').hide();
                        }, 5000);
                    }
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
    function formatAMPM(date) {
                    var month = date.getMonth()+1;
                    var day = date.getDate();
                    var year = date.getFullYear();
                    var hours = date.getHours();
                    var minutes = date.getMinutes();
                    var ampm = hours >= 12 ? 'PM' : 'AM';
                    month = month > 9 ? month : '0'+month;
                    day = day > 9 ? day : '0'+day;
                    hours = hours % 12;
                    hours = hours ? hours : 12; // the hour '0' should be '12'
                    minutes = minutes < 10 ? '0'+minutes : minutes;
                    var strTime = month+'/'+day+'/'+year+' '+hours + ':' + minutes + ' ' + ampm;
                    return strTime;
    }
    $(".amountPaid , .prevAmount").blur(amountTotal = function (event) {
debugger;
        var total = 0;
        var substract_total = 0;
        var prevalIdAndClass= $(this).prev().val();//physician id
        var contract_start_end_date = $(this).attr('s_date');
        var prevval= $("#"+contract_start_end_date).val();
        //alert($(this).val());
        if(/^\-?([0-9]+(\.[0-9]+)?|Infinity)$/
                        .test(getAmount($(this).val())))
        {
            $(this).parent().next().next().next().fadeOut();
            if (getAmount($(this).val()) != "" && !isNaN(getAmount($(this).val()))) {
                total = parseFloat(getAmount($(this).val()));
            }

            if($(this).hasClass('prevAmount')){
                if (getAmount($(this).parent().children('input.amountPaid').val()) != "" && !isNaN(getAmount($(this).parent().children('input.amountPaid').val()))) {
                    total += parseFloat(getAmount($(this).parent().children('input.amountPaid').val()));
                }
                console.log('1 0==' + total);
                // $('.prevAmount_' + $(this).prev().val() + '_' + $(this).parent().parent().prev().val()).each(function (index, el) {
                $('.prevAmount_' + contract_start_end_date).each(function (index, el) {
                    if (getAmount($(this).val()) != "" && !isNaN(getAmount($(this).val()))) {
                        total += parseFloat(getAmount($(this).val()));
                    }
                    console.log('1==' + total);
                });
                total = total - parseFloat(getAmount($(this).val()));
            }else {
                // $('.prevAmount_' + $(this).prev().val() + '_' + $(this).parent().parent().prev().val()).each(function (index, el) {
                $('.prevAmount_' + contract_start_end_date).each(function (index, el) {
                    if (getAmount($(this).val()) != "" && !isNaN(getAmount($(this).val()))) {
                        total += parseFloat(getAmount($(this).val()));
                    }
                    console.log('1==' + total);
                });
            }
            substract_total = total; // Sum of all previous amount paid.

            //Each contract iteration for calculating total using previous and to be paid amount.
            $(this).parent().parent().siblings('li').each(function (index, el) {
                $(this).children('div').children('input.prevAmount').each(function (index, el) {
                    if (getAmount($(this).val()) != "" && !isNaN(getAmount($(this).val()))) {
                        total += parseFloat(getAmount($(this).val()));
                    }
                });
                console.log('2'+total);
                console.log($(this).children('div').children('input.amountPaid').val());
                if (getAmount($(this).children('div').children('input.amountPaid').val()) != "" && !isNaN(getAmount($(this).children('div').children('input.amountPaid').val()))) {
                    total += parseFloat(getAmount($(this).children('div').children('input.amountPaid').val()));
                }
                console.log(total);
            });
            if (total == "" || isNaN(total)) {
                total = 0;
            }

            // test1 = getAmount($(this).parent().parent().parent().prev().prev().children('span').html());
            // test2 = getAmount($("."+contract_start_end_date).html());
            //Expected practice total - expected physician total.
            if($(".is_shared_contract_"+contract_start_end_date).val() == '1'){
                //For shared contract
                var practice_total_remain=parseFloat(getAmount($(this).parent().parent().parent().prev().prev().children('span').html())) - parseFloat(getAmount($("."+contract_start_end_date).html()));
            }else{
                //For non-shared contract
                var test1 = getAmount($("."+contract_start_end_date).html());
                var test2 = getAmount($(this).parent().parent().parent().parent().parent().parent().prev().prev().children('span').html());
                var practice_total_remain=parseFloat(getAmount($(this).parent().parent().parent().parent().parent().parent().prev().prev().children('span').html())) - parseFloat(getAmount($("."+contract_start_end_date).html()));
            }

            var remain= parseFloat($(".expected_total_"+contract_start_end_date).val())-parseFloat(substract_total);
            practice_total_remain = practice_total_remain + remain;
            $("."+contract_start_end_date).html('$'+remain.toFixed(2));
            //Setting up the practice total.
            if($(".is_shared_contract_"+contract_start_end_date).val() == '1'){
                //For shared contract
                $(this).parent().parent().parent().prev().children('span').html('$'+total.toFixed(2));
            } else {
                //For non-shared contract
                $(this).parent().parent().parent().parent().parent().parent().prev().children('span').html('$'+total.toFixed(2));
            }
            if(remain >= 0){
                $("."+contract_start_end_date).css("color", "black");
                $(this).css("color", "black");
            }else{
                $("."+contract_start_end_date).css("color", "red");
                $(this).css("color", "red");
            }
            //Setting up the expected practice total
            if($(".is_shared_contract_"+contract_start_end_date).val() == '1'){
                //For shared contract
                $(this).parent().parent().parent().prev().prev().children('span').html('$'+practice_total_remain.toFixed(2));
            }else{
                //For non-shared contract
                $(this).parent().parent().parent().parent().parent().parent().prev().prev().children('span').html('$'+practice_total_remain.toFixed(2));
            }
            $("#paymentDone"+contract_start_end_date).val(1);

            if($(this).hasClass('prevAmount')){
                // var current_element_id = $(this).attr('id');
                // var amt_paid_id = current_element_id.split("amountPaidId_").pop();
                // $("#amountPaidIdChanged_"+amt_paid_id).val($(this).val());
            }else {
                var physician_id = $(this).prev().val();//physician_id
                var practice_id = $(this).attr('practice_id');
                var start_period = $(this).attr('start_period');
                var end_period = $(this).attr('end_period');
                $("#remaining_amt_changed_" + contract_start_end_date).val($(this).val());
            }
        }else{
            $(this).parent().next().next().next().show();
            $('html,body').animate({scrollTop: $(this).parent().next().next().next().offset().top - '50'}, '3000');
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
                        .test(getAmount($(this).val())))
        {
            if (getAmount($(this).val()) != "" && !isNaN(getAmount($(this).val())) && $("#paymentDone"+prevalId).val() != 0) {
               prevval = parseFloat(getAmount($(this).val()));
            }
            //$("#"+prevalId).val(prevval);
            $("#previnputval").val(prevval);
        }else{
            $(this).parent().next().next().next().show();//display error
            $('html,body').animate({scrollTop: $(this).parent().next().next().next().offset().top - '50'}, '3000');
            errorShown = false;
            //$(this).val(0);
            //$(this).focus();
            return false;
        }
    });
</script>