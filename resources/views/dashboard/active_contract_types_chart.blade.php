
<script type="text/javascript">
        google.charts.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Contracts', 'Count',{type: 'string', role: 'tooltip'} @if(count($type_data) > 0) ,{type: 'number', role: 'id'} @endif],
                    @if(count($type_data) > 0)
                        @foreach ($type_data as $contract_stat)
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

            var chart = new google.visualization.PieChart(document.getElementById('donutchart'));
            function selectHandler() {
                var selectedItem = chart.getSelection()[0];
                if (selectedItem) {
                    var topping = data.getValue(selectedItem.row, 0);
                    var typeID = data.getValue(selectedItem.row, 3);
                    //alert('The user selected ' + topping);
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
                        url:'/getActiveContractsByType/'+$("#region").val()+'/'+$("#hospital").val()+'/'+typeID+'/'+$("#group").val(),
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
                                    "<th class='text-center'>End Date</th></tr></thead>" +
                                    "<tbody data-link='row' class='rowlink'>";

                            $.each( response, function( key, value ) {
                                text = text + "<tr><td>"+value.hospital_name+"</td>" +
                                        "<td>"+value.contract_name+"</td>" +
                                        "<td>"+value.physician_name+"</td>" +
                                        "<td class='text-center'>"+value.agreement_start_date+"</td>" +
                                        "<td class='text-center'>"+value.manual_contract_end_date+"</td></tr>";
                            });
                                text = text+"</tbody></table>";
                            $('div#pop-up .modal-body').html(text);
                        },
                        complete:function(){
                            //$('.overlay').hide();
                            $('.loaderPopup').hide();
                        }
                    });

                }
            }

            @if(count($type_data) > 0)
                google.visualization.events.addListener(chart, 'select', selectHandler);
            @endif
            chart.draw(data, options);
        }
    </script>
<div id="donutchart">
</div>
<span class="contractDetails">@if(count($type_data) == 0)No Active Contract Present. @else  &nbsp; @endif</span>
