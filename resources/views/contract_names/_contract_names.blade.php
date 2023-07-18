@if (count($items) > 0)
<table class="table table-striped table-hover contract-names-table">
    <thead>
    <th>Name</th>
    <th>Payment Type</th>
    <th class="text-right" width="100">Actions</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $contractName)
    <tr>
        <td>
            <a href="{{ URL::route('contract_names.edit', $contractName->id) }}">
                {{ $contractName->name }}
            </a>
        </td>
        <td>{{ $contractName->paymentType->name }}</td>
        <td class="text-right rowlink-skip">
            <div class="btn-group btn-group-xs">
                <a class="btn btn-default btn-delete"
                   href="{{ URL::route('contract_names.delete', $contractName->id) }}">
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
    <div class="panel-body">There are currently no contract names available for display.</div>
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