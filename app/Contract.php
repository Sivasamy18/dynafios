<?php

namespace App;

use App\Http\Controllers\ApiController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Validations\ContractValidation;
use Illuminate\Support\Facades\Response;
use OwenIt\Auditing\Contracts\Auditable;
use Request;
use Redirect;
use Lang;
use stdClass;
use DateTime;
use NumberFormatter;
use View;
use Log;
use App\Http\Controllers\Validations\ActionValidation;
use Auth;
use Mail;
use App\ActionHospital;
use App\PhysicianContracts;
use App\SortingContractActivity;
use App\SortingContractName;
use App\customClasses\PaymentFrequencyFactoryClass;
use PDO;
use App\Jobs\UpdatePendingPaymentCount;
use function App\Start\is_super_user;
use App\Http\Controllers\Validations\EmailValidation;


class Contract extends Model implements Auditable
{
    use SoftDeletes, \OwenIt\Auditing\Auditable;

    const CO_MANAGEMENT_MIN_MONTHS = 1;
    const ALLOWED_MAX_HOURS_PER_DAY = 24;

    protected $softDelete = true;
    protected $dates = ['deleted_at'];
    protected $table = 'contracts';

    public function agreement()
    {
        return $this->belongsTo('App\Agreement', 'agreement_id', 'id');
    }

    public function physician()
    {
        return $this->belongsTo('App\Physician');
    }

    public function physicians()
    {
        return $this->belongsToMany('App\Physician', 'physician_contracts', 'physician_id', 'contract_id')->withPivot('physician_id', 'contract_id', 'practice_id', 'created_by', 'updated_by')->withTimestamps();
    }

    public function contractName()
    {
        return $this->belongsTo(ContractName::class);
    }

    public function contractType()
    {
        return $this->belongsTo('App\ContractType');
    }

    public function actions()
    {
        return $this->belongsToMany('App\Action')->withPivot('hours');
    }

    public function logs()
    {
        return $this->hasMany('App\PhysicianLog');
    }

    public function manager_info()
    {
        return $this->getManagers(ApprovalManagerInfo::contract_managers($this->id));
    }

    public function activities()
    {
        //return $this->getActions(Action::activities($this->contract_type_id));
        //return $this->getActions(Action::activities($this->contract_type_id),$this->id);/*add contract_id to get on call activity names*/
        return $this->getActions(Action::activities($this->payment_type_id), $this->id);/*add payment type id to get on call activity names*/
    }

    public function duties()
    {
        //return $this->getActions(Action::duties($this->contract_type_id));
        //return $this->getActions(Action::duties($this->contract_type_id),$this->id);/*add contract_id to get on call activity names*/
        return $this->getActions(Action::duties($this->payment_type_id), $this->id);/*add payment type id to get on call activity names*/
    }

    public function activeOncallContract($physician_id, $hospital_id)
    {
        return $this->getContracts($physician_id, $hospital_id);
    }

    public function getContractDetails($physician_id, $contract_id, $hospital_id, $api_request = 'old')
    {
        return $this->getContractStatistics($physician_id, $contract_id, $hospital_id, $api_request);
    }

    public function getContractDetailsNew($physician_id, $contract_id, $hospital_id, $api_request = 'old')
    {
        return $this->getContractStatisticsNew($physician_id, $contract_id, $hospital_id, $api_request);
    }

    public function getContractDetailsPerformance($physician_id, $contract_id, $hospital_id, $api_request = 'old')
    {
        return $this->getContractStatisticsPerformance($physician_id, $contract_id, $hospital_id, $api_request);
    }

    public function getContractCurrentStat($contract)
    {
        return $this->getContractCurrentStatistics($contract);
    }

    public function getPriorContractStat($contract)
    {
        return $this->getPriorContractStatistics($contract);
    }

    public function getActiveContract($physician)
    {
        return $this->getActiveContracts($physician);
    }

    public function activeHospitalOncallContract($hospital_id)
    {
        return $this->getContractsForHospital($hospital_id);
    }

    public function getAllManager($agreement, $type)
    {
        return $this->getAllManagers($agreement, $type);
    }

    public function getUnapproveLogs($physician)
    {
        return $this->getUnapproveLogsDetails($physician);
    }

    public function paymentType()
    {
        return $this->belongsTo('App\PaymentType');
    }

    public function psaMetrics()
    {
        return $this->hasMany('App\ContractPsaMetrics');
    }

    public function psaWrvuRates()
    {
        return $this->hasMany('App\ContractPsaWrvuRates');
    }

    public function getContractPeriods($contract_id)
    {
        return $this->getContractPeriod($contract_id);
    }

    public function getContractsDetailOnProfileScreen($physician_id, $hospital_id, $api_request = 'old')
    {
        return $this->getContractStatisticsDetail($physician_id, $hospital_id, $api_request);
    }

    public static function createContract($physician_id = 0, $practice_id = 0, $view = 'physician_screen')
    {
        $selected_agreement = Request::input('agreement');
        $selected_contract_type = Request::input('contract_type');
        $agreement = Agreement::findOrFail($selected_agreement);

        /*
         * Validation - physician should only have single contract in one agreement. Each contract under physician should be under different agreements.
         */

        /*$check_contract_against_agreement = self::Where('agreement_id', '=', $selected_agreement)->Where('physician_id', '=', $physician_id)->count();

        if($check_contract_against_agreement >= 1){
            return Redirect::back()->with([
                'error' => Lang::get('contracts.agreement_contract_present')
            ])->withInput();
        }*/

        if ($agreement->payment_frequency_type == 4 && $selected_contract_type == 20) {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.quarterly_agreement_error')
            ])->withInput();
        }

        if (Request::exists('selectedPhysicianList')) {
            if (count(Request::input('selectedPhysicianList')) == 0) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.empty_physician_create_contract')
                ])->withInput();
            }
        } else {
            return Redirect::back()->with([
                'error' => Lang::get('physicians.empty_physician_create_contract')
            ])->withInput();
        }

        /*
         * Validations for single practice physicians
         */

        if (Request::exists('selectedPhysicianList')) {
            $physician_practice_arr_of_str = Request::input('selectedPhysicianList');
            $check_practice = 0;
            foreach ($physician_practice_arr_of_str as $physician_practice_str) {
                $physician_practice_arr = explode("_", $physician_practice_str);
                if ($check_practice != 0 && $check_practice != $physician_practice_arr[1]) {
                    return Redirect::back()->with([
                        'error' => Lang::get('physicians.physician_of_diff_practice_create_contract')
                    ])->withInput();
                }
                $check_practice = $physician_practice_arr[1];
            }
        }

        // Fetch invoice type from hospital.

//        if($practice_id != 0){
//            $practice = Practice::findOrFail($practice_id);
//        }
        $result_hospital = Hospital::findOrFail($agreement->hospital_id);

        /*if(Request::input('contract_type')==ContractType::ON_CALL) {*//*remove to add payment type check*/
        if (Request::input('payment_type') == PaymentType::PER_DIEM) {
            $validation = new ContractValidation();
            if (Request::input('on_off') == 1) {
                if (!$validation->validateOnCallData(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else {
                if (!$validation->validateOnCallAgreementsData(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            }
        } else {
            $validation = new ContractValidation();
            /*if( Request::input('contract_type')==ContractType::MEDICAL_DIRECTORSHIP) {*//*remove to add payment type check*/
            if (Request::input('payment_type') == PaymentType::HOURLY) {
                if (!$validation->validateMedicalDirectership(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if (Request::input('payment_type') == PaymentType::PER_UNIT) {
                if (!$validation->validatePerUnit(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if (Request::input('payment_type') == PaymentType::PSA) {
                if (!$validation->validatePSA(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } //Chaitraly::new code for monthly stipend validation
            else if (Request::input('payment_type') == PaymentType::MONTHLY_STIPEND) {
                if (!$validation->validateMonthlyStipend(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if (Request::input('payment_type') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if (!$validation->validatePerDiemUncompensatedData(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }

            } else if (Request::input('payment_type') == PaymentType::REHAB) {
                if (!$validation->validateCreateRehab(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                } else {
                    $selected_agreement = Agreement::find(Request::input('agreement'));
                    if ($selected_agreement->payment_frequency_type != 1) {
                        return Redirect::back()->with([
                            'error' => Lang::get('physicians.rehab_with_other_frequency_agreement_error')
                        ]);
                    }
                }

            } else if (!$validation->validateCreate(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
        }

        $validation = new ContractValidation();
        $emailvalidation = new EmailValidation();
        if (Request::input('contract_type') == 20) {
            if (!$validation->recipientValidate(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
            if (!$emailvalidation->validateEmailDomain(Request::input())) {
                return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
            }

        }

        $max_uploadable_contracts_validate_count = 6;
        for ($i = 1; $i < $max_uploadable_contracts_validate_count + 1; $i++) {
            if (Request::hasFile('upload_contract_copy_' . $i)) {
                if (!$validation->contractCopyValidate(Request::file())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            }
        }

        //validation for uncompensated payment type

        if (Request::input('payment_type') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

            $on_call_rate_count = Request::input("on_call_rate_count");
            $uncompensated_error = (array)[];
            $flag = false;
            $uncompensated_error[0] = "";


            //validation for partial hour calucation
            if (Request::input("partial_hours") == 1) {

//                $hospital_id = Practice::where("id", "=", $practice_id)->pluck("hospital_id")->toArray();
//                $agreement_id = Agreement::where("hospital_id", "=", $hospital_id)->pluck("id")->toArray();

                $partial_hour_calculations = Contract::where("agreement_id", "=", $agreement->id)
                    ->whereNull('contracts.deleted_at')
                    ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                    ->where('contracts.partial_hours', '=', 1)
                    ->where('contracts.payment_type_id', '=', 5)
                    ->pluck("partial_hours_calculation")->toArray();

                if (count($partial_hour_calculations) > 0 && ($partial_hour_calculations[0] != Request::input("hours_calculation"))) {
                    $flag = true;
                    $uncompensated_error[0] = "You can select hour for calcultion only for " . $partial_hour_calculations[0] . "hrs.";

                }
            }

            $next_start_day = 0;
            //added valdiation for dynamic rates
            for ($i = 1; $i <= $on_call_rate_count; $i++) {
                $end_day = "end_day" . ($i);
                $start_day = "start_day_hidden" . ($i);
                $start_day_value = Request::input(($start_day));
                $end_day_value = Request::input(($end_day));
                $rates = "rate" . ($i);
                $rates_value = Request::input(($rates));
                $errors = "";

                if (empty($rates_value) || $rates_value == " ")//preg_match("'/^[0-9]+(\.[0-9]{1,2})?$/'", $rates_value) )
                {
                    $flag = true;
                    $errors = "On Call Rate -" . ($i) . ' ' . "Required.";

                } else if (!(preg_match('/\d{1,4}\.\d{2}/', $rates_value)))//preg_match("'/^[0-9]+(\.[0-9]{1,2})?$/'", $rates_value) )
                {
                    $flag = true;
                    $errors = $errors . ' ' . "On Call Rate - " . ($i) . ' ' . "format is invalid.";

                }
                //log::info("error $i : end_day_value : start day vlaue",array($end_day_value,$start_day_value));
                if ($end_day_value < $start_day_value) {
                    $flag = true;
                    $errors = $errors . " End day range should be greater than start day range for on call rate -" . ($i) . ".";

                }
                if ($next_start_day != 0 && $next_start_day != $start_day_value) {
                    $flag = true;
                    $errors = $errors . "Start day should be just above than previous end day for on call rate -" . ($i) . ".";
                }
                $next_start_day = 0;
                $next_start_day += $end_day_value + 1;

                $uncompensated_error[$i] = $errors;

            }

            if ($flag == true) {
                return Redirect::back()->with(['on_call_rate_error' => $uncompensated_error])->withInput();
            }
        }


        //contract custom action validation by Akash

        $categories = ActionCategories::all();
        $custome_action_error = [];
//        $practice_details = Practice::findOrFail($practice_id);

        foreach ($categories as $category) {
            $category_name = 'customaction_name_' . $category->id;
            $customaction_names = Request::input($category_name);

            if ($customaction_names) {
                $custome_action_category = [];
                foreach ($customaction_names as $id => $customaction_name) {
                    $existactionforcategory = Action::select("actions.*")
                        ->where('actions.name', '=', $customaction_name)
                        ->where("actions.category_id", "=", $category->id)
                        ->where(function ($query) use ($agreement) {
                            $query->where("actions.hospital_id", "=", $agreement->hospital_id)
                                ->orWhere("actions.hospital_id", "=", 0);
                        })
                        ->get();

                    if (count($existactionforcategory) > 0) {
                        $custome_action_category[$customaction_name] = true;
                    } else {
                        if ($customaction_name != '') {
                            $custome_action_category[$customaction_name] = false;
                        }
                    }

                }

                $custome_action_error[$category_name] = $custome_action_category;

            }
        }

        if ($custome_action_error) {
            // Log::Info("fromContract.php", array($custome_action_error));
            // return Redirect::back()->withErrors($custome_action_error);
            foreach ($custome_action_error as $category_name => $action_arr) {
                if (count($action_arr) > 0) {
                    foreach ($action_arr as $action_name => $flag) {
                        if ($flag == true) {
                            return Redirect::back()->with(['action_error' => $custome_action_error])->withInput();
                        }
                    }
                }
            }
        }

        if (!Request::has('default_dates') || Request::input('default_dates') != 1) {
            /*
            - Validate if the agreement end date and manual contract end date fall between current day and agreemnet end date
            - added on: 2018/12/26
            */
            $agreementObject = new Agreement;
            $endDateQuery = $agreementObject->getAndCheckManualEndDate(Request::input('agreement'), @mysql_date(Request::input('manual_end_date')), 'create');
            if (!$endDateQuery) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.agreement_date_manual_end_date_error')
                ])->withInput();
            }
            $date_after90_days_of_end_date = date('Y-m-d', strtotime('+90 day', strtotime(Request::input('manual_end_date'))));
            if ((strtotime(Request::input('manual_end_date')) > strtotime(Request::input('valid_upto_date'))) || (strtotime(Request::input('valid_upto_date')) > strtotime($date_after90_days_of_end_date))) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.valid_upto_date_error')
                ])->withInput();
            }
        }
//        if($physician_id != 0){
//            $physician = Physician::findOrFail($physician_id);
//        }

        DB::beginTransaction();
        try {
            $contract = new Contract();
            $physician_emailCheck = Request::input('physician_emailCheck');
            $contract_deadline_option = Request::input('contract_deadline_on_off');
            $burden_of_call = Request::input('burden_on_off');
            $contract->agreement_id = Request::input('agreement');
//        $contract->physician_id = $physician->id;
            $contract->contract_type_id = Request::input('contract_type');
            $contract->payment_type_id = Request::input('payment_type');
            $holiday_on_off = Request::input('holiday_on_off');
            $contract->mandate_details = Request::input('mandate_details');
            $contract->physician_opt_in_email = $physician_emailCheck ? '1' : '0';
            $contract->deadline_option = $contract_deadline_option ? '1' : '0';
//        $contract->practice_id = $practice_id;
            $contract->rate = '0.00';
            $contract->allow_max_hours = Request::input('log_over_max_hour');

            /*if($contract->contract_type_id==ContractType::ON_CALL) {*//*remove to add payment type check*/
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if (Request::input('on_off') == 1) {
                    $contract->weekday_rate = 0.00;
                    $contract->weekend_rate = 0.00;
                    $contract->holiday_rate = 0.00;

                    $contract->on_call_rate = floatval(Request::input('On_Call_rate'));
                    $contract->called_back_rate = floatval(Request::input('called_back_rate'));
                    $contract->called_in_rate = floatval(Request::input('called_in_rate'));
                    $contract->burden_of_call = $burden_of_call ? '1' : '0';
                    //accept zero rate for per_diem contract by 1254 : set on call process flag=1 to on call rate zero
                    $contract->on_call_process = 1;

                } else {
                    $contract->weekday_rate = floatval(Request::input('weekday_rate'));
                    $contract->weekend_rate = floatval(Request::input('weekend_rate'));
                    $contract->holiday_rate = floatval(Request::input('holiday_rate'));
                    $contract->on_call_rate = 0.00;
                    $contract->called_back_rate = 0.00;
                    $contract->called_in_rate = 0.00;
                    $contract->burden_of_call = '0';
                    $contract->holiday_on_off = $holiday_on_off ? 1 : 0;    // physicians log the hours for holiday activity on any day
                }
                // Sprint 6.1.17 Start
                $contract->annual_cap = floatval(Request::input('annual_max_shifts'));
                // Sprint 6.1.17 End
            } //Skip Professional Services Agreements (PSA)
            else if ($contract->payment_type_id == PaymentType::PSA) {

            } else {
                $contract->burden_of_call = '0';
                /*if( $contract->contract_type_id==ContractType::MEDICAL_DIRECTORSHIP) {*//*remove to add payment type check*/
                if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                    $contract->annual_cap = floatval(Request::input('annual_cap'));
                    $contract->prior_worked_hours = floatval(Request::input('prior_worked_hours'));
                    $contract->prior_amount_paid = floatval(Request::input('prior_amount_paid'));
                    if (Request::input('contract_prior_start_date_on_off') == 1) {

                        $contract->prior_start_date = @mysql_date(Request::input('prior_start_date'));
                    } else {
                        $contract->prior_start_date = '0000-00-00';
                    }
                    //$contract->prior_start_date = @mysql_date(Request::input('prior_start_date'));
                }
                $contract->min_hours = floatval(Request::input('min_hours'));
                $contract->max_hours = floatval(Request::input('max_hours'));
                $contract->rate = floatval(Request::input('rate'));
                $contract->expected_hours = floatval(Request::input('hours'));

            }

            //call-coverage-duration: saved partial hours by 1254
            if (Request::input('partial_hours') == 1) {
                $contract->partial_hours = 1;

            } else {
                $contract->partial_hours = 0;
            }

            $contract->archived = false;
            // 6.1.1.8 Attestation
            if (Request::input('contract_type') == 20) {
                $contract->state_attestations_monthly = Request::input('state_attestations_monthly', 0);
                $contract->state_attestations_annually = Request::input('state_attestations_annually', 0);
                $contract->receipient1 = Request::input('receipient1');
                $contract->receipient2 = Request::input('receipient2');
                $contract->receipient3 = Request::input('receipient3');
                $contract->supervision_type = Request::input('supervision_type');
            } else {
                $contract->state_attestations_monthly = 0;
                $contract->state_attestations_annually = 0;
                $contract->receipient1 = "";
                $contract->receipient2 = "";
                $contract->receipient3 = "";
                $contract->supervision_type = 0;
            }

            if (Request::input('contract_name') > 0) {
                $contract->contract_name_id = Request::input('contract_name');
            }

            /*
            @description:custom action enable column
            @value : 0-not enabled,1-enabled*/
            $contract->custom_action_enabled = Request::has('custom_action_enable') ? 1 : 0;
            if ($contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT || $contract->payment_type_id == PaymentType::REHAB) {
                $contract->custom_action_enabled = 0;
            }
            // added on 10-17-2019
            $contract->default_to_agreement_dates = Request::has('default_dates') ? 1 : 0;
            $agreement_details = Agreement::findOrFail($contract->agreement_id);
            if ($contract->default_to_agreement_dates == 1) {

                // added on 2018-12-26
                $contract->manual_contract_end_date = $agreement_details->end_date;
                // added on 10-17-2019
                $contract->manual_contract_valid_upto = $agreement_details->valid_upto;
            } else {
                // added on 2018-12-26
                $contract->manual_contract_end_date = @mysql_date(Request::input('manual_end_date'));
                // added on 10-17-2019
                $contract->manual_contract_valid_upto = @mysql_date(Request::input('valid_upto_date'));
            }

            if (Request::input('payment_type') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if (Request::input('partial_hours') == 1) {
                    $partial_hours_calculation = Request::input('hours_calculation');
                    $contract->partial_hours_calculation = $partial_hours_calculation;
                } else {
                    $contract->partial_hours_calculation = 0.00;
                }

            } else if (Request::input('payment_type') == PaymentType::PER_DIEM) {

                if (Request::input('partial_hours') == 1) {
                    $contract->partial_hours_calculation = 24.00;

                } else {
                    $contract->partial_hours_calculation = 0.00;
                }
            } else {
                $contract->partial_hours_calculation = 24.00;
            }
            // if(Request::input('payment_type') == PaymentType::STIPEND || Request::input('payment_type') == PaymentType::HOURLY ) {
            if (Request::input('payment_type') == PaymentType::STIPEND || Request::input('payment_type') == PaymentType::HOURLY || Request::input('payment_type') == PaymentType::MONTHLY_STIPEND || Request::input('payment_type') == PaymentType::TIME_STUDY || Request::input('payment_type') == PaymentType::PER_UNIT) {
                $contract->quarterly_max_hours = Request::input('quarterly_max_hours', 0);
            }

            // 6.1.1.8 Attestation
            if (Request::input('contract_type') == 20) {
                $contract->state_attestations_monthly = Request::input('state_attestations_monthly', 0);
                $contract->state_attestations_annually = Request::input('state_attestations_annually', 0);
            } else {
                $contract->state_attestations_monthly = 0;
                $contract->state_attestations_annually = 0;
            }

            if (Request::input('payment_type') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS || Request::input('payment_type') == PaymentType::PER_DIEM) {
                $contract->annual_max_payment = Request::input('annual_max_payment');
            }

            if (Request::input('approval_process') == 1 && !Request::has('default')) {
                $contract->default_to_agreement = '0';
            }

            if (!$contract->save()) {
                DB::rollback();
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.create_contract_error')
                ])->withInput();
            } else //Save contract here
            {
                if (Request::exists('selectedPhysicianList')) {
                    $physician_practice_arr_of_str = Request::input('selectedPhysicianList');
                    foreach ($physician_practice_arr_of_str as $physician_practice_str) {
                        $physician_practice_arr = explode("_", $physician_practice_str);
                        $physician_id = $physician_practice_arr[0];
                        $practice_id = $physician_practice_arr[1];
//                        $assign_physician = PhysicianContracts::AssignPhysicianToContract($physician_id, $contract->id, $practice_id);
                        $contract->physicians()->attach([$contract->id => ['physician_id' => $physician_id, 'contract_id' => $contract->id, 'practice_id' => $practice_id, 'created_by' => Auth::user()->id, 'updated_by' => Auth::user()->id]]);
//
                        // Added 6.1.13 START
                        $max_sort_order = SortingContractName::select([DB::raw('MAX(sorting_contract_names.sort_order) AS max_sort_order')])
                            ->where('sorting_contract_names.practice_id', '=', $practice_id)
                            ->where('sorting_contract_names.physician_id', '=', $physician_id)
                            ->where('sorting_contract_names.is_active', '=', 1)
                            ->first();

                        $sort_contract = new SortingContractName();
                        $sort_contract->practice_id = $practice_id;
                        $sort_contract->physician_id = $physician_id;
                        $sort_contract->contract_id = $contract->id;
                        $sort_contract->sort_order = $max_sort_order['max_sort_order'] + 1;
                        $sort_contract->save();

                        // Added 6.1.13 END
                    }
                }
                $custom_actions = [];
                //store rates in Contract rate table
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    if (Request::input('on_off') == 1) {
                        // $contractOnCallRate=ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('On_Call_rate')),ContractRate::ON_CALL_RATE);
                        // $contractCalledBackRate=ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('called_back_rate')),ContractRate::CALLED_BACK_RATE);
                        // $contractCalledInRate=ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('called_in_rate')),ContractRate::CALLED_IN_RATE);
                        $contractOnCallRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('On_Call_rate')), ContractRate::ON_CALL_RATE);
                        $contractCalledBackRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('called_back_rate')), ContractRate::CALLED_BACK_RATE);
                        $contractCalledInRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('called_in_rate')), ContractRate::CALLED_IN_RATE);
                    } else {
                        // $contractWeekdayRate=ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('weekday_rate')),ContractRate::WEEKDAY_RATE);
                        // $contractWeekendRate=ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('weekend_rate')),ContractRate::WEEKEND_RATE);
                        // $contractHolidayRate=ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('holiday_rate')),ContractRate::HOLIDAY_RATE);
                        $contractWeekdayRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('weekday_rate')), ContractRate::WEEKDAY_RATE);
                        $contractWeekendRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('weekend_rate')), ContractRate::WEEKEND_RATE);
                        $contractHolidayRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('holiday_rate')), ContractRate::HOLIDAY_RATE);
                    }
                } //Skip Professional Services Agreements (PSA)
                else if ($contract->payment_type_id == PaymentType::PSA) {
                } else if (Request::input('payment_type') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

                    $on_call_rate_count = Request::input("on_call_rate_count");
                    $uncompensated_error = (array)[];
                    $flag = false;

                    //added valdiation for dynamic rates
                    for ($i = 1; $i <= $on_call_rate_count; $i++) {

                        $end_day = "end_day" . ($i);
                        $start_day = "start_day_hidden" . ($i);
                        $start_day_value = Request::input(($start_day));
                        $end_day_value = Request::input(($end_day));
                        $rates = "rate" . ($i);
                        $rates_value = Request::input(($rates));
                        $errors = "";

                        if (empty($rates_value) || $rates_value == " ")//preg_match("'/^[0-9]+(\.[0-9]{1,2})?$/'", $rates_value) )
                        {
                            $flag = true;
                            $errors = "On Call Rate -" . ($i) . ' ' . "Required.";

                        } else if (!(preg_match('/\d{1,4}\.\d{2}/', $rates_value)))//preg_match("'/^[0-9]+(\.[0-9]{1,2})?$/'", $rates_value) )
                        {
                            $flag = true;
                            $errors = $errors . ' ' . "On Call Rate - " . ($i) . ' ' . "format is invalid.";

                        }
                        // log::info("error $i : end_day_value : start day vlaue",array($end_day_value,$start_day_value));
                        if ($end_day_value < $start_day_value) {

                            $flag = true;
                            $errors = $errors . " End day range should be greater than start day range for on call rate -" . ($i) . ".";
                            // log::info($errors);
                        }
                        $uncompensated_error[$i] = $errors;
                    }

                    if ($flag == true) {
                        DB::rollback();
                        // log::info("error...",array($errors));
                        return Redirect::back()->with(['on_call_rate_error' => $uncompensated_error])->withInput();
                    }

                    for ($i = 1; $i <= $on_call_rate_count; $i++) {

                        $end_day = "end_day" . $i;
                        $start_day = "start_day_hidden" . ($i);

                        $oncalluncompensated_rate = "rate" . ($i);
                        $rate = floatval(Request::input($oncalluncompensated_rate));
                        if ($i == 1) {

                            $start_day_value = 1;
                        } else {
                            $start_day_value = Request::input(($start_day));
                        }
                        $end_day_value = Request::input(($end_day));
                        // $contractOnCallUncompensatedRate = ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,$rate,ContractRate::ON_CALL_UNCOMPENSATED_RATE,$i,$start_day_value,$end_day_value);
                        $contractOnCallUncompensatedRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, $rate, ContractRate::ON_CALL_UNCOMPENSATED_RATE, $i, $start_day_value, $end_day_value);

                    }
                } else {
                    if ($contract->payment_type_id == PaymentType::MONTHLY_STIPEND) {
                        // $contractFMVRate = ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('rate')),ContractRate::MONTHLY_STIPEND_RATE);
                        $contractFMVRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('rate')), ContractRate::MONTHLY_STIPEND_RATE);

                    } else {
                        // $contractFMVRate = ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('rate')),ContractRate::FMV_RATE);
                        $contractFMVRate = ContractRate::insertContractRate($contract->id, $agreement_details->payment_frequency_start_date, $contract->manual_contract_end_date, floatval(Request::input('rate')), ContractRate::FMV_RATE);
                    }

                    //$contractFMVRate = ContractRate::insertContractRate($contract->id,$agreement_details->start_date,$contract->manual_contract_end_date,floatval(Request::input('rate')),ContractRate::FMV_RATE);
                    $actions = Request::input('actions');
                    $hours = floatval(Request::input("hours"));

                    //Action-Redesign by 1254
                    if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                        $units = Request::input('units');
                        $hours = floatval(Request::input("hours"));

                        if ($units) {
                            //save into action table
                            $action = new Action();
                            $action->name = $units;
                            $action->contract_type_id = $contract->contract_type_id;
                            $action->payment_type_id = $contract->payment_type_id;
                            $action->hospital_id = $agreement_details->hospital_id;
                            $action->save();
                            //save into action_contract table
                            $action_contract = new ActionContract();
                            $action_contract->contract_id = $contract->id;
                            $action_contract->action_id = $action->id;
                            $action_contract->save();

                        }
                    } else {
                        if ($actions != null) {

                            foreach ($contract->actions as $action) {
                                if (!in_array($action->id, $actions)) {
                                    $contract->actions()->detach($action->id);
                                }
                            }

                            foreach ($actions as $action_id) {
                                $action = $contract->actions()->where('action_id', '=', $action_id)->first();
                                // $hours = floatval(Request::input("action-{$action_id}-value"));
                                $hours = floatval(Request::input("hours"));
                                if ($action) {
                                    $action->pivot->where('contract_id', '=', $contract->id)
                                        ->where('action_id', '=', $action_id)
                                        ->update(array('hours' => $hours));
                                } else {
                                    $contract->actions()->attach($action_id, ['hours' => $hours]);
                                }
                            }
                        } elseif (count($contract->actions) > 0) {
                            foreach ($contract->actions as $action) {
                                $contract->actions()->detach($action->id);
                            }
                        } else {
                            //saving hours into if no action selected
                            $action_contract = new ActionContract();
                            $action_contract->contract_id = $contract->id;
                            $action_contract->hours = $hours;
                            $action_contract->save();
                        }
                    }
                    //Action-Redesign by 1254
                    $categories = ActionCategories::all();

                    foreach ($categories as $category) {
                        $customaction_names = Request::input('customaction_name_' . $category->id);

                        //  $cnt = count($customaction_names);
                        if ($customaction_names) {
                            foreach ($customaction_names as $customaction_name) {

                                $hours = floatval(Request::input("hours"));
                                if ($customaction_name) {
                                    //save into action table
                                    $action = new Action();
                                    $action->name = $customaction_name;
                                    $action->category_id = $category->id;
                                    $action->contract_type_id = $contract->contract_type_id;
                                    $action->hospital_id = $agreement->hospital_id;
                                    $action->save();

                                    if (!in_array($action->id, $custom_actions)) {
                                        $custom_actions[] = $action->id;
                                    }
                                    //save into action_contract table

                                    $action_contract = new ActionContract();

                                    $action_contract->contract_id = $contract->id;
                                    $action_contract->action_id = $action->id;

                                    if ($hours > 0.0) {

                                        $action_contract->hours = $hours;
                                    }
                                    $action_contract->save();

                                    //save into action_hospitals table

                                    $action_hospital = new ActionHospital();
                                    $action_hospital->hospital_id = $agreement->hospital_id;
                                    $action_hospital->action_id = $action->id;
                                    $action_hospital->save();
                                }


                            }
                        }

                    }
                }

                $internal_note_insert = new ContractInternalNote;
                $internal_note_insert->contract_id = $contract->id;
                $internal_note_insert->note = Request::input('internal_notes');
                $internal_note_insert->save();


                $max_uploadable_contracts = 6;
                for ($i = 1; $i < $max_uploadable_contracts + 1; $i++) {
                    if (Request::hasFile('upload_contract_copy_' . $i)) {

                        // File was successfully uploaded and is now in temp storage.
                        // Move it somewhere more permanent to access it later.
                        $file = Request::file('upload_contract_copy_' . $i);
                        $file_name = $file->getClientOriginalName();
                        $extension = Request::file('upload_contract_copy_' . $i)->getClientOriginalExtension();
                        $current_time = time();//current time
                        $filename = $contract->id . '_' . $i . '_' . $current_time . '.' . $extension;

                        if (Request::file('upload_contract_copy_' . $i)->move(storage_path() . '/contract_copies/', $filename)) {
                            // File successfully saved to permanent storage
                            //log::info('success to upload');
                            $contract_document = new ContractDocuments();
                            $contract_document->contract_id = $contract->id;
                            $contract_document->filename = $filename;
                            $contract_document->is_active = '1';
                            $contract_document->save();
                        } else {
                            // Failed to save file, perhaps dir isn't writable. Give the user an error.
                            //log::info('fail to upload');
                        }

                    }
                }

                // if contract deadline option for this contract is enabled, the newly entered days should be saved as deadline days
                if ($contract->deadline_option == '1' && Request::input('deadline_days') != '') {
                    $contract_deadline_days = new ContractDeadlineDays();
                    $contract_deadline_days->contract_id = $contract->id;
                    $contract_deadline_days->contract_deadline_days = Request::input('deadline_days');
                    $contract_deadline_days->is_active = '1';
                    $contract_deadline_days->save();
                } else {
                    $contract->deadline_option = '0';
                }

                /*if($contract->contract_type_id==ContractType::ON_CALL) {*//*remove to add payment type check*/
                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

                    //save annual max for both payment type per-diem and uncompensated days
                    $contract->annual_max_payment = Request::input('annual_max_payment');
                    $duration = 0;
                    if (Request::input('actions') != null) {
                        foreach ($contract->activities() as $activity) {
                            switch ($activity->name) {
                                case "Weekend - HALF Day - On Call":
                                    $duration = Request::input('on_off') == 1 ? 0 : (in_array($activity->id, Request::input('actions')) ? 0.5 : 0);
                                    //$duration = Request::input('on_off') == 1 ? 0 : 0.5;
                                    break;
                                case "Weekend - FULL Day - On Call":
                                    $duration = Request::input('on_off') == 1 ? 0 : (in_array($activity->id, Request::input('actions')) ? 1 : 0);
                                    break;
                                case "Weekday - HALF Day - On Call":
                                    $duration = Request::input('on_off') == 1 ? 0 : (in_array($activity->id, Request::input('actions')) ? 0.5 : 0);
                                    break;
                                case "Weekday - FULL Day - On Call":
                                    $duration = Request::input('on_off') == 1 ? 0 : (in_array($activity->id, Request::input('actions')) ? 1 : 0);
                                    break;
                                case "Holiday - HALF Day - On Call":
                                    $duration = Request::input('on_off') == 1 ? 0 : (in_array($activity->id, Request::input('actions')) ? 0.5 : 0);
                                    break;
                                case "Holiday - FULL Day - On Call":
                                    $duration = Request::input('on_off') == 1 ? 0 : (in_array($activity->id, Request::input('actions')) ? 1 : 0);
                                    break;
                                case "On-Call":
                                    $duration = Request::input('on_off') == 1 ? (in_array($activity->id, Request::input('actions')) ? 1 : 0) : 0;
                                    break;
                                case "Called-Back":
                                    $duration = Request::input('on_off') == 1 ? (in_array($activity->id, Request::input('actions')) ? 1 : 0) : 0;
                                    break;
                                case "Called-In":
                                    $duration = Request::input('on_off') == 1 ? (in_array($activity->id, Request::input('actions')) ? 1 : 0) : 0;
                                    break;
                                case "On-Call/Uncompensated":
                                    $duration = in_array($activity->id, Request::input('actions')) ? 1 : 0;
                                    break;

                                default:
                                    /*App::abort(501);*/
                                    return Redirect::back()->with([
                                        'error' => Lang::get('physicians.create_contract_error')
                                    ])->withInput();
                            }

                            if ($duration > 0) {
                                if ((Request::input("partial_hours") == 1) && ($activity->name == "Weekend - HALF Day - On Call" || $activity->name == "Weekday - HALF Day - On Call" || $activity->name == "Holiday - HALF Day - On Call")) {

                                    $contract->actions()->detach($activity->id, ['hours' => $duration]);
                                } else {
                                    $contract->actions()->attach($activity->id, ['hours' => $duration]);
                                }
                            }
                            // added for save new names for on call activities if reocord is new -insert and if record is already exist -update

                            $name = Request::input('name' . $activity->id);
                            if ($name != null && $name != '') {
                                $data = array('name' => $name, 'action_id' => $activity->id, 'contract_id' => $contract->id);
                                DB::table('on_call_activities')->insert($data);
                            }
                        }
                    }

                }

                if ($contract->payment_type_id == PaymentType::PSA) {
                    $contract_psa_metrics = new ContractPsaMetrics();
                    $contract_psa_metrics->contract_id = $contract->id;
                    $contract_psa_metrics->annual_comp = floatval(Request::input('annual_comp'));
                    $contract_psa_metrics->annual_comp_fifty = floatval(Request::input('annual_comp_fifty'));
                    $contract_psa_metrics->wrvu_fifty = Request::input('wrvu_fifty');
                    $contract_psa_metrics->annual_comp_seventy_five = floatval(Request::input('annual_comp_seventy_five'));
                    $contract_psa_metrics->wrvu_seventy_five = Request::input('wrvu_seventy_five');
                    $contract_psa_metrics->annual_comp_ninety = floatval(Request::input('annual_comp_ninety'));
                    $contract_psa_metrics->wrvu_ninety = Request::input('wrvu_ninety');
                    $contract_psa_metrics->created_by = Auth::user()->id;
                    $contract_psa_metrics->updated_by = Auth::user()->id;
                    $contract_psa_metrics->save();
                    $contract->enter_by_day = Request::input('enter_by_day');
                    $contract->wrvu_payments = Request::input('wrvu_payments');
                    /*add invoice notes for physician*/
                    if (Request::input('contract_psa_wrvu_rates_count') > 0) {
                        $index = 1;
                        for ($i = 1; $i <= Request::input('contract_psa_wrvu_rates_count'); $i++) {
                            if (Request::input("contract_psa_wrvu_ranges" . $i) != '' && "contract_psa_wrvu_rates" . $i != '') {
                                $contractPsaWrvuRate = new ContractPsaWrvuRates();
                                $contractPsaWrvuRate->contract_id = $contract->id;
                                $contractPsaWrvuRate->upper_bound = Request::input("contract_psa_wrvu_ranges" . $i);
                                $contractPsaWrvuRate->rate = Request::input("contract_psa_wrvu_rates" . $i);
                                $contractPsaWrvuRate->rate_index = $index;
                                $contractPsaWrvuRate->created_by = Auth::user()->id;
                                $contractPsaWrvuRate->updated_by = Auth::user()->id;
                                $contractPsaWrvuRate->save();
                                $index++;
                            }
                        }
                    }
                }

                if (Request::input('approval_process') == 1 && !Request::has('default')) {
                    $contract->default_to_agreement = '0';
                }

                if (Request::input('approval_process') == 1 && !Request::has('default')) {
                    //Write code for approval manager conditions & to save them in database
                    $agreement_id = $contract->agreement_id;//fetch agreement id
                    $contract_id = $contract->id;//fetch contract id
                    $approval_manager_info = array();
                    $levelcount = 0;
                    $approval_level = array();
                    $emailCheck = Request::input('emailCheck');
                    //Fetch all levels of approval managers & remove NA approvaal levels
                    for ($i = 1; $i < 7; $i++) {
//                    if (Request::input('approverTypeforLevel' . $i) != 0) {
                        if (Request::input('approval_manager_level' . $i) != 0) {

//                        $approval_level[$levelcount]['approvalType'] = Request::input('approverTypeforLevel' . $i);
                            $approval_level[$levelcount]['approvalType'] = 0;
                            $approval_level[$levelcount]['level'] = $levelcount + 1;
                            $approval_level[$levelcount]['approvalManager'] = Request::input('approval_manager_level' . $i);
                            $approval_level[$levelcount]['initialReviewDay'] = Request::input('initial_review_day_level' . $i);
                            $approval_level[$levelcount]['finalReviewDay'] = Request::input('final_review_day_level' . $i);
                            $approval_level[$levelcount]['emailCheck'] = $emailCheck > 0 ? in_array("level" . $i, $emailCheck) ? '1' : '0' : '0';
                            $levelcount++;
                        }
                    }
                    // asort($approval_level);//Sorting on basis of type of approval level
                    $approval_level_number = 1;
                    $fail_to_save_level = 0;
                    foreach ($approval_level as $key => $approval_level) {
                        $contract_approval_manager_info = new ApprovalManagerInfo;
                        $contract_approval_manager_info->contract_id = $contract_id;
                        $contract_approval_manager_info->agreement_id = $agreement_id;
//                    $contract_approval_manager_info->level = $approval_level_number;
                        $contract_approval_manager_info->level = $approval_level['level'];
                        $contract_approval_manager_info->type_id = $approval_level['approvalType'];
                        $contract_approval_manager_info->user_id = $approval_level['approvalManager'];
                        $contract_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                        $contract_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                        $contract_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                        $contract_approval_manager_info->is_deleted = '0';

                        if (!$contract_approval_manager_info->save()) {
                            DB::rollback();
                            $fail_to_save_level = 1;
                        } else {
                            //success
                            $approval_level_number++;
                        }
                    }//End of for loop
                    if ($fail_to_save_level == 1) {
                        //if fails while saving approval level, delete all the approval levels & agreement as well
                        //Delete all the entries from approval levels for the agreement
                        DB::table('agreement_approval_managers_info')->where('agreement_id', "=", $agreement_id)->where('contract_id', "=", $contract_id)->forceDelete();
                        DB::table('contracts')->where('id', "=", $contract_id)->forceDelete();
                        DB::rollback();
                        return Redirect::back()->with([
                            'error' => Lang::get('physicians.create_contract_error')
                        ])->withInput();
                    } else {
//                    $result["response"] = "success";
//                    $result["msg"] = Lang::get('hospitals.create_agreement_success');
                    }
                }

                /*add invoice notes for physician*/
                if (Request::input('note_count') > 0) {
                    $index = 1;
                    for ($i = 1; $i <= Request::input('note_count'); $i++) {
                        if (Request::input("note" . $i) != '') {
                            $invoice_note = new InvoiceNote();
                            $invoice_note->note_type = InvoiceNote::CONTRACT;
                            $invoice_note->note_for = $contract->id;
                            $invoice_note->note_index = $index;
                            $invoice_note->note = Request::input("note" . $i);
                            $invoice_note->is_active = true;
                            $invoice_note->hospital_id = $result_hospital->id;
                            $invoice_note->save();
                            $index++;
                        }
                    }
                }
            }

            // 6.1.9 contract duties sorting management
            $sorting_contract_data = json_decode(Request::input('sorting_contract_data'));
            if ($sorting_contract_data > 0) {
                $actions = Request::input('actions');
                $index = 0;
                if ($actions) {
                    foreach ($sorting_contract_data as $sorting_contract_data) {
                        if ($contract) {
                            if (in_array($sorting_contract_data->action_id, $actions)) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $sorting_contract_data->category_id;
                                $sorting_contract->action_id = $sorting_contract_data->action_id;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }

                    foreach ($actions as $action) {
                        $category_id = Action::select('category_id')->where('actions.id', '=', $action)->first();
                        if ($category_id['category_id'] != 0) {
                            $check_exist = SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                                ->where('sorting_contract_activities.action_id', '=', $action)
                                ->where('sorting_contract_activities.category_id', '=', $category_id['category_id'])
                                ->where('sorting_contract_activities.is_active', '=', 1)
                                ->count();

                            if ($check_exist == 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            } else {
                                // log::info('No need to save record.');
                            }
                        } else {
                            log::info('Category not found.');
                        }
                    }
                }

                if (count($custom_actions) > 0) {
                    foreach ($custom_actions as $custom_action) {
                        if ($contract) {
                            $category_id = Action::select('category_id')->where('actions.id', '=', $custom_action)->first();
                            if ($category_id['category_id'] != 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $custom_action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }
                }
            } else {
                $actions = Request::input('actions');
                $index = 0;
                if ($actions) {
                    foreach ($actions as $action) {
                        if ($contract) {
                            $category_id = Action::select('category_id')->where('actions.id', '=', $action)->first();
                            if ($category_id['category_id'] != 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }
                }
                if (count($custom_actions) > 0) {
                    foreach ($custom_actions as $custom_action) {
                        if ($contract) {
                            $category_id = Action::select('category_id')->where('actions.id', '=', $custom_action)->first();

                            if ($category_id['category_id'] != 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $custom_action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }
                }
            }

            DB::commit();

            if ($view == 'physician_screen') {
                return Redirect::route('physicians.contracts', [$physician_id, $practice_id])->with([
                    'success' => Lang::get('physicians.create_contract_success')
                ]);
            } else {
                return Redirect::route('agreements.show', [$agreement->id])->with([
                    'success' => Lang::get('physicians.create_contract_success')
                ]);
            }

            // all good
        } catch (\Exception $e) {
            DB::rollback();
            log::info('from createContract', array($e));
            return Redirect::back()->with([
                'error' => Lang::get('physicians.create_contract_error')
            ])->withInput();
        }

    }


    public static function getEdit($id, $practice_id, $physician_id)
    {
        $contract = self::findOrFail($id);
        $physician = Physician::findOrFail($physician_id);

        $users = User::select('users.id', DB::raw('CONCAT(users.first_name, " ", users.last_name) AS name'))
            ->join('hospital_user', 'hospital_user.user_id', '=', 'users.id')
            ->where('hospital_user.hospital_id', '=', $contract->agreement->hospital_id)
            ->where('group_id', '!=', Group::Physicians)
            ->orderBy("name")
            ->pluck('name', 'id');
        if (count($users) == 0) {
            $users[''] = "Select User";
        }
        $users[0] = "NA";
        $display_on_call_rate = 0;
        //if($contract->contract_type_id == ContractType::ON_CALL) {
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            //accept zero rate for per_diem contract by 1254 : checked for  on call process flag=0 to week rate zero
            if ($contract->weekday_rate > 0 || $contract->weekend_rate > 0 || $contract->holiday_rate > 0 || $contract->on_call_process == 0) {
                $display_on_call_rate = 0;
            } else {
                $display_on_call_rate = 1;
            }
        }

        if ($contract->default_to_agreement == '1') {
            $contract_id = 0;//when we are fetching all approval managers same as agreement
        } else {
            $contract_id = $id;// when we are fetching approval managers for the specific contract
        }

        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';

        $ApprovalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $contract->agreement_id)
            ->where('contract_id', "=", $contract_id)
            ->where('is_deleted', '=', '0')
            ->orderBy('level')->get();


        $ContractDocument = ContractDocuments::where('contract_id', "=", $id)
            ->where('is_active', '=', '1')
            ->get();

        if ($contract->deadline_option == '1') {
            $contract_deadlines = ContractDeadlineDays::where('contract_id', "=", $id)
                ->where('is_active', '=', '1')
                ->first();
            if ($contract_deadlines) {
                $contract_deadline_days = $contract_deadlines->contract_deadline_days;
            } else {
                $contract_deadline_days = '';
            }
        } else {
            $contract_deadline_days = '';
        }


        $agreement_details = Agreement::findOrFail($contract->agreement_id);

        /**
         * Below code is added to set the initial review day and final review day on edit contract.
         */
        $range_day = 28;
        $initial_review_day = 10;
        $final_review_day = 28;
        if ($agreement_details->payment_frequency_type == Agreement::MONTHLY) {
            $range_day = 28;
            $initial_review_day = 10;
            $final_review_day = 28;
            $range_limit = 31;      // Dropdown limit for change rate
        } else if ($agreement_details->payment_frequency_type == Agreement::WEEKLY) {
            $range_day = 7;
            $initial_review_day = 2;
            $final_review_day = 6;
            $range_limit = 7;      // Dropdown limit for change rate
        } else if ($agreement_details->payment_frequency_type == Agreement::BI_WEEKLY) {
            $range_day = 14;
            $initial_review_day = 2;
            $final_review_day = 12;
            $range_limit = 14;      // Dropdown limit for change rate
        } else if ($agreement_details->payment_frequency_type == Agreement::QUARTERLY) {
            $range_day = 85;
            $initial_review_day = 10;
            $final_review_day = 20;
            $range_limit = 90;      // Dropdown limit for change rate
        }

        if ($contract->default_to_agreement_dates == 1) {
            $contract->manual_contract_end_date = $agreement_details->end_date;
            $contract->manual_contract_valid_upto = $agreement_details->valid_upto;
        }
        $invoice_notes = InvoiceNote::getInvoiceNotes($id, InvoiceNote::CONTRACT, $contract->agreement->hospital_id, 0);
        $internal_notes = ContractInternalNote::
        where('contract_id', '=', $contract->id)
            ->value('note');

        $end_date = $contract->manual_contract_end_date;
        $dates = ["start" => $agreement_details->payment_frequency_start_date, "end" => $end_date];

        // Below changes are done based on payment frequency of agreement by akash.
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_details->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_details);

        $start_dates = array_column($res_pay_frequency['date_range_with_start_end_date'], 'start_date');
        $end_dates = array_column($res_pay_frequency['date_range_with_start_end_date'], 'end_date');
        $date_arr = array();
        $temp_date_arr = array();
        foreach ($start_dates as $date) {
            $formated_date = date("m/d/Y", strtotime($date));
            $temp_date_arr[$date] = $formated_date;
        }
        $date_arr['start_dates'] = $temp_date_arr;

        //This line is commented for payment frequency by akash
        // $dates_data=SELF::datesOptions( $dates );

        $dates_data = $date_arr; // This dates are calculated based on the payment frequency for the contract.

        //Action-Redesign by 1254 : 15022020

//        $categories = ActionCategories::all();
        $categories = ActionCategories::getCategoriesByPaymentType($contract->payment_type_id);

        //set hours for calulation value in dropdown
        $hours_calculations = range(0, 24);
        unset($hours_calculations[0]);

        $activities_1 = DB::table('actions')->select('actions.*')
            ->join('action_hospitals', 'action_hospitals.action_id', '=', 'actions.id')
            ->whereIn('action_hospitals.hospital_id', [0, $contract->agreement->hospital_id])
            ->where('action_hospitals.is_active', '=', 1)
            ->distinct()
            ->get();

        if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
            $custom_categories_array = [];
            foreach ($categories as $category) {
                $custom_category = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('category_id', '=', $category->id)
                    ->where('category_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $custom_categories_array[] = [
                    "id" => $category->id,
                    "name" => $custom_category ? $custom_category->category_name : $category->name,
                    "created_at" => $category->created_at,
                    "updated_at" => $category->updated_at,
                    "deleted_at" => $category->deleted_at
                ];
            }
            $categories = json_decode(json_encode($custom_categories_array), FALSE);

            $custom_actions_array = [];
            foreach ($activities_1 as $activity_1) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $activity_1->id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $custom_actions_array[] = [
                    "id" => $activity_1->id,
                    "name" => $custom_action ? $custom_action->action_name : $activity_1->name,
                    "action_type_id" => $activity_1->action_type_id,
                    "contract_type_id" => $activity_1->contract_type_id,
                    "payment_type_id" => $activity_1->payment_type_id,
                    "created_at" => $activity_1->created_at,
                    "updated_at" => $activity_1->updated_at,
                    "category_id" => $activity_1->category_id,
                    "hospital_id" => $activity_1->hospital_id,
                    "sort_order" => $activity_1->sort_order
                ];
            }
            $activities_1 = json_decode(json_encode($custom_actions_array), FALSE);
        }

        $actions_contract = DB::table('actions')->select('actions.*')
            ->join('action_contract', 'action_contract.action_id', '=', 'actions.id')
            ->where('action_contract.contract_id', '=', $contract->id)
            ->distinct()
            ->get();

        $on_call_uncompensated_rates = ContractRate::getRate($contract->id, $agreement_details->start_date, ContractRate::ON_CALL_UNCOMPENSATED_RATE);

        // sorting contract activities
        $sorting_activities = SortingContractActivity::getSortingContractActivities($contract->id);

        $data = [
            'contract' => $contract,
            'physician' => $physician,
            'activities' => $contract->activities(),
            'activities_1' => $activities_1,
            'actions_contract' => $actions_contract,
            'duties' => $contract->duties(),
            /*'contract_names' => ContractName::options($contract->contract_type_id),*/
            'contract_names' => ContractName::options($contract->payment_type_id),
            'contract_types' => ContractType::options(),
            'payment_type' => PaymentType::getName($contract->payment_type_id),
            'users' => $users,
            'display_on_call_rate' => $display_on_call_rate,
            'approval_manager_type' => $approval_manager_type,
            'ApprovalManagerInfo' => $ApprovalManagerInfo,
            'contract_document' => $ContractDocument,
            'contract_deadline_days' => $contract_deadline_days,
            'agreement_end_date' => $agreement_details->end_date,
            'agreement_name' => $agreement_details->name,
            'agreement_valid_upto_date' => $agreement_details->valid_upto,
            'agreements' => Hospital::get_active_agreements($agreement_details->hospital_id, $agreement_details->id),
            "note_count" => count($invoice_notes),
            "invoice_notes" => $invoice_notes,
            "contract_psa_wrvu_rates_count" => count(ContractPsaWrvuRates::getRates($contract->id)),
            'internal_notes' => $internal_notes,
            'dates' => $dates_data,
            //Action-Redesign by 1254 : 15022020
            'categories' => $categories,
            'hours_calculations' => $hours_calculations,
            'on_call_uncompensated_rates' => $on_call_uncompensated_rates,
            "on_call_rate_count" => count($on_call_uncompensated_rates),
            'range_day' => $range_day,
            'initial_review_day' => $initial_review_day,
            'final_review_day' => $final_review_day,
            'range_limit' => $range_limit,
            "sorting_contract_activities" => $sorting_activities,
            'categories_count' => count($categories),
            'range_day' => $range_day,
            'initial_review_day' => $initial_review_day,
            'final_review_day' => $final_review_day,
            'range_limit' => $range_limit,
            'supervisionTypes' => options(PhysicianType::all(), 'id', 'type')
        ];
        if ($contract->payment_type_id == PaymentType::PSA) {
            $data['contract_psa_metrics'] = ContractPsaMetrics::getMetrics($contract->id);

            $data['contract_types'] = ContractType::psaOptions();

            if ($contract->wrvu_payments) {
                $data['contract_psa_wrvu_rates'] = ContractPsaWrvuRates::getRates($contract->id);
                $data['contract_psa_wrvu_ranges'] = ContractPsaWrvuRates::getRanges($contract->id);
            } else {
                $data['contract_psa_wrvu_rates'] = ContractPsaWrvuRates::getRates(-1);
                $data['contract_psa_wrvu_ranges'] = ContractPsaWrvuRates::getRanges(-1);
            }

        } else {
            $data['contract_psa_metrics'] = new ContractPsaMetrics();
            $data['contract_psa_wrvu_rates'] = ContractPsaWrvuRates::getRates(-1);
            $data['contract_psa_wrvu_ranges'] = ContractPsaWrvuRates::getRanges(-1);
        }

        //get per unit value
        if ($contract->payment_type_id == PaymentType::PER_UNIT) {
            $unit = Action::select("actions.name as units_name")
                ->join("action_contract", "action_contract.action_id", "=", "actions.id")
                ->where("action_contract.contract_id", "=", $contract->id)
                ->first();

            $data['units'] = $unit["units_name"];
        }

        return $data;
    }

    public static function getMonthyStipendRate($contract_id)
    {
        $rate = DB::table("contract_rate")
            ->select("contract_rate.rate")
            ->whereRaw("contract_rate.contract_id = " . $contract_id)
            ->where("contract_rate.status", "=", "1")
            ->first();
        if ($rate != null) {
            return $rate->rate;
        } else {
            return 0;
        }
    }

    public static function getEditPsa($id)
    {
        $contract = self::findOrFail($id);
        $agreement_details = Agreement::findOrFail($contract->agreement_id);

        $data = [
            'contract' => $contract,
            'physician' => $contract->physician,
            'agreement_name' => $agreement_details->name,
            'payment_type' => PaymentType::getName($contract->payment_type_id),
            'contract_type' => ContractType::getName($contract->contract_type_id),
            'contract_name' => ContractName::getName($contract->payment_type_id),
            'contract_psa_metrics' => ContractPsaMetrics::getMetrics($contract->id)
        ];
        return $data;
    }

    public static function postEdit($id, $pid = 0, $physician_id)
    {
        $contract = self::findOrFail($id);
        $original_agreement_id = $contract->agreement_id;

        $contract_agreement = Agreement::findOrFail($original_agreement_id);
        $selected_agreement = Agreement::findOrFail(Request::input('agreement'));

        if ($contract_agreement->payment_frequency_type != $selected_agreement->payment_frequency_type) {
            return Redirect::route('contracts.edit', [$id, $pid, $physician_id])
                ->with(['error' => Lang::get('agreements.agreement_payment_frequency_error')]);
        }

        if (Request::exists('selectedPhysicianList')) {
            if (count(Request::input('selectedPhysicianList')) == 0) {
                return Redirect::route('contracts.edit', [$id, $pid, $physician_id])
                    ->with(['error' => Lang::get('physicians.empty_physician_create_contract')]);
            } else {
                /*
                * Validations for single practice physicians
                */

                $physician_practice_arr_of_str = Request::input('selectedPhysicianList');
                $check_practice = 0;
                foreach ($physician_practice_arr_of_str as $physician_practice_str) {
                    $physician_practice_arr = explode("_", $physician_practice_str);
                    if ($check_practice != 0 && $check_practice != $physician_practice_arr[1]) {
                        return Redirect::back()->with([
                            'error' => Lang::get('physicians.physician_of_diff_practice_create_contract')
                        ])->withInput();
                    }
                    $check_practice = $physician_practice_arr[1];
                }
            }
        } else {
            return Redirect::route('contracts.edit', [$id, $pid, $physician_id])
                ->with(['error' => Lang::get('physicians.empty_physician_create_contract')]);
        }

        // Fetch invoice type from hospital.
        $practice_details = Practice::findOrFail($pid);
        $result_hospital = Hospital::findOrFail($practice_details->hospital_id);

        $categories = ActionCategories::all();

        //if($contractdata->contract_type_id==ContractType::ON_CALL) {
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $validation = new ContractValidation();
            if (Request::input('on_off') == 1) {
                if (!$validation->validateOnCallData(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else {
                if (!$validation->validateOnCallAgreementsData(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            }
        } else {
            $validation = new ContractValidation();
            //if( $contractdata->contract_type_id==ContractType::MEDICAL_DIRECTORSHIP) {
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                if (!$validation->validateMedicalDirectership(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                if (!$validation->validatePerUnit(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if ($contract->payment_type_id == PaymentType::PSA) {
                if (!$validation->validatePSA(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if (!$validation->validatePerDiemUncompensatedData(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } //Chaitraly::new code for monthly stipend validation
            else if (Request::input('payment_type_id') == PaymentType::MONTHLY_STIPEND) {
                if (!$validation->validateMonthlyStipendEdit(Request::input(), $result_hospital->invoice_type)) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if (Request::input('payment_type_id') == PaymentType::REHAB) {
                if (!$validation->validateEditRehab(Request::input())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            } else if (!$validation->validateEdit(Request::input())) {

                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
            //contract custom action validation by Akash

            $custome_action_error = (array)[];

            foreach ($categories as $category) {
                $category_name = 'customaction_name_' . $category->id;
                $customaction_names = Request::input($category_name);

                if ($customaction_names) {
                    $custome_action_category = (array)[];
                    foreach ($customaction_names as $elem_id => $customaction_name) {
                        $existactionforcategory = Action::select("actions.*")
                            ->where('actions.name', '=', $customaction_name)
                            ->where("actions.category_id", "=", $category->id)
                            ->where(function ($query) use ($practice_details) {
                                $query->where("actions.hospital_id", "=", $practice_details->hospital_id)
                                    ->orWhere("actions.hospital_id", "=", 0);
                            })
                            // ->where("actions.hospital_id", "=", $practice_details->hospital_id)
                            ->get();

                        if (count($existactionforcategory) > 0) {
                            $custome_action_category[$customaction_name] = true;
                        } else {
                            if ($customaction_name != '') {
                                $custome_action_category[$customaction_name] = false;
                            }
                        }

                    }

                    $custome_action_error[$category_name] = $custome_action_category;

                }
            }

            if ($custome_action_error) {
                // Log::Info("fromContract.php", array($custome_action_error));
                // return Redirect::back()->withErrors($custome_action_error);
                foreach ($custome_action_error as $category_name => $action_arr) {
                    if (count($action_arr) > 0) {
                        foreach ($action_arr as $action_name => $flag) {
                            if ($flag == true) {
                                return Redirect::back()->with(['action_error' => $custome_action_error])->withInput();
                            }
                        }
                    }
                }
            }
        }

        $validation = new ContractValidation();
        $emailvalidation = new EmailValidation();
        if (Request::input('contract_type') == 20) {
            if (!$validation->recipientValidate(Request::input())) {
                return Redirect::back()->withErrors($validation->messages())->withInput();
            }
            if (!$emailvalidation->validateEmailDomain(Request::input())) {
                return Redirect::back()->withErrors($emailvalidation->messages())->withInput();
            }

        }

        $max_uploadable_contracts_validate_count = 6;
        for ($i = 1; $i < $max_uploadable_contracts_validate_count + 1; $i++) {
            if (Request::hasFile('upload_contract_copy_' . $i)) {
                if (!$validation->contractCopyValidate(Request::file())) {
                    return Redirect::back()->withErrors($validation->messages())->withInput();
                }
            }
        }

        if (Request::input('payment_type_id') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

            $on_call_rate_count = Request::input("on_call_rate_count");
            $uncompensated_error = (array)[];
            $flag = false;
            $uncompensated_error[0] = " ";
            $on_call_rate = array();


            //validation for partial hour calucation
            if (Request::input("partial_hours") == 1) {

                $hospital_id = Practice::where("id", "=", $pid)->pluck("hospital_id")->toArray();
                $agreement_id = Agreement::where("hospital_id", "=", $hospital_id)->pluck("id")->toArray();

                $partial_hour_calculations = Contract::where("agreement_id", "=", $agreement_id[0])
                    ->whereNull('contracts.deleted_at')
                    ->where('contracts.end_date', '=', "0000-00-00 00:00:00")
                    ->where('contracts.partial_hours', '=', 1)
                    ->where('contracts.payment_type_id', '=', 5)
                    ->pluck("partial_hours_calculation")->toArray();

                if (count($partial_hour_calculations) > 0 && ($partial_hour_calculations[0] != Request::input("hours_calculation"))) {

                    $flag = true;
                    $uncompensated_error[0] = "You can select hour for calcultion only for " . $partial_hour_calculations[0] . "hrs.";

                }
            }
            if (Request::has('change_rate_check')) {
                // $agreement_details = Agreement::findOrFail($contract->agreement_id);
                // $on_call_uncompensated_rates = ContractRate::getRate($contract->id, $agreement_details->start_date,ContractRate::ON_CALL_UNCOMPENSATED_RATE);
                // log::info("on_call_uncompensated_rates",array($on_call_uncompensated_rates));

                $range_start_days = range(0, 31);
                unset($range_start_days[0]);
                $next_start_day = 0;
                for ($i = 1; $i <= $on_call_rate_count; $i++) {
                    $end_day = "end_day" . $i;
                    $start_day = "start_day" . ($i);
                    if ($i == 1) {
                        $start_day_value = "1";
                    } else {
                        $start_day_value = Request::input(($start_day));
                    }

                    $end_day_value = Request::input(($end_day));
                    $rates = "rate" . ($i);
                    $rates_value = Request::input(($rates));
                    $errors = "";

                    if (empty($rates_value))//preg_match("'/^[0-9]+(\.[0-9]{1,2})?$/'", $rates_value) )
                    {
                        $flag = true;
                        $errors = "On Call Rate -" . ($i) . ' ' . "Required.";

                    } else if (!(preg_match('/\d{1,4}\.\d{2}/', $rates_value)))//preg_match("'/^[0-9]+(\.[0-9]{1,2})?$/'", $rates_value) )
                    {
                        $flag = true;
                        $errors = $errors . ' ' . "On Call Rate - " . ($i) . ' ' . "format is invalid.";

                    }
                    if ($end_day_value < $start_day_value) {
                        $flag = true;
                        $errors = $errors . "End day range should be greater than start day range for on call rate -" . ($i) . ".";

                    }
                    if ($next_start_day != 0 && $next_start_day != $start_day_value) {
                        $flag = true;
                        $errors = $errors . "Start day should be just above than previous end day for on call rate -" . ($i) . ".";
                    }
                    $next_start_day = 0;
                    $next_start_day += $end_day_value + 1;
                    $uncompensated_error[$i] = $errors;
                    $on_call_rate[] = ["rate_index" => $i,
                        "rate" => $rates_value,
                        "range_start_day" => $start_day_value,
                        "range_end_day" => $end_day_value,
                        "range_start_days" => $range_start_days];
                }
            }
            if ($flag == true) {
                return Redirect::back()->with(['on_call_rate_error' => $uncompensated_error, 'on_call_uncompensated_rates' => $on_call_rate])->withInput();
            }

        }


        if (Request::input('payment_type_id') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS || Request::input('payment_type_id') == PaymentType::PER_DIEM) {
            $contract->annual_max_payment = Request::input('annual_max_payment');
        }

        if (!Request::has('default_dates') || Request::input('default_dates') != 1) {
            /*
            - Validate if the agreement end date and manual contract end date fall between current day and agreemnet end date
            - added on: 2018/12/26
            */
            $agreementObject = new Agreement;
            //$endDateQuery = $agreementObject->getAndCheckManualEndDate(Request::input('agreement_id'), @mysql_date(Request::input('edit_manual_end_date')), 'edit');
            $endDateQuery = $agreementObject->getAndCheckManualEndDate(Request::input('agreement'), @mysql_date(Request::input('edit_manual_end_date')), 'edit');
            if (!$endDateQuery) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.agreement_date_manual_end_date_error')
                ])->withInput();
            }

            if (strtotime(Request::input('edit_manual_end_date')) < strtotime(date('Y-m-d'))) {
                /* added for check logs after selected end dates*/
                $logs = PhysicianLog::where('contract_id', '=', $id)->where('date', '>', @mysql_date(Request::input('edit_manual_end_date')))->get();
                if (count($logs) > 0) {
                    return Redirect::back()->with([
                        'error' => Lang::get('physicians.manual_end_date_logs_error')
                    ])->withInput();
                }
            }
            $date_after90_days_of_end_date = date('Y-m-d', strtotime('+90 day', strtotime(Request::input('edit_manual_end_date'))));
            if ((strtotime(Request::input('edit_manual_end_date')) > strtotime(Request::input('edit_valid_upto_date'))) || (strtotime(Request::input('edit_valid_upto_date')) > strtotime($date_after90_days_of_end_date))) {
                return Redirect::back()->with([
                    'error' => Lang::get('physicians.valid_upto_date_error')
                ])->withInput();
            }

            /*check valid upto is less than todays date then logs are not pendding for physician approval on 7 nov 2019*/
            if (strtotime(Request::input('edit_valid_upto_date')) < strtotime(date('Y-m-d'))) {
                $checkPenndingLogs = PhysicianLog::penddingPhysicianApprovalForContract($id);
                if ($checkPenndingLogs) {
                    return Redirect::back()->with([
                        'error' => Lang::get('physicians.valid_upto_date_logs_present_error')
                    ])->withInput();
                }
            }
        }
        $result = array();
        $actions = Request::input('actions');
        $customactions = Request::input('name');
        $physician_emailCheck = Request::input('physician_emailCheck');
        $contract->physician_opt_in_email = $physician_emailCheck ? '1' : '0';
        $contract_deadline_option = Request::input('contract_deadline_on_off');
        $contract->deadline_option = $contract_deadline_option ? '1' : '0';
        $contract->custom_action_enabled = Request::has('custom_action_enable') ? '1' : '0';
        // added on 10-17-2019
        $contract->default_to_agreement_dates = Request::has('default_dates') ? '1' : '0';
        if ($contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT || $contract->payment_type_id == PaymentType::REHAB) {
            $contract->custom_action_enabled = 0;
        }
        if ($contract->default_to_agreement_dates == 1) {
            //$agreement_details = Agreement::findOrFail($contract->agreement_id);
            $agreement_details = Agreement::findOrFail(Request::input('agreement'));
            // added on 2018-12-26
            $contract->manual_contract_end_date = $agreement_details->end_date;
            // added on 10-17-2019
            $contract->manual_contract_valid_upto = $agreement_details->valid_upto;
        } else {
            // added on 2018-12-26
            $contract->manual_contract_end_date = @mysql_date(Request::input('edit_manual_end_date'));
            // added on 10-17-2019
            $contract->manual_contract_valid_upto = @mysql_date(Request::input('edit_valid_upto_date'));
        }
        $burden_of_call = Request::input('burden_on_off');
        $contract->burden_of_call = '0';

        $holiday_on_off = Request::input('holiday_on_off');
        $contract->holiday_on_off = 0;

        $partial_hours = Request::input('partial_hours');
        //  contract with partial hours off to on changes
        $contract_partial_hour = $contract->partial_hours;

        if ($partial_hours == 1) {
            $contract->partial_hours = 1;
            if (Request::input('payment_type_id') == PaymentType::PER_DIEM) {
                $contract->partial_hours_calculation = 24;
            }
        } else {
            $contract->partial_hours = 0;
        }
        $hours = floatval(Request::input("hours"));
        $custom_actions = [];
        //if( $contract->contract_type_id==ContractType::ON_CALL) {
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {

            //call-coverage by 1254 : partial_hours
            //convert and save duration to hrs when partial_hours is true
            //when partial hours is false store duration as it is in log hours

            $physician_logs = PhysicianLog::where("contract_id", "=", $id)->get();
            foreach ($physician_logs as $log) {
                //  contract with partial hours off to on changes
                if (($contract_partial_hour == 0) && ($partial_hours == 1)) {

                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract->partial_hours_calculation = Request::input('hours_calculation');
                    } else {
                        $contract->partial_hours_calculation = 24;
                    }

                    if ($log->duration == 1) {
                        $log->duration = $contract->partial_hours_calculation;

                    } else if ($log->duration == 0.5) {
                        $log->duration = 12;
                    }

                }
                if ($partial_hours == 1) {
                    $log->log_hours = (1.00 / $contract->partial_hours_calculation) * $log->duration;
                } else {
                    $duration = $log->duration;
                    $log->log_hours = $duration;
                }
                $log->save();
                DB::table('physician_log_history')->where('physician_log_id', $log->id)->update(
                    ['log_hours' => $log->log_hours, 'duration' => $log->duration]);
                //end contract partial hour off to on
            }

            if (Request::input('display_on_call_rate') == 1) {
                $contract->burden_of_call = $burden_of_call ? '1' : '0';
            }

            if (Request::input('display_on_call_rate') == 0) {
                $contract->holiday_on_off = $holiday_on_off ? 1 : 0;    // physicians log the hours for holiday activity on any day
            }
            //change rate: added details in contract rate table by  1254
            if (Request::has('change_rate_check')) {

                $effective_start_date = @mysql_date(Request::input('change_rate_start_date'));
                if (Request::input('display_on_call_rate') == 1) {
                    $contract->weekday_rate = 0.00;
                    $contract->weekend_rate = 0.00;
                    $contract->holiday_rate = 0.00;

                    $contract->on_call_rate = floatval(Request::input('On_Call_rate'));
                    $contract->called_back_rate = floatval(Request::input('called_back_rate'));
                    $contract->called_in_rate = floatval(Request::input('called_in_rate'));

                } else {
                    $contract->weekday_rate = floatval(Request::input('weekday_rate'));
                    $contract->weekend_rate = floatval(Request::input('weekend_rate'));
                    $contract->holiday_rate = floatval(Request::input('holiday_rate'));

                    $contract->on_call_rate = 0.00;
                    $contract->called_back_rate = 0.00;
                    $contract->called_in_rate = 0.00;
                }
                $updateContractWeekDayRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->weekday_rate, ContractRate::WEEKDAY_RATE);
                $updateContractWeekDayRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->weekend_rate, ContractRate::WEEKEND_RATE);
                $updateContractWeekDayRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->holiday_rate, ContractRate::HOLIDAY_RATE);
                $updateContractOnCallRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->on_call_rate, ContractRate::ON_CALL_RATE);
                $updateContractCalledBAcklRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->called_back_rate, ContractRate::CALLED_BACK_RATE);
                $updateContractCalledInRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->called_in_rate, ContractRate::CALLED_IN_RATE);
            }

            $hours = 0.00;
            // actions for on call have fixed value that won't change
            // change action for on call on client demand

            if ($actions != null) {
                foreach ($contract->actions as $action) {
                    if (!in_array($action->id, $actions)) {
                        $contract->actions()->detach($action->id);
                    }
                }

                foreach ($actions as $action_id) {
                    $action = $contract->actions()->where('action_id', '=', $action_id)->first();
                    $hours = floatval(Request::input("action-{$action_id}-value"));

                    if ($action) {
                        /* $action->pivot->where('contract_id', '=', $contract->id)
                             ->where('action_id', '=', $action_id)
                             ->update(array('hours' => $hours));*/
                    } else {
                        $contract->actions()->attach($action_id, ['hours' => $hours]);
                    }
                    // added for save new names for on call activities if reocord is new -insert and if record is already exist -update
                    $oncallactivities = DB::table('on_call_activities')
                        ->select('name')
                        ->where('action_id', '=', $action_id)
                        ->where('contract_id', '=', $id)
                        ->first();
                    $name = Request::input('name' . $action_id);
                    if (!$oncallactivities && $name != null && $name != '') {
                        $data = array('name' => $name, 'action_id' => $action_id, 'contract_id' => $id);
                        DB::table('on_call_activities')->insert($data);
                    } elseif ($name != null && $name != '') {
                        DB::table('on_call_activities')->where('action_id', '=', $action_id)
                            ->where('contract_id', '=', $id)
                            ->update(array('name' => $name));
                    } else {
                        $changeName = OnCallActivity::where("contract_id", "=", $id)->where("action_id", "=", $action_id)->first();
                        if ($changeName) {
                            $changeName->delete();
                        }
                    }
                }
            } else {
                foreach ($contract->actions as $action) {
                    $contract->actions()->detach($action->id);
                }
            }
        } else if ($contract->payment_type_id == PaymentType::PSA) {
            $contract_psa_metrics_old = ContractPsaMetrics::where('contract_id', '=', $contract->id)
                ->where('end_date', '=', '2037-12-31')
                ->first();
            $contract_psa_metrics_old->end_date = DB::raw('CURRENT_TIMESTAMP');
            $contract_psa_metrics_old->updated_by = Auth::user()->id;

            $contract_psa_metrics = new ContractPsaMetrics();
            $contract_psa_metrics->contract_id = $contract->id;
            $contract_psa_metrics->annual_comp = floatval(Request::input('annual_comp'));
            $contract_psa_metrics->annual_comp_fifty = floatval(Request::input('annual_comp_fifty'));
            $contract_psa_metrics->wrvu_fifty = Request::input('wrvu_fifty');
            $contract_psa_metrics->annual_comp_seventy_five = floatval(Request::input('annual_comp_seventy_five'));
            $contract_psa_metrics->wrvu_seventy_five = Request::input('wrvu_seventy_five');
            $contract_psa_metrics->annual_comp_ninety = floatval(Request::input('annual_comp_ninety'));
            $contract_psa_metrics->wrvu_ninety = Request::input('wrvu_ninety');
            $contract_psa_metrics->created_by = Auth::user()->id;
            $contract_psa_metrics->updated_by = Auth::user()->id;
            if (!ContractPsaMetrics::compareObjects($contract_psa_metrics_old, $contract_psa_metrics)) {
                $contract_psa_metrics_old->save();
                $contract_psa_metrics->save();
            }
            $contract->enter_by_day = Request::input('enter_by_day');
            $contract->wrvu_payments = Request::input('wrvu_payments');

            if (!Request::input('wrvu_payments')) {
                if (count(ContractPsaWrvuRates::getRates($contract->id)) > 0) {
                    $contractPsaWrvuRates = ContractPsaWrvuRates::where('contract_id', '=', $contract->id)
                        ->where('is_active', '=', true)
                        ->get();
                    foreach ($contractPsaWrvuRates as $contractPsaWrvuRateId) {
                        $contractPsaWrvuRateId->is_active = false;
                        $contractPsaWrvuRateId->save();
                    }
                }
            } else {
                /*update PSA wRVU rates*/
                $index = 1;
                if (Request::input('contract_psa_wrvu_rates_count') > 0) {
                    for ($i = 1; $i <= Request::input('contract_psa_wrvu_rates_count'); $i++) {
                        if (Request::input("contract_psa_wrvu_ranges" . $i) != '' && "contract_psa_wrvu_rates" . $i != '') {
                            $contract_psa_wrvu_rate_old = ContractPsaWrvuRates::where("contract_id", '=', $contract->id)
                                ->where("rate_index", '=', $index)
                                ->where("is_active", '=', true)
                                ->update(["rate" => Request::input("contract_psa_wrvu_rates" . $i)]);
                            $contract_psa_wrvu_range_old = ContractPsaWrvuRates::where("contract_id", '=', $contract->id)
                                ->where("rate_index", '=', $index)
                                ->where("is_active", '=', true)
                                ->update(["upper_bound" => Request::input("contract_psa_wrvu_ranges" . $i)]);
                            if (!$contract_psa_wrvu_rate_old) {
                                $contractPsaWrvuRate = new ContractPsaWrvuRates();
                                $contractPsaWrvuRate->contract_id = $contract->id;
                                $contractPsaWrvuRate->upper_bound = Request::input("contract_psa_wrvu_ranges" . $i);
                                $contractPsaWrvuRate->rate = Request::input("contract_psa_wrvu_rates" . $i);
                                $contractPsaWrvuRate->rate_index = $index;
                                $contractPsaWrvuRate->created_by = Auth::user()->id;
                                $contractPsaWrvuRate->updated_by = Auth::user()->id;
                                $contractPsaWrvuRate->save();
                            }
                            $index++;
                        }
                    }
                }
                ContractPsaWrvuRates::where("contract_id", '=', $contract->id)
                    ->where("rate_index", '>=', $index)
                    ->where("is_active", '=', true)
                    ->update(["is_active" => false]);
            }

            // change action values
            if ($actions != null) {
                foreach ($contract->actions as $action) {
                    if (!in_array($action->id, $actions)) {
                        $contract->actions()->detach($action->id);
                    }
                }

                foreach ($actions as $action_id) {
                    $action = $contract->actions()->where('action_id', '=', $action_id)->first();
                    $hours = floatval(Request::input("action-{$action_id}-value"));

                    if ($action) {
                        $action->pivot->where('contract_id', '=', $contract->id)
                            ->where('action_id', '=', $action_id)
                            ->update(array('hours' => $hours));
                    } else {
                        $contract->actions()->attach($action_id, ['hours' => $hours]);
                    }
                }
            } elseif (count($contract->actions) > 0) {
                foreach ($contract->actions as $action) {
                    $contract->actions()->detach($action->id);
                }
            }
        } else if (Request::input('payment_type_id') == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {

            $physician_logs = PhysicianLog::where("contract_id", "=", $id)->get();
            foreach ($physician_logs as $log) {
                //  contract with partial hours off to on changes
                if (($contract_partial_hour == 0) && ($partial_hours == 1)) {

                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract->partial_hours_calculation = Request::input('hours_calculation');
                    } else {
                        $contract->partial_hours_calculation = 24;
                    }

                    if ($log->duration == 1) {
                        $log->duration = $contract->partial_hours_calculation;

                    } else if ($log->duration == 0.5) {
                        $log->duration = 12;
                    }

                }
                if ($partial_hours == 1) {
                    $log->log_hours = (1.00 / $contract->partial_hours_calculation) * $log->duration;
                } else {
                    $duration = $log->duration;
                    $log->log_hours = $duration;
                }
                $log->save();
                DB::table('physician_log_history')->where('physician_log_id', $log->id)->update(
                    ['log_hours' => $log->log_hours, 'duration' => $log->duration]);
                //end contract partial hour off to on
            }

            if (Request::input('partial_hours') == 1) {
                $partial_hours_calculation = Request::input('hours_calculation');
                $contract->partial_hours_calculation = $partial_hours_calculation;
            }
            if (Request::has('change_rate_check')) {
                $on_call_rate_count = Request::input("on_call_rate_count");
                $effective_start_date = @mysql_date(Request::input('change_rate_start_date'));
                //make status =>0 current rates and add new rates with status 0

                // $contract_rate_data = DB::table('contract_rate')
                // ->where("effective_start_date","=", @mysql_date($effective_start_date))
                // ->where("contract_id", "=", $contract->id)
                // ->where("rate_type", "=", 8)
                // // ->where("rate_index","=",$rateindex)
                // ->where("status","=",'1')
                // ->get();

                // if(count($contract_rate_data) > 0){
                //     DB::table('contract_rate')
                //     ->where("effective_start_date","<=",@mysql_date($effective_start_date))
                //     ->where("effective_end_date","<=", @mysql_date($contract->manual_contract_end_date))
                //     ->where("contract_id","=",$contract->id)
                //     ->where("rate_type","=",8)
                //     ->where("status","=",'1')
                //     ->update(["status"=>'0']);
                // }

                //add all rate with status 1 into contractrate
                for ($i = 1; $i <= $on_call_rate_count; $i++) {
                    $end_day = "end_day" . $i;
                    $start_day = "start_day" . ($i);
                    $oncalluncompensated_rate = "rate" . ($i);

                    if ($i == 1) {
                        $start_day_value = 1;
                    } else {
                        $start_day_value = Request::input(($start_day));
                    }
                    $end_day_value = Request::input(($end_day));
                    $rate = floatval(Request::input($oncalluncompensated_rate));
                    if ($start_day_value != null) {
                        $update_exist_data = ContractRate::where('contract_id', $contract->id)->where('rate_index', $i)->update(array('status' => '0'));
                        $contractOnCallUncompensatedRate = ContractRate::insertContractRate($contract->id, $effective_start_date, $contract->manual_contract_end_date, $rate, ContractRate::ON_CALL_UNCOMPENSATED_RATE, $i, $start_day_value, $end_day_value);
                    }
                }
            }

            if ($actions != null) {
                foreach ($contract->actions as $action) {
                    if (!in_array($action->id, $actions)) {
                        $contract->actions()->detach($action->id);
                    }
                }

                foreach ($actions as $action_id) {
                    $action = $contract->actions()->where('action_id', '=', $action_id)->first();
                    $hours = floatval(Request::input("action-{$action_id}-value"));

                    if ($action) {

                    } else {
                        $contract->actions()->attach($action_id, ['hours' => $hours]);
                    }
                    // added for save new names for on call activities if reocord is new -insert and if record is already exist -update
                    $oncallactivities = DB::table('on_call_activities')
                        ->select('name')
                        ->where('action_id', '=', $action_id)
                        ->where('contract_id', '=', $id)
                        ->first();

                    $name = Request::input('name' . $action_id);
                    if (!$oncallactivities && $name != null && $name != '') {
                        $data = array('name' => $name, 'action_id' => $action_id, 'contract_id' => $id);
                        DB::table('on_call_activities')->insert($data);
                    } elseif ($name != null && $name != '') {
                        DB::table('on_call_activities')->where('action_id', '=', $action_id)
                            ->where('contract_id', '=', $id)
                            ->update(array('name' => $name));
                    } else {
                        $changeName = OnCallActivity::where("contract_id", "=", $id)->where("action_id", "=", $action_id)->first();
                        if ($changeName) {
                            $changeName->delete();
                        }
                    }
                }
            } else {
                foreach ($contract->actions as $action) {
                    $contract->actions()->detach($action->id);
                }
            }


        } else {
            $contract->min_hours = floatval(Request::input('min_hours'));
            $contract->max_hours = floatval(Request::input('max_hours'));
            //$contract->rate = floatval(Request::input('rate'));
            if (Request::has('change_rate_check')) {    //Chaitraly::Update Contract for monthly stipend rate
                if ($contract->payment_type_id == PaymentType::MONTHLY_STIPEND) {

                    $effective_start_date = @mysql_date(Request::input('change_rate_start_date'));
                    $contract->rate = floatval(Request::input('rate'));
                    $updateContractWeekDayRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->rate, ContractRate::MONTHLY_STIPEND_RATE);
                } else { //Chaitraly::Code for Stipend is put in else block
                    $effective_start_date = @mysql_date(Request::input('change_rate_start_date'));
                    $contract->rate = floatval(Request::input('rate'));
                    $updateContractWeekDayRate = ContractRate::updateContractRate($contract->id, $effective_start_date, $contract->rate, ContractRate::FMV_RATE);
                }
            }

            //if( $contract->contract_type_id==ContractType::MEDICAL_DIRECTORSHIP) {
            if ($contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::PER_UNIT) {
                $contract->annual_cap = floatval(Request::input('annual_cap'));
                $contract->prior_worked_hours = floatval(Request::input('prior_worked_hours'));
                $contract->prior_amount_paid = floatval(Request::input('prior_amount_paid'));
                $contract->allow_max_hours = floatval(Request::input('log_over_max_hour'));
                if (Request::input('contract_prior_start_date_on_off') == 1) {
                    $contract->prior_start_date = @mysql_date(Request::input('prior_start_date'));
                } else {
                    $contract->prior_start_date = '0000-00-00';
                }
                //$contract->prior_start_date = @mysql_date(Request::input('prior_start_date'));
            }


            $actions = Request::input('actions');
            $hours = floatval(Request::input("hours"));

            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                $units = Request::input('units');
                if ($units) {
                    // update action
                    DB::table('actions')
                        ->join('action_contract', 'action_contract.action_id', '=', 'actions.id')
                        ->where('action_contract.contract_id', '=', $contract->id)
                        ->where('actions.payment_type_id', '=', $contract->payment_type_id)
                        ->update(array("actions.name" => $units));
                }
            } else {
                // change action values
                if ($actions != null) {

                    foreach ($contract->actions as $action) {
                        if (!in_array($action->id, $actions)) {
                            $contract->actions()->detach($action->id);
                        }
                    }

                    foreach ($actions as $action_id) {
                        $action = $contract->actions()->where('action_id', '=', $action_id)->first();
                        // $hours = floatval(Request::input("action-{$action_id}-value"));
                        $hours = floatval(Request::input("hours"));

                        if ($action) {
                            $action->pivot->where('contract_id', '=', $contract->id)
                                ->where('action_id', '=', $action_id)
                                ->update(array('hours' => $hours));
                        } else {
                            $contract->actions()->attach($action_id, ['hours' => $hours]);
                        }

                    }
                } elseif (count($contract->actions) > 0) {
                    foreach ($contract->actions as $action) {
                        $contract->actions()->detach($action->id);
                    }
                }

            }
            //add custom action vy  1254
            //Action-Redesign by 1254
            $hospital_id = DB::table("contracts")
                ->select("agreements.hospital_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->where("agreements.id", "=", $contract->agreement_id)->first();

            foreach ($categories as $category) {
                $customaction_names = Request::input('customaction_name_' . $category->id);

                if ($customaction_names) {
                    foreach ($customaction_names as $customaction_name) {
                        $hours = floatval(Request::input("hours"));

                        // $existactionforcategory = Action::select("actions.*")
                        // ->where('actions.name','=',  $customaction_name)
                        // ->where("actions.category_id","=",$category->id)
                        //  ->get();

                        // if(count($existactionforcategory)==0)
                        // {
                        if ($customaction_name) {

                            //save into action table
                            $action = new Action();
                            $action->name = $customaction_name;
                            $action->category_id = $category->id;
                            $action->contract_type_id = $contract->contract_type_id;
                            $action->hospital_id = $hospital_id->hospital_id;
                            $action->save();

                            if (!in_array($action->id, $custom_actions)) {
                                $custom_actions[] = $action->id;
                            }
                            //save into action_contract table

                            $action_contract = new ActionContract();
                            $action_contract->contract_id = $contract->id;
                            $action_contract->action_id = $action->id;

                            if ($hours > 0.0) {
                                $action_contract->hours = $hours;
                            }
                            $action_contract->save();

                            //save into action_hospitals table

                            $action_hospital = new ActionHospital();
                            $action_hospital->hospital_id = $hospital_id->hospital_id;
                            $action_hospital->action_id = $action->id;
                            $action_hospital->save();

                        }
                        // }else
                        // {
                        //     return Redirect::back()
                        //     ->with(['error' => Lang::get('contracts.contract_action_edit_error')])
                        //     ->withInput();
                        // }
                    }
                }

            }

        }

        //$contract->expected_hours = $contract->actions()->sum('hours');
        $contract->expected_hours = $hours;

        if (Request::input('contract_name') > 0) {
            $contract->contract_name_id = Request::input('contract_name');
        } else {
            $contract->contract_name_id = null;
        }

        if (Request::input('contract_type') > 0) {
            $contract->contract_type_id = Request::input('contract_type');
        } else {
            $contract->contract_type_id = null;
        }

        if (Request::input('agreement') > 0) {
            if (Request::input('agreement') != $original_agreement_id) {
                $checkPenndingLogs = PhysicianLog::penddingApprovalForContract($id);
                if ($checkPenndingLogs) {
                    return Redirect::back()->with([
                        'error' => Lang::get('contracts.change_agreement_logs_present_error')
                    ])->withInput(Request::except('agreement'));
                }
                $contract->agreement_id = Request::input('agreement');
            }
        }

        $contract->mandate_details = Request::input('mandate_details');
        if (Request::input('approval_process') == 1 && !Request::has('default')) {
            $contract->default_to_agreement = '0';
        } else {
            self::update_next_approver($contract, 1, 0, 0);
            $contract->default_to_agreement = '1';
        }
        /*if(Request::input('approval_process') == 1 && !Request::has('default')){
            $contract->contract_CM = Request::input('contract_manager');
            $contract->contract_FM = Request::input('financial_manager');
        }else{
            $contract->contract_CM = 0;
            $contract->contract_FM = 0;
        }*/
        $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($id);
        if ($contract->deadline_option == '1') {
            $contract_deadline_days = new ContractDeadlineDays();
            //Log::info('Contract Deadline days',array($contract_deadline_days_number[0]));
            //Log::info('Contract Deadline days',array($contract_deadline_days_number->contract_deadline_days));
            if ($contract_deadline_days_number) {
                if ($contract_deadline_days_number->contract_deadline_days != Request::input('deadline_days')) {
                    DB::table('contract_deadline_days')
                        ->where('contract_id', '=', $id)
                        ->where('is_active', '=', '1')
                        ->update(array('is_active' => '0'));
                    //Log::info('Contract Deadline days changed, should save now');

                    if (Request::input('deadline_days') != '') {
                        $contract_deadline_days->contract_id = $id;
                        $contract_deadline_days->contract_deadline_days = Request::input('deadline_days');
                        $contract_deadline_days->is_active = '1';
                        $contract_deadline_days->save();
                    } else {
                        $contract->deadline_option = '0';
                    }
                }
            } elseif (Request::input('deadline_days') != '') {
                $contract_deadline_days->contract_id = $id;
                $contract_deadline_days->contract_deadline_days = Request::input('deadline_days');
                $contract_deadline_days->is_active = '1';
                $contract_deadline_days->save();
            } else {
                $contract->deadline_option = '0';
            }
        } else {
            if ($contract_deadline_days_number) {
                DB::table('contract_deadline_days')
                    ->where('contract_id', '=', $id)
                    ->where('is_active', '=', '1')
                    ->update(array('is_active' => '0'));
                //Log::info('Contract Deadline days changed, should save now');
            }
        }
        $result['physician_id'] = $physician_id;

        // Sprint 6.1.17 Start
        if (Request::input('payment_type_id') == PaymentType::PER_DIEM) {
            $contract->annual_cap = floatval(Request::input('annual_max_shifts'));
        }
        // Sprint 6.1.17 End

        if ($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) {
            $contract->quarterly_max_hours = Request::input('quarterly_max_hours', 0);
        }

        // 6.1.1.8 Attestation
        if (Request::input('contract_type') == 20) {
            $contract->state_attestations_monthly = Request::input('state_attestations_monthly', 0);
            $contract->state_attestations_annually = Request::input('state_attestations_annually', 0);
            $contract->receipient1 = Request::input('receipient1');
            $contract->receipient2 = Request::input('receipient2');
            $contract->receipient3 = Request::input('receipient3');
            $contract->supervision_type = Request::input('supervision_type');
        } else {
            $contract->state_attestations_monthly = 0;
            $contract->state_attestations_annually = 0;
            $contract->receipient1 = "";
            $contract->receipient2 = "";
            $contract->receipient3 = "";
            $contract->supervision_type = 0;
        }

        if (!$contract->save()) {
            return Redirect::back()
                ->with(['error' => Lang::get('contracts.edit_error')])
                ->withInput();
        } else {
            $internal_note = ContractInternalNote::
            where('contract_id', '=', $contract->id)
                ->first();
            if ($internal_note != null) {
                $internal_note->note = Request::input('internal_notes');
                $internal_note->save();
            } else {
                $internal_note_insert = new ContractInternalNote;
                $internal_note_insert->contract_id = $contract->id;
                $internal_note_insert->note = Request::input('internal_notes');
                $internal_note_insert->save();
            }


            $max_uploadable_contracts = 6;
            for ($i = 1; $i < $max_uploadable_contracts + 1; $i++) {
                if (Request::hasFile('upload_contract_copy_' . $i)) {
                    //  log::info('in if');
                    // File was successfully uploaded and is now in temp storage.
                    // Move it somewhere more permanent to access it later.
                    $file = Request::file('upload_contract_copy_' . $i);
                    $file_name = $file->getClientOriginalName();
                    $extension = Request::file('upload_contract_copy_' . $i)->getClientOriginalExtension();
                    $current_time = time();//current time
                    $filename = $contract->id . '_' . $i . '_' . $current_time . '.' . $extension;

                    if (Request::file('upload_contract_copy_' . $i)->move(storage_path() . '/contract_copies/', $filename)) {
                        // File successfully saved to permanent storage
                        //log::info('success to upload');

                        DB::table('contract_documents')
                            ->where('contract_id', '=', $id)
                            ->where('is_active', '=', '1')
                            ->where('filename', 'like', '%\_' . $i . '%\_' . '%.pdf') //do some pattern matching to replace only the relevant files.
                            ->update(array('is_active' => '0'));

                        //catch the old contract format
                        if ($i == 1) {
                            DB::table('contract_documents')
                                ->where('contract_id', '=', $id)
                                ->where('is_active', '=', '1')
                                ->where('filename', 'not like', '%\_2%\_' . '%.pdf') //do some pattern matching to replace only the relevant files.
                                ->where('filename', 'not like', '%\_3%\_' . '%.pdf') //do some pattern matching to replace only the relevant files.
                                ->where('filename', 'not like', '%\_4%\_' . '%.pdf') //do some pattern matching to replace only the relevant files.
                                ->where('filename', 'not like', '%\_5%\_' . '%.pdf') //do some pattern matching to replace only the relevant files.
                                ->where('filename', 'not like', '%\_6%\_' . '%.pdf') //do some pattern matching to replace only the relevant files.
                                ->update(array('is_active' => '0'));
                        }

                        $contract_document = new ContractDocuments();
                        $contract_document->contract_id = $id;
                        $contract_document->filename = $filename;
                        $contract_document->is_active = '1';
                        $contract_document->save();

                    } else {
                        // Failed to save file, perhaps dir isn't writable. Give the user an error.
                        return Redirect::back()
                            ->with(['error' => Lang::get('contracts.edit_error')])
                            ->withInput();
                    }
                }
            }


            // check approval process is 'ON' & contract approval managers are not same as agreement
            if (Request::input('approval_process') == 1 && !Request::has('default')) {
                //Write code for approval manager conditions & to save them in database
                $agreement_id = $contract->agreement_id;//fetch agreement id
                $contract_id = $contract->id;// contract id
                $approval_manager_info = array();
                $levelcount = 0;
                $approval_level = array();
                $emailCheck = Request::input('emailCheck');
                //Fetch all levels of approval managers & remove NA approvaal levels
                for ($i = 1; $i < 7; $i++) {
//                    if(Request::input('approverTypeforLevel'.$i)!=0)
                    if (Request::input('approval_manager_level' . $i) != 0) {

//                        $approval_level[$levelcount]['approvalType']=Request::input('approverTypeforLevel'.$i);
                        $approval_level[$levelcount]['approvalType'] = 0;
                        $approval_level[$levelcount]['level'] = $levelcount + 1;
                        $approval_level[$levelcount]['approvalManager'] = Request::input('approval_manager_level' . $i);
                        $approval_level[$levelcount]['initialReviewDay'] = Request::input('initial_review_day_level' . $i);
                        $approval_level[$levelcount]['finalReviewDay'] = Request::input('final_review_day_level' . $i);
                        // $approval_level[$levelcount]['emailCheck']= count($emailCheck)>0 ? in_array("level".$i, $emailCheck) ? 1 : 0 : 0;
                        $approval_level[$levelcount]['emailCheck'] = $emailCheck > 0 ? in_array("level" . $i, $emailCheck) ? '1' : '0' : '0';
                        $levelcount++;
                    }
                }

                // asort($approval_level);//Sorting on basis of type of approval level
                $approval_level_number = 1;
                $fail_to_save_level = 0;

                ApprovalManagerInfo::where('level', ">", $levelcount)->where('agreement_id', '=', $agreement_id)->where('contract_id', '=', $contract_id)->where('is_deleted', '=', '0')->update(array('is_deleted' => '1'));
                foreach ($approval_level as $key => $approval_level) {
                    /*Query for level, fetch type & manager, if type & levels are matching update all other info,
                    if not matching, update flag is_deleted =1 & insert new row for info  */
                    $contract_approval_manager_data = DB::table('agreement_approval_managers_info')->where('agreement_id', "=", $agreement_id)
                        ->where('level', "=", $approval_level['level'])
                        ->where('is_deleted', '=', '0')
                        ->where('contract_id', '=', $contract_id)
                        ->first();

                    // if(count($contract_approval_manager_data)>0 && $contract_approval_manager_data->type_id==$approval_level['approvalType'])
                    if ($contract_approval_manager_data != null && $contract_approval_manager_data->type_id == $approval_level['approvalType']) {
                        $contract_approval_manager_info = ApprovalManagerInfo::findOrFail($contract_approval_manager_data->id);
                        $contract_approval_manager_info->agreement_id = $agreement_id;
                        $contract_approval_manager_info->contract_id = $contract_id;
                        $contract_approval_manager_info->level = $approval_level_number;
                        $contract_approval_manager_info->type_id = $approval_level['approvalType'];
                        $contract_approval_manager_info->user_id = $approval_level['approvalManager'];

                        $contract_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                        $contract_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                        $contract_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                        $contract_approval_manager_info->is_deleted = '0';

                        if (!$contract_approval_manager_info->save()) {
                            $fail_to_save_level = 1;
                        } else {
//                            log::info('$contract_approval_manager_data', array($contract_approval_manager_data));
//                            log::info('approvalManager', array($approval_level['approvalManager']));
                            if ($contract_approval_manager_data->user_id != $approval_level['approvalManager']) {
                                self::update_next_approver($contract, $contract->default_to_agreement, $contract_approval_manager_data->level, $approval_level['approvalManager']);
                            }
                            //success
                            $approval_level_number++;
                        }
                    } else {
                        DB::table('agreement_approval_managers_info')
//                            ->where('level','=',$approval_level_number)
                            ->where('level', '=', $approval_level['level'])
                            ->where('agreement_id', '=', $agreement_id)
                            ->where('contract_id', '=', $contract_id)
                            ->where('is_deleted', '=', '0')
                            ->update(array('is_deleted' => '1'));
                        $contract_approval_manager_info = new ApprovalManagerInfo;
                        $contract_approval_manager_info->agreement_id = $agreement_id;
//                        $contract_approval_manager_info->level=$approval_level_number;
                        $contract_approval_manager_info->level = $approval_level['level'];
                        $contract_approval_manager_info->type_id = $approval_level['approvalType'];

                        $contract_approval_manager_info->user_id = $approval_level['approvalManager'];
                        $contract_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                        $contract_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                        $contract_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                        $contract_approval_manager_info->is_deleted = '0';

                        $contract_approval_manager_info->contract_id = $contract_id;
                        if (!$contract_approval_manager_info->save()) {
                            $fail_to_save_level = 1;
                        } else {
                            /*if(count($contract_approval_manager_data)>0) {
                                if ($contract_approval_manager_data->user_id != $approval_level['approvalManager']) {
                                    self::update_next_approver($contract, $contract->default_to_agreement, $contract_approval_manager_data->level, $approval_level['approvalManager']);
                                }
                            }*/
//                            self::update_next_approver($contract, $contract->default_to_agreement, $approval_level_number, $approval_level['approvalManager']);
                            self::update_next_approver($contract, $contract->default_to_agreement, $approval_level['level'], $approval_level['approvalManager']);
                            //success
                            $approval_level_number++;
                        }
                    }
                }//End of for loop
                if ($fail_to_save_level == 1) {
                    return Redirect::back()
                        ->with(['error' => Lang::get('contracts.edit_error')])
                        ->withInput();
                }
//                else
//                {
//                    return[
//                        'success' => true,
//                        'physician_id' => $contract->physician_id
//                    ];
//                }
            }

            /*update Contract notes*/
            $index = 1;
            if (Request::input('note_count') > 0) {
                for ($i = 1; $i <= Request::input('note_count'); $i++) {
                    if (Request::input("note" . $i) != '') {
                        $invoice_note_old = InvoiceNote::where("note_type", '=', InvoiceNote::CONTRACT)
                            ->where("note_for", '=', $contract->id)
                            ->where("note_index", '=', $index)
                            ->where("hospital_id", '=', $result_hospital->id)
                            ->where("is_active", '=', true)
                            ->update(["note" => Request::input("note" . $i)]);
                        if (!$invoice_note_old) {
                            $invoice_note = new InvoiceNote();
                            $invoice_note->note_type = InvoiceNote::CONTRACT;
                            $invoice_note->note_for = $contract->id;
                            $invoice_note->note_index = $index;
                            $invoice_note->note = Request::input("note" . $i);
                            $invoice_note->hospital_id = $result_hospital->id;
                            $invoice_note->is_active = true;
                            $invoice_note->save();
                        }
                        $index++;
                    }
                }
            }
            InvoiceNote::where("note_type", '=', InvoiceNote::CONTRACT)
                ->where("note_for", '=', $contract->id)
                ->where("note_index", '>=', $index)
                ->where("hospital_id", '>=', $result_hospital->id)
                ->where("is_active", '=', true)
                ->update(["is_active" => false]);

//physician to multiple hospital by 1254
//              $pid = Contract::select("practice_id")
//                     ->where('id','=',$id)
//                     ->where('physician_id','=',$contract->physician_id)
//                     ->first();
//                     $practice = Practice::findOrFail($pid->practice_id);
//                    $data['practice']=$practice;

            // 6.1.9 contract duties sorting management
            $sorting_activities = json_decode(Request::input('sorting_contract_data'));
            if ($sorting_activities > 0) {
                $actions = Request::input('actions');
                $index = 0;
                if ($actions) {
                    SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                        // ->whereIn('sorting_contract_activities.action_id', $actions)
                        ->where('sorting_contract_activities.is_active', '=', 1)
                        ->update(['is_active' => 0]);

                    foreach ($sorting_activities as $sorting_activity) {
                        if ($contract) {
                            if (in_array($sorting_activity->action_id, $actions)) {
                                $index++;
                                $sorting_contract_activity = new SortingContractActivity();
                                $sorting_contract_activity->contract_id = $contract->id;
                                $sorting_contract_activity->category_id = $sorting_activity->category_id;
                                $sorting_contract_activity->action_id = $sorting_activity->action_id;
                                $sorting_contract_activity->sort_order = $index;
                                $sorting_contract_activity->save();
                            } else {
                                // log::info('Action not found in request.');
                            }
                        } else {
                            log::info('Contract not found.');
                        }
                    }

                    foreach ($actions as $action) {
                        $category_id = Action::select('category_id')->where('actions.id', '=', $action)->first();
                        if ($category_id['category_id'] != 0) {
                            $check_exist = SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                                ->where('sorting_contract_activities.action_id', '=', $action)
                                ->where('sorting_contract_activities.category_id', '=', $category_id['category_id'])
                                ->where('sorting_contract_activities.is_active', '=', 1)
                                ->count();

                            if ($check_exist == 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }
                } else {
                    SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                        ->where('sorting_contract_activities.is_active', '=', 1)
                        ->update(['sorting_contract_activities.is_active' => 0]);
                }

                if (count($custom_actions) > 0) {
                    foreach ($custom_actions as $custom_action) {
                        if ($contract) {
                            $category_id = Action::select('category_id')->where('actions.id', '=', $custom_action)->first();
                            if ($category_id['category_id'] != 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $custom_action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }
                }
            } else {
                $actions = Request::input('actions');
                $index = 0;
                if ($actions) {
                    SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                        ->whereNotIn('sorting_contract_activities.action_id', $actions != null ? $actions : [0])
                        ->where('sorting_contract_activities.is_active', '=', 1)
                        ->update(['sorting_contract_activities.is_active' => 0]);

                    $activities = SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                        ->where('sorting_contract_activities.is_active', '=', 1)
                        ->get();

                    foreach ($activities as $activity) {
                        SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                            ->where('sorting_contract_activities.action_id', '=', $activity->action_id)
                            ->where('sorting_contract_activities.is_active', '=', 1)
                            ->update(['sorting_contract_activities.is_active' => 0]);

                        $index++;
                        $sorting_contract = new SortingContractActivity();
                        $sorting_contract->contract_id = $contract->id;
                        $sorting_contract->category_id = $activity->category_id;
                        $sorting_contract->action_id = $activity->action_id;
                        $sorting_contract->sort_order = $index;
                        $sorting_contract->save();
                    }

                    // $index = count($activities);

                    foreach ($actions as $action) {
                        $category_id = Action::select('category_id')->where('actions.id', '=', $action)->first();
                        if ($category_id['category_id'] != 0) {
                            $check_exist = SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                                ->where('sorting_contract_activities.action_id', '=', $action)
                                ->where('sorting_contract_activities.category_id', '=', $category_id['category_id'])
                                ->where('sorting_contract_activities.is_active', '=', 1)
                                ->count();

                            if ($check_exist == 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }

                } else {
                    SortingContractActivity::where('sorting_contract_activities.contract_id', '=', $contract->id)
                        ->where('sorting_contract_activities.is_active', '=', 1)
                        ->update(['sorting_contract_activities.is_active' => 0]);
                }

                if (count($custom_actions) > 0) {
                    foreach ($custom_actions as $custom_action) {
                        if ($contract) {
                            $category_id = Action::select('category_id')->where('actions.id', '=', $custom_action)->first();
                            if ($category_id['category_id'] != 0) {
                                $index++;
                                $sorting_contract = new SortingContractActivity();
                                $sorting_contract->contract_id = $contract->id;
                                $sorting_contract->category_id = $category_id['category_id'];
                                $sorting_contract->action_id = $custom_action;
                                $sorting_contract->sort_order = $index;
                                $sorting_contract->save();
                            }
                        }
                    }
                }
            }

            // 6.1.1.12 changes starts
            PhysicianContracts::where('contract_id', '=', $contract->id)
                ->update(array('deleted_at' => DB::raw('NOW()'), 'updated_by' => Auth::user()->id));

            SortingContractName::where('contract_id', '=', $contract->id)
                ->update(array('is_active' => 0));

            if (Request::exists('selectedPhysicianList')) {
                $physician_practice_arr_of_str = Request::input('selectedPhysicianList');
                foreach ($physician_practice_arr_of_str as $physician_practice_str) {
                    $physician_practice_arr = explode("_", $physician_practice_str);
                    $physician_id = $physician_practice_arr[0];
                    $practice_id = $physician_practice_arr[1];
                    $check_physician = PhysicianContracts::where('contract_id', '=', $contract->id)
                        ->where('physician_id', '=', $physician_id)
                        ->where('practice_id', '=', $practice_id)
                        ->first();

                    if ($check_physician) {
                        if ($check_physician->deleted_at != Null) {
                            PhysicianContracts::where('contract_id', '=', $contract->id)
                                ->where('physician_id', '=', $physician_id)
                                ->where('practice_id', '=', $practice_id)
                                ->update(array('deleted_at' => Null, 'updated_by' => Auth::user()->id));
                        }
                    } else {
                        $contract->physicians()->attach([$contract->id => ['physician_id' => $physician_id, 'contract_id' => $contract->id, 'practice_id' => $practice_id, 'created_by' => Auth::user()->id, 'updated_by' => Auth::user()->id]]);
                    }

                    // Below code is for adding multiple physicians to contract on edit .
                    $check_sorting_contract_exists = SortingContractName::where('sorting_contract_names.contract_id', '=', $contract->id)
                        ->where('sorting_contract_names.practice_id', '=', $practice_id)
                        ->where('sorting_contract_names.physician_id', '=', $physician_id)
                        ->orderBy('sorting_contract_names.id', 'desc')
                        ->first();

                    if ($check_sorting_contract_exists) {
                        if ($check_sorting_contract_exists->is_active == 0) {
                            $check_sorting_contract_exists->is_active = 1;
                            $check_sorting_contract_exists->save();
                        }

                    } else {
                        $max_sort_order = SortingContractName::select([DB::raw('MAX(sorting_contract_names.sort_order) AS max_sort_order')])
                            ->where('sorting_contract_names.practice_id', '=', $practice_id)
                            ->where('sorting_contract_names.physician_id', '=', $physician_id)
                            ->where('sorting_contract_names.is_active', '=', 1)
                            ->first();

                        if ($max_sort_order) {
                            $sort_contract = new SortingContractName();
                            $sort_contract->practice_id = $practice_id;
                            $sort_contract->physician_id = $physician_id;
                            $sort_contract->contract_id = $contract->id;
                            $sort_contract->sort_order = $max_sort_order['max_sort_order'] + 1;
                            $sort_contract->save();
                        } else {
                            $sort_contract = new SortingContractName();
                            $sort_contract->practice_id = $practice_id;
                            $sort_contract->physician_id = $physician_id;
                            $sort_contract->contract_id = $contract->id;
                            $sort_contract->sort_order = 1;
                            $sort_contract->save();
                        }
                    }

                }
            }
            // 6.1.1.12 changes end

            UpdatePendingPaymentCount::dispatch($result_hospital->id);
            // PaymentStatusDashboard::updatePaymentStatus($result_hospital->id, $contract->agreement_id, $contract->id);

            return Redirect::route('physicians.contracts', [$physician_id, $pid])->with([
                'success' => Lang::get('contracts.edit_success')
            ]);
        }
    }

    public static function update_next_approver($contract, $default_to_agreement, $level, $new_user_id)
    {
        if ($contract->default_to_agreement != $default_to_agreement && $default_to_agreement == 1 && $level == 0 && $new_user_id == 0) {
            $agreement_approval_managers_info = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)
                ->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->get();
            foreach ($agreement_approval_managers_info as $agreement_approval_manager_info) {
                PhysicianLog::where("next_approver_level", "=", $agreement_approval_manager_info->level)
                    ->where("contract_id", "=", $contract->id)->update(array("next_approver_user" => $agreement_approval_manager_info->user_id));
            }
        } elseif ($level != 0 && $new_user_id != 0) {
            PhysicianLog::where("next_approver_level", "=", $level)
                ->where("contract_id", "=", $contract->id)->update(array("next_approver_user" => $new_user_id));
        }
        return true;
    }

//physician to multiple hospital by 1254 added hospital id

    public static function postEditPsa($id)
    {
        $contract = self::findOrFail($id);
        $contract_psa_metrics_old = ContractPsaMetrics::getMetrics($contract->id);

        $contract_psa_metrics_old->end_date = DB::raw('CURRENT_TIMESTAMP');
        $contract_psa_metrics_old->updated_by = Auth::user()->id;

        $contract_psa_metrics = new ContractPsaMetrics();
        $contract_psa_metrics->contract_id = $contract->id;
        $contract_psa_metrics->annual_comp = floatval(Request::input('annual_comp'));
        $contract_psa_metrics->annual_comp_fifty = floatval(Request::input('annual_comp_fifty'));
        $contract_psa_metrics->wrvu_fifty = Request::input('wrvu_fifty');
        $contract_psa_metrics->annual_comp_seventy_five = floatval(Request::input('annual_comp_seventy_five'));
        $contract_psa_metrics->wrvu_seventy_five = Request::input('wrvu_seventy_five');
        $contract_psa_metrics->annual_comp_ninety = floatval(Request::input('annual_comp_ninety'));
        $contract_psa_metrics->wrvu_ninety = Request::input('wrvu_ninety');
        $contract_psa_metrics->created_by = Auth::user()->id;
        $contract_psa_metrics->updated_by = Auth::user()->id;
        if (!ContractPsaMetrics::compareObjects($contract_psa_metrics_old, $contract_psa_metrics)) {
            $contract_psa_metrics_old->save();
            $contract_psa_metrics->save();
        }

        return Redirect::route('physicians.contracts', $contract->physician_id)->with([
            'success' => Lang::get('contracts.edit_success')
        ]);
    }

    public static function getPsaRate($contract_id, $duration)
    {
        $rate = DB::table("contract_psa_wrvu_rates")
            ->select("contract_psa_wrvu_rates.rate")
            ->whereRaw("contract_psa_wrvu_rates.contract_id = " . $contract_id)
            ->whereRaw("contract_psa_wrvu_rates.upper_bound >= " . $duration)
            ->whereRaw("contract_psa_wrvu_rates.deleted_at is null")
            ->orderBy("contract_psa_wrvu_rates.upper_bound", "asc")
            ->first();
        if ($rate != null) {
            return $rate->rate;
        } else {
            return 0;
        }
    }

    public static function contract_logs_pending_approval($user_id)
    {
        $result = array();
        $contracts_pending_for_approval = array();
        //$prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $proxy_check_id = LogApproval::find_proxy_aaprovers($user_id); //added this condition for checking with proxy approvers
        //finding contracts having approval process on & this user has approval process allotted
        $contracts = Contract::select(
            DB::raw("contracts.*"))
            ->join('physician_logs', 'physician_logs.contract_id', '=', 'contracts.id')
            ->join('agreements', 'agreements.id', '=', 'contracts.agreement_id')
            ->join('agreement_approval_managers_info', 'agreement_approval_managers_info.agreement_id', '=', 'agreements.id')
            //->where("physician_logs.date", "<=", mysql_date($prior_month_end_date))
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->where('agreements.approval_process', '=', '1')
            ->where('agreements.archived', '=', false)
            //->where('agreement_approval_managers_info.user_id', '=', $user_id)
            ->whereIn('agreement_approval_managers_info.user_id', $proxy_check_id) //added this condition for checking with proxy approvers
            ->where('agreement_approval_managers_info.is_deleted', '=', '0')
            ->distinct()->get();

        foreach ($contracts as $contract) {
            $agreement_data = Agreement::getAgreementData($contract->agreement_id);
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $logs_count = PhysicianLog::select('id')->where('physician_logs.contract_id', '=', $contract->id)
                ->where('physician_logs.signature', '=', 0)
                ->where('physician_logs.approval_date', '=', '0000-00-00')
                ->where('physician_logs.date', '<=', mysql_date($prior_month_end_date))
                ->count();

            if ($logs_count > 0) {
                $contract_present = 0;
                // add new approval check 30Aug2018
                if ($contract->default_to_agreement == 0) //contract has it's own CM/FM/EM other than agreement
                {
                    //Check contract level CM FM
                    $agreement_approval_managers_info = ApprovalManagerInfo::where("contract_id", "=", $contract->id)
                        ->where("is_deleted", "=", '0')
                        //->where("user_id","=",$user_id)
                        ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                        ->get();
                    if (count($agreement_approval_managers_info) > 0) //if CM/FM/EM is present
                    {
                        $contract_present = 1;
                    } else //CM/FM/EM is not present
                    {
                        $contract_present = 0;
                    }
                } else //Contract has same CM/FM/EM as of agreement
                {
                    //Check agreement level  CM FM
                    $agreement_approval_managers_info = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)
                        ->where("contract_id", "=", 0)
                        ->where("is_deleted", "=", '0')
                        //->where("user_id","=",$user_id)
                        ->whereIn("user_id", $proxy_check_id) //added this condition for checking with proxy approvers
                        ->get();
                    if (count($agreement_approval_managers_info) > 0) //if CM/FM/EM is present for agreement
                    {
                        $contract_present = 1;
                    } else //CM/FM/EM is not present
                    {
                        $contract_present = 0;
                    }
                }
//log::debug('TEst', array($contract->physician_id, $contract_present));
//                $physician = Physician::where('id', '=', $contract->physician_id)->first();
//                if ($physician && $contract_present ==1)
                if ($contract_present == 1) {
//                    $practice = Practice::where('id', '=',$contract->pivot->practice_id)->first(); // This condition is commented because contract belongs to many physicians of multiple practices now 6.1.1.12
//                    if($practice) //if practice is present
//                    {
                    foreach ($agreement_approval_managers_info as $agreement_approval_manager_info) {
                        //finding not approved physician logs
                        $logs = PhysicianLog::join('log_approval', 'log_approval.log_id', '=', 'physician_logs.id')
                            ->where("physician_logs.date", "<=", $prior_month_end_date)
                            ->where("physician_logs.signature", "=", 0)
                            ->where("physician_logs.approval_date", "=", "0000-00-00")
                            ->where("physician_logs.contract_id", "=", $contract->id)
                            ->where(function ($query) use ($agreement_approval_manager_info) {
                                $query->where('log_approval.approval_managers_level', '=', $agreement_approval_manager_info->level - 1)
                                    ->where('log_approval.approval_status', '=', 1);
                            })
                            ->whereIn("physician_logs.next_approver_user", $proxy_check_id) //added this condition for checking with proxy approvers
                            ->distinct()->pluck('log_approval.log_id');
                        if (count($logs) > 0) {
                            $current_approval = LogApproval::whereIn('log_id', $logs)
                                ->where('approval_managers_level', '=', $agreement_approval_manager_info->level)
                                ->where('approval_status', '=', 1)->get();
                            if (count($current_approval) != count($logs))
                                if (!in_array($contract->id, $contracts_pending_for_approval)) {
                                    array_push($contracts_pending_for_approval, $contract->id);
                                }
                        }
                    }
//                    }//end of if for practice check
//                    else
//                    {
//                        //code for if practice is not present
//                    }
                }//end of if for contract &physician present
                else {
                    //code for contract or physician is not present
                }
            }

        }// end of foreach for contracts

        return $contracts_pending_for_approval;
    }

    public static function get_contract_info($user_id)
    {
        ini_set('max_execution_time', '3000');
        /**
         * This code is used for fetching the records using stored procedure by akash.
         */

        $hospital_user_obj = DB::table("hospital_user")
            ->join("hospitals", "hospital_user.hospital_id", "=", "hospitals.id")
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNull('hospitals.deleted_at')
            ->where('hospitals.archived', '=', 0)
            ->pluck('hospital_user.hospital_id')->toArray();

        $hospital_ids_str = implode(",", $hospital_user_obj);
        // $hospital_id = $hospital_user_obj[0];

        $deployment_date = getDeploymentDate("ContractRateUpdate");

        $queries = DB::getQueryLog();
        $last_query = end($queries);

        $pending_payment_contract = 0;

        foreach ($hospital_user_obj as $hospital_id) {
            $result = HospitalContractSpendPaid::getPendingPaymentCount($hospital_id);
            if ($result) {
                $pending_payment_contract += $result['pending_payment_count'];
            }
        }

        return $pending_payment_contract;
    }

    public static function fetch_note_display_contracts_amended_count($id)
    {
        $contract_count = count(DB::select("call sp_contracts_updated_by_hospital_based_userid('" . $id . "','30')"));
        return $contract_count;
    }

    public static function fetch_note_display_contracts_count($id)
    {
        $day_after_90_days = date('Y-m-d', strtotime("+90 days"));
        if (!is_super_user()) {
            $contract_count = DB::table("contracts")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospital_user", "hospital_user.hospital_id", "=", "agreements.hospital_id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.archived=0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("agreements.end_date >= now()")
                ->where("agreements.end_date", "<=", $day_after_90_days)
                ->where("agreements.hospital_id", "<>", 45)
                ->where("hospital_user.user_id", "=", $id)
                ->whereNull('contracts.deleted_at')->distinct()->count();
        } else {
            $last_month = date('Y-m-d', strtotime("first day of last month"));
            $contract_count = DB::table("contracts")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereRaw("agreements.is_deleted=0")
                ->whereRaw("agreements.archived=0")
                ->whereRaw("agreements.start_date <= now()")
                ->where("agreements.end_date", ">=", $last_month)
                ->where("agreements.end_date", "<=", $day_after_90_days)
                ->where("agreements.hospital_id", "<>", 45)
                ->where("agreements.hospital_id", "=", $id)
                ->whereNull('contracts.deleted_at')->distinct()->count();
        }
        return $contract_count;
    }

    public static function getContractManualEndDate($contractId)
    {
        $getManualContractEndDate = DB::table('contracts')
            ->select('contracts.manual_contract_end_date')
            ->where('contracts.id', '=', $contractId)
            ->get();
        if (!empty($getManualContractEndDate)) {
            return $getManualContractEndDate[0]->manual_contract_end_date;
        }
    }

    public static function archiveUnrchive($id, $status)
    {
        if (!is_super_user())
            App::abort(403);

        $contract = self::findOrFail($id);

        if ($status) {
            $checkPenddingLogs = PhysicianLog::penddingApprovalForContract($contract->id);
            if ($checkPenddingLogs) {
                return Redirect::back()->with([
                    'error' => Lang::get('contracts.approval_pending_error')
                ]);
            }
            $checkPenddingPayments = Amount_paid::penddingPaymentForContract($contract->id);
            if ($checkPenddingPayments) {
                return Redirect::back()->with([
                    'error' => Lang::get('contracts.payment_pending_error')
                ]);
            }
        }

        $contract->manually_archived = $status;

        if (!$contract->save()) {
            if ($status) {
                return Redirect::back()->with(['error' => Lang::get('contracts.archive_error')]);
            } else {
                return Redirect::back()->with(['error' => Lang::get('contracts.unarchive_error')]);
            }
        }

        if ($status) {
            return Redirect::back()->with(['success' => Lang::get('contracts.archive_success')]);
        } else {
            return Redirect::back()->with(['success' => Lang::get('contracts.unarchive_success')]);
        }
    }

    public static function getContractsForHealthSystemUsers($contract_type, $payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem = false)
    {
        $contract_list = self::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
            "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
            "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name")
            ->join('physicians', 'physicians.id', '=', 'contracts.physician_id')
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id");

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contract_list = $contract_list->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id")
                ->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->where("health_system_users.user_id", "=", $user_id);
        } else {
            $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->where("health_system_region_users.user_id", "=", $user_id);
        }

        if ($region != 0) {
            $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
        }

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }

        if ($exclude_perDiem) {
            $contract_list = $contract_list->where("contracts.payment_type_id", "<>", PaymentType::PER_DIEM);
        }

        $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("contracts.manual_contract_end_date >= now()");
        if ($contract_type != 0) {
            $contract_list = $contract_list->where("contracts.contract_type_id", "=", $contract_type);
        }
        if ($payment_type != 0) {
            $contract_list = $contract_list->where("contracts.payment_type_id", "=", $payment_type);
        }
        $contract_list = $contract_list->whereNull('region_hospitals.deleted_at')
            ->whereNull('contracts.deleted_at')->distinct()->get();
        return $contract_list;
    }

    public static function get_hospitals_contract_info($hospital_id)
    {
        $allAgreements = DB::table("agreements")
            ->select("agreements.id as id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->where("agreements.hospital_id", "<>", 45)
            ->where("agreements.hospital_id", "=", $hospital_id)
            ->orderBy("agreements.hospital_id")
            ->orderBy("agreements.id")
            ->distinct()->pluck('id');
        $practice_info = Contract::get_agreements_contract_info($allAgreements);
        return $practice_info;
    }

    public static function get_agreements_contract_info($agreement_ids)
    {
        $result = array();
        $contract_name_info = array();
        $prev_contract_name_id = 0;
        $contract_name = "";

        $contract_name_ids = DB::table("physicians")
            ->select("contracts.contract_name_id as contract_name_id")
            // ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
            ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereIn("agreements.id", $agreement_ids)
            ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
            ->whereNull("contracts.deleted_at")
            ->whereNull('physicians.deleted_at')
            ->distinct()
            ->get();
        foreach ($contract_name_ids as $contract_name_id) {
            //drop column practice_id from table 'physicians' changes by 1254
            $active_contracts = DB::table("physicians")
                ->select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'),
                    "practices.name as practice_name", "physician_practices.practice_id as practice_id",
                    "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
                    "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.payment_frequency_type")
                // ->join("contracts", "contracts.physician_id", "=", "physicians.id")
                ->join("physician_contracts", "physician_contracts.physician_id", "=", "physicians.id")
                ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
                ->join("physician_practices", function ($join) {
                    $join->on('physician_practices.physician_id', '=', 'physicians.id')
                        ->on('physician_practices.practice_id', '=', 'physician_contracts.practice_id');
                })
                ->join("practices", "practices.id", "=", "physician_practices.practice_id")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereIn("agreements.id", $agreement_ids)
                ->where("contracts.contract_name_id", "=", $contract_name_id->contract_name_id)
                ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
                ->whereNull("contracts.deleted_at")
                ->whereNull('physicians.deleted_at')
                ->orderBy("physician_practices.practice_id")
                ->orderBy("agreements.id")
                ->distinct()
                ->get();
            if ($prev_contract_name_id != $contract_name_id->contract_name_id && $prev_contract_name_id != 0) {
                $contractName = ContractName::findOrFail($prev_contract_name_id);
                $contract_name = $contractName->name;
                $result[] = [
                    'contract_name' => $contract_name,
                    'contracts_physician_count' => $contract_name_info['contracts_physicianCount'],
                    'contracts_paidToDate' => $contract_name_info['contracts_paidToDate'],
                    'practice_info' => $contract_name_info['practices_data']
                ];
                unset($contract_name_info); //  is gone
                $contract_name_info = array(); // is here again
                unset($practice_data); //  is gone
                $practice_data = array(); // is here again
            }
            $prev_contract_name_id = $contract_name_id->contract_name_id;
            $prev_practice = 0;
            $prev_practice_name = '';
            $current_practice = 0;
            $current_practice_name = "";
            $practice_paidToDate = 0;
            $paidToDate = 0;
            $contracts_paidToDate = 0;
            $contracts_physicianCount = 0;
            $physicianCount = 0;
            $contracts_info = array();
            $contract_info = array();
            $manager_types = ApprovalManagerType::all();
            $processed_contracts = [];

            foreach ($active_contracts as $contract) {
//physician to multiple hospital by 1254
                $practicenew = DB::table("practices")
                    ->select("practices.name")
                    // ->join("contracts","contracts.practice_id","=","practices.id")
                    ->join("physician_contracts", "physician_contracts.practice_id", "=", "practices.id")
                    ->join("contracts", "contracts.id", "=", "physician_contracts.contract_id")
                    ->where("contracts.id", "=", $contract->id)
                    ->first();

                $current_practice = $contract->practice_id;
                //Action Redesign by 1254 : getting practice
                if ($practicenew)
                    $current_practice_name = $practicenew->name;
                //  else
                //  $current_practice_name = $contract->practice_name;


                if ($prev_practice != $current_practice && $prev_practice != 0) {
                    $practice_data[] = [
                        'practice_name' => $prev_practice_name,
                        'total_physician' => $physicianCount,
                        'practice_paidToDate' => $practice_paidToDate,
                        'contract_info' => $contracts_info
                    ];

                    $contracts_paidToDate = $contracts_paidToDate + $practice_paidToDate;
                    $contracts_physicianCount = $contracts_physicianCount + $physicianCount;
                    $practice_paidToDate = 0;
                    $physicianCount = 0;
                    unset($contracts_info); //  is gone
                    $contracts_info = array(); // is here again
                }
                $prev_practice = $current_practice;
                $prev_practice_name = $current_practice_name;
                $physicianCount++;
                if ($contract->approval_process == 1) {
                    if ($contract->default_to_agreement == 1) {
                        $approval_managers = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->orderBy("level")->get();
                    } else {
                        $approval_managers = ApprovalManagerInfo::where("agreement_id", "=", $contract->agreement_id)->where("contract_id", "=", $contract->id)->where("is_deleted", "=", '0')->orderBy("level")->get();
                    }
                    $managers_info = array();
                    foreach ($approval_managers as $manager) {
                        $user = User::select(DB::raw('CONCAT(users.first_name, " ", users.last_name) AS manager_name'))->where("id", "=", $manager->user_id)->first();
                        if ($user != null) {
                            switch ($manager->type_id) {
                                case 1:
                                    $manager_type = "CM";
                                    break;
                                case 2:
                                    $manager_type = "FM";
                                    break;
                                case 3:
                                    $manager_type = "EM";
                                    break;
                                default:
                                    $manager_type = "";
                            }
                            $managers_info[] = [
                                "type" => $manager_type,
                                "manager_name" => $user->manager_name
                            ];
                        } else {
                            $managers_info[] = [
                                // "type" => $manager_type, //This line is commented to solved undefined variable $manager_type issue.
                                "type" => "",
                                "manager_name" => "Not Present"
                            ];
                        }
                    }
                    $contract_info["approval_managers"] = $managers_info;
                } else {
                    $contract_info["approval_managers"] = [];
                }

                $contractName = ContractName::select("name")->where("id", "=", $contract->contract_name_id)->first();
                if (!isset($contractName->name)) {
                    $contractName = new StdClass();
                    $contractName->name = "Contract name not Found";
                }
                $contract_name = $contractName->name;
                //$contract_document = ContractDocuments::select("filename")->where("contract_id", "=", $contract->id)->where("is_active", "=", '1')->first();
                $contract_document = ContractDocuments::select("filename")->where("contract_id", "=", $contract->id)->where("is_active", "=", '1')->get();

                if (!in_array($contract->id, $processed_contracts)) {
                    $amount_paid = DB::table("amount_paid")
                        ->select(DB::raw('SUM(amount_paid.amountPaid) AS paid'))
                        ->where("amount_paid.contract_id", "=", $contract->id)
                        ->where("amount_paid.practice_id", "=", $contract->practice_id)->first();

                    if ($amount_paid->paid != null) {
                        $paidToDate = $amount_paid->paid;
                    } else {
                        $paidToDate = 0;
                    }

                    $paidToDate = $paidToDate + $contract->prior_amount_paid;//add prior paid amount to contract paid amount
                } else {
                    $paidToDate = 0;
                }

                $contract_data = Contract::findOrFail($contract->id);
                $actions = Action::getActions($contract_data);

                $contract_info["contract_id"] = $contract->id;
                $contract_info["contract_name"] = $contract_name;
                $contract_info["agreement_start_date"] = $contract->agreement_start_date;
                $contract_info["agreement_end_date"] = $contract->agreement_end_date;
                $contract_info["agreement_valid_upto_date"] = $contract->agreement_valid_upto_date;
                $contract_info["manual_contract_end_date"] = $contract->manual_contract_end_date;
                $contract_info["manual_contract_valid_upto"] = $contract->manual_contract_valid_upto;
                $contract_info["physician_name"] = $contract->physician_name;
                $contract_info["expected_hours"] = $contract->expected_hours;
                $contract_info["min_hours"] = $contract->min_hours;
                $contract_info["max_hours"] = $contract->max_hours;
                $contract_info["annual_cap"] = $contract->annual_cap;
                $contract_info["updated_at"] = $contract->updated_at;
                $contract_info["FMV_rate"] = $contract->rate;
                $contract_info["payment_type_id"] = $contract->payment_type_id;
                $contract_info["contract_type_id"] = $contract->contract_type_id;
                $contract_info["weekday_rate"] = $contract->weekday_rate;
                $contract_info["weekend_rate"] = $contract->weekend_rate;
                $contract_info["holiday_rate"] = $contract->holiday_rate;
                $contract_info["on_call_rate"] = $contract->on_call_rate;
                $contract_info["called_back_rate"] = $contract->called_back_rate;
                $contract_info["called_in_rate"] = $contract->called_in_rate;
                $contract_info["on_call_process"] = $contract->on_call_process;
                $contract_info["payment_type"] = $contract->payment_type_id;
                $contract_info["payment_frequency_type"] = $contract->payment_frequency_type;

                $contract_info["contract_document"] = (!is_null($contract_document)) ? $contract_document : "NA";
                $contract_info["actions"] = $actions;
                $agreement_details = Agreement::findOrFail($contract->agreement_id);

                $on_call_uncompensated_rates = ContractRate::getRate($contract->id, $agreement_details->start_date, ContractRate::ON_CALL_UNCOMPENSATED_RATE);
                $contract_info["on_call_uncompensated_rates"] = $on_call_uncompensated_rates;

                $practice_paidToDate = $practice_paidToDate + $paidToDate;

                $contracts_info[] = $contract_info;
                unset($contract_info); //  is gone
                $contract_info = array(); // is here again
                $processed_contracts[] = $contract->id;
            }
            if (count($active_contracts) > 0) {
                $practice_data[] = [
                    'practice_name' => $current_practice_name,
                    'total_physician' => $physicianCount,
                    'practice_paidToDate' => $practice_paidToDate,
                    'contract_info' => $contracts_info
                ];
            } else {
                $practice_data = [];
            }
            $contracts_paidToDate = $contracts_paidToDate + $practice_paidToDate;
            $contracts_physicianCount = $contracts_physicianCount + $physicianCount;
            $contract_name_info["contract_name"] = $contract_name;
            $contract_name_info["contracts_physicianCount"] = $contracts_physicianCount;
            $contract_name_info["contracts_paidToDate"] = $contracts_paidToDate;
            $contract_name_info["practices_data"] = $practice_data;
        }
        if (count($contract_name_info) > 0) {
            $result[] = [
                'contract_name' => $contract_name,
                'contracts_physician_count' => $contract_name_info['contracts_physicianCount'],
                'contracts_paidToDate' => $contract_name_info['contracts_paidToDate'],
                'practice_info' => $contract_name_info['practices_data']
            ];
        } else {
            $result[] = [
                'contract_name' => '',
                'contracts_physician_count' => 0,
                'contracts_paidToDate' => '',
                'practice_info' => array()
            ];
        }
        return $result;
    }

    public static function get_primary_approver_contracts($user_id)
    {
        $contracts_for_user = array();
        $agreement_ids = ApprovalManagerInfo::select("agreement_id")
            ->where("is_deleted", "=", '0')->where("user_id", "=", $user_id)->pluck("agreement_id");
        if (count($agreement_ids) > 0) {
            $contract_with_default = DB::table('contracts')->select("contracts.id as contract_id")
                ->where("contracts.default_to_agreement", "=", "1")
                ->whereNull("contracts.deleted_at")
                ->whereIN("contracts.agreement_id", $agreement_ids);
            $contracts_for_user = ApprovalManagerInfo::select("agreement_approval_managers_info.contract_id")
                ->join('contracts', 'contracts.id', '=', 'agreement_approval_managers_info.contract_id')
                ->where("agreement_approval_managers_info.contract_id", ">", 0)
                ->where("agreement_approval_managers_info.is_deleted", "=", '0')->where("agreement_approval_managers_info.user_id", "=", $user_id)
                ->whereNull("contracts.deleted_at")
                ->union($contract_with_default)->pluck("agreement_approval_managers_info.contract_id")->toArray();
        }
        return $contracts_for_user;
    }

    public static function updateApprovers($id)
    {
        $contract = Contract::findOrFail($id);
        $usedefault = Request::input('default');
        if ($usedefault == 0) {
            $result = array();
            $contract->default_to_agreement = '0';
            //Write code for approval manager conditions & to save them in database
            $agreement_id = $contract->agreement_id;//fetch agreement id
            $contract_id = $contract->id;// contract id
            $approval_manager_info = array();
            $levelcount = 0;
            $approval_level = array();
            $emailCheck = Request::input('emailCheck');
            if (!$contract->save()) {
                $result["response"] = "error";
                $result["msg"] = Lang::get('contracts.edit_error');
            } else {
                $result["response"] = "success";
                $result["msg"] = Lang::get('contracts.edit_success');
            }

            $contract = Contract::findOrFail($id);
            //Fetch all levels of approval managers & remove NA approvaal levels
            for ($i = 1; $i < 7; $i++) {

                //            if(Request::input('approverTypeforLevel'.$i)!=0)
                if (Request::input('approval_manager_level' . $i) > 0) {
                    //log::info("in loop type for level",array(Request::input('approverTypeforLevel'.$i)));
                    //              $approval_level[$levelcount]['approvalType']=Request::input('approverTypeforLevel'.$i);
                    $approval_level[$levelcount]['approvalType'] = 0;
                    $approval_level[$levelcount]['level'] = $levelcount + 1;
                    $approval_level[$levelcount]['approvalManager'] = Request::input('approval_manager_level' . $i);
                    $approval_level[$levelcount]['initialReviewDay'] = Request::input('initial_review_day_level' . $i);
                    $approval_level[$levelcount]['finalReviewDay'] = Request::input('final_review_day_level' . $i);
                    $approval_level[$levelcount]['emailCheck'] = $emailCheck > 0 ? in_array("level" . $i, $emailCheck) ? '1' : '0' : '0';
                    $levelcount++;
                }
            }

            //          asort($approval_level);//Sorting on basis of type of approval level
            //log::info($approval_level);
            $approval_level_number = 1;
            $fail_to_save_level = 0;

            DB::table('agreement_approval_managers_info')->where('level', ">", $levelcount)->where('agreement_id', '=', $agreement_id)->where('contract_id', '=', $id)->where('is_deleted', '=', '0')->update(array('is_deleted' => '1'));

            foreach ($approval_level as $key => $approval_level) {
                /*Query for level, fetch type & manager, if type & levels are matching update all other info,
                if not matching, update flag is_deleted =1 & insert new row for info  */
                $contract_approval_manager_data = DB::table('agreement_approval_managers_info')->where('agreement_id', "=", $agreement_id)
                    ->where('level', "=", $approval_level_number)
                    ->where('is_deleted', '=', '0')
                    ->where('contract_id', '=', $contract_id)
                    ->first();
                if ($contract_approval_manager_data != null && $contract_approval_manager_data->type_id == $approval_level['approvalType']) {
                    $contract_approval_manager_info = ApprovalManagerInfo::findOrFail($contract_approval_manager_data->id);
                    $contract_approval_manager_info->agreement_id = $agreement_id;
                    $contract_approval_manager_info->contract_id = $contract_id;
                    //                $contract_approval_manager_info->level=$approval_level_number;
                    $contract_approval_manager_info->level = $approval_level['level'];
                    $contract_approval_manager_info->type_id = $approval_level['approvalType'];
                    $contract_approval_manager_info->user_id = $approval_level['approvalManager'];
                    $contract_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                    $contract_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                    $contract_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                    $contract_approval_manager_info->is_deleted = '0';
                    //Log::info('approval_manager_info for check',array($contract_approval_manager_info));
                    if (!$contract_approval_manager_info->save()) {
                        $fail_to_save_level = 1;
                    } else {

                        if ($contract_approval_manager_data->user_id != $approval_level['approvalManager']) {
                            Contract::update_next_approver($contract, $contract->default_to_agreement, $contract_approval_manager_data->level, $approval_level['approvalManager']);
                        }
                        //success
                        $approval_level_number++;
                    }
                } else {
                    DB::table('agreement_approval_managers_info')
                        ->where('level', '=', $approval_level_number)
                        ->where('agreement_id', '=', $agreement_id)
                        ->where('contract_id', '=', $contract_id)
                        ->where('is_deleted', '=', '0')
                        ->update(array('is_deleted' => '1'));
                    $contract_approval_manager_info = new ApprovalManagerInfo;
                    $contract_approval_manager_info->agreement_id = $agreement_id;
                    $contract_approval_manager_info->level = $approval_level_number;
                    $contract_approval_manager_info->type_id = $approval_level['approvalType'];
                    $contract_approval_manager_info->user_id = $approval_level['approvalManager'];
                    $contract_approval_manager_info->initial_review_day = $approval_level['initialReviewDay'];
                    $contract_approval_manager_info->final_review_day = $approval_level['finalReviewDay'];
                    $contract_approval_manager_info->opt_in_email_status = $approval_level['emailCheck'];
                    $contract_approval_manager_info->is_deleted = '0';
                    $contract_approval_manager_info->contract_id = $contract_id;
                    if (!$contract_approval_manager_info->save()) {
                        $fail_to_save_level = 1;
                    } else {
                        Contract::update_next_approver($contract, $contract->default_to_agreement, $approval_level_number, $approval_level['approvalManager']);
                        //success
                        $approval_level_number++;
                    }
                }
            }//End of for loop

            if ($fail_to_save_level == 1) {
                $result["response"] = "error";
                $result["msg"] = Lang::get('contracts.edit_error');
            } else {
                $result["response"] = "success";
                $result["msg"] = Lang::get('contracts.edit_success');
            }

        } else {
            self::update_next_approver($contract, 1, 0, 0);
            $contract->default_to_agreement = '1';
            if (!$contract->save()) {
                $result["response"] = "error";
                $result["msg"] = Lang::get('contracts.edit_error');
            } else {
                $result["response"] = "success";
                $result["msg"] = Lang::get('contracts.edit_success');
            }
        }
        return $result;
    }

    public static function datesOptions($dates)
    {
        $results = array();
        $start_dates = array();
        $end_dates = array();
        $start = date("Y-m-01", strtotime($dates['start']));
        $end = date("Y-m-t", strtotime($dates['end']));
        $term = months($start, $end);
        $start_date = with(new DateTime($start))->setTime(0, 0, 0);
        $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
        for ($i = 0; $i < $term; $i++) {
            $month_data = new StdClass;
            $month_data->number = $i + 1;
            $month_data->start_date = $start_date->format('m/d/Y');
            $month_data->startdate = $start_date->format('Y-m-d');
            $month_data->end_date = $end_date->format('m/d/Y');
            $month_data->enddate = $end_date->format('Y-m-d');
            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
            $start_dates["{$month_data->startdate}"] = "{$month_data->start_date}";
            $end_dates["{$month_data->enddate}"] = "{$month_data->end_date}";
        }
        $results = ["start_dates" => $start_dates, "end_dates" => $end_dates];
        return $results;
    }

    public static function updatePartialHoursCalculation()
    {
        ini_set('max_execution_time', 6000);

        DB::table('contracts')->where('partial_hours', "=", 1)->where("payment_type_id", "=", 3)->update(
            array('partial_hours_calculation' => 24));
        return 1;
    }

    public static function getContractsType($contract_type, $user_id, $facility)
    {
        $contract_list = self::select("contracts.*", "contract_names.id as contract_type_id", "contract_names.name as contract_name", "hospitals.name as hospital_name", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'))
            ->join('physician_contracts', 'physician_contracts.contract_id', 'contracts.id')
            ->join('physicians', 'physicians.id', '=', 'physician_contracts.physician_id')
            // ->join('practices','practices.id','=','physicians.practice_id')
            // ->join("physician_practices", "physician_practices.physician_id", "=", "physicians.id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
            ->join('hospital_user', 'hospital_user.hospital_id', '=', 'hospitals.id')
            //->join('users', 'users.id', '=', 'hospital_user.user_id')
            ->where('hospital_user.user_id', '=', $user_id)
            ->whereNull("contracts.deleted_at")
            ->whereIn("hospitals.id", $facility);

        // if($facility != 0){
        //     $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        // }

        if ($contract_type != 0) {
            $contract_list = $contract_list->where("contracts.contract_type_id", "=", $contract_type);
        }

        $contract_list = $contract_list
            ->whereNull('contracts.deleted_at')->distinct()->get();

        return $contract_list;
    }

    public static function getContractsForRegionAndHealthSystemUsers($contract_type, $payment_type, $user_id, $group_id, $region, $facility, $exclude_perDiem = false, $selected_start_date, $selected_end_date, $with_physician = 0)
    {
        if ($with_physician == 0) {
            $contract_list = self::select("contracts.*", DB::raw('CONCAT(physicians.first_name, " ", physicians.last_name) AS physician_name'), "physicians.id as physicians_id",
                "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
                "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name")
                ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
                ->join("physicians", "physicians.id", "=", "physician_contracts.physician_id")
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id");
        } else {
            $contract_list = self::select("contracts.*",
                "contract_names.name as contract_name", "agreements.id as agreement_id", "agreements.approval_process as approval_process", "agreements.start_date as agreement_start_date",
                "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "hospitals.name as hospital_name")
                ->join('contract_names', 'contract_names.id', '=', 'contracts.contract_name_id')
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->join("hospitals", "hospitals.id", "=", "agreements.hospital_id")
                ->join("region_hospitals", "region_hospitals.hospital_id", "=", "agreements.hospital_id");
        }

        if ($group_id == Group::HEALTH_SYSTEM_USER) {
            $contract_list = $contract_list->join("health_system_regions", "health_system_regions.id", "=", "region_hospitals.region_id")
                ->join("health_system_users", "health_system_users.health_system_id", "=", "health_system_regions.health_system_id")
                ->where("health_system_users.user_id", "=", $user_id);
        } else {
            $contract_list = $contract_list->join("health_system_region_users", "health_system_region_users.health_system_region_id", "=", "region_hospitals.region_id")
                ->where("health_system_region_users.user_id", "=", $user_id);
        }

        if ($region != 0) {
            $contract_list = $contract_list->where("region_hospitals.region_id", "=", $region);
        }

        if ($facility != 0) {
            $contract_list = $contract_list->where("hospitals.id", "=", $facility);
        }

        if ($exclude_perDiem) {
            $contract_list = $contract_list->where("contracts.payment_type_id", "<>", PaymentType::PER_DIEM);
        }

        $contract_list = $contract_list->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            ->where('agreements.start_date', '<', date("Y-m-d", strtotime($selected_end_date)))
            ->whereRaw("contracts.manual_contract_end_date != 0000-00-00")
            ->where('contracts.manual_contract_end_date', '>', date("Y-m-d", strtotime($selected_start_date)));

        if ($contract_type != 0) {
            $contract_list = $contract_list->where("contracts.contract_type_id", "=", $contract_type);
        }
        if ($payment_type != 0) {
            $contract_list = $contract_list->where("contracts.payment_type_id", "=", $payment_type);
        }
        $contract_list = $contract_list->whereNull('region_hospitals.deleted_at')
            ->whereNull('contracts.deleted_at')->distinct()->get();
        return $contract_list;
    }

    //Chaitraly::Return monthly stipend amount

    public static function getPriorMonthLogs($physician, $hospital_id)
    {
        $active_contracts = $physician->contracts()
            ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.payment_frequency_type")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
            ->whereRaw("practices.hospital_id = $hospital_id")
            ->whereRaw("contracts.practice_id=practices.id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
            ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
            ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
            ->distinct()
            ->get();

        $contracts = [];
        foreach ($active_contracts as $contract) {
            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                $valid_upto = $contract->manual_contract_end_date;
            }

            $today = date('Y-m-d');
            if ($valid_upto > $today) {
                $contracts[] = [
                    "id" => $contract->id,
                    "payment_type_id" => $contract->payment_type_id,
                    "payment_frequency_type" => $contract->payment_frequency_type,
                    "partial_hours" => $contract->partial_hours,
                    "partial_hours_calculation" => $contract->partial_hours_calculation,
                    "contract_type_id" => $contract->contract_type_id,
                    "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                    "start_date" => format_date($contract->agreement->start_date),
                    "end_date" => format_date($contract->manual_contract_end_date),
                    "rate" => $contract->rate,
                    "statistics" => self::getPriorMonthStatistics($contract),
                    "priorMonthStatistics" => self::getPriorMonthContractStatistics($contract),
                    "actions" => self::getContractActions($contract),
                    "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,       // physicians log the hours for holiday activity on any day
                    "state_attestations_monthly" => $contract->state_attestations_monthly,      // Sprint 6.1.1.8
                    "state_attestations_annually" => $contract->state_attestations_annually    // Sprint 6.1.1.8
                ];
            }
        }

        return $contracts;
    }

    private function getPriorMonthStatistics($contract)
    {
        $monthNum = 3;
        $dateObj = DateTime::createFromFormat('!m', $monthNum);
        $monthName = $dateObj->format('M');
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $expected_months = months($agreement_data->start_date, $agreement_month->now_date);
        $expected_hours = $contract->expected_hours * $expected_months;

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", mysql_date($agreement_data->start_date))
            ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
            ->sum("duration");

        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $worked_hours = $contract->logs()
                ->select("physician_logs.*")
                ->where("physician_logs.date", ">=", mysql_date($agreement_month->start_date))
                ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
                ->sum("duration");
            $expected_hours = $contract->expected_hours;
        }

        $remaining_hours = $expected_hours - $worked_hours;

        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">=", mysql_date($agreement_data->start_date))
            ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">=", mysql_date($agreement_data->start_date))
            ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
            ->count();

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => $saved_logs
        ];
    }

//physician to multiple hospital by 1254

    private function getPriorMonthContractStatistics($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->start_date)));
        $current_date = date('Y-m-d h:m:s', strtotime(mysql_date($agreement_month->now_date)));
        //$prior_month_end_date = date('Y-m-d', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $expected_months = months($agreement_data->start_date, $agreement_month->now_date);
        $expected_hours = $contract->expected_hours;

        $current_month_start_date_time = new DateTime($agreement_month->start_date);

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", $current_month_start_date_time)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");

        $total_prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->sum("duration");
        $remaining_hours = $expected_hours - $worked_hours;

        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->count();

        $prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->sum("duration");

        if (($contract->payment_type_id == PaymentType::HOURLY) && ($contract->prior_start_date != '0000-00-00')) {

            $prior_worked_hours = $prior_worked_hours + $contract->prior_worked_hours;
        }

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => $saved_logs,
            "prior_worked_hours" => formatNumber($prior_worked_hours),
            "total_prior_worked_hours" => formatNumber($total_prior_worked_hours)
        ];
    }

    private function getManagers($available)
    {
        $results = [];
        return $available;
    }

    private function getContracts($physician_id, $hospital_id)
    {
        $active_contracts = DB::table("physicians")
            ->select("contracts.*", "agreements.id as agreement_id", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereRaw("physicians.id = " . $physician_id)
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.hospital_id = " . $hospital_id)
            ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
            //->whereRaw("contracts.contract_type_id = ".ContractType::ON_CALL)
            ->whereRaw("contracts.payment_type_id = " . PaymentType::PER_DIEM)
            //->whereRaw("agreements.valid_upto >= now()")
            //->whereRaw(("agreements.valid_upto >= now()") OR ("agreements.end_date >= now()"))
            ->get();
        return $active_contracts;
    }

    private function getContractStatistics($physician, $contract_id, $hospital_id, $api_request)
    {

        /*$practice_id = PhysicianPractices::select("practice_id")
                                          ->where("physician_id","=",$physician->id)
                                          ->where("hospital_id","=",$hospital_id)->first();
       $physician->practice_id = $practice_id->practice_id;        */
        $active_contracts = $physician->contracts()
            ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.start_date as agreement_start_date", "agreements.payment_frequency_type")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            //    ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
            // ->join("practices", "practices.id", "=", "contracts.practice_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            //->whereRaw("contracts.practice_id = $physician->practice_id")
            // ->whereRaw("practices.id = $physician->practice_id")
            ->whereRaw("practices.hospital_id = $hospital_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
            ->orderBy('sorting_contract_names.practice_id', 'ASC')
            ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
            ->distinct()
            //->whereRaw("agreements.end_date >= now()")
            //->whereRaw("agreements.valid_upto >= now()")
            //->whereRaw("if( agreements.valid_upto == '0000-00-00', agreements.end_date, agreements.valid_upto) >= now()")
            //->whereRaw("agreements.valid_upto >= now()")
            //->orWhereRaw((("agreements.valid_upto = '0000-00-00'"))AND("agreements.end_date >= now()")  )
            ->get();

        $contracts = [];
        $physician_logs = new PhysicianLog();
        foreach ($active_contracts as $contract) {
            /*$valid_upto=$contract->agreement_valid_upto_date;
            if($valid_upto=='0000-00-00')
            {
                $valid_upto=$contract->agreement_end_date;
            }*/
            $today = date('Y-m-d');
            /*
              @added on :2018/12/27
              @description: Check if agreement end date is same or different with manual_contract_end_date(newly added condition)
              - if found same get the valid upto date
              - if found different get manual_contract_end_date
              - for old data check if 'manual_contract_end_date' is '0000-00-00' as 'manual_contract_end_date' is added on 2018/12/26
             */
            /*if($contract->agreement_end_date == $contract->manual_contract_end_date || $contract->manual_contract_end_date == '0000-00-00'){*//* remove to add valid upto to contract*/
            //$valid_upto = $contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                //$valid_upto=$contract->agreement_end_date; /* remove to add valid upto to contract*/
                $valid_upto = $contract->manual_contract_end_date;
            }
            /*}else{
                $valid_upto = $contract->manual_contract_end_date;
            }*//* remove to add valid upto to contract*/
            if ($valid_upto > $today) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_logEntry_Deadline_number = $contract_deadline_days_number->contract_deadline_days;
                } else {
                    //if($contract->contract_type_id == ContractType::ON_CALL){
                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract_logEntry_Deadline_number = 90;
                    } else {
                        $contract_logEntry_Deadline_number = 365;
                    }
                }

                $physicians_contract_data = [];
                $total_duration_log_details = []; // Added to solve the issue in getContract for other payment type by akash.
                $uncompensated_rate = [];
                //if($contract->contract_type_id == ContractType::ON_CALL){
                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $physicians_contract_data = $this->getPhysiciansContractData($contract, $physician->id);
                    //call-coverage by 1254 : added to pass total_duration of all log for agreements
                    $total_duration_log_details = $this->getTotalDurationLogs($contract);
                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $uncompensated_rate = ContractRate::getRate($contract->id, $contract->agreement_start_date, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                    }
                }

                // Sprint 6.1.15 Start
                $get_actions = $this->getContractActions($contract);
                $categories = array();

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    foreach ($get_actions as $get_action) {
                        if ($get_action['category_id'] != 0) {
                            $category = DB::table("action_categories")->select('id', 'name')
                                ->where('id', '=', $get_action['category_id'])
                                ->first();

                            $custom_category = CustomCategoryActions::select('*')
                                ->where('contract_id', '=', $contract->id)
                                ->where('category_id', '=', $get_action['category_id'])
                                ->where('category_name', '!=', null)
                                ->where('is_active', '=', true)
                                ->first();

                            $categories[] = [
                                'category_id' => $get_action['category_id'],
                                'category_name' => $custom_category ? $custom_category->category_name : $category->name
                            ];
                        }
                    }
                    $categories = array_unique($categories, SORT_REGULAR);
                }
                // Sprint 6.1.15 End

                // Sprint 6.1.6 Start
                $recent_logs = $this->getRecentLogs($contract, $physician->id);
                $hourly_summary = array();
                if ($contract->payment_type_id == PaymentType::HOURLY) {
                    $hourly_summary = PhysicianLog::getMonthlyHourlySummaryRecentLogs($recent_logs, $contract, $physician->id);
                }
                // Sprint 6.1.6 End

                $agreement_log_details = [];
                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $agreement_log_details = $this->getAgreementLogs($contract);
                }

                $contracts[] = [
                    "id" => $contract->id,
                    "contract_type_id" => $contract->contract_type_id,
                    "payment_type_id" => $contract->payment_type_id,
                    "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                    "start_date" => format_date($contract->agreement->start_date),
                    "end_date" => format_date($contract->agreement->end_date),
                    "rate" => (string)$contract->rate,
                    "weekday_rate" => (string)$contract->weekday_rate,
                    "weekend_rate" => (string)$contract->weekend_rate,
                    "holiday_rate" => (string)$contract->holiday_rate,
                    "on_call_rate" => (string)$contract->on_call_rate,
                    "called_back_rate" => (string)$contract->called_back_rate,
                    "called_in_rate" => (string)$contract->called_in_rate,
                    "uncompensated_rate" => $uncompensated_rate,
                    "burden_of_call" => $contract->burden_of_call == '1' ? true : false,
                    "on_call_process" => $contract->on_call_process == '1' ? true : false,
                    "statistics" => $this->getContractCurrentStatistics($contract),
                    "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                    "actions" => $get_actions,  // $this->getContractActions($contract),
                    "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                    "log_entry_deadline" => $contract_logEntry_Deadline_number,
                    "recent_logs" => $recent_logs,  // $this->getRecentLogs($contract),
                    "on_call_schedule" => $this->getOnCallSchedule($contract, $physician->id),
                    "physiciansContractData" => $physicians_contract_data,
                    "total_duration_log_details" => $total_duration_log_details,
                    "monthsWithApprovedLogs" => (($api_request == 'old') ? $this->getApprovedLogsMonths($contract) : $this->getApprovedLogsRange($contract, $physician)),
                    "rejectedLogs" => $physician_logs->rejectedLogs($contract->id, $physician->id),
                    "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,   // physicians log the hours for holiday activity on any day
                    "partial_hours" => $contract->partial_hours, // This flag is added for checking the contract is partial ON/OFF
                    "partial_hours_calculation" => $contract->partial_hours_calculation, // This gives the partial hours for calculation for the partial ON contract type.
                    "allow_max_hours" => $contract->allow_max_hours,
                    "payment_frequency_type" => $contract->payment_frequency_type,
                    "categories" => $categories,
                    "hourly_summary" => $hourly_summary,
                    "quarterly_max_hours" => $contract->quarterly_max_hours,
                    "mandate_details" => $contract->mandate_details,
                    "agreement_log_details" => $agreement_log_details,
                    "physician_id" => $physician->id,
                    "state_attestations_monthly" => $contract->state_attestations_monthly,
                    "state_attestations_annually" => $contract->state_attestations_annually
                ];
            }
        }
        return $contracts;
    }

    private function getPhysiciansContractData($contract, $physician_id = 0)
    {

        $agreement = Agreement::findOrFail($contract->agreement_id);
        $contractsInAgreement = Contract::select("contracts.*")->where('contracts.agreement_id', '=', $agreement->id)
            // ->join("physicians", "physicians.id", "=", "contracts.physician_id")
            ->get();

        $contractData = [];
        foreach ($contractsInAgreement as $contractInAgreement) {
            /**
             * getContractsPerContractID currently returns recent logs
             * will update 'method name' or 'return data' as per requirement
             * required for tracking logs of other physicians in on call contract
             */
            if ($contract->id != $contractInAgreement->id) {
                //$contractData[] = $this->getContractsPerContractID($contractInAgreement);
                $cData = $this->getContractsPerContractID($contractInAgreement, $physician_id);
                if (count($cData) > 0) {
                    $contractData[] = $cData;
                }
            }
        }
        return $contractData;
    }

    private function getContractsPerContractID($contract, $physician_id = 0)
    {
        // $physicianId = $contract->physician_id;
        $contractId = $contract->id;
        $contract_physician = PhysicianContracts::where('contract_id', '=', $contract->id)->whereNull('deleted_at')->get();
        $contracts = [];

        if (count($contract_physician) > 0) {
            foreach ($contract_physician as $contract_physician_obj) {
                if ($contract_physician_obj->physician_id) {
                    $physicianId = $contract_physician_obj->physician_id;
                    $physician = Physician::find($physicianId);
                    if ($physician) {
                        $active_contracts = $physician->contracts()
                            ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
                            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                            ->whereRaw("agreements.start_date <= now()")
                            ->whereRaw("agreements.is_deleted = 0")
                            ->get();

                        foreach ($active_contracts as $contract) {
                            //$valid_upto=$contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
                            $valid_upto = $contract->manual_contract_valid_upto;
                            if ($valid_upto == '0000-00-00') {

                                //$valid_upto=$contract->agreement_end_date;/* remove to add valid upto to contract*/
                                $valid_upto = $contract->manual_contract_end_date;
                            }
                            $today = date('Y-m-d');
                            if ($valid_upto > $today) {
                                if ($contract->id == $contractId) {
                                    $data['recent_logs'] = $this->getRecentLogs($contract, $physician->id);
                                    $contracts = [
                                        "id" => $contract->id,
                                        "contract_type_id" => $contract->contract_type_id,
                                        "payment_type_id" => $contract->payment_type_id,
                                        "partial_hours" => $contract->partial_hours,
                                        "partial_hours_calculation" => $contract->partial_hours_calculation,
                                        "name" => contract_name($contract),
                                        "start_date" => format_date($contract->agreement->start_date),
                                        //"end_date" => format_date($contract->agreement->end_date),
                                        "end_date" => format_date($contract->manual_contract_end_date),
                                        "rate" => $contract->rate,
                                        "recent_logs" => $data['recent_logs'],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $contracts;

        return Response::json([
            "status" => ApiController::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    private function getRecentLogs($contract, $physician_id = 0)
    {
        //if($contract->contract_type_id == ContractType::ON_CALL && $contract->deadline_option == 1)
        if (($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) && $contract->deadline_option == '1') {
            $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
            $contract_Deadline = $contract_deadline_days_number->contract_deadline_days;
        } else {
            $contract_Deadline = 90;
        }
        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->whereRaw("physician_logs.date >= date(now() - interval " . $contract_Deadline . " day)")
            ->where('physician_logs.physician_id', '=', $physician_id)
            // @sanket - add condition for approved logs only
            ->orderBy("date", "desc")
            ->orderBy("created_at", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        foreach ($recent_logs as $log) {

            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }

            //if ($contract->contract_type_id == ContractType::ON_CALL) {
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00)
                        $duration_data = "Full Day";
                    elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }
            } else {
                $duration_data = formatNumber($log->duration);
            }

            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $entered_by = $user->full_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->entered_by)->first();
                    $entered_by = $user->full_name;
                }
            }

            if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }

            $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

            $results[] = [
                "id" => $log->id,
                "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                "date" => format_date($log->date),
                "date_selector" => format_date($log->date, "M-Y"),
                "duration" => $duration_data,
                "created" => $created,
                "isSigned" => ($log->signature > 0) ? true : false,
                "note_present" => (strlen($log->details) > 0) ? true : false,
                "note" => (strlen($log->details) > 0) ? $log->details : '',
                "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                "contract_type" => $contract->contract_type_id,
                "payment_type" => $contract->payment_type_id,
                "mandate" => $contract->mandate_details,
                "actions" => Action::getActions($contract),
                "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                "action_id" => $log->action->id,
                "custom_action" => $log->action->name,
                "shift" => $log->am_pm_flag,
                "isApproved" => $isApproved,
                "start_time" => date('g:i A', strtotime($log->start_time)),
                "end_time" => date('g:i A', strtotime($log->end_time))
            ];
        }

        return $results;
    }

    private function getActions($available, $contract_id)
    {
        $results = [];

        //call for Edit contract
        $contract = Contract::findOrFail($contract_id);
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $hospital = $agreement->hospital_id;

        foreach ($available as $item) {

            if ($item->hospital_id == 0 || $item->hospital_id == $hospital) {

                $data = new stdClass();
                $data->id = $item->id;
                $data->name = $item->name;
                $data->category_id = $item->category_id;
                $data->field = "action-{$data->id}-value";

                $data->checked = false;
                $data->hours = formatNumber(0.00);

                // Retrieving the data into textfield of change Lable
                //if($item->contract_type_id == ContractType::ON_CALL) {
                if ($item->payment_type_id == PaymentType::PER_DIEM || $item->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $changeName = OnCallActivity::where("contract_id", "=", $contract_id)
                        ->where("action_id", "=", $item->id)->first();
                    if ($changeName) {
                        $data->changeName = $changeName->name;
                    } else {
                        $data->changeName = '';
                    }
                } else {
                    $data->changeName = '';
                }

                $action = $this->actions()->where('action_id', '=', $item->id)->first();
                if ($action) {
                    $data->checked = true;

                    $data->hours = formatNumber($action->pivot->hours);
                    $results[] = $data;
                    //}elseif($item->contract_type_id == ContractType::ON_CALL) {
                } elseif ($item->payment_type_id == PaymentType::PER_DIEM) {
                    //accept zero rate for per_diem contract by 1254 : checked on call process flag=0  for showing activities to week rate zero
                    if ($this->weekday_rate > 0 || $this->weekend_rate > 0 || $this->holiday_rate > 0 || $this->on_call_process == 0) {
                        if (($data->name == 'Weekday - HALF Day - On Call') || ($data->name == 'Weekend - HALF Day - On Call') || ($data->name == 'Holiday - HALF Day - On Call')) {
                            $data->hours = formatNumber(0.50);
                        }
                        if (($data->name == 'Weekend - FULL Day - On Call') || ($data->name == 'Weekday - FULL Day - On Call') || ($data->name == 'Holiday - FULL Day - On Call')) {
                            $data->hours = formatNumber(1.00);
                        }
                        if (($data->name != 'On-Call') && ($data->name != 'Called-Back') && ($data->name != 'Called-In')) {
                            $results[] = $data;
                        }
                    } else {
                        if (($data->name == 'On-Call') || ($data->name == 'Called-Back') || ($data->name == 'Called-In')) {
                            $data->hours = formatNumber(1.00);
                        }
                        if (($data->name != 'Weekday - HALF Day - On Call') && ($data->name != 'Weekend - HALF Day - On Call') && ($data->name != 'Holiday - HALF Day - On Call') && ($data->name != 'Weekend - FULL Day - On Call') && ($data->name != 'Weekday - FULL Day - On Call') && ($data->name != 'Holiday - FULL Day - On Call')) {
                            $results[] = $data;
                        }
                    }
                } elseif ($item->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $data->hours = formatNumber(1.00);
                    $results[] = $data;
                } else {
                    $results[] = $data;
                }

                //$results[] = $data;
            }
        }

        return $results;
    }

    public static function getTotalDurationLogs($contract)
    {
        //call-coverage-duration  by 1254
        //hospital id is fetched from query because not getting from getcontract() when contracts id's are different for other physician

        $logs = PhysicianLog::select("physician_logs.*", "contracts.partial_hours")
            ->join("contracts", "contracts.id", "physician_logs.contract_id")
            ->where("contracts.agreement_id", "=", $contract->agreement_id)
            ->orderBy("physician_logs.date", "desc")
            ->get();

        $results = [];
        foreach ($logs as $log) {

            $total_duration = 0.0;
            //call-coverage-duration  by 1254
            // if($contract->partial_hours ==1  ){
            // Above mentioned condition is commented because we are getting all logs of agreement and checking against only one contract in agreement. Changes done by Akash
            if ($log->partial_hours == 1) {

                $physicianLog = new PhysicianLog();
                $total_duration = $physicianLog->getTotalDurationForPartialHoursOn($log->date, $log->action_id, $contract->agreement_id);

            }

            $results[] = [
                "action" => $log->action_id,
                "date" => format_date($log->date),
                "duration" => $log->duration,
                "total_duration" => $total_duration,
            ];
        }
        return $results;

    }

    private function getContractActions($contract)
    {
        $results = Action::getActions($contract);

        return $results;
    }

    protected function getAgreementLogs($contract)
    {
        $logs = PhysicianLog::select("physician_logs.*", "contracts.partial_hours")
            ->join("contracts", "contracts.id", "physician_logs.contract_id")
            ->where("contracts.agreement_id", "=", $contract->agreement_id)
            ->whereNull('physician_logs.deleted_at')
            ->orderBy("physician_logs.date", "desc")
            ->get();

        $results = [];
        $duration_data = "";

        foreach ($logs as $log) {
            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        $duration_data = "Full Day";
                    } elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }
            } else {
                $duration_data = formatNumber($log->duration);
            }

            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $entered_by = $user->full_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->entered_by)->first();
                    $entered_by = $user->full_name;
                }
            }

            $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

            $results[] = [
                "id" => $log->id,
                "physician_id" => $log->physician_id,
                // "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                "date" => format_date($log->date),
                // "date_selector" => format_date($log->date,"M-Y"),
                // "date_selector" => $date_selector,
                "duration" => $duration_data,
                "created" => $created,
                "isSigned" => ($log->signature > 0) ? true : false,
                "note_present" => (strlen($log->details) > 0) ? true : false,
                "note" => (strlen($log->details) > 0) ? $log->details : '',
                "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                "payment_type" => $contract->payment_type_id,
                "contract_type" => $contract->contract_type_id,
                "mandate" => $contract->mandate_details,
                // "actions" => Action::getActions($contract),
                "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                "action_id" => $log->action->id,
                "custom_action" => $log->action->name,
                "shift" => $log->am_pm_flag,
                "contract_id" => $log->contract_id
                // "isApproved" => $isApproved,
                // "start_time" => date('g:i A', strtotime($log->start_time)),
                // "end_time" => date('g:i A', strtotime($log->end_time))
            ];
        }
        return $results;
    }

    private function getContractCurrentStatistics($contract)
    {
        $monthNum = 3;
        $dateObj = DateTime::createFromFormat('!m', $monthNum);
        $monthName = $dateObj->format('M');
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
        }

        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        $date_range_res_count = count($date_range_res);

        if ($date_range_res_count == 1) {
            $prior_period_start_date = $date_range_res[0]['start_date'];
            $prior_start_date = date('Y-m-d', strtotime(mysql_date($prior_period_start_date)));
            $date = with(new DateTime($prior_start_date));
            $prior_month_end_date_obj = with(clone $date)->modify('-1 day');
            $prior_month_end_date = $prior_month_end_date_obj->format('Y-m-d');
        }

        $expected_months = months($agreement_data->start_date, $agreement_month->now_date);
        $expected_hours = $contract->expected_hours * $expected_months;

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", mysql_date($prior_month_end_date))
            ->where("physician_logs.date", "<=", now())
            ->sum("duration");

        //if ($contract->contract_type_id == ContractType::MEDICAL_DIRECTORSHIP) {
        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $worked_hours = $contract->logs()
                ->select("physician_logs.*")
                ->where("physician_logs.date", ">=", mysql_date($prior_month_end_date))
                ->where("physician_logs.date", "<=", now())
                ->sum("duration");
            $expected_hours = $contract->expected_hours;
        }

        $remaining_hours = $expected_hours - $worked_hours;
        /*if ($worked_hours < $expected_hours){
            $remaining_hours = $expected_hours - $worked_hours;
        }else{
            $remaining_hours = $worked_hours - $expected_hours;
        }*/

        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">=", mysql_date($agreement_data->start_date))
            ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">=", mysql_date($agreement_data->start_date))
            ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
            ->count();

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => (string)$saved_logs,
            "annual_cap" => formatNumber($contract->annual_cap)
        ];
    }

    private function getPriorContractStatistics($contract)
    {

        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        if (($contract->payment_type_id == PaymentType::STIPEND || $contract->payment_type_id == PaymentType::HOURLY || $contract->payment_type_id == PaymentType::MONTHLY_STIPEND || $contract->payment_type_id == PaymentType::TIME_STUDY || $contract->payment_type_id == PaymentType::PER_UNIT) && $contract->quarterly_max_hours == 1) {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass(Agreement::QUARTERLY);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        } else {
            $payment_type_factory = new PaymentFrequencyFactoryClass();
            $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
            $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
            $prior_month_end_date = $res_pay_frequency['prior_date'];
            $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];
        }

        $date_range_res_count = count($date_range_res);
        if ($date_range_res_count > 4) {
            $prior_period_start_date = $date_range_res[$date_range_res_count - 4]['start_date']; //for fetching last prior 3 periods data
        } else {
            $prior_period_start_date = $date_range_res[0]['start_date'];
            if ($date_range_res_count == 1) {
                $prior_start_date = date('Y-m-d', strtotime(mysql_date($prior_period_start_date)));
                $date = with(new DateTime($prior_start_date));
                $prior_month_end_date_obj = with(clone $date)->modify('-1 day');
                $prior_month_end_date = $prior_month_end_date_obj->format('Y-m-d');
            }
        }

        $start_date = date('Y-m-d', strtotime(mysql_date($agreement_data->start_date)));
        $current_date = date('Y-m-d h:m:s', strtotime(mysql_date($agreement_month->now_date)));
        //$prior_month_end_date = date('Y-m-d', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));
        $prior_month_start_date = date('Y-m-d', strtotime("first day of -1 month"));
        $expected_months = months($agreement_data->start_date, $agreement_month->now_date);
        $expected_hours = $contract->expected_hours;

        $current_month_start_date_time = new DateTime($agreement_month->start_date);

        $worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", $current_month_start_date_time)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");

        $total_prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->sum("duration");
        $remaining_hours = $expected_hours - $worked_hours;
        /*if ($worked_hours < $expected_hours){
            $remaining_hours = $expected_hours - $worked_hours;
        }else{
            $remaining_hours = $worked_hours - $expected_hours;
        }*/

        //$total_hours = $contract->logs()->sum("duration");
        $total_hours = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->sum("duration");
        $saved_logs = $contract->logs()
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $current_date)
            ->count();

        $prior_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", mysql_date($prior_period_start_date))
            ->where("physician_logs.date", "<=", mysql_date($prior_month_end_date))
            //->where("physician_logs.created_at", ">=", $current_month_start_date_time->format('Y-m-d 00:00:00'))
            //->where("physician_logs.created_at", "<=", date('Y-m-d H:i:s'))
            ->sum("duration");

//        if(($contract->payment_type_id == PaymentType::HOURLY)&&($contract->prior_start_date!='0000-00-00'))
        if (($contract->payment_type_id == PaymentType::HOURLY)) {
            /**
             * Take contract start date as prior start date if it is set, otherwise take agreement start date as contract start date.
             */
            if ($contract->prior_start_date != '0000-00-00') {
                $contract_start_date = $contract->prior_start_date;
            } else {
                $contract_start_date = $contract->agreement->start_date;
            }
            $contractFirstYearStartDate = date('Y-m-d', strtotime($contract_start_date));

            if ($contractFirstYearStartDate >= date('Y-m-d', strtotime(mysql_date($prior_period_start_date))) && ($contractFirstYearStartDate <= date('Y-m-d', strtotime(mysql_date($prior_month_end_date))))) {
                $prior_worked_hours = $prior_worked_hours + $contract->prior_worked_hours;
            }
        }

        $prior_month_worked_hours = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.date", ">=", mysql_date($prior_period_start_date))
            ->where("physician_logs.date", "<=", mysql_date($prior_month_end_date))
            //->where("physician_logs.created_at", ">=", $current_month_start_date_time->format('Y-m-d 00:00:00'))
            //->where("physician_logs.created_at", "<=", date('Y-m-d H:i:s'))
            ->sum("duration");

        return [
            "min_hours" => formatNumber($contract->min_hours),
            "max_hours" => formatNumber($contract->max_hours),
            "expected_hours" => formatNumber($expected_hours),
            "worked_hours" => formatNumber($worked_hours),
            "remaining_hours" => $remaining_hours < 0 ? "0.00" : formatNumber($remaining_hours) . "",
            "total_hours" => formatNumber($total_hours),
            "saved_logs" => (string)$saved_logs,
            "prior_worked_hours" => formatNumber($prior_worked_hours),
            "prior_month_worked_hours" => formatNumber($prior_month_worked_hours),
            "total_prior_worked_hours" => formatNumber($total_prior_worked_hours),
            "contract_type_id" => formatNumber($contract->contract_type_id),
            "payment_type_id" => formatNumber($contract->payment_type_id)
        ];
    }

    private function getOnCallSchedule($contract, $physician_id)
    {
        //if ($contract->contract_type_id == ContractType::ON_CALL) {
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            // return physician schedule for current month
            $schedules = DB::table("on_call_schedule")->select("*")
                ->where("physician_id", "=", $physician_id)
                ->where("contract_id", "=", $contract->id)
                //->whereRaw('date >= CURDATE()')
                ->get();

            $return_schedules = [];
            foreach ($schedules as $schedule) {
                $return_schedules[] = [
                    "shift" => ($schedule->physician_type == 1) ? "AM Shift" : "PM Shift",
                    "date" => format_date($schedule->date, "m/d/Y")
                ];
            }
            return $return_schedules;
        }
        return [];
    }

    private function getApprovedLogsMonths($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $approval_process = $contract->agreement->approval_process;
        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $current_month = 0;
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            /*to check the log has been approved by physician or not, if yes then no log should be entered for the month*/
            $isApproved = false;
            //add condition for approval process off
            if ($approval_process == 1) {
                $approval = DB::table('log_approval_history')->select('*')
                    ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
                if (count($approval) > 0) {
                    $isApproved = true;
                }
            } else {
                $isApproved = ($log->approval_date != '0000-00-00' || $log->signature > 0) ? true : false;
            }

            if ($isApproved == true) //if($log->approval_date !='0000-00-00' || $log->signature > 0)
            {
                $d = date_parse_from_format("Y-m-d", $log->date);

                if ($current_month != $d["month"]) {
                    $current_month = $d["month"];
                    $results[] = $d["month"] . '/01/' . $d["year"];
                    //$results[] = $d["month"];
                }
            }
        }
        return $results;
    }

    private function getApprovedLogsRange($contract, $physician)
    {
        // log::info("REcent");
        $agreement_data = Agreement::getAgreementData($contract->agreement);
        $approval_process = $contract->agreement->approval_process;
        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month")); // This line is commented to get the last range prior date for the range.

        if (count($agreement_data->months) > 1) {
            $prior_month_end_date = date('Y-m-d', strtotime($agreement_data->months[count($agreement_data->months) - 1]->end_date));
        } else {
            $prior_month_end_date = date('Y-m-d', strtotime($agreement_data->months[count($agreement_data->months)]->end_date));
        }

        $approved_range = new StdClass;
        $approved_range->months = [];

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where('physician_logs.physician_id', '=', $physician->id)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $current_month = 0;
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            /*to check the log has been approved by physician or not, if yes then no log should be entered for the month*/
            $isApproved = false;
            //add condition for approval process off
            if ($approval_process == 1) {
                $approval = DB::table('log_approval_history')->select('*')
                    ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
                if (count($approval) > 0) {
                    $isApproved = true;
                }
            } else {
                $isApproved = ($log->approval_date != '0000-00-00' || $log->signature > 0) ? true : false;
            }

            if ($isApproved == true) {
                foreach ($agreement_data->months as $index => $date_obj) {
                    $start_date = date("Y-m-d", strtotime($date_obj->start_date));
                    $end_date = date("Y-m-d", strtotime($date_obj->end_date));
                    // log::info('$log->date', array($log->date));
                    // log::info('$start_date', array($start_date));
                    if (strtotime($log->date) >= strtotime($start_date) && strtotime($log->date) <= strtotime($end_date)) {
                        $range_data = [];
                        $range_data['number'] = $index;
                        $range_data['start_date'] = $start_date;
                        $range_data['end_date'] = $end_date;

                        $approved_range->months[$index] = $range_data;
                    }


                }
            }
        }
        // return $results;
        return $approved_range->months;
    }

    private function getContractStatisticsNew($physician, $contract_id, $hospital_id, $api_request)
    {
        $active_contracts = $physician->contracts()
            ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.start_date as agreement_start_date", "agreements.payment_frequency_type")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->whereRaw("practices.hospital_id = $hospital_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
            ->orderBy('sorting_contract_names.practice_id', 'ASC')
            ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
            ->distinct()
            ->get();

        $contracts = [];
        $physician_logs = new PhysicianLog();
        foreach ($active_contracts as $contract) {
            $today = date('Y-m-d');
            /*
              @added on :2018/12/27
              @description: Check if agreement end date is same or different with manual_contract_end_date(newly added condition)
              - if found same get the valid upto date
              - if found different get manual_contract_end_date
              - for old data check if 'manual_contract_end_date' is '0000-00-00' as 'manual_contract_end_date' is added on 2018/12/26
             */

            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                $valid_upto = $contract->manual_contract_end_date;
            }

            if ($valid_upto > $today) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_logEntry_Deadline_number = $contract_deadline_days_number->contract_deadline_days;
                } else {
                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract_logEntry_Deadline_number = 90;
                    } else {
                        $contract_logEntry_Deadline_number = 365;
                    }
                }

                $physicians_contract_data = [];
                $total_duration_log_details = []; // Added to solve the issue in getContract for other payment type by akash.
                $uncompensated_rate = [];

                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    // $physicians_contract_data = $this->getPhysiciansContractData($contract);
                    $physicians_contract_data = Contract::getOtherContractData($contract, $physician);
                    //call-coverage by 1254 : added to pass total_duration of all log for agreements
                    $total_duration_log_details = $this->getTotalDurationLogs($contract);
                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $uncompensated_rate = ContractRate::getRate($contract->id, $contract->agreement_start_date, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                    }
                }

                $get_actions = $this->getContractActions($contract);
                $categories = array();

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    foreach ($get_actions as $get_action) {
                        if ($get_action['category_id'] != 0) {
                            $category = DB::table("action_categories")->select('id', 'name')
                                ->where('id', '=', $get_action['category_id'])
                                ->first();

                            $custom_category = CustomCategoryActions::select('*')
                                ->where('contract_id', '=', $contract->id)
                                ->where('category_id', '=', $get_action['category_id'])
                                ->where('category_name', '!=', null)
                                ->where('is_active', '=', true)
                                ->first();

                            $categories[] = [
                                'category_id' => $get_action['category_id'],
                                'category_name' => $custom_category ? $custom_category->category_name : $category->name
                            ];
                        }
                    }
                    $categories = array_unique($categories, SORT_REGULAR);
                }

                if ($contract_id == 0) {
                    $contract_id = $contract->id;
                }

                $contracts[] = [
                    "id" => $contract->id,
                    "contract_type_id" => $contract->contract_type_id,
                    "payment_type_id" => $contract->payment_type_id,
                    "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                    "start_date" => format_date($contract->agreement->start_date),
                    "end_date" => format_date($contract->agreement->end_date),
                    "rate" => (string)$contract->rate,
                    "weekday_rate" => (string)$contract->weekday_rate,
                    "weekend_rate" => (string)$contract->weekend_rate,
                    "holiday_rate" => (string)$contract->holiday_rate,
                    "on_call_rate" => (string)$contract->on_call_rate,
                    "called_back_rate" => (string)$contract->called_back_rate,
                    "called_in_rate" => (string)$contract->called_in_rate,
                    "uncompensated_rate" => $uncompensated_rate,
                    "burden_of_call" => $contract->burden_of_call == '1' ? true : false,
                    "on_call_process" => $contract->on_call_process == '1' ? true : false,
                    "statistics" => $this->getContractCurrentStatistics($contract),
                    "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                    "actions" => $get_actions,  // $this->getContractActions($contract),
                    "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                    "log_entry_deadline" => $contract_logEntry_Deadline_number,
                    // "recent_logs" => $recent_logs,  // $this->getRecentLogs($contract),
                    "on_call_schedule" => $this->getOnCallSchedule($contract, $physician->id),
                    "physiciansContractData" => $physicians_contract_data,
                    "total_duration_log_details" => $total_duration_log_details,
                    "monthsWithApprovedLogs" => (($api_request == 'old') ? $this->getApprovedLogsMonths($contract) : $this->getApprovedLogsRange($contract, $physician)),
                    "rejectedLogs" => $physician_logs->rejectedLogs($contract->id, $physician->id),
                    "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,   // physicians log the hours for holiday activity on any day
                    "partial_hours" => $contract->partial_hours, // This flag is added for checking the contract is partial ON/OFF
                    "partial_hours_calculation" => $contract->partial_hours_calculation, // This gives the partial hours for calculation for the partial ON contract type.
                    "allow_max_hours" => $contract->allow_max_hours,
                    "payment_frequency_type" => $contract->payment_frequency_type,
                    "categories" => $categories,
                    // "hourly_summary" => $hourly_summary,
                    "quarterly_max_hours" => $contract->quarterly_max_hours,
                    "mandate_details" => $contract->mandate_details,
                    "selected_contract_id" => $contract_id,
                    "state_attestations_monthly" => $contract->state_attestations_monthly,
                    "state_attestations_annually" => $contract->state_attestations_annually,
                    "contract_id" => $contract_id
                ];
            }
        }
        return $contracts;
    }

    public function getOtherContractData($contract, $physician)
    {
        $contractsInAgreement = Contract::select('contracts.*', 'physician_contracts.physician_id as contract_physician_id')
            ->join("physician_contracts", "physician_contracts.contract_id", "=", "contracts.id")
            ->where('contracts.agreement_id', '=', $contract->agreement_id)
//            ->where('physician_contracts.contract_id', '!=', $contract->id)
//            ->where('physician_contracts.physician_id','!=',$physician->id)
            ->whereNull('physician_contracts.deleted_at')
            ->whereNull('contracts.deleted_at')
            ->whereIn('payment_type_id', [3, 5])
            ->get();

        $contractData = [];
        foreach ($contractsInAgreement as $agreement_contract) {

            if ($agreement_contract->id == $contract->id && $agreement_contract->contract_physician_id == $physician->id) {
                continue;
            }

            $data['recent_logs'] = PhysicianLog::getRecentLogs($agreement_contract, $agreement_contract->contract_physician_id);
            $contract_data_temp = [
                "id" => $agreement_contract->id,
                "contract_type_id" => $agreement_contract->contract_type_id,
                "payment_type_id" => $agreement_contract->payment_type_id,
                "name" => contract_name($agreement_contract),
                "start_date" => format_date($agreement_contract->agreement->start_date),
                "end_date" => format_date($agreement_contract->manual_contract_end_date),
                "rate" => $agreement_contract->rate,
                "contract_physician_id" => $agreement_contract->contract_physician_id,
                "recent_logs" => $data['recent_logs']
            ];
            $contractData[] = $contract_data_temp;
        }
        return $contractData;
    }

    private function getContractStatisticsPerformance($physician, $contract_id, $hospital_id, $api_request)
    {
        $contracts = [];
        $physician_logs = new PhysicianLog();
        $contract = [];
        $data = [];

        $contract = $physician->contracts()
            ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.start_date as agreement_start_date", "agreements.payment_frequency_type")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
            // ->join("practices", "practices.id", "=", "contracts.practice_id")
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->where('sorting_contract_names.is_active', '=', 1);     // 6.1.13

        if ($contract_id > 0) {
            $contract = $contract->where('contracts.id', '=', $contract_id);
        }

        $contract = $contract->where(function ($contract) {
            $contract->where('contracts.manual_contract_valid_upto', '>', date('Y-m-d'))
                ->where('contracts.manual_contract_end_date', '>', date('Y-m-d'));
        });

        $contract = $contract->orderBy('sorting_contract_names.practice_id', 'ASC')
            ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
            ->distinct()
            ->first();

        if ($contract) {
            $today = date('Y-m-d');

            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                $valid_upto = $contract->manual_contract_end_date;
            }

            if ($valid_upto > $today) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_logEntry_Deadline_number = $contract_deadline_days_number->contract_deadline_days;
                } else {
                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract_logEntry_Deadline_number = 90;
                    } else {
                        $contract_logEntry_Deadline_number = 365;
                    }
                }

                $physicians_contract_data = [];
                $total_duration_log_details = []; // Added to solve the issue in getContract for other payment type by akash.
                $uncompensated_rate = [];

                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    // $physicians_contract_data = $this->getPhysiciansContractData($contract);
                    $physicians_contract_data = Contract::getOtherContractData($contract, $physician);
                    $total_duration_log_details = $this->getTotalDurationLogs($contract);

                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $uncompensated_rate = ContractRate::getRate($contract->id, $contract->agreement_start_date, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                    }
                }

                $get_actions = $this->getContractActions($contract);
                $categories = array();

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    foreach ($get_actions as $get_action) {
                        if ($get_action['category_id'] != 0) {
                            $category = DB::table("action_categories")->select('id', 'name')
                                ->where('id', '=', $get_action['category_id'])
                                ->first();

                            $custom_category = CustomCategoryActions::select('*')
                                ->where('contract_id', '=', $contract->id)
                                ->where('category_id', '=', $get_action['category_id'])
                                ->where('category_name', '!=', null)
                                ->where('is_active', '=', true)
                                ->first();

                            $categories[] = [
                                'category_id' => $get_action['category_id'],
                                'category_name' => $custom_category ? $custom_category->category_name : $category->name
                            ];
                        }
                    }
                    $categories = array_unique($categories, SORT_REGULAR);
                }

                $active_contract_list = $physician->contracts()
                    ->select("contracts.id as contract_id", "contract_names.name")
                    ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
                    ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                    ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
                    // ->join("practices", "practices.id", "=", "contracts.practice_id")
                    ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
                    ->whereRaw("practices.hospital_id = $hospital_id")
                    ->whereRaw("agreements.is_deleted = 0")
                    ->whereRaw("agreements.start_date <= now()")
                    ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
                    ->where('sorting_contract_names.is_active', '=', 1);     // 6.1.13
                $active_contract_list = $active_contract_list->where(function ($active_contract_list) {
                    $active_contract_list->where('contracts.manual_contract_valid_upto', '>', date('Y-m-d'))
                        ->where('contracts.manual_contract_end_date', '>', date('Y-m-d'));
                });
                $active_contract_list = $active_contract_list->orderBy('sorting_contract_names.practice_id', 'ASC')
                    ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
                    ->distinct()
                    ->get();

                $rejected_log_flag = false;
                foreach ($active_contract_list as $active_contract) {
                    $rejected_logs = $physician_logs->rejectedLogs($active_contract->contract_id, $physician->id);
                    if (count($rejected_logs) > 0) {
                        $rejected_log_flag = true;
                        break;
                    }
                }

                $data = [
                    'contract' => [
                        "id" => $contract->id,
                        "contract_type_id" => $contract->contract_type_id,
                        "payment_type_id" => $contract->payment_type_id,
                        "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                        "start_date" => format_date($contract->agreement->start_date),
                        "end_date" => format_date($contract->agreement->end_date),
                        "rate" => (string)$contract->rate,
                        "weekday_rate" => (string)$contract->weekday_rate,
                        "weekend_rate" => (string)$contract->weekend_rate,
                        "holiday_rate" => (string)$contract->holiday_rate,
                        "on_call_rate" => (string)$contract->on_call_rate,
                        "called_back_rate" => (string)$contract->called_back_rate,
                        "called_in_rate" => (string)$contract->called_in_rate,
                        "uncompensated_rate" => $uncompensated_rate,
                        "burden_of_call" => $contract->burden_of_call == '1' ? true : false,
                        "on_call_process" => $contract->on_call_process == '1' ? true : false,
                        "statistics" => $this->getContractCurrentStatistics($contract),
                        "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                        "actions" => $get_actions,  // $this->getContractActions($contract),
                        "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                        "log_entry_deadline" => $contract_logEntry_Deadline_number,
                        "on_call_schedule" => $this->getOnCallSchedule($contract, $physician->id),
                        "physiciansContractData" => $physicians_contract_data,
                        "total_duration_log_details" => $total_duration_log_details,
                        "monthsWithApprovedLogs" => (($api_request == 'old') ? $this->getApprovedLogsMonths($contract) : $this->getApprovedLogsRange($contract, $physician)),
                        "rejectedLogs" => $physician_logs->rejectedLogs($contract->id, $physician->id),
                        "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,   // physicians log the hours for holiday activity on any day
                        "partial_hours" => $contract->partial_hours, // This flag is added for checking the contract is partial ON/OFF
                        "partial_hours_calculation" => $contract->partial_hours_calculation, // This gives the partial hours for calculation for the partial ON contract type.
                        "allow_max_hours" => $contract->allow_max_hours,
                        "payment_frequency_type" => $contract->payment_frequency_type,
                        "categories" => $categories,
                        "quarterly_max_hours" => $contract->quarterly_max_hours,
                        "mandate_details" => $contract->mandate_details,
                        "state_attestations_monthly" => $contract->state_attestations_monthly,
                        "state_attestations_annually" => $contract->state_attestations_annually
                    ],
                    "contract_list" => $active_contract_list,
                    "selected_contract_id" => $contract->id,
                    "rejected_log_flag" => $rejected_log_flag
                ];
            }
        }

        return $data;
    }

    private function getActiveContracts($physician)
    {

        $active_contracts = $physician->contracts()
            ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
            ->whereRaw("practices.id = $physician->practice_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            //->whereRaw("agreements.end_date >= now()")
            //->whereRaw("agreements.valid_upto >= now()")
            //->whereRaw("if( agreements.valid_upto == '0000-00-00', agreements.end_date, agreements.valid_upto) >= now()")
            //->whereRaw("agreements.valid_upto >= now()")
            //->orWhereRaw((("agreements.valid_upto = '0000-00-00'"))AND("agreements.end_date >= now()")  )
            ->get();
        $contracts = [];
        foreach ($active_contracts as $contract) {
            //$valid_upto = $contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                //$valid_upto = $contract->agreement_end_date;/* remove to add valid upto to contract*/
                $valid_upto = $contract->manual_contract_end_date;
            }
            $today = date('Y-m-d');
            if ($valid_upto > $today) {
                $contracts[] = $contract;
            }
        }
        return $contracts;
    }

    private function getContractsForHospital($hospital_id)
    {
        $active_contracts = DB::table("physicians")
            ->select("contracts.*", "agreements.id as agreement_id", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
            ->join("contracts", "contracts.physician_id", "=", "physicians.id")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("agreements.end_date >= now()")
            ->whereRaw("agreements.hospital_id = " . $hospital_id)
            ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
            //->whereRaw("contracts.contract_type_id = ".ContractType::ON_CALL)
            ->whereRaw("contracts.payment_type_id = " . PaymentType::PER_DIEM)
            //->whereRaw("agreements.valid_upto >= now()")
            //->whereRaw(("agreements.valid_upto >= now()") OR ("agreements.end_date >= now()"))
            ->get();
        return $active_contracts;
    }

    private function getAllManagers($agreement, $type)
    {
        //old approval levels remove 28Aug2018
        /*if($type == LogApproval::contract_manager) {
            $managers = self::select("contracts.contract_CM")
                        ->where("contracts.contract_CM","!=",0);
        }else if($type == LogApproval::financial_manager) {
            $managers = self::select("contracts.contract_FM")
                ->where("contracts.contract_FM","!=",0);
        }
        $managers =$managers->where("contracts.agreement_id","=",$agreement->id)
            ->distinct()
            ->get();*/
        //new approval levels added 28Aug2018
        $managers = ApprovalManagerInfo::select("agreement_approval_managers_info.user_id")->where(function ($pass) {
            $pass->where('agreement_approval_managers_info.initial_review_day', '=', date("d"))
                ->orWhere('agreement_approval_managers_info.final_review_day', '=', date("d"));
        })
            ->where("agreement_approval_managers_info.type_id", "=", $type)
            ->where("agreement_approval_managers_info.is_deleted", "=", '0')
            ->where("agreement_approval_managers_info.opt_in_email_status", "=", '1')
            ->where("agreement_approval_managers_info.agreement_id", "=", $agreement->id)->get();
        return $managers;
    }

    private function getUnapproveLogsDetails($physician)
    {
        $active_contracts = $physician->contracts()
            ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
            ->whereRaw("practices.id = $physician->practice_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->get();

        $contracts = [];
        $physician_logs = new PhysicianLog();
        foreach ($active_contracts as $contract) {
            //$valid_upto=$contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                //$valid_upto=$contract->agreement_end_date;/* remove to add valid upto to contract*/
                $valid_upto = $contract->manual_contract_end_date;
            }
            $today = date('Y-m-d');
            if ($valid_upto > $today) {
                $logs = $this->getLogsToApprove($contract);
                if (count($logs) > 0) {
                    $contracts[] = [
                        "id" => $contract->id,
                        "contract_type_id" => $contract->contract_type_id,
                        "payment_type_id" => $contract->payment_type_id,
                        "name" => contract_name($contract),
                        "logs_to_approve" => $logs,
                        "burden_of_call" => ($contract->burden_of_call == "0") ? false : true
                    ];
                }
            }
        }

        return $contracts;
    }

    private function getLogsToApprove($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            //->where("physician_logs.created_at", "<=", $prior_month_end_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.signature", "=", 0)
            ->where("physician_logs.approval_date", "=", "0000-00-00")
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();

            if (count($approval) > 0) {
                $isApproved = true;
            }

            if (count($approval) < 1) {
                //if ($contract->contract_type_id == ContractType::ON_CALL) {
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    if ($contract->partial_hours == 1) {
                        $duration_data = formatNumber($log->duration);
                    } else {
                        if ($log->duration == 1.00) {
                            $duration_data = "Full Day";
                        } elseif ($log->duration == 0.50) {
                            if ($log->am_pm_flag == 1) {
                                $duration_data = "AM";
                            } else {
                                $duration_data = "PM";
                            }
                        } else {
                            $duration_data = formatNumber($log->duration);
                        }
                    }
                } else {
                    $duration_data = formatNumber($log->duration);
                }

                $entered_by = "Not available.";
                if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('physicians')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->physician_id)->first();
                        $entered_by = $user->full_name;
                    }
                } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                    if ($log->entered_by > 0) {
                        $user = DB::table('users')
                            ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                            ->where('id', '=', $log->entered_by)->first();
                        $entered_by = $user->full_name;
                    }
                }

                $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */

                $results[] = [
                    "id" => $log->id,
                    "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                    "date" => format_date($log->date),
                    "date_selector" => format_date($log->date, "M-Y"),
                    "duration" => $duration_data,
                    "created" => $created,
                    "isSigned" => ($log->signature > 0) ? true : false,
                    "note_present" => (strlen($log->details) > 0) ? true : false,
                    "note" => (strlen($log->details) > 0) ? $log->details : '',
                    "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                    "contract_type" => $contract->contract_type_id,
                    "payment_type" => $contract->payment_type_id,
                    "mandate" => $contract->mandate_details,
                    "actions" => Action::getActions($contract),
                    "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                    "action_id" => $log->action->id,
                    "custom_action" => $log->action->name,
                    "shift" => $log->am_pm_flag,
                    "isApproved" => $isApproved
                ];
            }
        }
        return $results;
    }

    public function getPsaTrackingData($id, $type, $physician_id = 'All')
    {
        return $this->getPsaTrackingDatas($id, $type, $physician_id = 'All');
    }

    private function getPsaContracts($physician)
    {
        $contracts = self::select("contracts.*", "agreements.start_date as start_date", "contract_names.name as contract_name")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("contract_names", "contract_names.id", "=", "contracts.contract_name_id")
            ->where("contracts.payment_type_id", "=", PaymentType::PSA)
            ->where("contracts.physician_id", "=", $physician->id)
            ->whereRaw("agreements.is_deleted=0")
            ->whereRaw("agreements.start_date <= now()")
            //->whereRaw("agreements.end_date >= now()")
            //->whereRaw("contracts.manual_contract_end_date >= now()")
            ->whereNull('contracts.deleted_at')
            ->get();
        //foreach contract break into periods
        foreach ($contracts as $key => $contract) {
            $ContractPsaMetrics = new ContractPsaMetrics();
            $contract_metrics = $ContractPsaMetrics->getBreakdown($contract);
            $contract['metrics'] = $contract_metrics;
            $this->getPsaContractPeriods($contract);
            //get period tracking data
            $this->getPsaContractPeriodData($contract);
            $period_selectors = [];
            foreach ($contract->periods->month_stats as $key => $month_stat) {
                $period_selectors['chart_div_' . $month_stat->contract_id . '_' . $month_stat->period_number] = $month_stat->period;
            }
            $contract['period_selectors'] = $period_selectors;
        }

        $physician->contracts = $contracts;
        return true;
    }

    private function getPsaContractPeriods($contract)
    {
        $now = new DateTime('now');
        $contract_data = new StdClass();
        $contract_data->start_date = format_date($contract->start_date);
        $contract_data->end_date = format_date($contract->manual_contract_end_date);
        $contract_data->term = months($contract->start_date, $contract->manual_contract_end_date);
        $contract_data->months = [];
        $contract_data->start_dates = [];
        $contract_data->end_dates = [];
        $contract_data->dates = [];
        $contract_data->current_month = -1;
        $contract_data->disable = false;
        $start_date = with(new DateTime($contract->start_date))->setTime(0, 0, 0);
        $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
        $number_of_periods = 0;
        for ($i = 0; $i < $contract_data->term; $i++) {
            $month_data = new StdClass();
            $month_data->number = $i + 1;
            $month_data->start_date = $start_date->format('m/d/Y');
            $month_data->end_date = $end_date->format('m/d/Y');
            $month_data->now_date = $now->format('m/d/Y');
            $month_data->current = ($now >= $start_date && $now <= $end_date);
            $month_data->number_of_periods = 1;

            $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
            $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
            $d = date_parse_from_format("m/d/Y", $month_data->start_date);
            $contract_start_date = date_parse_from_format("m/d/Y", $contract_data->start_date);

            $current_month = date('m');
            $current_year = date('Y');

            //limit future months for physician log report and hospital report
            if ($current_year == $d["year"]) {
                if ($current_month >= $d["month"] && $current_year >= $d["year"]) {

                    if ($month_data->current) {
                        $contract_data->current_month = $month_data->number;
                    }

                    //limit current month for physician log report
                    if ($current_month > $d["month"] && $current_year == $d["year"]) {
                        $contract_data->start_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date}";
                        $contract_data->end_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->end_date}";
                        $number_of_periods++;
                    } else if ($current_month == $contract_start_date["month"] && $current_year == $contract_start_date["year"] && $current_year == $d["year"]) {
                        $contract_data->disable = true;
                        $contract_data->start_dates[""] = "No Dates Available";
                        $contract_data->end_dates[""] = "No Dates Available";
                    }

                    $contract_data->dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date} - {$month_data->end_date}";
                    $contract_data->months[$month_data->number] = $month_data;
                }
            } else if ($current_year > $d["year"]) {
                $contract_data->start_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date}";
                $contract_data->end_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->end_date}";
                $contract_data->dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date} - {$month_data->end_date}";
                $contract_data->months[$month_data->number] = $month_data;
                $number_of_periods++;
            }
        }
        //add contract year to date periods
        array_unshift($contract_data->start_dates, "CYTD: " . substr($contract_data->start_dates[1], 3));
        array_unshift($contract_data->end_dates, "CYTD: " . substr($contract_data->end_dates[$number_of_periods], 3));
        array_unshift($contract_data->dates, "CYTD: " . substr($contract_data->start_dates[1], 3) . " - " . substr($contract_data->end_dates[$number_of_periods], 3));
        $month_data_cytd = new StdClass();
        $month_data_cytd->number = 0;
        $month_data_cytd->start_date = substr($contract_data->start_dates[1], 3);
        $month_data_cytd->end_date = substr($contract_data->end_dates[$number_of_periods], 3);
        $month_data_cytd->now_date = $now->format('m/d/Y');
        $month_data_cytd->current = false;
        $month_data_cytd->number_of_periods = $number_of_periods;
        array_unshift($contract_data->months, $month_data_cytd);

        $contract->periods = $contract_data;
        return true;
    }

    private function getPsaContractPeriodData($contract)
    {
        $month_stats = [];
        foreach ($contract->periods->start_dates as $key => $start_date) {
            $month = $contract->periods->months[$key];
            $stats = new StdClass();
            $fmt = numfmt_create(locale_get_default(), NumberFormatter::CURRENCY);
            $stats->comp_per_period_90 = $contract->metrics['comp_per_period_90'] * $month->number_of_periods;
            $stats->comp_per_period_75 = $contract->metrics['comp_per_period_75'] * $month->number_of_periods;
            $stats->comp_per_period_50 = $contract->metrics['comp_per_period_50'] * $month->number_of_periods;
            $stats->comp_string_per_period_90 = numfmt_format_currency($fmt, $contract->metrics['comp_per_period_90'] * $month->number_of_periods, "USD");
            $stats->comp_string_per_period_75 = numfmt_format_currency($fmt, $contract->metrics['comp_per_period_75'] * $month->number_of_periods, "USD");
            $stats->comp_string_per_period_50 = numfmt_format_currency($fmt, $contract->metrics['comp_per_period_50'] * $month->number_of_periods, "USD");
            $stats->wrvu_per_period_90 = $contract->metrics['wrvu_per_period_90'] * $month->number_of_periods;
            $stats->wrvu_per_period_75 = $contract->metrics['wrvu_per_period_75'] * $month->number_of_periods;
            $stats->wrvu_per_period_50 = $contract->metrics['wrvu_per_period_50'] * $month->number_of_periods;
            $stats->wrvu_rate_per_period_90 = $contract->metrics['wrvu_rate_per_period_90'];
            $stats->wrvu_rate_per_period_75 = $contract->metrics['wrvu_rate_per_period_75'];
            $stats->wrvu_rate_per_period_50 = $contract->metrics['wrvu_rate_per_period_50'];
            $stats->comp_per_period = $contract->metrics['comp_per_period'] * $month->number_of_periods;
            $stats->comp_string_per_period = numfmt_format_currency($fmt, $contract->metrics['comp_per_period'] * $month->number_of_periods, "USD");
            $stats->expected_wrvu_per_period = $contract->metrics['expected_wrvu_per_period'] * $month->number_of_periods;
            $stats->period = $contract->periods->dates[$key];
            $stats->start_date = $month->start_date;
            $stats->end_date = $month->end_date;
            $stats->period_number = $month->number;
            $stats->contract_id = $contract->id;

            $start_dt = new DateTime($month->start_date);
            $start_dt = $start_dt->format('Y-m-d');
            $end_dt = new DateTime($month->end_date);
            $end_dt = $end_dt->format('Y-m-d');
            $logs = $contract->logs()
                ->select(DB::raw('SUM(physician_logs.duration) AS duration'))
                ->where("physician_logs.date", ">=", $start_dt)
                //->where("physician_logs.created_at", "<=", $prior_month_end_date)
                ->where("physician_logs.date", "<=", $end_dt)
                ->where("physician_logs.signature", "!=", 0)
                ->where("physician_logs.approval_date", "!=", "0000-00-00")
                ->whereNull('physician_logs.deleted_at')
                ->orderBy("date", "desc")
                ->get();
            if ($logs[0]->duration != null) {
                $duration = $logs[0]->duration;
            } else {
                $duration = 0;
            }
            $stats->duration = $duration;
            $stats->wrvu_gap = $stats->duration - $stats->expected_wrvu_per_period;
            $stats->is_wrvu_payment = $contract->wrvu_payments;
            if ($contract->wrvu_payments) {
                $amount_paid = Amount_paid::select("amountPaid")
                    ->where("contract_id", "=", $contract->id)
                    ->where("start_date", ">=", $start_dt)
                    ->where("end_date", "<=", $end_dt)
                    ->get();
                $stats->actual_comp = 0;
                if (count($amount_paid) > 0) {
                    foreach ($amount_paid as $amount) {
                        $stats->actual_comp = $stats->actual_comp + $amount->amountPaid;
                    }
                }
            } else {
                $stats->actual_comp = (($stats->wrvu_rate_per_period_90 + $stats->wrvu_rate_per_period_75 + $stats->wrvu_rate_per_period_50) / 3) * $stats->duration;
            }
            $stats->actual_comp_string = numfmt_format_currency($fmt, $stats->actual_comp, "USD");
            $stats->comp_gap = $stats->actual_comp - $stats->comp_per_period;
            $stats->comp_gap_string = numfmt_format_currency($fmt, $stats->comp_gap, "USD");

            if ($stats->actual_comp > $stats->comp_per_period) {
                $stats->bar_style = 'green';
            } else {
                $stats->bar_style = 'red';
            }

            $data = [
                'data' => $stats
            ];
            $chart = View::make('dashboard/_psa_tracking_chart')->with($data)->render();
            $stats->chart = $chart;

            $month_stats[$key] = $stats;
        }
        $contract->periods->month_stats = $month_stats;
        return true;
    }

    private function getContractPeriod($contract_id)
    {
        $contract = Contract::findOrFail($contract_id);
        // $agreement_data = Agreement::getAgreementData($contract->agreement_id);
        // log::info('$agreement_date', array($agreement_data));
        $agreement = Agreement::findOrFail($contract->agreement_id);
        $now = new DateTime('now');
        $contract_data = new StdClass();
        $contract->start_date = $agreement->start_date;
        $contract_data->start_date = format_date($contract->start_date);
        $contract_data->end_date = format_date($contract->manual_contract_end_date);
        $contract_data->term = months($contract->start_date, $contract->manual_contract_end_date);
        $contract_data->months = [];
        $contract_data->start_dates = [];
        $contract_data->end_dates = [];
        $contract_data->dates = [];
        $contract_data->current_month = -1;
        $contract_data->disable = false;
        // $start_date = with(new DateTime($contract->start_date))->setTime(0, 0, 0);
        // $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
        $number_of_periods = 0;

        // Below changes are done based on payment frequency of agreement by akash.
        $start_date = with(new DateTime($agreement->payment_frequency_start_date))->setTime(0, 0, 0);

        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement);
        $payment_frequency_range = $res_pay_frequency['date_range_with_start_end_date'];

        foreach ($payment_frequency_range as $index => $date_obj) {
            $start_date = date("m/d/Y", strtotime($date_obj['start_date']));
            $end_date = date("m/d/Y", strtotime($date_obj['end_date']));
            $period_data = new StdClass;
            $period_data->number = $index + 1;
            $period_data->start_date = $start_date;
            $period_data->end_date = $end_date;
            $period_data->now_date = $now->format('m/d/Y');
            $period_data->current = ($now->format('m/d/Y') >= $start_date && $now->format('m/d/Y') <= $end_date);
            $period_data->number_of_periods = 1;

            // if ($period_data->current) {
            //     $contract_data->current_month = $period_data->number;
            // }

            $d = date_parse_from_format("m/d/Y", $period_data->start_date);
            $contract_start_date = date_parse_from_format("m/d/Y", $contract_data->start_date);

            $current_month = date('m');
            $current_year = date('Y');

            //limit future months for physician log report and hospital report
            if ($current_year == $d["year"]) {
                if ($current_month >= $d["month"] && $current_year >= $d["year"]) {

                    if ($period_data->current) {
                        $contract_data->current_month = $period_data->number;
                    }

                    //limit current month for physician log report
                    if ($current_month > $d["month"] && $current_year == $d["year"]) {
                        $contract_data->start_dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->start_date}";
                        $contract_data->end_dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->end_date}";
                        $number_of_periods++;
                    } else if ($current_month == $contract_start_date["month"] && $current_year == $contract_start_date["year"] && $current_year == $d["year"]) {
                        $contract_data->disable = true;
                        $contract_data->start_dates[""] = "No Dates Available";
                        $contract_data->end_dates[""] = "No Dates Available";
                    }

                    $contract_data->dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->start_date} - {$period_data->end_date}";
                    $contract_data->months[$period_data->number] = $period_data;
                }
            } else if ($current_year > $d["year"]) {
                $contract_data->start_dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->start_date}";
                $contract_data->end_dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->end_date}";
                $contract_data->dates["{$period_data->number}"] = "{$period_data->number}: {$period_data->start_date} - {$period_data->end_date}";
                $contract_data->months[$period_data->number] = $period_data;
                $number_of_periods++;
            }
        }

        // for ($i = 0; $i < $contract_data->term; $i++) {
        //     $month_data = new StdClass();
        //     $month_data->number = $i + 1;
        //     $month_data->start_date = $start_date->format('m/d/Y');
        //     $month_data->end_date = $end_date->format('m/d/Y');
        //     $month_data->now_date = $now->format('m/d/Y');
        //     $month_data->current = ($now >= $start_date && $now <= $end_date);
        //     $month_data->number_of_periods = 1;

        //     $start_date = $start_date->modify('+1 month')->setTime(0, 0, 0);
        //     $end_date = with(clone $start_date)->modify('+1 month')->modify('-1 day')->setTime(23, 59, 59);
        //     $d = date_parse_from_format("m/d/Y", $month_data->start_date);
        //     $contract_start_date = date_parse_from_format("m/d/Y", $contract_data->start_date);

        //     $current_month = date('m');
        //     $current_year = date('Y');

        //     //limit future months for physician log report and hospital report
        //     if ($current_year == $d["year"]) {
        //         if ($current_month >= $d["month"] && $current_year >= $d["year"]) {

        //             if ($month_data->current) {
        //                 $contract_data->current_month = $month_data->number;
        //             }

        //             //limit current month for physician log report
        //             if ($current_month > $d["month"] && $current_year == $d["year"]) {
        //                 $contract_data->start_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date}";
        //                 $contract_data->end_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->end_date}";
        //                 $number_of_periods++;
        //             } else if ($current_month == $contract_start_date["month"] && $current_year == $contract_start_date["year"] && $current_year == $d["year"]) {
        //                 $contract_data->disable = true;
        //                 $contract_data->start_dates[""] = "No Dates Available";
        //                 $contract_data->end_dates[""] = "No Dates Available";
        //             }

        //             $contract_data->dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date} - {$month_data->end_date}";
        //             $contract_data->months[$month_data->number] = $month_data;
        //         }
        //     } else if ($current_year > $d["year"]) {
        //         $contract_data->start_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date}";
        //         $contract_data->end_dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->end_date}";
        //         $contract_data->dates["{$month_data->number}"] = "{$month_data->number}: {$month_data->start_date} - {$month_data->end_date}";
        //         $contract_data->months[$month_data->number] = $month_data;
        //         $number_of_periods++;
        //     }
        // }
        // log::info('$contract_data', array($contract_data));
        return $contract_data;

    }

    public function getContractStartDateForYear()
    {
        $result = array();
        $agreement = $this->agreement;
        if (($this->payment_type_id == PaymentType::HOURLY) && ($this->prior_start_date != '0000-00-00')) {
            $contract_start_date = with(new DateTime($this->prior_start_date))->setTime(0, 0, 0);
            $start_date = with(new DateTime($this->prior_start_date))->setTime(0, 0, 0);
        } else {
            $contract_start_date = with(new DateTime($agreement->start_date))->setTime(0, 0, 0);
            $start_date = with(new DateTime($agreement->start_date))->setTime(0, 0, 0);
        }

        $agreement_end_date = with(new DateTime($agreement->end_date))->setTime(0, 0, 0);
        $end_date = with(new DateTime($agreement->end_date))->setTime(0, 0, 0);
        $year_start_date = $start_date;
        $now = new DateTime('now');
        $i = 0;
        while ($now >= $start_date) {
            $add_year_start_date = $i;
            $start_date = $start_date->modify('+1 year')->setTime(0, 0, 0);
            $i++;
        }
        if ($i > 0) {
            $year_start_date = $contract_start_date->modify('+' . $add_year_start_date . ' year')->setTime(0, 0, 0);
        }
        $year_end_date = $agreement_end_date->modify('+' . $add_year_start_date . ' year')->setTime(0, 0, 0);
        if ($end_date <= $year_end_date) {
            $end = $end_date;
        } else {
            $end = $year_end_date;
        }
        $result['year_start_date'] = $year_start_date;
        $result['year_end_date'] = $end;
        return $result;
    }

    public function postWeeklyMaxForSelectedPeriod()
    {
        $contract_id = Request::input('contract_id');
        $selected_date = Request::input('selected_date');
        $max_hours = Request::input('max_hours');
//        log::debug('$contract_id, $selected_date', array($contract_id, $selected_date));
        $result = [];

        if (!empty($contract_id) && !empty($selected_date)) {
            $check_exist = DB::table("rehab_max_hours_per_week")
                ->where('contract_id', '=', $contract_id)
                ->where('start_date', '=', mysql_date($selected_date))
                ->first();

            if ($check_exist) {

                DB::beginTransaction();

                try {

                    $max_hour_update = DB::table("rehab_max_hours_per_week")
                        ->where("contract_id", '=', $contract_id)
                        ->where("start_date", '=', mysql_date($selected_date))
                        ->update(["max_hours_per_week" => $max_hours, 'updated_by' => Auth::user()->id]);

                    if ($max_hour_update) {
                        $data_arr = [
                            'contract_id' => $contract_id,
                            'start_date' => $selected_date,
                            'end_date' => date("Y-m-t", strtotime($selected_date)),
                            'max_hours_per_week' => $max_hours,
                            'is_active' => '1',
                            'created_by' => Auth::user()->id,
                            'updated_by' => Auth::user()->id,
                        ];
                        $max_hour_update_history = DB::table("rehab_max_hours_per_week_history")
                            ->where('contract_id', '=', $contract_id)
                            ->where('start_date', '=', mysql_date($selected_date))
                            ->update(["is_active" => '0', 'updated_by' => Auth::user()->id]);

                        if ($max_hour_update_history) {
                            $insert_max_hour_history = DB::table('rehab_max_hours_per_week_history')->insert($data_arr);
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    log::debug('postWeeklyMaxForSelectedPeriod()-> max hours update DB::transaction failed.');

                    $result['success'] = false;
                    $result['message'] = "Something went wrong. Dynafios will get back to you shortly.";
                    return $result;

                }

                DB::commit();
                $result['success'] = true;
                $result['message'] = "Max hours updated successfully.";
                $result['max_hours_per_week'] = $max_hours;
                return $result;
            } else {
                $data_arr = [
                    'contract_id' => $contract_id,
                    'start_date' => $selected_date,
                    'end_date' => date("Y-m-t", strtotime($selected_date)),
                    'max_hours_per_week' => $max_hours,
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ];

                DB::beginTransaction();

                try {
                    $insert_max_hour = DB::table('rehab_max_hours_per_week')->insert($data_arr);
                    if ($insert_max_hour) {
                        $data_arr['is_active'] = '1';
                    }

                    $insert_max_hour_history = DB::table('rehab_max_hours_per_week_history')->insert($data_arr);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    log::debug('postWeeklyMaxForSelectedPeriod()-> max hours save DB::transaction failed.');

                    $result['success'] = false;
                    $result['message'] = "Something went wrong. Dynafios will get back to you shortly.";
                    return $result;
                }
                $result['success'] = true;
                $result['message'] = "Max hours saved successfully.";
                $result['max_hours_per_week'] = $max_hours;
                return $result;
            }
        } else {
            $result['success'] = false;
            $result['message'] = "Please check the data.";
            return $result;
        }
    }

    public function postAdminHours()
    {
        $contract_id = Request::input('contract_id');
        $selected_date = Request::input('selected_date');
        $admin_hours = Request::input('admin_hours');
//        log::debug('$contract_id, $selected_date', array($contract_id, $selected_date));
        $result = [];

        if (!empty($contract_id) && !empty($selected_date)) {
            $check_exist = DB::table("rehab_admin_hours")
                ->where('contract_id', '=', $contract_id)
                ->where('start_date', '=', mysql_date($selected_date))
                ->first();

            if ($check_exist) {

                DB::beginTransaction();

                try {

                    $admin_hour_update = DB::table("rehab_admin_hours")
                        ->where("contract_id", '=', $contract_id)
                        ->where("start_date", '=', mysql_date($selected_date))
                        ->update(["admin_hours" => $admin_hours, 'updated_by' => Auth::user()->id]);

                    if ($admin_hour_update) {
                        $data_arr = [
                            'contract_id' => $contract_id,
                            'start_date' => $selected_date,
                            'end_date' => date("Y-m-t", strtotime($selected_date)),
                            'admin_hours' => $admin_hours,
                            'is_active' => '1',
                            'created_by' => Auth::user()->id,
                            'updated_by' => Auth::user()->id,
                        ];
                        $admin_hour_update_history = DB::table("rehab_admin_hours_history")
                            ->where('contract_id', '=', $contract_id)
                            ->where('start_date', '=', mysql_date($selected_date))
                            ->update(["is_active" => '0', 'updated_by' => Auth::user()->id]);

                        if ($admin_hour_update_history) {
                            $insert_admin_hour_history = DB::table('rehab_admin_hours_history')->insert($data_arr);
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    log::debug('postAdminHours()-> admin hours update DB::transaction failed.');

                    $result['success'] = false;
                    $result['message'] = "Something went wrong. Dynafios will get back to you shortly.";
                    return $result;

                }

                DB::commit();
                $result['success'] = true;
                $result['message'] = "Admin hours updated successfully.";
                $result['admin_hours'] = $admin_hours;
                return $result;
            } else {
                $data_arr = [
                    'contract_id' => $contract_id,
                    'start_date' => $selected_date,
                    'end_date' => date("Y-m-t", strtotime($selected_date)),
                    'admin_hours' => $admin_hours,
                    'created_by' => Auth::user()->id,
                    'updated_by' => Auth::user()->id,
                ];

                DB::beginTransaction();

                try {
                    $insert_admin_hour = DB::table('rehab_admin_hours')->insert($data_arr);
                    if ($insert_admin_hour) {
                        $data_arr['is_active'] = '1';
                    }

                    $insert_admin_hour_history = DB::table('rehab_admin_hours_history')->insert($data_arr);

                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollback();
                    log::debug('postAdminHours()-> admin hours save DB::transaction failed.');

                    $result['success'] = false;
                    $result['message'] = "Something went wrong. Dynafios will get back to you shortly.";
                    return $result;
                }
                $result['success'] = true;
                $result['message'] = "Admin hours saved successfully.";
                $result['admin_hours'] = $admin_hours;
                return $result;
            }
        } else {
            $result['success'] = false;
            $result['message'] = "Please check the data.";
            return $result;
        }
    }

    /*function for fetching recent logs */

    public function getContractRecentLogs($contract)
    {
        $contract_Deadline = $contract->contract_logentry_deadline_number;
        $contract_actions = Action::getActions($contract);
        //added for get changed names
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id")->toArray();
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->whereRaw("physician_logs.date > date(now() - interval " . $contract_Deadline . " day)")
            ->orderBy("date", "desc")
            ->get();
        $results = [];
        $duration_data = "";
        $log_dates = [];
        foreach ($recent_logs as $log) {
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }

            if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        if ($log->action->name == "On-Call" || $log->action->name == "Called-Back" || $log->action->name == "Called-In") {
                            $duration_data = "On call full Day";
                        } else {
                            $duration_data = "Full Day";
                        }
                    } elseif ($log->duration == 0.50) {
                        if ($log->am_pm_flag == 1) {
                            $duration_data = "AM";
                        } else {
                            $duration_data = "PM";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }
            } else if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                if ($contract->partial_hours == true) {
                    $duration_data = formatNumber($log->duration);
                } else {
                    if ($log->duration == 1.00) {
                        if ($log->action->name == "On-Call/Uncompensated") {
                            $duration_data = "On call Uncompensated Day";
                        } else {
                            $duration_data = "On call Uncompensated Day";
                        }
                    } else {
                        $duration_data = formatNumber($log->duration);
                    }
                }
            } else {
                $duration_data = formatNumber($log->duration);
            }
            $entered_by = "Not available.";
            if ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->entered_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $entered_by = $user->full_name;
                }
            } elseif ($log->entered_by_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->entered_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->entered_by)->first();
                    $entered_by = $user->full_name;
                }
            }
            $approved_by = "-";
            if ($log->approving_user_type == PhysicianLog::ENTERED_BY_PHYSICIAN) {
                if ($log->approved_by > 0) {
                    $user = DB::table('physicians')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->physician_id)->first();
                    $approved_by = $user->full_name;
                }
            } elseif ($log->approving_user_type == PhysicianLog::ENTERED_BY_USER) {
                if ($log->approved_by > 0) {
                    $user = DB::table('users')
                        ->select(DB::raw("CONCAT(last_name, ', ' ,first_name ) AS full_name"))
                        ->where('id', '=', $log->approved_by)->first();
                    $approved_by = $user->full_name;
                }
            }
            $total_duration = 0.0;
            if ($contract->partial_hours == 1) {
                $physicianLog = new PhysicianLog();
                $total_duration = $physicianLog->getTotalDurationForPartialHoursOn($log->date, $log->action_id, $contract->agreement_id);
            }

            if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                $custom_action = CustomCategoryActions::select('*')
                    ->where('contract_id', '=', $contract->id)
                    ->where('action_id', '=', $log->action_id)
                    ->where('action_name', '!=', null)
                    ->where('is_active', '=', true)
                    ->first();

                $log->action->name = $custom_action ? $custom_action->action_name : $log->action->name;
            }

            $created = $log->timeZone != '' ? $log->timeZone : format_date($log->created_at, "m/d/Y h:i A"); /*show timezone */
            $results[] = [
                "id" => $log->id,
                "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                "date" => format_date($log->date),
                "date_selector" => format_date($log->date, "M-Y"),
                "duration" => $duration_data,
                "created" => $created,
                "isSigned" => ($log->signature > 0) ? true : false,
                "note_present" => (strlen($log->details) > 0) ? true : false,
                "note" => (strlen($log->details) > 0) ? $log->details : '',
                "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                "approvedBy" => (strlen($approved_by) > 0) ? $approved_by : '-',
                "approvedDate" => ($log->approval_date == '0000-00-00') ? 'NotApproved' : 'Approved',
                "payment_type_id" => $contract->payment_type_id,
                "actions" => $contract_actions,
                "action_id" => $log->action_id,
                "custom_action" => $log->action->name,
                "contract_type" => $contract->contract_type_id,
                "shift" => $log->am_pm_flag,
                "mandate" => $contract->mandate_details,
                "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                "log_physician_id" => $log->physician_id,
                "isApproved" => $isApproved,
                "partial_hours" => $contract->partial_hours,
                "contract_id" => $contract->id,
                "total_duration" => $total_duration,
                "partial_hours_calculation" => $contract->partial_hours_calculation,
                "start_time" => date('g:i A', strtotime($log->start_time)),
                "end_time" => date('g:i A', strtotime($log->end_time))
            ];

        }
        return $results;
    }

    private function getContractStatisticsDetail($physician, $hospital_id, $api_request)
    {
        $active_contracts = $physician->contracts()
            ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.start_date as agreement_start_date", "agreements.payment_frequency_type")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->whereRaw("practices.hospital_id = $hospital_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
            ->orderBy('sorting_contract_names.practice_id', 'ASC')
            ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
            ->distinct()
            ->get();

        $contracts = [];
        $physician_logs = new PhysicianLog();
        foreach ($active_contracts as $contract) {
            $today = date('Y-m-d');
            /*
              @added on :2018/12/27
              @description: Check if agreement end date is same or different with manual_contract_end_date(newly added condition)
              - if found same get the valid upto date
              - if found different get manual_contract_end_date
              - for old data check if 'manual_contract_end_date' is '0000-00-00' as 'manual_contract_end_date' is added on 2018/12/26
             */

            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                $valid_upto = $contract->manual_contract_end_date;
            }

            if ($valid_upto > $today) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_logEntry_Deadline_number = $contract_deadline_days_number->contract_deadline_days;
                } else {
                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract_logEntry_Deadline_number = 90;
                    } else {
                        $contract_logEntry_Deadline_number = 365;
                    }
                }

                $physicians_contract_data = [];
                $total_duration_log_details = []; // Added to solve the issue in getContract for other payment type by akash.
                $uncompensated_rate = [];

                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $uncompensated_rate = ContractRate::getRate($contract->id, $contract->agreement_start_date, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                    }
                }

                $contracts[] = [
                    "id" => $contract->id,
                    "payment_type_id" => $contract->payment_type_id,
                    "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                    "start_date" => format_date($contract->agreement->start_date),
                    "end_date" => format_date($contract->agreement->end_date),
                    "rate" => (string)$contract->rate,
                    "weekday_rate" => (string)$contract->weekday_rate,
                    "weekend_rate" => (string)$contract->weekend_rate,
                    "holiday_rate" => (string)$contract->holiday_rate,
                    "on_call_rate" => (string)$contract->on_call_rate,
                    "called_back_rate" => (string)$contract->called_back_rate,
                    "called_in_rate" => (string)$contract->called_in_rate,
                    "uncompensated_rate" => $uncompensated_rate,
                    "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                ];
            }
        }
        return $contracts;
    }

    public static function getAllRejectedLogs($physician, $hospital_id){
        $active_contracts = $physician->contracts()
            ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.start_date as agreement_start_date", "agreements.payment_frequency_type")
            ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
            ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
            ->join("practices", "practices.id", "=", "physician_contracts.practice_id")
            ->whereRaw("practices.hospital_id = $hospital_id")
            ->whereRaw("agreements.is_deleted = 0")
            ->whereRaw("agreements.start_date <= now()")
            ->whereRaw("contracts.end_date <= '0000-00-00 00:00:00'")
            ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
            ->orderBy('sorting_contract_names.practice_id', 'ASC')
            ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
            ->distinct()
            ->get();

        $rejected_logs_array = [];
        $physician_logs = new PhysicianLog();
        $contracts = [];
        $api_request = 'old';
        foreach ($active_contracts as $contract) {
            $today = date('Y-m-d');
            $valid_upto = $contract->manual_contract_valid_upto;
            if ($valid_upto == '0000-00-00') {
                $valid_upto = $contract->manual_contract_end_date;
            }

            // if ($valid_upto > $today) {
            //     $rejected_logs = $physician_logs->rejectedLogs($contract->id, $physician->id);

            //     foreach($rejected_logs as $rejected_log){
            //         array_push($rejected_logs_array, $rejected_log);
            //     }
            // }

            if ($valid_upto > $today) {
                if ($contract->deadline_option == '1') {
                    $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                    $contract_logEntry_Deadline_number = $contract_deadline_days_number->contract_deadline_days;
                } else {
                    if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $contract_logEntry_Deadline_number = 90;
                    } else {
                        $contract_logEntry_Deadline_number = 365;
                    }
                }

                $physicians_contract_data = [];
                $total_duration_log_details = []; // Added to solve the issue in getContract for other payment type by akash.
                $uncompensated_rate = [];

                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $physicians_contract_data = Contract::getOtherContractData($contract, $physician);
                    //call-coverage by 1254 : added to pass total_duration of all log for agreements
                    $total_duration_log_details = self::getTotalDurationLogs($contract);
                    if ($contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                        $uncompensated_rate = ContractRate::getRate($contract->id, $contract->agreement_start_date, contractRate::ON_CALL_UNCOMPENSATED_RATE);
                    }
                }

                $get_actions = self::getContractActions($contract);
                $categories = array();

                if ($contract->payment_type_id == PaymentType::TIME_STUDY) {
                    foreach ($get_actions as $get_action) {
                        if ($get_action['category_id'] != 0) {
                            $category = DB::table("action_categories")->select('id', 'name')
                                ->where('id', '=', $get_action['category_id'])
                                ->first();

                            $custom_category = CustomCategoryActions::select('*')
                                ->where('contract_id', '=', $contract->id)
                                ->where('category_id', '=', $get_action['category_id'])
                                ->where('category_name', '!=', null)
                                ->where('is_active', '=', true)
                                ->first();

                            $categories[] = [
                                'category_id' => $get_action['category_id'],
                                'category_name' => $custom_category ? $custom_category->category_name : $category->name
                            ];
                        }
                    }
                    $categories = array_unique($categories, SORT_REGULAR);
                }

                $rejected_logs = $physician_logs->rejectedLogs($contract->id, $physician->id);

                if(count($rejected_logs) > 0){
                    $contracts[] = [
                        "id" => $contract->id,
                        "contract_type_id" => $contract->contract_type_id,
                        "payment_type_id" => $contract->payment_type_id,
                        "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                        "start_date" => format_date($contract->agreement->start_date),
                        "end_date" => format_date($contract->agreement->end_date),
                        "rate" => (string)$contract->rate,
                        "weekday_rate" => (string)$contract->weekday_rate,
                        "weekend_rate" => (string)$contract->weekend_rate,
                        "holiday_rate" => (string)$contract->holiday_rate,
                        "on_call_rate" => (string)$contract->on_call_rate,
                        "called_back_rate" => (string)$contract->called_back_rate,
                        "called_in_rate" => (string)$contract->called_in_rate,
                        "uncompensated_rate" => $uncompensated_rate,
                        "burden_of_call" => $contract->burden_of_call == '1' ? true : false,
                        "on_call_process" => $contract->on_call_process == '1' ? true : false,
                        "statistics" => self::getContractCurrentStatistics($contract),
                        "priorMonthStatistics" => self::getPriorContractStatistics($contract),
                        "actions" => $get_actions,  // $this->getContractActions($contract),
                        "custom_action_enabled" => $contract->custom_action_enabled == 0 ? false : true,
                        "log_entry_deadline" => $contract_logEntry_Deadline_number,
                        // "recent_logs" => $recent_logs,  // $this->getRecentLogs($contract),
                        "on_call_schedule" => self::getOnCallSchedule($contract, $physician->id),
                        "physiciansContractData" => $physicians_contract_data,
                        "total_duration_log_details" => $total_duration_log_details,
                        "monthsWithApprovedLogs" => (($api_request == 'old') ? self::getApprovedLogsMonths($contract) : self::getApprovedLogsRange($contract, $physician)),
                        "rejectedLogs" => $rejected_logs,
                        "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,   // physicians log the hours for holiday activity on any day
                        "partial_hours" => $contract->partial_hours, // This flag is added for checking the contract is partial ON/OFF
                        "partial_hours_calculation" => $contract->partial_hours_calculation, // This gives the partial hours for calculation for the partial ON contract type.
                        "allow_max_hours" => $contract->allow_max_hours,
                        "payment_frequency_type" => $contract->payment_frequency_type,
                        "categories" => $categories,
                        // "hourly_summary" => $hourly_summary,
                        "quarterly_max_hours" => $contract->quarterly_max_hours,
                        "mandate_details" => $contract->mandate_details,
                        // "selected_contract_id" => $contract_id,
                        "state_attestations_monthly" => $contract->state_attestations_monthly,
                        "state_attestations_annually" => $contract->state_attestations_annually
                    ];
                }
            }
        }

        return $contracts;
    }

    public function getContractApprovers($contract)
    {

        $id = $contract->id;
        if ($contract->default_to_agreement == '1') {
            $contract_id = 0;//when we are fetching all approval managers same as agreement
        } else {
            $contract_id = $id;// when we are fetching approval managers for the specific contract
        }

        $approval_manager_type = ApprovalManagerType::where('is_active', '=', '1')->pluck('manager_type', 'approval_manager_type_id');
        $approval_manager_type[0] = 'NA';

        $approvalManagerInfo = ApprovalManagerInfo::where('agreement_id', '=', $contract->agreement_id)
            ->where('contract_id', "=", $contract_id)
            ->where('is_deleted', '=', '0')
            ->orderBy('level')->get();

        return $approvalManagerInfo;

    }
}
