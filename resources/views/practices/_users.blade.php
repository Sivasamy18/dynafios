@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@if (count($items) > 0)
<table class="table table-striped table-hover practice-managers-table">
    <thead>
    <tr>
        <th>Email</th>
        <th>Last Name</th>
        <th>First Name</th>
        <th>Last Online</th>
        <th width="125">Created</th>
        @if (is_super_user() || is_super_hospital_user())
            <th class="text-right" width="100">Actions</th>
        @endif
    </tr>
    </thead>
    <tbody >
    @foreach ($items as $manager)
      @if(is_super_user())
        <tr data-link="row" class="rowlink">
      @else
        <tr>
      @endif
        <td>
            @if (is_super_user())
                <a href="{{ URL::route('users.show', $manager->id) }}">{{ $manager->email }}</a>
            @else
                {{ $manager->email }}
            @endif
        </td>
        <td>{{ $manager->last_name }}</td>
        <td>{{ $manager->first_name }}</td>
        <td>{{ format_date($manager->seen_at) }}</td>
        <td>{{ format_date($manager->created_at) }}</td>
        @if (is_super_user() || is_super_hospital_user())
            <td class="text-right rowlink-skip">
                <div class="btn-group btn-group-xs">
                    <a class="btn btn-default btn-delete"
                       href="{{ URL::route('practices.delete_manager', [ $practice->id, $manager->id ]) }}">
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
    <div class="panel-body">There is currently no managers available for display.</div>
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