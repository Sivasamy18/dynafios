@php use function App\Start\is_super_user; @endphp
@if (count($physicians) > 0)
  <h4>Physicians</h4>
  <table class="table table-striped table-hover physicians-table">
    <thead>
    <th>NPI</th>
    <th>Email</th>
    <th>Last Name</th>
    <th>First Name</th>
    <th>Phone</th>
    <th>Practice</th>
    @if (is_super_user())
      <th>Hospital</th>
      <th>Password</th>
    @endif
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($physicians as $physician)
      <tr>
        <td>
          <!-- physician to multiple hospital by 1254 -->
          <a href="{{ URL::route('physicians.show', [$physician->id,$physician->practice_id]) }}">{{ $physician->npi }}</a>
        </td>
        <td>{{ $physician->email }}</td>
        <td>{{ $physician->last_name }}</td>
        <td>{{ $physician->first_name }}</td>
        <td>{{ $physician->phone }}</td>
        <td>{{ $physician->practice_name }}</td>
        @if (is_super_user())
          <td>{{ $physician->name }}</td>
          <td>
            <div id="password-text-user-{{$physician->id}}" class="password-text">
              {{ $physician->password_text }}
            </div>
            <button class="btn btn-link password-label" data-user-id="{{$physician->id}}">
              Copy to Clipboard
            </button>
          </td>
        @endif
      </tr>
    @endforeach
    </tbody>
  </table>
@endif

