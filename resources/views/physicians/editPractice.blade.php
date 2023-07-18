@extends('layouts/_physician', ['tab' => 6])
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal' ]) }}
{{ Form::hidden('id', $physician->id) }}
<div class="panel panel-default">
    <div class="panel-heading">
        Physician Settings
<!-- Physician to multiple hospital by  1254  -->
<!-- issue :  Deleted Practice Manager, add existing Practice Manager -->
        <a style="float: right; margin-top: -7px" class="btn btn-primary"
           href="{{ URL::route('physicians.edit', [$physician->id,$practice->id]) }}">
            Back
        </a>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-3 control-label">Hospital</label>
            <div class="col-xs-5">
                {{ Form::select('hospitals', $hospitals, Request::old('hospitals', $hospital_id), [ 'class'
                => 'form-control' ]) }}
                <!-- //1254 -->
                <input type="hidden" name="practiceid" value="{{$practice->id}}" />
            </div>
        </div>
    </div>
    
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-3 control-label">Practice</label>
            <div class="col-xs-5">
            
                <!-- //drop column practice_id from table 'physicians' changes by 1254 -->
                 {{-- Form::select('practices', $practices, Request::old('practices', $physician->practice_id), [ 'class'
                => 'form-control' ]) --}} 

                {{ Form::select('practices', $practices, Request::old('practices', $practice->id), [ 'class'
                => 'form-control' ]) }}

                
            </div>
        </div>
    </div>
    <div class="panel-body">
        <div class="form-group">
        <label class="col-xs-3 control-label">Practice Start Date</label>
        <div class="col-xs-5">
            <div id="end-date" class="input-group">
                {{ Form::text('change_date', Request::old('change_date', format_date(date("Y-m-d"))), [ 'class' =>
                'form-control' ]) }}
                <span class="input-group-addon"><i class="fa fa-calendar fa-fw"></i></span>
            </div>
        </div>
        <div class="col-xs-5">{!! $errors->first('change_date', '<p class="validation-error">:message</p>') !!}</div>
        </div>
    </div>
    <div class="panel-footer clearfix">
        <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
    </div>
</div>
{{ Form::close() }}
@endsection
@section("scripts")
    <script type="text/javascript">
        $(function() {
            $(document).on("change", "[name=hospitals]", function(event) {
                var hid=$("[name=hospitals]").val();
                window.location.href = hid
                //1254

                
            });
        });
    </script>
@endsection