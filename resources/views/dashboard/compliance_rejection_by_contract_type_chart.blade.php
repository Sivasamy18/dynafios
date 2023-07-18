
<script type="text/javascript">
        google.charts.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Contracts', 'Count',{type: 'string', role: 'tooltip'} @if(count($type_data) > 0) ,{type: 'number', role: 'id'} @endif],
                    @if(count($type_data) > 0)
                        @foreach ($type_data as $contract_type_stat)
                            ['{{$contract_type_stat['contract_type_name']}}', {{$contract_type_stat['rejection_count']}},'{{$contract_type_stat['contract_type_name'].' \n'.$contract_type_stat['rejection_count'].' ('}}'+numeral({{$contract_type_stat['logs_count']}}).format('0,0[.]00')+')',{{$contract_type_stat['contract_type_id']}}],
                        @endforeach
                    @else
                        ['No Active Contract Present.',1,'No Active Contract Present.']
                    @endif
            ]);

            var options = {
                title: '',
                legend: @if(count($type_data) > 0) 'labeled' @else 'none' @endif,
                pieHole: @if(count($type_data) > 0) {{0.4}} @else {{0.8}} @endif,
                chartArea:{left:0,top:"10%",bottom:5,width:"100%",height:"100%"},
                height: 250,
                colors: [@if(count($type_data) == 0) "d3d3d3" , @endif "#221f1f","#a09284","#f68a1f","#e98125", "#d0743c", "#515151", "#947E6E", "#B5A790", "#6A5A4E", "#564940","#000","#f2f2f2", "#5c5247","#d2c2b3", "#d8d23a",
                    "#0c6197", "#7d9058", "#207f33", "#44b9b0", "#bca44a", "#e4a14b", "#a3acb2", "#8cc3e9", "#69a6f9", "#5b388f",
                    "#546e91", "#8bde95", "#d2ab58", "#273c71", "#98bf6e", "#4daa4b", "#98abc5", "#cc1010", "#31383b", "#006391",
                    "#c2643f", "#b0a474", "#a5a39c", "#a9c2bc", "#22af8c", "#7fcecf", "#987ac6", "#3d3b87", "#b77b1c", "#c9c2b6",
                    "#807ece", "#8db27c", "#be66a2", "#9ed3c6", "#00644b", "#005064", "#77979f", "#77e079", "#9c73ab", "#1f79a7"],
                @if(count($type_data) == 0)
                    pieSliceText: 'none',
                    enableInteractivity: false,
                    'tooltip' : {
                        trigger: 'none'
                    }
                @endif
            };

            var chart = new google.visualization.PieChart(document.getElementById('donutchart_contract_type'));
            function selectHandler() {
                
            }

            @if(count($type_data) > 0)
                google.visualization.events.addListener(chart, 'select', selectHandler);
            @endif
            chart.draw(data, options);
        }
    </script>
<div id="donutchart_contract_type">
</div>
<span class="contractDetails">@if(count($type_data) == 0)Data Not Available. @else  &nbsp; @endif</span>
