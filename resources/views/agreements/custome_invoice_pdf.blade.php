<!DOCTYPE html>
<html lang="en">

<head>
</head>
<body class="@yield('body-class', 'default')">
<table class="invoice_main_table" style="border:none">

    <tr style="border:none">
	@if($hospital->invoice_type == 2)
		<td colspan="4" class="header" style="border:none"><span class='span-text'></span></td>
        <td colspan="3" class="header" style="border:none">&nbsp;</td>
        <td colspan="3" class="header" style="border:none"><span class='span-text'>Invoice Type PP</span></td>
        <td colspan="2" class="header" style="border:none">&nbsp;</td>
	@else
		<td colspan="4" class="header" style="border:none"><span class='span-text'>Referral Source Check Request</span></td>
        <td colspan="3" class="header" style="border:none">&nbsp;</td>
        <td colspan="3" class="header" style="border:none"><span class='span-text'>Invoice Type PP	</span></td>
        <td colspan="2" class="header" style="border:none">&nbsp;</td>
	@endif
    </tr>
    <tr style="border:none">
        <td colspan="12" class="header" style="border:none">&nbsp;</td>
    </tr>
    <tr>
        <td  colspan="2" class="header"><span class='span-text'>Organization Name: </span></td>
        <td  colspan="6" class="header"><span class='span-text'>{{ $hospital->name}}</span></td>
        <td colspan="4" class="header" style="border:none">&nbsp;</td>
    </tr>
    <!-- <tr>
        <td  colspan="2" class="header"><span class='span-text'>COID: </span></td>
        <td  colspan="3" class="header"><span class='span-text'>{{ isset($invoice_notes['1']) > 0 ? $invoice_notes['1'] : '' }}</span></td>
        <td colspan="7" class="header" style="border:none">&nbsp;</td>
    </tr> -->
    @foreach($data as $agreement)
      <?php
        $last_invoice_no = $agreement["agreement_data"]["invoice_no"];
        $invoice_no_period = $agreement["agreement_data"]["invoice_no_period"];
        // if($hospital->id!=123){
        //   $invoice_no = date('F').' '.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
        // }
        // else{
        //   $invoice_no = date('F').' '.date('Y').' '.str_pad($last_invoice_no, 3, "0", STR_PAD_LEFT);
        // }

//        $invoice_no = $last_invoice_no . '_' . $hospital->id . '_' . date('m') . '_' . date('Y'); // New invoice format is added to reports.
        $invoice_no = $invoice_no_period;

        $run_date = $localtimeZone;
        $date_range = $agreement["agreement_data"]["period"];
      ?>
      @foreach($agreement["practices"] as $practice)
          @if(isset($practice["practice_invoice_notes"]) > 0)
          <?php
            if(isset($practice["practice_invoice_notes"]['1'])){
              $name_cont = $practice["practice_invoice_notes"]['1'];
            } else {
              $name_cont = "";  
            }
          ?>
          @else
          <?php
            $name_cont = "";
          ?>
          @endif
        @foreach($practice["contract_data"] as $contract_data)
          @if(isset($contract_data["physician_invoice_notes"]) > 0)
          <?php
            if(isset($contract_data["physician_invoice_notes"]['1'])){
              $vendor_number = $contract_data["physician_invoice_notes"]['1'];
            } else {
              $vendor_number = "";  
            }

            if(isset($contract_data["physician_invoice_notes"]['2'])){
              $payee_name = $contract_data["physician_invoice_notes"]['2'];
            } else {
              $payee_name = "";  
            }

            if(isset($contract_data["physician_invoice_notes"]['3'])){
              $address_one = $contract_data["physician_invoice_notes"]['3'];
            } else {
              $address_one = "";  
            }

            if(isset($contract_data["physician_invoice_notes"]['4'])){
              $address_two = $contract_data["physician_invoice_notes"]['4'];
            } else {
              $address_two = "";  
            }
          ?>
          @else
          <?php
            $vendor_number = "";
            $payee_name = "";
            $address_one = "";
            $address_two = "";
          ?>
          @endif

          @if(isset($contract_data["contract_invoice_notes"]) > 0)
            <?php
              if(isset($contract_data["contract_invoice_notes"]['1'])){
                $gl_code = $contract_data["contract_invoice_notes"]['1'];
              } else {
                $gl_code = "";  
              }

              if(isset($contract_data["contract_invoice_notes"]['2'])){
                $custome_code = $contract_data["contract_invoice_notes"]['2'];
              } else {
                $custome_code = "";  
              }
            ?>
          @else
            <?php
              $gl_code = "";
              $custome_code = "";
            ?>
          @endif
        @endforeach
      @endforeach
      <tr>
          <td  colspan="2" class="header"><span class='span-text'>COID: </span></td>
          <td  colspan="3" class="header"><span class='span-text'>{{$name_cont}}</span></td>
          <td colspan="7" class="header" style="border:none">&nbsp;</td>
      </tr>
    <tr>
        <td colspan="2" class="header"><span class='span-text'>Vendor Number: </span></td>
        <td colspan="3" class="header"><span class='span-text'>{{ $vendor_number }}</span></td>
        <td colspan="3" class="header" style="border:none"></td>
        <td colspan="3" class="header"><span class='span-text'>Special Handle Y or N</span></td>
        <td colspan="1" class="header" style="text-align:center"><span>N</span></td>
    </tr>
    @endforeach
    <tr>
        <td colspan="8" class="header" style="border:none"></td>
        <td colspan="2" class="header"><span class='span-text'>Delivery check to: </span></td>
        <td colspan="2" class="header"></td>
    </tr>
    
    <tr>
        <td colspan="12" class="header" style="border:none">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="2" class="header" ><span class='span-text'>Invoice Number:</span></td>
        <td colspan="2" class="header" ><span class='span-text'>{{ $invoice_no }}</span></td>
        <td colspan="2" class="header" style="border:none"></td>
        <td colspan="2" class="header" ><span class='span-text'>Invoice Date:</span></td>
        <td colspan="3" class="header" ><span class='span-text'>{{$run_date}}</span></td>
        <td colspan="1" class="header" style="border:none">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="12" class="header" style="border:none">&nbsp;</td>
    </tr>
    <tr>
        <td colspan="4" class="header" ><span class='span-text'>Payee Name and Address:</span></td>
        <td colspan="8" class="header" style="border:none"></td>
    </tr>
    <tr>
        <td colspan="2" class="header" ><span class='span-text'>Name:</span></td>
        <td colspan="9" class="header" ><span class='span-text'>{{$payee_name}}</span></td>
        <td colspan="1" class="header" style="border:none"></td>
    </tr>
    <tr>
        <td colspan="2" class="header" ><span class='span-text'>Name Con't:</span></td>
        <td colspan="9" class="header" ><span class='span-text'></span></td>
        <td colspan="1" class="header" style="border:none"></td>
    </tr>
    <tr>
        <td colspan="2" class="header" ><span class='span-text'>Address-1:</span></td>
        <td colspan="9" class="header" ><span class='span-text'>{{$address_one}}</span></td>
        <td colspan="1" class="header" style="border:none"></td>
    </tr>
    <tr>
        <td colspan="2" class="header" ><span class='span-text'>Address-2:</span></td>
        <td colspan="9" class="header" ><span class='span-text'>{{$address_two}}</span></td>
        <td colspan="1" class="header" style="border:none"></td>
    </tr>
    <!-- <tr>
        <td colspan="2" class="header" style="border:none"></td>
        <td colspan="3" class="header" ><span class='span-text'>City</span></td>
        <td colspan="3" class="header" ><span class='span-text'>State</span></td>
        <td colspan="3" class="header" ><span class='span-text'>Zip</span></td>
        <td colspan="1" class="header" style="border:none"></td>
    </tr> -->
    <!-- <tr>
        <td colspan="2" class="header" style="border:none"></td>
        <td colspan="3" class="header" ><span class='span-text'>&nbsp;</span></td>
        <td colspan="3" class="header" ><span class='span-text'>&nbsp;</span></td>
        <td colspan="3" class="header" ><span class='span-text'>&nbsp;</span></td>
        <td colspan="1" class="header" style="border:none"></td>
    </tr> -->
    <tr>
        <td colspan="12" class="header" style="border:none">&nbsp;</td>
    </tr>
@foreach($data as $agreement)
  <?php 
  $run_date = format_date('now', 'm/d/Y \a\t h:i:s A');
  $level1_signature = "NA";
  $level2_signature = "NA";
  $level3_signature = "NA";
  $level4_signature = "NA";
  $level5_signature = "NA";
  $level6_signature = "NA";

  ?>
    @foreach($agreement["practices"] as $practice)
    <?php $grant_total = 0;?>
    @foreach($practice["contract_data"] as $contract_data)
      @if(count($contract_data["breakdown"]) > 0)
      <tr>
        <td colspan="2" class="header" ><span class="text-color-red span-text">COID</span></td>
        <td colspan="3" class="header" ><span class="text-color-red span-text">GL Code</span></td>
        <td colspan="3" class="header" ><span class="text-color-red span-text">Description</span></td>
        <td colspan="1" class="header" ><span class="text-color-red span-text">Hours</span></td>
        <td colspan="1" class="header" ><span class="text-color-red span-text">Rate</span></td>
        <td colspan="1" class="header" ><span class="text-color-red span-text">Total</span></td>
        <td colspan="1" class="header" ><span class="text-color-red span-text">1099 Code</span></td>
      </tr>
      <?php $rowspan= count($contract_data["breakdown"]);
       $rownumber=1;
       $midrow=intval($rowspan/2);
       $midrow=$midrow < 1 ? 1 : $midrow; 
       $check = 0;
       ?>
       <?php
       // Code added for formatted description.
       $date_range = $contract_data['date_range'];
       $single_date = substr($date_range,0,10);
       $short_month_year =  date('m.y', strtotime($single_date)); //
       $custom_description = $short_month_year . '-' . $contract_data['contract_name'];
       $discription_show = strlen($custom_description) > 29 ? substr($custom_description,0,29)."..." : $custom_description;
       $amt_paid = $contract_data["amount_paid"];
       ?>
      <tr>
        @foreach($contract_data["breakdown"] as $breakdown)
        @if($check == 0)
          <td colspan="2" class="header" >{{$name_cont}}</td>
          <td colspan="3" class="header" ><span class='span-text'>{{$gl_code}}</span></td>
          <td colspan="3" class="header" style="font-size: 11px">{{$discription_show}}</td>
          <td colspan="1" class="header">{{number_format($contract_data["sum_worked_hour"], 2)}}</td>
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
          <!-- <td colspan="1" class="header">${{ formatNumber($breakdown["rate"])}}</td> -->
          <td colspan="1" class="header">${{ formatNumber($amt_paid)}}</td>
          <td colspan="1" class="header"><span class='span-text'>{{$custome_code}}</span></td>
      </tr>
       <tr>
          <?php
          $Physician_signature = $breakdown["Physician_approve_signature"];
          $Physician_date = $breakdown["Physician_signature_date"];
          $levels = $breakdown["levels"];
          $rownumber++;
          $check++;
          ?>
          @endif
        @endforeach
      @else
        <tr>
      @endif
      <td colspan="12" style="border:none"></td>
      <?php $grant_total = $grant_total+$contract_data["amount_paid"]; ?>
    </tr>
    <tr>
      <td colspan="5" class="data_header header">&nbsp;</td>
      <td class="data_header header">Physician</td>
      <td class="data_header header">Approval Level 1</td>
      <td class="data_header header">Approval Level 2</td>
      <td class="data_header header">Approval Level 3</td>
      <td class="data_header header">Approval Level 4</td>
      <td class="data_header header">Approval Level 5</td>
      <td class="data_header header">Approval Level 6</td>
    </tr>
    <tr>
      <td colspan="5">&nbsp;</td>
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
    <td colspan="5">&nbsp;</td>
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
        <td colspan="5">&nbsp;</td>
          <td> </td>
          @foreach($levels as $level_user)
            @if($level_type["type"]== "NA")
                <td><span> - <span></td>
                @else
                <td>{{$level_user["name"]}}</td>
            @endif          
          @endforeach
        </tr>

        <tr class="contract_end">
        <td colspan="5">&nbsp;</td>
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
        <td colspan="5">&nbsp;</td>
        <td colspan="2">&nbsp;</td>
        <td class="header">Practice TOTAL</td>
        <td colspan="5"><span style="float:right">${{ formatNumber($grant_total)}}<span></td>
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
.span-text{
  padding-left:5px;
  display: inline-block;
}
.text-color-red {
  color:red
}
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
  /* text-align: center; */
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
  /* font-weight: bold; */
  font-weight: normal;
}
.logo_container
{
  background-color: #000000;
  text-align: center;
}
.signature
{
  width: 50px;
  height:50px;
}
.header_red
{
  color: #000!important;
  background-color: #ffdddd!important;
}


</style>

</body>
</html>