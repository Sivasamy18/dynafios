<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body class="@yield('body-class', 'default')">
@foreach($data as $agreement)
        <?php
        $last_invoice_no = $agreement["agreement_data"]["invoice_no"];
        $signature_folder = 'signatures_' . $last_invoice_no;
        $invoice_no_period = $agreement["agreement_data"]["invoice_no_period"];
        if (!$is_lawson_interfaced) {
            $invoice_no = $invoice_no_period;
        } else {
            $invoice_no = str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
        }
        $run_date = format_date('now', 'm/d/Y \a\t h:i:s A');
        $level1_signature = "NA";
        $level2_signature = "NA";
        $level3_signature = "NA";
        $level4_signature = "NA";
        $level5_signature = "NA";
        $level6_signature = "NA";

        ?>
  <table class="invoice_main_table">
    <tr>
      @if($hospital->id!=94 && $hospital->id!=143)
        <td colspan="4" class="header">Invoice #</td>
      @else
        <td colspan="4" class="header"></td>
      @endif
      <td colspan="4" class="header">Invoice Run Date</td>
      <td colspan="8" class="header">DYNAFIOS Invoice / Check Request</td>
      <td colspan="19" rowspan="6" class="logo_container"><img src="{{storage_path(). "/dynafios.png"}}"
                                                               alt="DYNAFIOS Logo"></td>
    </tr>
    <tr>
      @if($hospital->id!=94 && $hospital->id!=143)
        <td colspan="4">{{$invoice_no}}</td>
      @else
        <td colspan="4"></td>
      @endif
      <td colspan="4">{{$localtimeZone}}</td>
      <td colspan="8" class="header">Date Range:{{$agreement["agreement_data"]["period"]}}</td>
    </tr>
    <tr>
      <td colspan="8">&nbsp;</td>
      <td colspan="8" rowspan="4"> &nbsp;</td>
    </tr>
    <tr>
      <td colspan="4" class="header">Organization:</td>
      <td colspan="4" class="header">{{$hospital->name}}</td>
    </tr>
    <tr>
      <td colspan="4" rowspan="{{count($invoice_notes)+2}}">&nbsp;</td>
      <td colspan="4">{{$hospital->address}}</td>
    </tr>
    <tr>
      <td colspan="4">{{$hospital->city, $hospital->state->name}}</td>
    </tr>

    {{--            @foreach($invoice_notes as $index => $invoice_note)--}}
    {{--            <tr>--}}
    {{--                <td>{{$invoice_note}}</td>--}}
    {{--                <td colspan="5">&nbsp;</td>--}}
    {{--            </tr>--}}
    {{--            @endforeach--}}

    <tr>
      <td colspan="35">&nbsp;</td>
    </tr>

    @foreach($agreement["practices"] as $practice)
              <?php $grant_total = 0; ?>
      <tr class="practice_heading_row header">
        <td colspan="35">{{$practice["name"]}}</td>
      </tr>

      <tr class="data_header header contract_start">
        <td colspan="3">Date</td>
        @for($date_count=1; $date_count <= 31; $date_count++)
          <td colspan="1">{{$date_count}}</td>
        @endfor
        <td colspan="1">Total</td>
      </tr>

              <?php
              $processed_contract_arr = [];
              $Physician_signature = "NA";
              ?>

      @foreach($practice["contract_data"] as $contract_data)
        @if(!in_array($contract_data['contract_id'],$processed_contract_arr))
                      <?php
                      $admin_per_day_total = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
                      $approver_details = [];
                      ?>
          @foreach($contract_data["rehab_category_action_list"] as $category => $action)
            @if($category != "Total Clinical")
              <tr class=" header">
                <td colspan="3">{{$category}}</td>
                <td colspan="32">&nbsp;</td>
              </tr>
              @foreach ($action as $action_detail)
                <tr>
                  <td colspan="3">{{$action_detail["name"]}}</td>

                  @if(count($contract_data["breakdown"]) > 0)
                    @if (!array_key_exists($action_detail->id,$contract_data["breakdown"]))

                                <?php
                                $lastColumn = 32;
                                ?>
                      @for ($column = 1; $column <= $lastColumn; $column++)
                        <td colspan="1">0</td>
                      @endfor
                    @else
                                <?php
                                $action_logs = $contract_data["breakdown"][$action_detail->id]["action_logs"];
//                                                    $day = 1;
                                $action_total = 0;
                                $lastDay = 31;
                                $worked_hours = 0;
                                ?>

                      @for ($day = 1; $day <= $lastDay; $day++)
                        @foreach($action_logs as $action_log)
                                        <?php
                                        list($y, $m, $d) = explode('-', $action_log["date"]);
                                        $d = ltrim($d, "0");
                                        ?>
                          {{--                                                            {{  logger(array($action_log["date"], $day, $d)) }}--}}
                          @if($day == $d)
                            {{--                                                            <td colspan="1" >{{$action_log["worked_hours"]}}</td>--}}
                                            <?php
                                            $action_total = $action_total + $action_log["worked_hours"];
                                            $number = $admin_per_day_total[$day - 1] + $action_log["worked_hours"];
                                            $admin_per_day_total[$day - 1] = number_format((float)$number, 2, '.', '');
                                            $worked_hours = $action_log["worked_hours"];
                                            ?>
                            @break
                          @else
                                            <?php $worked_hours = 0; ?>
                          @endif

                        @endforeach
                        <td colspan="1">{{$worked_hours}}</td>
                      @endfor
                      <td colspan="1">{{$action_total}}</td>
                                <?php
                                /**
                                 * This block of code is for setting the approver's signature array.
                                 */
                                foreach ($contract_data["breakdown"][$action_detail->id]["levels"] as $level => $level_detail) {
                                    if ($level_detail['signature'] != 'NA') {
                                        if (!array_key_exists($level, $approver_details)) {
                                            $approver_details[$level] = $level_detail;
                                        }
                                    }
                                }
                                if ($Physician_signature != "") {
                                    $Physician_signature = $contract_data["breakdown"][$action_detail->id]['Physician_approve_signature'];
                                }
                                // Ends setting approver's signature array here.
                                ?>
                    @endif
                  @endif
                </tr>
              @endforeach
            @else
              {{--    This block of code is for Total Admin hours. --}}
              <tr class=" header">
                <td colspan="3">Total Admin</td>
                <td colspan="32">&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3">Total Admin Hours</td>
                      <?php
                      $index = 0;
                      $lastColumn = 31;
                      ?>
                @for ($column = 1; $column <= $lastColumn; $column++)

                  <td colspan="1">{{$admin_per_day_total[$index]}}</td>

                          <?php $index++; ?>
                @endfor
                      <?php $admin_hour_row_total = array_sum($admin_per_day_total); ?>
                <td colspan="3">{{$admin_hour_row_total}}</td>
              </tr>
              {{--   Admin hours code block ends here.--}}

              {{--    This block of code again repeated to run for Total Clinical hours only.--}}
              <tr class=" header">
                <td colspan="3">{{$category}}</td>
                <td colspan="32">&nbsp;</td>
              </tr>
                              <?php $per_day_total = $admin_per_day_total; ?>

              @foreach ($action as $action_detail)
                <tr>
                  <td colspan="3">{{$action_detail["name"]}}</td>

                  @if(count($contract_data["breakdown"]) > 0)
                    @if (!array_key_exists($action_detail->id,$contract_data["breakdown"]))

                                <?php
                                $lastColumn = 32;
                                ?>
                      @for ($column = 1; $column <= $lastColumn; $column++)
                        <td colspan="1">0</td>
                      @endfor
                    @else
                                <?php
                                $action_logs = $contract_data["breakdown"][$action_detail->id]["action_logs"];
                                $day = 1;
                                $action_total = 0;
                                $lastColumn = 31;
                                ?>

                      @for ($column = 1; $column <= $lastColumn; $column++)
                        @foreach($action_logs as $action_log)
                                        <?php
                                        list($y, $m, $d) = explode('-', $action_log["date"]);
                                        $d = ltrim($d, "0");
                                        ?>
                          @if($day == $d)
                            <td colspan="1">{{$action_log["worked_hours"]}}</td>
                                            <?php
                                            $action_total = $action_total + $action_log["worked_hours"];
                                            $number = $per_day_total[$column - 1] + $action_log["worked_hours"];
                                            $per_day_total[$column - 1] = number_format((float)$number, 2, '.', '');
                                            ?>
                            @break
                          @else
                            <td colspan="1">0</td>
                          @endif
                        @endforeach
                                    <?php $day++; ?>
                      @endfor
                      <td colspan="1">{{$action_total}}</td>
                    @endif
                  @endif
                </tr>
              @endforeach

              <tr class=" header">
                <td colspan="3">Total Hours</td>
                @for ($column = 0; $column <= 30; $column++)
                  <td colspan="1">{{$per_day_total[$column]}}</td>
                @endfor
                      <?php $admin_hour_row_total = array_sum($per_day_total); ?>
                <td colspan="1">{{$admin_hour_row_total}}</td>
              </tr>

              {{--    Total Clinical Hours code block ends here.--}}
            @endif
          @endforeach
                      <?php
                      array_push($processed_contract_arr, $contract_data['contract_id']);
                      $approver_details_last_to_first = array_reverse($approver_details);
                      ?>

          <tr>
            <td colspan="8" style="height: 100px;">Medical Director/Covering Medical Director's Signature</td>
            <td colspan="8" style="height: 100px;">
                    <?PHP
                    if ($Physician_signature != "NA" && $Physician_signature != "") {
                        $dataSCM = "data:image/png;base64," . $Physician_signature;
                        list($type, $dataSCM) = explode(';', $dataSCM);
                        list(, $dataSCM) = explode(',', $dataSCM);

                        $dataSCM = base64_decode($dataSCM);
                        $now = DateTime::createFromFormat('U.u', microtime(TRUE));
                        $now->setTimeZone(new DateTimeZone('America/Denver'));

                        if (!is_dir(storage_path() . "/" . $signature_folder)) {
                            mkdir(storage_path() . "/" . $signature_folder);
                        }

                        file_put_contents(storage_path() . "/" . $signature_folder . "/imageUserM" . $now->format("mdYHisu") . ".png", $dataSCM);
                    }
                    ?>
              <img src="{{storage_path(). "/".$signature_folder."/imageUserM".$now->format("mdYHisu") . ".png"}}"
                   class="signature" style="height: 100%;"/>
            </td>
            <td colspan="18" style="height: 100px;">Contracted Admin Hours to be paid for month</td>
            <td colspan="1" style="height: 100px;">{{$contract_data['rehab_max_hours_per_month']}}</td>
          </tr>

          <tr>
            <td colspan="8" style="height: 100px;">Program Director/Administrator Signature</td>
            <td colspan="8" style="height: 100px;">
                    <?PHP
                    $dataSCM = "data:image/png;base64," . $approver_details_last_to_first[0]["signature"];
                    list($type, $dataSCM) = explode(';', $dataSCM);
                    list(, $dataSCM) = explode(',', $dataSCM);

                    $dataSCM = base64_decode($dataSCM);
                    $now = DateTime::createFromFormat('U.u', microtime(TRUE));
                    $now->setTimeZone(new DateTimeZone('America/Denver'));

                    if (!is_dir(storage_path() . "/" . $signature_folder)) {
                        mkdir(storage_path() . "/" . $signature_folder);
                    }

                    file_put_contents(storage_path() . "/" . $signature_folder . "/imageUserM" . $now->format("mdYHisu") . ".png", $dataSCM);
                    ?>
              <img src="{{storage_path(). "/".$signature_folder."/imageUserM".$now->format("mdYHisu") . ".png"}}"
                   class="signature" style="height: 100%;"/>
            </td>
            <td colspan="18" style="height: 100px;">Additional Approved Admin Hours to be paid</td>
            <td colspan="1" style="height: 100px;">{{$contract_data['rehab_admin_hours']}}</td>
          </tr>

          <tr>
            <td colspan="16" style="height: 100px;">&nbsp;</td>
            <td colspan="18" style="height: 100px;">Total Admin Hours to be paid for month</td>
            <td colspan="1"
                style="height: 100px;">{{($contract_data['rehab_max_hours_per_month'] + $contract_data['rehab_admin_hours'])}}</td>
          </tr>

          <tr>
            <td colspan="16" style="height: 100px;">&nbsp;</td>
            <td colspan="18" style="height: 100px;">Total Amount to be paid for month</td>
            <td colspan="1"
                style="height: 100px;">{{ (($contract_data['rehab_max_hours_per_month'] + $contract_data['rehab_admin_hours']) * $contract_data['rate'])}}</td>
          </tr>
        @endif
      @endforeach <!--End of pratice level foreach-->
    @endforeach
  </table>
@endforeach <!--End of agreement level foreach-->

<style type="text/css">
    /*.hrs
    {
      width:10px;
      max-width: 25px;
      text-overflow: ellipsis;
      overflow: hidden;
    }*/
    .single_row_td {
        border: 0.01px solid #000000;
    }

    .other_rows_td {
        border-top-width: 0px;
        border-bottom-width: 0px;
    }

    .contract_start {
        /*border-left: 1px solid red;*/
        border-top-width: thick;

    }

    .contract_end {
        border-bottom-width: thick;
    }

    .invoice_main_table, .invoice_main_table td {
        /*border: 1px solid;
        border-collapse: collapse;*/
        border: 0.01px solid;
        border-spacing: 0;
        text-align: center;
        font-size: 14px;
    }

    .invoice_main_table {
        width: 100%;
        table-layout: fixed;
    }

    .invoice_main_table td {
        word-wrap: break-word;
    }

    .practice_heading_row td, .data_header td {
        /*border:1px solid #000000;*/
        border: 0.01px solid #000000;
        border-spacing: 0;
    }

    .practice_heading_row {
        background-color: #000000;
        color: #ffffff;
        text-align: center;

    }

    .data_header {
        /*background-color: #f68a1f;*/
        background-color: #a09284;
        color: #ffffff;
        text-align: center;
    }

    .header {
        font-weight: bold;
        font-size: 25px !important;
    }

    .logo_container {
        background-color: #000000;
        text-align: center;
    }

    .signature {
        width: 90px;
        height: 90px;
    }

    .header_red {
        color: #000 !important;
        background-color: #ffdddd !important;
    }

    /*tr{*/
    /*    line-height: 40px;*/
    /*}*/
    td {
        font-size: 25px !important;
    }

</style>
</body>
</html>
