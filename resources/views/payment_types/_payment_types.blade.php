@if (count($items) > 0)
<table class="table table-striped table-hover payment-types-table">
    <thead>
    <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
    <th class="text-right" width="100">Actions</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $paymentType)
    <tr>
        <td>
            <a href="{{ URL::route('payment_types.edit', $paymentType->id) }}">
                {{ $paymentType->name }}
            </a>
        </td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('payment_types.delete', $paymentType->id) }}">
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
    <div class="panel-body">There are currently no payment types available for display.</div>
</div>
@endif