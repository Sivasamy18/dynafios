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
	.provider_comparison
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
		/* box-shadow: 0 0 2px #dadada; */
		border-radius: 5px;
		position: relative;
		height: 341px;
	}
	.pie_chart_outer_container
	{
		width:100%;
		float:left;
		/* margin: 0 0.5%; */
		margin: 0 0.5%;
		padding: 1% 1%;
		background: #fff;
		box-shadow: 0 0 2px #dadada;
		border-radius: 5px;
		position: relative;
		height: 445px;
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
	.loaderContractTypes, .loaderSpecialty, .loaderProviders,
	.loaderContractTypesactual, .loaderSpecialtyactual, .loaderProvidersactual{
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
	
		<div><input type="hidden" name="group_id" id="group_id" value="{{$group_id}}"></div>	
			<div class="col-md-3">
				<div class="form-group  col-xs-12">
					<label class="col-xs-3 control-label lable-align">Regions: </label>
					<div class="col-md-9 col-sm-10 col-xs-10">
						{{ Form::select('regions', $regions, Request::old('region',0), [ 'id'=>'regions','class' => 'form-control' ]) }}
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group  col-xs-12">
					<label class="col-xs-4 control-label lable-align">Organization: </label>
					<div class="col-md-8 col-sm-8 col-xs-8">
						{{ Form::select('hospital', $hospitals, Request::old('hospital',0), [ 'id'=>'hospital','class' => 'form-control' ]) }}
					</div>
				</div>
			</div>
			<div class="col-md-5">
				<div class="form-group  col-xs-12">
					<label class="col-xs-4 control-label lable-align">Practice Type: </label>
					<div class="col-md-6 col-sm-6 col-xs-6">
						{{ Form::select('practice_type', $practice_types, Request::old('practice_type',0), [ 'id'=>'practice_type','class' => 'form-control' ]) }}
					</div>
					<div class="col-md-2 col-sm-2 col-xs-2 performanceDashboardReportsButton" style="padding-top: 10px;">
						<a href="{{route('performance.report')}}">Reports</a>
						<input type="hidden" name="group" id="group" value="{{$group_id}}">
					</div>
				</div>
			</div>
		</div>
		
		<div id="pie_chart_header_first_block" class="pie_chart_outer_container">
			<div id="provider_comparison" class="pie_chart_heading_text">Provider Comparison - Management Duty (Time)</div>
			<div style="border-top: 1px #d6d6d6 solid; margin-top: 21px"></div>
			
			<div id="pie_chart_container" class="pie_chart_container">
				<div class="pie_chart_inner_container">
					<div id="pie_chart1_heading" class="pie_chart_heading">
						<div id="pie_chart1_icon"></div>
						<div id="pie_chart1_heading_text" class="pie_chart_heading_text">Contract Type</div>
					</div>
					<div class="loaderContractTypes"></div>
					<div id="pie_chart1" class="text-center"></div>
				</div>
				<div class="pie_chart_inner_container">
					<div id="pie_chart2_heading" class="pie_chart_heading">
						<div id="pie_chart2_icon"></div>
							<div id="pie_chart2_heading_text" class="pie_chart_heading_text">Specialty</div>
						</div>
					<div class="loaderSpecialty"></div>
					<div id="pie_chart2" class="text-center"></div>
				</div>
				<div class="pie_chart_inner_container">
					<div id="pie_chart3_heading" class="pie_chart_heading">
						<div id="pie_chart3_icon"></div>
						<div id="pie_chart3_heading_text" class="pie_chart_heading_text">Provider</div>
					</div>
					<div class="loaderProviders"></div>
					<div id="pie_chart3" class="text-center"></div>
				</div>
			</div>
			
			<div class="col-md-4">
				<div class="form-group  col-xs-8">
					<div class="col-md-offset-3 col-sm-12 col-xs-12">
						@if($contract_types)
							{{ Form::select('contract_type', $contract_types, Request::old('contract_type', $contract_type), [ 'id'=>'contract_type','class' => 'form-control' ]) }}
						@endif
					</div>
				</div>
			</div>
			
			<div class="col-md-4">
				<div class="form-group  col-xs-8">
					<div class="col-md-offset-3 col-sm-12 col-xs-12">
						@if($specialties)
							{{ Form::select('specialty', $specialties, Request::old('specialty', $specialty), [ 'id'=>'specialty','class' => 'form-control' ]) }}
						@endif
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group  col-xs-8">
					<div class="col-md-offset-3 col-sm-12 col-xs-12">
						@if($providers)
							{{ Form::select('provider', $providers, Request::old('provider', $provider), [ 'id'=>'provider','class' => 'form-control' ]) }}
						@endif
					</div>
				</div>
			</div>
		</div>
		
		<div id="pie_chart_header_second_block" class="pie_chart_outer_container" style="margin-top: 2%;">
			<div id="provider_comparison" class="pie_chart_heading_text">Provider Comparison - Actual to Expected (Time)</div>
			<div style="border-top: 1px #d6d6d6 solid; margin-top: 21px"></div>
			
			<div id="pie_chart_container_actual" class="pie_chart_container">
				<div class="pie_chart_inner_container">
					<div id="pie_chart4_heading" class="pie_chart_heading">
						<div id="pie_chart4_icon"></div>
						<div id="pie_chart4_heading_text" class="pie_chart_heading_text">Contract Type</div>
					</div>
					<div class="loaderContractTypesactual"></div>
					<div id="pie_chart4" class="text-center"></div>
				</div>
				<div class="pie_chart_inner_container">
					<div id="pie_chart5_heading" class="pie_chart_heading">
						<div id="pie_chart5_icon"></div>
						<div id="pie_chart5_heading_text" class="pie_chart_heading_text">Specialty</div>
					</div>
					<div class="loaderSpecialtyactual"></div>
					<div id="pie_chart5" class="text-center"></div>
				</div>
				<div class="pie_chart_inner_container">
					<div id="pie_chart6_heading" class="pie_chart_heading">
						<div id="pie_chart6_icon"></div>
						<div id="pie_chart6_heading_text" class="pie_chart_heading_text">Provider</div>
					</div>
					<div class="loaderProvidersactual"></div>
					<div id="pie_chart6" class="text-center"></div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group  col-xs-8">
					<div class="col-md-offset-3 col-sm-12 col-xs-12">
						@if($contract_types)
							{{ Form::select('contract_type_actual', $contract_types, Request::old('contract_type', $contract_type), [ 'id'=>'contract_type_actual','class' => 'form-control' ]) }}
						@endif
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group  col-xs-8">
					<div class="col-md-offset-3 col-sm-12 col-xs-12">
						@if($specialties)
							{{ Form::select('specialty_actual', $specialties, Request::old('specialty', $specialty), [ 'id'=>'specialty_actual','class' => 'form-control' ]) }}
						@endif
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="form-group  col-xs-8">
					<div class="col-md-offset-3 col-sm-12 col-xs-12">
						@if($specialties)
							{{ Form::select('provider_actual', $providers, Request::old('provider', $provider), [ 'id'=>'provider_actual','class' => 'form-control' ]) }}
						@endif	
					</div>
				</div>
			</div>
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
								
								$("#contract_type").change(function (){
									getManagementContractTypeChart();
									getSpecialtiesForPerformansDashboard();
								});
								$("#specialty").change(function (){
									getManagementSpecialtyChart();
									getProvidersForPerformansDashboard();
								});
								$("#provider").change(function (){
									getManagementProviderChart();
								});
								
								$("#contract_type_actual").change(function (){
									getActualToExpectedTimeContractTypeChart();
									getSpecialtyListForActualToExpected();
								});
								$("#specialty_actual").change(function (){
									getActualToExpectedTimeSpecialtyChart();
									getProvidersListForActualToExpected();
								});
								$("#provider_actual").change(function (){
									getActualToExpectedTimeProviderChart();
								});
								
								$("#regions, #hospital, #practice_type").change(function (){
									getContractTypesForPerformansDashboard();
									getSpecialtiesForPerformansDashboard();
									getProvidersForPerformansDashboard();

									getContractTypeListForActualToExpected();
									getSpecialtyListForActualToExpected();
									getProvidersListForActualToExpected();
								});
								
								getManagementContractTypeChart();
								getManagementSpecialtyChart();
								getManagementProviderChart();
								getActualToExpectedTimeContractTypeChart();
								getActualToExpectedTimeSpecialtyChart();
								getActualToExpectedTimeProviderChart();
							});
							
							function getContractTypesForPerformansDashboard(){
								var count = 0;
								$.ajax({
									url:'/getContractTypesForPerformansDashboard/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#group_id").val(),	
									type:'get',
									success:function(response){
										$("[name=contract_type]").html('');
										$.each(response, function (data, value) {  
											if(count == 0){
												$("[name=contract_type]").append($("<option selected='selected'></option>").val(data).html(value));  
											} else{
												$("[name=contract_type]").append($("<option></option>").val(data).html(value));   
											}
											count ++;
										}) 
										getManagementContractTypeChart();
										//getActualToExpectedTimeContractTypeChart();
									}
								});
							}

							function getContractTypeListForActualToExpected(){
								var count = 0;
								$.ajax({
									url:'/getContractTypesForPerformansDashboard/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#group_id").val(),	
									type:'get',
									success:function(response){
										$("[name=contract_type_actual]").html('');
										$.each(response, function (data, value) {  
											if(count == 0){
												$("[name=contract_type_actual]").append($("<option selected='selected'></option>").val(data).html(value));  
											} else{
												$("[name=contract_type_actual]").append($("<option></option>").val(data).html(value));   
											}
											count ++;
										}) 
										// getManagementContractTypeChart();
										getActualToExpectedTimeContractTypeChart();
									}
								});
							}
							
							function getSpecialtiesForPerformansDashboard(){
								var count = 0;
								$.ajax({
									url:'/getSpecialtiesForPerformansDashboard/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#group_id").val() + '/' + $("#contract_type").val(),
									type:'get',
									success:function(response){
										$("[name=specialty]").html('');
										$.each(response, function (data, value) {  
											if(count == 0){
												$("[name=specialty]").append($("<option selected='selected'></option>").val(data).html(value));  
											} else{
												$("[name=specialty]").append($("<option></option>").val(data).html(value));   
											}
											count ++;
										}) 
										getManagementSpecialtyChart();
										getProvidersForPerformansDashboard();
										getManagementProviderChart();
									}
								});
							}

							function getSpecialtyListForActualToExpected(){
								var count = 0;
								$.ajax({
									url:'/getSpecialtiesForPerformansDashboard/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#group_id").val() + '/' + $("#contract_type_actual").val(),
									type:'get',
									success:function(response){
										$("[name=specialty_actual]").html('');
										$.each(response, function (data, value) {  
											if(count == 0){
												$("[name=specialty_actual]").append($("<option selected='selected'></option>").val(data).html(value));  
											} else{
												$("[name=specialty_actual]").append($("<option></option>").val(data).html(value));   
											}
											count ++;
										}) 
										getActualToExpectedTimeSpecialtyChart();
										getProvidersListForActualToExpected();
										getActualToExpectedTimeProviderChart();
									}
								});
							}
							
							function getProvidersForPerformansDashboard(){
								var count = 0;
								$.ajax({
									url:'/getProvidersForPerformansDashboard/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#group_id").val() + '/' + $("#contract_type").val() + '/' + $("#specialty").val(),
									type:'get',
									success:function(response){
										$("[name=provider]").html('');
										$.each(response, function (data, value) { 
											if(count == 0){
												$("[name=provider]").append($("<option selected='selected'></option>").val(data).html(value));  
											} else{
												$("[name=provider]").append($("<option></option>").val(data).html(value));   
											}
											count ++; 
										}) 
										getManagementProviderChart();
										// getActualToExpectedTimeProviderChart();
									}
								});
							}

							function getProvidersListForActualToExpected(){
								var count = 0;
								$.ajax({
									url:'/getProvidersForPerformansDashboard/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#group_id").val() + '/' + $("#contract_type_actual").val() + '/' + $("#specialty_actual").val(),
									type:'get',
									success:function(response){
										$("[name=provider_actual]").html('');
										$.each(response, function (data, value) { 
											if(count == 0){
												$("[name=provider_actual]").append($("<option selected='selected'></option>").val(data).html(value));  
											} else{
												$("[name=provider_actual]").append($("<option></option>").val(data).html(value));   
											}
											count ++; 
										}) 
										getActualToExpectedTimeProviderChart();
									}
								});
							}
							
							// @description : function to call  pie charts
							// @return html

							function getManagementContractTypeChart(){
								var test = $("#contract_type").val();
								$('#pie_chart1').html('');
								$('.loaderContractTypes').show();
								$.ajax({
									url:'/getManagementContractTypeChart/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#contract_type").val() + '/' + $("#group_id").val(),
									type:'get',
									success:function(response){
										$('#pie_chart1').html(response);
									},
									complete:function(){
										$('.loaderContractTypes').hide();
									}
								});
							}
							
							function getManagementSpecialtyChart(){
								$('#pie_chart2').html('');
								$('.loaderSpecialty').show();
								$.ajax({
									url:'/getManagementSpecialtyChart/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#specialty").val() + '/' + $("#group_id").val() + '/' + $("#contract_type").val(),
									type:'get',
									success:function(response){
										$('#pie_chart2').html(response);
									},
									complete:function(){
										$('.loaderSpecialty').hide();
									}
								});
							}

							function getManagementProviderChart(){
								$('#pie_chart3').html('');
								$('.loaderProviders').show();
								$.ajax({
									url:'/getManagementProviderChart/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#provider").val() + '/' + $("#group_id").val() + '/' + $("#contract_type").val() + '/' + $("#specialty").val(),
									type:'get',
									success:function(response){
										$('#pie_chart3').html(response);
									},
									complete:function(){
										$('.loaderProviders').hide();
									}
								});
							}

							function getActualToExpectedTimeContractTypeChart(){
								$('#pie_chart4').html('');
								$('.loaderContractTypesactual').show();
								$.ajax({
									url:'/getActualToExpectedTimeContractTypeChart/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#contract_type_actual").val() + '/' + $("#group_id").val(),
									type:'get',
									success:function(response){
										$('#pie_chart4').html(response);
									},
									complete:function(){
										$('.loaderContractTypesactual').hide();
									}
								});
							}

							function getActualToExpectedTimeSpecialtyChart(){
								$('#pie_chart5').html('');
								$('.loaderSpecialtyactual').show();
								$.ajax({
									url:'/getActualToExpectedTimeSpecialtyChart/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#specialty_actual").val() + '/' + $("#group_id").val() + '/' + $("#contract_type_actual").val(),
									type:'get',
									success:function(response){
										$('#pie_chart5').html(response);
									},
									complete:function(){
										$('.loaderSpecialtyactual').hide();
									}
								});
							}
							
							function getActualToExpectedTimeProviderChart(){
								$('#pie_chart6').html('');
								$('.loaderProvidersactual').show();
								$.ajax({
									url:'/getActualToExpectedTimeProviderChart/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#provider_actual").val() + '/' + $("#group_id").val() + '/' + $("#contract_type_actual").val() + '/' + $("#specialty_actual").val(),
									type:'get',
									success:function(response){
										$('#pie_chart6').html(response);
									},
									complete:function(){
										$('.loaderProvidersactual').hide();
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
