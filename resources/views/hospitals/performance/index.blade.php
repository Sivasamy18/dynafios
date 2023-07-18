<!--Chaitraly:: 26/7/2021 -->
@extends('dashboard/_index_landing_page')
@section('links')
<style>
    .landing_page_main .container-fluid .welcomeHeading {
        font-size: 20px;
        font-family: 'open sans';
        font-weight: normal;
    }

    table {
        border-collapse: collapse;
        background: white;
        table-layout: fixed;
        width: 100%;
    }
    th, td {
        padding: 7px 16px !important;
        /* border: 1px solid #ddd; */
        /* width: 160px;  */
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: wrap;
        font-family: 'open sans';
        font-size: 14px;
        color: #221f1f;
        word-wrap: break-word; 
    }

    th {
        white-space: normal;
        background: #000000;
        color: #fff;
        font-family: 'open sans';
        font-size: 14px;
        font-weight: normal;
        /* text-align: center; */

    }

    table tbody tr {
        background: #eaeaea;
        /* border: solid 1px #b8b8b8; */
    }

    table tbody tr:nth-child(odd) {
        background: #dfdfdf;
    }

    .pane {
        background: #eee;
    }
    .pane-hScroll {
        overflow: auto;
        width: 100%;
        background: transparent;
    }
    .pane-vScroll {
        overflow-y: auto;
        overflow-x: hidden;
        max-height: 560px;
        background: transparent;
    }

    .pane--table2 {
        width: 100%;
        overflow-x: scroll;
    }
    .pane--table2 th, .pane--table2 td {
        width: auto;
        min-width: 160px;
    }
    .pane--table2 tbody {
        overflow-y: scroll;
        overflow-x: hidden;
        display: block;
        height: 200px;
    }
    .pane--table2 thead {
        display: table-row;
    }

    label {
        margin-top: 5px;
    }

    .odd_contract_class
    {
        background: #dfdfdf !important;

    }
    .even_contract_class
    {
        background: #fdfdfd !important;
    }

    .pagination a {
        width: auto !important;
        height: auto !important;
        margin: 0 6px !important;
    }

    .pagination span {
        margin: 0 6px;
        -webkit-box-shadow: -3px 3px 0 0 #c4c4c4;
        -moz-box-shadow: -3px 3px 0 0 #c4c4c4;
        -ms-box-shadow: -3px 3px 0 0 #c4c4c4;
        -o-box-shadow: -3px 3px 0 0 #c4c4c4;
        box-shadow: -3px 3px 0 0 #c4c4c4;
    }
    .pagination>li>a:hover{
        color: #fff !important;
    }

    .approved-text{
        color : #f68a1f;
    }

    .rejected-text{
        color : red;
    }
    .proxy_approver
    {
        color:red;
    }

    .dashboardSummationHeading{
        float:left;
        margin-left:40px;
        font-size: 15px;
    }

    .dashboardSummationHeading1{
        float:left;
        margin: -10px 0px 0px 35px;
        font-size: 15px;
    }

    .mb-0 {
        height: 60px !important;
        margin: 0px 0px 0px 0px !important;
    }
    .mb-0 > a {
        display: block;
        position: relative;
        height:60px;
    }

    #level-one > a {
        width:100%;
        background:#524a42;
        margin: 0px 0px 0px 0px !important;
        padding:0px;
        line-height: 80px;border:none;
    }

    #level-one > a:before {
        /* content: "\f055"; /* fa-chevron-plus-circle */
        content: "\f068"; /* fa-chevron-minus-circle */
        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        color: #000000;
        margin-left: 5px;
        margin: -9px 0px 0px 15px !important;

        line-height: 80px;
    }

    #level-one > a.collapsed:before {
        content: "\f067"; /* fa-chevron-plus-circle */
        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        color: #000000;
        margin-left: 5px;
        margin: -9px 0px 0px 15px !important;
        line-height: 80px;
    }

    #level-two > a:before {
        content: "\f068" !important; /* fa-chevron-minus-circle */
        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        color: #000000;
        margin-left: 5px;
        margin: -9px 0px 0px 31px !important;

        line-height: 80px;


    }

    #level-two > a.collapsed:before {
        content: "\f067" !important; /* fa-chevron-plus-circle */
        /*   font-family: 'FontAwesome';
           position: absolute;
           left: 0;
           font-size: 30px;
           color: #f68a1f;
           margin-left: 5px;
           margin: -7px 0px 0px 25px !important;
           line-height: 80px;*/

        font-family: 'FontAwesome';
        position: absolute;
        left: 0;
        font-size: 15px;
        color: #000000;
        margin-left: 5px;
        margin: -9px 0px 0px 31px !important;

        line-height: 80px;


    }

    .facilityAgreement a .agreementHeading1 {
        float: left;
        /* margin: 0 0.5%; */
        /* margin: 10px 10px; */
        font-size: 1.0625em;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: normal;
        /*color:#333;*/

        /* font-weight: 800;*/
        color: #fff;
        font-family: 'open sans';

    }

    .facilityAgreement a .dashboardSummationHeading1 {
        float: left;
        /* margin: 0 0.5%; */
        /* margin: 10px 10px; */
        font-size: 1.0625em;
        font-weight: 600;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: normal;
    }

    .collapse-circle{

        position: absolute;
        left: 9px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        color: #000000;
        /* transition: .5s; */
        line-height: 18px;
        border: solid 3px #000000;
        border-radius: 20px;
        width: 24px;
        height: 24px;
    }

    .collapse-level-two-circle{
        position: absolute;
        left: 25px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        color: #000000;
        /* transition: .5s; */
        line-height: 18px;
        border: solid 3px #000000;
        border-radius: 20px;
        width: 24px;
        height: 24px;

    }

    .level-one-heading{
        color: #fff;
        font-family: 'open sans';
        font-size: 1.0625em;
        font-weight: normal;
    }

    /* input[type=checkbox] {
        margin-right: 5px;
        cursor: pointer;
        font-size: 14px;
        width: 15px;
        height: 12px;
        position: relative;
    }

    input[type=checkbox]:after {
        position: absolute;
        width: 10px;
        height: 15px;
        top: 0;
        content: " ";
        background-color: #544d4d;
        color: #fff;
        display: inline-block;
        visibility: visible;
        padding: 0px 3px;
        border-radius: 3px;
    }

    input[type=checkbox]:checked:after {
        content: "✓";
        font-size: 15px;
    }

    input[type=checkbox]:after {
        background-color: #544d4d;
        width:20px;
        height:20px;
        border: 1px solid #fff;
    }
    input[type="checkbox"]:checked {
        background: #544d4d;
    }

    .th-level{
        color:#f68a1f;
        text-align: center;
        padding: 7px 0px 7px 0px !important;
        background: #efefef !important;
    }

    .level-two-heading{
        background:#8e8174;
        color:#333;
        padding: 3px 0px 3px 15px !important;
        text-align:center;
    }

    .level-two-physician-heading{
        background:#8e8174;
        color:#333;
        padding: 3px 0px 3px 50px !important;
        text-align:center;
    }

    .input-level-one-checkbox{
        background:#8e8174;
        padding-left:0px;
        padding-right:0px;
        border-left: 1px solid #b8b8b8;padding-top: 10px;
        border-bottom: 1px solid #efefef;
        height: 61px;
    }

    .approve-image-actionCheckbox{

        border:2px dotted;;
        line-height:16px;
        border-bottom: 0px dotted #000;
    }

    .table-arpprove-reject-checkbox{
        background:#524a42;
        padding-left:0px;
        padding-right:0px;
        border-left: 1px solid #b8b8b8;
        padding-top: 15px;
        border-bottom: 1px solid #b8b8b8;
    }

    .reject-checkbox{
        margin-right: 0em !important;height: 21px;width: 14px;margin-left: 6px;
    }

    .approve-checkbox {
        margin-right: 0em !important;
        height: 21px !important;
        width: 14px !important;
        margin-left: -15px !important;
        margin-bottom: 9px !important;
    } */

    .approve-reject-inputs{
        background:#524a42;
        padding-left:0px;
        padding-right:0px;
        border-left: 1px solid #b8b8b8;
        padding-top: 14px;
        padding-bottom: 3px;
        border-bottom: 1px solid #b8b8b8;
    }

    .approve-level-two-value{
        color:#fff;font-size: 1.0625em;font-weight: normal; font-weight: 800;
    }
    /*Chaitraly::added for generate report button*/
    .generateReport
    {
        margin-right: 140px;
    }

    a.performanceButton{ 
    /* padding: 10px 25px; */
    display: inline-block;
    border: solid 1px #f68a1f;
    font-size: 16px;
    background: #f68a1f;
    color: #fff;
    margin-top: 20px;
    width: auto;
    height: auto;
    line-height: inherit;
    padding: 9px 40px;
    box-shadow: none;
    font-family: 'open sans';
    font-weight: 600;
    border-radius: 5px;}

    .form-generate-report select[multiple].form-control{
        height: 100px;
        display: inline-block;
        vertical-align: top;
    }
     /* adjust the select dropdown width */
    .form-control{
        width: 75% ;
        height: 43px;
    }
    .form-control#start_date,#end_date{
        width: 100%
    }
    input[type="checkbox"] {
       
        margin-left: 7px !important;
        margin-top: 7px !important;
    
    }
    .selectAllCSS{
        font-size: 13px;
        margin-left: 0px !important;
        font-family: 'open sans';
    }
    
</style>

  <!--Sandip 26/7/2021 Pie chart HTML-->
<!-- <div id="pie_chart_container" class="pie_chart_container">
	<div id="pie_chart1_container" class="pie_chart_inner_container">
			<div id="pie_chart1_heading" class="pie_chart_heading">
				<div id="pie_chart1_icon"></div>
				<div id="pie_chart1_heading_text" class="pie_chart_heading_text"><img class="img-responsive" src="../assets/img/default/activeContractTypes.png" alt="">Physician Logs </div>
			</div>
			 <div id="pie1">
				 <span id="contractDetailsSpan1" class="contractDetails"></span> 
			</div>  -->
            <!-- <a name="top"></a> -->
            <div id="pie_chart">
				  {{-- <span id="contractDetailsSpan1" class="contractDetails"></span>   --}}
			</div> 
	

<br>

<div id="form_replace" class="approvalDashboard">
        {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
        <div class="appDashboardFilters">
            <div class="col-md-6">
                <div class="form-group col-xs-12">
                    <label class="col-xs-3 control-label">Organization: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('hospital', $hospitals, Request::old('hospital',$hospital), [ 'id'=>'hospital','class' => 'form-control' ]) }}
                    </div>
                </div>

                <div class="form-group col-xs-12">
                    <label class="col-xs-3 control-label">Practice: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('practice', $practices, Request::old('practice',$practice), ['id'=>'practice', 'class' => 'form-control','multiple'=>true ]) }}
                        <input type="checkbox" class= "selectAll" id="pracChk" name="SelectAll" onclick="selectAll('practice','pracChk')" value="All"  /><span class="selectAllCSS" >Select All</span>
                    </div>
                </div>

                <div class="form-group  col-xs-12">
                    <label class="col-xs-3 control-label">Payment Type: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('payment_types', $payment_types, Request::old('payment_type',$payment_type), ['id'=>'payment_type', 'class' => 'form-control' ]) }}
                    </div>
                </div>
                <div class="form-group  col-xs-12">
                    <label class="col-xs-3 control-label">Contract Type: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('contract_types[]', $contract_types, Request::old('contract_type',$contract_type), ['id'=>'contract_type', 'class' => 'form-control','multiple'=>true ]) }}
                        <input type="checkbox" class= "selectAll" id="typeChk" name="SelectAll" onclick="selectAll('contract_type','typeChk')" value="All"  /><span class="selectAllCSS" >Select All</span>
                    </div>
                    <!-- <div>
                    <input type="checkbox" id="typeChk" name="SelectAll" onclick="selectAll('contract_type','typeChk')" value="All"  />Select All
                    </div> -->
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group  col-xs-12">
                    <label class="col-xs-3 control-label">Agreement: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9 filters">
                        {{ Form::select('agreement[]', $agreements, Request::old('agreement', $agreement), ['id'=> 'agreement', 'class' => 'form-control','multiple'=>true ]) }}
                        <input type="checkbox" class= "selectAll"  id ="agreementChk" name="SelectAll" value="All" onclick="selectAll('agreement','agreementChk')" /><span class="selectAllCSS" >Select All</span>
                    </div>
                    <!-- <div>
                   
                    </div> -->
                </div>

                <div class="form-group  col-xs-12">
                    <label class="col-xs-3 control-label">Physician: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('physician[]', $physicians, Request::old('physician',$physician), [ 'id'=> 'physician','class' => 'form-control','multiple'=>true]) }}
                        <input type="checkbox" class= "selectAll" id="phyChk" name="SelectAll" value="All" onclick="selectAll('physician','phyChk')" /><span class="selectAllCSS" >Select All</span>
                    </div>
                    <!-- <div>
                   
                    </div> -->
                </div>

              

                <div class="form-group  col-xs-12">
                    <label class="col-xs-3 control-label">Contract Name: </label>
                    <div class="col-md-9 col-sm-9 col-xs-9">
                        {{ Form::select('contract_names[]', $contract_names, Request::old('contract_name',$contract_name), ['id'=>'contract_name', 'class' => 'form-control','multiple'=>'multiple' ]) }}
                        <input type="checkbox" class= "selectAll" id="nameChk" name="SelectAll" value="All" onclick="selectAll('contract_name','nameChk')" /><span class="selectAllCSS" >Select All</span>
                    </div>
                    <!-- <div>
                     <input type="checkbox" id="nameChk" name="SelectAll" value="All" onclick="selectAll('contract_name','nameChk')" />Select All
                    </div> -->
                </div>
            </div>

             <div class="form-group col-xs-offset-4 col-xs-5" style="margin: 0 auto 50px; float: none; clear:both;">
                <label class="col-xs-4 control-label" style="margin-top: 35px;">Time Period:</label>
                <div class="col-md-8 col-sm-8 col-xs-8 paddingZero">
                    <div class="col-md-6 col-sm-6 col-xs-6 paddingLeft">
                        <!-- <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Month</label> -->
                        <label class="col-xs-12 control-label paddingLeft " style="font-weight: normal; text-align: center;">Start Period</label>
                        {{ Form::select('start_dates', $dates['start_dates'], Request::old('start_date',$start_date), [ 'id'=> 'start_date','class' => 'form-control' ]) }}
                    </div>
                    <div class="col-md-6 col-sm-6 col-xs-6 paddingRight">
                        <!-- <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Month</label> -->
                        <label class="col-xs-12 control-label paddingLeft" style="font-weight: normal; text-align: center;">End Period</label>
                        {{ Form::select('end_dates', $dates['end_dates'], Request::old('end_date',$end_date), [ 'id'=> 'end_date','class' => 'form-control' ]) }}
                    </div>

                </div>
            </div>
                <div style="align-items: center;
                            display: flex;
                            justify-content: center;">
                 <a class="performanceButton" type="button" name="submit" onclick="generatePieChartNew()" href="#pie_chart"> Generate Chart </a>
                 <a class="performanceButton" type="button" name="excelButton" href="{{ URL::route('performance.report') }}">Generate Excel Report</a> 
                </div>
        </div>
		
        <script type="text/javascript" src="{{ asset('assets/js/performanceDashboard.js') }}"></script>
        {{ Form::close() }}
    </div>
    <!--Chaitraly:: A different division created for pop up -->
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


    <!-- Chaitraly::Copied 3 new scripts for pop up functionality -->
    <link rel="stylesheet"  href="{{ asset('assets/css/lightslider.css') }}"/> <!-- CSS file -->
	<script type="text/javascript" src="{{ asset('assets/js/lightslider.js') }}"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<script type="text/javascript" src="{{ asset('assets/js/numeral.js') }}"></script>
    <script>
        h_id_array = new Array();
		google.charts.load('current', {packages: ['corechart', 'bar','gauge','table']});
        $(document).ready(function (){
            $(".close").click(function(){
				$('div#pop-up  .modal-dialog').css('top','-100%');
				$('body').css('overflow-y', 'auto');
				setTimeout(function(){
				    $('div#pop-up').hide();
					$('div#pop-up').css('transition','1s');
				},100);
			});
            // selectAll('agreement','agreementChk');
            
          //  google.charts.setOnLoadCallback(getFacilityContractCountDataByAjax);

        });
        //Chaitraly::Function to load the pop up table
        // function getPhysicianLogsList(){
        //    // alert("inside getPhysicianLogList() javaScript function");
		// 						//var topping = data3.getValue(selectedItem.row, 0);
		// 						//alert('The user selected ' + topping);
        //             var hospital_id = $('#hospital').val();
		// 			var agreement_id = $('#agreement').val();
		// 			var contract_type = $('#contract_type').val();
		// 			var contract_name = $('#contract_name').val();
		// 			var physician_id = $('#physician').val();
        //             var practice = $('#practice').val();
        //             var start_date = $('#start_date').val();
        //             var end_date = $('#end_date').val();
        //             var categoryIdArr=[];
        //             categoryIdArr.push(categoryIdArray);
		// 						// $('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
		// 						// $('div#pop-up .modal-dialog').css('top','-100%');
		// 						// $('.modal-title').html('List of '+hospital_name+' physician logs: ');
		// 						// $('div#pop-up').show();
		// 						// $('body').css('overflow-y', 'hidden');
		// 						// var group_id=$("#group").val();
		// 						// setTimeout(function(){
		// 						// 	$('div#pop-up .modal-dialog').css('top','15%');
		// 						// 	//$('div#pop-up').css('transition','1s');
		// 						// },1);
		// 						// $('.loaderPopup').show();
		// 						$.ajax({
		// 							url:'/getPhysicianLogsList',
		// 							type:'post',
        //                                 data:{hospital_id:hospital_id,
        //                                 agreement_id:agreement_id,
        //                                 contract_type:contract_type,
        //                                 contract_name:contract_name,
        //                                 physician_id:physician_id,
        //                                 //practice:practice,
        //                                 start_date:start_date,
        //                                 end_date:end_date,
        //                                 categoryIdArray:categoryIdArr
		// 					        },
        //                             dataType:"json",
		// 							success:function(response){
        //                                // console.log("response from 2nd api");
        //                                 console.log(response);
		// 								var text = "<table class='table'><thead>" +
		// 										"<tr><th>Serial Number</th>" +
        //                                         "<th>Facility</th>" +
		// 										"<th>Contract Name</th>" +
		// 										"<th>Physician Name</th>" +
		// 										"<th class='text-center'>Log Date</th>" +
		// 										"<th class='text-center'>Duration</th>" +
        //                                         "<th class='text-center'>Action</th>" +
		// 										"<th class='text-center'>Detail</th></tr></thead>" +
		// 										"<tbody data-link='row' class='rowlink'>";

		// 								$.each(response.activity_log, function( key, value ) {
		// 									text = text + "<tr><td>"+value.srNo+"</td>" +
		// 											"<td>"+value.hospital_name+"</td>" +
        //                                             "<td>"+value.contract_name+"</td>" +
		// 											"<td>"+value.physician_name+"</td>" +
		// 											"<td class='text-center'>"+value.log_date+"</td>" +
        //                                             "<td class='text-center'>"+value.duration+"</td>" +
        //                                             "<td class='text-center'>"+value.action_name+"</td>" +
		// 											"<td class='text-center'>"+value.details+"</td></tr>";
		// 								});
		// 								text = text+"</tbody></table>";
		// 								$('#chaitraly').html(text);
		// 							},
		// 							complete:function(){
		// 								//$('.overlay').hide();
		// 								$('.loaderPopup').hide();
		// 							}
		// 						});
        //                        // alert("end of getPhysicianLogList() javaScript function");
		// }
    </script>
    <!--End of new script files -->

        <script type="text/javascript" src="{{ asset('assets/js/d3.min.js') }}"></script>
        <script type = "text/javascript" scr="https://www.gstatic.com/charts/loader.js"> </script>
        <script type="text/javascript" src="{{ asset('assets/js/d3pie.js') }}"></script> 
        <script type="text/javascript" src="{{ asset('assets/js/numeral.js') }}"></script>

        <script>
				$(document).ready(function (){
                    generatePieChartNew();
                   // getPhysicianLogsList();
				});

		</script>
		<script>
                function  generatePieChartNew(){
                    var hospital_id = $('#hospital').val();
					var agreement_id = $('#agreement').val();
					var contract_type = $('#contract_type').val();
					var contract_name = $('#contract_name').val();
					var physician_id = $('#physician').val();
                    var practice = $('#practice').val();
                    var start_date = $('#start_date').val();
                    var end_date = $('#end_date').val();
                    $('.loaderPayment').show();
					$('#contracts_ready_payment_label').hide();
                    $.ajax({
						url:'/getTotalHoursForPhysicanLog',
						type:'post',
                        headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                        data:{hospital_id:hospital_id,
								agreement_id:agreement_id,
								contract_type:contract_type,
								contract_name:contract_name,
								physician_id:physician_id,
                                //practice:practice,
                                start_date:start_date,
                                end_date:end_date
							},
                        // dataType:"json",
						success:function(response){
                            // console.log("new api res"+response);
                             // alert(response);
							$('#pie_chart').html(response);
						},
						complete:function(){
						   // alert('Hiii....');
						}
					});
                }
                
				// function generatePieChart(){
           
				// 	var hospital_id = $('#hospital').val();
				// 	var agreement_id = $('#agreement').val();
				// 	var contract_type = $('#contract_type').val();
				// 	var contract_name = $('#contract_name').val();
				// 	var physician_id = $('#physician').val();
                //     var practice = $('#practice').val();
                //     var start_date = $('#start_date').val();
                //     var end_date = $('#end_date').val();
				// 	$('.loaderPayment').show();
				// 	$('#contracts_ready_payment_label').hide();
				// 	$.ajax({
				// 		url:'/getTotalHoursForPhysicanLog',
				// 		type:'post',
				// 		data:{hospital_id:hospital_id,
				// 				agreement_id:agreement_id,
				// 				contract_type:contract_type,
				// 				contract_name:contract_name,
				// 				physician_id:physician_id,
                //                 //practice:practice,
                //                 start_date:start_date,
                //                 end_date:end_date
				// 			},
				// 		dataType:"json",
				// 		success:function(response){
                //             if(response.activity_log.length == 0){
                //                     $('#pie').html("<label>NO LOGS FOUND</label>");
                //              }
							
				// 			if ( $( "#pie1" ).length)
				// 			{
				// 				var pie_div_width=parseInt((0.98)*($('#pie1').width()));
				// 				var pie_div_height=parseInt((0.30)*($('#pie1').width()));
				// 			}
				// 			else
				// 			{
				// 				var pie_div_width=parseInt((0.98)*($('#pie6').width()));
				// 				var pie_div_height=parseInt((0.85)*($('#pie6').width()));
				// 			}
				// 			var pie_inner_width="80%";
				// 			var pie_outer_width="50%";
							
				// 			$('#pie1').html('');
				// 			if ( $( "#pie1" ).length)
				// 			{
				// 			var arr = [];
                //             window.categoryIdArray = [];
                //               if(response.activity_log.length > 0){
				// 				$.each(response.activity_log, function(k, v) {
                //                     window.actionName = v.activity_name;
                //                     console.log("global var print");
                //                     console.log(actionName);
                //                     //console.log(v.total_duration);
                //                     if(v.total_duration>0){
                //                         arr.push({ label: v.activity_name , value: v.total_duration ,id:"101"  },);
                //                         categoryIdArray.push(v.category_id);
                //                         console.log("categoryIdArray"+categoryIdArray);
                //                     }
                //                 });
				// 				var pie3 = new d3pie("pie1", {
				// 					size: {
				// 						canvasHeight: pie_div_height,
				// 						canvasWidth: pie_div_width,
				// 						pieInnerRadius: pie_inner_width,
				// 						pieOuterRadius: pie_outer_width
				// 					},
				// 					data: {
				// 						sortOrder: "label-asc",
				// 						content: arr,
				// 					},
				// 					labels: {
				// 						outer: {
				// 							format: "label-hour1",
				// 							hideWhenLessThanPercentage: null,
				// 							pieDistance: 10
				// 						},
				// 						inner: {
				// 							format: null,
				// 							hideWhenLessThanPercentage: null
				// 						},
				// 						mainLabel: {
				// 							color: "#333333",
				// 							font: "arial",
				// 							fontSize: 10
				// 						},
				// 						percentage: {
				// 							color: "#dddddd",
				// 							font: "arial",
				// 							fontSize: 10,
				// 							decimalPlaces: 0
				// 						},
				// 						value: {
				// 							color: "#333333",
				// 							font: "arial",
				// 							fontSize: 10
				// 						},
				// 						lines: {
				// 							enabled: true,
				// 							style: "curved",
				// 							color: "segment"
				// 						},
				// 						truncation: {
				// 							enabled: false,
				// 							truncateLength: 30
				// 						},
				// 						// amount: {
				// 						// 	color: "#333333",
				// 						// 	font: "arial",
				// 						// 	fontSize: 10
				// 						// },
                //                         hour: {
				// 							color: "#333333",
				// 							font: "arial",
				// 							fontSize: 10
				// 						},
				// 						formatter: null
				// 					}
				// 				});
                //               }//end of the if for pie with activity logs
                //              if(response.activity_log.length == 0){ //defined pie for no logs
                //                 //new pie defined
                //                 arr.push({ label: "No Record found" , value: 0.1 ,  },);
                //                 var pie3 = new d3pie("pie1", {
				// 					size: {
				// 						canvasHeight: pie_div_height,
				// 						canvasWidth: pie_div_width,
				// 						pieInnerRadius: pie_inner_width,
				// 						pieOuterRadius: pie_outer_width
				// 					},
				// 					data: {
				// 						sortOrder: "label-asc",
				// 						content: arr,
				// 					},
				// 					 labels: {
				// 						outer: {
				// 							// format: "label-hour1",
				// 							 hideWhenLessThanPercentage: null,
				// 							 pieDistance: 10
				// 						},
				// 						inner: {
				// 							format: null,
				// 							hideWhenLessThanPercentage: null
				// 						},
				// 						mainLabel: {
				// 							color: "#333333",
				// 							font: "arial",
				// 							fontSize: 10
				// 						},
				// 						percentage: {
				// 							color: "#dddddd",
				// 							font: "arial",
				// 							fontSize: 10,
				// 							decimalPlaces: 0
				// 						},
				// 					// 	// value: {
				// 					// 	// 	color: "#333333",
				// 					// 	// 	font: "arial",
				// 					// 	// 	fontSize: 10
				// 					// 	// },
				// 						lines: {
				// 							enabled: false,
				// 							style: "curved",
				// 							color: "segment"
				// 						},
				// 					// 	truncation: {
				// 					// 		enabled: false,
				// 					// 		truncateLength: 30
				// 					// 	},
				// 					// 	// amount: {
				// 					// 	// 	color: "#333333",
				// 					// 	// 	font: "arial",
				// 					// 	// 	fontSize: 10
				// 					// 	// },
                //                     //     // hour: {
				// 							// color: "#333333",
				// 							// font: "arial",
				// 							// fontSize: 10
				// 						//},
				// 					// 	formatter: null
				// 					 }
				// 				});
                //                 }
                                
				// 			}//end of main pie length if
				// 		},
				// 		complete:function(){
				// 			//$('.overlay').hide();
				// 			$('.loaderPayment').hide();
				// 			$('#contracts_ready_payment_label').show();
				// 		}
				// 	});
				// } 

                function selectAll(selectBox,chkbox) { 
                        if (typeof selectBox == "string") { 
                            selectBox = document.getElementById(selectBox);
                            chkbox = document.getElementById(chkbox);
                        } 
                        if (chkbox.checked) { 
                                for (var i = 0; i < selectBox.options.length; i++) { 
                                selectBox.options[i].selected = true; 
                                }     
                       }  
                        else
                        {
                            for (var i = 0; i < selectBox.options.length; i++) { 
                                selectBox.options[i].selected = false; 
                                }  
                        }
                }
               
			</script>
            <!--Chaitraly::CSS for pop up table -->
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
								/* transition: 1s; */
								/* overflow-x: auto; */
								/* height: 50%;*/
							}
							div#pop-up .modal-dialog{
								width:80%;
							}
			</style>
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
					/* width:32%;
					float:left; */
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
                    top: 50px;
					left: 580px;
					right: 0;
					/*text-align: center; */
					color: #f77802;
					font-size: 16px;
					font-family: 'open sans';
					font-weight: 600;
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