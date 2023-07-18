<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>NPI</th>
        <th>Name</th>
        <th>Practice Type</th>
        <th>Created</th>
        <th>Agreements</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $practice)
        <tr>
            <td>
                <a href="{{ URL::route('practices.show', $practice->id) }}">{!! $practice->npi !!}</a>
            </td>

            <td> {!! $practice->practice_name !!} </td>
            <td> {!! $practice->practice_type !!} </td>
            <td> {!! format_date($practice->created_at) !!} </td>
            <?php $agreementFound=0;?>
            @foreach ($agreements as $agreement)
                @if($practice->id==$agreement->id)
                    <td> {!! $agreement->name !!} </td>
                    <?php $agreementFound++;?>
                    @break;
                @endif
            @endforeach
            <?php if($agreementFound == 0) echo "<td>-</td>";?>
        </tr>
    @endforeach
    </tbody>
</table>

<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable({
            "order": [[ 1, "asc" ]]
        });
    });
</script>

<style type="text/css">
    .dataTables_wrapper {
        margin-top: 20px;
    }
</style>