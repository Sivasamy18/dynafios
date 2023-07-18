@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3>
        <i class="fa fa-question-circle fa-fw icon"></i> DYNAFIOS Help Center
        <small class="index">{{ $index }}</small>
    </h3>

</div>
@include('layouts/_flash')
<div id="tickets" style="position: relative">
    {!! $table !!}
</div>
@endsection
@section('scripts')
@endsection