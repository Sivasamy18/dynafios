<ul id="menu">
@foreach($agreement_data as $data)
<li>
  <a href="#" class="regionDetails has-arrow" aria-expanded="false">
    <div class="col-xs-11">
        <span title="{{$data['region_name']}}" class="agreementHeading hospitalName">{{$data['region_name']}}</span>
        <span class="fa plus-minus"></span>
     </div>
  </a>
  <ul>
  @foreach($data["hospitals_info"] as $hospitals_info)
      <li>
        <a href="#" class="hospitalDetails has-arrow" aria-expanded="false">
          <div class="col-xs-11">
            <span title="{{$hospitals_info['hospital_name']}}" class="agreementHeading hospitalName">{{$hospitals_info['hospital_name']}}</span>
            <span class="fa plus-minus" data-info = "{{$hospitals_info['hospital_id']}}"></span>
          </div>
        </a>
        <ul id="hos-list-{{$hospitals_info['hospital_id']}}">
            <li style="color: #f68a1f;"><b>Loading....</b></li>
          @foreach($hospitals_info["contracts_info"] as $contracts_info)
            <li>
              <a href="#" class="has-arrow" aria-expanded="false">
                <div class="col-xs-11">
                  <span title="{{$contracts_info['contract_name']}}" class="agreementHeading">{{$contracts_info['contract_name']}}</span>
                  <span title="Total Physicians - {{$contracts_info['contracts_physician_count']}}" class="agreementHeading">Total Physicians - {{$contracts_info['contracts_physician_count']}}</span>
                  <span title="Paid to Date -{{$contracts_info['contracts_paidToDate']}}" class="agreementHeading format_amount">Paid to Date -{{$contracts_info['contracts_paidToDate']}}</span>
                  <span class="fa plus-minus"></span>
                </div>
              </a>
              <ul>
                @foreach($contracts_info['practice_info'] as $practice_info)
                  <li class="practiceDetails">
                    <a href="#" class="has-arrow" aria-expanded="false">
                      <span title="{{$practice_info['practice_name']}}" class="agreementHeading">{{$practice_info['practice_name']}}</span>
                      <span title="{{$practice_info['total_physician']}} Physician(s)" class="agreementHeading">{{$practice_info['total_physician']}} Physician(s)</span>
                      <span title="Paid to Date -{{$practice_info['practice_paidToDate']}}" class="agreementHeading format_amount">Paid to Date -{{$practice_info['practice_paidToDate']}}</span>
                      <span class="fa plus-minus"></span>
                    </a>

                    <ul>
                      @foreach($practice_info['contract_info'] as $contract_info)
                        <li class="AMdetails">
                          <a class="contractInfoLandingPage">
                            <span title="{{$contract_info['physician_name']}}" class="agreementHeading">{{$contract_info['physician_name']}}</span>
                            @foreach($contract_info['approval_managers'] as $managers)
                              <span title="{{$managers['type']}}: {{$managers['manager_name']}}" class="agreementHeading">{{$managers['type']}}: {{$managers['manager_name']}}</span>
                            @endforeach


                            @if($contract_info['contract_document'] != 'NA')
                            @if(count($contract_info['contract_document'])>1)
                            <div class="" style="float:left;margin-top:0px;padding:0px;">
                            @else
                            <div class="" style="float:left;margin-top:10px;padding:0px;">
                            @endif

                            @foreach ($contract_info['contract_document'] as $document)
                              <img class="region_data_image" onClick="location.href='{{ URL::route('contract.document',  $document->filename) }}'" src="../assets/img/default/copyOfContract.png" alt="Copy of Contract"/>
                            @endforeach
                            </div>
                            @endif
                              <!-- <i class="fa fa-cloud-download"></i></span> -->

                      </span>
                            <div class="contractInfo"><img class="img-responsive" src="../assets/img/default/contractInfoIcon.png" alt="">
                              <div class="showContractInfo">

                                <div class="panel panel-default">
                                  <div class="panel-heading">{{$contract_info['contract_name']}}</div>
                                  <div class="panel-body">
                                    <table class="table" style="font-size: 12px;">
                                      <tr>
                                        <td>Start Date:</td>
                                        <td>{{ format_date($contract_info['agreement_start_date']) }}</td>
                                      </tr>
                                      <tr>
                                        <td>End Date:</td>
                                        <td>{{ format_date($contract_info['agreement_end_date']) }}</td>
                                      </tr>
                                      <tr>
                                        <td>Final Submission Date:</td>
                                        <td>{{ format_date($contract_info['agreement_valid_upto_date']) }}</td>
                                      </tr>
                                      @if ($contract_info['payment_type_id'] == App\PaymentType::HOURLY)
                                        <tr>
                                          <td>Min Hours:</td>
                                          <td>{{ number_format($contract_info['min_hours'], 2) }} / Month</td>
                                        </tr>
                                        <tr>
                                          <td>Max Hours:</td>
                                          <td>{{ number_format($contract_info['max_hours'], 2) }} / Month</td>
                                        </tr>
                                      @endif
                                      @if ($contract_info['payment_type_id']== App\PaymentType::PER_DIEM)
                                        @if(formatNumber($contract_info['weekday_rate']) >0 || formatNumber($contract_info['weekend_rate']) > 0 || formatNumber($contract_info['holiday_rate']) > 0 )
                                          <tr>
                                            <td>Weekday Rate:</td>
                                            <td>${{ formatNumber($contract_info['weekday_rate'])}} / day</td>
                                          </tr>
                                          <tr>
                                            <td>Weekend Rate:</td>
                                            <td>${{ formatNumber($contract_info['weekend_rate'])}} / day</td>
                                          </tr>

                                          <tr>
                                            <td>Holiday Rate:</td>
                                            <td>${{ formatNumber($contract_info['holiday_rate'])}} / day</td>
                                          </tr>
                                        @else
                                          <tr>
                                            <td>On Call Rate:</td>
                                            <td>${{ formatNumber($contract_info['on_call_rate'])}} / day</td>
                                          </tr>
                                          <tr>
                                            <td>Called Back Rate:</td>
                                            <td>${{ formatNumber($contract_info['called_back_rate'])}} / day</td>
                                          </tr>

                                          <tr>
                                            <td>Called In Rate:</td>
                                            <td>${{ formatNumber($contract_info['called_in_rate'])}} / day</td>
                                          </tr>
                                        @endif
                                      @endif
                                      @if ($contract_info['payment_type_id']== App\PaymentType::HOURLY)
                                        <tr>
                                          <td>Rate:</td>
                                          <td>${{ formatNumber($contract_info['FMV_rate'])}} / Hour</td>
                                        </tr>
                                      @endif
                                      @if ($contract_info['payment_type_id']== App\PaymentType::STIPEND)
                                        <tr>
                                          <td>Expected Hours:</td>
                                          <td>{{ number_format($contract_info['expected_hours'], 2) }}</td>
                                        </tr>
                                        <tr>
                                          <td>FMV Rate:</td>
                                          <td>${{ formatNumber($contract_info['FMV_rate']) }} / Hour</td>
                                        </tr>
                                      @endif
                                      @if ($contract_info['payment_type_id'] == App\PaymentType::PER_UNIT)
                                        <tr>
                                          <td>Min Units:</td>
                                          <td>{{ round($contract_info['min_hours'], 0) }} / Month</td>
                                        </tr>
                                        <tr>
                                          <td>Max Units:</td>
                                          <td>{{ round($contract_info['max_hours'], 0) }} / Month</td>
                                        </tr>
                                        <tr>
                                          <td>Rate:</td>
                                          <td>${{ formatNumber($contract_info['FMV_rate'])}} / Unit</td>
                                        </tr>
                                      @endif
                                    </table>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </a>
                        </li>
                      @endforeach
                    </ul>
                  </li>
                @endforeach
              </ul>
          @endforeach
        </ul>
      </li>
  @endforeach
  </ul>
</li>
@endforeach
</ul>
<script>
    /*For first hardcoded pie chart*/
    $(document).ready(function () {
        $('.format_amount').each(function () {
            var text = $(this).text();
            var split_text = text.split("-");
            var amount = split_text[1];
            var full_text = split_text[0] + '- ' + numeral(amount).format('$0,0[.]00');
            $(this).html(full_text);
            $(this).attr('title', full_text);

        });
    });
</script>
