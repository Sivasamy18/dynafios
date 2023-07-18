@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-user fa-fw icon"></i> Proxy Approver selection for - {{ "$user->first_name $user->last_name" }}
    </h3>
    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('users.show', $user->id) }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
    </div>
</div>
@include('layouts/_flash')
{{ Form::open([ 'class' => 'form form-horizontal form-add-proxy' ]) }}
{{ Form::hidden('id', $user->id) }}
<div class="panel panel-default">
    <div class="panel-heading">Appoint Proxy Approver</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-3 control-label">Proxy Approver</label>

            <div class="col-xs-5">
                {{ Form::select('approval_manager', $users, Request::old('approval_manager',$proxy_approver_id), [ 'class' => 'form-control select-managers']) }}

            </div>

        </div>
        <div class="form-group">
            <label class="col-xs-3 control-label">Start Date</label>

            <div class="col-xs-4">
                <div id="start-date" class="input-group">
                    {{ Form::text('start_date', Request::old('start_date', $proxy_approver_start_date), [ 'class'
                        => 'form-control' ]) }}
                    <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-3 control-label">End Date</label>

            <div class="col-xs-4">
                <div id="end-date" class="input-group">
                    {{ Form::text('end_date', Request::old('end_date',$proxy_approver_end_date), [ 'class' => 'form-control' ]) }}
                    <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
                </div>
            </div>
        </div>
        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>

            <a class="btn btn-default btn-delete" href="" data-toggle="modal" data-target="#modal-confirm-delete">
                <i class="fa fa-trash-o fa-fw"></i> Delete Proxy
            </a>
        </div>

    </div>
    <div id="modal-confirm-delete" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Delete Proxy?</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this proxy?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a type="button" class="btn btn-primary" href="{{ route('proxy_user.delete', $user->id) }}">Delete</a>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div><!-- /.modal -->
</div>
{{ Form::close() }}

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
