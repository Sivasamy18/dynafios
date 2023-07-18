@extends('layouts/default')
@section('body-class', 'error')
@section('body')
<div class="main">
    <div class="wrapperx">        
        <div class="container container-well">
            <a class="logo" href="{{ route('dashboard.index') }}"><img src="{{ asset('assets/img/error/dynafios.png') }}"></a>
            <div class="col-xs-12 text-center">
                <h1>{{ $code }} - {{ $info['title'] }}</h1>
                <p>{{ $info['message'] }}</p>
            </div>
            @if (Auth::check())
                <div class="col-xs-6 col-xs-push-3">
                    <form class="form form-horizontal search-form" action="{{ URL::to('search') }}" method="post">
                        @csrf
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search fa-fw"></i></span>
                            <input class="form-control" name="query" type="text" placeholder="Search Query"/>
                        </div>
                    </form>
                </div>
            @endif
            <div class="col-xs-12 text-center">
                <a href="{{ route('dashboard.index') }}">Back to Index &rarr;</a>
            </div>            
        </div>
    </div>
</div>
@endsection