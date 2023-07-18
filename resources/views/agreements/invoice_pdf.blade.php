<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body class="@yield('body-class', 'default')">
  @foreach($data as $agreement)
  <?php $last_invoice_no = $agreement["agreement_data"]["invoice_no"];
  $invoice_no_period = $agreement["agreement_data"]["invoice_no_period"];
  if(!$is_lawson_interfaced){
    // if($hospital->id!=123){
    //   $invoice_no = date('F').' '.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
    // }
    // else{
    //   $invoice_no = date('F').' '.date('Y').' '.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
    // }
//    $invoice_no = $last_invoice_no . '_' . $hospital->id . '_' . date('m') . '_' . date('Y'); // New invoice format is added to reports.
      $invoice_no = $invoice_no_period;
  }
  else{
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
    @if($is_lawson_interfaced)
    <tr>
      <td colspan="7" class="header_red">The following invoice was sent to Lawson via the AP520 interface.</td>
    </tr>
    @endif
    <tr>
      @if($hospital->id!=94 && $hospital->id!=143)
      <td class="header">Invoice #</td>
      @else
      <td class="header"></td>
      @endif
      <td class="header">Invoice Run Date</td>
      <td colspan="2" class="header">DYNAFIOS Invoice / Check Request</td>
      <td colspan="3" rowspan="6" class="logo_container"><img src="{{storage_path(). "/dynafios.png"}}" alt="DYNAFIOS Logo"></td>
    </tr>
    <tr>
      @if($hospital->id!=94 && $hospital->id!=143)
      <td></td>
      @else
      <td></td>
      @endif
      <td >{{$localtimeZone}}</td>
      <td colspan="2" class="header">
          @if(!$hospital->approve_all_invoices)
              Date Range:{{$agreement["agreement_data"]["period"]}}
          @endif
      </td>
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
    @foreach($invoice_notes as $index => $invoice_note)
      <tr>
        <td>{{$invoice_note}}</td>
        <td colspan="5">&nbsp;</td>
      </tr>
    @endforeach
    <tr>
      <td colspan="7">&nbsp;</td>
    </tr>
    @foreach($agreement["practices"] as $practice)
    <?php $grant_total = 0; $date_range = ''; $contract_id = 0; $physician_id = 0; $processed_contracts = []; $processed_amt_id = [];?>
    <tr class="practice_heading_row header">
      <td colspan="7">{{$practice["name"]}}</td>
    </tr>
      @foreach($practice['practice_invoice_notes'] as $index => $practice_invoice_note)
        <tr class="practice_heading_row header">
          <td colspan="7">{{$practice_invoice_note}}</td>
        </tr>
      @endforeach

    @foreach($practice["contract_data"] as $contract_data)
      @if(count($contract_data["breakdown"]) > 0)
        <tr>
            <td  class="other_rows_td">Invoice #</td>
            <td  class="other_rows_td" colspan="2">{{$contract_data['invoice_number']}}</td>
            @if($print_all_invoice_flag && $hospital->approve_all_invoices)
              <td  class="other_rows_td" colspan="2">{{$contract_data['date_range']}}</td>
            @endif
        </tr>
        <tr class="data_header header contract_start">
            <td>Contract</td>
            <td>Physician</td>
            <td>Actions</td>
            <td class="hrs" >Hours/<br/>Days/<br/>wRVUs/<br/>Units</td>
            <td>Rate</td>
            <td>Calculated Payments</td>
            <td>Actual Payments</td>
        </tr>
      <?php $rowspan= count($contract_data["breakdown"]);
       $rownumber=1;
       $midrow=intval($rowspan/2);
       $midrow=$midrow < 1 ? 1 : $midrow; ?>
      <tr>
        @foreach($contract_data["breakdown"] as $breakdown)
          @if($rownumber==1 && $rownumber==$rowspan)
            <td  class="single_row_td">
          @else
            <td  class="other_rows_td" style="border-top-width:0px; border-bottom-width:0px; border-left-width:thin;border-right-width:thin;">
          @endif
          @if($rownumber==$midrow)
            {{$contract_data["contract_name"]}}
          @endif
          </td>
          @if($rownumber==1 && $rownumber==$rowspan)
            <td class="single_row_td">
          @else
            <td class="other_rows_td" style="border-top-width:0px; border-bottom-width:0px; border-right-width:thin;">
          @endif
          @if($rownumber==$midrow)
            {{$contract_data["physician_name"]}}
          @endif
          </td>
          <td>{{$breakdown["action"]}}</td>
          <td>
              {{number_format($breakdown["worked_hours"], 2)}}
          </td>
		  @if($contract_data["payment_type_id"] == 5)
			<td>@foreach($contract_data["on_call_rates"] as $on_call_rate)
				<?php $rate = $on_call_rate["rate"]; 
					  $range_start_day = $on_call_rate["range_start_day"]; 
            $range_end_day = $on_call_rate["range_end_day"];
            $all_rate = "Day " .$range_start_day. " - " .$range_end_day. " - $" .formatNumber($rate) ;
				    ?>
            <span style = "font-size:8.0pt"> {{ $all_rate }} </span><br />
			@endforeach
      </td>
		  @else
			  <td>${{ formatNumber($breakdown["rate"])}}</td>
		  @endif
          <td>
              @if($contract_data["is_shared_contract"] == 0)
              ${{ formatNumber($breakdown["calculated_payment"])}}
              @else
                  -
              @endif
          </td>
          <td>&nbsp;</td>
      </tr>
       <tr>
          <?php
          $Physician_signature = $breakdown["Physician_approve_signature"];
          $Physician_date = $breakdown["Physician_signature_date"];
          $levels = $breakdown["levels"];
          $rownumber++;
          ?>
        @endforeach
      @else
        <tr>
      @endif
      <td>@if(count($contract_data['contract_invoice_notes']) > 0) {{$contract_data['contract_invoice_notes'][1]}} @else &nbsp; @endif</td>
      <td>@if(count($contract_data['physician_invoice_notes']) > 0) {{$contract_data['physician_invoice_notes'][1]}} @else &nbsp; @endif</td>
      <td class="header">TOTAL</td>
      <td>
          @if($contract_data["is_shared_contract"] == 0)
              {{number_format($contract_data["sum_worked_hour"], 2)}}
          @else
              {{number_format($contract_data["worked_hours"], 2)}}
          @endif
      </td>
      <td>-</td>
      <td>
          @if($contract_data["is_shared_contract"] == 0)
              ${{ formatNumber($contract_data["total_calculated_payment"])}}
          @else
              -
          @endif
      </td>
      <td>
          @if($contract_data["is_shared_contract"] == 0)
              ${{ formatNumber($contract_data["amount_paid"])}}
          @else
              -
          @endif
      </td>
        <?php
          if(!in_array($contract_data["contract_id"], $processed_contracts)){
              $grant_total = $grant_total+$contract_data["amount_paid"];
              $processed_contracts[] = $contract_data["contract_id"];
              $processed_amt_id[] = $contract_data["amount_paid_id"];
          } else if (!in_array($contract_data["amount_paid_id"], $processed_amt_id)){
              $grant_total = $grant_total+$contract_data["amount_paid"];
              $processed_amt_id[] = $contract_data["amount_paid_id"];
          }

            $contract_id = $contract_data["contract_id"];
            $date_range = $contract_data["date_range"];
            $physician_id = $contract_data["physician_id"];
        ?>
    </tr>
        <?php
          $notes_length = 0;
          if(count($contract_data['contract_invoice_notes']) >= count($contract_data['physician_invoice_notes']) && count($contract_data['contract_invoice_notes']) > count($contract_data['split_payment'])){
            $notes_length = count($contract_data['contract_invoice_notes']);
          } else if(count($contract_data['physician_invoice_notes']) >= count($contract_data['contract_invoice_notes']) && count($contract_data['physician_invoice_notes']) > count($contract_data['split_payment'])){
            $notes_length = count($contract_data['physician_invoice_notes']);
          } else {
            $notes_length = count($contract_data['split_payment']) + 1;
          }
        ?>
          @for($n = 2, $m = 0; $n <= $notes_length; $n++, $m++)
            <tr>
              @if($n != 0 && $n != 1)
                <td>@if(isset($contract_data['contract_invoice_notes'][$n])) {{$contract_data['contract_invoice_notes'][$n]}} @else &nbsp; @endif</td>
                <td>@if(isset($contract_data['physician_invoice_notes'][$n])) {{$contract_data['physician_invoice_notes'][$n]}} @else &nbsp; @endif</td>
              @else
                <td>-</td>
                <td>-</td>
              @endif
			  
              @if($m < count($contract_data['split_payment']))
                <td>{{$contract_data['split_payment'][$m]['payment_note_1']}}</td>
                <td>{{$contract_data['split_payment'][$m]['payment_note_2']}}</td>
                <td>{{$contract_data['split_payment'][$m]['payment_note_3']}}</td>
                <td>{{$contract_data['split_payment'][$m]['payment_note_4']}}</td>
                <td>{{$contract_data['split_payment'][$m]['amount']}}</td>
              @endif
              
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
          <span> - </span>
          <?PHP
            $signature_folder = 'signatures_'.$last_invoice_no;
          ?>
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
            <span> - </span>
          @endif
        </td>
      @endforeach
    </tr>
    <tr class="contract_end">
      <td> </td>
      @foreach($levels as $level_type)
        @if($level_type["type"]== "NA")
            <td><span> - <span></td>
            @else
            <td>{{$level_type["type"]}}</td>
            @endif
      @endforeach
    </tr>

        <tr class="contract_end">
          <td> </td>
          @foreach($levels as $level_user)
			@if($level_user["name"]== "NA")
			<td><span> - <span></td>
            @else
            <td>{{$level_user["name"]}}</td>
            @endif
          @endforeach
        </tr>

        <tr class="contract_end">
          <td>{{$Physician_date}}</td>
          @foreach($levels as $level_sign_date)
			@if($level_sign_date["sign_date"] == "NA")
            <td><span> - <span></td>
            @else
            <td>{{$level_sign_date["sign_date"]}}</td>
            @endif          
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
.single_row_td
{
  border:0.01px solid #000000;
}
.other_rows_td
{
  border-top-width:0px;
  border-bottom-width:0px;
}
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
      table-layout: fixed;
}
.invoice_main_table td
{
  word-wrap: break-word;
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
