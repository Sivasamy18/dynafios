@php use function App\Start\is_super_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 11 ])
@section('actions')
    @if (is_super_user() )
        <a class="btn btn-default" href="{{ URL::route('hospitals.create_admin', $hospital->id) }}">
            <i class="fa fa-plus-circle fa-fw"></i> User
        </a>
    @endif
@endsection
@section('content')
<div class="admins" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
@endsection
