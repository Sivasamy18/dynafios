@extends('layouts/_physician', [ 'tab' => 6 ])
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal' ]) }}
    {{ Form::hidden('physician_id', $physician->id) }}
    {{ Form::hidden('interface_type_id', $interfaceType) }}
    <div class="panel panel-default">
        <div class="panel-heading">
            Physician Interface Settings
 <!-- physician to multiple hospital by 1254 -->
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('physicians.edit', [$physician->id,$practice->id]) }}">
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
                    <label class="col-xs-2 control-label">Company</label>
                    <div class="col-xs-5">
                        {{ Form::text('cvi_company', Request::old('cvi_company', $interfaceDetailsLawson->cvi_company), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_company', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Vendor</label>
                    <div class="col-xs-5">
                        {{ Form::text('cvi_vendor', Request::old('cvi_vendor', $interfaceDetailsLawson->cvi_vendor), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_vendor', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Authority Code</label>
                    <div class="col-xs-5">
                        {{ Form::text('cvi_auth_code', Request::old('cvi_auth_code', $interfaceDetailsLawson->cvi_auth_code), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_auth_code', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Process Level</label>
                    <div class="col-xs-5">
                        {{ Form::text('cvi_proc_level', Request::old('cvi_proc_level', $interfaceDetailsLawson->cvi_proc_level), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_proc_level', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Separate Payment Option</label>
                    <div class="col-xs-5">
                        {{ Form::select('cvi_sep_chk_flag', array('N' => 'N', 'Y' => 'Y'), Request::old('cvi_sep_chk_flag', $interfaceDetailsLawson->cvi_sep_chk_flag), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_sep_chk_flag', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Terms Code</label>
                    <div class="col-xs-5">
                        {{ Form::text('cvi_term_code', Request::old('cvi_term_code', $interfaceDetailsLawson->cvi_term_code), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_term_code', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Invoice Status</label>
                    <div class="col-xs-5">
                        {{ Form::select('cvi_rec_status', array('0' => 'Unreleased', '1' => 'Released', '9' => 'Historical'), Request::old('cvi_rec_status', $interfaceDetailsLawson->cvi_rec_status), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_rec_status', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Distribution Posting Status</label>
                    <div class="col-xs-5">
                        {{ Form::select('cvi_posting_status', array('0' => 'Unreleased', '1' => 'Unposted', '9' => 'Posted or Historical'), Request::old('cvi_posting_status', $interfaceDetailsLawson->cvi_posting_status), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_posting_status', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Payment Code</label>
                    <div class="col-xs-5">
                        {{ Form::text('cvi_bank_inst_code', Request::old('cvi_bank_inst_code', $interfaceDetailsLawson->cvi_bank_inst_code), [ 'class' => 'form-control' ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_bank_inst_code', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Invoice Reference Type</label>
                    <div class="col-xs-5">
                        {{ Form::select('cvi_invc_ref_type', array('IN' => 'Invoice', 'BM' => 'Bill of Lading', 'OW' => 'Service Order Number', 'PL' => 'Packing List Number', 'PO' => 'PO Number', 'VN' => 'Vendor Order', 'WO' => 'Work Order', 'WP' => 'Warehouse Pick Ticket Number', 'ZZ' => 'Mutually Defined'), Request::old('cvi_invc_ref_type', $interfaceDetailsLawson->cvi_invc_ref_type), [ 'class' => 'form-control'
                        ]) }}
                    </div>
                    <div class="col-xs-5">{!! $errors->first('cvi_invc_ref_type', '<p class="validation-error">:message</p>') !!}</div>
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