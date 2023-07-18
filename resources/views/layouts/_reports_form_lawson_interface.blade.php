<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-3 control-label">Interface Date</label>
                <div class="col-xs-5">
                    {{ Form::select('interface_date', $interface_dates, Request::old('interface_date', $interface_date), [ 'class' =>
                    'form-control' ]) }}
                </div>
            </div>
           

        </div>
        <div class="panel-footer clearfix">
            <div class="help-block" style="float:left; margin-top: 8px;">
                Choose an interface date above before pressing submit.
            </div>
            <button class="btn btn-primary btn-sm btn-submit">Submit</button>
        </div>
    </div>
    {{ Form::close() }}
</div>
