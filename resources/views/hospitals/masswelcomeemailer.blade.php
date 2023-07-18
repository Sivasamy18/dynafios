@extends('layouts/_hospital', [ 'tab' => 7 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('hospital_id', $hospital->id) }}
    <div class="panel panel-default">
        <div class="panel-heading">
            Mass Welcome Emailer
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('hospitals.edit', [$hospital->id]) }}">
                Back
            </a>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">User Type</label>
                <div class="col-xs-5">
                    {{ Form::select('user_type', $user_types, Request::old('user_type', $user_type), [ 'class' => 'form-control'
                    ]) }}
                </div>
            </div>

            

        <div class="panel-footer clearfix">
            <button class="btn btn-primary btn-sm btn-submit" type="submit">Send Email</button>
        </div>
    </div>
    {{ Form::close() }}
@endsection
@section('scripts')
<script type="text/javascript">
</script>
@endsection