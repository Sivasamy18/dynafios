<table class="table table-striped table-hover hospital-admins-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Physician Name', 1, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Agreement Name', 2, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Contract Name', 3, $reverse, $page) !!}</th>
        <th>{!! HTML::sort_link('Lawson Interface Information', 4, $reverse, $page) !!}</th>
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $contract)
        <tr>
            <td>{{ $contract->last_name }}, {{ $contract->first_name }}</td>
            <td>{{ $contract->agreement_name }}</td>
            <td>{{ $contract->contract_name }}</td>
            <td>
            <div class="contractInfo"><img class="img-responsive" src="../assets/img/default/contractInfoIcon.png" alt="">
                          <div class="showContractInfo">

                            <div class="panel panel-default">
                              <div class="panel-heading">Lawson Interface Information</div>
                              <div class="panel-body">
                                <table class="table" style="font-size: 12px;">
                                    Invoice Company: {{$contract->invoice_company}}</br>
                                    Vendor Number: {{$contract->invoice_vendor}}</br>
                                    Process Level: {{$contract->invoice_process_level}}</br>
                                    Distribution Company: {{$contract->distrib_company}}</br>
                                    Accounting Unit: {{$contract->distrib_accounting_unit}}</br>
                                    Account: {{$contract->distrib_account}}</br>
                                    Subaccount: {{$contract->distrib_sub_account}}
                                </table>
                              </div>
                            </div>
                          </div>
                        </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
