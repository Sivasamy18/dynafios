<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover health-system-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Name', 1, $reverse, $page, $filter) !!}</th>
        <th style="text-align:center;"># Facilities</th>
        <th style="text-align:center;"># Users</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $health_system)
    <tr>
        <td><a href="{{ URL::route('healthSystem.show', $health_system->id) }}">{{ $health_system->health_system_name }}</a></td>
        @php($foundFacility=false)
        @if(isset($facility_count))
        @foreach ($facility_count as $facility)
            @if($facility['id'] == $health_system->id)
                @php($foundFacility=true)

                <td style="text-align:center;">
                    {{ $facility['count']>0 ? $facility['count']:'0' }}  
                </td>
                <td style="text-align:center;">
                    {{ $facility['total_users']>0 ? $facility['total_users']:'0' }}  
                </td>
            @endif
        @endforeach
        @else
            <td style="text-align:center;"> Please Refresh .. Cache Rebuilding...</td>
            <td style="text-align:center;"> Please Refresh .. Cache Rebuilding...</td>
        @endif

        @if(!$foundFacility)
            <td style="text-align:center;">0</td>
            <td style="text-align:center;">0</td>
        @endif
    
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default panel-filtered">
    <div class="panel-body">
        There are no health systems to display at this time.
    </div>
</div>
@endif

<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable();
    });
</script>