<?php

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\AccessToken;
use App\Physician;
use App\User;
use App\Group;
use App\Contract;
use App\Signature;
use App\Agreement;
use App\PaymentType;
use App\Action;
use App\OnCallActivity;
use App\PhysicianDeviceToken;
use App\ContractDeadlineDays;
use App\PhysicianLog;
use App\LogApproval;
use App\ApprovalManagerInfo;
use App\ProxyApprovalDetails;
use App\PhysicianPractices;
use App\Practice;
use App\ContractType;
use App\OtpHistory;
use App\customClasses\PaymentFrequencyFactoryClass;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use App\HospitalOverrideMandateDetails;
use App\Services\EmailQueueService;
use App\customClasses\EmailSetup;
use App\CustomCategoryActions;
use App\AttestationQuestion;
use App\Services\ProductivMDService;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\Validations\PhysicianValidation;
use PragmaRX\Version\Package\Version;
use stdClass;
use App\Http\Controllers\Validations\UserValidation;
use Dynafios\Managers\PhysicianLogManager;
use App\Services\CognitoSSOService;

class ApiController extends BaseController
{
    const STATUS_FAILURE = 0;
    const STATUS_SUCCESS = 1;
    const UNSUCCESSFUL_LOGIN_ATTEMPTS_LIMIT = 10;

    public function postAuthenticate()
    {
        $email = '';
        $auth = false;
        $awsCongnitoSSOToken = Request::input("awsCognitoSSOToken", null);
        $physician = null;
        $user = null;
        // if cognito token is present, validate that and don't check the password
        if ($awsCongnitoSSOToken !== null) {
            $cognitoIdentityObject = CognitoSSOService::verifyToken($awsCongnitoSSOToken);
            Log::debug('Cognito Identify Object: ', array($cognitoIdentityObject));
            // if cognito token is verified, get email from that and pull out the physician
            if ($cognitoIdentityObject->isVerified) {
                $email = $cognitoIdentityObject->email;
                $physician = Physician::where("email", "=", $email)->first();
                $auth = true;
            }
        } else {
            $email = Request::input("email");
            $password = Request::input("password");
            $physician = Physician::where("email", "=", $email)->first();

            if ($physician) {
                $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
                if ($user) {
                    if ($user->getLocked() == 1) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.account_locked")
                        ]);
                    }
                }
                // check password only if user is a physician because this API is meant for mobile login which is only for physicians
                $auth = Hash::check($password, $physician->password);
            }

        }


        if ($physician && $auth) {
            $access_token = AccessToken::generate($physician);
            $access_token->save();

            if (Request::has("deviceToken") && Request::has("deviceType")) {
                $deviceToken = Request::input("deviceToken");
                if (Request::input("deviceType") != '' && Request::input("deviceToken") != '') {
                    $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician->id)->first();
                    if ($device_token) {
                        $device_token->device_token = $deviceToken;
                        $device_token->device_type = Request::input("deviceType");
                        $device_token->save();
                    } else {
                        $PhysicianDeviceToken = new PhysicianDeviceToken();
                        $PhysicianDeviceToken->physician_id = $physician->id;
                        $PhysicianDeviceToken->device_type = Request::input("deviceType");
                        $PhysicianDeviceToken->device_token = $deviceToken;
                        $PhysicianDeviceToken->save();
                    }
                }
            }

            if ($user) {
                $user->setUnsuccessfulLoginAttempts(0);
                $user->save();
            }

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "token" => $access_token->key
            ]);
        }

        //if we are here, it means login was unsuccessful so increase failure count
        if ($user) {
            $unsuccessful_login_attempts = $user->getUnsuccessfulLoginAttempts();
            $user->setUnsuccessfulLoginAttempts($unsuccessful_login_attempts + 1);
            if (($unsuccessful_login_attempts + 1) >= self::UNSUCCESSFUL_LOGIN_ATTEMPTS_LIMIT) {
                $user->setLocked(1);
            }
            $user->save();
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getProfile()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token) {
            $physician = $access_token->physician;
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "id" => $physician->id,
                "name" => "{$physician->first_name} {$physician->last_name}",
                "specialty" => $physician->specialty->name,
                "npi" => $physician->npi,
                "email" => $physician->email,
                "phone" => $physician->phone
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function postProfile()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token || $token === "dashboard") {
            if ($token != "dashboard") {
                $physician = $access_token->physician;
                $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
            } else {
                $physician = Physician::findOrFail(Request::input("id"));
                $user = User::findOrFail(Request::input("user_id"));
            }

            $new_password = Request::input("new_password");
            $confirmed_password = Request::input("confirmed_password");

            if ($new_password != $confirmed_password) {
                if ($token != "dashboard") {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => Lang::get("api.password_mismatch")
                    ]);
                } else {
                    return Redirect::back()->with([
                        "error" => Lang::get("api.password_mismatch")
                    ]);
                }
            }

            if (strlen($new_password) < 8) {
                if ($token != "dashboard") {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => Lang::get("api.password_length")
                    ]);
                } else {
                    return Redirect::back()->with([
                        "error" => Lang::get("api.password_length")
                    ]);
                }
            }

            $validation = new PhysicianValidation();
            if (!$validation->validatePasswordEdit(Request::input())) {
                if ($token != "dashboard") {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => $validation->messages()->first()
                    ]);
                } else {
                    return Redirect::back()->with([
                        "error" => $validation->messages()->first()
                    ]);
                }
            }

            if (Hash::check($new_password, $physician->password)) {
                if ($token != "dashboard") {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => 'Your new password must be different than your current password.'
                    ]);
                } else {
                    return Redirect::back()->with(['error' => 'Your new password must be different than your current password.']);
                }
            }

            $physician->password = Hash::make($new_password);
            $physician->setPasswordText($new_password);
            $physician->save();

            $user->password = Hash::make($new_password);
            $user->setPasswordText($new_password);
            $expiration_date = $user->getPhysicianHospitalPasswordExpirationDate($user->id);
            if (!$expiration_date) {
                $expiration_date = new DateTime("+12 months");
            }
            $user->password_expiration_date = $expiration_date;
            $user->save();

            if ($token != "dashboard") {
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get("api.password_changed")
                ]);
            } else {
                //return Redirect::back()->with([
                //    "success" => Lang::get("api.password_changed")
                //]);
                if (Request::has('new_password')) {
                    return Redirect::route('auth.logout')->with(['success' => 'Password successfully changed. Please login with your new password.']);
                } else {
                    return Redirect::back()->with(["success" => Lang::get("api.password_changed")]);
                }
            }

        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getContracts()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        //Physician to multiple hospital by 1254
        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {

                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $contracts = $contract->getContractDetails($physician, "all", $hospital_id);
            //log::info("contracts",array($contracts));
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getHospitalFromPhysician($physician)
    {
        // $contract_detail = Contract::where('physician_id','=',$physician->id)->first();
        $contract_detail = Contract::select('contracts.*')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->where('physician_contracts.physician_id', '=', $physician->id)->first();
        $agreement_detail = Agreement::findOrFail($contract_detail->agreement_id);
        return $agreement_detail->hospital_id;
    }

    public function getContractsRecent()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        //Physician to multiple hospital by 1254
        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {

                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $contracts = $contract->getContractDetails($physician, "all", $hospital_id, 'recent');
            //log::info("contracts",array($contracts));
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getContractsNew()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        $hospital_id = Request::input("hospital_id");
        $contract_id = Request::input("contract_id", 0);

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $contracts = $contract->getContractDetailsNew($physician, $contract_id, $hospital_id, 'recent');

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getContractsPerformance()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        $hospital_id = Request::input("hospital_id");
        $contract_id = Request::input("contract_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $contracts = $contract->getContractDetailsPerformance($physician, $contract_id, $hospital_id, 'recent');

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getHospitals()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token) {
            $physician = $access_token->physician;


            $hospitals = PhysicianPractices::getHospitals($physician->id);

            //log::info("hospitals",array($hospitals));
            // $contract = new Contract();
            // $contracts = $contract->getContractDetails($physician,"all");
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "hospitals" => $hospitals
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    /* required for tracking logs of other physicians in on call contract*/

    public function getOnCallSchedule($contract, $physician_id)
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

    public function getPriorMonthLogsRecent()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }
            //One to Many changes by 1254
            $active_contracts = $physician->contracts()
                ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date", "agreements.payment_frequency_type")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                //->join("physician_practices","physician_practices.hospital_id","=","agreements.hospital_id")
                //->where("physician_practices.hospital_id","=",$hospital_id)
                ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
                ->whereRaw("practices.hospital_id = $hospital_id")
                // ->whereRaw("contracts.practice_id=practices.id")
                //->whereNull("physician_practices.deleted_at")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
                ->whereRaw("practices.id = physician_contracts.practice_id")
                //->whereRaw("agreements.valid_upto >= now()")
                //->whereRaw(("agreements.valid_upto >= now()") OR ("agreements.end_date >= now()"))
                ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
                ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
                ->distinct()
                ->get();

            $contracts = [];
            foreach ($active_contracts as $contract) {
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
                $today = date('Y-m-d');
                if ($valid_upto > $today) {
                    $recent_logs = $this->getPriorMonthUnapprovedLogsRecent($contract, $physician->id);
                    $date_selectors = [];
                    $renumbered = [];
                    array_push($date_selectors, 'All');
                    if (count($recent_logs) > 0) {
                        foreach ($recent_logs as $log) {
                            array_push($date_selectors, $log['date_selector']);
                        }
                        $date_selectors = array_unique($date_selectors);
                        $renumbered = array_merge($date_selectors, array());
                        json_encode($renumbered);
                    }

                    // Sprint 6.1.7 Start
                    $hourly_summary = array();
                    if ($contract->payment_type_id == PaymentType::HOURLY) {
                        $hourly_summary = PhysicianLog::getMonthlyHourlySummary($recent_logs, $contract, $physician->id);
                    }
                    // Sprint 6.1.7 End

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
                        "statistics" => $this->getContractStatistics($contract),
                        "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                        "actions" => $this->getContractActions($contract),
                        "recent_logs" => $recent_logs,
                        "date_selectors" => $renumbered,
                        "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,       // physicians log the hours for holiday activity on any day
                        "hourly_summary" => $hourly_summary,     // Sprint 6.1.7
                        "state_attestations_monthly" => $contract->state_attestations_monthly,      // Sprint 6.1.1.8
                        "state_attestations_annually" => $contract->state_attestations_annually    // Sprint 6.1.1.8
                    ];
                }
            }

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    protected function getPriorMonthUnapprovedLogsRecent($contract, $physician_id)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        // $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        // $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        /**
         * #payment_frequency
         * Below factory function is used for fetching the date range and prior_date for respective payment frequency.
         */
        $payment_type_factory = new PaymentFrequencyFactoryClass();
        $payment_type_obj = $payment_type_factory->getPaymentFactoryClass($agreement_data->payment_frequency_type);
        $res_pay_frequency = $payment_type_obj->calculateDateRange($agreement_data);
        $prior_month_end_date = $res_pay_frequency['prior_date'];
        $date_range_res = $res_pay_frequency['date_range_with_start_end_date'];

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
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
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            $isApproved = false;
            if (count($approval) > 0) {
                $isApproved = true;
            }

            if (count($approval) < 1) {

                //if ($contract->contract_type_id == ContractType::ON_CALL) {
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

                foreach ($date_range_res as $date_range_obj) {
                    if (strtotime($log->date) >= strtotime($date_range_obj['start_date']) && strtotime($log->date) <= strtotime($date_range_obj['end_date'])) {
                        $date_selector = format_date($date_range_obj['start_date'], "m/d/Y") . " - " . format_date($date_range_obj['end_date'], "m/d/Y");
                    }
                }

                $results[] = [
                    "id" => $log->id,
                    "action" => array_key_exists($log->action_id, $changedActionNamesList) ? $changedActionNamesList[$log->action_id] : $log->action->name,
                    "date" => format_date($log->date),
                    // "date_selector" => format_date($log->date,"M-Y"),
                    "date_selector" => $date_selector,
                    "duration" => $duration_data,
                    "created" => $created,
                    "isSigned" => ($log->signature > 0) ? true : false,
                    "note_present" => (strlen($log->details) > 0) ? true : false,
                    "note" => (strlen($log->details) > 0) ? $log->details : '',
                    "enteredBy" => (strlen($entered_by) > 0) ? $entered_by : 'Not available.',
                    "payment_type" => $contract->payment_type_id,
                    "contract_type" => $contract->contract_type_id,
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
        }
        return $results;
    }

    private function getContractStatistics($contract)
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

        //if ($contract->contract_type_id == ContractType::MEDICAL_DIRECTORSHIP) {
        if ($contract->payment_type_id == PaymentType::HOURLY) {
            $worked_hours = $contract->logs()
                ->select("physician_logs.*")
                ->where("physician_logs.date", ">=", mysql_date($agreement_month->start_date))
                ->where("physician_logs.date", "<=", mysql_date($agreement_month->now_date))
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
            "saved_logs" => $saved_logs
        ];
    }

    private function getPriorContractStatistics($contract)
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
            ->where("physician_logs.date", ">=", $start_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            //->where("physician_logs.created_at", ">=", $current_month_start_date_time->format('Y-m-d 00:00:00'))
            //->where("physician_logs.created_at", "<=", date('Y-m-d H:i:s'))
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

    private function getContractActions($contract)
    {
        $results = Action::getActions($contract);
        /*$activities = $contract->actions()
            ->whereIn("action_type_id", [1, 2])
            ->orderBy("action_type_id", "asc")
            ->orderBy("name", "asc")
            ->get();

        $results = [];
        foreach ($activities as $activity) {
            $results[] = [
                "id" => $activity->id,
                "name" => $activity->name,
                "action_type_id" => $activity->action_type_id,
                "action_type" => $activity->action_type->name,
                "duration" => DB::table("action_contract")
                    ->where("contract_id", "=", $contract->id)
                    ->where("action_id", "=", $activity->id)
                    ->pluck("hours")
            ];
        }*/

        return $results;
    }

    public function getPriorMonthLogsNew()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {

                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $contracts = $contract->getPriorMonthLogs($physician, $hospital_id);
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getPriorMonthLogs()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }
            //One to Many changes by 1254
            $active_contracts = $physician->contracts()
                ->select("contracts.*", "practices.name as practice_name", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                //->join("physician_practices","physician_practices.hospital_id","=","agreements.hospital_id")
                //->where("physician_practices.hospital_id","=",$hospital_id)
                ->join("practices", "practices.hospital_id", "=", "agreements.hospital_id")
                ->join("sorting_contract_names", "sorting_contract_names.contract_id", "=", "contracts.id")     // 6.1.13
                ->whereRaw("practices.hospital_id = $hospital_id")
                //->whereNull("physician_practices.deleted_at")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                ->whereRaw("contracts.end_date = '0000-00-00 00:00:00'")
                ->where('sorting_contract_names.is_active', '=', 1)     // 6.1.13
                ->orderBy('sorting_contract_names.sort_order', 'ASC')   // 6.1.13
                //->whereRaw("agreements.valid_upto >= now()")
                //->whereRaw(("agreements.valid_upto >= now()") OR ("agreements.end_date >= now()"))
                ->distinct()
                ->get();

            $contracts = [];
            foreach ($active_contracts as $contract) {
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
                $today = date('Y-m-d');
                if ($valid_upto > $today) {
                    $recent_logs = $this->getPriorMonthUnapprovedLogs($contract, $physician->id);
                    $date_selectors = [];
                    $renumbered = [];
                    array_push($date_selectors, 'All');
                    if (count($recent_logs) > 0) {
                        foreach ($recent_logs as $log) {
                            array_push($date_selectors, $log['date_selector']);
                        }
                        $date_selectors = array_unique($date_selectors);
                        $renumbered = array_merge($date_selectors, array());
                        json_encode($renumbered);
                    }

                    // Sprint 6.1.7 Start
                    // $hourly_summary = array();
                    // if($contract->payment_type_id == PaymentType::HOURLY){
                    //     $hourly_summary = PhysicianLog::getMonthlyHourlySummary($recent_logs, $contract, $contract->physician_id);
                    // }
                    // Sprint 6.1.7 End

                    $contracts[] = [
                        "id" => $contract->id,
                        "payment_type_id" => $contract->payment_type_id,
                        "partial_hours" => $contract->partial_hours,
                        "partial_hours_calculation" => $contract->partial_hours_calculation,
                        "contract_type_id" => $contract->contract_type_id,
                        "name" => contract_name($contract) . ' ( ' . $contract->practice_name . ' ) ',
                        "start_date" => format_date($contract->agreement->start_date),
                        "end_date" => format_date($contract->manual_contract_end_date),
//                    "rate"       => formatCurrency($contract->rate),
                        "rate" => $contract->rate,
                        "statistics" => $this->getContractStatistics($contract),
                        "priorMonthStatistics" => $this->getPriorContractStatistics($contract),
                        "actions" => $this->getContractActions($contract),
                        //"recent_logs" => $this->getPriorMonthLogsData($contract),
                        // "recent_logs" => $recent_logs,
                        "date_selectors" => $renumbered,
                        "holiday_on_off" => $contract->holiday_on_off == 1 ? true : false,       // physicians log the hours for holiday activity on any day
                        //"prior_unapproved_logs" => $this -> getPriorMonthUnapprovedLogs($contract),
                        // "hourly_summary" => $hourly_summary,     // Sprint 6.1.7
                        "state_attestations_monthly" => $contract->state_attestations_monthly,      // Sprint 6.1.1.8
                        "state_attestations_annually" => $contract->state_attestations_annually    // Sprint 6.1.1.8
                        // "attestation_questions" => $attestation_questions       // Sprint 6.1.1.8
                    ];
                }
            }

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    protected function getPriorMonthUnapprovedLogs($contract, $physician_id)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
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
            ->where("physician_logs.physician_id", "=", $physician_id)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $duration_data = "";
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            $isApproved = false;
            if (count($approval) > 0) {
                $isApproved = true;
            }

            if (count($approval) < 1) {

                //if ($contract->contract_type_id == ContractType::ON_CALL) {
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
                    "payment_type" => $contract->payment_type_id,
                    "contract_type" => $contract->contract_type_id,
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
        }
        return $results;
    }

    public function postSaveLog()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        //$queries = DB::getQueryLog();
        //$last_query = end($queries);
        //print_r($last_query);die;
        if (Request::input("timeZone") != null) {
            // Log::Info('physician', array($access_token->physician));
            // Log::Info('timeZone', array(Request::input("timeZone")));
            // Log::Info('contract_id', array(Request::input("contract_id")));
            $zone = new DateTime(strtotime(Request::input("timeZone")));
            //$zone->setTimezone(new DateTimeZone('Asia/calcutta'));
            $zone->setTimezone(new DateTimeZone(Request::input("localTimeZone")));
            //$zone = $zone->format('T');
            //Log::info($zone->format('m/d/Y h:i A T'));
            Request::merge(['timeZone' => $zone->format('m/d/Y h:i A T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }
        if ($access_token) {
            $physician = $access_token->physician;
            $contract = $physician->apiContracts()
                ->where("id", "=", Request::input("contract_id"))
                ->first();
            $present = $this->checkPractice($contract);
            if ($present) {
                //if ($contract->contract_type_id == ContractType::ON_CALL) {
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    $hours = DB::table("action_contract")
                        ->select("hours")
                        ->where("contract_id", "=", Request::input("contract_id"))
                        ->where("action_id", "=", Request::input("action_id"))
                        ->first();
                }

                if ($contract) {
                    $action = Action::find(Request::input("action_id"));

                    $date = strtotime(Request::input("date"));

                    if ($date < strtotime($contract->agreement->start_date) || $date > strtotime(date('Y-m-d'))) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.date_range")
                        ]);
                    }

                    if ($date > strtotime($contract->manual_contract_end_date)) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.date_range")
                        ]);
                    }

                    if ($contract->deadline_option == '1') {
                        $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                        $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';
                        if ($date <= strtotime($contract_Deadline_number_string)) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.log_entrybeforedays") . $contract_deadline_days_number->contract_deadline_days . " days."
                            ]);
                        }
                    } else {
                        if ($date <= strtotime('-365 days')) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.before365days")
                            ]);
                        }
                    }

                    if (Request::input("action_id") == -1) {
                        $action = new Action;
                        $action->name = Request::input("action_name");
                        $action->contract_type_id = $contract->contract_type_id;
                        $action->payment_type_id = $contract->payment_type_id;
                        $action->action_type_id = 5;
                        $action->save();

                        $physician->actions()->attach($action->id);
                    }

                    $log_date = mysql_date(Request::input("date"));
                    $details = Request::input("details", "");

                    $hospital_override_mandate_details_count = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.*')
                        ->join('practices', 'practices.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                        ->join('contracts', 'contracts.practice_id', '=', 'practices.id')
                        ->where("contracts.id", "=", $contract->id)
                        ->where('hospitals_override_mandate_details.action_id', '=', Request::input("action_id"))
                        ->where('hospitals_override_mandate_details.is_active', '=', 1)
                        ->distinct()
                        ->count();

                    $log_details_mandate = false;
                    if ($contract->mandate_details == 1 && $details === "" && $hospital_override_mandate_details_count == 0) {
                        $log_details_mandate = true;
                    }

                    $start_time = Request::input("start_time", "");
                    $end_time = Request::input("end_time", "");

                    $approvedLogsMonth = $this->getApprovedLogsMonths($contract);
                    $logdate_formatted = date_parse_from_format("Y-m-d", $log_date);
                    $save_flag = 1;
                    foreach ($approvedLogsMonth as $approvedMonth) {
                        $approvedMonth_formatted = date_parse_from_format("m-d-Y", $approvedMonth);
                        if ($approvedMonth_formatted["year"] == $logdate_formatted["year"]) {
                            if ($approvedMonth_formatted["month"] == $logdate_formatted["month"]) {
                                $save_flag = 0;
                            }
                        }
                    }
                    if ($save_flag == 1) {
                        $log = new PhysicianLog;
                        $ckeckHours = $log->getHoursCheck(Request::input("contract_id"), $physician->id, $log_date, Request::input("duration"));
                        //fetch already entered logs for the physician, same contract, same date
                        // Sprint 6.1.14
                        if ($ckeckHours == 'Excess 250') {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.log_hours_full_per_unit")
                            ]);
                        }

                        if ($ckeckHours != 'Excess annual') {
                            if ($ckeckHours != 'Excess monthly') {
                                if ($ckeckHours != 'Excess 24') {
                                    if (!$log_details_mandate) {
//                                        $result = $log->saveLogs($action->id, 0, $details, $physician, Request::input("contract_id"), $log_date, Request::input("duration"), $physician->id, $start_time, $end_time);
                                        $save_log_factory = new PhysicianLogManager();
                                        $result = $save_log_factory->saveLog($action->id, 0, $details, $physician, Request::input("contract_id"), $log_date, Request::input("duration"), $physician->id, $start_time, $end_time);
                                        if ($result === 'Success') {
                                            return Response::json([
                                                "status" => self::STATUS_SUCCESS,
                                                "message" => Lang::get("api.save")
                                            ]);
                                        } else if ($result === 'practice_error') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => Lang::get("api.practice_not_present")
                                            ]);
                                        } else if ($result === 'Error') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => Lang::get("api.contract_not_exist")
                                            ]);
                                        } else if ($result === 'Log Exist') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => "Log is already exist between start time and end time."
                                            ]);
                                        } else if ($result === 'Start And End Time') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => "Start time should be less than end time."
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => "Request time out"
                                            ]);
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => Lang::get("api.mandate_details")
                                        ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full")
                                    ]);
                                }
                            } else {
                                if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full_month_per_unit")
                                    ]);
                                } else {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full_month")
                                    ]);
                                }
                            }
                        } else {
                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_hours_full_year_per_unit")
                                ]);
                            } else {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_hours_full_year")
                                ]);
                            }
                        }
                    } else {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.approved_month_failure")
                        ]);
                    }
                }
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("api.authentication")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function checkPractice($contract)
    {
        //physician to multiple hospital by 1254
        /*$a = DB::table('physicians')
            ->join('contracts', 'contracts.physician_id', '=', 'physicians.id')
            ->join('agreements', 'contracts.agreement_id', '=', 'agreements.id')
            ->join("practices", function ($join) {
                $join->on("physicians.practice_id", "=", "practices.id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id");
            })
            ->where("contracts.id","=", $contract->id)
            ->get();*/
        /*$a = DB::table('physician_practices')
        ->join("practices", function ($join) {
            $join->on("physician_practices.practice_id", "=", "practices.id")
                ->on("practices.hospital_id", "=", "agreements.hospital_id");

        })
        ->join("physician_contracts", function ($join) {
            $join->on("physician_contracts.practice_id", "=", "practices.id");

        })
        ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
        ->join('agreements', 'contracts.agreement_id', '=', 'agreements.id')
        ->whereRaw("physician_practices.start_date <= now()")
        ->whereRaw("physician_practices.end_date >= now()")
        ->where("contracts.id","=", $contract->id)
        ->get();*/

        $a = DB::table('contracts')->select('physician_practices.*')
            ->join('physician_contracts', 'physician_contracts.contract_id', '=', 'contracts.id')
            ->join('physician_practices', 'physician_practices.practice_id', '=', 'physician_contracts.practice_id')
            ->join('agreements', 'contracts.agreement_id', '=', 'agreements.id')
            ->join("practices", function ($join) {
                $join->on("physician_practices.practice_id", "=", "practices.id")
                    ->on("practices.hospital_id", "=", "agreements.hospital_id")
                    ->on("physician_contracts.practice_id", "=", "practices.id");
            })
            // ->join("physician_contracts", function ($join) {
            //     $join->on("physician_contracts.practice_id", "=", "practices.id");

            // })
            // ->join('contracts', 'contracts.id', '=', 'physician_contracts.contract_id')
            // ->join('agreements', 'contracts.agreement_id', '=', 'agreements.id')
            ->whereRaw("physician_practices.start_date <= now()")
            ->whereRaw("physician_practices.end_date >= now()")
            ->where("contracts.id", "=", $contract->id)
            ->distinct()
            ->get();

        if (count($a) > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getApprovedLogsMonths($contract)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

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
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
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

    public function postSaveLogRange()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        //$queries = DB::getQueryLog();
        //$last_query = end($queries);
        //print_r($last_query);die;
        if (Request::input("timeZone") != null) {
            // Log::Info('physician', array($access_token->physician));
            // Log::Info('timeZone', array(Request::input("timeZone")));
            // Log::Info('contract_id', array(Request::input("contract_id")));
            $zone = new DateTime(strtotime(Request::input("timeZone")));
            //$zone->setTimezone(new DateTimeZone('Asia/calcutta'));
            $zone->setTimezone(new DateTimeZone(Request::input("localTimeZone")));
            //$zone = $zone->format('T');
            //Log::info($zone->format('m/d/Y h:i A T'));
            Request::merge(['timeZone' => $zone->format('m/d/Y h:i A T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }
        if ($access_token) {
            $physician = $access_token->physician;
            $contract = $physician->apiContracts()
                ->where("contracts.id", "=", Request::input("contract_id"))
                ->first();
            $present = $this->checkPractice($contract);
            if ($present) {
                //if ($contract->contract_type_id == ContractType::ON_CALL) {
                if ($contract->payment_type_id == PaymentType::PER_DIEM) {
                    $hours = DB::table("action_contract")
                        ->select("hours")
                        ->where("contract_id", "=", Request::input("contract_id"))
                        ->where("action_id", "=", Request::input("action_id"))
                        ->first();
                }

                if ($contract) {
                    $action = Action::find(Request::input("action_id"));

                    $date = strtotime(Request::input("date"));

                    if ($date < strtotime($contract->agreement->start_date) || $date > strtotime(date('Y-m-d'))) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.date_range")
                        ]);
                    }

                    if ($date > strtotime($contract->manual_contract_end_date)) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.date_range")
                        ]);
                    }

                    if ($contract->deadline_option == '1') {
                        $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                        $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';
                        if ($date <= strtotime($contract_Deadline_number_string)) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.log_entrybeforedays") . $contract_deadline_days_number->contract_deadline_days . " days."
                            ]);
                        }
                    } else {
                        if ($date <= strtotime('-365 days')) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.before365days")
                            ]);
                        }
                    }

                    if (Request::input("action_id") == -1) {
                        $action = new Action;
                        $action->name = Request::input("action_name");
                        $action->contract_type_id = $contract->contract_type_id;
                        $action->payment_type_id = $contract->payment_type_id;
                        $action->action_type_id = 5;
                        $action->save();

                        $physician->actions()->attach($action->id);
                    }

                    $log_date = mysql_date(Request::input("date"));
                    $details = Request::input("details", "");

                    $hospital_override_mandate_details_count = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.*')
                        ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                        ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                        ->where("contracts.id", "=", $contract->id)
                        ->where('hospitals_override_mandate_details.action_id', '=', Request::input("action_id"))
                        ->where('hospitals_override_mandate_details.is_active', '=', 1)
                        ->distinct()
                        ->count();

                    $log_details_mandate = false;
                    if ($contract->mandate_details == 1 && $details === "" && $hospital_override_mandate_details_count == 0) {
                        $log_details_mandate = true;
                    }

                    $start_time = Request::input("start_time", "");
                    $end_time = Request::input("end_time", "");

                    $approvedLogsRange = $this->getApprovedLogsRange($contract, $physician);
                    $logdate_formatted = date_parse_from_format("Y-m-d", $log_date);
                    $save_flag = 1;

                    $approved_range = [];

                    foreach ($approvedLogsRange as $key => $range_obj) {
                        if (strtotime($log_date) >= strtotime($range_obj->start_date) && strtotime($log_date) <= strtotime($range_obj->end_date)) {
                            if (!array_key_exists($key, $approved_range)) {
                                $save_flag = 0;
                                break;
                            }
                        }
                    }
                    if ($save_flag == 1) {
                        $log = new PhysicianLog;
                        $ckeckHours = $log->getHoursCheck(Request::input("contract_id"), $physician->id, $log_date, Request::input("duration"));
                        //fetch already entered logs for the physician, same contract, same date
                        // Sprint 6.1.14
                        if ($ckeckHours == 'Excess 250') {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.log_hours_full_per_unit")
                            ]);
                        }

                        if ($ckeckHours != 'Excess annual') {
                            if ($ckeckHours != 'Excess monthly') {
                                if ($ckeckHours != 'Excess 24') {
                                    if (!$log_details_mandate) {
//                                        $result = $log->saveLogs($action->id, 0, $details, $physician, Request::input("contract_id"), $log_date, Request::input("duration"), $physician->id, $start_time, $end_time);
                                        $save_log_factory = new PhysicianLogManager();
                                        $result = $save_log_factory->saveLog($action->id, 0, $details, $physician, Request::input("contract_id"), $log_date, Request::input("duration"), $physician->id, $start_time, $end_time);
                                        if ($result === 'Success') {
                                            return Response::json([
                                                "status" => self::STATUS_SUCCESS,
                                                "message" => Lang::get("api.save")
                                            ]);
                                        } else if ($result === 'practice_error') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => Lang::get("api.practice_not_present")
                                            ]);
                                        } else if ($result === 'Error') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => Lang::get("api.contract_not_exist")
                                            ]);
                                        } else if ($result === 'Log Exist') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => "Log is already exist between start time and end time."
                                            ]);
                                        } else if ($result === 'Start And End Time') {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => "Start time should be less than end time."
                                            ]);
                                        } else {
                                            return Response::json([
                                                "status" => self::STATUS_FAILURE,
                                                "message" => "Request time out"
                                            ]);
                                        }
                                    } else {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => Lang::get("api.mandate_details")
                                        ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full")
                                    ]);
                                }
                            } else {
                                if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full_month_per_unit")
                                    ]);
                                } else {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full_month")
                                    ]);
                                }
                            }
                        } else {
                            if ($contract->payment_type_id == PaymentType::PER_UNIT) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_hours_full_year_per_unit")
                                ]);
                            } else {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_hours_full_year")
                                ]);
                            }
                        }
                    } else {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.approved_month_failure")
                        ]);
                    }
                }
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("api.authentication")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getApprovedLogsRange($contract, $physician)
    {
        $agreement_data = Agreement::getAgreementData($contract->agreement);

        // log::info('$agreement_data', array($agreement_data));

        $agreement_month = $agreement_data->months[$agreement_data->current_month];

        $start_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_data->start_date))); // . " -1 month"

        //$prior_month_end_date = date('Y-m-d 00:00:00', strtotime(mysql_date($agreement_month->end_date) . " -1 month"));
        $prior_month_end_date = date('Y-m-d', strtotime("last day of -1 month"));

        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->where("physician_logs.created_at", ">=", $start_date)
            ->where("physician_logs.date", "<=", $prior_month_end_date)
            ->where("physician_logs.physician_id", "=", $physician->id)
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $current_month = 0;
        $approved_range = [];
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            /*to check the log has been approved by physician or not, if yes then no log should be entered for the month*/
            $isApproved = false;
            $approval = DB::table('log_approval_history')->select('*')
                ->where('log_id', '=', $log->id)->orderBy('created_at', 'desc')->get();
            if (count($approval) > 0) {
                $isApproved = true;
            }
            if ($isApproved == true) //if($log->approval_date !='0000-00-00' || $log->signature > 0)
            {
                foreach ($agreement_data->months as $key => $range_obj) {
                    if (strtotime($log->date) >= strtotime($range_obj->start_date) && strtotime($log->date) <= strtotime($range_obj->end_date)) {
                        if (!array_key_exists($key, $approved_range)) {
                            $approved_range[$key] = $range_obj;
                        }
                    }
                }

            }
        }

        return $approved_range;
    }

    public function postSaveLogForMultipleDates()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        if (Request::input("timeZone") != null) {
            $zone = new DateTime(strtotime(Request::input("timeZone")));
            //$zone->setTimezone(new DateTimeZone('Asia/calcutta'));
            $zone->setTimezone(new DateTimeZone(Request::input("localTimeZone")));
            //$zone = $zone->format('T');
            //Log::info($zone->format('m/d/Y h:i A T'));
            Request::merge(['timeZone' => $zone->format('m/d/Y h:i A T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }

        if ($access_token) {
            $physician = $access_token->physician;

            $contract = $physician->apiContracts()
                ->where("contracts.id", "=", Request::input("contract_id"))
                ->first();
            $present = $this->checkPractice($contract);
            if ($present) {
                //if ($contract->contract_type_id == ContractType::ON_CALL) {
                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $hours = DB::table("action_contract")
                        ->select("hours")
                        ->where("contract_id", "=", Request::input("contract_id"))
                        ->where("action_id", "=", Request::input("action_id"))
                        ->first();
                }
                $action_id = Request::input("action_id");

                if ($contract) {
                    $action = Action::find(Request::input("action_id"));

                    if (Request::input("action_id") == -1) {
                        $action = new Action;
                        $action->name = Request::input("action_name");
                        $action->contract_type_id = $contract->contract_type_id;
                        $action->payment_type_id = $contract->payment_type_id;
                        $action->action_type_id = 5;
                        $action->save();

                        $physician->actions()->attach($action->id);
                    }

                    $dates = json_decode(Request::input("dates"));
                    $log_error_for_dates = [];
                    foreach ($dates as $date) {
                        if (strtotime($date) < strtotime($contract->agreement->start_date) ||
                            strtotime($date) > strtotime(date('Y-m-d'))
                        ) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.date_range")
                            ]);
                        }

                        if (strtotime($date) > strtotime($contract->manual_contract_end_date)) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.date_range")
                            ]);
                        }

                        if ($contract->deadline_option == '1') {
                            $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                            $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';
                            if (strtotime($date) <= strtotime($contract_Deadline_number_string)) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_entrybeforedays") . $contract_deadline_days_number->contract_deadline_days . "days."
                                ]);
                            }
                        } else {
                            if (strtotime($date) <= strtotime('-90 days')) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.before90days")
                                ]);
                            }
                        }

                        //$duration = $contract->contract_type_id != ContractType::ON_CALL ? Request::input("duration") : $hours->hours;
                        // $duration = $contract->payment_type_id != PaymentType::PER_DIEM ? Request::input("duration") : $hours->hours;
                        if ($contract->partial_hours == 1 && ($contract->payment_type_id == 3 || $contract->payment_type_id == 5)) {
                            $duration = Request::input("duration");
                        } else {
                            $duration = $hours->hours;
                        }

                        $hospital_override_mandate_details_count = HospitalOverrideMandateDetails::select('hospitals_override_mandate_details.*')
                            ->join('agreements', 'agreements.hospital_id', '=', 'hospitals_override_mandate_details.hospital_id')
                            ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                            ->where("contracts.id", "=", $contract->id)
                            ->where('hospitals_override_mandate_details.action_id', '=', $action_id)
                            ->where('hospitals_override_mandate_details.is_active', '=', 1)
                            ->distinct()
                            ->count();

                        $details = Request::input("details", "");
                        $log_details_mandate = false;
                        if ($contract->mandate_details == 1 && $details === "" && $hospital_override_mandate_details_count == 0) {
                            $log_details_mandate = true;
                        }
                        //  $approvedLogsMonth = $this->getApprovedLogsMonths($contract);
                        //  $logdate_formatted = date_parse_from_format("Y-m-d", mysql_date($date));

                        $user_id = $physician->id;
                        //shift
                        $shift = Request::input("shift");
                        $physician_logs = new PhysicianLog();
                        $results = $physician_logs->validateLogEntryForSelectedDate($physician->id, $contract, $contract->id, $date, $action->id, $shift, $details, $physician, $duration, $user_id);

                        $start_time = Request::input("start_time", "");
                        $end_time = Request::input("end_time", "");

                        if ($results->getdata()->message == "Success") {
                            // $physician_logs->saveLogs($action_id, $shift, $details, $physician, $contract->id, $date, $duration, $user_id, $start_time, $end_time);
                            $save_log_factory = new PhysicianLogManager();
                            $result = $save_log_factory->saveLog($action_id, $shift, $details, $physician, $contract->id, $date, $duration, $user_id, $start_time, $end_time);
                            if ($result !== 'Success') {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("Error")
                                ]);
                            }
                        } else {
                            if ($results->getdata()->message == "annual_max_shifts_error") {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.annual_max_shifts_error")
                                ]);
                            } else {
                                $log_error_for_dates[$date] = $results->getdata()->message;
                            }
                        }
                    }

                    if (count($log_error_for_dates) > 0) {
                        $final_error_message = "You are not allowed to enter log for dates :";
                        $cnt = 1;
                        foreach ($log_error_for_dates as $error_date => $error_message) {
                            $datetime = strtotime($error_date);
                            $format_date = date('d-M', $datetime);
                            $final_error_message .= "$format_date" . ",";
                            if ($cnt > 2) {
                                $final_error_message .= "\n";
                                $cnt = 1;
                            }
                            $cnt = $cnt + 1;
                        }
                        $final_error_message = rtrim($final_error_message, ',');
                        $final_error_message .= ".";
                        // return $final_error_message;
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => $final_error_message
                        ]);

                    } else {
                        return Response::json([
                            "status" => self::STATUS_SUCCESS,
                            "message" => Lang::get("api.save")
                        ]);
                    }

                    // return Response::json([
                    //     "status" => self::STATUS_SUCCESS,
                    //     "message" => Lang::get("api.save")
                    // ]);
                }
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("api.authentication")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function postSaveLogForMultipleDatesRange()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        if (Request::input("timeZone") != null) {
            $zone = new DateTime(strtotime(Request::input("timeZone")));
            //$zone->setTimezone(new DateTimeZone('Asia/calcutta'));
            $zone->setTimezone(new DateTimeZone(Request::input("localTimeZone")));
            //$zone = $zone->format('T');
            //Log::info($zone->format('m/d/Y h:i A T'));
            Request::merge(['timeZone' => $zone->format('m/d/Y h:i A T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }

        if ($access_token) {
            $physician = $access_token->physician;

            $contract = $physician->apiContracts()
                ->where("contracts.id", "=", Request::input("contract_id"))
                ->first();
            $present = $this->checkPractice($contract);
            if ($present) {
                //if ($contract->contract_type_id == ContractType::ON_CALL) {
                if ($contract->payment_type_id == PaymentType::PER_DIEM || $contract->payment_type_id == PaymentType::PER_DIEM_WITH_UNCOMPENSATED_DAYS) {
                    $hours = DB::table("action_contract")
                        ->select("hours")
                        ->where("contract_id", "=", Request::input("contract_id"))
                        ->where("action_id", "=", Request::input("action_id"))
                        ->first();
                }
                $action_id = Request::input("action_id");

                if ($contract) {
                    $action = Action::find(Request::input("action_id"));

                    if (Request::input("action_id") == -1) {
                        $action = new Action;
                        $action->name = Request::input("action_name");
                        $action->contract_type_id = $contract->contract_type_id;
                        $action->payment_type_id = $contract->payment_type_id;
                        $action->action_type_id = 5;
                        $action->save();

                        $physician->actions()->attach($action->id);
                    }

                    $dates = json_decode(Request::input("dates"));
                    $log_error_for_dates = [];
                    foreach ($dates as $date) {
                        if (strtotime($date) < strtotime($contract->agreement->start_date) ||
                            strtotime($date) > strtotime(date('Y-m-d'))
                        ) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.date_range")
                            ]);
                        }

                        if (strtotime($date) > strtotime($contract->manual_contract_end_date)) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.date_range")
                            ]);
                        }

                        if ($contract->deadline_option == '1') {
                            $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                            $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';
                            if (strtotime($date) <= strtotime($contract_Deadline_number_string)) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_entrybeforedays") . $contract_deadline_days_number->contract_deadline_days . "days."
                                ]);
                            }
                        } else {
                            if (strtotime($date) <= strtotime('-90 days')) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.before90days")
                                ]);
                            }
                        }

                        //$duration = $contract->contract_type_id != ContractType::ON_CALL ? Request::input("duration") : $hours->hours;
                        // $duration = $contract->payment_type_id != PaymentType::PER_DIEM ? Request::input("duration") : $hours->hours;
                        if ($contract->partial_hours == 1 && ($contract->payment_type_id == 3 || $contract->payment_type_id == 5)) {
                            $duration = Request::input("duration");
                        } else {
                            $duration = $hours->hours;
                        }

                        $details = Request::input("details", "");
                        $log_details_mandate = false;
                        if ($contract->mandate_details == 1 && $details === "") {
                            $log_details_mandate = true;
                        }
                        //  $approvedLogsMonth = $this->getApprovedLogsMonths($contract);
                        //  $logdate_formatted = date_parse_from_format("Y-m-d", mysql_date($date));

                        $start_time = Request::input("start_time", "");
                        $end_time = Request::input("end_time", "");

                        $user_id = $physician->id;
                        //shift
                        $shift = Request::input("shift");
                        $physician_logs = new PhysicianLog();
                        $results = $physician_logs->validateLogEntryForSelectedDateRange($physician->id, $contract, $contract->id, $date, $action->id, $shift, $details, $physician, $duration, $user_id);

                        if ($results->getdata()->message == "Success") {
//                            $physician_logs->saveLogs($action_id, $shift, $details, $physician, $contract->id, $date, $duration, $user_id, $start_time, $end_time);
                            $save_log_factory = new PhysicianLogManager();
                            $result = $save_log_factory->saveLog($action_id, $shift, $details, $physician, $contract->id, $date, $duration, $user_id, $start_time, $end_time);
                            if ($result !== 'Success') {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("Error")
                                ]);
                            }
                        } else {
                            if ($results->getdata()->message == "annual_max_shifts_error") {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.annual_max_shifts_error")
                                ]);
                            } else {
                                $log_error_for_dates[$date] = $results->getdata()->message;
                            }
                        }
                    }

                    if (count($log_error_for_dates) > 0) {
                        $final_error_message = "You are not allowed to enter log for dates :";
                        $cnt = 1;
                        foreach ($log_error_for_dates as $error_date => $error_message) {
                            $datetime = strtotime($error_date);
                            $format_date = date('d-M', $datetime);
                            $final_error_message .= "$format_date" . ",";
                            if ($cnt > 2) {
                                $final_error_message .= "\n";
                                $cnt = 1;
                            }
                            $cnt = $cnt + 1;
                        }
                        $final_error_message = rtrim($final_error_message, ',');
                        $final_error_message .= ".";
                        // return $final_error_message;
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => $final_error_message
                        ]);

                    } else {
                        return Response::json([
                            "status" => self::STATUS_SUCCESS,
                            "message" => Lang::get("api.save")
                        ]);
                    }

                    // return Response::json([
                    //     "status" => self::STATUS_SUCCESS,
                    //     "message" => Lang::get("api.save")
                    // ]);
                }
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("api.authentication")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function postDeleteLog()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $contract = new stdClass();

        if ($access_token) {
            $log_id = Request::input("log_id");

            $physician = $access_token->physician;

            $log = $physician->logs()->where("id", "=", $log_id)->first();

            if ($log) {
                $contract->id = $log->contract_id;
                $present = $this->checkPractice($contract);

                if ($present) {
                    $contract_detail = Contract::findOrFail($contract->id);
                    //issue fixing : on call log should not be delete before called-ack and called-in in case of burden_on_call true by 1254
                    $logdata = PhysicianLog::where('physician_id', '=', $log->physician_id)
                        ->where('contract_id', '=', $contract_detail->id)
                        ->where('date', '=', mysql_date($log->date))
                        ->whereNull('deleted_at')
                        ->get();

                    //Issue fixed for Undefined property: stdClass::$on_call_process earlier $contract->on_call_process was used, which is replaced with $contract_detail->on_call_process.
                    if ($contract_detail->burden_of_call == 1 && $contract_detail->on_call_process == 1) {
                        $action_name = Action::findOrFail($log->action_id);
                        if (count($logdata) > 1 && $action_name->name == "On-Call") {
                            return Response::json([
                                "status" => "0",
                                "message" => "Please delete Called Back & Called In logs, before deleting On Call log."
                            ]);
                        }

                    }//end issue fixing : on call log should not be delete before called-ack and called-in in case of burden_on_call true by 1254

                    if ($log->approval_date == '0000-00-00' && $log->signature == 0) {
                        $log->delete();

                        return Response::json([
                            "status" => self::STATUS_SUCCESS,
                            "message" => Lang::get("api.delete")
                        ]);
                    } else {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.log_already_approved")
                        ]);
                    }
                } else {
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "message" => Lang::get("api.authentication")
                    ]);
                }
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("api.log_not_exist")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function sendSMS()
    {
        include(app_path() . '/includes/Services/Twilio.php');
        $AccountSid = "AC62f5c7ca2106d437127277d0eb6c678a";
        $AuthToken = "2807786b58d560ed6b75cf13724a4a09";
        $client = new Services_Twilio($AccountSid, $AuthToken);
        echo "hello";
        $message = $client->account->messages->create(array(
            "From" => "+15005550006",
            "To" => "+919016472636",
            "Body" => "Test message!",
        ));
        // Display a confirmation message on the screen
        echo "Sent message {$message->sid}";
    }

    public function isSignatureSubmitted()
    {
        //$this->content = file_get_contents('php://input');
        //$arr = json_decode($this->content);
        $token = Request::input("token");
        //echo $token;
        $access_token1 = AccessToken::where("key", "=", $token)->first();
        $date_check = date('Y-m') . "-01";
        $access_token = DB::table('signature')->where("physician_id", "=", $access_token1->physician_id)->where("date", ">", $date_check)
            ->orderBy("created_at", "desc")->first();
        //$access_token = Signature::where("physician_id", "=", $access_token1->physician_id)->first();

        if ($access_token) {
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "signature" => $access_token->signature_path
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.not_exist")
        ]);
    }

    public function submitSignature()
    {
        //$this->content = file_get_contents('php://input');
        //$arr = json_decode($this->content);
        $token = Request::input("token");
        $signature = Request::input("signature");
        $date = mysql_date(Request::input("date"));
        $access_token = AccessToken::where("key", "=", $token)->first();

        $physician = $access_token->physician_id;
        $signature_obj = new Signature();
        $result = $signature_obj->postSignature($physician, $signature, $token, $date);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "message" => Lang::get("api.save")
        ]);
    }


    public function submitSignatureForLog()
    {
        //$this->content = file_get_contents('php://input');
        //$arr = json_decode($this->content);

        $log_ids = Request::input("log_ids");
        $log_ids = json_decode($log_ids);
        $token = Request::input("token");
        $date = mysql_date(Request::input("date"));
        $signature = Request::input("signature");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $date_check = date('Y-m') . "-01";
        $rejected = array();

        $physician = $access_token->physician_id;
        $physician_info = Physician::findOrFail($physician);
        try {
            $role = LogApproval::physician;
            $user = User::where("email", "=", $physician_info->email)->where("group_id", "=", Group::Physicians)->first();
            $status = 1;
            $reason = 0;
            $physician_log = new PhysicianLog();
            if ($signature) {
                $signatureRecord = Signature::where('physician_id', '=', $physician)
                    ->orderBy("created_at", "desc")->first();

                //check if signature exist
                if ($signatureRecord) {
                    //update record
//                    $result = DB::table('signature')
//                        ->where('signature_id', $signatureRecord->signature_id)
//                        ->update(array('physician_id' => $physician, 'signature_path' => $signature, 'tokken_id' => $token, 'date' => $date));
//                    if ($result) {
//                        $physician_log->approveLogs($log_ids,$rejected,$signatureRecord,$role,$user->id,$status,$reason);
//                    }
                    //create record
                    $saveResult = Signature::create(
                        array('physician_id' => $physician, 'signature_path' => $signature, 'tokken_id' => $token, 'date' => $date)
                    );
                    $queryResult = Signature::where('physician_id', '=', $physician)
                        ->orderBy("created_at", "desc")->first();
                    $physician_log->approveLogs($log_ids, $rejected, $queryResult, $role, $user->id, $status, $reason);
                    $logArray = $physician_log->getApprovedLogsDetails($log_ids);
                    $agreement_details = Agreement::select('agreements.*', 'contracts.id as contract_id', 'contracts.default_to_agreement as default_to_agreement')
                        ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                        ->where('agreements.id', '=', $logArray[0]['contract']->agreement_id)
                        ->first();

                    if ($agreement_details->approval_process == 1) {
                        if ($agreement_details->default_to_agreement != 0) {
                            $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                        } else {
                            $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $logArray[0]['contract']->id)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                        }
                    } else {
                        $approval_levels = [];
                    }
                    if ($logArray[0]['physician_opt_in_email'] > 0) {
                        $data = [
                            "email" => $logArray[0]['physician_email'],
                            "name" => $logArray[0]['physician_first_name'] . ' ' . $logArray[0]['physician_last_name'],
                            "logArray" => $logArray,
                            "type" => EmailSetup::LOG_APPROVAL,
                            'with' => [
                                'logArray' => $logArray
                            ]
                        ];
                        try {
                            EmailQueueService::sendEmail($data);
                        } catch (Exception $e) {

                        }
                    }
                    if (count($approval_levels) > 0) {
                        $proxy_users = ProxyApprovalDetails::find_proxy_aaprover_users($approval_levels[0]->user_id);
                        /*$data = [
                            "email"=>$approval_user_email->email,
                            "name"=>$approval_user_email->first_name . ' ' . $approval_user_email->last_name,
                        ];*/

                        foreach ($proxy_users as $proxy_user_id) {
                            $user = User::findOrFail($proxy_user_id);
                            $data["email"] = $user->email;
                            $data["name"] = $user->first_name . ' ' . $user->last_name;
                            $data["type"] = EmailSetup::APPROVAL_REMINDER_MAIL_NEXT_LEVEL;
                            $data['with'] = [
                                'name' => $user->first_name . ' ' . $user->last_name
                            ];
                            try {
                                EmailQueueService::sendEmail($data);
                            } catch (Exception $e) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => $e->getMessage()
                                ]);
                            }
                        }
                    }
                    $c = new Contract;
                    $contract_stats = $c->getContractCurrentStat($logArray[0]['contract']);
                    $prior_month_stats = $c->getPriorContractStat($logArray[0]['contract']);
                    return Response::json([
                        "status" => self::STATUS_SUCCESS,
                        "message" => Lang::get("api.save"),
                        "contract_stats" => $contract_stats,
                        "prior_month_stats" => $prior_month_stats
                    ]);

                } else {
                    //create record
                    $saveResult = Signature::create(
                        array('physician_id' => $physician, 'signature_path' => $signature, 'tokken_id' => $token, 'date' => $date)
                    );
                    $queryResult = Signature::where('physician_id', '=', $physician)
                        ->orderBy("created_at", "desc")->first();
                    $physician_log->approveLogs($log_ids, $rejected, $queryResult, $role, $user->id, $status, $reason);
                    $logArray = $physician_log->getApprovedLogsDetails($log_ids);
                    $agreement_details = Agreement::select('agreements.*', 'contracts.id as contract_id', 'contracts.default_to_agreement as default_to_agreement')
                        ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                        ->where('agreements.id', '=', $logArray[0]['contract']->agreement_id)
                        ->first();

                    if ($agreement_details->approval_process == 1) {
                        if ($agreement_details->default_to_agreement != 0) {
                            $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                        } else {
                            $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $logArray[0]['contract']->id)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                        }
                    } else {
                        $approval_levels = [];
                    }
                    if ($logArray[0]['physician_opt_in_email'] > 0) {
                        $data = [
                            "email" => $logArray[0]['physician_email'],
                            "name" => $logArray[0]['physician_first_name'] . ' ' . $logArray[0]['physician_last_name'],
                            "logArray" => $logArray,
                            "type" => EmailSetup::LOG_APPROVAL,
                            'with' => [
                                'logArray' => $logArray
                            ]
                        ];
                        try {
                            EmailQueueService::sendEmail($data);
                        } catch (Exception $e) {

                        }
                    }
                    if (count($approval_levels) > 0) {

                        $proxy_users = ProxyApprovalDetails::find_proxy_aaprover_users($approval_levels[0]->user_id);
                        /*$data = [
                            "email"=>$approval_user_email->email,
                            "name"=>$approval_user_email->first_name . ' ' . $approval_user_email->last_name,
                        ];*/

                        foreach ($proxy_users as $proxy_user_id) {
                            $user = User::findOrFail($proxy_user_id);
                            $data["email"] = $user->email;
                            $data["name"] = $user->first_name . ' ' . $user->last_name;
                            $data["type"] = EmailSetup::APPROVAL_REMINDER_MAIL_NEXT_LEVEL;
                            $data['with'] = [
                                'name' => $user->first_name . ' ' . $user->last_name
                            ];
                            try {
                                EmailQueueService::sendEmail($data);
                            } catch (Exception $e) {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => $e->getMessage()
                                ]);
                            }
                        }
                    }
                    $c = new Contract;
                    $contract_stats = $c->getContractCurrentStat($logArray[0]['contract']);
                    $prior_month_stats = $c->getPriorContractStat($logArray[0]['contract']);
                    return Response::json([
                        "status" => self::STATUS_SUCCESS,
                        "message" => Lang::get("api.save"),
                        "contract_stats" => $contract_stats,
                        "prior_month_stats" => $prior_month_stats
                    ]);
                }

            } else {
                $queryResult = Signature::where('physician_id', '=', $physician)
                    ->orderBy("created_at", "desc")->first();
                if ($queryResult == null) {
                    //   log::info('signature not found', array(Lang::get("api.empty_signature")));
                    return Response::json([
                        "status" => self::STATUS_FAILURE,
                        "statusCode" => 400,
                        "message" => Lang::get("api.empty_signature")
                    ]);
                }
                $physician_log->approveLogs($log_ids, $rejected, $queryResult, $role, $user->id, $status, $reason);
                $logArray = $physician_log->getApprovedLogsDetails($log_ids);
                $agreement_details = Agreement::select('agreements.*', 'contracts.id as contract_id', 'contracts.default_to_agreement as default_to_agreement')
                    ->join('contracts', 'contracts.agreement_id', '=', 'agreements.id')
                    ->where('agreements.id', '=', $logArray[0]['contract']->agreement_id)
                    ->first();

                if ($agreement_details->approval_process == 1) {
                    if ($agreement_details->default_to_agreement != 0) {
                        $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", 0)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                    } else {
                        $approval_levels = ApprovalManagerInfo::where("agreement_id", "=", $agreement_details->id)->where("contract_id", "=", $logArray[0]['contract']->id)->where("is_deleted", "=", '0')->where("level", "=", 1)->where("opt_in_email_status", "=", '1')->get();
                    }
                } else {
                    $approval_levels = [];
                }
                if ($logArray[0]['physician_opt_in_email'] > 0) {
                    $data = [
                        "email" => $logArray[0]['physician_email'],
                        "name" => $logArray[0]['physician_first_name'] . ' ' . $logArray[0]['physician_last_name'],
                        "logArray" => $logArray,
                        "type" => EmailSetup::LOG_APPROVAL,
                        'with' => [
                            'logArray' => $logArray
                        ]
                    ];
                    try {
                        EmailQueueService::sendEmail($data);
                    } catch (Exception $e) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => $e->getMessage()
                        ]);
                    }
                }
                if (count($approval_levels) > 0) {
                    $proxy_users = ProxyApprovalDetails::find_proxy_aaprover_users($approval_levels[0]->user_id);

                    foreach ($proxy_users as $proxy_user_id) {
                        $user = User::findOrFail($proxy_user_id);
                        $data["email"] = $user->email;
                        $data["name"] = $user->first_name . ' ' . $user->last_name;
                        $data["type"] = EmailSetup::APPROVAL_REMINDER_MAIL_NEXT_LEVEL;
                        $data['with'] = [
                            'name' => $user->first_name . ' ' . $user->last_name
                        ];
                        try {
                            EmailQueueService::sendEmail($data);
                        } catch (Exception $e) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => $e->getMessage()
                            ]);
                        }
                    }
                }
                $c = new Contract;
                $contract_stats = $c->getContractCurrentStat($logArray[0]['contract']);
                $prior_month_stats = $c->getPriorContractStat($logArray[0]['contract']);
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get("api.save"),
                    "contract_stats" => $contract_stats,
                    "prior_month_stats" => $prior_month_stats
                ]);
            }
        } catch (Exception $e) {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => $e->getMessage()
            ]);
        }


    }

    public function checkPriorMonthLogsApproval()
    {
        //check if date is between startDate to endDate

        $startDate = 1;
        $endDate = 10;

        if (date('d') >= $startDate && date('d') <= $endDate) {
            // show prior month logs

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "message" => Lang::get("api.save"),
                "last_month" => date("Y-n-j", strtotime("first day of previous month"))
            ]);
        }
        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.exist")
        ]);
    }

    public function getSignature()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $access_token_Signature = DB::table('signature')->where("physician_id", "=", $access_token->physician_id)
            ->orderBy("created_at", "desc")->first();
        if ($access_token_Signature) {

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "message" => Lang::get("api.exist"),
                "sign" => $access_token_Signature->signature_path
            ]);
        }


        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.not_exist"),
            "sign" => ""
        ]);
        /*return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.not_exist")
        ]);*/
    }

    public function DeleteAllSignature()
    {
        Signature::where('signature_id', '>', 0)->delete();
        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "message" => Lang::get("api.delete")
        ]);
    }

    public function createTable()
    {
        $a = DB::table('amount_paid')->get();
        echo "<pre>";
        print_r($a);
    }

    public function reSubmitLog()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        $physician_id = $access_token->physician_id;
        if ($access_token) {
            $physicianLog = new PhysicianLog();
            return $physicianLog->reSubmit(array(Request::input()), $physician_id);
        } else {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("api.authentication")
            ]);
        }
    }

    public function reSubmitUnapprovedLog()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token) {
            $physicianLog = new PhysicianLog();
            $save_log = $physicianLog->reSubmitUnapproved(array(Request::input()));
//            $contracts = $this->getPriorMonthLogs();
            //return $x;
            return Response::json([
                "status" => $save_log->getData()->status,
                "message" => $save_log->getData()->message,
//                "contracts" => $contracts->getData()->contracts
            ]);
        } else {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("api.authentication")
            ]);
        }
    }

    public function reSubmitUnapprovedLogRecent()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token) {
            $physicianLog = new PhysicianLog();
            $save_log = $physicianLog->reSubmitUnapproved(array(Request::input()));

            return Response::json([
                "status" => $save_log->getData()->status,
                "message" => $save_log->getData()->message
            ]);
        } else {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("api.authentication")
            ]);
        }
    }

    public function getUnapproveLogs()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        //Physician to multiple hospital by 1254
        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $dates = [1, 10];
            //Physician to multiple hospital by 1254
            $physicianpractices = DB::table('physician_practices')
                ->select('physician_practices.practice_id')
                ->where('physician_practices.hospital_id', '=', $hospital_id)
                ->where('physician_practices.physician_id', '=', $physician->id)
                ->whereNull("physician_practices.deleted_at")
                ->first();

            $physician->practice_id = $physicianpractices->practice_id;
            $contracts = $contract->getUnapproveLogs($physician);
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts,
                "dates" => $dates
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function logOut()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if ($access_token) {
            $physician = $access_token->physician;
            $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician->id)->first();
            if ($device_token) {
                $device_token->delete();
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get("Log Out.")
                ]);
            } else {
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get("Log Out.")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function lockedCheck()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        if ($access_token) {
            $physician = $access_token->physician;
            if ($physician) {
                $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
                if ($user) {
                    if ($user->locked == 1) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("Account Locked.")
                        ]);
                    } else {
                        return Response::json([
                            "status" => self::STATUS_SUCCESS,
                            "message" => Lang::get("Account Not Locked.")
                        ]);
                    }
                }
            }
        }
    }

    public function expiredCheck()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        if ($access_token) {
            $physician = $access_token->physician;
            if ($physician) {
                $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
                if ($user) {
                    $today = date("Y-m-d");
                    if ($user->password_expiration_date <= $today) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("Password Expired.")
                        ]);
                    } else {
                        return Response::json([
                            "status" => self::STATUS_SUCCESS,
                            "message" => Lang::get("Password Not Expired.")
                        ]);
                    }
                }
            }
        }
    }

    public function sendOTP()
    {
        $email = Request::input("email");
        $physician = Physician::where("email", "=", $email)->first();

        $result = OtpHistory::saveOTP($physician, 'RESET_PASSWORD');
        return $result;
    }

    public function verifyOTP()
    {
        $email = Request::input("email");
        $otp = Request::input("otp");
        $physician = Physician::where("email", "=", $email)->first();

        $result = OtpHistory::verifyOTP($physician, $otp, 'RESET_PASSWORD');
        return $result;
    }

    public function resetPassword()
    {
        $email = Request::input("email");
        $password = Request::input("password");
        $password_confirmation = Request::input("password_confirmation");

        $lock_check = User::where("email", "=", $email)->first();
        if ($lock_check) {
            if ($lock_check->getLocked() == 1) {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("Your account has been locked. Please contact support@dynafiosapp.com to unlock your account.")
                ]);
            }
        }

        if (Hash::check($password, $lock_check->password)) {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please use another password.")
            ]);
        }
        $validation = new UserValidation();
        if (!$validation->validateRemind(Request::only('password'))) {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Password must be between 8 and 20 characters in length, match the confirmation, cannot be the same as your current password, and must contain: lower or upper case letters, numbers, and at least one special character (!@#$%&*).")
            ]);
        } else if ($password == '') {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter valid password and confirm password.")
            ]);
        } else if ($password_confirmation == '') {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Please enter valid password and confirm password.")
            ]);
        } else if ($password != $password_confirmation) {
            return Response::json([
                "status" => self::STATUS_FAILURE,
                "message" => Lang::get("Password and confirm password must be same.")
            ]);
        }

        $physician = Physician::where("email", "=", $email)->first();
        if ($physician) {
            $user = User::where("email", "=", $email)->first();
            if ($user) {
                $user->password = Hash::make($password);
                $user->password_text = ($password);
                $user->save();
                //reset password for physician in physician table
                if ($user->group_id == Group::Physicians) {
                    $physician->password = Hash::make($password);
                    $physician->setPasswordText($password);
                    $physician->save();
                }
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get("Password reset successfully.")
                ]);
            }
        } else {
            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "message" => Lang::get("A link to reset password has been sent to this email address.")
            ]);
        }
    }

    public function versionCheck()
    {
        // $version_number = Version::version();
        $version = new version();
        $version_number = $version->format('version-only');
        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "message" => $version_number
        ]);
    }

    public function postSaveTimeStudyLog()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        if (Request::input("timeZone") != null) {
            $zone = new DateTime(strtotime(Request::input("timeZone")));
            $zone->setTimezone(new DateTimeZone(Request::input("localTimeZone")));
            Request::merge(['timeZone' => $zone->format('m/d/Y h:i A T')]);
        } else {
            Request::merge(['timeZone' => '']);
        }
        if ($access_token) {
            $physician = $access_token->physician;
            $contract = $physician->apiContracts()
                ->where("contracts.id", "=", Request::input("contract_id"))
                ->first();

            $present = $this->checkPractice($contract);
            if ($present) {
                if ($contract) {
                    $actions = Request::input("action_id");
                    $date = strtotime(Request::input("date"));

                    if ($date < strtotime($contract->agreement->start_date) || $date > strtotime(date('Y-m-d'))) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.date_range")
                        ]);
                    }

                    if ($date > strtotime($contract->manual_contract_end_date)) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.date_range")
                        ]);
                    }

                    if ($contract->deadline_option == '1') {
                        $contract_deadline_days_number = ContractDeadlineDays::get_contract_deadline_days($contract->id);
                        $contract_Deadline_number_string = '-' . $contract_deadline_days_number->contract_deadline_days . ' days';

                        if ($date <= strtotime($contract_Deadline_number_string)) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.log_entrybeforedays") . $contract_deadline_days_number->contract_deadline_days . " days."
                            ]);
                        }
                    } else {
                        if ($date <= strtotime('-365 days')) {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.before365days")
                            ]);
                        }
                    }

                    $log_date = mysql_date(Request::input("date"));
                    $details = Request::input("details", "");

                    $start_time = Request::input("start_time", "");
                    $end_time = Request::input("end_time", "");

                    $approvedLogsMonth = $this->getApprovedLogsMonths($contract);
                    $logdate_formatted = date_parse_from_format("Y-m-d", $log_date);
                    $save_flag = 1;
                    foreach ($approvedLogsMonth as $approvedMonth) {
                        $approvedMonth_formatted = date_parse_from_format("m-d-Y", $approvedMonth);
                        if ($approvedMonth_formatted["year"] == $logdate_formatted["year"]) {
                            if ($approvedMonth_formatted["month"] == $logdate_formatted["month"]) {
                                $save_flag = 0;
                            }
                        }
                    }
                    if ($save_flag == 1) {
                        $log = new PhysicianLog;
                        //fetch already entered logs for the physician, same contract, same date
                        $ckeckHours = $log->getHoursCheck(Request::input("contract_id"), $physician->id, $log_date, Request::input("duration"));

                        if ($ckeckHours == 'Excess 24') {
                            return Response::json([
                                "status" => self::STATUS_FAILURE,
                                "message" => Lang::get("api.log_hours_full")
                            ]);
                        }

                        $action_count = count($actions);
                        $i = 1;

                        foreach ($actions as $action) {
                            if ($ckeckHours != 'Excess annual') {
                                if ($ckeckHours != 'Excess monthly') {
//                                    $result = $log->saveLogs($action[0], 0, $details, $physician, Request::input("contract_id"), $log_date, $action[1], $physician->id, $start_time, $end_time);
                                    $save_log_factory = new PhysicianLogManager();
                                    $result = $save_log_factory->saveLog($action[0], 0, $details, $physician, Request::input("contract_id"), $log_date, $action[1], $physician->id, $start_time, $end_time);
                                    if ($result === 'Success') {
                                        if ($action_count === $i) {
                                            return Response::json([
                                                "status" => self::STATUS_SUCCESS,
                                                "message" => Lang::get("api.save")
                                            ]);
                                        }
                                        $i++;
                                    } else if ($result === 'practice_error') {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => Lang::get("api.practice_not_present")
                                        ]);
                                    } else if ($result === 'Error') {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => Lang::get("api.contract_not_exist")
                                        ]);
                                    } else {
                                        return Response::json([
                                            "status" => self::STATUS_FAILURE,
                                            "message" => "Request time out"
                                        ]);
                                    }
                                } else {
                                    return Response::json([
                                        "status" => self::STATUS_FAILURE,
                                        "message" => Lang::get("api.log_hours_full_month")
                                    ]);
                                }
                            } else {
                                return Response::json([
                                    "status" => self::STATUS_FAILURE,
                                    "message" => Lang::get("api.log_hours_full_year")
                                ]);
                            }
                        }

                    } else {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.approved_month_failure")
                        ]);
                    }
                }
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("api.authentication")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function checkAttestationsExist()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $physician = $access_token->physician_id;
        $contract_id = Request::input("contract_id");
        $date_selector = Request::input("date_selector");
        // $results = AttestationQuestion::getAttestations($physician, $contract_id, $date_selector);
        $results = AttestationQuestion::getPhysicianAttestations($physician, $contract_id, $date_selector);

        return Response::json([
            "status" => self::STATUS_SUCCESS,
            "results" => $results
        ]);
    }

    public function saveAttestations()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $physician = $access_token->physician_id;
        $contract_id = Request::input("contract_id");
        $date_selector = Request::input("date_selector");
        $questions_answer = Request::input('questions_answer');
        $dates_range = Request::input('date_range');

        $questions_answer_annually = [];
        $questions_answer_monthly = [];

        if ($questions_answer) {
            foreach ($questions_answer as $question_answer) {
                if ($question_answer['attestation_type'] == 2) {
                    $questions_answer_annually[] = [
                        "question_id" => $question_answer['question_id'],
                        "answer" => $question_answer['answer']
                    ];
                } else {
                    $questions_answer_monthly[] = [
                        "question_id" => $question_answer['question_id'],
                        "answer" => $question_answer['answer']
                    ];
                }
            }
        }

        if (count($questions_answer_annually) > 0 || count($questions_answer_monthly) > 0) {
            $results = AttestationQuestion::saveAttestations($physician, $contract_id, $date_selector, $questions_answer_annually, $questions_answer_monthly, $dates_range);
            // $results = AttestationQuestion::postAttestationQuestionsAnswer($physician, $contract_id, $date_selector, $questions_answer_annually, $questions_answer_monthly, $dates_range);
            if ($results == 1) {
                return Response::json([
                    "status" => self::STATUS_SUCCESS,
                    "message" => Lang::get('attestations.add_success')
                ]);
            } else {
                return Response::json([
                    "status" => self::STATUS_FAILURE,
                    "message" => Lang::get("attestations.add_error")
                ]);
            }
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("attestations.add_error")
        ]);
    }

    public function postNewAuthenticate()
    {
        $email = '';
        $auth = false;
        $awsCongnitoSSOToken = Request::input("awsCognitoSSOToken", null);
        $physician = null;
        $user = null;
        // if cognito token is present, validate that and don't check the password
        if ($awsCongnitoSSOToken !== null) {
            $cognitoIdentityObject = CognitoSSOService::verifyToken($awsCongnitoSSOToken);
            Log::debug('Cognito Identify Object: ', array($cognitoIdentityObject));
            // if cognito token is verified, get email from that and pull out the physician
            if ($cognitoIdentityObject->isVerified) {
                $email = $cognitoIdentityObject->email;
                $physician = Physician::where("email", "=", $email)->first();
                $auth = true;
            }
        } else {
            $email = Request::input("email");
            $password = Request::input("password");
            $physician = Physician::where("email", "=", $email)->first();

            if ($physician) {
                $user = User::where("email", "=", $physician->email)->where("group_id", "=", Group::Physicians)->first();
                if ($user) {
                    if ($user->getLocked() == 1) {
                        return Response::json([
                            "status" => self::STATUS_FAILURE,
                            "message" => Lang::get("api.account_locked")
                        ]);
                    }
                }
                // check password only if user is a physician because this API is meant for mobile login which is only for physicians
                $auth = Hash::check($password, $physician->password);
            }

        }


        if ($physician && $auth) {
            $access_token = AccessToken::generate($physician);
            $access_token->save();

            if (Request::has("deviceToken") && Request::has("deviceType")) {
                $deviceToken = Request::input("deviceToken");
                if (Request::input("deviceType") != '' && Request::input("deviceToken") != '') {
                    $device_token = PhysicianDeviceToken::where("physician_id", "=", $physician->id)->first();
                    if ($device_token) {
                        $device_token->device_token = $deviceToken;
                        $device_token->device_type = Request::input("deviceType");
                        $device_token->save();
                    } else {
                        $PhysicianDeviceToken = new PhysicianDeviceToken();
                        $PhysicianDeviceToken->physician_id = $physician->id;
                        $PhysicianDeviceToken->device_type = Request::input("deviceType");
                        $PhysicianDeviceToken->device_token = $deviceToken;
                        $PhysicianDeviceToken->save();
                    }
                }
            }

            if ($user) {
                $user->setUnsuccessfulLoginAttempts(0);
                $user->save();
            }

            $productivmd = new ProductivMDService();
            $results = $productivmd->getProviderByNPI($physician->npi);
            $firstName = "";
            $lastName = "";
            $physician_GUID = "";

            if ($results) {
                foreach ($results as $result) {
                    $firstName = $result->firstName;
                    $lastName = $result->lastName;
                    $physician_GUID = $result->id;
                }
            }

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "token" => $access_token->key,
                "physician_npi" => $physician->npi,
                "physician_name" => $firstName . " " . $lastName,
                "physician_GUID" => $physician_GUID
            ]);
        }

        //if we are here, it means login was unsuccessful so increase failure count
        if ($user) {
            $unsuccessful_login_attempts = $user->getUnsuccessfulLoginAttempts();
            $user->setUnsuccessfulLoginAttempts($unsuccessful_login_attempts + 1);
            if (($unsuccessful_login_attempts + 1) >= self::UNSUCCESSFUL_LOGIN_ATTEMPTS_LIMIT) {
                $user->setLocked(1);
            }
            $user->save();
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getPhysicianRecentLogs()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $contract_id = Request::input("contract_id");

        if ($access_token) {
            $physician = $access_token->physician;
            $contract = Contract::findOrFail($contract_id);
            $data["recent_logs"] = PhysicianLog::getRecentLogs($contract, $physician->id);
            $data['hourly_summary'] = array();
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $data['hourly_summary'] = PhysicianLog::getMonthlyHourlySummaryRecentLogs($data["recent_logs"], $contract, $physician->id);
            }

            $data['contract_id'] = $contract_id;

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $data
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getPhysicianApprovedLogs()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $contract_id = Request::input("contract_id");

        if ($access_token) {
            $physician = $access_token->physician;
            $contract = Contract::findOrFail($contract_id);
            $approve_logs = PhysicianLog::getPhysicianApprovedLogs($contract, $physician->id);
            $data['approve_logs'] = $approve_logs;
            $data['hourly_summary'] = array();
            if ($contract->payment_type_id == PaymentType::HOURLY) {
                $data['hourly_summary'] = PhysicianLog::getMonthlyHourlySummaryRecentLogs($data["approve_logs"], $contract, $physician->id);
            }
            $date_selectors = [];
            $renumbered = [];
            array_push($date_selectors, 'All');
            if (count($approve_logs) > 0) {
                foreach ($approve_logs as $log) {
                    array_push($date_selectors, $log['date_selector']);
                }
                $date_selectors = array_unique($date_selectors);
                $renumbered = array_merge($date_selectors, array());
                json_encode($renumbered);
            }
            $data['date_selectors'] = $renumbered;
            $data['contract_id'] = $contract_id;

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $data
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    private function getPhysiciansContractData($contract)
    {

        $agreement = Agreement::findOrFail($contract->agreement_id);
        $contractsInAgreement = Contract::select("contracts.*")->where('contracts.agreement_id', '=', $agreement->id)
            ->join("physicians", "physicians.id", "=", "contracts.physician_id")
            ->get();

        $contractData = [];
        foreach ($contractsInAgreement as $contractInAgreement) {
            /**
             * getContractsPerContractID currently returns recent logs
             * will update 'method name' or 'return data' as per requirement
             * required for tracking logs of other physicians in on call contract
             */
            if ($contract->id != $contractInAgreement->id) {
                $contractData[] = $this->getContractsPerContractID($contractInAgreement);
            }
        }
        return $contractData;
    }

    public function getContractsPerContractID($contract)
    {
        $physicianId = $contract->physician_id;
        $contractId = $contract->id;

        if ($physicianId) {
            $physician = Physician::findOrFail($physicianId);
            $active_contracts = $physician->contracts()
                ->select("contracts.*", "agreements.end_date as agreement_end_date", "agreements.valid_upto as agreement_valid_upto_date")
                ->join("agreements", "agreements.id", "=", "contracts.agreement_id")
                ->whereRaw("agreements.is_deleted = 0")
                ->whereRaw("agreements.start_date <= now()")
                //->whereRaw("agreements.valid_upto >= now()")
                //->whereRaw(("agreements.valid_upto >= now()") OR ("agreements.end_date >= now()"))
                ->get();

            $contracts = [];
            foreach ($active_contracts as $contract) {
                //$valid_upto=$contract->agreement_valid_upto_date;/* remove to add valid upto to contract*/
                $valid_upto = $contract->manual_contract_valid_upto;
                if ($valid_upto == '0000-00-00') {
                    //$valid_upto=$contract->agreement_end_date; /* remove to add valid upto to contract*/
                    $valid_upto = $contract->manual_contract_end_date;
                }
                $today = date('Y-m-d');
                if ($valid_upto > $today) {
                    if ($contract->id == $contractId) {
                        $data['recent_logs'] = $this->getRecentLogs($contract);
                        $contracts = [
                            "id" => $contract->id,
                            "contract_type_id" => $contract->contract_type_id,
                            "payment_type_id" => $contract->payment_type_id,
                            "name" => contract_name($contract),
                            "start_date" => format_date($contract->agreement->start_date),
                            "end_date" => format_date($contract->manual_contract_end_date),
                            "rate" => $contract->rate,
                            "recent_logs" => $data['recent_logs'],
                        ];
                    }
                }
            }
            return $contracts;
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    private function getRecentLogs($contract)
    {
        //added for get changed names
        //if($contract->contract_type_id == ContractType::ON_CALL)
        if ($contract->payment_type_id == PaymentType::PER_DIEM) {
            $changedActionNamesList = OnCallActivity::where("contract_id", "=", $contract->id)->pluck("name", "action_id");
        } else {
            $changedActionNamesList = [0 => 0];
        }
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->whereRaw("physician_logs.date >= date(now() - interval 90 day)")
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

        return $results;
    }

    private function getPriorMonthLogsData($contract)
    {
        $recent_logs = $contract->logs()
            ->select("physician_logs.*")
            ->whereRaw("physician_logs.date >= date(now() - interval 90 day)")
            ->orderBy("date", "desc")
            ->get();

        $results = [];
        $new_date = date("Y-m-d", strtotime("last day of previous month"));
        $from_date = strtotime($new_date . "-3 months");
        //echo date("Y-m-d", $from_date);

        foreach ($recent_logs as $log) {
            if ((strtotime($log->date) > strtotime(date("Y-n-j", $from_date))) && (strtotime($log->date) <= strtotime(date("Y-n-j", strtotime("last day of previous month"))))) {
                $results[] = [
                    "id" => $log->id,
                    "action" => $log->action->name,
                    "date" => format_date($log->date),
                    "duration" => formatNumber($log->duration),
                    "created" => format_date($log->created_at, "m/d/Y h:i A"),
                    "isSigned" => ($log->signature > 0) ? true : false
                ];
            }
        }

        return $results;
    }

    public function getContractDetails()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();

        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $contracts = $contract->getContractsDetailOnProfileScreen($physician, $hospital_id, 'recent');

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $contracts
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }

    public function getAllRejectedLogs()
    {
        $token = Request::input("token");
        $access_token = AccessToken::where("key", "=", $token)->first();
        $hospital_id = Request::input("hospital_id");

        if ($access_token) {
            $physician = $access_token->physician;

            if ($hospital_id == null) {
                $hospital_id = $this->getHospitalFromPhysician($physician);
            }

            $contract = new Contract();
            $rejected_logs = $contract->getAllRejectedLogs($physician, $hospital_id);

            return Response::json([
                "status" => self::STATUS_SUCCESS,
                "contracts" => $rejected_logs
            ]);
        }

        return Response::json([
            "status" => self::STATUS_FAILURE,
            "message" => Lang::get("api.authentication")
        ]);
    }
}
