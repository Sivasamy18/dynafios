<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body class="@yield('body-class', 'default')">
  @foreach($data as $agreement)
  <?php $last_invoice_no = $agreement["agreement_data"]["invoice_no"];
  $invoice_no = date('F').' '.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
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
      <td colspan="7" class="header_red">***THIS IS NOT AN INVOICE/CHECK REQUEST***</td>
    </tr>
    <tr>
      <td colspan="7" class="header_red">The following invoices will be sent to Lawson via the AP520 interface</td>
    </tr>
    <tr>
      <td class="header"></td>
      <td class="header">Run Date</td>
      <td colspan="2" class="header">DYNAFIOS Lawson Interface Report</td>
      <td colspan="3" rowspan="6" class="logo_container"><img src="{{storage_path(). "/dynafios.png"}}" alt="DYNAFIOS Logo"></td>
    </tr>
    <tr>
      <td></td>
      <td >{{$run_date}}</td>
      <td colspan="2" class="header">Date Range:{{$agreement["agreement_data"]["period"]}}</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
      <td colspan="2" rowspan="4"> &nbsp;</td>
    </tr>
    <tr>
      <td class="header">Organization:</td>
      <td class="header">{{$hospital->name}}</td>
    </tr>
    <tr>
      <td rowspan="{{count($invoice_notes)+2}}">&nbsp;</td>
      <td>{{$hospital->address}}</td>
    </tr>
    <tr>
      <td>{{$hospital->city, $hospital->state->name}}</td>
    </tr>
    <tr>
      <td colspan="5">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="7">&nbsp;</td>
    </tr>
    @foreach($agreement["practices"] as $practice)
    <?php $grant_total = 0;?>
    <tr class="practice_heading_row header">
      <td colspan="7">{{$practice["name"]}}</td>
    </tr>
      @foreach($practice['practice_invoice_notes'] as $index => $practice_invoice_note)
        <tr class="practice_heading_row header">
          <td colspan="7">&nbsp;</td>
        </tr>
      @endforeach

    @foreach($practice["contract_data"] as $contract_data)
      @if(count($contract_data["breakdown"]) > 0)
      <tr class="data_header header contract_start">
        <td>Contract</td>
        <td>Physician</td>
        <td>Actions</td>
        <td class="hrs" >Hours/<br/>Days</td>
        <td>Rate</td>
        <td>Calculated Payments</td>
        <td>Actual Payments</td>
      </tr>
      <?php $rowspan= count($contract_data["breakdown"]); ?>
      <tr>
        <td rowspan='{{$rowspan}}'>{{$contract_data["contract_name"]}}</td>
        <td rowspan='{{$rowspan}}'>{{$contract_data["physician_name"]}}</td>
        @foreach($contract_data["breakdown"] as $breakdown)
        <td>{{$breakdown["action"]}}</td>
        <td>{{number_format($breakdown["worked_hours"], 2)}}</td>
        <td>${{ formatNumber($breakdown["rate"])}}</td>
        <td>${{ formatNumber($breakdown["calculated_payment"])}}</td>
          <td>&nbsp;</td>
      </tr>
       <tr>
          <?php
          $Physician_signature = $breakdown["Physician_approve_signature"];
          $Physician_date = $breakdown["Physician_signature_date"];
          $levels = $breakdown["levels"];
          ?>
        @endforeach
      @else
        <tr>
      @endif
      <td>@if(count($contract_data['contract_invoice_notes']) > 0) &nbsp; @else &nbsp; @endif</td>
      <td>@if(count($contract_data['physician_invoice_notes']) > 0) &nbsp; @else &nbsp; @endif</td>
      <td class="header">TOTAL</td>
      <td>{{number_format($contract_data["sum_worked_hour"], 2)}}</td>
      <td>-</td>
      <td>${{ formatNumber($contract_data["total_calculated_payment"])}}</td>
      <td>${{ formatNumber($contract_data["amount_paid"])}}</td>
      <?php $grant_total = $grant_total+$contract_data["amount_paid"]; ?>
    </tr>
          @for($n = 2; $n <= (count($contract_data['contract_invoice_notes']) > count($contract_data['physician_invoice_notes']) ? count($contract_data['contract_invoice_notes']) : count($contract_data['physician_invoice_notes'])); $n++)
            <tr>
              <td>@if(isset($contract_data['contract_invoice_notes'][$n])) &nbsp; @else &nbsp; @endif</td>
              <td>@if(isset($contract_data['physician_invoice_notes'][$n])) &nbsp; @else &nbsp; @endif</td>
              <td colspan="5">&nbsp;</td>
            </tr>
          @endfor
    <tr>
      <td class="data_header header">Physician</td>
      <td class="data_header header">Approval Level 1</td>
      <td class="data_header header">Approval Level 2</td>
      <td class="data_header header">Approval Level 3</td>
      <td class="data_header header">Approval Level 4</td>
      <td class="data_header header">Approval Level 5</td>
      <td class="data_header header">Approval Level 6</td>
    </tr>
    <tr>
      <td>
        @if ($Physician_signature != "NA" && $Physician_signature != "" && $Physician_signature != null)
        <?PHP
        $dataSCM = "data:image/png;base64," . $Physician_signature;
        list($type, $dataSCM) = explode(';', $dataSCM);
        list(, $dataSCM) = explode(',', $dataSCM);

        $dataSCM = base64_decode($dataSCM);
        //echo storage_path()."/image.png";die;
        $signature_folder = 'signatures_'.$last_invoice_no;
        $now = DateTime::createFromFormat('U.u', microtime(TRUE));
        $now->setTimeZone(new DateTimeZone('America/Denver'));

        if (!is_dir(storage_path() . "/".$signature_folder)) {
          mkdir(storage_path() . "/".$signature_folder);
        }

        file_put_contents(storage_path() . "/".$signature_folder."/imageUserM".$now->format("mdYHisu") . ".png", $dataSCM);
        ?>
          <img src="{{storage_path(). "/".$signature_folder."/imageUserM".$now->format("mdYHisu") . ".png"}}" class="signature" />
        @else
          NA
        @endif
      </td>
      @foreach($levels as $level_sign)
        <td>
          @if ($level_sign["signature"] != "NA" && $level_sign["signature"] != "" && $level_sign["signature"] != null)
          <?PHP
          $dataSCM = "data:image/png;base64," . $level_sign["signature"];
          list($type, $dataSCM) = explode(';', $dataSCM);
          list(, $dataSCM) = explode(',', $dataSCM);

          $dataSCM = base64_decode($dataSCM);
          //echo storage_path()."/image.png";die;

          if (!is_dir(storage_path() . "/".$signature_folder)) {
            mkdir(storage_path() . "/".$signature_folder);
          }

          file_put_contents(storage_path() . "/".$signature_folder."/imageUserM".$level_sign['name'].date("mdYH") . ".png", $dataSCM);
          ?>
          <img src="{{storage_path() . "/".$signature_folder."/imageUserM".$level_sign['name']. date("mdYH") . ".png"}}" class="signature" />
          @else
            NA
          @endif
        </td>
      @endforeach
    </tr>
    <tr class="contract_end">
      <td> </td>
      @foreach($levels as $level_type)
        <td>{{$level_type["type"]}}</td>
      @endforeach
    </tr>

        <tr class="contract_end">
          <td> </td>
          @foreach($levels as $level_user)
            <td>{{$level_user["name"]}}</td>
          @endforeach
        </tr>

        <tr class="contract_end">
          <td>{{$Physician_date}}</td>
          @foreach($levels as $level_sign_date)
            <td>{{$level_sign_date["sign_date"]}}</td>
          @endforeach
        </tr>

      @endforeach
      <tr>
        <td colspan="2">&nbsp;</td>
        <td class="header">Practice TOTAL</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>${{ formatNumber($grant_total)}}</td>
      </tr>
    @endforeach <!--End of pratice level foreach-->
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
.contract_start
{
  /*border-left: 1px solid red;*/
  border-top-width:thick;

}
.contract_end
{
 border-bottom-width:thick;
}
.invoice_main_table,.invoice_main_table td
{
  /*border: 1px solid;
  border-collapse: collapse;*/
  border: 0.01px solid;
  border-spacing: 0;
  text-align: center;
  font-size: 14px;
}

.invoice_main_table
{
      width: 100%;
}

.practice_heading_row td,.data_header td
{
  /*border:1px solid #000000;*/
  border: 0.01px solid #000000;
  border-spacing: 0;
}
.practice_heading_row
{
  background-color: #000000;
  color: #ffffff;
  text-align: center;

}
.data_header
{
  /*background-color: #f68a1f;*/
  background-color: #a09284;
  color: #ffffff;
  text-align: center;
}
.header
{
  font-weight: bold;
}
.logo_container
{
  background-color: #000000;
  text-align: center;
}
.signature
{
  width: 90px;
  height:90px;
}
.header_red
{
  color: #000!important;
  background-color: #ffdddd!important;
}


</style>
</body>
</html>
