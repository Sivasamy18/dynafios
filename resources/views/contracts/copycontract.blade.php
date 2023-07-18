@extends('layouts/_physician', [ 'tab' => 2 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('contract_id', $contract->id) }}
    <div class="panel panel-default">
        <div class="panel-heading">
           Copy Contract
            <!-- physician to multiple hospital by 1254 -->
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('contracts.edit', [$contract->id,$practice->id, $physician->id]) }}">
                Back
            </a>
        </div>

        <div class="panel-body">

            <div class="form-group">
                <label class="col-xs-2 control-label">Physicians</label>
                <div class="col-xs-5">
                    {{ Form::select('physician_id', $physicians, Request::old('physician_id', $physician->id), [ 'class' => 'form-control'
                    ]) }}
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label">Practice</label>
                <div class="col-xs-5">
                    {{ Form::select('practice_id', $practices, Request::old('practice_id', $practice), [ 'class' => 'form-control'
                    ]) }}
                </div>
            </div>
            
            <div class="form-group">
                <label class="col-xs-2 control-label">Agreement</label>
                <div class="col-xs-5">
                    {{ Form::select('agreement_id', $agreements, Request::old('agreement_id', $agreement), [ 'class' => 'form-control'
                    ]) }}
                </div>
            </div>

            <div class="panel-footer clearfix">
                <button class="btn btn-primary btn-sm btn-submit" type="submit">Copy</button>
            </div>

    </div>
    {{ Form::close() }}
    <script type="text/javascript">
    $(document).ready(function() {
        // $("select").selectize({
        //     create: true,
        //     sortField: "text",
        // });

        $("[name='physician_id']").on('change', function() {

            $(".overlay").show();
            var physician_id = this.value;
            $.ajax({
                url: '{{ URL::current() }}?physician_id=' + physician_id,
                dataType: "json"
            }).done(function(response) {
                var old_practices = $("[name='practice_id']");
                old_practices.empty(); // remove old options
                $.each(response.practices, function(value,key) {
                    old_practices.append($("<option></option>")
                        .attr("value", value).text(key));
                });
                $(".overlay").hide();
            });
        });


        $(".overlay").show();
        var physician_id = $("[name='physician_id']")[0].value;
        $.ajax({
            url: '{{ URL::current() }}?physician_id=' + physician_id,
            dataType: "json"
        }).done(function(response) {
            var old_practices = $("[name='practice_id']");
            old_practices.empty(); // remove old options
            $.each(response.practices, function(value,key) {
                old_practices.append($("<option></option>")
                    .attr("value", value).text(key));
            });
            $(".overlay").hide();
        });
    });
    </script>
@endsection
@section('scripts')
<script type="text/javascript">
</script>
@endsection