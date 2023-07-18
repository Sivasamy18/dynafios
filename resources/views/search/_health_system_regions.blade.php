@if (count($regions) > 0)
<h4>Health System Regions</h4>
<table class="table table-striped table-hover hospitals-table">
    <thead>
    <th>Name</th>
    <th>Health System</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($regions as $region)
    <tr>
        <td>
            <a href="{{ URL::route('healthSystemRegion.show', [$region->health_system_id,$region->id]) }}">{{ $region->region_name }}</a>
        </td>
        <td>{{$region->health_system_name}}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif