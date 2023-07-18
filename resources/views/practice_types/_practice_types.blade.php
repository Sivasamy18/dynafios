@if (count($items) > 0)
<table class="table table-striped table-hover practice-types-table">
    <thead>
    <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
    <th class="text-right" width="100">Actions</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $practiceType)
    <tr>
        <td>
            <a href="{{ URL::route('practice_types.edit', $practiceType->id) }}">
                {{ $practiceType->name }}
            </a>
        </td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('practice_types.delete', $practiceType->id) }}">
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
    <div class="panel-body">There are currently no practice types available for display.</div>
</div>
@endif