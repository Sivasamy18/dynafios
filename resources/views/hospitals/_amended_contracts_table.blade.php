<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>Hospital Name</th>
        <th>Agreement Name</th>
        <th>Contract Name</th>
        <th>Physician Name</th>
        <th>Amended</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $contract)
        <tr>
            <td>{{ $contract->Hospitals__name }}</td>
            <td>{{ $contract->{'Agreements__name'} }}</td>
            <td>{{ $contract->{'Contract Names__name'} }}</td>
            <td>{{ $contract->{'Physicians__first_name'} }}, {{ $contract->{'Physicians__last_name'} }}</td>
            <td>{{ format_date($contract->updated_at) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>