@extends('layouts/_physician', [ 'tab' => 2 ])
@section('actions')

@endsection
<style>
    input.form-control.check {
        height: 20px;
        width: 20px;
    }
</style>
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-physician' , 'enctype'=> 'multipart/form-data']) }}
    {{ Form::hidden('id', $contract->id) }}
    <div class="panel panel-default">
        <div class="panel-heading">General</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label"> Agreement</label>

                <div class="col-xs-5">
                    {{ $agreement_name }}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Payment Type</label>

                <div class="col-xs-5">
                    {{ $payment_type }}
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label"> Contract Name</label>

                <div class="col-xs-5">
                    {{ $contract_name }}
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label"> Contract Type</label>

                <div class="col-xs-5">
                    {{ $contract_type }}
                </div>
            </div>

            <!--annual comp-->
            <div class="form-group" id="annual_comp_div">
                <label class="col-xs-2 control-label">Annual Comp</label>

                <div class="col-xs-5">
                    <div class="input-group" id="annual_comp_div_text">
                        {{ Form::text('annual_comp', Request::old('annual_comp',  formatNumber($contract_psa_metrics->annual_comp)), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="annual_comp_error_div">{!! $errors->first('annual_comp', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp ninety-->
            <div class="form-group" id="annual_comp_ninety_div">
                <label class="col-xs-2 control-label">Annual Comp 90th</label>

                <div class="col-xs-5">
                    <div class="input-group" id="annual_comp_ninety_div_text">
                        {{ Form::text('annual_comp_ninety', Request::old('annual_comp_ninety',  formatNumber($contract_psa_metrics->annual_comp_ninety)), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="annual_comp_ninety_error_div">{!! $errors->first('annual_comp_ninety', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu ninety-->
            <div class="form-group" id="wrvu_ninety_div">
                <label class="col-xs-2 control-label">wRVU 90th</label>

                <div class="col-xs-5">
                    <div class="input-group" id="wrvu_ninety_div_text">
                        {{ Form::text('wrvu_ninety', Request::old('wrvu_ninety',  $contract_psa_metrics->wrvu_ninety), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="wrvu_ninety_error_div">{!! $errors->first('wrvu_ninety', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp seventy five-->
            <div class="form-group" id="annual_comp_seventy_five_div">
                <label class="col-xs-2 control-label">Annual Comp 75th</label>

                <div class="col-xs-5">
                    <div class="input-group" id="annual_comp_seventy_five_div_text">
                        {{ Form::text('annual_comp_seventy_five', Request::old('annual_comp_seventy_five',  formatNumber($contract_psa_metrics->annual_comp_seventy_five)), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="annual_comp_seventy_five_error_div">{!! $errors->first('annual_comp_seventy_five', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu seventy five-->
            <div class="form-group" id="wrvu_seventy_five_div">
                <label class="col-xs-2 control-label">wRVU 75th</label>

                <div class="col-xs-5">
                    <div class="input-group" id="wrvu_seventy_five_div_text">
                        {{ Form::text('wrvu_seventy_five', Request::old('wrvu_seventy_five',  $contract_psa_metrics->wrvu_seventy_five), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="wrvu_seventy_five_error_div">{!! $errors->first('wrvu_seventy_five', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp fifty-->
            <div class="form-group" id="annual_comp_fifty_div">
                <label class="col-xs-2 control-label">Annual Comp 50th</label>

                <div class="col-xs-5">
                    <div class="input-group" id="annual_comp_fifty_div_text">
                        {{ Form::text('annual_comp_fifty', Request::old('annual_comp_fifty',  formatNumber($contract_psa_metrics->annual_comp_fifty)), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="annual_comp_fifty_error_div">{!! $errors->first('annual_comp_fifty', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu fifty-->
            <div class="form-group" id="wrvu_fifty_div">
                <label class="col-xs-2 control-label">wRVU 50th</label>

                <div class="col-xs-5">
                    <div class="input-group" id="wrvu_fifty_div_text">
                        {{ Form::text('wrvu_fifty', Request::old('wrvu_fifty',  $contract_psa_metrics->wrvu_fifty), [ 'class' =>
                    'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5" id="wrvu_fifty_error_div">{!! $errors->first('wrvu_fifty', '<p class="validation-error">:message</p>') !!}</div>
            </div>

        </div>
    </div>
    
    <button class="btn btn-default btn-primary btn-submit" type="submit">Submit</button>
    {{ Form::close() }}
@endsection
@section("scripts")
    <script type="text/javascript">
        $( document ).ready(function(){
                            $('#wrvu_fifty_div_text').removeClass('input-group');
                            $('#wrvu_seventy_five_div_text').removeClass('input-group');
                            $('#wrvu_ninety_div_text').removeClass('input-group');
        });
    </script>
@endsection
