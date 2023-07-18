@php use function App\Start\is_super_user; @endphp
@if (count($items) > 0)
<table class="table table-striped table-hover practice-managers-table">
    <thead>
    <tr>
        <th>NPI</th>
        @if (is_super_user())
            <th>Email</th>
        @endif
        <th>Last Name</th>
        <th>First Name</th>
        <th>Last Online</th>
        <th width="125">Created</th>
        @if (is_super_user())
            <th class="text-right" width="100">Actions</th>
        @endif
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $physician)
    <tr>
        <td><a href="{{ URL::route('physicians.show', [$physician->id,$practice->id]) }}">{{ $physician->npi }}</a>
        @if (is_super_user())
            <td>
                {{ $physician->email }}
            </td>
        @endif
        <td>{{ $physician->last_name }}</td>
        <td>{{ $physician->first_name }}</td>
        <td>{{ format_date($physician->seen_at) }}</td>
        <td>{{ format_date($physician->created_at) }}</td>
        @if (is_super_user())
            <td class="text-right rowlink-skip">
                <div class="btn-group btn-group-xs">
<!-- physician to all hospital by 1254 -->
                    <a class="btn btn-default btn-delete"
                       href="{{ URL::route('physicians.delete', [$physician->id,$practice->id]) }}">
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
    <div class="panel-body">There is currently no physicians available for display.</div>
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