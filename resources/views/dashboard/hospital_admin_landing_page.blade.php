@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\has_invoice_dashboard_access; @endphp
@php use function App\Start\is_approval_manager; @endphp
@php use function App\Start\is_hospital_admin; @endphp
@extends('dashboard/_index_landing_page')
@section('links')

	<div id="pie_chart_container" class="pie_chart_container">
		<div id="pie_chart1_container" class="pie_chart_inner_container">
			<div id="pie_chart1_heading" class="pie_chart_heading">
				<div id="pie_chart1_icon"></div>
				<div id="pie_chart1_heading_text" class="pie_chart_heading_text"><img class="img-responsive" src="../assets/img/default/activeContractTypes.png" alt="">Active Contract Types</div>
			</div>
			<?php
			$total_active_contract=0;
			$total_paid=0;
			$total_spend=0;
			foreach ($contract_stats as $contract_stats123)
			{
				$total_active_contract+= $contract_stats123['active_contract_count'];
				$total_spend+=$contract_stats123['total_spend'];
				$total_paid+=$contract_stats123['total_paid'];
			}
			?>
			@if($total_active_contract>0)
				<div id="pie1">
					<span id="contractDetailsSpan1" class="contractDetails"></span>
				</div>
			@else
				<div id="pie6">
					<span class="contractDetails">No Active Contract Present.</span>
				</div>
			@endif
		</div>
		<div id="pie_chart2_container" class="pie_chart_inner_container">
			<div id="pie_chart2_heading" class="pie_chart_heading">

				<div id="pie_chart2_icon"></div>
				<div id="pie_chart2_heading_text" class="pie_chart_heading_text"><img class="img-responsive" src="../assets/img/default/contractSpendYearDate.png" alt="">Contract Spend Year To Date</div>
			</div>
			@if($total_paid>0)
				<div id="pie3">
					<span id="contractDetailsSpan3" class="contractDetails"></span>
				</div>
				@else
						<!--<div class="no_amount_to_display"><span class="noAmountPaid"></span> No amount paid.</div>-->
				<div id="pie4">
					<span class="contractDetails">No amount paid.</span>
				</div>
			@endif

		</div>
		<div id="pie_chart3_container"  class="pie_chart_inner_container">
			<div id="pie_chart3_heading" class="pie_chart_heading">
				<div id="pie_chart3_icon"></div>
				<div id="pie_chart3_heading_text" class="pie_chart_heading_text"><img class="img-responsive" src="../assets/img/default/totalContractSpend.png" alt="">Total Contract Spend</div>
			</div>

			@if($total_spend>0)
				<div id="pie2">
					<span id="contractDetailsSpan2" class="contractDetails"></span>
				</div>
			@else
				<div id="pie5">
					<span class="contractDetails">No total spend amount.</span>
				</div>
			@endif
		</div>
	</div>



	<div id="contract_details" class="contract_details">
	</div>

	<div class="contractOverview text-center">
		@if(is_super_hospital_user())
			<div class="countStatus super_hospital_users_overview">
				@else
					<div class="countStatus">
						@endif
						<h3 class="overviewHeading clearfix"><img class="img-responsive" src="../assets/img/default/contractOverview.png" alt="">Contract Management</h3>
						<ul>
						@if(has_invoice_dashboard_access())
							<li>
						@else					
							<li style="margin-left:25%; border-right: solid 0px #d6d6d6;">
						@endif
								<div class="contractLogs">
									<div class="contractCount">
										<div class="loader" style="display:none"></div>
										<span class="" id="contract_logs_details_count"></span>
									</div>
									<span id="contract_logs_details_label">
										@if(is_approval_manager())
											<a href="{{ URL::route('approval.index') }}">Contracts Pending for Approval</a>
										@else
											<a href="#" disabled="true">Contracts Pending for Approval</a>
										@endif
									</span>
								</div>
							</li>
							@if(has_invoice_dashboard_access())
							<li>
								<div class="contractLogs">
									<div class="contractCount">
										<div class="loaderPayment" style="display:none"></div>
										<span class="" id="contracts_ready_payment_count"></span>
									</div>
									<span id="contracts_ready_payment_label">
										@if ((count($current_user->hospitals) == 1)&&($invoice_dashboard_display== 1))
											<a href="{{ URL::route('agreements.payment', $current_user->hospitals[0]->id) }}">Contracts Ready for Payment</a>
										@elseif($invoice_dashboard_display== 1)
											<a href="{{ URL::route('hospitals.index') }}?type=2">Contracts Ready for Payment</a>
										@else
											<a href="#">Contracts Ready for Payment</a>
										@endif
									</span>
								</div>
							</li>
							@endif
						</ul>
						<div style="border-top: 1px #d6d6d6 solid; margin-top: 21px"></div>
						@if(has_invoice_dashboard_access())
							<div>
								<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
									<h4 class="" style="width: 330px; clear: both; margin: 10px auto 30px; font-size: 20px;padding-top: 21px; font-family: 'open sans'; font-size: 14px;">
										@if ($note_display_count_amended > 0)
											<span class="" style="font-weight: 700; text-align: center">NOTE:</span> You have <a class="" style="font-size:120%;font-weight:bold;background:none;border:none;width:0px;height:0px;text-align:left;line-height:0px;margin:0;color:#f68a1f;float:none;box-shadow:none;"  href="{{ URL::route('hospitals.amendedContracts') }}">{{$note_display_count_amended}}</a> contract(s) that have been amended in the last <span style="font-weight: bold;">30</span> days
										@else
											<span class="" style="font-weight: 700;"><br/><br/></span>
										@endif
									</h4>
								</div>
								<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
									<h4 class="" style="width: 330px; clear: both; margin: 10px auto 30px; font-size: 20px;padding-top: 21px; font-family: 'open sans'; font-size: 14px;">
										@if ($note_display_count > 0)
											<span class="" style="font-weight: 700;">NOTE:</span> You will have <a class="" style="font-size:120%;font-weight:bold;background:none;border:none;width:0px;height:0px;text-align:left;line-height:0px;margin:0;color:#f68a1f;float:none;box-shadow:none;"  href="{{ URL::route('hospitals.expiringContracts') }}">{{$note_display_count}}</a> contract(s) that will expire in the next <span style="font-weight: bold;">90</span> days
										@else
											<span class="" style="font-weight: 700;"><br/><br/></span>
										@endif
									</h4>
								</div>
							</div>
						@else
							<div>
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
									<h4 class="" style="width: 330px; clear: both; margin: 10px auto 30px; font-size: 20px;padding-top: 21px; font-family: 'open sans'; font-size: 14px;">
										@if ($note_display_count_amended > 0)
											<span class="" style="font-weight: 700; text-align: center">NOTE:</span> You have <a class="" style="font-size:120%;font-weight:bold;background:none;border:none;width:0px;height:0px;text-align:left;line-height:0px;margin:0;color:#f68a1f;float:none;box-shadow:none;"  href="{{ URL::route('hospitals.amendedContracts') }}">{{$note_display_count_amended}}</a> contract(s) that have been amended in the last <span style="font-weight: bold;">30</span> days
										@else
											<span class="" style="font-weight: 700;"><br/><br/></span>
										@endif
									</h4>
								</div>
							</div>
						@endif

					</div>
					
					<div class="expiringAggrement"> <!--Chaitraly::Re ordered Tan boxes 21/7/2021-->
						<div class="redirectionBtn">
							<ul>
								<li><a href="{{ env('PRODUCTIVITY_URL') }}">Productivity</a></li>
								
								@if (count($current_user->hospitals) == 1)
											<!-- <li><a href="{{ URL::route('hospitals.reports', $current_user->hospitals[0]->id) }}">Reporting</a></li> -->
											@if(has_invoice_dashboard_access())
											@if($invoice_dashboard_display== 1)
											<li><a href="{{ URL::route('agreements.payment', $current_user->hospitals[0]->id) }}">Payment Processing</a></li>
											@endif
											@endif
											<!-- {{ logger($invoice_dashboard_display) }} -->
								@else
											<!-- <li><a href="{{ URL::route('hospitals.index') }}?type=1">Reporting</a></li> -->
											@if(has_invoice_dashboard_access())
											@if($invoice_dashboard_display== 1)
											<li><a href="{{ URL::route('hospitals.index') }}?type=2">Payment Processing</a></li>
											@endif
											@endif
								@endif
								
								
								@if(is_super_hospital_user() || is_hospital_admin())
									<li><a href="{{ URL::route('approval.paymentStatus') }}">Payment Status</a></li>
								@endif
								@if (count($current_user->hospitals) == 1)
									<li><a href="{{ URL::route('hospitals.reports', $current_user->hospitals[0]->id) }}">Reporting</a></li>
								@else
									<li><a href="{{ URL::route('hospitals.index') }}?type=1">Reporting</a></li>
								@endif
								@if(is_super_hospital_user() || is_hospital_admin())
									@if($performance_dashboard_display == 1)
										<li><a href="{{ URL::route('performance_dashboard.display') }}" >Provider Performance Dashboard</a></li>
									@endif
									@if($compliance_dashboard_display == 1)
										<li><a href="{{ URL::route('compliance_dashboard.display') }}">Compliance Dashboard</a></li>
									@endif
								@endif

{{--								<li><a href="{{ URL::route('dashboard.admin') }}">Hospital Settings</a></li>--}}
								<li><a href="{{ URL::route('dashboard.rehab_admin') }}">Rehab Management</a></li>
							</ul>
						</div>
						<h4 class="" style="width: 330px; clear: both; margin: 10px auto 30px; font-size: 20px; border-top: 1px #d6d6d6 solid; padding-top: 34px; font-family: 'open sans'; font-size: 14px;">
							@if ($lawson_interfaced_contracts_count_exclude_one > 0)
								<span class="" style="font-weight: 700;">NOTE:</span> There are <a class="" style="font-size:120%;font-weight:bold;background:none;border:none;width:0px;height:0px;text-align:left;line-height:0px;margin:0;color:#f68a1f;float:none;box-shadow:none;"  href="{{ URL::route('hospitals.isLawsonInterfacedContracts') }}">{{$lawson_interfaced_contracts_count_exclude_one}}</a> contract(s) that are interfaced to Lawson
							@else
								<span class="" style="font-weight: 700;"><br/><br/></span>
							@endif
						</h4>

					</div>

			</div>

			<div class="facilityAgreement col-xs-12">
				<span class="facilityAgreementHeading">List of Contract Specifics</span>
				<div class="loaderAgreement" style="margin:auto;"></div>
				<span id="agreementDataByAjax"></span>
				<!-- <span class="facilityAgreementHeading">List of Contract Specifics</span> -->
			</div>

			<script type="text/javascript" src="{{ asset('assets/js/d3.min.js') }}"></script>
			<script type="text/javascript" src="{{ asset('assets/js/d3pie.js') }}"></script>
			<script type="text/javascript" src="{{ asset('assets/js/numeral.js') }}"></script>
			<!--<script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>-->

			<script>
				/*For first hardcoded pie chart*/
				$(document).ready(function (){
					$('.format_amount').each(function(){
						var text=$(this).text();
						var split_text=text.split("-");
						var amount=split_text[1];
						var full_text=split_text[0]+'- '+numeral(amount).format('$0,0[.]00');
						$(this).html(full_text);
						$(this).attr('title',full_text);

					});
					//var string = numeral(509250).format('$ 0,0[.]00');

					/*var pie_div_width=parseInt((0.98)*($('#pie1').width()));
					 var pie_div_height=parseInt((0.85)*($('#pie1').width()));*/

					if ( $( "#pie1" ).length)
					{
						var pie_div_width=parseInt((0.98)*($('#pie1').width()));
						var pie_div_height=parseInt((0.85)*($('#pie1').width()));
					}
					else
					{
						var pie_div_width=parseInt((0.98)*($('#pie6').width()));
						var pie_div_height=parseInt((0.85)*($('#pie6').width()));
					}
					var pie_inner_width="80%";
					var pie_outer_width="50%";
					//for Active Contract Types Pie chart display
					if ( $( "#pie1" ).length)
					{
						var pie1 = new d3pie("pie1", {
							size: {
								canvasHeight: pie_div_height,
								canvasWidth: pie_div_width,
								pieInnerRadius: pie_inner_width,
								pieOuterRadius: pie_outer_width
							},
							data: {
								sortOrder: "label-asc",
								content: [
										<?php $total=0;?>
										@foreach ($contract_stats as $contract_stat1)
										@if($contract_stat1['contract_type_id'] == App\ContractType::CO_MANAGEMENT)
									{ label:"{{$contract_stat1['contract_type_name']}}" , value: {{$contract_stat1['active_contract_count']}}, color: "#221f1f"  },
										@elseif ($contract_stat1['contract_type_id'] == App\ContractType::MEDICAL_DIRECTORSHIP)
									{ label:"{{$contract_stat1['contract_type_name']}}s" , value: {{$contract_stat1['active_contract_count']}}, color: "#a09284"  },
										@elseif ($contract_stat1['contract_type_id'] == App\ContractType::ON_CALL)
									{ label:"{{$contract_stat1['contract_type_name']}}" , value: {{$contract_stat1['active_contract_count']}}, color: "#f68a1f"  },
										@else
									{ label:"{{$contract_stat1['contract_type_name']}}" , value: {{$contract_stat1['active_contract_count']}} },
									@endif

									<?php $total+= $contract_stat1['active_contract_count']; ?>
									@endforeach
								]
							},
							labels: {
								outer: {
									format: "label-value2",
									hideWhenLessThanPercentage: null,
									pieDistance: 10
								},
								inner: {
									format: null,
									hideWhenLessThanPercentage: null
								},
								mainLabel: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								percentage: {
									color: "#dddddd",
									font: "arial",
									fontSize: 10,
									decimalPlaces: 0
								},
								value: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								lines: {
									enabled: true,
									style: "curved",
									color: "segment"
								},
								truncation: {
									enabled: false,
									truncateLength: 30
								},
								formatter: null
							}
						});
						$('#contractDetailsSpan1').html({{$total}}+" Contract(s)");
					}

					//for Total Contract Spend Pie chart display when total contract spend is > 0
					if ( $( "#pie2" ).length)
					{
						var pie2 = new d3pie("pie2", {
							size: {
								canvasHeight: pie_div_height,
								canvasWidth: pie_div_width,
								pieInnerRadius: pie_inner_width,
								pieOuterRadius: pie_outer_width
							},
							data: {
								sortOrder: "label-asc",
								content: [
										<?php $total_spend=0;?>
										@foreach ($contract_stats as $contract_stat2)
										@if($contract_stat2['contract_type_id'] == App\ContractType::CO_MANAGEMENT)
									{ label:"{{$contract_stat2['contract_type_name']}}" , value: {{$contract_stat2['total_spend']}}, color: "#221f1f" },
										@elseif ($contract_stat2['contract_type_id'] == App\ContractType::MEDICAL_DIRECTORSHIP)
									{ label:"{{$contract_stat2['contract_type_name']}}s" , value: {{$contract_stat2['total_spend']}}, color: "#a09284" },
										@elseif ($contract_stat2['contract_type_id'] == App\ContractType::ON_CALL)
									{ label:"{{$contract_stat2['contract_type_name']}}" , value: {{$contract_stat2['total_spend']}}, color: "#f68a1f" },
										@else
									{ label:"{{$contract_stat2['contract_type_name']}}" , value: {{$contract_stat2['total_spend']}} },
									@endif

									<?php $total_spend+= $contract_stat2['total_spend']; ?>
									@endforeach
								]
							},
							labels: {
								outer: {
									format: "label-amount1",
									hideWhenLessThanPercentage: null,
									pieDistance: 10
								},
								inner: {
									format: null,
									hideWhenLessThanPercentage: null
								},
								mainLabel: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								percentage: {
									color: "#dddddd",
									font: "arial",
									fontSize: 10,
									decimalPlaces: 0
								},
								value: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								lines: {
									enabled: true,
									style: "curved",
									color: "segment"
								},
								truncation: {
									enabled: false,
									truncateLength: 30
								},
								amount: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								formatter: null
							}
						});
						$('#contractDetailsSpan2').html(numeral({{$total_spend}}).format('$0,0[.]00'));
					}

					//for Contract Spend Year To Date Pie chart display when contract spend is > 0
					if ( $( "#pie3" ).length)
					{
						var pie3 = new d3pie("pie3", {
							size: {
								canvasHeight: pie_div_height,
								canvasWidth: pie_div_width,
								pieInnerRadius: pie_inner_width,
								pieOuterRadius: pie_outer_width
							},
							data: {
								sortOrder: "label-asc",
								content: [
										<?php $total_paid=0;?>
										@foreach ($contract_stats as $contract_stat3)
										@if($contract_stat3['contract_type_id'] == App\ContractType::CO_MANAGEMENT)
									{ label:"{{$contract_stat3['contract_type_name']}}" , value: {{$contract_stat3['total_paid']}}, color: "#221f1f"  },
										@elseif ($contract_stat3['contract_type_id'] == App\ContractType::MEDICAL_DIRECTORSHIP)
									{ label:"{{$contract_stat3['contract_type_name']}}s" , value: {{$contract_stat3['total_paid']}}, color: "#a09284"  },
										@elseif ($contract_stat3['contract_type_id'] == App\ContractType::ON_CALL)
									{ label:"{{$contract_stat3['contract_type_name']}}" , value: {{$contract_stat3['total_paid']}}, color: "#f68a1f"  },
										@else
									{ label:"{{$contract_stat3['contract_type_name']}}" , value: {{$contract_stat3['total_paid']}} },
									@endif
									<?php $total_paid+= $contract_stat3['total_paid']; ?>
									@endforeach
								]
							},
							labels: {
								outer: {
									format: "label-amount1",
									hideWhenLessThanPercentage: null,
									pieDistance: 10
								},
								inner: {
									format: null,
									hideWhenLessThanPercentage: null
								},
								mainLabel: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								percentage: {
									color: "#dddddd",
									font: "arial",
									fontSize: 10,
									decimalPlaces: 0
								},
								value: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								lines: {
									enabled: true,
									style: "curved",
									color: "segment"
								},
								truncation: {
									enabled: false,
									truncateLength: 30
								},
								amount: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								formatter: null
							}
						});
						$('#contractDetailsSpan3').html(numeral({{$total_paid}}).format('$0,0[.]00'));
					}

					//for Contract Spend Year To Date Pie chart display when contract spend is =0
					if ( $( "#pie4" ).length)
					{
						var pie4 = new d3pie("pie4",{
							size: {
								canvasHeight: pie_div_height,
								canvasWidth: pie_div_width,
								pieInnerRadius: pie_inner_width,
								pieOuterRadius: pie_outer_width
							},
							data: {
								sortOrder: "label-asc",
								content: [
									{
										label: "",
										value: 100
									}
								]
							},
							labels: {
								outer: {
									format: "none",
									hideWhenLessThanPercentage: null,
									pieDistance: 10
								},
								inner: {
									format: "none",
									hideWhenLessThanPercentage: null
								},
								mainLabel: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								percentage: {
									color: "#dddddd",
									font: "arial",
									fontSize: 10,
									decimalPlaces: 0
								},
								value: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								lines: {
									enabled: false,
									style: "curved",
									color: "segment"
								},
								truncation: {
									enabled: false,
									truncateLength: 30
								},
								formatter: null
							}
						});
						//
						setTimeout(
								function()
								{
									$('#pie4 svg path').css({'fill':'#D3D3D3','pointer-events':'none'});
								}, 1000);
					}

					//for Total Contract Spend Pie chart display when Total contract spend is =0
					if ( $( "#pie5" ).length)
					{
						var pie5 = new d3pie("pie5",{
							size: {
								canvasHeight: pie_div_height,
								canvasWidth: pie_div_width,
								pieInnerRadius: pie_inner_width,
								pieOuterRadius: pie_outer_width
							},
							data: {
								sortOrder: "label-asc",
								content: [
									{
										label: " ",
										value: 100
									}
								]
							},
							labels: {
								outer: {
									format: "none",
									hideWhenLessThanPercentage: null,
									pieDistance: 10
								},
								inner: {
									format: "none",
									hideWhenLessThanPercentage: null
								},
								mainLabel: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								percentage: {
									color: "#dddddd",
									font: "arial",
									fontSize: 10,
									decimalPlaces: 0
								},
								value: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								lines: {
									enabled: false,
									style: "curved",
									color: "segment"
								},
								truncation: {
									enabled: false,
									truncateLength: 30
								},
								formatter: null
							}
						});
						setTimeout(
								function()
								{
									$('#pie5 svg path').css({'fill':'#D3D3D3','pointer-events':'none'});
								}, 1000);
					}

					//for Total Active Contract Pie chart display when No Active Contract present
					if ( $( "#pie6" ).length)
					{
						var pie6 = new d3pie("pie6",{
							size: {
								canvasHeight: pie_div_height,
								canvasWidth: pie_div_width,
								pieInnerRadius: pie_inner_width,
								pieOuterRadius: pie_outer_width
							},
							data: {
								sortOrder: "label-asc",
								content: [
									{
										label: " ",
										value: 100
									}
								]
							},
							labels: {
								outer: {
									format: "none",
									hideWhenLessThanPercentage: null,
									pieDistance: 10
								},
								inner: {
									format: "none",
									hideWhenLessThanPercentage: null
								},
								mainLabel: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								percentage: {
									color: "#dddddd",
									font: "arial",
									fontSize: 10,
									decimalPlaces: 0
								},
								value: {
									color: "#333333",
									font: "arial",
									fontSize: 10
								},
								lines: {
									enabled: false,
									style: "curved",
									color: "segment"
								},
								truncation: {
									enabled: false,
									truncateLength: 30
								},
								formatter: null
							}
						});
						setTimeout(
								function()
								{
									$('#pie6 svg path').css({'fill':'#D3D3D3','pointer-events':'none'});
								}, 1000);
					}
					getContractLogDetailCounts();
					getContractPaymentCounts();
					getAgreementDataByAjax();

					/*$('#menu>li:first-child').addClass( "active" );
					 $('#menu>li:first-child>ul>li:first-child').addClass( "active" );
					 $('#menu>li:first-child>ul>li:first-child>ul>li:first-child').addClass( "active" );*/

				});
				function callMenu(){
					// $('#menu').metisMenu();
					$('#menu').metisMenu({
						// enabled/disable the auto collapse.
						toggle: false,
						// prevent default event
						preventDefault: true,
						// default classes
						activeClass: 'active',
						collapseClass: 'collapse',
						collapseInClass: 'in',
						collapsingClass: 'collapsing',
						// .nav-link for Bootstrap 4
						triggerElement: 'a',
						// .nav-item for Bootstrap 4
						parentTrigger: 'li',
						// .nav.flex-column for Bootstrap 4
						subMenu: 'ul'
					});
				}
			</script>
			<script>
				// @description : function to call contract log details count
				// @return json
				function getContractLogDetailCounts(){
					$('.loader').show();
					$('#contract_logs_details_label').hide();
					$.ajax({
						url:'/getContractDetailsCount',
						type:'get',
						success:function(response){
							$('#contract_logs_details_count').html(response.contract_logs_details);
						},
						complete:function(){
							//$('.overlay').hide();
							$('.loader').hide();
							$('#contract_logs_details_label').show();
						}
					});
				}

				function getContractPaymentCounts(){
					$('.loaderPayment').show();
					$('#contracts_ready_payment_label').hide();
					$.ajax({
						url:'/getContractPaymentCounts',
						type:'get',
						success:function(response){
							$('#contracts_ready_payment_count').html(response.contracts_ready_payment);
						},
						complete:function(){
							//$('.overlay').hide();
							$('.loaderPayment').hide();
							$('#contracts_ready_payment_label').show();
						}
					});
				}
				// @description : function to call agreement details count
				// @return json
				function getAgreementDataByAjax(){
					$('.loaderAgreement').show();
					$.ajax({
						url:'/getAgreementDataByAjax',
						type:'get',
						success:function(response){
							//console.log("agreementDataByAjax:",response);
							$('#agreementDataByAjax').html(response);
						},
						complete:function(){
							// $('#menu>li:first-child').addClass( "active" );
						//	$('#menu>li:first-child>ul>li:first-child').addClass( "active" );
						//	$('#menu>li:first-child>ul>li:first-child>ul>li:first-child').addClass( "active" );
							callMenu();
							$('.loaderAgreement').hide();
						}
					});

				}
			</script>
			<style>
				.contractContent
				{
					cursor: pointer;
					margin: 10px 15px !important;
				}
				.pie_chart_heading_text
				{
					text-align: center;
					font-weight: bold;
					font-size: 17px;
				}
				.pie_chart_heading
				{
					border-bottom: 1px #d6d6d6 solid;
					padding: 1% 0;
				}
				.pie_chart_inner_container
				{
					width:32%;
					float:left;
					/* margin: 0 0.5%; */
					margin: 0 0.5%;
					padding: 1% 1%;
					background: #fff;
					box-shadow: 0 0 2px #dadada;
					border-radius: 5px;
					position: relative;
				}
				.contract_details
				{
					width: 100%;
					/* float: left; */
					clear: both;
				}
				.contract_overview_heading_container_row, .contract_counters_heading_container_row
				{
					width: 100%;
					float: left;
					border: 1px solid;
				}
				.contract_overview_heading_container_left, .contract_counter_heading_container_left
				{
					width: 30%;
					float: left;
					visibility: hidden;
				}
				.contract_overview_heading_container_main, .contract_counter_heading_container_main
				{
					width: 40%;
					float: left;
				}
				.contract_overview_heading_text{
					text-align: center;
					font-weight: bold;
					font-size: 30px;
				}
				.contract_overview_heading_container_right, .contract_counter_heading_container_right
				{
					width: 30%;
					float: left;
					visibility: hidden;
				}
				.heading_not_active_contracts
				{
					clear: both;
					display: block;
					font-size: 30px;
				}
				.no_amount_to_display
				{
					font-size: 20px;
					padding: 20px;
					text-align: center;
				}
				#default .navbar {
					box-shadow: none;
				}
				.landing_page_main .container-fluid {
					padding-right: 0;
					padding-left: 0;
				}
				.landing_page_main .page-header {
					background: #fff; margin-top: 0; padding: 10px 50px;
				}
				.contractDetails {
					position: absolute;
					bottom: 30px;
					left: 0;
					right: 0;
					text-align: center;
					color: #221f1f;
					font-size: 16px;
					font-family: 'open sans';
					font-weight: 700;
				}
			</style>
			<style>
				.loader,.loaderAgreement,.loaderPayment {
					border: 16px solid #f3f3f3;
					border-radius: 50%;
					/*border-top: 16px solid #3498db;*/
					border-top: 16px solid rgb(246, 138, 31);
					width: 120px;
					height: 120px;
					-webkit-animation: spin 2s linear infinite; /* Safari */
					animation: spin 2s linear infinite;
				}

				/* Safari */
				@-webkit-keyframes spin {
					0% { -webkit-transform: rotate(0deg); }
					100% { -webkit-transform: rotate(360deg); }
				}

				@keyframes spin {
					0% { transform: rotate(0deg); }
					100% { transform: rotate(360deg); }
				}
			</style>
@endsection
