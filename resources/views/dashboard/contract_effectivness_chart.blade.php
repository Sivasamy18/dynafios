<script type="text/javascript">
    google.charts.setOnLoadCallback(drawStacked);
    function drawStacked() {
        var data3 = google.visualization.arrayToDataTable([
            ['Type', 'Contracts', { role: 'style' }, { role: 'annotation' },{type: 'string', role: 'tooltip'} @if(count($effectivness_data) > 0) ,{type: 'number', role: 'id'} @endif,'Effectiveness', { type:'boolean',role:'certainty' }],
            @if(count($effectivness_data) > 0)
                @foreach ($effectivness_data as $contract_stat)
                    ['{{$contract_stat['contract_type_name']}}', {{$contract_stat['contract_effectiveness']}},'{{$contract_stat['style_color']}}','{{$contract_stat['contract_effectiveness']." %"}}','{{$contract_stat['active_contract_count']}} Contract(s)',{{$contract_stat['contract_type_id']}}, 50, false],
                @endforeach
            @else
                ['',0,'#d3d3d3','No Active Contract Present.','',50,false]
            @endif
        ]);

        var options3 = {
            title: '',
            isStacked: true,
            chartArea:{left:30,top:"10%",bottom:40,width:"90%",height:"90%"},
            height:250,
            annotations: {
                highContrast: false,
                textStyle: {
                    auraColor: 'none',
                    color: '#000',
                    fontWeight: 600
                }
            },
            seriesType: 'bars',
            series: {
                1: {
                    type: 'line',
                    color:'black',
                    enableInteractivity: false
                 }
            },
            legend: { position: "none" },
            vAxis: {
                minValue: 0,
                maxValue: 100,
                ticks: [20,40,60,80,100]
            },
            @if(count($effectivness_data) == 0)
                enableInteractivity: false,
                'tooltip' : {
                    trigger: 'none'
                }
            @endif
        };



        var chart3 = new google.visualization.ColumnChart(document.getElementById('barchart_div'));

        function selectHandler3() {
            var selectedItem = chart3.getSelection()[0];
            if (selectedItem) {
                var topping = data3.getValue(selectedItem.row, 0);
                var typeID = data3.getValue(selectedItem.row, 5);
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
                    url:'/getActiveContractsTypeEffectivness/'+$("#region").val()+'/'+$("#hospital").val()+'/'+typeID+'/'+$("#group").val(),
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
                                "<th class='text-center'>Expected Hours</th>" +
                                "<th class='text-center'>Worked Hours</th>" +
                                "<th class='text-center'>Maximum Annual Hours</th></tr></thead>" +
                                "<tbody data-link='row' class='rowlink'>";

                        $.each( response, function( key, value ) {
                            text = text + "<tr><td>"+value.hospital_name+"</td>" +
                                    "<td>"+value.contract_name+"</td>" +
                                    "<td>"+value.physician_name+"</td>" +
                                    "<td class='text-center'>"+value.agreement_start_date+"</td>" +
                                    "<td class='text-center'>"+value.manual_contract_end_date+"</td>" +
                                    "<td class='text-center'>"+value.contract_effectiveness+" %</td>" +
                                    "<td class='text-center'>"+value.expected_hrs+"</td>" +
                                    "<td class='text-center'>"+value.worked_hrs+"</td>" +
                                    "<td class='text-center'>"+value.max_expected_hrs+"</td></tr>";
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
        google.visualization.events.addListener(chart3, 'select', selectHandler3);
        chart3.draw(data3, options3);
    }
</script>
<div id="barchart_div">
</div>
<span class="contractDetails"> &nbsp; </span>
