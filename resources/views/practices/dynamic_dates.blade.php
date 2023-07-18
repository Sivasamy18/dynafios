<input type="hidden" name="date_count" value="{{ count($start_date_list) }}">
<?php $i = 0;   ?>
@foreach($start_date_list as $list)
    <div>
        <div class="col-xs-2">{{$list}}</div>
        <div class="col-xs-4 form-group"><select name="am_phisicians_{{$i}}" class="form-control">
                <option value="0">Select AM Physician</option>
                    @for($j=0;$j<count($all_physicians);$j++)
                    <option value="{{$all_physicians[$j]['id']}}"
                    <?php
                            foreach ($fetch_oncall_data as $fetch_data) {
                                $newDate = date("m-d-Y", strtotime($fetch_data->date));
                                $newlistdate = date("m-d-Y", strtotime($list));
                                if ($fetch_data->physician_type == 1 && $newDate == $newlistdate && $all_physicians[$j]['id'] == $fetch_data->physician_id) {
                                    echo $selected = "selected";
                                } else {
                                    echo $selected = "";
                                }
                            }
                            ?>
                    >{{$all_physicians[$j]['name']}}</option>
                @endfor
            </select>
        </div>
        <div class="col-xs-1"></div>
        <div class="col-xs-4 form-group"><select name="pm_phisicians_{{$i}}" class="form-control">
                <option value="0">Select PM Physician</option>
                @for($k=0;$k<count($all_physicians);$k++)
                    <option value="{{$all_physicians[$k]['id']}}"
                    <?php    foreach ($fetch_oncall_data as $fetch_data) {
                        $newDate = date("m-d-Y", strtotime($fetch_data->date));
                        $newlistdate = date("m-d-Y", strtotime($list));
                        if ($fetch_data->physician_type == 2 && $newDate == $newlistdate && $all_physicians[$k]['id'] == $fetch_data->physician_id)
                            echo $selected = "selected";
                        echo $selected = "";
                    }
                            ?>
                    >{{$all_physicians[$k]['name']}}</option>
                @endfor
            </select>
        </div>
    </div>
    <?php $i++; ?>
@endforeach



