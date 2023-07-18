<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body class="@yield('body-class', 'default')">
	<?php $count = 0; ?>
	@foreach($results as $agreement)
		<?php $count ++; ?>
		<div class="panel panel-default">
			<div class="panel-body">
				@if($count == 1)
					<div class="col-md-12" style="padding:0px; font-size: 15px; text-align:left; ">{{ $agreement['attestation_type'] }}</div>
				@endif
				<div class="col-md-12" style="padding:0px; font-size: 17px; text-align:center; "><b>Agreement Name : {{ $agreement['agreement_name'] }}</b></div>
				@foreach($agreement['contracts'] as $contracts)
					<div class="col-md-12" style="padding:0px; font-size: 17px; text-align:center; margin-top: 5px;"><b>Contract Name : {{ $contracts['contract_name'] }} => {{ $contracts['submitted_for_date'] }}</b></div>
					<div class="col-md-12" style="padding:0px; font-size: 17px; text-align:center; margin-top: 5px;"><b>Physician Name : {{ $contracts['physician_name'] }}</b></div>
					<div style="border-bottom: 2px solid #ddd; margin-top: 10px; margin-bottom: 20px;"></div>
					@foreach($contracts['attestations'] as $attestation)
						<div class="col-md-12" style="padding:0px; font-size: 17px; text-align:center; margin-top: 5px;"><b>{{ $attestation['attestation_name'] }}</b></div>
						@foreach($attestation['questions'] as $question)
							<div class="col-md-12">{!! $question->question !!}</div>
							<div class="col-md-12" style="word-wrap: break-word;"> Answer : {{ $question->answer }} </div>
							<div style="border-bottom: 1px solid #ddd; margin-top: 10px; margin-bottom: 20px;"></div>
						@endforeach
					@endforeach
				@endforeach
			</div>
			<div class="panel-footer clearfix">
				
			</div>
		</div>
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
