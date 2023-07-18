@extends('layouts/_hospital', [ 'tab' => 9 ])
@section('content')
    <div class="panel panel-default">
        @if(count($agreements) > 0)
        <div class="panel-body">
            <div class="col-xs-5">
                    <div class="pendingPayment">
                        <span class="">Pending Payments</span>
                        <ul>
                            @if(count($contract_period_list)>0)
                            @foreach($contract_period_list as $agreement_id =>$agreements_list)
                                <li class="list_agreement_name"><label>{{$agreements_list["name"]}}</label></li>
                                <ul class="borderZero">
                                    @foreach($agreements_list['types'] as $type_id => $type_name_data )
                                        @foreach($type_name_data as $name_id => $name_data )
                                            <li class="list_contract_name"><label>{{$name_data["c_name"]}}</label></li>
                                            <ul class="borderZero">
                                                @foreach($name_data["c_months"] as $month_number=>$months)
                                                    <li class="list_month_name"><button onclick="getSelectedMonthData({{$agreement_id}},{{$type_id}},{{$name_id}},{{$month_number}})" type="button" class="btn btn-default">{{$months}}</button></li>
                                                @endforeach
                                            </ul>
                                        @endforeach
                                    @endforeach
                                </ul>
                            @endforeach
                            @else
                                <li class="list_agreement_name"><label>No Contracts Pending Payments</label></li>
                            @endif
                        </ul>
                    </div>
            </div>
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
                  @if(count($contract_period_list)>0)
                    $('.list_month_name').first().find("button").trigger("click");
                  @else
                    getDataforselected('');
                  @endif
                    
                });
            </script>
        @else
            <p>There are currently no contracts available for display.</p>
        @endif
    </div>
@endsection
