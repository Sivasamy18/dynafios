@extends('layouts/default')
@section('body')
<div id="login" class="wrapper">
    <header class="header">
        <div class="container">
            <img src="{{ asset('assets/img/auth/dynafios.png') }}" alt="DYNAFIOS Logo"/>

            <p>
                <small>Welcome to the </small>
                DYNAFIOS APP <span>Experience!</span>
            </p>
        </div>
    </header>
    <div class="content">
        <div class="main">
            <div class="container">
                @yield('main')
            </div>
        </div>
        <div class="featured">
            <div class="container">
                @yield('featured')
            </div>
        </div>
    </div>
    @include('layouts/_footer')
</div>
@endsection