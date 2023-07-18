@php use function App\Start\is_super_user; @endphp
@php use function App\Start\is_super_hospital_user; @endphp
<script type="text/javascript" src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/jquery.dragoptions.min.js') }}"></script>

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

    /* ul {
        list-style-type: none;
        padding: 0;
        border: 1px solid #ddd;
    }

    ul li {
        padding: 8px 16px;
        border-bottom: 1px solid #ddd;
    }

    ul li:last-child {
        border-bottom: none
    } */

    .liborderstyle {
        padding: 10px 50px;
        margin-bottom: 0px;
        border-bottom: 1px solid #ccc;
    }

    select.small-dropdown {
        width: 24%;
    }

    .no-padding-top {
        padding-top: 0px !important;
    }

    #toggle .switch {
        margin-left: 3px;
    }

    .no-margin-left {
        margin-left: 0px !important;
    }
</style>

<!-- //Action-Redesign by 1254 -->
<style>

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
        margin-left: 1px;
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
        margin-left: 1px;
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
        padding: 1%;
        position: relative
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

    .actionCheckbox {
        float: left !important;
    }

    .actionWrap {
        max-width: 80%;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        float: left;
    }

    input.form-control.check {
        height: 20px;
        width: 20px;
    }

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


</style>

{{--@extends('layouts/_physician', [ 'tab' => 2 ])--}}
@extends('layouts/_physician_hospital_contract', ['tab' => 2])
@section('actions')
    @if (is_super_user())
        @if ($contract->manually_archived)
            <a class="btn btn-default btn-unarchive" href="{{ URL::route('contracts.unarchive', $contract->id) }}">
                <i class="fa fa-unlock fa-fw"></i> Unarchive
            </a>
        @else
            @if (strtotime($contract->manual_contract_end_date) < strtotime(date('Y-m-d')))
                <a class="btn btn-default btn-archive" href="{{ URL::route('contracts.archive', $contract->id) }}">
                    <i class="fa fa-lock fa-fw"></i> Archive
                </a>
            @endif
        @endif
    @endif
@endsection

<style>
    .info-cls {
        display: flex;
        align-items: center;
    }

    .info-icon {
        margin-right: 10px;
    }

    .no-info-icon {
        margin-right: 7px;
    }

    .right-field {
        display: inline-flex;
        width: 100%;
    }

</style>
@section('content')
    {{ Form::open([ 'class' => 'form form-horizontal form-create-physician' , 'enctype'=> 'multipart/form-data']) }}
    {{ Form::hidden('id', $contract->id) }}
    {{ Form::hidden('contract_type_id', $contract->contract_type_id , array('id' => 'contract_type_id')) }}
    {{ Form::hidden('payment_type_id', $contract->payment_type_id , array('id' => 'payment_type_id')) }}
    {{ Form::hidden('display_on_call_rate', $display_on_call_rate , array('id' => 'display_on_call_rate')) }}
    {{ Form::hidden('agreement_end_date', format_date($agreement_end_date) , array('id' => 'agreement_end_date')) }}
    {{ Form::hidden('agreement_valid_upto_date', format_date($agreement_valid_upto_date) , array('id' => 'agreement_valid_upto_date')) }}
    {{ Form::hidden('range_limit', $range_limit , array('id' => 'range_limit')) }}
    {{ Form::hidden('categories_count', $categories_count, array('id' => 'categories_count')) }}

    <input type="hidden" name="sorting_category_id" id="sorting_category_id" value="0">
    <input type="hidden" name="sorting_contract_data" id="sorting_contract_data" value="0">
    <input type="hidden" name="get_sorting_contract_data" id="get_sorting_contract_data"
           value="{{$sorting_contract_activities}}">
    <input type="hidden" name="contract_id" id="contract_id" value="{{$contract->id}}">
    <input type="hidden" name="agreement_id" id="agreement_id" value="{{$contract->agreement_id}}">
    <?php



    $quarterly_max_hours_div = '<div class="info-icon">  <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Max hours will be tallied on a yearly quarterly basis, regardless of payment frequency.">
                        </i></div>';
    $mandate_details_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When Yes all logs entered must have details included with log to submit.">
                </i></div>';
    $rate_selection = '   <div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When  ORANGE the contract will permit three rates with can be entered on any date.">
                        </i></div>';
    $hours_selection = '<div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When ORANGE a hourly duration bar will be available for all entries to select 0-24 hours.">
                        </i></div>';
    $burden_selection = ' <div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When ORANGE the rates will be dependent on each other to select, e.g. must submit On-Call rate prior to selecting Called Back rate on the same day.">
                        </i></div>';
    $holiday_selection = '<div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When ORANGE Physicians can select the Holiday
                   title="Free text box, can be any defined unit that is compensated at FMV rate.">
                </i></div>';
    $annual_max_hours_div = ' <div class="info-icon"> <i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Max Hours allowed for the year based on the agreement frequency start date.">
                </i></div>';
    $log_over_max_hour_flag = '<div class="info-icon"> <i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When YES contract will allow hours over max to be submitted but not compensated.">
                </i></div>';

    $On_Call_rate_div = '<div class="info-icon"> <i id="On_Call_rate_tooltip"  class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Is base rate, and must be selected before next rate.">
                </i></div>';
    $called_back_rate_div = '<div class="info-icon"> <i id="called_back_rate_tooltip"  class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Is additional rate that is available once On-Call is submitted for a date.">
                </i></div>';
    $called_in_rate_div = ' <div class="info-icon"> <i id="called_in_rate_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Is final rate available once On-Call and Called Back have been submitted for a date.">
                </i></div>';
    //Check Loop
    $on_call_uncompensated_rate_text = '<i  style="width: 23px;" class="fa fa-info-circle " aria-hidden="true" data-toggle="tooltip"
                                   data-placement="top"
                                   title="Allows you to add different amount of rates AFTER X amount of days e.g. days 1-5 pay
rate, On Call Rate 2 - days 6-31 pay rate."></i>';
    $custom_action_div = ' <div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title="When CHECKED there is an option from the Activities drop down to create a custom action for use with that log entry. When not CHECKED there is no ability for provider to add a custom action to log entry."></i></div>';

    $min_hours_div = ' <div class="info-icon"> <i id="min_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="If contract has min hours enter them here if not you have to enter 0 ">
                </i></div>';

    $max_hours_div = ' <div class="info-icon"> <i id="max_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Total hours available to enter for period based on payment frequency.">
                </i></div>';
    $prior_worked_hours_div = '<div class="info-icon">  <i class="fa fa-info-circle " id="prior_worked_units_hours" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Hours to be considered towards annual max hours when contract goes live that other than the true renewal date.">
                </i></div>';
    $prior_amount_paid_div = ' <div class="info-icon">  <i class="fa fa-info-circle " id="prior_amount_units_hours" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Dollars to be considered towards annual max hours when contract goes live that other than the true renewal date.">
                </i></div>';

    $fmv_rate_div = '<div class="info-icon"> <i id="rateId_tooltip" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Hourly Rate."></i></div>';
    $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is 90 days .">
                        </i></div>';

    $annual_max_shifts_tooltip = ' <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>';

    $on_call_uncompensated_rate_text_blank = ' <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>';

    $payment_type_id = $contract->payment_type_id;
    switch ($payment_type_id) {
        case "1":
            //Stipend
            $mandate_details_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When “Yes” all logs entered must have details included with log to submit.">
                </i></div>';
            // When “Checked” there is an option from the Activities drop down to create a  custom action for that log entry. Will not save
            $custom_action_div = '<div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title=" When “Checked” there is an option from the Activities drop down to create a
custom action for that log entry. Will not save.">
                    </i></div>';
            $min_hours_div = ' <div class="info-icon"> <i id="min_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Min hours required on a yearly average basis.">
                </i></div>';
            $quarterly_max_hours_div = '<div class="info-icon">  <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title=" Max hours will be tallied on a yearly quarterly basis, regardless of payment
frequency.">
                        </i></div>';
            $max_hours_div = '<div class="info-icon"> <i id="max_hours_div_tooltip" class="fa fa-info-circle lbl-circle" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="No effect.">
                </i></div>';

            $fmv_rate_div = '<div class="info-icon"> <i id="rateId_tooltip" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Amount of which will be multiplied by the expected hours."></i></div>';
            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is 365 days.">
                        </i></div>';
            break;
        case "2":
            //Hourly
            $custom_action_div = ' <div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title="When CHECKED there is an option from the Activities drop down to create a
custom action for use with that log entry. When not CHECKED there is no ability for provider to add a
custom action to log entry.">
                    </i></div>';

            $min_hours_div = ' <div class="info-icon"> <i id="min_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title=" If contract has min hours enter them here if not you have to enter 0.">
                </i></div>';
            $max_hours_div = '<div class="info-icon"> <i id="max_hours_div_tooltip" class="fa fa-info-circle lbl-circle" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Total hours available to enter for period based on payment frequency.">
                </i></div>';
            $prior_worked_hours_div = '<div class="info-icon"> <i class="fa fa-info-circle lbl-circle" id="prior_worked_units_hours" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Hours to be considered towards annual max hours when contract goes live that
other than the true renewal date.">
                </i></div>';
            $prior_amount_paid_div = ' <div class="info-icon">  <i class="fa fa-info-circle " id="prior_amount_units_hours" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Dollars to be considered towards annual max hours when contract goes live that
other than the true renewal date.">
                </i></div>';
            $fmv_rate_div = '<div class="info-icon"> <i id="rateId_tooltip" class="fa fa-info-circle " aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Hourly Rate."></i></div>';

            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is 365 days.">
                        </i></div>';
            break;
        case "3":
            //Per Diem
            $mandate_details_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When “Yes” all logs entered must have details included with log to submit.">
                </i></div>';
            $hours_selection = '<div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When “Orange” a hourly duration bar will be available for all entries to select 0-24 hours.">
                        </i></div>';

            $burden_selection = ' <div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When “Orange” the rates will be dependent on each other to select, e.g. must submit
On-Call rate prior to selecting Called Back rate on the same day .">
                        </i></div>';
            $On_Call_rate_div = '<div class="info-icon"> <i id="On_Call_rate_tooltip"  class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title=" Is base rate, and must be selected before next rate.">
                </i></div>';
            $called_back_rate_div = '<div class="info-icon"> <i id="called_back_rate_tooltip"  class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title=" Is additional rate that is available once On-Call is submitted for a date.">
                </i></div>';
            $called_in_rate_div = ' <div class="info-icon"> <i id="called_in_rate_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Is final rate available once On-Call and Called Back have been submitted for a date.">
                </i></div>';
            $holiday_selection = '<div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                   title="When ORANGE Physicians can select the HOLIDAY activities while logging their hours on any day, weekday, weekend, or public holiday.">
                </i></div>';

            $custom_action_div = ' <div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title="When CHECKED there is an option from the Activities drop down to create a custom action for that log entry. Will not save.">
                    </i></div>';

            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is 365 days.">
                        </i></div>';

            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is
90 days."></i></div>';

            $annual_max_hours_div = ' <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>';
            break;
        case "5":
            //Per Diem with Uncompensated Days
            $mandate_details_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When “Yes” all logs entered must have details included with log to submit.">
                </i></div>';
            $hours_selection = '<div class="info-icon"> <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="When “Orange” a hourly duration bar will be available for all entries to select 0-24 hours">
                        </i></div>';
            $on_call_uncompensated_rate_text = '<i  style="width: 23px;" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip"
                                   data-placement="top"
                                   title="Allows you to add different amount of rates AFTER X amount of days e.g. days 1-5 pay
rate, On Call Rate 2 - days 6-31 pay rate."></i>';

            $custom_action_div = ' <div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title="When CHECKED there is an option from the Activities drop down to create a custom action for that log entry. Will not save.">
                    </i></div>';
            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is
90 days.">
                        </i></div>';
            break;
        case "6":
            //Monthly Stipend
            $mandate_details_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When Yes all logs entered must have details included with log to submit.">
                </i></div>';
            $custom_action_div = '<div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title=" When “Checked” there is an option from the Activities drop down to create a
custom action for that log entry. Will not save.">
                    </i></div>';
            $min_hours_div = ' <div class="info-icon"> <i id="min_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Amount of hours required to release invoice, should be greater than 0 or will generate a
payment without any logs entered.">
                </i></div>';
            $quarterly_max_hours_div = '<div class="info-icon">  <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title=" Max hours will be tallied on a yearly quarterly basis, regardless of payment
frequency.">
                        </i></div>';
            $max_hours_div = '<div class="info-icon"> <i id="max_hours_div_tooltip" class="fa fa-info-circle lbl-circle" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="No effect.">
                </i></div>';

            $fmv_rate_div = '<div class="info-icon"> <i id="rateId_tooltip" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Amount of which will be multiplied by the expected hours."></i></div>';
            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is
        365 days .">
                        </i></div>';
            break;
        case "7":
            //Time Study
            $min_hours_div = ' <div class="info-icon"> <i id="min_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Minimum amount of hours to reach to be eligible for compensation.">
                </i></div>';
            $quarterly_max_hours_div = '<div class="info-icon">  <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Max hours will be tallied on a yearly quarterly basis, regardless of payment
frequency.">
                        </i></div>';
            $max_hours_div = '<div class="info-icon"><i id="max_hours_div_tooltip" class="fa fa-info-circle lbl-circle" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Total Max hours permitted in the payment frequency of the agreement.">
                </i></div>';

            $custom_action_div = ' <div class="info-icon"><i id="custom_action_div_tooltip"
                       class="fa fa-info-circle " aria-hidden="true"
                       data-toggle="tooltip" data-placement="top"
                       title="When CHECKED there is an option from the Activities drop down to create a custom action for that log entry. Will not save.">
                    </i></div>';
            break;
        case "8":
            //Per Unit
            $mandate_details_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="When “Yes” all logs entered must have details included with log to submit.">
                </i></div>';

            $units_div = '<div class="info-icon"><i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Free text box, can be any defined unit that is compensated at FMV rate.">
                </i></div>';
            $min_hours_div = ' <div class="info-icon"> <i id="min_hours_div_tooltip" class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="The minimum amount of units approved to qualify for payment.">
                </i></div>';
            $quarterly_max_hours_div = '<div class="info-icon">  <i class="fa fa-info-circle "
                           aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Max Units will be tallied on a yearly quarterly basis, regardless of payment
frequency.">
                        </i></div>';
            $max_hours_div = '<div class="info-icon"><i id="max_hours_div_tooltip" class="fa fa-info-circle lbl-circle" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Max units will be tallied on frequency set at the agreement level.">
                </i></div>';
            $annual_max_hours_div = ' <div class="info-icon"> <i class="fa fa-info-circle " aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Max units allowed for the year based on the agreement frequency start date.">
                </i></div>';
            $prior_worked_hours_div = '<div class="info-icon"> <i class="fa fa-info-circle lbl-circle" id="prior_worked_units_hours" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Unites to be considered towards annual max unites when contract goes live that other than the true renewal date.">
                </i></div>';
            $prior_amount_paid_div = ' <div class="info-icon">  <i class="fa fa-info-circle " id="prior_amount_units_hours" aria-hidden="true"
                   data-toggle="tooltip" data-placement="top"
                   title="Dollars to be considered towards annual max unites when contract goes live that other than the true renewal date.">
                </i></div>';

            $fmv_rate_div = '<div class="info-icon"> <i id="rateId_tooltip" class="fa fa-info-circle" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="Per Unit Rate."></i></div>';
            $contract_deadline_on_off = ' <div class="info-icon"> <i id="contract_deadline_on_off_tooltip"
                           class="fa fa-info-circle " aria-hidden="true"
                           data-toggle="tooltip" data-placement="top"
                           title="Amount of days that the contract will accept a new entry, default deadline is
365 days.">
                        </i></div>';
            break;

        default:
    }
    ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            General
            <a style="float: right; margin-top: -7px" class="btn btn-primary"
               href="{{ route('contracts.interfacedetails', [$contract->id,$practice->id]) }}">
                Interface Details
            </a>
            <a style="float: right; margin-top: -7px; margin-right: 5px" class="btn btn-primary"
               href="{{ route('contracts.copycontract', [$contract->id,$practice->id,$physician->id]) }}">
                Copy Contract
            </a>
            <!--physician to multiple hospital by 1254 -->
            <a style="float: right; margin-top: -7px; margin-right: 5px" class="btn btn-primary"
               href="{{ route('contracts.unapprovelogs', [$contract->id,$practice->id, $physician->id]) }}">
                Unapprove Logs
            </a>
            @if(is_super_user())
                <a style="float: right; margin-top: -7px; margin-right: 5px" class="btn btn-primary"
                   href="{{ route('contracts.show', [$contract->id]) }}">
                    Audit History
                </a>
            @endif
        </div>
        <div class="panel-body">
        <!--div class="form-group">
                <label class="col-xs-2 control-label">Agreement</label>

                <div class="col-xs-5">
                    {{ $agreement_name }}
                </div>
            </div-->
            <div class="form-group">
                <label class="col-xs-2 control-label"> Agreement</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        {{ Form::select('agreement', $agreements, Request::old('agreement',
                    $contract->agreement_id), ['class' => 'form-control' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label no-padding-top">Payment Type</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        {{ $payment_type }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label"> Contract Name</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <input class="contract-name-search" name="contract_name_search" type="text">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Selection</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        {{ Form::select('contract_name', $contract_names, Request::old('contract_name',
                    $contract->contract_name_id), ['class' => 'form-control' ]) }}
                        {{ Form::select('contract_name_autocomplete', $contract_names, Request::old('contract_name',
                    $contract->contract_name_id), ['class' => 'form-control' ], ['style' => 'display: none'])}}


                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="col-xs-2 control-label"> Contract Type</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        {{ Form::select('contract_type', $contract_types, Request::old('contract_type',
                    $contract->contract_type_id), ['class' => 'form-control' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group" id="supervision_type_div" style="display:none">
                <label class="col-xs-2 control-label">Supervision Type</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        {{ Form::select('supervision_type', $supervisionTypes, Request::old('supervision_type', $contract->supervision_type), [ 'class' => 'form-control' ]) }}
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-xs-2 control-label">Internal Notes</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div id="internal_notes" class="input-group">
                            {{ Form::textarea('internal_notes', Request::old('internal_notes', ($internal_notes) ? $internal_notes : ""), [ 'class' => 'form-control' ]) }}
                        </div>
                    </div>
                </div>
            </div>

            <!--CM FM For Contract-->
            <?php // $default = 0;
            $default = $contract->default_to_agreement;
            $managercount = count($ApprovalManagerInfo);
            $physician_opt_in_mail = $contract->physician_opt_in_email;
            ?>

            <div id="approval_feilds"
                 style="display: {{ $contract->agreement->approval_process == 1 ? 'block' : 'none' }}">
                <div class="form-group">
                    <label class="col-xs-2 control-label">Use Default</label>
                    <div class="info-cls">
                        <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div class="col-xs-1 right-field">
                            {{ Form::checkbox('default', 1, Request::old('default',$default), [ 'class' => 'form-control check' ]) }}
                        </div>
                    </div>
                    <div class="col-xs-9 d-block col-xs-offset-4">
                    <span class="help-block">
                        <span id="help_block_id" style="margin-left:-131px;">Use All Managers same as Agreement.</span>
                    </span>
                    </div>
                </div>
                <div class="approvalContainer">
                    <div class="tableHeading">
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
                            {{--                            {{ Form::select('approverTypeforLevel'.$i, $approval_manager_type, Request::old('approverTypeforLevel'.$i,$i<=$managercount?$ApprovalManagerInfo[$i-1]->type_id:0), [ 'class' => 'form-control approval_type' ]) }}--}}
                            {{--                        </div>--}}

                            <div class="col-md-3 col-sm-3 col-xs-3 paddingLeft">
                                {{ Form::select('approval_manager_level'.$i, $users, Request::old('approval_manager_level'.$i,$i<=$managercount?$ApprovalManagerInfo[$i-1]->user_id:0), [ 'class' => 'form-control' ]) }}
                            </div>

                            <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                                {{ Form::selectRange('initial_review_day_level'.$i, 1, $range_day, Request::old('initial_review_day_level'.$i,$i<=$managercount?$ApprovalManagerInfo[$i-1]->initial_review_day:$initial_review_day), [ 'class' => 'form-control' ]) }}
                            </div>

                            <div class="col-md-2 col-sm-1 col-xs-1 paddingLeft">
                                {{ Form::selectRange('final_review_day_level'.$i, 1, $range_day, Request::old('final_review_day_level'.$i,$i<=$managercount?$ApprovalManagerInfo[$i-1]->final_review_day:$final_review_day), [ 'class' => 'form-control' ]) }}
                            </div>
                            <div class="col-md-2 col-sm-2 col-xs-2">
                                <?php
                                //echo 'i'.$i.'emailstatus'.$ApprovalManagerInfo[$i-1]->opt_in_email_status;
                                if($i <= $managercount){if($ApprovalManagerInfo[$i - 1]->opt_in_email_status == 1)
                                {?>
                                <input type="checkbox" name="emailCheck[]" value="level{{$i}}" checked>
                                <?php }
                                else
                                {?>
                                <input type="checkbox" name="emailCheck[]" value="level{{$i}}">
                                <?php }
                                }else
                                {?>
                                <input type="checkbox" name="emailCheck[]" value="level{{$i}}" checked>
                                <?php } ?>

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
                                <select multiple="multiple" id="physicianList" name="physicianList[]"
                                        class="form-control" title="" style="height: 254px;overflow-x: scroll;">
                                    {{--                                    @if(Request::is('agreements/*'))--}}
                                    @foreach($hospitals_physicians as $physician_obj)
                                        <option value="{{ $physician_obj->id }}_{{$physician_obj->practice_id}}">{{ $physician_obj->physician_name }}
                                            ( {{$physician_obj->practice_name}} )
                                        </option>
                                    @endforeach
                                    {{--                                    @endif--}}
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
                                    {{--                                    @if(Request::is('contracts/*'))--}}
                                    @foreach($contract_physicians as $physician_obj)
                                        <option value="{{ $physician_obj->id }}_{{$physician_obj->practice_id}}"
                                                selected="true">{{ $physician_obj->physician_name }}
                                            ( {{$physician_obj->practice_name}} )
                                        </option>
                                    @endforeach
                                    {{--                                    @endif--}}
                                </select>
                            </div>
                            <p class="help-block">
                            </p>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-xs-2 control-label" style="padding-top: 0;">Physician Opt-in email</label>
                    <div class="info-cls">
                        <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div class="col-xs-1 right-field">
                            <!--<input type="checkbox" name="physician_emailCheck" class="physician_emailCheck" value="physician_emailCheck">-->
                            {{ Form::checkbox('physician_emailCheck', 1, Request::old('physician_emailCheck',$physician_opt_in_mail), [ 'class' => 'form-control check' ]) }}
                        </div>
                    </div>
                    <div class="col-xs-5">
                        <span class="help-block">
                            <span id=""></span>
                        </span>
                    </div>
                </div>

            <!--<div class="form-group">
                    <label class="col-xs-2 control-label">Contract Manager</label>

                    <div class="col-xs-5">
                        {{ Form::select('contract_manager', $users, Request::old('contract_manager',$default==0 ? $contract->contract_CM : $contract->agreement->contract_manager), [ 'class' => 'form-control' ]) }}
                    </div>
                        <div class="col-xs-5">{!! $errors->first('contract_manager', '<p class="validation-error">:message</p>') !!}</div>
                </div>
                <div class="form-group">
                    <label class="col-xs-2 control-label">Financial Manager</label>

                    <div class="col-xs-5">
                        {{ Form::select('financial_manager', $users, Request::old('financial_manager',$default==0 ? $contract->contract_FM :$contract->agreement->financial_manager), [ 'class' => 'form-control' ]) }}
                    </div>
                        <div class="col-xs-5">{!! $errors->first('financial_manager', '<p class="validation-error">:message</p>') !!}</div>
                </div>-->


                <input type="hidden" name="approval_process" id="approval_process"
                       value="{{$contract->agreement->approval_process}}">
                <input type="hidden" name="default_CM" id="default_CM"
                       value="{{$contract->agreement->contract_manager}}">
                <input type="hidden" name="default_FM" id="default_FM"
                       value="{{$contract->agreement->financial_manager}}">
                <input type="hidden" name="agreement_id" id="agreement_id" value="{{$contract->agreement_id}}">
            <!--input type="hidden" name="agreement" id="agreement" value="{{$contract->agreement_id}}"-->
            </div>

            <!--Mandate Details-->
            <div class="form-group" id="mandate_details_div">
                <label class="col-xs-2 control-label"> Mandate Log Details *</label>

                <div class="col-xs-5 info-cls">{!! $mandate_details_div !!}
                    <div class="right-field col-xs-3" style="padding-left: 3px;">
                        {{ Form::select('mandate_details', array('0' => 'No', '1' => 'Yes'), Request::old('mandate_details',$contract->mandate_details), ['class' =>
                    'form-control']) }}
                    </div>
                </div>
            </div>

            <!-- custom action enable label starts -->
            <div class="form-group" id="custom_action_div">
                <label class="col-xs-2 control-label"> Custom Action Enable</label>
                <div class="col-xs-5 info-cls">
                    {!! $custom_action_div !!}
                    <div class="right-field" style="margin-left: 3px;">
                        {{ Form::checkbox('custom_action_enable',1,Request::old('custom_action_enable',$contract->custom_action_enabled), [ 'class' => 'form-control check' ]) }}
                    </div>
                </div>
            </div>
            <!-- Partial Hours label -->
            <!-- call-coverage-duration  by 1254 -->

            <div class="form-group" id="hours_selection">
                <label class="col-xs-2 control-label">Partial Hours</label>
                <div class="col-xs-5 info-cls">
                    {!! $hours_selection !!}
                    <div id="toggle" class="input-group right-field">
                        <label class="switch">
                            {{ Form::checkbox('partial_hours', 1, Request::old('on_off',$contract->partial_hours), ['id' => 'partial_hours']) }}
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
                $on_call_uncompensated_rates = Session::get('on_call_uncompensated_rates');


            }
            ?>

        <!-- Per Diem with Uncompensated Days by 1254  -->
            <div class="form-group" id="hours_calculation_div" style="display:none;">
                <label class="col-xs-2 control-label"> Hours for Calculation</label>
                <div class="info-cls">
                    <div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        {{ Form::select('hours_calculation', $hours_calculations, Request::old('hours_calculation',$contract->partial_hours_calculation), ['class' => 'form-control small-dropdown','id' => 'hours_calculation' ]) }}
                    </div>
                </div>
                <div class="col-xs-12 validation-error" style="margin-left: 222px;margin-top: -48px;"><p
                            class="validation-error"></p>{{ $partial_hour_selection_error }}</div>

            </div>

            <!-- Burden of Call label -->
            <div class="form-group" id="burden_selection">
                <label class="col-xs-2 control-label">Burden of Call</label>
                <div class="col-xs-5 info-cls">
                    {!! $burden_selection !!}
                    <div id="toggle" class="input-group right-field">
                        <label class="switch">
                            {{ Form::checkbox('burden_on_off', 1, Request::old('on_off',$contract->burden_of_call), ['id' => 'burden_on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5"></div>
            </div>

            <!-- Holiday label start-->
            <div class="form-group" id="holiday_selection">
                <label class="col-xs-2 control-label">Holiday Action</label>
                <div class="col-xs-5 info-cls">
                    {!! $holiday_selection !!}
                    <div id="toggle" class="input-group right-field">
                        <label class="switch">
                            {{ Form::checkbox('holiday_on_off', 1, Request::old('holiday_on_off',$contract->holiday_on_off), ['id' => 'holiday_on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
                <div class="col-xs-5 d-block" style="margin-left:175px;color: #000; font-size: 12px;">

                    <span>Note: Once turned 'On', Physicians can select the 'Holiday' activities while logging their hours on any day, weekday, weekend, or public holiday.
                    </span></div>
            </div>
            <!-- Holiday label end-->

            <!--Enter PSA logs by day option-->
            <div class="form-group" id="logs_by_day_div">
                <label class="col-xs-2 control-label">Enter Logs by Day</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div id="toggle" class="input-group">
                            <label class="switch">
                                {{ Form::checkbox('enter_by_day', 1, Request::old('enter_by_day',$contract->enter_by_day), ['id' => 'enter_by_day']) }}
                                <div class="slider round"></div>
                                <div class="text"></div>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5 d-block col-xs-offset-2">
                <span class="help-block">
                <span id="help_block_id" style="margin-left: -131px;">Note: By default, logs for Professional Services Agreement contracts are entered for the entire month. Enable this option to allow logs to be entered for individual days in the month.
                    </span>
                 </span>
                </div>
                <div class="col-xs-5"></div>
            </div>

            <!--annual comp-->
            <div class="form-group" id="annual_comp_div">
                <label class="col-xs-2 control-label">Annual Comp</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="annual_comp_div_text">
                            {{ Form::text('annual_comp', Request::old('annual_comp',  formatNumber($contract_psa_metrics->annual_comp)), [ 'class' =>
                    'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5 d-block col-xs-offset-2">
                <span class="help-block" style="margin-left: 19px;">The Compensation rates must include the dollar amount followed by cents, for example
                        <strong>50000.00</strong>
                 </span>
                </div>
                <div class="col-xs-5"
                     id="annual_comp_error_div">{!! $errors->first('annual_comp', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp ninety-->
            <div class="form-group" id="annual_comp_ninety_div">
                <label class="col-xs-2 control-label">Annual Comp 90th</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="annual_comp_ninety_div_text">
                            {{ Form::text('annual_comp_ninety', Request::old('annual_comp_ninety',  formatNumber($contract_psa_metrics->annual_comp_ninety)), [ 'class' =>
                    'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_comp_ninety_error_div">{!! $errors->first('annual_comp_ninety', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu ninety-->
            <div class="form-group" id="wrvu_ninety_div">
                <label class="col-xs-2 control-label">wRVU 90th</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="wrvu_ninety_div_text">
                            {{ Form::text('wrvu_ninety', Request::old('wrvu_ninety',  $contract_psa_metrics->wrvu_ninety), [ 'class' =>
                    'form-control' ]) }}
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="wrvu_ninety_error_div">{!! $errors->first('wrvu_ninety', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual comp seventy five-->
            <div class="form-group" id="annual_comp_seventy_five_div">
                <label class="col-xs-2 control-label">Annual Comp 75th</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="annual_comp_seventy_five_div_text">
                            {{ Form::text('annual_comp_seventy_five', Request::old('annual_comp_seventy_five',  formatNumber($contract_psa_metrics->annual_comp_seventy_five)), [ 'class' =>
                    'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_comp_seventy_five_error_div">{!! $errors->first('annual_comp_seventy_five', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu seventy five-->
            <div class="form-group" id="wrvu_seventy_five_div">
                <label class="col-xs-2 control-label">wRVU 75th</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="wrvu_seventy_five_div_text">
                            {{ Form::text('wrvu_seventy_five', Request::old('wrvu_seventy_five',  $contract_psa_metrics->wrvu_seventy_five), [ 'class' =>
                    'form-control' ]) }}
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="wrvu_seventy_five_error_div">{!! $errors->first('wrvu_seventy_five', '<p class="validation-error">:message</p>') !!}</div>
            </div>


            <!--annual comp fifty-->
            <div class="form-group" id="annual_comp_fifty_div">
                <label class="col-xs-2 control-label">Annual Comp 50th</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="annual_comp_fifty_div_text">
                            {{ Form::text('annual_comp_fifty', Request::old('annual_comp_fifty',  formatNumber($contract_psa_metrics->annual_comp_fifty)), [ 'class' =>
                    'form-control' ]) }}
                            <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_comp_fifty_error_div">{!! $errors->first('annual_comp_fifty', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wrvu fifty-->
            <div class="form-group" id="wrvu_fifty_div">
                <label class="col-xs-2 control-label">wRVU 50th</label>

                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group" id="wrvu_fifty_div_text">
                            {{ Form::text('wrvu_fifty', Request::old('wrvu_fifty',  $contract_psa_metrics->wrvu_fifty), [ 'class' =>
                    'form-control' ]) }}
                        </div>
                    </div>
                </div>
                <div class="col-xs-5"
                     id="wrvu_fifty_error_div">{!! $errors->first('wrvu_fifty', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--wRVU Payments on/off-->
            <div class="approvalContainer" id="wrvu_payments_div">

                <div class="form-group">
                    <label class="col-xs-2 control-label">wRVU Payments</label>
                    <div class="info-cls">
                        <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div class="col-xs-5 right-field">
                            <div id="toggle" class="input-group">
                                <label class="switch">
                                    {{ Form::checkbox('wrvu_payments', 1, Request::old('wrvu_payments',$contract->wrvu_payments), ['id' => 'wrvu_payments']) }}
                                    <div class="slider round"></div>
                                    <div class="text"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-5 d-block col-xs-offset-2">
                <span class="help-block" style="margin-left: 19px;">Note: By enabling the wRVU payments option, wRVUs entered for each month will generate a payment. Leave disabled/off if using contract only for PSA monitoring.
                 </span>
                    </div>
                </div>

                <div class="form-group" id="contract_psa_wrvu_rates_div">
                    <!--add wrvu payments structure-->
                    {{ Form::hidden('contract_psa_wrvu_rates_count',Request::old('contract_psa_wrvu_rates_count',$contract_psa_wrvu_rates_count),['id' => 'contract_psa_wrvu_rates_count']) }}
                    <div id="contract_psa_wrvu_rates">
                        @for($i = 0; $i < Request::old('contract_psa_wrvu_rates_count',$contract_psa_wrvu_rates_count); $i++ )
                            <div>
                                <div class="form-group wrvu-range">
                                    <label class="col-xs-2 control-label">wRVU Range {{ $i+1 }}</label>

                                    <div class="col-xs-5">
                                        {{ Form::text("contract_psa_wrvu_ranges".($i+1), Request::old("contract_psa_wrvu_ranges".($i+1),isset($contract_psa_wrvu_ranges[$i+1]) ? $contract_psa_wrvu_ranges[$i+1] : ''), [ 'class' => 'form-control','id' => "contract_psa_wrvu_ranges".($i+1) ]) }}
                                        <span class="help-block">
                                        Note: Enter the upper bound for the range. For example, 500 means wRVUs from 1-500 would pay at the rate below. Enter 9999999 for the last range.
                                    </span>
                                    </div>
                                    <div class="col-xs-3">{!! $errors->first('contract_psa_wrvu_range'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                                </div>
                                <div class="form-group wrvu-rate">
                                    <label class="col-xs-2 control-label">Rate {{ $i+1 }}</label>

                                    <div class="col-xs-5">
                                        {{ Form::text("contract_psa_wrvu_rates".($i+1), Request::old("contract_psa_wrvu_rates".($i+1),isset($contract_psa_wrvu_rates[$i+1]) ? $contract_psa_wrvu_rates[$i+1] : ''), [ 'class' => 'form-control','id' => "contract_psa_wrvu_rates".($i+1) ]) }}
                                    </div>
                                    <div class="col-xs-2">
                                        <button class="btn btn-primary btn-submit remove-wrvu-rate" type="button"> -
                                        </button>
                                    </div>
                                    <div class="col-xs-3">{!! $errors->first('contract_psa_wrvu_rates'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                                </div>
                                <hr>
                            </div>
                        @endfor
                    </div>
                    <button class="btn btn-primary btn-submit add-wrvu-rate" type="button">Add wRVU Range and Rate
                    </button>
                </div>

            </div>
            <!-- units -->
            @if($payment_type=='Per Unit')
                <div class="form-group" id="units_div" style="display:block">
                    <label class="col-xs-2 control-label">Units</label>
                    <div class="col-xs-5 info-cls">
                        {!! $units_div !!}
                        <div id="units_div_text" class="right-field">
                            {{ Form::text('units', Request::old('units', $units), [ 'class' => 'form-control' ]) }}
                        </div>
                    </div>
                    <div class="col-xs-5"
                         id="units_error_div">{!! $errors->first('units', '<p class="validation-error">:message</p>') !!}</div>
                </div>
        @endif

        <!--min hours-->
            <div class="form-group" id="min_hours_div">
                <label id="lbl_min_hours" class="col-xs-2 control-label">Min Hours *</label>

                <div class="col-xs-5 info-cls"> {!! $min_hours_div !!}
                     
                    <div class="right-field">
                        @if($payment_type=='Per Unit')
                            {{ Form::text('min_hours', Request::old('min_hours',  round($contract->min_hours, 0)), [ 'class' =>
                            'form-control' ]) }}
                        @else
                            {{ Form::text('min_hours', Request::old('min_hours',  formatNumber($contract->min_hours)), [ 'class' =>
                        'form-control' ]) }}
                        @endif
                    </div>
                </div>
                <div id="min_hours_error_div"
                     class="col-xs-5">{!! $errors->first('min_hours', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--weekday rate-->
        <!-- <div class="form-group" id="weekday_rate_div">
                <label class="col-xs-2 control-label">Weekday Rate</label>

                <div class="col-xs-5">
                    <div class="input-group">
                        {{ Form::text('weekday_rate', Request::old('weekday_rate',  formatNumber($contract->weekday_rate)), [ 'class' =>
                        'form-control' ]) }}
                <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
                <div class="col-xs-5">{!! $errors->first('weekday_rate', '<p class="validation-error">:message</p>') !!}</div>
            </div> -->

            <!-- quatrely_max_hours_fuctionality toggle -->
            <div class="form-group" id="quarterly_max_hours_div">
                <label id="lbl_Quarterly_max_hours" class="col-xs-2 control-label">Quarterly Max Hours</label>


                <div class="col-xs-5 info-cls">
                    {!! $quarterly_max_hours_div !!}
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('quarterly_max_hours',1, Request::old('quarterly_max_hours',$contract->quarterly_max_hours), ['id' => 'quarterly_max_hours']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>

                </div>
                <div class="col-xs-5"></div>
            </div>

            <!-- quaterly max hours fuctionality toggle -->
            <!--Annual Max Payment-->
            <div class="form-group" id="annual_max_div" style="display:none;">
                <label class="col-xs-2 control-label">Annual Max Payment</label>
                <div class="info-cls">
                    <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="col-xs-5 right-field">
                        <div class="input-group">
                            {{ Form::text('annual_max_payment',Request::old('annual_max_payment', formatNumber($contract->annual_max_payment)), [ 'class' => 'form-control' ]) }}
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
                <div class="col-xs-5 info-cls">
                    {!! $annual_max_shifts_tooltip !!}
                    <div class="input-group" id="annual_max_shifts_div_text right-field">
                        {{ Form::text('annual_max_shifts', Request::old('annual_max_shifts', $contract->annual_cap), [ 'class' => 'form-control', 'maxlength' => 5, 'autocomplete' => "off" ]) }}
                    </div>
                </div>
                <div class="col-xs-5"
                     id="annual_max_shifts_error_div">{!! $errors->first('annual_max_shifts', '<p class="validation-error">:message</p>') !!}</div>
            </div>
            <!--Annual Max Shifts End-->

            <!--max hours-->
            <div class="form-group" id="max_hours_div">
                <label id="lbl_max_hours" class="col-xs-2 control-label">Max Hours *</label>
                <div class="col-xs-5 info-cls">
                    {!! $max_hours_div !!}
                    <div class="input-group right-field" style="margin-left: 3px;">
                        @if($payment_type=='Per Unit')
                            {{ Form::text('max_hours', Request::old('max_hours', round($contract->max_hours, 0)), [ 'class' =>
                        'form-control' ]) }}
                        @else
                            {{ Form::text('max_hours', Request::old('max_hours', formatNumber($contract->max_hours)), [ 'class' =>
                        'form-control' ]) }}
                        @endif
                    </div>
                </div>
                <div id="max_hours_error_div"
                     class="col-xs-5">{!! $errors->first('max_hours', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--annual max hours-->
            <div class="form-group" id="annual_max_hours_div">
                <label id="lbl_annual_max_hours" class="col-xs-2 control-label">Annual Max Hours</label>
                <div class="col-xs-5 info-cls">
                    {!! $annual_max_hours_div !!}
                    <div class="right-field">
                        @if($payment_type=='Per Unit')
                            {{ Form::text('annual_cap', Request::old('annual_cap', round($contract->annual_cap)), [ 'class' =>
                        'form-control' ]) }}
                        @else
                            {{ Form::text('annual_cap', Request::old('annual_cap', formatNumber($contract->annual_cap)), [ 'class' =>
                        'form-control' ]) }}
                        @endif
                    </div>
                </div>
                <div id="annual_max_hours_error_div"
                     class="col-xs-5">{!! $errors->first('annual_cap', '<p class="validation-error">:message</p>') !!}</div>
            </div>

            <!--Allow Log Over Max Hour ON/OFF-->
            <div class="form-group" id="log_over_max_hour_flag">
                <label class="col-xs-2 control-label"> Log Over Max Hour *</label>
                <div class="col-xs-5 info-cls">
                    {!! $log_over_max_hour_flag !!}
                    <div class="right-field">
                        {{ Form::select('log_over_max_hour', array('0' => 'No', '1' => 'Yes'), Request::old('log_over_max_hour',$contract->allow_max_hours), ['class' =>
                    'form-control']) }}
                    </div>
                </div>
                <div class="col-xs-5 info-cls"></div>
            </div>

        @if($payment_type=='Hourly' || $payment_type=='Per Unit')
            <!--Prior start date-->
                <div class="form-group" id="prior_start_date_div">
                    <label class="col-xs-2 control-label">Prior Start Date</label>
                    <div class="col-xs-5 info-cls">
                        <div class="info-icon"></div>
                        @if($contract->prior_start_date == '0000-00-00')
                            <div class="col-xs-10 right-field">
                                <div id="prior_start_date" class="input-group">
                                    {{ Form::text('prior_start_date', Request::old('prior_start_date',format_date(date("Y/m/d"))), [ 'class' => 'form-control', 'id'=>'prior_start_date_field' ]) }}
                                    <span class="input-group-addon calendar" style="visibility:hidden;"><i
                                                class="fa fa-calendar fa-fw"></i></span>
                                </div>
                            </div>
                            <div class="col-xs-2 right-field">
                                {{ Form::checkbox('contract_prior_start_date_on_off', '0', Request::old('default',0), ['id' => 'contract_prior_start_date_on_off','class' => 'form-control check' ]) }}
                            </div>
                        @else
                            <div class="col-xs-10 info-cls"">
                            <div id="prior_start_date" class="input-group right-field">
                                {{ Form::text('prior_start_date', Request::old('prior_start_date',format_date($contract->prior_start_date)), [ 'class' => 'form-control', 'id'=>'prior_start_date_field' ]) }}
                                <span class="input-group-addon calendar" style="visibility:visible;"><i
                                            class="fa fa-calendar fa-fw"></i></span>

                            </div>
                    </div>
                    <div class="col-xs-2 right-field">
                        {{ Form::checkbox('contract_prior_start_date_on_off', '1', Request::old('default',1), ['id' => 'contract_prior_start_date_on_off','class' => 'form-control check' ]) }}
                    </div>
                    @endif
                </div>
        </div>

        <!--Prior Worked hours-->
        <div class="form-group" id="prior_worked_hours_div">
            <label id="lbl_prior_worked_hours" class="col-xs-2 control-label">Prior Worked Hours</label>
            <div class="col-xs-5 info-cls">
                {!! $prior_worked_hours_div !!}
                <div class="right-field">
                    @if($payment_type=='Per Unit')
                        {{ Form::text('prior_worked_hours', Request::old('prior_worked_hours', round($contract->prior_worked_hours, 0)), [ 'class' =>
                        'form-control' ]) }}
                    @else
                        {{ Form::text('prior_worked_hours', Request::old('prior_worked_hours', formatNumber($contract->prior_worked_hours)), [ 'class' =>
                        'form-control' ]) }}
                    @endif
                </div>
            </div>

        </div>

        <!--Prior Amount Paid-->
        <div class="form-group" id="prior_amount_paid_div">
            <label class="col-xs-2 control-label">Prior Amount Paid</label>
            <div class="col-xs-5 info-cls">
                {!! $prior_amount_paid_div !!}
                <div class="right-field">
                    {{ Form::text('prior_amount_paid', Request::old('prior_amount_paid', formatNumber($contract->prior_amount_paid)), [ 'class' =>
                    'form-control' ]) }}
                </div>
            </div>

        </div>
    @endif
    <!--rate change checkbox-->
        <div class="form-group">
            <label class="col-xs-2 control-label" id="changeRateID">Change Rate</label>
            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div class="input-group">
                        {{ Form::checkbox('change_rate_check', 1, Request::old('change_rate',0), [ 'class' => 'form-control check' ]) }}</label>
                    </div>
                </div>
            </div>
        </div>

        <!--weekday rate-->
        <div class="form-group" id="weekday_rate_div">
            <label class="col-xs-2 control-label">Weekday Rate *</label>
            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div class="input-group">
                        {{ Form::text('weekday_rate', Request::old('weekday_rate',  formatNumber($contract->weekday_rate)), [ 'class' =>
                        'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-5">{!! $errors->first('weekday_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <!--weekend rate-->
        <div class="form-group" id="weekend_rate_div">
            <label class="col-xs-2 control-label">Weekend Rate *</label>
            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div class="input-group">
                        {{ Form::text('weekend_rate', Request::old('weekend_rate', formatNumber($contract->weekend_rate)), [ 'class' =>
                        'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-5">{!! $errors->first('weekend_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <!--rate-->
        <div class="form-group" id="fmv_rate_div">
            <label class="col-xs-2 control-label" id="rateID">FMV Rate *</label>
            <div class="col-xs-5 info-cls">
                {!! $fmv_rate_div !!}
                <div class="input-group right-field" style="margin-left: 12px;">
                    {{ Form::text('rate', Request::old('rate',formatNumber($contract->rate)), [ 'class' => 'form-control'
                        ]) }}
                    <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                </div>
            </div>
            <div class="col-xs-5 d-block col-xs-offset-2" style="margin-left: 172px;">
                      <span class="help-block">
                The rate must include the dollar amount followed by cents, for example
                <strong>50.75</strong>.
               </span>
            </div>
            <div class="col-xs-5">{!! $errors->first('rate', '<p class="validation-error">:message</p>') !!}</div>

        </div>

        <!--holiday rate-->
        <div class="form-group" id="holiday_rate_div">
            <label class="col-xs-2 control-label">Holiday Rate *</label>
            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div class="input-group">
                        {{ Form::text('holiday_rate', Request::old('holiday_rate',formatNumber($contract->holiday_rate)), [ 'class' => 'form-control'
                        ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>

            </div>
            <div class="col-xs-5 d-block col-xs-offset-2" style="margin-left: 172px;">
              <span class="help-block">
                The Holiday rate must include the dollar amount followed by cents, for example
                <strong>50.75</strong>.
              </span></div>
            <div class="col-xs-5">{!! $errors->first('holiday_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <!--On-Call Rate-->
        <div class="form-group" id="On_Call_rate_div">
            <label class="col-xs-2 control-label">On-Call Rate *</label>
            <div class="info-cls col-xs-5 ">
                {!! $On_Call_rate_div !!}
                <div class="right-field">
                    <div class="input-group">
                        {{ Form::text('On_Call_rate', Request::old('On_Call_rate',formatNumber($contract->on_call_rate)), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-5"
                 id="On_Call_rate_error_div">{!! $errors->first('On_Call_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <!--Called Back Rate-->
        <div class="form-group" id="called_back_rate_div">
            <label class="col-xs-2 control-label">Called Back Rate *</label>
            <div class="info-cls col-xs-5 ">
                {!! $called_back_rate_div !!}
                <div class="right-field">
                    <div class="input-group">
                        {{ Form::text('called_back_rate', Request::old('called_back_rate',formatNumber($contract->called_back_rate)), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-5"
                 id="called_back_rate_error_div">{!! $errors->first('called_back_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <!--Called In Rate-->
        <div class="form-group" id="called_in_rate_div">
            <label class="col-xs-2 control-label">Called In Rate *</label>
            <div class="info-cls col-xs-5">
                {!! $called_in_rate_div !!}
                <div class="right-field">
                    <div class="input-group">
                        {{ Form::text('called_in_rate', Request::old('called_in_rate',formatNumber($contract->called_in_rate)), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-5 d-block col-xs-offset-2" style="margin-left: 172px;">
              <span class="help-block">
                <span id="help_block_id">The rate's must include the dollar amount followed by cents, for example
                    </span>
                <strong>50.75</strong>.
              </span>
            </div>
            <div class="col-xs-5"
                 id="called_in_rate_error_div">{!! $errors->first('called_in_rate', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <!--Contract rate change effective start date-->
        <div class="form-group" id="change_rate_start_date_div">
            <label class="col-xs-2 control-label"> Rate Effective Start Date</label>
            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-3 right-field">
                    {{ Form::select('change_rate_start_date',$dates['start_dates'], Request::old('change_rate_start_date'), [ 'id'=> 'change_rate_start_date','class' => 'form-control' ]) }}
                </div>
            </div>
        </div>

    <?php
    $range_start_days = range(0, $range_limit);
    unset($range_start_days[0]);
    $uncompensated_error = [];
    if (Session::has('on_call_rate_error')) {
        $uncompensated_error = Session::get('on_call_rate_error');
    }

    ?>
    <!-- //Per Diem with Uncompensated Days by 1254  -->
        {{ Form::hidden('on_call_rate_count',Request::old('on_call_rate_count',$on_call_rate_count),['id' => 'on_call_rate_count']) }}


        <div id="on_call_uncompensated_rate">


            @foreach($on_call_uncompensated_rates as $key => $rates)
                <div id="on-call-rate-div{{$rates['rate_index']}}">
                    <div class="col-xs-12 on-call-rate">
                        <label class="col-xs-2 control-label" id="on_call_rate_label_{{$rates['rate_index']}}">On Call
                            Rate {{ $rates['rate_index'] }}
                        </label>
                        <div class="col-xs-4 form-group info-cls">
                            @if($rates['rate_index']  == 1)

                                {!! $on_call_uncompensated_rate_text !!}

                            @endif

                            @if($rates['rate_index']  != 1)

                                {!! $on_call_uncompensated_rate_text_blank !!}

                            @endif
                            <div class="input-group right-field">
                                {{ Form::text('rate'.($rates['rate_index']), Request::old('rate'.($rates['rate_index']), formatNumber((float)$rates['rate'],2)), [ 'class' => 'col-xs-5 form-control','id' => "rate".($rates['rate_index']),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none','readonly'=>'true'  ]) }}
                                <span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>
                            </div>
                        </div>
                        <div class="col-xs-6 form-group">
                            <label class="col-xs-3 control-label" style="width:20%; padding-top:0px;">Days
                                Entered:</label>
                            <div class="col-xs-3"
                                 style="width:24%;">{{ Form::select('start_day'.($rates['rate_index']),$range_start_days , Request::old('start_day'.($rates['rate_index']),$rates['range_start_day']), ['class' =>'form-control','id'=>'start_day'.($rates['rate_index']), 'disabled' => true] ) }}</div>
                            {{ Form::hidden('start_day_hidden'.($rates['rate_index'])) ,'1',1,['id' => 'start_day_hidden'.($rates['rate_index'])] }}
                            <div class="col-xs-3"
                                 style="width:24%;">{{ Form::select('end_day'.($rates['rate_index']),$range_start_days, Request::old('end_day'.($rates['rate_index']),$rates['range_end_day']), ['class' =>'form-control','id'=>'end_day'.($rates['rate_index']),'disabled' => true, 'onchange' => 'rangechange( '. ($rates['rate_index']) .' )']) }}</div>
                            {{ Form::hidden('end_day_hidden'.($rates['rate_index'])) ,'1',1,['id' => 'end_day_hidden'.($rates['rate_index'])] }}
                            @if($key != 0)
                            <!-- <div class="col-xs-2" id="btn-uncompensated-remove"><button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate" referId="on-call-rate-div{{$rates['rate_index']}}" type="button" onClick="changeindex({{ $rates['rate_index'] }})"> - </button></div> -->
                                <div class="col-xs-2" id="btn-uncompensated-remove">
                                    <button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate"
                                            id="btn-remove-uncompensated{{$rates['rate_index']}}"
                                            value='{{$rates['rate_index']}}' type="button"
                                            onClick="removeRangeCustom(this);"> -
                                    </button>
                                </div>
                            @endif

                        </div>
                    </div>
                    <div class="col-xs-12 on-call-rate">
                        @foreach($uncompensated_error as $key=>$value)
                            @if($key == ($rates['rate_index']))

                                <div class="col-xs-12" style="margin-left: 126px;margin-top: -21px;"><p
                                            class="validation-error">{{ $value }}</p></div>
                            @endif
                        @endforeach
                    </div>
                </div>

            @endforeach
        </div>
        <button class="btn btn-primary btn-submit add-on-call-uncompensated-rate" id="add-uncompensaed-btn"
                type="button" style="display:none">Add On Call Rate
        </button>
        <!-- state attestations monthly toggle -->
        <div class="form-group" id="state_attestations_monthly_div" style="display:none">
            <label id="lbl_Quarterly_max_hours" class="col-xs-2 control-label">State Attestations Monthly</label>

            <div class="col-xs-5">
                <div id="toggle" class="input-group">
                    <label class="switch">
                        {{ Form::checkbox('state_attestations_monthly', 1, Request::old('state_attestations_monthly', $contract->state_attestations_monthly), ['id' => 'state_attestations_monthly']) }}
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

            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div id="toggle" class="input-group">
                        <label class="switch">
                            {{ Form::checkbox('state_attestations_annually', 1, Request::old('state_attestations_annually', $contract->state_attestations_annually), ['id' => 'state_attestations_annually']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-xs-5"></div>
        </div>

        <!--Contract deadline option-->
        <div class="form-group">
            <label class="col-xs-2 control-label">Contract Deadline Option</label>
            <div class="info-cls col-xs-5" style="margin-left: 5px">
                {!! $contract_deadline_on_off !!}
                <div class="right-field">
                    <div id="toggle" class="input-group">
                        <label class="switch">

                            {{ Form::checkbox('contract_deadline_on_off', 1, Request::old('contract_deadline_on_off',$contract->deadline_option), ['id' => 'contract_deadline_on_off']) }}
                            <div class="slider round"></div>
                            <div class="text"></div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-xs-5 d-block col-xs-offset-2" style="margin-left:24px;">
                <span id="help_block_id" style="color: #000; font-size: 12px;">Note: By enabling contract deadline option, predefined contract deadline values will be overwritten by Deadline Days value.
                    </span>
            </div>
        </div>

        <div class="form-group" id="deadline_days_div" style="display:block;">
            <label class="col-xs-2 control-label">Deadline Days</label>
            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    {{ Form::input('number','deadline_days', Request::old('deadline_days',$contract_deadline_days), [ 'class' => 'form-control','id' => 'deadline_days','min'=> 1 ]) }}
                </div>
            </div>
            <div class="col-xs-5">{!! $errors->first('deadline_Days', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <!--End Contract deadline option-->

        <div class="form-group">
            <label class="col-xs-2 control-label"> Upload Contract Copy</label>

            <div class="col-xs-10">
                {{--                    @php($istart=1)--}}
                {{--                    @if(count($contract_document)==1)--}}
                {{--                        @php($istart=2)--}}
                {{--                        <div class="col-xs-10">--}}
                {{--                            <div class="col-xs-6">--}}
                {{--                                {{ Form::file('upload_contract_copy_0', array('id' => 'upload_contract_copy_0' , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}--}}
                {{--                            </div>--}}
                {{--                            <div class="col-xs-5">--}}
                {{--                                @if(!empty($contract_document[0]))--}}
                {{--                                    <div class="col-xs-4" style="margin-top:15px;"><a href="{{ URL::route('contract.document',  $contract_document[0]->filename)}}">{{$contract_document[0]->filename}}</a></div>--}}
                {{--                                @else--}}
                {{--                                    <div class="col-xs-4">&nbsp;</div>--}}
                {{--                                @endif--}}
                {{--                            </div>--}}
                {{--                        </div>--}}

                {{--                    @endif--}}


                @for($i=1;$i<=6;$i++)
                    @php($found = false)
                    @foreach($contract_document as $document)



                        @if(strpos($document->filename,"_".$i."_") !== false)
                            @php($found = true)
                            <div class="col-xs-10">
                                <div class="col-xs-6">
                                    {{ Form::file('upload_contract_copy_'.$i, array('id' => 'upload_contract_copy_'.$i , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                                    {!! $errors->first('upload_contract_copy_'.$i, '<p class="validation-error">:message</p>') !!}
                                </div>
                                <div class="col-xs-5">
                                    @if(!empty($contract_document[0]))
                                        <div class="col-xs-4" style="margin-top:15px;"><a
                                                    href="{{ URL::route('contract.document',  $document->filename)}}">{{$document->filename}}</a>
                                        </div>
                                    @else
                                        <div class="col-xs-4">&nbsp;</div>
                                    @endif
                                </div>
                            </div>

                        @endif



                    @endforeach

                    @if(!$found)
                        <div class="col-xs-10">
                            <div class="col-xs-6">
                                {{ Form::file('upload_contract_copy_'.$i, array('id' => 'upload_contract_copy_'.$i , 'type' => 'file','accept' => '.pdf','class' => 'form-control','style'=>'margin-top:10px;' )) }}
                                {!! $errors->first('upload_contract_copy_'.$i, '<p class="validation-error">:message</p>') !!}
                            </div>
                            <div class="col-xs-5">
                                <div class="col-xs-4">&nbsp;</div>
                            </div>
                        </div>
                    @endif

                @endfor


            </div>

        </div>


        <!--use agreements dates option-->
        <div class="form-group">
            <label class="col-xs-2 control-label">Use Agreements Dates</label>

            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div class="input-group">
                        {{ Form::checkbox('default_dates', 1, Request::old('default_dates',$contract->default_to_agreement_dates), [ 'class' => 'form-control check' ]) }}</label>
                    </div>
                </div>
            </div>
            <div class="d-block col-xs-offset-2">
                <span class="help-block" style="margin-left:38px;">Note: By enabling contract end date and valid upto dates will be same as agreement end date and valid upto date.
                </span>
            </div>
        </div>

        <!-- edit manual end date starts -->
        <div class="form-group">
            <label class="col-xs-2 control-label">Manual End Date*</label>

            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div id="edit_manual_end_date" class="input-group">
                        {{ Form::text('edit_manual_end_date', Request::old('edit_manual_end_date', format_date($contract->manual_contract_end_date)), [ 'class' => 'form-control']) }}
                        <span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-4">{!! $errors->first('edit_manual_end_date', '<p class="validation-error">:message</p>') !!}</div>
        </div>

        <!-- edit manual end date ends -->
        <!-- valid upto date label starts -->
        <div class="form-group">
            <label class="col-xs-2 control-label">Manual Valid Upto Date *</label>

            <div class="info-cls">
                <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="col-xs-5 right-field">
                    <div id="edit_valid_upto_date" class="input-group">
                        {{ Form::text('edit_valid_upto_date', Request::old('edit_valid_upto_date', format_date($contract->manual_contract_valid_upto)), [ 'class' => 'form-control' ]) }}
                        <span class="input-group-addon calendar"><i class="fa fa-calendar fa-fw"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-xs-5">{!! $errors->first('edit_valid_upto_date', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <!-- valid upto date label ends -->

        <!-- Receipient start -->
        <div class="form-group" id="div_receipient1" style="display:none">
            <label class="col-xs-2 control-label">Recipient #1</label>

            <div class="col-xs-5">
                {{ Form::text('receipient1', Request::old('receipient1', $contract->receipient1), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('receipient1', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group" id="div_receipient2" style="display:none">
            <label class="col-xs-2 control-label">Recipient #2</label>

            <div class="col-xs-5">
                {{ Form::text('receipient2', Request::old('receipient2', $contract->receipient2), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('receipient2', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <div class="form-group" id="div_receipient3" style="display:none">
            <label class="col-xs-2 control-label">Recipient #3</label>

            <div class="col-xs-5">
                {{ Form::text('receipient3', Request::old('receipient3', $contract->receipient3), [ 'class' => 'form-control' ]) }}
            </div>
            <div class="col-xs-5">{!! $errors->first('receipient3', '<p class="validation-error">:message</p>') !!}</div>
        </div>
        <!-- Receipient end -->

        <!--add invoice notes-->
        <?php
        // if($note_count < App\InvoiceNote::CONTRACTCOUNT){
        //     $note_count = App\InvoiceNote::CONTRACTCOUNT;
        // }
        // if($invoice_type == 1){
        //     if($note_count < App\InvoiceNote::CONTRACTCOUNT){
        //         $note_count = App\InvoiceNote::CONTRACTCOUNT;
        //     }
        // } else {
        //     if($note_count < 1){
        //         $note_count = 1;
        //     }
        // }
        if ($note_count == 0) {
            $note_count = App\InvoiceNote::CONTRACTCOUNT;
        }
        ?>
        {{ Form::hidden('note_count',Request::old('note_count',$note_count),['id' => 'note_count']) }}
        <div id="notes">
            @for($i = 0; $i < Request::old('note_count',$note_count); $i++ )
                <div class="form-group invoive-note">
                    <label class="col-xs-2 control-label">Invoice Note {{ $i+1 }}</label>
                    <div class="info-cls">
                        <div class="info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                        <div class="col-xs-6">
                            {{ Form::textarea("note".($i+1), Request::old("note".($i+1), (isset($invoice_notes[$i+1]) ) ? $invoice_notes[$i+1] : ''), [ 'class' => 'form-control','id' => "note".($i+1),'maxlength' => 50, 'rows' => 2, 'cols' => 54, 'style' => 'resize:none' ]) }}
                        </div>
                        <div class="col-xs-1">
                            <button class="btn btn-primary btn-submit remove-note" type="button"
                                    style="margin-right: 0px; margin-top: 0px;"> -
                            </button>
                        </div>
                    </div>


                    <div class="col-xs-3">{!! $errors->first('note'.($i+1), '<p class="validation-error">:message</p>') !!}</div>
                </div>
            @endfor

        </div>
        <button class="btn btn-primary btn-submit add-note" type="button">Add Invoice Note</button>
        </br></br>

        <!--START add payment management-->
        <a class="btn btn-primary"
           href="{{ route('contracts.paymentmanagement', [$contract->id,$practice->id, $physician->id]) }}">Payment
            Management</a>
        <!--END payment management-->

        <!-- ** Need to start from here after break ** -->
    </div>
    </div>
    <div id="activities" class="panel panel-default">
        <!-- //Action-Redesign by 1254 : 12022020 -->
        <div class="panel-heading"
             style="height: 60px !important;padding-top: 20px !important;font-size: 16px !important;font-weight: 600 !important;">
            <span style="float:left;">Activities</span>
            @if($contract->payment_type_id != App\PaymentType::PER_DIEM && $contract->payment_type_id != App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS && $contract->payment_type_id != App\PaymentType::REHAB)
                <div style="float:left; width:65%; padding: 0px 1% 0px 1%;"><span style="float:left;">  <button
                                type="button" id="sorting" name="sorting" class="btn btn-primary"
                                style="margin-right:0%; float: right;margin-top: -8px; width:65px;float: right"
                                data-toggle="modal" data-target="#contract_sorting_modal">Sort</button></span></div>
                <div id="expected-hours" style="float:left; width:25%"><span style="float:left; width:65%"> Expected Hours:</span>
                    <span>  {{ Form::text('hours',Request::old('hours',formatNumber($contract->expected_hours)), ['class' => 'form-control','style'=>'width: 65px;float: right;margin-top: -8px;' ,'id' => 'action_value' ]) }}  </span>
                </div>
                {!! $errors->first('hours', '<p class="validation-error" style="margin-top: -4px;float: right;margin-right:10px">:message</p>') !!}

            @endif

        </div>
        <div class="col-xs-5"></div>
        <!-- //Action-Redesign by 1254 : 12022020 -->

        @if($contract->payment_type_id !=  App\PaymentType::PER_DIEM && $contract->payment_type_id !=  App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS && $contract->payment_type_id !=  App\PaymentType::PER_UNIT)
            <div class="container" style="width: 100%;padding: 0px 0px 0px 0px;margin: 0px 0px 0px 0px;">
                <div class="row">
                    <div class="col-md-3" style="width: 100%;">
                        <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                            <div class="panel panel-default">
                                @foreach($categories as $category)
                                    <div class="panel panel-default">
                                        <div class="panel-heading1" role="tab" id="{{$category->id}}" name="action-div"
                                             value="{{$category->id}}">
                                            <input type="hidden" name="categoryid" value="{{$category->id}}">
                                            <h4 class="panel-title1">
                                                <a class="collapsed" data-toggle="collapse"
                                                   style="color:black;font-weight:600;text-decoration: none;margin-left: 10px;"
                                                   href="#category_{{$category->id}}" aria-expanded="false"
                                                   aria-controls="category_{{$category->id}}">
                                                    <div class="collapse-level-two-circle"></div>
                                                    {{ $category->name}}&nbsp;&nbsp;
                                                </a>
                                                @if($contract->payment_type_id ==  App\PaymentType::TIME_STUDY)
                                                    <i id="{{$category->id}}" title="{{$category->name}}"
                                                       name="custom_category" data-toggle="modal"
                                                       data-target="#custom_heading_actions" class="fa fa-pencil"></i>
                                                @endif
                                                <button class="hidden" type="button" id="sorting_{{$category->id}}"
                                                        value="{{$category->id}}" name="sorting" class="btn btn-primary"
                                                        data-toggle="modal" data-target="#contract_sorting_modal">Sort
                                                </button>
                                                <div class="alert alert-danger"
                                                     style="display:none;padding:5px;clear:both;margin-top:10px;">
                                                    <a class="close" id="duplicateaction">&times;</a>
                                                    Action already present under category.
                                                </div>
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
                                                @foreach($activities_1 as $action)
                                                    @if($action->category_id == $category->id )
                                                        <div class="col-xs-4" class="action-container">
                                                            @if($action->name!="")
                                                                <?php $checked = false; ?>
                                                                @foreach($actions_contract as $action_contract)
                                                                    @if($action->id == $action_contract->id)
                                                                        <?php $checked = true; ?>
                                                                    @endif
                                                                @endforeach
                                                                {{ Form::checkbox('actions[]', $action->id,$checked,['class' => 'actionCheckbox']) }}
                                                                <span title="{{$action->name}}" class="actionWrap">{{ $action->name }}&nbsp;&nbsp;</span>
                                                                @if($contract->payment_type_id ==  App\PaymentType::TIME_STUDY)
                                                                    <i id="{{$action->id}}" title="{{$action->name}}"
                                                                       category_id="{{$category->id}}"
                                                                       name="custom_action" data-toggle="modal"
                                                                       data-target="#custom_heading_actions"
                                                                       class="fa fa-pencil"></i>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    @endif
                                                @endforeach

                                                @if(is_super_user()||is_super_hospital_user())
                                                    <div class="col-xs-12">
                                                        {{ Form::hidden('[custom_count]',Request::old('custom_count',1),['id' => 'custom_count_'.$category->id]) }}
                                                        <div id="customaction_{{$category->id}}"
                                                             style="padding-top: 35px;">
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
                                                                                       class="form-control custom_name_input"
                                                                                       value="{{$action_name}}"/>
                                                                                <p class="validation-error">Action
                                                                                    already exist under this
                                                                                    category.</p>
                                                                            </div>
                                                                        <!-- <div class="col-xs-5">{!! $errors->first("customaction_name_{{-- $category->id --}}[]", '<p class="validation-error">:message</p>') !!}</div> -->
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
                                                                                       class="form-control custom_name_input"
                                                                                       value="{{$action_name}}"/>
                                                                            </div>
                                                                        <!-- <div class="col-xs-5">{!! $errors->first("customaction_name_{{-- $category->id --}}[]", '<p class="validation-error">:message</p>') !!}</div> -->
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
                                                                               class="form-control custom_name_input"/>
                                                                    </div>
                                                                <!-- <div class="col-xs-5">{!! $errors->first("customaction_name_{{-- $category->id --}}[]", '<p class="validation-error">:message</p>') !!}</div> -->
                                                                    <div class="col-xs-2">
                                                                        <button type="button" name="remove"
                                                                                class="btn btn-primary btn-submit btn_remove"
                                                                                referId="custom_action_div_{{$category->id}}_1">
                                                                            -
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            <button class="btn btn-primary btn-submit add_custom"
                                                                    id="add_custom_{{$category->id}}" type="button"
                                                                    addcustrefId="{{$category->id}}">Add Custom Action
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
        @endif
    </div>
    <div class="panel-body" id="panel_body">
        <div class="row">
            @foreach ($activities as $action)
                @if($contract->payment_type_id == App\PaymentType::PER_DIEM || $contract->payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
                    <div class="col-xs-12">
                        @else
                            <div class="col-xs-6">
                                @endif

                                <?php $action_name_withoutspace = preg_replace('/\s+/', '_', $action->name); ?>
                                <div class="col-xs-12" id="div_action_{{$action_name_withoutspace}}">
                                    @if($contract->payment_type_id == App\PaymentType::PER_DIEM || $contract->payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
                                        <div class="form-group">
                                            <div class="col-xs-4">
                                                @if( $contract->payment_type_id == App\PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS)
                                                    {{ Form::checkbox('actions[]', $action->id, true, array('id'=>'uncompensated_action','disabled')) }}
                                                @else
                                                    {{ Form::checkbox('actions[]', $action->id, $action->checked) }}
                                                @endif
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
                                    @else

                                    @endif
                                </div>
                            </div>
                            @endforeach
                    </div>
        </div>

        <!-- </div> -->
    <!-- @if($contract->payment_type_id != App\PaymentType::PER_DIEM)
        <div id="duties" class="panel panel-default">
            <div class="panel-heading">Management Duties <span class="badge" style="float:right">0.00</span></div>
            <div class="panel-body">
                <div class="row">
                    @foreach ($duties as $action)
            <div class="col-xs-6">
                            <div class="form-group">
                                <div class="col-xs-8">
                                    {{ Form::checkbox('actions[]', $action->id, $action->checked) }} {{ $action->name }}
                    </div>

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif -->
        <div class="panel-footer clearfix">
            <button type="button" id="contract_confirmation" name="contract_confirmation" class="btn btn-primary"
                    data-toggle="modal" data-target="#contract_submit_confirmation_modal">Submit
            </button>
            <button class="btn btn-default btn-primary btn-submit hidden" type="submit" onClick="enabledropdown()"
                    id="submit_contract">Submit
            </button>
        </div>


        <!-- Modal logs in approval queue-->
        <div class="modal fade" id="logsInApprovalQueue" tabindex="-1" role="dialog"
             aria-labelledby="logsInApprovalQueueTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <!-- <h5 class="modal-title" id="exampleModalLongTitle">Modal title</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button> -->
                    </div>
                    <div class="modal-body">
                        There are logs currently in the approval process. Changing approvers is not permitted until all
                        logs have had their final approval. Please contact support if you require assistance.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal"
                                onclick="(function (){$('.overlay').show(); location.reload();})()">Close
                        </button>
                        <!-- <button type="button" class="btn btn-primary">Save changes</button> -->
                    </div>
                </div>
            </div>
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
                                {{--                                    @if(Request::is('contracts/*'))--}}
                                @foreach($contract_physicians as $physician_obj)
                                    <option value="{{ $physician_obj->id }}_{{$physician_obj->practice_id}}"
                                            selected="true">{{ $physician_obj->physician_name }}
                                        ( {{$physician_obj->practice_name}} )
                                    </option>
                                @endforeach
                                {{--                                    @endif--}}
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
                            style="width: 100%; height: 200px; overflow-y: auto; list-style-type:none; padding-left:0px;">

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

        <!-- Add modal contract sort popup start-->
        <div class="modal fade" id="custom_heading_actions" data-backdrop="static" data-keyboard="false" tabindex="-1"
             role="dialog" aria-labelledby="custom_heading_actions_title" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title-heading" style="font-weight: bold;"></h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label id="lbl_custom_heading" class="col-xs-3 control-label"></label>
                            <div class="col-xs-9">
                                {{ Form::text('txt_custom_heading', Request::old('txt_custom_heading'), [ 'class' => 'form-control', 'id' => 'txt_custom_heading', 'maxlength' => 100 ]) }}
                                <p id="error_txt_custom_heading" class="validation-error" style="display:none"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" id="btn_custom_heading_submit" name="btn_custom_heading_submit"
                                class="btn btn-primary">Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Added modal popup end-->

        @endsection
        @section("scripts")
            <script type="text/javascript">

                // 6.1.22 Start
                $(function () {
                    $('[name=approval_manager_level1],[name=approval_manager_level2], [name=approval_manager_level3], [name=approval_manager_level4], [name=approval_manager_level5], [name=approval_manager_level6]').change(function (e) {
                        $.ajax({
                            url: '/getPhysicianLogsInApprovalQueue/' + $('#agreement_id').val() + '/' + $('#contract_id').val(),
                            type: 'get',
                            success: function (response) {
                                if (response > 0) {
                                    $('#logsInApprovalQueue').modal({backdrop: 'static', keyboard: false});
                                    // setTimeout(function(){
                                    //     location.reload();
                                    // }, 2000);
                                }
                            }
                        });
                    });
                });
                // 6.1.22 End
                $(document).ready(function () {
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

                    var categories_count = $('#categories_count').val();
                    var activity_count = 0;

                    // $("[name=txt_custom_heading]").keypress(function(event) {
                    //     if ((event.keyCode >= 65 && event.keyCode <= 90) || (event.keyCode >= 97 && event.keyCode <= 122)
                    //         || event.keyCode == 8 || event.keyCode == 32 ) {

                    //     } else {
                    //         event.preventDefault();
                    //     }
                    // });

                    $('[name=custom_category]').click(function () {
                        $(".modal-title-heading").text("");
                        $(".modal-title-heading").text("Update Category");
                        $("#lbl_custom_heading").text("");
                        $("#lbl_custom_heading").text("Category :");

                        var category_id = $(this).attr('id');
                        var category_title = $(this).attr('title');
                        var action_id = 0;
                        $("#txt_custom_heading").attr("category_id", category_id);
                        $("#txt_custom_heading").attr("action_id", action_id);
                        $('#txt_custom_heading').val('');
                        $('#txt_custom_heading').val(category_title);

                        $('#error_txt_custom_heading').hide();
                        $('#error_txt_custom_heading').text('');
                    });

                    $('[name=custom_action]').click(function () {
                        $(".modal-title-heading").text("");
                        $(".modal-title-heading").text("Update Action");
                        $("#lbl_custom_heading").text("");
                        $("#lbl_custom_heading").text("Action :");

                        var category_id = $(this).attr('category_id');
                        var action_id = $(this).attr('id');
                        var category_title = $(this).attr('title');
                        $("#txt_custom_heading").attr("category_id", category_id);
                        $("#txt_custom_heading").attr("action_id", action_id);
                        $('#txt_custom_heading').val('');
                        $('#txt_custom_heading').val(category_title);

                        $('#error_txt_custom_heading').hide();
                        $('#error_txt_custom_heading').text('');
                    });

                    $('#btn_custom_heading_submit').click(function () {
                        $('#btn_custom_heading_submit').addClass("disabled");
                        $.ajax({
                            url: '/UpdateCustomCategoriesActions/' + $('#contract_id').val(),
                            type: 'post',
                            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
                            data: {
                                category_id: $("#txt_custom_heading").attr("category_id"),
                                action_id: $("#txt_custom_heading").attr("action_id"),
                                category_action_name: $("#txt_custom_heading").val()
                            },
                            success: function (response) {
                                $('#error_txt_custom_heading').hide();
                                if (response == 'action_name_exist') {
                                    $('#error_txt_custom_heading').show();
                                    $('#error_txt_custom_heading').text('Action already exist under this category.');
                                    $('#btn_custom_heading_submit').removeClass("disabled");
                                    modal('show');
                                } else if (response == 'category_name_exist') {
                                    $('#error_txt_custom_heading').show();
                                    $('#error_txt_custom_heading').text('Category already exist under this contract.');
                                    $('#btn_custom_heading_submit').removeClass("disabled");
                                    modal('show');
                                } else if (response > 0) {
                                    location.reload();
                                }
                            },
                            complete: function () {

                            }
                        });

                    });

                    $('#sorting').click(function () {
                        var category_wise_activities = [];
                        $('ul.ul_activities').empty();
                        var get_sorting_contract_data = $.parseJSON($('#get_sorting_contract_data').val());

                        for (var i = 0; i < get_sorting_contract_data.length; i++) {
                            if (get_sorting_contract_data[i]['contract_id'] == $('#contract_id').val()) {
                                category_wise_activities.push({
                                    action_id: get_sorting_contract_data[i]['action_id'],
                                    sort_order: get_sorting_contract_data[i]['sort_order'],
                                    category_id: get_sorting_contract_data[i]['category_id']
                                });
                            }
                        }

                        var existing_activities_array = [];
                        var new_activities_array = [];

                        for (var p = 1; p <= categories_count; p++) {
                            $('#category_' + p).find('input[type=checkbox]:checked').each(function () {
                                var action_id = $(this).val();
                                var count = 1;
                                var flag = false;

                                for (var j = 0; j < category_wise_activities.length; j++) {
                                    if (category_wise_activities[j]["action_id"] == action_id) {
                                        flag = true;
                                        existing_activities_array.push({
                                            action_id: $(this).val(),
                                            text: $(this).parent().find('span').html(),
                                            sort_order: j + 1,
                                            category_id: category_wise_activities[j]["category_id"]
                                        });
                                    }
                                }
                                if (!flag) {
                                    new_activities_array.push('<li value=' + $(this).val() + ' category_id=' + p + '>' + $(this).parent().find('span').html() + '</li>');
                                }
                                count++;
                            });
                        }

                        arrObj = existing_activities_array.sort((a, b) => a.sort_order < b.sort_order ? -1 : 1);

                        for (var k = 0; k < arrObj.length; k++) {
                            $('#ul_li_activities').append('<li value=' + arrObj[k]['action_id'] + ' category_id=' + arrObj[k]['category_id'] + '>' + arrObj[k]['text'] + '</li>');
                        }

                        for (var l = 0; l < new_activities_array.length; l++) {
                            $('#ul_li_activities').append(new_activities_array[l]);
                        }
                        $('#ul_li_activities li').css({
                            'padding': '5px',
                            'margin': '5px',
                            'border': '1px solid #ccc',
                            'border-radius': '6px'
                        });
                    });
                    $('#btn_sorting_submit').click(function () {
                        var sorting_contract_array = [];
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
                        if ($('#payment_type_id').val() == 8) {
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
                        if ($('#payment_type_id').val() == 8) {
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

                $("#btn_contract_submit").click(function (e) {
                    $('#submit_contract').trigger('click');
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
                    $("#partial_hours").prop("disabled", false);

                }

                //function is added to change value for next start day dropdown by one step dynamically
                function rangechange(index) {

                    var rate_number = parseInt($('#on_call_rate_count').val());
                    var on_call_rate_btn_flag = false;
                    var range_limit = $('#range_limit').val();
                    for (var i = 1; i <= rate_number; i++) {
                        var end_day_value = parseInt($('#end_day' + (i)).val())
                        $('#start_day' + (i + 1)).attr("disabled", "false");
                        $('#start_day' + (i + 1)).val(end_day_value + 1);
                        // $('#start_day'+(i+1)).attr("disabled","true");
                        var day = $('#start_day' + (i + 1)).val();
                        $('[name=start_day_hidden' + (i + 1) + ']').val(end_day_value + 1);

                        if (end_day_value == range_limit) {
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

                    if ($('#partial_hours').prop("checked") == true) {
                        if ($('[name=payment_type_id]').val() == 5) {
                            $('#hours_calculation_div').show();
                        }
                    }
                    if ($('[name=change_rate_check]').is(':checked')) {
                        if ($('[name=payment_type_id]').val() == 5) {
                            $('#add-uncompensaed-btn').show();
                        }
                    }
                    if ($("#partial_hours").prop('checked') == true) {
                        //edit contract changes for partial hours toggle off to on
                        $("#partial_hours").prop("disabled", true);

                        $('#div_action_Holiday_-_HALF_Day_-_On_Call').hide();
                        $('#div_action_Weekday_-_HALF_Day_-_On_Call').hide();
                        $('#div_action_Weekend_-_HALF_Day_-_On_Call').hide();

                    }

                    var rate_count = sessionStorage.getItem("rate_count");
                    var value = $('[name=payment_type]').val();
                    $('#expected-hours').show();

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
                    }


                    //     var rate_count = $('#on_call_rate_count').val();


                    if ($('[name=change_rate_check]').is(':checked')) {
                        for (var i = 1; i <= rate_count; i++) {
                            $('[name=rate' + i + ']').attr('readonly', false);
                            $('[name=start_day' + (i + 1) + ']').attr('disabled', false);
                            $('[name=end_day' + (i + 1) + ']').attr('disabled', false);
                            $('[name=end_day1]').attr('disabled', false);

                        }
                        if ($('[name=payment_type_id]').val() == 5) {
                            $('#add-uncompensaed-btn').show();
                        }

                    } else {
                        for (var i = 1; i <= rate_count; i++) {
                            $('[name=rate' + i + ']').attr('readonly', true);
                            $('[name=start_day' + (i + 1) + ']').attr
                            ('disabled', true);
                            $('[name=start_day' + (i + 1) + ']').attr('disabled', true);
                            $('[name=end_day' + (i + 1) + ']').attr('disabled', true);

                        }
                        $('#add-uncompensaed-btn').hide();
                    }

                    //call-coverage-duration  : make partial hours toggle non editale  by 1254
                    // $('#partial_hours').prop("disabled", true);
                    var category_id = $('[name=categoryid]').val();

                    $('.add_custom').click(function () {
                        var add_btn_id = $(this).attr('addcustrefId');
                        var i = $('#custom_count_' + add_btn_id).val();
                        i = parseInt(i) + 1;
                        $('#customaction_' + add_btn_id).append('<div class="form-group invoive-note" id="custom_action_div_' + add_btn_id + '_' + i + '"><label class="col-xs-2" id="label_' + i + '">Custom Action </label><div class="col-xs-5"><input  type="text" name="customaction_name_' + add_btn_id + '[]" class="form-control custom_name_input"/></div><div class="col-xs-2"><button type="button" name="remove" id="remove_' + i + '" class="btn btn-primary btn-submit btn_remove"  referId="custom_action_div_' + add_btn_id + '_' + i + '">-</button></div></div>');
                        $('#custom_count_' + add_btn_id).val(i);
                    });

                    //Per Diem with Uncompensated Days by 1254
                    $(".add-on-call-uncompensated-rate").on('click', function (event) {
                        event.preventDefault();
                        var rate_number = parseInt($('#on_call_rate_count').val()) + 1;
                        var start = parseInt($('#end_day' + (rate_number - 1)).val()) + 1;
                        var end = 31;
                        $('#add-uncompensaed-btn').prop('disabled', false);
                        var range_limit = $('#range_limit').val();
                        if (isNaN(start)) {
                            var start = 1;
                            rate_number = 1;
                        }

                        if (start <= range_limit) {
                            var range_values = [];
                            // for (i = start; i <= 31; i++){
                            //     range_values.push(i)
                            // }

                            for (i = 1; i <= range_limit; i++) {
                                range_values.push(i)
                            }

                            var range_start_dropdown = $("<select></select>").attr("id", 'start_day' + rate_number).attr("name", 'start_day' + rate_number).attr("class", 'form-control').attr('disabled', false);
                            $.each(range_values, function (i, value) {
                                range_start_dropdown.append("<option>" + value + "</option>");
                            });

                            var range_end_dropdown = $("<select></select>").attr("id", 'end_day' + rate_number).attr("name", 'end_day' + rate_number).attr("class", 'form-control').attr("onchange", "rangechange(" + rate_number + ")");
                            $.each(range_values, function (i, value) {
                                range_end_dropdown.append("<option>" + value + "</option>");
                            });

                            var uncompensated_rates = '<div id="on-call-rate-div' + rate_number + '">'
                                + '<div class="form-group col-xs-12 on-call-rate no-margin-left">  <label class="col-xs-2 control-label" id="on_call_rate_label_' + rate_number + '">On Call Rate ' + rate_number + '</label>'
                                + '<div class="col-xs-4 form-group info-cls" ><div class="no-info-icon">&nbsp;&nbsp;&nbsp;&nbsp;</div>'
                                + '<div class="input-group right-field">'
                                + '<input type="text" class="col-xs-5 form-control" id="rate' + rate_number + '" maxlength="50" rows="2" cols="54" style="resize:none;" name="rate' + rate_number + '"  />'
                                + '<span class="input-group-addon"><i class="fa fa-dollar fa-fw"></i></span>'
                                + '</div>'  //end of input group
                                + '</div>'  //col-xs-4 form-group

                                + '<div class="col-xs-6 form-group">'
                                + '<label class="col-xs-3 control-label no-padding-top"  style="width:20%;">Days Entered:</label>'
                                + '<div class="col-xs-3" style="width:24%" id="on-call-start-days' + rate_number + '">'

                                + '</div>'
                                + '<div class="col-xs-3" style="width:24%" id="on-call-end-days' + rate_number + '">'

                                + '</div>'
                                + '<input type=hidden name=start_day_hidden' + rate_number + '>'
                                + '<input type=hidden name=end_day_hidden' + rate_number + '>'
                                // +'<div class="col-xs-2"><button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate" referid="on-call-rate-div'+rate_number+'" type="button"> - </button></div>'
                                // +'<div class="col-xs-2"><button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate"  id="btn-remove-uncompensated'+rate_number+'" referid="on-call-rate-div'+rate_number+'"  value='+rate_number+' type="button"> - </button></div>'
                                + '<div class="col-xs-2"><button class="btn btn-primary btn-submit btn_remove-on-call-uncompensated-rate"  id="btn-remove-uncompensated' + rate_number + '"   value=' + rate_number + ' type="button" onClick="removeRangeCustom(this);"> - </button></div>'

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
                        $('[name=end_day_hidden' + (rate_number) + ']').val(parseInt($('#end_day' + (rate_number)).val()));


                    });
                    //Per Diem with Uncompensated Days by 1254
                    // $(document).on('click', '.btn_remove-on-call-uncompensated-rate', function(){
                    //     var on_call_rate_div = $(this).attr("referId");
                    //     $('#'+on_call_rate_div).remove();
                    //     var rate_number = parseInt($('#on_call_rate_count').val());
                    //     $('#on_call_rate_count').val(rate_number - 1);

                    //     var end_day_value = $('#end_day'+(rate_number-1)).val();
                    //     if(end_day_value!=31)
                    //     {
                    //         $('#add-uncompensaed-btn').prop('disabled', false);
                    //     }
                    // });


                    $(document).on('click', '.btn_remove', function () {
                        var category_id = $(this).attr("referId");
                        console.log(category_id);
                        $('#' + category_id).remove();

                        var category_sub_id = $(this).attr("refcatId");
                        console.log(category_sub_id);
                        $('#' + category_id).remove();
                        //     $('#remove_'+i+'').remove();
                        // //  $('#label_'+i+'').remove();

                    });


                    $("[name=edit_manual_end_date]").inputmask({mask: '99/99/9999'});
                    $("[name=edit_valid_upto_date]").inputmask({mask: '99/99/9999'});
                    $("#prior_start_date").inputmask({mask: '99/99/9999'});
                    if ($('[name=default]').is(':checked')) {

                        for (var i = 1; i <= 6; i++) {
                            $('[name=approverTypeforLevel' + i + ']').attr("disabled", true);
                            $('[name=approval_manager_level' + i + ']').attr("disabled", true);
                            $('[name=initial_review_day_level' + i + ']').attr("disabled", true);
                            $('[name=final_review_day_level' + i + ']').attr("disabled", true);
                        }
                        $('input.emailCheck').attr("disabled", true);

                        /*$('[name=contract_manager]').attr("disabled", true);
                 $('[name=financial_manager]').attr("disabled", true);
                 $('[name=contract_manager]').val($('#default_CM').val());
                 $('[name=financial_manager]').val($('#default_FM').val());*/
                    } else {
                        for (var i = 1; i <= 6; i++) {
                            $('[name=approverTypeforLevel' + i + ']').attr("disabled", false);
                            if ($('[name=approverTypeforLevel' + i + ']').val() > 0) {
                                $('[name=approval_manager_level' + i + ']').attr("disabled", false);
                                $('[name=initial_review_day_level' + i + ']').attr("disabled", false);
                                $('[name=final_review_day_level' + i + ']').attr("disabled", false);
                                $('[value=level' + i + ']').attr("disabled", false);
                            } else {
                                $('[name=approval_manager_level' + i + ']').attr("disabled", true);
                                $('[name=initial_review_day_level' + i + ']').attr("disabled", true);
                                $('[name=final_review_day_level' + i + ']').attr("disabled", true);
                                $('[value=level' + i + ']').attr("disabled", true);
                            }
                        }

                    }

                    if ($('[name=change_rate_check]').is(':checked')) {
                        $('[name=rate]').attr('readonly', false);
                        $('[name=weekday_rate]').attr('readonly', false);
                        $('[name=weekend_rate]').attr('readonly', false);
                        $('[name=holiday_rate]').attr('readonly', false);
                        $('[name=On_Call_rate]').attr('readonly', false);
                        $('[name=called_back_rate]').attr('readonly', false);
                        $('[name=called_in_rate]').attr('readonly', false);
                        $("#change_rate_start_date_div").show();
                        if ($('[name=payment_type_id]').val() == 5) {
                            $('.btn_remove-on-call-uncompensated-rate').show();
                            $('.add-on-call-uncompensated-rate').show();
                        }
                    } else {
                        $('[name=rate]').attr('readonly', true);
                        $('[name=weekday_rate]').attr('readonly', true);
                        $('[name=weekend_rate]').attr('readonly', true);
                        $('[name=holiday_rate]').attr('readonly', true);
                        $('[name=On_Call_rate]').attr('readonly', true);
                        $('[name=called_back_rate]').attr('readonly', true);
                        $('[name=called_in_rate]').attr('readonly', true);
                        $("#change_rate_start_date_div").hide();
                        $('.btn_remove-on-call-uncompensated-rate').hide();
                        $('.add-on-call-uncompensated-rate').hide();
                    }


                    if ($('[name=default_dates]').is(':checked')) {
                        $('[name=edit_manual_end_date]').val($('#agreement_end_date').val());
                        $('[name=edit_valid_upto_date]').val($('#agreement_valid_upto_date').val());
                        $('[name=edit_manual_end_date]').attr('readonly', true);
                        $('[name=edit_valid_upto_date]').attr('readonly', true);
                        $('.calendar').css('visibility', 'hidden');
                    } else {
                        $('[name=edit_manual_end_date]').attr('readonly', false);
                        $('[name=edit_valid_upto_date]').attr('readonly', false);
                        $('.calendar').css('visibility', 'visible');
                    }
                    //$('#prior_start_date .calendar').css('visibility','visible');
                    /*code for prior start date handling*/
                    if (($('[name=contract_prior_start_date_on_off]').is(':checked'))) {
                        $('#prior_start_date .calendar').css('visibility', 'visible');
                        $('#prior_start_date_field').attr('readonly', false);
                    } else {
                        $('#prior_start_date .calendar').css('visibility', 'hidden');
                        $('#prior_start_date_field').attr('readonly', true);
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
                        if ($("#deadline_days").val() == 0 || $("#deadline_days").val() == '') {
                            //if ($('[name=contract_type_id]').val() == 4) {
                            if ($('[name=payment_type_id]').val() == 3 || $('[name=payment_type_id]').val() == 5) {
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

                    $("#edit_manual_end_date").datetimepicker(
                        {
                            language: 'en_US', pickTime: false
                        }
                    );

                    $("#edit_valid_upto_date").datetimepicker(
                        {
                            language: 'en_US', pickTime: false
                        }
                    );
                    $("#prior_start_date").datetimepicker({language: 'en_US', pickTime: false});

                });
                //call-coverage by 1254 : added to hide all half day activities for perdiem when partial hours set to true
                $(function () {
                    $("#partial_hours").change(function () {
                        if ($(this).prop("checked") == true) {

                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').hide();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').hide();
                            //Per Diem with Uncompensated Days by 1254
                            if ($('[name=payment_type_id]').val() == 5) {

                                $('#hours_calculation_div').show();
                            }
                        } else {
                            $('#div_action_Holiday_-_HALF_Day_-_On_Call').show();
                            $('#div_action_Weekday_-_HALF_Day_-_On_Call').show();
                            $('#div_action_Weekend_-_HALF_Day_-_On_Call').show();
                            //Per Diem with Uncompensated Days by 1254
                            $('#hours_calculation_div').hide();

                        }

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

                $(function () {
                    // Update the counters.
                    //var value = $("input[name=contract_type_id]").val();
                    var value = $("input[name=payment_type_id]").val();

                    $("#mandate_details_div, #custom_action_div").show();
                    $("#quarterly_max_hours_div").show();

                    $('#log_over_max_hour_flag').hide();
                    $("#lbl_min_hours").html("Min Hours *");
                    $("#lbl_max_hours").html("Max Hours *");
                    $("#lbl_annual_max_hours").html("Annual Max Hours *");
                    $("#lbl_prior_worked_hours").html("Prior Worked Hours *");
                    $("#lbl_Quarterly_max_hours").html("Quarterly Max Hours *");
                    $("#units_div").hide();
                    $('#rateId_tooltip').show();

                    if (value == 5) {

                        $('#annual_max_div').show();
                        $('#min_hours_div').hide();
                        $('#max_hours_div').hide();
                        $('#fmv_rate_div').hide();

                        $('#weekday_rate_div').hide();
                        $('#weekend_rate_div').hide();
                        $('#holiday_rate_div').hide();

                        $('#On_Call_rate_div').hide();
                        $('#called_back_rate_div').hide();
                        $('#called_in_rate_div').hide();
                        $('#burden_selection').hide();

                        $('#custom_action_div').hide();
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
                        $('#rate_selection').hide();
                        $('#on_call_uncompensated_rate').show();
                        $('#action_category').hide();
                        $('#per-diem-activities').hide();
                        $('#log_over_max_hour_flag').hide();
                        $("#quarterly_max_hours_div").hide();
                        //As per QA suggestion
                        $('#holiday_selection').hide();


                    } else if (value == 3) {
                        $("#annual_max_shifts_div").show();
                        $('#annual_max_shifts_div_text').removeClass('input-group');
                        $('#annual_max_div').show();
                        $('#min_hours_div').hide();
                        $('#max_hours_div').hide();
                        $('#fmv_rate_div').hide();
                        $('#custom_action_div').hide();
                        $('#weekday_rate_div').show();
                        $('#weekend_rate_div').show();
                        $('#holiday_rate_div').show();
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
                        $('#holiday_selection').show();
                        $('#log_over_max_hour_flag').hide();
                        $("#quarterly_max_hours_div").hide();
                        if ($("#display_on_call_rate").val() == 1) {
                            $('#weekday_rate_div').hide();
                            $('#weekend_rate_div').hide();
                            $('#holiday_rate_div').hide();
                            $('#On_Call_rate_div').show();
                            $('#called_back_rate_div').show();
                            $('#called_in_rate_div').show();
                            $('#burden_selection').show();
                            $('#holiday_selection').hide();
                            <!-- call-coverage-duration  by 1254 -->
                            $('#hours_selection').show();
                        } else {
                            $('#weekday_rate_div').show();
                            $('#weekend_rate_div').show();
                            $('#holiday_rate_div').show();
                            $('#On_Call_rate_div').hide();
                            $('#called_back_rate_div').hide();
                            $('#called_in_rate_div').hide();
                            $('#burden_selection').hide();
                            $('#holiday_selection').show();
                            <!-- call-coverage-duration  by 1254 -->
                            $('#hours_selection').show();
                        }
                    } else {
                        $('#min_hours_div').show();
                        $('#max_hours_div').show();
                        $('#fmv_rate_div').show();
                        $('#log_over_max_hour_flag').hide();
                        // //Chaitraly::Label change for Monthly Stipend
                        if (value == 6) {
                            document.getElementById('rateID').innerHTML = 'Monthly Stipend';
                            document.getElementById('changeRateID').innerHTML = 'Change Monthly Stipend Rate';

                            $('#rateId_tooltip').hide();
                        }

                        $('#custom_action_div').show();
                        $('#weekday_rate_div').hide();
                        $('#weekend_rate_div').hide();
                        $('#holiday_rate_div').hide();
                        $('#holiday_selection').hide();
                        if (value == 7) {
                            $("#mandate_details_div, #custom_action_div").hide();
                        }
                        if (value == 2) {
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

                            $('#min_hours_div').hide();
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
                            // $('#container_div').hide();
                            $('#activities').hide();
                            $('#panel_body').hide();

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
                            $('#rateId_tooltip').hide();

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
                        }
                        $('#rate_selection').hide();
                        $('#On_Call_rate_div').hide();
                        $('#called_back_rate_div').hide();
                        $('#called_in_rate_div').hide();
                        $('#burden_selection').hide();
                        <!-- call-coverage-duration  by 1254 -->
                        $('#hours_selection').hide();
                    }
                    if ($("#deadline_days").val() == 0 || $("#deadline_days").val() == '') {
                        //if ($('[name=contract_type_id]').val() == 4) {
                        if ($('[name=payment_type_id]').val() == 3 || $('[name=payment_type_id]').val() == 5) {
                            $("#deadline_days").val(90);
                        } else {
                            $("#deadline_days").val(365);
                        }
                    }
                    update("#activities");
                    update("#duties");

                    // Monitor each section for updates.
                    $(document).on("change", "#activities input", function (event) {
                        update("#activities");
                    });
                    $(document).on("change", "#duties input", function (event) {
                        update("#duties");
                    });

                    function update(container) {
                        var sum = 0.0;
                        $(container + " input[type=checkbox]").each(function (index) {
                            if (this.checked) {
                                var action = $(this).val();

                                var input = $(container + " [name=action-" + action + "-value]");

                                sum += parseFloat(input.val());
                            }
                        });

                        $(container + " .badge").html(sum.toFixed(2));
                    }
                });

                $(function () {
                    $('[name=default]').click(function (event) {
                        if ($('[name=default]').is(':checked')) {
                            var value = $('#agreement_id').val();
                            $.ajax({
                                url: '{{ URL::current() }}/checkApproval/' + value,
                                dataType: 'json'
                            }).done(function (response) {
                                if (response['agreement']['approval_process'] == 1) {

                                    $('#approval_feilds').show();
                                    $('#approvalContainer').show();
                                    $('#approval_process').val(1);

                                    $('[name=default]').prop('checked', true);
                                    var approval_manager_info_length = response['approvalManagerInfo'].length;
                                    for (var i = 1; i <= 6; i++) {
                                        // $('[name=approverTypeforLevel'+i+']').attr("disabled", true);
                                        $('[name=approval_manager_level' + i + ']').attr("disabled", true);
                                        $('[name=initial_review_day_level' + i + ']').attr("disabled", true);
                                        $('[name=final_review_day_level' + i + ']').attr("disabled", true);

                                        if (i <= approval_manager_info_length) {
                                            //$('[name=approverTypeforLevel'+i+']').val(response['approvalManagerInfo'][i-1]['type_id']);
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
                                            var start_range = 10;
                                            var end_range = 20;
                                            if (response['agreement']['payment_frequency_type'] == 1) {
                                                var start_range = 10;
                                                var end_range = 20;
                                            } else if (response['agreement']['payment_frequency_type'] == 2) {
                                                var start_range = 2;
                                                var end_range = 6;
                                            } else if (response['agreement']['payment_frequency_type'] == 3) {
                                                var start_range = 2;
                                                var end_range = 12;
                                            } else if (response['agreement']['payment_frequency_type'] == 4) {
                                                var start_range = 10;
                                                var end_range = 20;
                                            }

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
                                            $('[name=approval_manager_level' + i + '] option:selected ').removeAttr('selected');
                                            $('[name=initial_review_day_level' + i + ']').val(start_range);
                                            $('[name=final_review_day_level' + i + ']').val(end_range);
                                            ;
                                            $('input:checkbox[value="level' + i + '"]').prop('checked', true);
                                        }
                                    }
                                    $('input.emailCheck').attr("disabled", true);
                                } else {
                                    $('#approval_feilds').hide();
                                    $('#approvalContainer').hide();
                                    $('#approval_process').val(0);
                                    $('[name=default]').prop('checked', false);
                                }

                            });

                        } else {
                            for (var i = 1; i <= 6; i++) {
                                $('[name=approverTypeforLevel' + i + ']').attr("disabled", false);
                                if ($('[name=approverTypeforLevel' + i + ']').val() > 0) {
                                    $('[name=approval_manager_level' + i + ']').attr("disabled", false);
                                    $('[name=initial_review_day_level' + i + ']').attr("disabled", false);
                                    $('[name=final_review_day_level' + i + ']').attr("disabled", false);
                                    $('[value=level' + i + ']').attr("disabled", false);
                                } else {
                                    $('[name=approval_manager_level' + i + ']').attr("disabled", true);
                                    $('[name=initial_review_day_level' + i + ']').attr("disabled", true);
                                    $('[name=final_review_day_level' + i + ']').attr("disabled", true);
                                    $('[value=level' + i + ']').attr("disabled", true);
                                }
                            }
                            $('input.emailCheck').attr("disabled", false);
                        }

                    });

                    $('[name=change_rate_check]').click(function (event) {

                        var rate_count = $('#on_call_rate_count').val();

                        if ($('[name=change_rate_check]').is(':checked')) {


                            for (var i = 1; i <= rate_count; i++) {

                                $('[name=rate' + i + ']').attr('readonly', false);
                                $('[name=start_day' + (i + 1) + ']').attr('disabled', false);
                                $('[name=end_day' + (i + 1) + ']').attr('disabled', false);
                                $('[name=start_day_hidden' + (i) + ']').val(parseInt($('#start_day' + (i)).val()));
                                $('[name=end_day_hidden' + (i) + ']').val(parseInt($('#end_day' + (i)).val()));

                            }

                            $('[name=rate]').attr('readonly', false);
                            $('[name=weekday_rate]').attr('readonly', false);
                            $('[name=weekend_rate]').attr('readonly', false);
                            $('[name=holiday_rate]').attr('readonly', false);
                            $('[name=On_Call_rate]').attr('readonly', false);
                            $('[name=called_back_rate]').attr('readonly', false);
                            $('[name=called_in_rate]').attr('readonly', false);

                            $("#change_rate_start_date_div").show();
                            $('[name=end_day1]').attr('disabled', false);

                            if ($('[name=payment_type_id]').val() == 5) {
                                $('.btn_remove-on-call-uncompensated-rate').show();
                                $('#add-uncompensaed-btn').show();
                            }


                        } else {
                            for (var i = 1; i <= rate_count; i++) {
                                $('[name=rate' + i + ']').attr('readonly', true);
                                $('[name=start_day' + (i + 1) + ']').attr('disabled', true);
                                $('[name=end_day' + (i + 1) + ']').attr('disabled', true);

                            }
                            $('[name=rate]').attr('readonly', true);
                            $('[name=weekday_rate]').attr('readonly', true);
                            $('[name=weekend_rate]').attr('readonly', true);
                            $('[name=holiday_rate]').attr('readonly', true);
                            $('[name=On_Call_rate]').attr('readonly', true);
                            $('[name=called_back_rate]').attr('readonly', true);
                            $('[name=called_in_rate]').attr('readonly', true);
                            $("#change_rate_start_date_div").hide();
                            $('[name=end_day1]').attr('disabled', true);
                            $('[name=start_day1]').attr('disabled', true);


                            $('.btn_remove-on-call-uncompensated-rate').hide();
                            $('#add-uncompensaed-btn').hide();

                        }
                    });

                    $('[name=default_dates]').click(function (event) {
                        if ($('[name=default_dates]').is(':checked')) {
                            $('[name=edit_manual_end_date]').val($('#agreement_end_date').val());
                            $('[name=edit_valid_upto_date]').val($('#agreement_valid_upto_date').val());
                            $('[name=edit_manual_end_date]').attr('readonly', true);
                            $('[name=edit_valid_upto_date]').attr('readonly', true);
                            $('.calendar').css('visibility', 'hidden');
                        } else {
                            $('[name=edit_manual_end_date]').attr('readonly', false);
                            $('[name=edit_valid_upto_date]').attr('readonly', false);
                            $('.calendar').css('visibility', 'visible');
                        }
                    });
                    //code for prior start date handling
                    $('[name=contract_prior_start_date_on_off]').click(function (event) {
                        if ($('[name=contract_prior_start_date_on_off]').is(':checked')) {
                            $('#prior_start_date .calendar').css('visibility', 'visible');
                            $('#prior_start_date_field').attr('readonly', false);
                            $(this).attr('value', '1');
                        } else {
                            $('#prior_start_date .calendar').css('visibility', 'hidden');
                            $('#prior_start_date_field').attr('readonly', true);
                            $(this).attr('value', '0');
                        }
                    });
                });
                // code for change state of contract deadline option
                $("#contract_deadline_on_off").change(function () {
                    if ($(this).prop("checked") == true) {
                        if ($("#deadline_days").val() == 0 || $("#deadline_days").val() == '') {
                            if ($('[name=contract_type]').val() == 4) {
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

                $(document).ready(
                    function () {
                        if ($("#burden_on_off").prop("checked") == true) {
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

                // code for change state of wrvu payments option
                $("#wrvu_payments").change(function () {
                    if ($(this).prop("checked") == true) {
                        $("#contract_psa_wrvu_rates_div").show("slow");
                    } else if ($(this).prop("checked") == false) {
                        $("#contract_psa_wrvu_rates_div").hide("slow");
                    }
                });

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
                        $('#on_call_rate_label_' + i).text("On Call Rate" + (i - 1));
                        $('#on_call_rate_label_' + i).attr("id", "on_call_rate_label_" + (i - 1));


                        $('[name=rate' + i + ']').attr("name", 'rate' + (i - 1));
                        $('[name=start_day' + i + ']').attr("name", 'start_day' + (i - 1));
                        $('[name=end_day' + i + ']').attr("name", 'end_day' + (i - 1));
                        $('[name=start_day_hidden' + (i) + ']').attr("name", 'start_day_hidden' + (i - 1));
                        $('#on_call_rate_label_' + i).text("On Call Rate" + "test");

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

            <script type="text/javascript">

                var mgr1 = null;
                var mgr2 = null;
                var mgr3 = null;
                var mgr4 = null;
                var mgr5 = null;
                var mgr6 = null;


                $(document).ready(function () {
                    $("select[name='agreement']").selectize({
                        create: true,
                        sortField: "text",
                    });

                    $("select[name='contract_type']").selectize({
                        create: true,
                        sortField: "text",
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
                    } else {
                        mgr1[0].selectize.enable();
                        mgr2[0].selectize.enable();
                        mgr3[0].selectize.enable();
                        mgr4[0].selectize.enable();
                        mgr5[0].selectize.enable();
                        mgr6[0].selectize.enable();
                    }


                    // if($("select[name='approverTypeforLevel1']").val()==1){
                    //         mgr1[0].selectize.disable();
                    // }
                    // if($("select[name='approverTypeforLevel2']").val()==1){
                    //         mgr2[0].selectize.disable();
                    // }
                    // if($("select[name='approverTypeforLevel3']").val()==1){
                    //         mgr3[0].selectize.disable();
                    // }
                    // if($("select[name='approverTypeforLevel4']").val()==1){
                    //         mgr4[0].selectize.disable();
                    // }

                    // if($("select[name='approverTypeforLevel5']").val()==1){
                    //         mgr5[0].selectize.disable();
                    // }
                    // if($("select[name='approverTypeforLevel6']").val()==1){
                    //         mgr6[0].selectize.disable();
                    // }

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
                    $("select[name='contract_name']").css("display", "none");
                    $("input[name='contract_name_search']").on("keyup", function (e) {
                        $("select[name='contract_name']").css("display", "none");
                        $("select[name='contract_name_autocomplete']").css("display", "block");


                        let term = $(this).val();
                        $("select[name='contract_name_autocomplete']").empty();
                        // console.log(term);
                        $("select[name='contract_name'] > option").each(function () {
                            if (this.text.toLowerCase().indexOf(term) >= 0) {
                                // console.log("Matching:"+this.text);
                                $("select[name='contract_name_autocomplete']").append("<option value='" + this.value + "'>" + this.text + "</option>");
                            }
                        });

                        $("select[name='contract_name']").val($("select[name='contract_name_autocomplete']").val());


                    });


                    $('body').on('change', "select[name='contract_name_autocomplete']", function () {
                        $("select[name='contract_name']").val(this.value);
                        console.log("Updated Select Value is Now:" + $("select[name='contract_name']").val());
                    });

                    $('body').on('change', "select[name='contract_name']", function () {
                        console.log("Updated Select contact_name Value is Now:" + $("select[name='contract_name']").val());
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

            <style type="text/css">

                select[name="contract_name"] {
                    display: none;
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

            </style>
@endsection


