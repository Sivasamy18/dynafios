@php use function App\Start\is_super_user; @endphp
@if (count($users) > 0)
  <h4>Users</h4>
  <table class="table table-striped table-hover hospitals-table">
    <thead>
    <th>Email</th>
    <th>First Name</th>
    <th>Last Name</th>
    <th>Phone</th>
    @if (is_super_user())
      <th>Hospital</th>
      <th>Password</th>
    @endif
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($users as $user)
        <td>
          <a href="{{ URL::route('users.show', $user->id) }}">{{ $user->email }}</a>
        </td>
        <td>{{ $user->last_name }}</td>
        <td>{{ $user->first_name }}</td>
        <td>{{ $user->phone }}</td>
        @if (is_super_user())
          <td>{{ $user->name }}</td>
          <td>
            <div id="password-text-user-{{$user->id}}" class="password-text">
              {{ $user->password_text }}
            </div>
            <button class="btn btn-link password-label" style="z-index: 100" data-user-id="{{$user->id}}">
              Copy to Clipboard
            </button>
          </td>
        @endif
      </tr>
      </tr>
    @endforeach
    </tbody>
  </table>

@endif