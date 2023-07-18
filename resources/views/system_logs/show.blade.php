@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3><i class="fa fa-cog fa-fw icon"></i> System Log</h3>

    <div class="btn-group btn-group-sm">
        <a class="btn btn-default" href="{{ URL::route('system_logs.index') }}"><i class="fa fa-arrow-circle-left fa-fw"></i> Back</a>
    </div>
</div>
@include('layouts/_flash')
<div class="panel panel-default">
    <div class="panel-heading">Log Details</div>
    <div class="panel-body">        
        <table class="table">
            <tr>
                <td style="width: 150px"><strong>User:</strong></td>
                <td><a href="{{ route('users.show', $system_log->user->id) }}">{{ $system_log->user->email }}</a></td>
            </tr>
            <tr>
                <td><strong>URL:</strong></td>
                <td><a href="{{ $system_log->url }}">{{ $system_log->url }}</a></td>
            </tr>
            <tr>
                <td><strong>Input:</strong></td>
                <td><pre style="width: 600px; height: 200px; overflow-y: scroll;margin:0; padding:0;">{{ $system_log->input }}</pre></td>
            </tr>
        </table>
    </div>
    <div class="panel-footer clearfix">&nbsp;</div>
</div>
{{ Form::close() }}
@endsection