@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp

@extends('layouts/_physician', ['tab' => 2])
@section('actions')
    <a class="btn btn-default" href="{{ Request::url() }}">
        <i class="fa fa-list fa-fw"></i> Index
    </a>
@endsection
<script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.dragoptions.min.js') }}"></script>
<!--https://unpkg.com/@popperjs/core@2-->
<style>
    body.dragging, body.dragging * {
        cursor: move !important;
    }

    .dragged {
        position: absolute;
        opacity: 0.5;
        z-index: 2000;
    }

    ul.ul_activities li.placeholder {
        position: relative;
    }

    ul.ul_activities li.placeholder:before {
        position: absolute;
    }

    .borderlist {
        border: 1px solid black;
        list-style-position: inside;
    }

    .info-cls {
        display: flex;
        align-items: center;
    }

    .info-icon {
        margin-right: 10px;
    }

    .right-field {
        display: inline-flex;
        width: 100%;
    }

    .fmv_text {
        padding-left: 5px;
    }

    .text-align {
        margin-left: 22px;
    }

    #toggle .switch {
        margin-bottom: 0px;
    }

    select.small-dropdown {
        width: 24%;
    }

    .no-padding-top {
        padding-top: 0px !important;
    }

    .no-padding-left {
        padding-left: 0px !important;
    }

    .no-margin-left {
        margin-left: 0px !important;
    }

    .no-margin-bottom {
        margin-bottom: 0px !important;
    }

    .no-margin-right {
        margin-right: 0px !important;
    }

    .no-padding-right {
        padding-right: 0px !important;
    }

</style>
<style>
    input.form-control.check {
        height: 20px;
        width: 20px;
    }
</style>
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-contract', 'enctype'=> 'multipart/form-data']) }}
    <div class="panel panel-default createContract">
        <div class="panel-heading">Create Contract</div>
        {{ Form::hidden('agreement_end_date', '' , array('id' => 'agreement_end_date')) }}
        {{ Form::hidden('agreement_valid_upto_date', '' , array('id' => 'agreement_valid_upto_date')) }}

        {{ Form::hidden('agreement_pmt_frequency', '' , array('id' => 'agreement_pmt_frequency')) }}
        <input type="hidden" name="sorting_category_id" id="sorting_category_id" value="0">
        <input type="hidden" name="sorting_contract_data" id="sorting_contract_data" value="0">
        {{ Form::hidden('categories_count', $categories_count, array('id' => 'categories_count')) }}
        {{ Form::hidden('agreement_pmt_frequency', '' , array('id' => 'agreement_pmt_frequency')) }}

        <div class="panel-body">
            <div class="form-group">
                <label class="col-xs-2 control-label">Agreement
                </label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        {{ Form::select('agreement', $agreements, old('agreement'), [ 'class' => 'form-control', 'id' => 'agreement' ]) }}
                    </div>
                </div>
            <!-- <div class="col-xs-5" id="agreement_error_div">{!! $errors->first('rate', '<p class="validation-error">:message</p>') !!}</div> -->
                <div class="col-xs-5" id="agreement_error_div"></div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Payment Type</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        {{ Form::select('payment_type', $paymentTypes, old('payment_type'), [ 'class' =>
                        'form-control' ]) }}
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label">Contract Type</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        {{ Form::select('contract_type', $contractTypes, old('contract_type'), [ 'class' =>
                        'form-control' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group" id="supervision_type_div" style="display:none">
                <label class="col-xs-2 control-label">Supervision Type</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        {{ Form::select('supervision_type', $supervisionTypes, old('supervision_type'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label"> Contract Name *</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        <input class="contract-name-search" name="contract_name_search" type="text">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label"> Selection</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        {{ Form::select('contract_name', $contractNames, old('contract_name'), ['class' =>
                        'form-control']) }}
                        <select id="payment_type_autocomplete" style="display:none;" class="form-control"></select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label">Internal Notes</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6 right-field">
                        <div id="internal_notes" class="input-group">
                            {{ Form::textarea('internal_notes', old('internal_notes'), [ 'class' => 'form-control' ]) }}
                        </div>
                    </div>
                </div>
            </div>

            <!--CM FM For Contract-->
            <div id="approval_feilds" style="display: none;">
                <div class="form-group">
                    <label class="col-xs-2 control-label">Use Default</label>
                    <div class="info-cls">
                        <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div class="col-xs-1" style="width: 3.333333%;">
                            {{ Form::checkbox('default', 1, old('default',1), [ 'class' => 'form-control check' ]) }}
                        </div>
                    </div>
                    <div class="col-xs-5">
                <span class="help-block text-align">
                    <!--<span id="help_block_id">Use Contract And Financial Manager same as Agreement.</span>-->
                    <span id="help_block_id text-align">Use All Managers same as Agreement.</span>
                </span>
                    </div>
                </div>
                <input type="hidden" name="approval_process" id="approval_process" value="0">
            </div>

            <div class="approvalContainer" id="approvalContainer" style="display: none;">
                <div class="tableHeading" style="height: 60px;">
                    <label class="col-xs-3 control-label"></label>
                    <!-- <div class="col-md-3 col-sm-3 col-xs-3">
                            <strong>Approval Manager Type</strong>
                        </div> -->
                    <div class="col-md-3 col-sm-3 col-xs-3">
                        <strong>Approval Manager</strong>
                    </div>
                    <div class="col-md-2 col-sm-2 col-xs-2">
                        <strong>Initial Review Day</strong>
                    </div>
                    <div class="col-md-2 col-sm-2 col-xs-2">
                        <strong>Final Review Day</strong>
                    </div>
                    <div class="col-md-2 col-sm-2 col-xs-2">
                        <strong>Opt-in email</strong>
                    </div>

                </div>

                @for($i = 1; $i <= 6; $i++)
                    <div class="form-group">
                        <label class="col-xs-3 control-label">Approval Level {{$i}}</label>

                        {{--                        <div class="col-md-3 col-sm-3 col-xs-3">--}}
                        {{--                        {{ Form::select('approverTypeforLevel'.$i, $approval_manager_type, old('approverTypeforLevel'.$i,0), [ 'class' => 'form-control approval_type' ]) }}--}}
                        {{--                        </div>--}}

                        <div class="col-md-3 col-sm-3 col-xs-3 paddingLeft">
                            {{ Form::select('approval_manager_level'.$i, $users, old('approval_manager_level'.$i, 0), [ 'class' => 'form-control' ]) }}
                        </div>

                        <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                            {{ Form::selectRange('initial_review_day_level'.$i, 1, 85, old('initial_review_day_level'.$i,10), [ 'class' => 'form-control' ]) }}
                        </div>

                        <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                            {{ Form::selectRange('final_review_day_level'.$i, 1, 85, old('final_review_day_level'.$i,20), [ 'class' => 'form-control' ]) }}
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-2">
                            {{ Form::checkbox('emailCheck['.$i.']', 'level'.$i, old('emailCheck'.$i,1), [ 'class' => 'form-control check']) }}
                        </div>

                        <div class="col-md-3 col-sm-3 col-xs-3"></div>
                        <div class="col-md-9">
                            <p class="validationFieldErr">{!! $errors->first('approverTypeforLevel'.$i, '<p class="validation-error">:message</p>') !!}</p>
                            <p class="validationFieldErr">{!! $errors->first('approval_manager_level'.$i, '<p class="validation-error">:message</p>') !!}</p>
                            <p class="validationFieldErr">{!! $errors->first('initial_review_day_level'.$i, '<p class="validation-error">:message</p>') !!}</p>
                            <p class="validationFieldErr">{!! $errors->first('final_review_day_level'.$i, '<p class="validation-error">:message</p>') !!}</p>
                        </div>
                    </div>
                @endfor
            </div>

            <div class="approvalContainer" id="approvalContainer">
                <div class="bootstrap-duallistbox-container row moveonselect moveondoubleclick">
                    <div class="box1 col-md-5">
                        <div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
                            <span>Select Physicians</span>
                        </div>
                        <div style="padding:2% 0% 0% 0%">
                            <select multiple="multiple" id="physicianList" name="physicianList[]" class="form-control"
                                    title="" style="height: 254px;overflow-x: scroll;">
                                @if(Request::is('agreements/*'))
                                    @foreach($hospitals_physicians as $physician_obj)
                                        <option value="{{ $physician_obj->id }}_{{$physician_obj->practice_id}}">{{ $physician_obj->physician_name }}
                                            ( {{$physician_obj->practice_name}} )
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="button-box col-md-2">
                        <div style="padding:31% 28%">
                            <div style="padding:19%">
                                <input class="btn btn-default" type="button" id="btnRight" value="  >  ">
                            </div>
                            <div style="padding:20% 13%">
                                <input class="btn btn-default" type="button" id="btnRightAll" value="  >>  ">
                            </div>
                            <div style="padding:19%">
                                <input class="btn btn-default" type="button" id="btnLeft" value="  <  ">
                            </div>
                            <div style="padding:20% 13%">
                                <input class="btn btn-default" type="button" id="btnLeftAll" value="  <<  ">
                            </div>
                        </div>
                    </div>

                    <div class="box2 col-md-5">
                        <div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
                            <span>Selected Physicians</span>
                        </div>
                        <div style="padding:2% 0% 0% 0%">
                            <select multiple="multiple" id="selectedPhysicianList" name="selectedPhysicianList[]"
                                    class="form-control" title="" style="height: 254px;overflow-x: scroll;">
                                @if(Request::is('physicians/*'))
                                    @foreach($hospitals_physicians as $physician_obj)
                                        <option value="{{ $physician_obj->id }}_{{$physician_obj->practice_id}}"
                                                selected="true">{{ $physician_obj->physician_name }}
                                            ( {{$physician_obj->practice_name}} )
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <p class="help-block">
                        </p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label no-padding-top">Physician Opt-in email</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;
                    </div>
                    <div class="col-xs-1" style="width: 3.333333%;">
                        <!--<input type="checkbox" name="physician_emailCheck" class="physician_emailCheck" value="physician_emailCheck">-->
                        {{ Form::checkbox('physician_emailCheck', 1, old('physician_emailCheck',1), [ 'class' => 'form-control check' ]) }}
                    </div>
                </div>
                <div class="col-xs-5">
            <span class="help-block">
                <span id=""></span>
            </span>
                </div>
            </div>

            <div class="form-group" id="mandate_details_div">
                <label class="col-xs-2 control-label no-padding-top"> Mandate Log Details</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon"><i class="fa fa-info-circle" aria-hidden="true"
                                              data-toggle="tooltip" data-placement="top"
                                              title="When 'Yes' all logs entered must have details included with log to submit.">
                        </i>
                    </div>
                    <div class="right-field col-xs-4 no-padding-left">
                        {{ Form::select('mandate_details', array('0' => 'No', '1' => 'Yes'), old('mandate_details'), ['class' =>
                        'form-control']) }}
                    </div>
                </div>
            </div>
            <!-- custom action enable label starts -->
            <div class="form-group" id="custom_action_div">
                <label class="col-xs-2 control-label"> Custom Action Enable</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i id="custom_action_div_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When 'Checked' there is an option from the Activities drop down to create a custom action for use with that log entry. When not 'Checked' there is no ability for provider to add a custom action to log entry.">
                        </i>
                    </div>
                    <div style="margin-left:-12px;" class="col-xs-5 right-field">
                        {{ Form::checkbox('custom_action_enable',1,old('custom_action_enable',1), [ 'class' => 'form-control check' , 'id' => "custom_action_enable" ]) }}
                    </div>
                </div>
            </div>
            <!-- custom action enable label ends -->
            <div class="form-group" id="rate_selection">
                <label class="col-xs-2 control-label">On call Process</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When 'Orange' the contract will permit three rates with can be entered on any date.">
                        </i>
                    </div>
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            <!--<input id="on_off" name="on_off" type="checkbox" checked>-->
                            {{ Form::checkbox('on_off', 1, old('on_off'), ['id' => 'on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>

            <!--call-coverage-duration : added partial hours toggle for per-diem by 1254-->
            <div class="form-group" id="hours_selection">
                <label class="col-xs-2 control-label">Partial Hours</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When 'Orange' a hourly duration bar will be available for all entries to select 0-24 hours.">
                        </i>
                    </div>
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('partial_hours', 1, old('partial_hours'), ['id' => 'partial_hours']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>

                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>


        <?php
        $partial_hour_calculations_error = [];
        $partial_hour_selection_error = "";
        if (Session::has('on_call_rate_error')) {
            $partial_hour_calculations_error = Session::get('on_call_rate_error');
            $partial_hour_selection_error = $partial_hour_calculations_error[0];
        }
        ?>
        <!-- Per Diem with Uncompensated Days by 1254  -->


            <div class="form-group" id="hours_calculation_div" style="display:none;">
                <label class="col-xs-2 control-label no-padding-top"> Hours for Calculation</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="right-field col-xs-4 no-padding-left">
                        {{ Form::select('hours_calculation', $hours_calculations, old('hours_calculation'), ['class' =>
                        'form-control']) }}
                    </div>
                    <div class="col-xs-7 right-field no-padding-left">
                        <p class="validation-error" style="margin-bottom: 0px !important">{{ $partial_hour_selection_error }}</p>
                    </div>
                </div>

            </div>

            <!-- Burden of Call label -->
            <div class="form-group" id="burden_selection">
                <label class="col-xs-2 control-label">Burden of Call</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When 'Orange' the rates will be dependent on each other to select, e.g. must submit On-Call rate prior to selecting Called Back rate on the same day.">
                        </i>
                    </div>
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('burden_on_off', 1, old('burden_on_off'), ['id' => 'burden_on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>

                    </div>
                </div>
            </div>

            <!-- Holiday label start-->
            <div class="form-group" id="holiday_selection">
                <label class="col-xs-2 control-label">Holiday Action</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When 'Orange' Physicians can select the 'Holiday' activities while logging their hours on any day, weekday, weekend, or public holiday.">
                        </i>
                    </div>
                    <div id="toggle" class="input-group right-align">
                        <label class="switch">
                            {{ Form::checkbox('holiday_on_off', 1, old('holiday_on_off'), ['id' => 'holiday_on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>

                    </div>
                </div>
                <div class="col-xs-5 d-block col-xs-offset-2">
                <span class="help-block text-align">Note: Once turned 'On', Physicians can select the 'Holiday' activities while logging their hours on any day, weekday, weekend, or public holiday.
                </span>
                </div>
                <div class="col-xs-5"></div>
            </div>
            <!-- Holiday label end-->

            <!--Enter PSA logs by day option-->
            <div class="form-group" id="logs_by_day_div">
                <label class="col-xs-2 control-label">Enter Logs by Day</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        <div id="toggle" class="input-group right-field">
                            <label class="switch">
                                {{ Form::checkbox('enter_by_day', 1, old('enter_by_day',0), ['id' => 'enter_by_day']) }}
                                <div class="slider round"></div>
                                <div class="text"></div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5" style="margin-left:25px">
                <span class="help-block test-align">Note: By default, logs for Professional Services Agreement contracts are entered for the entire month. Enable this option to allow logs to be entered for individual days in the month.
            </span>

                </div>
                <div class="col-xs-5"></div>
            </div>

            <!--annual comp-->
            <div class="form-group" id="annual_comp_div">
                <label class="col-xs-2 control-label">Annual Comp</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="annual_comp_div_text">
                            {{ Form::text('annual_comp', old('annual_comp'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5" style="margin-left:160px">
                <span class="help-block text-align">
                    The Compensation rates must include the dollar amount followed by cents, for example
                    <strong>50000.00</strong>.
                </span>
                </div>

                <div class="col-xs-5 col-xs-offset-2"
                     id="annual_comp_error_div" style="padding-left: 42px;">{!! $errors->first('annual_comp', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp ninety-->
            <div class="form-group" id="annual_comp_ninety_div">
                <label class="col-xs-2 control-label">Annual Comp 90th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="annual_comp_ninety_div_text">
                            {{ Form::text('annual_comp_ninety', old('annual_comp_ninety'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_comp_ninety_error_div" style="margin-left: 25px;">{!! $errors->first('annual_comp_ninety', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu ninety-->
            <div class="form-group" id="wrvu_ninety_div">
                <label class="col-xs-2 control-label">wRVU 90th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="wrvu_ninety_div_text">
                            {{ Form::text('wrvu_ninety', old('wrvu_ninety'), [ 'class' => 'form-control' ]) }}

                        </div>
                    </div>
                </div>
                <div class="col-xs-5 col-xs-offset-2"
                     id="wrvu_ninety_error_div" style="padding-left: 42px;">{!! $errors->first('wrvu_ninety', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp seventy five-->
            <div class="form-group" id="annual_comp_seventy_five_div">
                <label class="col-xs-2 control-label">Annual Comp 75th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="annual_comp_seventy_five_div_text">
                            {{ Form::text('annual_comp_seventy_five', old('annual_comp_seventy_five'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5 col-xs-offset-2"
                     id="annual_comp_seventy_five_error_div" style="margin-left: 25px;">{!! $errors->first('annual_comp_seventy_five', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu seventy five-->
            <div class="form-group" id="wrvu_seventy_five_div">
                <label class="col-xs-2 control-label">wRVU 75th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="wrvu_seventy_five_div_text">
                            {{ Form::text('wrvu_seventy_five', old('wrvu_seventy_five'), [ 'class' => 'form-control' ]) }}

                        </div>
                    </div>
                </div>
                <div class="col-xs-5 col-xs-offset-2"
                     id="wrvu_seventy_five_error_div" style="padding-left: 42px;">{!! $errors->first('wrvu_seventy_five', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp fifty-->
            <div class="form-group" id="annual_comp_fifty_div">
                <label class="col-xs-2 control-label">Annual Comp 50th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="annual_comp_fifty_div_text">
                            {{ Form::text('annual_comp_fifty', old('annual_comp_fifty'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_comp_fifty_error_div" style="margin-left: 25px;">{!! $errors->first('annual_comp_fifty', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu fifty-->
            <div class="form-group" id="wrvu_fifty_div">
                <label class="col-xs-2 control-label">wRVU 50th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-6">
                        <div class="input-group right-field" id="wrvu_fifty_div_text">
                            {{ Form::text('wrvu_fifty', old('wrvu_fifty'), [ 'class' => 'form-control' ]) }}

                        </div>
                    </div>
                </div>
                <div class="col-xs-5 col-xs-offset-2"
                     id="wrvu_fifty_error_div" style="padding-left: 42px;">{!! $errors->first('wrvu_fifty', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wRVU Payments on/off-->
            <div class="approvalContainer" id="wrvu_payments_div">

                <div class="form-group">
                    <label class="col-xs-2 control-label">wRVU Payments</label>
                    <div class="col-xs-5">
                        <div id="toggle" class="input-group">
                            <label class="switch">
                                {{ Form::checkbox('wrvu_payments', 1, old('wrvu_payments',0), ['id' => 'wrvu_payments']) }}
                                <div class="slider round"></div>
                                <div class="text"></div>
                            </label>
                        </div>
                        <span class="help-block">Note: By enabling the wRVU payments option, wRVUs entered for each month will generate a payment. Leave disabled/off if using contract only for PSA monitoring.
                    </span>
                    </div>
                    <div class="col-xs-5"></div>
                </div>

                <div class="form-group" id="contract_psa_wrvu_rates_div">
                    <!--add wrvu payments structure-->
                    {{ Form::hidden('contract_psa_wrvu_rates_count',old('contract_psa_wrvu_rates_count',1),['id' => 'contract_psa_wrvu_rates_count']) }}
                    <div id="contract_psa_wrvu_rates">
                        @for($i = 0; $i < old('contract_psa_wrvu_rates_count',1); $i++ )
                            <div>
                                <div class="form-group wrvu-range">
                                    <label class="col-xs-2 control-label">wRVU Range {{ $i+1 }}</label>

                                    <div class="col-xs-5">
                                        {{ Form::text("contract_psa_wrvu_ranges".($i+1), old("contract_psa_wrvu_ranges".($i+1)), [ 'class' => 'form-control','id' => "contract_psa_wrvu_ranges".($i+1) ]) }}
                                        @if($i+1==1)
                                            <span class="help-block">
                                        Note: Enter the upper bound for the range. For example, 500 means wRVUs from 1-500 would pay at the rate below. Enter 9999999 for the last range.
                                    </span>
                                        @endif
                                    </div>
                                    <div class="col-xs-3">{!! $errors->first('contract_psa_wrvu_range'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                                </div>
                                <div class="form-group wrvu-rate">
                                    <label class="col-xs-2 control-label">Rate {{ $i+1 }}</label>

                                    <div class="col-xs-5">
                                        {{ Form::text("contract_psa_wrvu_rates".($i+1), old("contract_psa_wrvu_rates".($i+1)), [ 'class' => 'form-control','id' => "contract_psa_wrvu_rates".($i+1) ]) }}
                                    </div>
                                    <div class="col-xs-2">
                                        <button class="btn btn-primary btn-submit remove-wrvu-rate" type="button"> -
                                        </button>
                                    </div>
                                    <div class="col-xs-3">{!! $errors->first('contract_psa_wrvu_rates'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                                </div>
                            </div>
                            <hr>
                        @endfor
                    </div>
                    <button class="btn btn-primary btn-submit add-wrvu-rate" type="button">Add wRVU Range and Rate
                    </button>
                </div>

            </div>

            <!-- units -->
            <div class="form-group" id="units_div" style="display:none">
                <label class="col-xs-2 control-label">Units</label>
                <div class="col-xs-4 info-cls no-padding-right">
                    <div class="info-icon">
                        <i class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Free text box, can be any defined unit that is compensated at FMV rate.">
                        </i>
                    </div>
                    <div id="units_div_text" class="input-group right-field">
                        {{ Form::text('units', old('units'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5"
                     id="units_error_div">{!! $errors->first('units', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--min hours-->
            <div class="form-group" id="min_hours_div">
                <label id="lbl_min_hours" class="col-xs-2 control-label">Min Hours</label>
                <div class="v-align-center">
                    <div class="col-xs-5 info-cls">
                        <div class="info-icon"><i id="min_hours_div_tooltip" class="fa fa-info-circle"
                                                  aria-hidden="true"
                                                  data-toggle="tooltip" data-placement="top"
                                                  title="If contract has min hours enter them here if not you have to enter 0.">
                            </i>
                        </div>
                        <div class="input-group right-field" id="min_hours_div_text">
                            {{ Form::text('min_hours', old('min_hours'), [ 'class' => 'form-control' ]) }}
                        </div>
                    </div>
                    <div class="col-xs-5" id="min_hours_error_div">
                        {!! $errors->first('min_hours', '<p class="validation-error">:message</p>') !!}
                    </div>
                </div>
            </div>

            <!--Annual Max Payment-->
            <div class="form-group" id="annual_max_div" style="display:none;">
                <label class="col-xs-2 control-label no-padding-top">Annual Max Payment</label>
                <div class="info-cls col-xs-4 no-padding-left no-padding-right">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-12" style="padding-right: 8px;">
                        <div class="input-group right-field">
                            {{ Form::text('annual_max_payment', old('annual_max_payment'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_max_payment_error_div">{!! $errors->first('annual_max_payment', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--Annual Max Shifts Start-->
            <div class="form-group" id="annual_max_shifts_div" style="display:none;">
                <label class="col-xs-2 control-label">Annual Max Shifts</label>
                <div class="info-cls col-xs-4 ">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-12 no-padding-left" style="padding-right: 8px;">
                        <div class="input-group" id="annual_max_shifts_div_text">
                            {{ Form::text('annual_max_shifts', old('annual_max_shifts'), [ 'class' => 'form-control', 'maxlength' => 5, 'autocomplete' => "off" ]) }}
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_max_shifts_error_div">{!! $errors->first('annual_max_shifts', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <!--Annual Max Shifts End-->
            <!--Weekday Rate-->
            <div class="form-group" id="weekday_rate_div">
                <label class="col-xs-2 control-label">Weekday Rate</label>
                <div class="info-cls col-xs-4 no-padding-right">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-12 no-padding-left" style="padding-right: 8px;">
                        <div class="input-group">
                            {{ Form::text('weekday_rate', old('weekday_rate'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="weekday_rate_error_div">{!! $errors->first('weekday_rate', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!-- quaterly max hours fuctionality toggle -->
            <div class="form-group" id="quarterly_max_hours_div">
                <label id="lbl_Quarterly_max_hours" class="col-xs-2 control-label">Quarterly Max Hours</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon"><i id="lbl_Quarterly_max_hours_tooltip" class="fa fa-info-circle"
                                              aria-hidden="true"
                                              data-toggle="tooltip" data-placement="top"
                                              title="Max hours will be tallied on a yearly quarterly basis, regardless of payment frequency.">
                        </i></div>
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('quarterly_max_hours', 1, old('quarterly_max_hours',0), ['id' => 'quarterly_max_hours']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>

                </div>
                <div class="col-xs-5"></div>
            </div>

            <!--max hours-->
            <div class="form-group" id="max_hours_div">
                <label id="lbl_max_hours" class="col-xs-2 control-label">Max Hours</label>
                <div class="v-align-center">
                    <div class="col-xs-5 info-cls">
                        <div class="info-icon">
                            <i id="max_hours_div_tooltip" class="fa fa-info-circle" aria-hidden="true"
                               data-toggle="tooltip" data-placement="top"
                               title="Total hours available to enter for period based on payment frequency.">
                            </i>
                        </div>
                        <div class="input-group right-field" id="max_hours_div_text">
                            {{ Form::text('max_hours', old('max_hours'), [ 'class' => 'form-control' ]) }}
                        </div>
                    </div>
                    <div class="col-xs-5 error-cls"
                         id="max_hours_error_div">{!! $errors->first('max_hours', '<p class="validation-error">:message</p>') !!}</div>
                </div>
            </div>

            <!--annual max hours-->
            <div class="form-group" id="annual_max_hours_div">
                <label id="lbl_annual_max_hours" class="col-xs-2 control-label">Annual Max Hours</label>
                <div class="col-xs-4 info-cls no-padding-right">
                    <div class="info-icon">
                        <i id="annual_max_hours_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Max units allowed for the year based on the agreement frequency start date.">
                        </i>
                    </div>
                    <div class="input-group right-field" id="annual_max_hours_div_text">
                        {{ Form::text('annual_cap', old('annual_cap'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_max_hours_error_div">{!! $errors->first('annual_cap', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--Allow Log Over Max Hour ON/OFF-->
            <div class="form-group" id="log_over_max_hour_flag">
                <label class="col-xs-2 control-label"> Log Over Max Hour</label>
                <div class="col-xs-4 info-cls no-padding-right">
                    <div class="info-icon">
                        <i class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When 'Yes' contract will allow hours over max to be submitted but not compensated.">
                        </i>
                    </div>
                    <div class="right-field">
                        {{ Form::select('log_over_max_hour', array('0' => 'No', '1' => 'Yes'), old('log_over_max_hour'), ['class' =>
                        'form-control']) }}
                    </div>
                </div>
            </div>

            <!--Prior start date-->

            <div class="form-group" id="prior_start_date_div">
                <label class="col-xs-2 control-label">Prior Start Date</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-4 no-padding-right">
                        <div id="prior_start_date" class="input-group right-field">
                            {{ Form::text('prior_start_date', old('prior_start_date'), [ 'class' => 'form-control', 'id'=>'prior_start_date_field' ]) }}
                            <span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
                        </div>
                    </div>
                    <div class="col-xs-2">
                        {{ Form::checkbox('contract_prior_start_date_on_off', '0', old('default',0), ['id' => 'contract_prior_start_date_on_off','class' => 'form-control check' ]) }}
                    </div>
                </div>
            </div>

            <!--Prior Worked hours-->

            <div class="form-group" id="prior_worked_hours_div">
                <label id="lbl_prior_worked_hours" class="col-xs-2 control-label">Prior Worked Hours</label>
                <div class="col-xs-4 info-cls no-padding-right">
                    <div class="info-icon">
                        <i id="lbl_prior_worked_hours_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Hours to be considered towards annual max hours when contract goes live that other than the true renewal date.">
                        </i>
                    </div>
                    <div class="right-field">
                        {{ Form::text('prior_worked_hours', old('prior_worked_hours'), [ 'class' =>
                            'form-control' ]) }}
                    </div>

                </div>
            </div>

            <!--Prior Amount Paid-->

            <div class="form-group" id="prior_amount_paid_div">
                <label class="col-xs-2 control-label">Prior Amount Paid</label>
                <div class="col-xs-4 info-cls no-padding-right">
                    <div class="info-icon">
                        <i id="prior_amount_paid_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Dollars to be considered towards annual max hours when contract goes live that other than the true renewal date.">
                        </i>
                    </div>
                    <div class="right-field">
                        {{ Form::text('prior_amount_paid', old('prior_amount_paid'), [ 'class' =>
                            'form-control' ]) }}
                    </div>
                </div>
            </div>


            <!--Weekend Rate-->
            <div class="form-group" id="weekend_rate_div">
                <label class="col-xs-2 control-label">Weekend Rate</label>
                <div class="info-cls col-xs-4 no-padding-right">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-12 no-padding-left" style="padding-right: 8px;">
                        <div class="input-group">
                            {{ Form::text('weekend_rate', old('weekend_rate'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="weekend_rate_error_div">{!! $errors->first('weekend_rate', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--FMV Rate-->
            <div class="form-group" id="fmv_rate_div">
                <label class="col-xs-2 control-label" id="rateID">FMV Rate</label>
                <div class="v-align-center">
                    <div class="col-xs-5 info-cls" style="padding-left: 12px;">
                        <div class="info-icon" id="rateID_icon_fmv">
                            <i id="rateID_tooltip" class="fa fa-info-circle" aria-hidden="true"
                               data-toggle="tooltip" data-placement="top"
                               title="Hourly Rate.">
                            </i>
                        </div>
                        <div class="info-icon" id="rateID_icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div class="input-group right-field">
                            {{ Form::text('rate', old('rate'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                    <div class="col-xs-5 error-cls"
                         id="rate_error_div">{!! $errors->first('rate', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="col-xs-5 d-block col-xs-offset-2">
            <span class="help-block text-align">
            <span id="help_block_id ">The rate must include the dollar amount followed by cents, for example
                </span>
            <strong>50.75</strong>.
                </span>
                </div>
            </div>

            <!--Holiday Rate-->
            <div class="form-group" id="holiday_rate_div">
                <label class="col-xs-2 control-label">Holiday Rate</label>
                <div class="info-cls col-xs-4 no-padding-right">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-12 no-padding-left" style="padding-right: 8px;">
                        <div class="input-group right-field">
                            {{ Form::text('holiday_rate', old('holiday_rate'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5" id="holiday_rate_error_div">{!! $errors->first('holiday_rate', '<p class="validation-error">:message</p>') !!}</div>
                <div class="col-xs-5 d-block col-xs-offset-2">
                    <span class="help-block" style="padding-left:25px;">
                        <span id="help_block_id">The Holiday rate must include the dollar amount followed by cents, for example </span>
                        <strong>50.75</strong>.
                    </span>
                </div>
            </div>

            <!--On-Call Rate-->
            <div class="form-group" id="On_Call_rate_div">
                <label class="col-xs-2 control-label">On-Call Rate</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i id="On_Call_rate_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Any rate.">
                        </i>
                    </div>
                    <div class="input-group right-field">
                        {{ Form::text('On_Call_rate', old('On_Call_rate'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5" id="On_Call_rate_error_div">{!! $errors->first('On_Call_rate', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--Called Back Rate-->
            <div class="form-group" id="called_back_rate_div">
                <label class="col-xs-2 control-label">Called Back Rate</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i id="called_back_rate_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Any rate.">
                        </i>
                    </div>
                    <div class="input-group right-field">
                        {{ Form::text('called_back_rate', old('called_back_rate'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5" id="called_back_rate_error_div">{!! $errors->first('called_back_rate', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--Called In Rate-->
            <div class="form-group" id="called_in_rate_div">
                <label class="col-xs-2 control-label">Called In Rate</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i id="called_in_rate_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Any rate.">
                        </i>
                    </div>
                    <div class="input-group right-field">
                        {{ Form::text('called_in_rate', old('called_in_rate'), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5" id="called_in_rate_error_div">{!! $errors->first('called_in_rate', '<p class="validation-error">:message</p>') !!}</div>
                <div class="col-xs-5 d-block col-xs-offset-2">
                    <span class="help-block text-align">
                        <span id="help_block_id ">The rate's must include the dollar amount followed by cents, for example </span>
                        <strong>50.75</strong>.
                    </span>
                </div>

            </div>

        <?php
        $on_call_rate_count = 1;
        $range_start_days = range(0, 90);
        unset($range_start_days[0]);

        $uncompensated_error = [];
        if (Session::has('on_call_rate_error')) {
            $uncompensated_error = Session::get('on_call_rate_error');
        }
        ?>
        {{  logger($uncompensated_error) }}
        <!-- //Per Diem with Uncompensated Days by 1254  -->
            {{ Form::hidden('on_call_rate_count',old('on_call_rate_count',$on_call_rate_count),['id' => 'on_call_rate_count']) }}
            <div id="on_call_uncompensated_rate">

                @for($i = 0; $i < old('on_call_rate_count',$on_call_rate_count); $i++ )
                    <div id="on-call-rate-div{{$i+1}}">
                        <div class="on-call-rate col-xs-12 no-padding-left no-padding-right">
                            <label class="col-xs-2 control-label" id="rate-label{{$i+1}}">On Call
                                Rate {{ $i+1 }}</label>
                            <div class="col-xs-4 info-cls">
                                <div class="info-icon">
                                    <i style="margin-left:-8px;" class="fa fa-info-circle" aria-hidden="true"
                                       data-toggle="tooltip" data-placement="top"
                                       title="Allows you to add different amount of rates AFTER X amount of days e.g. days 1-5 pay  rate, On Call Rate 2 - days 6-31 pay rate."></i>
                                </div>
                                <div class="col-xs-12 form-group no-padding-left no-margin-left no-margin-bottom"
                                     style="padding-right: 8px;">
                                    <div class="input-group right-field">
                                        {{ Form::text('rate'.($i+1), old('rate'.($i+1)), [ 'class' => 'form-control','id' => "rate".($i+1),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xs-6 form-group">
                                <label class="col-xs-3 control-label no-padding-top"
                                       style="width:20%;">Days
                                    Entered:</label>
                                <div class="col-xs-3"
                                     style="width: 24%;">{{ Form::select('start_day',$range_start_days , old('start_day'), ['class' =>'form-control','id'=>'start_day'.($i+1), 'disabled' => true] ) }}</div>
                                {{ Form::hidden('start_day_hidden'.($i+1)) ,'1',1,['id' => 'start_day_hidden'.($i+1)] }}
                                <div class="col-xs-3"
                                     style="width: 24%;">{{ Form::select('end_day'.($i+1),$range_start_days , old('end_day'), ['class' =>'form-control','id'=>'end_day'.($i+1), 'onchange' => 'rangechange( '. ($i+1) .' )']) }}</div>
                                <div class="col-xs-2">
                                    <button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate"
                                            id="btn-remove-uncompensated{{$i+1}}" value={{$i+1}} type="button"
                                            onClick="removeRangeCustom(this);"> -
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 on-call-rate">
                            @foreach($uncompensated_error as $key=>$value)
                                @if($key == ($i+1))
                                    <div class="col-xs-12"><p class="validation-error"
                                                              style="margin-left: 145px !important;margin-top: -18px;">{{ $value }}</p>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                    </div>
                @endfor
            </div>
            <button class="btn btn-primary btn-submit add-on-call-uncompensated-rate" id="add-uncompensaed-btn"
                    type="button" onClick="addRangeCustom()">Add On Call Rate
            </button>

            <!-- state attestations monthly toggle -->
            <div class="form-group" id="state_attestations_monthly_div" style="display:none">
                <label id="lbl_Quarterly_max_hours" class="col-xs-2 control-label">State Attestations Monthly</label>

                <div class="col-xs-5 info-cls">
                    <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('state_attestations_monthly', 1, old('state_attestations_monthly',0), ['id' => 'state_attestations_monthly']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>

            <!-- state attestations annually toggle -->
            <div class="form-group" id="state_attestations_annually_div" style="display:none">
                <label id="lbl_Quarterly_max_hours" class="col-xs-2 control-label">State Attestations Annually</label>

                <div class="col-xs-5 info-cls">
                    <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('state_attestations_annually', 1, old('state_attestations_annually',0), ['id' => 'state_attestations_annually']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>

            <!--Contract Deadline Option-->
            <div class="form-group">
                <label class="col-xs-2 control-label no-padding-top">Contract Deadline Option</label>
                <div class="col-xs-5 info-cls">
                    <div class="info-icon">
                        <i id="contract_deadline_on_off_tooltip" class="fa fa-info-circle" aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is 90 days .">
                        </i>
                    </div>
                    <div class="input-group" id="toggle">
                        <label class="switch">
                            {{ Form::checkbox('contract_deadline_on_off', 1, old('contract_deadline_on_off',0), ['id' => 'contract_deadline_on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-9 d-block col-xs-offset-2 ">
                <span class="help-block text-align">Note: By enabling contract deadline option, predefined <br> contract deadline values will be overwritten by Deadline Days <br> value.
                </span>
                </div>

                <div class="col-xs-5"></div>
            </div>
            <div class="form-group" id="deadline_days_div">
                <label class="col-xs-2 control-label">Deadline Days</label>
                <div class="info-cls">
                    <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5" style="margin-left: 6px;">
                    {{ Form::input('number','deadline_days', old('deadline_days'), [ 'class' => 'form-control','id' => 'deadline_days','min'=> 1 ]) }}
                </div>
                <div class="col-xs-5">{!! $errors->first('deadline_Days', '<p class="validation-error">:message</p>') !!}</div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label"> Upload Contract Copy</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        <span class="help-block">Note: Please upload pdf files only.</span>
                        <!--<input class="form-control" type="file" name="upload_file" id="upload_file">-->
                        {{ Form::file('upload_contract_copy_1', array('id' => 'upload_contract_copy_1' , 'type' => 'file','accept' => '.pdf','class' => 'form-control' )) }}
                        {!! $errors->first('upload_contract_copy_1', '<p class="validation-error">:message</p>') !!}
                        {{ Form::file('upload_contract_copy_2', array('id' => 'upload_contract_copy_2' , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                        {!! $errors->first('upload_contract_copy_2', '<p class="validation-error">:message</p>') !!}
                        {{ Form::file('upload_contract_copy_3', array('id' => 'upload_contract_copy_3' , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                        {!! $errors->first('upload_contract_copy_3', '<p class="validation-error">:message</p>') !!}
                        {{ Form::file('upload_contract_copy_4', array('id' => 'upload_contract_copy_4' , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                        {!! $errors->first('upload_contract_copy_4', '<p class="validation-error">:message</p>') !!}
                        {{ Form::file('upload_contract_copy_5', array('id' => 'upload_contract_copy_5' , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                        {!! $errors->first('upload_contract_copy_5', '<p class="validation-error">:message</p>') !!}
                        {{ Form::file('upload_contract_copy_6', array('id' => 'upload_contract_copy_6' , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                        {!! $errors->first('upload_contract_copy_6', '<p class="validation-error">:message</p>') !!}

                    </div>
                </div>
            </div>

            <!--use agreements dates option-->
            <div class="form-group">
                <label class="col-xs-2 control-label">Use Agreements Dates</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        <div class="input-group right-field">
                            {{ Form::checkbox('default_dates', 1, old('default_dates',1), [ 'class' => 'form-control check' ]) }}</label>
                        </div>
                    </div>

                </div>
                <div class="col-xs-5">
                    <span class="help-block text-align">Note: By enabling contract end date and valid upto dates will be same as agreement end date and valid upto date.
                </span>
                </div>
                <div class="col-xs-5"></div>
            </div>
            <!-- manual end date label starts -->
            <div class="form-group">
                <label class="col-xs-2 control-label">Manual End Date</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        <div id="manual_end_date" class="input-group">
                            {{ Form::text('manual_end_date', old('manual_end_date'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('manual_end_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <!-- manual end date label ends -->
            <!-- valid upto date label starts -->
            <div class="form-group">
                <label class="col-xs-2 control-label">Manual Valid Upto Date</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        <div id="valid_upto_date" class="input-group">
                            {{ Form::text('valid_upto_date', old('valid_upto_date'), [ 'class' => 'form-control' ]) }}
                            <span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('valid_upto_date', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <!-- valid upto date label ends -->

            <!-- Receipient start -->
            <div class="form-group" id="div_receipient1" style="display:none">
                <label class="col-xs-2 control-label">Recipient #1</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        {{ Form::text('receipient1', old('receipient1'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('receipient1', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group" id="div_receipient2" style="display:none">
                <label class="col-xs-2 control-label">Recipient #2</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        {{ Form::text('receipient2', old('receipient2'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('receipient2', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <div class="form-group" id="div_receipient3" style="display:none">
                <label class="col-xs-2 control-label">Recipient #3</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5">
                        {{ Form::text('receipient3', old('receipient3'), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('receipient3', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <!-- Receipient end -->

            <?php
            // if($invoice_type == 1){
            //     $note_count = App\InvoiceNote::CONTRACTCOUNT;
            // } else {
            //     $note_count = 1;
            // }
            $note_count = 1;
            ?>

        <!--add invoice notes-->
            {{ Form::hidden('note_count',old('note_count',$note_count),['id' => 'note_count']) }}
            <div id="notes">
                @for($i = 0; $i < old('note_count',$note_count); $i++ )
                    <div class="form-group invoive-note">
                        <label class="col-xs-2 control-label">Invoice Note {{ $i+1 }}</label>
                        <div class="info-cls">
                            <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                            <div class="col-xs-6">
                                {{ Form::textarea("note".($i+1), old("note".($i+1)), [ 'class' => 'form-control','id' => "note".($i+1),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                            </div>

                            <div class="col-xs-2">
                                <button class="btn btn-primary btn-submit remove-note" type="button"
                                        style="margin-right:65px"> -
                                </button>
                            </div>
                        </div>
                        <div class="col-xs-3">{!! $errors->first('note'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                    </div>
                @endfor
            </div>
            <!--//Prevent form submission-->
            <a class="btn btn-primary btn-submit add-note" type="button">Add Invoice Note</a>
        </div>
        <!-- Action Redesign by 1254 -->

        <div class="container" style="width: 100%;padding: 0px 0px 0px 0px;margin: 0px 0px 0px 0px;"
             id="action_category">

            <div id="activities" class="panel panel-default">
                <!-- //Action-Redesign by 1254 : 12022020 -->
                <div class="panel-heading"
                     style="height: 60px !important;padding-top: 20px !important;font-size: 16px !important;font-weight: 600 !important;">
                    <span style="float:left;">Activities</span>

                    <div style="float:left; width:65%; padding: 0px 1% 0px 1%;"><span style="float:left;"> <button
                                    type="button" id="sorting" name="sorting" class="btn btn-primary"
                                    style="margin-right:0%; float: right;margin-top: -8px; width:65px;float: right"
                                    data-toggle="modal" data-target="#contract_sorting_modal">Sort</button></span></div>
                    <div id="expected-hours" style="float:left; width:25%"><span style="float:left; width:65%"> Expected Hours:  </span>
                        <span>  {{ Form::text('hours','0.00', ['class' => 'form-control','style'=>'width:65px;float: right;margin-top: -8px;' ,'id' => 'action_value' ]) }}  </span>
                    </div>
                    {!! $errors->first('hours', '<p class="validation-error" style="margin-top: -4px;float: right;margin-right:10px">:message</p>') !!}
                </div>
                <div class="row">
                    <div class="col-md-3" style="width: 100%;">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default">

                                <div id="categories-without-rehab">
                                    @foreach($categories_except_rehab as $category)
                                        <div class="panel panel-default">
                                            <div class="panel-heading1" role="tab" id="{{$category->id}}"
                                                 name="action-div" value="{{$category->id}}">
                                                <input type="hidden" name="categoryid" value="{{$category->id}}">
                                                <h4 class="panel-title1">

                                                    <a class="collapsed" data-toggle="collapse"
                                                       style="color:black;font-weight:600;text-decoration: none;margin-left: 10px;"
                                                       href="#category_{{$category->id}}" aria-expanded="false"
                                                       aria-controls="category_{{$category->id}}">
                                                        <div class="collapse-level-two-circle"></div>
                                                        {{ $category->name}}

                                                    </a>

                                                    <button class="hidden" type="button" id="sorting_{{$category->id}}"
                                                            value="{{$category->id}}" name="sorting"
                                                            class="btn btn-primary" style="margin-right:1%"
                                                            data-toggle="modal" data-target="#contract_sorting_modal">
                                                        Sort
                                                    </button>

                                                </h4>

                                            </div>
                                            <?php
                                            $category_show = false;
                                            $action_error_arr = [];
                                            if (Session::has('action_error')) {
                                                $action_error_arr = Session::get('action_error');
                                            }
                                            ?>

                                            <div id="category_{{$category->id}}"
                                                 class="panel-collapse {{ (isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? 'in' : '' }} collapse"
                                                 role="tabpanel" aria-labelledby="headingOne" value="{{$category->id}}">
                                                <div class="panel-body">
                                                    @foreach($actions as $action)

                                                        @if($action->category_id == $category->id )
                                                            <div class="col-xs-4" class="action-container">
                                                                @if($action->name!="")
                                                                    {{ Form::checkbox('actions[]', $action->id,'',['class' => 'actionCheckbox'] )  }}
                                                                    <span title="{{$action->name}}"
                                                                          class="actionWrap">{{ $action->name  }} </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach

                                                    @if(is_super_user()||is_super_hospital_user())
                                                        <div class="col-xs-12">
                                                            {{ Form::hidden('[custom_count]',old('custom_count',1),['id' => 'custom_count_'.$category->id]) }}
                                                            <div id="customaction_{{$category->id}}"
                                                                 style="padding-top: 35px;">
                                                            <!-- <div class="form-group invoive-note" id="custom_action_div_{{$category->id}}_1"  style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}" >
                                  <label class="col-xs-2">Custom Action </label> -->

                                                                @if(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0)
                                                                    @foreach( $action_error_arr['customaction_name_'.$category->id] as $action_name=>$flag)
                                                                        @if($flag == true)
                                                                            <div class="form-group"
                                                                                 id="custom_action_div_{{preg_replace('/\s+/', '', $action_name)}}_{{$category->id}}_1"
                                                                                 style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
                                                                                <label class="col-xs-2">Custom
                                                                                    Action </label>
                                                                                <div class="col-xs-5">
                                                                                    <input type="text"
                                                                                           name="customaction_name_{{$category->id}}[]"
                                                                                           class="form-control"
                                                                                           value="{{$action_name}}"/>
                                                                                    <p class="validation-error">Action
                                                                                        already exist under this
                                                                                        category.</p>
                                                                                </div>
                                                                                <div class="col-xs-2">
                                                                                    <button type="button" name="remove"
                                                                                            class="btn btn-primary btn-submit btn_remove"
                                                                                            referId="custom_action_div_{{preg_replace('/\s+/', '', $action_name)}}_{{$category->id}}_1">
                                                                                        -
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="form-group"
                                                                                 id="custom_action_div_{{$category->id}}_1"
                                                                                 style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
                                                                                <label class="col-xs-2">Custom
                                                                                    Action </label>
                                                                                <div class="col-xs-5">
                                                                                    <input type="text"
                                                                                           name="customaction_name_{{$category->id}}[]"
                                                                                           class="form-control"
                                                                                           value="{{$action_name}}"/>
                                                                                </div>
                                                                                <div class="col-xs-2">
                                                                                    <button type="button" name="remove"
                                                                                            class="btn btn-primary btn-submit btn_remove"
                                                                                            referId="custom_action_div_{{$category->id}}_1">
                                                                                        -
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    @endforeach
                                                                @else
                                                                    <div class="form-group"
                                                                         id="custom_action_div_{{$category->id}}_1"
                                                                         style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
                                                                        <label class="col-xs-2">Custom Action </label>
                                                                        <div class="col-xs-5">
                                                                            <input type="text"
                                                                                   name="customaction_name_{{$category->id}}[]"
                                                                                   class="form-control"/>
                                                                        </div>
                                                                        <div class="col-xs-2">
                                                                            <button type="button" name="remove"
                                                                                    class="btn btn-primary btn-submit btn_remove"
                                                                                    referId="custom_action_div_{{$category->id}}_1">
                                                                                -
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                            @endif

                                                            <!-- </div> -->
                                                                <button class="btn btn-primary btn-submit add_custom"
                                                                        id="add_custom_{{$category->id}}" type="button"
                                                                        addcustrefId="{{$category->id}}">Add Custom
                                                                    Action
                                                                </button>
                                                            </div>
                                                        </div>

                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>

                                <div id="categories-with-rehab">
                                    @foreach($categories_for_rehab as $category)
                                        <div class="panel panel-default">
                                            <div class="panel-heading1" role="tab" id="{{$category->id}}"
                                                 name="action-div" value="{{$category->id}}">
                                                <input type="hidden" name="categoryid" value="{{$category->id}}">
                                                <h4 class="panel-title1">

                                                    <a class="collapsed" data-toggle="collapse"
                                                       style="color:black;font-weight:600;text-decoration: none;margin-left: 10px;"
                                                       href="#category_{{$category->id}}" aria-expanded="false"
                                                       aria-controls="category_{{$category->id}}">
                                                        <div class="collapse-level-two-circle"></div>
                                                        {{ $category->name}}

                                                    </a>

                                                    <button class="hidden" type="button" id="sorting_{{$category->id}}"
                                                            value="{{$category->id}}" name="sorting"
                                                            class="btn btn-primary" style="margin-right:1%"
                                                            data-toggle="modal" data-target="#contract_sorting_modal">
                                                        Sort
                                                    </button>

                                                </h4>

                                            </div>
                                            <?php
                                            $category_show = false;
                                            $action_error_arr = [];
                                            if (Session::has('action_error')) {
                                                $action_error_arr = Session::get('action_error');
                                            }
                                            ?>

                                            <div id="category_{{$category->id}}"
                                                 class="panel-collapse {{ (isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? 'in' : '' }} collapse"
                                                 role="tabpanel" aria-labelledby="headingOne" value="{{$category->id}}">
                                                <div class="panel-body">
                                                    @foreach($actions as $action)

                                                        @if($action->category_id == $category->id )
                                                            <div class="col-xs-4" class="action-container">
                                                                @if($action->name!="")
                                                                    {{ Form::checkbox('actions[]', $action->id,'',['class' => 'actionCheckbox'] )  }}
                                                                    <span title="{{$action->name}}"
                                                                          class="actionWrap">{{ $action->name  }} </span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endforeach

                                                    @if(is_super_user()||is_super_hospital_user())
                                                        <div class="col-xs-12">
                                                            {{ Form::hidden('[custom_count]',old('custom_count',1),['id' => 'custom_count_'.$category->id]) }}
                                                            <div id="customaction_{{$category->id}}"
                                                                 style="padding-top: 35px;">
                                                            <!-- <div class="form-group invoive-note" id="custom_action_div_{{$category->id}}_1"  style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}" >
                                  <label class="col-xs-2">Custom Action </label> -->

                                                                @if(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0)
                                                                    @foreach( $action_error_arr['customaction_name_'.$category->id] as $action_name=>$flag)
                                                                        @if($flag == true)
                                                                            <div class="form-group"
                                                                                 id="custom_action_div_{{preg_replace('/\s+/', '', $action_name)}}_{{$category->id}}_1"
                                                                                 style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
                                                                                <label class="col-xs-2">Custom
                                                                                    Action </label>
                                                                                <div class="col-xs-5">
                                                                                    <input type="text"
                                                                                           name="customaction_name_{{$category->id}}[]"
                                                                                           class="form-control"
                                                                                           value="{{$action_name}}"/>
                                                                                    <p class="validation-error">Action
                                                                                        already exist under this
                                                                                        category.</p>
                                                                                </div>
                                                                                <div class="col-xs-2">
                                                                                    <button type="button" name="remove"
                                                                                            class="btn btn-primary btn-submit btn_remove"
                                                                                            referId="custom_action_div_{{preg_replace('/\s+/', '', $action_name)}}_{{$category->id}}_1">
                                                                                        -
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="form-group"
                                                                                 id="custom_action_div_{{$category->id}}_1"
                                                                                 style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
                                                                                <label class="col-xs-2">Custom
                                                                                    Action </label>
                                                                                <div class="col-xs-5">
                                                                                    <input type="text"
                                                                                           name="customaction_name_{{$category->id}}[]"
                                                                                           class="form-control"
                                                                                           value="{{$action_name}}"/>
                                                                                </div>
                                                                                <div class="col-xs-2">
                                                                                    <button type="button" name="remove"
                                                                                            class="btn btn-primary btn-submit btn_remove"
                                                                                            referId="custom_action_div_{{$category->id}}_1">
                                                                                        -
                                                                                    </button>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    @endforeach
                                                                @else
                                                                    <div class="form-group"
                                                                         id="custom_action_div_{{$category->id}}_1"
                                                                         style="{{(isset($action_error_arr['customaction_name_'.$category->id]) && count($action_error_arr['customaction_name_'.$category->id]) > 0) ? '' : 'display:none'}}">
                                                                        <label class="col-xs-2">Custom Action </label>
                                                                        <div class="col-xs-5">
                                                                            <input type="text"
                                                                                   name="customaction_name_{{$category->id}}[]"
                                                                                   class="form-control"/>
                                                                        </div>
                                                                        <div class="col-xs-2">
                                                                            <button type="button" name="remove"
                                                                                    class="btn btn-primary btn-submit btn_remove"
                                                                                    referId="custom_action_div_{{$category->id}}_1">
                                                                                -
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                            @endif

                                                            <!-- </div> -->
                                                                <button class="btn btn-primary btn-submit add_custom"
                                                                        id="add_custom_{{$category->id}}" type="button"
                                                                        addcustrefId="{{$category->id}}">Add Custom
                                                                    Action
                                                                </button>
                                                            </div>
                                                        </div>

                                                    @endif
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


            </div>
        </div>

        <div id="per-diem-activities" class="per-diem-activities"
             style="width: 100%;padding: 0px 0px 0px 0px;margin: 0px 0px 0px 0px;" id="action_category">

            <div id="activities" class="panel panel-default">
                <div class="panel-heading">Activities
                </div>
                <div class="panel-body">
                    <div class="row">
                        @foreach ($per_diem_actions as $action)

                            @if( $action->payment_type_id == App\PaymentType::PER_DIEM && $action->action_type_id != 5)
                                <?php $action_name_withoutspace = preg_replace('/\s+/', '_', $action->name); ?>
                                <div class="col-xs-12" id="div_action_{{$action_name_withoutspace}}">
                                    <div class="form-group">
                                        <div class="col-xs-4">
                                            <!-- issue fixes for set default actions checked for per-diem  -->
                                            {{ Form::checkbox('actions[]', $action->id, $action->checked) }}
                                            {{ $action->name }}
                                        </div>
                                        <div class="col-xs-2">
                                            {{ Form::label($action->field, $action->hours)}}
                                            {{ Form::hidden($action->field, $action->hours) }}
                                        </div>
                                        <div class="col-xs-4">
                                            {{ Form::text('name'.$action->id,$action->changeName,array('class'=>'form-control','id'=>'name'. $action->id))}}

                                        </div>
                                    </div>

                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            </div>
        </div>

        <div id="per-diem-uncompensated-activities" class="per-diem-uncompensated-activities"
             style="width: 100%;padding: 0px 0px 0px 0px;margin: 0px 0px 0px 0px;" id="action_category">

            <div id="activities" class="panel panel-default">
                <div class="panel-heading">Activities
                </div>
                <div class="panel-body">
                    <div class="row">
                        @foreach ($per_diem_uncompensated_action as $action)

                            @if( $action->payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS && $action->action_type_id != 5)
                                <?php $action_name_withoutspace = preg_replace('/\s+/', '_', $action->name); ?>
                                <div class="col-xs-12" id="div_action_{{$action_name_withoutspace}}">
                                    <div class="form-group">
                                        <div class="col-xs-4">
                                            <!-- issue fixes for set default actions checked for per-diem  -->
                                        {{ Form::checkbox('actions[]', $action->id, $action->checked, array('id'=>'uncompensated_action','disabled')) }}
                                        <!-- {{ Form::checkbox('actions[]', $action->id, $action->checked) }} -->
                                            {{ $action->name }}
                                        </div>
                                        <div class="col-xs-2">
                                            {{ Form::label($action->field, $action->hours)}}
                                            {{ Form::hidden($action->field, $action->hours) }}
                                        </div>
                                        <div class="col-xs-4">
                                            {{ Form::text('name'.$action->id,$action->changeName,array('class'=>'form-control','id'=>'name'. $action->id))}}

                                        </div>
                                    </div>

                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
        <div class="panel-footer clearfix">
            <button type="button" id="contract_confirmation" name="contract_confirmation" class="btn btn-primary"
                    data-toggle="modal" data-target="#contract_submit_confirmation_modal">Submit
            </button>
            <button class="btn btn-primary btn-sm btn-submit hidden" onClick="enabledropdown()" type="submit"
                    id="submit_contract">Submit
            </button>
        </div>

        {{ Form::close() }}

    <!-- Modal Contract submit confirmation popup start-->
        <div class="modal fade" id="contract_submit_confirmation_modal" data-backdrop="static" data-keyboard="false"
             tabindex="-1" role="dialog" aria-labelledby="contract_submit_confirmation_title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" style="font-weight: bold;">Contract Physicians</h4>
                    </div>
                    <div class="modal-body">
                        <div style="color:black;font-weight:600;text-decoration: none; padding:2% 0% 0% 0%">
                            <span>Selected Physicians</span>
                        </div>
                        <div style="padding:2% 0% 0% 0%">
                            <select multiple="multiple" id="selectedPhysicianListShow"
                                    name="selectedPhysicianListShow[]" class="form-control" title=""
                                    style="height: 254px;overflow-x: scroll;" disabled>
                                @if(Request::is('physicians/*'))
                                    @foreach($hospitals_physicians as $physician_obj)
                                        <option value="{{ $physician_obj->id }}_{{$physician_obj->practice_id}}"
                                                selected="true">{{ $physician_obj->physician_name }}
                                            ( {{$physician_obj->practice_name}} )
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="button" id="btn_contract_submit" class="btn btn-primary">Save Contract</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Contract submit confirmation popup end-->

        <!-- Add modal contract sort popup start-->
        <div class="modal fade" id="contract_sorting_modal" data-backdrop="static" data-keyboard="false" tabindex="-1"
             role="dialog" aria-labelledby="contract_sorting_modal_title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" style="font-weight: bold;">Selected Duties</h4>
                    </div>
                    <div class="modal-body">
                        <ul class='ul_activities' id="ul_li_activities" class="ul_li_activities"
                            style="width: 100%; height: 200px; overflow-y: auto; list-style-type:none; padding-left: 0px;">

                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" id="btn_sorting_submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Added modal popup end-->
        @endsection
        @section('scripts')
            <script type="text/javascript">
                const pageFailedAtValidation = document.referrer === window.location.href;

                $(document).ready(function () {

                    $("#btn_contract_submit").click(function (e) {
                        $('#submit_contract').trigger('click');
                    });

                    var categories_count = $('#categories_count').val();

                    if (categories_count > 0) {
                        for (var j = 1; j <= categories_count; j++) {
                            var activity_count = 0;
                            $('#category_' + j + ' input[type=checkbox]').each(function () {
                                activity_count++;
                            });

                            if (activity_count > 1) {
                                $('#sorting_' + j).show();
                            } else {
                                $('#sorting_' + j).hide();
                            }
                        }
                    }

                    var sorting_contract_array = [];
                    $('#sorting').click(function () {

                        $('ul.ul_activities').empty();
                        for (var p = 1; p <= categories_count; p++) {
                            // $('#sorting_category_id').val(this.value);
                            $('#category_' + p).find('input[type=checkbox]:checked').each(function () {
                                $('#ul_li_activities').append('<li value=' + $(this).val() + ' category_id= ' + p + '>' + $(this).parent().find('span').html() + '</li>');
                                $('#ul_li_activities li').css({
                                    'padding': '5px',
                                    'margin': '5px',
                                    'border': '1px solid #ccc',
                                    'border-radius': '6px'
                                });
                            });
                        }
                    });

                    var sorting_contract_array = [];

                    $('#btn_sorting_submit').click(function () {
                        $('#ul_li_activities li').each(function (i) {
                            var index = i + 1;
                            var action_id = $(this).val();
                            var category_id = $(this).attr("category_id");
                            sorting_contract_array.push({
                                category_id: category_id,
                                action_id: action_id,
                                sort_order: index
                            });
                        });

                        $('#sorting_contract_data').val('');
                        $('#sorting_contract_data').val(JSON.stringify(sorting_contract_array));
                        $('#contract_sorting_modal').modal('hide');
                    });

                    $('.btn-submit').click(function () {
                        if ($('[name=payment_type]').val() == 8) {
                            $('#units_error_div').html('');
                            $('#units_error_div').removeClass('validation-error');
                            $('#min_hours_error_div').html('');
                            $('#min_hours_error_div').removeClass('validation-error');
                            $('#max_hours_error_div').html('');
                            $('#max_hours_error_div').removeClass('validation-error');
                            $('#annual_max_hours_error_div').html('');
                            $('#annual_max_hours_error_div').removeClass('validation-error');
                            $('#rate_error_div').html('');
                            $('#rate_error_div').removeClass('validation-error');
                            valid = false;

                            if ($('input[name="units"]').val() == '' && $('input[name="min_hours"]').val() == '' && $('input[name="max_hours"]').val() == '' && $('input[name="annual_cap"]').val() == '' && $('input[name="rate"]').val() == '') {
                                $('#units_error_div').html('The units field is required.');
                                $('#units_error_div').addClass('validation-error');
                                $('#min_hours_error_div').html('The min units field is required.');
                                $('#min_hours_error_div').addClass('validation-error');
                                $('#max_hours_error_div').html('The max units field is required.');
                                $('#max_hours_error_div').addClass('validation-error');
                                $('#annual_max_hours_error_div').html('The annual max units field is required.');
                                $('#annual_max_hours_error_div').addClass('validation-error');
                                $('#rate_error_div').html('The rate field is required.');
                                $('#rate_error_div').addClass('validation-error');
                                $(window).scrollTop(0);
                                return false;
                            } else {
                                if ($('input[name="units"]').val() == '') {
                                    $('#units_error_div').html('The units field is required.');
                                    $('#units_error_div').addClass('validation-error');
                                    valid = true;
                                }

                                if ($('input[name="min_hours"]').val() == '') {
                                    $('#min_hours_error_div').html('The min units field is required.');
                                    $('#min_hours_error_div').addClass('validation-error');
                                    valid = true;
                                }

                                if ($('input[name="max_hours"]').val() == '') {
                                    $('#max_hours_error_div').html('The max units field is required.');
                                    $('#max_hours_error_div').addClass('validation-error');
                                    valid = true;
                                }

                                if ($('input[name="annual_cap"]').val() == '') {
                                    $('#annual_max_hours_error_div').html('The annual max units field is required.');
                                    $('#annual_max_hours_error_div').addClass('validation-error');
                                    valid = true;
                                }

                                if ($('input[name="rate"]').val() == '') {
                                    $('#rate_error_div').html('The rate field is required.');
                                    $('#rate_error_div').addClass('validation-error');
                                    valid = true;
                                }

                                if (valid) {
                                    $(window).scrollTop(0);
                                    return false;
                                }
                            }
                        } else if ($('[name=payment_type]').val() == 9) {
                            var frequency_type = $('[name=agreement_pmt_frequency]').val();
                            var payment_type = $('[name=payment_type]').val();
                            valid = false;
                            if (payment_type == 9 && frequency_type != 1) {
                                $('#agreement_error_div').html('Please select monthly frequency agreement.');
                                $('#agreement_error_div').addClass('validation-error');
                                valid = true;
                            }

                            if (valid) {
                                $(window).scrollTop(0);
                                return false;
                            }
                        }
                    });
                });

                $(function () {
                    $('[name=contract_type]').change(function () {
                        if ($(this).val() == '20') {
                            $('#state_attestations_monthly_div').show();
                            $('#state_attestations_annually_div').show();
                            $('#div_receipient1').show();
                            $('#div_receipient2').show();
                            $('#div_receipient3').show();
                            $('#supervision_type_div').show();
                        } else {
                            $('#state_attestations_monthly_div').hide();
                            $('#state_attestations_annually_div').hide();
                            $('#div_receipient1').hide();
                            $('#div_receipient2').hide();
                            $('#div_receipient3').hide();
                            $('#supervision_type_div').hide();
                        }
                    });
                });

                $(function () {
                    $('input[name="min_hours"], input[name="max_hours"], input[name="annual_cap"], input[name="prior_worked_hours"]').keypress(function (e) {
                        if ($('[name=payment_type]').val() == 8) {
                            if ((event.keyCode >= 48 && event.keyCode <= 57) ||
                                event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 ||
                                event.keyCode == 39 || event.keyCode == 190) {

                            } else {
                                event.preventDefault();
                            }
                        }
                    });
                });

                $(function () {
                    $("ul.ul_activities").sortable();
                });

                function enabledropdown() {

                    var rate_count = $('#on_call_rate_count').val();
                    sessionStorage.setItem("rate_count", $('#on_call_rate_count').val());


                    for (var i = 1; i <= rate_count; i++) {
                        $('[name=rate' + i + ']').attr('readonly', false);
                        $('[name=start_day' + (i + 1) + ']').attr('disabled', false);
                        $('[name=end_day' + (i + 1) + ']').attr('disabled', false);
                        $('[name=end_day1]').attr('disabled', false);
                        sessionStorage.setItem("end_day" + (i), parseInt($('#end_day' + (i)).val()));
                        sessionStorage.setItem("start_day" + (i), parseInt($('#start_day' + (i)).val()));


                    }

                    $('#uncompensated_action').prop('disabled', false);
                }

                //function is added to change value for next start day dropdown by one step dynamically
                function rangechange(i) {


                    var rate_number = parseInt($('#on_call_rate_count').val());
                    var on_call_rate_btn_flag = false;
                    for (var i = 1; i <= rate_number; i++) {
                        var end_day_value = parseInt($('#end_day' + (i)).val())
                        $('#start_day' + (i + 1)).attr("disabled", "false");
                        $('#start_day' + (i + 1)).val(end_day_value + 1);
                        $('[name=start_day_hidden' + (i + 1) + ']').val(end_day_value + 1);

                        if (end_day_value == 31) {
                            var rate_index = i;
                            while (rate_index <= rate_number) {
                                $('#on-call-rate-div' + (rate_index + 1)).remove();
                                var rate_number = parseInt($('#on_call_rate_count').val());
                                rate_index++;

                            }
                            $('#on_call_rate_count').val(i);
                            $('#add-uncompensaed-btn').prop('disabled', true);
                            on_call_rate_btn_flag = true;

                        } else {
                            $('#add-uncompensaed-btn').prop('disabled', false);
                        }
                    }

                    if (on_call_rate_btn_flag) {
                        $('#add-uncompensaed-btn').prop('disabled', true);
                    }

                }


                $(document).ready(function () {
                    // $('[name=payment_type]').val(1);

                    var agreementPmtFrequencyObj = {!! json_encode($agreement_pmt_frequency->toArray()) !!};

                    if ($('[name=agreement]').val() > 0) {
                        var agreement_id = $('[name=agreement]').val();
                        $('#agreement_pmt_frequency').val(agreementPmtFrequencyObj[agreement_id]);
                    }

                    if ($('#partial_hours').prop("checked") == true) {
                        if ($('[name=payment_type]').val() == 5) {
                            $('#hours_calculation_div').show();
                        }
                    }

                    var rate_count = sessionStorage.getItem("rate_count");
                    var value = $('[name=payment_type]').val();

                    if (value == 5) {
                        var end_value = $('#end_day' + (rate_count)).val();
                        if (end_value != 31) {
                            $('#add-uncompensaed-btn').prop('disabled', false);
                        } else {
                            $('#add-uncompensaed-btn').prop('disabled', true);
                        }
                    }
                    for (var i = 1; i <= rate_count; i++) {
                        var end_day = sessionStorage.getItem("end_day" + (i));
                        var start_day = sessionStorage.getItem("start_day" + (i));
                        $('#start_day' + (i)).val(start_day);
                        $('[name=start_day_hidden' + (i) + ']').val(start_day);
                        $("#end_day" + (i) + "option[value=" + end_day + "]").attr('selected', 'selected');

                        if (i == 1) {
                            $('#btn-remove-uncompensated' + (i)).prop('disabled', true);
                        } else {
                            $('#btn-remove-uncompensated' + (i)).prop('disabled', false);
                        }
                    }

                    var category_id = $('[name=categoryid]').val();

                    $('.add_custom').click(function () {
                        var add_btn_id = $(this).attr('addcustrefId');
                        var i = $('#custom_count_' + add_btn_id).val();
                        i = parseInt(i) + 1;
                        $('#customaction_' + add_btn_id).append('<div class="form-group" id="custom_action_div_' + add_btn_id + '_' + i + '"><label class="col-xs-2" id="label_' + i + '">Custom Action </label><div class="col-xs-5"><input  type="text" name="customaction_name_' + add_btn_id + '[]" class="form-control"/></div><div class="col-xs-2"><button type="button" name="remove" id="remove_' + i + '" class="btn btn-primary btn-submit btn_remove"  referId="custom_action_div_' + add_btn_id + '_' + i + '">-</button></div></div>');

                        $('#custom_count_' + add_btn_id).val(i);

                        console.log(i);
                    });


                    $(document).on('click', '.btn_remove', function () {
                        var category_id = $(this).attr("referId");
                        console.log(category_id);
                        $('#' + category_id).remove();

                        var category_sub_id = $(this).attr("refcatId");
                        console.log(category_sub_id);
                        $('#' + category_id).remove();
                    });

                    $("#manual_end_date").datetimepicker({language: 'en_US', pickTime: false});
                    $("#valid_upto_date").datetimepicker({language: 'en_US', pickTime: false});
                    $("#prior_start_date").datetimepicker({language: 'en_US', pickTime: false});
                    $("[name=manual_end_date]").inputmask({mask: '99/99/9999'});
                    $("[name=prior_start_date]").inputmask({mask: '99/99/9999'});
                    setTimeout(function () {
                        const internalTrigger = true
                        $('[name=agreement]').trigger('change', true);
                    }, 1000);
                    /*var value =$('[name=contract_type]').val();*/
                    var value = $('[name=payment_type]').val();

                    $('#log_over_max_hour_flag').hide();
                    $("#lbl_min_hours").html("Min Hours");
                    $("#lbl_max_hours").html("Max Hours");
                    $("#lbl_annual_max_hours").html("Annual Max Hours");
                    $("#lbl_prior_worked_hours").html("Prior Worked Hours");
                    $("#lbl_Quarterly_max_hours").html("Quarterly Max Hours");
                    $("#units_div").hide();
                    $('#activities').show();
                    $('#expected-hours').show();
                    $('#categories-without-rehab').show();
                    $('#categories-with-rehab').hide();
                    if (value == 3 || value == 5) {

                        $("#annual_max_div").show();
                        $("#quarterly_max_hours_div").hide();
                        $('#min_hours_div').hide();
                        $('#max_hours_div').hide();
                        $('#fmv_rate_div').hide();
                        $('#custom_action_div').hide();
                        $('#categories-without-rehab').hide();
                        $('#categories-with-rehab').hide();
                        if (value == 3) {
                            $('#rate_selection').show();
                            $('#per-diem-uncompensated-activities').hide();
                            $('#on_call_uncompensated_rate').hide();
                            $('#add-uncompensaed-btn').hide();
                        } else {
                            $('#action_category').hide();
                            $('#per-diem-activities').hide();
                            $('#per-diem-uncompensated-activities').show();
                            $('#rate_selection').hide();
                        }
                        //call-coverage-duration  by 1254
                        $('#hours_selection').show();
                        $('#annual_max_hours_div').hide();
                        $('#annual_comp_div').hide();
                        $('#annual_comp_fifty_div').hide();
                        $('#wrvu_fifty_div').hide();
                        $('#annual_comp_seventy_five_div').hide();
                        $('#wrvu_seventy_five_div').hide();
                        $('#annual_comp_ninety_div').hide();
                        $('#wrvu_ninety_div').hide();
                        $('#logs_by_day_div').hide();
                        $('#wrvu_payments_div').hide();
                        $('#prior_worked_hours_div').hide();
                        $('#prior_amount_paid_div').hide();
                        $('#prior_start_date_div').hide();
                        if ($("#on_off").prop('checked') == true) {
                            $('#weekday_rate_div').hide();
                            $('#weekend_rate_div').hide();
                            $('#holiday_rate_div').hide();
                            $('#On_Call_rate_div').show();
                            $('#called_back_rate_div').show();
                            $('#called_in_rate_div').show();
                            $('#burden_selection').show();
                            $('#burden_on_off').prop("checked", true);
                            $('#div_action_Called-Back').show();
                            $('#div_action_Called-In').show();
                            $('#div_action_On-Call').show();
                            $('#div_action_Holiday_-_FULL_Day_-_On_Call').hide();
                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekday_-_FULL_Day_-_On_Call').hide();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekend_-_FULL_Day_-_On_Call').hide();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').hide();
                            $('#holiday_selection').hide();
                            $('#holiday_on_off').prop("checked", false);
                        } else {
                            if (value == 3) {
                                $('#weekday_rate_div').show();
                                $('#weekend_rate_div').show();
                                $('#holiday_rate_div').show();
                            } else {
                                $('#weekday_rate_div').hide();
                                $('#weekend_rate_div').hide();
                                $('#holiday_rate_div').hide();
                            }
                            $('#On_Call_rate_div').hide();
                            $('#called_back_rate_div').hide();
                            $('#called_in_rate_div').hide();
                            $('#burden_selection').hide();
                            $('#burden_on_off').prop("checked", false);
                            $('#div_action_Called-Back').hide();
                            $('#div_action_Called-In').hide();
                            $('#div_action_On-Call').hide();
                            $('#div_action_Holiday_-_FULL_Day_-_On_Call').show();
                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').show();
                            $('#div_action_Weekday_-_FULL_Day_-_On_Call').show();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').show();
                            $('#div_action_Weekend_-_FULL_Day_-_On_Call').show();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').show();
                            $('#holiday_selection').show();
                            $('#holiday_on_off').prop("checked", false);
                        }
                        $('#action_category').hide();
                        if (value == 3) {
                            $('#per-diem-activities').show();
                        }

                    } else {
                        $('#min_hours_div').show();
                        $("#quarterly_max_hours_div").show();
                        $('#max_hours_div').show();
                        $('#fmv_rate_div').show();
                        $('#rateID_icon_fmv').show();
                        $('#rateID_icon').hide();
                        if (value == 9) {
                            $('#rateID_icon_fmv').hide();
                            $('#rateID_icon').show();
                        }
                        document.getElementById('rateID').innerHTML = 'FMV Rate';
                        //Chaitraly::Added new line
                        if (value == 6) {
                            document.getElementById('rateID').innerHTML = 'Monthly Stipend';
                            $('#rateID_icon_fmv').hide();
                            $('#rateID_icon').show();
                        }
                        $('#custom_action_div').show();
                        $('#weekday_rate_div').hide();
                        $('#weekend_rate_div').hide();
                        $('#holiday_rate_div').hide();
                        // $('#hours_calculation_div').hide();
                        $('#on_call_uncompensated_rate').hide();
                        $('.add-on-call-uncompensated-rate').hide();
                        $('#max_hours_div_text').removeClass('input-group');
                        $('#min_hours_div_text').removeClass('input-group');
                        $('#annual_max_hours_div_text').removeClass('input-group');
                        if (value == 2) {
                            $("#quarterly_max_hours_div").show();
                            $('#annual_max_hours_div').show();
                            $('#annual_comp_div').hide();
                            $('#annual_comp_fifty_div').hide();
                            $('#wrvu_fifty_div').hide();
                            $('#annual_comp_seventy_five_div').hide();
                            $('#wrvu_seventy_five_div').hide();
                            $('#annual_comp_ninety_div').hide();
                            $('#wrvu_ninety_div').hide();
                            $('#logs_by_day_div').hide();
                            $('#wrvu_payments_div').hide();
                            $('#prior_worked_hours_div').show();
                            $('#prior_amount_paid_div').show();
                            $('#prior_start_date_div').show();
                            $('#log_over_max_hour_flag').show();
                        } else if (value == 4) {
                            $('#logs_by_day_div').show();
                            $('#annual_comp_div').show();
                            $('#annual_comp_fifty_div').show();
                            $('#wrvu_fifty_div').show();
                            $('#wrvu_fifty_div_text').removeClass('input-group');
                            $('#annual_comp_seventy_five_div').show();
                            $('#wrvu_seventy_five_div').show();
                            $('#wrvu_seventy_five_div_text').removeClass('input-group');
                            $('#annual_comp_ninety_div').show();
                            $('#wrvu_ninety_div').show();
                            $('#wrvu_ninety_div_text').removeClass('input-group');
                            $('#wrvu_payments_div').show();
                            $('#prior_worked_hours_div').hide();
                            $('#prior_amount_paid_div').hide();
                            $('#prior_start_date_div').hide();
                            $('#min_hours_div').hide();
                            $("#quarterly_max_hours_div").hide();
                            $('#max_hours_div').hide();
                            $('#fmv_rate_div').hide();
                            $('#annual_max_hours_div').hide();
                        } else if (value == 8) {
                            $("#lbl_min_hours").html("Min Units");
                            $("#lbl_max_hours").html("Max Units");
                            $("#lbl_annual_max_hours").html("Annual Max Units");
                            $("#lbl_prior_worked_hours").html("Prior Paid Units");
                            $("#lbl_Quarterly_max_hours").html("Quarterly Max Units");
                            $("#units_div").show();
                            $('#annual_max_hours_div').show();
                            $('#annual_comp_div').hide();
                            $('#annual_comp_fifty_div').hide();
                            $('#wrvu_fifty_div').hide();
                            $('#annual_comp_seventy_five_div').hide();
                            $('#wrvu_seventy_five_div').hide();
                            $('#annual_comp_ninety_div').hide();
                            $('#wrvu_ninety_div').hide();
                            $('#logs_by_day_div').hide();
                            $('#wrvu_payments_div').hide();
                            $('#prior_worked_hours_div').show();
                            $('#prior_amount_paid_div').show();
                            $('#prior_start_date_div').show();
                            $('#log_over_max_hour_flag').hide();
                            $('#custom_action_div').hide();
                            $('#action_category').hide();
                            $('#activities').hide();
                        } else if (value == 9) {
                            $("#mandate_details_div, #custom_action_div").hide();
                            $("#quarterly_max_hours_div").hide();
                            $('#min_hours_div').hide();
                            $('#max_hours_div').hide();
                            $('#expected-hours').hide();
                            $('#annual_max_hours_div').hide();
                            $('#annual_comp_div').hide();
                            $('#annual_comp_fifty_div').hide();
                            $('#wrvu_fifty_div').hide();
                            $('#annual_comp_seventy_five_div').hide();
                            $('#wrvu_seventy_five_div').hide();
                            $('#annual_comp_ninety_div').hide();
                            $('#wrvu_ninety_div').hide();
                            $('#logs_by_day_div').hide();
                            $('#wrvu_payments_div').hide();
                            $('#prior_worked_hours_div').hide();
                            $('#prior_amount_paid_div').hide();
                            $('#prior_start_date_div').hide();
                            $('#categories-without-rehab').hide();
                            $('#categories-with-rehab').show();
                        } else {
                            $('#annual_max_hours_div').hide();
                            $('#annual_comp_div').hide();
                            $('#annual_comp_fifty_div').hide();
                            $('#wrvu_fifty_div').hide();
                            $('#annual_comp_seventy_five_div').hide();
                            $('#wrvu_seventy_five_div').hide();
                            $('#annual_comp_ninety_div').hide();
                            $('#wrvu_ninety_div').hide();
                            $('#logs_by_day_div').hide();
                            $('#wrvu_payments_div').hide();
                            $('#prior_worked_hours_div').hide();
                            $('#prior_amount_paid_div').hide();
                            $('#prior_start_date_div').hide();
                        }
                        $('#rate_selection').hide();
                        //call-coverage-duration  by 1254
                        $('#hours_selection').hide();
                        $('#On_Call_rate_div').hide();
                        $('#called_back_rate_div').hide();
                        $('#called_in_rate_div').hide();
                        $('#burden_selection').hide();
                        $('#burden_on_off').prop("checked", false);
                        $('#action_category').show();
                        $('#per-diem-activities').hide();
                        $('#holiday_selection').hide();
                        $('#holiday_on_off').prop("checked", false);
                        $('#per-diem-uncompensated-activities').hide();
                    }
                    /*function for selecting approval type other than NA */
                    $('.approval_type').change(function () {
                        var name = $(this).attr('name');
                        var select_number = name.match(/\d+/);
                        if ($(this).val() > 0) {
                            $('[name=approval_manager_level' + select_number + ']').attr("disabled", false);
                            $('[name=initial_review_day_level' + select_number + ']').attr("disabled", false);
                            $('[name=final_review_day_level' + select_number + ']').attr("disabled", false);
                            $('[value=level' + select_number + ']').attr("disabled", false);
                        } else {
                            $('[name=approval_manager_level' + select_number + ']').attr("disabled", true);
                            $('[name=initial_review_day_level' + select_number + ']').attr("disabled", true);
                            $('[name=final_review_day_level' + select_number + ']').attr("disabled", true);
                            $('[value=level' + select_number + ']').attr("disabled", true);
                        }

                    });

                    //Code for display & hide contract deadline option
                    if ($('#contract_deadline_on_off').prop("checked") == true) {
                        alert();
                        if ($("#deadline_days").val() == 0 || $("#deadline_days").val() == '') {
                            /*if ($('[name=contract_type]').val() == 4) {*/
                            if ($('[name=payment_type]').val() == 3 || $('[name=payment_type]').val() == 5) {
                                $("#deadline_days").val(90);
                            } else {
                                $("#deadline_days").val(365);
                            }
                        }
                        $("#deadline_days_div").show();
                    } else {
                        $("#deadline_days_div").hide();
                    }

                    //Code for display & hide wrvu payments option
                    if ($('#wrvu_payments').prop("checked") == true) {
                        $("#contract_psa_wrvu_rates_div").show();
                    } else {
                        $("#contract_psa_wrvu_rates_div").hide();
                    }

                    if ($('[name=contract_type]').val() == '20') {
                        $('#state_attestations_monthly_div').show();
                        $('#state_attestations_annually_div').show();
                        $('#div_receipient1').show();
                        $('#div_receipient2').show();
                        $('#div_receipient3').show();
                        $('#supervision_type_div').show();
                    } else {
                        $('#state_attestations_monthly_div').hide();
                        $('#state_attestations_annually_div').hide();
                        $('#div_receipient1').hide();
                        $('#div_receipient2').hide();
                        $('#div_receipient3').hide();
                        $('#supervision_type_div').hide();
                    }

                });
                $(function () {
                    $("#on_off").change(function () {

                        if ($(this).prop("checked") == true) {
                            $('#weekday_rate_div').hide("slow");
                            $('#weekend_rate_div').hide("slow");
                            $('#holiday_rate_div').hide("slow");
                            $('#On_Call_rate_div').show("slow");
                            $('#called_back_rate_div').show("slow");
                            $('#called_in_rate_div').show("slow");
                            $('#burden_selection').show("slow");
                            $('#burden_on_off').prop("checked", true);
                            $('#div_action_Holiday_-_FULL_Day_-_On_Call').hide();
                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekday_-_FULL_Day_-_On_Call').hide();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekend_-_FULL_Day_-_On_Call').hide();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Called-Back').show();
                            $('#div_action_Called-In').show();
                            $('#div_action_On-Call').show();
                            $('#holiday_selection').hide("slow");
                            $('#holiday_on_off').prop("checked", false);
                        } else if ($(this).prop("checked") == false) {
                            $('#weekday_rate_div').show("slow");
                            $('#weekend_rate_div').show("slow");
                            $('#holiday_rate_div').show("slow");
                            $('#On_Call_rate_div').hide("slow");
                            $('#called_back_rate_div').hide("slow");
                            $('#called_in_rate_div').hide("slow");
                            $('#burden_selection').hide("slow");
                            $('#burden_on_off').prop("checked", false);
                            $('#div_action_Called-Back').hide();
                            $('#div_action_Called-In').hide();
                            $('#div_action_On-Call').hide();
                            $('#div_action_Holiday_-_FULL_Day_-_On_Call').show();
                            $('#div_action_Weekday_-_FULL_Day_-_On_Call').show();
                            $('#div_action_Weekend_-_FULL_Day_-_On_Call').show();
                            //call-coverage by 1254: added to hide half day activities when partial hours on
                            if ($('#partial_hours').prop("checked") == false) {
                                $('#div_action_Holiday_-_HALF_Day_-_On_Call').show();
                                $('#div_action_Weekday_-_HALF_Day_-_On_Call').show();
                                $('#div_action_Weekend_-_HALF_Day_-_On_Call').show();
                            }
                            $('#holiday_selection').show("slow");
                            $('#holiday_on_off').prop("checked", false);
                        }
                    });
                });

                //call-coverage by 1254 : added to hide all half day activities for perdiem when partial hours set to true
                $(function () {
                    $("#partial_hours").change(function () {
                        if ($(this).prop("checked") == true) {
                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').hide();
                            //Per Diem with Uncompensated Days by 1254
                            if ($('[name=payment_type]').val() == 5) {
                                $('#hours_calculation_div').show();
                            }
                        } else {
                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').show();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').show();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').show();
                            //Per Diem with Uncompensated Days by 1254
                            $('#hours_calculation_div').hide();
                        }
                    });
                });

                $(document).ready(function () {
                    var payment_type_value = $('[name=payment_type]').val();
                    if (payment_type_value == 1) {
                        $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for that log entry. Will not save.');
                        $('#min_hours_div_tooltip').prop('title', 'Min hours required on a yearly average basis.');
                        $('#max_hours_div_tooltip').prop('title', 'No effect.');
                        $('#rateID_tooltip').prop('title', 'Amount of which will be multiplied by the expected hours.');
                        $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 365 days.');
                    }
                });
                $(function () {
                    /* $('[name=contract_type]').change(function (event) {*/
                    $('[name=payment_type]').change(function (event) {
                        //To refresh particular tooltip title
                        document.getElementById("custom_action_div_tooltip").innerHTML.reload;
                        document.getElementById("min_hours_div_tooltip").innerHTML.reload;
                        document.getElementById("max_hours_div_tooltip").innerHTML.reload;
                        document.getElementById("contract_deadline_on_off_tooltip").innerHTML.reload;
                        document.getElementById("rateID_tooltip").innerHTML.reload;
                        //       document.getElementById("rateID_icon_fmv").innerHTML.reload;
                        //      document.getElementById("rateID_iconp").innerHTML.reload;
                        var payment_type_value = $('[name=payment_type]').val();
                        if (payment_type_value == 9) {
                            $('#rateID_tooltip').hide();
                            $('#rateID_icon').show();
                        } else {
                            console.log(payment_type_value);
                            $('#rateID_tooltip').show();
                            $('#rateID_icon').hide();
                        }

                        //To change Title onchnage particular payment type
                        switch (payment_type_value) {
                            case "1":
                                //Stipend
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for that log entry. Will not save.');
                                $('#min_hours_div_tooltip').prop('title', 'Min hours required on a yearly average basis.');
                                $('#max_hours_div_tooltip').prop('title', 'No effect.');
                                $('#rateID_tooltip').prop('title', 'Amount of which will be multiplied by the expected hours.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 365 days.');
                                break;
                            case "2":
                                //Hourly
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for use with that log entry. When not “Checked” there is no ability for provider to add a custom action to log entry.');

                                $('#min_hours_div_tooltip').prop('title', 'If contract has min hours enter them here if not you have to enter 0.');

                                $('#max_hours_div_tooltip').prop('title', 'Total hours available to enter for period based on payment frequency.');

                                $('#rateID_tooltip').prop('title', 'Hourly Rate.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 90 days.');
                                $('#lbl_prior_worked_hours_tooltip').prop('title', 'Hours to be considered towards annual max hours when contract goes live that other than the true renewal date.');
                                $('#prior_amount_paid_tooltip').prop('title', 'Dollars to be considered towards annual max hours when contract goes live that other than the true renewal date.');
                                $('#lbl_Quarterly_max_hours_tooltip').prop('title', 'Max hours will be tallied on a yearly quarterly basis, regardless of payment frequency.');
                                $('#annual_max_hours_tooltip').prop('title', 'Max hours allowed for the year based on the agreement frequency start date."');
                            case "3":
                                //Per Diem
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for use with that log entry. When not “Checked” there is no ability for provider to add a custom action to log entry.');
                                $('#min_hours_div_tooltip').prop('title', 'If contract has min hours enter them here if not you have to enter 0.');
                                $('#max_hours_div_tooltip').prop('title', 'Total hours available to enter for period based on payment frequency.');
                                $('#rateID_tooltip').prop('title', 'Hourly Rate.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 90 days.');
                                break;

                            case "5":
                                //Per Diem with Uncompensated Days
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for use with that log entry. When not “Checked” there is no ability for provider to add a custom action to log entry.');
                                $('#min_hours_div_tooltip').prop('title', 'If contract has min hours enter them here if not you have to enter 0.');
                                $('#max_hours_div_tooltip').prop('title', 'Total hours available to enter for period based on payment frequency.');
                                $('#rateID_tooltip').prop('title', 'Hourly Rate.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 90 days.');
                                break;

                            case "6":
                                //Monthly Stipend
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for that log entry. Will not save.');
                                $('#min_hours_div_tooltip').prop('title', 'Amount of hours required to release invoice, should be greater than 0 or will generate a payment without any logs entered.');
                                $('#max_hours_div_tooltip').prop('title', 'No effect.');
                                $('#rateID_tooltip').prop('title', 'Hourly Rate.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 365 days.');
                                break;
                            case "7":
                                //Time Study
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for use with that log entry. When not “Checked” there is no ability for provider to add a custom action to log entry.');

                                $('#min_hours_div_tooltip').prop('title', 'Minimum amount of hours to reach to be eligible for compensation.');

                                $('#max_hours_div_tooltip').prop('title', 'Total Max hours permitted in the payment frequency of the agreement.');

                                $('#rateID_tooltip').prop('title', 'Hourly Rate.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 90 days.');
                                break;
                            case "8":
                                //Per Unit
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for use with that log entry. When not “Checked” there is no ability for provider to add a custom action to log entry.');

                                $('#min_hours_div_tooltip').prop('title', 'The minimum amount of units approved to qualify for payment.');

                                $('#max_hours_div_tooltip').prop('title', 'Max units will be tallied on frequency set at the agreement level.');

                                $('#rateID_tooltip').prop('title', 'Per Unit Rate.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 365 days.');
                                $('#lbl_prior_worked_hours_tooltip').prop('title', 'Unites to be considered towards annual max unites when contract goes live that other than the true renewal date.');
                                $('#prior_amount_paid_tooltip').prop('title', 'Dollars to be considered towards annual max unites when contract goes live that other than the true renewal date.');
                                $('#lbl_Quarterly_max_hours_tooltip').prop('title', 'Max units will be tallied on a yearly quarterly basis, regardless of payment frequency.');
                                break;
                            default:
                                $('#custom_action_div_tooltip').prop('title', 'When “Checked” there is an option from the Activities drop down to create a custom action for that log entry. Will not save.');
                                $('#min_hours_div_tooltip').prop('title', 'Min hours required on a yearly average basis.');
                                $('#max_hours_div_tooltip').prop('title', 'No effect.');
                                $('#rateID_tooltip').prop('title', 'Amount of which will be multiplied by the expected hours.');
                                $('#contract_deadline_on_off_tooltip').prop('title', 'Amount of days that the contract will accept a new entry, default deadline is 365 days.');
                        }

                        //To change i button tax for on call, called back, and called in.redmine
                        $("#burden_on_off").change(
                            function () {
                                if ($(this).prop("checked") == true) {
                                    $('#On_Call_rate_tooltip').prop('title', 'Is base rate, and must be selected before next rate.');
                                    $('#called_back_rate_tooltip').prop('title', ' Is additional rate that is available once On-Call is submitted for a date.');
                                    $('#called_in_rate_tooltip').prop('title', 'Is final rate available once On-Call and Called Back have been submitted for a date.');
                                } else {
                                    $('#On_Call_rate_tooltip').prop('title', 'Any rate.');
                                    $('#called_back_rate_tooltip').prop('title', 'Any rate.');
                                    $('#called_in_rate_tooltip').prop('title', 'Any rate.');
                                }
                            }
                        );

                        $("#on_off").change(
                            function () {
                                if ($(this).prop("checked") == true) {
                                    $('#On_Call_rate_tooltip').prop('title', 'Is base rate, and must be selected before next rate.');
                                    $('#called_back_rate_tooltip').prop('title', ' Is additional rate that is available once On-Call is submitted for a date.');
                                    $('#called_in_rate_tooltip').prop('title', 'Is final rate available once On-Call and Called Back have been submitted for a date.');
                                } else {
                                    $('#On_Call_rate_tooltip').prop('title', 'Any rate.');
                                    $('#called_back_rate_tooltip').prop('title', 'Any rate.');
                                    $('#called_in_rate_tooltip').prop('title', 'Any rate.');
                                }
                            }
                        );

                        var value = $(this).val();
                        //Action Redesign by 1254
                        $('#partial_hours').prop('checked', false);
                        $('#agreement_error_div').html('');
                        $('.overlay').show();
                        $.ajax({
                            /*url: '{{ URL::current() }}?contract_type=' + value,*/
                            url: '{{ URL::current() }}?payment_type=' + value,
                            dataType: 'json'
                        }).done(function (response) {
                            $('[name=contract_name] option').remove();
                            $.each(response.contractNames, function (index, value) {
                                $('[name=contract_name]').append('<option value="' + index + '">' + value + '</option>');
                            });
                            $("[name=contract_name]").append($("[name=contract_name] option")
                                .remove().sort(function (a, b) {
                                    var at = $(a).text(),
                                        bt = $(b).text();
                                    return (at > bt) ? 1 : ((at < bt) ? -1 : 0);
                                }));
                            $('[name=contract_type] option').remove();
                            $.each(response.contractTypes, function (index, value) {
                                $('[name=contract_type]').append('<option value="' + index + '">' + value + '</option>');
                            });

                            // Below code is added for updating select range for uncompensated payment type.
                            if (value == 5) {
                                var agreement_pmt_frequency_type = $('#agreement_pmt_frequency').val();

                                if (agreement_pmt_frequency_type == 1) {
                                    var onCallDayRange = 31;
                                } else if (agreement_pmt_frequency_type == 2) {
                                    var onCallDayRange = 7;
                                } else if (agreement_pmt_frequency_type == 3) {
                                    var onCallDayRange = 14;
                                } else if (agreement_pmt_frequency_type == 4) {
                                    var onCallDayRange = 92;
                                }

                                // for(var i = onCallDayRange; i <= 31; i++){
                                //     $("#end_day1 option[value='"+i+"']").attr("disabled","disabled");
                                // }

                                $("[name=end_day1]" + "> option").each(function () {
                                    if (this.value > onCallDayRange) {
                                        $(this).attr("disabled", true);
                                        $(this).css({backgroundColor: '#eee'});
                                    } else {
                                        $(this).attr("disabled", false);
                                        $(this).css({backgroundColor: ''});
                                    }
                                });
                            }
                            $('.overlay').hide();
                        });
                    });

                    $("[name=annual_max_shifts]").keypress(function (event) {
                        if ((event.keyCode >= 48 && event.keyCode <= 57) ||
                            event.keyCode == 8 || event.keyCode == 9 || event.keyCode == 37 ||
                            event.keyCode == 39 || event.keyCode == 190) {

                        } else {
                            event.preventDefault();
                        }
                    });

                    /*added for check payment tpe*/
                    $('[name=payment_type]').change(function (event) {
                        var value = $(this).val();

                        $("#mandate_details_div, #custom_action_div").show();
                        $("#lbl_min_hours").html("Min Hours");
                        $("#lbl_max_hours").html("Max Hours");
                        $("#lbl_annual_max_hours").html("Annual Max Hours");
                        $("#lbl_prior_worked_hours").html("Prior Worked Hours");
                        $("#lbl_Quarterly_max_hours").html("Quarterly Max Hours");
                        $("#units_div").hide();
                        $('#activities').show();
                        $('#expected-hours').show();
                        $('#categories-without-rehab').show();
                        $('#categories-with-rehab').hide();

                        if (value == 3) {
                            $("#annual_max_shifts_div").show();
                            $('#annual_max_shifts_div_text').removeClass('input-group');
                            $("#annual_max_div").show();
                            $('#min_hours_div').hide();
                            $("#quarterly_max_hours_div").hide();
                            $('#max_hours_div').hide();
                            $('#fmv_rate_div').hide();
                            $('#custom_action_div').hide();
                            $('#rate_selection').show();
                            //call-coverage-duration  by 1254
                            $('#hours_selection').show();
                            $('#annual_max_hours_div').hide();
                            $("#deadline_days").val(90);
                            $('#annual_comp_div').hide();
                            $('#annual_comp_fifty_div').hide();
                            $('#wrvu_fifty_div').hide();
                            $('#annual_comp_seventy_five_div').hide();
                            $('#wrvu_seventy_five_div').hide();
                            $('#annual_comp_ninety_div').hide();
                            $('#wrvu_ninety_div').hide();
                            $('#logs_by_day_div').hide();
                            $('#wrvu_payments_div').hide();
                            $('#prior_worked_hours_div').hide();
                            $('#prior_amount_paid_div').hide();
                            $('#prior_start_date_div').hide();
                            $('#log_over_max_hour_flag').hide();
                            $('#categories-without-rehab').hide();
                            $('#categories-with-rehab').hide();
                            if ($("#on_off").prop('checked') == true) {
                                $('#weekday_rate_div').hide();
                                $('#weekend_rate_div').hide();
                                $('#holiday_rate_div').hide();
                                $('#On_Call_rate_div').show();
                                $('#called_back_rate_div').show();
                                $('#called_in_rate_div').show();
                                $('#burden_selection').show();
                                $('#burden_on_off').prop("checked", true);
                                $('#div_action_Holiday_-_FULL_Day_-_On_Call').hide();
                                $('#div_action_Weekday_-_FULL_Day_-_On_Call').hide();
                                $('#div_action_Weekend_-_FULL_Day_-_On_Call').hide()
                                $('#div_action_Weekday_-_HALF_Day_-_On_Call').hide();
                                $('#div_action_Holiday_-_HALF_Day_-_On_Call').hide();
                                $('#div_action_Weekend_-_HALF_Day_-_On_Call').hide();

                                $('#div_action_Called-Back').show();
                                $('#div_action_Called-In').show();
                                $('#div_action_On-Call').show();
                                $('#holiday_selection').hide();
                                $('#holiday_on_off').prop("checked", false);
                            } else {
                                $('#weekday_rate_div').show();
                                $('#weekend_rate_div').show();
                                $('#holiday_rate_div').show();
                                $('#On_Call_rate_div').hide();
                                $('#called_back_rate_div').hide();
                                $('#called_in_rate_div').hide();
                                $('#burden_selection').hide();
                                $('#burden_on_off').prop("checked", false);
                                $('#div_action_Called-Back').hide();
                                $('#div_action_Called-In').hide();
                                $('#div_action_On-Call').hide();
                                $('#div_action_Holiday_-_FULL_Day_-_On_Call').show();
                                $('#div_action_Weekday_-_FULL_Day_-_On_Call').show();
                                $('#div_action_Weekend_-_FULL_Day_-_On_Call').show();
                                if ($("#partial_hours").prop('checked') == false) {
                                    $('#div_action_Holiday_-_HALF_Day_-_On_Call').show();
                                    $('#div_action_Weekday_-_HALF_Day_-_On_Call').show();
                                    $('#div_action_Weekend_-_HALF_Day_-_On_Call').show();
                                }
                                $('#holiday_selection').show();
                                $('#holiday_on_off').prop("checked", false);
                            }

                            // $('#hours_calculation_div').hide();
                            //Action-Redesign by 1254
                            $('#action_category').hide();
                            $('#per-diem-uncompensated-activities').hide();
                            $('#per-diem-activities').show();
                            $('#on_call_uncompensated_rate').hide();
                            $('.add-on-call-uncompensated-rate').hide();
                            //show perdiem actions

                        } else if (value == 5) //Per Diem with Uncompensated Days by 1254
                        {
                            $("#annual_max_shifts_div").hide();
                            $("#annual_max_div").show();
                            $("#rate1").val(' ');
                            $("#start_day1").val('1');
                            $('#min_hours_div').hide();
                            $("#quarterly_max_hours_div").hide();
                            $('#max_hours_div').hide();
                            $('#fmv_rate_div').hide();
                            $("#deadline_days").val(90);
                            $('#weekday_rate_div').hide();
                            $('#weekend_rate_div').hide();
                            $('#holiday_rate_div').hide();

                            $('#On_Call_rate_div').hide();
                            $('#called_back_rate_div').hide();
                            $('#called_in_rate_div').hide();
                            $('#burden_selection').hide();

                            $('#custom_action_div').hide();
                            $('#hours_selection').show();

                            //$('#hours_calculation_div').hide();

                            $('#annual_max_hours_div').hide();
                            $('#annual_comp_div').hide();
                            $('#annual_comp_fifty_div').hide();
                            $('#wrvu_fifty_div').hide();
                            $('#annual_comp_seventy_five_div').hide();
                            $('#wrvu_seventy_five_div').hide();
                            $('#annual_comp_ninety_div').hide();
                            $('#wrvu_ninety_div').hide();
                            $('#logs_by_day_div').hide();
                            $('#wrvu_payments_div').hide();
                            $('#prior_worked_hours_div').hide();
                            $('#prior_amount_paid_div').hide();
                            $('#prior_start_date_div').hide();
                            $('#rate_selection').hide();
                            $('#on_call_uncompensated_rate').show();
                            $('.add-on-call-uncompensated-rate').show();

                            $('#action_category').hide();
                            $('#per-diem-activities').hide();
                            $('#per-diem-uncompensated-activities').show();
                            $('#log_over_max_hour_flag').hide();
                            $('#categories-without-rehab').hide();
                            $('#categories-without-rehab').hide();
                            $('#categories-with-rehab').hide();
                        } else {
                            $("#annual_max_shifts_div").hide();
                            $('#action_category').show();
                            $('#per-diem-activities').hide();
                            $('#per-diem-uncompensated-activities').hide();
                            $('#min_hours_div').show();
                            $("#quarterly_max_hours_div").show();
                            $('#max_hours_div').show();
                            $('#fmv_rate_div').show();
                            $('#log_over_max_hour_flag').hide();
                            $('#categories-without-rehab').show();
                            $('#categories-with-rehab').hide();
                            $('#rateID_icon_fmv').show();
                            $('#rateID_icon').hide();
                            if (value == 9) {
                                $('#rateID_icon_fmv').hide();
                                $('#rateID_icon').show();
                            }
                            document.getElementById('rateID').innerHTML = 'FMV Rate';
                            if (value == 6) {
                                document.getElementById('rateID').innerHTML = 'Monthly Stipend';
                                $('#rateID_icon_fmv').hide();
                                $('#rateID_icon').show();
                            }

                            $('#custom_action_div').show();
                            $('#weekday_rate_div').hide();
                            $('#weekend_rate_div').hide();
                            $('#holiday_rate_div').hide();
                            $("#deadline_days").val(365);
                            $('#max_hours_div_text').removeClass('input-group');
                            $('#min_hours_div_text').removeClass('input-group');
                            $('#annual_max_hours_div_text').removeClass('input-group');
                            $('#on_call_uncompensated_rate').hide();
                            $('.add-on-call-uncompensated-rate').hide();
                            if (value == 7) {
                                $("#mandate_details_div, #custom_action_div").hide();
                            }
                            if (value == 2) {
                                $('#annual_max_div').hide();
                                $('#annual_max_hours_div').show();
                                $('#annual_comp_div').hide();
                                $('#annual_comp_fifty_div').hide();
                                $('#wrvu_fifty_div').hide();
                                $('#annual_comp_seventy_five_div').hide();
                                $('#wrvu_seventy_five_div').hide();
                                $('#annual_comp_ninety_div').hide();
                                $('#wrvu_ninety_div').hide();
                                $('#logs_by_day_div').hide();
                                $('#wrvu_payments_div').hide();
                                $('#prior_worked_hours_div').show();
                                $('#prior_amount_paid_div').show();
                                $('#prior_start_date_div').show();
                                $('#log_over_max_hour_flag').show();

                                // $('#hours_calculation_div').hide();
                            } else if (value == 4) {
                                $('#logs_by_day_div').show();
                                $('#annual_comp_div').show();
                                $('#annual_comp_fifty_div').show();
                                $('#wrvu_fifty_div').show();
                                $('#wrvu_fifty_div_text').removeClass('input-group');
                                $('#annual_comp_seventy_five_div').show();
                                $('#wrvu_seventy_five_div').show();
                                $('#wrvu_seventy_five_div_text').removeClass('input-group');
                                $('#annual_comp_ninety_div').show();
                                $('#wrvu_ninety_div').show();
                                $('#wrvu_ninety_div_text').removeClass('input-group');
                                $('#wrvu_payments_div').show();
                                $('#prior_worked_hours_div').hide();
                                $('#prior_amount_paid_div').hide();
                                $('#prior_start_date_div').hide();
                                $('#min_hours_div').hide();
                                $("#quarterly_max_hours_div").hide();
                                $('#max_hours_div').hide();
                                $('#fmv_rate_div').hide();
                                $('#annual_max_hours_div').hide();
                                //$('#hours_calculation_div').hide();
                            } else if (value == 8) {
                                $("#lbl_min_hours").html("Min Units");
                                $("#lbl_max_hours").html("Max Units");
                                $("#lbl_annual_max_hours").html("Annual Max Units");
                                $("#lbl_prior_worked_hours").html("Prior Paid Units");
                                $("#lbl_Quarterly_max_hours").html("Quarterly Max Units");
                                $("#units_div").show();
                                $('#annual_max_div').hide();
                                $('#annual_max_hours_div').show();
                                $('#annual_comp_div').hide();
                                $('#annual_comp_fifty_div').hide();
                                $('#wrvu_fifty_div').hide();
                                $('#annual_comp_seventy_five_div').hide();
                                $('#wrvu_seventy_five_div').hide();
                                $('#annual_comp_ninety_div').hide();
                                $('#wrvu_ninety_div').hide();
                                $('#logs_by_day_div').hide();
                                $('#wrvu_payments_div').hide();
                                $('#prior_worked_hours_div').show();
                                $('#prior_amount_paid_div').show();
                                $('#prior_start_date_div').show();
                                $('#log_over_max_hour_flag').hide();
                                $('#custom_action_div').hide();
                                $('#action_category').hide();
                                $('#activities').hide();

                            } else if (value == 9) {
                                $("#mandate_details_div, #custom_action_div").hide();
                                $("#units_div").hide();
                                $('#annual_max_div').hide();
                                $('#annual_max_hours_div').hide();
                                $('#annual_comp_div').hide();
                                $('#annual_comp_fifty_div').hide();
                                $('#wrvu_fifty_div').hide();
                                $('#annual_comp_seventy_five_div').hide();
                                $('#wrvu_seventy_five_div').hide();
                                $('#annual_comp_ninety_div').hide();
                                $('#wrvu_ninety_div').hide();
                                $('#logs_by_day_div').hide();
                                $('#wrvu_payments_div').hide();
                                $('#prior_worked_hours_div').hide();
                                $('#prior_amount_paid_div').hide();
                                $('#prior_start_date_div').hide();
                                $('#log_over_max_hour_flag').hide();
                                $("#quarterly_max_hours_div").hide();
                                $('#min_hours_div').hide();
                                $('#max_hours_div').hide();
                                $('#expected-hours').hide();
                                $('#categories-without-rehab').hide();
                                $('#categories-with-rehab').show();
                                $('#rateID_tooltip').hide();
                            } else {
                                $('#annual_max_hours_div').hide();
                                $('#annual_comp_div').hide();
                                $('#annual_comp_fifty_div').hide();
                                $('#wrvu_fifty_div').hide();
                                $('#annual_comp_seventy_five_div').hide();
                                $('#wrvu_seventy_five_div').hide();
                                $('#annual_comp_ninety_div').hide();
                                $('#wrvu_ninety_div').hide();
                                $('#logs_by_day_div').hide();
                                $('#wrvu_payments_div').hide();
                                $('#prior_worked_hours_div').hide();
                                $('#prior_amount_paid_div').hide();
                                $('#prior_start_date_div').hide();
                                //  $('#hours_calculation_div').hide();
                            }
                            $('#rate_selection').hide();
                            //call-coverage-duration :  by 1254
                            $('#hours_selection').hide();
                            $('#On_Call_rate_div').hide();
                            $('#called_back_rate_div').hide();
                            $('#called_in_rate_div').hide();
                            $('#burden_selection').hide();
                            $('#burden_on_off').prop("checked", false);
                            $('#holiday_selection').hide();
                            $('#holiday_on_off').prop("checked", false);
                        }
                    });
                });

                $(function () {
                    $('[name=agreement]').change(function (event, internalTrigger) {
                        const userClicked = !internalTrigger;
                        const pageJustLoaded = internalTrigger
                        // To avoid overriding original values when page fails validation
                        // Properties still need to be modified so function needs to run (eg. setting visibility to hidden)
                        const changeFormValues = userClicked || (pageJustLoaded && !pageFailedAtValidation)

                        var value = $(this).val();
                        var url = '{{ URL::current() }}/checkApproval/';
                        @if(Request::is('agreements/*'))
                            url = url;
                        @else
                            url = url + value;
                        @endif
                        if (value > 0) {
                            $.ajax({
                                url: url,
                                dataType: 'json'
                            }).done(function (response) {
                                //var result = JSON.parse(response);
                                //var obj = $.parseJSON(response);
                                //console.log(response);

                                var agreement_pmt_frequency_type = response['agreement']['payment_frequency_type'];
                                var max_review_day = 28;
                                if (changeFormValues) {
                                    $('#agreement_pmt_frequency').val(agreement_pmt_frequency_type);
                                }

                                if (agreement_pmt_frequency_type == 1) {
                                    var min_review_day = 10;
                                    var max_review_day = 28;
                                    var onCallDayRange = 31;
                                } else if (agreement_pmt_frequency_type == 2) {
                                    var min_review_day = 2;
                                    var max_review_day = 6;
                                    var onCallDayRange = 7;
                                } else if (agreement_pmt_frequency_type == 3) {
                                    var min_review_day = 2;
                                    var max_review_day = 14;
                                    var onCallDayRange = 14;
                                } else if (agreement_pmt_frequency_type == 4) {
                                    var min_review_day = 10;
                                    var max_review_day = 85;
                                    var onCallDayRange = 92;
                                }

                                $('#prior_start_date .calendar').css({'visibility':'hidden','display':'none'});

                                if (changeFormValues) {
                                    $('#prior_start_date_field').attr('readonly', true);
                                    $('[name=manual_end_date]').attr('readonly', true);
                                    $('[name=valid_upto_date]').attr('readonly', true);

                                    $('[name=default_dates]').prop('checked', true);
                                    $("#manual_end_date input[name=manual_end_date]").val(response['agreement_end_date']);
                                    $("#valid_upto_date input[name=valid_upto_date]").val(response['agreement_valid_upto_date']);
                                    $("#agreement_end_date").val(response['agreement_end_date']);
                                    $("#agreement_valid_upto_date").val(response['agreement_valid_upto_date']);
                                    $("#prior_start_date input[name=prior_start_date]").val(response['agreement_start_date']);
                                    $('[name=contract_prior_start_date_on_off]').attr('value', '0');
                                    $('[name=contract_prior_start_date_on_off]').attr('checked', false);
                                }

                                $('.calendar').css('visibility', 'hidden');

                                if (response['agreement']['approval_process'] == 1) {
                                    //var i=

                                    $('#approval_feilds').show();
                                    $('#approvalContainer').show();

                                    $('#approval_process').val(1);

                                    // if(!changeFormValues && pageFailedAtValidation) {
                                    if (!changeFormValues) {
                                        // Do not modify any values, but trigger all independent 'change' functions that disable unwanted fields from the form
                                        // These functions only make changes depending on the current state (if 'value' then enable)
                                        $('[name=default_dates]').trigger('change')
                                        $('[name=contract_prior_start_date_on_off]').trigger('change')
                                        $('[name=default]').trigger('change')
                                        $("#partial_hours").trigger('change')
                                        $('[name=contract_type]').trigger('change')
                                        $('[class=approval_type').trigger('change')

                                        $("#contract_deadline_on_off").trigger('change')
                                        $("#wrvu_payments").trigger('change')

                                        if ($('[name=default]').prop('checked') == true) {
                                            $('#approval_process').val(1);
                                        }
                                        if ($("#on_off").prop('checked') == true) {
                                            $("#on_off").trigger('change')
                                        }


                                        // $('[name=payment_type]') is skipped because it triggers changes

                                        // Enabling back to default value the opt-in checkboxes (not included as part of the name=default change)
                                        for (let i = 1; i <= 6; i++) {
                                            let optInCheckBox = $(`input:checkbox[value="level${i}"]`)
                                            if (optInCheckBox && optInCheckBox.attr('disabled') === 'disabled') {
                                                optInCheckBox.prop('checked', true);
                                            }
                                        }

                                        return
                                    }
                                    ;

                                    $('[name=default]').prop('checked', true);

                                    var approval_manager_info_length = response['approvalManagerInfo'].length;

                                    for (var i = 1; i <= 6; i++) {

                                        $('[name=approverTypeforLevel' + i + ']').attr("disabled", true);
                                        $('[name=approval_manager_level' + i + ']').attr("disabled", true);
                                        $('[name=initial_review_day_level' + i + ']').attr("disabled", true);
                                        $('[name=final_review_day_level' + i + ']').attr("disabled", true);

                                        if (i <= approval_manager_info_length) {
                                            $('[name=approverTypeforLevel' + i + ']').val(response['approvalManagerInfo'][i - 1]['type_id']);
                                            // $('[name=approval_manager_level'+i+']').val(response['approvalManagerInfo'][i-1]['user_id']);
                                            if (i == 1) {
                                                mgr1[0].selectize.setValue(response['approvalManagerInfo'][i - 1]['user_id'], false);
                                            } else if (i == 2) {
                                                mgr2[0].selectize.setValue(response['approvalManagerInfo'][i - 1]['user_id'], false);
                                            } else if (i == 3) {
                                                mgr3[0].selectize.setValue(response['approvalManagerInfo'][i - 1]['user_id'], false);
                                            } else if (i == 4) {
                                                mgr4[0].selectize.setValue(response['approvalManagerInfo'][i - 1]['user_id'], false);
                                            } else if (i == 5) {
                                                mgr5[0].selectize.setValue(response['approvalManagerInfo'][i - 1]['user_id'], false);
                                            } else if (i == 6) {
                                                mgr6[0].selectize.setValue(response['approvalManagerInfo'][i - 1]['user_id'], false);
                                            }
                                            $('[name=initial_review_day_level' + i + ']').val(response['approvalManagerInfo'][i - 1]['initial_review_day']);
                                            $('[name=final_review_day_level' + i + ']').val(response['approvalManagerInfo'][i - 1]['final_review_day']);
                                            if (response['approvalManagerInfo'][i - 1]['opt_in_email_status'] == 0) {
                                                $('input:checkbox[value="level' + i + '"]').prop('checked', false);
                                            } else {
                                                $('input:checkbox[value="level' + i + '"]').prop('checked', true);
                                            }
                                        } else {
                                            if (i == 1) {
                                                mgr1[0].selectize.setValue(0, false);
                                            } else if (i == 2) {
                                                mgr2[0].selectize.setValue(0, false);
                                            } else if (i == 3) {
                                                mgr3[0].selectize.setValue(0, false);
                                            } else if (i == 4) {
                                                mgr4[0].selectize.setValue(0, false);
                                            } else if (i == 5) {
                                                mgr5[0].selectize.setValue(0, false);
                                            } else if (i == 6) {
                                                mgr6[0].selectize.setValue(0, false);
                                            }

                                            // $('[name=approverTypeforLevel'+i+']').val(0);
                                            // $('[name=approval_manager_level'+i+'] option:selected ').removeAttr('selected');
                                            $('[name=initial_review_day_level' + i + ']').val(min_review_day);
                                            $('[name=final_review_day_level' + i + ']').val(max_review_day);
                                            ;
                                            $('input:checkbox[value="level' + i + '"]').prop('checked', true);
                                        }

                                        $("[name=initial_review_day_level" + i + "]" + "> option").each(function () {
                                            if (this.value > max_review_day) {
                                                $(this).attr("disabled", true);
                                                $(this).css({backgroundColor: '#eee'});
                                            } else {
                                                $(this).attr("disabled", false);
                                                $(this).css({backgroundColor: ''});
                                            }
                                        });


                                        if ($('[name=payment_type]').val() == 5) {
                                            $("[name=end_day1]" + "> option").each(function () {
                                                if (this.value > onCallDayRange) {
                                                    $(this).attr("disabled", true);
                                                    $(this).css({backgroundColor: '#eee'});
                                                } else {
                                                    $(this).attr("selected", "selected");
                                                    $(this).attr("disabled", false);
                                                    $(this).css({backgroundColor: ''});
                                                }
                                            });

                                            var rangeLength = $('[id*="on-call-rate-div"]').length;

                                            // Below code is added to remove the custom added date range for uncompensated payment type.
                                            for (current_rate_index = 2; current_rate_index <= rangeLength; current_rate_index++) {

                                                if ($('#on-call-rate-div' + current_rate_index).length) {
                                                    $('#on-call-rate-div' + current_rate_index).remove();
                                                    var rate_number = parseInt($('#on_call_rate_count').val());

                                                    // This loop is to change all attributes value after removing on call rates
                                                    for (var i = current_rate_index + 1; i <= rate_number; i++) {
                                                        $('#rate' + i).attr("id", 'rate' + (i - 1));
                                                        $('#start_day' + i).attr("id", 'start_day' + (i - 1));
                                                        $('#end_day' + i).attr("id", 'end_day' + (i - 1));
                                                        $('#start_day_hidden' + i).attr("id", 'start_day_hidden' + (i - 1));
                                                        $('#rate-label' + i).text("On Call Rate" + (i - 1));
                                                        $('#rate-label' + i).attr("id", "rate-label" + (i - 1));


                                                        $('[name=rate' + i + ']').attr("name", 'rate' + (i - 1));
                                                        $('[name=start_day' + i + ']').attr("name", 'start_day' + (i - 1));
                                                        $('[name=end_day' + i + ']').attr("name", 'end_day' + (i - 1));
                                                        $('[name=start_day_hidden' + (i) + ']').attr("name", 'start_day_hidden' + (i - 1));
                                                        // $('#rate-label'+i).text("On Call Rate"+"test");
                                                        $('#rate-label' + i).text("On Call Rate" + i);

                                                        $('#on-call-start-days' + i).attr("id", 'on-call-start-days' + (i - 1));
                                                        $('#on-call-end-days' + i).attr("id", 'on-call-end-days' + (i - 1));
                                                        $('#on-call-rate-div' + i).attr("id", 'on-call-rate-div' + (i - 1));
                                                        $('#btn-remove-uncompensated' + i).val(i - 1);
                                                        $('#btn-remove-uncompensated' + i).attr("id", 'btn-remove-uncompensated' + (i - 1));
                                                        //   $('#btn-remove-uncompensated'+(i)).attr("referId","on-call-rate-div"+(i-1));


                                                        if (current_rate_index == 1) {
                                                            $('#start_day1').attr("disabled", "false");
                                                            $('#start_day1').append("<option>" + 1 + "</option>");
                                                            $('#start_day1').val("1");
                                                        }

                                                    }

                                                    var previous_end_day = parseInt($('#end_day' + (current_rate_index - 1)).val());
                                                    $('#start_day' + (current_rate_index)).val(previous_end_day + 1);

                                                    $('#on_call_rate_count').val(rate_number - 1);
                                                    //check after removing last range is 31 then disable add on call rate button else make it enable
                                                    if ($('#end_day' + (rate_number - 1)).val() != 31) {
                                                        $('#add-uncompensaed-btn').prop('disabled', false);
                                                    } else {
                                                        $('#add-uncompensaed-btn').prop('disabled', true);
                                                    }
                                                } else {
                                                    break;
                                                }
                                            }

                                            $('#add-uncompensaed-btn').prop('disabled', true);
                                        }

                                        $("[name=final_review_day_level" + i + "]" + "> option").each(function () {
                                            if (this.value > max_review_day) {
                                                $(this).attr("disabled", true);
                                                $(this).css({backgroundColor: '#eee'});
                                            } else {
                                                $(this).attr("disabled", false);
                                                $(this).css({backgroundColor: ''});
                                            }
                                        });
                                    }
                                    $('input.emailCheck').attr("disabled", true);
                                } else {
                                    $('#approval_feilds').hide();
                                    $('#approvalContainer').hide();
                                    if (changeFormValues) {
                                        $('#approval_process').val(0);
                                        $('[name=default]').prop('checked', false);
                                    }
                                }

                            });
                        }
                    });
                });

                $(function () {

                    $('[name=default_dates]').change(function (event) {
                        if ($('[name=default_dates]').is(':checked')) {
                            $('[name=manual_end_date]').val($('#agreement_end_date').val());
                            $('[name=valid_upto_date]').val($('#agreement_valid_upto_date').val());
                            $('[name=manual_end_date]').attr('readonly', true);
                            $('[name=valid_upto_date]').attr('readonly', true);
                            $('.calendar').css('visibility', 'hidden');
                        } else {
                            $('[name=manual_end_date]').attr('readonly', false);
                            $('[name=valid_upto_date]').attr('readonly', false);
                            $('.calendar').css('visibility', 'visible');
                        }
                    });
                    //code for prior start date handling
                    $('[name=contract_prior_start_date_on_off]').change(function (event) {
                        if ($('[name=contract_prior_start_date_on_off]').is(':checked')) {
                            $('#prior_start_date .calendar').css({'visibility':'visible','display':'block'});
                            $('#prior_start_date_field').attr('readonly', false);
                            $(this).attr('value', '1');
                        } else {
                            $('#prior_start_date .calendar').css({'visibility':'hidden','display':'none'});
                            $('#prior_start_date_field').attr('readonly', true);
                            $(this).attr('value', '0');
                        }
                    });
                });
                // code for change state of contract deadline option
                $("#contract_deadline_on_off").change(function () {
                    if ($(this).prop("checked") == true) {
                        if ($("#deadline_days").val() == 0 || $("#deadline_days").val() == '') {
                            /*if ($('[name=contract_type]').val() == 4) {*/
                            if ($('[name=payment_type]').val() == 3 || $('[name=payment_type]').val() == 5) {
                                $("#deadline_days").val(90);
                            } else {
                                $("#deadline_days").val(365);
                            }
                        }
                        $("#deadline_days_div").show("slow");
                    } else if ($(this).prop("checked") == false) {
                        $("#deadline_days_div").hide("slow");
                    }
                });

                // code for change state of wrvu payments option
                $("#wrvu_payments").change(function () {
                    if ($(this).prop("checked") == true) {
                        $("#contract_psa_wrvu_rates_div").show("slow");
                    } else if ($(this).prop("checked") == false) {
                        $("#contract_psa_wrvu_rates_div").hide("slow");
                    }
                });

                function addRangeCustom() {
                    var rate_number = parseInt($('#on_call_rate_count').val()) + 1;
                    var start = parseInt($('#end_day' + (rate_number - 1)).val()) + 1;
                    var endvalue = parseInt($('#end_day' + (rate_number)).val());


                    if (isNaN(start)) {
                        var start = 1;
                        rate_number = 1;
                    }

                    var agreement_pmt_frequency_type = $('#agreement_pmt_frequency').val();

                    if (agreement_pmt_frequency_type == 1) {
                        var endDate = 31;
                    } else if (agreement_pmt_frequency_type == 2) {
                        var endDate = 7;
                    } else if (agreement_pmt_frequency_type == 3) {
                        var endDate = 14;
                    } else if (agreement_pmt_frequency_type == 4) {
                        var endDate = 92;
                    }

                    if (endvalue != endDate) {
                        var range_values = [];
                        // for (i = start; i <= 31; i++){
                        //     range_values.push(i)
                        // }
                        for (i = 1; i <= endDate; i++) {
                            range_values.push(i)
                        }

                        if ($('#end_day' + (rate_number)).val() != endDate) {
                            $('#add-uncompensaed-btn').prop('disabled', false);
                        } else {
                            $('#add-uncompensaed-btn').prop('disabled', true);
                        }

                        var range_start_dropdown = $("<select></select>").attr("id", 'start_day' + rate_number).attr("name", 'start_day' + rate_number).attr("class", 'form-control').attr('disabled', true);
                        $.each(range_values, function (i, value) {
                            range_start_dropdown.append("<option>" + value + "</option>");
                        });

                        var range_end_dropdown = $("<select></select>").attr("id", 'end_day' + rate_number).attr("name", 'end_day' + rate_number).attr("class", 'form-control').attr("onchange", "rangechange()");
                        $.each(range_values, function (i, value) {
                            range_end_dropdown.append("<option>" + value + "</option>");
                        });

                        var uncompensated_rates = '<div id="on-call-rate-div' + rate_number + '">'
                            + '<div class="form-group col-xs-12 no-padding-left no-padding-right no-margin-left">  <label class="col-xs-2 control-label" id="rate-label' + rate_number + '">On Call Rate ' + rate_number + '</label>'
                            + '<div class="col-xs-4 info-cls" style="padding-left: 5px; padding-right: 0px;"><div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>'
                            + '<div class="col-xs-12 form-group no-margin-right" style="padding-right: 8px;">'
                            + '<div class="input-group right-field">'
                            + '<input type="text" class="form-control" id="rate' + rate_number + '" maxlength="50" rows="2" cols="54" style="resize:none;" name="rate' + rate_number + '" />'
                            + '<span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>'
                            + '</div>'  //end of input group
                            + '</div>'  //col-xs-4 form-group
                            + '</div>'
                            + '<div class="col-xs-6 form-group">'
                            + '<label class="col-xs-2 control-label no-padding-top"  style="width:20%;">Days Entered:</label>'
                            + '<div class="col-xs-3" style="width:24%;"  id="on-call-start-days' + rate_number + '">'

                            + '</div>'
                            + '<div class="col-xs-3" style="width:24%" id="on-call-end-days' + rate_number + '">'

                            + '</div>'
                            + '<input type=hidden name=start_day_hidden' + rate_number + '>'
                            // +'<div class="col-xs-2"><button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate"  id="btn-remove-uncompensated'+rate_number+'" referid="on-call-rate-div'+rate_number+'"  value='+rate_number+' type="button"> - </button></div>'
                            + '<div class="col-xs-2"><button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate"  id="btn-remove-uncompensated' + rate_number + '"   value=' + rate_number + ' type="button" onClick="removeRangeCustom(this);" > - </button></div>'
                            + '</div>'
                            + '</div>'
                            + '</div>'
                        $('#on_call_uncompensated_rate').append(uncompensated_rates);
                        $("#on-call-start-days" + rate_number).append(range_start_dropdown);
                        $("#on-call-end-days" + rate_number).append(range_end_dropdown);
                        $('#on_call_rate_count').val(rate_number);
                    } else {

                        $('#add-uncompensaed-btn').prop('disabled', true);
                    }

                    var on_call_rate_count = $('#on_call_rate_count').val();
                    $('[name=start_day_hidden' + (rate_number) + ']').val(parseInt($('#end_day' + (rate_number - 1)).val()) + 1);
                    $('[name=start_day' + (rate_number) + ']').val(parseInt($('#end_day' + (rate_number - 1)).val()) + 1);
                }

                function removeRangeCustom(current_rate_index_val) {
                    //change all id's after removing on call rates
                    current_rate_index = parseInt(current_rate_index_val.value);

                    $('#on-call-rate-div' + current_rate_index).remove();
                    var rate_number = parseInt($('#on_call_rate_count').val());

                    // This loop is to change all attributes value after removing on call rates
                    for (var i = current_rate_index + 1; i <= rate_number; i++) {
                        $('#rate' + i).attr("id", 'rate' + (i - 1));
                        $('#start_day' + i).attr("id", 'start_day' + (i - 1));
                        $('#end_day' + i).attr("id", 'end_day' + (i - 1));
                        $('#start_day_hidden' + i).attr("id", 'start_day_hidden' + (i - 1));
                        $('#rate-label' + i).text("On Call Rate" + (i - 1));
                        $('#rate-label' + i).attr("id", "rate-label" + (i - 1));


                        $('[name=rate' + i + ']').attr("name", 'rate' + (i - 1));
                        $('[name=start_day' + i + ']').attr("name", 'start_day' + (i - 1));
                        $('[name=end_day' + i + ']').attr("name", 'end_day' + (i - 1));
                        $('[name=start_day_hidden' + (i) + ']').attr("name", 'start_day_hidden' + (i - 1));
                        // $('#rate-label'+i).text("On Call Rate"+"test");
                        $('#rate-label' + i).text("On Call Rate" + i);

                        $('#on-call-start-days' + i).attr("id", 'on-call-start-days' + (i - 1));
                        $('#on-call-end-days' + i).attr("id", 'on-call-end-days' + (i - 1));
                        $('#on-call-rate-div' + i).attr("id", 'on-call-rate-div' + (i - 1));
                        $('#btn-remove-uncompensated' + i).val(i - 1);
                        $('#btn-remove-uncompensated' + i).attr("id", 'btn-remove-uncompensated' + (i - 1));
                        //   $('#btn-remove-uncompensated'+(i)).attr("referId","on-call-rate-div"+(i-1));


                        if (current_rate_index == 1) {
                            $('#start_day1').attr("disabled", "false");
                            $('#start_day1').append("<option>" + 1 + "</option>");
                            $('#start_day1').val("1");
                        }

                    }

                    var previous_end_day = parseInt($('#end_day' + (current_rate_index - 1)).val());
                    $('#start_day' + (current_rate_index)).val(previous_end_day + 1);

                    $('#on_call_rate_count').val(rate_number - 1);
                    //check after removing last range is 31 then disable add on call rate button else make it enable
                    if ($('#end_day' + (rate_number - 1)).val() != 31) {
                        $('#add-uncompensaed-btn').prop('disabled', false);
                    } else {
                        $('#add-uncompensaed-btn').prop('disabled', true);
                    }

                }
            </script>


            <!-- //Action-Redesign by 1254 -->
            <style>
                @import url("https://maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css");

                .panel-title1 > a:before {
                    float: left !important;
                    font-family: FontAwesome;
                    content: "\f068";
                    font-size: 16px;
                    font-weight: 100;
                    padding-right: 4px;
                    border-radius: 60px;
                    color: #f68a1f;
                    padding-left: 10px;
                    margin-left: 4px;
                    width: 5%;
                }

                .panel-title1 > a.collapsed:before {
                    float: left !important;
                    content: "\f067";
                    font-family: FontAwesome;
                    padding-right: 4px;
                    border-radius: 60px;
                    color: #f68a1f;
                    padding-left: 10px;
                    margin-left: 4px;
                    font-size: 16px;
                    font-weight: 100;
                    width: 5%;
                }

                .panel-title > a:hover,
                .panel-title > a:active,
                .panel-title > a:focus {
                    text-decoration: none;

                }

                .panel-heading1 {
                    background-color: #8e8174 !important;
                    color: #fff !important;
                    background-image: none !important;
                    padding: 5px 0px 5px 5px;
                    position: relative;
                }

                .panel-title1 {
                    margin-top: 0;
                    margin-bottom: 0;
                    font-size: 16px;
                    color: inherit;
                    line-height: 36px;
                }

                .action-container {
                    width: 50% !important;
                    float: left;
                }

                .actionWrap {
                    max-width: 80%;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    overflow: hidden;
                    float: left;
                }

                .actionCheckbox {
                    float: left !important;
                }

                /* input[type="checkbox"] {
        float: left;
    } */

                .collapse-level-two-circle {
                    position: absolute;
                    left: 25px;
                    /* top: 50%; */
                    transform: translateY(-50%);
                    font-size: 14px;
                    color: #f68a1f;
                    transition: .5s;
                    /* line-height: 18px; */
                    border: solid 3px #f68a1f;
                    border-radius: 20px;
                    width: 27px;
                    height: 28px;
                    margin-top: 16px;
                    margin-left: -13px;
                }

                .contract-name-search {
                    -webkit-text-size-adjust: 100%;
                    -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
                    box-sizing: border-box;
                    margin: 0;
                    font: inherit;
                    text-transform: none;
                    font-family: inherit;
                    display: block;
                    width: 100%;
                    height: 34px;
                    padding: 6px 12px;
                    font-size: 14px;
                    line-height: 1.42857143;
                    color: #555;
                    background-color: #fff;
                    background-image: none;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    box-shadow: inset 0 1px 1px rgba(0, 0, 0, .075);
                    transition: border-color ease-in-out .15s, box-shadow ease-in-out .15s;
                    padding-right: 5px;
                }

                .error-cls, .v-align-center {
                    display: flex;
                    align-items: center;
                }

                .error-cls .validation-error {
                    margin-bottom: 0;
                }
            </style>

            <script type="text/javascript">


                var mgr1 = null;
                var mgr2 = null;
                var mgr3 = null;
                var mgr4 = null;
                var mgr5 = null;
                var mgr6 = null;


                $(document).ready(function () {
                    $("select[name='agreement']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });

                    $("select[name='contract_type']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });

                    mgr1 = $("select[name='approval_manager_level1']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });

                    mgr2 = $("select[name='approval_manager_level2']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });

                    mgr3 = $("select[name='approval_manager_level3']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });

                    mgr4 = $("select[name='approval_manager_level4']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });
                    mgr5 = $("select[name='approval_manager_level5']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });
                    mgr6 = $("select[name='approval_manager_level6']").selectize({
                        create: false,
                        sortField: "text",
                        highlight: true
                    });


                    //Setup
                    if ($("input[name='default']").is(":checked")) {
                        mgr1[0].selectize.disable();
                        mgr2[0].selectize.disable();
                        mgr3[0].selectize.disable();
                        mgr4[0].selectize.disable();
                        mgr5[0].selectize.disable();
                        mgr6[0].selectize.disable();
                    }

                    $('body').on('change', "select[name='approval_manager_level1']", function () {

                        if ($("input[name='default']").is(":checked")) {
                            $("[name='initial_review_day_level1']").attr("disabled", true);
                            $("[name='final_review_day_level1']").attr("disabled", true);
                            $("[value='level1']").attr("disabled", true);
                        } else {
                            if ($("select[name='approval_manager_level1']").val() == 0) {
                                $("[name='initial_review_day_level1']").attr("disabled", true);
                                $("[name='final_review_day_level1']").attr("disabled", true);
                                $("[value='level1']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level1']").attr("disabled", false);
                                $("[name='final_review_day_level1']").attr("disabled", false);
                                $("[value='level1']").attr("disabled", false);
                            }
                        }
                    });

                    $('body').on('change', "select[name='approval_manager_level2']", function () {

                        if ($("input[name='default']").is(":checked")) {
                            $("[name='initial_review_day_level2']").attr("disabled", true);
                            $("[name='final_review_day_level2']").attr("disabled", true);
                            $("[value='level2']").attr("disabled", true);
                        } else {
                            if ($("select[name='approval_manager_level2']").val() == 0) {
                                $("[name='initial_review_day_level2']").attr("disabled", true);
                                $("[name='final_review_day_level2']").attr("disabled", true);
                                $("[value='level2']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level2']").attr("disabled", false);
                                $("[name='final_review_day_level2']").attr("disabled", false);
                                $("[value='level2']").attr("disabled", false);
                            }
                        }
                    });

                    $('body').on('change', "select[name='approval_manager_level3']", function () {
                        if ($("input[name='default']").is(":checked")) {
                            $("[name='initial_review_day_level3']").attr("disabled", true);
                            $("[name='final_review_day_level3']").attr("disabled", true);
                            $("[value='level3']").attr("disabled", true);
                        } else {
                            if ($("select[name='approval_manager_level3']").val() == 0) {
                                $("[name='initial_review_day_level3']").attr("disabled", true);
                                $("[name='final_review_day_level3']").attr("disabled", true);
                                $("[value='level3']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level3']").attr("disabled", false);
                                $("[name='final_review_day_level3']").attr("disabled", false);
                                $("[value='level3']").attr("disabled", false);
                            }
                        }
                    });

                    $('body').on('change', "select[name='approval_manager_level4']", function () {

                        if ($("input[name='default']").is(":checked")) {
                            $("[name='initial_review_day_level4']").attr("disabled", true);
                            $("[name='final_review_day_level4']").attr("disabled", true);
                            $("[value='level4']").attr("disabled", true);
                        } else {
                            if ($("select[name='approval_manager_level4']").val() == 0) {
                                $("[name='initial_review_day_level4']").attr("disabled", true);
                                $("[name='final_review_day_level4']").attr("disabled", true);
                                $("[value='level4']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level4']").attr("disabled", false);
                                $("[name='final_review_day_level4']").attr("disabled", false);
                                $("[value='level4']").attr("disabled", false);
                            }
                        }
                    });

                    $('body').on('change', "select[name='approval_manager_level5']", function () {

                        if ($("input[name='default']").is(":checked")) {
                            $("[name='initial_review_day_level5']").attr("disabled", true);
                            $("[name='final_review_day_level5']").attr("disabled", true);
                            $("[value='level5']").attr("disabled", true);
                        } else {
                            if ($("select[name='approval_manager_level5']").val() == 0) {
                                $("[name='initial_review_day_level5']").attr("disabled", true);
                                $("[name='final_review_day_level5']").attr("disabled", true);
                                $("[value='level5']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level5']").attr("disabled", false);
                                $("[name='final_review_day_level5']").attr("disabled", false);
                                $("[value='level5']").attr("disabled", false);
                            }
                        }
                    });

                    $('body').on('change', "select[name='approval_manager_level6']", function () {

                        if ($("input[name='default']").is(":checked")) {
                            $("[name='initial_review_day_level6']").attr("disabled", true);
                            $("[name='final_review_day_level6']").attr("disabled", true);
                            $("[value='level6']").attr("disabled", true);
                        } else {
                            if ($("select[name='approval_manager_level6']").val() == 0) {
                                $("[name='initial_review_day_level6']").attr("disabled", true);
                                $("[name='final_review_day_level6']").attr("disabled", true);
                                $("[value='level6']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level6']").attr("disabled", false);
                                $("[name='final_review_day_level6']").attr("disabled", false);
                                $("[value='level6']").attr("disabled", false);
                            }
                        }
                    });

                    $('body').on('change', "input[name='default']", function () {

                        if ($(this).is(":checked")) {
                            $('[name=agreement]').trigger('change');

                            mgr1[0].selectize.disable();
                            mgr2[0].selectize.disable();
                            mgr3[0].selectize.disable();
                            mgr4[0].selectize.disable();
                            mgr5[0].selectize.disable();
                            mgr6[0].selectize.disable();
                        } else {
                            mgr1[0].selectize.enable();
                            mgr2[0].selectize.enable();
                            mgr3[0].selectize.enable();
                            mgr4[0].selectize.enable();
                            mgr5[0].selectize.enable();
                            mgr6[0].selectize.enable();

                            if ($("select[name='approval_manager_level1']").val() == 0) {
                                $("[name='initial_review_day_level1']").attr("disabled", true);
                                $("[name='final_review_day_level1']").attr("disabled", true);
                                $("[value='level1']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level1']").attr("disabled", false);
                                $("[name='final_review_day_level1']").attr("disabled", false);
                                $("[value='level1']").attr("disabled", false);
                            }

                            if ($("select[name='approval_manager_level2']").val() == 0) {
                                $("[name='initial_review_day_level2']").attr("disabled", true);
                                $("[name='final_review_day_level2']").attr("disabled", true);
                                $("[value='level2']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level2']").attr("disabled", false);
                                $("[name='final_review_day_level2']").attr("disabled", false);
                                $("[value='level2']").attr("disabled", false);
                            }
                            if ($("select[name='approval_manager_level3']").val() == 0) {
                                $("[name='initial_review_day_level3']").attr("disabled", true);
                                $("[name='final_review_day_level3']").attr("disabled", true);
                                $("[value='level3']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level3']").attr("disabled", false);
                                $("[name='final_review_day_level3']").attr("disabled", false);
                                $("[value='level3']").attr("disabled", false);
                            }
                            if ($("select[name='approval_manager_level4']").val() == 0) {
                                $("[name='initial_review_day_level4']").attr("disabled", true);
                                $("[name='final_review_day_level4']").attr("disabled", true);
                                $("[value='level4']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level4']").attr("disabled", false);
                                $("[name='final_review_day_level4']").attr("disabled", false);
                                $("[value='level4']").attr("disabled", false);
                            }
                            if ($("select[name='approval_manager_level5']").val() == 0) {
                                $("[name='initial_review_day_level5']").attr("disabled", true);
                                $("[name='final_review_day_level5']").attr("disabled", true);
                                $("[value='level5']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level5']").attr("disabled", false);
                                $("[name='final_review_day_level5']").attr("disabled", false);
                                $("[value='level5']").attr("disabled", false);
                            }
                            if ($("select[name='approval_manager_level6']").val() == 0) {
                                $("[name='initial_review_day_level6']").attr("disabled", true);
                                $("[name='final_review_day_level6']").attr("disabled", true);
                                $("[value='level6']").attr("disabled", true);
                            } else {
                                $("[name='initial_review_day_level6']").attr("disabled", false);
                                $("[name='final_review_day_level6']").attr("disabled", false);
                                $("[value='level6']").attr("disabled", false);
                            }
                        }
                    });


                    let contract_name_autocomplete_box_values = [];
                    $("input[name='contract_name_search']").on("keyup", function (e) {
                        $("select[name='contract_name']").css("display", "none");
                        $("#payment_type_autocomplete").css("display", "block");


                        let term = $(this).val();
                        $("#payment_type_autocomplete").empty();
                        //console.log(term);
                        $("select[name='contract_name'] > option").each(function () {
                            if (this.text.toLowerCase().indexOf(term) >= 0) {
                                //console.log("Matching:"+this.text);
                                $('#payment_type_autocomplete').append("<option value='" + this.value + "'>" + this.text + "</option>");
                            }
                        });

                    });


                    $('body').on('change', '#payment_type_autocomplete', function () {
                        $("select[name='contract_name']").val(this.value);
                        console.log("Updated Select Value is Now:" + $("select[name='contract_name']").val());
                    });

                    $('#btnRight').click(function (e) {
                        $('#physicianList > option:selected').appendTo('#selectedPhysicianList');

                        $("#selectedPhysicianList > option").each(function () {
                            $(this).prop("selected", true);
                        });

                        $('#selectedPhysicianListShow > option').remove();
                        var $options = $('#selectedPhysicianList > option').clone();
                        $('#selectedPhysicianListShow').append($options);

                        e.preventDefault();
                    });

                    $('#btnRightAll').click(function (e) {
                        $('#physicianList > option').appendTo('#selectedPhysicianList');

                        $("#selectedPhysicianList > option").each(function () {
                            $(this).prop("selected", true);
                        });

                        $('#selectedPhysicianListShow > option').remove();
                        var $options = $('#selectedPhysicianList > option').clone();
                        $('#selectedPhysicianListShow').append($options);

                        e.preventDefault();
                    });

                    $('#btnLeft').click(function (e) {
                        $('#selectedPhysicianList > option:selected').appendTo('#physicianList');

                        $("#selectedPhysicianList > option").each(function () {
                            $(this).prop("selected", true);
                        });

                        $('#selectedPhysicianListShow > option').remove();
                        var $options = $('#selectedPhysicianList > option').clone();
                        $('#selectedPhysicianListShow').append($options);

                        e.preventDefault();
                    });

                    $('#btnLeftAll').click(function (e) {
                        $('#selectedPhysicianList > option').appendTo('#physicianList');

                        $('#selectedPhysicianListShow > option').remove();
                        var $options = $('#selectedPhysicianList > option').clone();
                        $('#selectedPhysicianListShow').append($options);

                        e.preventDefault();
                    });


                });
                $(function () {
                    $('[data-toggle="tooltip"]').tooltip()
                })
            </script>

@endsection
