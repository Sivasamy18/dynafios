@extends('layouts/_dashboard')
@section('main')
    <div class="page-header">
        <h3><i class="fa fa-user fa-fw icon"></i> Report Scheduler</h3>
    </div>
    @include('layouts/_flash')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-scheduler' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">Set Report Scheduler</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">Scheduler Type</label>

                <div class="col-xs-2">
                    <label class="col-xs-6 control-label" style="padding-left: 0; font-weight: normal;">Monthly</label>
                    <div class="col-xs-6" style="padding-left: 0;">
                    {{ Form::radio('type', '1', $type ==1 ? true:false,[ 'class' => 'form-control','style'=>'height:24px;box-shadow: none;outline: 0;' ]) }}
                    </div>
                </div>
                <div class="col-xs-2">
                    <label class="col-xs-6 control-label" style="padding-left: 0; font-weight: normal;">Weekly</label>
                    <div class="col-xs-6" style="padding-left: 0;">
                    {{ Form::radio('type', '2',$type ==2 ? true:false,[ 'class' => 'form-control','style'=>'height:24px;box-shadow: none;outline: 0;'  ]) }}
                    </div>
                </div>
                <div class="col-xs-6">{!! $errors->first('type', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div id="date" class="form-group" style="display:{{ $type ==1 ? 'block':'none' }}">
                <label class="col-xs-2 control-label">Select Date</label>

                <div class="col-xs-5">
                    {{ Form::selectRange('date', 1, 28, Request::old('date',$date), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('date', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
            <div id="day" class="form-group" style="display:{{ $type ==2 ? 'block':'none' }}">
                <label class="col-xs-2 control-label">Select Day </label>

                <div class="col-xs-5">
                    {{ Form::select('day', ['1' => 'Monday','2' => 'Tuesday','3' => 'wednesday','4' => 'Thursday','5' => 'Friday','6' => 'Saturday','7' => 'Sunday'], Request::old('day',$day ), [ 'class' => 'form-control' ]) }}
                </div>
                <div class="col-xs-5">
                    {!! $errors->first('day', '<p class="validation-error">:message</p>') !!}
                </div>
            </div>
        </div>
        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit" type="submit">Submit</button>
        </div>
    </div>
    {{ Form::close() }}
@endsection

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function(){
            var type= $('input:radio[name=type]:checked').val();
            if(type == 1){
                $("#date").show();
                $("#day").hide();
            }else if(type == 2){
                $("#day").show();
                $("#date").hide();
            }
        });
        $('input:radio[name=type]').change(function() {
           var type= $('input:radio[name=type]:checked').val();
            if(type == 1){
                $("#date").show();
                $("#day").hide();
            }else if(type == 2){
                $("#day").show();
                $("#date").hide();
            }
        });
    </script>
@endsection