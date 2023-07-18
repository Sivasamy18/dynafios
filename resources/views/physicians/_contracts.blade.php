@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
<input type="hidden" name="practice_id" id="practice_id" value="{{$practice->id}}">
<input type="hidden" name="physician_id" id="physician_id" value="{{$physician->id}}">
<div class="filters">
    <a class="{{ HTML::active($filter == 0) }}" href="{{ URL::current().'?filter=0' }}">Active</a>
    <a class="{{ HTML::active($filter == 1) }}" href="{{ URL::current().'?filter=1' }}">Archived</a>
    <a class="{{ HTML::active($filter == 2) }}" href="{{ URL::current().'?filter=2' }}">All</a>
</div>
<div class="clearfix"></div>
@if (count($items) > 0)
<table class="table table-striped table-hover contracts-table">
    <thead>
    <tr>
        <th>{!! HTML::sort_link('Name', 1, $reverse, $page, $filter) !!}</th>
        <th>{!! HTML::sort_link('Start Date', 2, $reverse, $page, $filter) !!}</th>
        <th>{!! HTML::sort_link('End date', 3, $reverse, $page, $filter) !!}</th>
        <th>{!! HTML::sort_link('Rate', 4, $reverse, $page, $filter) !!}</th>
        <th width="125">{!! HTML::sort_link('Created', 5, $reverse, $page) !!}</th>
        @if (is_super_user() || is_super_hospital_user())
            <th class="text-right" width="100">Actions</th>
        @endif
    </tr>
    </thead>
    <tbody data-link="row" class="rowlink">
    @foreach ($items as $contract)
    @if($contract->agreement->is_deleted==0)
    <tr>
        <td>
            @if (is_super_user() || is_super_hospital_user())
           <!-- physician to multiple hosptial by 1254 -->
            <a href="{{ route('contracts.edit', [$contract->id,$practice->id,$physician->id]) }}">
                {{ contract_name($contract) }}
            </a>
			<p id="{{$contract->id}}" class="hidden">{{contract_name($contract)}}</p>
            @else
            @if($contract->payment_type_id == App\PaymentType::PSA)
                    <a href="{{ route('contracts_psa.edit', $contract->id) }}">
                        {{ contract_name($contract) }}
                    </a>
                @else
                    {{ contract_name($contract) }}
                @endif
            @endif
        </td>
        <td>{{ format_date($contract->agreement->start_date) }}</td>
        <td>{{ format_date($contract->manual_contract_end_date) }}</td>
        @if($contract->payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
        <td> </td>
        @else
        <td>{{ ($contract->payment_type_id == App\PaymentType::PER_DIEM) ?($contract->weekday_rate):($contract->rate)}}</td>
        @endif
      
        <td>{{ format_date($contract->created_at) }}</td>
        @if (is_super_user() || is_super_hospital_user())
            <td class="text-right rowlink-skip">
                <div class="btn-group btn-group-xs">
                    <a class="btn btn-default btn-delete" href="{{ route('contracts.delete', [$contract->id,$practice->id]) }}">
                        <i class="fa fa-trash-o fa-fw"></i>
                    </a>
                </div>
            </td>
        @endif
    </tr>

    @endif
    @endforeach
    </tbody>
</table>
@else
<div class="panel panel-default">
    <div class="panel-body">There are currently no contracts available for display.</div>
</div>
@endif
