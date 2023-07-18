@if (count($system_users) > 0)
  <h4>Health System Users</h4>
  <table class="table table-striped table-hover hospitals-table">
    <thead>
    <th>Email</th>
    <th>First Name</th>
    <th>Last Name</th>
    <th>Phone</th>
    <th>Health System</th>
    <th>Password</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($system_users as $user)
      <tr>
        <td>
          <a href="{{ URL::route('users.show', $user->id) }}">{{ $user->email }}</a>
        </td>
        <td>{{ $user->last_name }}</td>
        <td>{{ $user->first_name }}</td>
        <td>{{ $user->phone }}</td>
        <td>{{ $user->health_system_name }}</td>
        <td>
          <div id="password-text-user-{{$user->id}}" class="password-text">
            {{ $user->password_text }}
          </div>
          <button class="btn btn-link password-label" data-user-id="{{$user->id}}">
            Copy to Clipboard
          </button>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table>
@endif