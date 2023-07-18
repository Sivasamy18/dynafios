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
										@if($contract_stat1['contract_type_id'] == ContractType::CO_MANAGEMENT)
    { label:"{{$contract_stat1['contract_type_name']}}" , value: {{$contract_stat1['active_contract_count']}}, color: "#221f1f"  },
    @elseif ($contract_stat1['contract_type_id'] == ContractType::MEDICAL_DIRECTORSHIP)
        { label:"{{$contract_stat1['contract_type_name']}}s" , value: {{$contract_stat1['active_contract_count']}}, color: "#a09284"  },
    @elseif ($contract_stat1['contract_type_id'] == ContractType::ON_CALL)
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
										@if($contract_stat2['contract_type_id'] == ContractType::CO_MANAGEMENT)
    { label:"{{$contract_stat2['contract_type_name']}}" , value: {{$contract_stat2['total_spend']}}, color: "#221f1f" },
    @elseif ($contract_stat2['contract_type_id'] == ContractType::MEDICAL_DIRECTORSHIP)
        { label:"{{$contract_stat2['contract_type_name']}}s" , value: {{$contract_stat2['total_spend']}}, color: "#a09284" },
    @elseif ($contract_stat2['contract_type_id'] == ContractType::ON_CALL)
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
										@if($contract_stat3['contract_type_id'] == ContractType::CO_MANAGEMENT)
    { label:"{{$contract_stat3['contract_type_name']}}" , value: {{$contract_stat3['total_paid']}}, color: "#221f1f"  },
    @elseif ($contract_stat3['contract_type_id'] == ContractType::MEDICAL_DIRECTORSHIP)
        { label:"{{$contract_stat3['contract_type_name']}}s" , value: {{$contract_stat3['total_paid']}}, color: "#a09284"  },
    @elseif ($contract_stat3['contract_type_id'] == ContractType::ON_CALL)
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
            $('#menu>li:first-child').addClass( "active" );
            $('#menu>li:first-child>ul>li:first-child').addClass( "active" );
            $('#menu>li:first-child>ul>li:first-child>ul>li:first-child').addClass( "active" );
            callMenu();
            $('.loaderAgreement').hide();
        }
    });

}