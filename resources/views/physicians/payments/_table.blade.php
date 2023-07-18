@php use function App\Start\is_super_user; @endphp

@if (count($items) > 0)
<table class="table table-striped table-hover contracts-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Agreement', 1, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Month', 2, $reverse, $page) !!}</th>        
        <th>{!! HTML::sort_link('Amount', 3, $reverse, $page) !!}</th>
        @if (is_super_user())
            <th class="text-right" width="100">Actions</th>
        @endif
    </tr>
    </thead>
    <tbody class="rowlink">
    @foreach ($items as $payment)
    <tr>
        <td>{{ $payment->agreement->name }}</td>
        <td>{{ $payment->agreement->getMonthString($payment->month) }}</td>
        <td>{{ formatCurrency($payment->amount) }}</td>
        @if (is_super_user())
            <td class="text-right rowlink-skip">
                <div class="btn-group btn-group-xs">
                    <a class="btn btn-default btn-delete" href="{{ route('physicians.delete_payment', [$physician->id, $payment->id]) }}">
                        <i class="fa fa-trash-o fa-fw"></i>
                    </a>
                </div>
            </td>
        @endif
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are currently no payments available for display.</div>
</div>
@endif