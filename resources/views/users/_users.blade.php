<style>
    .filters a {
        padding: 6px 16px;
    }
</style>
<div class="filters">
  <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">All</a>
  <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Super Users</a>
  <a class="{{ HTML::active($filter == 5) }}" href="{{ URL::current().'?filter=5' }}">Super Hospital Users</a>
  <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">Hospital Users</a>
  <a class="{{ HTML::active($filter == 3) }}" href="{{ URL::current().'?filter=3' }}">Practice Managers</a>
  <a class="{{ HTML::active($filter == 7) }}" href="{{ URL::current().'?filter=7' }}">System Users</a>
  <a class="{{ HTML::active($filter == 8) }}" href="{{ URL::current().'?filter=8' }}">Region Users</a>
</div>
<div class="clearfix"></div>
@if (count($items) > 0)
  <table class="table table-striped table-hover users-table">
    <thead>
    <tr>
      <th>{!! HTML::sort_link('Email', 2, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Last Name', 3, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('First Name', 4, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Group', 5, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Password', 6, $reverse, $page, $filter) !!}</th>
      <th>{!! HTML::sort_link('Created', 7, $reverse, $page, $filter) !!}</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $user)
      <tr>
        <td>
          <a href="{{ URL::route('users.show', $user->id) }}">
            {{ $user->email }}
          </a>
        </td>
        <td>{{ $user->last_name }}</td>
        <td>{{ $user->first_name }}</td>
        <td>{{ $user->group->name }}</td>
        <td>
          <div id="password-text-user-{{$user->id}}" class="password-text">
            {{ $user->password_text }}
          </div>
          <button class="btn btn-link password-label" data-user-id="{{$user->id}}">
            Copy to Clipboard
          </button>
        </td>
        <td>{{ format_date($user->created_at) }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>

@else
  <div class="panel panel-default">
    <div class="panel-body">There are currently no users available for display.</div>
  </div>
@endif

<script type="text/javascript">
    $(document).ready(function () {
        $('.table').DataTable({
            "order": [[0, "asc"]]
        });
    });

    $(document).ready(function () {
        $('.password-label').click(function (event) {
            event.stopPropagation(); //Stop the click event from bubbling up to the row and going into the user's profile

            var userId = $(this).data('user-id');
            var passwordTextId = 'password-text-user-' + userId;

            // get the password text
            var passwordText = $('#' + passwordTextId).text().trim();

            // create a temporary input element to copy the password text to clipboard
            var tempInput = $('<input>');
            $('body').append(tempInput);
            tempInput.val(passwordText).select();

            // copy the password text to clipboard
            document.execCommand('copy');

            // remove the temporary input element
            tempInput.remove();

            // update the button text to indicate that the password has been copied
            $(this).text('Copied!').addClass('btn-success');
            var self = this;
            setTimeout(function () {
                $(self).text('Copy to Clipboard').removeClass('btn-success');
            }, 2000);
        }).addClass('btn').addClass('btn-link');
    });
</script>

<style>
    .password-text {
        display: none;
    }
</style>
