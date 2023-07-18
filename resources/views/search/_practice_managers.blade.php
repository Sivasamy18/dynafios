@php use function App\Start\is_super_user; @endphp
@if (count($practice_managers) > 0)
  <h4>Practice Managers</h4>
  <table class="table table-striped table-hover hospitals-table">
    <thead>
    <th>Email</th>
    <th>First Name</th>
    <th>Last Name</th>
    <th>Phone</th>
    @if (is_super_user())
      <th>Practice</th>
      <th>Password</th>
    @endif
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($practice_managers as $practice_manager)
      <tr>
        <td>
          <a href="{{ URL::route('users.show', $practice_manager->id) }}">{{ $practice_manager->email }}</a>
        </td>
        <td>{{ $practice_manager->last_name }}</td>
        <td>{{ $practice_manager->first_name }}</td>
        <td>{{ $practice_manager->phone }}</td>
        @if (is_super_user())
          <td>{{ $practice_manager->name }}</td>
          <td>
            <div id="password-text-user-{{$practice_manager->id}}" class="password-text">
              {{ $practice_manager->password_text }}
            </div>
            <button class="btn btn-link password-label" data-user-id="{{$practice_manager->id}}">
              Copy to Clipboard
            </button>
          </td>
        @endif
      </tr>
    @endforeach
    </tbody>
  </table>
@endif
