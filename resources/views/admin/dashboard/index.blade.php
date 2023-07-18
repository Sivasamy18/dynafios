@extends('layouts._admin')

@section('main')
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">APP Maintenance Dashboard</h3>
    </div>
    <div class="card-body">
      <div class="quicklinks">
        <a href="{{route('admin.payments.index')}}">Payment Manager</a>
        <a href="{{route('log-viewer::logs.list')}}">Log Viewer</a>
        <a href="{{route('admin.emails.index')}}">Email Tracker</a>
        <a href="{{url('/admin/dashboard/horizon/dashboard')}}">Queue</a>
        <a href="{{route('roles_permissions.index')}}">Roles & Permissions Manager</a>
      </div>
    </div>
  </div>
@endsection
