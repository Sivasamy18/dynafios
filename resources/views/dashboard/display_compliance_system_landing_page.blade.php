@php use function App\Start\is_health_system_region_user; @endphp
@extends('dashboard/_index_landing_page')

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
		width:48%;
		float:left;
		/* margin: 0 0.5%; */
		margin: 0 0.5%;
		padding: 1% 1%;
		background: #fff;
		box-shadow: 0 0 2px #dadada;
		border-radius: 5px;
		position: relative;
		height: 341px;
	}
	.contract_details
	{
		width: 100%;
		/* float: left; */
		clear: both;
	}

	.contract_details_test
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
		font-size: 12px;
		font-family: 'open sans';
		font-weight: 700;
	}
	.loader,.loaderAgreement,.loaderPayment,.loaderPopup,
	.loaderSpendYTD,.loaderTypeEffi,.loaderGaugeEffectiveness,
	.loaderGaugeActual,.loaderTypeAlerts,.loaderByRegions,.loaderByFacility,
	.loaderRejectionRate, .loaderRejectionRateOverallCompared, .loaderbyphysician,
	.loaderbypractice, .loaderbyreason, .loaderbyapprover{
		border: 16px solid #f3f3f3;
		border-radius: 50%;
		/*border-top: 16px solid #3498db;*/
		border-top: 16px solid rgb(246, 138, 31);
		width: 120px;
		height: 120px;
		-webkit-animation: spin 2s linear infinite; /* Safari */
		animation: spin 2s linear infinite;
		margin: 20% auto;
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

	.lable-align{
		text-align: right;
		padding-right: 0;
		margin-top: 7px;
	}

	.align-center{
		margin: 0 auto;
		float: none;
	}

	.contractDetails{
		position: relative;
		bottom: 125px;
		color: #d3d3d3;
	}

	.contractDetailsLine{
		position: absolute;
		bottom: 148px;
		left: 46px;
		color: #FFFFFF;
		width: 84%;
		border-top: 2px dashed #000;
	}

	#gauge_chart_container{
		margin-top: 20px;
	}

	.gauge table,.gauge1 table,.gauge-no table{
		margin: 0 auto !important;
	}

	.gauge svg g text,.gauge1 svg g text {
		font-size: 16px;
	}

	.countStatus, .expiringAggrement{
		height: 341px;;
	}

	.loaderByRegions @if(is_health_system_region_user()) , .loaderByFacility @endif{
		margin: 10% auto;
	}

	.loaderbyphysician @if(is_health_system_region_user()) , .loaderByFacility @endif{
		margin: 10% auto;
	}
	.loaderPopup{
		margin: 5% auto;
	}

	#facilityContractCount .google-visualization-table-page-number{
		width: 20px;
		height: 15px;
		margin: 0;
		line-height: 1.4;
	}

	.row-height{
		height: 20px;
	}

	#facilityContractCount table thead tr{
		height: 60px;
	}

	#facilityContractCount table thead tr th{
		text-align: center;
		font-size: 15px;
		font-family: 'open sans';
		font-weight: 700;
	}
/*To remove fliker effect on donut hover*/
	#donutchart svg > g > g:last-child,#donut_regions svg > g > g:last-child,
	#donutchart1 svg > g > g:last-child
	{
		pointer-events: none;
	}

	/*#donutchart svg > g > g > path{
		display: none;
	}
	#donutchart svg > g > g > circle{
		display: none;
	}*/

	#agreementDataOverlay{
		position: absolute;
		width: 100%;
		height: 100%;
		background: transparent;
		z-index: 9;
		display: none;
	}
</style>
@section('links')
	<div class="appDashboardFilters col-md-12">
	
						<div>	<input type="hidden" name="region" id="region" value="{{$group_id}}"></div>	
						
						<div class="col-md-5">
									<div class="form-group  col-xs-12">
										<label class="col-xs-3 control-label lable-align">Organization: </label>
										<div class="col-md-9 col-sm-10 col-xs-10">
											{{ Form::select('hospital', $hospitals, Request::old('hospital',0), [ 'id'=>'hospital','class' => 'form-control' ]) }}
										</div>
									</div>
								</div>
								<div class="col-md-3"></div>
								<div class="col-md-4 complianceDashboardReportsButton">
								<div class="form-group col-xs-12">
									<a href="{{route('compliance.complianceReport', $group_id)}}">Reports</a>
									<input type="hidden" name="group" id="group" value="{{$group_id}}">
								</div>
							</div>
								
						</div>
						<div id="pie_chart_container" class="pie_chart_container">
							<div class="pie_chart_inner_container">
								<div id="gauge_chart1_heading" class="pie_chart_heading">
									<div id="gauge_chart1_icon"></div>
									<div id="gauge_chart1_heading_text" class="pie_chart_heading_text">Rejection Rate – Overall</div>
								</div>
								<div class="loaderRejectionRate"></div>
								<div id="gauge_chart1" class="text-center"></div>
							</div>
							<div class="pie_chart_inner_container">
								<div id="pie_chart2_heading" class="pie_chart_heading">
									<div id="pie_chart2_icon"></div>
									<div id="pie_chart2_heading_text" class="pie_chart_heading_text">Rejection Rate – Contract Type</div>
								</div>
								<div class="loader"></div>
								<div id="pie_chart2" class="text-center"></div>
							</div>

						<div class="contract_details"></div>

						<div id="gauge_chart_container" class="pie_chart_container">
							<div class="pie_chart_inner_container">
								<div id="pie_chart5_heading" class="pie_chart_heading">
									<div id="pie_chart5_icon"></div>
									<div id="pie_chart5_heading_text" class="pie_chart_heading_text">Rejection Rate – Approver </div>
								</div>
								<div class="loaderbyapprover"></div>
								<div id="pie_chart5" class="text-center"></div>
							</div>
							<div class="pie_chart_inner_container">
								<div id="pie_chart4_heading" class="pie_chart_heading">
									<div id="pie_chart4_icon"></div>
									<div id="pie_chart4_heading_text" class="pie_chart_heading_text">Rejection Rate – Reason</div>
								</div>
								<div class="loaderbyreason"></div>
								<div id="pie_chart4" class="text-center"></div>
							</div>
						</div>

						<div class="contract_details"></div>

						<div class="contractOverview text-center pie_chart_container">
							<div class="pie_chart_inner_container">
								<div id="pie_chart3_heading" class="pie_chart_heading">
									<div id="pie_chart3_icon"></div>
									<div id="pie_chart3_heading_text" class="pie_chart_heading_text">Rejection Rate – Practice</div>
								</div>
								<div class="loaderbypractice"></div>
								<div id="pie_chart3" class="text-center"></div>
							</div>

							<div class="pie_chart_inner_container">
								<div id="pie_chart1_heading" class="pie_chart_heading">
									<div id="pie_chart1_icon"></div>
									<div id="pie_chart1_heading_text" class="pie_chart_heading_text">Rejection Rate – Provider</div>
								</div>
								<div class="loaderbyphysician"></div>
								<div id="pie_chart1" class="text-center"></div>
							</div>
						</div>

						<div class="contract_details"></div>

						<div class="contractOverview text-center pie_chart_container">
							<div class="pie_chart_inner_container">
								<div id="pie_chart3_heading" class="pie_chart_heading">
									<div id="pie_chart3_icon"></div>
									<div id="pie_chart3_heading_text" class="pie_chart_heading_text">Average Duration of Payment Approval</div>
								</div>
								<div class="loaderTypeEffi"></div>
								<div id="bar_chart1"></div>
							</div>

							<div class="pie_chart_inner_container">
								<div id="pie_chart3_heading" class="pie_chart_heading">
									<div id="pie_chart3_icon"></div>
									<div id="pie_chart3_heading_text" class="pie_chart_heading_text">Average Duration of Provider Approval</div>
								</div>
								<div class="loaderTypeAlerts"></div>
								<div id="bar_chart2"></div>
							</div>
						</div>
						
						<div class="facilityAgreement col-xs-12 hide">
							<span class="facilityAgreementHeading">List of Contract Specifics</span>
							<div class="loaderAgreement" style="margin:auto;"></div>
							<div id="agreementDataOverlay"></div>
							<span id="agreementDataByAjax"></span>
							
						</div>
						<style type="text/css">
							/* HOVER STYLES */
							div#pop-up {
								display: none;
								position: fixed;
								/* width: 70%; */
								padding: 10px;
								background: #292525ad;
								/* color: #000000; */
								/* border: 1px solid #1a1a1a; */
								font-size: 90%;
								/* top: -100%; */
								left: 0;
								right: 0;
								margin: 0 auto;
								transition: 1s;
								/* overflow-x: auto; */
								/* height: 50%;*/
							}
							div#pop-up .modal-dialog{
								width:80%;
							}
						</style>
						<!-- HIDDEN / POP-UP DIV -->

						<div id="pop-up" class="modal">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header">
										<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
										<h4 class="modal-title">Lists</h4>
									</div>
									<div class="modal-body">
										<div class="loaderPopup" style="display:none"></div>
									</div>

								</div>
								<!-- /.modal-content -->
							</div>
							<!-- /.modal-dialog -->
						</div><!-- /.modal -->

						<!--<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.0/jquery.min.js"></script>-->
						<link rel="stylesheet"  href="{{ asset('assets/css/lightslider.css') }}"/>
						<script type="text/javascript" src="{{ asset('assets/js/lightslider.js') }}"></script>
						<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
						<script type="text/javascript" src="{{ asset('assets/js/numeral.js') }}"></script>
						<!--<script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>-->

						<script>
							h_id_array = new Array();
							google.charts.load('current', {packages: ['corechart', 'bar','gauge','table']});
							/*For first hardcoded pie chart*/
							$(document).ready(function (){
								$(".close").click(function(){
									$('div#pop-up  .modal-dialog').css('top','-100%');
									$('body').css('overflow-y', 'auto');
									setTimeout(function(){
										$('div#pop-up').hide();
										//$('div#pop-up').css('transition','1s');
									},100);
								});

								$("#hospital").change(function (){
									getRejectionRateOverallcomparedChart();
									getRejectionByContractTypeChart();
									getRejectionByApproverChart();
									getRejectionByReasonChart();
									getRejectionByPracticeChart();
									getRejectionByphysicianChart();
									getAverageDurationOfApprovalTimeChart();
									getAverageDurationOfTimeBetweenApproveLogs();
								});
								$('.format_amount').each(function(){
									var text=$(this).text();
									var split_text=text.split("-");
									var amount=split_text[1];
									var full_text=split_text[0]+'- '+numeral(amount).format('$0,0[.]00');
									$(this).html(full_text);
									$(this).attr('title',full_text);

								});
								var string = numeral(509250).format('$ 0,0[.]00');
								getRejectionRateOverallcomparedChart();
								getRejectionByContractTypeChart();
								getRejectionByApproverChart();
								getRejectionByReasonChart();
								getRejectionByPracticeChart();
								getRejectionByphysicianChart();
								getAverageDurationOfApprovalTimeChart();
								getAverageDurationOfTimeBetweenApproveLogs();
								
								// setTimeout(function() {
								// 	getAverageDurationOfApprovalTimeChart();
								// 	getAverageDurationOfTimeBetweenApproveLogs();
								// }, 1200);
								
								// getAgreementDataByAjax();

							});
							function callMenu(){
                                var index = 0;
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
								}).on('show.metisMenu', function(event) {
                                    var h_id = $(event.target).parent('li').children('a').children('div').children('span').last().attr('data-info');
                                    //alert(h_id + ' opened');
                                    if(h_id > 0 && h_id_array.indexOf(h_id) == -1) {
										$('#agreementDataOverlay').show();
                                        $.ajax({
                                            url: '/getFacilityContractSpecifyDataByAjax/' + h_id,
                                            type: 'get',
                                            success: function (response) {
                                                $('#hos-list-'+h_id).html(response);
                                            },
                                            complete: function () {
                                                $(event.target).parent('li').children('a').children('div').children('span').last().attr('data-info','0');
                                                $('#menu').metisMenu('dispose');
                                                callMenu();
												$('#agreementDataOverlay').hide();
                                            }
                                        });
                                        index = 1;
										h_id_array.push(h_id);
                                    }
                                });
							}
							// @description : function to call  pie charts
							// @return html

							function getRejectionByphysicianChart(){
								$('#pie_chart1').html('');
								$('.loaderbyphysician').show();
								$.ajax({
									url:'/getRejectionByphysicianChart/' + $("#hospital").val(),
									type:'get',
									success:function(response){
										$('#pie_chart1').html(response);
									},
									complete:function(){
										$('.loaderbyphysician').hide();
									}
								});
							}

							function getRejectionByContractTypeChart(){
								$('#pie_chart2').html('');
								$('.loader').show();
								$.ajax({
									url:'/getRejectionByContractTypeChart/'+ $("#hospital").val(),
									type:'get',
									success:function(response){
										$('#pie_chart2').html(response);
									},
									complete:function(){
										$('.loader').hide();
									}
								});
							}

							function getRejectionByPracticeChart(){
								$('#pie_chart3').html('');
								$('.loaderbypractice').show();
								$.ajax({
									url:'/getRejectionByPracticeChart/'+ $("#hospital").val(),
									type:'get',
									success:function(response){
										$('#pie_chart3').html(response);
									},
									complete:function(){
										//$('.overlay').hide();
										$('.loaderbypractice').hide();
									}
								});
							}

							function getRejectionByReasonChart(){
								$('#pie_chart4').html('');
								$('.loaderbyreason').show();
								$.ajax({
									url:'/getRejectionByReasonChart/'+ $("#hospital").val(),
									type:'get',
									success:function(response){
										$('#pie_chart4').html(response);
									},
									complete:function(){
										//$('.overlay').hide();
										$('.loaderbyreason').hide();
									}
								});
							}

							function getRejectionByApproverChart(){
								$('#pie_chart5').html('');
								$('.loaderbyapprover').show();
								$.ajax({
									url:'/getRejectionByApproverChart/'+ $("#hospital").val(),
									type:'get',
									success:function(response){
										$('#pie_chart5').html(response);
									},
									complete:function(){
										//$('.overlay').hide();
										$('.loaderbyapprover').hide();
									}
								});
							}

							// @description : function to call  dial charts
							// @return html
							function getAverageDurationOfApprovalTimeChart(){
								$('#bar_chart1').html('');
								$('.loaderTypeEffi').show();
								$.ajax({
									url:'/getAverageDurationOfApprovalTimeChart/'+$("#hospital").val(),
									type:'get',
									success:function(response){
										$('#bar_chart1').html(response);
									},
									complete:function(){
										//$('.overlay').hide();
										$('.loaderTypeEffi').hide();
									}
								});
							}
							
							// @description : function to call gauge charts 
							// @return html
							function getRejectionRateOverallcomparedChart(){
								$('#gauge_chart1').html('');
								$('.loaderRejectionRate').show();
								$.ajax({
									url:'/getRejectionRateOverallcomparedChart/'+ $("#hospital").val(),
									type:'get',
									success:function(response){
										$('#gauge_chart1').html(response);
									},
									complete:function(){
										//$('.overlay').hide();
										$('.loaderRejectionRate').hide();
									}
								});
							}
							
							// @description : function to get Average duration of time between approve logs 
							// @return json
							function getAverageDurationOfTimeBetweenApproveLogs(){
								$('#bar_chart2').html('');
								$('.loaderTypeAlerts').show();
								$.ajax({
									url:'/getAverageDurationOfTimeBetweenApproveLogs/'+$("#hospital").val(),
									type:'get',
									success:function(response){
										$('#bar_chart2').html(response);
									},
									complete:function(){
										//$('.overlay').hide();
										$('.loaderTypeAlerts').hide();
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
										$('#menu>li:first-child').addClass( "active" );
										$('#menu>li:first-child>ul>li:first-child').addClass( "active" );
										$('#menu>li:first-child>ul>li:first-child>ul>li:first-child').addClass( "active" );
										callMenu();
										$('.loaderAgreement').hide();
									}
								});

							}

						</script>
						<style>

							.countStatus{
								padding: 0.15% 1%;
							}

							#default .table th {
								position: sticky;
								top: 0;
							}

							.modal-body {
								overflow-y: auto;
								max-height: 426px;
								z-index: 10;
								padding-top: 0;
								margin-top: 15px;
							}
							.modal-body table {
								font-size: 13px;
							}

							.expiringAggrement{
								margin: 0 0 0 .5%;
								padding: 0;
								@if(is_health_system_region_user())
								width: 98%;
								@endif
							}
						</style>
@endsection
