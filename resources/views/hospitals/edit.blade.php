@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
@extends('layouts/_hospital', [ 'tab' => 7 ])
@section('content')
{{ Form::open([ 'class' => 'form form-horizontal form-create-hospital' ]) }}
{{ Form::hidden('id', $hospital->id) }}
<div class="panel panel-default">
    <div class="panel-heading">
        Hospital Settings
        <a style="float: right; margin-top: -7px; margin-right: 10px" class="btn btn-primary"
           href="{{ route('hospitals.interfacedetails', [$hospital->id]) }}">
            Interface Details
        </a>
        <a style="float: right; margin-top: -7px; margin-right: 10px" class="btn btn-primary"
           href="{{ route('hospitals.masswelcomeemailer', [$hospital->id]) }}">
            Mass Welcome Emailer
        </a>
		<a style="float: right; margin-top: -7px; margin-right: 10px" class="btn btn-primary"
           href="{{ route('hospitals.dutiesmanagement', [$hospital->id]) }}">
            Duties Management
        </a>
    </div>
    <div class="panel-body">
        <div class="form-group">
            <label class="col-xs-2 control-label">Name</label>
            <div class="col-xs-5">
                {{ Form::text('name', Request::old('name', $hospital->name), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!!  $errors->first('name', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">NPI</label>
            <div class="col-xs-5">
                {{ Form::text('npi_show', Request::old('npi', $hospital->npi), [ 'class' => 'form-control','disabled' => true]) }}
                {{ Form::hidden('npi', Request::old('npi', $hospital->npi)) }}
            </div>
            <div class="col-xs-5">{!!  $errors->first('npi', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <div class="form-group">
            <label class="col-xs-2 control-label">Facility Type</label>
            <div class="col-xs-5">
                {{ Form::select('facility_type_show', $facility_types, Request::old('facility_type', $hospital->facility_type), [ 'class' => 'form-control','disabled' => true ]) }}
                {{ Form::hidden('facility_type', Request::old('facility_type', $hospital->facility_type)) }}
            </div>
            <div class="col-xs-5">{!!  $errors->first('facility_type', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        
        <div class="form-group">
            <label class="col-xs-2 control-label">Address</label>
            <div class="col-xs-5">
                {{ Form::text('address', Request::old('address', $hospital->address), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('address', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">City</label>
            <div class="col-xs-5">
                {{ Form::text('city', Request::old('city', $hospital->city), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('city', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">State</label>
            <div class="col-xs-5">
                {{ Form::select('state', $states, Request::old('state', $hospital->state_id), [ 'class' => 'form-control'
                ]) }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Benchmark Rejection Percentage</label>

            <div class="col-xs-5">
                {{ Form::text('benchmark', Request::old('benchmark', $hospital->benchmark_rejection_percentage), [ 'class' => 'form-control' ]) }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Primary User</label>
            <div class="col-xs-5">
                {{ Form::select('primary_user_id', $users, Request::old('primary_user_id', $primary_user_id), ['class' => 'form-control']) }}
            </div>
        </div>
        <div class="form-group">
            <label class="col-xs-2 control-label">Password Expiration Months</label>
            <div class="col-xs-5">
                {{ Form::text('password_expiration_months', Request::old('password_expiration_months', $hospital->password_expiration_months), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('password_expiration_months', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <div class="form-group" id="invoice_dashboard_toggle" >
            <label class="col-xs-2 control-label">Payment Dashboard</label>

            <div class="col-xs-4">
                <div id="toggle" class="input-group" >
                    <label class="switch">
                        {{ Form::checkbox('invoice_dashboard_on_off', 1, Request::old('invoice_dashboard_on_off',$hospital->invoice_dashboard_on_off), ['id' => 'invoice_dashboard_on_off']) }} 
                        <div class="slider round"></div>
                        <div class="text"></div>
                    </label>
                </div>
            </div>
            <div class="col-xs-5"></div>
        </div><br>

        <div class="form-group">
            <label class="col-xs-2 control-label">Assertation Popup:</label>
            <div class="col-xs-5">
            {{ Form::textarea("assertation_text", Request::old("assertation_text",$hospital->assertation_text), [ 'class' => 'form-control','maxlength' => 150, 'rows' => 15, 'cols' => 54, 'style' => 'resize:none' ]) }}
            </div>
        </div>
        <?php 
            // if($hospital->invoice_type ==1){
            //     if($note_count < App\InvoiceNote::HOSPITALCOUNT){
            //         $note_count = App\InvoiceNote::HOSPITALCOUNT;
            //     }
            // } else {
            //     if($note_count < 1){
            //         $note_count = 1;
            //     }
            // }
            if($note_count < 1){
                $note_count = 1;
            }
        ?>
        {{ Form::hidden('note_count',Request::old('note_count',$note_count),['id' => 'note_count']) }}
        <div id="notes">
            @for($i = 0; $i < Request::old('note_count',$note_count); $i++ )
                <div class="form-group invoive-note">
                    <label class="col-xs-2 control-label">Invoice Note {{ $i+1 }}</label>
                    <div class="col-xs-5">
                        {{ Form::textarea("note".($i+1), Request::old("note".($i+1),(isset($invoice_notes[$i+1]) ) ? $invoice_notes[$i+1] : ''), [ 'class' => 'form-control','id' => "note".($i+1),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                    </div>
                    <div class="col-xs-2"><button class="btn btn-primary btn-submit remove-note" type="button"> - </button></div>
                    <div class="col-xs-3">{!! $errors->first('note'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                </div>
            @endfor
        </div>
        <button class="btn btn-primary btn-submit add-note" type="button">Add Invoice Note</button>
        @if (is_super_user() || is_super_hospital_user())
            @if (is_super_user())
                <div class="form-group" id="compliance_toggle" style="display: block" >
                    <label class="col-xs-2 control-label">Compliance Dashboard</label>

                    <div class="col-xs-4">
                        <div id="toggle" class="input-group" >
                            <label class="switch">
                                {{ Form::checkbox('compliance_on_off', 1, Request::old('compliance_on_off',$hospital_feature_details->compliance_on_off), ['id' => 'compliance_on_off']) }} 
                                <div class="slider round"></div>
                                <div class="text"></div>
                            </label>
                        </div>
                    </div>
                    <div class="col-xs-5"></div>
                </div>

                <div class="form-group" id="performance_toggle" style="display: block"  >
                    <label class="col-xs-2 control-label">Performance Dashboard</label>

                    <div class="col-xs-4">
                        <div id="toggle" class="input-group" >
                            <label class="switch">
                                {{ Form::checkbox('performance_on_off', 1, Request::old('performance_on_off',$hospital_feature_details->performance_on_off), ['id' => 'performance_on_off']) }}
                                <div class="slider round"></div>
                                <div class="text"></div>
                            </label>
                        </div>
                    </div>
                    <div class="col-xs-5"></div>
                </div>
            @endif
            <div class="form-group" id="approve_all_invoice_toggle" style="display: block"  >
                <label class="col-xs-2 control-label">Approve All Invoices</label>

                <div class="col-xs-4">
                    <div id="toggle" class="input-group" >
                        <label class="switch">
                            {{ Form::checkbox('approve_all_invoices', 1, Request::old('approve_all_invoices', $hospital->approve_all_invoices), ['id' => 'approve_all_invoices']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>
        @endif
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
        $("[name=npi]").inputmask({ mask: '9999999999' });
        $("[name=expiration]").inputmask({ mask: '99/99/9999' });
        $("#expiration").datetimepicker({ language: 'en_US', pickTime: false });
    })
</script>
@endsection