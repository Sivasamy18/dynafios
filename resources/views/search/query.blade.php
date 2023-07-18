@extends('layouts/_dashboard')
@section('main')
<div class="page-header">
    <h3><i class="fa fa-search fa-fw icon"></i> Search Results: {{ $query }}</h3>
</div>
@include('layouts/_flash')
<div class="panel panel-default">
    <div class="panel-heading">Search</div>
    <div class="panel-body">
        {{ Form::open([ 'class' => 'form form-horizontal form-search' ]) }}
        <div class="form-group">
            <label class="col-xs-2 control-label">Query</label>

            <div class="col-xs-5">
                {{ Form::text('query', $query, [ 'id' => 'query', 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">
                <div>

                </div>
                <button class="btn btn-default btn-submit" type="submit">Submit</button>
            </div>
        </div>
        {{ Form::close() }}
    </div>
</div>
<div class="results" style="position: relative">{!! $results_table !!}</div>
@endsection
@section('scripts')
<script type="text/javascript">
    $(function () {
        var timer = null;

        $(document).on('keyup', '#query', function (event) {
            if (timer) {
                window.clearTimeout(timer);
            }

            timer = window.setTimeout(function () {
                timer = null;
                ajaxUpdate();
            }, 500);
        });

        function ajaxUpdate() {
            $(".results").block('show');

            $.ajax({
                url: '{{ URL::to('search') }}',
                method: 'post',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                dataType: 'json',
                data: {
                    query: $('#query').val(),
                    users: $("[name=users]").val(),
                    hospitals: $("[name=hospitals]").val(),
                    practices: $("[name=practices]").val(),
                    physicians: $("[name=physicians]").val()
                }
            }).done(function (response) {
                $(".results").html(response.results_table);
            });
        }
    });
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
</script>
@endsection