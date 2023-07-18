<!--<link rel="stylesheet"  href="{{ asset('assets/css/lightslider.css') }}"/>-->
<!--<script type="text/javascript" src="{{ asset('assets/js/lightslider.js') }}"></script>-->
<script type="text/javascript">
    google.charts.setOnLoadCallback(drawGauge);

    var gaugeOptions1 = {height: 250,min: 0, max: 100, redFrom: 0, redTo: 50,yellowFrom: 50, yellowTo: 80,
        greenFrom: 80, greenTo: 100, minorTicks: 5};

    function drawGauge() {
        @foreach ($overall_log_rejection_data as $rejection_data)
           var gauge{{$rejection_data["id"]}};
            gaugeData{{$rejection_data["id"]}} = new google.visualization.DataTable();
            gaugeData{{$rejection_data["id"]}}.addColumn('number', '');
            gaugeData{{$rejection_data["id"]}}.addRows(1);
            gaugeData{{$rejection_data["id"]}}.setCell(0, 0, {{$rejection_data["total_rate"]}});
        var formatter1 = new google.visualization.NumberFormat(
                {suffix: '%',pattern:'#.#'}
        );
        formatter1.format(gaugeData{{$rejection_data["id"]}},0);
            gauge{{$rejection_data["id"]}} = new google.visualization.Gauge(document.getElementById('gauge_div_2'));
            gauge{{$rejection_data["id"]}}.draw(gaugeData{{$rejection_data["id"]}}, gaugeOptions1);
            @break
        @endforeach
        @if(count($overall_log_rejection_data) == 0)
            var gauge0;
            gaugeData0 = new google.visualization.DataTable();
            gaugeData0.addColumn('number', 'No Spend data');
            gaugeData0.addRows(1);
            gaugeData0.setCell(0, 0, 0);

            gauge0 = new google.visualization.Gauge(document.getElementById('gauge_div1_0'));
            gauge0.draw(gaugeData0, gaugeOptions1);
        @endif
    }
    $(document).ready(function() {
        var slider = $("#content-slider1").lightSlider({
            item:1,
            loop:false,
            keyPress:true,
            speed:700,
            auto:false,
            pager: false,
            controls: false,
            onAfterSlide: function (el) {
                //console.log('current', el.getCurrentSlideCount());
                if(el.getCurrentSlideCount() == {{count($overall_log_rejection_data)}}){
//                    setTimeout(function(){
//                            refresh();
//                    },1800);
                }
            }
        });

        // $(".gauge1").click(function(){
        //     //slider.pause();
        //     console.log("id  ==  "+ $(this).attr("data-id"));
        //     var topping = $(this).attr("data-name");
        //     var typeID = $(this).attr("data-id");
        //     var total = $(this).attr("data-spend");
        //     $('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
        //     $('div#pop-up .modal-dialog').css('top','-100%');
        //     $('.modal-title').html('List of '+topping+' Contracts: ');
        //     $('div#pop-up').show();
        //     $('body').css('overflow-y', 'hidden');
        //     setTimeout(function(){
        //         $('div#pop-up .modal-dialog').css('top','15%');
        //         //$('div#pop-up').css('transition','1s');
        //     },1);
        //     $('.loaderPopup').show();
        //     $.ajax({
        //         url:'/getContractSpendToActual/'+$("#region").val()+'/'+$("#hospital").val()+'/'+typeID+'/'+total+'/'+$("#group").val(),
        //         type:'get',
        //         success:function(response){
        //             var text = "<table class='table'><thead>" +
        //                     "<tr><th>Facility</th>" +
        //                     "<th>Contract Name</th>" +
        //                     "<th>Physician Name</th>" +
        //                     "<th class='text-center'>Start Date</th>" +
        //                     "<th class='text-center'>End Date</th>" +
        //                     "<th class='text-center'>Expected Spend CYTD</th>" +
        //                     "<th class='text-center'>Actual Spend CYTD</th>"+
        //                     "<th class='text-center'>Maximum Annual Spend</th></tr></thead>" +
        //                     "<tbody data-link='row' class='rowlink'>";

        //             $.each( response, function( key, value ) {
        //                 text = text + "<tr><td>"+value.hospital_name+"</td>" +
        //                         "<td>"+value.contract_name+"</td>" +
        //                         "<td>"+value.physician_name+"</td>" +
        //                         "<td class='text-center'>"+value.agreement_start_date+"</td>" +
        //                         "<td class='text-center'>"+value.manual_contract_end_date+"</td>" +
        //                         "<td class='text-right'>"+numeral(Math.round(value.expected_spend_YTD)).format('$0,0')+"</td>" +
        //                         "<td class='text-right'>"+numeral(Math.round(value.actual_spend_YTD)).format('$0,0')+"</td>"+
        //                         "<td class='text-right'>"+numeral(Math.round(value.max_expected_payment)).format('$0,0')+"</td></tr>";
        //             });
        //             text = text+"</tbody></table>";
        //             $('div#pop-up .modal-body').html(text);
        //         },
        //         complete:function(){
        //             //$('.overlay').hide();
        //             $('.loaderPopup').hide();
        //         }
        //     });
        // });
         function refresh(){
             //console.log('refresh');
                 slider.goToSlide(0);
                 //slider.play();
         }
    });
</script>
<div class="item">
    <ul id="content-slider1" class="content-slider">
                <div id="gauge_div_2" class="gauge"></div>
            @if(count($overall_log_rejection_data) == 0)
                <li class="gauge-no">
                    <div id="gauge_div1_0"></div>
                </li>
            @endif
    </ul>
</div>
<span class="contractDetails"> &nbsp; </span>
