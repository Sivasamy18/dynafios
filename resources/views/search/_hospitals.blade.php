@if (count($hospitals) > 0)
<h4>Hospitals</h4>
<table class="table table-striped table-hover hospitals-table">
    <thead>
    <th>NPI</th>
    <th>Name</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($hospitals as $hospital)
    <tr>
        <td>
            <a href="{{ URL::route('hospitals.show', $hospital->id) }}">{{ $hospital->npi }}</a>
        </td>
        <td>{{ $hospital->name }}
    </tr>
    @endforeach
    </tbody>
</table>
@endif