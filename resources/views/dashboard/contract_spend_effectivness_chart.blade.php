<!--<link rel="stylesheet"  href="{{ asset('assets/css/lightslider.css') }}"/>-->
<!--<script type="text/javascript" src="{{ asset('assets/js/lightslider.js') }}"></script>-->
<script type="text/javascript">
    google.charts.setOnLoadCallback(drawGauge);

    var gaugeOptions = {height: 250,min: 0, max: 100, redFrom: 0, redTo: 50,yellowFrom: 50, yellowTo: 80,
        greenFrom: 80, greenTo: 100, minorTicks: 5};

    function drawGauge() {
        @foreach ($effectivness_data as $contract_stat)
           var gauge{{$contract_stat["payment_type_id"]}};
            gaugeData{{$contract_stat["payment_type_id"]}} = new google.visualization.DataTable();
            gaugeData{{$contract_stat["payment_type_id"]}}.addColumn('number', '');
            gaugeData{{$contract_stat["payment_type_id"]}}.addRows(1);
            gaugeData{{$contract_stat["payment_type_id"]}}.setCell(0, 0, {{$contract_stat["contract_effectiveness"]}});
        var formatter = new google.visualization.NumberFormat(
                {suffix: '%',pattern:'#.#'}
        );
        formatter.format(gaugeData{{$contract_stat["payment_type_id"]}},0);
            gauge{{$contract_stat["payment_type_id"]}} = new google.visualization.Gauge(document.getElementById('gauge_div_{{$contract_stat["payment_type_id"]}}'));
            gauge{{$contract_stat["payment_type_id"]}}.draw(gaugeData{{$contract_stat["payment_type_id"]}}, gaugeOptions);
        @endforeach
        @if(count($effectivness_data) == 0)
            var gauge0;
            gaugeData0 = new google.visualization.DataTable();
            gaugeData0.addColumn('number', 'No Spend data');
            gaugeData0.addRows(1);
            gaugeData0.setCell(0, 0, 0);

            gauge0 = new google.visualization.Gauge(document.getElementById('gauge_div_0'));
            gauge0.draw(gaugeData0, gaugeOptions);
        @endif
    }
    $(document).ready(function() {
        var slider = $("#content-slider").lightSlider({
            item:1,
            loop:false,
            keyPress:true,
            speed:700,
            auto:false,
            pager: false,
            controls: @if(count($effectivness_data) <= 1) false @else true @endif,
            onAfterSlide: function (el) {
                //console.log('current', el.getCurrentSlideCount());
                if(el.getCurrentSlideCount() == {{count($effectivness_data)}}){
//                    setTimeout(function(){
//                            refresh();
//                    },1800);
                }
            }
        });

        $(".gauge").click(function(){
            //slider.pause();
            console.log("id  ==  "+ $(this).attr("data-id"));
            var topping = $(this).attr("data-name");
            var typeID = $(this).attr("data-id");
            $('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
            $('div#pop-up .modal-dialog').css('top','-100%');
            $('.modal-title').html('List of '+topping+' Contracts: ');
            $('div#pop-up').show();
            $('body').css('overflow-y', 'hidden');
            setTimeout(function(){
                $('div#pop-up .modal-dialog').css('top','15%');
                //$('div#pop-up').css('transition','1s');
            },1);
            $('.loaderPopup').show();
            $.ajax({
                url:'/getActiveContractSpendEffectivness/'+$("#region").val()+'/'+$("#hospital").val()+'/'+typeID+'/'+$("#group").val(),
                type:'get',
                data:{start_date:$("#start_date").val(),
					end_date:$("#end_date").val()
				},
                success:function(response){
                    var text = "<table class='table'><thead>" +
                            "<tr><th>Organization</th>" +
                            "<th>Contract Name</th>" +
                            "<th>Physician Name</th>" +
                            "<th class='text-center'>Start Date</th>" +
                            "<th class='text-center'>End Date</th>" +
                            "<th class='text-center'>Effectiveness</th>" +
                            "<th class='text-center'>Expected Spend</th>" +
                            "<th class='text-center'>Actual Spend</th>" +
                            "<th class='text-center'>Maximum Annual Spend</th></tr></thead>" +
                            "<tbody data-link='row' class='rowlink'>";

                    $.each( response, function( key, value ) {
                        text = text + "<tr><td>"+value.hospital_name+"</td>" +
                                "<td>"+value.contract_name+"</td>" +
                                "<td>"+value.physician_name+"</td>" +
                                "<td class='text-center'>"+value.agreement_start_date+"</td>" +
                                "<td class='text-center'>"+value.manual_contract_end_date+"</td>" +
                                "<td class='text-center'>"+value.contract_effectiveness+" %</td>" +
                                "<td class='text-right'>"+numeral(Math.round(value.expected_spend)).format('$0,0')+"</td>" +
                                "<td class='text-right'>"+numeral(Math.round(value.actual_spend)).format('$0,0')+"</td>"  +
                                "<td class='text-right'>"+numeral(Math.round(value.max_expected_payment)).format('$0,0')+"</td></tr>";
                    });
                    text = text+"</tbody></table>";
                    $('div#pop-up .modal-body').html(text);
                },
                complete:function(){
                    //$('.overlay').hide();
                    $('.loaderPopup').hide();
                }
            });
        });
         function refresh(){
             //console.log('refresh');
                 slider.goToSlide(0);
                 //slider.play();
         }
    });
</script>
<div class="item">
    <ul id="content-slider" class="content-slider">
        @foreach ($effectivness_data as $contract_stat)
            <li>
                <div id="gauge_div_{{$contract_stat["payment_type_id"]}}" class="gauge" data-id="{{$contract_stat["payment_type_id"]}}" data-name="{{$contract_stat["payment_type_name"]}}"></div>

                <span><b>{{$contract_stat["payment_type_name"]}}</b></span>
            </li>
        @endforeach
            @if(count($effectivness_data) == 0)
                <li class="gauge-no">
                    <div id="gauge_div_0"></div>
                </li>
            @endif
    </ul>
</div>
<span class="contractDetails"> &nbsp; </span>
