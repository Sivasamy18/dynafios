<input type="hidden" name="date_count" value="{{ count($start_date_list) }}">
<?php $i = 0;
?>
@foreach($start_date_list as $list)
    <div>
        <div class="col-xs-2">{{$list}}</div>
        <div class="col-xs-4 form-group">
            <?php $amselected = "";

            ?>
            @for($j=0;$j<count($all_physicians);$j++)
                <?php    foreach ($fetch_oncall_data as $fetch_data) {
                    $newDate = date("m-d-Y", strtotime($fetch_data->date));
                    $newlistdate = date("m-d-Y", strtotime($list));
                    if ($fetch_data->physician_type == 1 && $newDate == $newlistdate && $all_physicians[$j]['id'] == $fetch_data->physician_id)
                        $amselected = $all_physicians[$j]['name'];

                }
                $am_physician_flag = $amselected;
                ?>
            @endfor
            @if(strlen($am_physician_flag)>0)
                <div class="col-xs-12 text-center" style="border: 0px;box-shadow: none;">{{$amselected}}</div>
            @else
                <div class="col-xs-12 text-center form-control" style="border: 0px;box-shadow: none;">-</div>
            @endif
            <?php $amselected = "";?>
        </div>

        <div class="col-xs-1"></div>
        <div class="col-xs-4 form-group">
            <?php $selected = "";?>
            @for($k=0;$k<count($all_physicians);$k++)
                <?php    foreach ($fetch_oncall_data as $fetch_data) {
                    $newDate = date("m-d-Y", strtotime($fetch_data->date));
                    $newlistdate = date("m-d-Y", strtotime($list));
                    if ($fetch_data->physician_type == 2 && $newDate == $newlistdate && $all_physicians[$k]['id'] == $fetch_data->physician_id) {
                        $selected = $all_physicians[$k]['name'];

                    }
                }
                $pm_physician_flag = $selected;
                ?>
            @endfor
            @if(strlen($pm_physician_flag)>0)
                <div class="col-xs-12 text-center form-control"
                     style="border: 0px;box-shadow: none;">{{$selected}}</div>
            @else
                <div class="col-xs-12 text-center form-control" style="border: 0px;box-shadow: none;">-</div>
            @endif
            <?php $selected = "";?>
        </div>

    </div>
    <?php $i++; ?>
@endforeach



