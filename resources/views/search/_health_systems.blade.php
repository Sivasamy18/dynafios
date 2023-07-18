@if (count($systems) > 0)
<h4>Health Systems</h4>
<table class="table table-striped table-hover hospitals-table">
    <thead>
    <th>Name</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($systems as $system)
    <tr>
        <td>
            <a href="{{ URL::route('healthSystem.show', $system->id) }}">{{ $system->health_system_name }}</a>
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif