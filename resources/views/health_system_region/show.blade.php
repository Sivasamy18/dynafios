@php use function App\Start\is_super_user; @endphp
@extends('layouts/_healthSystemRegion', [ 'tab' => 1 ])
@section('actions')
@if (is_super_user())
    <a class="btn btn-default" href="{{ URL::route('healthSystem.regions', $system->id) }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
<a class="btn btn-default btn-delete" href="{{ URL::route('healthSystemRegion.delete', [$system->id,$region->id]) }}">
    <i class="fa fa-trash-o fa-fw"></i> Delete Region
</a>
@endif
@endsection
@section('content')
<div class="row">
    <div class="col-xs-12">
        <h3 class="details-bottom-style">Region Users</h3>

        <div class="recent-activity">
            @if(count($region_users) > 0)
                <ul>
                    @foreach($region_users as $region_user)
                        <li>{{$region_user->last_name .', '. $region_user->first_name}}</li>
                    @endforeach
                </ul>
            @else
                 <div class="panel-body details-error-style">
                     There are no users to display at this time.
                 </div>
            @endif
        </div>
    </div>
    <div class="col-xs-12">
        <h3 class="details-bottom-style">Hospitals Associated to Region</h3>

        <div class="recent-activity">
            @if(count($region_hospitals) > 0)
                <ul>
                    @foreach($region_hospitals as $region_hospital)
                        <li>{{$region_hospital->name}}</li>
                    @endforeach
                </ul>
            @else
                 <div class="panel-body details-error-style">
                     There are no hospitals associated to display at this time.
                 </div>
            @endif
        </div>
    </div>
</div>
<div class="modal modal-delete-confirmation fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete this Health System Region?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this health system region?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this health system region and any associated data. There is no way to
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