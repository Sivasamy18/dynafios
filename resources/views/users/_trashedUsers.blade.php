<div class="filters">
  <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">All</a>
  <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Super Users</a>
  <a class="{{ HTML::active($filter == 5) }}" href="{{ URL::current().'?filter=5' }}">Super Hospital Users</a>
  <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">Hospital Users</a>
  <a class="{{ HTML::active($filter == 3) }}" href="{{ URL::current().'?filter=3' }}">Practice Managers</a>
</div>
<div class="clearfix"></div>
@if (count($items) > 0)
  <table class="table table-striped table-hover users-table">
    <thead>
    <tr>
      <th>{!! HTML::sort_link('Email', 2, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Last Name', 3, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('First Name', 4, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Hospital Name', 5, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Group', 6, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Password', 7, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Created', 8, $reverse, $page, $filter) !!}</th>
      <th class="text-right" width="50">Actions</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $user)
      <tr>
        <td>
          <a href="{{ URL::route('users.restore', $user->id) }}">
            {{ $user->email }}
          </a>
        </td>
        <td>{{ $user->last_name }}</td>
        <td>{{ $user->first_name }}</td>
        <td>{{ $user->hospital_name }}</td>
        <td>{{ $user->group->name }}</td>
        <td>{{ format_date($user->created_at) }}</td>
        <td class="text-right rowlink-skip">
          <div class="btn-group btn-group-xs">
            <a class="btn btn-default" href="{{ URL::route('users.restore', $user->id) }}">
              <i class="fa fa-undo"></i>
            </a>
          </div>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>

@else
  <div class="panel panel-default">
    <div class="panel-body">There are currently no users available for display.</div>
  </div>
@endif
