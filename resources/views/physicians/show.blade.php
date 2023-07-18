@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_physician', ['tab' => 1])
@section('actions')
  <!-- physician to multiple hosptial by 1254 -->
  <a class="btn btn-default btn-reset-password"
     href="{{ URL::route('physicians.reset_password', [$physician->id,$practice->id]) }}">
    <i class="fa fa-refresh fa-fw"></i> Reset Password
  </a>
  @if (is_super_user() || is_super_hospital_user())
    <!-- physician to multiple hosptial by 1254 :1002-->
    <a class="btn btn-default btn-delete" href="{{ URL::route('physicians.delete', [$physician->id,$practice->id]) }}">
      <i class="fa fa-trash-o fa-fw"></i> Delete
    </a>
  @endif
@endsection
@section('content')
  <div class="row">
    <div class="col-xs-8">
      <h4>Recent Activity</h4>

      <div class="recent-activity">
        {!! $table !!}
      </div>
    </div>
    <div class="col-xs-4">
      <div class="panel panel-default">
        <div class="panel-heading">Physician Information</div>
        <div class="panel-body">
          <table class="table" style="font-size: 12px;">
            <tr>
              <td>Name:</td>
              <td style="word-break: break-word;">{{ "{$physician->first_name} {$physician->last_name}" }}</td>
            </tr>
            <tr>
              <td>NPI:</td>
              <td>{{ $physician->npi }}</td>
            </tr>
            @if (is_super_user())
              <tr>
                <td>Email</td>
                <td style="word-break: break-word;"><a href="mailto:{{ $physician->email }}">{{ $physician->email }}</a>
                </td>
              </tr>
            @endif
            <tr>
              <td>Specialty:</td>
              <td style="word-break: break-word;">{{ $physician->specialty->name }}</td>
            </tr>
            <tr>
              <td>Locked:</td>
              @isset($user)
                @if($user->locked===1)
                  <td style="word-break: break-word;">True</td>
                @else
                  <td style="word-break: break-word;">False</td>
                @endif
              @else
                <td style="word-break: break-word;">Unable to find user account with same email in Group 2</td>
              @endisset
            </tr>
            <tr>
              <td>Password Expiration:</td>
              @isset($user)
                <td>{{ format_date($user->password_expiration_date) }}</td>
              @else
                <td style="word-break: break-word;">Unable to find user account with same email in Group 2</td>
              @endisset
            </tr>
            <tr>
              <td>Created:</td>
              <td>{{ format_date($physician->created_at) }}</td>
            </tr>
            <tr>
              <td>Updated:</td>
              <td>{{ format_date($physician->updated_at) }}</td>
            </tr>
          </table>
        </div>
      </div>
      @foreach ($contracts as $contract)
        <!--Not printing expired contracts -->
        @if($contract->agreement->end_date > date("Y-m-d"))
          <div class="panel panel-default">
            <div class="panel-heading">{{ contract_name($contract) }}</div>
            <div class="panel-body">

                    <?php
                    $unit = "Period";
                    if ($contract->payment_frequency_type == 1) {
                        $unit = "Month";
                    } else if ($contract->payment_frequency_type == 2) {
                        $unit = "Week";
                    } else if ($contract->payment_frequency_type == 3) {
                        $unit = "Bi-Week";
                    } else if ($contract->payment_frequency_type == 4) {
                        $unit = "Quarter";
                    }
                    ?>
              <table class="table" style="font-size: 12px;">
                <tr>
                  <td>Start Date:</td>
                  <td>{{ format_date($contract->agreement->start_date) }}</td>
                </tr>
                <tr>
                  <td>End Date:</td>
                  {{--                                    <td>{{ format_date($contract->agreement->end_date) }}</td>--}}
                  <td>{{ format_date($contract->manual_contract_end_date) }}</td>
                </tr>
                @if ($contract->payment_type_id==App\PaymentType::HOURLY)
                  <tr>
                    <td>Min Hours:</td>
                    <td>{{ number_format($contract->min_hours, 2) }} / {{$unit}}</td>
                  </tr>
                  <tr>
                    <td>Max Hours:</td>
                    <td>{{ number_format($contract->max_hours, 2) }} / {{$unit}}</td>
                  </tr>
                @endif

                @if ($contract->payment_type_id==App\PaymentType::PER_DIEM)
                  @if(formatNumber($contract->weekday_rate) >0 || formatNumber($contract->weekend_rate) > 0 || formatNumber($contract->holiday_rate) > 0 )
                              <?php $weekday = false; $weekend = false; $holiday = false; ?>
                    @foreach ( $contract['actions'] as $actions )
                      @if ( $actions['name'] == 'Weekday - FULL Day - On Call' || $actions['name'] == 'Weekday - HALF Day - On Call')
                                      <?php $weekday = true ?>
                      @elseif ( $actions['name'] == 'Weekend - FULL Day - On Call' || $actions['name'] == 'Weekend - HALF Day - On Call')
                                      <?php $weekend = true ?>
                      @elseif ( $actions['name'] == 'Holiday - FULL Day - On Call' || $actions['name'] == 'Holiday - HALF Day - On Call')
                                      <?php $holiday = true ?>
                      @endif
                    @endforeach
                    @if ( $weekday == true)
                      <tr>
                        <td>Weekday Rate:</td>
                        <td>${{ formatNumber($contract['weekday_rate'])}} / day</td>
                      </tr>
                    @endif
                    @if ( $weekend == true)
                      <tr>
                        <td>Weekend Rate:</td>
                        <td>${{ formatNumber($contract['weekend_rate'])}} / day</td>
                      </tr>
                    @endif
                    @if ( $holiday == true)
                      <tr>
                        <td>Holiday Rate:</td>
                        <td>${{ formatNumber($contract['holiday_rate'])}} / day</td>
                      </tr>
                    @endif
                  @else
                    @foreach ( $contract['actions'] as $actions )
                      @if ( $actions['name'] == 'On-Call')
                        <tr>
                          <td>{{ $actions['display_name'] }} Rate:</td>
                          <td>${{ formatNumber($contract->on_call_rate) }} / day</td>
                        </tr>
                      @elseif ( $actions['name'] == 'Called-Back')
                        <tr>
                          <td>{{ $actions['display_name'] }} Rate:</td>
                          <td>${{ formatNumber($contract->called_back_rate) }} / day</td>
                        </tr>
                      @else
                        <tr>
                          <td>{{ $actions['display_name'] }} Rate:</td>
                          <td>${{ formatNumber($contract->called_in_rate) }} / day</td>
                        </tr>
                      @endif
                    @endforeach
                  @endif
                @endif
                @if ($contract->payment_type_id==App\PaymentType::HOURLY)
                  <tr>
                    <td>Rate:</td>
                    <td>${{ formatNumber($contract->rate) }} / Hour</td>
                  </tr>
                @endif
                @if ($contract->payment_type_id==App\PaymentType::STIPEND)
                  <tr>
                    <td>Expected Hours:</td>
                    <td>{{ number_format($contract->expected_hours, 2) }}</td>
                  </tr>
                  <tr>
                    <td>FMV Rate:</td>
                    <td>${{ formatNumber($contract->rate) }} / Hour</td>
                  </tr>
                @endif
                @if ($contract->payment_type_id==App\PaymentType::MONTHLY_STIPEND)
                  <tr>
                    <td>Expected Hours:</td>
                    <td>{{ number_format($contract->expected_hours, 2) }}</td>
                  </tr>
                  <tr>
                    <td>Monthly Stipend Rate:</td>
                    <td>${{ formatNumber($contract->rate) }} / {{$unit}}</td>
                  </tr>
                @endif
                @if ($contract->payment_type_id==App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
                  @foreach ( $contract['uncompenseted_rates'] as $uncompenseted_rate )
                    @if ( $uncompenseted_rate->rate != null)
                      <tr>
                        <td>On Call Rate {{ $uncompenseted_rate->rate_index }} :</td>
                        <td>${{ formatNumber($uncompenseted_rate->rate) }} / Day</td>
                      </tr>
                    @endif
                  @endforeach
                @endif
                @if ($contract->payment_type_id==App\PaymentType::PER_UNIT)
                  <tr>
                    <td>Min Units:</td>
                    <td>{{ round($contract->min_hours, 0) }} / {{$unit}}</td>
                  </tr>
                  <tr>
                    <td>Max Units:</td>
                    <td>{{ round($contract->max_hours, 0) }} / {{$unit}}</td>
                  </tr>
                  <tr>
                    <td>Rate:</td>
                    <td>${{ formatNumber($contract->rate) }} / Unit</td>
                  </tr>
                @endif
              </table>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  </div>
  <div id="modal-confirm-reset" class="modal fade">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">Reset Password?</h4>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to reset this physician's password?</p>

          <p>
            <small>Note: The new password will be emailed to the physicians current email address.</small>
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary">Reset Password</button>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div><!-- /.modal -->
  <div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
          <h4 class="modal-title">Delete Physician?</h4>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this physician?</p>

          <p><strong style="color: red">Warning!</strong><br>
            This action will delete this physician and any associated data. There is no way to
            restore this data once this action has been completed.
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary">Delete</button>
        </div>
      </div>
      <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
  </div><!-- /.modal -->
@endsection
@section('scripts')
  <script type="text/javascript">
      $(function () {
          Dashboard.confirm({
              button: '.btn-reset-password',
              dialog: '#modal-confirm-reset',
              dialogButton: '#modal-confirm-reset .btn-primary'
          });

          Dashboard.confirm({
              button: '.btn-delete',
              dialog: '#modal-confirm-delete',
              dialogButton: '#modal-confirm-delete .btn-primary'
          });
      });
  </script>
@endsection
