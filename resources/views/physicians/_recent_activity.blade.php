@if ($recentLogs)
<table class="table table-striped table-hover recent-activity-table">
    <thead>
    <tr>
        <th>Date</th>
        <th>Action</th>
        <th>Time</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($recentLogs as $log)
    <tr>
        <td>{{ format_date($log->date) }}</td>

        @if($log->payment_type_id == App\PaymentType::PER_DIEM)
        <td>{{ "{$log->action->actionType->name}: {$log->action->name}" }}</td>
        @else
        <td>{{ "{$log->action->name}" }}  </td>
        @endif
       

        <td>{{ formatNumber($log->duration) }} Hour(s)</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">This physician has no recent activity.</div>
</div>
@endif