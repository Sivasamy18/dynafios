<div class="form-group">
    <div class="col-xs-4"><label>Agreement</label></div>
    <div class="col-xs-3"><label>Start Date</label></div>
    <div class="col-xs-3"><label>End Date</label></div>
</div>
@foreach ($agreements as $agreement)
    <div class="form-group">
        <div class="col-xs-4">
            {{ Form::checkbox('agreements[]', $agreement->id, false, ['class' => 'agreement']) }}{{ $agreement->name }}
        </div>
        <div class="col-xs-3">
            {{ Form::select("agreement_{$agreement->id}_start_month", $agreement->start_dates,
            $agreement->current_month - 1, ['class' => 'form-control']) }}

        </div>
        <div class="col-xs-3">
            {{ Form::select("agreement_{$agreement->id}_end_month", $agreement->end_dates,
            $agreement->current_month - 1, ['class' => 'form-control']) }}

        </div>
    </div>
@endforeach