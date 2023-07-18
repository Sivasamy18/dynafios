@extends('layouts/default')
@section('body')
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
