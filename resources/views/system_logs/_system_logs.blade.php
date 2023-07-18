@if (count($items) > 0)
<table class="table table-striped table-hover actions-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
        <th width="500">{!! HTML::sort_link('URL', 2, $reverse, $page) !!}</th>
        <th width="120">{!! HTML::sort_link('Date', 3, $reverse, $page) !!}</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $system_log)
    <tr>
        <td><a href="{{ route('system_logs.show', $system_log->id) }}">{{ $system_log->user->email }}</a></td>
        <td>{{ $system_log->url }}</td>
        <td>{{ format_date($system_log->created_at) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are currently no actions available for display.</div>
</div>
@endif