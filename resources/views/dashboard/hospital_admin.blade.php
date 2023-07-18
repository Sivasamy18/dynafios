@php use function App\Start\is_super_hospital_user; @endphp
@extends('dashboard/_index')
@section('links')
	<a href="{{ URL::route('users.show', $current_user->id) }}"><i class="fa fa-user fa-fw"></i> User Profile</a>
	<a href="{{ URL::route('practices.index') }}"><i class="fa fa-user-md fa-fw"></i> Practices</a>
	<a href="{{ URL::route('tickets.index') }}"><i class="fa fa-question-circle fa-fw"></i> Help Center</a>
	@if (count($current_user->hospitals) == 1)
		<a href="{{ URL::route('hospitals.show', $current_user->hospitals[0]->id) }}">
			<i class="fa fa-hospital-o fa-fw"></i> Overview
		</a>
		<a href="{{ URL::route('hospitals.agreements', $current_user->hospitals[0]->id) }}">
			<i class="fa fa-certificate fa-fw"></i> Agreements
		</a>
		<a href="{{ URL::route('hospitals.reports', $current_user->hospitals[0]->id) }}">
			<i class="fa fa-cloud-download fa-fw"></i> Reports
		</a>
        <!-- add new button for Approver Asignments by #1254 -->
		@if(is_super_hospital_user())
		<a href="{{ URL::route('hospitals.approvers', $current_user->hospitals[0]->id) }}">
			<i class="fa fa-plug" aria-hidden="true"></i> Approver Assignments
		</a>
		@endif

	@else
		<div class="clearfix"></div>
		@foreach ($current_user->hospitals as $hospital)
			<h5><i class="fa fa-hospital-o fa-fw"></i> {{ $hospital->name }}</h5>
			<a href="{{ URL::route('hospitals.show', $hospital->id) }}">
				<i class="fa fa-hospital-o fa-fw"></i> Overview
			</a>
			<a href="{{ URL::route('hospitals.agreements', $hospital->id) }}">
				<i class="fa fa-certificate fa-fw"></i> Agreements
			</a>
			<a href="{{ URL::route('hospitals.reports', $hospital->id) }}">
				<i class="fa fa-cloud-download fa-fw"></i> Reports
			</a>
			<!-- add new button for Approver Asignments by #1254 -->
			@if(is_super_hospital_user())
			<a href="{{ URL::route('hospitals.approvers', $hospital->id) }}">
				<i class="fa fa-plug" aria-hidden="true"></i> Approver Assignments
			</a>
			@endif
			<div class="clearfix"></div>



		@endforeach
	@endif
@endsection
