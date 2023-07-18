@extends("layouts/_physician", ["tab" => 5])
@section('actions')
	<a class="btn btn-default btn-payment" href="#">
		<i class="fa fa-plus-circle fa-fw"></i> Payment
	</a>
@endsection
@section("content")
<div class="form-drawer">
    <div class="drawer-content" style="{{ HTML::hidden($errors->count() == 0) }}">
    	{!! $form !!}
    </div>
</div>
<div id="payments" style="position: relative">
    {!! $table !!}
</div>
<div id="links">
    {!! $pagination !!}
</div>
@endsection
@section("scripts")
<script type="text/javascript">
	$(function() {
		Dashboard.pagination({
            container: '#payments',
            filters: '#payments .filters a',
            sort: '#payments .table th a',
            links: '#links',
            pagination: '#links .pagination a'
        });

		$(".btn-payment").on('click', function(event) {
            event.preventDefault();
            $('.form-drawer').drawer('toggle');
        });

        $(document).on("change", "[name=agreement]", function(event) { Dashboard.updatePaymentForm(); });
        $(document).on("change", "[name=month]", function(event) { Dashboard.updatePaymentFormMonth(); });
	});
</script>
@endsection