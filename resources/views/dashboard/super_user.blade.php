@extends('dashboard/_index')
@section('links')
<a href="{{ URL::route('users.show', $current_user->id) }}"><i class="fa fa-user fa-fw"></i> User Profile</a>
<a href="{{ URL::route('actions.index') }}"><i class="fa fa-exclamation-circle fa-fw"></i> Actions</a>
<a href="{{ URL::route('contract_names.index') }}"><i class="fa fa-list fa-fw"></i> Contract Names</a>
<a href="{{ URL::route('contract_types.index') }}"><i class="fa fa-list fa-fw"></i> Contract Types</a>
<a href="{{ URL::route('practice_types.index') }}"><i class="fa fa-hospital-o fa-fw"></i> Practice Types</a>
<a href="{{ URL::route('interface_types.index') }}"><i class="fa fa-arrows-alt fa-fw"></i> Interface Types</a>
<a href="{{ URL::route('specialties.index') }}"><i class="fa fa-star fa-fw"></i> Specialties</a>
<a href="{{ URL::route('users.index') }}"><i class="fa fa-user fa-fw"></i> Users</a>
<a href="{{ URL::route('healthSystem.index') }}"><i class="fa fa-globe fa-fw"></i> Health Systems</a>
<a href="{{ URL::route('hospitals.index') }}"><i class="fa fa-hospital-o fa-fw"></i> Hospitals</a>
<a href="{{ URL::route('practices.index') }}"><i class="fa fa-user-md fa-fw"></i> Practices</a>
<a href="{{ URL::route('physicians.index') }}"><i class="fa fa-user-md fa-fw"></i> Physicians</a>
<a href="{{ URL::route('reports.index') }}"><i class="fa fa-cloud-download fa-fw"></i> Admin Reports</a>
<a href="{{ URL::route('tickets.index') }}"><i class="fa fa-question-circle fa-fw"></i> Help Center</a>
@endsection