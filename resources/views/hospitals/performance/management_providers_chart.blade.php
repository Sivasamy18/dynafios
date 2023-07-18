
<script type="text/javascript">
        google.charts.setOnLoadCallback(drawChart);
        function drawChart() {
            var data = google.visualization.arrayToDataTable([
                ['Providers', 'Count',{type: 'string', role: 'tooltip'} @if(count($type_data) > 0) ,{type: 'number', role: 'id'} @endif],
                    @if(count($type_data) > 0)
                        @foreach ($type_data as $category)
                            ['{{$category['category_name']}} - {{$category['total_duration']}} hours'.replaceAll('&amp;', '&').replaceAll('&lt;', '<').replace('&gt;', '>').replaceAll('&#039;', "'"), {{$category['total_duration']}},'{{$category['category_name'].' \n'.$category['total_duration'].' hours'}}'.replaceAll('&amp;', '&').replaceAll('&lt;', '<').replace('&gt;', '>').replaceAll('&#039;', "'"),{{$category['category_id']}}],
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

            var chart = new google.visualization.PieChart(document.getElementById('donutchart_providers'));
            
			function selectHandler() {
				var selectedItem = chart.getSelection()[0];
				
				if (selectedItem) {
					var topping = data.getValue(selectedItem.row, selectedItem.column + 0);
					var category_id = data.getValue(selectedItem.row, selectedItem.column + 3);
						
					var category = topping.lastIndexOf('-');
					var topping = topping.slice(0, category);
					
					$('div#pop-up .modal-body').html('<div class="loaderPopup" style="display:none"></div>');
					$('div#pop-up .modal-dialog').css('top','-100%');
					$('.modal-title').html('List of physician logs under '+ topping + ' action category');
					$('div#pop-up').show();
					$('body').css('overflow-y', 'hidden');
					
					setTimeout(function(){
						$('div#pop-up .modal-dialog').css('top','15%');
					},1);
						
					$('.loaderPopup').show();
					
					$.ajax({
						url:'/getManagementDutyPopUp/' + $("#regions").val() + '/'+ $("#hospital").val() + '/' + $("#practice_type").val() + '/' + $("#contract_type").val() + '/' + $("#specialty").val() + '/' + $("#provider").val() + '/' + $("#group_id").val() + '/' + category_id,
						type:'get',
						success:function(response){
							var text = "<table class='table'><thead>" +
									"<tr><th>Organization</th>" +
									"<th>Contract Name</th>" +
									"<th>Physician Name</th>" +
									"<th class='text-center' style='text-align:center !important;'>Log Date</th>" +
									"<th class='text-center' style='text-align:center !important;'>Duration</th>" +
									"<th>Action</th>" +
									"<th>Details</th></tr></thead>" +
									"<tbody data-link='row' class='rowlink'>";

							$.each( response, function( key, value ) {
								text = text + "<tr><td>"+value.organization+"</td>" +
											"<td>"+value.contract_name+"</td>" +
											"<td>"+value.physician_name+"</td>" +
											"<td class='text-center' style='text-align:center !important;'>"+value.log_date+"</td>" +
											"<td class='text-center' style='text-align:center !important;'>"+value.duration+"</td>" +
											"<td>"+value.action+"</td>" +
											"<td>"+value.details+"</td></tr>";
							});
								text = text+"</tbody></table>";
								$('div#pop-up .modal-body').html(text);
						},
							complete:function(){
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
<div id="donutchart_providers">
</div>
<span class="contractDetails">@if(count($type_data) == 0)Data Not Available. @else  &nbsp; @endif</span>
