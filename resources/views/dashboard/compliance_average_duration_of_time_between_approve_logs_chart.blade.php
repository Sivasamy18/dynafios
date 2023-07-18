<script type="text/javascript">
    google.charts.setOnLoadCallback(drawChart);

	function drawChart() {
		var data = new google.visualization.DataTable({
			@if(count($type_data) > 0)
				cols: [
					{label: 'X', type: 'string'},
					@foreach ($type_data as $contract_stat)
						{label: '{{$contract_stat['contract_type_name']}}', type: 'number'},
						{role: 'annotationText', type: 'string'},
						{role: 'id', type: 'number'},
					@endforeach
				],
				rows: [
					{c:[{v: ''},
						@foreach ($type_data as $contract_stat)
							{ v: {{$contract_stat['contract_average_days']}}},
							{ v: '{{$contract_stat['contract_type_name']}}'},
							{ v: {{$contract_stat['contract_type_id']}}},
						@endforeach
					]},
				]
			@else
				cols: [
						{label: 'X', type: 'string'},
						{label: '', type: 'number'},
					  ],
				rows: [
						{c:[{v: ''}, { v: 0 }]}
					  ]
			@endif
		});

		var options = {
			@if(count($type_data) > 0)
				colors: ["#221f1f","#a09284","#f68a1f","#e98125", "#d0743c", "#515151", "#5c5247","#000","#f2f2f2", "#d2c2b3", "#d8d23a",
						"#0c6197", "#7d9058", "#207f33", "#44b9b0", "#bca44a", "#e4a14b", "#a3acb2", "#8cc3e9", "#69a6f9", "#5b388f",
						"#546e91", "#8bde95", "#d2ab58", "#273c71", "#98bf6e", "#4daa4b", "#98abc5", "#cc1010", "#31383b", "#006391",
						"#c2643f", "#b0a474", "#a5a39c", "#a9c2bc", "#22af8c", "#7fcecf", "#987ac6", "#3d3b87", "#b77b1c", "#c9c2b6",
						"#807ece", "#8db27c", "#be66a2", "#9ed3c6", "#00644b", "#005064", "#77979f", "#77e079", "#9c73ab", "#1f79a7"],
			@else
				colors: [''],
			@endif
			legend: { position: 'none' },
			vAxis: {
					minValue: 0,
					maxValue: 50,
					ticks: [0,10,20,30,40,50],
					title: 'Days'
			  },
			height:250,
			chartArea:{left:50,top:"10%",bottom:20,width:"85%",height:"90%"},
			annotations: {
				highContrast: false,
				textStyle: {
					auraColor: 'none',
					color: '#000',
					fontWeight: 600
				}
			},
			seriesType: 'bars',
		};

		var chart = new google.visualization.ColumnChart( document.getElementById('columnchart_div_1') );
		
		function selectHandler() {
            var selectedItem = chart.getSelection()[0];
            if (selectedItem) {
                var topping = data.getValue(selectedItem.row, selectedItem.column + 1);
				var typeID = data.getValue(selectedItem.row, selectedItem.column + 2);
				
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
                    url:'/getAverageDurationOfProviderApprovalPopUp/'+$("#hospital").val()+'/'+typeID,
                    type:'get',
                    success:function(response){
                        var text = "<table class='table'><thead>" +
                                "<tr><th>Organization</th>" +
								"<th>Physician Name</th>" +
                                "<th>Contract Name</th>" +
                                "<th class='text-center' style='text-align:center !important;'>Start Date</th>" +
                                "<th class='text-center' style='text-align:center !important;'>End Date</th>" +
                                "<th class='text-center' style='text-align:center !important;'>Average Days</th>" +
                                "</tr></thead>" +
                                "<tbody data-link='row' class='rowlink'>";

                        $.each( response, function( key, value ) {
                            text = text + "<tr><td>"+value.hospital_name+"</td>" +
                                    "<td>"+value.physician_name+"</td>" +
									"<td>"+value.contract_name+"</td>" +
                                    "<td class='text-center' style='text-align:center !important;'>"+value.agreement_start_date+"</td>" +
                                    "<td class='text-center' style='text-align:center !important;'>"+value.agreement_end_date+"</td>" +
                                    "<td class='text-center' style='text-align:center !important;'>"+value.rate+"</td>" +
                                    "</tr>";
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
		google.visualization.events.addListener(chart, 'select', selectHandler);
		chart.draw(data, options);
	}
    
</script>
<div id="columnchart_div_1">
</div>
<span class="contractDetails">@if(count($type_data) == 0)Data Not Available. @else  &nbsp; @endif</span>
