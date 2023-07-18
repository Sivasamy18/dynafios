<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover health-system-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Name', 1, $reverse, $page, $filter) !!}</th>
        <th style="text-align:center;">Label</th>
        <th style="text-align:center;">Identity Provider</th>
        <th style="text-align:center;">Domains</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $sso_client)
    <tr>
        <td><a href="{{ URL::route('sso_clients.show', $sso_client->id) }}">{{ $sso_client->client_name }}</a></td>
        <td style="text-align:center;">{{ $sso_client->label }}</td>
        <td style="text-align:center;">
                {{ $sso_client->identity_provider }}  
        </td>
        @php($domain_list='')
        @foreach ($sso_client->domain as $domain)
            @if($domain_list==='')
                @php($domain_list=$domain->name)
            @else
                @php($domain_list=$domain->name.', '.$domain_list)
            @endif
        @endforeach
        <td style="text-align:center;">
            {{ $domain_list }}  
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default panel-filtered">
    <div class="panel-body">
        There are no SSO clietns to display at this time.
    </div>
</div>
@endif

<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable();
    });
</script>