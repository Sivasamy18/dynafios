<!-- <li class="practiceDetails"> -->
    <div class="pane pane--table1">
        <div class="pane-hScroll">
            <table style="width: calc(100% - 17px);">
                <thead>
                    <tr>
                        @if($column_preferences->date)<th width="120">Date</th>@endif
                        @if($column_preferences->hospital)<th width="200">Hospital</th>@endif
                        @if($column_preferences->agreement)<th width="200">Agreement</th>@endif
                        @if($column_preferences->contract)<th width="200">Contract Name</th>@endif
                        @if($column_preferences->practice)<th width="120">Practice</th>@endif
                        @if($column_preferences->physician)<th width="150">Physician</th>@endif
                        @if($column_preferences->log)<th width="130">Log</th>@endif
                        @if($column_preferences->details)<th width="100">Details</th>@endif
                        @if($column_preferences->duration)<th width="100">Hours/Units Worked</th>@endif
                        @if($column_preferences->physician_approval)<th width="120">Physician Approval</th>@endif
                        <th width="100" style="color: #f68a1f;">Approve</th>
                        <th width="100" style="color: #f68a1f;">Reject</th>
                        @if($column_preferences->lvl_1)<th width="160">Approval <br>Level 1</th>@endif
                        @if($column_preferences->lvl_2)<th width="160">Approval <br>Level 2</th>@endif
                        @if($column_preferences->lvl_3)<th width="160">Approval <br>Level 3</th>@endif
                        @if($column_preferences->lvl_4)<th width="160">Approval <br>Level 4</th>@endif
                        @if($column_preferences->lvl_5)<th width="160">Approval <br>Level 5</th>@endif
                        @if($column_preferences->lvl_6)<th width="160">Approval <br>Level 6</th>@endif                      
                    </tr>
                </thead>
            </table>

            <div>
                <table>
                    <tbody>
                    <?php $prev_contract_id=0;
                    $contract_change_flag=0;?>
                    @foreach ($sorted_items as $log)
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
                                @if($column_preferences->date)<td width="120" title="{{ format_date($log['log_date']) }}" class="text_ellipses">{{ format_date($log['log_date']) }}</td>@endif
                                @if($column_preferences->hospital)<td width="200" title="{{ $log['hospital_name'] }}" class="text_ellipses">{{ $log['hospital_name'] }}</td>@endif
                                @if($column_preferences->agreement)<td width="200" title="{{ $log['agreement_name'] }}" class="text_ellipses">{{ $log['agreement_name'] }}</td>@endif
                                @if($column_preferences->contract)<td width="200" title="{{ $log['contract_name'] }}" class="text_ellipses">{{ $log['contract_name'] }}</td>@endif
                                @if($column_preferences->practice)<td width="120" title="{{ $log['practice_name'] }}" class="text_ellipses">{{ $log['practice_name'] }}</td>@endif
                                @if($column_preferences->physician)<td width="150" title="{{ $log['physician_name'] }}" class="text_ellipses">{{ $log['physician_name'] }}</td>@endif
                                @if($column_preferences->log)<td width="130" class="{{$log['proxy']=='true'?'proxy_approver':''}} text_ellipses" title="{{ $log['action'] }}">{{ $log['action'] }}</td>@endif
                                @if($column_preferences->details)<td width="100" title="{{ $log['log_details'] }}" class="text_ellipses">{{ $log['log_details'] }}</td>@endif
                                @if($column_preferences->duration)<td width="100" style="text-align: center;" title="{{ $log['duration'] }}">{{ $log['duration'] }}</td>@endif
                                @if($column_preferences->physician_approval)<td  width="120" class="{{ $log['levels'][0]["status"] === "Approved" ? "approved-text" : "" }}" title="{{ $log['levels'][0]["status"] }}">{{ $log['levels'][0]["status"] }}</td>@endif
                                <td width="100" style="text-align: center;">{{ Form::checkbox('approved[]', $log['log_id'],null , ['id' => 'select_log_'.$log['log_id'] , 'class' =>'select_logs_'.$log['contract_type_id'].'_'.$log['contract_id'].'_'.$log['payment_type_id'], 'onChange' => 'selectDeselectLog('. $log['contract_type_id'] . ',' . $log['contract_id'] . ',' . $log['physician_id'] . ',' . $log['payment_type_id'] . ',' . $log['log_id'] . ');', $log['current_user_status'] != 'Waiting' ? 'disabled':'', $log['current_user_status'] === 'Waiting' ? $is_selected_level_two ? 'checked' : '':'']) }}</td>
                                <td width="100" style="text-align: center;">{{ Form::checkbox('rejected[]',$log['log_id'], null, ['id' => 'reject_log_'.$log['log_id'] ,  'class' =>'reject_logs_'.$log['contract_type_id'].'_'.$log['contract_id'].'_'.$log['payment_type_id'], 'onChange' => 'selectDeselectRejectLog('. $log['contract_type_id'] . ',' . $log['physician_id'] . ',' . $log['payment_type_id'] . ',' . $log['log_id'] . ',' . $log['contract_id'] . ');',$log['current_user_status'] != 'Waiting' ? 'disabled':'']) }}</td>
                                <?php $ip=0; ?>
                                @foreach($log['levels'] as $levels)
                                    @if($ip != 0)
                                        <?php $lvl='lvl_'.$ip; ?>
                                        @if($column_preferences->$lvl)
                                        <td width="160">
                                            <div class="{{ $levels["status"] === "Approved" ? "approved-text" : ($levels["status"] === "Rejected" ? "rejected-text" : "") }}" title='{{ $levels["name"] }}'>{{ $levels["name"] }}</div>
                                            <div class="{{ $levels["status"] === "Approved" ? "approved-text" : ($levels["status"] === "Rejected" ? "rejected-text" : "") }}" title='{{ $levels["status"] }}'>{{ $levels["status"] }}</div>
                                        </td>
                                        @endif
                                    @endif
                                    <?php $ip++; ?>
                                @endforeach
                                <input type="hidden" name="reason_{{$log['log_id']}}" id="reason_{{$log['log_id']}}" value="">
                                <input type="hidden" name="manager_type_{{$log['log_id']}}" id="manager_type_{{$log['log_id']}}" value="{{$log['manager_type_id']}}">
                            </tr>
                            @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<!-- </li> -->