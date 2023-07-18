@if (count($items) > 0)
<table class="table table-striped table-hover contract-types-table">
    <thead>
    <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
    <th class="text-right" width="100">Actions</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $contractType)
    <tr>
        <td>
            <a href="{{ URL::route('contract_types.edit', $contractType->id) }}">
                {{ $contractType->name }}
            </a>
        </td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('contract_types.delete', $contractType->id) }}">
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
    <div class="panel-body">There are currently no contract types available for display.</div>
</div>
@endif