@extends('layouts/_dashboard')
@section('main')
  <div class="page-header physician-page-header">
    <div class="row">
      <div class="col-xs-1">
        <i class="fa fa-user-md fa-fw icon"></i>
      </div>
      <div class="col-xs-7">
        <h3>{{ "{$physician->first_name} {$physician->last_name}" }}</h3>
        <ul class="info">
          <li><i class="fa fa-hospital-o"></i> {{ $physician->practices->first()->name }}</li>
          <li><i class="fa fa-star"></i> {{ $physician->specialty->name }}</li>
        </ul>
      </div>
      <div class="col-xs-4">            
        <div class="btn-group btn-group-sm">
          @yield('actions')                
        </div>
      </div>
    </div>
    <ul class="nav nav-tabs">
      <li class="{{ HTML::active($tab == 1) }}">
        <a href="{{ URL::route('physicians.show', $physician->id) }}">Overview</a>
      </li>
      <li class="{{ HTML::active($tab == 2) }}">
        <a href="{{ URL::route('physicians.contracts', $physician->id) }}">Contracts</a>
      </li>
      <li class="{{ HTML::active($tab == 3) }}">
        <a href="{{ URL::route('physicians.logs', $physician->id) }}">Logs</a>
      </li>
      <li class="{{ HTML::active($tab == 4) }}">
        <a href="{{ URL::route('physicians.reports', $physician->id) }}">Reports</a>
      </li>        
      <li class="{{ HTML::active($tab == 5) }}">
        <a href="{{ URL::route('physicians.edit', $physician->id) }}">Settings</a>
      </li>        
    </ul>
  </div>
  @include('layouts/_flash')
  @yield('content')
@endsection