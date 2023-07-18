<style>
    .scheduleTable {
        width: 100%;
    }

    .scheduleTable td {
        padding: 8px;
        line-height: 1.42857143;
        vertical-align: middle;
        border: 1px solid #ddd;
        border-top: 0;
    }

    .tableTitle {
        color: #fff;
        /* background: #004de4; */
        background: #221f1f;
        font-weight: 300;
    }

    .scheduleTable tr {
        display: inline-table;
        table-layout: fixed;
        width: 100%;
    }

    .scheduleTable thead td {
        text-align: center;
    }

    .scheduleTable thead .headerRow {
        width: 97.33%;
    }

</style>
<div style="height: 577px;">
    @if($contract_type_id==App\ContractType::ON_CALL && count($fetch_oncall_data)>0)
        <table class="scheduleTable">
            <thead style="text-align: left;">
            <tr class="headerRow">
                <td class="tableTitle">Date</td>
                <td class="tableTitle">AM Physician</td>
                <td class="tableTitle">PM Physician</td>
            </tr>
            </thead>
            <tbody style="position: absolute; width: 100%; z-index: 99999; height: 534px; overflow: auto; background: #fff; border-bottom: 1px solid #ddd;-webkit-overflow-scrolling: touch;">
            @foreach($start_date_list as $fetch_date)
                @if(strtotime($fetch_date) <=strtotime($dates->end_date))
                    <?php
                    $rowDate = date('Y-m-d', strtotime($fetch_date));
                    ?>
                    <tr>
                        <td>
                            {{$fetch_date}}
                        </td>

                        @if(array_key_exists($rowDate, $physicians_data))
                            @if(array_key_exists('am', $physicians_data[$rowDate]))
                                <td>
                                    @foreach($physicians_data[$rowDate]['am'] as $physician_name)
                                    {{$physician_name}}
                                    @if(count($physicians_data[$rowDate]['am']) > 1)
                                    </br>
                                    @endif
                                    @endforeach
                                </td>
                            @else
                                <td>-</td>
                            @endif
                            @if(array_key_exists('pm', $physicians_data[$rowDate]))
                                <td>
                                    @foreach($physicians_data[$rowDate]['pm'] as $physician_name)
                                    {{$physician_name}}
                                    @if(count($physicians_data[$rowDate]['pm']) > 1)
                                    </br>
                                    @endif
                                    @endforeach
                                </td>
                            @else
                                <td>-</td>
                            @endif
                        @else
                            <td>-</td>
                            <td>-</td>
                        @endif
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    @endif
    @if(count($fetch_oncall_data)<=0)
        <div style="height: 510px">
            <div class="alert alert-danger" role="alert">
                Schedule not available.
            </div>
        </div>
    @endif
</div>

