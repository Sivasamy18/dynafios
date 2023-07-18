@if (count($items) > 0)
<table class="table table-striped table-hover hospital-reports-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Filename', 1, $reverse, $page) !!}</th>
        <th width="125">{!! HTML::sort_link('Created', 2, $reverse, $page) !!}</th>
        <th class="text-right" width="100">Actions</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $report)
    <tr>
        <td>
            <a href="{{ URL::route('physician.report', [ $physician->id, $report->id]) }}">
                <i class="fa fa-cloud-download"></i> {{ $report->filename }}
            </a>
        </td>
        <td>{{ format_date($report->created_at) }}</td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('physician.delete_report', [ $physician->id, $report->id ]) }}">
                    <i class="fa fa-trash-o fa-fw"></i>
                </a>
            </div>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">
        There are no payment status reports to display at this time.
    </div>
</div>
@endif