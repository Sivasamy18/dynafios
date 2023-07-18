<div class="form-wrapper" style="position: relative;">
    {{ Form::open([ 'class' => 'form form-horizontal form-generate-report' ]) }}
    <div class="panel panel-default">
        <div class="panel-heading">{{ $form_title }}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">Contract Type</label>
                <div class="col-xs-5">
                    {{ Form::select('contract_type', $contract_types, Request::old('contract_type', $contract_type), [ 'class' =>
                    'form-control' ]) }}
                </div>
            </div>            
            <div class="form-group" style="border-top: 1px solid #ddd; padding-top: 10px;">
                <div class="col-xs-6"><label>Agreement</label></div>
                <div class="col-xs-3"><label>Start Date</label></div>
                <div class="col-xs-3"><label>End Date</label></div>
            </div>
            <div class="agreements" style="border-bottom: 1px solid #ddd; margin-bottom: 20px;">
                @foreach ($agreements as $agreement)
                <div class="form-group">
                    <div class="col-xs-6">
                        {{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement']) }}{{ $agreement->name }}
                    </div>
                    <div class="col-xs-3">
                        {{ Form::select("agreement_{$agreement->id}_start_month", $agreement->start_dates, $agreement->current_month - 1, ['class' => 'form-control']) }}

                    </div>
                    <div class="col-xs-3">
                        {{ Form::select("agreement_{$agreement->id}_end_month", $agreement->end_dates, $agreement->current_month - 1, ['class' => 'form-control']) }}
                    </div>
                </div>
                @endforeach
            </div>
            @if (isset($practices))
                <div class="form-group">
                    <label class="col-xs-2 control-label">Practices</label>
                    <div class="col-xs-5">
                        {{ Form::select('practices[]', $practices, Request::old('practices[]'), [ 'id' => 'practices', 'class' => 'form-control', 'multiple' => 'multiple' ]) }}
                        <p class="help-block">
                            <input id="all" type="checkbox"/><span id="all-label">Select All (Control/Command + Click to select or deselect items)</span>
                        </p>
                    </div>
                </div>
            @endif    
            @if (isset($physicians))
                <div class="form-group">
                    <label class="col-xs-2 control-label">Physicians</label>
                    <div class="col-xs-5">
                        {{ Form::select('physicians[]', $physicians, Request::old('physicians[]'), [ 'id' => 'physicians', 'class' => 'form-control', 'multiple' => 'multiple' ]) }}       
                        <p class="help-block">
                            <input id="all" type="checkbox"/><span id="all-label">Select All (Control/Command + Click to select or deselect items)</span>
                        </p>
                    </div>
                </div>
            @endif
        </div>
        <div class="panel-footer clearfix">
            <div class="help-block" style="float:left; margin-top: 8px;">
                Click the agreement(s) for your report or invoice before pressing submit.
            </div>
            <button class="btn btn-primary btn-sm btn-submit">Submit</button>
        </div>
    </div>
    {{ Form::close() }}
</div>