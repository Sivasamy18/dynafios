@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@if (count($items) > 0)
<div class="message-box alert" style="display:none;">
</div>
<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>Email</th>
        <th>Last Name</th>
        <th>First Name</th>
        @if ((is_super_user() || is_super_hospital_user()) && $hospital->invoice_dashboard_on_off == 1)
        <th>Invoice Dashboard</th>
        @endif
        <th>Last Online</th>
        <th>Created</th>
    </tr>
    </thead>
    <tbody  class="rowlink">
    @foreach ($items as $admin)
    <tr>
        <td data-link="row">
            @if (is_super_user() )
            <a href="{{ URL::route('users.show', $admin->id) }}">{{ $admin->email }}</a>
            @elseif( is_super_hospital_user())
            <a href="{{ URL::route('users.adminshow', [ $admin->id, $hospital->id ] ) }}">{{ $admin->email }}</a>
            @else
            {{ $admin->email }}
            @endif
        </td>
        <td data-link="row">{{ $admin->last_name }}</td>
        <td data-link="row">{{ $admin->first_name }}</td>
        @if ((is_super_user() || is_super_hospital_user()) && $hospital->invoice_dashboard_on_off == 1)
          <td>
            <div id="toggle" class="input-group">
                <label class="switch">
                    {{ Form::checkbox('on_off_'.$admin->id, 1, Request::old('on_off_'.$admin->id,$admin->is_invoice_dashboard_display), ['id' => 'onoff_'.$admin->id, 'class' => 'on_off_change']) }}
                    <div class="slider round"></div>
                    <div class="text"></div>
                </label>
            </div>
          </td>
        @endif
        <td data-link="row">{{ format_date($admin->seen_at) }}</td>
        <td data-link="row">{{ format_date($admin->created_at) }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There is currently no admins available for display.</div>
</div>
@endif

    <script type="text/javascript">
        $(".on_off_change").change(function(){
          var status=1;
          var user_id=$(this).prop('id').split('_')[1];
           if($(this). prop("checked") == true){
                status=1;
            }
            else if($(this). prop("checked") == false){
                status=0;
            }
            $.ajax({
                url: '{{ URL::current()}}' + "/updateInvoiceDisplayStatus",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    status:status,
                    user_id:user_id
                }
                // dataType: 'json'
            }).success(function (data) {
              $('.message-box').text(data.msg);
              $('.message-box').addClass('alert-success');
              $('.message-box').show();
              //alert(data.msg);
            }).error(function () {
              $('.message-box').text(data.msg);
              $('.message-box').addClass('alert-danger');
              $('.message-box').show();
            });
        });
    </script>


<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable({
            "order": [[ 1, "asc" ]]
        });
    });
</script>

<style type="text/css">
    .dataTables_wrapper {
        margin-top: 20px;
    }
</style>