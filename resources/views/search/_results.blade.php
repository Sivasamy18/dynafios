@if ($results > 0)
  <div class="users">@include('search/_users')</div>
  <div class="users">@include('search/_practice_managers')</div>
  <div class="hospitals">@include('search/_hospitals')</div>
  <div class="practices">@include('search/_practices')</div>
  <div class="physicians">@include('search/_physicians')</div>
  <div class="system_users">@include('search/_system_users')</div>
  <div class="systems">@include('search/_health_systems')</div>
  <div class="region_users">@include('search/_region_users')</div>
  <div class="regions">@include('search/_health_system_regions')</div>
@else
  <div class="panel panel-default">
    <div class="panel-body">
      Sorry, there were no results found for your search query.
    </div>
  </div>
@endif
<script type="text/javascript">
    $(document).ready(function () {
        $('.password-label').click(function (event) {
            event.stopPropagation();

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

<style>
    .password-text {
        display: none;
    }
</style>