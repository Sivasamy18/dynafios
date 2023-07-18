<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Hospital Name', 1, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Agreement Name', 2, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Contract Name', 3, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Physician Name', 4, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Start Date', 5, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('End Date', 6, $reverse, $page) !!}</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $contract)
        <tr>
            <td>{{ $contract->hospital_name }}</td>
            <td>{{ $contract->agreement_name }}</td>
            <td>{{ $contract->contract_name }}</td>
            <td>{{ $contract->last_name }}, {{ $contract->first_name }}</td>
            <td>{{ format_date($contract->start_date) }}</td>
            <td>{{ format_date($contract->manual_contract_end_date) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>