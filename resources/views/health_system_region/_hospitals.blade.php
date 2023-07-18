@if (count($items) > 0)
    <table class="table table-striped table-hover hospitals-table">
        <thead>
        <tr>
            <th>{!! HTML::sort_link('NPI', 1, $reverse, $page) !!}</th>
            <th>{!! HTML::sort_link('Name', 2, $reverse, $page) !!}</th>
            <th>{!! HTML::sort_link('State', 3, $reverse, $page) !!}</th>
            <th>{!! HTML::sort_link('Expiration', 4, $reverse, $page) !!}</th>
            <th>{!! HTML::sort_link('Created', 5, $reverse, $page) !!}</th>
        </tr>
        </thead>
        <tbody data-link="row" class="rowlink">
        @foreach ($items as $hospital)
            <tr>
                <td><a class="btn-disassociate" href="{{ URL::route('healthSystemRegion.disassociate_hospital', [$system->id, $region->id,$hospital->id]) }}">{{ $hospital->npi }}</a></td>
                <td>{{ $hospital->name }}</td>
                <td>{{ $hospital->state->name }}</td>
                <td>{{ format_date($hospital->expiration) }}</td>
                <td>{{ format_date($hospital->created_at) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@else
    <div class="panel panel-default panel-filtered">
        <div class="panel-body">
            There are no hospitals to display at this time.
        </div>
    </div>
@endif