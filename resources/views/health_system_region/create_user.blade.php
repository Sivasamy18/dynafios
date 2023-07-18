@extends('layouts/_healthSystemRegion', [ 'tab' => 2 ])
@section('actions')
<a class="btn btn-default" href="{{ URL::route('healthSystemRegion.add_user', [$system->id, $region->id]) }}">
    <i class="fa fa-user fa-fw"></i> Existing
</a>
<a class="btn btn-default" href="{{ URL::route('healthSystemRegion.users', [$system->id, $region->id]) }}">
    <i class="fa fa-list fa-fw"></i> Index
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
