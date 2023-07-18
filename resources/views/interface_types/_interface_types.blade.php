@if (count($items) > 0)
<table class="table table-striped table-hover contract-types-table">
    <thead>
    <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $interfaceType)
    <tr>
        <td>
            {{ $interfaceType->name }}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are currently no interface types available for display.</div>
</div>
@endif