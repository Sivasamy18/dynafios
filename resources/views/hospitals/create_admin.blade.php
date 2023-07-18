@extends('layouts/_hospital', [ 'tab' => 3 ])
@section('actions')
<a class="btn btn-default" href="{{ URL::route('hospitals.admins', $hospital->id) }}">
    <i class="fa fa-list fa-fw"></i> Index
</a>
<a class="btn btn-default" href="{{ URL::route('hospitals.add_admin', $hospital->id) }}">
    <i class="fa fa-user fa-fw"></i> Existing
</a>
@endsection
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-action' ]) }}
<div class="panel panel-default">
    <div class="panel-heading">Create User</div>
    @include('users/_createForm')
</div>
{{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
        $('input[name=phone]').inputmask({
            mask: '(999) 999-9999'
        });
    });
</script>
@endsection