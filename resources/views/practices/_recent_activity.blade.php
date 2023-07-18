@if (count($recentLogs) > 0)
<table class="table table-striped table-hover actions-table recent-activity-table">
    <thead>
    <tr>
        <th>Date</th>
        <th>Physician</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($recentLogs as $log)
    <tr>
        <td>{{ format_date($log->date) }}</td>
        <td>{{ "{$log->physician->last_name}, {$log->physician->first_name}" }}</td>
        <td>Submitted a log</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There is currently no recent activity available for display.</div>
</div>
@endif