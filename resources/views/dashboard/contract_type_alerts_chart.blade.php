<script type="text/javascript">
    google.charts.setOnLoadCallback(drawStacked);
    function drawStacked() {
        var data6 = google.visualization.arrayToDataTable([
            ['Type', 'Contracts', { role: 'annotation' },{type: 'string', role: 'tooltip'} @if(count($alerts_data) > 0) ,{type: 'number', role: 'id'},'Remaining',{type: 'string', role: 'tooltip'} @endif],
            @if(count($alerts_data) > 0)
                @foreach ($alerts_data as $contract_stat)
                    ['{{$contract_stat['contract_type_name']}}', {{$contract_stat['alerts']}},'{{$contract_stat['alerts']}}','{{$contract_stat['contract_type_name'].'\n'.$contract_stat['alerts']}} Contract(s) with no payments.',{{$contract_stat['contract_type_id']}},{{$contract_stat['remaining']}},'{{$contract_stat['contract_type_name'].'\n'.$contract_stat['remaining']}} Contract(s) with payments.'],
                @endforeach
            @else
                ['',0,'No Contract Present.','No Contract Present.']
            @endif
        ]);

        var options6 = {
            title: '',
            isStacked: true,
            chartArea:{left:30,top:"10%",bottom:40,width:"90%",height:"90%"},
            height:250,
            legend: { position: "none" },
            vAxis: {
                minValue: 0
            },
            series: {
                0: {
                    color: '#221f1f'
                },
                1: {
                    color: '#a09284'
                }
            },
            @if(count($alerts_data) == 0)
                enableInteractivity: false,
                'tooltip' : {
                    trigger: 'none'
                }
            @endif
        };



        var chart6 = new google.visualization.ColumnChart(document.getElementById('barchart_div1'));

        function selectHandler6() {
            var selectedItem = chart6.getSelection()[0];
            if (selectedItem) {
                var topping = data6.getValue(selectedItem.row, 0);
                var typeID = data6.getValue(selectedItem.row, 4);
                var colLabel = data6.getColumnLabel(selectedItem.column);
                //alert('The user selected ' + colLabel);
                $('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
                $('div#pop-up .modal-dialog').css('top','-100%');
                $('.modal-title').html('List of '+topping+' Contracts with no Payments: ');
                var URL = 'getContractTypesAlerts';
                var noPayment = 0;
                if(colLabel == 'Remaining'){
                    /*URL = 'getActiveContractsByType';*/
                    $('.modal-title').html('List of '+topping+' Contracts with Payments: ');
                    noPayment = 1;
                }
                $('div#pop-up').show();
                $('body').css('overflow-y', 'hidden');
                setTimeout(function(){
                    $('div#pop-up .modal-dialog').css('top','15%');
                    //$('div#pop-up').css('transition','1s');
                },1);
                $('.loaderPopup').show();
                $.ajax({
                    url:'/'+URL+'/'+$("#region").val()+'/'+$("#hospital").val()+'/'+typeID+'/'+noPayment+'/'+$("#group").val(),
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
        google.visualization.events.addListener(chart6, 'select', selectHandler6);
        chart6.draw(data6, options6);
    }
</script>
<div id="barchart_div1">
</div>
<span class="contractDetails"> &nbsp; </span>
