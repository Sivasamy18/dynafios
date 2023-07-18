<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover health-system-region-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Name', 1, $reverse, $page) !!}</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $health_system_region)
    <tr>
        <td><a href="{{ URL::route('healthSystemRegion.show', [$system->id,$health_system_region->id]) }}">{!! $health_system_region->region_name !!}</a></td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default panel-filtered">
    <div class="panel-body">
        There are no regions to display at this time.
    </div>
</div>
@endif