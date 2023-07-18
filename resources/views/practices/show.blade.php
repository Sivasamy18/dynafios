@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@php use function App\Start\is_hospital_owner; @endphp
@extends('layouts/_practice', [ 'tab' => 1 ])
@section('actions')
    @if (is_super_user()||is_super_hospital_user())
        <a class="btn btn-default" href="{{ URL::route('practices.edit', $practice->id) }}">
            <i class="fa fa-cogs fa-fw"></i> Settings
        </a>
        <a class="btn btn-default btn-delete" href="{{ URL::route('practices.delete', $practice->id) }}">
            <i class="fa fa-trash-o fa-fw"></i> Delete
        </a>
    @endif
@endsection
@section('content')
<div class="row">
    <div class="col-xs-8">
        <h4>Recent Activity</h4>

        <div class="recent-activity">
            {!! $table !!}
        </div>
    </div>
    <div class="col-xs-4">
        <div class="panel panel-default">
            <div class="panel-heading">Practice Information</div>
            <div class="panel-body">
                <table class="table" style="font-size: 12px">
                    <tr>
                        <td>NPI</td>
                        <td>{{ $practice->npi }}</td>
                    </tr>
                    <tr>
                        <td>Type</td>
                        <td>{{ $practice->practiceType->name }}</td>
                    </tr>
                    <tr>
                        <td>State</td>
                        <td>{{ $practice->state->name }}</td>
                    </tr>
                    <tr>
                        <td>Hospital</td>
                        <td>
                            @if (is_hospital_owner($practice->hospital->id))
                                <a href="{{ route('hospitals.show', $practice->hospital->id) }}">
                                    {{ $practice->hospital->name }}
                                </a>
                            @else
                                {{ $practice->hospital->name }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Physicians</td>
                        <td>{{  $practice_physicians_count  }}</td>
                        
                    </tr>
                    <tr>
                        <td>Created</td>
                        <td>{{ format_date($practice->created_at) }}</td>
                    </tr>
                    <tr>
                        <td>Updated</td>
                        <td>{{ format_date($practice->updated_at) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@include('audits.audit-history', ['audits' => $practice->audits()->orderBy('created_at', 'desc')->with('user')->paginate(50)])

<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Practice?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this practice?</p>
                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this practice and any associated data. There is no way
                    to restore this data once this action has been completed.
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
        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
            dialogButton: '.btn-primary'
        });
    });
</script>
@endsection
