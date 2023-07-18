@extends('dashboard/_index')
@section('links')
	<a href="{{ URL::route('users.show', $current_user->id) }}"><i class="fa fa-user fa-fw"></i> User Profile</a>
	<a href="{{ URL::route('tickets.index') }}"><i class="fa fa-question-circle fa-fw"></i> Help Center</a>
	@if (count($current_user->practices) == 1)
		<a href="{{ URL::route('practices.show', $current_user->practices[0]->id) }}">
			<i class="fa fa-eye fa-fw"></i> Overview
		</a>
		<a href="{{ URL::route('practiceManager.reports', [$current_user->practices[0]->id,$current_user->practices[0]->hospital->id]) }}">
			<i class="fa fa-cloud-download fa-fw"></i> Reports
		</a>
		<a href="{{ URL::route('practices.contracts', [$current_user->practices[0]->id]) }}">
			<i class="fa fa-edit fa-fw"></i> Log Entry
		</a>
		<a href="{{ env('PRODUCTIVITY_URL') }}" >
			<i class="fa fa-signal fa-fw"></i> Productivity
		</a>
	@else
		<div class="clearfix"></div>
		@foreach ($current_user->practices as $practice)
		<!-- 7.//Add features : hospital name in front of practice name for pract manager login by 1254 -->

			 <h5><i class="fa fa-user-md fa-fw"></i> {{ $practice->name }} - {{$practice->hospital->name}} </h5>


		 	<a href="{{ URL::route('practices.show', $practice->id) }}">
				<i class="fa fa-eye fa-fw"></i> Overview
			</a>
			<a href="{{ URL::route('practiceManager.reports', [$practice->id,$practice->hospital->id]) }}">
				<i class="fa fa-cloud-download fa-fw"></i> Reports
			</a>
			<a href="{{ URL::route('practices.contracts', [$practice->id]) }}">
				<i class="fa fa-edit fa-fw"></i> Log Entry
			</a>
			<a href="{{ env('PRODUCTIVITY_URL') }}" >
				<i class="fa fa-signal fa-fw"></i> Productivity
			</a>	
			<div class="clearfix"></div>
		@endforeach
	@endif
	<a href="{{ URL::route('approval.paymentStatus') }}"><i class="fa fa-certificate fa-fw"></i> Payment Status</a>
	@if(!$rejected)
        <script type="text/javascript">
            $('.blink_me').hide()
        </script>
  @else
        <script type="text/javascript">
            $('.blink_me').show()
        </script>
  @endif
@endsection
