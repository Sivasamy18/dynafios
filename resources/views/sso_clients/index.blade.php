@php use function App\Start\is_super_user; @endphp
@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-laptop icon"></i> SSO Clients 
        <small class="index">{{ $index }}</small>
    </h3>
    <div class="btn-group btn-group-sm">
        @if (is_super_user())
            <a class="btn btn-default" href="{{ URL::route('sso_clients.create') }}">
                <i class="fa fa-plus-circle fa-fw"></i> Add SSO Client
            </a>
        @endif
    </div>
</div>
@include('layouts/_flash')
<div class="actions" style="position: relative">
    {!! $table !!}
</div>
<div class="links">
    {!! $pagination !!}
</div>
@endsection
@section('scripts')
<script type="text/javascript">

</script>
@endsection
