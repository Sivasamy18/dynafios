@if (count($items) > 0)
<table class="table table-striped table-hover hospital-invoices-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Filename', 1, $reverse, $page) !!}</th>
        <th width="125">{!! HTML::sort_link('Created', 2, $reverse, $page) !!}</th>
        <th class="text-right" width="100">Actions</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $invoice)
    <tr>
        <td>
            <a href="{{ URL::route('hospitals.invoice', [ $hospital->id, $invoice->id]) }}">
                <i class="fa fa-cloud-download"></i> {{ $invoice->filename }}
            </a>
        </td>
        <td>{{ format_date($invoice->created_at) }}</td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('hospitals.delete_invoice', [ $hospital->id, $invoice->id ]) }}">
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
        There are no invoices reports to display at this time.
    </div>
</div>
@endif