@php use function App\Start\is_health_system_region_user; @endphp
@php use function App\Start\is_health_system_user; @endphp
@extends('dashboard/_index_landing_page')

<style>
    .contractContent {
        cursor: pointer;
        margin: 10px 15px !important;
    }

    .pie_chart_heading_text {
        text-align: center;
        font-weight: bold;
        font-size: 17px;
    }

    .pie_chart_heading {
        border-bottom: 1px #d6d6d6 solid;
        padding: 1% 0;
    }

    .pie_chart_inner_container {
        width: 32%;
        float: left;
        /* margin: 0 0.5%; */
        margin: 0 0.5%;
        padding: 1% 1%;
        background: #fff;
        box-shadow: 0 0 2px #dadada;
        border-radius: 5px;
        position: relative;
        height: 341px;
    }

    .contract_details {
        width: 100%;
        /* float: left; */
        clear: both;
    }

    .contract_overview_heading_container_row, .contract_counters_heading_container_row {
        width: 100%;
        float: left;
        border: 1px solid;
    }

    .contract_overview_heading_container_left, .contract_counter_heading_container_left {
        width: 30%;
        float: left;
        visibility: hidden;
    }

    .contract_overview_heading_container_main, .contract_counter_heading_container_main {
        width: 40%;
        float: left;
    }

    .contract_overview_heading_text {
        text-align: center;
        font-weight: bold;
        font-size: 30px;
    }

    .contract_overview_heading_container_right, .contract_counter_heading_container_right {
        width: 30%;
        float: left;
        visibility: hidden;
    }

    .heading_not_active_contracts {
        clear: both;
        display: block;
        font-size: 30px;
    }

    .no_amount_to_display {
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
        background: #fff;
        margin-top: 0;
        padding: 10px 50px;
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

    .loader, .loaderAgreement, .loaderPayment, .loaderPopup,
    .loaderSpendYTD, .loaderTypeEffi, .loaderGaugeEffectiveness,
    .loaderGaugeActual, .loaderTypeAlerts, .loaderByRegions, .loaderByFacility {
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
        0% {
            -webkit-transform: rotate(0deg);
        }
        100% {
            -webkit-transform: rotate(360deg);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    .lable-align {
        text-align: right;
        padding-right: 0;
        margin-top: 7px;
    }

    .align-center {
        margin: 0 auto;
        float: none;
    }

    .contractDetails {
        position: relative;
        bottom: 125px;
        color: #d3d3d3;
    }

    .contractDetailsLine {
        position: absolute;
        bottom: 148px;
        left: 46px;
        color: #FFFFFF;
        width: 84%;
        border-top: 2px dashed #000;
    }

    #gauge_chart_container {
        margin-top: 20px;
    }

    .gauge table, .gauge1 table, .gauge-no table {
        margin: 0 auto !important;
    }

    .gauge svg g text, .gauge1 svg g text {
        font-size: 16px;
    }

    .countStatus, .expiringAggrement {
        height: 341px;;
    }

    .loaderByRegions @if(is_health_system_region_user()) , .loaderByFacility @endif {
        margin: 10% auto;
    }

    .loaderPopup {
        margin: 5% auto;
    }

    #facilityContractCount .google-visualization-table-page-number {
        width: 20px;
        height: 15px;
        margin: 0;
        line-height: 1.4;
    }

    .row-height {
        height: 20px;
    }

    #facilityContractCount table thead tr {
        height: 60px;
    }

    #facilityContractCount table thead tr th {
        text-align: center;
        font-size: 15px;
        font-family: 'open sans';
        font-weight: 700;
    }

    /*To remove fliker effect on donut hover*/
    #donutchart svg > g > g:last-child, #donut_regions svg > g > g:last-child,
    #donutchart1 svg > g > g:last-child {
        pointer-events: none;
    }

    /*#donutchart svg > g > g > path{
        display: none;
    }
    #donutchart svg > g > g > circle{
        display: none;
    }*/

    #agreementDataOverlay {
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
        @if(is_health_system_user())
            <div class="col-md-4">
                @else
                    <div style="display: none;">
                        @endif
                        <div class="form-group col-xs-12">
                            <label class="col-xs-2 control-label lable-align">Region: </label>
                            <div class="col-md-10 col-sm-10 col-xs-10">
                                {{ Form::select('region', $regions, Request::old('region',0), [ 'id'=>'region','class' => 'form-control' ]) }}
                            </div>
                        </div>
                    </div>

                    @if(is_health_system_user())
                        <div class="col-md-5">
                            @else
                                <div class="col-md-4 align-center padding_none">
                                    @endif
                                    <div class="form-group  col-xs-12">
                                        <label class="col-xs-4 control-label lable-align padding_none text_align_left">Organization: </label>
                                        <div class="col-md-8 col-sm-8 col-xs-8">
                                            {{ Form::select('hospital', $hospitals, Request::old('hospital',0), [ 'id'=>'hospital','class' => 'form-control' ]) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 padding_none">
                                    <div class="form-group  col-xs-12">
                                        <label class="col-xs-4 control-label lable-align padding_none text_align_left">Start
                                            Date: </label>
                                        <div class="col-md-8 col-sm-8 col-xs-8">
                                            {{ Form::select('start_date', $agreement_start_period, Request::old('selected_start_date',0), [ 'id'=>'start_date','class' => 'form-control' ]) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 padding_none">
                                    <div class="form-group  col-xs-12">
                                        <label class="col-xs-4 control-label lable-align padding_none text_align_left">End
                                            Date: </label>
                                        <div class="col-md-8 col-sm-8 col-xs-8">
                                            {{ Form::select('end_date', $agreement_end_period, Request::old('selected_end_date',0), [ 'id'=>'end_date','class' => 'form-control' ]) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2 healthsystemReportsButton padding_none">
                                    <div class="form-group col-xs-12">
                                        <a href="{{route('healthSystem.activeContractsReport',$group_id)}}">Reports</a>
                                        <input type="hidden" name="group" id="group" value="{{$group_id}}">
                                    </div>
                                </div>
                        </div>
                        <div id="pie_chart_container" class="pie_chart_container">
                            <div class="pie_chart_inner_container">
                                <div id="gauge_chart1_heading" class="pie_chart_heading">
                                    <div id="gauge_chart1_icon"></div>
                                    <div id="gauge_chart1_heading_text" class="pie_chart_heading_text">Contract Spend
                                        Effectiveness
                                    </div>
                                </div>
                                <div class="loaderGaugeEffectiveness"></div>
                                <div id="gauge_chart1" class="text-center"></div>
                            </div>
                            <div class="pie_chart_inner_container">
                                <div id="gauge_chart2_heading" class="pie_chart_heading">
                                    <div id="gauge_chart2_icon"></div>
                                    <div id="gauge_chart2_heading_text" class="pie_chart_heading_text">Contract Spend To
                                        Actual
                                    </div>
                                </div>
                                <div class="loaderGaugeActual"></div>
                                <div id="gauge_chart2" class="text-center"></div>

                            </div>
                            <div class="pie_chart_inner_container">
                                <div id="pie_chart3_heading" class="pie_chart_heading">
                                    <div id="pie_chart3_icon"></div>
                                    <div id="pie_chart3_heading_text" class="pie_chart_heading_text">Contract
                                        Effectiveness
                                    </div>
                                </div>

                                <div class="loaderTypeEffi"></div>
                                <div id="bar_chart1"></div>
                            </div>
                        </div>

                        <div class="contract_details"></div>

                        <div id="gauge_chart_container" class="pie_chart_container">
                            @if(is_health_system_user())
                                <div class="countStatus">
                                    <div id="pie_chart1_heading" class="pie_chart_heading">
                                        <div id="pie_chart1_icon"></div>
                                        <div id="pie_chart1_heading_text" class="pie_chart_heading_text">Active
                                            Contracts By Region
                                        </div>
                                    </div>
                                    <div class="loaderByRegions"></div>
                                    <div id="donut_regions" class="text-center"></div>
                                </div>
                            @elseif(is_health_system_region_user())
                                <div class="pie_chart_inner_container">
                                    <div id="pie_chart1_heading" class="pie_chart_heading">
                                        <div id="pie_chart1_icon"></div>
                                        <div id="pie_chart1_heading_text" class="pie_chart_heading_text">Active Contract
                                            Types
                                        </div>
                                    </div>
                                    <div class="loader"></div>
                                    <div id="pie_chart1" class="text-center"></div>
                                </div>
                                <div class="pie_chart_inner_container">
                                    <div id="pie_chart2_heading" class="pie_chart_heading">
                                        <div id="pie_chart2_icon"></div>
                                        <div id="pie_chart2_heading_text" class="pie_chart_heading_text">Contract Spend
                                            Year To Date
                                        </div>
                                    </div>
                                    <div class="loaderSpendYTD"></div>
                                    <div id="pie_chart2" class="text-center"></div>

                                </div>
                            @endif
                            <div class="pie_chart_inner_container">
                                <div id="pie_chart3_heading" class="pie_chart_heading">
                                    <div id="pie_chart3_icon"></div>
                                    <div id="pie_chart3_heading_text" class="pie_chart_heading_text">Contracts With No
                                        Payments
                                    </div>
                                </div>

                                <div class="loaderTypeAlerts"></div>
                                <div id="bar_chart2"></div>
                            </div>
                        </div>

                        <div class="contract_details"></div>

                        <div class="contractOverview text-center pie_chart_container">
                            @if(is_health_system_user())
                                <div class="pie_chart_inner_container">
                                    <div id="pie_chart1_heading" class="pie_chart_heading">
                                        <div id="pie_chart1_icon"></div>
                                        <div id="pie_chart1_heading_text" class="pie_chart_heading_text">Active Contract
                                            Types
                                        </div>
                                    </div>
                                    <div class="loader"></div>
                                    <div id="pie_chart1" class="text-center"></div>
                                </div>
                                <div class="pie_chart_inner_container">
                                    <div id="pie_chart2_heading" class="pie_chart_heading">
                                        <div id="pie_chart2_icon"></div>
                                        <div id="pie_chart2_heading_text" class="pie_chart_heading_text">Overall Spend -
                                            All Contracts Year To Date
                                        </div>
                                    </div>
                                    <div class="loaderSpendYTD"></div>
                                    <div id="pie_chart2" class="text-center"></div>

                                </div>
                            @endif
                            <div class="expiringAggrement">
                                <div class="loaderByFacility"></div>
                                <div id="facilityContractCount"></div>
                            </div>
                        </div>

                        <div class="facilityAgreement col-xs-12">
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

                            div#pop-up .modal-dialog {
                                width: 80%;
                            }
                        </style>
                        <!-- HIDDEN / POP-UP DIV -->

                        <div id="pop-up" class="modal">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                                            &times;
                                        </button>
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
                        <link rel="stylesheet" href="{{ asset('assets/css/lightslider.css') }}"/>
                        <script type="text/javascript" src="{{ asset('assets/js/lightslider.js') }}"></script>
                        <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
                        <script type="text/javascript" src="{{ asset('assets/js/numeral.js') }}"></script>
                        <!--<script src="//cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>-->

                        <script>
                            h_id_array = new Array();
                            google.charts.load('current', {packages: ['corechart', 'bar', 'gauge', 'table']});
                            /*For first hardcoded pie chart*/
                            $(document).ready(function () {
                                // $("#start_date option:first").attr("selected", "selected");
                                // $("#end_date option:last").attr("selected", "selected");

                                var now = new Date();
                                var current_start = new Date();
                                current_start.setFullYear(now.getFullYear() - 1)
                                let closest_start = Infinity;
                                let closest_end = Infinity;

                                $("#start_date > option").each(function () {
                                    const date = new Date(this.value);

                                    if (date >= current_start && (date < new Date(closest_start) || date < closest_start)) {
                                        closest_start = this.value;
                                        $("#start_date").val(closest_start);
                                    }
                                });

                                $("#end_date > option").each(function () {
                                    const date = new Date(this.value);

                                    if (date >= now && (date < new Date(closest_end) || date < closest_end)) {
                                        closest_end = this.value;
                                        $("#end_date").val(closest_end);
                                    }
                                });

                                $(".close").click(function () {
                                    $('div#pop-up  .modal-dialog').css('top', '-100%');
                                    $('body').css('overflow-y', 'auto');
                                    setTimeout(function () {
                                        $('div#pop-up').hide();
                                        //$('div#pop-up').css('transition','1s');
                                    }, 100);
                                });
                                $("#region").change(function () {
                                    var value = $(this).val();

                                    $.ajax({
                                        /*url: '{{ URL::current() }}?contract_type=' + value,*/
                                        url: 'getRegionFacilities/' + value,
                                        dataType: 'json'
                                    }).done(function (response) {
                                        $('[name=hospital] option').remove();
                                        $('[name=hospital]').append('<option value="0">All</option>');
                                        var response_sorted_array = response.sorted_array;
                                        $.each(response_sorted_array, function (index, value) {
                                            $('[name=hospital]').append('<option value="' + value.id + '">' + value.name + '</option>');
                                        });
                                    });
                                    getActiveContractTypesChart();
                                    getContractSpendYTDChart();
                                    getContractTypesEffectivenessChart();
                                    getContractSpendEffectivenessChart();
                                    getContractSpendToActualChart();
                                    getContractTypesAlertsChart();
                                    google.charts.setOnLoadCallback(getFacilityContractCountDataByAjax);
                                });

                                $("#hospital").change(function () {
                                    var region_id = $('#region').val();
                                    var facility = $(this).val();

                                    $.ajax({
                                        url: 'getHospitalAgreementStartEndDate/' + region_id + '/' + facility,
                                        type: 'get',
                                    }).done(function (response) {
                                        $('[name=start_date] option').remove();
                                        $.each(response.agreement_start_period, function (index, value) {
                                            $('[name=start_date]').append('<option value="' + index + '">' + value + '</option>');
                                        });

                                        $('[name=end_date] option').remove();
                                        $.each(response.agreement_end_period, function (index, value) {
                                            $('[name=end_date]').append('<option value="' + index + '">' + value + '</option>');
                                        });

                                        $("#start_date option:first").attr("selected", "selected");
                                        $("#end_date option:last").attr("selected", "selected");
                                    });

                                    getActiveContractTypesChart();
                                    getContractSpendYTDChart();
                                    getContractTypesEffectivenessChart();
                                    getContractSpendEffectivenessChart();
                                    getContractSpendToActualChart();
                                    getContractTypesAlertsChart();
                                });

                                $("#start_date, #end_date").change(function () {
                                    getActiveContractTypesChart();
                                    getContractSpendYTDChart();
                                    getContractTypesEffectivenessChart();
                                    getContractSpendEffectivenessChart();
                                    getContractSpendToActualChart();
                                    getContractTypesAlertsChart();
                                });

                                $('.format_amount').each(function () {
                                    var text = $(this).text();
                                    var split_text = text.split("-");
                                    var amount = split_text[1];
                                    var full_text = split_text[0] + '- ' + numeral(amount).format('$0,0[.]00');
                                    $(this).html(full_text);
                                    $(this).attr('title', full_text);

                                });
                                //var string = numeral(509250).format('$ 0,0[.]00');

                                /*var pie_div_width=parseInt((0.98)*($('#pie1').width()));
                                 var pie_div_height=parseInt((0.85)*($('#pie1').width()));*/


                                getActiveContractTypesChart();
                                getContractSpendYTDChart();
                                getAgreementDataByAjax();
                                setTimeout(function () {
                                    getContractTypesEffectivenessChart();
                                    getContractSpendEffectivenessChart();
                                    getContractSpendToActualChart();
                                }, 1200);
                                getContractTypesAlertsChart();
                                @if(is_health_system_user())
                                getContractTypesByRegionChart();
                                @endif
                                google.charts.setOnLoadCallback(getFacilityContractCountDataByAjax);

                                /*$('#menu>li:first-child').addClass( "active" );
                                 $('#menu>li:first-child>ul>li:first-child').addClass( "active" );
                                 $('#menu>li:first-child>ul>li:first-child>ul>li:first-child').addClass( "active" );*/

                            });

                            function callMenu() {
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
                                }).on('show.metisMenu', function (event) {
                                    var h_id = $(event.target).parent('li').children('a').children('div').children('span').last().attr('data-info');
                                    //alert(h_id + ' opened');
                                    if (h_id > 0 && h_id_array.indexOf(h_id) == -1) {
                                        $('#agreementDataOverlay').show();
                                        $.ajax({
                                            url: '/getFacilityContractSpecifyDataByAjax/' + h_id,
                                            type: 'get',
                                            success: function (response) {
                                                $('#hos-list-' + h_id).html(response);
                                            },
                                            complete: function () {
                                                $(event.target).parent('li').children('a').children('div').children('span').last().attr('data-info', '0');
                                                $('#menu').metisMenu('dispose');
                                                callMenu();
                                                $('#agreementDataOverlay').hide();
                                                //$('.overlay').hide();
                                                // $('.loader').hide();
                                            }
                                        });
                                        index = 1;
                                        h_id_array.push(h_id);
                                    }
                                });
                            }

                            // @description : function to call  pie charts
                            // @return html
                            function getActiveContractTypesChart() {
                                $('#pie_chart1').html('');
                                $('.loader').show();
                                $.ajax({
                                    url: '/getActiveContractTypesChart/' + $("#region").val() + '/' + $("#hospital").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#pie_chart1').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loader').hide();
                                    }
                                });
                            }

                            function getContractSpendYTDChart() {
                                $('#pie_chart2').html('');
                                $('.loaderSpendYTD').show();
                                $.ajax({
                                    url: '/getContractSpendYTDChart/' + $("#region").val() + '/' + $("#hospital").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#pie_chart2').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderSpendYTD').hide();
                                    }
                                });
                            }

                            // @description : function to get effectivness bar chart
                            // @return html
                            function getContractTypesEffectivenessChart() {
                                $('#bar_chart1').html('');
                                $('.loaderTypeEffi').show();
                                $.ajax({
                                    url: '/getContractTypesEffectivenessChart/' + $("#region").val() + '/' + $("#hospital").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#bar_chart1').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderTypeEffi').hide();
                                    }
                                });
                            }

                            // @description : function to call gauge charts
                            // @return html
                            function getContractSpendEffectivenessChart() {
                                $('#gauge_chart1').html('');
                                $('.loaderGaugeEffectiveness').show();
                                $.ajax({
                                    url: '/getContractSpendEffectivenessChart/' + $("#region").val() + '/' + $("#hospital").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#gauge_chart1').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderGaugeEffectiveness').hide();
                                    }
                                });
                            }

                            // @description : function to call gauge charts for spend to actual
                            // @return html
                            function getContractSpendToActualChart() {
                                $('#gauge_chart2').html('');
                                $('.loaderGaugeActual').show();
                                $.ajax({
                                    url: '/getContractSpendToActualChart/' + $("#region").val() + '/' + $("#hospital").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#gauge_chart2').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderGaugeActual').hide();
                                    }
                                });
                            }

                            // @description : function to get Alert chart
                            // @return json
                            function getContractTypesAlertsChart() {
                                $('#bar_chart2').html('');
                                $('.loaderTypeAlerts').show();
                                $.ajax({
                                    url: '/getContractTypesAlertsChart/' + $("#region").val() + '/' + $("#hospital").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#bar_chart2').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderTypeAlerts').hide();
                                    }
                                });
                            }

                            // @description : function to get region contract types
                            // @return json
                            function getContractTypesByRegionChart() {
                                $('#donut_regions').html('');
                                $('.loaderByRegions').show();
                                $.ajax({
                                    url: '/getContractTypesByRegionChart' + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        $('#donut_regions').html(response);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderByRegions').hide();
                                    }
                                });
                            }

                            // @description : function to call agreement details count
                            // @return html
                            function getAgreementDataByAjax() {
                                $('.loaderAgreement').show();
                                $.ajax({
                                    url: 'getAgreementDataByAjaxForHealthSystem' + '/' + $("#group").val(),
                                    type: 'get',
                                    success: function (response) {
                                        //console.log("agreementDataByAjax:",response);
                                        $('#agreementDataByAjax').html(response);
                                    },
                                    complete: function () {
                                        @if(is_health_system_user())
                                        $('#menu>li:first-child').addClass("active");
                                        @endif
                                        /*$('#menu>li:first-child>ul>li:first-child').addClass( "active" );
                                        $('#menu>li:first-child>ul>li:first-child>ul>li:first-child').addClass( "active" );*/
                                        callMenu();
                                        $('.loaderAgreement').hide();
                                    }
                                });

                            }

                            // @description : function to call facility contract count
                            // @return html
                            function getFacilityContractCountDataByAjax() {
                                $('#facilityContractCount').html('');
                                $('.loaderByFacility').show();

                                var datat = new google.visualization.DataTable();
//								datat.addColumn({type: 'number', role: 'id'});
                                datat.addColumn('string', 'Contracts By Organization');
                                datat.addColumn('number', '');
                                $.ajax({
                                    url: '/getFacilityContractCountDataByAjax/' + $("#region").val() + '/' + $("#group").val(),
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        //$('#donut_regions').html(response);
                                        datat.addRows(
                                            response.rows
                                        );
                                        var container = document.getElementById('facilityContractCount');
                                        var table = new google.visualization.Table(container);
                                        /*var table = new google.visualization.Table(document.getElementById('facilityContractCount'));*/

                                        /*google.visualization.events.addListener(table, 'change', function () {
                                            var tableRows = container.getElementsByTagName('tbody')[0].rows;
                                            // change first cell to link
                                            var index = 0;
                                            Array.prototype.forEach.call(tableRows, function(row) {
                                                /*console.log(response.ids[index]);*/
                                        /*row.cells[0].innerHTML = '<a href="#">' + row.cells[0].innerHTML + '</a>';*/
                                        /*row.setAttribute("class", "dt-rows");*/
                                        /*row.setRowProperties(index,{'id':response.ids[index]});
                                        row.setAttribute("data-info", response.ids[index]);
                                        row.setAttribute("data-name", row.cells[0].innerHTML);
                                        index = index+1;
                                    });
                                });*/


                                        table.draw(datat, {
                                            showRowNumber: false,
                                            showRowId: false,
                                            width: '100%',
                                            height: '95%',
                                            pageSize: 5,
                                            allowHtml: true,
                                            cssClassNames: {
                                                tableCell: 'row-height'
                                            }
                                        });
                                        google.visualization.events.addListener(table, 'select', selectionHandlerTable);

                                        function selectionHandlerTable() {
                                            var selection = table.getSelection();
                                            var item = selection[0];
                                            console.log(datat.getFormattedValue(item.row, 0));
                                            getContractList(datat.getFormattedValue(item.row, 0));
                                        }

                                        /*$("#facilityContractCount table tbody tr").click(function(){
                                            alert('clicked');
                                            getContractList($(this).attr("data-info"),$(this).attr("data-name"));
                                        });*/


                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderByFacility').hide();
                                    }
                                });


                                $('.loaderByFacility').hide();
                            }

                            function getContractList(hospital_name) {
                                //var topping = data3.getValue(selectedItem.row, 0);
                                //alert('The user selected ' + topping);
                                $('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
                                $('div#pop-up .modal-dialog').css('top', '-100%');
                                $('.modal-title').html('List of ' + hospital_name + ' Contracts: ');
                                $('div#pop-up').show();
                                $('body').css('overflow-y', 'hidden');
                                var group_id = $("#group").val();
                                setTimeout(function () {
                                    $('div#pop-up .modal-dialog').css('top', '15%');
                                    //$('div#pop-up').css('transition','1s');
                                }, 1);
                                $('.loaderPopup').show();
                                $.ajax({
                                    url: '/getFacilityActiveContracts/' + hospital_name + '/' + group_id,
                                    type: 'get',
                                    data: {
                                        start_date: $("#start_date").val(),
                                        end_date: $("#end_date").val()
                                    },
                                    success: function (response) {
                                        var text = "<table class='table'><thead>" +
                                            "<tr><th>Organization</th>" +
                                            "<th>Contract Name</th>" +
                                            "<th>Physician Name</th>" +
                                            "<th class='text-center'>Start Date</th>" +
                                            "<th class='text-center'>End Date</th></tr></thead>" +
                                            "<tbody data-link='row' class='rowlink'>";

                                        $.each(response, function (key, value) {
                                            text = text + "<tr><td>" + value.hospital_name + "</td>" +
                                                "<td>" + value.contract_name + "</td>" +
                                                "<td>" + value.physician_name + "</td>" +
                                                "<td class='text-center'>" + value.agreement_start_date + "</td>" +
                                                "<td class='text-center'>" + value.manual_contract_end_date + "</td></tr>";
                                        });
                                        text = text + "</tbody></table>";
                                        $('div#pop-up .modal-body').html(text);
                                    },
                                    complete: function () {
                                        //$('.overlay').hide();
                                        $('.loaderPopup').hide();
                                    }
                                });
                            }

                        </script>
                        <style>

                            .countStatus {
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

                            .expiringAggrement {
                                margin: 0 0 0 .5%;
                                padding: 0;
                                @if(is_health_system_region_user())
 width: 98%;
                            @endif

                            }
                        </style>
@endsection
