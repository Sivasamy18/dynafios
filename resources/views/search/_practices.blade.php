@if (count($practices) > 0)
<h4>Practices</h4>
<table class="table table-striped table-hover practices-table">
    <thead>
    <th>NPI</th>
    <th>Name</th>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($practices as $practice)
    <tr>
        <td>
            <a href="{{ URL::route('practices.show', $practice->id) }}">{{ $practice->npi }}</a>
        </td>
        <td>{{ $practice->name }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif