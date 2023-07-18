@php use function App\Start\is_super_user; @endphp
@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <div class="row">
        <div class="col-xs-6">
            <h3>Lawson Interfaced Contracts</h3>
        </div>
        <div class="col-xs-6">
            <div class="btn-group btn-group-sm">
                @if (is_super_user())
                <a class="btn btn-default" href="{{ URL::route('hospitals.show', $id) }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Back
                </a>
                @else
                <a class="btn btn-default" href="{{ URL::route('dashboard.index') }}">
                    <i class="fa fa-arrow-circle-left fa-fw"></i> Back
                </a>
                @endif
            </div>
        </div>
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
