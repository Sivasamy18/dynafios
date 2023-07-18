@if($type == 0)
    <div class="filters">
        <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">All</a>
        <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Active</a>
        <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">Archived</a>
    </div>
@else
    <div class="filters">
        <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0&type='.$type }}">All</a>
        <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1&type='.$type }}">Active</a>
        <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2&type='.$type }}">Archived</a>
    </div>
@endif
<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover hospitals-table">
    <thead>
    <tr>
        <th>NPI</th>
        <th>Name</th>
        <th>State</th>
        <th>Expiration</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $hospital)
    <tr>
        @if($type == 0)
            <td><a href="{{ URL::route('hospitals.show', $hospital->id) }}">{{ $hospital->npi }}</a></td>
        @elseif($type == 1)
            <td><a href="{{ URL::route('hospitals.reports', $hospital->id) }}">{{ $hospital->npi }}</a></td>
        @elseif($type == 2)
            <td><a href="{{ URL::route('agreements.payment', $hospital->id) }}">{{ $hospital->npi }}</a></td>
        @elseif($type == 3)
            <td><a href="{{ env('PRODUCTIVITY_URL') }}">{{ $hospital->npi }}</a></td>
        @endif
        <td>{{ $hospital->name }}</td>
        <td>{{ $hospital->state->name }}</td>
        <td>{{ format_date($hospital->expiration) }}</td>
        <td>{{ format_date($hospital->created_at) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default panel-filtered">
    <div class="panel-body">
        There are no hospitals to display at this time.
    </div>
</div>
@endif

<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable({
            "order": [[ 1, "asc" ]]
        });
    });
</script>

<style type="text/css">
    .dataTables_wrapper {
        margin-top: 20px;
    }
</style>