@if (count($items) > 0)
  <table class="table table-striped table-hover physicians-table">
    <thead>
    <tr>
      <th>{!! HTML::sort_link('Email', 1, $reverse, $page) !!}</th>
      <th>{!! HTML::sort_link('Last Name', 2, $reverse, $page) !!}</th>
      <th>{!! HTML::sort_link('First Name', 3, $reverse, $page) !!}</th>
      <th>{!! HTML::sort_link('Password', 4, $reverse, $page) !!}</th>
      <th>{!! HTML::sort_link('Practice', 5, $reverse, $page) !!}</th>
      <th>{!! HTML::sort_link('Hospital', 6, $reverse, $page) !!}</th>
      <th>{!! HTML::sort_link('Created', 7, $reverse, $page) !!}</th>
      <th class="text-right" width="100">Actions</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $physician)
      <tr>
        <td><a
              href="{{ URL::route('physicians.restore', [$physician->id, $physician->practice_id]) }}">{{ $physician->email }}</a>
        </td>
        <td>{{ $physician->last_name }}</td>
        <td>{{ $physician->first_name }}</td>
        <td>{{ $physician->practice_name }}</td>
        <td>{{ $physician->hospital_name }}</td>
        <td>{{ format_date($physician->created_at) }}</td>
        <td class="text-right rowlink-skip">
          <div class="btn-group btn-group-xs">
            <a class="btn btn-default"
               href="{{ URL::route('physicians.restore', [$physician->id, $physician->practice_id]) }}">
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
    <div class="panel-body">There are currently no physicians available for display.</div>
  </div>
@endif