
@foreach ($level_two as $key => $level_two_obj)

<?php
$hours_approving = 0.00;
if($status == 0 || $status == 4){
//    $hours_approving = $level_two_obj->hours_approving;
    $hours_approving = $level_two_obj->hours_approving;
} else if($status == 1){
    $hours_approving = $level_two_obj->pending_logs_hours_approving;
} else if($status == 2){
    $hours_approving = $level_two_obj->approved_logs_hours_approving;
} else if($status == 3){
    $hours_approving = $level_two_obj->rejected_logs_hours_approving;
}
?>

<div class="card-body">
    <div id="accordion">
        <div class="card">
            <div class="card-header" id="heading-{{$level_two_obj->contract_id}}-{{$level_two_obj->physician_id}}">
                <h5 class="mb-0">
                    
                    <div class="row" style="padding:0px">
                   
                        <div class="col-xs-12" id="level-two">
                        <div class="collapse-level-two-circle"></div>
                            <a onClick="getDataForLevelThree({{$level_two_obj->contract_id}}, {{$level_two_obj->physician_id}}, {{$level_two_obj->practice_id}}, '{{$level_two_obj->period_min_date}}', '{{$level_two_obj->period_max_date}}',{{$level_two_obj->payment_type_id}})" class="collapsed" role="button" data-toggle="collapse" href="#collapse-{{$level_two_obj->contract_id}}-{{$level_two_obj->physician_id}}-{{$level_two_obj->period_min_date}}" aria-expanded="false" aria-controls="collapse-" style="width:100%; background:#8e8174; margin: 0px 0px 0px 0px !important; padding:0px; line-height: 40px;border:none; height: 60px;-webkit-box-shadow: 0 0 0 0 !important;border-bottom: solid 1px #b8b8b8;">
                            <table style="width:100%;margin-top: 15px;">
                                    <tr>
                                        <!-- <th width="20" style="background:#d1cac3;"></th> -->
                                        <th width="75" class="level-two-physician-heading">
                                            <span title="{{ $level_two_obj->contract_name }}" class="agreementHeading1">{{ $level_two_obj->contract_name }}</span>
                                        </th>
                                        <th width="70" class="level-two-heading" >
                                            <span title="{{ date('m/d/Y', strtotime($level_two_obj->period_min_date))}} - {{ date('m/d/Y', strtotime($level_two_obj->period_max_date)) }}" class="agreementHeading1">{{ date("m/d/Y", strtotime($level_two_obj->period_min_date))}} - {{ date("m/d/Y", strtotime($level_two_obj->period_max_date)) }}</span>
                                        </th>
                                        <th width="95"  class="level-two-heading">
                                            <span title="Hours/Units to Approve: {{ $hours_approving }}" class="agreementHeading1">Hrs/Units Approving: <span class = "approve-level-two-value" >{{ $hours_approving }}<span> </span>
                                        </th>
                                        <th width="70"  class="level-two-heading">
                                            <span title="Expected Hours/Units: {{ $level_two_obj->expected_hours}}" class="agreementHeading1">EXP Hrs/Units: <span class = "approve-level-two-value">{{ $level_two_obj->expected_hours }}</span></span>
                                        </th>
                                    </tr>
                                  
                            </table>
                            </a>
                        </div>
                    </div>
                </h5>
            </div>
            <div id="collapse-{{$level_two_obj->contract_id}}-{{$level_two_obj->physician_id}}-{{$level_two_obj->period_min_date}}" class="collapse" data-parent="#accordion" aria-labelledby="heading-{{$level_two_obj->contract_id}}-{{$level_two_obj->physician_id}}-{{$level_two_obj->period_min_date}}" style="height: auto;">
                <!-- <div class="card-body">
                    Level 3
                </div> -->
            </div>
        </div>
    </div>
</div>
@endforeach