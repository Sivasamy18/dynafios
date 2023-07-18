<!-- <li class="practiceDetails"> -->
<div class="pane pane--table1">
        <div class="pane-hScroll">
            <table style="width: calc(100% - 17px);">
                <thead>
                    @if($column_preferences->date)<th width="120" id="lbl_date">Date</th>@endif
                    @if($column_preferences->hospital)<th width="200" id="lbl_hospital">Hospital</th>@endif
                    @if($column_preferences->agreement)<th width="200" id="lbl_agreement">Agreement</th>@endif
                    @if($column_preferences->contract)<th width="200" id="lbl_contract">Contract Name</th>@endif
                    @if($column_preferences->practice)<th width="120" id="lbl_practice">Practice</th>@endif
                    @if($column_preferences->physician)<th width="150" id="lbl_physician">Physician</th>@endif
                    @if($column_preferences->log)<th width="130" id="lbl_log">Log</th>@endif
                    @if($column_preferences->details)<th width="100" id="lbl_details">Details</th>@endif
                    @if($column_preferences->duration)<th width="100" id="lbl_duration">Hours/Units Worked</th>@endif
                    @if($column_preferences->physician_approval)<th width="120" id="lbl_physician_approval">Physician Approval</th>@endif
                    @if($column_preferences->lvl_1)<th width="200" id="lbl_lvl1">Approval Level 1</th>@endif
                    @if($column_preferences->lvl_2)<th width="200" id="lbl_lvl2">Approval Level 2</th>@endif
                    @if($column_preferences->lvl_3)<th width="200" id="lbl_lvl3">Approval Level 3</th>@endif
                    @if($column_preferences->lvl_4)<th width="200" id="lbl_lvl4">Approval Level 4</th>@endif
                    @if($column_preferences->lvl_5)<th width="200" id="lbl_lvl5">Approval Level 5</th>@endif
                    @if($column_preferences->lvl_6)<th width="200" id="lbl_lvl6">Approval Level 6</th>@endif
                    <th width="150" id="lbl_lvl6">Payment Status</th>
                </thead>
            </table>

            <div>
                <table>
                    <tbody>
                        <?php $prev_contract_id=0;
                        $contract_change_flag=0;?>
                        @foreach ($items as $log)
                        <?php
                        if($prev_contract_id!=$log['contract_id'])
                        {
                        $prev_contract_id=$log['contract_id'];
                        if($contract_change_flag==0)
                        {
                            $contract_change_flag=1;
                        }
                        else
                        {
                            $contract_change_flag=0;
                        }
                        }
                        ?>
                        @if($contract_change_flag==1)
                        <tr class="odd_contract_class">
                        @else
                        <tr class="even_contract_class">
                        @endif
                        @if($column_preferences->date)<td width="120" id="date" title="{{ format_date($log['log_date']) }}">{{ format_date($log['log_date']) }}</td>@endif
                        @if($column_preferences->hospital)<td width="200" id="hospital" title="{{ $log['hospital_name'] }}">{{ $log['hospital_name'] }}</td>@endif
                        @if($column_preferences->agreement)<td width="200" id="agreement" title="{{ $log['agreement_name'] }}">{{ $log['agreement_name'] }}</td>@endif
                        @if($column_preferences->contract)<td width="200" id="contract" title="{{ $log['contract_name'] }}">{{ $log['contract_name'] }}</td>@endif
                        @if($column_preferences->practice)<td width="120" id="practice" title="{{ $log['practice_name'] }}">{{ $log['practice_name'] }}</td>@endif
                        @if($column_preferences->physician)<td width="150" id="physician" title="{{ $log['physician_name'] }}">{{ $log['physician_name'] }}</td>@endif
                        @if($column_preferences->log)<td width="130" id="log" title="{{ $log['action'] }}">{{ $log['action'] }}</td>@endif
                        @if($column_preferences->details)<td width="100" id="details" title="{{ $log['log_details'] }}">{{ $log['log_details'] }}</td>@endif
                        @if($column_preferences->duration)<td width="100" id="duration" style="text-align: center;" title="{{ $log['duration'] }}">{{ $log['duration'] }}</td>@endif
                        @if($column_preferences->physician_approval)<td  width="120" id="physician_approval" class="{{ $log['levels'][0]["status"] === "Approved" ? "approved-text" : "" }}" title="{{ $log['levels'][0]["status"] }}">{{ $log['levels'][0]["status"] }}</td>@endif
                        <?php $ip=0; ?>
                        @foreach($log['levels'] as $levels)
                            @if($ip != 0)
                            <?php $lvl='lvl_'.$ip; ?>
                            @if($column_preferences->$lvl)
                            <td width="200" id="lvl_{{$ip}}" class="text-center">
                                <div class="{{ $levels["status"] === "Approved" ? "approved-text" : ($levels["status"] === "Rejected" ? "rejected-text" : "") }}" title='{{ $levels["name"] }}'>{{ $levels["name"] }}</div>
                                <div class="{{ $levels["status"] === "Approved" ? "approved-text" : ($levels["status"] === "Rejected" ? "rejected-text" : "") }}" title='{{ $levels["status"] }}'>{{ $levels["status"] }}</div>
                            </td>
                            @endif
                            @endif
                            <?php $ip++; ?>
                        @endforeach
                        <td width="150" id="practice" style="text-align: center;" title="{{ $log['payment_status'] }}"> {{ $log['payment_status'] }} </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<!-- </li> -->