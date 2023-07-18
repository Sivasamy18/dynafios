<!--<link rel="stylesheet"  href="{{ asset('assets/css/lightslider.css') }}"/>-->
<!--<script type="text/javascript" src="{{ asset('assets/js/lightslider.js') }}"></script>-->
<script type="text/javascript">
    google.charts.setOnLoadCallback(drawChartAll);
    function drawChartAll() {
        @foreach($regions_data as $region_data)
            var data{{$region_data["region_id"]}} = google.visualization.arrayToDataTable([
                ['Contracts', 'Count',{type: 'string', role: 'tooltip'} @if(count($region_data['region_data']) > 0) ,{type: 'number', role: 'id'} @endif],
                    @if(count($region_data['region_data']) > 0)
                    @foreach ($region_data['region_data'] as $contract_stat)
                ['{{$contract_stat['contract_type_name']}}', {{$contract_stat['active_contract_count']}},'{{$contract_stat['contract_type_name'].' \n'.$contract_stat['active_contract_count'].' ('}}'+numeral({{$contract_stat['total_spend']}}).format('$0,0[.]00')+')',{{$contract_stat['contract_type_id']}}],
                    @endforeach
                    @else
                ['No Active Contract Present.',1,'No Active Contract Present.']
                @endif
                //                ['Co-Management', 11,'change \n done',1],
                //                ['Medical Directorship',      2,'change',2],
                //                ['On Call',  2,'change',3],
                //                ['Month to Month', 2,'change',4],
                //                ['Per Diem',    7,'change',5]
            ]);

            var options{{$region_data["region_id"]}} = {
                title: '',
                legend: @if(count($region_data) > 0) 'labeled' @else 'none' @endif,
                pieHole: @if(count($region_data['region_data']) > 0) {{0.4}} @else {{0.8}} @endif,
                chartArea:{left:0,top:"10%",bottom:5,width:"100%",height:"100%"},
                height: 250,
                colors: [@if(count($region_data['region_data']) == 0) "d3d3d3" , @endif "#221f1f","#a09284","#f68a1f","#e98125", "#d0743c", "#515151", "#947E6E", "#B5A790", "#6A5A4E", "#564940","#000","#f2f2f2", "#5c5247","#d2c2b3", "#d8d23a",
                    "#0c6197", "#7d9058", "#207f33", "#44b9b0", "#bca44a", "#e4a14b", "#a3acb2", "#8cc3e9", "#69a6f9", "#5b388f",
                    "#546e91", "#8bde95", "#d2ab58", "#273c71", "#98bf6e", "#4daa4b", "#98abc5", "#cc1010", "#31383b", "#006391",
                    "#c2643f", "#b0a474", "#a5a39c", "#a9c2bc", "#22af8c", "#7fcecf", "#987ac6", "#3d3b87", "#b77b1c", "#c9c2b6",
                    "#807ece", "#8db27c", "#be66a2", "#9ed3c6", "#00644b", "#005064", "#77979f", "#77e079", "#9c73ab", "#1f79a7"],
                @if(count($region_data) == 0)
                pieSliceText: 'none',
                enableInteractivity: false,
                'tooltip' : {
                    trigger: 'none'
                }
                @endif
            };

            var chart{{$region_data["region_id"]}} = new google.visualization.PieChart(document.getElementById('donutchartRegio{{$region_data["region_id"]}}'));
            chart{{$region_data["region_id"]}}.draw(data{{$region_data["region_id"]}}, options{{$region_data["region_id"]}});
        @endforeach
    }
    $(document).ready(function() {
        var slider2 = $("#content-slider2").lightSlider({
            item:2,
            loop:false,
            keyPress:true,
            speed:700,
            auto:false,
            pager: false,
            controls: @if(count($regions_data) <= 1) false @else true @endif,
            onAfterSlide: function (el) {
                //console.log('current', el.getCurrentSlideCount());
                if(el.getCurrentSlideCount() == {{count($regions_data)}}){
//                    setTimeout(function(){
//                            refresh();
//                    },1800);
                }
            }
        });
         function refresh(){
             //console.log('refresh');
                 slider2.goToSlide(0);
                 //slider.play();
         }
    });
</script>
<div class="item">
    <ul id="content-slider2" class="content-slider">
        @foreach ($regions_data as $region_data)
            <li  style="padding: 0px 45px;">
                <span><b>{{$region_data["region_name"]}}</b></span>
                <div id="donutchartRegio{{$region_data["region_id"]}}" ></div>
            </li>
        @endforeach
            @if(count($regions_data) == 0)
                <li>
                    <span><b>No Regions Available To Display.</b></span>
                </li>
            @endif
    </ul>
</div>
<span class="contractDetails"> &nbsp; </span>