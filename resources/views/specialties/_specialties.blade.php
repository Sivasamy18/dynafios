@if (count($items) > 0)
<table class="table table-striped table-hover practice-types-table">
    <thead>
    <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
    <th width="125">{!! HTML::sort_link('FMV Rate', 2, $reverse, $page) !!}</th>
    <th class="text-right" width="100">Actions</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $specialty)
    <tr>
        <td>
            <a href="{{ URL::route('specialties.edit', $specialty->id) }}">
                {{ $specialty->name }}
            </a>
        </td>
        <td>{{ formatCurrency($specialty->fmv_rate) }}</td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete" href="{{ URL::route('specialties.delete', $specialty->id) }}">
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
    <div class="panel-body">There are currently no specialties available for display.</div>
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