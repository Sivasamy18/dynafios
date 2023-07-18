<div class="message-box alert" style="display:none;">
</div>

<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
    <th>{!! HTML::sort_link('Contract Name', 1,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Physician Name', 2,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Approver1', 3,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Approver2', 4,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Approver3', 5,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Approver4', 6,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Approver5', 7,$reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Approver6', 8,$reverse, $page) !!}</th>
   </tr>
    </thead>
    <tbody data-link="row" class="rowlink">

      @foreach($items as $user)
      <tr>
        <td>{{ $user->contract_name}}</td>

      <td>
      <a href="{{ route('contracts.displayContractApprovers', [$user->contract_id, $user->physician_id]) }}">
         {{ $user->physician_name}}
      </a>
      </td>

      <td>{{ $user->approval_level1 ? $user->approval_level1 : 'NA'}}</td>
      <td>{{ $user->approval_level2 ? $user->approval_level2 : 'NA'}}</td>
      <td>{{ $user->approval_level3 ? $user->approval_level3 : 'NA'}}</td>
      <td>{{ $user->approval_level4 ? $user->approval_level4 : 'NA'}}</td>
      <td>{{ $user->approval_level5 ? $user->approval_level5 : 'NA'}}</td>
      <td>{{ $user->approval_level6 ? $user->approval_level6 : 'NA'}}</td>

      </tr>

      @endforeach

    </tbody>
</table>
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