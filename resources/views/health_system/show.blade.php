@php use function App\Start\is_super_user; @endphp
@extends('layouts/_healthSystem', [ 'tab' => 1 ])
@section('actions')
@if (is_super_user())
    <a class="btn btn-default" href="{{ URL::route('healthSystem.index') }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
<a class="btn btn-default btn-delete" href="{{ URL::route('healthSystem.delete', $system->id) }}">
    <i class="fa fa-trash-o fa-fw"></i> Delete System
</a>
@endif
@endsection
@section('content')
<div class="row">
    <div class="col-xs-12">
        <h3 class="details-bottom-style">Health System User Counts</h3>

        <div class="recent-activity">
            <ul>
                <li>Total User Count: {{ $system->total_users_count }}</li>
                <li>Percentage of Total DYNAFIOS Users: {{ $system->percent_of_total_users }}</li>
                <li>Health System Dashboard User Count: {{ count($system_users) }}</li>
                <li>Health System Region User Count: {{$system->region_users_count}}</li>
                <li>Physician Count: {{$system->physician_count}}</li>
                <li>Hospital User Count: {{$system->user_count}}</li>
                <li>Practice Manager Count: {{$system->pm_count}}</li>
                <li>Active Contracts: {{$system->active_contracts}}</li>
            </ul>
        </div>
    </div>
    <div class="col-xs-12">
        <h3 class="details-bottom-style">Health System Region User Counts</h3>
        @if(count($system_regions) > 0)
            <div class="recent-activity details-region-left-style">
                @foreach($system_regions as $system_region)
                    <h4><span style="font-weight:bold">{{ $system_region['name'] }}</span></h4>
                    <ul>
                        <li>Total User Count: {{$system_region['total_users_count']}}</li>
                        <li>Health System Region Dashboard User Count: {{ count($system_region['region_users']) }}</h5>
                        <li>Physician Count: {{ $system_region['physician_count'] }}</li>
                        <li>Hospital User Count: {{ $system_region['user_count'] }}</li>
                        <li>Practice Manager Count: {{ $system_region['pm_count'] }}</li>
                        <li>Active Contracts: {{$system_region['active_contracts']}}</li>
                    </ul>
                    <h5 class="details-region-info-left-style">Hospitals</h5>
                    @if(count($system_region['region_hospitals']) > 0)
                        <ul class="details-region-info-list-left-style">
                            @foreach($system_region['region_hospitals'] as $region_hospitals)
                                <li>
                                    <a target="_blank" href="{{ URL::route('hospitals.show', $region_hospitals->hospital_id) }}"><span style="font-weight:bold">{{$region_hospitals->name}} -</span></a> Total Users: {{$region_hospitals->total_users}}, Users Added Last Month: <span style="font-weight:bold">{{$region_hospitals->added_users}}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="panel-body details-error-style">
                            There are no hospitals associated to display at this time.
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <div class="panel-body details-error-style">
                There are no regions to display at this time.
            </div>
        @endif
    </div>
</div>
<div class="modal modal-delete-confirmation fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete this Health System?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this health system?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this health system and any associated data. There is no way to
                    restore this data once this action has been completed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Delete</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {

        $('.btn-delete').on('click', function (event) {
            $('.modal-delete-confirmation').modal('show');
            event.preventDefault();
        });

        $('.modal-delete-confirmation .btn-primary').on('click', function (event) {
            location.assign($('.btn-delete').attr('href'));
        });
    });
</script>
@endsection