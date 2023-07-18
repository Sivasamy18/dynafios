
<script type="text/javascript">
    google.charts.setOnLoadCallback(drawChart);

	function drawChart() {
		var data = new google.visualization.DataTable({
			@if(count($type_data) > 0)
				cols: [
					{label: 'X', type: 'string'},
					@foreach ($type_data as $physician_stat)
						{label: '{{$physician_stat['physician_name']}}', type: 'number'},
						{role: 'annotationText', type: 'string'},
						{role: 'id', type: 'number'},
					@endforeach
				],
				rows: [
					{c:[{v: ''},
						@foreach ($type_data as $physician_stat)
							{ v: {{$physician_stat['rate']}}},
							{ v: '{{$physician_stat['physician_name']}}'},
							{ v: {{$physician_stat['physician_id']}}},
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
				colors: ['#f68a1f', '#221f1f', '#a09284', '#515151', '#d0743c', '#f2f2f2', '#5c5247'],
			@else
				colors: [''],
			@endif
			legend: { position: 'top' },
			hAxis: {
					minValue: 0,
					maxValue: 20,
					ticks: [0,5,10,15,20]
			  },
			height:250,
			chartArea:{left:10,top:"10%",bottom:20,width:"90%",height:"90%"},
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

		var chart = new google.visualization.BarChart( document.getElementById('donutchart_physician') );
		chart.draw(data, options);

	function selectHandler3() {
	
	var selectedItem = chart.getSelection()[0];
	
	if (selectedItem) {
		var topping = data.getValue(selectedItem.row, selectedItem.column + 1);
		var typeID = data.getValue(selectedItem.row, selectedItem.column + 2);
		
		$('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
		$('div#pop-up .modal-dialog').css('top','-100%');
		$('.modal-title').html('Rejection Rate By Provider '+topping);
		$('div#pop-up').show();
		$('body').css('overflow-y', 'hidden');
		setTimeout(function(){
			$('div#pop-up .modal-dialog').css('top','15%');
			//$('div#pop-up').css('transition','1s');
		},1);
		$('.loaderPopup').show();
		$.ajax({
			url:'/getComplianceRejectionByPhysician/'+$("#hospital").val()+'/' + typeID,
			type:'get',
			success:function(response){
				var text = "<table class='table'><thead>" +
						"<tr><th>Organization</th>" +
						"<th>Physician Name</th>" +
						"<th>Practice Name</th>" +
						"<th>Contract Name</th>" +
						"<th class='text-center' style='text-align:center !important;'>Total Logs</th>" +
						"<th class='text-center' style='text-align:center !important;'>Total Rejected Logs</th>" +
						"<th class='text-center' style='text-align:center !important;'>Rejection Rate</th></tr></thead>" +
						"<tbody data-link='row' class='rowlink'>";

				$.each( response, function( key, value ) {
					text = text + "<tr><td>"+value.hospital_name+"</td>" +
								"<td>"+value.physician_name+"</td>" +
								"<td>"+value.practice_name+"</td>" +
								"<td>"+value.contract_name+"</td>" +
								"<td class='text-center' style='text-align:center !important;'>"+value.logs_count+"</td>" +
								"<td class='text-center' style='text-align:center !important;'>"+value.rejection_count+"</td>" +
								"<td class='text-center' style='text-align:center !important;'>"+value.rate+" %</td></tr>";
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
google.visualization.events.addListener(chart, 'select', selectHandler3);
chart.draw(data, options);	
	
	}

    </script>
<div id="donutchart_physician">
</div>
<span class="contractDetails">@if(count($type_data) == 0)Data Not Available. @else  &nbsp; @endif</span>
<div id="barchart_div">
</div>
<span class="contractDetails"> &nbsp; </span>