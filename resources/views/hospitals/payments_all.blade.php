@extends('layouts/_hospital', [ 'tab' => 9 ])
@section('content')
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="col-xs-7 invoiceDashboard" id="invoiceDashboard-selections">

            </div>
        </div>

		<script>
			
			function getDataforselected(redirect) {
				$(".overlay").show();
				$.ajax({
					url: "{{ route('agreements.getPaymentDetailsForInvoiceDashboard', $hospital->id) }}"+redirect,
					type: 'GET',
					/*data: {ids: idsArr, vals: dataArr,contract_ids: contractIdArray,practice_ids: practiceIdArray, start_date: start_date[1], end_date: end_date[0], selected_date: start_date_v, prev_values:prevAmountArray,printNew:printNewArray },*/
					success: function (data) {
						$("#invoiceDashboard-selections").html(data)
						//called when successful
						//$('.ajax-failed').hide();
						 $(".overlay").hide();

						//$('.ajax-success').show();

						$('html,body').animate({scrollTop: 0}, '3000');
						 /*setTimeout(function () {
						 $('.ajax-success').hide();
						 $('.ajax-error').hide();
						 //location.reload();
						 window.location='{{-- URL::route('agreements.payment', $hospital->id) --}}';
						 }, 3000);*/
					},
					error: function (xhr, textStatus, errorThrown) {
						$(".overlay").hide();
						$('.ajax-failed').show();
						$('#invoiceDashboard-selections #contracts ul').remove();
						$('#invoiceDashboard-selections #ajax_submit').remove();
						$('html,body').animate({scrollTop: 0}, '3000');
						setTimeout(function () {
							$('.ajax-failed').hide();
							$('.ajax-error').hide();
						}, 5000);
					}
				});
			}
			function getSelectedMonthData(id,type_id,cname_id,monthNumber){
				var practice_id =0;
				var physician_id =0;
				var redirectURL = "?a_id=" + id + "&p_id=" + practice_id + "&t_id=" + type_id + "&phy_id=" + physician_id + "&m_id=" + monthNumber+ "&cn_id=" + cname_id;
				//window.location.href = redirectURL;
				getDataforselected(redirectURL);
			}
			$(document).ready(function () {
				getDataforselected('');
			});
		</script>
    </div>
@endsection
