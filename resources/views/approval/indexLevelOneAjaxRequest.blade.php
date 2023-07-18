<!-- @foreach ($level_one as $level_one_obj)
<li id='level_one_{{$level_one_obj->id}}'>
    <a onClick="getDataForContractType({{$level_one_obj->id}})" class="hospitalDetails has-arrow" aria-expanded="false" style="height:80px">
        <div class="col-xs-10">
            <span title="hospitalName" class="dashboardSummationHeading"><b style="color:black">{{ $level_one_obj->name }}</b></span>
            <span title="hospitalName" class="dashboardSummationHeading">Total Contracts to Approve <b style="color:black"> {{ $level_one_obj->total_contracts_to_approve }}</b></span>
            <span title="hospitalName" class="dashboardSummationHeading"><b style="color:black">Period(s) ( {{ $level_one_obj->log_min_date }} - {{ $level_one_obj->log_max_date }})</b></span>
            <span title="hospitalName" class="dashboardSummationHeading">Calculated Payments:<b style="color:black"> ${{ $level_one_obj->total_payment }}</b></span>
            <span class="fa plus-minus"  data-info = "1"></span>
        </div>
        <div class="col-xs-2">
            <div class="col-xs-12">
                <div class="col-xs-6">
                    <span style="color: #f68a1f;"><b>Approve</b></span> <br/>
                    <input type="checkbox"/>
                </div>
                <div class="col-xs-6">
                    <span style="color: #f68a1f;"><b>Reject</b></span> <br/>
                    <input type="checkbox"/>
                </div>
            </div>
        </div>
    </a>
    <ul id="hos-list-{{$level_one_obj->id}}">
        
    </ul>
</li>
@endforeach -->

@foreach ($level_one as $level_one_obj)
    <div id="accordion">
        <div class="card">
            <div class="card-header" id="heading-{{$level_one_obj->id}}">
                <h5 class="mb-0">

                    <div class="row" style="padding:0px">
                        <div class="col-xs-10" style="padding:0px" id="level-one">
                            <a onClick="getDataForContractType({{$level_one_obj->id}})" class="collapsed" role="button" data-toggle="collapse" href="#collapse-{{$level_one_obj->id}}" aria-expanded="false" aria-controls="collapse-{{$level_one_obj->id}}">
                                <div class="col-xs-10">
                                    <span title="hospitalName" class="dashboardSummationHeading"><b style="color:black">{{ $level_one_obj->name }}</b></span>
                                    <span title="hospitalName" class="dashboardSummationHeading">Total Contracts to Approve <b style="color:black"> {{ $level_one_obj->total_contracts_to_approve }}</b></span>
                                    <span title="hospitalName" class="dashboardSummationHeading"><b style="color:black">Period(s) ( {{ $level_one_obj->log_min_date }} - {{ $level_one_obj->log_max_date }})</b></span>
                                    <span title="hospitalName" class="dashboardSummationHeading">Calculated Payments:<b style="color:black"> {{ ($level_one_obj->calculated_payment != 0.00) ? '$'.$level_one_obj->calculated_payment : 'NA' }}</b></span>
                                </div>
                            </a>
                        </div>
                        <div class="col-xs-2" style="background:#524a42;padding-left:0px;padding-right:0px;">
                            <table style="line-height:34px">
                                <tr>
                                    <th style="color:#f68a1f">Approve</th>
                                    <th style="color:#f68a1f">Reject</th>
                                </tr>
                                <tr style="background:#524a42;border-left: 1px solid #b8b8b8;">
                                    <td>
                                    <input type="checkbox" onChange="selectLogLevelOne({{$level_one_obj->id}})" id="level-one-approve-{{$level_one_obj->id}}" {{  ($level_one_obj->flagApprove == true ? 'checked' : 'disabled') }}>
                                    </td>
                                    <td>
                                        <input type="checkbox" disabled>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- <a onClick="getDataForContractType({{$level_one_obj->id}})" role="button" data-toggle="collapse" href="#collapse-{{$level_one_obj->id}}" aria-expanded="false" aria-controls="collapse-{{$level_one_obj->id}}" style="width:100%; background:#524a42; margin: 0px 0px 0px 0px !important">
                        <div class="col-xs-10">
                            <span title="hospitalName" class="dashboardSummationHeading"><b style="color:black">{{ $level_one_obj->name }}</b></span>
                            <span title="hospitalName" class="dashboardSummationHeading">Total Contracts to Approve <b style="color:black"> {{ $level_one_obj->total_contracts_to_approve }}</b></span>
                            <span title="hospitalName" class="dashboardSummationHeading"><b style="color:black">Period(s) ( {{ $level_one_obj->log_min_date }} - {{ $level_one_obj->log_max_date }})</b></span>
                            <span title="hospitalName" class="dashboardSummationHeading">Calculated Payments:<b style="color:black"> ${{ $level_one_obj->total_payment }}</b></span>
                        </div>
                        <div class="col-xs-2">
                            <div class="col-xs-12">
                                    <span style="color: #f68a1f;"><b>Approve</b></span>
                                    <input type="checkbox"/>
                                    <span style="color: #f68a1f;"><b>Reject</b></span>
                                    <input type="checkbox"/>
                            </div>
                        </div>
                    </a> -->
                </h5>
            </div>
            <div id="collapse-{{$level_one_obj->id}}" class="collapse" data-parent="#accordion" aria-labelledby="heading-{{$level_one_obj->id}}">
                
            </div>
        </div>
    </div>

        <script>
            var tempArr = [];    
        </script>

        @foreach($level_one_obj->approval_log_ids as $log_id)
            <script>
                tempArr.push({{$log_id}});
            </script>

        @endforeach

        <script>
            log_ids[{{$level_one_obj->id}}] = tempArr;
        </script>

@endforeach