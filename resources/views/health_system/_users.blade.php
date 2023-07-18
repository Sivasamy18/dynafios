@php use function App\Start\is_super_user; @endphp
@if (count($items) > 0)
<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Email', 1, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Last Name', 2, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('First Name', 3, $reverse, $page) !!}</th>
        <th>{!!HTML::sort_link('Last Online', 4, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Created', 5, $reverse, $page) !!}</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $admin)
    <tr>
        <td>
            @if (is_super_user())
            <a href="{{ URL::route('users.show', $admin->id) }}">{{ $admin->email }}</a>
            @else
            {{ $admin->email }}
            @endif
        </td>
        <td>{{ $admin->last_name }}</td>
        <td>{{ $admin->first_name }}</td>
        <td>{{ format_date($admin->seen_at) }}</td>
        <td>{{ format_date($admin->created_at) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There is currently no users available for display.</div>
</div>
@endif