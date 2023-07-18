<div class="filters">
    <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">Active</a>
    <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Archived</a>
    <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">All</a>
</div>
<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Agreement', 1, $reverse, $page) !!}</th>
        <th style="width: 160px">{!! HTML::sort_link('Start Date', 2, $reverse, $page) !!}</th>
        <th style="width: 160px">{!! HTML::sort_link('End Date', 3, $reverse, $page) !!}</th>
        <th style="width: 100px">Physicians</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $agreement)
    <tr>
        <td>
            <a href="{{ route('agreements.show', $agreement->id) }}">
                {{ $agreement->name }}
            </a>
        </td>
        <td>{{ format_date($agreement->start_date) }}</td>
        <td>{{ format_date($agreement->end_date) }}</td>
        <td>{{ $agreement->getPhysicianCount() }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default panel-filtered">
    <div class="panel-body">There is currently no agreements available for display.</div>
</div>
@endif