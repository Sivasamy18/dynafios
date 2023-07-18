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
                    <th>Agreement</th>
                    <th style="width: 160px">Start Date</th>
                    <th style="width: 160px">End Date</th>

                </tr>
                </thead>
                <tbody data-link="row" class="rowlink">
                @foreach ($agreements as $agreement)
                    <tr>
                        <td>
                            <a href="{{ route('practices.agreement_show', [$practice->id,$agreement->id]) }}">
                                  {{ $agreement->name}}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('practices.agreement_show', [$practice->id,$agreement->id]) }}">
                                   {{format_date($agreement->start_date, "m/d/Y")}}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('practices.agreement_show', [$practice->id,$agreement->id]) }}">
                                   {{format_date($agreement->end_date, "m/d/Y")}}
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

        </div>

    </div>
    {{ Form::close() }}
@endsection
