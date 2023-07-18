@if (count($items) > 0)
<table class="table table-striped table-hover logs-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Date', 1, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Action', 2, $reverse, $page) !!}</th>
        <!-- <th>{!! HTML::sort_link('Action Type', 2, $reverse, $page) !!}</th> -->
        <th>{!! HTML::sort_link('Duration', 3, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Contract Name', 4, $reverse, $page) !!}</th>
        <th width="100">Approved</th>
        <th class="text-right" width="100">Actions</th>
    </tr>
    </thead>
    <tbody class="rowlink">
    @foreach ($items as $log)
    <tr>
        <td>{{ format_date($log->date) }}</td>
        
        @if($log->payment_type_id == App\PaymentType::PER_DIEM)
              <!-- <td>{{ $log->action->actionType->name }}</td> -->
              <td> {{$log->action->name}} </td>
        @else
               <td> {{$log->action->name}} </td>
       @endif
             
        @if($log->payment_type_id == App\PaymentType::PER_UNIT)
			<td>{{ round($log->duration, 0) }} <span>Units</span></td>
		@else
            <td>{{ formatNumber($log->duration) }} @if($log->partial_hours == 1 || ($log->payment_type_id == App\PaymentType::STIPEND ) || ($log->payment_type_id == App\PaymentType::HOURLY) || ($log->payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS || ($log->payment_type_id == App\PaymentType::MONTHLY_STIPEND) || ($log->payment_type_id == App\PaymentType::TIME_STUDY))) <span>Hour(s)</span> @else <span>Day</span>  @endif</td>
		@endif

        <td>{{ $log->contract_name }}</td>
        <td>{{ $log->physician_approved }}</td>
        <td class="text-right rowlink-skip">
            @if($log->approval_date =='0000-00-00' && $log->signature == 0)
                <div class="btn-group btn-group-xs">
			<!-- physician to multiple hosptial by 1254 -->
                    <a class="btn btn-default btn-delete"
                  href="{{ URL::route('physicians.delete_log', [ $physician->id, $log->id,$practice->id ]) }}">
                        <i class="fa fa-trash-o fa-fw"></i>
                    </a>
                </div>
            @else
                <div class="btn-group btn-group-xs">
                    <a class="btn btn-default btn-error" href="{{ URL::route('physicians.delete_log', [ $physician->id, $log->id,$practice->id ]) }}">
                        <i class="fa fa-trash-o fa-fw"></i>
                    </a>
                </div>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are currently no logs available for display.</div>
</div>
@endif