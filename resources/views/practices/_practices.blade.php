@if (count($items) > 0)
<table class="table table-striped table-hover practices-table">
    <thead>
    <tr>
        <th>NPI</th>
        <th>Name</th>
        <th>State</th>
        <th>Physicians</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $practice)
    <tr>
        <td>
            <a href="{{ URL::route('practices.show', $practice->id) }}">
                {{ $practice->npi }}
            </a>
        </td>
        <td>{{ $practice->name }}</td>
        <td>{{ $practice->state->name }}</td>
        <!-- added to show no of practices for one to many features by 1254 -->
        <td>{{ $practice->physicianspractices()->where("start_date", "<=", now())
            ->where("end_date", ">=", now())->count() }}</td>
        <td>{{ format_date($practice->created_at) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are no practices to display at this time.</div>
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