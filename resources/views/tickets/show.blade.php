@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_owner; @endphp
@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3><i class="fa fa-question-circle fa-fw icon"></i> DYNAFIOS Help Center</h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('tickets.index') }}">
            <i class="fa fa-arrow-circle-left fa-fw"></i> Back
        </a>
        <a class="btn btn-default" href="{{ URL::route('tickets.edit', $ticket->id) }}">
            <i class="fa fa-edit fa-fw"></i> Edit
        </a>
        <a class="btn btn-default btn-delete" href="{{ URL::route('tickets.delete', $ticket->id) }}">
            <i class="fa fa-trash-o fa-fw"></i> Delete
        </a>
        @if ($ticket->open)
        <a class="btn btn-default btn-close" href="{{ URL::route('tickets.close', $ticket->id) }}">
            <i class="fa fa-lock fa-fw"></i> Close
        </a>
        @else
        <a class="btn btn-default btn-open" href="{{ URL::route('tickets.open', $ticket->id) }}">
            <i class="fa fa-unlock fa-fw"></i> Open
        </a>
        @endif
    </div>
</div>
@include('layouts/_flash')
<div class="panel panel-default ticket-panel">
    <div class="panel-heading">{{ $ticket->subject }}</div>
    <div class="panel-body">
        <div class="row">
            <div class="col-xs-4">
                <table class="table">
                    <tr>
                        <td><i class="fa fa-user fa-fw"></i></td>
                        <td>{{ "{$ticket->user->first_name} {$ticket->user->last_name}" }}</td>
                    </tr>
                    <tr>
                        <td><i class="fa fa-calendar fa-fw"></i></td>
                        <td>{{ format_date($ticket->created_at) }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-xs-8">
                {{ $ticket->body }}
            </div>
        </div>
    </div>
</div>
@foreach ($ticket->messages as $message)
<div class="panel panel-default ticket-panel">
    <div class="panel-body">
        {{ $message->body }}
    </div>
    <div class="panel-footer">
        <div class="row">
            <div class="col-xs-6">
                {{ "{$message->user->first_name} {$message->user->last_name}" }} on {{ format_date($message->created_at)
                }}
            </div>
            <div class="col-xs-6 text-right">
                <div class="btn-group btn-group-xs">
                    @if (is_super_user() || is_owner($message->user->id))
                    <a class="btn btn-default"
                       href="{{ URL::route('tickets.edit_message', [ $ticket->id, $message->id ]) }}">
                        <i class="fa fa-edit fa-fw"></i> Edit
                    </a>
                    <a class="btn btn-default btn-delete" href="#">
                        <i class="fa fa-trash-o fa-fw"></i> Delete
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach
{{ Form::open([ 'class' => 'form form-horizontal form-ticket-message' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Reply to Ticket</div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Body</label>

            <div class="col-xs-5">
                {{ Form::textarea('body', Request::old('body'), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('body', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
<div id="modal-confirm-open" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Reopen this Ticket?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reopen this ticket?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Open</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<div id="modal-confirm-close" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Close this Ticket?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to close this ticket?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will close this ticket and no further responses will be possible.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary">Close</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div><!-- /.modal -->
<div id="modal-confirm-delete" class="modal fade">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Delete Ticket?</h4>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this ticket?</p>

                <p><strong style="color: red">Warning!</strong><br>
                    This action will delete this ticket and any associated data. There is no way to
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
        Dashboard.confirm({
            button: '.btn-delete',
            dialog: '#modal-confirm-delete',
            dialogButton: '#modal-confirm-delete .btn-primary'
        });

        Dashboard.confirm({
            button: '.btn-open',
            dialog: '#modal-confirm-open',
            dialogButton: '#modal-confirm-open .btn-primary'
        });

        Dashboard.confirm({
            button: '.btn-close',
            dialog: '#modal-confirm-close',
            dialogButton: '#modal-confirm-close .btn-primary'
        });
    });
</script>
@endsection