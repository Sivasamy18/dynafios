@extends('layouts/_practice', [ 'tab' => 6 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
    {{ Form::hidden('id', $practice->id) }}
    <div class="panel panel-default">
        <!--<div class="panel-heading ">
            <span >Agreement</span>
            <span >Start Date</span>
            <span >End Date</span>
        </div>-->
        <div class="panel-body">
            <table class="table table-striped table-hover hospital-admins-table">
                <thead>
                <tr>
                    <th>Contract</th>
                    <th style="width: 160px">Physician(s)</th>
                    <th style="width: 160px">Start Date</th>
                    <th style="width: 160px">End Date</th>
                </tr>
                </thead>
                <tbody data-link="row" class="rowlink">

                @foreach($contractArray as $contract)
                    <tr>
                        <td>
                            <a
                                    

                                        href="{{ URL::route('practices.contracts_show', [$practice->id , $contract['contract_id']]) }}"
                            >
                                {{$contract['contract_name']}}
                            </a>
                        </td>
                        <td>{{$contract['physican_id']}}</td>
                        <td>{{$contract['agreement_start_date']}}
                        </td>
                        <td>{{$contract['agreement_end_date']}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>

    </div>
    {{ Form::close() }}
@endsection


<script type="text/javascript">
    $(document).ready(function() {
        $('.table').DataTable({
            "order": [[ 0, "asc" ]]
        });
    });
</script>

<style type="text/css">
    .dataTables_wrapper {
        margin-top: 20px;
    }
</style>