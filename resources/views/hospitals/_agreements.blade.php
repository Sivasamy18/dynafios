<div class="filters">
    <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">Active</a>
    @if (Route::currentRouteName() != "agreements.payment")
        <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Archived</a>
        <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">All</a>
    @endif
</div>
<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>Agreement</th>
        <th style="width: 160px">Start Date</th>
        <th style="width: 160px">End Date</th>
        <th style="width: 160px">Created</th>
        @if ($filter == 1)
            <th style="width: 160px">Archived Date</th>
        @endif
        <th style="width: 100px">Physicians</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $agreement)
    <tr>
        <td>
@if (Route::currentRouteName() == "agreements.payment")
    	     <a href="{{ route('payments.show', $agreement->id) }}">
                {{ $agreement->name }}
            </a>
@else
	    <a href="{{ route('agreements.show', $agreement->id) }}">
                {{ $agreement->name }}
            </a>
@endif
        </td>
        <td>{{ format_date($agreement->start_date) }}</td>
        <td>{{ format_date($agreement->end_date) }}</td>
        <td>{{ format_date($agreement->created_at) }}</td>
        @if ($filter == 1)
            <td>{{ format_date($agreement->updated_at) }}</td>
        @endif
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

<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable({
            "order": [[ 0, "asc" ]]
        });
    });
</script>

<style type="text/css">
    .dataTables_wrapper {
        margin-top: 20px;
    }
</style>