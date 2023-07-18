@if (count($items) > 0)
  <table class="table table-striped table-hover physicians-table">
    <thead>
    <tr>
      <th>Email</th>
      <th>Last Name</th>
      <th>First Name</th>
      <th>Password</th>
      <th>Created</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $physician)
      <tr>
        <td><a href="{{ URL::route('physicians.show', [$physician->id,$physician->practice_id]) }}" class="row-link">{{ $physician->email }}</a></td>
        <td>{{ $physician->last_name }}</td>
        <td>{{ $physician->first_name }}</td>
        <td>
          <div id="password-text-user-{{$physician->id}}" class="password-text">
            {{ $physician->password_text }}
          </div>
          <button class="btn btn-link password-label" data-user-id="{{$physician->id}}">
            Copy to Clipboard
          </button>
        </td>
        <td>{{ format_date($physician->created_at) }}</td>
      </tr>
    @endforeach

    </tbody>
  </table>

@else
  <div class="panel panel-default">
    <div class="panel-body">There are currently no physicians available for display.</div>
  </div>
@endif

<script type="text/javascript">
    $(document).ready(function () {
        $('.table').DataTable({
            "order": [[0, "asc"]]
        });

        // Initialize rowlink functionality
        $('tbody.rowlink').rowlink({target: 'a.row-link'});

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
