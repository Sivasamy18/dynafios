

@foreach ($level_two as $key => $level_two_obj)
<div class="card-body">
    <div id="accordion">
        <div class="card">
            <div class="card-header" id="heading-{{$level_two_obj->contract_type_id}}-{{$level_two_obj->physician_id}}">
                <h5 class="mb-0">
                    
                    <div class="row" style="padding:0px">
                   
                        <div class="col-xs-11" style="padding:0px 0px 0px 17px;" id="level-two">
                        <div class="collapse-level-two-circle"></div>
                            <a onClick="getDataForPhysicianType(0, {{$level_two_obj->payment_type_id}}, {{$level_two_obj->contract_type_id}}, {{$level_two_obj->contract_id}}, {{$level_two_obj->contract_name_id}}, {{ json_encode($level_two_obj->error_logs)}})" class="collapsed" role="button" data-toggle="collapse" href="#collapse-{{$level_two_obj->contract_id}}-{{$level_two_obj->payment_type_id}}" aria-expanded="false" aria-controls="collapse-{{$level_two_obj->contract_type_id}}-{{$level_two_obj->contract_id}}" style="width:100%; background:#8e8174; margin: 0px 0px 0px 0px !important; padding:0px; line-height: 40px;border:none; height: 60px;-webkit-box-shadow: 0 0 0 0 !important;">
                            <table style="width:100%;margin-top: 15px;">
                                    <tr>
                                        <!-- <th width="20" style="background:#d1cac3;"></th> -->
                                        <th width="75" class="level-two-physician-heading">
                                            <span title="{{ array_key_exists($level_two_obj->practice_id, $practices) ? $practices[$level_two_obj->practice_id] : "NA" }}" class="agreementHeading1">{{ array_key_exists($level_two_obj->practice_id, $practices) ? $practices[$level_two_obj->practice_id] : "NA" }}</span>
                                        </th>
                                        <th width="70" class="level-two-heading" >
                                            <span title="{{$level_two_obj->period_min}} - {{$level_two_obj->period_max}}" class="agreementHeading1">{{$level_two_obj->period_min}} - {{$level_two_obj->period_max}}</span>
                                        </th>
                                        <th width="80"  class="level-two-heading">
                                            <span title="{{ $level_two_obj->name }} ({{ array_key_exists($level_two_obj->practice_id, $practices) ? $practices[$level_two_obj->practice_id] : "NA" }})" class="agreementHeading1">{{ $level_two_obj->name }}</span>
                                        </th>
                                        <th width="95"  class="level-two-heading">
                                            <span title="Hours/Units to Approve: {{ ($level_two_obj->hours_to_approve != 0.00) ? $level_two_obj->hours_to_approve : 'NA' }}" class="agreementHeading1">Hrs/Units Approving: <span class = "approve-level-two-value" >{{ ($level_two_obj->hours_to_approve != 0.00) ? $level_two_obj->hours_to_approve : 'NA' }}<span> </span>
                                        </th>
                                        <th width="70"  class="level-two-heading">
                                            <span title="Expected Hours/Units: {{ ($level_two_obj->expected_hours != 0.00) ? $level_two_obj->expected_hours : 'NA' }}" class="agreementHeading1">EXP Hrs/Units: <span class = "approve-level-two-value">{{ ($level_two_obj->expected_hours != 0.00) ? $level_two_obj->expected_hours : 'NA' }}</span></span>
                                        </th>
                                        <th width="85"  class="level-two-heading">
                                            <!-- <span title="Calculated Payments: {{ ($level_two_obj->calculated_payment != 0.00) ? '$'.$level_two_obj->calculated_payment : 'NA' }}" class="agreementHeading1">CALC PMT: <span class = "approve-level-two-value">{{ ($level_two_obj->calculated_payment != 0.00) ? '$'.$level_two_obj->calculated_payment : 'NA' }}</span></span> -->
                                            <span title="Calculated Payments: {{ $level_two_obj->calculated_payment }}" class="agreementHeading1">CALC PMT: <span class = "approve-level-two-value">{{ $level_two_obj->calculated_payment }}</span></span>
                                        </th>
                                        <th width="65"  class="level-two-heading">
                                            <!-- <span title="Expected Payments: {{ ($level_two_obj->expected_payment != 0.00) ? '$'.$level_two_obj->expected_payment : 'NA' }}" class="agreementHeading1">EXP PMT: <span class = "approve-level-two-value">{{ ($level_two_obj->expected_payment != 0.00) ? '$'.$level_two_obj->expected_payment : 'NA' }}</span></span> -->
                                            <span title="Expected Payments: {{ $level_two_obj->expected_payment }}" class="agreementHeading1">EXP PMT: <span class = "approve-level-two-value">{{ $level_two_obj->expected_payment }}</span></span>
                                        </th>
                                        <th width="80" style="background:#8e8174;color:#333;padding: 3px 10px 3px 10px !important;text-align:center">
                                            @if($level_two_obj->contract_document != 'NA')
                                            <div class="" style="float:left;">
                                            @foreach ($level_two_obj->contract_document as $document)
                                              <img class="agreement_image" onClick="location.href='{{ URL::route('contract.document',  $document->filename) }}'" src="../assets/img/default/copyOfContract.png" alt="Copy of Contract"/>
                                            @endforeach
                                            </div>
                                            @endif
                                        </th>
                                    </tr>
                                  
                            </table>
                            @if($level_two_obj->allow_max_hours != '0')
                                <div class="contractInfo" style="margin: -19px 5px 0 0 !important;">
                                    <img class="img-responsive" src="../assets/img/default/contractInfoIcon.png" alt="">
                                    <div class="showContractInfo" style="background-color: #FFFFFF !important">

                                        <div class="panel panel-default">
                                            <div class="panel-heading">{{ $level_two_obj->name }}</div>
                                        </div>
                                        <div class="panel-body">
                                            <table class="table" style="font-size: 12px;">
                                                <tr style='background: #FFFFFF !important;'>
                                                    <th width="90" style='background: #FFFFFF !important; color: #212121'>Max Hours:</th>
                                                    <th width="10" style='background: #FFFFFF !important; color: #212121'>{{ $level_two_obj->max_hours }}</th>
                                                </tr>
                                                <tr style='background: #FFFFFF !important;'>
                                                    <th width="90" style='background: #FFFFFF !important;color: #212121;'>Annual Max Hours:</th>
                                                    <th width="10" style='background: #FFFFFF !important;color: #212121'>{{ $level_two_obj->annual_max_hours }}</th>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            </a>
                        </div>
                        <!-- <div class="col-xs-1" style="background:#8e8174;padding-left:0px;padding-right:0px;border-left: 1px solid #b8b8b8;padding-top: 10px;border-bottom: 1px solid #efefef;height: 61px;"> -->
                        <div class="col-xs-1 input-level-one-checkbox" >
                            <table style="line-height:10px;margin-left:6px">
                                <tr style="background:#8e8174;">
                                    <td style="text-align: center;">
                                    <input type="checkbox" class="approve-checkbox" onChange="selectLogLevelTwo({{$level_two_obj->contract_type_id}},{{$level_two_obj->contract_id}}, {{$level_two_obj->payment_type_id}}, {{$key+1}}, {{count($level_two)}})" id="level-two-approve-{{$level_two_obj->contract_type_id}}-{{$level_two_obj->contract_id}}-{{$level_two_obj->payment_type_id}}-{{$key+1}}" {{  ($level_two_obj->flagApprove == true ? ($is_level_one_selected ? 'checked' : 'unchecked') : 'disabled') }}>
                                    </td>
                                    <td style="text-align: center;">
                                    <input type="checkbox" class="approve-checkbox" onChange="unapproveLogLevelTwoWithReason({{$level_two_obj->contract_type_id}},{{$level_two_obj->contract_id}}, {{$level_two_obj->payment_type_id}}, {{$level_two_obj->contract_name_id}})" id="level-two-reject-{{$level_two_obj->contract_type_id}}-{{$level_two_obj->contract_id}}-{{$level_two_obj->payment_type_id}}" {{  ($level_two_obj->flagReject == true ? 'disabled' : 'unchecked') }}>
                                    </td>
                                </tr>
                                <!-- <tr style="background:#d1cac3;">
                                    <td style="text-align: center;"></td>
                                    <td style="text-align: center;"></td>
                                </tr> -->
                            </table>
                        </div>
                    </div>
                </h5>
            </div>
            <div id="collapse-{{$level_two_obj->contract_id}}-{{$level_two_obj->payment_type_id}}" class="collapse" data-parent="#accordion" aria-labelledby="heading-{{$level_two_obj->contract_type_id}}-{{$level_two_obj->physician_id}}" style="width:100.9%;margin-left: 2px;">
                <!-- <div class="card-body">
                    Level 3
                </div> -->
            </div>
        </div>
    </div>
</div>
@endforeach