@extends('layouts/_physician', [ 'tab' => 2 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('contract_id', $contract->id) }}
    {{ Form::hidden('interface_type_id', $interfaceType) }}
    {{ Form::hidden('physicianIsLawsonInterfaceReady', $physicianIsLawsonInterfaceReady) }}
    <div class="panel panel-default">
        <div class="panel-heading">
            Contract Interface Settings
<!-- physician to multiple hospital by 1254:1002 -->
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('contracts.edit', [$contract,$practice,$physician]) }}">
                Back
            </a>
        </div>

        <div class="panel-body">

                <div class="form-group">
                    <label class="col-xs-2 control-label">Interface Type</label>
                    <div class="col-xs-5">
                        {{ Form::select('interface_type_id', $interfaceTypes, Request::old('interface_type_id', $interfaceType), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                </div>

                <!-- LAWSON INTERFACE FIELD NAMES -->
                    <div id="lawsonFieldNames" class="hidden">

                    <div class="form-group">
                            <label class="col-xs-2 control-label">Enabled</label>
                            <div class="col-xs-4">
                                <div id="toggle" class="input-group">
                                    <label class="switch">
                                        {{ Form::checkbox('is_lawson_interfaced', 1, Request::old('is_lawson_interfaced',$is_lawson_interfaced), ['id' => 'is_lawson_interfaced']) }}
                                        <div class="slider round"></div>
                                        <div class="text"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-xs-5"></div>
                        </div>

                        <div class="form-group">
                            <label class="col-xs-2 control-label">Company</label>
                            @if($physicianIsLawsonInterfaceReady=='1')
                            <div class="col-xs-5">
                                {{ Form::text('cvd_company', Request::old('cvd_company', $physicianInterfaceDetailsLawson->cvi_company), [ 'class' => 'form-control' ]) }}
                            </div>
                            @else
                            <div class="col-xs-5">
                                {{ Form::text('cvd_company', Request::old('cvd_company', $interfaceDetailsLawson->cvd_company), [ 'class' => 'form-control' ]) }}
                            </div>
                            @endif
                            <div class="col-xs-5">{!! $errors->first('cvd_company', '<p class="validation-error">:message</p>') !!}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label">Vendor</label>
                            @if($physicianIsLawsonInterfaceReady=='1')
                            <div class="col-xs-5">
                                {{ Form::text('cvd_vendor', Request::old('cvd_vendor', $physicianInterfaceDetailsLawson->cvi_vendor), [ 'class' => 'form-control' ]) }}
                            </div>
                            @else
                            <div class="col-xs-5">
                                {{ Form::text('cvd_vendor', Request::old('cvd_vendor', $interfaceDetailsLawson->cvd_vendor), [ 'class' => 'form-control' ]) }}
                            </div>
                            @endif
                            <div class="col-xs-5">{!! $errors->first('cvd_vendor', '<p class="validation-error">:message</p>') !!}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label">Invoice Number Suffix</label>
                            <div class="col-xs-5">
                                {{ Form::text('invoice_number_suffix', Request::old('invoice_number_suffix', $interfaceDetailsLawson->invoice_number_suffix), [ 'class' => 'form-control' ]) }}
                            </div>
                            <div class="col-xs-5">{!! $errors->first('invoice_number_suffix', '<p class="validation-error">:message</p>') !!}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label">Distribution Company</label>
                            <div class="col-xs-5">
                                {{ Form::text('cvd_dist_company', Request::old('cvd_dist_company', $interfaceDetailsLawson->cvd_dist_company), [ 'class' => 'form-control' ]) }}
                            </div>
                            <div class="col-xs-5">{!! $errors->first('cvd_dist_company', '<p class="validation-error">:message</p>') !!}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label">Accounting Unit</label>
                            <div class="col-xs-5">
                                {{ Form::text('cvd_dis_acct_unit', Request::old('cvd_dis_acct_unit', $interfaceDetailsLawson->cvd_dis_acct_unit), [ 'class' => 'form-control' ]) }}
                            </div>
                            <div class="col-xs-5">{!! $errors->first('cvd_dis_acct_unit', '<p class="validation-error">:message</p>') !!}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label">Account Number</label>
                            <div class="col-xs-5">
                                {{ Form::text('cvd_dis_account', Request::old('cvd_dis_account', $interfaceDetailsLawson->cvd_dis_account), [ 'class' => 'form-control' ]) }}
                            </div>
                            <div class="col-xs-5">{!! $errors->first('cvd_dis_account', '<p class="validation-error">:message</p>') !!}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-xs-2 control-label">Subaccount Number</label>
                            <div class="col-xs-5">
                                {{ Form::text('cvd_dis_sub_acct', Request::old('cvd_dis_sub_acct', $interfaceDetailsLawson->cvd_dis_sub_acct), [ 'class' => 'form-control' ]) }}
                            </div>
                            <div class="col-xs-5">{!! $errors->first('cvd_dis_sub_acct', '<p class="validation-error">:message</p>') !!}</div>
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
    $(function () {

        $( "input[name|='cvd_company']" ).prop('readOnly',true);
        $( "input[name|='cvd_vendor']" ).prop('readOnly',true);

        $( "select[name|='interface_type_id']" ).change(function() {
                if($( "select[name|='interface_type_id']" ).val()==='1'){
                    $( "#lawsonFieldNames" ).removeClass("hidden").addClass("show");
                }
                else {
                    $( "#lawsonFieldNames" ).removeClass("show").addClass("hidden");
                }
                if($( "select[name|='interface_type_id']" ).val()==='2'){
                    $( "#imagenowFieldNames" ).removeClass("hidden").addClass("show");
                }
                else {
                    $( "#imagenowFieldNames" ).removeClass("show").addClass("hidden");
                }
        });

        if($( "select[name|='interface_type_id']" ).val()==='1'){
            $( "#lawsonFieldNames" ).removeClass("hidden").addClass("show");
        }
        else {
            $( "#lawsonFieldNames" ).removeClass("show").addClass("hidden");
        }
        if($( "select[name|='interface_type_id']" ).val()==='2'){
            $( "#imagenowFieldNames" ).removeClass("hidden").addClass("show");
        }
        else {
            $( "#imagenowFieldNames" ).removeClass("show").addClass("hidden");
        }

    })
</script>
@endsection