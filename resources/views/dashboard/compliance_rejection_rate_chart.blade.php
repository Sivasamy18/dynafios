<script type="text/javascript">
    google.charts.setOnLoadCallback(drawChart);

	function drawChart() {
		var data = new google.visualization.DataTable({
			@if(count($log_rejection_data) > 0)
				cols: [
					{label: 'X', type: 'string'},
					@foreach ($log_rejection_data as $log_rejection)
						{label: '{{$log_rejection['name']}}', type: 'number'},
						{role: 'annotationText', type: 'string'},
                        {role: 'id', type: 'number'},
					@endforeach
				],
				rows: [
					{c:[{v: ''},
						@foreach ($log_rejection_data as $log_rejection)
							{ v: {{$log_rejection['total_rate']}}},
							{ v: '{{$log_rejection['name']}}'},
                            { v: {{$log_rejection['id']}}},
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
			@if(count($log_rejection_data) > 0)
				colors: ['#f68a1f', '#221f1f'],
			@else
				colors: [''],
			@endif
			legend: { position: 'top' },
			vAxis: {
					minValue: 1,
					maxValue: 5,
					ticks: [0,1,2,3,4,5]
			  },
			height:250,
			chartArea:{left:30,top:"10%",bottom:20,width:"90%",height:"90%"},
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

		var chart = new google.visualization.ColumnChart( document.getElementById('gauge_div_1') );
		chart.draw(data, options);

	function selectHandler3() {
	
		var selectedItem = chart.getSelection()[0];
		if (selectedItem) {
            var topping = data.getValue(selectedItem.row, selectedItem.column + 1);
			var typeID = data.getValue(selectedItem.row, selectedItem.column + 2);

			if(typeID != 101){
				$('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
				$('div#pop-up .modal-dialog').css('top','-100%');
				$('.modal-title').html('Rejection Rate By '+topping);
				$('div#pop-up').show();
				$('body').css('overflow-y', 'hidden');
				setTimeout(function(){
					$('div#pop-up .modal-dialog').css('top','15%');
					//$('div#pop-up').css('transition','1s');
				},1);
				$('.loaderPopup').show();
				$.ajax({
					url:'/getComplianceRejectionRateOverall/'+$("#hospital").val()+'/'+typeID,
					type:'get',
					success:function(response){
						var text = "<table class='table'><thead>" +
								"<tr><th>Organization</th>" +
								"<th>Physician Name</th>" +
								"<th>Practice Name</th>" +
								"<th>Contract Name</th>" +
								"<th class='text-center' style='text-align:center !important;'>Start Date</th>" +
								"<th class='text-center' style='text-align:center !important;'>End Date</th>" +
								"<th class='text-center' style='text-align:center !important;'>Total Logs</th>" +
								"<th class='text-center' style='text-align:center !important;'>Total Rejected Logs</th>" +
								"<th class='text-center' style='text-align:center !important;'>Rejection Rate</th></tr></thead>" +
								"<tbody data-link='row' class='rowlink'>";

						$.each( response, function( key, value ) {
							text = text + "<tr><td>"+value.hospital_name+"</td>" +
										"<td>"+value.physician_name+"</td>" +
										"<td>"+value.practice_name+"</td>" +
										"<td>"+value.contract_name+"</td>" +
										"<td class='text-center' style='text-align:center !important;'>"+value.start_date+"</td>" +
										"<td class='text-center' style='text-align:center !important;'>"+value.end_date+"</td>" +
										"<td class='text-center' style='text-align:center !important;'>"+value.logs_count+"</td>" +
										"<td class='text-center' style='text-align:center !important;'>"+value.rejection_count+"</td>" +
										"<td class='text-center' style='text-align:center !importants;'>"+value.rate+" %</td></tr>";

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
	}
	google.visualization.events.addListener(chart, 'select', selectHandler3);
	chart.draw(data, options);	

	}
    
</script>
<div id="donutchart_overall">
</div>
<div id="gauge_div_1">
</div>
<span class="contractDetails">@if(count($log_rejection_data) == 0)Data Not Available. @else  &nbsp; @endif</span>
